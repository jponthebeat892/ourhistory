<?php
require_once 'db.php';
try {
    // Add trainer_id to modules if it doesn't exist
    $pdo->exec("ALTER TABLE modules ADD COLUMN IF NOT EXISTS trainer_id INT");
    echo "Successfully updated modules table.\n";
} catch (PDOException $e) {
    echo "Error updating modules table: " . $e->getMessage() . "\n";
}
?>
