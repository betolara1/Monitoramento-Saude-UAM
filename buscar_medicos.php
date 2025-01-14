<?php
include "conexao.php";

// Buscar todos os profissionais de saúde e os dados relacionados
$sql = "
    SELECT 
        p.id AS profissional_id,
        u.nome AS nome_profissional,
        p.especialidade
    FROM profissionais p
    INNER JOIN usuarios u ON p.usuario_id = u.id
    ORDER BY u.nome
";

$result = $conn->query($sql);

$medicos = [];
while ($row = $result->fetch_assoc()) {
    $medicos[] = [
        'id' => $row['profissional_id'],
        'nome' => $row['nome_profissional'],
        'especialidade' => $row['especialidade']
    ];
}

// Retornar dados como JSON
header('Content-Type: application/json');
echo json_encode($medicos);
?>
