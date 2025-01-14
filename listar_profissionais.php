<?php
include "conexao.php";
include "sidebar.php";
include 'verificar_login.php';

// Verifica se é admin ou profissional
$is_admin = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'Admin';
$usuario_id = $_SESSION['usuario_id'] ?? null;

// Query SQL diferente baseada no tipo de usuário
if ($is_admin) {
    // Admin vê todos os profissionais
    $sql = "SELECT u.id as usuario_id, 
            u.nome, u.email, u.telefone,
            p.id as profissional_id,
            p.especialidade, p.registro_profissional, p.unidade_saude 
            FROM usuarios u 
            LEFT JOIN profissionais p ON u.id = p.usuario_id 
            WHERE u.tipo_usuario = 'Profissional' 
            ORDER BY u.nome";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
} else {
    // Profissional vê apenas seu próprio perfil
    $sql = "SELECT u.id as usuario_id, 
            u.nome, u.email, u.telefone,
            p.id as profissional_id,
            p.especialidade, p.registro_profissional, p.unidade_saude 
            FROM usuarios u 
            LEFT JOIN profissionais p ON u.id = p.usuario_id 
            WHERE u.tipo_usuario = 'Profissional' 
            AND u.id = ?
            ORDER BY u.nome";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
}

$profissionais = $stmt->get_result();

// Ajusta o título baseado no tipo de usuário
$titulo = $is_admin ? "Profissionais de Saúde" : "Meu Perfil Profissional";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Usuários - Profissionais</title>
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

        h1 {
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

        input {
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
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background-color: #4CAF50;
        }

        .btn-primary:hover {
            background-color: #45a049;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }

        .status-pendente {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-completo {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $titulo; ?></h1>

        <!-- Remove a busca se não for admin -->
        <?php if ($is_admin): ?>
            <div class="filters">
                <div class="search-box">
                    <input type="text" id="busca" onkeyup="filtrarProfissionais()" 
                           placeholder="Buscar por nome, especialidade ou unidade...">
                </div>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Especialidade</th>
                    <th>Registro Profissional</th>
                    <th>Unidade de Saúde</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody id="profissionais-tbody">
                <?php foreach ($profissionais as $profissional): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($profissional['nome']); ?></td>
                        <td><?php echo htmlspecialchars($profissional['email']); ?></td>
                        <td><?php echo htmlspecialchars($profissional['telefone']); ?></td>
                        <td><?php echo htmlspecialchars($profissional['especialidade'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($profissional['registro_profissional'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($profissional['unidade_saude'] ?? ''); ?></td>
                        <td>
                            <?php if ($profissional['especialidade']): ?>
                                <span class="status-badge status-completo">Cadastro Completo</span>
                            <?php else: ?>
                                <span class="status-badge status-pendente">Pendente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($profissional['especialidade']): ?>
                                <a href="editar_profissional.php?id=<?php echo $profissional['profissional_id']; ?>&usuario_id=<?php echo $profissional['usuario_id']; ?>" class="btn btn-primary">Editar</a>
                            <?php else: ?>
                                <a href="cadastro_profissional.php?id=<?php echo $profissional['usuario_id']; ?>" class="btn btn-primary">Completar Cadastro</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($is_admin): ?>
    <script>
    function filtrarProfissionais() {
        const input = document.getElementById('busca');
        const filter = input.value.toLowerCase();
        const tbody = document.getElementById('profissionais-tbody');
        const rows = tbody.getElementsByTagName('tr');

        for (let row of rows) {
            const nome = row.cells[0].textContent.toLowerCase();
            const especialidade = row.cells[3].textContent.toLowerCase();
            const unidade = row.cells[5].textContent.toLowerCase();
            
            const matchTermo = nome.includes(filter) || 
                             especialidade.includes(filter) || 
                             unidade.includes(filter);

            row.style.display = matchTermo ? '' : 'none';
        }
    }
    </script>
    <?php endif; ?>
</body>
</html>