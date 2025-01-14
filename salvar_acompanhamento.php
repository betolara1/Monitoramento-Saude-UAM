<?php
// Habilitar exibição de erros para debug
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Configurar log de erros
error_log("=== Início do processamento de acompanhamento ===");

try {
    session_start();
    require_once 'conexao.php';

    header('Content-Type: application/json');

    // Verificações de autenticação
    if (!isset($_SESSION['tipo_usuario']) || 
        ($_SESSION['tipo_usuario'] !== 'Admin' && $_SESSION['tipo_usuario'] !== 'Profissional')) {
        throw new Exception('Acesso não autorizado');
    }

    // Validar dados obrigatórios
    if (empty($_POST['paciente_id'])) {
        throw new Exception('ID do paciente não fornecido');
    }

    if (empty($_POST['data_acompanhamento'])) {
        throw new Exception('Data do acompanhamento não fornecida');
    }

    // Preparar dados
    $acompanhamento_id = !empty($_POST['acompanhamento_id']) ? intval($_POST['acompanhamento_id']) : null;
    $paciente_id = intval($_POST['paciente_id']);
    $data_acompanhamento = $_POST['data_acompanhamento'];
    $pressao_arterial = !empty($_POST['pressao_arterial']) ? $_POST['pressao_arterial'] : null;
    $glicemia = !empty($_POST['glicemia']) ? $_POST['glicemia'] : null;
    $peso = !empty($_POST['peso']) ? str_replace(',', '.', $_POST['peso']) : null;
    $altura = !empty($_POST['altura']) ? str_replace(',', '.', $_POST['altura']) : null;
    $habitos_de_vida = !empty($_POST['habitos_de_vida']) ? $_POST['habitos_de_vida'] : null;
    $emocao = !empty($_POST['emocao']) ? $_POST['emocao'] : null;

    // Calcular IMC
    $imc = null;
    if ($peso && $altura) {
        $altura_metros = $altura / 100;
        $imc = $peso / ($altura_metros * $altura_metros);
    }

    // Definir query baseada na presença do acompanhamento_id
    if ($acompanhamento_id) {
        // UPDATE
        $query = "UPDATE historico_acompanhamento SET 
                    data_acompanhamento = ?,
                    pressao_arterial = ?,
                    glicemia = ?,
                    peso = ?,
                    altura = ?,
                    imc = ?,
                    habitos_de_vida = ?,
                    emocao = ?
                 WHERE id = ? AND paciente_id = ?";
                 
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Erro ao preparar query de atualização: " . $conn->error);
        }

        $stmt->bind_param("sssdddssii", 
            $data_acompanhamento,
            $pressao_arterial,
            $glicemia,
            $peso,
            $altura,
            $imc,
            $habitos_de_vida,
            $emocao,
            $acompanhamento_id,
            $paciente_id
        );
    } else {
        // INSERT
        $query = "INSERT INTO historico_acompanhamento 
                  (paciente_id, data_acompanhamento, pressao_arterial, glicemia, 
                   peso, altura, imc, habitos_de_vida, emocao) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                  
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Erro ao preparar query de inserção: " . $conn->error);
        }

        $stmt->bind_param("isssdddss", 
            $paciente_id,
            $data_acompanhamento,
            $pressao_arterial,
            $glicemia,
            $peso,
            $altura,
            $imc,
            $habitos_de_vida,
            $emocao
        );
    }

    // Executar
    if (!$stmt->execute()) {
        throw new Exception("Erro ao executar query: " . $stmt->error);
    }

    // Retornar sucesso
    echo json_encode([
        'success' => true,
        'message' => $acompanhamento_id ? 'Acompanhamento atualizado com sucesso' : 'Acompanhamento salvo com sucesso',
        'id' => $acompanhamento_id ?: $conn->insert_id
    ]);

} catch (Exception $e) {
    error_log("ERRO: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar: ' . $e->getMessage()
    ]);
}

error_log("=== Fim do processamento ===");
?> 