<?php
require_once 'db.php';
session_start();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $login_code = $_POST['login_code'] ?? '';

    // Trainer Login with Code
    if (!empty($login_code)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login_code = ? AND role = 'trainer'");
        $stmt->execute([$login_code]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, activity, details) VALUES (?, 'Trainer Login', 'Trainer logged in via code')");
            $stmt->execute([$user['id']]);
            
            header("Location: trainer_dashboard.php");
            exit();
        } else {
            $error = "Invalid trainer login code.";
        }
    } 
    // Normal Login (Student/Admin/Trainer)
    else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, activity, details) VALUES (?, 'Login', 'User logged in via email')");
            $stmt->execute([$user['id']]);

            if ($user['role'] === 'admin') header("Location: admin_dashboard.php");
            elseif ($user['role'] === 'trainer') header("Location: trainer_dashboard.php");
            elseif ($user['role'] === 'student') header("Location: students_dashboard.php");
            else header("Location: index.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rwanda History</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .login-container { max-width: 450px; margin: 8rem auto; padding: 2rem; }
        .tab-nav { display: flex; gap: 2rem; margin-bottom: 2rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem; }
        .tab-btn { background: none; border: none; color: var(--text-dim); font-weight: 600; cursor: pointer; padding: 0; font-size: 1rem; }
        .tab-btn.active { color: var(--primary); }
        .form-section { display: none; }
        .form-section.active { display: block; }
        input { width: 100%; padding: 1rem; margin-bottom: 1.5rem; background: var(--glass); border: 1px solid var(--glass-border); color: #fff; border-radius: 12px; font-size: 1rem; }
    </style>
</head>
<body style="background: #0a0b10;">

    <div class="login-container card glass">
        <h2 style="margin-bottom: 2rem; text-align: center;">Welcome Back</h2>

        <div class="tab-nav">
            <button class="tab-btn active" onclick="switchTab('standard')">Standard Login</button>
            <button class="tab-btn" onclick="switchTab('trainer')">Trainer Code</button>
        </div>

        <?php if ($error): ?>
            <p style="color: #f87171; text-align: center; margin-bottom: 1.5rem;"><?= $error ?></p>
        <?php endif; ?>

        <!-- Standard Login -->
        <div id="standard" class="form-section active">
            <form method="POST">
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">Sign In</button>
            </form>
        </div>

        <!-- Trainer Login -->
        <div id="trainer" class="form-section">
            <form method="POST">
                <p style="color: var(--text-dim); font-size: 0.9rem; margin-bottom: 1.5rem;">Enter the 6-character code provided by your administrator.</p>
                <input type="text" name="login_code" placeholder="Trainer Code (e.g. A1B2C3)" maxlength="6" style="text-transform: uppercase;">
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">Enter Dashboard</button>
            </form>
        </div>

        <p style="margin-top: 2rem; text-align: center; color: var(--text-dim);">
            Don't have an account? <a href="register.php" style="color: var(--primary); text-decoration: none;">Join the platform</a>
        </p>
    </div>

    <script>
        lucide.createIcons();
        function switchTab(tab) {
            document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tab).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
