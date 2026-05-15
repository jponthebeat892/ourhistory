<?php
require_once 'db.php';
$stmt = $pdo->query("DESCRIBE modules");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt = $pdo->query("DESCRIBE lessons");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
