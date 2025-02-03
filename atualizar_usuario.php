<?php
include 'conexao.php';
include 'verificar_login.php';

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $usuario_id = $_POST['usuario_id'];
        
        $sql = "UPDATE usuarios SET 
                nome = ?,
                cpf = ?,
                email = ?,
                telefone = ?,
                cep = ?,
                rua = ?,
                numero = ?,
                bairro = ?,
                cidade = ?,
                estado = ?,
                complemento = ?
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssi",
            $_POST['nome'],
            $_POST['cpf'],
            $_POST['email'],
            $_POST['telefone'],
            $_POST['cep'],
            $_POST['rua'],
            $_POST['numero'],
            $_POST['bairro'],
            $_POST['cidade'],
            $_POST['estado'],
            $_POST['complemento'],
            $usuario_id
        );
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Usuário atualizado com sucesso!';
        } else {
            throw new Exception("Erro ao atualizar usuário");
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Erro: ' . $e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode($response); 