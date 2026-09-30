<?php
session_start();
require_once('config.php');

// Security Check: Solo utenti loggati
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];
$msg = "";

// GESTIONE SALVATAGGIO VOTO (Rating)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rate_item'])) {
    $item_id = intval($_POST['item_id']);
    $stars = intval($_POST['rating']);

    // prende user e item per controllare se l'user possiede davvero l'oggetto
    $check = $conn->prepare("SELECT id FROM user_inventory WHERE user_id = ? AND item_id = ?");
    $check->bind_param("ii", $user_id, $item_id);
    $check->execute();
    
    //  controlla se l'utente possiede davvero l'oggetto
    if ($check->get_result()->num_rows > 0 && $stars >= 1 && $stars <= 5) {
        // Inserisci o Aggiorna il voto (*gemini* ON DUPLICATE KEY UPDATE permette di cambiare voto)
        $stmt = $conn->prepare("INSERT INTO shop_reviews (user_id, shop_item_id, rating) VALUES (?, ?, ?) 
                                ON DUPLICATE KEY UPDATE rating = ?");
        $stmt->bind_param("iiii", $user_id, $item_id, $stars, $stars);
        if($stmt->execute()){
            $msg = "Grazie per il tuo feedback!";
        }
        $stmt->close();
    }
    $check->close(); // chiude la variabile $check esistente
}

// Recupero saldo dell'utente
$stmt = $conn->prepare("SELECT coins FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_coins = $stmt->get_result()->fetch_assoc()['coins'];
$stmt->close();

// Recupero oggetti GIÀ POSSEDUTI
$owned_items = [];
$stmt_inv = $conn->prepare("SELECT item_id FROM user_inventory WHERE user_id = ?");
$stmt_inv->bind_param("i", $user_id);
$stmt_inv->execute();
$res_inv = $stmt_inv->get_result();
while ($inv_row = $res_inv->fetch_assoc()) {
    $owned_items[] = $inv_row['item_id'];
}
$stmt_inv->close();

// Inizializza carrello
if (!isset($_SESSION['cart'])) { 
    $_SESSION['cart'] = []; 
}

if (isset($_POST['action'])) {
    // Se l'azione è svuotare, lo facciamo subito e saltiamo il resto
    if ($_POST['action'] == 'empty') {
        $_SESSION['cart'] = [];
    } 
    // Altrimenti, controlliamo se esiste l'item_id prima di procedere
    elseif (isset($_POST['item_id'])) {
        $id = intval($_POST['item_id']); // Recupera l'ID dell'oggetto dal modulo e lo converte in numero intero

        if ($_POST['action'] == 'add' && !in_array($id, $_SESSION['cart'])) {
            if (!in_array($id, $owned_items)) { // Controlla anche che l'utente non possieda già l'oggetto prima di aggiungerlo
                $_SESSION['cart'][] = $id;
            }
        } elseif ($_POST['action'] == 'remove') {
            $key = array_search($id, $_SESSION['cart']);
            if ($key !== false) unset($_SESSION['cart'][$key]);
        }
    }
}

// QUERY PRINCIPALE (Oggetti + Medie + Voto Utente) (gemini)
// Questa query prende:
// - Info oggetto
// - Media voti totale (avg_rating)
// - Il TUO voto specifico se esiste (my_rating)
$sql_shop = "SELECT i.*, 
             AVG(r.rating) as avg_rating, 
             COUNT(r.id) as num_votes,
             MAX(CASE WHEN r.user_id = $user_id THEN r.rating ELSE NULL END) as my_rating
             FROM shop_items i 
             LEFT JOIN shop_reviews r ON i.id = r.shop_item_id 
             GROUP BY i.id";
$items = $conn->query($sql_shop);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <title>Game Shop</title>
    <!-- <link rel="stylesheet" href="FlappyBird/flappybird.css"> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <link rel="stylesheet" href="shop.css?v=<?php echo time(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--google fonts-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
</head>
<body class="bg-day">

<div class="shop-header">
    <h1>ITEM SHOP</h1>
    <!-- Stampa semplicemente il numero di monete attuali dell'utente recuperate dal database -->
    <div class="coins-display"><i class='bx bx-coin-stack'></i> <?php echo $user_coins; ?></div>
    <a href="profile.php" class="profilo">Torna al Profilo</a>
</div>

<div class="shop-layout">
    
    <div class="items-grid">
        <?php while($row = $items->fetch_assoc()): 
            $is_owned = in_array($row['id'], $owned_items); // Crea una variabile temporanea per capire se l'utente possiede già l'oggetto che stiamo disegnando in questo momento
            $in_cart = in_array($row['id'], $_SESSION['cart']);   // Controlla se l'oggetto è già stato messo nel carrello per evitare duplicati
            // Se lo possiedo, non mostrarlo nel carrello o come acquistabile
            // MA LO MOSTRIAMO COMUNQUE per poterlo votare!
        ?>
            <div class="item-card <?php echo $is_owned ? 'owned' : ''; ?>">  <!-- // se utente lo ha acquistato, applico il colore -->
                <!-- salva il percorso e rende in grassetto il testo dell'oggetto -->
                <img src="<?php echo $row['image_path']; ?>" class="item-img" alt="Item"> <strong><?php echo $row['name']; ?></strong>
                
                <div class="item-stars">
                    <?php 
                    $avg_stars = round($row['avg_rating']);         // Prende la media dei voti dal database
                    for ($k = 1; $k <= 5; $k++) {
                        echo ($k <= $avg_stars) 
                            ? "<i class='bx bxs-star star-gold'></i>" 
                            : "<i class='bx bx-star star-grey'></i>";
                    }
                    ?>
                    <!-- Stampa tra parentesi il numero totale di persone che hanno recensito quell'oggetto -->
                    <span class="vote-count">(<?php echo $row['num_votes']; ?>)</span>  
                </div>
                
                <span class="item-description">
                    <!-- Stampa il testo descrittivo dell'oggetto -->
                    <?php echo $row['description']; ?>     
                </span>

                <?php if (!$is_owned): ?>
                    <!-- se l'utente NON possiede allora fai tutto questo -->
                    <div class="item-price"><?php echo $row['price']; ?> <i class='bx bx-coin'></i></div>
                    
                    <!-- Il Modulo di Invio (Form) -->
                    <!-- ci ha aiutato GEMINI -->
                    <form method="POST" class="form-full">
                        <!-- Quando clicchi sul tasto "Aggiungi", il server deve sapere esattamente quale skin vuoi comprare(utile per hidden) -->
                        <!-- hidden per nascondere all'utente, action dice al server cosa vuoi fare(add). Mentre item dice al serve QUALE oggetto vuoi aggiungere -->
                        <input type="hidden" name="item_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="add">

                        <button type="submit" class="btn-add" <?php echo $in_cart ? 'disabled' : ''; ?>>
                            <?php echo $in_cart ? 'Nel Carrello' : 'Aggiungi'; ?>
                        </button>
                    </form>

                <!-- CONTINUO della if sopra (quindi utente possiede l'oggetto) -->
                <?php else: ?>
                    <div class="user-rating-box">
                        <span class="rating-label">Il tuo voto:</span>
                        <form method="POST">
                            <input type="hidden" name="item_id" value="<?php echo $row['id']; ?>">
                            <!-- Invia una "bandierina" al PHP per fargli capire che questo specifico form serve per registrare un voto e non per aggiungere al carrello -->
                            <input type="hidden" name="rate_item" value="1">    
                            
                            <div class="star-rating-input">
                                <!-- Genera graficamente le 5 stelle per permettere all'utente di votare (GEMINI)-->
                                <?php for($s=5; $s>=1; $s--): 
                                    $checked = ($row['my_rating'] == $s) ? 'checked' : ''; 
                                ?>

                                    <!-- invia il voto -->
                                    <input type="radio" id="st-<?php echo $row['id'].'-'.$s; ?>" name="rating" value="<?php echo $s; ?>" <?php echo $checked; ?> onchange="this.form.submit()">
                                    <label for="st-<?php echo $row['id'].'-'.$s; ?>"><i class='bx bxs-star'></i></label>
                                <?php endfor; ?>
                            </div>
                        </form>
                    </div>
                    <div class="status-owned-label">
                        <i class='bx bx-check-circle'></i> POSSEDUTO
                    </div>
                <?php endif; ?>

            </div>
        <?php endwhile; ?>
    </div>

    <div class="cart-panel">
        <h3><i class='bx bx-cart'></i> Carrello</h3>
        <?php 
        // Inizializzazione e Recupero Dati
            $total = 0;                 // conterrà la somma dei prezzi

            if(!empty($_SESSION['cart'])):  // Controlla se ci sono ID salvati nella sessione del carrello
                $ids = implode(',', $_SESSION['cart']); // trasforma l'array di ID in una stringa separata da virgole per poterla usare nella query SQL
                $cart_query = $conn->query("SELECT * FROM shop_items WHERE id IN ($ids)");
                while($c_item = $cart_query->fetch_assoc()):
                    $total += $c_item['price']; // Ogni volta che il ciclo trova un oggetto, aggiunge il suo prezzo al totale.
        ?>
            <div class="cart-item">
                <span><?php echo $c_item['name']; ?></span>
                <span><?php echo $c_item['price']; ?></span>
                <form method="POST" class="form-inline">
                    <input type="hidden" name="item_id" value="<?php echo $c_item['id']; ?>">
                    <input type="hidden" name="action" value="remove">
                    <button class="btn-remove-item">X</button>
                </form>
            </div>
        <?php endwhile; endif; ?>

        <hr>
        <div class="cart-total">TOTALE: <span class="price-gold"><?php echo $total; ?></span></div>

        <?php if(!empty($_SESSION['cart'])): ?>
            <form action="checkout.php" method="POST">
                <input type="hidden" name="total_amount" value="<?php echo $total; ?>">
                <button type="submit" class="btn-buy" <?php echo ($user_coins < $total) ? 'disabled' : ''; ?>>
                    <?php echo ($user_coins < $total) ? 'Saldo Insufficiente' : 'ACQUISTA'; ?>
                </button>
            </form>
            <form method="POST" class="cart-empty-form">
                <input type="hidden" name="action" value="empty">
                <button class="btn-empty-cart">Svuota</button>
            </form>
        <?php else: ?>
            <p class="empty-cart-msg">Il carrello è vuoto.</p>
        <?php endif; ?>
    </div>

</div>
<a href="FlappyBird/menu.php" class="back-btn"><i class='bx bx-arrow-back'></i> Torna al Menu</a>

</body>
</html>