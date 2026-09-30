<?php
// Verifica se la sessione NON esiste (!isset) e se esiste il cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    
    // explode() per separare ID e Token
    list($user_id, $token) = explode(':', $_COOKIE['remember_me']);

    // Selezioniamo anche la data per controllare che non sia scaduta
    $stmt = $conn->prepare("SELECT id, username, remember_token, token_expiry FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id); // i sta per integer

    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc(); // Slide 11 di 24_php5.pdf
    $stmt->close();

  
    // hash_equals -> L'hash del token nel cookie coincide con quello nel DB
    // Se il token è scaduto, niente accesso
    if ($user && hash_equals($user['remember_token'], hash('sha256', $token)) && strtotime($user['token_expiry']) > time()) {
        
        // Rigenera l'ID di sessione per prevenire Session Fixation (un hacker che ti ruba la sessione prima ancora che tu faccia login)
        session_regenerate_id(true); 

        // Impostazione variabili di sessione
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        $new_token = bin2hex(random_bytes(32)); 
        $new_token_hash = hash('sha256', $new_token);
        
        // Calcolo nuova scadenza (+30 giorni)
        $expires = time() + (86400 * 30);   // secondi di un giorno per trenta giorni
        $expiry_date = date('Y-m-d H:i:s', $expires);

        // Aggiorniamo il Database con il nuovo token
        // Importante: usiamo i nomi corretti delle colonne (remember_token, token_expiry)
        $stmt_update = $conn->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
        $stmt_update->bind_param("ssi", $new_token_hash, $expiry_date, $user['id']);
        $stmt_update->execute();
        $stmt_update->close();

        $cookie_options = [
            'expires' => $expires,
            'path' => '/',
            'secure' => true,      // HTTPS only
            'httponly' => true,    // Protezione XSS (non accessibile da JS)
            'samesite' => 'Strict' // controlla che venga dallo stesso sito 
        ];

        setcookie('remember_me', $user['id'] . ':' . $new_token, $cookie_options);

        // Se l'utente era sulla pagina di login, lo mandiamo al menu
        if (basename($_SERVER['PHP_SELF']) == 'login.php') {
            header("Location: FlappyBird/menu.php");
            exit();
        }

    } else {
        // Per cancellare un cookie si imposta la scadenza nel passato
        setcookie('remember_me', '', [
            'expires' => time() - 3600, 
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
}
?>