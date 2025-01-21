<?php
include 'conexao.php';
include 'verificar_login.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    try {
        $paciente_id = $_POST['paciente_id'];
        $tipo_doenca = $_POST['tipo_doenca'];
        $historico_familiar = $_POST['historico_familiar'];
        $estado_civil = $_POST['estado_civil'];
        $profissao = $_POST['profissao'];

        $sql = "UPDATE pacientes SET 
                tipo_doenca = ?,
                historico_familiar = ?,
                estado_civil = ?,
                profissao = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", 
            $tipo_doenca, 
            $historico_familiar, 
            $estado_civil, 
            $profissao, 
            $paciente_id
        );

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Informações atualizadas com sucesso!';
        } else {
            throw new Exception("Erro ao atualizar: " . $stmt->error);
        }

    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = 'Erro: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit;
}
?> 