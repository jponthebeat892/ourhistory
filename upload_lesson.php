<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is a trainer
// (In a real app, you'd check $_SESSION['role'] === 'trainer')
// For this demo, we assume the user has permission if they reached the dashboard

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module_id = $_POST['module_id'];
    $title = $_POST['title'];
    $content_type = $_POST['content_type'];
    $content = $_POST['content'] ?? '';
    $order_num = $_POST['order_num'] ?? 0;

    $media_url = null;

    // Handle File Upload
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['media_file']['tmp_name'];
        $file_name = $_FILES['media_file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Generate unique filename
        $new_file_name = uniqid('lesson_', true) . '.' . $file_ext;
        $upload_dir = 'uploads/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $dest_path = $upload_dir . $new_file_name;

        if (move_uploaded_file($file_tmp, $dest_path)) {
            $media_url = $new_file_name;
        } else {
            $_SESSION['error'] = "Failed to move uploaded file.";
            header("Location: trainer_dashboard.php");
            exit();
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO lessons (module_id, title, content, content_type, media_url, order_num) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$module_id, $title, $content, $content_type, $media_url, $order_num]);
        
        $_SESSION['success'] = "Lesson uploaded successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    header("Location: trainer_dashboard.php");
    exit();
}
?>
