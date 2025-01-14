<?php
include 'sidebar.php';
include 'conexao.php';
include 'verificar_login.php';

$tem_permissao = false;
if (isset($_SESSION['tipo_usuario']) && 
    ($_SESSION['tipo_usuario'] === 'Admin' || $_SESSION['tipo_usuario'] === 'Profissional')) {
    $tem_permissao = true;
}

?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f4f4f4;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-top: 5px;
        }

        input[type="text"], input[type="email"], input[type="password"] { 
            display: block; 
            margin: 10px 0; 
        } 
        
        #email-status { 
            margin-top: 10px; 
            color: red; 
            display: none; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cadastro de Usuário</h1>
        <form method="POST" action="salvar_usuario.php">
            <div class="form-group">
                <label for="nome">Nome Completo*:</label>
                <input type="text" id="nome" name="nome" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="senha">Senha*:</label>
                    <input type="password" id="senha" name="senha" required>
                </div>

                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha*:</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                    <div id="erro-senha" class="error">As senhas não coincidem!</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">E-mail*:</label>
                    <input type="email" id="email" name="email" required>
                    <span id="email-status"></span>
                </div>

            <!-- Tipo de usuário - visível para Admin e Profissional -->
            <?php if (isset($_SESSION['tipo_usuario']) && ($_SESSION['tipo_usuario'] === 'Admin' || $_SESSION['tipo_usuario'] === 'Profissional')): ?>
                <div class="form-group">
                    <label for="tipo_usuario">Tipo de Usuário:</label>
                    <select name="tipo_usuario" required>
                        <option value="Paciente">Paciente</option>
                        <?php if ($_SESSION['tipo_usuario'] === 'Admin'): ?>
                            <option value="Profissional">Profissional</option>
                            <option value="Familia">Família</option>
                            <option value="Cidadão">Cidadão</option>
                        <?php endif; ?>
                    </select>
                </div>
            <?php else: ?>
                <!-- Se estiver deslogado ou for paciente, tipo é fixo como Paciente -->
                <input type="hidden" name="tipo_usuario" value="Paciente">
            <?php endif; ?>

                <div class="form-group">
                    <label for="telefone">Telefone*:</label>
                    <input type="tel" id="telefone" name="telefone" required placeholder="(00)0000-00000">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="cep" class="required">CEP*:</label>
                    <input type="text" id="cep" name="cep" required placeholder="00000-000">
                </div>

                <div class="form-group">
                    <label for="rua">Rua:</label>
                    <input type="text" id="rua" name="rua" readonly placeholder="Endereço">
                </div>

                <div class="form-group">
                    <label for="numero" class="required">Número*:</label>
                    <input type="text" id="numero" name="numero" required placeholder="Número">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="bairro">Bairro:</label>
                    <input type="text" id="bairro" name="bairro" readonly placeholder="Bairro">
                </div>

                <div class="form-group">
                    <label for="cidade">Cidade:</label>
                    <input type="text" id="cidade" name="cidade" readonly placeholder="Cidade">
                </div>

                <div class="form-group">
                    <label for="complemento">Complemento:</label>
                    <input type="text" id="complemento" name="complemento" placeholder="Apartamento, sala, etc.">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="estado">Estado:</label>
                    <input type="text" id="estado" name="estado" readonly placeholder="Estado">
                </div>
                <div class="form-group"></div>

                <div class="form-group">
                    <label for="data_nascimento">Data de Nascimento:</label>
                    <input type="date" id="data_nascimento" name="data_nascimento">
                </div>

                <div class="form-group">
                    <label for="sexo">Sexo:</label>
                    <select id="sexo" name="sexo">
                        <option value="">Selecione</option>
                        <option value="M">Masculino</option>
                        <option value="F">Feminino</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <input type="submit" value="Cadastrar">
            </div>
        </form>
        <script src="js/buscar_cep.js"></script>
        <script src="js/confirmar_senha.js"></script>
        <script src="js/verifica_email.js"></script>
    </div>
</body>
</html>