<?php
session_start();
// ../ per tornare indietro alla cartella principale
require_once('../config.php');

// Se la sessione è scaduta ma c'è il cookie, auth_check lo ri-logga automaticamente
require_once('../authentic_check.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); 
    exit();
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Menu</title>
         <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">

         <!--boxicons-->
        <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">

        <link rel="stylesheet" href="flappybird.css?v=<?php echo time(); ?>">
    </head>
    <body>
        <div class="top-nav">
            <a href="../profile.php" class="profile-link">
                <i class='bx bx-user-circle'></i>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </a>
        </div>
        
        <p style="color: white; font-family: sans-serif;">
            Benvenuto, <b><?php echo htmlspecialchars($_SESSION['username']); ?></b>!
        </p>

        <h1>FLAPPY SAW</h1>
        
        <a href="Game.php">
            <button class="btn-start">PRESS TO START</button>
        </a>

        <br><br>

        <a href="../leaderboard.php">
            <button class="btn-classifica">CLASSIFICA</button>
        </a>

        <br><br>

         <a href="../shop.php">
            <button class="btn-negozio">NEGOZIO</button>
        </a>
        
        <br><br>
        <a href="../logout.php">
            <button class="btn-logout">LOGOUT</button>
        </a>

    </body>
</html>