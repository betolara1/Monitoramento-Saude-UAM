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
                        observacoes,
                        profissional_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = ["isssssssssi", $paciente_id, $data_consulta, $pressao_arterial, $glicemia, $peso, $altura, $imc, $estado_emocional, $habitos_vida, $observacoes, $profissional_id];
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