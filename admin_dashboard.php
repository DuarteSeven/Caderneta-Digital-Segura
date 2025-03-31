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

if ($admin != 1) {
    header('Location: login.php');
    exit();
}

// Fetch all users
$users = [];
$stmt = $conn->prepare("SELECT username FROM users");
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($user);

while ($stmt->fetch()) {
    $users[] = $user;
}
$stmt->close();

if (isset($_POST['delete_user'])) {
    $user_to_delete = $_POST['delete_user'];
    $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
    $stmt->bind_param("s", $user_to_delete);
    if ($stmt->execute()) {
        echo "User deleted successfully.";
    } else {
        echo "Error deleting user.";
    }
    $stmt->close();
}

if (isset($_POST['edit_user'])) {
    $user_to_edit = $_POST['edit_user'];
    header("Location: editar_user.php?username=" . urlencode($user_to_edit));
    exit();
}

$cadernetas = [];
if (isset($_POST['username'])) {
    $selected_user = $_POST['username'];
    $stmt = $conn->prepare("SELECT c.cadernetaid, c.nomecaderneta 
                            FROM caderneta c
                            JOIN usercaderneta uc ON c.cadernetaid = uc.cadernetaid
                            WHERE uc.username = ?");
    $stmt->bind_param("s", $selected_user);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($cadernetaid, $nomecaderneta);

    while ($stmt->fetch()) {
        $cadernetas[] = [
            'cadernetaid' => $cadernetaid,
            'nomecaderneta' => $nomecaderneta
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
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin_dashboard.css">
    <script>
        function loadCadernetas() {
            var user = document.getElementById('user-dropdown').value;
            var form = document.getElementById('caderneta-form');
            form.submit(); 
        }
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
        <h1>Admin Dashboard</h1>
        <h2>Gestão de usuários cadernetas e cromos</h2>

        <form id="caderneta-form" method="post">
            <label for="user-dropdown">Selecionar Utilizador:</label>
            <select id="user-dropdown" name="username" onchange="loadCadernetas()">
                <option value="">-- Selecionar Utilizador --</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo htmlspecialchars($user); ?>" <?php echo isset($selected_user) && $selected_user == $user ? 'selected' : ''; ?>><?php echo htmlspecialchars($user); ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if (isset($selected_user)): ?>
            <div class="user-actions">
                <form method="post">
                    <button type="submit" name="edit_user" value="<?php echo htmlspecialchars($selected_user); ?>">Edit User</button>
                    <button type="submit" name="delete_user" value="<?php echo htmlspecialchars($selected_user); ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete User</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (isset($selected_user) && !empty($cadernetas)): ?>
            <h3>Cadernetas for <?php echo htmlspecialchars($selected_user); ?></h3>
            <label for="caderneta-dropdown">Select Caderneta:</label>
            <select id="caderneta-dropdown">
                <option value="">-- Select Caderneta --</option>
                <?php foreach ($cadernetas as $caderneta): ?>
                    <option value="<?php echo $caderneta['cadernetaid']; ?>"><?php echo htmlspecialchars($caderneta['nomecaderneta']); ?></option>
                <?php endforeach; ?>
            </select>

            <div class="caderneta-actions">
                <button onclick="window.location.href = 'editar_caderneta.php?cadernetaid=' + document.getElementById('caderneta-dropdown').value;">Edit</button>
                <button onclick="window.location.href = 'delete_caderneta.php?cadernetaid=' + document.getElementById('caderneta-dropdown').value;" onclick="return confirm('Tens a certeza que queres apagar esta caderneta?');">Delete</button>
                <button onclick="window.location.href = 'admin_cromo_dashboard.php?cadernetaid=' + document.getElementById('caderneta-dropdown').value;">Gerir Cromos</button>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>

<?php $conn->close(); ?>
