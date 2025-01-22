<?php
// Verifica se a sessão já está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário é Admin
function estaLogado() {
    return isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] === 'Admin';
}

// Verifica se o usuário não é Admin e redireciona para a página de login
function requireLogin() {
    if (!estaLogado()) {
        header("Location: index.php");
        exit();
    }
}

// Verifica permissão específica para Admin
function verificarPermissao() {
    requireLogin();
    
    if ($_SESSION['tipo_usuario'] !== 'Admin') {
        header("Location: acesso_negado.php");
        exit();
    }
}
?> 