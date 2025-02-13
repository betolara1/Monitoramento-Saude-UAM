<?php
include 'conexao.php';
include 'verificar_login.php';

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $usuario_id = $_POST['usuario_id'];
        
        // Verifica se o usuário atual tem permissão para atualizar a micro área
        $is_admin = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'Admin';
        $is_medico = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'Medico';
        $is_enfermeiro = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'Enfermeiro';
        $is_acs = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'ACS';
        
        // Base SQL query
        $sql = "UPDATE usuarios SET 
                nome = ?,
                cpf = ?,
                email = ?,
                telefone = ?,
                numero_familia = ?,
                cep = ?,
                rua = ?,
                numero = ?,
                bairro = ?,
                cidade = ?,
                estado = ?,
                complemento = ?";
        
        $params = [
            $_POST['nome'],
            $_POST['cpf'],
            $_POST['email'],
            $_POST['telefone'],
            $_POST['numero_familia'],
            $_POST['cep'],
            $_POST['rua'],
            $_POST['numero'],
            $_POST['bairro'],
            $_POST['cidade'],
            $_POST['estado'],
            $_POST['complemento']
        ];
        $types = "ssssssssssss";
        
        // Adiciona o campo micro_area_id se o usuário tiver permissão
        if ($is_admin || $is_medico || $is_enfermeiro || $is_acs) {
            $sql .= ", micro_area_id = ?";
            $params[] = $_POST['micro_area_id'];
            $types .= "i";
        }
        
        // Adiciona a condição WHERE
        $sql .= " WHERE id = ?";
        $params[] = $usuario_id;
        $types .= "i";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
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