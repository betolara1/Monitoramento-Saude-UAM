<?php
include 'conexao.php';

if(isset($_POST['cpf'])) {
    $cpf = $_POST['cpf'];
    
    // Remove caracteres não numéricos do CPF
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE cpf = ?");
    $stmt->bind_param("s", $cpf);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        echo 'existe';
    } else {
        echo 'disponivel';
    }
}
?> 