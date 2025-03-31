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

$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT email, pfp FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($email, $pfp);
$stmt->fetch();
$stmt->close();

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $username_to_delete = $_SESSION['username'];
    $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
    $stmt->bind_param("s", $username_to_delete);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        session_destroy(); // Destroy the session after deletion
        header('Location: login.php');
        exit();
    } else {
        echo "Error deleting user.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css"> 
    <style>

    </style>
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
        <h1>Bem Vindo, <?php echo htmlspecialchars($username); ?>!</h1>
        <p>Email: <?php echo htmlspecialchars($email); ?></p>

        <div class="profile-pic-container">
            <?php 
                $profile_picture_path = "pfps/" . $username . ".png"; 
                if (file_exists($profile_picture_path)): 
            ?>
                <img src="<?php echo $profile_picture_path; ?>" alt="Profile Picture" width="150">
            <?php else: ?>
                <img src="pfps/default.png" alt="Profile Picture" width="150">
            <?php endif; ?>
        </div>

        <br>
        <!-- Edit User Button -->
        <a href="editar_user.php?username=<?php echo urlencode($_SESSION['username']); ?>" 
        class="edit-user-button"
        style="background-color: white; color: #333; border: 1px solid #ddd; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block; transition: all 0.3s ease;">
            Editar Perfil
        </a>
        
        <!-- Delete Account Button -->
        <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This cannot be undone.');">
            <input type="hidden" name="delete_user" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
            <button type="submit" class="delete-account-button">Apagar Conta</button>
        </form>

        <br>
        <a href="logout.php" class="purple-button">Logout</a>
    </div>

</body>
</html>