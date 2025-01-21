<?php
include "conexao.php";
include 'verificar_login.php';

$response = ['success' => false, 'message' => ''];

try {
    $profissional_id = $_POST['profissional_id'];
    $especialidade = $_POST['especialidade'];
    $unidade_saude = $_POST['unidade_saude'];
    $registro_profissional = $_POST['registro_profissional'] === 'null' ? null : $_POST['registro_profissional'];

    $sql = "UPDATE profissionais SET 
            especialidade = ?, 
            registro_profissional = ?, 
            unidade_saude = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $especialidade, $registro_profissional, $unidade_saude, $profissional_id);
    
    if($stmt->execute()) {
        $response['success'] = true;
    } else {
        $response['message'] = "Erro ao atualizar no banco de dados";
    }
} catch (Exception $e) {
    $response['message'] = "Erro: " . $e->getMessage();
}

echo json_encode($response);