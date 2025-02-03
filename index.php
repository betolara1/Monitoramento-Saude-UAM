<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Monitoramento de Saúde</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .welcome-section {
            padding: 4rem 0;
            text-align: center;
            color: #2c3e50;
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #2c3e50;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            background: white;
            margin-bottom: 2rem;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-body {
            padding: 2rem;
        }

        .login-form {
            max-width: 400px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-control {
            border-radius: 10px;
            padding: 0.8rem;
            border: 1px solid #e0e0e0;
            margin-bottom: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.8rem;
            border-radius: 5px;
            width: 100%;
            font-weight: 500;
            margin-top: 1rem;
            transition: all 0.3s ease;
            color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }

        .feature-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #4CAF50;
        }

        .error-message {
            color: #dc3545;
            background-color: #ffe6e6;
            padding: 0.5rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: none;
        }

        .features-section {
            padding: 3rem 0;
        }

        .footer {
            background: #2c3e50;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }

        .btn-outline-success {
            background: linear-gradient(135deg, #2a9d8f 0%, #264653 100%);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.8rem;
            border-radius: 5px;
            width: 100%;
            font-weight: 500;
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-outline-success:hover {
            background: linear-gradient(135deg, #264653 0%, #2a9d8f 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }

        .text-decoration-none {
            text-decoration: none !important;
            color: #fff;
            transition: color 0.3s ease;
        }

        .text-decoration-none:hover {
            color: rgba(255, 255, 255, 0.8);
        }

        .text-muted {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .d-grid {
            display: grid;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .my-3 {
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .mt-3 {
            margin-top: 1rem;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Seção de Boas-vindas -->
    <div class="welcome-section">
        <h1 class="welcome-title">Sistema de Monitoramento de Saúde</h1>
        <p class="lead">Acompanhamento integrado para pacientes e profissionais de saúde</p>
    </div>

    <div class="row">
        <!-- Formulário de Login -->
        <div class="col-md-6 mb-4">
            <?php if (!isset($_SESSION['usuario_id'])): ?>
                <div class="login-form">
                    <h3 class="text-center mb-4">Login</h3>
                    
                    <?php if (isset($_GET['erro'])): ?>
                        <div class="error-message" style="display: block;">
                            Email ou senha incorretos. Tente novamente.
                        </div>
                    <?php endif; ?>

                    <form action="processar_login.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="senha" name="senha" required>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Entrar
                            </button>
                            
                            <div class="text-center my-3">
                                <span class="text-muted">ou</span>
                            </div>
                            
                            <a href="cadastro_usuario.php" class="btn btn-outline-success">
                                <i class="fas fa-user-plus"></i> Criar Nova Conta
                            </a>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Ao se cadastrar, você concorda com nossos 
                                <a href="#" class="text-decoration-none">Termos de Uso</a> e 
                                <a href="#" class="text-decoration-none">Política de Privacidade</a>
                            </small>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Cards de Recursos -->
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-heartbeat feature-icon"></i>
                            <h5 class="card-title">Monitoramento de Saúde</h5>
                            <p class="card-text">Acompanhamento contínuo de pacientes com doenças crônicas.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-user-md feature-icon"></i>
                            <h5 class="card-title">Equipe Médica</h5>
                            <p class="card-text">Profissionais especializados para seu atendimento.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line feature-icon"></i>
                            <h5 class="card-title">Análise de Dados</h5>
                            <p class="card-text">Acompanhamento estatístico da evolução do paciente.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-mobile-alt feature-icon"></i>
                            <h5 class="card-title">Acesso Facilitado</h5>
                            <p class="card-text">Sistema responsivo para acesso em qualquer dispositivo.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer text-center">
    <div class="container">
        <p>&copy; 2024 Sistema de Monitoramento de Saúde. Todos os direitos reservados.</p>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    // Animação suave para cards
    $('.card').hover(
        function() {
            $(this).addClass('shadow-lg');
        },
        function() {
            $(this).removeClass('shadow-lg');
        }
    );

    // Validação do formulário
    $('form').on('submit', function(e) {
        const email = $('#email').val();
        const senha = $('#senha').val();
        
        if (!email || !senha) {
            e.preventDefault();
            $('.error-message').text('Por favor, preencha todos os campos.').show();
        }
    });
});
</script>

</body>
</html>