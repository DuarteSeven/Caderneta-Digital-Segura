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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_caderneta'])) {
    $nomecaderneta = $_POST['nomecaderneta'];
    $tema = $_POST['tema'];

    // Check if the caderneta name already exists
    $stmt_check = $conn->prepare("SELECT cadernetaid FROM caderneta WHERE nomecaderneta = ?");
    $stmt_check->bind_param("s", $nomecaderneta);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $errorMessage = "Caderneta with this name already exists. Please choose a different name.";
    } else {
        $defaultPfp = 'cadernetapfp/default.png';

        $stmt = $conn->prepare("INSERT INTO caderneta (nomecaderneta, tema, cadernetapfp) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nomecaderneta, $tema, $defaultPfp);

        if ($stmt->execute()) {
            $cadernetaid = $stmt->insert_id; 
            
            $stmt_user = $conn->prepare("INSERT INTO usercaderneta (cadernetaid, username, own) VALUES (?, ?, 1)");
            $stmt_user->bind_param("is", $cadernetaid, $_SESSION['username']);
            if ($stmt_user->execute()) {
                $successMessage = "Caderneta created and linked to user successfully!";
                header('Location: cadernetas.php');
                exit();  
            } else {
                $errorMessage = "Error linking caderneta to user: " . $stmt_user->error;
            }
            $stmt_user->close();
        } else {
            $errorMessage = "Error creating caderneta: " . $stmt->error;
        }
        $stmt->close();
    }

    $stmt_check->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Caderneta</title>
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
        <h1>Criar uma nova Caderneta</h1>

        <?php
            if (isset($successMessage)) {
                echo "<p style='color: green;'>$successMessage</p>";
            } elseif (isset($errorMessage)) {
                echo "<p style='color: red;'>$errorMessage</p>";
            }
        ?>

        <form action="createcaderneta.php" method="POST">
            <label for="nomecaderneta">Caderneta Name:</label>
            <input type="text" name="nomecaderneta" required><br>

            <label for="tema">Theme:</label>
            <textarea name="tema" required></textarea><br>

            <button type="submit" name="create_caderneta">Create</button>
        </form>
    </div>

</body>
</html>

<?php $conn->close(); ?>
