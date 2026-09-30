<?php
session_start();
require_once('config.php');

// Security check: l'utente non puo visualizzare il profilo senza i permessi
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg_profile = "";
$msg_password = "";
$class_profile = "";    // colore errore username
$class_password = "";   // colore errore password


// GESTIONE AGGIORNAMENTO USERNAME 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);

    if (strlen($new_username) < 3) {
        $msg_profile = "Errore: Lo username deve contenere almeno 3 caratteri!";
        $class_profile = "msg-err";
    }elseif (!empty($new_username)) {
        
        // cerchiamo username attuale
        $stmt_curr = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt_curr->bind_param("i", $user_id);
        $stmt_curr->execute();
        $res_curr = $stmt_curr->get_result();
        $row_curr = $res_curr->fetch_assoc();
        $current_db_username = $row_curr['username'];
        $stmt_curr->close();

        // Controllo se in nuovo nome e' uguale a quello attuale
        if ($new_username === $current_db_username) {
            $msg_profile = "Errore: Il nuovo nome utente deve essere diverso da quello attuale!";
            $class_profile = "msg-err";
        } else {
            // Se sono diversi, verifichiamo se il nome e' gia utilizzato da qualcun'altro
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt_check->bind_param("si", $new_username, $user_id);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result();

            if ($res_check->num_rows > 0) {
                // Nome gia preso da qualcun altro
                $msg_profile = "Errore: Questo nome utente è già in uso!";
                $class_profile = "msg-err";
            } else {
                // Nome libero e valido, procediamo all'UPDATE
                $stmt_check->close(); 

                $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt->bind_param("si", $new_username, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['username'] = $new_username; // Aggiorniamo la sessione
                    $msg_profile = "Nome utente aggiornato con successo!";
                    $class_profile = "msg-ok";
                } else {
                    $msg_profile = "Errore nel database!";
                    $class_profile = "msg-err";
                }
                $stmt->close();
            }
        }
    } else {
        $msg_profile = "Il nome non può essere vuoto.";
    }
}

// GESTIONE CAMBIO PASSWORD 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];

    $regex = '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';

    if ($old_pass === $new_pass) {
        $msg_password = "Errore: La nuova password deve essere diversa da quella attuale.";
        $class_password = "msg-err";
    } elseif (!preg_match($regex, $new_pass)) {
        $msg_password = "La nuova password deve contenere almeno 8 caratteri, una maiuscola, un numero e un simbolo.";
        $class_password = "msg-err";
    }
    else {
        // Recupera hash attuale
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        // Verifica vecchia password
        if (password_verify($old_pass, $row['password'])) {
            // Hash nuova password
            $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
            
            $stmt_upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_upd->bind_param("si", $new_hash, $user_id);
            
            if ($stmt_upd->execute()) {
                $msg_password = "Password cambiata con successo!";
                $class_password = "msg-ok";
            } else {
                $msg_password = "Errore durante il cambio password.";
                $class_password = "msg-err";
            }
            $stmt_upd->close();

        } else {
            $msg_password = "La vecchia password è errata.";
            $class_password = "msg-err";
        }
    }
}

// Recupera dati per visualizzazione (lo facciamo alla fine così vediamo i dati aggiornati)
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>


<!DOCTYPE html>
<html lang="it">
<head>
    <title>Impostazioni</title>
    <link rel="stylesheet" href="FlappyBird/flappybird.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
</head>

<body>

    <h1 class="settings">Impostazioni</h1>

    <div class="profile-wrapper"> 
        <h2 class="profile-section-title"><i class='bx bx-user'></i> Dati Personali</h2>
        
        <!--Gemini-->
        <?php 
        if ($msg_profile) { 
            ?>
            <div class="<?php echo $class_profile; ?>">
                <?php echo $msg_profile; ?>
            </div>
            <?php 
            } 
        ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
            </div>
            <button type="submit" name="update_profile" class="btn-save">Salva Modifiche</button>
        </form>

        <hr class="separator">

        <h2 class="profile-section-title"><i class='bx bx-lock-alt'></i> Sicurezza</h2>

        <!--Gemini-->
        <?php 
        if ($msg_password) { 
        ?>
            <div class="<?php echo $class_password; ?>">
                <?php echo $msg_password; ?>
            </div>
        <?php 
        } 
        ?>

        <form method="post" action="">
            <div class="form-group">
                <label>Vecchia Password</label>
                <input type="password" name="old_password" required placeholder="Inserisci la tua password attuale">
            </div>
            <div class="form-group">
                <label>Nuova Password</label>
                <input type="password" name="new_password" required placeholder="Inserisci la nuova password">
            </div>
            <button type="submit" name="update_password" class="btn-password">Cambia Password</button>
        </form>

        <a href="profile.php" class="back-btn"><i class='bx bx-arrow-back'></i> Torna al Profilo</a>
    </div>

</body>
</html>