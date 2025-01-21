<?php
include 'conexao.php';
include 'verificar_login.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    try {
        $conn->begin_transaction();
        
        $paciente_id = $_POST['paciente_id'];
        $profissional_id = $_POST['profissional_id'];
        $tipo_profissional = $_POST['tipo_profissional'];
        
        // Primeiro verifica se já existe uma atribuição para este tipo de profissional
        $sql_check = "SELECT id FROM paciente_profissional 
                     WHERE paciente_id = ? AND tipo_profissional = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("is", $paciente_id, $tipo_profissional);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        if ($result->num_rows > 0) {
            // Se existe, atualiza
            $sql = "UPDATE paciente_profissional 
                   SET profissional_id = ?
                   WHERE paciente_id = ? AND tipo_profissional = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $profissional_id, $paciente_id, $tipo_profissional);
        } else {
            // Se não existe, insere
            $sql = "INSERT INTO paciente_profissional 
                   (paciente_id, profissional_id, tipo_profissional) 
                   VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $paciente_id, $profissional_id, $tipo_profissional);
        }
        
        if ($stmt->execute()) {
            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Profissional atribuído com sucesso!';
        } else {
            throw new Exception("Erro ao atribuir: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $response['success'] = false;
        $response['message'] = 'Erro: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}
?> 