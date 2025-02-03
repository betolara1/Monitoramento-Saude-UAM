<?php
include 'conexao.php';
include 'verificar_login.php';

$response = ['success' => false];

if (isset($_POST['usuario_id'])) {
    $usuario_id = $_POST['usuario_id'];
    
    $sql = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($usuario = $result->fetch_assoc()) {
            $response['success'] = true;
            $response['data'] = $usuario;
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response); 