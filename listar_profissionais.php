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
$tipo_usuario = $_SESSION['tipo_usuario'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? null;

// Query SQL baseada no tipo de usuário
if ($tipo_usuario === 'Admin') {
    // Admin vê todos os profissionais e ACS
    $sql = "SELECT u.id as usuario_id, 
            u.nome, u.email, u.telefone, u.tipo_usuario,
            p.id as profissional_id,
            p.especialidade, p.registro_profissional, p.unidade_saude 
            FROM usuarios u 
            LEFT JOIN profissionais p ON u.id = p.usuario_id 
            WHERE u.tipo_usuario IN ('Medico', 'Enfermeiro', 'ACS')
            ORDER BY u.tipo_usuario, u.nome";
} elseif ($tipo_usuario === 'ACS') {
    // ACS vê apenas seu próprio perfil
    $sql = "SELECT u.id as usuario_id, 
            u.nome, u.email, u.telefone, u.tipo_usuario,
            p.id as profissional_id,
            p.especialidade, p.registro_profissional, p.unidade_saude 
            FROM usuarios u 
            LEFT JOIN profissionais p ON u.id = p.usuario_id 
            WHERE u.id = ?
            ORDER BY u.nome";
} else {
    // Médico ou Enfermeiro vê apenas seu próprio perfil
    $sql = "SELECT u.id as usuario_id, 
            u.nome, u.email, u.telefone, u.tipo_usuario,
            p.id as profissional_id,
            p.especialidade, p.registro_profissional, p.unidade_saude 
            FROM usuarios u 
            LEFT JOIN profissionais p ON u.id = p.usuario_id 
            WHERE u.id = ?";
}

$stmt = $conn->prepare($sql);
if ($tipo_usuario !== 'Admin') {
    // Bind o parâmetro para ACS, Médico e Enfermeiro
    $stmt->bind_param("i", $usuario_id);
}
$stmt->execute();

$profissionais = $stmt->get_result();

// Ajusta o título baseado no tipo de usuário
$titulo = $tipo_usuario === 'Admin' ? "Profissionais de Saúde" : ($tipo_usuario === 'ACS' ? "Outros ACS" : "Meu Perfil Profissional");

// Mova os arrays para o PHP
$especialidades = [
    "Medicina de Família e Comunidade"
];

$unidades = [
    "USF Santa Rita Avencas"
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profissionais</title>
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

        .filters {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
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

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .btn {
            padding: 8px 16px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }

        .status-pendente {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-completo {
            background-color: #d4edda;
            color: #155724;
        }

        /* Estilização do Select2 */
        .select2-container--default .select2-selection--single {
            height: 42px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 6px 12px;
            background-color: #fff;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .select2-container--default .select2-selection--single:focus,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #495057;
            line-height: 28px;
            padding-left: 0;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
            right: 6px;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #4CAF50;
        }

        .select2-dropdown {
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .select2-search__field {
            border: 1px solid #ced4da !important;
            border-radius: 4px !important;
            padding: 6px !important;
        }

        .select2-search__field:focus {
            border-color: #80bdff !important;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .select2-results__option {
            padding: 8px 12px;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #6c757d;
        }

        /* Correção para o Select2 aparecer sobre o modal */
        .select2-container--open {
            z-index: 9999;
        }

        /* Ajuste adicional para garantir que o dropdown fique sobre outros elementos */
        .select2-dropdown {
            z-index: 9999;
        }

        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .modal-header {
            padding: 20px 25px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .modal-body {
            padding: 2rem;
        }

        /* Animação do Modal */
        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out;
        }

        .modal.fade.show .modal-dialog {
            transform: none;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .form-floating > label {
            padding: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }

        .btn-primary:hover {
            background-color: #45a049;
            border-color: #45a049;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #5a6268;
            transform: translateY(-2px);
        }

        .invalid-feedback {
            font-size: 0.875rem;
            color: #dc3545;
            margin-top: 0.25rem;
        }

        /* Ícones */
        .fas {
            width: 20px;
            text-align: center;
        }

        /* Estilo específico para o campo de registro */
        .form-floating input[type="text"] {
            text-transform: uppercase;
        }

        .form-floating input[type="text"]::placeholder {
            text-transform: none;
        }

        /* Feedback visual durante a digitação */
        .form-floating input[type="text"]:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }

        /* Estilo para o ícone dentro do label */
        .form-floating label i {
            color: #6c757d;
        }

        /* Estilos para o Select2 */
        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 0.375rem 0.75rem;
            min-height: 60px;
            display: flex;
            align-items: center;
        }

        .select2-container--bootstrap-5 .select2-selection--single {
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 10px;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            color: #212529;
            line-height: 38px;
            padding-left: 5px;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 58px;
            position: absolute;
            top: 1px;
            right: 1px;
            width: 20px;
        }

        .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 8px;
        }

        .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
            background-color: #4CAF50;
            color: white;
        }

        .select2-container--bootstrap-5 .select2-results__option {
            padding: 8px 12px;
        }

        .select2-dropdown {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Ajuste para o label flutuante */
        .form-floating > .select2-container {
            padding-top: 1.625rem;
        }

        .form-floating > label {
            padding: 1rem 0.75rem;
            height: auto;
            pointer-events: none;
            transform-origin: 0 0;
            transition: opacity .1s ease-in-out,transform .1s ease-in-out;
        }

        /* Ajuste para quando o select está focado ou tem valor selecionado */
        .form-floating > .select2-container ~ label {
            opacity: .65;
            transform: scale(.85) translateY(-0.5rem) translateX(0.15rem);
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

            .table-container {
                overflow-x: scroll;
            }

            .btn {
                padding: 4px 8px;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $titulo; ?></h1>

        <!-- Remove a busca se não for admin -->
        <?php if ($tipo_usuario === 'Admin'): ?>

                <div class="search-box">
                    <input type="text" id="busca" class="form-control" onkeyup="filtrarProfissionais()" 
                           placeholder="Buscar por nome, especialidade ou unidade...">
                </div>
            
        <?php endif; ?>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> Nome</th>
                        <th><i class="fas fa-envelope"></i> Email</th>
                        <th><i class="fas fa-phone"></i> Telefone</th>
                        <th><i class="fas fa-user-md"></i> Profissional</th>
                        <th><i class="fas fa-stethoscope"></i> Especialidade</th>
                        <th><i class="fas fa-id-card"></i> Registro Profissional</th>
                        <th><i class="fas fa-hospital"></i> Unidade de Saúde</th>
                        <th><i class="fas fa-cog"></i> Ação</th>
                    </tr>
                </thead>
                <tbody id="profissionais-tbody">
                    <?php foreach ($profissionais as $profissional): ?>
                        <tr data-usuario-id="<?php echo $profissional['usuario_id']; ?>">
                            <td><?php echo htmlspecialchars($profissional['nome']); ?></td>
                            <td><?php echo htmlspecialchars($profissional['email']); ?></td>
                            <td><?php echo htmlspecialchars($profissional['telefone']); ?></td>
                            <td><?php echo htmlspecialchars($profissional['tipo_usuario']); ?></td>
                            <td><?php echo htmlspecialchars($profissional['especialidade'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($profissional['registro_profissional'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($profissional['unidade_saude'] ?? ''); ?></td>
                            <td>
                                <?php if ($profissional['especialidade']): ?>
                                    <button onclick="abrirModalEditar(<?php echo $profissional['profissional_id']; ?>, <?php 
                                        echo $profissional['usuario_id']; ?>)" 
                                        class="btn btn-primary"><i class="fas fa-user-edit"></i> Editar</button>
                                <?php else: ?>
                                    <button onclick="abrirModalCadastro(<?php 
                                        echo $profissional['usuario_id']; ?>)" 
                                        class="btn btn-warning"><i class="fas fa-user-plus"></i> Cadastrar</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Cadastro -->
    <div class="modal fade" id="modalCadastro" tabindex="-1" aria-labelledby="modalCadastroLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCadastroLabel">Cadastro de Profissional</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formCadastro" method="POST">
                        <input type="hidden" id="usuario_id" name="usuario_id">
                        <?php if ($tipo_usuario === 'Admin' || $tipo_usuario === 'Medico'): ?>
                            <div class="mb-3">
                                <label for="especialidade" class="form-label">
                                    <i class="fas fa-stethoscope"></i> Especialidade
                                </label>
                                <select class="form-select" id="especialidade" name="especialidade" required>
                                    <option value="">Selecione uma especialidade</option>
                                    <?php foreach ($especialidades as $esp): ?>
                                        <option value="<?php echo htmlspecialchars($esp); ?>"><?php echo htmlspecialchars($esp); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ($tipo_usuario !== 'ACS'): ?>
                            <div class="mb-3">
                                <label for="registro_profissional" class="form-label">
                                    <i class="fas fa-id-card"></i> 
                                    <?php echo ($tipo_usuario === 'Enfermeiro') ? 'COREN' : 
                                          ($tipo_usuario === 'Medico' ? 'CRM' : 'CRM/COREN'); ?>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="registro_profissional" 
                                       name="registro_profissional" 
                                       required
                                       data-tipo-usuario="<?php echo strtolower($tipo_usuario); ?>"
                                       placeholder="<?php echo ($tipo_usuario === 'Enfermeiro') ? '000.000-XX/UF' : '000000/UF'; ?>">
                            </div>
                        <?php endif; ?>

                        <?php if ($tipo_usuario === 'ACS'): ?>
                            <div class="mb-3">
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

                        <div class="mb-3">
                            <label for="unidade_saude" class="form-label">
                                <i class="fas fa-hospital"></i> Unidade de Saúde
                            </label>
                            <select class="form-select" id="unidade_saude" name="unidade_saude" required>
                                <option value="">Selecione uma unidade</option>
                                <?php foreach ($unidades as $uni): ?>
                                    <option value="<?php echo htmlspecialchars($uni); ?>"><?php echo htmlspecialchars($uni); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalEditarLabel"><i class="fas fa-user-edit"></i> Editar Profissional</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditar" method="POST">
                        <input type="hidden" id="edit_profissional_id" name="profissional_id">
                        <input type="hidden" id="edit_usuario_id" name="usuario_id">

                        <?php if ($tipo_usuario === 'Admin' || $tipo_usuario === 'Medico'): ?>
                            <div class="mb-3">
                                <label for="edit_especialidade" class="form-label">
                                    <i class="fas fa-stethoscope"></i> Especialidade
                                </label>
                                <select class="form-select" id="edit_especialidade" name="especialidade" required>
                                    <option value="">Selecione uma especialidade</option>
                                    <?php foreach ($especialidades as $esp): ?>
                                        <option value="<?php echo htmlspecialchars($esp); ?>"><?php echo htmlspecialchars($esp); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ($tipo_usuario !== 'ACS'): ?>
                            <div class="mb-3">
                                <label for="edit_registro_profissional" class="form-label">
                                    <i class="fas fa-id-card"></i>
                                    <?php echo ($tipo_usuario === 'Enfermeiro') ? 'COREN' : 
                                          ($tipo_usuario === 'Medico' ? 'CRM' : 'CRM/COREN'); ?>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="edit_registro_profissional" 
                                       name="registro_profissional" 
                                       required
                                       data-tipo-usuario="<?php echo strtolower($tipo_usuario); ?>"
                                       placeholder="<?php echo ($tipo_usuario === 'Enfermeiro') ? '000.000-XX/UF' : '000000/UF'; ?>">
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="edit_unidade_saude" class="form-label">
                                <i class="fas fa-hospital"></i> Unidade de Saúde
                            </label>
                            <select class="form-select" id="edit_unidade_saude" name="unidade_saude" required>
                                <option value="">Selecione uma unidade</option>
                                <?php foreach ($unidades as $uni): ?>
                                    <option value="<?php echo htmlspecialchars($uni); ?>"><?php echo htmlspecialchars($uni); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Atualizar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Micro Área -->
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let modalCadastro, modalEditar;

            // Inicializar os modais
            try {
                modalCadastro = new bootstrap.Modal(document.getElementById('modalCadastro'));
                modalEditar = new bootstrap.Modal(document.getElementById('modalEditar'));
            } catch (error) {
                console.error('Erro ao inicializar modais:', error);
            }

            // Adicionar o tipo de usuário como variável JavaScript
            const tipoUsuario = <?php echo json_encode($tipo_usuario); ?>;

            // Disponibilizar os arrays para JavaScript
            const especialidades = <?php echo json_encode($especialidades); ?>;
            const unidades = <?php echo json_encode($unidades); ?>;

            // Função para aplicar a máscara correta baseada no tipo de usuário
            function aplicarMascaraRegistro(registroInput) {
                const $registro = $(registroInput);
                const tipoUsuario = $registro.data('tipo-usuario');
                
                // Remove máscaras anteriores
                $registro.unmask();
                
                // Aplica a máscara apropriada baseada no tipo de usuário
                if (tipoUsuario.toLowerCase() === 'enfermeiro') {
                    // COREN: 000.000-AA/UF
                    $registro.mask('000.000-AA/AA', {
                        translation: {
                            'A': { pattern: /[A-Za-z]/ }
                        },
                        onKeyPress: function(coren, e, field, options) {
                            const value = $(field).val();
                            $(field).val(value.toUpperCase());
                        }
                    });
                    $registro.attr('placeholder', '000.000-XX/UF');
                    // Atualiza a label dinamicamente
                    $registro.closest('.mb-3').find('label').text('COREN');
                } 
                else if (tipoUsuario.toLowerCase() === 'medico') {
                    // CRM: 000000/UF
                    $registro.mask('000000/AA', {
                        translation: {
                            'A': { pattern: /[A-Za-z]/ }
                        },
                        onKeyPress: function(crm, e, field, options) {
                            const value = $(field).val();
                            $(field).val(value.toUpperCase());
                        }
                    });
                    $registro.attr('placeholder', '000000/UF');
                    // Atualiza a label dinamicamente
                    $registro.closest('.mb-3').find('label').text('CRM');
                }
            }

            // Aplicar as máscaras quando os modais são abertos
            $('#modalCadastro').on('shown.bs.modal', function () {
                aplicarMascaraRegistro('#registro_profissional');
            });

            $('#modalEditar').on('shown.bs.modal', function () {
                aplicarMascaraRegistro('#edit_registro_profissional');
            });

            // Validação dos formulários
            ['#formCadastro', '#formEditar'].forEach(formSelector => {
                $(formSelector).off('submit').on('submit', function(e) {
                    e.preventDefault();
                    
                    let isValid = true;
                    let mensagem = '';

                    // Criar FormData
                    const formData = new FormData(this);
                    const registro = $(this).find('[name="registro_profissional"]').val();
                    const isEditForm = formSelector === '#formEditar';
                    
                    // Validações
                    if (tipoUsuario.toLowerCase() === 'acs') {
                        formData.set('especialidade', 'ACS');
                        formData.set('registro_profissional', null);
                    } 
                    else if (tipoUsuario.toLowerCase() === 'enfermeiro') {
                        formData.set('especialidade', 'Enfermeiro');
                        const corenRegex = /^\d{3}\.\d{3}-[A-Z]{2}\/[A-Z]{2}$/;
                        if (!corenRegex.test(registro)) {
                            isValid = false;
                            mensagem = 'COREN inválido. Use o formato: 000.000-XX/UF';
                        }
                    }
                    else if (tipoUsuario.toLowerCase() === 'medico') {
                        const crmRegex = /^\d{6}\/[A-Z]{2}$/;
                        if (!crmRegex.test(registro)) {
                            isValid = false;
                            mensagem = 'CRM inválido. Use o formato: 000000/UF';
                        }
                    }

                    if (!isValid) {
                        Swal.fire({
                            title: 'Atenção!',
                            text: mensagem,
                            icon: 'warning',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#4CAF50'
                        });
                        $(this).find('[name="registro_profissional"]').focus();
                        return false;
                    }

                    // Confirmar ação com SweetAlert2
                    Swal.fire({
                        title: isEditForm ? 'Confirmar Edição' : 'Confirmar Cadastro',
                        text: isEditForm ? 
                              'Deseja salvar as alterações realizadas?' : 
                              'Deseja confirmar o cadastro do profissional?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#4CAF50',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sim, salvar',
                        cancelButtonText: 'Cancelar',
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            // Desabilitar o botão de envio para evitar múltiplos envios
                            const submitButton = $(this).find('button[type="submit"]');
                            submitButton.prop('disabled', true);

                            const url = isEditForm ? 'atualizar_profissional.php' : 'salvar_profissional.php';

                            return fetch(url, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (!data.success) {
                                    throw new Error(data.message || 'Erro ao processar a requisição');
                                }
                                return data;
                            })
                            .catch(error => {
                                Swal.showValidationMessage(`Erro: ${error.message}`);
                            })
                            .finally(() => {
                                submitButton.prop('disabled', false);
                            });
                        },
                        allowOutsideClick: () => !Swal.isLoading()
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const data = result.value;
                            // Atualizar a tabela dinamicamente
                            const usuarioId = formData.get('usuario_id');
                            const row = $(`tr[data-usuario-id="${usuarioId}"]`);
                            
                            // Criar objeto com os novos dados
                            const novosDados = {
                                especialidade: formData.get('especialidade'),
                                registro_profissional: formData.get('registro_profissional'),
                                unidade_saude: formData.get('unidade_saude')
                            };

                            // Atualizar a linha existente
                            if (row.length) {
                                row.find('td:eq(4)').text(novosDados.especialidade);
                                row.find('td:eq(5)').text(novosDados.registro_profissional || '');
                                row.find('td:eq(6)').text(novosDados.unidade_saude);
                                row.find('td:eq(7)').html('<span class="status-badge status-completo">Cadastro Completo</span>');
                                row.find('td:eq(8)').html(`<button onclick="abrirModalEditar(${data.profissional_id}, ${usuarioId})" class="btn btn-primary">Editar</button>`);
                            }

                            // Fechar o modal
                            if (isEditForm) {
                                modalEditar.hide();
                            } else {
                                modalCadastro.hide();
                            }

                            // Mostrar mensagem de sucesso
                            Swal.fire({
                                title: 'Sucesso!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#4CAF50'
                            }).then(() => {
                                location.reload();
                            });
                        }
                    });
                });
            });

            // Inicializar Select2 para o modal de cadastro
            $('#especialidade, #unidade_saude').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('#modalCadastro .modal-body'),
                placeholder: 'Selecione uma opção',
                allowClear: true
            });

            // Inicializar Select2 para o modal de edição
            $('#edit_especialidade, #edit_unidade_saude').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('#modalEditar .modal-body'),
                placeholder: 'Selecione uma opção',
                allowClear: true
            });

            // Ajustar z-index dos dropdowns
            $(document).on('select2:open', () => {
                document.querySelector('.select2-container--open .select2-search__field').focus();
            });

            // Recarregar Select2 quando o modal for aberto
            $('#modalCadastro').on('shown.bs.modal', function () {
                $('#especialidade').select2('destroy').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#modalCadastro .modal-body'),
                    placeholder: 'Selecione uma especialidade',
                    allowClear: true
                });
            });

            $('#modalEditar').on('shown.bs.modal', function () {
                $('#edit_especialidade').select2('destroy').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#modalEditar .modal-body'),
                    placeholder: 'Selecione uma especialidade',
                    allowClear: true
                });
            });

            // Ajusta a altura do container do Select2
            $('.select2-container--bootstrap-5 .select2-selection').css('height', '60px');

            // Ajustar z-index do dropdown do Select2
            $('.select2-dropdown').css('z-index', 9999);
            
            // Habilitar o campo de busca
            $('#busca').prop('disabled', false);

            // Mova as funções para o escopo global
            window.filtrarProfissionais = function() {
                const input = document.getElementById('busca');
                const filter = input.value.toLowerCase();
                const tbody = document.getElementById('profissionais-tbody');
                const rows = tbody.getElementsByTagName('tr');

                for (let row of rows) {
                    const nome = row.cells[0].textContent.toLowerCase();
                    const email = row.cells[1].textContent.toLowerCase();
                    const telefone = row.cells[2].textContent.toLowerCase();
                    const especialidade = row.cells[3].textContent.toLowerCase();
                    const registro = row.cells[4].textContent.toLowerCase();
                    const unidade = row.cells[5].textContent.toLowerCase();
                    
                    const matchTermo = nome.includes(filter) || 
                                    email.includes(filter) ||
                                    telefone.includes(filter) ||
                                    especialidade.includes(filter) || 
                                    registro.includes(filter) ||
                                    unidade.includes(filter);

                    row.style.display = matchTermo ? '' : 'none';
                }
            }

            window.abrirModalCadastro = function(usuarioId) {
                document.getElementById('usuario_id').value = usuarioId;
                $('#especialidade').val(null).trigger('change');
                $('#unidade_saude').val(null).trigger('change');
                $('#registro_profissional').val('');
                modalCadastro.show();
            }

            window.abrirModalEditar = function(profissionalId, usuarioId) {
                document.getElementById('edit_profissional_id').value = profissionalId;
                document.getElementById('edit_usuario_id').value = usuarioId;
                
                fetch(`buscar_profissional.php?id=${profissionalId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Dados recebidos:', data); // Para debug
                        
                        if (data.success && data.profissional) {
                            const profissional = data.profissional;
                            
                            // Preencher os campos do formulário
                            if ($('#edit_especialidade').length) {
                                $('#edit_especialidade').val(profissional.especialidade).trigger('change');
                            }
                            
                            if ($('#edit_registro_profissional').length) {
                                $('#edit_registro_profissional').val(profissional.registro_profissional);
                            }
                            
                            if ($('#edit_unidade_saude').length) {
                                $('#edit_unidade_saude').val(profissional.unidade_saude).trigger('change');
                            }
                            
                            modalEditar.show();
                        } else {
                            alert(data.message || 'Profissional não encontrado.');
                        }
                    })
                    .catch(error => {
                        handleError(error, 'carregar os dados do profissional');
                    });
            }

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
        });

        // Adicione esta função no início do seu arquivo
        function handleError(error, context) {
            console.error(`Erro no contexto: ${context}`, error);
            Swal.fire({
                title: 'Erro!',
                text: `Ocorreu um erro ao ${context}. Por favor, tente novamente.`,
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#4CAF50'
            });
        }
    </script>
</body>
</html>