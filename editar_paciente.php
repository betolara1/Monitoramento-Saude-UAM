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

        
    <script>
      

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
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Excluído!',
                                    text: 'O medicamento foi excluído com sucesso.'
                                }).then(() => {
                                    location.reload();
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
                    try {
                        if (response.success) {
                            // Fechar o modal
                            var myModal = bootstrap.Modal.getInstance(document.getElementById('modalMedicamento'));
                            myModal.hide();
                            
                            // Mostrar mensagem de sucesso
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: 'Medicamento salvo com sucesso!'
                            }).then((result) => {
                                // Recarrega a página após fechar o alerta
                                location.reload();
                            });
                        } else {
                            throw new Error(response.message || 'Erro ao salvar medicamento');
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
                }
            });
        });

    </script>

</body>
</html>