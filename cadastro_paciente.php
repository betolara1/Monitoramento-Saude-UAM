<?php
include "sidebar.php";
include "conexao.php";

// Verificar se o ID foi passado na URL
if (!isset($_GET['id'])) {
    header('Location: listar_pacientes.php');
    exit;
}

$usuario_id = $_GET['id'];

// Buscar informações do usuário
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

// Verificar se o usuário existe
if (!$usuario) {
    header('Location: listar_pacientes.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cadastro de Pacientes com DCNT</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Cadastro de DCNT - <?php echo htmlspecialchars($usuario['nome']); ?></h2>
        <form action="salvar_pacientes_doenca.php" method="POST">
            <input type="hidden" name="usuario_id" value="<?php echo $usuario_id; ?>">
            
            <div class="mb-3">
                <label for="tipo_doenca" class="form-label">Tipo de Doença:</label>
                <select class="form-select" id="tipo_doenca" name="tipo_doenca" required>
                    <option value="">Selecione...</option>
                    <option value="Hipertensão">Hipertensão</option>
                    <option value="Diabetes">Diabetes</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="historico_familiar" class="form-label">Histórico Familiar:</label>
                <textarea class="form-control" id="historico_familiar" name="historico_familiar" rows="3"></textarea>
            </div>

            <div class="mb-3">
                <label for="estado_civil" class="form-label">Estado Civil:</label>
                <select class="form-select" id="estado_civil" name="estado_civil">
                    <option value="">Selecione...</option>
                    <option value="Solteiro">Solteiro(a)</option>
                    <option value="Casado">Casado(a)</option>
                    <option value="Divorciado">Divorciado(a)</option>
                    <option value="Viúvo">Viúvo(a)</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="profissao" class="form-label">Profissão:</label>
                <input type="text" class="form-control" id="profissao" name="profissao">
            </div>

            <button type="submit" class="btn btn-primary">Cadastrar</button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>