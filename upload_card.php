<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Include the config.php for passphrase handling
include 'config.php';

global $passvalue;
$passvalue = getPassphrase();

// If the passphrase isn't set, redirect to some secure page where it will be entered
if (!isset($passvalue) || empty($passvalue)) {
    echo json_encode(['success' => false, 'error' => 'Passphrase is not set.']);
    exit();
}

// Ensure global variable for passphrase is accessible

header('Content-Type: application/json');

$conn = new mysqli('localhost', 'root', '', 'caderneta');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['imageUrl']) && isset($_POST['cadernetaid']) && isset($_POST['name']) && isset($_POST['description'])) {
    $imageUrl = $_POST['imageUrl'];
    $cadernetaid = intval($_POST['cadernetaid']);
    $name = $_POST['name'];
    $description = $_POST['description'];

    // Validate input
    if (!$cadernetaid || empty($imageUrl) || empty($name) || empty($description)) {
        echo json_encode(['success' => false, 'error' => 'Invalid data provided.']);
        exit();
    }

    // Check if the card name already exists
    $stmt_check = $conn->prepare("SELECT cardid FROM card WHERE cardname = ?");
    $stmt_check->bind_param("s", $name);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'A card with this name already exists. Please choose a different name.']);
        $stmt_check->close();
        exit();
    }

    // Path to the private key
    $privateKeyPath = 'keys/server_private_key_private.pem';
    $privateKeyContents = file_get_contents($privateKeyPath);

    if ($privateKeyContents === false) {
        die("Unable to read private key.");
    }

    // Try to load the private key using the passphrase
    $privateKeyResource = openssl_pkey_get_private($privateKeyContents, $passvalue);

    if ($privateKeyResource === false) {
        echo json_encode(['success' => false, 'error' => 'Failed to decrypt private key. Invalid passphrase.']);
        exit();
    }

    // Extract the public key from the private key
    $publicKeyDetails = openssl_pkey_get_details($privateKeyResource);
    $publicKey = $publicKeyDetails['key'];

    // Insert the card into the database
    $aprovado = 0; // Default value for 'aprovado' column
    $stmt = $conn->prepare("INSERT INTO card (cardname, carddescription, cardimage, pubkeyserver, aprovado) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $description, $imageUrl, $publicKey, $aprovado);

    if ($stmt->execute()) {
        $cardId = $stmt->insert_id;

        // Link the card to the caderneta
        $linkStmt = $conn->prepare("INSERT INTO cardcaderneta (cardid, cadernetaid) VALUES (?, ?)");
        $linkStmt->bind_param("ii", $cardId, $cadernetaid);

        if ($linkStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Card criada com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to link card to the caderneta.']);
        }

    } else {
        echo json_encode(['success' => false, 'error' => 'Error saving card to the database.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid submission.']);
}

$conn->close();
?>
