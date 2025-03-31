<?php
global $passvalue; // Declare global variable

function storePassphrase($passphrase) {
    apcu_store('passvalue', $passphrase);
}

function getPassphrase() {
    return apcu_exists('passvalue') ? apcu_fetch('passvalue') : null;
}


function isPassphraseCorrect($passphrase) {
    global $passvalue;

    $privateKeyPath = 'C:\\xampp\\htdocs\\projeto\\keys\\server_private_key.pem';
    $privateKeyContents = file_get_contents($privateKeyPath);

    if ($privateKeyContents === false) {
        return false;
    }

    $privateKey = openssl_pkey_get_private($privateKeyContents, $passphrase);

    if ($privateKey !== false) {
        $passvalue = $passphrase; // Modify global variable
        return true;
    }

    return false;
}
?>
