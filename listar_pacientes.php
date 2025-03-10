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


// Modificar a query para buscar micro áreas da tabela micro_areas
$sql_micro_areas = "SELECT id, nome FROM micro_areas ORDER BY nome";
$result_micro_areas = $conn->query($sql_micro_areas);
$micro_areas = [];
if ($result_micro_areas) {
    while ($row = $result_micro_areas->fetch_assoc()) {
        $micro_areas[] = $row;
    }
}

// Query SQL diferente baseada no tipo de usuário
if ($is_admin || $is_medico || $is_enfermeiro || $is_acs) {
    // Admin e Profissional veem todos os pacientes
    $sql = "SELECT 
        u.*,
        p.id as paciente_id,
        p.tipo_doenca,
        u.cpf,
        u.micro_area_id
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

        .search-container {
            margin-bottom: 20px;
        }

        .search-container .row {
            margin: 0;
            gap: 15px;
        }

        .search-container .form-control,
        .search-container .form-select {
            height: 38px;
            border-radius: 4px;
            border: 1px solid #ced4da;
        }

        .search-container .form-select {
            cursor: pointer;
        }

        .search-container .form-select:focus,
        .search-container .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .search-container .col-md-6,
            .search-container .col-md-4 {
                margin-bottom: 10px;
            }
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
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .table th:nth-child(1), .table td:nth-child(1) { width: 20%; }
        .table th:nth-child(2), .table td:nth-child(2) { width: 15%; }
        .table th:nth-child(3), .table td:nth-child(3) { width: 20%; }
        .table th:nth-child(4), .table td:nth-child(4) { width: 15%; }
        .table th:nth-child(5), .table td:nth-child(5) { width: 10%; }
        .table th:nth-child(6), .table td:nth-child(6) { width: 20%; }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: rgb(255, 255, 255);
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .btn-group {
            white-space: nowrap;
            display: flex;
            gap: 5px;
        }

        .btn {
            padding: 6px 12px;
            font-size: 0.875rem;
            line-height: 1.5;
            white-space: nowrap;
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
            <div class="search-container">
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" 
                               id="busca" 
                               class="form-control" 
                               placeholder="Buscar por nome, CPF, email ou telefone...">
                    </div>
                    
                    <div class="col-md-4">
                        <select id="micro_area_filter" class="form-select">
                            <option value="">Todas as Micro Áreas</option>
                            <?php foreach ($micro_areas as $area): ?>
                                <option value="<?php echo htmlspecialchars($area['id']); ?>">
                                    <?php echo htmlspecialchars($area['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
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
                        <tr data-usuario-id="<?php echo $usuario['id']; ?>" 
                            data-micro-area="<?php echo htmlspecialchars($usuario['micro_area_id']); ?>">
                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['telefone']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['numero_familia']); ?></td>
                            <td>
                                <button class="btn btn-primary btn-editar-usuario" 
                                        onclick="abrirModalEditarUsuario(<?php echo $usuario['id']; ?>)">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <?php if ($usuario['paciente_id']): ?>
                                    <a href="editar_paciente.php?id=<?php echo $usuario['paciente_id']; ?>" 
                                       class="btn btn-success">
                                        <i class="fas fa-user-md"></i> Dados Clínicos
                                    </a>
                                <?php else: ?>
                                    <a href="cadastro_paciente.php?id=<?php echo $usuario['id']; ?>" 
                                       class="btn btn-warning">
                                        <i class="fas fa-user-plus"></i> Completar
                                    </a>
                                <?php endif; ?>

                                <?php if (($is_admin || $is_medico || $is_enfermeiro) && !$is_paciente && !$is_acs): ?>
                                    <button class="btn btn-danger" onclick="deletarUsuario(<?php echo $usuario['id']; ?>)">
                                        <i class="fas fa-trash"></i> Excluir
                                    </button>
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

                                <?php if ($is_admin || $is_medico || $is_enfermeiro || $is_acs): ?>
                                    <div class="col-md-4">
                                        <label for="edit_micro_area" class="form-label">
                                            <i class="fas fa-map-marked-alt"></i> Micro Área*
                                        </label>
                                        <div class="input-group">
                                            <select class="form-select" id="edit_micro_area" name="micro_area_id" required>
                                                <option value="">Selecione</option>
                                                <?php foreach ($micro_areas as $area): ?>
                                                    <option value="<?php echo htmlspecialchars($area['id']); ?>">
                                                        <?php echo htmlspecialchars($area['nome']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="btn btn-primary" onclick="abrirModalMicroArea()">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger" onclick="deletarMicroArea()" id="btn-deletar-micro-area-edit" disabled>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
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

    <!-- Adicionar o Modal de Micro Área após os outros modais -->
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($is_admin || $is_medico || $is_enfermeiro || $is_acs): ?>
    <script>
    function filtrarPacientes() {
        const input = document.getElementById('busca');
        const filter = input.value.toLowerCase();
        const microAreaSelecionada = document.getElementById('micro_area_filter').value;
        const tbody = document.getElementById('pacientes-tbody');
        const rows = tbody.getElementsByTagName('tr');

        for (let row of rows) {
            const nome = row.cells[0].textContent.toLowerCase();
            const cpf = row.cells[1].textContent.toLowerCase();
            const email = row.cells[2].textContent.toLowerCase();
            const telefone = row.cells[3].textContent.toLowerCase();
            const microAreaUsuario = row.getAttribute('data-micro-area');
            
            const matchTexto = nome.includes(filter) || 
                              cpf.includes(filter) || 
                              email.includes(filter) || 
                              telefone.includes(filter);
                              
            const matchMicroArea = !microAreaSelecionada || microAreaSelecionada === microAreaUsuario;

            if (matchTexto && matchMicroArea) {
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

                    // Adicionar preenchimento da micro área
                    if ($('#edit_micro_area').length) {
                        $('#edit_micro_area').val(response.data.micro_area_id).trigger('change');
                    }

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

    // Função para filtrar por micro área
    document.getElementById('micro_area_filter').addEventListener('change', function() {
        const microAreaSelecionada = this.value;
        const tbody = document.getElementById('pacientes-tbody');
        const rows = tbody.getElementsByTagName('tr');

        for (let row of rows) {
            const microAreaUsuario = row.getAttribute('data-micro-area');
            
            if (!microAreaSelecionada || microAreaSelecionada === microAreaUsuario) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });

    // Adicionar evento de busca também ao select de micro área
    document.getElementById('micro_area_filter').addEventListener('change', filtrarPacientes);
    document.getElementById('busca').addEventListener('input', filtrarPacientes);
    </script>

    <script>
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
                        // Adiciona a nova opção aos selects
                        const novaOption = new Option(novaMicroArea, data.id);
                        $('#edit_micro_area').append(novaOption);
                        $('#micro_area_filter').append(novaOption);
                        
                        // Fecha o modal
                        $('#modalMicroArea').modal('hide');
                        
                        // Limpa o campo
                        $('#nova_micro_area').val('');
                        
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

    // Adicionar o event listener para o select de micro área
    document.getElementById('edit_micro_area')?.addEventListener('change', function() {
        const btnDeletar = document.getElementById('btn-deletar-micro-area-edit');
        btnDeletar.disabled = !this.value;
    });
    </script>

    <script>
    function deletarUsuario(usuarioId) {
        Swal.fire({
            title: 'Tem certeza?',
            text: "Esta ação não poderá ser revertida!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'deletar_usuario.php',
                    type: 'POST',
                    data: { usuario_id: usuarioId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: 'Usuário excluído com sucesso!',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: response.message || 'Erro ao excluir usuário'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Erro ao processar a requisição'
                        });
                    }
                });
            }
        });
    }
    </script>
</body>
</html>