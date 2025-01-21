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

// Habilita log de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Log dos dados recebidos
        error_log('Dados POST recebidos: ' . print_r($_POST, true));

        // Validação dos dados recebidos
        if (!isset($_POST['consulta_id'])) {
            throw new Exception('ID da consulta não fornecido');
        }

        $consulta_id = intval($_POST['consulta_id']);
        $profissional_id = intval($_POST['profissional_id']);
        $data_consulta = $_POST['data_consulta'];
        $pressao_arterial = $_POST['pressao_arterial'] ?? null;
        $glicemia = $_POST['glicemia'] ?? null;
        $peso = !empty($_POST['peso']) ? floatval($_POST['peso']) : null;
        $altura = !empty($_POST['altura']) ? floatval($_POST['altura']) : null;
        $observacoes = $_POST['observacoes'] ?? '';

        // Calcula o IMC
        $imc = null;
        $classificacao_imc = null;
        if ($peso && $altura) {
            $altura_metros = $altura / 100;
            $imc = $peso / ($altura_metros * $altura_metros);
            $imc = round($imc, 1);

            // Classificação do IMC
            if ($imc < 18.5) $classificacao_imc = 'Abaixo do peso';
            else if ($imc < 25) $classificacao_imc = 'Peso normal';
            else if ($imc < 30) $classificacao_imc = 'Sobrepeso';
            else if ($imc < 35) $classificacao_imc = 'Obesidade Grau I';
            else if ($imc < 40) $classificacao_imc = 'Obesidade Grau II';
            else $classificacao_imc = 'Obesidade Grau III';
        }

        $query = "UPDATE consultas SET 
            profissional_id = ?, 
            data_consulta = ?, 
            pressao_arterial = ?, 
            glicemia = ?, 
            peso = ?, 
            altura = ?, 
            imc = ?, 
            classificacao_imc = ?, 
            observacoes = ?
            WHERE id = ?";

        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception('Erro na preparação da query: ' . $conn->error);
        }

        $stmt->bind_param(
            "issssssssi", 
            $profissional_id,
            $data_consulta,
            $pressao_arterial,
            $glicemia,
            $peso,
            $altura,
            $imc,
            $classificacao_imc,
            $observacoes,
            $consulta_id
        );

        if (!$stmt->execute()) {
            throw new Exception('Erro na execução da query: ' . $stmt->error);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Consulta atualizada com sucesso',
            'dados_atualizados' => [
                'consulta_id' => $consulta_id,
                'imc' => $imc,
                'classificacao_imc' => $classificacao_imc
            ]
        ]);

    } catch (Exception $e) {
        error_log('Erro na atualização: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erro: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
}
?> 