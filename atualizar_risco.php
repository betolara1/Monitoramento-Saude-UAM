<?php
require_once 'conexao.php';
require_once 'verificar_login.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar se todos os campos necessários foram enviados
        $campos_requeridos = [
            'risco_id', 'sexo', 'idade', 
            'colesterol_total', 'colesterol_hdl', 'pressao_sistolica',
            'fumante', 'remedios_hipertensao', 'pontuacao', 'probabilidade'
        ];

        foreach ($campos_requeridos as $campo) {
            if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
                throw new Exception("Campo obrigatório não fornecido: $campo");
            }
        }

        // Início da transação
        $conn->begin_transaction();

        try {
            // Atualizar o registro de risco
            $query = "UPDATE riscos_saude SET 
                        sexo = ?,
                        idade = ?,
                        colesterol_total = ?,
                        colesterol_hdl = ?,
                        pressao_sistolica = ?,
                        fumante = ?,
                        remedios_hipertensao = ?,
                        pontuacao = ?,
                        probabilidade = ?
                    WHERE id = ?";

            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "ssiiissisi",
                $_POST['sexo'],
                $_POST['idade'],
                $_POST['colesterol_total'],
                $_POST['colesterol_hdl'],
                $_POST['pressao_sistolica'],
                $_POST['fumante'],
                $_POST['remedios_hipertensao'],
                $_POST['pontuacao'],
                $_POST['probabilidade'],
                $_POST['risco_id']
            );

            if ($stmt->execute()) {
                $conn->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Risco atualizado com sucesso'
                ]);
            } else {
                throw new Exception('Erro ao atualizar risco');
            }

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro: ' . $e->getMessage()
        ]);
    } finally {
        // Fechar conexões
        if (isset($stmt)) {
            $stmt->close();
        }
        $conn->close();
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método de requisição inválido'
    ]);
}
?> 