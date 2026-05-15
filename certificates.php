<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch earned certificates
$stmt = $pdo->prepare("
    SELECT c.*, m.title, m.thumbnail, m.description 
    FROM certificates c
    JOIN modules m ON c.module_id = m.id
    WHERE c.user_id = ?
    ORDER BY c.issue_date DESC
");
$stmt->execute([$user_id]);
$certificates = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Certificates - Rwanda History</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .dashboard-layout { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
        .sidebar { background: var(--surface); border-right: 1px solid var(--glass-border); padding: 2rem; display: flex; flex-direction: column; gap: 0.5rem; }
        .side-link { display: flex; align-items: center; gap: 1rem; padding: 1rem; border-radius: 12px; color: var(--text-dim); text-decoration: none; transition: var(--transition); }
        .side-link:hover, .side-link.active { background: var(--glass); color: var(--primary); }
        .main-content { padding: 4rem; }
        
        .cert-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .cert-card {
            background: var(--surface);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            overflow: hidden;
            transition: var(--transition);
        }
        .cert-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
        }
        .cert-thumb {
            height: 160px;
            background-color: var(--glass);
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .cert-thumb::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .cert-thumb i {
            position: relative;
            z-index: 2;
            color: var(--primary);
        }
        .cert-info {
            padding: 1.5rem;
        }
        .cert-info h3 { margin-bottom: 0.5rem; color: #fff; }
        .cert-info p { color: var(--text-dim); font-size: 0.9rem; margin-bottom: 1rem; }
    </style>
</head>
<body style="background: var(--bg);">

    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="brand" style="margin-bottom: 3rem; padding: 0 1rem;">RwandaHistory.</div>
            
            <a href="students_dashboard.php" class="side-link">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <a href="courses.php" class="side-link">
                <i data-lucide="book-open"></i> My Courses
            </a>
            <a href="exam.php" class="side-link">
                <i data-lucide="file-text"></i> Exams
            </a>
            <a href="certificates.php" class="side-link active">
                <i data-lucide="award"></i> Certificates
            </a>
            <a href="community.php" class="side-link">
                <i data-lucide="message-square"></i> Community
            </a>
            
            <div style="margin-top: auto;">
                <a href="index.php" class="side-link" style="color: #f87171;">
                    <i data-lucide="log-out"></i> Logout
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header style="margin-bottom: 3rem;">
                <h1>My Certificates</h1>
                <p style="color: var(--text-dim);">View and download your earned certificates of achievement.</p>
            </header>

            <?php if (count($certificates) > 0): ?>
                <div class="cert-grid">
                    <?php foreach ($certificates as $cert): ?>
                        <div class="cert-card">
                            <div class="cert-thumb" style="background-image: url('<?= htmlspecialchars($cert['thumbnail']) ?>');">
                                <i data-lucide="award" size="48"></i>
                            </div>
                            <div class="cert-info">
                                <h3><?= htmlspecialchars($cert['title']) ?></h3>
                                <p>Earned on <?= date('M d, Y', strtotime($cert['issue_date'])) ?></p>
                                <p style="font-size: 0.8rem; color: var(--text-dim); margin-bottom: 1.5rem;">ID: <?= htmlspecialchars($cert['cert_code']) ?></p>
                                
                                <a href="certificate.php?module_id=<?= $cert['module_id'] ?>" class="btn btn-outline" style="width: 100%; display: block; text-align: center; text-decoration: none;">
                                    View Certificate
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem; background: var(--glass); border: 1px dashed var(--glass-border); border-radius: 20px;">
                    <i data-lucide="award" size="64" style="color: var(--text-dim); margin-bottom: 1.5rem; opacity: 0.5;"></i>
                    <h2 style="margin-bottom: 1rem;">No Certificates Yet</h2>
                    <p style="color: var(--text-dim); margin-bottom: 2rem;">Complete course exams with a score of 70% or higher to earn certificates.</p>
                    <a href="courses.php" class="btn btn-primary" style="text-decoration: none;">Start Learning</a>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
