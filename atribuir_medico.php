<?php
include 'conexao.php';
include 'verificar_login.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    try {
        $paciente_id = $_POST['paciente_id'];
        $profissional_id = $_POST['profissional_id'];
        if (isset($_POST['tipo_profissional'])) {
            $tipo_profissional = $_POST['tipo_profissional'];
        } else {
            throw new Exception("Tipo de profissional não fornecido");
        }
        
        $sql = "INSERT INTO paciente_profissional (paciente_id, profissional_id, tipo_profissional) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $paciente_id, $profissional_id, $tipo_profissional);
        
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