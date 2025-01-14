<?php
session_start();
include 'sidebar.php'; 
?>

<!DOCTYPE html>
<html>
<head>
    <title>Formulário de Cadastro</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    
    <style>
        .botao {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 12px;
            box-shadow: 0 9px #999;
            }

            .botao:hover {
            background-color: #3e8e41;
            box-shadow: 0 5px #666;
            transform: translateY(4px);
            }
        
        .login-container {
            width: 300px;
            margin: 100px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .error-message {
            color: red;
            margin-bottom: 10px;
        }
        
        .welcome-container {
            width: 300px;
            margin: 100px auto;
            padding: 20px;
            text-align: center;
        }
        
        .welcome-message {
            font-size: 20px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php if(isset($_SESSION['usuario_id'])): ?>
        <div class="welcome-container">
            <div class="welcome-message">
                Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['nome']); ?>!
            </div>
            <a href="logout.php" class="botao">Sair</a>
        </div>
    <?php else: ?>
        <div class="login-container">
            <h2>Login</h2>
            <?php if(isset($_GET['erro'])): ?>
                <div class="error-message">Email ou senha incorretos!</div>
            <?php endif; ?>
            
            <form action="processar_login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                
                <button type="submit" class="botao">Entrar</button>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>