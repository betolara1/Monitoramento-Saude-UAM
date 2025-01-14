<?php
include "conexao.php";
include "sidebar.php";

$paciente_id = $_GET['id'];

$sql = "SELECT 
    u.*,
    p.*,
    COALESCE(up.nome, 'Não atribuído') as nome_profissional,
    pr.especialidade,
    pr.registro_profissional,
    pr.unidade_saude
    FROM usuarios u 
    INNER JOIN pacientes p ON u.id = p.usuario_id 
    LEFT JOIN paciente_profissional pp ON p.id = pp.paciente_id
    LEFT JOIN profissionais pr ON pr.id = pp.profissional_id
    LEFT JOIN usuarios up ON pr.usuario_id = up.id
    WHERE p.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $paciente_id);
$stmt->execute();
$paciente = $stmt->get_result()->fetch_assoc();
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Header com nome do paciente e botão voltar -->
        <div class="header-container">
            <h1>Paciente <?php echo htmlspecialchars($paciente['nome']); ?></h1>
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
                        <th>Data Cadastro</th>
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
                            <?php echo $paciente['data_cadastro'] ? date('d/m/Y', strtotime($paciente['data_cadastro'])) : '-'; ?>
                        </td>
                        <td>
                            <?php if ($paciente['tipo_doenca']): ?>
                                <a href="atualizar_pacientes_doenca.php?id=<?php echo $paciente_id; ?>" 
                                   class="btn btn-secondary">Editar</a>
                            <?php else: ?>
                                <a href="cadastro_pacientes_doenca.php?id=<?php echo $paciente_id; ?>" 
                                   class="btn btn-primary">Cadastrar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

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
                            <?php if ($paciente['nome_profissional'] !== 'Não atribuído'): ?>
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
                                <button onclick="abrirModalEditar(<?php echo $paciente_id; ?>)" 
                                        class="btn btn-secondary">Trocar Médico</button>
                            <?php else: ?>
                                <button onclick="abrirModal(<?php echo $paciente_id; ?>)" 
                                        class="btn btn-primary"
                                        <?php echo empty($paciente['tipo_doenca']) ? 'disabled' : ''; ?>>
                                    Atribuir Médico
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>


    <!-- Modal de seleção de médicos -->
    <div id="modalMedicos" class="modal hidden">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Selecione um Médico</h2>
                <button class="close-button" onclick="fecharModal()">&times;</button>
            </div>
            <div class="modal-body">
                <ul id="listaMedicos" class="medicos-list"></ul>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <div id="modalEditarMedico" class="modal hidden">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Médico</h2>
                <button class="close-button" onclick="fecharModalEditar()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="medicoAtual" class="medico-atual">
                    <h3>Médico Atual</h3>
                    <div class="info-medico"></div>
                </div>
                <div class="separador">
                    <span>Selecione um novo médico</span>
                </div>
                <ul id="listaMedicosEditar" class="medicos-list"></ul>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="fecharModalEditar()">Cancelar</button>
            </div>
        </div>
    </div>


    <script>
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
            fetch('atribuir_medico.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pacienteId, medicoId }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Médico atribuído com sucesso!');
                    fecharModal();
                    location.reload();
                } else {
                    alert('Erro ao atribuir médico.');
                }
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
                <strong>Nome:</strong> ${medicoAtual}<br>
                <strong>Especialidade:</strong> ${especialidadeAtual}
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
                            <button onclick="atualizarMedico(${pacienteId}, ${medico.id})">
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
    </script>

</body>
</html>