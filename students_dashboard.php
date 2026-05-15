<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch student progress
$stmt = $pdo->prepare("SELECT COUNT(*) FROM progress WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed_lessons = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM results WHERE user_id = ? AND status = 'pass'");
$stmt->execute([$user_id]);
$passed_exams = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM certificates WHERE user_id = ?");
$stmt->execute([$user_id]);
$certificates_earned = $stmt->fetchColumn();
// Fetch active live sessions for students
$stmt = $pdo->query("SELECT l.*, u.full_name FROM live_sessions l JOIN users u ON l.trainer_id = u.id WHERE l.status = 'live' ORDER BY l.created_at DESC");
$active_lives = $stmt->fetchAll();
// Fetch modules that have at least one lesson
$stmt = $pdo->query("
    SELECT DISTINCT m.* 
    FROM modules m 
    JOIN lessons l ON m.id = l.module_id 
    ORDER BY m.order_num ASC 
    LIMIT 3
");
$recent_modules = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Rwanda History</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .dashboard-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }
        .sidebar {
            background: var(--surface);
            border-right: 1px solid var(--glass-border);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .side-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 12px;
            color: var(--text-dim);
            text-decoration: none;
            transition: var(--transition);
        }
        .side-link:hover, .side-link.active {
            background: var(--glass);
            color: var(--primary);
        }
        .main-content {
            padding: 4rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }
        .stat-card {
            background: var(--surface);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            text-align: center;
        }
        .stat-card h1 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        .course-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        .course-card-inner {
            background: var(--surface);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            overflow: hidden;
            transition: var(--transition);
        }
        .course-card-inner:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
        }
    </style>
</head>
<body style="background: var(--bg);">

    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="brand" style="margin-bottom: 3rem; padding: 0 1rem;">RwandaHistory.</div>
            
            <a href="students_dashboard.php" class="side-link active">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <a href="courses.php" class="side-link">
                <i data-lucide="book-open"></i> My Courses
            </a>
            <a href="exam.php" class="side-link">
                <i data-lucide="file-text"></i> Exams
            </a>
            <a href="certificates.php" class="side-link">
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
            <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem;">
                <div>
                    <h1>Welcome to your Dashboard!</h1>
                    <p style="color: var(--text-dim);">Track your progress and continue learning.</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button class="btn btn-outline"><i data-lucide="bell" size="20"></i></button>
                    <div style="width: 45px; height: 45px; background: var(--primary); border-radius: 50%;"></div>
                </div>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <h1><?= $completed_lessons ?></h1>
                    <p style="color: var(--text-dim);">Lessons Completed</p>
                </div>
                <div class="stat-card">
                    <h1><?= $passed_exams ?></h1>
                    <p style="color: var(--text-dim);">Exams Passed</p>
                </div>
                <div class="stat-card">
                    <h1><?= $certificates_earned ?></h1>
                    <p style="color: var(--text-dim);">Certificates</p>
                </div>
                <div class="stat-card">
                    <h1 style="color: #22c55e;">Active</h1>
                    <p style="color: var(--text-dim);">Student Status</p>
                </div>
            </div>

            <section style="margin-bottom: 4rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <div>
                        <h2>Live Sessions</h2>
                        <p style="color: var(--text-dim);">Join currently active live sessions without going to Community.</p>
                    </div>
                </div>

                <?php if (count($active_lives) > 0): ?>
                    <div style="display: grid; gap: 1.5rem;">
                        <?php foreach ($active_lives as $live): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; background: var(--surface); border-radius: 20px; border: 1px solid var(--glass-border);">
                                <div>
                                    <strong style="font-size: 1rem;"><?= htmlspecialchars($live['title']) ?></strong>
                                    <p style="margin: 0.5rem 0 0; color: var(--text-dim); font-size: 0.9rem;">Hosted by <?= htmlspecialchars($live['full_name']) ?></p>
                                </div>
                                <a href="live_room.php?code=<?= $live['join_code'] ?>" class="btn btn-primary" style="background: #ef4444; border-color: #ef4444;">Join Live</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="padding: 1.5rem; background: var(--surface); border-radius: 20px; border: 1px solid var(--glass-border); color: var(--text-dim);">
                        No live sessions are currently active. Check back later or ask a trainer to start one.
                    </div>
                <?php endif; ?>
            </section>

            <section style="margin-bottom: 4rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>Continue Learning</h2>
                    <a href="courses.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">View All Courses &rarr;</a>
                </div>

                <div class="course-list">
                    <?php foreach ($recent_modules as $module): ?>
                    <a href="course_details.php?id=<?= $module['id'] ?>" style="text-decoration: none; display: block;">
                        <div class="course-card-inner">
                            <div style="height: 150px; background-image: url('<?= htmlspecialchars($module['thumbnail']) ?>'); background-size: cover; background-position: center;">
                            </div>
                            <div style="padding: 1.5rem;">
                                <h3 style="color: #fff; margin-bottom: 0.5rem;"><?= htmlspecialchars($module['title']) ?></h3>
                                <p style="color: var(--text-dim); font-size: 0.9rem; margin-bottom: 1rem;"><?= htmlspecialchars(substr($module['description'], 0, 80)) ?>...</p>
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--primary); font-weight: 600;">
                                    <span>Go to Course</span>
                                    <i data-lucide="arrow-right" size="18"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
