<?php
session_start();
require_once('config.php');

// Controllo sicurezza: accesso solo se loggato
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Recupero dati base dell'utente
$stmt = $conn->prepare("SELECT username, best_score, avatar_id, bg_theme, card_theme, header_theme, purchases 
                        FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Recupero Badge Permanenti dal Database 
$my_badges = [];
$stmt_b = $conn->prepare("SELECT badge_name FROM user_badges WHERE user_id = ?");
$stmt_b->bind_param("i", $user_id);
$stmt_b->execute();
$res_b = $stmt_b->get_result();
while ($row = $res_b->fetch_assoc()) {
    $my_badges[] = $row['badge_name'];
}
$stmt_b->close();

// Gestione Avatar e Temi
$current_avatar_id = $user_data['avatar_id'] ? $user_data['avatar_id'] : 1;
$avatar_path = "img/avatar" . $current_avatar_id . ".png";
$bg_theme = $user_data['bg_theme'] ? $user_data['bg_theme'] : 'bg-day';
$card_theme = $user_data['card_theme'] ? $user_data['card_theme'] : 'card-light';
$header_theme = $user_data['header_theme'] ? $user_data['header_theme'] : 'header-classic';

// LOGICA DEI BADGE !!!

// BADGE SCORE
$badge_score_data = ['label' => 'No Score', 'class' => 'locked', 'icon' => 'bx-lock-alt'];
// score in ordine decrescente, Progressivo: 5, 10, 20, 35, 50, 75, 100
$score_tiers = [100, 75, 50, 35, 20, 10, 5];
foreach ($score_tiers as $tier) {
    if (in_array("score_" . $tier, $my_badges)) {  //gemini, x badge permanenti
        $badge_score_data = [
            'label' => $tier, // Mostra il numero di score sbloccato
            'class' => 'unlocked score-badge', 
            'icon' => 'bx-medal' // Icona base, il numero sarà visibile
        ];
        break; // Trovato il livello più alto, esco dal ciclo
    }
}

// BADGE LEADERBOARD
$badge_rank_data = ['label' => 'Unranked', 'class' => 'locked', 'icon' => 'bx-lock-alt'];

if (in_array('top_1', $my_badges)) {
    $badge_rank_data = [
        'label' => 'PRIMO AL MONDO', 
        'class' => 'unlocked rank-gold', 
        'icon' => 'bx-crown'
    ];
} elseif (in_array('top_3', $my_badges)) {
    $badge_rank_data = [
        'label' => 'TOP 3',
         'class' => 'unlocked rank-silver',
          'icon' => 'bx-trophy'
    ];
} elseif (in_array('top_10', $my_badges)) {
    $badge_rank_data = [
        'label' => 'TOP 10',
        'class' => 'unlocked rank-bronze', 
        'icon' => 'bx-trophy'
    ];
}

// BADGE SHOP (Skin comprate: 1, 5, 10) 
$skins = $user_data['purchases'];
$badge_shop_data = ['label' => 'No Skins', 'class' => 'locked', 'icon' => 'bx-lock-alt'];

if ($skins >= 9) {
    $badge_shop_data = [
        'label' => 'MILIONARIO', 
        'class' => 'unlocked shop-diamond', 
        'icon' => 'bx-closet'
    ];
} elseif ($skins >= 5) {
    $badge_shop_data = [
        'label' => 'MANI BUCATE', 
        'class' => 'unlocked shop-gold', 
        'icon' => 'bx-cart-alt'
    ];
} elseif ($skins >= 1) {
    $badge_shop_data = [
        'label' => 'SPENDACCIONE', 
        'class' => 'unlocked shop-bronze', 
        'icon' => 'bx-shopping-bag'
    ];
}
?>


<!DOCTYPE html>
<html lang="it">
<head>
    <title>Profilo Giocatore</title>
    <link rel="stylesheet" href="FlappyBird/flappybird.css?v=<?php echo time(); ?>">
     <link rel="stylesheet" href="badge.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
</head>

<!-- PHP decide quale classe CSS applicare allo sfondo -->
<body class="<?php echo htmlspecialchars($bg_theme); ?>">

    <h1 class="profilo">IL TUO PROFILO</h1>
    
    <!-- sfondo della card profilo -->
    <div class="profile-card <?php echo htmlspecialchars($card_theme); ?>">
        <!-- sfondo dell'header profilo -->
        <div class="profile-header <?php echo htmlspecialchars($header_theme); ?>">
            <div class="avatar-box">
                <!-- foto profilo -->
                <img src="<?php echo $avatar_path; ?>" alt="Avatar" class="current-avatar-img">
            </div>
            <div class="user-info-box">
                <!-- info box del profilo, con username e best score -->
                <h2 class="username-display"><?php echo htmlspecialchars($user_data['username']); ?></h2>
                <p style="margin:0; font-size: 0.9rem; opacity:0.8;">Best Score: <?php echo $user_data['best_score']; ?></p>
            </div>
        </div>

        <div class="action-buttons">
            <!-- bottone che porta alla pagina edit del profilo -->
            <a href="edit.php" class="btn-action btn-edit">
                <i class='bx bxs-pencil'></i> EDIT
            </a>
            <!-- bottone che porta alla pagina settings del profilo (cambiare password ecc) -->
            <a href="settings.php" class="btn-action btn-settings">
                <i class='bx bxs-cog'></i> SETTINGS
            </a>
        </div>

        <hr class="separator">

        <div class="badges-section">
            <h3 class="section-title">Achievements</h3>
            
            <div class="badges-grid">
                
                <!-- badge score -->
                <div class="achievement-card">
                    <div class="badge-circle <?php echo $badge_score_data['class']; ?>">
                        <?php if($badge_score_data['class'] != 'locked'): ?>
                            <span class="score-number"><?php echo $badge_score_data['label']; ?></span>
                        <?php else: ?>
                            <i class='bx <?php echo $badge_score_data['icon']; ?>'></i>
                        <?php endif; ?>
                    </div>
                    <span class="achievement-label">Score</span>
                </div>

                <!-- badge ranking della leaderboard -->
                <div class="achievement-card">
                    <div class="badge-circle <?php echo $badge_rank_data['class']; ?>" title="<?php echo $badge_rank_data['label']; ?>">
                        <i class='bx <?php echo $badge_rank_data['icon']; ?>'></i>
                    </div>
                    <span class="achievement-label"><?php echo $badge_rank_data['label']; ?></span>
                </div>

                <!-- badge shop (numero acquisti) -->
                <div class="achievement-card">
                    <div class="badge-circle <?php echo $badge_shop_data['class']; ?>">
                        <i class='bx <?php echo $badge_shop_data['icon']; ?>'></i>
                    </div>
                    <span class="achievement-label"><?php echo $badge_shop_data['label']; ?></span>
                </div>

            </div>

        </div>
        
        <a href="FlappyBird/menu.php" class="back-btn"><i class='bx bx-arrow-back'></i> Torna al Menu</a>
    
    </div>

</body>
</html>