<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

try {
    // Verificar permissões
    if (!isset($_SESSION['tipo_usuario']) || 
        ($_SESSION['tipo_usuario'] !== 'Admin' && $_SESSION['tipo_usuario'] !== 'Profissional')) {
        throw new Exception('Acesso não autorizado');
    }

    // Validar dados
    if (empty($_POST['analise_id'])) {
        throw new Exception('ID da análise não fornecido');
    }

    $analise_id = intval($_POST['analise_id']);

    // Excluir registro
    $query = "DELETE FROM analises_estatisticas WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $analise_id);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao excluir análise: " . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Análise excluída com sucesso'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 