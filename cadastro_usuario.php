<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Cadastro de Usuário</h1>
        <form method="POST" action="salvar_usuario.php" class="mt-4">
            <!-- Informações Pessoais -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-user"></i> Informações Pessoais
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nome" class="form-label">
                                <i class="fas fa-user"></i> Nome Completo*
                            </label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        <div class="col-md-6">
                            <label for="cpf" class="form-label">
                                <i class="fas fa-id-card"></i> CPF*
                            </label>
                            <input type="text" class="form-control" id="cpf" name="cpf" required placeholder="000.000.000-00">
                            <span id="cpf-status"></span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="data_nascimento" class="form-label">
                                <i class="fas fa-birthday-cake"></i> Data de Nascimento*
                            </label>
                            <input type="text" class="form-control" id="data_nascimento" name="data_nascimento" required placeholder="DD/MM/AAAA">
                        </div>
                        <div class="col-md-2">
                            <label for="sexo" class="form-label">
                                <i class="fas fa-venus-mars"></i> Gênero*
                            </label>
                            <select class="form-select" id="sexo" name="sexo" required>
                                <option value="">Selecione</option>
                                <option value="M">Masculino</option>
                                <option value="F">Feminino</option>
                                <option value="Outros">Outros</option>
                            </select>
                            <div id="outro_genero_div" style="display: none; margin-top: 10px;">
                                <input type="text" class="form-control" id="outro_genero" name="outro_genero" placeholder="Especifique seu gênero">
                            </div>
                        </div>
                        <?php if (isset($_SESSION['tipo_usuario']) && in_array($_SESSION['tipo_usuario'], ['Admin', 'Medico', 'Enfermeiro', 'ACS'])): ?>
                            <div class="col-md-3">
                                <label for="micro_area" class="form-label">
                                    <i class="fas fa-map-marked-alt"></i> Micro Área*
                                </label>
                                <div class="input-group">
                                    <select class="form-select" id="micro_area" name="micro_area" required>
                                        <option value="">Selecione</option>
                                        <?php
                                            $sql = "SELECT nome FROM micro_areas ORDER BY nome";
                                            $result = $conn->query($sql);
                                            
                                            if ($result && $result->num_rows > 0) {
                                                while($row = $result->fetch_assoc()) {
                                                    echo "<option value='" . htmlspecialchars($row['nome']) . "'>" . htmlspecialchars($row['nome']) . "</option>";
                                                }
                                            }
                                        ?>
                                    </select>
                                    <button type="button" class="btn btn-primary" onclick="abrirModalMicroArea()">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-2">
                            <?php if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] === 'Paciente'): ?>
                                <label for="numero_familia" class="form-label">
                                    <i class="fas fa-users"></i> N° da Família*
                                </label>
                                <input type="text" class="form-control" id="numero_familia" name="numero_familia" required placeholder="00000000">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações de Acesso -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-lock"></i> Informações de Acesso
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> E-mail*
                            </label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <span id="email-status"></span>
                        </div>
                        <div class="col-md-6">
                            <label for="telefone" class="form-label">
                                <i class="fas fa-phone"></i> Telefone*
                            </label>
                            <input type="tel" class="form-control" id="telefone" name="telefone" required placeholder="(00)0000-00000">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="senha" class="form-label">
                                <i class="fas fa-lock"></i> Senha*
                            </label>
                            <input type="password" class="form-control" id="senha" name="senha" required>
                        </div>
                        <div class="col-md-6">
                            <label for="confirmar_senha" class="form-label">
                                <i class="fas fa-lock"></i> Confirmar Senha*
                            </label>
                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                            <div id="erro-senha" class="error text-danger mt-1">As senhas não coincidem!</div>
                        </div>
                    </div>

                    <!-- Tipo de usuário - visível para Admin e Profissional -->
                    <?php if (isset($_SESSION['tipo_usuario']) && ($_SESSION['tipo_usuario'] === 'Admin' || $_SESSION['tipo_usuario'] === 'Profissional')): ?>
                        <div class="col-md-2">
                            <label for="tipo_usuario"><i class="fas fa-user-tag"></i>Tipo de Usuário:</label>
                            <select name="tipo_usuario" required>
                                <?php if ($_SESSION['tipo_usuario'] === 'Admin'): ?>
                                    <option value="Medico">Médico</option>
                                    <option value="Enfermeiro">Enfermeiro</option>
                                    <option value="ACS">ACS</option>
                                    <option value="Paciente">Paciente</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Endereço -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-map-marked-alt"></i> Endereço
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="cep" class="form-label">
                                <i class="fas fa-map-marker-alt"></i> CEP*
                            </label>
                            <input type="text" class="form-control" id="cep" name="cep" required placeholder="00000-000">
                        </div>
                        <div class="col-md-6">
                            <label for="rua" class="form-label">
                                <i class="fas fa-road"></i> Rua
                            </label>
                            <input type="text" class="form-control" id="rua" name="rua" readonly placeholder="Endereço">
                        </div>
                        <div class="col-md-2">
                            <label for="numero" class="form-label">
                                <i class="fas fa-home"></i> Número*
                            </label>
                            <input type="text" class="form-control" id="numero" name="numero" required placeholder="Número">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="bairro" class="form-label">
                                <i class="fas fa-map"></i> Bairro
                            </label>
                            <input type="text" class="form-control" id="bairro" name="bairro" readonly placeholder="Bairro">
                        </div>
                        <div class="col-md-4">
                            <label for="cidade" class="form-label">
                                <i class="fas fa-city"></i> Cidade
                            </label>
                            <input type="text" class="form-control" id="cidade" name="cidade" readonly placeholder="Cidade">
                        </div>
                        <div class="col-md-4">
                            <label for="estado" class="form-label">
                                <i class="fas fa-flag"></i> Estado
                            </label>
                            <input type="text" class="form-control" id="estado" name="estado" readonly placeholder="Estado">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <label for="complemento" class="form-label">
                                <i class="fas fa-info-circle"></i> Complemento
                            </label>
                            <input type="text" class="form-control" id="complemento" name="complemento" placeholder="Apartamento, sala, etc.">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões -->
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-4">
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <a href="dashboard.php" class="btn btn-info me-md-2">
                        <i class="fas fa-arrow-left"></i> Voltar para Dashboard
                    </a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-info me-md-2">
                        <i class="fas fa-arrow-left"></i> Voltar para Login
                    </a>
                <?php endif; ?>
                
                <button type="reset" class="btn btn-secondary me-md-2">
                    <i class="fas fa-undo"></i> Limpar
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Cadastrar
                </button>
            </div>
        </form>
    </div>

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

            // Máscara para o CPF
            $('#cpf').mask('000.000.000-00');
            
            // Validação do CPF
            $('#cpf').blur(function() {
                var cpf = $(this).val();
                if(cpf) {
                    $.ajax({
                        url: 'verificar_cpf.php',
                        type: 'post',
                        data: {cpf: cpf},
                        success: function(response) {
                            if(response == 'existe') {
                                $('#cpf-status').html('<span style="color: #dc3545;"><i class="fas fa-times-circle"></i> CPF já cadastrado</span>');
                                $('#cpf').addClass('is-invalid');
                                $('button[type="submit"]').prop('disabled', true);
                            } else {
                                $('#cpf-status').html('<span style="color: #198754;"><i class="fas fa-check-circle"></i> CPF disponível</span>');
                                $('#cpf').removeClass('is-invalid');
                                $('button[type="submit"]').prop('disabled', false);
                            }
                        }
                    });
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

            // Validação do formulário
            $('form').submit(function(e) {
                var cpfStatus = $('#cpf-status').text();
                if(cpfStatus.includes('já cadastrado')) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Por favor, utilize um CPF não cadastrado.',
                        confirmButtonColor: '#dc3545'
                    });
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

            // Máscara para data de nascimento
            $('#data_nascimento').mask('00/00/0000');
            
            // Validação da data de nascimento
            $('#data_nascimento').blur(function() {
                let data = $(this).val();
                if(data) {
                    // Converte data do formato DD/MM/YYYY para objeto Date
                    let partes = data.split('/');
                    let dataNascimento = new Date(partes[2], partes[1] - 1, partes[0]);
                    let hoje = new Date();
                    let dataMinima = new Date('1900-01-01');
                    
                    // Validações
                    if(dataNascimento > hoje) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Data inválida',
                            text: 'A data de nascimento não pode ser maior que hoje'
                        });
                        $(this).val('');
                        return;
                    }
                    
                    if(dataNascimento < dataMinima) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Data inválida',
                            text: 'A data de nascimento não pode ser anterior a 01/01/1900'
                        });
                        $(this).val('');
                        return;
                    }
                    
                    // Validação adicional para data válida
                    if(partes[2] < 1900 || partes[1] > 12 || partes[0] > 31) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Data inválida',
                            text: 'Por favor, insira uma data válida'
                        });
                        $(this).val('');
                        return;
                    }
                }
            });

            $('#sexo').change(function() {
                if($(this).val() === 'Outros') {
                    $('#outro_genero_div').show();
                    $('#outro_genero').prop('required', true);
                } else {
                    $('#outro_genero_div').hide();
                    $('#outro_genero').prop('required', false);
                    $('#outro_genero').val('');
                }
            });
        });

        // Adicione este código ao bloco de JavaScript existente
        $('form').submit(function(e) {
            if ($('#sexo').val() === 'Outros' && !$('#outro_genero').val().trim()) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Campo obrigatório',
                    text: 'Por favor, especifique seu gênero'
                });
                return false;
            }
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

        // Adiciona o evento de submit no formulário
        $('form').on('submit', function(e) {
            e.preventDefault(); // Previne o submit padrão

            // Verifica se as senhas coincidem
            if ($('#senha').val() !== $('#confirmar_senha').val()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'As senhas não coincidem!',
                    confirmButtonColor: '#d33'
                });
                return false;
            }

            // Verifica se todos os campos obrigatórios foram preenchidos
            let camposVazios = [];
            $(this).find('[required]').each(function() {
                if (!$(this).val()) {
                    camposVazios.push($(this).prev('label').text().replace('*', '').trim());
                }
            });

            if (camposVazios.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção!',
                    text: 'Por favor, preencha os seguintes campos: ' + camposVazios.join(', '),
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }

            // Se tudo estiver ok, mostra o SweetAlert de confirmação
            Swal.fire({
                title: 'Confirmar cadastro?',
                text: "Verifique se todos os dados estão corretos",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4CAF50',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, cadastrar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostra loading enquanto processa
                    Swal.fire({
                        title: 'Processando...',
                        html: 'Por favor, aguarde enquanto realizamos seu cadastro',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Faz o submit do formulário via AJAX
                    $.ajax({
                        url: $(this).attr('action'),
                        type: 'POST',
                        data: $(this).serialize(),
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: 'Cadastro realizado com sucesso!',
                                confirmButtonColor: '#4CAF50',
                                timer: 3000,
                                timerProgressBar: true
                            }).then(() => {
                                window.location.href = 'index.php';
                            });
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: 'Ocorreu um erro ao realizar o cadastro. Tente novamente.',
                                confirmButtonColor: '#d33'
                            });
                        }
                    });
                }
            });
        });

        function abrirModalMicroArea() {
            var myModal = new bootstrap.Modal(document.getElementById('modalMicroArea'));
            myModal.show();
        }

        function salvarMicroArea() {
            const novaMicroArea = $('#nova_micro_area').val().trim();
            
            if (!novaMicroArea) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Por favor, insira um nome para a micro área'
                });
                return;
            }

            $.ajax({
                url: 'salvar_micro_area.php',
                type: 'POST',
                data: { nome: novaMicroArea },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            // Adiciona a nova opção ao select
                            $('#micro_area').append(new Option(novaMicroArea, novaMicroArea));
                            
                            // Fecha o modal
                            $('#modalMicroArea').modal('hide');
                            
                            // Limpa o campo
                            $('#nova_micro_area').val('');
                            
                            // Mostra mensagem de sucesso
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso',
                                text: 'Micro área adicionada com sucesso!'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro',
                                text: data.message || 'Erro ao salvar micro área'
                            });
                        }
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: 'Erro ao processar resposta do servidor'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao comunicar com o servidor'
                    });
                }
            });
        }
    </script>

    <!-- Adicione este modal no final do arquivo, antes do </body> -->
    <div class="modal fade" id="modalMicroArea" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Nova Micro Área</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nova_micro_area">Nome da Micro Área</label>
                        <input type="text" class="form-control" id="nova_micro_area" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="salvarMicroArea()">Salvar</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>