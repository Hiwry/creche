<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=creche', 'root', '');
    echo "Connected successfully to 127.0.0.1\n";
} catch (PDOException $e) {
    echo "Failed to connect to 127.0.0.1: " . $e->getMessage() . "\n";
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=creche', 'root', '');
    echo "Connected successfully to localhost\n";
} catch (PDOException $e) {
    echo "Failed to connect to localhost: " . $e->getMessage() . "\n";
}
