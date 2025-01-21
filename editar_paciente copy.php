<?php
include "conexao.php";
include 'verificar_login.php';
include "sidebar.php";

$paciente_id = $_GET['id'];

$sql = "SELECT 
            p.*,
            u.nome as nome_paciente,
            u.tipo_usuario,
            GROUP_CONCAT(
                DISTINCT CONCAT(
                    up.nome, '|',
                    COALESCE(prof.especialidade, 'Não informado'), '|',
                    COALESCE(prof.unidade_saude, 'Não informado'), '|',
                    pp.tipo_profissional
                ) SEPARATOR ';;'
            ) as profissionais_info
        FROM pacientes p
        INNER JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN paciente_profissional pp ON p.id = pp.paciente_id
        LEFT JOIN profissionais prof ON pp.profissional_id = prof.id
        LEFT JOIN usuarios up ON prof.usuario_id = up.id
        WHERE p.id = ?
        GROUP BY p.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $paciente_id);
$stmt->execute();
$result = $stmt->get_result();
$paciente = $result->fetch_assoc();

// Verifica se o paciente existe
if (!$paciente) {
    echo "Paciente não encontrado";
    exit;
}

// Processa as informações dos profissionais com verificação de dados
$profissionais = [];
if (!empty($paciente['profissionais_info'])) {
    $profissionais_array = explode(';;', $paciente['profissionais_info']);
    foreach ($profissionais_array as $prof_info) {
        if (!empty($prof_info)) {
            $info = explode('|', $prof_info);
            if (count($info) === 4) { // Verifica se tem todos os elementos necessários
                list($nome, $especialidade, $unidade_saude, $tipo) = $info;
                $profissionais[$tipo] = [
                    'nome' => $nome ?: 'Não informado',
                    'especialidade' => $especialidade ?: 'Não informado',
                    'unidade_saude' => $unidade_saude ?: 'Não informado'
                ];
            }
        }
    }
}

// Debug para verificar os dados (remova em produção)
// echo "<pre>"; print_r($profissionais); echo "</pre>";

// Adicionar verificação após buscar os dados
if (!$paciente) {
    echo "<div class='alert alert-danger'>Paciente não encontrado.</div>";
    exit();
}

// Função para verificar permissões
function temPermissao() {
    return isset($_SESSION['tipo_usuario']) && 
           ($_SESSION['tipo_usuario'] === 'Admin' || 
           $_SESSION['tipo_usuario'] === 'Medico' || 
           $_SESSION['tipo_usuario'] === 'Enfermeiro'
    );
}

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
            margin: 20px 0;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background-color: #f5f5f5;
        }

        /* Status badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-cadastrado {
            background-color: #28a745;
            color: white;
        }

        .status-pendente {
            background-color: #ffc107;
            color: #000;
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

        /* Corpo do Modal */
        .modal-body {
            padding: 25px;
            max-height: 60vh;
            overflow-y: auto;
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
        /* Estilo para mostrar a classificação do IMC no hover */
        #imc[title]:hover:after {
            content: attr(title);
            position: absolute;
            background: #333;
            color: white;
            padding: 5px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 10px;
        }

        .modal-lg {
            max-width: 800px;
        }

        .form-text {
            font-size: 0.875em;
            margin-top: 0.25rem;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .modal-body {
            max-height: calc(100vh - 210px);
            overflow-y: auto;
        }

        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .text-truncate:hover {
            overflow: visible;
            white-space: normal;
            background-color: #fff;
            position: relative;
            z-index: 1;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 4px;
            padding: 5px;
        }
        
        .data-table td {
            max-width: 200px;
            vertical-align: middle;
        }
        
        .btn-group {
            display: flex;
            gap: 5px;
        }

        .section-actions {
            position: relative;
        }
        
        .tooltip-text {
            display: none;
            position: absolute;
            background: #333;
            color: white;
            padding: 5px;
            border-radius: 3px;
            font-size: 12px;
            bottom: -30px;
            left: 0;
            white-space: nowrap;
        }
        
        .btn[disabled]:hover .tooltip-text {
            display: block;
        }
        
        .btn-group {
            display: flex;
            gap: 5px;
        }
        
        .data-table td {
            vertical-align: middle;
        }

    </style>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- jQuery (se necessário) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>
<body>
    <div class="container">
        <!-- Header com nome do paciente e botão voltar -->
        <div class="header-container">
            <h1>Paciente <?php echo htmlspecialchars($paciente['nome_paciente']); ?></h1>
        </div>
        <input type="hidden" id="p_id" value="<?php echo $paciente_id; ?>">

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
                                   class="btn btn-primary">Cadastrar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Seção de Profissionais de Saúde -->
        <div class="section-card">
            <h2 class="section-header">Profissionais de Saúde</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Nome do Profissional</th>
                        <th>Especialidade</th>
                        <th>Unidade de Saúde</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (['Medico', 'Enfermeiro'] as $tipo): ?>
                    <tr>
                        <td><?php echo $tipo === 'Medico' ? 'Médico' : 'Enfermeiro'; ?></td>
                        <td>
                            <?php if (isset($profissionais[$tipo])): ?>
                                <span class="status-badge status-cadastrado">Atribuído</span>
                            <?php else: ?>
                                <span class="status-badge status-pendente">Não Atribuído</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo isset($profissionais[$tipo]) ? 
                                  htmlspecialchars($profissionais[$tipo]['nome']) : 
                                  'Não atribuído'; ?>
                        </td>
                        <td>
                            <?php echo isset($profissionais[$tipo]) ? 
                                  htmlspecialchars($profissionais[$tipo]['especialidade']) : 
                                  'Não informado'; ?>
                        </td>
                        <td>
                            <?php echo isset($profissionais[$tipo]) ? 
                                  htmlspecialchars($profissionais[$tipo]['unidade_saude']) : 
                                  'Não informado'; ?>
                        </td>
                        <td>
                            <?php if (temPermissao()): ?>
                                <?php if (isset($profissionais[$tipo])): ?>
                                    <button onclick="abrirModalMedico(<?php echo $paciente_id; ?>, '<?php echo $tipo; ?>')" 
                                            class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php else: ?>
                                    <button onclick="abrirModalAtribuirMedico(<?php echo $paciente_id; ?>, '<?php echo $tipo; ?>')" 
                                            class="btn btn-primary"
                                    <?php echo empty($paciente['tipo_doenca']) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-plus"></i> Atribuir
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Seção de Histórico Clínico -->
        <div class="section-card">
            <h2 class="section-header">Histórico Clínico do Paciente</h2>
            <?php if (temPermissao()): ?>
                <div class="section-actions">
                    <button onclick="abrirModalConsulta(<?php echo $paciente_id; ?>)" 
                            class="btn btn-primary"><i class="fas fa-plus"></i>
                    </button>
                </div>
            <?php endif; ?>
            
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
                        <th>Hábitos de Vida</th>
                        <th>Estado Emocional</th>
                        <th>Observações</th>
                        <?php if (temPermissao()): ?>
                            <th>Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
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

                    while ($registro = mysqli_fetch_assoc($result)):
                    ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($registro['data_consulta'])); ?></td>
                            <td>
                                <?php 
                                    echo htmlspecialchars($registro['nome_profissional']);
                                    if (!empty($registro['especialidade'])) {
                                        echo " (" . htmlspecialchars($registro['especialidade']) . ")";
                                    }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($registro['pressao_arterial']); ?></td>
                            <td><?php echo htmlspecialchars($registro['glicemia']); ?></td>
                            <td><?php echo $registro['peso'] ? number_format($registro['peso'], 2) . ' kg' : '-'; ?></td>
                            <td><?php echo $registro['altura'] ? number_format($registro['altura'], 1) . ' cm' : '-'; ?></td>
                            <td><?php echo $registro['imc'] ? number_format($registro['imc'], 1) : '-'; ?></td>
                            <td>
                                <?php 
                                    if (!empty($registro['habitos_vida'])) {
                                        echo '<span class="text-truncate d-inline-block" style="max-width: 150px;" title="' . 
                                             htmlspecialchars($registro['habitos_vida']) . '">' . 
                                             nl2br(htmlspecialchars($registro['habitos_vida'])) . 
                                             '</span>';
                                    } else {
                                        echo '-';
                                    }
                                ?>
                            </td>
                            <td>
                                <?php 
                                    echo $registro['estado_emocional'] ? 
                                        htmlspecialchars($registro['estado_emocional']) : 
                                        '-'; 
                                ?>
                            </td>
                            <td>
                                <?php 
                                    if (!empty($registro['observacoes'])) {
                                        echo '<span class="text-truncate d-inline-block" style="max-width: 150px;" title="' . 
                                             htmlspecialchars($registro['observacoes']) . '">' . 
                                             nl2br(htmlspecialchars($registro['observacoes'])) . 
                                             '</span>';
                                    } else {
                                        echo '-';
                                    }
                                ?>
                            </td>
                            <?php if (temPermissao()): ?>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary" onclick="editarConsulta(<?php echo $registro['id']; ?>)">Editar</button>
                                        <button onclick="excluirConsulta(<?php echo $registro['id']; ?>)" 
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

                <!-- Modal Nova Consulta -->
                <div class="modal fade" id="modalConsulta" tabindex="-1" aria-labelledby="modalConsultaLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalConsultaLabel">Nova Consulta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formConsulta" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="consulta_id" id="consulta_id">
                            <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="profissional" class="form-label">Profissional*:</label>
                                    <select class="form-select" id="profissional" name="profissional" required>
                                        <option value="">Selecione...</option>
                                        <?php
                                        // Consulta para obter os profissionais
                                        $sql = "SELECT id, nome, tipo_usuario FROM usuarios WHERE tipo_usuario IN ('Medico', 'Enfermeiro')";
                                        $result = $conn->query($sql);

                                        // Verifica se há resultados e preenche o select
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='" . $row['id'] . "'>" . $row['nome'] . " (" . $row['tipo_usuario'] . ")</option>";
                                            }
                                        } else {
                                            echo "<option value=''>Nenhum profissional encontrado</option>";
                                        }

                                        // Fecha a conexão
                                        $conn->close();
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="data_consulta" class="form-label">Data da Consulta*:</label>
                                    <input type="date" class="form-control" id="data_consulta" name="data_consulta" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="pressao_arterial" class="form-label">Pressão Arterial:</label>
                                    <input type="text" class="form-control" id="pressao_arterial" name="pressao_arterial" required
                                           placeholder="Ex: 120/80" maxlength="7">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="glicemia" class="form-label">Glicemia (mg/dL):</label>
                                    <input type="text" class="form-control" id="glicemia" name="glicemia" required
                                           placeholder="Ex: 100">
                                </div>
                                <div class="col-md-3">
                                    <label for="peso" class="form-label">Peso (kg):</label>
                                    <input type="text" class="form-control" id="peso" name="peso" required
                                           placeholder="Ex: 70.5">
                                </div>
                                <div class="col-md-3">
                                    <label for="altura" class="form-label">Altura (cm):</label>
                                    <input type="text" class="form-control" id="altura" name="altura" required
                                           placeholder="Ex: 170">
                                </div>
                                <div class="col-md-3">
                                    <label for="imc" class="form-label">IMC:</label>
                                    <input type="text" class="form-control" id="imc" name="imc" readonly>
                                    <small class="form-text text-muted" id="imc_classificacao"></small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
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
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="habitos_vida" class="form-label">Hábitos de Vida:</label>
                                    <textarea class="form-control" id="habitos_vida" name="habitos_vida" rows="3"
                                              placeholder="Descreva os hábitos de vida do paciente (alimentação, exercícios, sono, etc)"></textarea>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="observacoes" class="form-label">Observações:</label>
                                    <textarea class="form-control" id="observacoes" name="observacoes" rows="3"
                                              placeholder="Observações adicionais sobre a consulta"></textarea>
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

        <!-- Modal Editar Consulta -->
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

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="profissional" class="form-label">Profissional*:</label>
                                    <select class="form-select" id="profissional" name="profissional" required>
                                        <option value="">Selecione...</option>
                                        <?php
                                        // Conexão com o banco de dados
                                        $conn = new mysqli('localhost', 'usuario', 'senha', 'nome_do_banco');

                                        // Verifica a conexão
                                        if ($conn->connect_error) {
                                            die("Conexão falhou: " . $conn->connect_error);
                                        }

                                        // Consulta para obter os profissionais
                                        $sql = "SELECT id, nome FROM profissionais";
                                        $result = $conn->query($sql);

                                        // Preenche o select com os profissionais
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='" . $row['id'] . "'>" . $row['nome'] . "</option>";
                                            }
                                        } else {
                                            echo "<option value=''>Nenhum profissional encontrado</option>";
                                        }

                                        // Fecha a conexão
                                        $conn->close();
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="data_consulta" class="form-label">Data da Consulta*:</label>
                                    <input type="date" class="form-control" id="edit_data_consulta" name="data_consulta" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="pressao_arterial" class="form-label">Pressão Arterial:</label>
                                    <input type="text" class="form-control" id="edit_pressao_arterial" name="pressao_arterial" 
                                           placeholder="Ex: 120/80" maxlength="7">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="glicemia" class="form-label">Glicemia (mg/dL):</label>
                                    <input type="text" class="form-control" id="edit_glicemia" name="glicemia" 
                                           placeholder="Ex: 100">
                                </div>
                                <div class="col-md-3">
                                    <label for="peso" class="form-label">Peso (kg):</label>
                                    <input type="text" class="form-control" id="edit_peso" name="peso" 
                                           placeholder="Ex: 70.5">
                                </div>
                                <div class="col-md-3">
                                    <label for="altura" class="form-label">Altura (cm):</label>
                                    <input type="text" class="form-control" id="edit_altura" name="altura" 
                                           placeholder="Ex: 170">
                                </div>
                                <div class="col-md-3">
                                    <label for="imc" class="form-label">IMC:</label>
                                    <input type="text" class="form-control" id="edit_imc" name="imc" readonly>
                                    <small class="form-text text-muted" id="edit_imc_classificacao"></small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="estado_emocional" class="form-label">Estado Emocional:</label>
                                    <select class="form-select" id="edit_estado_emocional" name="estado_emocional">
                                        <option value="">Selecione...</option>
                                        <option value="Calmo">Calmo</option>
                                        <option value="Ansioso">Ansioso</option>
                                        <option value="Deprimido">Deprimido</option>
                                        <option value="Estressado">Estressado</option>
                                        <option value="Irritado">Irritado</option>
                                        <option value="Alegre">Alegre</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="habitos_vida" class="form-label">Hábitos de Vida:</label>
                                    <textarea class="form-control" id="edit_habitos_vida" name="habitos_vida" rows="3"
                                              placeholder="Descreva os hábitos de vida do paciente (alimentação, exercícios, sono, etc)"></textarea>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="observacoes" class="form-label">Observações:</label>
                                    <textarea class="form-control" id="edit_observacoes" name="observacoes" rows="3"
                                              placeholder="Observações adicionais sobre a consulta"></textarea>
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

        <!-- Seção de Medicamentos -->
        <div class="section-card">
            <h2 class="section-header">Medicamentos</h2>
            <?php if (temPermissao()): ?>
                <div class="section-actions">
                    <button onclick="abrirModalMedicamento(<?php echo $paciente_id; ?>)" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            <?php endif; ?>
            
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
                    $query_med = "SELECT * FROM medicamentos WHERE paciente_id = ? ORDER BY data_inicio DESC";
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

        <!-- Seção de Exames -->
        <div class="section-card">
            <h2 class="section-header">Exames</h2>
            <?php if (temPermissao()): ?>
                <div class="section-actions">
                    <button onclick="abrirModalExame(<?php echo $paciente_id; ?>)" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            <?php endif; ?>
            
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
                    $query_exames = "SELECT * FROM exames WHERE paciente_id = ? ORDER BY data_exame DESC";
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

        <!-- Seção de Análises e Estatísticas -->
        <div class="section-card">
            <h2 class="section-header">Análises e Estatísticas</h2>
            <?php if (temPermissao()): ?>
                <div class="section-actions">
                    <button onclick="abrirModalAnalise(<?php echo $paciente_id; ?>)" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            <?php endif; ?>
            
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
                    $query_analises = "SELECT * FROM analises_estatisticas WHERE paciente_id = ? ORDER BY data_analise DESC";
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
                                    <button onclick='editarAnalise(<?php echo json_encode($analise); ?>)' 
                                            class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </button>
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

        <!-- Modal de Análise -->
        <div class="modal fade" id="modalAnalise" tabindex="-1" aria-labelledby="modalAnaliseLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAnaliseLabel">Nova Análise</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formAnalise" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="analise_id" id="analise_id">
                            <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
                            
                            <div class="mb-3">
                                <label>Data da Análise:</label>
                                <input type="date" name="data_analise" id="data_analise" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label>Comparativo Pressão Arterial:</label>
                                <select name="comparativo_pa" id="comparativo_pa" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <option value="Melhorou">Melhorou</option>
                                    <option value="Estável">Estável</option>
                                    <option value="Piorou">Piorou</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label>Comparativo Glicemia:</label>
                                <select name="comparativo_glicemia" id="comparativo_glicemia" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <option value="Melhorou">Melhorou</option>
                                    <option value="Estável">Estável</option>
                                    <option value="Piorou">Piorou</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label>Comparativo Risco Cardiovascular:</label>
                                <select name="comparativo_risco_cardio" id="comparativo_risco_cardio" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <option value="Baixo">Baixo</option>
                                    <option value="Moderado">Moderado</option>
                                    <option value="Alto">Alto</option>
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

        <!-- Modal Editar Doença -->
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
                                <input type="text" class="form-control" id="edit_tipo_doenca" name="tipo_doenca" required>
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

        <script>
        // Adicione isso no início do seu arquivo JavaScript
        console.log('JavaScript carregado');

        $(document).ready(function() {
            // Máscaras para os campos
            $('#pressao_arterial').mask('000/000');
            $('#glicemia').mask('000');
            $('#peso').mask('000.0', {reverse: true});
            $('#altura').mask('000');
            $('#edit_pressao_arterial').mask('000/000');
            $('#edit_glicemia').mask('000');
            $('#edit_peso').mask('000.0', {reverse: true});
            $('#edit_altura').mask('000');

            // Função para calcular e classificar o IMC
            function calcularIMC() {
                let peso = parseFloat($('#peso').val().replace(',', '.'));
                let altura = parseInt($('#altura').val());
                
                if (peso && altura) {
                    let alturaMetros = altura / 100;
                    let imc = peso / (alturaMetros * alturaMetros);
                    $('#imc').val(imc.toFixed(1));
                    
                    // Classificação do IMC
                    let classificacao = '';
                    if (imc < 18.5) classificacao = 'Abaixo do peso';
                    else if (imc < 25) classificacao = 'Peso normal';
                    else if (imc < 30) classificacao = 'Sobrepeso';
                    else if (imc < 35) classificacao = 'Obesidade Grau I';
                    else if (imc < 40) classificacao = 'Obesidade Grau II';
                    else classificacao = 'Obesidade Grau III';
                    
                    $('#imc_classificacao').text(classificacao);
                } else {
                    $('#imc').val('');
                    $('#imc_classificacao').text('');
                }
            }

            // Eventos para calcular IMC
            $('#peso, #altura').on('input', calcularIMC);
        });

        // Função para calcular e classificar o IMC
        function calcularIMC() {
            let peso = parseFloat($('#edit_peso').val().replace(',', '.'));
            let altura = parseInt($('#edit_altura').val());
            
            if (peso && altura) {
                let alturaMetros = altura / 100;
                let imc = peso / (alturaMetros * alturaMetros);
                $('#edit_imc').val(imc.toFixed(1));
                
                // Classificação do IMC
                let classificacao = '';
                if (imc < 18.5) classificacao = 'Abaixo do peso';
                else if (imc < 25) classificacao = 'Peso normal';
                else if (imc < 30) classificacao = 'Sobrepeso';
                else if (imc < 35) classificacao = 'Obesidade Grau I';
                else if (imc < 40) classificacao = 'Obesidade Grau II';
                else classificacao = 'Obesidade Grau III';
                
                $('#edit_imc_classificacao').text(classificacao);
            } else {
                $('#edit_imc').val('');
                $('#edit_imc_classificacao').text('');
            }
        }

        // Eventos para calcular IMC
        $('#edit_peso, #edit_altura').on('input', calcularIMC);
            /* Funções para o modal de adicionar médico */
            function abrirModal(pacienteId) {
                // Verifica se o botão está desabilitado
                const button = event.target;
                if (button.disabled) {
                    return; // Não faz nada se o botão estiver desabilitado
                }

                const modal = document.getElementById('modalMedicos');
                modal.classList.remove('hidden');

                // Carregar médicos do servidor
                fetch('buscar_medicos.php')
                    .then(response => response.json())
                    .then(medicos => {
                        const lista = document.getElementById('listaMedicos');
                        lista.innerHTML = ''; // Limpar a lista de médicos

                        medicos.forEach(medico => {
                            const li = document.createElement('li');
                            li.innerHTML = `
                                ${medico.nome} (${medico.especialidade})
                                <button onclick="atribuirMedico(${pacienteId}, ${medico.id})">Selecionar</button>
                            `;
                            lista.appendChild(li);
                        });
                    });
            }

            function fecharModal() {
                const modal = document.getElementById('modalMedicos');
                modal.classList.add('hidden');
            }

            function atribuirMedico(pacienteId, medicoId) {
                // Adicionar console.log para debug
                console.log('Atribuindo médico:', { pacienteId, medicoId });
                
                fetch('atribuir_medico.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        // Adicionar header para prevenir cache
                        'Cache-Control': 'no-cache'
                    },
                    body: JSON.stringify({ pacienteId, medicoId })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na resposta do servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Médico atribuído com sucesso!');
                        fecharModal();
                        location.reload();
                    } else {
                        alert('Erro ao atribuir médico: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao atribuir médico. Verifique o console para mais detalhes.');
                });
            }

            function atualizarListaMedicos(medicos, pacienteId) {
                const lista = document.getElementById('listaMedicos');
                lista.innerHTML = '';

                medicos.forEach(medico => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <div>
                            <strong>${medico.nome}</strong>
                            <div style="color: #666; font-size: 0.9rem; margin-top: 4px;">
                                ${medico.especialidade}
                            </div>
                        </div>
                        <button onclick="atribuirMedico(${pacienteId}, ${medico.id})">
                            Selecionar
                        </button>
                    `;
                    lista.appendChild(li);
                });
            }

            /* Funções para o modal de editar médico */
            function abrirModalEditar(pacienteId, medicoAtual, especialidadeAtual) {
                const modal = document.getElementById('modalEditarMedico');
                modal.classList.remove('hidden');

                // Preenche informações do médico atual
                const infoMedico = modal.querySelector('.info-medico');
                infoMedico.innerHTML = `
                    <p><strong>Nome:</strong> ${medicoAtual || 'Não atribuído'}</p>
                    <p><strong>Especialidade:</strong> ${especialidadeAtual || 'Não informada'}</p>
                `;

                // Carrega lista de médicos disponíveis
                fetch('buscar_medicos.php')
                    .then(response => response.json())
                    .then(medicos => {
                        const lista = document.getElementById('listaMedicosEditar');
                        lista.innerHTML = '';

                        medicos.forEach(medico => {
                            const li = document.createElement('li');
                            li.innerHTML = `
                                <div>
                                    <strong>${medico.nome}</strong>
                                    <div style="color: #666; font-size: 0.9rem; margin-top: 4px;">
                                        ${medico.especialidade}
                                    </div>
                                </div>
                                <button onclick="atribuirMedico(${pacienteId}, ${medico.id})">
                                    Selecionar
                                </button>
                            `;
                            lista.appendChild(li);
                        });
                    });
            }

            function fecharModalEditar() {
                const modal = document.getElementById('modalEditarMedico');
                modal.classList.add('hidden');
            }

            function atualizarMedico(pacienteId, medicoId) {
                fetch('atribuir_medico.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pacienteId, medicoId }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Médico atualizado com sucesso!');
                        fecharModalEditar();
                        location.reload();
                    } else {
                        alert('Erro ao atualizar médico.');
                    }
                });
            }

            function abrirModalConsulta(pacienteId) {
                var myModal = new bootstrap.Modal(document.getElementById('modalConsulta'));
                myModal.show();
            }

            function verDetalhesConsulta(consultaId) {
                // Fazer uma requisição AJAX para buscar os detalhes da consulta
                $.ajax({
                    url: 'buscar_consulta.php',
                    type: 'GET',
                    data: { id: consultaId },
                    success: function(response) {
                        // Aqui você pode criar outro modal para mostrar os detalhes completos
                        // incluindo as observações
                        $('#modalDetalhesConsulta').html(response).modal('show');
                    }
                });
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
                    if (glicemia && (glicemia < 20 || glicemia > 999)) {
                        alert('Valor de glicemia fora do intervalo aceitável (20-999 mg/dL)');
                        return false;
                    }
                    
                    // Validar peso
                    const peso = $('input[name="peso"]').val();
                    if (peso && (peso < 20 || peso > 500)) {
                        alert('Valor de peso fora do intervalo aceitável (20-500 kg)');
                        return false;
                    }
                    
                    // Validar altura
                    const altura = $('input[name="altura"]').val();
                    if (altura && (altura < 10 || altura > 300)) {
                        alert('Valor de altura fora do intervalo aceitável (10-300 cm)');
                        return false;
                    }
                    
                    // Se todas as validações passarem, envia o formulário
                    $.ajax({
                        url: 'salvar_consulta.php',
                        type: 'POST',
                        data: $(this).serialize(),
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                alert('Consulta cadastrada com sucesso!');
                                var myModal = bootstrap.Modal.getInstance(document.getElementById('modalConsulta'));
                                myModal.hide();
                                location.reload();
                            } else {
                                alert(response.message || 'Erro ao cadastrar consulta');
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
                        alert('Erro ao processar a requisição. Verifique o console para mais detalhes.');
                    }
                    });
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

            // Defina a função no escopo global
            function editarConsulta(consultaId) {
                // Lógica para buscar os dados da consulta e abrir o modal
                $.ajax({
                    url: 'obter_consulta.php', // URL para obter os dados da consulta
                    method: 'GET',
                    data: { id: consultaId },
                    success: function(data) {
                        // Preencher os campos do modal com os dados retornados
                        $('#edit_consulta_id').val(data.id);
                        $('#edit_data_consulta').val(data.data_consulta);
                        $('#edit_pressao_arterial').val(data.pressao_arterial);
                        $('#edit_glicemia').val(data.glicemia);
                        $('#edit_peso').val(data.peso);
                        $('#edit_altura').val(data.altura);
                        $('#edit_imc').val(data.imc);
                        $('#edit_estado_emocional').val(data.estado_emocional);
                        $('#edit_habitos_vida').val(data.habitos_vida);
                        $('#edit_observacoes').val(data.observacoes);
                        $('#profissional').val(data.profissional_id); // Seleciona o profissional

                        // Abre o modal
                        var myModal = new bootstrap.Modal(document.getElementById('modalEditarConsulta'));
                        myModal.show();
                    },
                    error: function() {
                        alert('Erro ao obter os dados da consulta.');
                    }
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
                        if (response.success) {
                            alert('Consulta atualizada com sucesso!');
                            var myModal = bootstrap.Modal.getInstance(document.getElementById('modalEditarConsulta'));
                            myModal.hide();
                            location.reload();
                        } else {
                            alert(response.message || 'Erro ao atualizar consulta');
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
                        alert('Erro ao processar a requisição. Verifique o console para mais detalhes.');
                    }
                });
            });

            function excluirConsulta(consultaId) {
                if (confirm('Tem certeza que deseja excluir esta consulta?')) {
                    $.ajax({
                        url: 'excluir_consulta.php',
                        type: 'POST',
                        data: { consulta_id: consultaId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                alert('Consulta excluída com sucesso!');
                                location.reload();
                            } else {
                                alert(response.message || 'Erro ao excluir consulta');
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
                        alert('Erro ao processar a requisição. Verifique o console para mais detalhes.');
                    }
                    });
                }
            }

            function abrirModalMedico(pacienteId) {
                $('#paciente_id').val(pacienteId);
                var myModal = new bootstrap.Modal(document.getElementById('modalMedico'));
                myModal.show();
            }

            // Manipular o envio do formulário
            $('#formTrocarMedico').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: 'trocar_medico.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Médico responsável atualizado com sucesso!');
                            var myModal = bootstrap.Modal.getInstance(document.getElementById('modalMedico'));
                            myModal.hide();
                            location.reload();
                        } else {
                            alert(response.message || 'Erro ao atualizar médico responsável');
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
                        alert('Erro ao processar a requisição. Verifique o console para mais detalhes.');
                    }
                });
            });

            function abrirModalAtribuirMedico(pacienteId, tipo) {
                $('#atribuir_paciente_id').val(pacienteId);
                $('#tipo_profissional').val(tipo);
                
                // Preenche o select com os profissionais do tipo selecionado
                const selectProfissional = $('#profissional_id');
                selectProfissional.empty();
                selectProfissional.append('<option value="">Selecione o profissional...</option>');
                
                if (window.profissionaisCadastrados && window.profissionaisCadastrados[tipo]) {
                    window.profissionaisCadastrados[tipo].forEach(function(profissional) {
                        selectProfissional.append(
                            `<option value="${profissional.id}" 
                             data-especialidade="${profissional.especialidade}"
                             data-unidade="${profissional.unidade_saude}">
                                ${profissional.nome} - ${profissional.especialidade}
                             </option>`
                        );
                    });
                }
                
                selectProfissional.prop('disabled', false);
                
                var myModal = new bootstrap.Modal(document.getElementById('modalAtribuirMedico'));
                myModal.show();
            }

            // Remove o evento change anterior e simplifica o modal
            $('#modalAtribuirMedico').on('show.bs.modal', function() {
                const tipo = $('#tipo_profissional').val();
                if (tipo) {
                    $('#profissional_id').prop('disabled', false);
                }
            });

            // Mantém o resto do código do formulário de atribuição
            $('#formAtribuirMedico').on('submit', function(e) {
                e.preventDefault();
                
                const profissionalSelect = $('#profissional_id option:selected');
                const dados = {
                    paciente_id: $('#atribuir_paciente_id').val(),
                    profissional_id: profissionalSelect.val(),
                    tipo_profissional: $('#tipo_profissional').val(),
                    especialidade: profissionalSelect.data('especialidade'),
                    unidade_saude: profissionalSelect.data('unidade')
                };
                
                $.ajax({
                    url: 'atribuir_profissional.php',
                    type: 'POST',
                    data: dados,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Profissional atribuído com sucesso!');
                            var myModal = bootstrap.Modal.getInstance(document.getElementById('modalAtribuirMedico'));
                            myModal.hide();
                            location.reload();
                        } else {
                            alert(response.message || 'Erro ao atribuir profissional');
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
                        alert('Erro ao processar a requisição. Verifique o console para mais detalhes.');
                    }
                });
            });

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
                if (confirm('Tem certeza que deseja excluir este medicamento?')) {
                    $.ajax({
                        url: 'excluir_medicamento.php',
                        type: 'POST',
                        data: { medicamento_id: medicamentoId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                alert('Medicamento excluído com sucesso!');
                                location.reload();
                            } else {
                                alert(response.message || 'Erro ao excluir medicamento');
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
                        alert('Erro ao processar a requisição. Verifique o console para mais detalhes.');
                    }
                    });
                }
            }

            $('#formMedicamento').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: 'salvar_medicamento.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Medicamento salvo com sucesso!');
                            var myModal = bootstrap.Modal.getInstance(document.getElementById('modalMedicamento'));
                            myModal.hide();
                            location.reload();
                        } else {
                            alert(response.message || 'Erro ao salvar medicamento');
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
                        alert('Erro ao processar a requisição. Verifique o console para mais detalhes.');
                    }
                });
            });

            function editarDoenca(dados) {
                // Preenche os campos do modal com os dados recebidos
                document.getElementById('edit_doenca_id').value = dados.id;
                document.getElementById('edit_tipo_doenca').value = dados.tipo_doenca;
                document.getElementById('edit_historico_familiar').value = dados.historico_familiar;
                document.getElementById('edit_estado_civil').value = dados.estado_civil;
                document.getElementById('edit_profissao').value = dados.profissao;

                // Abre o modal
                var myModal = new bootstrap.Modal(document.getElementById('modalEditarDoenca'));
                myModal.show();
            }

            // Manipula o envio do formulário
            document.getElementById('formEditarDoenca').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Coleta os dados do formulário
                var formData = new FormData(this);

                // Envia os dados para o servidor
                fetch('atualizar_doenca_paciente.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Dados atualizados com sucesso!');
                        location.reload(); // Recarrega a página para mostrar as alterações
                    } else {
                        alert('Erro ao atualizar os dados: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao atualizar os dados');
                });
            });
        </script>
    </div>
</body>
</html>