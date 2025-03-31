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
// Get the cadernetaid from the query parameter
$cadernetaid = isset($_GET['cadernetaid']) ? intval($_GET['cadernetaid']) : null;

if (!$cadernetaid) {
    echo "Invalid Caderneta ID.";
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submeter Cromo</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/submeter.css">
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
        <h1>Submeter Cromo</h1>

        <form id="submitForm" method="POST" enctype="multipart/form-data">
            <label for="name">Nome:</label>
            <input type="text" id="name" name="name" required>

            <label for="description">Descrição:</label>
            <textarea id="description" name="description" rows="4" required></textarea>

            <label for="file">Imagem:</label>
            <input type="file" id="file" name="file" accept="image/*" required>

            <button type="submit">Gerar</button>
        </form>

        <label for="textColor">Text Color:</label>
        <div id="color-picker">
            <label for="red">Red:</label>
            <input type="range" id="red" name="red" min="0" max="255" value="255">
            <span id="red-value">255</span><br>
            
            <label for="green">Green:</label>
            <input type="range" id="green" name="green" min="0" max="255" value="255">
            <span id="green-value">255</span><br>
            
            <label for="blue">Blue:</label>
            <input type="range" id="blue" name="blue" min="0" max="255" value="255">
            <span id="blue-value">255</span><br>
        </div>


        <div id="result-container" style="margin-top: 20px;">
            <img id="result-image" src="template.png" alt="Customized Caderneta" width="300">
        </div>

        <div id="upload-container" style="display: none; margin-top: 20px;">
            <button id="upload-button">Submeter</button>
        </div>
    </div>

    <script>
    document.getElementById('submitForm').onsubmit = async function(event) {
        event.preventDefault();

        const formData = new FormData(this);

        document.getElementById('result-image').src = "template.png"; 
        const uploadContainer = document.getElementById('upload-container');
        uploadContainer.style.display = 'none'; 

        try {
            const response = await fetch('process_submission.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            console.log(result);  
            if (result.success) {
                const imageUrl = result.imageUrl + '?t=' + new Date().getTime();
                document.getElementById('result-image').src = imageUrl;

                uploadContainer.style.display = 'block';

                document.getElementById('upload-button').onclick = function() {
                    uploadToDatabase(imageUrl);
                };
            } else {
                alert("There was an error: " + result.error);
            }
        } catch (error) {
            console.error("Error:", error);
            alert("Failed to process submission.");
        }
    };

    async function uploadToDatabase(imageUrl) {
        const formData = new FormData();
        formData.append('imageUrl', imageUrl);
        formData.append('cadernetaid', <?php echo json_encode($cadernetaid); ?>); 
        formData.append('name', document.getElementById('name').value); 
        formData.append('description', document.getElementById('description').value); 
        try {
            const response = await fetch('upload_card.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                alert("Card successfully uploaded to the database!");
            } else {
                alert("Failed to upload card to the database: " + result.error);
            }
        } catch (error) {
            console.error("Error:", error);
            alert("Failed to upload card.");
        }
    }

    document.getElementById('red').oninput = function() {
    document.getElementById('red-value').textContent = this.value;
};

document.getElementById('green').oninput = function() {
    document.getElementById('green-value').textContent = this.value;
};

document.getElementById('blue').oninput = function() {
    document.getElementById('blue-value').textContent = this.value;
};

    document.getElementById('submitForm').onsubmit = async function(event) {
        event.preventDefault();

        const formData = new FormData(this);

        // Append RGB values to the formData
        formData.append('red', document.getElementById('red').value);
        formData.append('green', document.getElementById('green').value);
        formData.append('blue', document.getElementById('blue').value);

        document.getElementById('result-image').src = "template.png"; 
        const uploadContainer = document.getElementById('upload-container');
        uploadContainer.style.display = 'none'; 

        try {
            const response = await fetch('process_submission.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            console.log(result);  
            if (result.success) {
                const imageUrl = result.imageUrl + '?t=' + new Date().getTime();
                document.getElementById('result-image').src = imageUrl;

                uploadContainer.style.display = 'block';

                document.getElementById('upload-button').onclick = function() {
                    uploadToDatabase(imageUrl);
                };
            } else {
                alert("There was an error: " + result.error);
            }
        } catch (error) {
            console.error("Error:", error);
            alert("Failed to process submission.");
        }
    };

    </script>

</body>
</html>

