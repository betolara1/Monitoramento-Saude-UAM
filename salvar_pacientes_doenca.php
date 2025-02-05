<?php
include "conexao.php";

// Recebe os dados do formulário
$usuario_id = $_POST['usuario_id'];
$tipo_doenca = $_POST['tipo_doenca'];
$historico_familiar = $_POST['historico_familiar'];
$estado_civil = $_POST['estado_civil'];
$profissao = $_POST['profissao'];

// Prepara e executa a query
$stmt = $conn->prepare("INSERT INTO pacientes (usuario_id, tipo_doenca, historico_familiar, estado_civil, profissao) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $usuario_id, $tipo_doenca, $historico_familiar, $estado_civil, $profissao);

if ($stmt->execute()) {
    // Após o sucesso do cadastro
    header('Location: cadastro_paciente.php?success=true');
    exit();
} else {
    echo "<script>alert('Erro ao cadastrar: " . $stmt->error . "'); window.location.href='cadastro_pacientes_doenca.php';</script>";
}

$stmt->close();
$conn->close();
?>