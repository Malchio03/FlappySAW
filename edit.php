<?php
session_start();
require_once('config.php');

// Protezione: se l'utente non è loggato, lo rimanda alla home
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_class = "";

// Array di configurazione: mappano l'ID estetico all'ID dello shop (0 = gratis/base)
$avatars_config = [
    1 => ['shop_id' => 0,  'img' => 'img/avatar1.png'],
    2 => ['shop_id' => 10, 'img' => 'img/avatar2.png'],
    3 => ['shop_id' => 11, 'img' => 'img/avatar3.png'],
    4 => ['shop_id' => 12, 'img' => 'img/avatar4.png'],
];

$bg_config = [
    'bg-day'   => ['shop_id' => 0,  'label' => 'Chiaro'],
    'bg-night' => ['shop_id' => 20, 'label' => 'Notte'],
    'bg-retro' => ['shop_id' => 21, 'label' => 'Cartoon'],
];

$header_config = [
    'header-classic' => ['shop_id' => 0,  'label' => 'Base',   'class' => 'header-classic-style'],
    'header-gold'    => ['shop_id' => 30, 'label' => 'Gold',   'class' => 'header-gold-style'],
    'header-dark'    => ['shop_id' => 31, 'label' => 'Nero',   'class' => 'header-dark-style'],
    'header-minimal' => ['shop_id' => 32, 'label' => 'Bianco', 'class' => 'header-minimal-style'],
];

$card_config = [
    'card-light' => ['shop_id' => 0,  'label' => 'Chiaro'],
    'card-glass' => ['shop_id' => 40, 'label' => 'Glass'],
];

// Recupera tutti i 'item_id' posseduti dall'utente dalla tabella user_inventory
$my_inventory = [];
$stmt_inv = $conn->prepare("SELECT item_id FROM user_inventory WHERE user_id = ?");
$stmt_inv->bind_param("i", $user_id);
$stmt_inv->execute();
$res_inv = $stmt_inv->get_result();

while ($row = $res_inv->fetch_assoc()) {    // In ogni giro del ciclo, salva i dati della riga corrente nella variabile $row
    $my_inventory[] = $row['item_id'];
}

$stmt_inv->close();

// Funzione di controllo: un item è sbloccato se shop_id è 0 o se è presente nell'inventario
function isUnlocked($req_id, $inventory) {
    return ($req_id == 0 || in_array($req_id, $inventory));
}

// SALVATAGGIO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_customization'])) {
    $req_avatar = intval($_POST['avatar_id']);
    $req_bg     = $_POST['bg_theme'];
    $req_header = $_POST['header_theme'];
    $req_card   = $_POST['card_theme'];

    // SICUREZZA: Controlla se l'utente possiede davvero l'oggetto inviato nel form.
    // Se non lo possiede, resetta al valore base (es. avatar 1 o bg-day)
    if (!isUnlocked($avatars_config[$req_avatar]['shop_id'], $my_inventory)) $req_avatar = 1;
    if (!isUnlocked($bg_config[$req_bg]['shop_id'], $my_inventory))           $req_bg = 'bg-day';

    // Aggiorna le preferenze nella tabella 'users'
    $stmt = $conn->prepare("UPDATE users SET avatar_id = ?, bg_theme = ?, card_theme = ?, header_theme = ? WHERE id = ?");
    $stmt->bind_param("isssi", $req_avatar, $req_bg, $req_card, $req_header, $user_id);
    
    if ($stmt->execute()) {
        $msg = "Profilo aggiornato con successo!";
        $msg_class = "msg-ok";
    } else {
        $msg = "Errore durante l'aggiornamento.";
        $msg_class = "msg-err";
    }
    $stmt->close();
}

// RECUPERO SELEZIONE ATTUALE
$stmt = $conn->prepare("SELECT avatar_id, bg_theme, card_theme, header_theme FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Il ?? controlla se il valore estratto dal database esiste ed è diverso da NULL
$curr_avatar = $current_data['avatar_id'] ?? 1;
$curr_bg     = $current_data['bg_theme'] ?? 'bg-day';
$curr_card   = $current_data['card_theme'] ?? 'card-light';
$curr_header = $current_data['header_theme'] ?? 'header-classic';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <title>Modifica Profilo</title>
    <link rel="stylesheet" href="FlappyBird/flappybird.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="edit.css?v=<?php echo time(); ?>">
    <!-- libreria icone -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
</head>
<!-- fa in modo che una persona non possa mettere una custom che non ha pagato -->
<body class="<?php echo htmlspecialchars($curr_bg); ?>">    

    <h1 class="profilo">PERSONALIZZA</h1>
    
    <div class="profile-card <?php echo htmlspecialchars($curr_card); ?>">
        
        <!-- se ce un errore lo stampa -->
        <?php if ($msg): ?>
            <div class="<?php echo $msg_class; ?>" style="margin-bottom:15px; text-align:center;">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <!-- action e vuoto perche invia i dati in questa pagina, quindi a se stessa -->
        <form method="POST" action="">
            
            <h3 class="section-title"><i class='bx bx-face'></i> Avatar</h3>
            <div class="selection-grid">
                <?php foreach($avatars_config as $id => $data): 
                    $unlocked = isUnlocked($data['shop_id'], $my_inventory);
                ?>
                <!-- Se l'oggetto è nell'inventario ($my_inventory), viene mostrato il pulsante di scelta
                Se non è sbloccato, viene mostrata un'icona a lucchetto e applicata la classe CSS .locked-item -->
                    <label class="avatar-option <?php echo $unlocked ? '' : 'locked-item'; ?>">
                        <?php if($unlocked): ?>
                            <input type="radio" name="avatar_id" value="<?php echo $id; ?>" <?php echo ($curr_avatar == $id) ? 'checked' : ''; ?>>
                            <img src="<?php echo $data['img']; ?>" alt="Avatar">
                        <?php else: ?>
                            <img src="<?php echo $data['img']; ?>" alt="Locked">
                            <i class='bx bx-lock-alt lock-icon'></i>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <hr class="separator">

            <h3 class="section-title"><i class='bx bx-image'></i> Sfondo Pagina</h3>
            <div class="selection-grid">
                <?php foreach($bg_config as $val => $data): 
                    $unlocked = isUnlocked($data['shop_id'], $my_inventory);
                ?>
                    <div class="<?php echo $unlocked ? '' : 'locked-item'; ?>" style="position:relative;">
                        <?php if($unlocked): ?>
                            <label>
                                <input type="radio" name="bg_theme" value="<?php echo $val; ?>" <?php echo ($curr_bg == $val) ? 'checked' : ''; ?>>
                                <span class="theme-label"><?php echo $data['label']; ?></span>
                            </label>
                        <?php else: ?>
                            <span class="theme-label theme-locked-bg"><?php echo $data['label']; ?></span>
                            <i class='bx bx-lock-alt lock-icon'></i>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <hr class="separator">

            <h3 class="section-title"><i class='bx bx-id-card'></i> Riquadro Info</h3>
            <div class="selection-grid">
                <?php foreach($header_config as $val => $data): 
                     $unlocked = isUnlocked($data['shop_id'], $my_inventory);
                ?>
                    <div class="<?php echo $unlocked ? '' : 'locked-item'; ?>" style="position:relative;">
                         <?php if($unlocked): ?>
                            <label>
                                <input type="radio" name="header_theme" value="<?php echo $val; ?>" <?php echo ($curr_header == $val) ? 'checked' : ''; ?>>
                                <span class="theme-label <?php echo $data['class']; ?>"><?php echo $data['label']; ?></span>
                            </label>
                        <?php else: ?>
                            <span class="theme-label theme-locked-bg"><?php echo $data['label']; ?></span>
                            <i class='bx bx-lock-alt lock-icon'></i>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <hr class="separator">

            <h3 class="section-title"><i class='bx bx-card'></i> Scheda Esterna</h3>
            <div class="selection-grid">
                 <?php foreach($card_config as $val => $data): 
                     $unlocked = isUnlocked($data['shop_id'], $my_inventory);
                 ?>
                    <div class="<?php echo $unlocked ? '' : 'locked-item'; ?>" style="position:relative;">
                        <?php if($unlocked): ?>
                            <label>
                                <input type="radio" name="card_theme" value="<?php echo $val; ?>" <?php echo ($curr_card == $val) ? 'checked' : ''; ?>>
                                <span class="theme-label"><?php echo $data['label']; ?></span>
                            </label>
                         <?php else: ?>
                            <span class="theme-label theme-locked-bg"><?php echo $data['label']; ?></span>
                            <i class='bx bx-lock-alt lock-icon'></i>
                        <?php endif; ?>
                    </div>
                 <?php endforeach; ?>
            </div>

            <div class="btn-save-container">
                <button type="submit" name="save_customization" class="btn-action btn-edit" style="border:none; cursor:pointer;">
                    <i class='bx bxs-save'></i> SALVA MODIFICHE
                </button>
            </div>
            
            <div class="btn-shop-container">
                 <a href="shop.php" class="shop-link">
                    <i class='bx bx-shopping-bag'></i> Vai allo Shop per sbloccare nuove skin!
                 </a>
            </div>

        </form>

        <a href="profile.php" class="back-btn"><i class='bx bx-arrow-back'></i> Torna al Profilo</a>
    </div>

</body>
</html>