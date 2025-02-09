<?php
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_id = $_POST['usuario_id'];
    $tipo_doenca = $_POST['tipo_doenca'];
    $historico_familiar = $_POST['historico_familiar'];
    $estado_civil = $_POST['estado_civil'];
    $profissao = $_POST['profissao'];

    $sql = "INSERT INTO pacientes (usuario_id, tipo_doenca, historico_familiar, estado_civil, profissao) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $usuario_id, $tipo_doenca, $historico_familiar, $estado_civil, $profissao);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}
?>