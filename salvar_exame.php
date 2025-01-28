<?php
session_start();
require_once 'conexao.php';

// Habilitar log de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    // Validar dados obrigatórios
    if (empty($_POST['paciente_id'])) {
        throw new Exception('ID do paciente não fornecido');
    }
    if (empty($_POST['data_exame'])) {
        throw new Exception('Data do exame não fornecida');
    }
    if (empty($_POST['tipo_exame'])) {
        throw new Exception('Tipo de exame não fornecido');
    }

    $paciente_id = intval($_POST['paciente_id']);
    $exame_id = !empty($_POST['exame_id']) ? intval($_POST['exame_id']) : null;
    $data_exame = $_POST['data_exame'];
    $tipo_exame = $_POST['tipo_exame'];
    $resultado = !empty($_POST['resultado']) ? $_POST['resultado'] : '';
    $arquivo_exame = null;

    // Processar upload de arquivo
    if (!empty($_FILES['arquivo_exame']['name'])) {
        // Criar diretório se não existir
        $upload_dir = 'uploads/exames/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception('Erro ao criar diretório de upload');
            }
        }

        // Validar extensão
        $file_extension = strtolower(pathinfo($_FILES['arquivo_exame']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];

        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Tipo de arquivo não permitido. Apenas PDF e imagens são aceitos.');
        }

        // Gerar nome único para o arquivo
        $new_filename = uniqid('exame_') . '_' . $paciente_id . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        // Fazer upload
        if (!move_uploaded_file($_FILES['arquivo_exame']['tmp_name'], $upload_path)) {
            throw new Exception('Erro ao fazer upload do arquivo');
        }

        $arquivo_exame = $upload_path;
    }

    // Se for atualização, manter arquivo existente se não foi enviado novo
    if ($exame_id && empty($_FILES['arquivo_exame']['name'])) {
        $query_arquivo = "SELECT arquivo_exame FROM exames WHERE id = ? AND paciente_id = ?";
        $stmt = $conn->prepare($query_arquivo);
        $stmt->bind_param("ii", $exame_id, $paciente_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $arquivo_exame = $row['arquivo_exame'];
        }
    }

    if ($exame_id) {
        // Se há um novo arquivo, buscar e excluir o arquivo antigo
        if (!empty($_FILES['arquivo_exame']['name'])) {
            $query_arquivo_antigo = "SELECT arquivo_exame FROM exames WHERE id = ? AND paciente_id = ?";
            $stmt = $conn->prepare($query_arquivo_antigo);
            $stmt->bind_param("ii", $exame_id, $paciente_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $arquivo_antigo = $row['arquivo_exame'];
                // Verifica se existe um arquivo antigo e o exclui
                if (!empty($arquivo_antigo) && file_exists($arquivo_antigo)) {
                    unlink($arquivo_antigo); // Remove o arquivo físico
                }
            }
            $stmt->close();
        }

        // UPDATE
        $query = "UPDATE exames SET 
                    data_exame = ?,
                    tipo_exame = ?,
                    resultado = ?,
                    arquivo_exame = ?
                 WHERE id = ? AND paciente_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssii", 
            $data_exame,
            $tipo_exame,
            $resultado,
            $arquivo_exame,
            $exame_id,
            $paciente_id
        );
    } else {
        // INSERT
        $query = "INSERT INTO exames 
                    (paciente_id, data_exame, tipo_exame, resultado, arquivo_exame) 
                 VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issss", 
            $paciente_id,
            $data_exame,
            $tipo_exame,
            $resultado,
            $arquivo_exame
        );
    }

    if (!$stmt->execute()) {
        throw new Exception("Erro ao salvar exame: " . $stmt->error);
    }

    $id = $exame_id ?: $conn->insert_id;

    echo json_encode([
        'success' => true,
        'message' => $exame_id ? 'Exame atualizado com sucesso' : 'Exame salvo com sucesso',
        'id' => $id,
        'arquivo' => $arquivo_exame
    ]);

} catch (Exception $e) {
    error_log("ERRO: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 
