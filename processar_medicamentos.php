<?php
require_once 'vendor/autoload.php';
require_once 'push_config.php';
require_once 'conexao.php';

use Twilio\Rest\Client;

// Configura o timezone para Brasília
date_default_timezone_set('America/Sao_Paulo');

// Ativa exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Função para verificar se está dentro da janela de 15 minutos
function dentroJanelaEnvio($horario_medicamento) {
    $agora = new DateTime();
    $hora_medicamento = DateTime::createFromFormat('H:i:s', $horario_medicamento);
    $limite_inicial = clone $hora_medicamento;
    $limite_inicial->modify('-15 minutes');
    
    return $agora >= $limite_inicial && $agora < $hora_medicamento;
}

// Função para formatar o número de telefone para o padrão E.164
function formatarTelefone($telefone) {
    // Remove todos os caracteres não numéricos
    $numero = preg_replace('/[^0-9]/', '', $telefone);
    
    // Se o número não começar com 55 (código do Brasil), adiciona
    if (substr($numero, 0, 2) !== '55') {
        $numero = '55' . $numero;
    }
    
    // Adiciona o + no início
    return '+' . $numero;
}

// Função para calcular os horários baseados na frequência
function calcularHorarios($horario_base, $frequencia) {
    $horarios = [];
    $horario_obj = DateTime::createFromFormat('H:i:s', $horario_base, new DateTimeZone('America/Sao_Paulo'));
    
    // Subtrai 15 minutos do horário base
    $horario_obj->modify('-15 minutes');
    
    // Adiciona o horário base (já com -15 minutos)
    $horarios[] = $horario_obj->format('H:i');
    
    // Se tiver frequência, calcula os horários adicionais
    if (!empty($frequencia)) {
        $horas_frequencia = intval($frequencia);
        if ($horas_frequencia > 0) {
            $horario_temp = clone $horario_obj;
            
            // Calcula horários adicionais baseados na frequência
            for ($i = $horas_frequencia; $i < 24; $i += $horas_frequencia) {
                $horario_temp->modify("+{$horas_frequencia} hours");
                $horarios[] = $horario_temp->format('H:i');
            }
        }
    }
    
    return $horarios;
}

// Função para registrar logs
function logDebug($mensagem) {
    echo "$mensagem\n";
    $log_file = 'debug_medicamentos.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $mensagem\n", FILE_APPEND);
}

logDebug("Script iniciado");

$data_atual = date('Y-m-d');
$hora_atual = date('H:i:s');

logDebug("Data atual: $data_atual");
logDebug("Hora atual (Brasília): $hora_atual");

// Busca medicamentos ativos
$sql = "SELECT m.*, u.nome, u.telefone 
        FROM medicamentos m 
        INNER JOIN pacientes p ON m.paciente_id = p.id 
        INNER JOIN usuarios u ON p.usuario_id = u.id 
        WHERE (m.data_fim IS NULL OR m.data_fim >= ?) 
        AND (m.data_inicio <= ?)";

logDebug("Executando SQL: $sql");

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $data_atual, $data_atual);
$stmt->execute();
$result = $stmt->get_result();

$total_registros = $result->num_rows;
logDebug("Total de medicamentos encontrados: $total_registros");

while ($row = $result->fetch_assoc()) {
    logDebug("\nProcessando medicamento: {$row['nome_medicamento']} para paciente: {$row['nome']}");
    
    // Verifica se já foi enviado hoje
    $sql_check = "SELECT id FROM logs_medicamentos 
                 WHERE medicamento_id = ? 
                 AND DATE(horario_envio) = CURRENT_DATE 
                 AND status = 'enviado'";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $row['id']);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        logDebug("SMS já enviado hoje para este medicamento");
        continue;
    }
    
    // Verifica se está dentro da janela de 15 minutos
    if (dentroJanelaEnvio($row['horario'])) {
        logDebug("Dentro da janela de envio para o horário: {$row['horario']}");
        
        try {
            // Calcula quantos minutos faltam
            $agora = new DateTime();
            $hora_med = DateTime::createFromFormat('H:i:s', $row['horario']);
            $diff = $agora->diff($hora_med);
            $minutos_restantes = ($diff->h * 60) + $diff->i;
            
            // Formata o número de telefone
            $telefone_formatado = formatarTelefone($row['telefone']);
            
            // Prepara a mensagem
            $mensagem = "Lembrete de medicação\n\n";
            $mensagem .= "{$row['nome']}, você precisará tomar sua medicação {$row['nome_medicamento']} ";
            if ($minutos_restantes > 1) {
                $mensagem .= "em {$minutos_restantes} minutos ";
            } else {
                $mensagem .= "em 1 minuto ";
            }
            $mensagem .= "(às " . $hora_med->format('H:i') . ")";
            
            // Adiciona a dosagem se estiver disponível
            if (!empty($row['dosagem'])) {
                $mensagem .= "\nDosagem: {$row['dosagem']}";
            }
            
            logDebug("Mensagem preparada: $mensagem");
            logDebug("Telefone original: {$row['telefone']}");
            logDebug("Telefone formatado: $telefone_formatado");
            
            
            // Cria uma instância do cliente Twilio
            $client = new Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);

            // Envia o SMS
            $message = $client->messages->create(
                $telefone_formatado,
                [
                    'from' => TWILIO_PHONE_NUMBER,
                    'body' => $mensagem
                ]
            );

            logDebug("SMS enviado com sucesso! SID: " . $message->sid);
            
            
            // Salva o log no banco de dados
            $sql_log = "INSERT INTO logs_medicamentos (medicamento_id, horario_envio, status, mensagem) 
                       VALUES (?, NOW(), 'enviado', ?)";
            $stmt_log = $conn->prepare($sql_log);
            $stmt_log->bind_param("is", $row['id'], $mensagem);
            
            if ($stmt_log->execute()) {
                logDebug("Log salvo com sucesso no banco de dados");
            } else {
                logDebug("Erro ao salvar log no banco de dados: " . $stmt_log->error);
            }

        } catch (Exception $e) {
            $erro = $e->getMessage();
            logDebug("ERRO ao processar: $erro");
            
            // Salva o log de erro no banco
            $sql_log = "INSERT INTO logs_medicamentos (medicamento_id, horario_envio, status, mensagem) 
                       VALUES (?, NOW(), 'erro', ?)";
            $stmt_log = $conn->prepare($sql_log);
            $stmt_log->bind_param("is", $row['id'], $erro);
            $stmt_log->execute();
        }
    } else {
        logDebug("Fora da janela de envio para o horário: {$row['horario']}");
    }
}

logDebug("\nScript finalizado"); 