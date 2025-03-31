<?php
$conn = new mysqli('localhost', 'root', '', 'caderneta');

// Include the config.php for passphrase handling
include 'config.php';

global $passvalue;
$passvalue = getPassphrase();

// If the passphrase isn't set, return an error
if (!isset($passvalue) || empty($passvalue)) {
    echo json_encode(['success' => false, 'error' => 'Passphrase is not set.']);
    exit();
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$cadernetaid = isset($_GET['cadernetaid']) ? intval($_GET['cadernetaid']) : 0;
$key = isset($_GET['key']) ? $_GET['key'] : '';

if (empty($key) || $cadernetaid <= 0) {
    die('Invalid request');
}

// Step 2: Validate the share link
$stmtCheckLink = $conn->prepare("SELECT * FROM sharelinks WHERE cadernetaid = ? AND link LIKE ? AND (expiration IS NULL OR expiration > ?)");
$linkWithKey = "%key=$key";
$currentTime = time();
$stmtCheckLink->bind_param("isi", $cadernetaid, $linkWithKey, $currentTime);
$stmtCheckLink->execute();
$linkResult = $stmtCheckLink->get_result();

if ($linkResult->num_rows <= 0) {
    die('Link inválido ou expirado!');
}

$linkData = $linkResult->fetch_assoc();
$cardid = $linkData['cardid'];

// Start the session to check if the user is logged in
session_start();
if (!isset($_SESSION['username'])) {
    die('You must be logged in to add a cromo');
}

$username = $_SESSION['username'];

// Step 1: Check if the user has already used the link
$stmtCheckLinkUsage = $conn->prepare("SELECT * FROM linkusers WHERE link = ? AND username = ?");
if ($stmtCheckLinkUsage === false) {
    die('MySQL prepare error: ' . $conn->error);
}

$stmtCheckLinkUsage->bind_param("ss", $key, $username);
$stmtCheckLinkUsage->execute();
$linkUsageResult = $stmtCheckLinkUsage->get_result();

if ($linkUsageResult->num_rows > 0) {
    die('Já usou este link!');
}

// Step 3: Mark the link as used by the current user
$stmtMarkLinkUsed = $conn->prepare("INSERT INTO linkusers (link, username) VALUES (?, ?)");
if ($stmtMarkLinkUsed === false) {
    die('MySQL prepare error: ' . $conn->error);
}

$stmtMarkLinkUsed->bind_param("ss", $key, $username);
$stmtMarkLinkUsed->execute();

// Step 4: Check caderneta
$sqlCheckCaderneta = "SELECT * FROM usercaderneta WHERE cadernetaid = ? AND username = ? AND own = 0";
$stmtCheckCaderneta = $conn->prepare($sqlCheckCaderneta);
$stmtCheckCaderneta->bind_param("is", $cadernetaid, $username);
$stmtCheckCaderneta->execute();
$cadernetaResult = $stmtCheckCaderneta->get_result();

if ($cadernetaResult->num_rows <= 0) {
    echo "Caderneta não encontrada ou você já possui ela!";
    $stmtCheckCaderneta->close();
    $stmtCheckLink->close();
    $stmtCheckLinkUsage->close();
    $stmtMarkLinkUsed->close();
    $conn->close();
    exit();
}

// Step 5: Fetch card details
$sqlGetCardDetails = "SELECT cardname, cardimage, carddescription FROM card WHERE cardid = ?";
$stmtGetCardDetails = $conn->prepare($sqlGetCardDetails);
$stmtGetCardDetails->bind_param("i", $cardid);
$stmtGetCardDetails->execute();
$cardDetailsResult = $stmtGetCardDetails->get_result();

if ($cardDetailsResult->num_rows <= 0) {
    echo "Cromo original não encontrado!";
    $stmtCheckCaderneta->close();
    $stmtGetCardDetails->close();
    $stmtCheckLink->close();
    $stmtCheckLinkUsage->close();
    $stmtMarkLinkUsed->close();
    $conn->close();
    exit();
}

$cardDetails = $cardDetailsResult->fetch_assoc();

// Path to the private key
$privateKeyPath = 'keys/server_private_key_private.pem';
$privateKeyContents = file_get_contents($privateKeyPath);

if ($privateKeyContents === false) {
    die("Unable to read private key.");
}

// Load the private key using the passphrase
$privateKeyResource = openssl_pkey_get_private($privateKeyContents, $passvalue);

if ($privateKeyResource === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to decrypt private key. Invalid passphrase.']);
    exit();
}

// Extract the public key from the private key
$publicKeyDetails = openssl_pkey_get_details($privateKeyResource);
$publicKey = $publicKeyDetails['key'];

// Step 6: Insert new card
$sqlInsertNewCard = "INSERT INTO card (cardname, cardimage, carddescription, pubkeyserver) VALUES (?, ?, ?, ?)";
$stmtInsertNewCard = $conn->prepare($sqlInsertNewCard);
$stmtInsertNewCard->bind_param("ssss", $cardDetails['cardname'], $cardDetails['cardimage'], $cardDetails['carddescription'], $publicKey);
$stmtInsertNewCard->execute();

$newCardid = $stmtInsertNewCard->insert_id;

// Step 7: Insert card into caderneta
$sqlInsertCardCaderneta = "INSERT INTO cardcaderneta (cardid, cadernetaid) VALUES (?, ?)";
$stmtInsertCardCaderneta = $conn->prepare($sqlInsertCardCaderneta);
$stmtInsertCardCaderneta->bind_param("ii", $newCardid, $cadernetaid);
$stmtInsertCardCaderneta->execute();

// Step 8: Add card to user's collection
$sqlInsertCollectedCard = "INSERT INTO collectedcard (username, cardid) VALUES (?, ?)";
$stmtInsertCollectedCard = $conn->prepare($sqlInsertCollectedCard);
$stmtInsertCollectedCard->bind_param("si", $username, $newCardid);
$stmtInsertCollectedCard->execute();

echo "Novo cromo adicionado à sua caderneta com sucesso!";

$stmtCheckCaderneta->close();
$stmtGetCardDetails->close();
$stmtInsertNewCard->close();
$stmtInsertCardCaderneta->close();
$stmtInsertCollectedCard->close();
$stmtCheckLink->close();
$stmtCheckLinkUsage->close();
$stmtMarkLinkUsed->close();
$conn->close();

// Redirect to cadernetas.php with the cadernetaid
header("Location: cadernetas.php?cadernetaid=$cadernetaid");
exit();
?>
