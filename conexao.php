<?php
// conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medicina";

$conn = new mysqli($servername, $username, $password, $dbname);

// verificar a conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}
?>
