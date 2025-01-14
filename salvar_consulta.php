<?php
session_start();
require_once 'conexao.php';

// Verifica permissão
if (!isset($_SESSION['tipo_usuario']) || 
    ($_SESSION['tipo_usuario'] !== 'Admin' && $_SESSION['tipo_usuario'] !== 'Profissional')) {
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $paciente_id = $_POST['paciente_id'];
        $profissional_id = $_POST['profissional_id'];
        $data_consulta = $_POST['data_consulta'];
        $pressao_arterial = $_POST['pressao_arterial'];
        $glicemia = $_POST['glicemia'];
        $peso = !empty($_POST['peso']) ? $_POST['peso'] : null;
        $altura = !empty($_POST['altura']) ? $_POST['altura'] : null;
        $observacoes = $_POST['observacoes'];

        // Calcula o IMC se peso e altura foram fornecidos
        // Converte altura de centímetros para metros antes do cálculo
        $imc = null;
        if ($peso && $altura) {
            $altura_metros = $altura / 100; // Converte cm para metros
            $imc = $peso / ($altura_metros * $altura_metros);
        }

        $query = "INSERT INTO consultas (
            paciente_id, 
            profissional_id, 
            data_consulta, 
            pressao_arterial, 
            glicemia, 
            peso, 
            altura, 
            imc, 
            observacoes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "iisssddds", 
            $paciente_id, 
            $profissional_id, 
            $data_consulta, 
            $pressao_arterial, 
            $glicemia, 
            $peso, 
            $altura, 
            $imc, 
            $observacoes
        );

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Consulta cadastrada com sucesso',
                'imc' => $imc // Opcional: retorna o IMC calculado
            ]);
        } else {
            throw new Exception('Erro ao cadastrar consulta');
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?> 