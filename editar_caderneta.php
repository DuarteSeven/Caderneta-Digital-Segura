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

$selectedCadernetaId = isset($_GET['cadernetaid']) ? intval($_GET['cadernetaid']) : null;

$caderneta = null;
$cromos = [];  

if ($selectedCadernetaId) {
    $stmt = $conn->prepare("SELECT caderneta.nomecaderneta, caderneta.tema, caderneta.cadernetapfp 
                            FROM caderneta WHERE caderneta.cadernetaid = ?");
    $stmt->bind_param("i", $selectedCadernetaId);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($nomecaderneta, $tema, $cadernetapfp);
    if ($stmt->fetch()) {
        $caderneta = [
            'nomecaderneta' => $nomecaderneta,
            'tema' => $tema,
            'cadernetapfp' => $cadernetapfp
        ];
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT card.cardid, card.cardname, card.cardimage 
                            FROM card 
                            JOIN cardcaderneta ON card.cardid = cardcaderneta.cardid
                            WHERE cardcaderneta.cadernetaid = ?");
    $stmt->bind_param("i", $selectedCadernetaId);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($cardid, $cardname, $cardimage);
    
    while ($stmt->fetch()) {
        $cromos[] = [
            'cardid' => $cardid,
            'cardname' => $cardname,
            'cardimage' => $cardimage
        ];
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_caderneta'])) {
    $nomecaderneta = $_POST['nomecaderneta'];
    $tema = $_POST['tema'];
    $cadernetapfp = $_POST['cadernetapfp']; 


    $stmt = $conn->prepare("UPDATE caderneta 
                            SET nomecaderneta = ?, tema = ?, cadernetapfp = ? 
                            WHERE cadernetaid = ?");
    $stmt->bind_param("sssi", $nomecaderneta, $tema, $cadernetapfp, $selectedCadernetaId);
    $stmt->execute();
    $stmt->close();


    header('Location: cadernetas.php?cadernetaid=' . $selectedCadernetaId);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_caderneta'])) {

    $stmt = $conn->prepare("DELETE FROM cardcaderneta WHERE cadernetaid = ?");
    $stmt->bind_param("i", $selectedCadernetaId);
    $stmt->execute();
    $stmt->close();


    $stmt = $conn->prepare("DELETE FROM caderneta WHERE cadernetaid = ?");
    $stmt->bind_param("i", $selectedCadernetaId);
    $stmt->execute();
    $stmt->close();


    $stmt = $conn->prepare("DELETE FROM usercaderneta WHERE cadernetaid = ?");
    $stmt->bind_param("i", $selectedCadernetaId);
    $stmt->execute();
    $stmt->close();

    header('Location: cadernetas.php');
    exit();
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
?>

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Caderneta</title>
    <link rel="stylesheet" href="css/style.css">
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
            <a href="admin_dashboard.php" class="admin-link">
                Admin
            </a>
        <?php endif; ?>
    </div>

    <div class="container">
        <h1>Edit Caderneta</h1>

        <form action="editar_caderneta.php?cadernetaid=<?php echo htmlspecialchars($selectedCadernetaId); ?>" method="post">
            <label for="nomecaderneta">Caderneta Name</label>
            <input type="text" id="nomecaderneta" name="nomecaderneta" value="<?php echo htmlspecialchars($caderneta['nomecaderneta']); ?>" required>

            <label for="tema">Tema</label>
            <input type="text" id="tema" name="tema" value="<?php echo htmlspecialchars($caderneta['tema']); ?>" required>

            <label for="cadernetapfp">Select Cromo for Profile Picture</label>
            <select id="cadernetapfp" name="cadernetapfp" required>
                <option value="">Select a Cromo</option>
                <?php foreach ($cromos as $cromo): ?>
                    <option value="<?php echo htmlspecialchars($cromo['cardimage']); ?>" <?php echo ($caderneta['cadernetapfp'] === $cromo['cardimage']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cromo['cardname']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" name="update_caderneta">Save Changes</button>
        </form>

        <form method="POST" onsubmit="return confirm('Tem certeza de que deseja excluir esta caderneta?');">
            <button type="submit" name="delete_caderneta" style="padding: 10px 20px; font-size: 16px; background-color: red; color: white;">Excluir Caderneta</button>
        </form>
        
    </div>

</body>
</html>

<?php $conn->close(); ?>
