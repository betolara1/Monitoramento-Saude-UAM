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

        <!-- Seção de Riscos Cardiovasculares -->
        <div class="section-card">
            <h2 class="section-header">Riscos Cardiovasculares</h2>
            
            <div class="d-flex justify-content-between mb-3">
                <?php if (temPermissao()): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalRiscoCardiovascular">
                        <i class="fas fa-plus"></i> Adicionar
                    </button>
                <?php endif; ?>
                
                <?php
                // Contar total de registros de riscos
                $query_total_riscos = "SELECT COUNT(*) as total FROM riscos_saude WHERE paciente_id = ?";
                $stmt_total = $conn->prepare($query_total_riscos);
                $stmt_total->bind_param("i", $paciente_id);
                $stmt_total->execute();
                $total_riscos = $stmt_total->get_result()->fetch_assoc()['total'];
                
                if ($total_riscos > 3): ?>
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalTodosRiscos">
                        <i class="fas fa-list"></i> Ver Todos (<?php echo $total_riscos; ?>)
                    </button>
                <?php endif; ?>
            </div>

            <!-- Tabela com os 3 últimos registros -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Pontuação</th>
                        <th>Probabilidade</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Buscar os últimos 3 registros de riscos
                    $query_riscos = "SELECT * FROM riscos_saude WHERE paciente_id = ? ORDER BY data_calculo DESC LIMIT 3";
                    $stmt_riscos = $conn->prepare($query_riscos);
                    $stmt_riscos->bind_param("i", $paciente_id);
                    $stmt_riscos->execute();
                    $result_riscos = $stmt_riscos->get_result();
                    
                    while ($risco = $result_riscos->fetch_assoc()): ?>
                        <tr data-id="<?php echo $risco['id']; ?>">
                            <td><?php echo date('d/m/Y', strtotime($risco['data_calculo'])); ?></td>
                            <td><?php echo $risco['pontuacao']; ?></td>
                            <td><?php echo $risco['probabilidade']; ?>%</td>
                            <td>
                                <?php if (temPermissao()): ?>
                                    <div class="btn-group">
                                        <button onclick='editarRisco(<?php echo json_encode($risco); ?>)' class="btn btn-sm btn-warning me-2">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button onclick="excluirRisco(<?php echo $risco['id']; ?>)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal para todos os riscos -->
        <div class="modal fade" id="modalTodosRiscos" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-heart"></i> Histórico Completo de Riscos Cardiovasculares
                        </h5>
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
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Preenchido via JavaScript -->
                                </tbody>
                            </table>
                        </div>
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
    </div>

    <script>
        // Função auxiliar para converter valores em números
        function getNumericValue(value) {
            const num = parseFloat(value);
            return isNaN(num) ? 0 : num;
        }

        // Função para calcular a probabilidade baseada na pontuação e sexo
        function calcularProbabilidade(pontuacao, sexo) {
            const TABELA_PROBABILIDADE = {
                'Homem': [
                    { max: -1, valor: '<1' },
                    { min: 0, max: 4, valor: '1' },
                    { min: 5, max: 6, valor: '2' },
                    { pontos: 7, valor: '3' },
                    { pontos: 8, valor: '4' },
                    { pontos: 9, valor: '5' },
                    { pontos: 10, valor: '6' },
                    { pontos: 11, valor: '8' },
                    { pontos: 12, valor: '10' },
                    { pontos: 13, valor: '12' },
                    { pontos: 14, valor: '16' },
                    { pontos: 15, valor: '20' },
                    { pontos: 16, valor: '25' },
                    { min: 17, valor: '≥30' }
                ],
                'Mulher': [
                    { max: 8, valor: '<1' },
                    { min: 9, max: 12, valor: '1' },
                    { min: 13, max: 14, valor: '2' },
                    { pontos: 15, valor: '3' },
                    { pontos: 16, valor: '4' },
                    { pontos: 17, valor: '5' },
                    { pontos: 18, valor: '6' },
                    { pontos: 19, valor: '8' },
                    { pontos: 20, valor: '11' },
                    { pontos: 21, valor: '14' },
                    { pontos: 22, valor: '17' },
                    { pontos: 23, valor: '22' },
                    { pontos: 24, valor: '27' },
                    { min: 25, valor: '≥30' }
                ]
            };

            const tabela = TABELA_PROBABILIDADE[sexo];
            
            for (const entrada of tabela) {
                // Caso específico de um único ponto
                if (entrada.pontos !== undefined && entrada.pontos === pontuacao) {
                    return entrada.valor;
                }
                // Caso de intervalo com mínimo e máximo
                if (entrada.min !== undefined && entrada.max !== undefined) {
                    if (pontuacao >= entrada.min && pontuacao <= entrada.max) {
                        return entrada.valor;
                    }
                }
                // Caso de apenas valor máximo
                if (entrada.max !== undefined && entrada.min === undefined) {
                    if (pontuacao <= entrada.max) {
                        return entrada.valor;
                    }
                }
                // Caso de apenas valor mínimo (último caso)
                if (entrada.min !== undefined && entrada.max === undefined) {
                    if (pontuacao >= entrada.min) {
                        return entrada.valor;
                    }
                }
            }
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
                const probabilidade = calcularProbabilidade(pontuacao, sexo);

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

            // Log para debug
            console.log('Dados sendo enviados:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            $.ajax({
                url: 'salvar_risco_cardiovascular.php',
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
                            $('#modalRiscoCardiovascular').modal('hide');
                            
                            // Atualizar ambas as tabelas
                            atualizarTodasTabelas();
                            
                            // Se o modal de todos os riscos estiver aberto, atualizar também
                            if ($('#modalTodosRiscos').is(':visible')) {
                                atualizarTodasTabelas();
                            }
                            
                            // Limpar o formulário e esconder resultados
                            $('#formRiscoCardiovascular')[0].reset();
                            $('#resultadosCalculo').hide();
                            $('#btnSalvar').hide();
                            
                            // Mostrar mensagem de sucesso
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 1500
                            });
                        } else {
                            throw new Error(jsonResponse.message || 'Erro ao salvar os dados');
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

        // Adicione este evento quando o documento estiver pronto
        $(document).ready(function() {
            // Garantir que o botão de salvar está escondido inicialmente
            document.getElementById('btnSalvar').style.display = 'none';
            
            // Atualizar a seção de riscos quando a página carregar
            atualizarTodasTabelas();
        });

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

        function excluirRisco(id) {
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
                        url: 'excluir_risco.php',
                        type: 'POST',
                        data: { id: id },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                atualizarTodasTabelas();
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Excluído!',
                                    text: 'Registro excluído com sucesso.',
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro!',
                                    text: response.message || 'Erro ao excluir o registro'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Erro na requisição:', error);
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

        // Função unificada para atualizar tabelas
        function atualizarTabela(tipo = 'principal') {
            const paciente_id = <?php echo $paciente_id; ?>;
            const limit = tipo === 'principal' ? 3 : null;
            
            return $.ajax({
                url: 'buscar_riscos.php',
                type: 'GET',
                data: { 
                    paciente_id: paciente_id,
                    limit: limit 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Selecionar o tbody correto baseado no tipo
                        const tbody = tipo === 'principal' 
                            ? $('.section-card .data-table tbody')  // Atualizado para selecionar a tabela correta
                            : $('#modalTodosRiscos .table tbody');
                        
                        tbody.empty();
                        
                        if (response.riscos.length === 0) {
                            tbody.append(`
                                <tr>
                                    <td colspan="4" class="text-center">Nenhum registro encontrado</td>
                                </tr>
                            `);
                        } else {
                            // Adicionar os novos registros
                            response.riscos.forEach(function(risco) {
                                const linha = `
                                    <tr data-id="${risco.id}">
                                        <td>${risco.data_formatada}</td>
                                        <td>${risco.pontuacao}</td>
                                        <td>${risco.probabilidade}${risco.probabilidade.startsWith('<') || risco.probabilidade.startsWith('≥') ? '' : '%'}</td>
                                        <td>
                                            ${temPermissao() ? `
                                                <div class="btn-group">
                                                    <button onclick='editarRisco(${JSON.stringify(risco)})' class="btn btn-sm btn-warning me-2">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </button>
                                                    <button onclick="excluirRisco(${risco.id})" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> Excluir
                                                    </button>
                                                </div>
                                            ` : ''}
                                        </td>
                                    </tr>
                                `;
                                tbody.append(linha);
                            });
                        }
                        
                        // Atualizar botão "Ver Todos" apenas para tabela principal
                        if (tipo === 'principal') {
                            const btnContainer = $('.d-flex.justify-content-between.mb-3');
                            const btnVerTodos = btnContainer.find('.btn-info');
                            
                            if (response.total > 3) {
                                if (btnVerTodos.length) {
                                    btnVerTodos.html(`<i class="fas fa-list"></i> Ver Todos (${response.total})`);
                                } else {
                                    btnContainer.append(`
                                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalTodosRiscos">
                                            <i class="fas fa-list"></i> Ver Todos (${response.total})
                                        </button>
                                    `);
                                }
                            } else {
                                btnVerTodos.remove();
                            }
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro ao atualizar tabela:', error);
                }
            });
        }

        // Função para atualizar todas as tabelas necessárias
        function atualizarTodasTabelas() {
            atualizarTabela('principal');
            if ($('#modalTodosRiscos').hasClass('show')) {
                atualizarTabela('todos');
            }
        }

        // Função para atualizar o modal de edição
        function editarRiscoCardiovascular(id) {
            // Mostrar loading
            Swal.fire({
                title: 'Carregando...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Buscar dados do risco cardiovascular
            fetch(`buscar_riscos.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro ao buscar dados');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Preencher o formulário com os dados
                        const risco = data.risco;
                        document.getElementById('editar_risco_id').value = risco.id;
                        document.getElementById('editar_sexo').value = risco.sexo;
                        document.getElementById('editar_idade').value = risco.idade;
                        document.getElementById('editar_colesterol_total').value = risco.colesterol_total;
                        document.getElementById('editar_colesterol_hdl').value = risco.colesterol_hdl;
                        document.getElementById('editar_pressao_sistolica').value = risco.pressao_sistolica;
                        document.getElementById('editar_fumante').value = risco.fumante;
                        document.getElementById('editar_remedios_hipertensao').value = risco.remedios_hipertensao;
                        document.getElementById('editar_pontuacao').value = risco.pontuacao;
                        document.getElementById('editar_probabilidade').value = risco.probabilidade;
                        
                        // Fechar o loading e abrir o modal
                        Swal.close();
                        new bootstrap.Modal(document.getElementById('modalEditarRiscoCardiovascular')).show();
                    } else {
                        throw new Error(data.message || 'Erro ao carregar os dados');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao carregar os dados do risco cardiovascular'
                    });
                });
        }

        // Função para recalcular o risco
        function recalcularRisco() {
            // Pegar os valores do formulário de edição
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
            
            // Calcular a probabilidade baseada na pontuação
            let probabilidade;
            if (formData.get('sexo') === 'Homem') {
                if (pontuacao <= 4) probabilidade = "<1";
                else if (pontuacao === 5) probabilidade = 1;
                else if (pontuacao === 6) probabilidade = 2;
                else if (pontuacao === 7) probabilidade = 3;
                else if (pontuacao === 8) probabilidade = 4;
                else if (pontuacao === 9) probabilidade = 5;
                else if (pontuacao === 10) probabilidade = 6;
                else if (pontuacao === 11) probabilidade = 8;
                else if (pontuacao === 12) probabilidade = 10;
                else if (pontuacao === 13) probabilidade = 12;
                else if (pontuacao === 14) probabilidade = 16;
                else if (pontuacao === 15) probabilidade = 20;
                else if (pontuacao === 16) probabilidade = 25;
                else probabilidade = ">30";
            } else {
                if (pontuacao <= 12) probabilidade = "<1";
                else if (pontuacao === 13) probabilidade = 1;
                else if (pontuacao === 14) probabilidade = 2;
                else if (pontuacao === 15) probabilidade = 3;
                else if (pontuacao === 16) probabilidade = 4;
                else if (pontuacao === 17) probabilidade = 5;
                else if (pontuacao === 18) probabilidade = 6;
                else if (pontuacao === 19) probabilidade = 8;
                else if (pontuacao === 20) probabilidade = 11;
                else if (pontuacao === 21) probabilidade = 14;
                else if (pontuacao === 22) probabilidade = 17;
                else if (pontuacao === 23) probabilidade = 22;
                else if (pontuacao === 24) probabilidade = 27;
                else probabilidade = ">30";
            }

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

        // Quando o formulário for enviado
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
                        
                        // Atualizar a tabela principal
                        atualizarTodasTabelas();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: 'Risco cardiovascular atualizado com sucesso!'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: response.message || 'Erro ao atualizar o registro'
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
        });

    </script>

</body>
</html>