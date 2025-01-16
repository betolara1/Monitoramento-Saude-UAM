<?php
include "conexao.php";

// Verifica se o usuário está logado e seu tipo
$is_logged = isset($_SESSION['tipo_usuario']);
$is_admin = $is_logged && $_SESSION['tipo_usuario'] === 'Admin';
$is_profissional = $is_logged && $_SESSION['tipo_usuario'] === 'Profissional';
$is_paciente = $is_logged && $_SESSION['tipo_usuario'] === 'Paciente';
?>

<!DOCTYPE html> 
<html lang="pt-br"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    
    <style> 
        body { margin: 0; font-family: Arial, sans-serif; } 
        .top-sidebar { background-color: #333; overflow: hidden; display: flex; justify-content: space-around; align-items: center; padding: 10px 0; } 
        .top-sidebar a { color: white; padding: 14px 20px; text-decoration: none; text-align: center; } 
        .top-sidebar a:hover { background-color: #ddd; color: black; } 
    </style> 
    
</head> 
<body> 
    <div class="top-sidebar"> 
        <!-- Home disponível para todos -->
        <a href="index.php">Home</a> 

        <!-- Cadastro disponível apenas para deslogados -->
        <?php if (!$is_logged): ?>
            <a href="cadastro_usuario.php">Cadastrar Usuario</a>
        <?php endif; ?>

        <!-- Opções para Admin e Profissional -->
        <?php if ($is_admin || $is_profissional): ?>
            <a href="cadastro_usuario.php">Cadastrar Usuario</a>
            <a href="listar_profissionais.php">Profissionais</a>
            <a href="listar_pacientes.php">Pacientes</a>
        <?php endif; ?>

        <!-- Opção exclusiva para Paciente -->
        <?php if ($is_paciente): ?>
            <a href="listar_pacientes.php">Paciente</a>
        <?php endif; ?>

        <!-- Logout apenas para usuários logados -->
        <?php if ($is_logged): ?>
            <a href="logout.php">Sair</a> 
        <?php endif; ?>

        <!-- Opção exclusiva para Admin -->
        <?php if ($is_admin): ?>
            <a href="visualizar_logs.php">Logs de Acesso</a>
        <?php endif; ?>
    </div>
    <br>
</body> 
</html>