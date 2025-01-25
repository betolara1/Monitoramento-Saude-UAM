<?php
include "conexao.php";
include 'verificar_login.php';

$response = ['success' => false, 'message' => ''];

try {
    $usuario_id = $_POST['usuario_id'];
    $especialidade = $_POST['especialidade'];
    $unidade_saude = $_POST['unidade_saude'];
    $registro_profissional = $_POST['registro_profissional'] === 'null' ? null : $_POST['registro_profissional'];

    // Verificar se o registro já existe
    $checkSql = "SELECT COUNT(*) FROM profissionais WHERE usuario_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $usuario_id);
    $checkStmt->execute();
    $checkStmt->bind_result($count);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($count > 0) {
        $response['message'] = "Registro já existe para este usuário e unidade de saúde.";
    } else {
        $sql = "INSERT INTO profissionais (usuario_id, especialidade, registro_profissional, unidade_saude) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $usuario_id, $especialidade, $registro_profissional, $unidade_saude);
        
        if($stmt->execute()) {
            $response['success'] = true;
            $response['profissional_id'] = $conn->insert_id;
            $response['message'] = "Profissional cadastrado com sucesso!";
        } else {
            $response['message'] = "Erro ao salvar no banco de dados";
        }
    }
} catch (Exception $e) {
    $response['message'] = "Erro: " . $e->getMessage();
}

echo json_encode($response);