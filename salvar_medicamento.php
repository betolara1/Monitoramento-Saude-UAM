<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tipo_usuario']) || 
    ($_SESSION['tipo_usuario'] !== 'Admin' && $_SESSION['tipo_usuario'] !== 'Profissional')) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $medicamento_id = !empty($_POST['medicamento_id']) ? intval($_POST['medicamento_id']) : null;
        $paciente_id = intval($_POST['paciente_id']);
        $nome_medicamento = $_POST['nome_medicamento'];
        $dosagem = $_POST['dosagem'];
        $frequencia = $_POST['frequencia'];
        $data_inicio = $_POST['data_inicio'];
        $data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;
        $observacoes = $_POST['observacoes'];

        if ($medicamento_id) {
            // Atualização
            $query = "UPDATE medicamentos SET 
                        nome_medicamento = ?, 
                        dosagem = ?, 
                        frequencia = ?, 
                        data_inicio = ?, 
                        data_fim = ?, 
                        observacoes = ? 
                     WHERE id = ? AND paciente_id = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssssii", 
                $nome_medicamento, 
                $dosagem, 
                $frequencia, 
                $data_inicio, 
                $data_fim, 
                $observacoes, 
                $medicamento_id, 
                $paciente_id
            );
        } else {
            // Inserção
            $query = "INSERT INTO medicamentos (
                        paciente_id, 
                        nome_medicamento, 
                        dosagem, 
                        frequencia, 
                        data_inicio, 
                        data_fim, 
                        observacoes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("issssss", 
                $paciente_id, 
                $nome_medicamento, 
                $dosagem, 
                $frequencia, 
                $data_inicio, 
                $data_fim, 
                $observacoes
            );
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Erro ao salvar medicamento');
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?> 