<?php
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validação do tipo de usuário
    $tipo_usuario = isset($_POST['tipo_usuario']) ? $_POST['tipo_usuario'] : "Paciente";
    
    // Se estiver vazio mesmo após o isset, define como "Paciente"
    if(empty($tipo_usuario)) {
        $tipo_usuario = "Paciente";
    }

    // Hash da senha
    $senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    // Determina o valor do sexo baseado na seleção
    $sexo = $_POST['sexo'];
    if ($sexo === 'Outros' && !empty($_POST['outro_genero'])) {
        $sexo = $_POST['outro_genero'];
    }

    $micro_area = null;
    if (isset($_POST['micro_area'])) {
        $micro_area = $_POST['micro_area'];
    }

    // Prepara a query SQL com todas as colunas necessárias
    $sql = "INSERT INTO usuarios (
        nome, 
        cpf,
        email, 
        senha, 
        tipo_usuario, 
        numero_familia,
        telefone, 
        cep, 
        rua, 
        numero, 
        complemento, 
        bairro, 
        cidade, 
        estado,
        data_nascimento,
        sexo,
        micro_area
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    // Bind dos parâmetros na mesma ordem das colunas
    $stmt->bind_param("sssssssssssssssss", 
        $_POST['nome'],
        $_POST['cpf'],
        $_POST['email'],
        $senha_hash,
        $tipo_usuario,
        $_POST['numero_familia'],
        $_POST['telefone'],
        $_POST['cep'],
        $_POST['rua'],
        $_POST['numero'],
        $_POST['complemento'],
        $_POST['bairro'],
        $_POST['cidade'],
        $_POST['estado'],
        $_POST['data_nascimento'],
        $sexo,
        $micro_area
    );
    
    if ($stmt->execute()) {
        echo "<script>window.location.href='cadastro_usuario.php';</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar usuario: " . $stmt->error . "'); window.location.href='cadastro_usuario.php';</script>";
    }
    
    $conn->close();
}
?>