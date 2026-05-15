<?php
require_once 'db.php';
session_start();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email address is already registered.";
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'student')");
            
            if ($stmt->execute([$full_name, $email, $hashed_password])) {
                $user_id = $pdo->lastInsertId();
                
                // Log activity
                $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, activity, details) VALUES (?, 'Registration', 'Student registered via email')");
                $stmt->execute([$user_id]);
                
                // Automatically log them in
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = 'student';
                
                header("Location: students_dashboard.php");
                exit();
            } else {
                $error = "An error occurred during registration. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Rwanda History</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .login-container { max-width: 450px; margin: 8rem auto; padding: 2rem; }
        input { width: 100%; padding: 1rem; margin-bottom: 1.5rem; background: var(--glass); border: 1px solid var(--glass-border); color: #fff; border-radius: 12px; font-size: 1rem; }
        .alert { padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; text-align: center; }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
    </style>
</head>
<body style="background: var(--bg);">

    <div class="login-container card glass">
        <h2 style="margin-bottom: 2rem; text-align: center;">Join the Platform</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="full_name" placeholder="Full Name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            <input type="email" name="email" placeholder="Email Address" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <input type="password" name="password" placeholder="Password (min 6 characters)" required minlength="6">
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">Create Account</button>
        </form>

        <p style="margin-top: 2rem; text-align: center; color: var(--text-dim);">
            Already have an account? <a href="login.php" style="color: var(--primary); text-decoration: none;">Sign In</a>
        </p>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
