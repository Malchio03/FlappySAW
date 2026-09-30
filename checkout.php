<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    header("Location: shop.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_ids = $_SESSION['cart'];

// Ricalcolo Totale Sicuro (Lato Server)
// implode converte forzatamente ogni valore in un numero intero. Per esempio [10,22,45] diventa "10,22,45"
if (!empty($cart_ids)) {
    $ids_string = implode(',', array_map('intval', $cart_ids));  // serve a trasformare un elenco di ID (contenuti in un array) in una stringa pronta per essere usata in una query SQL
    $result = $conn->query("SELECT sum(price) as total FROM shop_items WHERE id IN ($ids_string)");
    $row = $result->fetch_assoc();
    $server_total = $row['total'] ?? 0; // row total sarebbe il valore restituito dalla query, se != null allora restituisci il prezzo, else 0
} else {
    $server_total = 0;
}

// Controllo Saldo Utente
$stmt = $conn->prepare("SELECT coins FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_res = $stmt->get_result();
$user_coins = $user_res->fetch_assoc()['coins'];    // estrae un dato dal database e lo salva

if ($user_coins >= $server_total) {
    // INIZIO TRANSAZIONE
    $conn->begin_transaction();

    try {
        // aggiorna contatore acquisti (purchases) per i badge
        $num_items = count($cart_ids);
        $stmtUpd = $conn->prepare("UPDATE users SET coins = coins - ?, purchases = purchases + ? WHERE id = ?");
        $stmtUpd->bind_param("dii", $server_total, $num_items, $user_id);
        $stmtUpd->execute();

        // Inserisci nell'inventario
        $stmtInv = $conn->prepare("INSERT INTO user_inventory (user_id, item_id) VALUES (?, ?)");
        foreach ($cart_ids as $item_id) {
            $stmtInv->bind_param("ii", $user_id, $item_id);
            $stmtInv->execute();
        }

        // Conferma tutto
        $conn->commit();
        
        // Pulisci carrello
        $_SESSION['cart'] = [];
        
        // Redirect successo
        header("Location: profile.php?msg=purchase_ok");

    } catch (Exception $e) {
        $conn->rollback(); // Annulla se qualcosa va storto
        header("Location: shop.php?error=db_error");
    }
} else {
    header("Location: shop.php?error=no_money");
}
?>