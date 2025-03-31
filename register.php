<?php
session_start();

if (isset($_SESSION['username'])) {
    header('Location: profile.php'); 
    exit(); 
}

$conn = new mysqli('localhost', 'root', '', 'caderneta');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $errors = [];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "É preciso preencher todos os campos.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email inválido.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "As palavras-passe não são iguais.";
    }

    if (strlen($password) < 6) {
        $errors[] = "A palavra-passe tem que ter pelo menos 6 carateres.";
    }
    

    $stmt = $conn->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($existing_username, $existing_email);
        $stmt->fetch();

        if ($existing_username == $username) {
            $errors[] = "Username already exists.";
        }

        if ($existing_email == $email) {
            $errors[] = "Email already exists.";
        }
    }

    $stmt->close();

    if ($_FILES['pfp']['error'] == 0) {
        $file_extension = pathinfo($_FILES['pfp']['name'], PATHINFO_EXTENSION);
        $target_file = 'pfps/' . $username . '.' . 'png';  

        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (!in_array(strtolower($file_extension), $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG files are allowed.";
        }

        if (empty($errors) && !move_uploaded_file($_FILES['pfp']['tmp_name'], $target_file)) {
            $errors[] = "Error uploading the profile picture.";
        }
        if (empty($errors)) {
            $pfp = $target_file;  
        }
    } else {
        $pfp = "pfps/default.png";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT); 
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, pfp, admin) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $pfp);

        if ($stmt->execute()) {
            $_SESSION['username'] = $username; 
            header("Location: profile.php");
            exit();
        } else {
            $errors[] = "Error registering user: " . $stmt->error;
        }

        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
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
    </div>

    <div class="container">
        <h1>Register</h1>
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" enctype="multipart/form-data">
            <label for="username">Username:</label>
            <input type="text" name="username" required><br>

            <label for="email">Email:</label>
            <input type="email" name="email" required><br>

            <label for="password">Password:</label>
            <input type="password" name="password" required><br>

            <label for="confirm_password">Confirmar Password:</label>
            <input type="password" name="confirm_password" required><br>

            <label for="pfp">Foto de Perfil (Opcional):</label>
            <input type="file" name="pfp" id="pfp" onchange="previewImage(event)"><br>

            <div id="pfp-preview">
                <img src="" alt="Profile Picture Preview">
            </div>

            <button type="submit" name="register">Register</button>
        </form>

        <p>Já possui uma conta? 
        <button type="button" onclick="window.location.href='login.php';">Login</button>.</p>
    </div>

    <script>
        function previewImage(event) {
            const preview = document.querySelector('#pfp-preview img');
            const file = event.target.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
                document.getElementById('pfp-preview').style.display = 'block';
            };
            if (file) {
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>
