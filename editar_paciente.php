<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php"); // Redireciona para a página de login
    exit();
}

include "conexao.php";
include "sidebar.php";

$paciente_id = $_GET['id'];

$sql = "SELECT 
    u.*,
    p.*,
    COALESCE(up.nome, 'Não atribuído') as nome_profissional,
    COALESCE(pr.especialidade, '') as especialidade,
    COALESCE(pr.registro_profissional, '') as registro_profissional,
    COALESCE(pr.unidade_saude, '') as unidade_saude
    FROM usuarios u 
    INNER JOIN pacientes p ON u.id = p.usuario_id 
    LEFT JOIN paciente_profissional pp ON p.id = pp.paciente_id
    LEFT JOIN profissionais pr ON pr.id = pp.profissional_id
    LEFT JOIN usuarios up ON pr.usuario_id = up.id
    WHERE p.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $paciente_id);
$stmt->execute();
$resultado = $stmt->get_result();
$paciente = $resultado->fetch_assoc();

// Adicionar verificação após buscar os dados
if (!$paciente) {
    echo "<div class='alert alert-danger'>Paciente não encontrado.</div>";
    exit();
}

// Função para verificar permissões
function temPermissao() {
    return isset($_SESSION['tipo_usuario']) && 
           ($_SESSION['tipo_usuario'] === 'Admin' || $_SESSION['tipo_usuario'] === 'Medico' || $_SESSION['tipo_usuario'] === 'Enfermeiro');
}

function podeEditarAcompanhamento() {
    $tiposPermitidos = ['ACS', 'Admin', 'Medico', 'Enfermeiro', 'Paciente'];
    return isset($_SESSION['tipo_usuario']) && in_array($_SESSION['tipo_usuario'], $tiposPermitidos);
}

// Consultar dados de acompanhamento em casa
$query_acompanhamento = "SELECT * FROM acompanhamento_em_casa WHERE paciente_id = ? ORDER BY data_acompanhamento DESC";
$stmt_acompanhamento = $conn->prepare($query_acompanhamento);
$stmt_acompanhamento->bind_param("i", $paciente_id);
$stmt_acompanhamento->execute();
$result_acompanhamento = $stmt_acompanhamento->get_result();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Paciente</title>
    <style>

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .section-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .header-container h1 {
            margin: 0;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-voltar {
            background-color: #6c757d;
            color: white;
        }

        .btn-voltar:hover {
            background-color: #5a6268;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-secondary {
            background-color: #2196F3;
            color: white;
        }

        .btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        .section-header {
            margin-bottom: 20px;
            color: #333;
        }

        /* Estilos para as tabelas */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .data-table tr:hover {
            background-color: #f5f5f5;
        }

        /* Status badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-cadastrado {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-pendente {
            background-color: #fff3e0;
            color: #ef6c00;
        }

        /* Info badges */
        .info-badge {
            background-color: #e3f2fd;
            color: #1565c0;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
        }

        /* Modal Base */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.hidden {
            display: none;
        }

        /* Overlay escuro */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
        }

        /* Container do Modal */
        .modal-content {
            position: relative;
            background: white;
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            overflow: hidden;
            animation: modalFadeIn 0.3s ease-out;
        }

        /* Cabeçalho do Modal */
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

        /* Corpo do Modal */
        .modal-body {
            padding: 25px;
            max-height: 60vh;
            overflow-y: auto;
        }

        
        /* Rodapé do Modal */
        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Botões do Rodapé */
        .btn-secondary {
            padding: 8px 16px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        /* Animação de entrada do modal */
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Estilização da barra de rolagem */
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Botão Fechar */
        .close-button {
            background: none;
            border: none;
            font-size: 1.8rem;
            color: #666;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .close-button:hover {
            background-color: #eee;
            color: #333;
        }

        /* Lista de Médicos */
        .medicos-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .medicos-list li {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.2s;
        }

        .medicos-list li:last-child {
            border-bottom: none;
        }

        .medicos-list li:hover {
            background-color: #f8f9fa;
        }

        /* Botões na lista */
        .medicos-list button {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }

        .medicos-list button:hover {
            background-color: #45a049;
        }

        /* Estilo para o editar médico  */
        .medico-atual {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .medico-atual h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .info-medico {
            color: #666;
            font-size: 0.95rem;
        }

        .separador {
            position: relative;
            text-align: center;
            margin: 25px 0;
        }

        .separador::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background-color: #eee;
        }

        .separador span {
            position: relative;
            background-color: white;
            padding: 0 15px;
            color: #666;
            font-size: 0.9rem;
        }

        /* Estilo para os botões na mesma célula */
        .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn-edit {
            background-color: #2196F3;
        }

        .btn-edit:hover {
            background-color: #1976D2;
        }

        .risk-calculator {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .risk-calculator .form-group {
            margin-bottom: 15px;
        }

        .risk-calculator label {
            font-weight: 500;
            color: #495057;
        }

        .risk-calculator .alert {
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
        }

        #resultado {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        #resultado h4 {
            margin-bottom: 15px;
        }

        #probabilidade {
            font-size: 1.5rem;
            font-weight: bold;
        }

    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <!-- Header com nome do paciente e botão voltar -->
        <div class="header-container">
            <h1>Paciente <?php echo htmlspecialchars($paciente['nome']); ?></h1>
        </div>
        <input type="hidden" id="p_id" value="<?php echo $paciente_id; ?>">

        <!---------------------------------------------------------------------------->
        <!-- Seção de Acompanhamento em Casa -->
        <div class="section-card">
            <h2 class="section-header">Acompanhamento em Casa</h2>
            
            <div class="d-flex justify-content-between mb-3">
                <?php if (podeEditarAcompanhamento()): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAcompanhamento">
                        <i class="fas fa-plus"></i> Adicionar
                    </button>
                <?php endif; ?>
                
                <?php
                // Contar total de registros
                $total_registros = $result_acompanhamento->num_rows;
                if ($total_registros > 3): ?>
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalTodosAcompanhamentos">
                        <i class="fas fa-list"></i> Ver Todos (<?php echo $total_registros; ?>)
                    </button>
                <?php endif; ?>
            </div>

            <!-- Tabela com os 3 últimos registros -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Glicemia</th>
                        <th>Hipertensão</th>
                        <th>Observações</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Armazenar todos os registros em um array
                    $todos_acompanhamentos = [];
                    while ($acompanhamento = $result_acompanhamento->fetch_assoc()) {
                        $todos_acompanhamentos[] = $acompanhamento;
                    }
                    
                    // Pegar apenas os 3 últimos registros
                    $ultimos_tres = array_slice($todos_acompanhamentos, 0, 3);
                    
                    foreach ($ultimos_tres as $acompanhamento): ?>
                        <tr data-id="<?php echo $acompanhamento['id']; ?>">
                            <td><?php echo date('d/m/Y', strtotime($acompanhamento['data_acompanhamento'])); ?></td>
                            <td><?php echo htmlspecialchars($acompanhamento['glicemia']) ?: 'Não informado'; ?></td>
                            <td><?php echo htmlspecialchars($acompanhamento['hipertensao']) ?: 'Não informado'; ?></td>
                            <td><?php echo htmlspecialchars($acompanhamento['observacoes']) ?: 'Não informado'; ?></td>
                            <td>
                                <?php if (podeEditarAcompanhamento()): ?>
                                    <a href="#" onclick="editarAcompanhamento(<?php echo htmlspecialchars(json_encode($acompanhamento, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>)" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="#" onclick="excluirAcompanhamento(<?php echo $acompanhamento['id']; ?>)" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Excluir
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Acesso restrito</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal para todos os acompanhamentos -->
        <div class="modal fade" id="modalTodosAcompanhamentos" tabindex="-1" aria-labelledby="modalTodosAcompanhamentosLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTodosAcompanhamentosLabel">
                            <i class="fas fa-list"></i> Histórico Completo de Acompanhamentos
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Glicemia</th>
                                        <th>Hipertensão</th>
                                        <th>Observações</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($todos_acompanhamentos as $acompanhamento): ?>
                                        <tr data-id="<?php echo $acompanhamento['id']; ?>">
                                            <td><?php echo date('d/m/Y', strtotime($acompanhamento['data_acompanhamento'])); ?></td>
                                            <td><?php echo htmlspecialchars($acompanhamento['glicemia']) ?: 'Não informado'; ?></td>
                                            <td><?php echo htmlspecialchars($acompanhamento['hipertensao']) ?: 'Não informado'; ?></td>
                                            <td><?php echo htmlspecialchars($acompanhamento['observacoes']) ?: 'Não informado'; ?></td>
                                            <td>
                                                <?php if (podeEditarAcompanhamento()): ?>
                                                    <div class="btn-group">
                                                        <a href="#" onclick="editarAcompanhamento(<?php echo htmlspecialchars(json_encode($acompanhamento, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>)" class="btn btn-sm btn-warning me-2">
                                                            <i class="fas fa-edit"></i> Editar
                                                        </a>
                                                        <a href="#" onclick="excluirAcompanhamento(<?php echo $acompanhamento['id']; ?>)" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i> Excluir
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Acesso restrito</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Acompanhamento -->
        <div class="modal fade" id="modalAcompanhamento" tabindex="-1" aria-labelledby="modalAcompanhamentoLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAcompanhamentoLabel">Adicionar Acompanhamento em Casa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formAcompanhamento" method="POST" action="salvar_acompanhamento.php">
                        <div class="modal-body">
                            <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label>Data:</label>
                                    <input type="date" name="data_acompanhamento" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Glicemia:</label>
                                    <input type="text" 
                                        name="glicemia" 
                                        class="form-control glicemia" 
                                        placeholder="Ex: 99"
                                        pattern="[0-9]{1,3}"
                                        maxlength="3"
                                        title="Valor entre 20 e 600 mg/dL" required>
                                    <small class="form-text text-muted">Valor entre 20 e 600 mg/dL</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Pressão Arterial:</label>
                                    <input type="text" 
                                        name="hipertensao" 
                                        class="form-control pressao-arterial" 
                                        placeholder="Ex: 120/80"
                                        pattern="[0-9]{1,3}/[0-9]{1,3}"
                                        title="Formato: 120/80 (sistólica/diastólica)" required>
                                    <small class="form-text text-muted">Sistólica: 70-200 / Diastólica: 40-130</small>
                                </div>
                            </div>
                        

                            <div class="row">
                                <div class="mb-3">
                                    <label>Observações:</label>
                                    <textarea name="observacoes" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal de Edição de Acompanhamento -->
        <div class="modal fade" id="modalEditarAcompanhamento" tabindex="-1" aria-labelledby="modalEditarAcompanhamentoLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 80%; width: 1000px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarAcompanhamentoLabel">Editar Acompanhamento em Casa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formEditarAcompanhamento" method="POST" action="atualizar_acompanhamento.php">
                        <div class="modal-body p-4">
                            <input type="hidden" name="id" id="edit_acompanhamento_id">
                            <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
                            
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Data:</label>
                                    <input type="date" name="data_acompanhamento" id="edit_data_acompanhamento" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Glicemia:</label>
                                    <input type="text" 
                                        name="glicemia" 
                                        id="edit_glicemia"
                                        class="form-control form-control-lg glicemia" 
                                        placeholder="Ex: 99"
                                        title="Valor entre 20 e 600 mg/dL" required>
                                    <small class="form-text text-muted">Valor entre 20 e 600 mg/dL</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Pressão Arterial:</label>
                                    <input type="text" 
                                        name="hipertensao" 
                                        id="edit_hipertensao"
                                        class="form-control form-control-lg pressao-arterial" 
                                        placeholder="Ex: 120/80"
                                        title="Formato: 120/80 (sistólica/diastólica)" required>
                                    <small class="form-text text-muted">Sistólica: 70-200 / Diastólica: 40-130</small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Observações:</label>
                                <textarea name="observacoes" id="edit_observacoes" class="form-control form-control-lg" rows="5"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-lg">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <!---------------------------------------------------------------------------->
        <!-- Seção de Doença -->
        <div class="section-card">
            <h2 class="section-header">Tipo de Doença</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Tipo</th>
                        <th>Histórico Familiar</th>
                        <th>Estado Civil</th>
                        <th>Profissão</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <?php if ($paciente['tipo_doenca']): ?>
                                <span class="status-badge status-cadastrado">Cadastrado</span>
                            <?php else: ?>
                                <span class="status-badge status-pendente">Pendente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $paciente['tipo_doenca'] ? htmlspecialchars($paciente['tipo_doenca']) : 'Não cadastrado'; ?>
                        </td>
                        <td>
                            <?php echo $paciente['historico_familiar'] ? htmlspecialchars($paciente['historico_familiar']) : 'Não informado'; ?>
                        </td>
                        <td>
                            <?php echo $paciente['estado_civil'] ? htmlspecialchars($paciente['estado_civil']) : 'Não informado'; ?>
                        </td>
                        <td>
                            <?php echo $paciente['profissao'] ? htmlspecialchars($paciente['profissao']) : 'Não informado'; ?>
                        </td>
                        <td>
                            <?php if ($paciente['tipo_doenca']): ?>
                                <a href="#" onclick="editarDoenca(<?php 
                                    echo htmlspecialchars(json_encode([
                                        'id' => $paciente['id'],
                                        'tipo_doenca' => $paciente['tipo_doenca'],
                                        'historico_familiar' => $paciente['historico_familiar'],
                                        'estado_civil' => $paciente['estado_civil'],
                                        'profissao' => $paciente['profissao']
                                    ]), ENT_QUOTES); 
                                ?>)" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                                </a>
                            <?php else: ?>
                                <a href="cadastro_pacientes_doenca.php?id=<?php echo $paciente['usuario_id']; ?>" 
                                class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal Editar Tipo de Doença -->
        <div class="modal fade" id="modalEditarDoenca" tabindex="-1" aria-labelledby="modalEditarDoencaLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarDoencaLabel">Editar Tipo de Doença</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formEditarDoenca" method="POST">
                        <div class="modal-body">
                            <input type="hidden" id="edit_doenca_id" name="id">
                            
                            <div class="mb-3">
                                <label for="edit_tipo_doenca" class="form-label">Tipo de Doença:</label>
                                <select class="form-select" id="edit_tipo_doenca" name="tipo_doenca" required>
                                <option value="">Selecione o tipo de doença...</option>
                                    <option value="Hipertensão">Hipertensão</option>
                                    <option value="Diabetes">Diabetes</option>
                                    <option value="Doenças Cardiovasculares">Doenças Cardiovasculares</option>
                                    <option value="Asma">Asma</option>
                                    <option value="DPOC">Doença Pulmonar Obstrutiva Crônica (DPOC)</option>
                                    <option value="Câncer de Mama">Câncer de Mama</option>
                                    <option value="Câncer de Pulmão">Câncer de Pulmão</option>
                                    <option value="Câncer Colorretal">Câncer Colorretal</option>
                                    <option value="Câncer de Próstata">Câncer de Próstata</option>
                                    <option value="Doenças Renais Crônicas">Doenças Renais Crônicas</option>
                                    <option value="Obesidade">Obesidade</option>
                                    <option value="Depressão">Depressão</option>
                                    <option value="Ansiedade">Ansiedade</option>
                                    <option value="Artrite">Artrite</option>
                                    <option value="Osteoporose">Osteoporose</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="edit_historico_familiar" class="form-label">Histórico Familiar:</label>
                                <textarea class="form-control" id="edit_historico_familiar" name="historico_familiar" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="edit_estado_civil" class="form-label">Estado Civil:</label>
                                <select class="form-select" id="edit_estado_civil" name="estado_civil">
                                    <option value="">Selecione...</option>
                                    <option value="Solteiro(a)">Solteiro(a)</option>
                                    <option value="Casado(a)">Casado(a)</option>
                                    <option value="Divorciado(a)">Divorciado(a)</option>
                                    <option value="Viúvo(a)">Viúvo(a)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="edit_profissao" class="form-label">Profissão:</label>
                                <input type="text" class="form-control" id="edit_profissao" name="profissao">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <!---------------------------------------------------------------------------->
        <!-- Seção de Médico Responsável -->
        <div class="section-card">
            <h2 class="section-header">Médico Responsável</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Nome do Médico</th>
                        <th>Especialidade</th>
                        <th>Unidade de Saúde</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <?php if (isset($paciente['nome_profissional']) && $paciente['nome_profissional'] !== 'Não atribuído'): ?>
                                <span class="status-badge status-cadastrado">Atribuído</span>
                            <?php else: ?>
                                <span class="status-badge status-pendente">Não Atribuído</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($paciente['nome_profissional']); ?></td>
                        <td>
                            <?php echo $paciente['especialidade'] ? htmlspecialchars($paciente['especialidade']) : 'Não informado'; ?>
                        </td>
                        <td>
                            <?php echo $paciente['unidade_saude'] ? htmlspecialchars($paciente['unidade_saude']) : 'Não informado'; ?>
                        </td>
                        <td>
                            <?php if ($paciente['nome_profissional'] !== 'Não atribuído'): ?>
                                <?php if (temPermissao()): ?>
                                    <div class="section-actions">
                                        <button onclick="abrirModalMedico(<?php echo $paciente_id; ?>)" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (temPermissao()): ?>
                                    <button onclick="abrirModalAtribuirMedico(<?php echo $paciente_id; ?>)" 
                                            class="btn btn-primary"
                                            <?php echo empty($paciente['tipo_doenca']) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-plus"></i>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal para trocar médico -->
        <div class="modal fade" id="modalMedico" tabindex="-1" aria-labelledby="modalMedicoLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalMedicoLabel">Trocar Médico Responsável</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formTrocarMedico" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="paciente_id" id="paciente_id">
                            
                            <div class="form-group mb-3">
                                <label>Selecione o Médico:</label>
                                <select name="profissional_id" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <?php
                                    $query_medicos = "SELECT p.id, u.nome, p.especialidade 
                                                    FROM profissionais p 
                                                    JOIN usuarios u ON p.usuario_id = u.id 
                                                    WHERE u.tipo_usuario = 'Medico'
                                                    ORDER BY u.nome";
                                    $result_medicos = $conn->query($query_medicos);
                                    while($medico = $result_medicos->fetch_assoc()) {
                                        echo "<option value='{$medico['id']}'>{$medico['nome']} - {$medico['especialidade']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal para atribuir médico -->
        <div class="modal fade" id="modalAtribuirMedico" tabindex="-1" aria-labelledby="modalAtribuirMedicoLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAtribuirMedicoLabel">Atribuir Médico</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formAtribuirMedico" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="paciente_id" id="atribuir_paciente_id">
                            <input type="hidden" name="tipo_profissional" value="<?php echo $tipo_profissional; ?>">
                            <div class="form-group mb-3">
                                <label>Selecione o Médico:</label>
                                <select name="profissional_id" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <?php
                                    $query_medicos = "SELECT p.id, u.nome, p.especialidade 
                                                    FROM profissionais p 
                                                    JOIN usuarios u ON p.usuario_id = u.id 
                                                    WHERE u.tipo_usuario = 'Medico'
                                                    ORDER BY u.nome";
                                    $result_medicos = $conn->query($query_medicos);
                                    while($medico = $result_medicos->fetch_assoc()) {
                                        echo "<option value='{$medico['id']}'>{$medico['nome']} - {$medico['especialidade']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!---------------------------------------------------------------------------->
        <!-- Seção de Consultas e Acompanhamento -->
        <div class="section-card">
            <h2 class="section-header">Histórico de Consultas e Acompanhamento</h2>
            
            <?php
            // Executar a query para buscar as consultas
            $query = "SELECT c.*, 
                     COALESCE(u.nome, 'Não informado') as nome_profissional,
                     p.especialidade,
                     p.unidade_saude
                     FROM consultas c 
                     LEFT JOIN profissionais p ON c.profissional_id = p.id 
                     LEFT JOIN usuarios u ON p.usuario_id = u.id
                     WHERE c.paciente_id = ? 
                     ORDER BY c.data_consulta DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $paciente_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Armazenar todas as consultas em um array
            $todas_consultas = [];
            while ($consulta = $result->fetch_assoc()) {
                $todas_consultas[] = $consulta;
            }
            $total_consultas = count($todas_consultas);
            ?>

            <div class="d-flex justify-content-between mb-3">
                <div>
                    <?php if (temPermissao()): ?>
                        <button onclick="abrirModalConsulta(<?php echo $paciente_id; ?>)" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                        </button>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($total_consultas > 3): ?>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalTodasConsultas">
                            <i class="fas fa-list"></i> Ver Todas (<?php echo $total_consultas; ?>)
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabela com as 3 últimas consultas -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Profissional</th>
                        <th>Pressão Arterial</th>
                        <th>Glicemia</th>
                        <th>Peso</th>
                        <th>Altura</th>
                        <th>IMC</th>
                        <th>Classificação IMC</th>
                        <th>Estado Emocional</th>
                        <th>Habitos de Vida</th>
                        <th>Observações</th>
                        <?php if (temPermissao()): ?>
                            <th>Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Pegar apenas as 3 últimas consultas
                    $ultimas_tres = array_slice($todas_consultas, 0, 3);
                    
                    foreach ($ultimas_tres as $consulta): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($consulta['data_consulta'])); ?></td>
                            <td><?php echo htmlspecialchars($consulta['nome_profissional']); ?></td>
                            <td><?php echo htmlspecialchars($consulta['pressao_arterial']); ?></td>
                            <td><?php echo htmlspecialchars($consulta['glicemia']); ?></td>
                            <td><?php echo $consulta['peso'] ? number_format($consulta['peso'], 1) . ' kg' : '-'; ?></td>
                            <td><?php echo $consulta['imc'] ? number_format($consulta['imc'], 1) : '-'; ?></td>
                            <td><?php echo $consulta['altura'] ? number_format($consulta['altura']) . ' cm' : '-'; ?></td>
                            <td><?php echo htmlspecialchars($consulta['classificacao_imc']) ?: '-'; ?></td>
                            <td><?php echo htmlspecialchars($consulta['estado_emocional']) ?: '-'; ?></td>
                            <td><?php echo htmlspecialchars($consulta['habitos_vida']) ?: '-'; ?></td>
                            <td><?php echo htmlspecialchars($consulta['observacoes']) ?: '-'; ?></td>
                            <?php if (temPermissao()): ?>
                                <td>
                                    <div class="btn-group">
                                        <button onclick='editarConsulta(<?php echo json_encode($consulta, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                                            class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="excluirConsulta(<?php echo $consulta['id']; ?>)" 
                                            class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal para todas as consultas -->
        <div class="modal fade" id="modalTodasConsultas" tabindex="-1" aria-labelledby="modalTodasConsultasLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTodasConsultasLabel">
                            <i class="fas fa-list"></i> Histórico Completo de Consultas
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Profissional</th>
                                        <th>Pressão Arterial</th>
                                        <th>Glicemia</th>
                                        <th>Peso</th>
                                        <th>Altura</th>
                                        <th>IMC</th>
                                        <th>Classificação IMC</th>
                                        <th>Estado Emocional</th>
                                        <th>Hábitos de Vida</th>
                                        <th>Observações</th>
                                        <?php if (temPermissao()): ?>
                                            <th>Ações</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($todas_consultas as $consulta): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($consulta['data_consulta'])); ?></td>
                                            <td><?php echo htmlspecialchars($consulta['nome_profissional']); ?></td>
                                            <td><?php echo htmlspecialchars($consulta['pressao_arterial']); ?></td>
                                            <td><?php echo htmlspecialchars($consulta['glicemia']); ?></td>
                                            <td><?php echo $consulta['peso'] ? number_format($consulta['peso'], 1) . ' kg' : '-'; ?></td>
                                            <td><?php echo $consulta['altura'] ? number_format($consulta['altura']) . ' cm' : '-'; ?></td>
                                            <td><?php echo $consulta['imc'] ? number_format($consulta['imc'], 1) : '-'; ?></td>
                                            <td><?php echo htmlspecialchars($consulta['classificacao_imc']) ?: '-'; ?></td>
                                            <td><?php echo htmlspecialchars($consulta['estado_emocional']) ?: '-'; ?></td>
                                            <td><?php echo htmlspecialchars($consulta['habitos_vida']) ?: '-'; ?></td>
                                            <td><?php echo htmlspecialchars($consulta['observacoes']) ?: '-'; ?></td>
                                            <?php if (temPermissao()): ?>
                                                <td>
                                                    <div class="btn-group">
                                                        <button onclick='editarConsulta(<?php echo json_encode($consulta, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                                                            class="btn btn-sm btn-warning me-2">
                                                            <i class="fas fa-edit"></i> Editar
                                                        </button>
                                                        <button onclick="excluirConsulta(<?php echo $consulta['id']; ?>)" 
                                                            class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i> Excluir
                                                        </button>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Nova Consulta -->
        <div class="modal fade" id="modalConsulta" tabindex="-1" aria-labelledby="modalConsultaLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalConsultaLabel">Nova Consulta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formConsulta" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
                            
                            <div class="form-group mb-3">
                                <label>Profissional:</label>
                                <select name="profissional_id" class="form-control" required>
                                    <option value="">Selecione o profissional</option>
                                    <?php
                                    $query_prof = "SELECT p.id, u.nome 
                                                FROM profissionais p 
                                                JOIN usuarios u ON p.usuario_id = u.id 
                                                WHERE u.tipo_usuario = 'Medico'
                                                ORDER BY u.nome";
                                    $result_prof = $conn->query($query_prof);
                                    while($row = $result_prof->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Data da Consulta:</label>
                                    <input type="date" name="data_consulta" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Pressão Arterial:</label>
                                    <input type="text" 
                                        name="pressao_arterial" 
                                        class="form-control pressao-arterial" 
                                        placeholder="Ex: 120/80"
                                        title="Formato: 120/80 (sistólica/diastólica)">
                                    <small class="form-text text-muted">Sistólica: 70-200 / Diastólica: 40-130</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label>Glicemia:</label>
                                    <input type="text" 
                                        name="glicemia" 
                                        class="form-control glicemia" 
                                        placeholder="Ex: 99"
                                        title="Valor entre 20 e 600 mg/dL" required>
                                    <small class="form-text text-muted">Valor entre 20 e 600 mg/dL</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Peso (kg):</label>
                                    <input type="text" 
                                        name="peso" 
                                        class="form-control peso" 
                                        placeholder="Ex: 70.5"
                                        title="Valor entre 0 e 300 kg" required>
                                    <small class="form-text text-muted">Valor entre 0 e 300 kg</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Altura (cm):</label>
                                    <input type="text" 
                                        name="altura" 
                                        class="form-control altura" 
                                        placeholder="Ex: 170"
                                        title="Valor entre 10 e 250 cm" required>
                                    <small class="form-text text-muted">Valor entre 10 e 250 cm</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="estado_emocional" class="form-label">Estado Emocional:</label>
                                <select class="form-select" id="estado_emocional" name="estado_emocional" required>
                                    <option value="">Selecione...</option>
                                    <option value="Calmo">Calmo</option>
                                    <option value="Ansioso">Ansioso</option>
                                    <option value="Deprimido">Deprimido</option>
                                    <option value="Estressado">Estressado</option>
                                    <option value="Irritado">Irritado</option>
                                    <option value="Alegre">Alegre</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="habitos_vida" class="form-label">Hábitos de Vida:</label>
                                <textarea class="form-control" id="habitos_vida" name="habitos_vida" rows="3"></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label>Observações:</label>
                                <textarea name="observacoes" class="form-control" rows="4"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar Consulta</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Adicione o Modal de Edição de Consulta-->
        <div class="modal fade" id="modalEditarConsulta" tabindex="-1" aria-labelledby="modalEditarConsultaLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarConsultaLabel">Editar Consulta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formEditarConsulta" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="consulta_id" id="edit_consulta_id">
                            <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
                            
                            <div class="form-group mb-3">
                                <label>Profissional:</label>
                                <select name="profissional_id" id="edit_profissional_id" class="form-control" required>
                                    <option value="">Selecione o profissional</option>
                                    <?php
                                    $query_prof = "SELECT p.id, u.nome 
                                                FROM profissionais p 
                                                JOIN usuarios u ON p.usuario_id = u.id 
                                                WHERE u.tipo_usuario = 'Medico'
                                                ORDER BY u.nome";
                                    $result_prof = $conn->query($query_prof);
                                    while($row = $result_prof->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Data da Consulta:</label>
                                    <input type="date" name="data_consulta" id="edit_data_consulta" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Pressão Arterial:</label>
                                    <input type="text" name="pressao_arterial" id="edit_pressao_arterial" class="form-control pressao-arterial" placeholder="Ex: 120/80" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label>Glicemia:</label>
                                    <input type="text" 
                                        name="glicemia" 
                                        id="edit_glicemia"
                                        class="form-control glicemia" 
                                        placeholder="Ex: 99"
                                        title="Valor entre 20 e 600 mg/dL" required>
                                    <small class="form-text text-muted">Valor entre 20 e 600 mg/dL</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Peso (kg):</label>
                                    <input type="text" name="peso" id="edit_peso" class="form-control peso" placeholder="Ex: 70.5" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Altura (cm):</label>
                                    <input type="text" name="altura" id="edit_altura" class="form-control altura" placeholder="Ex: 170" required>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Estado Emocional:</label>
                                <select class="form-select" id="edit_estado_emocional" name="estado_emocional" required>
                                    <option value="">Selecione...</option>
                                    <option value="Calmo">Calmo</option>
                                    <option value="Ansioso">Ansioso</option>
                                    <option value="Deprimido">Deprimido</option>
                                    <option value="Estressado">Estressado</option>
                                    <option value="Irritado">Irritado</option>
                                    <option value="Alegre">Alegre</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="habitos_vida" class="form-label">Hábitos de Vida:</label>
                                <textarea name="habitos_vida" id="edit_habitos_vida" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label>Observações:</label>
                                <textarea name="observacoes" id="edit_observacoes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!---------------------------------------------------------------------------->
        <!-- Seção de Riscos para Saúde -->
        <div class="section-card">
            <h2 class="section-header">Riscos CardioVasculares</h2>

            <div class="d-flex justify-content-between mb-3">
                <div>
                    <?php if (temPermissao()): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalRiscoCardiovascular">
                            <i class="fas fa-plus"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <?php
                // Buscar todos os riscos do paciente
                $query_riscos = "SELECT * FROM riscos_saude WHERE paciente_id = ? ORDER BY data_calculo DESC";
                $stmt_riscos = $conn->prepare($query_riscos);
                $stmt_riscos->bind_param('i', $paciente_id);
                $stmt_riscos->execute();
                $result_riscos = $stmt_riscos->get_result();
                
                // Armazenar todos os riscos em um array
                $todos_riscos = [];
                while ($risco = $result_riscos->fetch_assoc()) {
                    $todos_riscos[] = $risco;
                }
                $total_riscos = count($todos_riscos);   
                ?>
                <div>
                    <?php if ($total_riscos > 3): ?>
                        <!-- Botão Ver Todos -->
                        <button id="btnVerTodosRiscos" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalTodosRiscos">
                            <i class="fas fa-list"></i> Ver Todos
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabela com os 3 últimos registros -->
            <div class="table-responsive">
                <!-- Tabela principal -->
                <table id="historico-riscos" class="table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Pontuação</th>
                            <th>Probabilidade</th>
                            <th>Sexo</th>
                            <th>Idade</th>
                            <th>Colesterol Total</th>
                            <th>HDL</th>
                            <th>Pressão Sistólica</th>
                            <th>Fumante</th>
                            <th>Remédios Hipertensão</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dados serão inseridos aqui -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal de Todos os Riscos -->
        <div class="modal fade" id="modalTodosRiscos" tabindex="-1" aria-labelledby="modalTodosRiscosLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl"> <!-- modal-xl para largura máxima -->
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTodosRiscosLabel">Histórico Completo de Riscos Cardiovasculares</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Pontuação</th>
                                        <th>Probabilidade</th>
                                        <th>Sexo</th>
                                        <th>Idade</th>
                                        <th>Colesterol Total</th>
                                        <th>HDL</th>
                                        <th>Pressão Sistólica</th>
                                        <th>Fumante</th>
                                        <th>Remédios Hipertensão</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dados serão inseridos via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Risco Cardiovascular -->
        <div class="modal fade" id="modalRiscoCardiovascular" tabindex="-1" aria-labelledby="modalRiscoCardiovascularLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalRiscoCardiovascularLabel">Calculadora de Risco Cardiovascular</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formRiscoCardiovascular">
                            <!-- Campo oculto para o sexo do paciente -->
                            <?php
                            $query_sexo = "SELECT u.sexo FROM usuarios u 
                                          INNER JOIN pacientes p ON u.id = p.usuario_id 
                                          WHERE p.id = ?";
                            $stmt = $conn->prepare($query_sexo);
                            $stmt->bind_param("i", $paciente_id);
                            $stmt->execute();
                            $result_sexo = $stmt->get_result();
                            $paciente_sexo = $result_sexo->fetch_assoc();
                            $sexo_valor = ($paciente_sexo['sexo'] == 'M') ? 'Homem' : 'Mulher';
                            ?>
                            <input type="hidden" name="sexo" value="<?php echo htmlspecialchars($sexo_valor); ?>">
                            <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Idade:</label>
                                        <select name="idade" class="form-control" required>
                                            <option value="">Selecione...</option>
                                            <option value="20-34">20-34</option>
                                            <option value="35-39">35-39</option>
                                            <option value="40-44">40-44</option>
                                            <option value="45-49">45-49</option>
                                            <option value="50-54">50-54</option>
                                            <option value="55-59">55-59</option>
                                            <option value="60-64">60-64</option>
                                            <option value="65-69">65-69</option>
                                            <option value="70-74">70-74</option>
                                            <option value="75-79">75-79</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Colesterol Total (mg/dL):</label>
                                        <input type="number" name="colesterol_total" class="form-control" placeholder="Ex: 180" required min="0" max="999" oninput="this.value = Math.min(this.value, 999)">
                                        <small class="form-text text-muted">Insira um valor entre 0 e 999 mg/dL.</small>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Colesterol HDL (mg/dL):</label>
                                        <input type="number" name="colesterol_hdl" class="form-control" placeholder="Ex: 45" required min="0" max="999" oninput="this.value = Math.min(this.value, 999)">
                                        <small class="form-text text-muted">Insira um valor entre 0 e 999 mg/dL.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Pressão Sistólica (mmHg):</label>
                                        <input type="number" name="pressao_sistolica" class="form-control" placeholder="Ex: 120" required min="0" max="999" oninput="this.value = Math.min(this.value, 999)">
                                        <small class="form-text text-muted">Insira um valor entre 0 e 999 mmHg.</small>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Fumante:</label>
                                        <select name="fumante" class="form-control" required>
                                            <option value="">Selecione...</option>
                                            <option value="Sim">Sim</option>
                                            <option value="Não">Não</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Toma remédios para hipertensão:</label>
                                        <select name="remedios_hipertensao" class="form-control" required>
                                            <option value="">Selecione...</option>
                                            <option value="Sim">Sim</option>
                                            <option value="Não">Não</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Adicionar div para resultados -->
                            <div class="row mb-3" id="resultadosCalculo" style="display: none;">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Pontuação:</label>
                                        <input type="text" id="pontuacao" name="pontuacao" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Probabilidade (%):</label>
                                        <input type="text" id="probabilidade" name="probabilidade" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Botões do modal -->
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                <button type="button" class="btn btn-primary" onclick="calcularRisco()">Calcular</button>
                                <button type="button" id="btnSalvar" class="btn btn-success" onclick="salvarRiscoCardiovascular()" style="display: none;">Salvar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Edição de Risco Cardiovascular -->
        <div class="modal fade" id="modalEditarRiscoCardiovascular" tabindex="-1" aria-labelledby="modalEditarRiscoCardiovascularLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarRiscoCardiovascularLabel">Editar Risco Cardiovascular</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formEditarRiscoCardiovascular">
                            <input type="hidden" name="risco_id" id="editar_risco_id">
                            <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
                            <input type="hidden" name="sexo" id="editar_sexo">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Idade:</label>
                                        <select name="idade" id="editar_idade" class="form-control" required>
                                            <option value="">Selecione...</option>
                                            <option value="20-34">20-34</option>
                                            <option value="35-39">35-39</option>
                                            <option value="40-44">40-44</option>
                                            <option value="45-49">45-49</option>
                                            <option value="50-54">50-54</option>
                                            <option value="55-59">55-59</option>
                                            <option value="60-64">60-64</option>
                                            <option value="65-69">65-69</option>
                                            <option value="70-74">70-74</option>
                                            <option value="75-79">75-79</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Colesterol Total (mg/dL):</label>
                                        <input type="number" name="colesterol_total" id="editar_colesterol_total" class="form-control" required min="0" max="999">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Colesterol HDL (mg/dL):</label>
                                        <input type="number" name="colesterol_hdl" id="editar_colesterol_hdl" class="form-control" required min="0" max="999">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Pressão Sistólica (mmHg):</label>
                                        <input type="number" name="pressao_sistolica" id="editar_pressao_sistolica" class="form-control" required min="0" max="999">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Fumante:</label>
                                        <select name="fumante" id="editar_fumante" class="form-control" required>
                                            <option value="">Selecione...</option>
                                            <option value="Sim">Sim</option>
                                            <option value="Não">Não</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Toma remédios para hipertensão:</label>
                                        <select name="remedios_hipertensao" id="editar_remedios_hipertensao" class="form-control" required>
                                            <option value="">Selecione...</option>
                                            <option value="Sim">Sim</option>
                                            <option value="Não">Não</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Campos de resultado -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Pontuação:</label>
                                        <input type="text" id="editar_pontuacao" name="pontuacao" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Probabilidade (%):</label>
                                        <input type="text" id="editar_probabilidade" name="probabilidade" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Botões do modal -->
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                <button type="button" class="btn btn-primary" onclick="recalcularRisco()">Recalcular</button>
                                <button type="submit" class="btn btn-success">Salvar Alterações</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!---------------------------------------------------------------------------->
        <!-- Seção de Medicamentos -->
        <div class="section-card">
            <h2 class="section-header">Medicamentos</h2>
            
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <?php if (temPermissao()): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMedicamento">
                            <i class="fas fa-plus"></i>
                        </button>
                    <?php endif; ?>
                </div>
                
                <?php
                // Contar total de registros de medicamentos
                $query_total = "SELECT COUNT(*) as total FROM medicamentos WHERE paciente_id = ?";
                $stmt_total = $conn->prepare($query_total);
                $stmt_total->bind_param("i", $paciente_id);
                $stmt_total->execute();
                $total_medicamentos = $stmt_total->get_result()->fetch_assoc()['total'];
                
                if ($total_medicamentos > 3): ?>
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalTodosMedicamentos">
                        <i class="fas fa-list"></i> Ver Todos (<?php echo $total_medicamentos; ?>)
                    </button>
                <?php endif; ?>
            </div>

            <!-- Tabela com os 3 últimos registros -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Dosagem</th>
                        <th>Frequência</th>
                        <th>Data Início</th>
                        <th>Data Fim</th>
                        <th>Observações</th>
                        <?php if (temPermissao()): ?>
                            <th>Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $query_med = "SELECT * FROM medicamentos WHERE paciente_id = ? ORDER BY data_inicio DESC LIMIT 3";
                    $stmt_med = $conn->prepare($query_med);
                    $stmt_med->bind_param('i', $paciente_id);
                    $stmt_med->execute();
                    $result_med = $stmt_med->get_result();

                    while ($medicamento = $result_med->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($medicamento['nome_medicamento']); ?></td>
                            <td><?php echo htmlspecialchars($medicamento['dosagem']); ?></td>
                            <td><?php echo htmlspecialchars($medicamento['frequencia']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($medicamento['data_inicio'])); ?></td>
                            <td><?php echo $medicamento['data_fim'] ? date('d/m/Y', strtotime($medicamento['data_fim'])) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($medicamento['observacoes']); ?></td>
                            <?php if (temPermissao()): ?>
                                <td>
                                    <div class="btn-group">
                                        <button onclick='editarMedicamento(<?php echo json_encode($medicamento); ?>)' 
                                            class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="excluirMedicamento(<?php echo $medicamento['id']; ?>)" 
                                            class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal para todos os medicamentos -->
        <div class="modal fade" id="modalTodosMedicamentos" tabindex="-1" aria-labelledby="modalTodosMedicamentosLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTodosMedicamentosLabel">
                            <i class="fas fa-list"></i> Histórico Completo de Medicamentos
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Medicamento</th>
                                        <th>Dosagem</th>
                                        <th>Frequência</th>
                                        <th>Data Início</th>
                                        <th>Data Fim</th>
                                        <th>Observações</th>
                                        <?php if (temPermissao()): ?>
                                            <th>Ações</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Será preenchido via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Medicamento -->
        <div class="modal fade" id="modalMedicamento" tabindex="-1" aria-labelledby="modalMedicamentoLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalMedicamentoLabel">Novo Medicamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formMedicamento" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="medicamento_id" id="medicamento_id">
                            <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Nome do Medicamento:</label>
                                    <input type="text" name="nome_medicamento" id="nome_medicamento" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Dosagem:</label>
                                    <input type="text" name="dosagem" id="dosagem" class="form-control" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label>Frequência:</label>
                                    <input type="text" name="frequencia" id="frequencia" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Data Início:</label>
                                    <input type="date" name="data_inicio" id="data_inicio" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Data Fim:</label>
                                    <input type="date" name="data_fim" id="data_fim" class="form-control">
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Observações:</label>
                                <textarea name="observacoes" id="observacoes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <!---------------------------------------------------------------------------->
        <!-- Seção de Exames -->
        <div class="section-card"> 
            <h2 class="section-header">Exames</h2>
            
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <?php if (temPermissao()): ?>
                        <button onclick="abrirModalExame(<?php echo $paciente_id; ?>)" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                        </button>
                    <?php endif; ?>
                </div>
                
                <?php
                // Contar total de registros
                $query_total = "SELECT COUNT(*) as total FROM exames WHERE paciente_id = ?";
                $stmt_total = $conn->prepare($query_total);
                $stmt_total->bind_param("i", $paciente_id);
                $stmt_total->execute();
                $total_exames = $stmt_total->get_result()->fetch_assoc()['total'];
                
                if ($total_exames > 3): ?>
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalTodosExames">
                        <i class="fas fa-list"></i> Ver Todos (<?php echo $total_exames; ?>)
                    </button>
                <?php endif; ?>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo de Exame</th>
                        <th>Resultado</th>
                        <th>Arquivo</th>
                        <?php if (temPermissao()): ?>
                            <th>Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query_exames = "SELECT * FROM exames WHERE paciente_id = ? ORDER BY data_exame DESC LIMIT 3";
                    $stmt_exames = $conn->prepare($query_exames);
                    $stmt_exames->bind_param('i', $paciente_id);
                    $stmt_exames->execute();
                    $result_exames = $stmt_exames->get_result();

                    while ($exame = $result_exames->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($exame['data_exame'])); ?></td>
                            <td><?php echo $exame['tipo_exame']; ?></td>
                            <td><?php echo nl2br($exame['resultado']); ?></td>
                            <td>
                                <?php if ($exame['arquivo_exame']): ?>
                                    <a href="<?php echo $exame['arquivo_exame']; ?>" target="_blank" class="btn btn-sm btn-info">
                                        <i class="fas fa-file-medical"></i> Ver Arquivo
                                    </a>
                                <?php endif; ?>
                            </td>
                            <?php if (temPermissao()): ?>
                                <td>
                                    <button onclick='editarExame(<?php echo json_encode($exame); ?>)' 
                                            class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="excluirExame(<?php echo $exame['id']; ?>)" 
                                            class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal de Exame -->
        <div class="modal fade" id="modalExame" tabindex="-1" aria-labelledby="modalExameLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalExameLabel">Novo Exame</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formExame" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="exame_id" id="exame_id">
                            <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Data do Exame:</label>
                                    <input type="date" name="data_exame" id="data_exame" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Tipo de Exame:</label>
                                    <input type="text" name="tipo_exame" id="tipo_exame" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Resultado:</label>
                                <textarea name="resultado" id="resultado" class="form-control" rows="4"></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label>Arquivo do Exame:</label>
                                <input type="file" name="arquivo_exame" id="arquivo_exame" class="form-control">
                                <div id="arquivo_atual" class="mt-2"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal para todos os exames -->
        <div class="modal fade" id="modalTodosExames" tabindex="-1" aria-labelledby="modalTodosExamesLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTodosExamesLabel">
                            <i class="fas fa-list"></i> Histórico Completo de Exames
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Tipo de Exame</th>
                                        <th>Resultado</th>
                                        <th>Arquivo</th>
                                        <?php if (temPermissao()): ?>
                                            <th>Ações</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query_todos_exames = "SELECT * FROM exames WHERE paciente_id = ? ORDER BY data_exame DESC";
                                    $stmt_todos_exames = $conn->prepare($query_todos_exames);
                                    $stmt_todos_exames->bind_param('i', $paciente_id);
                                    $stmt_todos_exames->execute();
                                    $result_todos_exames = $stmt_todos_exames->get_result();

                                    while ($exame = $result_todos_exames->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($exame['data_exame'])); ?></td>
                                            <td><?php echo $exame['tipo_exame']; ?></td>
                                            <td><?php echo nl2br($exame['resultado']); ?></td>
                                            <td>
                                                <?php if ($exame['arquivo_exame']): ?>
                                                    <a href="<?php echo $exame['arquivo_exame']; ?>" target="_blank" class="btn btn-sm btn-info">
                                                        <i class="fas fa-file-medical"></i> Ver Arquivo
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <?php if (temPermissao()): ?>
                                                <td>
                                                    <button onclick='editarExame(<?php echo json_encode($exame); ?>)' 
                                                            class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="excluirExame(<?php echo $exame['id']; ?>)" 
                                                            class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    
        <!---------------------------------------------------------------------------->
        <!-- Seção de Análises e Estatísticas -->
        <div class="section-card">
            <h2 class="section-header">Análises e Estatísticas</h2>
            
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <?php
                    // Contar total de registros
                    $query_total = "SELECT COUNT(*) as total FROM analises_estatisticas WHERE paciente_id = ?";
                    $stmt_total = $conn->prepare($query_total);
                    $stmt_total->bind_param("i", $paciente_id);
                    $stmt_total->execute();
                    $total_analises = $stmt_total->get_result()->fetch_assoc()['total'];
                    
                    if ($total_analises > 3): ?>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalTodasAnalises">
                            <i class="fas fa-list"></i> Ver Todos (<?php echo $total_analises; ?>)
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Pressão Arterial</th>
                        <th>Glicemia</th>
                        <th>Risco Cardiovascular</th>
                        <?php if (temPermissao()): ?>
                            <th>Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query_analises = "SELECT * FROM analises_estatisticas WHERE paciente_id = ? ORDER BY data_analise DESC LIMIT 3";
                    $stmt_analises = $conn->prepare($query_analises);
                    $stmt_analises->bind_param('i', $paciente_id);
                    $stmt_analises->execute();
                    $result_analises = $stmt_analises->get_result();

                    while ($analise = $result_analises->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($analise['data_analise'])); ?></td>
                            <td><?php echo $analise['comparativo_pa']; ?></td>
                            <td><?php echo $analise['comparativo_glicemia']; ?></td>
                            <td><?php echo $analise['comparativo_risco_cardio']; ?></td>
                            <?php if (temPermissao()): ?>
                                <td>
                                    <button onclick="excluirAnalise(<?php echo $analise['id']; ?>)" 
                                            class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal para todas as análises -->
        <div class="modal fade" id="modalTodasAnalises" tabindex="-1" aria-labelledby="modalTodasAnalisesLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTodasAnalisesLabel">
                            <i class="fas fa-list"></i> Histórico Completo de Análises
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Pressão Arterial</th>
                                        <th>Glicemia</th>
                                        <th>Risco Cardiovascular</th>
                                        <?php if (temPermissao()): ?>
                                            <th>Ações</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query_todas_analises = "SELECT * FROM analises_estatisticas WHERE paciente_id = ? ORDER BY data_analise DESC";
                                    $stmt_todas_analises = $conn->prepare($query_todas_analises);
                                    $stmt_todas_analises->bind_param('i', $paciente_id);
                                    $stmt_todas_analises->execute();
                                    $result_todas_analises = $stmt_todas_analises->get_result();

                                    while ($analise = $result_todas_analises->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($analise['data_analise'])); ?></td>
                                            <td><?php echo $analise['comparativo_pa']; ?></td>
                                            <td><?php echo $analise['comparativo_glicemia']; ?></td>
                                            <td><?php echo $analise['comparativo_risco_cardio']; ?></td>
                                            <?php if (temPermissao()): ?>
                                                <td>
                                                    <button onclick="excluirAnalise(<?php echo $analise['id']; ?>)" 
                                                            class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    <script>
        <!-- Script para o modal de acompanhamento -->
        $(document).ready(function() {
            initializeForms();
            initializeMasks();
        });

        // Inicialização de formulários e eventos
        function initializeForms() {
            $('#formAcompanhamento').on('submit', handleAcompanhamentoSubmit);
            $('#formEditarAcompanhamento').on('submit', handleEditFormSubmit);
        }

        // Handlers de formulários
        function handleAcompanhamentoSubmit(event) {
            event.preventDefault();
            submitAjaxForm($(this), 'salvar_acompanhamento.php', {
                onSuccess: function(response) {
                    $('#modalAcompanhamento').modal('hide');
                    $('#formAcompanhamento')[0].reset();
                    atualizarTodasTabelas(); // Adicionar esta linha
                }
            });
        }

        function handleEditFormSubmit(event) {
            event.preventDefault();
            submitAjaxForm($(this), 'atualizar_acompanhamento.php', {
                onSuccess: function(response) {
                    $('#modalEditarAcompanhamento').modal('hide');
                    atualizarTodasTabelas(); // Adicionar esta linha
                }
            });
        }

        // Funções AJAX principais
        function submitAjaxForm($form, url, options = {}) {
            $.ajax({
                type: 'POST',
                url: url,
                data: $form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (options.onSuccess) options.onSuccess(response);
                        showSuccessMessage(response.message);
                    } else {
                        showErrorMessage(response.message || 'Erro ao processar operação');
                    }
                },
                error: function(xhr) {
                    console.error('Erro na requisição:', xhr.responseText);
                    showErrorMessage('Erro ao processar a requisição.');
                }
            });
        }

        // Funções de UI
        function initializeMasks() {
            $('.glicemia').mask('000', {
                reverse: true,
                onChange: function(value, e) {
                    var numValue = parseInt(value);
                    if (numValue > 999) $(e.target).val('999');
                    else if (numValue < 0 && value !== '') $(e.target).val('0');
                }
            });

            $('.pressao-arterial').mask('000/000', {
                reverse: false,
                onChange: function(value, e) {
                    if (value.includes('/')) {
                        var [sistolica, diastolica] = value.split('/').map(Number);
                        sistolica = Math.min(999, sistolica);
                        diastolica = Math.min(999, diastolica);
                        if (!isNaN(sistolica) && !isNaN(diastolica)) {
                            $(e.target).val(`${sistolica}/${diastolica}`);
                        }
                    }
                }
            });
        }

        function criarLinhaTabela(acompanhamento) {
            const temPermissao = <?php echo temPermissao() ? 'true' : 'false'; ?>;
            
            return `
                <tr data-id="${acompanhamento.id}">
                    <td>${acompanhamento.data_formatada}</td>
                    <td>${acompanhamento.glicemia || 'Não informado'}</td>
                    <td>${acompanhamento.hipertensao || 'Não informado'}</td>
                    <td>${acompanhamento.observacoes || 'Não informado'}</td>
                    ${temPermissao ? `
                        <td>
                            <div class="btn-group">
                                <button onclick="editarAcompanhamento(${JSON.stringify(acompanhamento)})" 
                                        class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button onclick="excluirAcompanhamento(${acompanhamento.id})" 
                                        class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> Excluir
                                </button>
                            </div>
                        </td>
                    ` : ''}
                </tr>
            `;
        }

        // Funções de ações
        function editarAcompanhamento(acompanhamento) {
            $('#edit_acompanhamento_id').val(acompanhamento.id);
            $('#edit_data_acompanhamento').val(acompanhamento.data_acompanhamento);
            $('#edit_glicemia').val(acompanhamento.glicemia);
            $('#edit_hipertensao').val(acompanhamento.hipertensao);
            $('#edit_observacoes').val(acompanhamento.observacoes);
            
            new bootstrap.Modal(document.getElementById('modalEditarAcompanhamento')).show();
        }

        function excluirAcompanhamento(id) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Esta ação não poderá ser revertida!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'excluir_acompanhamento.php',
                        type: 'POST',
                        data: { id: id },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                atualizarTodasTabelas(); // Adicionar esta linha
                                showSuccessMessage('Registro excluído com sucesso!');
                            } else {
                                showErrorMessage(response.message || 'Erro ao excluir o registro.');
                            }
                        },
                        error: function(xhr) {
                            console.error('Erro:', xhr.responseText);
                            showErrorMessage('Erro na comunicação com o servidor.');
                        }
                    });
                }
            });
        }



        // Funções auxiliares
        function showSuccessMessage(message) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: message,
                showConfirmButton: false,
                timer: 1500
            });
        }

        function showErrorMessage(message) {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: message
            });
        }

        function atualizarBotaoVerTodos(total) {
            // Torna o seletor mais específico para a seção de acompanhamento
            const btnContainer = $('.section-card:contains("Acompanhamento em Casa") .d-flex.justify-content-between.mb-3');
            let btnVerTodos = btnContainer.find('.btn-info');
            
            if (total > 3) {
                const btnHtml = `
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalTodosAcompanhamentos">
                        <i class="fas fa-list"></i> Ver Todos (${total})
                    </button>
                `;
                
                btnVerTodos.length ? btnVerTodos.html(btnHtml) : btnContainer.append(btnHtml);
            } else {
                btnVerTodos.remove();
            }
        }

        // Função para atualizar todas as tabelas após qualquer operação CRUD
        function atualizarTodasTabelas() {
            // Atualiza a tabela principal (últimos 3 registros)
            atualizarTabelaAcompanhamentos();
            // Atualiza a tabela do modal com todos os registros
            atualizarTabelaAcompanhamentosModal();
        }

        // Função para atualizar a tabela principal
        function atualizarTabelaAcompanhamentos() {
            const paciente_id = <?php echo $paciente_id; ?>;
            
            $.ajax({
                url: 'buscar_acompanhamentos.php',
                type: 'GET',
                data: { 
                    paciente_id: paciente_id,
                    limit: 3
                },
                dataType: 'json',
                success: function(response) {
                    // Usar um seletor mais específico para a tabela de acompanhamentos
                    const tabelaAcompanhamento = $('.section-card:contains("Acompanhamento em Casa") .data-table tbody');
                    tabelaAcompanhamento.empty();
                    
                    if (response.registros.length === 0) {
                        tabelaAcompanhamento.append(`
                            <tr>
                                <td colspan="5" class="text-center">Nenhum registro encontrado</td>
                            </tr>
                        `);
                    } else {
                        response.registros.forEach(function(acompanhamento) {
                            var linha = criarLinhaTabela(acompanhamento);
                            tabelaAcompanhamento.append(linha);
                        });
                    }

                    // Atualizar botão "Ver Todos"
                    atualizarBotaoVerTodos(response.total);
                },
                error: function(xhr) {
                    console.error('Erro ao atualizar tabela:', xhr.responseText);
                    showErrorMessage('Erro ao atualizar os dados.');
                }
            });
        }

        // Função para atualizar a tabela do modal
        function atualizarTabelaAcompanhamentosModal() {
            const paciente_id = <?php echo $paciente_id; ?>;
            
            $.ajax({
                url: 'buscar_acompanhamentos.php',
                type: 'GET',
                data: { 
                    paciente_id: paciente_id,
                    get_all: true
                },
                dataType: 'json',
                success: function(response) {
                    // Usar um seletor específico para a tabela do modal de acompanhamentos
                    const tabelaModal = $('#modalTodosAcompanhamentos .table tbody');
                    tabelaModal.empty();
                    
                    if (response.registros.length === 0) {
                        tabelaModal.append(`
                            <tr>
                                <td colspan="5" class="text-center">Nenhum registro encontrado</td>
                            </tr>
                        `);
                    } else {
                        response.registros.forEach(function(acompanhamento) {
                            var linha = criarLinhaTabela(acompanhamento);
                            tabelaModal.append(linha);
                        });
                    }
                },
                error: function(xhr) {
                    console.error('Erro ao atualizar modal:', xhr.responseText);
                }
            });
        }

        <!---------------------------------------------------------------------------->
        <!-- Script para o modal de doença -->
        function editarDoenca(dados) {
            // Preenche os campos do modal com os dados recebidos
            document.getElementById('edit_doenca_id').value = dados.id;
            document.getElementById('edit_tipo_doenca').value = dados.tipo_doenca;
            document.getElementById('edit_historico_familiar').value = dados.historico_familiar;
            
            // Garante que o estado civil seja selecionado corretamente
            const selectEstadoCivil = document.getElementById('edit_estado_civil');
            if (dados.estado_civil) {
                selectEstadoCivil.value = dados.estado_civil;
            } else {
                selectEstadoCivil.value = '';
            }
            
            document.getElementById('edit_profissao').value = dados.profissao;

            // Abre o modal usando Bootstrap 5
            const modal = new bootstrap.Modal(document.getElementById('modalEditarDoenca'));
            modal.show();
        }

        // Manipula o envio do formulário
        document.getElementById('formEditarDoenca').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                // Coleta os dados do formulário
                const formData = new FormData(this);
                
                // Envia os dados para o servidor
                const response = await fetch('atualizar_doenca_paciente.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'Dados atualizados com sucesso!'
                    });
                    
                    location.reload();
                } else {
                    throw new Error(data.message || 'Erro ao atualizar os dados');
                }
            } catch (error) {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message || 'Erro ao atualizar os dados'
                });
            }
        });

        <!---------------------------------------------------------------------------->
        <!-- Script para o modal de médico -->
        function abrirModalMedico(pacienteId) {
            $('#paciente_id').val(pacienteId);
            new bootstrap.Modal(document.getElementById('modalMedico')).show();
        }

        function abrirModalAtribuirMedico(pacienteId) {
            $('#atribuir_paciente_id').val(pacienteId);
            new bootstrap.Modal(document.getElementById('modalAtribuirMedico')).show();
        }

        function handleFormSubmit(formId, actionUrl, modalId) {
            $(`#${formId}`).on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: actionUrl,
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: response.message || 'Operação realizada com sucesso!'
                            }).then(() => location.reload());
                        } else {
                            throw new Error(response.message || 'Erro ao processar operação');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Erro na requisição:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: 'Erro ao processar a requisição'
                        });
                    }
                });
            });
        }

        $(document).ready(function() {
            handleFormSubmit('formTrocarMedico', 'trocar_medico.php', 'modalMedico');
            handleFormSubmit('formAtribuirMedico', 'atribuir_medico.php', 'modalAtribuirMedico');
        });

        $('#formAtribuirMedico').on('submit', function(e) {
            e.preventDefault();
            
            // Mostrar loading
            Swal.fire({
                title: 'Processando...',
                text: 'Aguarde enquanto atribuímos o médico',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: 'atribuir_medico.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: response.message
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: response.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição:', error);
                    console.log('Resposta do servidor:', xhr.responseText);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Ocorreu um erro ao atribuir o médico. Por favor, tente novamente.'
                    });
                }
            });
        });


        <!---------------------------------------------------------------------------->
        <!-- Script para o modal de consulta -->
        function abrirModalConsulta(pacienteId) {
            var myModal = new bootstrap.Modal(document.getElementById('modalConsulta'));
            myModal.show();
        }

        // Adicione este código para fechar o modal após submeter o formulário com sucesso
        $(document).ready(function() {
            $('#formConsulta').on('submit', function(e) {
                e.preventDefault();
                
                // Validar pressão arterial
                const pressaoArterial = $('input[name="pressao_arterial"]').val();
                if (pressaoArterial) {
                    const pattern = /^\d{2,3}\/\d{2,3}$/;
                    if (!pattern.test(pressaoArterial)) {
                        alert('Formato de pressão arterial inválido. Use o formato: 120/80');
                        return false;
                    }
                    
                    const [sistolica, diastolica] = pressaoArterial.split('/').map(Number);
                    if (sistolica < 70 || sistolica > 200 || diastolica < 40 || diastolica > 130) {
                        alert('Valores de pressão arterial fora do intervalo aceitável');
                        return false;
                    }
                }
                
                // Validar glicemia
                const glicemia = $('input[name="glicemia"]').val();
                if (glicemia && (glicemia < 20 || glicemia > 600)) {
                    alert('Valor de glicemia fora do intervalo aceitável (20-600 mg/dL)');
                    return false;
                }
                
                // Validar peso
                const peso = $('input[name="peso"]').val();
                if (peso && (peso < 20 || peso > 300)) {
                    alert('Valor de peso fora do intervalo aceitável (20-300 kg)');
                    return false;
                }
                
                // Validar altura
                const altura = $('input[name="altura"]').val();
                if (altura && (altura < 10 || altura > 250)) {
                    alert('Valor de altura fora do intervalo aceitável (10-250 cm)');
                    return false;
                }
            });
        });

        function validarPressaoArterial(input) {
            // Remove qualquer caractere que não seja número ou /
            input.value = input.value.replace(/[^\d/]/g, '');
            
            if (input.value.includes('/')) {
                let [sistolica, diastolica] = input.value.split('/').map(Number);
                
                // Limita sistólica entre 70 e 200
                if (sistolica && !isNaN(sistolica)) {
                    sistolica = Math.min(Math.max(parseInt(sistolica), 70), 200);
                }
                
                // Limita diastólica entre 40 e 130
                if (diastolica && !isNaN(diastolica)) {
                    diastolica = Math.min(Math.max(parseInt(diastolica), 40), 130);
                }
                
                // Atualiza o valor do input
                if (sistolica && diastolica) {
                    input.value = `${sistolica}/${diastolica}`;
                }
            }
        }

        $(document).ready(function() {
            // Máscara para pressão arterial (000/000)
            $('.pressao-arterial').mask('000/000');
            
            // Máscara para glicemia (até 3 dígitos)
            $('.glicemia').mask('000', {
                reverse: true,
                translation: {
                    '0': {pattern: /[0-9]/}
                }
            });
            
            // Máscara para peso (000.0)
            $('.peso').mask('000.0', {
                reverse: true,
                translation: {
                    '0': {pattern: /[0-9]/}
                }
            });
            
            // Máscara para altura (000)
            $('.altura').mask('000', {
                reverse: true,
                translation: {
                    '0': {pattern: /[0-9]/}
                }
            });

            // Validação adicional para pressão arterial
            $('.pressao-arterial').on('blur', function() {
                let valor = $(this).val();
                if (valor) {
                    let [sistolica, diastolica] = valor.split('/').map(Number);
                    if (sistolica < 70 || sistolica > 200 || diastolica < 40 || diastolica > 130) {
                        alert('Valores de pressão arterial fora do intervalo aceitável');
                        $(this).val('');
                    }
                }
            });

            // Validação para glicemia
            $('.glicemia').on('blur', function() {
                let valor = parseInt($(this).val());
                if (valor < 20 || valor > 600) {
                    alert('Valor de glicemia fora do intervalo aceitável (20-600 mg/dL)');
                    $(this).val('');
                }
            });

            // Validação para peso
            $('.peso').on('blur', function() {
                let valor = parseFloat($(this).val());
                if (valor < 0 || valor > 300) {
                    alert('Valor de peso fora do intervalo aceitável (0-300 kg)');
                    $(this).val('');
                }
            });

            // Validação para altura
            $('.altura').on('blur', function() {
                let valor = parseInt($(this).val());
                if (valor < 10 || valor > 250) {
                    alert('Valor de altura fora do intervalo aceitável (10-250 cm)');
                    $(this).val('');
                }
            });
        });

        function editarConsulta(consulta) {
            var modalEditarConsulta = new bootstrap.Modal(document.getElementById('modalEditarConsulta'));
            
            // Log para debug
            console.log('Dados da consulta:', consulta);
            console.log('Observações:', consulta.observacoes);
            
            modalEditarConsulta.show();
            
            // Pequeno delay para garantir que o modal esteja carregado
            setTimeout(() => {
                // Preenche todos os campos
                document.getElementById('edit_consulta_id').value = consulta.id;
                document.getElementById('edit_profissional_id').value = consulta.profissional_id;
                document.getElementById('edit_data_consulta').value = consulta.data_consulta;
                document.getElementById('edit_pressao_arterial').value = consulta.pressao_arterial;
                document.getElementById('edit_glicemia').value = consulta.glicemia;
                document.getElementById('edit_peso').value = consulta.peso;
                document.getElementById('edit_altura').value = consulta.altura;
                document.getElementById('edit_estado_emocional').value = consulta.estado_emocional;
                document.getElementById('edit_habitos_vida').value = consulta.habitos_vida;
                
                // Garante que o campo de observações existe e tem um valor
                const observacoesField = document.getElementById('edit_observacoes');
                if (observacoesField) {
                    observacoesField.value = consulta.observacoes || '';
                }
            }, 100);
        }

        // Função para buscar os dados da consulta
        function buscarConsulta(consultaId) {
            fetch(`buscar_consulta.php?id=${consultaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Dados completos recebidos:', data.consulta);
                        console.log('Observações:', data.consulta.observacoes);
                        editarConsulta(data.consulta);
                    } else {
                        alert('Erro ao buscar dados da consulta: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao buscar dados da consulta');
                });
        }

        // Manipula o envio do formulário de edição
        $('#formEditarConsulta').on('submit', function(e) {
            e.preventDefault();
            
            let formData = $(this).serializeArray();
            let dados = {};
            
            // Converte os dados do formulário para um objeto
            formData.forEach(function(item) {
                dados[item.name] = item.value;
            });

            // Debug
            console.log('Dados a serem enviados:', dados);
            
            $.ajax({
                url: 'atualizar_consulta.php',
                type: 'POST',
                data: dados,
                dataType: 'json',
                success: function(response) {
                    console.log('Resposta do servidor:', response);
                    try {
                        if (response.success) {
                            // Fecha o modal
                            var myModal = bootstrap.Modal.getInstance(document.getElementById('modalEditarConsulta'));
                            myModal.hide();
                            
                            // Mostra mensagem de sucesso
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: 'Consulta atualizada com sucesso!'
                            }).then((result) => {
                                location.reload();
                            });
                        } else {
                            throw new Error(response.message || 'Erro ao atualizar consulta');
                        }
                    } catch (error) {
                        console.error('Erro ao processar resposta:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: error.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro detalhado:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        readyState: xhr.readyState,
                        statusText: xhr.statusText
                    });
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao processar a requisição. Verifique o console para mais detalhes.'
                    });
                }
            });
        });

        function excluirConsulta(consultaId) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Esta ação não poderá ser revertida!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'excluir_consulta.php',
                        type: 'POST',
                        data: { consulta_id: consultaId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Excluído!',
                                    text: 'A consulta foi excluída com sucesso.'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro!',
                                    text: response.message || 'Ocorreu um erro ao excluir a consulta.'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Erro:', xhr.responseText);
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: 'Ocorreu um erro na comunicação com o servidor.'
                            });
                        }
                    });
                }
            });
        }
        
        $(document).ready(function() {
            // Configuração única das máscaras e validações
            const mascaras = {
                '.pressao-arterial': {
                    mask: '000/000',
                    validation: function(valor) {
                        if (!valor) return true;
                        const [sistolica, diastolica] = valor.split('/').map(Number);
                        return validarCampo(sistolica, 70, 200, 'pressão sistólica') &&
                               validarCampo(diastolica, 40, 130, 'pressão diastólica');
                    }
                },
                '.glicemia': {
                    mask: '000',
                    reverse: true,
                    validation: (valor) => validarCampo(parseInt(valor), 20, 600, 'glicemia')
                },
                '.peso': {
                    mask: '000.0',
                    reverse: true,
                    validation: (valor) => validarCampo(parseFloat(valor), 0, 300, 'peso')
                },
                '.altura': {
                    mask: '000',
                    reverse: true,
                    validation: (valor) => validarCampo(parseInt(valor), 10, 250, 'altura')
                }
            };

            // Aplicar máscaras e validações
            Object.entries(mascaras).forEach(([selector, config]) => {
                $(selector).mask(config.mask, { reverse: config.reverse });
                $(selector).on('blur', function() {
                    const valor = $(this).val();
                    if (valor && !config.validation(valor)) {
                        $(this).val('');
                    }
                });
            });

            // Atualização automática do IMC
            $('.peso, .altura').on('input', calcularIMC);

            // Handlers de formulários
            initFormHandlers();
        });

        // 3. Consolidar handlers de formulários em uma função
        function initFormHandlers() {
            // Handler para o formulário de consulta
            $('#formConsulta').on('submit', handleConsultaSubmit);
            
            // Handler para o formulário de edição
            $('#formEditarConsulta').on('submit', handleConsultaSubmit);
        }

        // 4. Função genérica para lidar com submissão de formulários
        function handleConsultaSubmit(e) {
            e.preventDefault();
            const $form = $(this);
            const $submitButton = $form.find('button[type="submit"]');
            $submitButton.prop('disabled', true);

            const formData = new FormData(this);
            const isEditForm = $form.attr('id') === 'formEditarConsulta';
            const url = isEditForm ? 'atualizar_consulta.php' : 'salvar_consulta.php';

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const jsonResponse = (typeof response === 'string') ? JSON.parse(response) : response;
                        
                        if (jsonResponse.success) {
                            // Fechar o modal
                            const modalId = isEditForm ? 'modalEditarConsulta' : 'modalConsulta';
                            handleModal(modalId, 'hide');
                            
                            // Mostrar mensagem de sucesso
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: jsonResponse.message || 'Operação realizada com sucesso!'
                            }).then(() => location.reload());
                            
                            if (!isEditForm) {
                                $form[0].reset();
                            }
                        } else {
                            throw new Error(jsonResponse.message || 'Erro ao processar operação');
                        }
                    } catch (error) {
                        console.error('Erro ao processar resposta:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: error.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao processar a requisição: ' + error
                    });
                },
                complete: function() {
                    $submitButton.prop('disabled', false);
                }
            });
        }

        // 5. Consolidar funções de manipulação de modal
        function handleModal(modalId, action = 'show') {
            const modal = bootstrap.Modal.getInstance(document.getElementById(modalId)) || 
                         new bootstrap.Modal(document.getElementById(modalId));
            modal[action]();
        }

        // 1. Consolidar as validações de campos em uma única função
        function validarCampo(valor, min, max, tipo) {
            if (valor < min || valor > max) {
                alert(`Valor de ${tipo} fora do intervalo aceitável (${min}-${max})`);
                return false;
            }
            return true;
        }

        function calcularIMC() {
            const peso = parseFloat($('.peso').val());
            const altura = parseFloat($('.altura').val()) / 100; // Converter cm para metros
            
            if (peso && altura) {
                const imc = peso / (altura * altura);
                $('#imc').val(imc.toFixed(1));
                
                // Classificação do IMC
                let classificacao = '';
                if (imc < 18.5) classificacao = 'Abaixo do peso';
                else if (imc < 25) classificacao = 'Peso normal';
                else if (imc < 30) classificacao = 'Sobrepeso';
                else if (imc < 35) classificacao = 'Obesidade Grau I';
                else if (imc < 40) classificacao = 'Obesidade Grau II';
                else classificacao = 'Obesidade Grau III';
                
                // Atualiza o texto da classificação do IMC
                $('#classificacao_imc').text(classificacao); // Corrigido para usar .text()
            } else {
                $('#imc').val('');
                $('#classificacao_imc').text(''); // Corrigido para usar .text()
            }
        }

        <!---------------------------------------------------------------------------->
        <!-- Script para o modal de riscos cardiovasculares -->

        // Função auxiliar para converter valores em números
        function getNumericValue(value) {
            const num = parseFloat(value);
            return isNaN(num) ? 0 : num;
        }

        function getProbabilidadeByPontos(pontos, sexo) {
            if (sexo === 'Homem') {
                if (pontos < 0) return 1;
                switch (pontos) {
                    case 0:
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                        return 1;
                    case 5:
                    case 6:
                        return 2;
                    case 7:
                        return 3;
                    case 8:
                        return 4;
                    case 9:
                        return 5;
                    case 10:
                        return 6;
                    case 11:
                        return 8;
                    case 12:
                        return 10;
                    case 13:
                        return 12;
                    case 14:
                        return 16;
                    case 15:
                        return 20;
                    case 16:
                        return 25;
                    default:
                        return 30;
                }
            } else { // Mulher
                if (pontos < 9) return 1;
                switch (pontos) {
                    case 9:
                    case 10:
                    case 11:
                    case 12:
                        return 1;
                    case 13:
                    case 14:
                        return 2;
                    case 15:
                        return 3;
                    case 16:
                        return 4;
                    case 17:
                        return 5;
                    case 18:
                        return 6;
                    case 19:
                        return 8;
                    case 20:
                        return 11;
                    case 21:
                        return 14;
                    case 22:
                        return 17;
                    case 23:
                        return 22;
                    case 24:
                        return 27;
                    default:
                        return 30;
                }
            }
        }

        function validarCamposRisco() {
            const form = document.getElementById('formRiscoCardiovascular');
            const campos = form.querySelectorAll('select[required], input[required]');
            
            for (let campo of campos) {
                if (!campo.value) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Por favor, preencha todos os campos obrigatórios.'
                    });
                    return false;
                }
            }
            return true;
        }

        function calcularRisco() {
            const form = document.getElementById('formRiscoCardiovascular');
            const formData = new FormData(form);
            
            if (!validarCamposRisco()) {
                return false;
            }

            try {
                // Obter valores dos campos
                const sexo = formData.get('sexo');
                
                // Calcular pontuação
                const pontuacao = calcularPontuacao(formData);
                
                // Obter probabilidade baseada na pontuação
                const probabilidade = getProbabilidadeByPontos(pontuacao, sexo);

                // Atualizar campos de resultado
                const pontuacaoInput = document.getElementById('pontuacao');
                const probabilidadeInput = document.getElementById('probabilidade');
                
                if (pontuacaoInput && probabilidadeInput) {
                    pontuacaoInput.value = pontuacao;
                    probabilidadeInput.value = probabilidade;
                    
                    // Mostrar div de resultados e botão de salvar
                    document.getElementById('resultadosCalculo').style.display = 'flex';
                    document.getElementById('btnSalvar').style.display = 'inline-block';
                } else {
                    throw new Error('Elementos de resultado não encontrados');
                }

            } catch (error) {
                console.error('Erro no cálculo:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro no cálculo',
                    text: error.message
                });
            }
        }

        function salvarRiscoCardiovascular() {
            const form = document.getElementById('formRiscoCardiovascular');
            const formData = new FormData(form);

            $.ajax({
                url: 'salvar_risco_cardiovascular.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        let jsonResponse = (typeof response === 'string') ? JSON.parse(response) : response;
                        
                        if (jsonResponse.success) {
                            $('#modalRiscoCardiovascular').modal('hide');
                            
                            // Atualizar as tabelas
                            atualizarTabelasRisco();
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: 'Risco cardiovascular salvo com sucesso!',
                                showConfirmButton: false,
                                timer: 1500
                            });

                            form.reset();
                            document.getElementById('btnSalvar').style.display = 'none';
                            document.getElementById('resultadosCalculo').style.display = 'none';
                        } else {
                            throw new Error(jsonResponse.message || 'Erro ao salvar os dados');
                        }
                    } catch (error) {
                        console.error('Erro:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: error.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao processar a requisição: ' + error
                    });
                }
            });
        }

        // Função auxiliar para calcular pontuação
        function calcularPontuacao(formData) {
            const sexo = formData.get('sexo');
            const idade = parseInt(formData.get('idade').split('-')[0]);
            const colesterolTotal = parseFloat(formData.get('colesterol_total'));
            const colesterolHDL = parseFloat(formData.get('colesterol_hdl'));
            const pressaoSistolica = parseFloat(formData.get('pressao_sistolica'));
            const remediosHipertensao = formData.get('remedios_hipertensao') === 'Sim' ? 1 : 0;
            const fumante = formData.get('fumante') === 'Sim' ? 1 : 0;

            // Cálculo do logaritmo natural dos valores
            const lnIdade = Math.log(idade);
            const lnColTotal = Math.log(colesterolTotal);
            const lnHDL = Math.log(colesterolHDL);
            const lnPressao = Math.log(pressaoSistolica);

            let L, P;

            if (sexo === 'Homem') {
                // Ajuste para idade > 70 em homens
                const lnIdadeFumante = idade > 70 ? Math.log(70) * fumante : lnIdade * fumante;

                L = (52.00961 * lnIdade) +
                    (20.014077 * lnColTotal) +
                    (-0.905964 * lnHDL) +
                    (1.305784 * lnPressao) +
                    (0.241549 * remediosHipertensao) +
                    (12.096316 * fumante) +
                    (-4.605038 * lnIdade * lnColTotal) +
                    (-2.84367 * lnIdadeFumante) +
                    (-2.93323 * lnIdade * lnIdade) -
                    172.300168;

                P = 1 - Math.pow(0.9402, Math.exp(L));

                // Converter probabilidade em pontos para homens
                const probabilidade = P * 100;
                if (probabilidade < 1) return 0;
                else if (probabilidade < 2) return 5;
                else if (probabilidade < 3) return 7;
                else if (probabilidade < 4) return 8;
                else if (probabilidade < 5) return 9;
                else if (probabilidade < 6) return 10;
                else if (probabilidade < 8) return 11;
                else if (probabilidade < 10) return 12;
                else if (probabilidade < 12) return 13;
                else if (probabilidade < 16) return 14;
                else if (probabilidade < 20) return 15;
                else if (probabilidade < 25) return 16;
                else return 17;

            } else {
                // Ajuste para idade > 78 em mulheres
                const lnIdadeFumante = idade > 78 ? Math.log(78) * fumante : lnIdade * fumante;

                L = (31.764001 * lnIdade) +
                    (22.465206 * lnColTotal) +
                    (-1.187731 * lnHDL) +
                    (2.552905 * lnPressao) +
                    (0.420251 * remediosHipertensao) +
                    (13.07543 * fumante) +
                    (-5.060998 * lnIdade * lnColTotal) +
                    (-2.996945 * lnIdadeFumante) -
                    146.5933061;

                P = 1 - Math.pow(0.98767, Math.exp(L));

                // Converter probabilidade em pontos para mulheres
                const probabilidade = P * 100;
                if (probabilidade < 1) return 9;
                else if (probabilidade < 2) return 13;
                else if (probabilidade < 3) return 15;
                else if (probabilidade < 4) return 16;
                else if (probabilidade < 5) return 17;
                else if (probabilidade < 6) return 18;
                else if (probabilidade < 8) return 19;
                else if (probabilidade < 11) return 20;
                else if (probabilidade < 14) return 21;
                else if (probabilidade < 17) return 22;
                else if (probabilidade < 22) return 23;
                else if (probabilidade < 27) return 24;
                else return 25;
            }
        }

        // Inicialização quando o documento estiver pronto
        $(document).ready(function() {
            // Carregar dados iniciais
            atualizarTabelasRisco();

            // Handler para o formulário de novo risco
            $('#formRiscoCardiovascular').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                $.ajax({
                    url: 'salvar_risco_cardiovascular.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#modalRiscoCardiovascular').modal('hide');
                            $('#formRiscoCardiovascular')[0].reset();
                            
                            // Atualizar tabelas após salvar
                            atualizarTabelasRisco();
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: 'Risco cardiovascular salvo com sucesso!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        } else {
                            throw new Error(response.message || 'Erro ao salvar os dados');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Erro:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: 'Erro ao salvar os dados: ' + error
                        });
                    }
                });
            });

            // Handler para o formulário de edição
            $('#formEditarRiscoCardiovascular').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'atualizar_risco.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#modalEditarRiscoCardiovascular').modal('hide');
                            
                            // Atualizar tabelas após editar
                            atualizarTabelasRisco();
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: 'Risco atualizado com sucesso!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        } else {
                            throw new Error(response.message || 'Erro ao atualizar os dados');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Erro:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: 'Erro ao atualizar os dados: ' + error
                        });
                    }
                });
            });

            // Atualizar quando o modal de todos os riscos for aberto
            $('#modalTodosRiscos').on('show.bs.modal', function () {
                atualizarTabelasRisco();
            });
        });

        // Função para excluir risco
        function excluirRisco(id) {
            Swal.fire({
                title: 'Confirmar exclusão',
                text: "Você tem certeza que deseja excluir este registro?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'excluir_risco.php',
                        type: 'POST',
                        data: { id: id },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Atualizar tabelas após excluir
                                atualizarTabelasRisco();
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sucesso!',
                                    text: 'Registro excluído com sucesso!',
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                            } else {
                                throw new Error(response.message || 'Erro ao excluir o registro');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Erro:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro',
                                text: 'Erro ao excluir o registro: ' + error
                            });
                        }
                    });
                }
            });
        }

        // Função para calcular e salvar risco
        function calcularESalvarRisco() {
            // ... seu código de cálculo ...
            
            // Após o cálculo bem-sucedido
            $('#btnSalvar').on('click', function() {
                salvarRiscoCardiovascular();
            });
        }

        // Função para recalcular o risco
        function recalcularRisco() {
            const formData = new FormData();
            formData.append('sexo', document.getElementById('editar_sexo').value);
            formData.append('idade', document.getElementById('editar_idade').value);
            formData.append('colesterol_total', document.getElementById('editar_colesterol_total').value);
            formData.append('colesterol_hdl', document.getElementById('editar_colesterol_hdl').value);
            formData.append('pressao_sistolica', document.getElementById('editar_pressao_sistolica').value);
            formData.append('fumante', document.getElementById('editar_fumante').value);
            formData.append('remedios_hipertensao', document.getElementById('editar_remedios_hipertensao').value);

            // Calcular a pontuação
            const pontuacao = calcularPontuacao(formData);
            
            // Reutilizar a função existente para obter a probabilidade
            const probabilidade = getProbabilidadeByPontos(pontuacao, formData.get('sexo'));

            // Atualizar os campos de resultado
            document.getElementById('editar_pontuacao').value = pontuacao;
            document.getElementById('editar_probabilidade').value = probabilidade;

            // Mostrar o botão de salvar
            document.querySelector('#formEditarRiscoCardiovascular button[type="submit"]').style.display = 'block';

            // Feedback visual do cálculo concluído
            Swal.fire({
                icon: 'success',
                title: 'Cálculo concluído!',
                text: 'Você já pode salvar as alterações.',
                showConfirmButton: false,
                timer: 1500
            });
        }

        // Remover a função editarRiscoCardiovascular pois editarRisco já faz o trabalho
        function editarRisco(risco) {
            // Preencher os campos do modal com os dados do risco
            $('#editar_risco_id').val(risco.id);
            $('#editar_sexo').val(risco.sexo);
            $('#editar_idade').val(risco.idade);
            $('#editar_colesterol_total').val(risco.colesterol_total);
            $('#editar_colesterol_hdl').val(risco.colesterol_hdl);
            $('#editar_pressao_sistolica').val(risco.pressao_sistolica);
            $('#editar_fumante').val(risco.fumante);
            $('#editar_remedios_hipertensao').val(risco.remedios_hipertensao);
            $('#editar_pontuacao').val(risco.pontuacao);
            $('#editar_probabilidade').val(risco.probabilidade);

            // Abrir o modal
            $('#modalEditarRiscoCardiovascular').modal('show');
        }

        // Função para atualizar as tabelas de risco
        function atualizarTabelasRisco() {
            const paciente_id = <?php echo $paciente_id; ?>;
            
            $.ajax({
                url: 'buscar_riscos.php',
                type: 'GET',
                data: { paciente_id: paciente_id },
                dataType: 'json',
                success: function(response) {
                    if (!response.success) {
                        console.error('Erro ao buscar riscos:', response.message);
                        return;
                    }

                    // Atualizar tabela principal (últimos 3)
                    const tbodyPrincipal = $('#historico-riscos tbody');
                    tbodyPrincipal.empty();
                    
                    // Pegar apenas os 3 primeiros registros
                    const ultimos_tres = response.riscos.slice(0, 3);
                    
                    if (ultimos_tres.length === 0) {
                        tbodyPrincipal.append(`
                            <tr>
                                <td colspan="11" class="text-center">Nenhum registro encontrado</td>
                            </tr>
                        `);
                    } else {
                        ultimos_tres.forEach(function(risco) {
                            tbodyPrincipal.append(`
                                <tr>
                                    <td>${risco.data_formatada}</td>
                                    <td>${risco.pontuacao}</td>
                                    <td>${risco.probabilidade}</td>
                                    <td>${risco.sexo}</td>
                                    <td>${risco.idade}</td>
                                    <td>${risco.colesterol_total}</td>
                                    <td>${risco.colesterol_hdl}</td>
                                    <td>${risco.pressao_sistolica}</td>
                                    <td>${risco.fumante}</td>
                                    <td>${risco.remedios_hipertensao}</td>
                                    <td>
                                        <?php if (temPermissao()): ?>
                                            <div class="btn-group">
                                                <button onclick='editarRisco(${JSON.stringify(risco).replace(/'/g, "\\'")})' 
                                                    class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="excluirRisco(${risco.id})" 
                                                    class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            `);
                        });
                    }

                    // Atualizar visibilidade do botão "Ver Todos"
                    const totalRegistros = response.riscos.length;
                    const btnVerTodos = $('#btnVerTodosRiscos');
                    
                    if (totalRegistros > 3) {
                        if (btnVerTodos.length === 0) {
                            // Se o botão não existe, criar ele
                            $('.d-flex.justify-content-between.mb-3').append(`
                                <button id="btnVerTodosRiscos" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalTodosRiscos">
                                    <i class="fas fa-list"></i> Ver Todos (${totalRegistros})
                                </button>
                            `);
                        } else {
                            // Se já existe, apenas atualizar o texto e garantir que está visível
                            btnVerTodos.html(`<i class="fas fa-list"></i> Ver Todos (${totalRegistros})`);
                            btnVerTodos.show();
                        }
                    } else {
                        // Se tem 3 ou menos registros, esconder o botão
                        btnVerTodos.hide();
                    }

                    // Atualizar tabela do modal se estiver aberto
                    const tbodyModal = $('#modalTodosRiscos .table tbody');
                    if (tbodyModal.length) {
                        tbodyModal.empty();
                        response.riscos.forEach(function(risco) {
                            tbodyModal.append(`
                                <tr>
                                    <td>${risco.data_formatada}</td>
                                    <td>${risco.pontuacao}</td>
                                    <td>${risco.probabilidade}</td>
                                    <td>${risco.sexo}</td>
                                    <td>${risco.idade}</td>
                                    <td>${risco.colesterol_total}</td>
                                    <td>${risco.colesterol_hdl}</td>
                                    <td>${risco.pressao_sistolica}</td>
                                    <td>${risco.fumante}</td>
                                    <td>${risco.remedios_hipertensao}</td>
                                    <td>
                                        <?php if (temPermissao()): ?>
                                            <div class="btn-group">
                                                <button onclick='editarRisco(${JSON.stringify(risco).replace(/'/g, "\\'")})' 
                                                    class="btn btn-sm btn-warning me-2">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="excluirRisco(${risco.id})" 
                                                    class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            `);
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao buscar os dados: ' + error
                    });
                }
            });
        }


        <!---------------------------------------------------------------------------->
        <!-- Script para o modal de medicamentos -->

        // Adicionar variável global para permissão
        const userHasPermission = <?php echo json_encode(temPermissao()); ?>;

        function abrirModalMedicamento(pacienteId) {
            $('#formMedicamento')[0].reset();
            $('#medicamento_id').val('');
            $('#modalMedicamentoLabel').text('Novo Medicamento');
            var myModal = new bootstrap.Modal(document.getElementById('modalMedicamento'));
            myModal.show();
        }

        function editarMedicamento(medicamento) {
            $('#medicamento_id').val(medicamento.id);
            $('#nome_medicamento').val(medicamento.nome_medicamento);
            $('#dosagem').val(medicamento.dosagem);
            $('#frequencia').val(medicamento.frequencia);
            $('#data_inicio').val(medicamento.data_inicio);
            $('#data_fim').val(medicamento.data_fim);
            $('#observacoes').val(medicamento.observacoes);
            
            $('#modalMedicamentoLabel').text('Editar Medicamento');
            var myModal = new bootstrap.Modal(document.getElementById('modalMedicamento'));
            myModal.show();
        }

        function excluirMedicamento(medicamentoId) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Esta ação não poderá ser revertida!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'excluir_medicamento.php',
                        type: 'POST',
                        data: { medicamento_id: medicamentoId },
                        dataType: 'json',
                        success: function(response) {
                            console.log('Response:', response);
                            if (response.success) {
                                console.log('Medicamentos:', response.medicamentos);
                                // Atualizar ambas as tabelas
                                atualizarTabelaMedicamentos('principal');
                                if ($('#modalTodosMedicamentos').hasClass('show')) {
                                    atualizarTabelaMedicamentos('todos');
                                }
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Excluído!',
                                    text: 'O medicamento foi excluído com sucesso.',
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro!',
                                    text: response.message || 'Ocorreu um erro ao excluir o medicamento.'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Erro:', xhr.responseText);
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: 'Ocorreu um erro na comunicação com o servidor.'
                            });
                        }
                    });
                }
            });
        }

        $('#formMedicamento').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: 'salvar_medicamento.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    console.log('Response:', response);
                    if (response.success) {
                        // Fechar o modal e limpar o formulário
                        const myModal = bootstrap.Modal.getInstance(document.getElementById('modalMedicamento'));
                        myModal.hide();
                        $('#formMedicamento')[0].reset();
                        
                        // Primeiro mostrar o Swal.fire
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: 'Medicamento salvo com sucesso!',
                            showConfirmButton: false,
                            timer: 1500,
                            didOpen: () => {
                                // Atualizar as tabelas enquanto o Swal está visível
                                Promise.all([
                                    atualizarTabelaMedicamentos('principal'),
                                    $('#modalTodosMedicamentos').hasClass('show') ? 
                                        atualizarTabelaMedicamentos('todos') : 
                                        Promise.resolve()
                                ]).catch(error => {
                                    console.error('Erro ao atualizar tabelas:', error);
                                });
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: response.message || 'Erro ao salvar medicamento'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao processar a requisição: ' + error
                    });
                }
            });
        });

        // Função para atualizar tabelas (modificada para retornar Promise)
        function atualizarTabelaMedicamentos(tipo = 'principal') {
            const paciente_id = $('#p_id').val();
            
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: 'buscar_medicamentos.php',
                    type: 'GET',
                    data: { 
                        paciente_id: paciente_id,
                        limit: tipo === 'principal' ? 3 : null
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Response:', response);
                        if (response.success) {
                            console.log('Medicamentos:', response.medicamentos);
                            const tbody = tipo === 'principal' 
                                ? $('.section-card .data-table tbody')
                                : $('#modalTodosMedicamentos .table tbody');
                            
                            tbody.empty();
                            
                            if (response.medicamentos.length === 0) {
                                const colSpan = userHasPermission ? 7 : 6;
                                tbody.append(`
                                    <tr>
                                        <td colspan="${colSpan}" class="text-center">Nenhum registro encontrado</td>
                                    </tr>
                                `);
                            } else {
                                response.medicamentos.forEach(function(medicamento) {
                                    const linha = `
                                        <tr data-id="${medicamento.id}">
                                            <td>${medicamento.nome_medicamento}</td>
                                            <td>${medicamento.dosagem}</td>
                                            <td>${medicamento.frequencia}</td>
                                            <td>${medicamento.data_inicio_formatada}</td>
                                            <td>${medicamento.data_fim_formatada || 'Em uso'}</td>
                                            <td>${medicamento.observacoes || 'Não informado'}</td>
                                            ${userHasPermission ? `
                                                <td>
                                                    <div class="btn-group">
                                                        <button onclick='editarMedicamento(${JSON.stringify(medicamento)})' class="btn btn-sm btn-warning me-2">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button onclick="excluirMedicamento(${medicamento.id})" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            ` : ''}
                                        </tr>
                                    `;
                                    tbody.append(linha);
                                });
                            }

                            // Atualizar botão "Ver Todos" apenas para tabela principal
                            if (tipo === 'principal') {
                                const btnContainer = $('.d-flex.justify-content-between.mb-3');
                                if (response.total > 3) {
                                    let btnVerTodos = btnContainer.find('.btn-info');
                                    if (btnVerTodos.length) {
                                        btnVerTodos.html(`<i class="fas fa-list"></i> Ver Todos (${response.total})`);
                                    } else {
                                        btnContainer.append(`
                                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalTodosMedicamentos">
                                                <i class="fas fa-list"></i> Ver Todos (${response.total})
                                            </button>
                                        `);
                                    }
                                } else {
                                    btnContainer.find('.btn-info').remove();
                                }
                            }
                            resolve();
                        } else {
                            reject(new Error(response.message));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Erro ao atualizar tabela:', error);
                        reject(error);
                    }
                });
            });
        }

        // Atualizar tabelas quando o modal de todos os medicamentos for aberto
        $('#modalTodosMedicamentos').on('show.bs.modal', function () {
            atualizarTabelaMedicamentos('todos');
        });

        <!---------------------------------------------------------------------------->
        <!-- Script para o modal de exames -->
        function abrirModalExame(pacienteId) {
            console.log('Abrindo modal para paciente:', pacienteId);
            $('#formExame')[0].reset();
            $('#exame_id').val('');
            $('input[name="paciente_id"]').val(pacienteId);
            $('#arquivo_atual').html('');
            $('#modalExameLabel').text('Novo Exame');
            $('#modalExame').modal('show');
        }

        function editarExame(exame) {
            console.log('Editando exame:', exame);
            $('#exame_id').val(exame.id);
            $('#data_exame').val(exame.data_exame);
            $('#tipo_exame').val(exame.tipo_exame);
            $('#resultado').val(exame.resultado);
            
            if (exame.arquivo_exame) {
                $('#arquivo_atual').html(
                    `<p>Arquivo atual: <a href="${exame.arquivo_exame}" target="_blank">Ver arquivo</a></p>`
                );
            } else {
                $('#arquivo_atual').html('');
            }
            
            $('#modalExameLabel').text('Editar Exame');
            $('#modalExame').modal('show');
        }

        function excluirExame(exameId) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Esta ação não poderá ser revertida!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'excluir_exame.php',
                        type: 'POST',
                        data: { exame_id: exameId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Excluído!',
                                    text: 'O exame foi excluído com sucesso.'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro!',
                                    text: response.message || 'Ocorreu um erro ao excluir o exame.'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Erro:', xhr.responseText);
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: 'Ocorreu um erro na comunicação com o servidor.'
                            });
                        }
                    });
                }
            });
        }

        $(document).ready(function() {
            // Manipular o envio do formulário de exame
            $('#formExame').on('submit', function(e) {
                e.preventDefault();
                
                // Debug - mostrar dados antes do envio
                console.log('Dados do formulário:', {
                    paciente_id: $('#formExame input[name="paciente_id"]').val(),
                    exame_id: $('#exame_id').val(),
                    data_exame: $('#data_exame').val(),
                    tipo_exame: $('#tipo_exame').val(),
                    resultado: $('#resultado').val()
                });

                let formData = new FormData(this);

                // Debug - mostrar FormData
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
                
                $.ajax({
                    url: 'salvar_exame.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Resposta do servidor:', response);
                        try {
                            let jsonResponse = (typeof response === 'string') ? JSON.parse(response) : response;
                            
                            if (jsonResponse.success) {
                                // Fechar o modal
                                $('#modalExame').modal('hide');
                                
                                // Mostrar mensagem de sucesso
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sucesso!',
                                    text: 'Exame salvo com sucesso!'
                                }).then((result) => {
                                    // Recarrega a página após fechar o alerta
                                    location.reload();
                                });
                                
                                // Limpar o formulário
                                $('#formExame')[0].reset();
                            } else {
                                throw new Error(jsonResponse.message || 'Erro ao salvar o exame');
                            }
                        } catch (error) {
                            console.error('Erro ao processar resposta:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro',
                                text: error.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Erro na requisição:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: 'Erro ao processar a requisição: ' + error
                        });
                    }
                });
            });
        });

        <!---------------------------------------------------------------------------->
        <!-- Script para o modal de analises -->
        function excluirAnalise(analiseId) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Esta ação não poderá ser revertida!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'excluir_analise.php',
                        type: 'POST',
                        data: { analise_id: analiseId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Excluído!',
                                    text: 'A análise foi excluída com sucesso.'
                                }).then(() => {
                                    // Atualizar tanto a tabela principal quanto o modal
                                    if ($('#modalTodasAnalises').hasClass('show')) {
                                        $('#modalTodasAnalises').modal('hide');
                                    }
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro!',
                                    text: response.message || 'Ocorreu um erro ao excluir a análise.'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Erro:', xhr.responseText);
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: 'Ocorreu um erro na comunicação com o servidor.'
                            });
                        }
                    });
                }
            });
        }

    </script>

</body>
</html>