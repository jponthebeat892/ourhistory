<?php
session_start();
require_once 'db.php';

// Fetch modules that have at least one lesson
$stmt = $pdo->query("
    SELECT DISTINCT m.* 
    FROM modules m 
    JOIN lessons l ON m.id = l.module_id 
    ORDER BY m.order_num ASC
");
$modules = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Courses - Rwanda History</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

    <nav>
        <a href="index.php" class="brand">RwandaHistory.</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="courses.php" class="active">Courses</a>
            <a href="exams.php">Exams</a>
            <a href="certificates.php">Certificates</a>
            <a href="community.php">Community</a>
        </div>
        <div class="nav-actions">
            <a href="login.php" class="btn btn-outline">Login</a>
        </div>
    </nav>

    <header class="hero" style="padding-bottom: 2rem;">
        <h1>Explore Rwanda’s Legacy</h1>
        <p>Comprehensive courses covering every era of our nation's history.</p>
    </header>

    <main>
        <div class="course-grid">
            <?php foreach ($modules as $module): ?>
            <a href="course_details.php?id=<?= $module['id'] ?>" class="course-card glass">
                <div class="course-thumb" style="background-image: url('<?= $module['thumbnail'] ?>'); background-size: cover; background-position: center;">
                    <div class="course-badge">Module <?= $module['order_num'] ?></div>
                </div>
                <div class="course-info">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <span style="font-size: 0.75rem; color: var(--primary); font-weight: 800;">HISTORY</span>
                        <span style="font-size: 0.75rem; color: var(--text-dim);">8 Lessons</span>
                    </div>
                    <h3><?= htmlspecialchars($module['title']) ?></h3>
                    <p><?= htmlspecialchars($module['description']) ?></p>
                    <div style="margin-top: 1.5rem; display: flex; align-items: center; gap: 0.5rem; color: var(--primary); font-weight: 600;">
                        <span>Start Learning</span>
                        <i data-lucide="arrow-right" size="18"></i>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Rwanda History Platform. All rights reserved.</p>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
