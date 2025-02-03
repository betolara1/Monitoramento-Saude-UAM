<?php
session_start();
$tem_permissao = false;
if (isset($_SESSION['tipo_usuario']) && 
    ($_SESSION['tipo_usuario'] === 'Admin' || $_SESSION['tipo_usuario'] === 'Medico' || $_SESSION['tipo_usuario'] === 'Enfermeiro')) {
    $tem_permissao = true;
}
include 'conexao.php';
include 'verificar_login.php';
include 'sidebar.php';
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
        }

        .container {
            max-width: 1000px;  /* Aumentado para melhor distribuição */
            margin: 20px auto;
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 10px;
            top: 40px;
            color: #666;
        }

        .form-group input,
        .form-group select {
            padding-left: 35px;  /* Espaço para o ícone */
            height: 45px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
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
            width: 100%;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: transform 0.2s;
        }

        input[type="submit"]:hover {
            transform: translateY(-2px);
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
            display: block;
            margin-top: 5px;
            font-size: 14px;
        }
        
        #email-status.disponivel {
            color: #28a745;
        }
        
        #email-status.indisponivel {
            color: #dc3545;
        }

        /* Desabilita o autocomplete styling do navegador */
        input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 30px white inset !important;
        }

        .is-invalid {
            border-color: #dc3545 !important;
            background-color: #fff8f8;
        }

        .is-valid {
            border-color: #28a745 !important;
            background-color: #f8fff8;
        }

        .loading {
            background-image: url('data:image/gif;base64,R0lGODlhEAAQAPIAAP///wAAAMLCwkJCQgAAAGJiYoKCgpKSkiH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAADMwi63P4wyklrE2MIOggZnAdOmGYJRbExwroUmcG2LmDEwnHQLVsYOd2mBzkYDAdKa+dIAAAh+QQJCgAAACwAAAAAEAAQAAADNAi63P5OjCEgG4QMu7DmikRxQlFUYDEZIGBMRVsaqHwctXXf7WEYB4Ag1xjihkMZsiUkKhIAIfkECQoAAAAsAAAAABAAEAAAAzYIujIjK8pByJDMlFYvBoVjHA70GU7xSUJhmKtwHPAKzLO9HMaoKwJZ7Rf8AYPDDzKpZBqfvwQAIfkECQoAAAAsAAAAABAAEAAAAzMIumIlK8oyhpHsnFZfhYumCYUhDAQxRIdhHBGqRoKw0R8DYlJd8z0fMDgsGo/IpHI5TAAAIfkECQoAAAAsAAAAABAAEAAAAzIIunInK0rnZBTwGPNMgQwmdsNgXGJUlIWEuR5oWUIpz8pAEAMe6TwfwyYsGo/IpFKSAAAh+QQJCgAAACwAAAAAEAAQAAADMwi6IMKQORfjdOe82p4wGccc4CEuQradylesojEMBgsUc2G7sDX3lQGBMLAJibufbSlKAAAh+QQJCgAAACwAAAAAEAAQAAADMgi63P7wCRHZnFVdmgHu2nFwlWCI3WGc3TSWhUFGxTAUkGCbtgENBMJAEJsxgMLWzpEAACH5BAkKAAAALAAAAAAQABAAAAMyCLrc/jDKSatlQtScKdceCAjDII7HcQ4EMTCpyrCuUBjCYRgHVtqlAiB1YhiCnlsRkAAAOwAAAAAAAAAAAA==');
            background-position: right 10px center;
            background-repeat: no-repeat;
            background-size: 20px 20px;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>
<body>
    <div class="container">
        <h1>Cadastro de Usuário</h1>
        <form method="POST" action="salvar_usuario.php">
            <div class="form-group">
                <label for="nome">Nome Completo*:</label>
                <i class="fas fa-user"></i>
                <input type="text" id="nome" name="nome" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="senha">Senha*:</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="senha" name="senha" required>
                </div>

                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha*:</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                    <div id="erro-senha" class="error">As senhas não coincidem!</div>
                </div>

                <div class="form-group">
                    <label for="cpf">CPF*:</label>
                    <i class="fas fa-id-card"></i>
                    <input type="text" id="cpf" name="cpf" required placeholder="000.000.000-00">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">E-mail*:</label>
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" required>
                    <span id="email-status"></span>
                </div>

                <div class="form-group">
                    <label for="telefone">Telefone*:</label>
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="telefone" name="telefone" required placeholder="(00)0000-00000">
                </div>
                
                <!-- Tipo de usuário - visível para Admin e Profissional -->
                <?php if (isset($_SESSION['tipo_usuario']) && ($_SESSION['tipo_usuario'] === 'Admin' || $_SESSION['tipo_usuario'] === 'Profissional')): ?>
                    <div class="form-group">
                        <label for="tipo_usuario">Tipo de Usuário:</label>
                        <i class="fas fa-user-tag"></i>
                        <select name="tipo_usuario" required>
                            <?php if ($_SESSION['tipo_usuario'] === 'Admin'): ?>
                                <option value="Medico">Médico</option>
                                <option value="Enfermeiro">Enfermeiro</option>
                                <option value="ACS">ACS</option>
                                <option value="Paciente">Paciente</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                <?php else: ?>
                    <!-- Se estiver deslogado ou for paciente, tipo é fixo como Paciente -->
                    <div class="form-group">
                        <label for="numero_familia" class="required">N° da Familia*:</label>
                        <i class="fas fa-users"></i>
                        <input type="text" id="numero_familia" name="numero_familia" required placeholder="00000000">
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="cep" class="required">CEP*:</label>
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" id="cep" name="cep" required placeholder="00000-000">
                </div>

                <div class="form-group">
                    <label for="rua">Rua:</label>
                    <i class="fas fa-road"></i>
                    <input type="text" id="rua" name="rua" readonly placeholder="Endereço">
                </div>

                <div class="form-group">
                    <label for="numero" class="required">Número*:</label>
                    <i class="fas fa-home"></i>
                    <input type="text" id="numero" name="numero" required placeholder="Número">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="bairro">Bairro:</label>
                    <i class="fas fa-map-marker"></i>
                    <input type="text" id="bairro" name="bairro" readonly placeholder="Bairro">
                </div>

                <div class="form-group">
                    <label for="cidade">Cidade:</label>
                    <i class="fas fa-city"></i>
                    <input type="text" id="cidade" name="cidade" readonly placeholder="Cidade">
                </div>

                <div class="form-group">
                    <label for="complemento">Complemento:</label>
                    <i class="fas fa-plus"></i>
                    <input type="text" id="complemento" name="complemento" placeholder="Apartamento, sala, etc.">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="estado">Estado:</label>
                    <i class="fas fa-map"></i>
                    <input type="text" id="estado" name="estado" readonly placeholder="Estado">
                </div>
                <div class="form-group"></div>

                <div class="form-group">
                    <label for="data_nascimento">Data de Nascimento:</label>
                    <i class="fas fa-birthday-cake"></i>
                    <input type="date" id="data_nascimento" name="data_nascimento" max="" required>
                </div>

                <div class="form-group">
                    <label for="sexo">Sexo:</label>
                    <i class="fas fa-venus-mars"></i>
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

    <script src="js/confirmar_senha.js"></script>
    <script>
        $(document).ready(function() {
            $('#cep').mask('00000-000');
            $('#telefone').mask('(00) 0000-00000');
            $('#numero_familia').mask('00000000');

            $('#cep').blur(function() {
                var cep = $(this).val().replace(/\D/g, '');
                
                // Limpa os campos de endereço
                $("#rua").val("");
                $("#bairro").val("");
                $("#cidade").val("");
                $("#estado").val("");
                
                // Remove classes de validação anteriores
                $('#cep').removeClass('is-valid is-invalid');
                
                if (cep != "") {
                    var validacep = /^[0-9]{8}$/;
                    
                    if(validacep.test(cep)) {
                        // Mostra loading
                        $("#cep").addClass('loading');
                        
                        $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?")
                        .done(function(dados) {
                            if (!("erro" in dados)) {
                                $("#rua").val(dados.logradouro);
                                $("#bairro").val(dados.bairro);
                                $("#cidade").val(dados.localidade);
                                $("#estado").val(dados.uf);
                                $('#cep').addClass('is-valid').removeClass('is-invalid');
                            } else {
                                // CEP não encontrado
                                $('#cep').addClass('is-invalid').removeClass('is-valid');
                                Swal.fire({
                                    icon: 'error',
                                    title: 'CEP não encontrado',
                                    text: 'O CEP informado não foi encontrado. Por favor, verifique e tente novamente.'
                                });
                            }
                        })
                        .fail(function() {
                            // Erro na consulta
                            $('#cep').addClass('is-invalid').removeClass('is-valid');
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro na consulta',
                                text: 'Ocorreu um erro ao consultar o CEP. Por favor, tente novamente.'
                            });
                        })
                        .always(function() {
                            // Remove loading
                            $("#cep").removeClass('loading');
                        });
                    } else {
                        // CEP inválido
                        $('#cep').addClass('is-invalid').removeClass('is-valid');
                        Swal.fire({
                            icon: 'error',
                            title: 'CEP inválido',
                            text: 'Por favor, digite um CEP válido.'
                        });
                    }
                }
            });
        });

        $(document).ready(function() {
            // Máscara para CPF
            $('#cpf').mask('000.000.000-00', {
                reverse: true,
                onComplete: function(cpf) {
                    // Validação do CPF
                    if (!validarCPF(cpf)) {
                        alert('CPF inválido!');
                        $('#cpf').val('');
                    }
                }
            });

            // Máscara para telefone (ajusta automaticamente para celular ou fixo)
            $('#telefone').mask('(00) 0000-00009');
            $('#telefone').blur(function(event) {
                if ($(this).val().length == 15) {
                    $('#telefone').mask('(00) 00000-0009');
                } else {
                    $('#telefone').mask('(00) 0000-00009');
                }
            });

            let timeoutId;
            const emailInput = $('#email');
            const emailStatus = $('#email-status');
            const submitButton = $('input[type="submit"]');

            emailInput.on('input', function() {
                // Limpa o timeout anterior
                clearTimeout(timeoutId);
                
                const email = $(this).val();
                
                // Remove as classes de status
                emailStatus.removeClass('disponivel indisponivel');
                
                // Se o campo estiver vazio, limpa a mensagem
                if (!email) {
                    emailStatus.html('').hide();
                    return;
                }
                
                // Verifica se é um email válido
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    emailStatus.html('Por favor, insira um e-mail válido.').show();
                    emailStatus.addClass('indisponivel');
                    submitButton.prop('disabled', true);
                    return;
                }

                // Adiciona um pequeno delay para evitar muitas requisições
                timeoutId = setTimeout(function() {
                    emailStatus.html('Verificando...').show();
                    
                    $.ajax({
                        url: 'verificar_email.php',
                        type: 'POST',
                        data: { email: email },
                        dataType: 'json',
                        success: function(response) {
                            if (response.disponivel) {
                                emailStatus.html('✓ ' + response.mensagem);
                                emailStatus.addClass('disponivel');
                                submitButton.prop('disabled', false);
                            } else {
                                emailStatus.html('✕ ' + response.mensagem);
                                emailStatus.addClass('indisponivel');
                                submitButton.prop('disabled', true);
                            }
                        },
                        error: function() {
                            emailStatus.html('Erro ao verificar e-mail. Tente novamente.');
                            emailStatus.addClass('indisponivel');
                            submitButton.prop('disabled', true);
                        }
                    });
                }, 500); // Delay de 500ms
            });
        });

        // Função para validar CPF
        function validarCPF(cpf) {
            cpf = cpf.replace(/[^\d]+/g,'');
            if (cpf == '') return false;
            
            // Elimina CPFs inválidos conhecidos
            if (cpf.length != 11 || 
                cpf == "00000000000" || 
                cpf == "11111111111" || 
                cpf == "22222222222" || 
                cpf == "33333333333" || 
                cpf == "44444444444" || 
                cpf == "55555555555" || 
                cpf == "66666666666" || 
                cpf == "77777777777" || 
                cpf == "88888888888" || 
                cpf == "99999999999")
                return false;
                
            // Valida 1º dígito
            add = 0;
            for (i=0; i < 9; i++)
                add += parseInt(cpf.charAt(i)) * (10 - i);
            rev = 11 - (add % 11);
            if (rev == 10 || rev == 11)
                rev = 0;
            if (rev != parseInt(cpf.charAt(9)))
                return false;
                
            // Valida 2º dígito
            add = 0;
            for (i = 0; i < 10; i++)
                add += parseInt(cpf.charAt(i)) * (11 - i);
            rev = 11 - (add % 11);
            if (rev == 10 || rev == 11)
                rev = 0;
            if (rev != parseInt(cpf.charAt(10)))
                return false;
                
            return true;
        }

        // Define a data máxima como hoje
        const today = new Date();
        const dd = String(today.getDate()).padStart(2, '0');
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const yyyy = today.getFullYear();
        const maxDate = yyyy + '-' + mm + '-' + dd;
        
        // Define o atributo max do input
        document.getElementById('data_nascimento').setAttribute('max', maxDate);
        
        // Adiciona validação adicional no change do input
        document.getElementById('data_nascimento').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            if (selectedDate > today) {
                Swal.fire({
                    icon: 'error',
                    title: 'Data inválida',
                    text: 'A data de nascimento não pode ser maior que a data atual.'
                });
                this.value = '';
            }
        });
    </script>
    </div>
</body>
</html>