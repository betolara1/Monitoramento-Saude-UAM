<?php
include 'conexao.php';
include 'verificar_login.php';

$response = ['success' => false, 'message' => '', 'profissional' => null];

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $sql = "SELECT * FROM profissionais WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($profissional = $result->fetch_assoc()) {
            $response['success'] = true;
            $response['profissional'] = $profissional;
        } else {
            $response['message'] = "Profissional não encontrado";
        }
    } else {
        $response['message'] = "Erro ao buscar profissional";
    }
} else {
    $response['message'] = "ID não fornecido";
}

echo json_encode($response);

