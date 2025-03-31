<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'caderneta');

// Check if user is an admin
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

$cadernetaid = isset($_GET['cadernetaid']) ? intval($_GET['cadernetaid']) : 0;
$cromos = [];

if ($cadernetaid > 0) {
    // Buscar todos os cromos da caderneta
    $stmt = $conn->prepare("SELECT card.cardid, card.cardname, card.carddescription, card.cardimage 
                            FROM card 
                            JOIN cardcaderneta ON card.cardid = cardcaderneta.cardid 
                            WHERE cardcaderneta.cadernetaid = ?");
    $stmt->bind_param("i", $cadernetaid);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($cardid, $cardname, $carddescription, $cardimage);

    while ($stmt->fetch()) {
        $cromos[] = [
            'cardid' => $cardid,
            'cardname' => $cardname,
            'carddescription' => $carddescription,
            'cardimage' => $cardimage
        ];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Cromos</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin_dashboard.css">
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
    <h1>Gerir Cromos numa Caderneta</h1>

    <h2>Cromos</h2>

    <div class="cromo-list">
        <?php foreach ($cromos as $cromo): ?>
            <div class="cromo-item">
                <h3><?php echo htmlspecialchars($cromo['cardname']); ?></h3>
                <p><?php echo nl2br(htmlspecialchars($cromo['carddescription'])); ?></p>
                <?php if ($cromo['cardimage']): ?>
                    <img src="<?php echo htmlspecialchars($cromo['cardimage']); ?>" alt="Cromo Image" style="max-width: 150px; max-height: 150px;">
                <?php endif; ?>

                <a href="editar_cromo.php?cardid=<?php echo $cromo['cardid']; ?>&cadernetaid=<?php echo $cadernetaid; ?>">
                    <button>Editar</button>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>
