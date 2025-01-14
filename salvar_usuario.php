<?php
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validação do tipo de usuário
    $tipo_usuario = $_POST['tipo_usuario'];

    
    // Hash da senha
    $senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    // Prepara a query SQL com todas as colunas necessárias
    $sql = "INSERT INTO usuarios (
        nome, 
        email, 
        senha, 
        tipo_usuario, 
        telefone, 
        cep, 
        rua, 
        numero, 
        complemento, 
        bairro, 
        cidade, 
        estado,
        data_nascimento,
        sexo
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    // Bind dos parâmetros na mesma ordem das colunas
    $stmt->bind_param("ssssssssssssss", 
        $_POST['nome'],
        $_POST['email'],
        $senha_hash,
        $_POST['tipo_usuario'],
        $_POST['telefone'],
        $_POST['cep'],
        $_POST['rua'],
        $_POST['numero'],
        $_POST['complemento'],
        $_POST['bairro'],
        $_POST['cidade'],
        $_POST['estado'],
        $_POST['data_nascimento'],
        $_POST['sexo']
    );
    
    if ($stmt->execute()) {
        header("Location: index.php");
    } else {
        header("Location: index.php");
    }
    
    $conn->close();
}
?>