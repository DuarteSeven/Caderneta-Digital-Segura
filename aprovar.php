<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'caderneta');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['username'])) {
    die("Access denied.");
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

if ($admin != 1) {
    header('Location: login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cardid'])) {
    $card_id = intval($_POST['cardid']);
    $update_stmt = $conn->prepare("UPDATE card SET aprovado = 1 WHERE cardid = ?");
    $update_stmt->bind_param("i", $card_id);
    $update_stmt->execute();
    $update_stmt->close();
}

// Fetch cards pending approval
$cards = [];
$result = $conn->query("SELECT cardid, cardname, carddescription, cardimage FROM card WHERE aprovado = 0");
while ($row = $result->fetch_assoc()) {
    $cards[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Approve Cards</title>
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
            <a href="admin_dashboard.php" class="admin-link">Admin</a>
        <?php endif; ?>
        <?php if ($admin == 1): ?>
            <a href="aprovar.php">Aprovar Cromos</a>
        <?php endif; ?>
    </div>

    <h1>Aprovação de Cromos</h1>

    <?php if (empty($cards)): ?>
        <p>Não existem cromos a espera de aprovação.</p>
    <?php else: ?>
        <table border="1">
            <tr>
                <th>Imagem</th>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Ação</th>
            </tr>
            <?php foreach ($cards as $card): ?>
                <tr>
                    <td>
                        <img src="<?php echo htmlspecialchars($card['cardimage']); ?>" 
                             alt="Card Image" 
                             style="width: 50%; height: auto;">
                    </td>
                    <td><?php echo htmlspecialchars($card['cardname']); ?></td>
                    <td><?php echo htmlspecialchars($card['carddescription']); ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="cardid" value="<?php echo $card['cardid']; ?>">
                            <button type="submit">Aprovar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>
