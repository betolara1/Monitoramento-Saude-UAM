<?php
session_start();
include "conexao.php";

// Verificar se é administrador
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

// Inicializar variável de busca
$searchTerm = '';
if (isset($_POST['search'])) {
    $searchTerm = $_POST['search'];
}

// Buscar logs com filtro
$sql = "SELECT l.*, u.nome as nome_usuario, u.tipo_usuario 
        FROM logs_acesso l 
        JOIN usuarios u ON l.usuario_id = u.id 
        WHERE u.nome LIKE ? OR l.acao LIKE ? OR l.endereco_ip LIKE ? OR u.tipo_usuario LIKE ? 
        ORDER BY l.data_acesso DESC";

$stmt = $conn->prepare($sql);
$likeTerm = "%$searchTerm%";
$stmt->bind_param("ssss", $likeTerm, $likeTerm, $likeTerm, $likeTerm);
$stmt->execute();
$result = $stmt->get_result();

// Se a requisição for AJAX, retorne apenas o corpo da tabela
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $output = '';
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output .= '<tr>
                            <td>' . date('d/m/Y H:i:s', strtotime($row['data_acesso'])) . '</td>
                            <td>' . htmlspecialchars($row['nome_usuario']) . '</td>
                            <td>' . htmlspecialchars($row['tipo_usuario']) . '</td>
                            <td>' . htmlspecialchars($row['acao']) . '</td>
                            <td>' . htmlspecialchars($row['endereco_ip']) . '</td>
                        </tr>';
        }
    } else {
        $output .= '<tr><td colspan="5" class="no-logs">Nenhum log encontrado.</td></tr>';
    }
    echo $output; // Retorna apenas o corpo da tabela
    exit; // Para evitar que o restante do código seja executado
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
    </style>
    <script>
        function searchLogs(event) {
            if (event) {
                event.preventDefault();
            }
            const searchTerm = document.getElementById('searchInput').value;
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'visualizar_logs.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    document.getElementById('logsTableBody').innerHTML = this.responseText;
                }
            };
            xhr.send('search=' + encodeURIComponent(searchTerm));
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Logs de Acesso</h1>
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Buscar por usuário, ação ou IP" onkeyup="searchLogs()">
            <button id="searchButton" onclick="searchLogs(event)">Buscar</button>
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
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($row['data_acesso'])); ?></td>
                            <td><?php echo htmlspecialchars($row['nome_usuario']); ?></td>
                            <td><?php echo htmlspecialchars($row['tipo_usuario']); ?></td>
                            <td><?php echo htmlspecialchars($row['acao']); ?></td>
                            <td><?php echo htmlspecialchars($row['endereco_ip']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="no-logs">Nenhum log encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 