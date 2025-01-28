<?php
require_once 'conexao.php';
require_once 'verificar_login.php';

header('Content-Type: application/json');

// Recebe os dados
$id = isset($_POST['id']) ? intval($_POST['id']) : null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
    exit;
}

try {
    // Prepara e executa a query
    $query = "DELETE FROM riscos_saude WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Registro excluído com sucesso']);
    } else {
        throw new Exception('Erro ao excluir o registro');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?> 