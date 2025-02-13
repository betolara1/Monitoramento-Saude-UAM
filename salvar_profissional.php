<?php
include "conexao.php";
include 'verificar_login.php';

$response = ['success' => false, 'message' => ''];

try {
    $usuario_id = $_POST['usuario_id'];
    $especialidade = $_POST['especialidade'];
    $unidade_saude = $_POST['unidade_saude'];
    $registro_profissional = $_POST['registro_profissional'] === 'null' ? null : $_POST['registro_profissional'];
    $micro_area = isset($_POST['micro_area']) ? $_POST['micro_area'] : null;

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
        // Iniciar transação
        $conn->begin_transaction();

        try {
            // Inserir na tabela profissionais
            $sql = "INSERT INTO profissionais (usuario_id, especialidade, registro_profissional, unidade_saude) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $usuario_id, $especialidade, $registro_profissional, $unidade_saude);
            $stmt->execute();
            
            // Se for ACS e tiver micro área selecionada, atualizar na tabela usuarios
            $checkUserSql = "SELECT tipo_usuario FROM usuarios WHERE id = ?";
            $checkUserStmt = $conn->prepare($checkUserSql);
            $checkUserStmt->bind_param("i", $usuario_id);
            $checkUserStmt->execute();
            $result = $checkUserStmt->get_result();
            $user = $result->fetch_assoc();

            if ($user['tipo_usuario'] === 'ACS' && !empty($micro_area)) {
                $updateSql = "UPDATE usuarios SET micro_area = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("si", $micro_area, $usuario_id);
                $updateStmt->execute();
            }

            // Commit da transação
            $conn->commit();
            
            $response['success'] = true;
            $response['profissional_id'] = $conn->insert_id;
            $response['message'] = "Profissional cadastrado com sucesso!";
            
        } catch (Exception $e) {
            // Rollback em caso de erro
            $conn->rollback();
            throw $e;
        }
    }
} catch (Exception $e) {
    $response['message'] = "Erro: " . $e->getMessage();
}

echo json_encode($response);