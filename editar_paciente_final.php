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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
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


        <!---------------------------------------------------------------------------->
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

    <script>
        <!-- Script para o modal de acompanhamento -->
        $(document).ready(function() {
            // Consolidar todos os handlers document.ready em um único bloco
            initializeForms();
            initializeMasks();
        });

        function initializeForms() {
            // Handler para o formulário de novo acompanhamento
            $('#formAcompanhamento').on('submit', handleFormSubmit);
            
            // Handler para o formulário de edição
            $('#formEditarAcompanhamento').on('submit', handleEditFormSubmit);
        }

        function handleFormSubmit(event) {
            event.preventDefault();
            submitAjaxForm($(this), 'salvar_acompanhamento.php', {
                onSuccess: function(response) {
                    adicionarLinhaTabela(response.dados_acompanhamento);
                    $('#modalAcompanhamento').modal('hide');
                    $('#formAcompanhamento')[0].reset();
                }
            });
        }

        function handleEditFormSubmit(event) {
            event.preventDefault();
            submitAjaxForm($(this), 'atualizar_acompanhamento.php', {
                onSuccess: function(response) {
                    atualizarLinhaTabela(response.dados_acompanhamento);
                    $('#modalEditarAcompanhamento').modal('hide');
                }
            });
        }

        // Função genérica para submissão de formulários via AJAX
        function submitAjaxForm($form, url, options = {}) {
            $.ajax({
                type: 'POST',
                url: url,
                data: $form.serialize(),
                dataType: 'json',
                success: function(response) {
                    try {
                        if (response.success) {
                            if (options.onSuccess) options.onSuccess(response);
                            showSuccessMessage(response.message);
                        } else {
                            throw new Error(response.message || 'Erro ao processar operação');
                        }
                    } catch (error) {
                        showErrorMessage(error.message);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorMessage('Erro ao processar a requisição. Tente novamente.');
                }
            });
        }

        // Funções auxiliares para mensagens
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

        function initializeMasks() {
            // Máscara para glicemia
            $('.glicemia').mask('000', {
                reverse: true,
                onChange: function(value, e) {
                    var numValue = parseInt(value);
                    if (numValue > 999) {
                        $(e.target).val('999');
                    } else if (numValue < 0 && value !== '') {
                        $(e.target).val('0');
                    }
                }
            });

            // Máscara para pressão arterial
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

        // Função para adicionar uma nova linha na tabela de acompanhamento
        function adicionarLinhaTabela(acompanhamento) {
            // Criar a nova linha
            var novaLinha = `
                <tr data-id="${acompanhamento.id}">
                    <td>${acompanhamento.data_acompanhamento}</td>
                    <td>${acompanhamento.glicemia || 'Não informado'}</td>
                    <td>${acompanhamento.hipertensao || 'Não informado'}</td>
                    <td>${acompanhamento.observacoes || 'Não informado'}</td>
                    <td>
                        <div class="btn-group">
                            <a href="#" onclick="editarAcompanhamento(${JSON.stringify(acompanhamento)})" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        <a href="#" onclick="excluirAcompanhamento(${acompanhamento.id})" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i> Excluir
                            </a>
                        </div>
                    </td>
                </tr>
            `;

            // Adicionar a nova linha no início da tabela
            $('.data-table tbody').prepend(novaLinha);
            
            // Remover linhas extras para manter apenas 3 registros visíveis
            var linhas = $('.data-table tbody tr');
            if (linhas.length > 3) {
                linhas.slice(3).remove();
                
                // Atualizar o botão "Ver Todos"
                const btnContainer = $('.d-flex.justify-content-between.mb-3');
                let btnVerTodos = btnContainer.find('.btn-info');
                
                // Calcular novo total baseado no total atual + 1
                const totalAtual = $('#modalTodosAcompanhamentos .table tbody tr').length;
                const novoTotal = totalAtual + 1;
                
                // Adicionar a nova linha na tabela do modal também
                $('#modalTodosAcompanhamentos .table tbody').prepend(novaLinha);
                
                if (!btnVerTodos.length) {
                    btnContainer.append(`
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalTodosAcompanhamentos">
                            <i class="fas fa-list"></i> Ver Todos (${novoTotal})
                        </button>
                    `);
                } else {
                    btnVerTodos.html(`<i class="fas fa-list"></i> Ver Todos (${novoTotal})`);
                }
            }
        }

        function atualizarTabelaAcompanhamentosModal() {
            const paciente_id = <?php echo $paciente_id; ?>;
            
            $.ajax({
                url: 'buscar_acompanhamentos.php',
                type: 'GET',
                data: { 
                    paciente_id: paciente_id,
                    get_all: 'true'
                },
                dataType: 'json',
                success: function(response) {
                    // Limpar a tabela do modal
                    $('#modalTodosAcompanhamentos .table tbody').empty();
                    
                    // Adicionar os novos registros no modal
                    response.registros.forEach(function(acompanhamento) {
                        var linha = `
                            <tr data-id="${acompanhamento.id}">
                                <td>${acompanhamento.data_formatada}</td>
                                <td>${acompanhamento.glicemia || 'Não informado'}</td>
                                <td>${acompanhamento.hipertensao || 'Não informado'}</td>
                                <td>${acompanhamento.observacoes || 'Não informado'}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="#" onclick="editarAcompanhamento(${JSON.stringify(acompanhamento)})" class="btn btn-sm btn-warning me-2">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <a href="#" onclick="excluirAcompanhamento(${acompanhamento.id})" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Excluir
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        `;
                        $('#modalTodosAcompanhamentos .table tbody').append(linha);
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Erro ao atualizar tabela do modal:', error);
                }
            });
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
                                // Atualiza ambas as tabelas
                                atualizarTabelaAcompanhamentos();
                                atualizarTabelaAcompanhamentosModal();
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Excluído!',
                                    text: 'O acompanhamento foi excluído com sucesso.',
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro!',
                                    text: response.message || 'Erro ao excluir o acompanhamento.'
                                });
                            }
                        },
                        error: function(xhr) {
                            console.error('Erro:', xhr.responseText);
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: 'Erro na comunicação com o servidor.'
                            });
                        }
                    });
                }
            });
        }

        // Função do editar acompanhamento
        function editarAcompanhamento(acompanhamento) {
            // Preencher os campos do modal de edição com os dados do acompanhamento
            $('#edit_acompanhamento_id').val(acompanhamento.id);
            $('#edit_data_acompanhamento').val(acompanhamento.data_acompanhamento);
            $('#edit_glicemia').val(acompanhamento.glicemia);
            $('#edit_hipertensao').val(acompanhamento.hipertensao);
            $('#edit_observacoes').val(acompanhamento.observacoes);

            // Abre o modal
            var myModal = new bootstrap.Modal(document.getElementById('modalEditarAcompanhamento'));
            myModal.show();
        }

        // Função para atualizar a linha na tabela de acompanhamento
        function atualizarLinhaTabela(acompanhamento) {
            var linha = $('tr[data-id="' + acompanhamento.id + '"]');
            linha.find('td:eq(0)').text(acompanhamento.data_acompanhamento);
            linha.find('td:eq(1)').text(acompanhamento.glicemia);
            linha.find('td:eq(2)').text(acompanhamento.hipertensao);
            linha.find('td:eq(3)').text(acompanhamento.observacoes);
        }

        function atualizarTabelaAcompanhamentos() {
            const paciente_id = <?php echo $paciente_id; ?>;
            
            $.ajax({
                url: 'buscar_acompanhamentos.php',
                type: 'GET',
                data: { 
                    paciente_id: paciente_id,
                    get_total: true // Adicionar parâmetro para buscar total de registros
                },
                dataType: 'json',
                success: function(response) {
                    // Limpar a tabela atual
                    $('.data-table tbody').empty();
                    
                    // Adicionar os novos registros
                    response.registros.forEach(function(acompanhamento) {
                        var linha = `
                            <tr data-id="${acompanhamento.id}">
                                <td>${acompanhamento.data_formatada}</td>
                                <td>${acompanhamento.glicemia || 'Não informado'}</td>
                                <td>${acompanhamento.hipertensao || 'Não informado'}</td>
                                <td>${acompanhamento.observacoes || 'Não informado'}</td>
                                <td>
                                    <a href="#" onclick="editarAcompanhamento(${JSON.stringify(acompanhamento)})" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="#" onclick="excluirAcompanhamento(${acompanhamento.id})" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Excluir
                                    </a>
                                </td>
                            </tr>
                        `;
                        $('.data-table tbody').append(linha);
                    });
                    
                    // Atualizar o botão "Ver Todos"
                    const btnContainer = $('.d-flex.justify-content-between.mb-3');
                    if (response.total > 3) {
                        // Se já existe o botão, apenas atualiza o texto
                        let btnVerTodos = btnContainer.find('.btn-info');
                        if (btnVerTodos.length) {
                            btnVerTodos.html(`<i class="fas fa-list"></i> Ver Todos (${response.total})`);
                        } else {
                            // Se não existe, cria o botão
                            btnContainer.append(`
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalTodosAcompanhamentos">
                                    <i class="fas fa-list"></i> Ver Todos (${response.total})
                                </button>
                            `);
                        }
                    } else {
                        // Remove o botão se total <= 3
                        btnContainer.find('.btn-info').remove();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro ao atualizar tabela:', error);
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

        

    </script>

</body>
</html>