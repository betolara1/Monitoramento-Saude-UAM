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

// Verifica o tipo de usuário
$is_admin = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'Admin';
$is_medico = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'Medico';
$is_enfermeiro = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'Enfermeiro';
$is_paciente = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'Paciente';
$usuario_id = $_SESSION['usuario_id'] ?? null;

// Query SQL diferente baseada no tipo de usuário
if ($is_admin || $is_medico || $is_enfermeiro || $is_acs) {
    // Admin e Profissional veem todos os pacientes
    $sql = "SELECT 
        u.*,
        p.id as paciente_id,
        p.tipo_doenca,
        u.cpf
        FROM usuarios u 
        LEFT JOIN pacientes p ON u.id = p.usuario_id 
        WHERE u.tipo_usuario = 'Paciente' 
        ORDER BY u.nome";
    $stmt = $conn->prepare($sql);
} else {
    // Paciente vê apenas seu próprio registro
    $sql = "SELECT 
        u.*,
        p.id as paciente_id,
        p.tipo_doenca,
        u.cpf
        FROM usuarios u 
        LEFT JOIN pacientes p ON u.id = p.usuario_id 
        WHERE u.tipo_usuario = 'Paciente' 
        AND u.id = ?
        ORDER BY u.nome";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
}

$stmt->execute();
$usuarios = $stmt->get_result();

// Ajusta o título baseado no tipo de usuário
$titulo = ($is_admin || $is_medico || $is_enfermeiro || $is_acs) ? "Lista de Pacientes" : "Meus Dados";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .search-box {
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .btn-editar {
            background-color: #0d6efd;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .btn-editar:hover {
            background-color: #0b5ed7;
            color: white;
            text-decoration: none;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-cadastrado {
            background-color: #198754;
            color: white;
        }

        .status-pendente {
            background-color: #ffc107;
            color: #000;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            th, td {
                padding: 10px;
            }

            .btn-editar {
                padding: 6px 12px;
            }
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            width: 100%;
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            background-color: transparent;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px;
            vertical-align: middle;
            border-top: 1px solid #dee2e6;
            word-wrap: break-word;
            white-space: normal;
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .btn {
            display: inline-block;
            margin: 2px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-completo {
            background-color: #198754;
            color: white;
        }

        .status-pendente {
            background-color: #ffc107;
            color: #000;
        }

        .btn-primary {
            background-color: #4CAF50;
            border-color: #4CAF50;
            color: white;
        }

        .btn-primary:hover {
            background-color: #45a049;
            border-color: #45a049;
            transform: translateY(-2px);
        }

    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $titulo; ?></h1>
        
        <?php if ($is_admin || $is_medico || $is_enfermeiro || $is_acs): ?>
            <div class="search-box">
                <input type="text" 
                       id="busca" 
                       class="form-control" 
                       onkeyup="filtrarPacientes()" 
                       placeholder="Buscar por nome, CPF, email ou telefone...">
            </div>
        <?php endif; ?>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> Nome</th>
                        <th><i class="fas fa-id-card"></i> CPF</th>
                        <th><i class="fas fa-envelope"></i> Email</th>
                        <th><i class="fas fa-phone"></i> Telefone</th>
                        <th><i class="fas fa-phone"></i> N° da Família</th>
                        <th><i class="fas fa-cog"></i> Ações</th>
                    </tr>
                </thead>
                <tbody id="pacientes-tbody">
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr data-usuario-id="<?php echo $usuario['id']; ?>">
                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['telefone']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['numero_familia']); ?></td>
                            <td>
                                <button class="btn btn-primary btn-editar-usuario" 
                                        onclick="abrirModalEditarUsuario(<?php echo $usuario['id']; ?>)">
                                    <i class="fas fa-edit"></i> Editar Cadastro
                                </button>
                                <?php if ($usuario['paciente_id']): ?>
                                    <a href="editar_paciente.php?id=<?php echo $usuario['paciente_id']; ?>" 
                                       class="btn btn-success">
                                        <i class="fas fa-user-md"></i> Dados Clínicos
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-user-edit"></i> Editar Cadastro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarUsuario">
                        <input type="hidden" id="usuario_id" name="usuario_id">
                        
                        <!-- Informações Pessoais -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-user"></i> Informações Pessoais
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="edit_nome" class="form-label">
                                            <i class="fas fa-user"></i> Nome Completo*
                                        </label>
                                        <input type="text" class="form-control" id="edit_nome" name="nome" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit_cpf" class="form-label">
                                            <i class="fas fa-id-card"></i> CPF*
                                        </label>
                                        <input type="text" class="form-control" id="edit_cpf" name="cpf" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="edit_data_nascimento" class="form-label">
                                            <i class="fas fa-birthday-cake"></i> Data de Nascimento*
                                        </label>
                                        <input type="date" class="form-control" id="edit_data_nascimento" name="data_nascimento" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="edit_sexo" class="form-label">
                                            <i class="fas fa-venus-mars"></i> Sexo*
                                        </label>
                                        <select class="form-select" id="edit_sexo" name="sexo" required>
                                            <option value="">Selecione</option>
                                            <option value="M">Masculino</option>
                                            <option value="F">Feminino</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="edit_numero_familia" class="form-label">
                                            <i class="fas fa-users"></i> N° da Família*
                                        </label>
                                        <input type="text" class="form-control" id="edit_numero_familia" name="numero_familia" required placeholder="00000000">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informações de Contato -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-address-card"></i> Informações de Contato
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="edit_email" class="form-label">
                                            <i class="fas fa-envelope"></i> Email*
                                        </label>
                                        <input type="email" class="form-control" id="edit_email" name="email" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit_telefone" class="form-label">
                                            <i class="fas fa-phone"></i> Telefone*
                                        </label>
                                        <input type="text" class="form-control" id="edit_telefone" name="telefone" required>
                                    </div>
                                </div>
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
                                        <label for="edit_cep" class="form-label">
                                            <i class="fas fa-map-marker-alt"></i> CEP*
                                        </label>
                                        <input type="text" class="form-control" id="edit_cep" name="cep" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit_rua" class="form-label">
                                            <i class="fas fa-road"></i> Rua
                                        </label>
                                        <input type="text" class="form-control" id="edit_rua" name="rua">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="edit_numero" class="form-label">
                                            <i class="fas fa-home"></i> Número*
                                        </label>
                                        <input type="text" class="form-control" id="edit_numero" name="numero" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="edit_bairro" class="form-label">
                                            <i class="fas fa-map"></i> Bairro
                                        </label>
                                        <input type="text" class="form-control" id="edit_bairro" name="bairro">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="edit_cidade" class="form-label">
                                            <i class="fas fa-city"></i> Cidade
                                        </label>
                                        <input type="text" class="form-control" id="edit_cidade" name="cidade">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="edit_estado" class="form-label">
                                            <i class="fas fa-flag"></i> Estado
                                        </label>
                                        <input type="text" class="form-control" id="edit_estado" name="estado">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="edit_complemento" class="form-label">
                                            <i class="fas fa-info-circle"></i> Complemento
                                        </label>
                                        <input type="text" class="form-control" id="edit_complemento" name="complemento">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="salvarEdicao()">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($is_admin || $is_medico || $is_enfermeiro || $is_acs): ?>
    <script>
    function filtrarPacientes() {
        const input = document.getElementById('busca');
        const filter = input.value.toLowerCase();
        const tbody = document.getElementById('pacientes-tbody');
        const rows = tbody.getElementsByTagName('tr');

        for (let row of rows) {
            const nome = row.cells[0].textContent.toLowerCase();
            const cpf = row.cells[1].textContent.toLowerCase();
            const email = row.cells[2].textContent.toLowerCase();
            const telefone = row.cells[3].textContent.toLowerCase();
            
            if (nome.includes(filter) || 
                cpf.includes(filter) || 
                email.includes(filter) || 
                telefone.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }
    </script>
    <?php endif; ?>

    <script>
    function abrirModalEditarUsuario(usuarioId) {
        // Buscar dados do usuário
        $.ajax({
            url: 'buscar_usuario.php',
            type: 'POST',
            data: { usuario_id: usuarioId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Preencher o formulário com os dados
                    $('#usuario_id').val(usuarioId);
                    $('#edit_nome').val(response.data.nome);
                    $('#edit_cpf').val(response.data.cpf);
                    $('#edit_email').val(response.data.email);
                    $('#edit_telefone').val(response.data.telefone);
                    $('#edit_cep').val(response.data.cep);
                    $('#edit_rua').val(response.data.rua);
                    $('#edit_numero').val(response.data.numero);
                    $('#edit_bairro').val(response.data.bairro);
                    $('#edit_cidade').val(response.data.cidade);
                    $('#edit_estado').val(response.data.estado);
                    $('#edit_complemento').val(response.data.complemento);
                    $('#edit_data_nascimento').val(response.data.data_nascimento);
                    $('#edit_sexo').val(response.data.sexo);
                    $('#edit_numero_familia').val(response.data.numero_familia);

                    // Abrir o modal
                    new bootstrap.Modal(document.getElementById('modalEditarUsuario')).show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao carregar dados do usuário'
                    });
                }
            }
        });
    }

    function salvarEdicao() {
        const formData = new FormData(document.getElementById('formEditarUsuario'));
        
        $.ajax({
            url: 'atualizar_usuario.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Fechar o modal
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarUsuario')).hide();
                    
                    // Atualizar a linha da tabela
                    const usuarioId = formData.get('usuario_id');
                    const row = document.querySelector(`tr[data-usuario-id="${usuarioId}"]`);
                    if (row) {
                        row.cells[0].textContent = formData.get('nome');
                        row.cells[1].textContent = formData.get('cpf');
                        row.cells[2].textContent = formData.get('email');
                        row.cells[3].textContent = formData.get('telefone');
                        row.cells[4].textContent = formData.get('numero_familia');
                    }

                    // Mostrar mensagem de sucesso
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'Cadastro atualizado com sucesso!'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: response.message || 'Erro ao atualizar cadastro'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Erro ao processar a requisição'
                });
            }
        });
    }

    // Aplicar máscaras aos campos
    $(document).ready(function() {
        $('#edit_cpf').mask('000.000.000-00');
        $('#edit_telefone').mask('(00) 00000-0000');
        $('#edit_cep').mask('00000-000');
        $('#edit_numero_familia').mask('00000000');
        
        // Busca CEP
        $('#edit_cep').blur(function() {
            const cep = $(this).val().replace(/\D/g, '');
            if (cep.length === 8) {
                $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function(data) {
                    if (!("erro" in data)) {
                        $('#edit_rua').val(data.logradouro);
                        $('#edit_bairro').val(data.bairro);
                        $('#edit_cidade').val(data.localidade);
                        $('#edit_estado').val(data.uf);
                    }
                });
            }
        });
    });
    </script>
</body>
</html>