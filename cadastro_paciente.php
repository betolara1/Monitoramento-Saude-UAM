<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php"); // Redireciona para a página de login
    exit();
}

include "conexao.php";
include 'verificar_login.php';
include "sidebar.php";

// Verificar se o ID foi passado na URL
if (!isset($_GET['id'])) {
    header('Location: listar_pacientes.php');
    exit;
}

$usuario_id = $_GET['id'];

// Buscar informações do usuário
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

// Verificar se o usuário existe
if (!$usuario) {
    header('Location: listar_pacientes.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cadastro de Pacientes com DCNT</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 0px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            width: 100%;
            animation: fadeIn 0.5s ease;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .form-label {
            font-weight: 500;
            color: #34495e;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control, .form-select {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }

        textarea.form-control {
            min-height: 120px;
        }

        .btn-primary {
            background-color: #4CAF50;
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background-color: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }

            .container {
                margin: 1rem;
                padding: 1.5rem;
            }

            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="container">
            <h2><i class="fas fa-user-plus"></i> Cadastro de DCNT - <?php echo htmlspecialchars($usuario['nome']); ?></h2>
            <form id="formCadastroPaciente" action="salvar_pacientes_doenca.php" method="POST">
                <input type="hidden" name="usuario_id" value="<?php echo $usuario_id; ?>">
                
                <div class="form-group">
                    <label for="tipo_doenca" class="form-label">
                        <i class="fas fa-heartbeat"></i> Tipo de Doença*:
                    </label>
                    <select class="form-select" id="tipo_doenca" name="tipo_doenca" required>
                        <option value="">Selecione o tipo de doença...</option>
                        <option value="Hipertensão">Hipertensão</option>
                        <option value="Diabetes">Diabetes</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="historico_familiar" class="form-label">
                        <i class="fas fa-users"></i> Histórico Familiar:
                    </label>
                    <textarea class="form-control" id="historico_familiar" name="historico_familiar" 
                              placeholder="Descreva o histórico familiar de doenças..."></textarea>
                </div>

                <div class="form-group">
                    <label for="estado_civil" class="form-label">
                        <i class="fas fa-ring"></i> Estado Civil*:
                    </label>
                    <select class="form-select" id="estado_civil" name="estado_civil" required>
                        <option value="">Selecione o estado civil...</option>
                        <option value="Solteiro">Solteiro(a)</option>
                        <option value="Casado">Casado(a)</option>
                        <option value="Divorciado">Divorciado(a)</option>
                        <option value="Viúvo">Viúvo(a)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="profissao" class="form-label">
                        <i class="fas fa-briefcase"></i> Profissão*:
                    </label>
                    <input type="text" class="form-control" id="profissao" name="profissao" 
                           placeholder="Digite sua profissão..." required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Cadastrar Paciente
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {
        $('#formCadastroPaciente').on('submit', function(e) {
            e.preventDefault();
            
            // Verifica campos obrigatórios
            let camposVazios = [];
            $(this).find('[required]').each(function() {
                if (!$(this).val()) {
                    camposVazios.push($(this).prev('label').text().replace('*:', '').trim());
                }
            });

            if (camposVazios.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção!',
                    text: 'Por favor, preencha os seguintes campos: ' + camposVazios.join(', '),
                    confirmButtonColor: '#4CAF50'
                });
                return false;
            }

            // Confirmação antes de enviar
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
                    // Mostra loading
                    Swal.fire({
                        title: 'Processando...',
                        html: 'Por favor, aguarde enquanto realizamos o cadastro',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Envia o formulário via AJAX
                    $.ajax({
                        url: $(this).attr('action'),
                        type: 'POST',
                        data: $(this).serialize(),
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sucesso!',
                                    text: 'Cadastro realizado com sucesso!',
                                    confirmButtonColor: '#4CAF50',
                                    timer: 3000,
                                    timerProgressBar: true
                                }).then(() => {
                                    window.location.href = 'listar_pacientes.php';
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro!',
                                    text: 'Ocorreu um erro ao realizar o cadastro: ' + (response.error || 'Erro desconhecido'),
                                    confirmButtonColor: '#d33'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: 'Ocorreu um erro ao processar a requisição',
                                confirmButtonColor: '#d33'
                            });
                        }
                    });
                }
            });
        });
    });
    </script>
</body>
</html>