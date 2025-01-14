<?php
include "conexao.php";
include "sidebar.php";
include 'verificar_login.php';

// Verifica o tipo de usuário
$is_admin = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'Admin';
$is_profissional = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'Profissional';
$is_paciente = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'Paciente';
$usuario_id = $_SESSION['usuario_id'] ?? null;

// Query SQL diferente baseada no tipo de usuário
if ($is_admin || $is_profissional) {
    // Admin e Profissional veem todos os pacientes
    $sql = "SELECT 
        u.*,
        p.id as paciente_id,
        p.tipo_doenca
        FROM usuarios u 
        LEFT JOIN pacientes p ON u.id = p.usuario_id 
        WHERE u.tipo_usuario = 'Paciente' 
        ORDER BY u.nome";
    $stmt = $conn->prepare($sql);
} else {
    // Paciente vê apenas seu próprio registro
    $sql = "SELECT 
        u.*,
        p.id as paciente_id,
        p.tipo_doenca
        FROM usuarios u 
        LEFT JOIN pacientes p ON u.id = p.usuario_id 
        WHERE u.tipo_usuario = 'Paciente' 
        AND u.id = ?
        ORDER BY u.nome";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
}

$stmt->execute();
$usuarios = $stmt->get_result();

// Ajusta o título baseado no tipo de usuário
$titulo = ($is_admin || $is_profissional) ? "Lista de Pacientes" : "Meus Dados";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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

        .search-box {
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .btn-editar {
            background-color: #0d6efd;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .btn-editar:hover {
            background-color: #0b5ed7;
            color: white;
            text-decoration: none;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-cadastrado {
            background-color: #198754;
            color: white;
        }

        .status-pendente {
            background-color: #ffc107;
            color: #000;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            th, td {
                padding: 10px;
            }

            .btn-editar {
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $titulo; ?></h1>
        
        <?php if ($is_admin || $is_profissional): ?>
            <div class="search-box">
                <input type="text" 
                       id="busca" 
                       class="form-control" 
                       onkeyup="filtrarPacientes()" 
                       placeholder="Buscar por nome, email ou telefone...">
            </div>
        <?php endif; ?>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="pacientes-tbody">
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['telefone']); ?></td>
                            <td>
                                <?php if ($usuario['tipo_doenca']): ?>
                                    <span class="status-badge status-cadastrado">Cadastro Completo</span>
                                <?php else: ?>
                                    <span class="status-badge status-pendente">Pendente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($is_paciente): ?>
                                    <?php if ($usuario['paciente_id']): ?>
                                        <a href="editar_paciente.php?id=<?php echo $usuario['paciente_id']; ?>" 
                                           class="btn-editar">
                                            Editar Meus Dados
                                        </a>
                                    <?php else: ?>
                                        <a href="cadastro_paciente.php?id=<?php echo $usuario['id']; ?>" 
                                           class="btn-editar">
                                            Completar Meu Cadastro
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($usuario['paciente_id']): ?>
                                        <a href="editar_paciente.php?id=<?php echo $usuario['paciente_id']; ?>" 
                                           class="btn-editar">
                                            Editar Paciente
                                        </a>
                                    <?php else: ?>
                                        <a href="cadastro_paciente.php?id=<?php echo $usuario['id']; ?>" 
                                           class="btn-editar">
                                            Completar Cadastro
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($is_admin || $is_profissional): ?>
    <script>
    function filtrarPacientes() {
        const input = document.getElementById('busca');
        const filter = input.value.toLowerCase();
        const tbody = document.getElementById('pacientes-tbody');
        const rows = tbody.getElementsByTagName('tr');

        for (let row of rows) {
            const nome = row.cells[0].textContent.toLowerCase();
            const email = row.cells[1].textContent.toLowerCase();
            const telefone = row.cells[2].textContent.toLowerCase();
            
            if (nome.includes(filter) || 
                email.includes(filter) || 
                telefone.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }
    </script>
    <?php endif; ?>
</body>
</html>