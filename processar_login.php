<?php
session_start();
include 'conexao.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $senha = $_POST['senha'];
    
    $sql = "SELECT id, nome, senha, tipo_usuario FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        
        if (password_verify($senha, $usuario['senha'])) {
            // Remove a verificação de Admin e permite qualquer tipo de usuário
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nome'] = $usuario['nome'];
            $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
            
            // Registra o log de acesso
            $ip = $_SERVER['REMOTE_ADDR'];
            $sql_log = "INSERT INTO logs_acesso (usuario_id, acao, endereco_ip) VALUES (?, 'login', ?)";
            $stmt_log = $conn->prepare($sql_log);
            $stmt_log->bind_param("is", $usuario['id'], $ip);
            $stmt_log->execute();
            
            // Redireciona baseado no tipo de usuário
            switch($usuario['tipo_usuario']) {
                case 'Admin':
                    header("Location: index.php");
                    break;
                case 'Profissional':
                    header("Location: index.php");
                    break;
                case 'Paciente':
                    header("Location: index.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        }
    }
    
    // Login falhou
    header("Location: index.php?erro=1");
    exit();
}

$conn->close();
?>