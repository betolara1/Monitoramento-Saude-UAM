<?php
include 'conexao.php';
include 'verificar_login.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Modificar a query para buscar todos os dados necessários
    $sql = "SELECT p.*, u.tipo_usuario, u.micro_area 
            FROM profissionais p 
            JOIN usuarios u ON p.usuario_id = u.id 
            WHERE p.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($profissional = $result->fetch_assoc()) {
            // Retornar os dados em um formato mais simples
            echo json_encode([
                'success' => true,
                'profissional' => [
                    'id' => $profissional['id'],
                    'usuario_id' => $profissional['usuario_id'],
                    'especialidade' => $profissional['especialidade'],
                    'registro_profissional' => $profissional['registro_profissional'],
                    'unidade_saude' => $profissional['unidade_saude'],
                    'tipo_usuario' => $profissional['tipo_usuario'],
                    'micro_area' => $profissional['micro_area']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Profissional não encontrado"
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Erro ao buscar profissional"
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => "ID não fornecido"
    ]);
}

