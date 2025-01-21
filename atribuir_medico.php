<?php
include 'conexao.php';
include 'verificar_login.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    try {
        $paciente_id = $_POST['paciente_id'];
        $profissional_id = $_POST['profissional_id'];
        $tipo_profissional = $_POST['tipo_profissional'];
        
        $sql = "UPDATE paciente_profissional 
                SET profissional_id = ?,
                    tipo_profissional = ?
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $profissional_id, $tipo_profissional, $paciente_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Profissional atribuído com sucesso!';
        } else {
            throw new Exception("Erro ao atribuir: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = 'Erro: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}
?>