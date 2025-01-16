<?php
session_start();
include "conexao.php";
include "sidebar.php";

// Verificar se é administrador
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

// Buscar logs
$sql = "SELECT l.*, u.nome as nome_usuario, u.tipo_usuario 
        FROM logs_acesso l 
        JOIN usuarios u ON l.usuario_id = u.id 
        ORDER BY l.data_acesso DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Logs de Acesso</title>
    <style>
        .table-container {
            margin: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Logs de Acesso</h1>
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
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i:s', strtotime($row['data_acesso'])); ?></td>
                        <td><?php echo htmlspecialchars($row['nome_usuario']); ?></td>
                        <td><?php echo htmlspecialchars($row['tipo_usuario']); ?></td>
                        <td><?php echo htmlspecialchars($row['acao']); ?></td>
                        <td><?php echo htmlspecialchars($row['endereco_ip']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 