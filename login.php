<?php
// Includiamo la configurazione e avviamo la sessione
require_once('config.php');
session_start(); // Serve per ricordare l'utente loggato

// Se l'utente è già loggato (perché lo ha appena fatto o perché la sessione è ancora viva)
// lo mandiamo subito via da qui.
if (isset($_SESSION['user_id'])) {
    header("Location: FlappyBird/menu.php");
    exit();
}

// INCLUDIAMO IL CONTROLLO AUTO-LOGIN
// Se l'utente ha il cookie, verrà loggato automaticamente e reindirizzato
require_once('authentic_check.php');

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $username = trim($_POST['username']);   // trim toglie spazi 
    $password = $_POST['password']; // noi lasciamo lo spazio ammesso

    // Cerchiamo l'utente nel database
    // Usiamo Prepared Statements per sicurezza (preso dalle slide)
    $stmt = $conn->prepare("SELECT id, password, username 
                            FROM users
                            WHERE username = ?");
                            
    $stmt->bind_param("s", $_POST['username']); // s in quanto stiamo inviando string, questa linea sostiuisce il ? con username
    $stmt->execute();
    $result = $stmt->get_result();

    // Se troviamo un utente con quel nome.
    if ($result->num_rows === 1) {      // Controlliamo se il database ha trovato esattamente 1 persona con quel nome
        $row = $result->fetch_assoc();
        
        // Verifichiamo se la password è corretta (decifrando l'hash)
        if (password_verify($password, $row['password'])) {

            // Previene Session Fixation: rigenera l'ID sessione al login
            session_regenerate_id(true);
            
            // Salviamo i dati nella sessione
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];

            // Verifichiamo se la checkbox è stata spuntata
            if (isset($_POST['remember_me'])) {
                // Generiamo un token casuale sicuro
                $token = bin2hex(random_bytes(32));
                
                // Creiamo l'hash da salvare nel DB
                $token_hash = hash('sha256', $token);

                // Scadenza tra 30 giorni
                $expires = time() + (86400 * 30);   // secondi di un giorno per trenta giorni 
                $expiry_date = date('Y-m-d H:i:s', $expires);

                // Salviamo nel DB
                $stmt_upd = $conn->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
                $stmt_upd->bind_param("ssi", $token_hash, $expiry_date, $row['id']);
                $stmt_upd->execute();
                $stmt_upd->close();

                // Impostiamo il cookie HTTP Only
                // preso dalle slide
                $cookie_options = [
                    'expires' => $expires,
                    'path' => '/',
                    'secure' => true,     
                    'httponly' => true,   
                    'samesite' => 'Strict' 
                ];
                setcookie('remember_me', $row['id'] . ':' . $token, $cookie_options);
            }
            
            // Rimandiamo l'utente al Menu Principale
            header("Location: FlappyBird/menu.php");
            exit();
            
        } else {
            $msg = "Password o email errata.";
        }
    } else {
        $msg = "Utente non trovato.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <title>Login</title>
        <!-- libreria icone -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
        <!-- css -->
        <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    </head>
    <body>
        <div class="wrapper">
            <form action="" method="post">
                <h1>Login</h1>
                
                <!-- lo facciamo inline per comodita, il color si riferisce ai messaggi di errore -->
                <?php if($msg != "") { echo "<p style='color: red; text-align: center;'>$msg</p>"; } ?>

                <div class="input-box">
                    <span><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" placeholder="Username" required>
                </div>

                <div class="input-box">
                    <span><i class="fa-solid fa-unlock"></i></span>
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <span class="toggle-password" onclick="togglePassword('password', 'eye-icon')" style="cursor: pointer;">
                        <i id="eye-icon" class="fa-solid fa-eye"></i>
                    </span>
                </div>

                <div class="remember-forgot">
                    <label><input type="checkbox" name="remember_me"> Ricordami</label>
                    <a href="under_construction.html">Hai dimenticato la password?</a>
                </div>

                <button type="submit" class="btn">Login</button>

                <div class="register-link">
                    <p>Non hai un account?<a href="register.php">Registrati</a></p>
                </div>
            </form>
        </div>
        <script src="EyeToggle.js"></script>
    </body>
</html>