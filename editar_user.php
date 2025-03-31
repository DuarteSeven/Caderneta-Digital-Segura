<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'caderneta');

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

if (isset($_GET['username'])) {
    $edit_username = $_GET['username'];
    
    $stmt = $conn->prepare("SELECT username, email, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $edit_username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($username, $email, $stored_password);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
    } else {
        echo "User not found!";
        exit();
    }
    $stmt->close();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $new_username = $_POST['username'];
        $new_email = $_POST['email'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // If password is being changed, check if the new passwords match and hash the password
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                echo "Passwords do not match!";
                exit();
            }

            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        } else {
            // If no password change, keep the original password
            $hashed_password = $stored_password;
        }

        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE username = ?");
        $stmt->bind_param("ssss", $new_username, $new_email, $hashed_password, $edit_username);
        
        if ($stmt->execute()) {
            echo "User updated successfully.";
            header("Location: admin_dashboard.php");
            exit();
        } else {
            echo "Error updating user.";
        }
        $stmt->close();
    }
} else {
    echo "No user selected to edit.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/register.css">
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
    </div>

    <div class="container">
        <h1>Edit User: <?php echo htmlspecialchars($username); ?></h1>

        <form method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

            <label for="new_password">Nova Password:</label>
            <input type="password" id="new_password" name="new_password">

            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password">

            <button type="submit">Update User</button>
        </form>
    </div>

</body>
</html>

<?php $conn->close(); ?>
