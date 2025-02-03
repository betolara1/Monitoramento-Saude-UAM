<?php
include "conexao.php";

// Verifica se o usuário está logado e seu tipo
$is_logged = isset($_SESSION['tipo_usuario']);
$is_admin = $is_logged && $_SESSION['tipo_usuario'] === 'Admin';
$is_medico = $is_logged && $_SESSION['tipo_usuario'] === 'Medico';
$is_enfermeiro = $is_logged && $_SESSION['tipo_usuario'] === 'Enfermeiro';
$is_acs = $is_logged && $_SESSION['tipo_usuario'] === 'ACS';
$is_paciente = $is_logged && $_SESSION['tipo_usuario'] === 'Paciente';
?>

<!DOCTYPE html> 
<html lang="pt-br"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style> 
        .navbar {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1rem;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-link {
            color: white !important;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            opacity: 0.9;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            opacity: 1;
        }

        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            opacity: 1;
        }

        .nav-link i {
            font-size: 1.1rem;
            color: white;
        }

        .nav-link span {
            color: white;
        }

        .logout-btn {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        a[href^="cadastro"],
        a[href^="listar"],
        a[href^="visualizar"] {
            color: white !important;
        }

        @media (max-width: 768px) {
            .navbar-container {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style> 
</head> 
<body> 
    <nav class="navbar">
        <div class="navbar-container">
            <div class="nav-links">

                <?php if ($is_logged): ?>
                    <a href="dashboard.php" class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/index.php') ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span>Início</span>
                    </a>
                <?php endif; ?>

                <!-- Opções para Admin -->
                <?php if ($is_admin): ?>
                    <a href="cadastro_usuario.php" class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/cadastro_usuario.php') ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i>
                        <span>Cadastro</span>
                    </a>
                    <a href="listar_profissionais.php" class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/listar_profissionais.php') ? 'active' : ''; ?>">
                        <i class="fas fa-user-md"></i>
                        <span>Profissionais</span>
                    </a>
                    <a href="listar_pacientes.php" class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/listar_pacientes.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Pacientes</span>
                    </a>
                    <a href="visualizar_logs.php" class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/visualizar_logs.php') ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i>
                        <span>Logs</span>
                    </a>
                <?php endif; ?>

                <!-- Opções para Médico e Enfermeiro -->
                <?php if ($is_medico || $is_enfermeiro): ?>
                    <a href="cadastro_usuario.php" class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/cadastro_usuario.php') ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i>
                        <span>Cadastro</span>
                    </a>
                    <a href="listar_profissionais.php" class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/listar_profissionais.php') ? 'active' : ''; ?>">
                        <i class="fas fa-id-card"></i>
                        <span>Meus Dados</span>
                    </a>
                    <a href="listar_pacientes.php" class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/listar_pacientes.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Pacientes</span>
                    </a>
                <?php endif; ?>

                <!-- Opções para ACS -->
                <?php if ($is_acs): ?>
                    <a href="cadastro_usuario.php" class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/cadastro_usuario.php') ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i>
                        <span>Cadastro</span>
                    </a>
                    <a href="listar_profissionais.php" class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/listar_profissionais.php') ? 'active' : ''; ?>">
                        <i class="fas fa-id-card"></i>
                        <span>Meus Dados</span>
                    </a>
                    <a href="listar_pacientes.php" class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/listar_pacientes.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Pacientes</span>
                    </a>
                <?php endif; ?>

                <!-- Opção para Paciente -->
                <?php if ($is_paciente): ?>
                    <a href="listar_pacientes.php" class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/listar_pacientes.php') ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i>
                        <span>Meus Dados</span>
                    </a>
                <?php endif; ?>

                <!-- Logout apenas para usuários logados -->
                <?php if ($is_logged): ?>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Sair</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</body> 
</html>