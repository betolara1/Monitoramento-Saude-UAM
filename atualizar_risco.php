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
            if (!isset($_POST[$campo])) {
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
                        probabilidade = ?,
                        data_calculo = NOW()
                    WHERE id = ?";

            $stmt = $conn->prepare($query);
            
            // Tratar a probabilidade antes de salvar
            $probabilidade = $_POST['probabilidade'];
            if (strpos($probabilidade, '≥') !== false) {
                $probabilidade = '≥30';
            } elseif (strpos($probabilidade, '<') !== false) {
                $probabilidade = '<1';
            }

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
                $probabilidade,
                $_POST['risco_id']
            );

            if ($stmt->execute()) {
                // Buscar o registro atualizado para retornar
                $query_select = "SELECT 
                                    rs.*,
                                    DATE_FORMAT(rs.data_calculo, '%d/%m/%Y') as data_formatada,
                                    DATE_FORMAT(rs.data_calculo, '%Y-%m-%d') as data_calculo_iso
                                FROM riscos_saude rs 
                                WHERE rs.id = ?";
                
                $stmt_select = $conn->prepare($query_select);
                $stmt_select->bind_param("i", $_POST['risco_id']);
                $stmt_select->execute();
                $resultado = $stmt_select->get_result()->fetch_assoc();

                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Risco atualizado com sucesso',
                    'risco' => [
                        'id' => (int)$resultado['id'],
                        'data_formatada' => $resultado['data_formatada'],
                        'pontuacao' => (int)$resultado['pontuacao'],
                        'probabilidade' => $resultado['probabilidade'],
                        'sexo' => $resultado['sexo'],
                        'idade' => $resultado['idade'],
                        'colesterol_total' => (int)$resultado['colesterol_total'],
                        'colesterol_hdl' => (int)$resultado['colesterol_hdl'],
                        'pressao_sistolica' => (int)$resultado['pressao_sistolica'],
                        'fumante' => $resultado['fumante'],
                        'remedios_hipertensao' => $resultado['remedios_hipertensao']
                    ]
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
        if (isset($stmt)) $stmt->close();
        if (isset($stmt_select)) $stmt_select->close();
        $conn->close();
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método de requisição inválido'
    ]);
}
?> 