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
        $paciente_id = isset($_POST['paciente_id']) ? intval($_POST['paciente_id']) : 0;

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
                        observacoes = ? 
                     WHERE id = ? AND paciente_id = ?";
            $params = ["ssssssssii", $data_consulta, $pressao_arterial, $glicemia, $peso, $altura, $imc, $estado_emocional, $habitos_vida, $observacoes, $consulta_id, $paciente_id];
            executeQuery($conn, $query, $params);
        } else {
            // Inserção
            $query = "INSERT INTO consultas (
                        paciente_id, 
                        data_consulta, 
                        pressao_arterial, 
                        glicemia, 
                        peso, 
                        altura, 
                        imc, 
                        estado_emocional, 
                        habitos_vida, 
                        observacoes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = ["issssssss", $paciente_id, $data_consulta, $pressao_arterial, $glicemia, $peso, $altura, $imc, $estado_emocional, $habitos_vida, $observacoes];
            executeQuery($conn, $query, $params);
        }

        echo json_encode(['success' => true, 'message' => 'Consulta salva com sucesso!']);

    } catch (Exception $e) {
        // Retorna erro como JSON
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?> 