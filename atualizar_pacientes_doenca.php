<?php
// Arquivo: editar_pacientes_doenca.php
include "conexao.php";
include "sidebar.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='container mt-5 alert alert-danger'>ID do usuário não fornecido.</div>";
    exit();
}

$usuario_id = $_GET['id'];

// Buscar informações do usuário e da doença
$stmt = $conn->prepare("
    SELECT 
        u.*,
        p.id as paciente_id, 
        p.tipo_doenca,
        p.historico_familiar,
        p.estado_civil,
        p.profissao 
    FROM usuarios u 
    LEFT JOIN pacientes p ON u.id = p.usuario_id 
    WHERE u.id = ?
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();
$dados = $resultado->fetch_assoc();

// Adicionar verificação após buscar os dados
if (!$dados) {
    echo "<div class='container mt-5 alert alert-danger'>Paciente não encontrado.</div>";
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar DCNT do Paciente</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Editar DCNT - <?php echo htmlspecialchars($dados['nome']); ?></h2>
        <form action="salvar_edicao_pacientes_doenca.php" method="POST">
            <input type="hidden" name="usuario_id" value="<?php echo $usuario_id; ?>">
            <input type="hidden" name="paciente_id" value="<?php echo $dados['paciente_id']; ?>">
            
            <div class="mb-3">
                <label for="tipo_doenca" class="form-label">Tipo de Doença:</label>
                <select class="form-select" id="tipo_doenca" name="tipo_doenca" required>
                    <option value="Hipertensão" <?php echo $dados['tipo_doenca'] == 'Hipertensão' ? 'selected' : ''; ?>>Hipertensão</option>
                    <option value="Diabetes" <?php echo $dados['tipo_doenca'] == 'Diabetes' ? 'selected' : ''; ?>>Diabetes</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="historico_familiar" class="form-label">Histórico Familiar:</label>
                <textarea class="form-control" id="historico_familiar" name="historico_familiar" rows="3"><?php echo htmlspecialchars($dados['historico_familiar']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="estado_civil" class="form-label">Estado Civil:</label>
                <select class="form-select" id="estado_civil" name="estado_civil">
                    <option value="">Selecione...</option>
                    <option value="Solteiro" <?php echo $dados['estado_civil'] == 'Solteiro' ? 'selected' : ''; ?>>Solteiro(a)</option>
                    <option value="Casado" <?php echo $dados['estado_civil'] == 'Casado' ? 'selected' : ''; ?>>Casado(a)</option>
                    <option value="Divorciado" <?php echo $dados['estado_civil'] == 'Divorciado' ? 'selected' : ''; ?>>Divorciado(a)</option>
                    <option value="Viúvo" <?php echo $dados['estado_civil'] == 'Viúvo' ? 'selected' : ''; ?>>Viúvo(a)</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="profissao" class="form-label">Profissão:</label>
                <input type="text" class="form-control" id="profissao" name="profissao" value="<?php echo htmlspecialchars($dados['profissao']); ?>">
            </div>

            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="listar_pacientes.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>