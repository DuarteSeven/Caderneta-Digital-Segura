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

$cardid = isset($_GET['cardid']) ? intval($_GET['cardid']) : null;
$card = null;

if ($cardid) {
    $stmt = $conn->prepare("SELECT cardname, carddescription, cardimage FROM card WHERE cardid = ?");
    $stmt->bind_param("i", $cardid);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($cardname, $carddescription, $cardimage);
    if ($stmt->fetch()) {
        $card = [
            'cardid' => $cardid,
            'cardname' => $cardname,
            'carddescription' => $carddescription,
            'cardimage' => $cardimage
        ];
    }
    $stmt->close();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_card']) && $card) {
    $updatedCardName = $_POST['cardname'];
    $updatedCardDescription = $_POST['carddescription'];
    $updatedCardImage = $_POST['cardimage'];

    $stmt = $conn->prepare("UPDATE card SET cardname = ?, carddescription = ?, cardimage = ? WHERE cardid = ?");
    $stmt->bind_param("sssi", $updatedCardName, $updatedCardDescription, $updatedCardImage, $cardid);
    $stmt->execute();
    $stmt->close();

    header("Location: cadernetas.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_card'])) {
    $stmt = $conn->prepare("DELETE FROM card WHERE cardid = ?");
    $stmt->bind_param("i", $cardid);
    $stmt->execute();
    $stmt->close();

    header("Location: cadernetas.php");
    exit();
}

// Check if user is an admin
$admin = 0; // Default value for non-admin users
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
    <title>Editar Cromo</title>
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
        <h1>Editar Cromo</h1>

        <?php if ($card): ?>
        <form method="POST" action="editar_cromo.php?cardid=<?php echo htmlspecialchars($card['cardid']); ?>" class="form-edit-card">
            <div class="form-group">
                <label for="cardname">Nome do Cromo</label>
                <input type="text" id="cardname" name="cardname" value="<?php echo htmlspecialchars($card['cardname']); ?>" required>
            </div>

            <div class="form-group">
                <label for="carddescription">Descrição</label>
                <textarea id="carddescription" name="carddescription" required><?php echo htmlspecialchars($card['carddescription']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="cardimage">Imagem</label>
                <input type="text" id="cardimage" name="cardimage" value="<?php echo htmlspecialchars($card['cardimage']); ?>" placeholder="URL da imagem">
            </div>

            <div class="form-group">
                <button type="submit" name="update_card">Salvar Alterações</button>
            </div>
        </form>

        <form method="POST" onsubmit="return confirm('Tem certeza de que deseja excluir este cromo?');">
            <div class="form-group">
                <button type="submit" name="delete_card" style="padding: 10px 20px; font-size: 16px; background-color: red; color: white;">
                    Excluir Cromo
                </button>
            </div>
        </form>

        <?php else: ?>
            <p>Cromo não encontrado.</p>
        <?php endif; ?>
    </div>

</body>
</html>


<?php
$conn->close();
?>
