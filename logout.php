<?php
require_once('config.php');
session_start();

// CANCELLA IL TOKEN DAL DATABASE (Per sicurezza)
if (isset($_SESSION['user_id'])) {
    // Svuotiamo il campo token e la scadenza per l'utente corrente
    $stmt = $conn->prepare("UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

// CANCELLA LA SESSIONE
$_SESSION = array(); // Svuota l'array
session_destroy();   // Distrugge la sessione sul server

// CANCELLA IL COOKIE "RICORDAMI"
if (isset($_COOKIE['remember_me'])) {
    // Settiamo la scadenza nel passato per cancellarlo dal browser
    setcookie('remember_me', '', time() - 3600, '/');
}

// TORNA AL LOGIN
header("Location: login.php");
exit();
?>
