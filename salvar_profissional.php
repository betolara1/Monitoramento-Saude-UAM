<?php
include "conexao.php";
include 'verificar_login.php';

$response = ['success' => false, 'message' => ''];

try {
    $usuario_id = $_POST['usuario_id'];
    $especialidade = $_POST['especialidade'];
    $unidade_saude = $_POST['unidade_saude'];
    $registro_profissional = $_POST['registro_profissional'] === 'null' ? null : $_POST['registro_profissional'];

    $sql = "INSERT INTO profissionais (usuario_id, especialidade, registro_profissional, unidade_saude) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $usuario_id, $especialidade, $registro_profissional, $unidade_saude);
    
    if($stmt->execute()) {
        $response['success'] = true;
    } else {
        $response['message'] = "Erro ao salvar no banco de dados";
    }
} catch (Exception $e) {
    $response['message'] = "Erro: " . $e->getMessage();
}

echo json_encode($response);