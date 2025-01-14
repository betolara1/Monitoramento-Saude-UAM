<?php
session_start();
include 'conexao.php';

// Registra o logout no log de acesso
if (isset($_SESSION['usuario_id'])) {
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $sql_log = "INSERT INTO logs_acesso (usuario_id, acao, endereco_ip) VALUES (?, 'logout', ?)";
    $stmt_log = $conn->prepare($sql_log);
    $stmt_log->bind_param("is", $_SESSION['usuario_id'], $ip);
    $stmt_log->execute();
    
    $conn->close();
}

// Destroi todas as variáveis de sessão
$_SESSION = array();

// Destroi a sessão
session_destroy();

// Redireciona para a página de login
header("Location: index.php");
exit();
?> 