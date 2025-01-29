<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

// Verifica permissão
if (!isset($_SESSION['tipo_usuario']) || 
    !in_array($_SESSION['tipo_usuario'], ['Admin', 'Medico', 'Enfermeiro'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    try {
        // Captura o paciente_id e verifica se o paciente existe
        $paciente_id = intval($_POST['paciente_id']);

        // Verifica se o paciente_id é válido
        if ($paciente_id <= 0) {
            throw new Exception("Paciente ID inválido: $paciente_id");
        }

        $sql_check_paciente = "SELECT id FROM pacientes WHERE id = ?";
        $stmt_check = $conn->prepare($sql_check_paciente);
        $stmt_check->bind_param("i", $paciente_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows === 0) {
            throw new Exception("Paciente ID $paciente_id não encontrado na tabela pacientes");
        }

        // Obtém os dados da consulta
        $consulta_id = !empty($_POST['consulta_id']) ? intval($_POST['consulta_id']) : null;
        $data_consulta = $_POST['data_consulta'];
        $pressao_arterial = $_POST['pressao_arterial'] ?? null;
        $glicemia = $_POST['glicemia'] ?? null;
        $peso = !empty($_POST['peso']) ? str_replace(',', '.', $_POST['peso']) : null;
        $altura = !empty($_POST['altura']) ? $_POST['altura'] : null;
        $imc = !empty($_POST['imc']) ? str_replace(',', '.', $_POST['imc']) : null;
        $estado_emocional = $_POST['estado_emocional'] ?? null;
        $habitos_vida = $_POST['habitos_vida'] ?? null;
        $observacoes = $_POST['observacoes'] ?? null;
        $profissional_id = $_POST['profissional_id'] ?? null; // Use o operador null coalescing para evitar erros

        if (!$profissional_id) {
            echo json_encode(['success' => false, 'message' => 'Erro: Profissional não selecionado.']);
            exit;
        }

        // Verifique se o profissional existe na tabela
        $query_profissional = "SELECT * FROM profissionais WHERE id = ?";
        $stmt = $conn->prepare($query_profissional);
        $stmt->bind_param("i", $profissional_id);
        $stmt->execute();
        $result_profissional = $stmt->get_result();

        if ($result_profissional->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => "Erro: Profissional com usuario_id {$profissional_id} não encontrado na tabela profissionais."]);
            exit;
        }

        // Função para preparar e executar a consulta
        function executeQuery($conn, $query, $params) {
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Erro na preparação da query: " . $conn->error);
            }
            $stmt->bind_param(...$params);
            if (!$stmt->execute()) {
                throw new Exception('Erro ao executar query: ' . $stmt->error);
            }
            return $stmt;
        }

        // Verifica se é uma atualização ou inserção
        if ($consulta_id) {
            // Atualização
            $query = "UPDATE consultas SET 
                        data_consulta = ?, 
                        pressao_arterial = ?, 
                        glicemia = ?, 
                        peso = ?, 
                        altura = ?, 
                        imc = ?, 
                        estado_emocional = ?, 
                        habitos_vida = ?, 
                        observacoes = ?, 
                        profissional_id = ?
                     WHERE id = ? AND paciente_id = ?";
            $params = ["sssssssssiii", $data_consulta, $pressao_arterial, $glicemia, $peso, $altura, $imc, $estado_emocional, $habitos_vida, $observacoes, $profissional_id, $consulta_id, $paciente_id];
            executeQuery($conn, $query, $params);
        } else {
            // Calcular IMC
            if ($peso > 0 && $altura > 0) {
                $altura_metros = $altura / 100; // Converter cm para metros
                $imc = $peso / ($altura_metros * $altura_metros);
                $imc = round($imc, 1); // Arredondar para uma casa decimal

                // Classificação do IMC
                $classificacao_imc = '';
                if ($imc < 18.5) $classificacao_imc = 'Abaixo do peso';
                else if ($imc < 25) $classificacao_imc = 'Peso normal';
                else if ($imc < 30) $classificacao_imc = 'Sobrepeso';
                else if ($imc < 35) $classificacao_imc = 'Obesidade Grau I';
                else if ($imc < 40) $classificacao_imc = 'Obesidade Grau II';
                else $classificacao_imc = 'Obesidade Grau III';
            } else {
                $imc = null;
                $classificacao_imc = null;
            }

            // Inserção
            $query = "INSERT INTO consultas (
                        paciente_id, 
                        data_consulta, 
                        pressao_arterial, 
                        glicemia, 
                        peso, 
                        altura, 
                        imc, 
                        classificacao_imc, 
                        estado_emocional, 
                        habitos_vida, 
                        observacoes,
                        profissional_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = ["issssssssssi", $paciente_id, $data_consulta, $pressao_arterial, $glicemia, $peso, $altura, $imc, $classificacao_imc, $estado_emocional, $habitos_vida, $observacoes, $profissional_id];
            executeQuery($conn, $query, $params);
        }

        // Após salvar a consulta com sucesso, gerar análise automaticamente
        $query_ultimas_consultas = "SELECT pressao_arterial, glicemia, data_consulta 
                                  FROM consultas 
                                  WHERE paciente_id = ? 
                                  ORDER BY data_consulta DESC 
                                  LIMIT 2";
        
        $stmt = $conn->prepare($query_ultimas_consultas);
        $stmt->bind_param("i", $paciente_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $consultas = $result->fetch_all(MYSQLI_ASSOC);

        // Gerar comparativos
        $comparativo_pa = "Primeira medição";
        $comparativo_glicemia = "Primeira medição";
        
        if (count($consultas) >= 2) {
            // Comparar Pressão Arterial
            if ($consultas[0]['pressao_arterial'] && $consultas[1]['pressao_arterial']) {
                $pa_atual = explode('/', $consultas[0]['pressao_arterial'])[0]; // Pega sistólica atual
                $pa_anterior = explode('/', $consultas[1]['pressao_arterial'])[0]; // Pega sistólica anterior
                
                if ($pa_atual < $pa_anterior) {
                    $comparativo_pa = "Melhorou";
                } elseif ($pa_atual > $pa_anterior) {
                    $comparativo_pa = "Piorou";
                } else {
                    $comparativo_pa = "Estável";
                }
            }

            // Comparar Glicemia
            if ($consultas[0]['glicemia'] && $consultas[1]['glicemia']) {
                $glicemia_atual = intval($consultas[0]['glicemia']);
                $glicemia_anterior = intval($consultas[1]['glicemia']);
                
                if ($glicemia_atual < $glicemia_anterior) {
                    $comparativo_glicemia = "Melhorou";
                } elseif ($glicemia_atual > $glicemia_anterior) {
                    $comparativo_glicemia = "Piorou";
                } else {
                    $comparativo_glicemia = "Estável";
                }
            }
        }

        // Inserir nova análise
        $data_analise = date('Y-m-d');
        $query_analise = "INSERT INTO analises_estatisticas 
                         (paciente_id, data_analise, comparativo_pa, 
                          comparativo_glicemia, comparativo_risco_cardio) 
                         VALUES (?, ?, ?, ?, NULL)";
        
        $stmt = $conn->prepare($query_analise);
        $stmt->bind_param("isss", 
            $paciente_id,
            $data_analise,
            $comparativo_pa,
            $comparativo_glicemia
        );
        $stmt->execute();

        echo json_encode([
            'success' => true, 
            'message' => 'Consulta e análise salvas com sucesso!'
        ]);

    } catch (Exception $e) {
        // Retorna erro como JSON
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?> 