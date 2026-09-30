<?php
// Avvia la sessione per controllare chi è l'utente
session_start(); 

// Se l'utente non è loggato, si torna al login
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Flappy Bird</title>
       <link rel="stylesheet" href="flappybird.css?v=<?php echo time(); ?>">
       <!--boxicons-->
        <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
        <script src="flappybird.js?v=<?php echo time(); ?>"></script>
    </head>
    <body>
        <div class="top-nav">
        <a href="../profile.php" class="profile-link">
            <i class='bx bx-user-circle'></i>
            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </a>
    </div>
        <canvas id="board"></canvas>
    </body>
</html>