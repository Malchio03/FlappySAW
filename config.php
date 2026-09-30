<?php

// Dati di connessione (default di XAMPP)
// $host = "127.0.0.1";
$host = "localhost";
$db_user = "root";      // Utente di default su XAMPP
$db_psw = "";           // Password vuota di default
$db_name = "giuliaSAW";   // Il nome del database 

// Connessione in stile Object Oriented (come da slide)
//$conn = new mysqli($host, $db_user, $db_psw, $db_name);
$conn = new mysqli('localhost', 'root', '', 'giuliaSAW');

// Controllo se ci sono errori (gemini)
if ($conn->connect_error) {
    // Non mostrare l'errore all'utente, ma salvalo nel log 
    error_log("Connection failed: " . $conn->connect_error);
    die("Errore di connessione. Riprova più tardi.");
}

?>