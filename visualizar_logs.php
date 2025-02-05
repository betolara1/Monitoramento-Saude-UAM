<?php
session_start();
include "conexao.php";

// Verificar se é administrador
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

// Parâmetros de paginação
$registros_por_pagina = isset($_POST['per_page']) ? (int)$_POST['per_page'] : 10;
$pagina_atual = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Inicializar variável de busca
$searchTerm = isset($_POST['search']) ? $_POST['search'] : '';

// Primeiro, contar o total de registros
$sql_count = "SELECT COUNT(*) as total 
              FROM logs_acesso l 
              JOIN usuarios u ON l.usuario_id = u.id 
              WHERE u.nome LIKE ? OR l.acao LIKE ? OR l.endereco_ip LIKE ? OR u.tipo_usuario LIKE ?";

$stmt_count = $conn->prepare($sql_count);
$likeTerm = "%$searchTerm%";
$stmt_count->bind_param("ssss", $likeTerm, $likeTerm, $likeTerm, $likeTerm);
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Buscar logs com paginação
$sql = "SELECT l.*, u.nome as nome_usuario, u.tipo_usuario 
        FROM logs_acesso l 
        JOIN usuarios u ON l.usuario_id = u.id 
        WHERE u.nome LIKE ? OR l.acao LIKE ? OR l.endereco_ip LIKE ? OR u.tipo_usuario LIKE ? 
        ORDER BY l.data_acesso DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssii", $likeTerm, $likeTerm, $likeTerm, $likeTerm, $registros_por_pagina, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Se a requisição for AJAX, retorne JSON com os dados e informações de paginação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $output = [
        'html' => '',
        'pagination' => [
            'current_page' => $pagina_atual,
            'total_pages' => $total_paginas,
            'total_records' => $total_registros
        ]
    ];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output['html'] .= '<tr>
                                <td>' . date('d/m/Y H:i:s', strtotime($row['data_acesso'])) . '</td>
                                <td>' . htmlspecialchars($row['nome_usuario']) . '</td>
                                <td>' . htmlspecialchars($row['tipo_usuario']) . '</td>
                                <td>' . htmlspecialchars($row['acao']) . '</td>
                                <td>' . htmlspecialchars($row['endereco_ip']) . '</td>
                            </tr>';
        }
    } else {
        $output['html'] = '<tr><td colspan="5" class="no-logs">Nenhum log encontrado.</td></tr>';
    }

    echo json_encode($output);
    exit;
}

// Se não for uma requisição AJAX, inclua a sidebar e o restante da página
include "sidebar.php";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Logs de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
        }

        .container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            margin-left: 220px;
            background: transparent;
            box-shadow: none;
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .search-container {
            margin-bottom: 2rem;
            text-align: center;
        }

        .search-container input[type="text"] {
            padding: 0.8rem;
            width: 300px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-container input[type="text"]:focus {
            border-color: #1e3c72;
            outline: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .search-container button {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-container button:hover {
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }

        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1rem;
            text-align: left;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .no-logs {
            text-align: center;
            padding: 2rem;
            color: #666;
            font-size: 0.95rem;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1rem 0;
            position: relative;
            bottom: 0;
            width: 100%;
            margin-top: 2rem;
            text-align: center;
        }

        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
        }

        .pagination button {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pagination button:hover {
            background: #f0f0f0;
        }

        .pagination button.active {
            background: #1e3c72;
            color: white;
            border-color: #1e3c72;
        }

        .per-page-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .per-page-selector select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
    <script>
        let currentPage = 1;
        let perPage = 10;

        function loadLogs(page = 1) {
            // Atualiza a página atual antes de carregar os logs
            currentPage = page;

            const searchTerm = document.getElementById('searchInput').value;
            const formData = new FormData();
            formData.append('search', searchTerm);
            formData.append('page', page);
            formData.append('per_page', perPage);

            fetch('visualizar_logs.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('logsTableBody').innerHTML = data.html;
                updatePagination(data.pagination);
                updateTotalRecords(data.pagination.total_records);
            })
            .catch(error => console.error('Erro:', error));
        }

        function updatePagination(pagination) {
            const paginationDiv = document.getElementById('pagination');
            let html = '';

            // Botão Anterior
            html += `<button onclick="loadLogs(${currentPage - 1})" 
                            ${currentPage === 1 ? 'disabled' : ''}>
                        Anterior
                    </button>`;

            // Páginas
            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === 1 || i === pagination.total_pages || 
                    (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += `<button onclick="loadLogs(${i})" 
                                    ${i === currentPage ? 'class="active"' : ''}>
                                ${i}
                            </button>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += '<span>...</span>';
                }
            }

            // Botão Próximo
            html += `<button onclick="loadLogs(${currentPage + 1})" 
                            ${currentPage === pagination.total_pages ? 'disabled' : ''}>
                        Próximo
                    </button>`;

            paginationDiv.innerHTML = html;
        }

        function updateTotalRecords(total) {
            document.getElementById('totalRecords').innerHTML = 
                `Total de registros: ${total}`;
        }

        function searchLogs() {
            currentPage = 1;
            loadLogs(1);
        }

        function changePerPage() {
            perPage = document.getElementById('perPageSelect').value;
            currentPage = 1;
            loadLogs(1);
        }

        // Carregar logs iniciais
        document.addEventListener('DOMContentLoaded', () => {
            loadLogs(1);
        });

        // Adicionar debounce na busca
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(searchLogs, 500);
        });
    </script>
</head>
<body>
    <div class="container">
        <h1>Logs de Acesso</h1>
        
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Buscar por usuário, ação ou IP">
            <button onclick="searchLogs()">Buscar</button>
        </div>

        <div class="per-page-selector">
            <label>Registros por página:</label>
            <select id="perPageSelect" onchange="changePerPage()">
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Usuário</th>
                        <th>Tipo</th>
                        <th>Ação</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody id="logsTableBody">
                    <!-- Dados serão carregados via JavaScript -->
                </tbody>
            </table>
        </div>

        <div class="pagination-container">
            <div id="pagination" class="pagination">
                <!-- Botões de paginação serão gerados via JavaScript -->
            </div>
            <div id="totalRecords">
                <!-- Total de registros será exibido aqui -->
            </div>
        </div>
    </div>
</body>
</html> 