<?php
require_once 'conexao.php';
require_once 'verificar_login.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID não fornecido');
    }

    $id = intval($_GET['id']);
    
    $query = "SELECT * FROM riscos_saude WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Registro não encontrado');
    }
    
    $risco = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'risco' => $risco
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?> 