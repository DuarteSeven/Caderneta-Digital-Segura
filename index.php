<?php
session_start();

include 'config.php';

$conn = new mysqli('localhost', 'root', '', 'caderneta');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$admin = 0;
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT admin FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($admin);
        $stmt->fetch();
    }
    $stmt->close();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['passphrase'])) {
    storePassphrase($_POST['passphrase']);
    echo json_encode(['success' => true, 'message' => 'Passphrase stored in memory']);
    exit;
}

$passvalue = getPassphrase();


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderneta Segura</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
    function showPassphrasePopup() {
        var userPassphrase = prompt("Please enter the passphrase to decrypt the private key:");
        if (userPassphrase !== null) {
            var xhttp = new XMLHttpRequest();
            xhttp.open("POST", "<?php echo $_SERVER['PHP_SELF']; ?>", true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.onreadystatechange = function() {
                if (xhttp.readyState == 4 && xhttp.status == 200) {
                    alert("Passphrase has been successfully stored in memory.");
                }
            };
            xhttp.send("passphrase=" + userPassphrase);
        }
    }


    <?php if ($admin == 1 && $passvalue === null): ?>
        window.onload = function() {
            showPassphrasePopup();
        };
    <?php endif; ?>
    </script>
</head>
<body>

<div class="navbar">
        <a href="index.php">Home</a>
        <a href="cadernetas.php">Cadernetas</a>
        <a href="<?php echo isset($_SESSION['username']) ? 'profile.php' : 'login.php'; ?>">
            Profile
            <span>
                <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Not logged in'; ?>
            </span>
        </a>
        <?php if ($admin == 1): ?>
            <a href="admin_dashboard.php" class="admin-link">Admin</a>
        <?php endif; ?>
        <?php if ($admin == 1): ?>
            <a href="aprovar.php">Aprovar Cromos</a>
        <?php endif; ?>
    </div>

    <div class="container">
    <h1>Caderneta Digital Segura</h1>
    <?php
        echo "O seu gestor de cadernetas digitais. Uma simples forma de interagir com outros partilhando e criando itens digitais.";
        /*echo "<br><strong>Passphrase:</strong> " . htmlspecialchars($passvalue ?? "Não definida");*/
    ?>
    <p></p>
</div>

</body>
</html>