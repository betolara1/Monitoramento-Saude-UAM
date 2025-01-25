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
    // ACS vê apenas outros ACS e seu próprio perfil
    $sql = "SELECT u.id as usuario_id, 
            u.nome, u.email, u.telefone, u.tipo_usuario,
            p.id as profissional_id,
            p.especialidade, p.registro_profissional, p.unidade_saude 
            FROM usuarios u 
            LEFT JOIN profissionais p ON u.id = p.usuario_id 
            WHERE u.tipo_usuario = 'ACS'
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
if ($tipo_usuario !== 'Admin' && $tipo_usuario !== 'ACS') {
    $stmt->bind_param("i", $usuario_id);
}
$stmt->execute();

$profissionais = $stmt->get_result();

// Ajusta o título baseado no tipo de usuário
$titulo = $tipo_usuario === 'Admin' ? "Profissionais de Saúde" : ($tipo_usuario === 'ACS' ? "Outros ACS" : "Meu Perfil Profissional");

// Mova os arrays para o PHP
$especialidades = [
    "Acupuntura",
    "Alergia e Imunologia",
    "Anestesiologia",
    "Angiologia",
    "Cardiologia",
    "Cirurgia Cardiovascular",
    "Cirurgia da Mão",
    "Cirurgia de Cabeça e Pescoço",
    "Cirurgia do Aparelho Digestivo",
    "Cirurgia Geral",
    "Cirurgia Pediátrica",
    "Cirurgia Plástica",
    "Cirurgia Torácica",
    "Cirurgia Vascular",
    "Clínica Médica",
    "Coloproctologia",
    "Dermatologia",
    "Endocrinologia e Metabologia",
    "Endoscopia",
    "Enfermeiro",
    "Gastroenterologia",
    "Genética Médica",
    "Geriatria",
    "Ginecologia e Obstetrícia",
    "Hematologia e Hemoterapia",
    "Homeopatia",
    "Infectologia",
    "Mastologia",
    "Medicina de Emergência",
    "Medicina de Família e Comunidade",
    "Medicina do Trabalho",
    "Medicina de Tráfego",
    "Medicina Esportiva",
    "Medicina Física e Reabilitação",
    "Medicina Intensiva",
    "Medicina Legal e Perícia Médica",
    "Medicina Nuclear",
    "Medicina Preventiva e Social",
    "Médico de Família",
    "Nefrologia",
    "Neurocirurgia",
    "Neurologia",
    "Nutrologia",
    "Oftalmologia",
    "Oncologia Clínica",
    "Ortopedia e Traumatologia",
    "Otorrinolaringologia",
    "Patologia",
    "Patologia Clínica/Medicina Laboratorial",
    "Pediatria",
    "Pneumologia",
    "Psiquiatria",
    "Radiologia e Diagnóstico por Imagem",
    "Radioterapia",
    "Reumatologia",
    "Urologia"
];

$unidades = [
    "UBS Alvorada",
    "UBS Cecap",
    "UBS Centro",
    "UBS Independência",
    "UBS Jardim Oriente",
    "UBS Mario Dedini",
    "UBS Parque Orlanda",
    "UBS Paulicéia",
    "UBS Piracicamirim",
    "UBS São Jorge",
    "UBS Vila Cristina",
    "UBS Vila Rezende",
    "UBS Vila Sônia",
    "CRAB Boa Esperança",
    "CRAB Nova América",
    "CRAB Piracicamirim",
    "CRAB Vila Rezende",
    "CRAB Vila Sônia",
    "USF 1° de Maio",
    "USF Algodoal",
    "USF Anhumas",
    "USF Artemis",
    "USF Boa Esperança",
    "USF Chapadão",
    "USF Costa Rica",
    "USF Jardim Gilda",
    "USF Jardim Vitória",
    "USF Monte Líbano",
    "USF Novo Horizonte",
    "USF Santa Fé",
    "USF Santa Rosa",
    "USF Santo Antonio",
    "USF São Francisco",
    "USF Serra Verde",
    "USF Tanquinho",
    "USF Tupi",
    "Santa Casa de Piracicaba",
    "Hospital dos Fornecedores de Cana",
    "Hospital Unimed Piracicaba"
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Usuários - Profissionais</title>
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
        }

        .filters {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .search-box {
            flex: 1;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f5f5f5;
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

        .btn-primary {
            background-color: #4CAF50;
        }

        .btn-primary:hover {
            background-color: #45a049;
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

        /* Animação do Modal */
        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out;
        }

        .modal.fade.show .modal-dialog {
            transform: none;
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
    </style>
    <!-- Adicione estes links no head ou antes do fechamento do body -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>
<body>
    <div class="container">
        <h1><?php echo $titulo; ?></h1>

        <!-- Remove a busca se não for admin -->
        <?php if ($tipo_usuario === 'Admin'): ?>
            <div class="filters">
                <div class="search-box">
                    <input type="text" id="busca" class="form-control" onkeyup="filtrarProfissionais()" 
                           placeholder="Buscar por nome, especialidade ou unidade...">
                </div>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Profissional</th>
                    <th>Especialidade</th>
                    <th>Registro Profissional</th>
                    <th>Unidade de Saúde</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody id="profissionais-tbody">
                <?php foreach ($profissionais as $profissional): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($profissional['nome']); ?></td>
                        <td><?php echo htmlspecialchars($profissional['email']); ?></td>
                        <td><?php echo htmlspecialchars($profissional['telefone']); ?></td>
                        <td><?php echo htmlspecialchars($profissional['tipo_usuario']); ?></td>
                        <td><?php echo htmlspecialchars($profissional['especialidade'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($profissional['registro_profissional'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($profissional['unidade_saude'] ?? ''); ?></td>
                        <td>
                            <?php if ($profissional['especialidade']): ?>
                                <span class="status-badge status-completo">Cadastro Completo</span>
                            <?php else: ?>
                                <span class="status-badge status-pendente">Pendente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($profissional['especialidade']): ?>
                                <button onclick="abrirModalEditar(<?php echo $profissional['profissional_id']; ?>, <?php echo $profissional['usuario_id']; ?>)" class="btn btn-primary">Editar</button>
                            <?php else: ?>
                                <button onclick="abrirModalCadastro(<?php 
                                    echo $profissional['usuario_id']; ?>)" 
                                    class="btn btn-primary">Completar Cadastro</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
                            <label for="especialidade" class="form-label">Especialidade</label>
                            <select class="form-select select2" id="especialidade" name="especialidade" required>
                                <option value="">Selecione uma especialidade</option>
                                <?php foreach ($especialidades as $esp): ?>
                                    <option value="<?php echo htmlspecialchars($esp); ?>"><?php echo htmlspecialchars($esp); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <?php if ($tipo_usuario === 'Enfermeiro' || $tipo_usuario === 'Admin' || $tipo_usuario === 'Medico'): ?>
                        <div class="mb-3">
                            <label for="registro_profissional" class="form-label">CRM/COREN</label>
                            <input type="text" class="form-control" id="registro_profissional" name="registro_profissional" required>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="unidade_saude" class="form-label">Unidade de Saúde</label>
                            <select class="form-select select2" id="unidade_saude" name="unidade_saude" required>
                                <option value="">Selecione uma unidade</option>
                                <?php foreach ($unidades as $uni): ?>
                                    <option value="<?php echo htmlspecialchars($uni); ?>"><?php echo htmlspecialchars($uni); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarLabel">Editar Profissional</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditar" method="POST">
                        <input type="hidden" id="edit_profissional_id" name="profissional_id">
                        <input type="hidden" id="edit_usuario_id" name="usuario_id">
                        <?php if ($tipo_usuario === 'Admin' || $tipo_usuario === 'Medico'): ?>
                        <div class="mb-3">
                            <label for="edit_especialidade" class="form-label">Especialidade</label>
                            <select class="form-select" id="edit_especialidade" name="especialidade" required>
                                <option value="">Selecione uma especialidade</option>
                            </select>
                        </div>
                        <?php endif; ?>

                        <?php if ($tipo_usuario === 'Enfermeiro' || $tipo_usuario === 'Medico' || $tipo_usuario === 'Admin'): ?>
                        <div class="col-md-6 mb-4">
                            <div class="form-floating">
                                <input type="text" 
                                       class="form-control" 
                                       id="edit_registro_profissional" 
                                       name="registro_profissional" 
                                       required 
                                       placeholder="<?php echo ($tipo_usuario === 'Enfermeiro') ? '000.000-XX/UF' : '000000/UF'; ?>"
                                       data-tipo-usuario="<?php echo strtolower($tipo_usuario); ?>">
                                <label for="edit_registro_profissional">
                                    <i class="fas fa-id-card me-2"></i>
                                    <?php echo ($tipo_usuario === 'Enfermeiro') ? 'COREN' : 'CRM'; ?>
                                </label>
                                <div class="invalid-feedback">
                                    Por favor, insira o número de registro válido.
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="edit_unidade_saude" class="form-label">Unidade de Saúde</label>
                            <select class="form-select" id="edit_unidade_saude" name="unidade_saude" required>
                                <option value="">Selecione uma unidade</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Atualizar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Mova os scripts do Bootstrap para ANTES do fechamento do body -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    let modalCadastro, modalEditar;

    // Adicionar o tipo de usuário como variável JavaScript
    const tipoUsuario = <?php echo json_encode($tipo_usuario); ?>;

    $(document).ready(function() {
        modalCadastro = new bootstrap.Modal(document.getElementById('modalCadastro'));
        modalEditar = new bootstrap.Modal(document.getElementById('modalEditar'));

        // Função para aplicar a máscara correta baseada no tipo de usuário
        function aplicarMascaraRegistro(registroInput) {
            const $registro = $(registroInput);
            const tipoUsuario = $registro.data('tipo-usuario');
            
            // Remove máscaras anteriores
            $registro.unmask();
            
            // Aplica a máscara apropriada baseada no tipo de usuário
            if ($tipoUsuario.toLowerCase() === 'enfermeiro') {
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
            } else if ($tipoUsuario.toLowerCase() === 'medico') {
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
            }
        }

        // Aplicar as máscaras para ambos os formulários
        aplicarMascaraRegistro('#registro_profissional');
        aplicarMascaraRegistro('#edit_registro_profissional');

        // Reaplica a máscara quando o modal de edição é aberto
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
                
                // Se for ACS, adicionar registro_profissional como null
                if (tipoUsuario.toLowerCase() === 'acs') {
                    formData.set('especialidade', 'ACS');
                    formData.set('registro_profissional', null);
                } 
                else if (tipoUsuario.toLowerCase() === 'enfermeiro') {
                    // Se for enfermeiro, definir especialidade como "Enfermeiro"
                    formData.set('especialidade', 'Enfermeiro');
                    const corenRegex = /^\d{3}\.\d{3}-[A-Z]{2}\/[A-Z]{2}$/;
                        if (!corenRegex.test(registro)) {
                            isValid = false;
                            mensagem = 'COREN inválido. Use o formato: 000.000-XX/UF';
                        }
                }
                else {
                    // Validação para médicos e enfermeiros
                    if (tipoUsuario.toLowerCase() === 'medico') {
                        const crmRegex = /^\d{6}\/[A-Z]{2}$/;
                        if (!crmRegex.test(registro)) {
                            isValid = false;
                            mensagem = 'CRM inválido. Use o formato: 000000/UF';
                        }
                    }
                }

                if (!isValid) {
                    alert(mensagem);
                    $(this).find('[name="registro_profissional"]').focus();
                    return false;
                }

                // Desabilitar o botão de envio para evitar múltiplos envios
                const submitButton = $(this).find('button[type="submit"]');
                submitButton.prop('disabled', true);

                console.log("Enviando formulário..."); // Para depuração

                const url = $(this).attr('id') === 'formCadastro' ? 
                           'salvar_profissional.php' : 
                           'atualizar_profissional.php';

                fetch(url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Atualiza a linha da tabela dinamicamente
                        const linha = document.querySelector(`tr[data-id="${profissionalId}"]`);
                        if (linha) {
                            linha.querySelector('.especialidade').textContent = $('#edit_especialidade').val();
                            linha.querySelector('.registro_profissional').textContent = $('#edit_registro_profissional').val();
                            linha.querySelector('.unidade_saude').textContent = $('#edit_unidade_saude').val();
                        }
                        alert(data.message);
                        modalEditar.hide(); // Fecha o modal
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro ao atualizar profissional:', error);
                })
                .finally(() => {
                    // Reabilitar o botão de envio após a operação
                    submitButton.prop('disabled', false);
                });
            });
        });

        // Inicializar Select2 para todos os selects com a classe select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('.modal-body'), // Isso garante que o dropdown apareça dentro do modal
            placeholder: 'Selecione ou digite para buscar',
            allowClear: true
        });

        // Ajustar z-index do dropdown do Select2
        $('.select2-dropdown').css('z-index', 9999);
        
        // Habilitar o campo de busca
        $('#busca').prop('disabled', false);
    });

    function filtrarProfissionais() {
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

    function abrirModalCadastro(usuarioId) {
        document.getElementById('usuario_id').value = usuarioId;
        $('#especialidade').val(null).trigger('change');
        $('#unidade_saude').val(null).trigger('change');
        $('#registro_profissional').val('');
        modalCadastro.show();
    }

    function abrirModalEditar(profissionalId, usuarioId) {
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
                console.log(data); // For debugging
                let profissional = null;

                // Check if the professional is a doctor or nurse
                if (data.Medico && data.Medico.length > 0) {
                    profissional = data.Medico[0];
                } else if (data.Enfermeiro && data.Enfermeiro.length > 0) {
                    profissional = data.Enfermeiro[0];
                }

                // Check if the professional was found
                if (profissional) {
                    // Access the professional's properties
                    $('#edit_especialidade').val(profissional.especialidade || '').trigger('change');
                    $('#edit_registro_profissional').val(profissional.registro_profissional || '');
                    $('#edit_unidade_saude').val(profissional.unidade_saude || '').trigger('change');
                    console.log('Registro Profissional:', profissional.registro_profissional); // Add this line for debugging
                    modalEditar.show();
                } else {
                    alert('Profissional não encontrado.');
                }
            })
            .catch(error => {
                console.error('Erro ao buscar profissional:', error);
                alert('Erro ao carregar os dados do profissional.');
            });
    }
    </script>
</body>
</html>