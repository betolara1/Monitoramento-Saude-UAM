<?php
require_once 'conexao.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID da consulta não fornecido');
    }

    $id = intval($_GET['id']);
    
    $sql = "SELECT c.*, 
            COALESCE(c.observacoes, '') as observacoes,
            u.nome as profissional_nome 
            FROM consultas c 
            LEFT JOIN profissionais p ON c.profissional_id = p.id 
            LEFT JOIN usuarios u ON p.usuario_id = u.id 
            WHERE c.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Consulta não encontrada');
    }

    $consulta = $result->fetch_assoc();
    error_log('Dados da consulta: ' . print_r($consulta, true));
    
    // Debug: Imprimir os dados no log
    error_log('Dados da consulta: ' . print_r($consulta, true));

    echo json_encode([
        'success' => true,
        'consulta' => $consulta
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 