<?php
session_start();
require_once 'db.php';

$module_id = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 1;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$user_name = $user ? $user['full_name'] : "Student";

// Check if the student has earned the certificate
$stmt = $pdo->prepare("SELECT cert_code, issue_date FROM certificates WHERE user_id = ? AND module_id = ?");
$stmt->execute([$user_id, $module_id]);
$cert_record = $stmt->fetch();

if (!$cert_record) {
    die("<div style='text-align:center; padding: 50px; font-family: sans-serif; background: #0a0b10; color: #fff; min-height: 100vh;'><h2>Access Denied</h2><p style='color: #f87171;'>You must complete the module exam with a score of 70% or higher to access this certificate.</p><a href='students_dashboard.php' style='color: #c5a059; text-decoration: none; font-weight: bold;'>&larr; Return to Dashboard</a></div>");
}

// Fetch module info
$stmt = $pdo->prepare("SELECT title FROM modules WHERE id = ?");
$stmt->execute([$module_id]);
$module = $stmt->fetch();

// Fetch Admin Signature from settings
$stmt = $pdo->prepare("SELECT admin_signature FROM settings WHERE id = 1");
$stmt->execute();
$settings = $stmt->fetch();
$admin_signature = $settings['admin_signature'] ?? '';

$cert_date = date('F d, Y', strtotime($cert_record['issue_date']));
$cert_id = $cert_record['cert_code'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Achievement - <?= htmlspecialchars($module['title'] ?? 'Module') ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .cert-body {
            background: #050505;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .certificate {
            width: 1000px;
            max-width: 100%;
            background: #fff;
            color: #000;
            padding: 4rem;
            position: relative;
            border: 20px solid #c5a059;
            text-align: center;
            box-shadow: 0 50px 100px rgba(0,0,0,0.5);
            background-image: url('https://www.transparenttextures.com/patterns/cubes.png');
        }
        .cert-title {
            font-family: 'Outfit', sans-serif;
            font-size: 3.5rem;
            font-weight: 800;
            color: #1a1a1a;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        .cert-subtitle {
            font-size: 1.5rem;
            color: #c5a059;
            font-weight: 600;
            margin-bottom: 3rem;
            letter-spacing: 2px;
        }
        .cert-name {
            font-family: 'Outfit', sans-serif;
            font-size: 4rem;
            font-weight: 800;
            margin: 2rem 0;
            border-bottom: 2px solid #eee;
            display: inline-block;
            padding: 0 4rem;
        }
        .cert-text {
            font-size: 1.25rem;
            color: #666;
            max-width: 700px;
            margin: 0 auto 3rem;
        }
        .cert-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 4rem;
        }
        .signature {
            text-align: center;
            width: 250px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            font-weight: 600;
        }
        .qr-code {
            width: 120px;
            height: 120px;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
        }
        .cert-actions {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            display: flex;
            gap: 1rem;
        }
        @media print {
            .cert-actions, nav { display: none; }
            .cert-body { background: #fff; padding: 0; }
            .certificate { box-shadow: none; border-width: 10px; }
        }
    </style>
</head>
<body class="cert-body">

    <div class="certificate">
        <div class="cert-title">Certificate</div>
        <div class="cert-subtitle">OF ACHIEVEMENT</div>
        
        <p class="cert-text">This is to certify that</p>
        
        <div class="cert-name"><?= htmlspecialchars($user_name) ?></div>
        
        <p class="cert-text">
            has successfully completed the course module<br>
            <strong style="color: #000;"><?= htmlspecialchars($module['title'] ?? 'History Module') ?></strong><br>
            on the Rwandan History Platform, demonstrating excellence and deep understanding.
        </p>

        <div class="cert-footer">
            <div class="signature">
                <?php if ($admin_signature): ?>
                    <img src="<?= $admin_signature ?>" alt="Admin Signature" style="height: 60px; filter: contrast(1.2);">
                <?php else: ?>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/3/3a/Jon_Kirsch_Signature.png" alt="Placeholder Signature" style="height: 50px; filter: grayscale(1);">
                <?php endif; ?>
                <div class="signature-line">Director of Education</div>
            </div>

            <div class="qr-info">
                <div class="qr-code">
                    <i data-lucide="qr-code" size="80" style="color: #333;"></i>
                </div>
                <p style="font-size: 0.7rem; margin-top: 0.5rem; font-weight: 600;">ID: <?= $cert_id ?></p>
            </div>

            <div class="signature">
                <div style="font-family: 'Dancing Script', cursive; font-size: 1.5rem; margin-bottom: 0.5rem;"><?= $cert_date ?></div>
                <div class="signature-line">Date of Issue</div>
            </div>
        </div>
    </div>

    <div class="cert-actions">
        <button onclick="window.print()" class="btn btn-primary">
            <i data-lucide="download" style="vertical-align: middle; margin-right: 0.5rem;"></i> Download PDF
        </button>
        <a href="index.php" class="btn btn-outline" style="background: #fff; color: #000;">Back to Home</a>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
