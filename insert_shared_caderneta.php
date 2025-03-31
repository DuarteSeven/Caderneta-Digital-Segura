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

if (isset($_GET['cadernetaid']) && isset($_GET['key'])) {
    $cadernetaid = intval($_GET['cadernetaid']);
    $key = $_GET['key'];

    $stmt = $conn->prepare("SELECT * FROM sharelinks WHERE cadernetaid = ? AND link LIKE ? AND expiration > ?");
    $linkWithKey = "%key=$key"; 

    $stmt->bind_param("isi", $cadernetaid, $linkWithKey, time());
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $username = $_SESSION['username'];
        $own = 0;

        $stmt2 = $conn->prepare("SELECT * FROM usercaderneta WHERE cadernetaid = ? AND username = ?");
        $stmt2->bind_param("is", $cadernetaid, $username);
        $stmt2->execute();
        $userResult = $stmt2->get_result();

        if ($userResult->num_rows === 0) {
            $stmt3 = $conn->prepare("INSERT INTO usercaderneta (cadernetaid, username, own) VALUES (?, ?, ?)");
            $stmt3->bind_param("isi", $cadernetaid, $username, $own);
            if ($stmt3->execute()) {
                header('Location: cadernetas.php');
                exit();  
            } else {
                echo "<p>Erro ao adicionar a caderneta compartilhada.</p>";
            }
            $stmt3->close();
        } else {
            echo "<p>Você já tem acesso a esta caderneta.</p>";
        }

        $stmt2->close();
    } else {
        echo "<p>Link de compartilhamento inválido ou expirado.</p>";
    }

    $stmt->close();
}

$conn->close();
?>
