<?php
session_start();
include 'sidebar.php';


// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

function temPermissao($tipo_permissao = null) {
    // Verifica se o usuário está logado
    if (!isset($_SESSION['tipo_usuario'])) {
        return false;
    }

    // Se não foi especificado um tipo de permissão, retorna true (usuário está logado)
    if ($tipo_permissao === null) {
        return true;
    }

    // Verifica o tipo de permissão
    switch ($tipo_permissao) {
        case 'Admin':
            return $_SESSION['tipo_usuario'] === 'Admin';
        case 'Medico':
            return $_SESSION['tipo_usuario'] === 'Medico';
        case 'Enfermeiro':
            return $_SESSION['tipo_usuario'] === 'Enfermeiro';
        case 'Paciente':
            return $_SESSION['tipo_usuario'] === 'Paciente';
        case 'ACS':
            return $_SESSION['tipo_usuario'] === 'ACS';
        default:
            return false;
    }
}

?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Monitoramento de Saúde</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .dashboard-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome-message {
            text-align: center;
            margin-bottom: 3rem;
            color: #2c3e50;
        }

        .welcome-message h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #2c3e50;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: white;
            height: 100%;
            margin-bottom: 2rem;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        .card-body {
            padding: 2rem;
        }

        .feature-icon {
            font-size: 2.5rem;
            color: #1e3c72;
            margin-bottom: 1.5rem;
        }

        .card-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .card-text {
            color: #666;
            font-size: 0.95rem;
        }

        .stats-container {
            margin-top: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Footer Styles */
        .footer {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1rem 0;
            position: relative;
            bottom: 0;
            width: 100%;
            margin-top: 2rem;
        }

        /* Botões padrão */
        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.8rem;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }

        a.text-decoration-none {
            color: inherit;
        }

        .notification-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .notification-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
            background: linear-gradient(45deg, #224abe, #4e73df);
        }

        .notification-btn .bell-icon {
            font-size: 24px;
            animation: ring 4s ease-in-out infinite;
        }

        @keyframes ring {
            0% { transform: rotate(0); }
            5% { transform: rotate(25deg); }
            10% { transform: rotate(-20deg); }
            15% { transform: rotate(15deg); }
            20% { transform: rotate(-10deg); }
            25% { transform: rotate(5deg); }
            30% { transform: rotate(0); }
            100% { transform: rotate(0); }
        }

        .notification-btn.active {
            background: linear-gradient(45deg, #1cc88a, #169b6b);
            pointer-events: none;
        }

        .notification-btn.active .bell-icon {
            animation: none;
        }

        .notification-status {
            position: fixed;
            bottom: 100px;
            right: 30px;
            display: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            z-index: 9999;
            max-width: 250px;
            text-align: center;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .notification-status.success {
            display: block;
            background-color: white;
            color: #169b6b;
            border: 1px solid #1cc88a;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .notification-status.error {
            display: block;
            background-color: white;
            color: #e74a3b;
            border: 1px solid #e74a3b;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Tooltip */
        .notification-btn::before {
            content: 'Ativar Notificações';
            position: absolute;
            right: 70px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
            white-space: nowrap;
        }

        .notification-btn:hover::before {
            opacity: 1;
        }

        .notification-btn.active::before {
            content: 'Notificações Ativadas';
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="welcome-message">
        <h2>Bem-vindo(a), <?php echo $_SESSION['nome'] ?? 'Usuário'; ?>!</h2>
        <p>Painel de Controle do Sistema de Monitoramento de Saúde</p>
        <button id="notificationBtn" class="notification-btn" onclick="registrarServiceWorker()">
            <i class="fas fa-bell bell-icon"></i>
        </button>
    </div>

    <div id="notificationStatus" class="notification-status"></div>

    <div class="row">
        <?php if (temPermissao('Admin') || temPermissao('ACS') || temPermissao('Enfermeiro') || temPermissao('Medico')): ?>
            <div class="col-md-3 mb-4">
                <a href="cadastro_usuario.php" class="text-decoration-none">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-user-plus feature-icon"></i>
                            <h5 class="card-title">Cadastrar Usuário</h5>
                            <p class="card-text">Cadastre novos usuários, profissionais e pacientes no sistema.</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 mb-4">
                <a href="listar_profissionais.php" class="text-decoration-none">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-user-md feature-icon"></i>
                            <h5 class="card-title">Profissionais</h5>
                            <p class="card-text">Visualize e gerencie a lista de profissionais cadastrados.</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 mb-4">
                <a href="listar_pacientes.php" class="text-decoration-none">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-users feature-icon"></i>
                            <h5 class="card-title">Pacientes</h5>
                            <p class="card-text">Acesse a lista completa de pacientes e seus dados.</p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endif; ?>

        <?php if (temPermissao('Paciente')): 
            $usuario_id = $_SESSION['usuario_id'];
            $query = "SELECT id FROM pacientes WHERE usuario_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $paciente = $result->fetch_assoc();
            $paciente_id = $paciente['id'];
        ?>
            <div class="col-md-3 mb-4">
                <a href="listar_pacientes.php" class="text-decoration-none">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-user-edit feature-icon"></i>
                            <h5 class="card-title">Meus Dados</h5>
                            <p class="card-text">Visualize e atualize suas informações pessoais.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-4">
                <a href="editar_paciente.php?id=<?php echo $paciente_id; ?>" class="text-decoration-none">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-user-edit feature-icon"></i>
                            <h5 class="card-title">Meus Dados Clínicos</h5>
                            <p class="card-text">Visualize e atualize suas informações pessoais e de saúde.</p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Footer -->
<footer class="footer text-center">
    <div class="container">
        <p>&copy; 2024 Sistema de Monitoramento de Saúde. Todos os direitos reservados.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    async function registrarServiceWorker() {
        const btn = document.getElementById('notificationBtn');
        const status = document.getElementById('notificationStatus');
        
        try {
            const registration = await navigator.serviceWorker.register('./src/service-worker.js', {
                scope: '/medicina/src/'
            });
            console.log('Service Worker registrado:', registration);
            
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: 'BLXcZTuDynYjNrB-30khCkSHmLKhnR4tKIaMEiIOL5vxgSGdlhWLfR0NogDdFr8pi3b4aahogSaZ2mq8XVSd2Mk'
                });
                
                // Adicionar ID do usuário na requisição
                const userId = <?php echo $_SESSION['usuario_id']; ?>; // Pegando ID do usuário da sessão PHP
                
                // Enviar para salvar no banco
                const response = await fetch('http://localhost:3000/api/salvar-subscription', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        userId: userId,
                        subscription: subscription
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                console.log('Subscription salva com sucesso!');

                // Quando der sucesso
                btn.classList.add('active');
                btn.innerHTML = `<i class="fas fa-bell bell-icon"></i>`;
                status.className = 'notification-status success';
                status.textContent = 'Notificações ativadas!';
                
                // Esconder a mensagem de status após 3 segundos
                setTimeout(() => {
                    status.style.display = 'none';
                }, 3000);

            }
        } catch (error) {
            console.error('Erro ao registrar Service Worker:', error);
            status.className = 'notification-status error';
            status.textContent = 'Erro ao ativar notificações';
            
            // Esconder a mensagem de erro após 3 segundos
            setTimeout(() => {
                status.style.display = 'none';
            }, 3000);
        }
    }

    // Verificar estado inicial das notificações
    if ('Notification' in window) {
        if (Notification.permission === 'granted') {
            const btn = document.getElementById('notificationBtn');
            btn.classList.add('active');
            btn.innerHTML = `<i class="fas fa-bell bell-icon"></i>`;
        }
    }
</script>
</body>
</html>