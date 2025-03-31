<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $name = $_POST['name']; 
    $description = $_POST['description']; 

    // Get RGB values
    $red = isset($_POST['red']) ? intval($_POST['red']) : 255;
    $green = isset($_POST['green']) ? intval($_POST['green']) : 255;
    $blue = isset($_POST['blue']) ? intval($_POST['blue']) : 255;

    $templatePath = 'template.png';  
    $uploadDir = 'uploads/';
    $fileName = basename($_FILES['file']['name']);
    $uploadFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
        $templateImage = @imagecreatefrompng($templatePath);
        
        if (!$templateImage) {
            die("Failed to load template image.");
        }

        imagealphablending($templateImage, false);
        imagesavealpha($templateImage, true);

        $uploadedImage = imagecreatefromstring(file_get_contents($uploadFile));
        if (!$uploadedImage) {
            die("Failed to load uploaded image.");
        }

        $uploadedImageWidth = 720; 
        $uploadedImageHeight = 1280; 
        $resizedImage = imagecreatetruecolor($uploadedImageWidth, $uploadedImageHeight);
        imagecopyresampled($resizedImage, $uploadedImage, 0, 0, 0, 0, $uploadedImageWidth, $uploadedImageHeight, imagesx($uploadedImage), imagesy($uploadedImage));

        $imageX = 0;  
        $imageY = 0;

        imagecopy($templateImage, $resizedImage, $imageX, $imageY, 0, 0, $uploadedImageWidth, $uploadedImageHeight);

        // Define the selected text color based on RGB
        $textColor = imagecolorallocate($templateImage, $red, $green, $blue);
        $strokeColor = imagecolorallocate($templateImage, 0, 0, 0);  // Black stroke

        $fontPath = 'Arial.ttf';  

        // Add name with stroke
        $fontSize = 50;
        $x = 24;
        $y = 120;

        // Apply stroke effect by drawing text multiple times with slight offsets
        imagettftext($templateImage, $fontSize, 0, $x - 2, $y - 2, $strokeColor, $fontPath, $name);
        imagettftext($templateImage, $fontSize, 0, $x + 2, $y - 2, $strokeColor, $fontPath, $name);
        imagettftext($templateImage, $fontSize, 0, $x - 2, $y + 2, $strokeColor, $fontPath, $name);
        imagettftext($templateImage, $fontSize, 0, $x + 2, $y + 2, $strokeColor, $fontPath, $name);

        // Now draw the actual text on top of the stroke
        imagettftext($templateImage, $fontSize, 0, $x, $y, $textColor, $fontPath, $name);

        // Add description with stroke
        $fontSize = 30;
        $x = 24;
        $y = 1080;

        // Apply stroke effect for description
        imagettftext($templateImage, $fontSize, 0, $x - 2, $y - 2, $strokeColor, $fontPath, $description);
        imagettftext($templateImage, $fontSize, 0, $x + 2, $y - 2, $strokeColor, $fontPath, $description);
        imagettftext($templateImage, $fontSize, 0, $x - 2, $y + 2, $strokeColor, $fontPath, $description);
        imagettftext($templateImage, $fontSize, 0, $x + 2, $y + 2, $strokeColor, $fontPath, $description);

        // Now draw the actual description text on top of the stroke
        imagettftext($templateImage, $fontSize, 0, $x, $y, $textColor, $fontPath, $description);

        $finalImagePath = $uploadDir . 'final_' . time() . '.png';
        if (!is_writable($uploadDir)) {
            die("The upload directory is not writable.");
        }
        imagepng($templateImage, $finalImagePath);

        imagedestroy($templateImage);
        imagedestroy($uploadedImage);
        imagedestroy($resizedImage);

        echo json_encode(['success' => true, 'imageUrl' => $finalImagePath]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Error uploading file.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid submission.']);
    exit;
}
