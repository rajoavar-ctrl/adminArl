<?php

require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->connect();

if ($conn) {
    echo "Conexión exitosa 🚀";
}

?>