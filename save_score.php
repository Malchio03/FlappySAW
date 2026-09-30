<?php
    session_start();
    require_once('config.php');
    header('Content-Type: application/json');

    // Inizializziamo la risposta
    $response = ["success" => false, "message" => "Errore generico"];

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        $response["message"] = "Sessione scaduta, effettua il login.";
        echo json_encode($response);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $score = intval($_POST['score'] ?? 0);
    $coins_earned = floor($score / 5);

    // Salvataggio Storico Score
    $stmtHistory = $conn->prepare("INSERT INTO scores (user_id, score) VALUES (?, ?)");
    $stmtHistory->bind_param("ii", $user_id, $score);
    $stmtHistory->execute();
    $stmtHistory->close();

    // Aggiornamento Monete
    if ($coins_earned > 0) {
        $stmtCoins = $conn->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
        $stmtCoins->bind_param("ii", $coins_earned, $user_id);
        $stmtCoins->execute();
        $stmtCoins->close();
    }

    // Aggiornamento Best Score
    $stmtUpdate = $conn->prepare("UPDATE users SET best_score = ? WHERE id = ? AND best_score < ?");
    $stmtUpdate->bind_param("iii", $score, $user_id, $score);
    $stmtUpdate->execute(); 

    if ($stmtUpdate->affected_rows > 0) {
        // CASO NUOVO RECORD
        $response["new_record"] = true;
        $response["success"] = true;
        $response["message"] = "Nuovo Record Personale!"; 

        // Logica Badge Leaderboard
        $stmtRank = $conn->prepare("SELECT COUNT(*) as better_players FROM users WHERE best_score > ?");
        $stmtRank->bind_param("i", $score);
        $stmtRank->execute();
        $current_rank = $stmtRank->get_result()->fetch_assoc()['better_players'] + 1;
        $stmtRank->close();

        $stmtBadge = $conn->prepare("INSERT IGNORE INTO user_badges (user_id, badge_name) VALUES (?, ?)");
        
        if ($current_rank == 1) {
            $b_name = 'top_1';
            $stmtBadge->bind_param("is", $user_id, $b_name);
            $stmtBadge->execute();
        } 
        if ($current_rank <= 3) {
            $b_name = 'top_3';
            $stmtBadge->bind_param("is", $user_id, $b_name);
            $stmtBadge->execute();
        }
        if ($current_rank <= 10) {
            $b_name = 'top_10';
            $stmtBadge->bind_param("is", $user_id, $b_name);
            $stmtBadge->execute();
        }

        // Logica Badge Punteggio
        $score_tiers = [100, 75, 50, 35, 20, 10, 5];
        foreach ($score_tiers as $tier) {
            if ($score >= $tier) {
                $tier_name = "score_" . $tier;
                $stmtBadge->bind_param("is", $user_id, $tier_name);
                $stmtBadge->execute();
            }
        }
        $stmtBadge->close();
    } else {
        // CASO PUNTEGGIO SALVATO (MA NON RECORD) 
        $response["success"] = true; 
        $response["new_record"] = false;
        $response["message"] = "Punteggio salvato con successo!"; 
    }

    $stmtUpdate->close();
    $conn->close();

    echo json_encode($response);
    exit();
?>