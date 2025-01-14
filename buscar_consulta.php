<?php
require_once 'conexao.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    try {
        $consulta_id = $_GET['id'];
        
        $query = "SELECT c.*, 
                         COALESCE(u.nome, 'Não informado') as nome_profissional,
                         p.especialidade,
                         p.unidade_saude
                  FROM consultas c 
                  LEFT JOIN profissionais p ON c.profissional_id = p.id 
                  LEFT JOIN usuarios u ON p.usuario_id = u.id
                  WHERE c.id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $consulta_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($consulta = $result->fetch_assoc()) {
            // Formata os dados conforme necessário
            $consulta['data_consulta'] = date('Y-m-d', strtotime($consulta['data_consulta']));
            
            echo json_encode([
                'success' => true,
                'data' => $consulta
            ]);
        } else {
            throw new Exception('Consulta não encontrada');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ID da consulta não fornecido'
    ]);
}
?> 