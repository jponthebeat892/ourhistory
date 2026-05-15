<?php
require_once 'db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS live_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trainer_id INT,
        title VARCHAR(255) NOT NULL,
        join_code VARCHAR(10),
        status ENUM('pending', 'approved', 'live', 'ended') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Successfully created live_sessions table.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>
