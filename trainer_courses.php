<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is a trainer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

$trainer_id = $_SESSION['user_id'];

// Handle new course creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_course'])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $order_num = $_POST['order_num'] ?? 1;
    $thumbnail = 'default_course.jpg'; // default thumbnail

    if (!empty($title)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO modules (title, description, thumbnail, order_num, trainer_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $thumbnail, $order_num, $trainer_id]);
            $_SESSION['success'] = "Course created successfully!";
            header("Location: trainer_courses.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch courses created by this trainer
$stmt = $pdo->prepare("SELECT * FROM modules WHERE trainer_id = ? ORDER BY order_num ASC");
$stmt->execute([$trainer_id]);
$courses = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Trainer Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .dashboard-layout { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
        .sidebar { background: var(--surface); border-right: 1px solid var(--glass-border); padding: 2rem; display: flex; flex-direction: column; gap: 0.5rem; }
        .side-link { display: flex; align-items: center; gap: 1rem; padding: 1rem; border-radius: 12px; color: var(--text-dim); text-decoration: none; transition: var(--transition); }
        .side-link:hover, .side-link.active { background: var(--glass); color: var(--primary); }
        .main-content { padding: 4rem; }
        .course-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem; }
        .course-card { background: var(--surface); border: 1px solid var(--glass-border); border-radius: 20px; overflow: hidden; transition: var(--transition); }
        .course-card:hover { transform: translateY(-5px); border-color: var(--primary); }
        .course-thumb { height: 160px; background-color: var(--glass); display: flex; align-items: center; justify-content: center; color: var(--text-dim); }
        .course-info { padding: 1.5rem; }
        .course-info h3 { margin-bottom: 0.5rem; color: #fff; }
        .course-info p { color: var(--text-dim); font-size: 0.9rem; margin-bottom: 1rem; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: var(--surface); padding: 3rem; border-radius: 24px; width: 100%; max-width: 500px; border: 1px solid var(--glass-border); position: relative; }
        .close-modal { position: absolute; top: 1.5rem; right: 1.5rem; cursor: pointer; color: var(--text-dim); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: var(--text-dim); font-size: 0.9rem; }
        .form-control { width: 100%; padding: 1rem; background: var(--glass); border: 1px solid var(--glass-border); border-radius: 12px; color: var(--text); font-family: inherit; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        .alert { padding: 1rem; border-radius: 12px; margin-bottom: 2rem; }
        .alert-success { background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
    </style>
</head>
<body style="background: var(--bg);">

    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="brand" style="margin-bottom: 3rem; padding: 0 1rem;">RwandaHistory.</div>
            
            <a href="trainer_dashboard.php" class="side-link">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <a href="trainer_courses.php" class="side-link active">
                <i data-lucide="book-open"></i> My Courses
            </a>
            <a href="trainer_exams.php" class="side-link">
                <i data-lucide="file-text"></i> Exams
            </a>
            <a href="#" class="side-link">
                <i data-lucide="users"></i> Students
            </a>
            <a href="#" class="side-link">
                <i data-lucide="message-square"></i> Community
            </a>
            <a href="#" class="side-link">
                <i data-lucide="settings"></i> Settings
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
                    <h1>My Courses</h1>
                    <p style="color: var(--text-dim);">Manage the courses you have created.</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button class="btn btn-primary" onclick="openModal()">+ Create New Course</button>
                </div>
            </header>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <?php if (count($courses) > 0): ?>
                <div class="course-grid">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <div class="course-thumb">
                                <i data-lucide="book" size="48"></i>
                            </div>
                            <div class="course-info">
                                <h3><?= htmlspecialchars($course['title']) ?></h3>
                                <p><?= htmlspecialchars(substr($course['description'], 0, 80)) ?>...</p>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="color: var(--text-dim); font-size: 0.8rem;">Module <?= $course['order_num'] ?></span>
                                    <a href="#" style="color: var(--primary); text-decoration: none; font-size: 0.9rem; font-weight: 600;">Edit Course &rarr;</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem; background: var(--glass); border: 1px dashed var(--glass-border); border-radius: 20px;">
                    <i data-lucide="folder-open" size="48" style="color: var(--text-dim); margin-bottom: 1rem;"></i>
                    <h3 style="margin-bottom: 0.5rem;">No Courses Yet</h3>
                    <p style="color: var(--text-dim); margin-bottom: 2rem;">You haven't created any courses. Start by creating your first course.</p>
                    <button class="btn btn-primary" onclick="openModal()">Create First Course</button>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Create Course Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <i data-lucide="x" class="close-modal" onclick="closeModal()"></i>
            <h2 style="margin-bottom: 2rem;">Create New Course</h2>
            
            <form method="POST">
                <input type="hidden" name="create_course" value="1">
                <div class="form-group">
                    <label>Course Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. History of Rwandan Kings" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Brief description of the course..." required></textarea>
                </div>
                <div class="form-group">
                    <label>Module/Order Number</label>
                    <input type="number" name="order_num" class="form-control" value="1" min="1" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Create Course</button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function openModal() {
            document.getElementById('createModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('createModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('createModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
