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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .search-container {
            margin-bottom: 20px;
            text-align: center;
        }
        .search-container input[type="text"] {
            padding: 10px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 4px;
            transition: border-color 0.3s;
        }
        .search-container input[type="text"]:focus {
            border-color: #007bff;
            outline: none;
        }
        .search-container button {
            padding: 10px 15px;
            border: none;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 5px;
            transition: background-color 0.3s;
        }
        .search-container button:hover {
            background-color: #0056b3;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .no-logs {
            text-align: center;
            padding: 20px;
            color: #777;
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