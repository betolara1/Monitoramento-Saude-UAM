<?php
include "conexao.php";
include "sidebar.php";

// Buscar pacientes e profissionais com JOIN nas tabelas específicas
$sql = "SELECT 
    u.*, -- dados do usuário (paciente)
    p.id AS paciente_id, -- importante ter o id do paciente para a relação
    p.tipo_doenca, 
    p.historico_familiar, 
    p.estado_civil, 
    p.profissao,
    pp.profissional_id, -- id do profissional da relação
    COALESCE(up.nome, 'Não atribuído') as nome_profissional,
    pr.especialidade,
    pr.registro_profissional,
    pr.unidade_saude
    FROM usuarios u 
    LEFT JOIN pacientes p ON u.id = p.usuario_id 
    LEFT JOIN paciente_profissional pp ON p.id = pp.paciente_id
    LEFT JOIN profissionais pr ON pr.id = pp.profissional_id
    LEFT JOIN usuarios up ON pr.usuario_id = up.id
    WHERE u.tipo_usuario = 'Paciente' 
    ORDER BY u.nome";

$stmt = $conn->prepare($sql);
$stmt->execute();
$usuarios = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Usuários</title>
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

        h1, h2, h3 {
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

        .filter-tipo {
            width: 200px;
        }

        input, select {
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
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .tipo-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }

        .tipo-paciente {
            background-color: #e3f2fd;
            color: #1565c0;
        }

        .tipo-profissional {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }

        .usuario-detalhes {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .info-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .info-item {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .info-item strong {
            display: inline-block;
            width: 150px;
            color: #555;
        }

        .tipo-doenca {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
        }
        
        .doenca-hipertensao {
            background-color: #ffcdd2;
            color: #c62828;
        }
        
        .doenca-diabetes {
            background-color: #c8e6c9;
            color: #2e7d32;
        }
        
        .sem-doenca {
            background-color: #f5f5f5;
            color: #757575;
        }


        .hidden {
            display: none;
        }


        #listaMedicos li {
            margin-bottom: 10px;
        }

        .btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
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
        <h1>Pacientes</h1>

        <div class="filters">
            <div class="search-box">
                <input type="text" id="busca" onkeyup="filtrarUsuarios()" placeholder="Buscar por nome ou tipo de doença...">
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tipo Doença</th>
                    <th>Adicionar/Editar Doença</th>
                    <th>Médico Responsável</th>
                    <th>Adicionar/Editar Médico</th>
                </tr>
            </thead>
            <tbody id="usuarios-tbody">
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                        <td>
                            <?php
                            $doencaClass = '';
                            $doencaText = 'Não cadastrado';
                            
                            if ($usuario['tipo_doenca']) {
                                $doencaText = $usuario['tipo_doenca'];
                                $doencaClass = 'doenca-' . strtolower(str_replace('ã', 'a', $usuario['tipo_doenca']));
                            }
                            ?>
                            <span class="tipo-doenca <?php echo $doencaClass ?: 'sem-doenca'; ?>">
                                <?php echo htmlspecialchars($doencaText); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($usuario['tipo_doenca']): ?>
                                <button onclick="window.location.href='atualizar_pacientes_doenca.php?id=<?php echo $usuario['id']; ?>'" class="btn btn-edit">Editar</button>
                            <?php else: ?>
                                <button onclick="window.location.href='cadastro_pacientes_doenca.php?id=<?php echo $usuario['id']; ?>'" class="btn btn-primary">Cadastrar</button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($usuario['nome_profissional'] && $usuario['nome_profissional'] !== 'Não atribuído'): ?>
                                <?php echo htmlspecialchars($usuario['nome_profissional']); ?>
                                <?php if (!empty($usuario['especialidade'])): ?>
                                    <br>
                                    <small><?php echo htmlspecialchars($usuario['especialidade']); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="sem-doenca">Não atribuído</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($usuario['nome_profissional'] && $usuario['nome_profissional'] !== 'Não atribuído'): ?>
                                <div class="btn-group">
                                    <button onclick="abrirModalEditar(
                                        <?php echo $usuario['paciente_id']; ?>, 
                                        '<?php echo htmlspecialchars($usuario['nome_profissional'], ENT_QUOTES); ?>', 
                                        '<?php echo htmlspecialchars($usuario['especialidade'], ENT_QUOTES); ?>'
                                    )" class="btn btn-edit">Editar</button>
                                </div>
                            <?php else: ?>
                                <div class="tooltip">
                                    <button onclick="abrirModal(<?php echo $usuario['paciente_id']; ?>)" 
                                            class="btn btn-primary"
                                            <?php echo empty($usuario['tipo_doenca']) ? 'disabled' : ''; ?>>
                                        Atribuir
                                    </button>
                                    <?php if (empty($usuario['tipo_doenca'])): ?>
                                        <span class="tooltiptext">É necessário cadastrar o tipo de doença primeiro</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
        function filtrarUsuarios() {
            const input = document.getElementById('busca');
            const filter = input.value.toLowerCase();
            const tbody = document.getElementById('usuarios-tbody');
            const rows = tbody.getElementsByTagName('tr');

            for (let row of rows) {
                const nome = row.getElementsByTagName('td')[0].textContent.toLowerCase();
                const doenca = row.getElementsByTagName('td')[3].textContent.toLowerCase();
                const medico = row.getElementsByTagName('td')[5].textContent.toLowerCase();
                
                // Verifica se o termo de busca está presente no nome, doença OU nome do médico
                const matchTermo = nome.includes(filter) || 
                                doenca.includes(filter) || 
                                medico.includes(filter);

                row.style.display = matchTermo ? '' : 'none';
            }
        }

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