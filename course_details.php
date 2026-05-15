<?php
session_start();
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Fetch module details
$stmt = $pdo->prepare("SELECT * FROM modules WHERE id = ?");
$stmt->execute([$id]);
$module = $stmt->fetch();

if (!$module) {
    header("Location: index.php");
    exit();
}

// Fetch lessons for this module
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE module_id = ? ORDER BY order_num ASC");
$stmt->execute([$id]);
$lessons = $stmt->fetchAll();

// Select current lesson
$current_lesson_id = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : (count($lessons) > 0 ? $lessons[0]['id'] : null);
$current_lesson = null;
if ($current_lesson_id) {
    foreach ($lessons as $l) {
        if ($l['id'] == $current_lesson_id) {
            $current_lesson = $l;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($module['title']) ?> - Rwanda History</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .module-header {
            padding: 6rem 5% 4rem;
            background: linear-gradient(to bottom, #1a1c23, var(--bg));
            border-bottom: 1px solid var(--glass-border);
        }
        .content-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            min-height: 80vh;
        }
        .sidebar {
            border-right: 1px solid var(--glass-border);
            padding: 2rem;
            background: var(--surface);
        }
        .lesson-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: var(--transition);
            color: var(--text-dim);
            text-decoration: none;
        }
        .lesson-item:hover, .lesson-item.active {
            background: var(--glass);
            color: var(--primary);
        }
        .lesson-item.active {
            border-left: 4px solid var(--primary);
        }
        .main-content {
            padding: 4rem 10%;
        }
        .content-card {
            background: var(--surface);
            padding: 3rem;
            border-radius: 20px;
            border: 1px solid var(--glass-border);
        }
        .media-placeholder {
            width: 100%;
            aspect-ratio: 16/9;
            background: #000;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-dim);
        }
        @media (max-width: 992px) {
            .content-layout {
                grid-template-columns: 1fr;
            }
            .sidebar {
                border-right: none;
                border-bottom: 1px solid var(--glass-border);
            }
        }
    </style>
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
            <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
        </div>
    </nav>

    <header class="module-header">
        <p style="color: var(--primary); font-weight: 800; margin-bottom: 0.5rem;">Module <?= $module['order_num'] ?></p>
        <h1 style="font-size: 3rem;"><?= htmlspecialchars($module['title']) ?></h1>
        <p style="color: var(--text-dim); max-width: 600px;"><?= htmlspecialchars($module['description']) ?></p>
    </header>

    <div class="content-layout">
        <aside class="sidebar">
            <h3 style="margin-bottom: 1.5rem;">Lessons</h3>
            <?php if (empty($lessons)): ?>
                <p style="color: var(--text-dim); font-size: 0.9rem;">No lessons added yet.</p>
            <?php else: ?>
                <?php foreach ($lessons as $lesson): ?>
                <?php 
                    $icon = 'file-text';
                    if ($lesson['content_type'] === 'video') $icon = 'play-circle';
                    elseif ($lesson['content_type'] === 'audio') $icon = 'headphones';
                    elseif ($lesson['content_type'] === 'document') $icon = 'file';
                ?>
                <a href="?id=<?= $id ?>&lesson_id=<?= $lesson['id'] ?>" class="lesson-item <?= $current_lesson_id == $lesson['id'] ? 'active' : '' ?>">
                    <i data-lucide="<?= $icon ?>"></i>
                    <span><?= htmlspecialchars($lesson['title']) ?></span>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: 2rem 0;">
            <a href="exam.php?module_id=<?= $id ?>" class="btn btn-primary" style="width: 100%; display: block; text-align: center;">Take Exam</a>
        </aside>

        <main class="main-content">
            <div class="content-card">
                <?php if ($current_lesson): ?>
                    <h2><?= htmlspecialchars($current_lesson['title']) ?></h2>
                    <div style="margin: 2rem 0; color: var(--text-dim);">
                        
                        <?php if ($current_lesson['content_type'] === 'video' && $current_lesson['media_url']): ?>
                            <video controls style="width: 100%; border-radius: 15px; margin-bottom: 2rem; background: #000;">
                                <source src="uploads/<?= $current_lesson['media_url'] ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php elseif ($current_lesson['content_type'] === 'audio' && $current_lesson['media_url']): ?>
                            <div style="background: var(--glass); padding: 2rem; border-radius: 15px; margin-bottom: 2rem; display: flex; align-items: center; gap: 1.5rem;">
                                <i data-lucide="headphones" size="32" style="color: var(--primary);"></i>
                                <audio controls style="flex: 1;">
                                    <source src="uploads/<?= $current_lesson['media_url'] ?>" type="audio/mpeg">
                                    Your browser does not support the audio element.
                                </audio>
                            </div>
                        <?php elseif ($current_lesson['content_type'] === 'document' && $current_lesson['media_url']): ?>
                            <div style="background: var(--glass); padding: 3rem; border-radius: 15px; margin-bottom: 2rem; text-align: center; border: 1px dashed var(--glass-border);">
                                <i data-lucide="file" size="48" style="color: var(--primary); margin-bottom: 1rem;"></i>
                                <h3>Course Document</h3>
                                <p style="margin-bottom: 2rem;">Download or view the attached document for this lesson.</p>
                                <a href="uploads/<?= $current_lesson['media_url'] ?>" target="_blank" class="btn btn-outline">
                                    <i data-lucide="download" size="18" style="margin-right: 0.5rem;"></i> View Document
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($current_lesson['content']): ?>
                            <div class="lesson-text" style="font-size: 1.1rem; line-height: 1.8;">
                                <?= nl2br(htmlspecialchars($current_lesson['content'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 4rem 0;">
                        <i data-lucide="book-open" size="64" style="color: var(--glass-border); margin-bottom: 2rem;"></i>
                        <h3>Select a lesson to begin</h3>
                        <p style="color: var(--text-dim);">Content for this module is being curated.</p>
                    </div>
                <?php endif; ?>

                <?php 
                    $prev_id = null;
                    $next_id = null;
                    foreach ($lessons as $idx => $l) {
                        if ($l['id'] == $current_lesson_id) {
                            $prev_id = isset($lessons[$idx-1]) ? $lessons[$idx-1]['id'] : null;
                            $next_id = isset($lessons[$idx+1]) ? $lessons[$idx+1]['id'] : null;
                            break;
                        }
                    }
                ?>
                <div style="display: flex; justify-content: space-between; margin-top: 4rem;">
                    <?php if ($prev_id): ?>
                        <a href="?id=<?= $id ?>&lesson_id=<?= $prev_id ?>" class="btn btn-outline">Previous Lesson</a>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>

                    <?php if ($next_id): ?>
                        <a href="?id=<?= $id ?>&lesson_id=<?= $next_id ?>" class="btn btn-primary">Next Lesson</a>
                    <?php else: ?>
                        <a href="exam.php?module_id=<?= $id ?>" class="btn btn-primary">Take Module Exam</a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
