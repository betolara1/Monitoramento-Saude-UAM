<?php
include 'conexao.php';

if(isset($_POST['email'])) {
    $email = $_POST['email'];
    
    $sql = "SELECT id FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $response = array();
    if($result->num_rows > 0) {
        $response['disponivel'] = false;
        $response['mensagem'] = "Este e-mail já está cadastrado. Por favor, use outro e-mail.";
    } else {
        $response['disponivel'] = true;
        $response['mensagem'] = "E-mail disponível!";
    }
    
    echo json_encode($response);
}
?> 