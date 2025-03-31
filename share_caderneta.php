<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'caderneta');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cadernetaid = isset($_POST['cadernetaid']) ? intval($_POST['cadernetaid']) : null;
    $expiration = isset($_POST['expiration']) ? intval($_POST['expiration']) : null;

    if ($cadernetaid && $expiration > 0) {
        $key = bin2hex(random_bytes(16));  
        $expiration_time = date("Y-m-d H:i:s", time() + $expiration - 3600);
        $link = "cadernetadigital.hopto.org/insert_shared_caderneta.php?cadernetaid=$cadernetaid&key=$key";

        $stmt = $conn->prepare("INSERT INTO sharelinks (link, key_value, expiration, cadernetaid) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $link, $key, $expiration_time, $cadernetaid);

        if ($stmt->execute()) {
            echo json_encode(['link' => $link]);
        } else {
            echo json_encode(['error' => 'Erro ao gerar o link.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['error' => 'Caderneta não encontrada ou validade inválida.']);
    }
} else {
    echo json_encode(['error' => 'Método inválido.']);
}

$conn->close();
?>
