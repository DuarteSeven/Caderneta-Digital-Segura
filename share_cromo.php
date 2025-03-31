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
    $cardid = isset($_POST['cardid']) ? intval($_POST['cardid']) : null;
    $cadernetaid = isset($_POST['cadernetaid']) ? intval($_POST['cadernetaid']) : null;
    $expiration = isset($_POST['expiration']) ? intval($_POST['expiration']) : null;

    if ($cardid && $cadernetaid && $expiration > 0) {
        // Generate a random key for the share link.
        $key = bin2hex(random_bytes(16));
        // Calculate the expiration time and format it as DATETIME.
        $expiration_time = date("Y-m-d H:i:s", time() + $expiration - 3600);
        // Build the share link, including cardid, cadernetaid, and the key.
        $link = "cadernetadigital.hopto.org/insert_shared_cromo.php?cardid=$cardid&cadernetaid=$cadernetaid&key=$key";

        // Insert the share link into the sharelinks table, including the key_value column.
        $stmt = $conn->prepare("INSERT INTO sharelinks (link, expiration, cardid, cadernetaid, key_value) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiis", $link, $expiration_time, $cardid, $cadernetaid, $key);

        if ($stmt->execute()) {
            echo json_encode(['link' => $link]);
        } else {
            echo json_encode(['error' => 'Erro ao gerar o link.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['error' => 'Cromo ou caderneta não encontrados ou validade inválida.']);
    }
} else {
    echo json_encode(['error' => 'Método inválido.']);
}

$conn->close();
?>
