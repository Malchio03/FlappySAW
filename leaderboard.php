<?php
// Includiamo la configurazione e avviamo la sessione
require_once('config.php');
session_start();

// Query per prendere la TOP 10
// Selezioniamo username e best_score, ordinando dal più alto al più basso
$stmt = $conn->prepare("SELECT username, best_score 
                          FROM users 
                          WHERE best_score > 0 
                          ORDER BY best_score
                          DESC LIMIT 10");
    
$stmt->execute();
// $result = $conn->query($sql);
$result = $stmt->get_result();

// Verifica errori nella query
if (!$result) {
    error_log("Query failed: " . $conn->error); // Logghiamo l'errore 
    die("Errore nel recupero della classifica.");
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <title>Classifica Top 10</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body> 

    <div class="wrapper" style="width: 500px;"> 
        <h1 class="classifica"><i class="fa-solid fa-trophy" style="color: gold;"></i> Classifica</h1>
        <p style="text-align: center;">I migliori giocatori di Flappy Saw</p>

        <div class="leaderboard-box">
            <table>
                <thead> 
                    <tr> 
                        <th>Pos.</th>
                        <th>Giocatore</th>
                        <th>Punteggio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        $rank = 1;  // Contatore per la posizione

                        while($row = $result->fetch_assoc()) {
                            // Assegna una medaglia ai primi 3
                            $medal = "";
                            if ($rank == 1) $medal = "🥇";
                            elseif ($rank == 2) $medal = "🥈";
                            elseif ($rank == 3) $medal = "🥉";
                            
                            echo "<tr>";    // Apre una nuova riga della tabella.
                            echo "<td>" . $rank++ . " " . $medal . "</td>"; 
                            // htmlspecialchars per sicurezza
                            echo "<td>" . htmlspecialchars($row['username']) . "</td>"; 
                            echo "<td>" . $row['best_score'] . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td>Nessun punteggio ancora registrato!</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <a href="FlappyBird/menu.php" class="back">Back</a>
    </div>

</body>
</html>