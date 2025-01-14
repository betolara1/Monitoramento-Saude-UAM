<?php
include "conexao.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_id = $_POST['usuario_id'];
    $especialidade = $_POST['especialidade'];
    $registro_profissional = $_POST['registro_profissional'];
    $unidade_saude = $_POST['unidade_saude'];

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO profissionais (usuario_id, especialidade, registro_profissional, unidade_saude) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $usuario_id, $especialidade, $registro_profissional, $unidade_saude);

    // Execute the statement
    if ($stmt->execute()) {
        // Redirect to a success page or list of professionals
        header("Location: listar_profissionais.php?success=1");
        exit();
    } else {
        // If there's an error, redirect back to the form with an error message
        header("Location: cadastro_profissionais.php?error=1");
        exit();
    }

    $stmt->close();
} else {
    // If the form wasn't submitted, redirect to the form page
    header("Location: cadastro_profissionais.php");
    exit();
}

$conn->close();
?>