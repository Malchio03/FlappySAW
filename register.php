<?php
    // Includiamo il file di configurazione per connetterci al database
    require_once('config.php');

    $msg = ""; // Variabile per mostrare eventuali errori

    // Controlliamo se il form è stato inviato (metodo POST) 
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // Recuperiamo i dati scritti dall'utente usando $_POST 
        // Usiamo trim() per rimuovere spazi vuoti 
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $repassword = $_POST['repassword'];

        if (strlen($username) < 3) {
            $msg = "Lo username deve contenere almeno 3 caratteri!";
        } elseif ($password !== $repassword) {
            $msg = "Le password non coincidono!";
        } else {
            // Espressione regolare singola: Min 8 caratteri, 1 Maiuscola, 1 Numero, 1 Speciale
            $regex = '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';

            if (!preg_match($regex, $password)) {
            $msg = "La password deve contenere almeno 8 caratteri, una maiuscola, un numero e un simbolo.";
        }else {
            // Hash della password (OBBLIGATORIO per la sicurezza)
            // Non salviamo mai la password in chiaro!
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Controlliamo se l'utente esiste già usando i Prepared Statements 
            // Questo previene SQL Injection
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $check_stmt->bind_param("ss", $email, $username);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
            // $msg = "Username o Email già registrati!";
                $msg ="Errore durante la registrazione.";// per non far capire che l'utente e gia registrato
            } else {
                // Inseriamo il nuovo utente nel database
                $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("sss", $username, $email, $hashed_password);
                
                if ($insert_stmt->execute()) {
                    // Se tutto va bene, rimandiamo alla pagina di login
                    header("Location: login.php"); 
                    exit();
                } else {
                    $msg = "Errore durante la registrazione: " . $conn->error;
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
        }
    }
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <title>Registrati</title>
        <!-- libreria icone -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
        <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    </head>
    <body>
        <div class="wrapper register-page">
            <form action="" method="post"> 
                <h1>Registrati</h1>
                <p>Completa tutte le informazioni</p>
                
                <?php if($msg != "") { echo "<p style='color: red; text-align: center;'>$msg</p>"; } ?>

                <div class="input-box2">
                    <input type="text" name="username" placeholder="Inserisci Username" required>
                </div>

                <div class="input-box2">
                    <input type="email" name="email" placeholder="Inserisci Email" required>
                </div>

               <div class="input-box2">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <span class="toggle-password2" onclick="togglePassword('password', 'eye-icon1')" style="cursor: pointer;">
                        <i id="eye-icon1" class="fa-solid fa-eye"></i>
                    </span>
                </div>

                <div class="input-box2">
                    <input type="password" name="repassword" id="repassword" placeholder="Ripeti Password" required>
                    <span class="toggle-password3" onclick="togglePassword('repassword', 'eye-icon2')" style="cursor: pointer;">
                        <i id="eye-icon2" class="fa-solid fa-eye"></i>
                    </span>
                </div>

                <button type="submit" class="btn re-spazio">Registrati</button>

                <div class="login-link">
                    <p>Hai un account?<a href="login.php">Login</a></p>
                </div>

            </form>
        </div>
        <script src="EyeToggle.js?v=<?php echo time(); ?>"></script>
    </body>
</html>