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

// Include the config.php for passphrase handling
include 'config.php';

$selectedCadernetaId = isset($_GET['cadernetaid']) ? intval($_GET['cadernetaid']) : null;

$cards = [];
$own = 0;  
if ($selectedCadernetaId) {
    // ver se o utilizador possui cadernetas
    $stmt = $conn->prepare("SELECT own FROM usercaderneta WHERE cadernetaid = ? AND username = ?");
    $stmt->bind_param("is", $selectedCadernetaId, $_SESSION['username']);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($own);
        $stmt->fetch();
    }
    $stmt->close();
    
    // Lista de cromos nac adereneta
    $stmt = $conn->prepare("
        SELECT card.cardid, card.cardname, card.carddescription, card.cardimage, card.pubkeyserver
        FROM card
        JOIN cardcaderneta ON card.cardid = cardcaderneta.cardid
        WHERE cardcaderneta.cadernetaid = ?");
    $stmt->bind_param("i", $selectedCadernetaId);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($cardid, $cardname, $carddescription, $cardimage, $pubkeyserver);

    while ($stmt->fetch()) {
        $cards[] = [
            'cardid' => $cardid,
            'cardname' => $cardname,
            'carddescription' => $carddescription,
            'cardimage' => $cardimage,
            'pubkeyserver' => $pubkeyserver
        ];
    }
    $stmt->close();
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

$stmt = $conn->prepare("SELECT aprovado FROM card WHERE cardid = ?");
$stmt->bind_param("i", $cardid);
$stmt->execute();
$stmt->bind_result($aprovado);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cromo'])) {
    $cardid = isset($_POST['cardid']) ? intval($_POST['cardid']) : null;

    if ($cardid && $own == 1) { 
        $stmt = $conn->prepare("DELETE FROM cardcaderneta WHERE cardid = ? AND cadernetaid = ?");
        $stmt->bind_param("ii", $cardid, $selectedCadernetaId);  
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM card WHERE cardid = ?");
        $stmt->bind_param("i", $cardid);
        $stmt->execute();

        $stmt->close();
        
        header("Location: ?cadernetaid=" . $selectedCadernetaId);  
        exit;
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderneta Segura</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/caderneta.css">
    <script>

        function switchTab(tab) {
            var ownTab = document.getElementById('yourCadernetaSection');
            var otherTab = document.getElementById('otherCadernetaSection');
            var cardTab = document.getElementById('cardSection');
            
            ownTab.style.display = "none";
            otherTab.style.display = "none";
            cardTab.style.display = "none";
            
            if (tab === 'your') {
                ownTab.style.display = "block";
            } else if (tab === 'other') {
                otherTab.style.display = "block";
            } else if (tab === 'cards') {
                cardTab.style.display = "block";
            }
        }

        function goBackToCaderneta() {
            window.location.href = "cadernetas.php"; 
        }

        window.onload = function() {
            <?php if ($selectedCadernetaId): ?>
                switchTab('cards'); 
            <?php else: ?>
                switchTab('your'); 
            <?php endif; ?>
        }

        function openSharePopup(cadernetaid) {
            document.getElementById('sharePopup').style.display = 'block';
            document.getElementById('cadernetaidInput').value = cadernetaid;
        }

        function closeSharePopup() {
            document.getElementById('sharePopup').style.display = 'none';
        }

        function generateLink() {
            const cadernetaid = document.getElementById('cadernetaidInput').value;
            const expiration = document.getElementById('expirationInput').value;

            if (!expiration || expiration <= 0) {
                alert("Por favor, insira um valor de validade válido!");
                return;
            }

            fetch('share_caderneta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `cadernetaid=${encodeURIComponent(cadernetaid)}&expiration=${encodeURIComponent(expiration)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.link) {
                    navigator.clipboard.writeText(data.link).then(() => {
                        alert('Link copiado para a área de transferência: ' + data.link);
                        closeSharePopup();
                    });
                } else {
                    alert('Erro ao gerar o link.');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
            });
        }

    // Opens the share cromo popup and sets both cardid and cadernetaid.
    function openShareCromoPopup(cardid, cadernetaid) {
        document.getElementById('shareCromoPopup').style.display = 'block';
        document.getElementById('cardidInput').value = cardid;
        document.getElementById('cadernetaidForCromoInput').value = cadernetaid;
    }

    // Closes the share cromo popup.
    function closeShareCromoPopup() {
        document.getElementById('shareCromoPopup').style.display = 'none';
    }

    // Sends an AJAX request to share_cromo.php to generate the share link.
    function generateCromoLink() {
        const cardid = document.getElementById('cardidInput').value;
        const cadernetaid = document.getElementById('cadernetaidForCromoInput').value;
        const expiration = document.getElementById('cromoExpirationInput').value;
        if (!expiration || expiration <= 0) {
            alert("Por favor, insira um valor de validade válido!");
            return;
        }
        fetch('share_cromo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `cardid=${encodeURIComponent(cardid)}&cadernetaid=${encodeURIComponent(cadernetaid)}&expiration=${encodeURIComponent(expiration)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.link) {
                navigator.clipboard.writeText(data.link).then(() => {
                    alert('Link copiado para a área de transferência: ' + data.link);
                    closeShareCromoPopup();
                });
            } else {
                alert('Erro ao gerar o link: ' + (data.error ? data.error : 'Desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao gerar o link.');
        });
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
        <h1>Cadernetas</h1>

        <div class="tab-buttons">
            <button onclick="switchTab('your')" style="padding: 10px 20px; margin-right: 10px;">Minhas Cadernetas</button>
            <button onclick="switchTab('other')" style="padding: 10px 20px;">Outras Cadernetas</button>
        </div>

        
    <!-- As tuas cadernetas -->
    <div id="yourCadernetaSection" style="display:none;">
        <h2>As minhas cadernetas</h2>
        <?php
        $stmt = $conn->prepare("SELECT caderneta.cadernetaid, caderneta.nomecaderneta, caderneta.tema, caderneta.cadernetapfp 
                                FROM caderneta
                                JOIN usercaderneta ON caderneta.cadernetaid = usercaderneta.cadernetaid
                                WHERE usercaderneta.username = ? AND usercaderneta.own = 1");
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<div>
                    <a href='?cadernetaid=" . $row['cadernetaid'] . "'><h3>" . htmlspecialchars($row['nomecaderneta']) . "</h3></a>
                    <p>" . htmlspecialchars($row['tema']) . "</p>";
                if ($row['cadernetapfp']) {
                    echo "<img src='" . htmlspecialchars($row['cadernetapfp']) . "' alt='Caderneta Profile Picture' style='max-width: 150px; max-height: 150px;'>"; 
                }
                // Butoes para as minhas cadernetas
                echo "<div>
                        </a>
                            <button onclick='openSharePopup(" . $row['cadernetaid'] . ")' style='padding: 5px 15px; font-size: 14px; background-color: #2196F3; color: white;'>Compartilhar Caderneta</button>
                        </a>
                        <a href='editar_caderneta.php?cadernetaid=" . $row['cadernetaid'] . "'>
                            <button style='padding: 5px 15px; font-size: 14px; background-color: #4CAF50; color: white;'>Editar Caderneta</button>
                        </a>
                        <!-- Add the Submit Cromo button -->
                        <a href='submeter.php?cadernetaid=" . $row['cadernetaid'] . "'>
                            <button style='padding: 5px 15px; font-size: 14px; background-color: #FFC107; color: white;'>Submeter Cromo</button>
                        </a>
                    </div>";
                echo "</div>";
            }
        } else {
            echo "<p>Ainda não criou uma caderneta.</p>";
        }
        $stmt->close();
        ?>

        <div style="margin-top: 20px;">
            <a href="createcaderneta.php">
                <button style="padding: 10px 20px; font-size: 16px;">Criar Nova Caderneta</button>
            </a>
        </div>
    </div>


    
    <!-- Outras Cadernetas -->
    <div id="otherCadernetaSection" style="display:none;">
        <h2>Outras cadernetas</h2>
        <?php
        $stmt = $conn->prepare("SELECT caderneta.cadernetaid, caderneta.nomecaderneta, caderneta.tema, caderneta.cadernetapfp 
                                FROM caderneta
                                JOIN usercaderneta ON caderneta.cadernetaid = usercaderneta.cadernetaid
                                WHERE usercaderneta.username = ? AND usercaderneta.own = 0");
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<div>
                    <a href='?cadernetaid=" . $row['cadernetaid'] . "'><h3>" . htmlspecialchars($row['nomecaderneta']) . "</h3></a>
                    <p>" . htmlspecialchars($row['tema']) . "</p>";
                if ($row['cadernetapfp']) {
                    echo "<img src='" . htmlspecialchars($row['cadernetapfp']) . "' alt='Caderneta Profile Picture' style='max-width: 150px; max-height: 150px;'>"; 
                }
                echo "</div>";
            }
        } else {
            echo "<p>Ainda não existem outras cadernetas.</p>";
        }
        $stmt->close();
        ?>
    </div>

    
<!-- Cromos -->
<div id="cardSection" style="display:none;">
    <h2>Cromos</h2>
    <?php
    if (empty($cards)) {
        echo "<p>Esta caderneta ainda não contém cromos.</p>";
    } else {
        $displayedCardNames = [];  

        function verifyCardAuthenticity($cardid, $pubkeyserver, $cardData) {
            global $passvalue;
            $passvalue = getPassphrase();

            // If the passphrase isn't set, redirect to some secure page where it will be entered
            if (!isset($passvalue) || empty($passvalue)) {
                echo json_encode(['success' => false, 'error' => 'Passphrase is not set.']);
                exit();
            }
        
            // Load private key from the server for verification
            $privateKeyPath = 'keys/server_private_key_private.pem';
            $privateKeyContents = file_get_contents($privateKeyPath);
        
            if ($privateKeyContents === false) {
                die("Unable to read private key.");
            }
        
            // Decrypt private key using the passphrase
            $privateKeyResource = openssl_pkey_get_private($privateKeyContents, $passvalue);
        
            if ($privateKeyResource === false) {
                echo json_encode(['success' => false, 'error' => 'Failed to decrypt private key. Invalid passphrase.']);
                exit();
            }
        
            $keyDetails = openssl_pkey_get_details($privateKeyResource);
        
            // Extract the public key
            $extractedPublicKey = $keyDetails['key'];
        
            // Ensure that both keys are formatted correctly (stripping whitespace/newlines)
            $extractedPublicKey = str_replace(["\r", "\n", " "], "", $extractedPublicKey);
            $pubkeyserver = str_replace(["\r", "\n", " "], "", $pubkeyserver);
        
            // Now compare the stored public key with the extracted public key
            if ($pubkeyserver === $extractedPublicKey) {
                return true;
            } else {
                return false;
            }
        }

        function deleteNonAuthenticCard($cardId) {
            global $conn; // Assuming $conn is your database connection
        
            // Prepare the query to delete the card from the 'card' table
            $deleteQuery = "DELETE FROM card WHERE cardid = ?";
            
            // Prepare and execute the query
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bind_param("i", $cardId); // Bind the cardId parameter
            $stmt->execute();
            $stmt->close();
        
        }

        foreach ($cards as $card) {

            // Get the card data and verify its authenticity
            $cromoData = $card['cardid'] . $card['cardname'] . $card['carddescription'];

            // Here, we check if the current card's public key matches the extracted key
            $isAuthentic = verifyCardAuthenticity($card['cardid'], $card['pubkeyserver'], $cromoData);

            if ($isAuthentic) {
            } else {
                deleteNonAuthenticCard($card['cardid']);
            }

            if (in_array($card['cardname'], $displayedCardNames)) {
                continue;
            }
            

            echo "<div>
                <h3>" . htmlspecialchars($card['cardname']) . "</h3>
                <p>" . nl2br(htmlspecialchars($card['carddescription'])) . "</p>";

            if ($card['cardimage']) {
                echo "<img src='" . htmlspecialchars($card['cardimage']) . "' alt='Card Image' style='max-width: 150px; max-height: 150px;'>"; 
            }

            $displayedCardNames[] = $card['cardname'];

            if ($own == 1) {
                $query = "SELECT cadernetaid FROM cardcaderneta WHERE cardid = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $card['cardid']);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $cadernetaidForCard = $row['cadernetaid'];
                    if (isset($aprovado) && $aprovado != 0) {
                        echo "<div>
                                <button onclick=\"openShareCromoPopup(" . $card['cardid'] . ", " . $cadernetaidForCard . ")\" 
                                        style='padding: 5px 15px; font-size: 14px; background-color: #2196F3; color: white; margin-top: 5px;'>
                                    Compartilhar Cromo
                                </button>
                            </div>";
                    }
                } else {
                    echo "<p>Este cromo não está associado a uma caderneta.</p>";
                }
            }


            if ($own == 1) {
                echo "<div>
                        <form method='POST' onsubmit='return confirm(\"Tem certeza de que deseja excluir este cromo?\");'>
                            <input type='hidden' name='cardid' value='" . htmlspecialchars($card['cardid']) . "'>
                            <button type='submit' name='delete_cromo' style='font-size: 12px; background-color: red; color: white;'>Excluir Cromo</button>
                        </form>
                    </div>";
            }

            $countQuery = "
                SELECT COUNT(*) AS card_count
                FROM collectedcard
                JOIN card ON collectedcard.cardid = card.cardid
                JOIN cardcaderneta ON card.cardid = cardcaderneta.cardid
                JOIN usercaderneta ON cardcaderneta.cadernetaid = usercaderneta.cadernetaid
                WHERE collectedcard.username = ? 
                AND card.cardname = ? 
                AND usercaderneta.username = ? 
                AND usercaderneta.own = 0
            ";

            // Contar
            if ($own == 0) {
                $stmtCount = $conn->prepare($countQuery);
                $stmtCount->bind_param("sss", $_SESSION['username'], $card['cardname'], $_SESSION['username']);
                $stmtCount->execute();
                $countResult = $stmtCount->get_result();
                
                $countRow = $countResult->fetch_assoc();
                $cardCount = $countRow['card_count'];

                echo "<p>Você possui $cardCount cópias deste cromo.</p>";
                
                $stmtCount->close();
            }

            echo "</div>";
        }
    }
    ?>
</div>



</div>

    <div id="sharePopup" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:#424242; padding:20px; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.2); z-index:1000;">
        <h2>Compartilhar Caderneta</h2>
        <p>Defina a validade do link em segundos (60: 1 minuto | 3600: 1 hora):</p>
        <input type="number" id="expirationInput" placeholder="Horas de validade" style="margin-bottom:10px;">
        <input type="hidden" id="cadernetaidInput">
        <button onclick="generateLink()" style="background-color:#4CAF50; color:white; padding:10px; margin-right:10px;">Gerar Link</button>
        <button onclick="closeSharePopup()" style="background-color:#f44336; color:white; padding:10px;">Cancelar</button>
    </div>

    <!-- Share Cromo Popup -->
    <div id="shareCromoPopup" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:#424242; padding:20px; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.2); z-index:1000;">
        <h2>Compartilhar Cromo</h2>
        <p>Defina a validade do link em segundos (60: 1 minuto | 3600: 1 hora):</p>
        <input type="number" id="cromoExpirationInput" placeholder="Horas de validade" style="margin-bottom:10px;">
        <input type="hidden" id="cardidInput">
        <input type="hidden" id="cadernetaidForCromoInput">
        <button onclick="generateCromoLink()" style="background-color:#4CAF50; color:white; padding:10px; margin-right:10px;">Gerar Link</button>
        <button onclick="closeShareCromoPopup()" style="background-color:#f44336; color:white; padding:10px;">Cancelar</button>
    </div>

</body>
</html>
