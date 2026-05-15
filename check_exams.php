<?php
require_once 'db.php';
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'exams'");
    if ($stmt->fetch()) {
        echo "Exams table exists.\n";
    } else {
        echo "Exams table DOES NOT exist.\n";
    }
    $stmt = $pdo->query("SHOW TABLES LIKE 'questions'");
    if ($stmt->fetch()) {
        echo "Questions table exists.\n";
    } else {
        echo "Questions table DOES NOT exist.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
