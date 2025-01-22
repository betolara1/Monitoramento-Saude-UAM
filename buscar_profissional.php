<?php
include 'conexao.php';
include 'verificar_login.php';

if (isset($_GET['id'])) {
    $profissional_id = $_GET['id'];

    $sql = "SELECT p.id, p.usuario_id, p.especialidade, p.registro_profissional, p.unidade_saude, u.tipo_usuario
            FROM profissionais p
            JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $profissional_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $profissional = $result->fetch_assoc();
        $response = [
            $profissional['tipo_usuario'] => [
                [
                    'id' => $profissional['id'],
                    'especialidade' => $profissional['especialidade'],
                    'registro_profissional' => $profissional['registro_profissional'],
                    'unidade_saude' => $profissional['unidade_saude']
                ]
            ]
        ];
        echo json_encode($response);
    } else {
        echo json_encode(['error' => 'Profissional não encontrado']);
    }
} else {
    echo json_encode(['error' => 'ID não fornecido']);
}

