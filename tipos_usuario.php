<?php
include "conexao.php";
include "sidebar.php";

// Buscar pacientes e profissionais com JOIN nas tabelas específicas
$sql = "SELECT u.*, 
        p.tipo_doenca, p.historico_familiar, p.estado_civil, p.profissao,
        pr.especialidade, pr.registro_profissional, pr.unidade_saude
        FROM usuarios u 
        LEFT JOIN pacientes p ON u.id = p.usuario_id 
        LEFT JOIN profissionais pr ON u.id = pr.usuario_id
        WHERE u.tipo_usuario IN ('Paciente', 'Profissional') 
        ORDER BY u.tipo_usuario, u.nome";
$stmt = $conn->prepare($sql);
$stmt->execute();
$usuarios = $stmt->get_result();


// Quando um usuário é selecionado
if (isset($_POST['usuario_id'])) {
    try {
        $sql = "SELECT u.*,
                p.tipo_doenca, p.historico_familiar, p.estado_civil, p.profissao,
                pr.especialidade, pr.registro_profissional, pr.unidade_saude
                FROM usuarios u 
                LEFT JOIN pacientes p ON u.id = p.usuario_id 
                LEFT JOIN profissionais pr ON u.id = pr.usuario_id
                WHERE u.id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id' => $_POST['usuario_id']]);
        $usuario_detalhes = $stmt->get_result();
    } catch(connException $e) {
        echo "Erro ao buscar detalhes: " . $e->getMessage();
    }
}
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Usuários</h1>

        <div class="filters">
            <div class="search-box">
                <input type="text" id="busca" onkeyup="filtrarUsuarios()" placeholder="Buscar usuário por nome...">
            </div>
            <div class="filter-tipo">
                <select id="filtro-tipo" onchange="filtrarUsuarios()">
                    <option value="">Todos os tipos</option>
                    <option value="Paciente">Pacientes</option>
                    <option value="Profissional">Profissionais</option>
                </select>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Tipo</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody id="usuarios-tbody">
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['telefone']); ?></td>
                        <td>
                            <span class="tipo-badge tipo-<?php echo strtolower($usuario['tipo_usuario']); ?>">
                                <?php echo htmlspecialchars($usuario['tipo_usuario']); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                <button type="submit" class="btn">Selecionar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

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
                
                // Verifica se o termo de busca está presente no nome OU na doença
                const matchTermo = nome.includes(filter) || doenca.includes(filter);

                row.style.display = matchTermo ? '' : 'none';
            }
        }
    </script>
</body>
</html>