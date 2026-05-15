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
    <title>Rwanda History - Discover the Past</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>

    <nav>
        <a href="index.php" class="brand">RwandaHistory.</a>
        <div class="nav-links">
            <a href="index.php" class="active">Home</a>
            <a href="courses.php">Courses</a>
            <a href="exam.php">Exams</a>
            <a href="certificates.php">Certificates</a>
            <a href="community.php">Community</a>
        </div>
        <div class="nav-actions">
            <a href="login.php" class="btn btn-outline">Login</a>
        </div>
    </nav>

    <header class="hero">
        <h1>Rwanda’s history in a new, expanded, and reliable way.</h1>
        <p>Explore the journey of a nation from pre-colonial traditions to modern reconstruction.</p>

        <div class="search-container">
            <i data-lucide="search" class="search-icon"></i>
            <input type="text" placeholder="Search Rwanda’s history of... (Rwabugiri, Ubwiru, Genocide, etc.)">
        </div>
    </header>

    <main>
        <section class="section-title">
            <h2>Explore History Modules</h2>
            <a href="courses.php" class="btn btn-outline">View All Courses</a>
        </section>

        <div class="course-grid">
            <?php foreach ($modules as $module): ?>
                <a href="course_details.php?id=<?= $module['id'] ?>" class="course-card glass">
                    <div class="course-thumb"
                        style="background-image: url('<?= $module['thumbnail'] ?>'); background-size: cover; background-position: center;">
                        <div class="course-badge">Module <?= $module['order_num'] ?></div>
                    </div>
                    <div class="course-info">
                        <h3><?= htmlspecialchars($module['title']) ?></h3>
                        <p><?= htmlspecialchars($module['description']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Rwanda History Platform. All rights reserved.</p>
        <div style="margin-top: 1rem; font-size: 0.8rem;">
            <a href="#" style="color: var(--text-dim);">Privacy Policy</a> |
            <a href="#" style="color: var(--text-dim);">Terms of Service</a>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>