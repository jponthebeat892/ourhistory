<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

// Handle Live Session Request (Trainer)
$live_msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_live'])) {
    $title = trim($_POST['live_title']);
    if ($title !== '') {
        $trainer_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("INSERT INTO live_sessions (trainer_id, title) VALUES (?, ?)");
        $stmt->execute([$trainer_id, $title]);
        $live_msg = "Live session requested successfully! Awaiting admin approval.";
    }
}

// Fetch stats
$total_students = 1240;
$total_courses = 5;
$active_exams = 8;

// Fetch modules for the upload form created by this trainer
$stmt = $pdo->prepare("SELECT id, title FROM modules WHERE trainer_id = ? ORDER BY order_num ASC");
$stmt->execute([$_SESSION['user_id']]);
$modules = $stmt->fetchAll();

// Fetch trainer's live sessions
$stmt = $pdo->prepare("SELECT * FROM live_sessions WHERE trainer_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$my_lives = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - Rwanda History</title>
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
        .upload-section {
            background: var(--glass);
            padding: 3rem;
            border-radius: 30px;
            border: 2px dashed var(--glass-border);
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }
        .upload-section:hover {
            border-color: var(--primary);
            background: rgba(197, 160, 89, 0.05);
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: var(--surface);
            padding: 3rem;
            border-radius: 24px;
            width: 100%;
            max-width: 600px;
            border: 1px solid var(--glass-border);
            position: relative;
        }
        .close-modal {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            cursor: pointer;
            color: var(--text-dim);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dim);
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 1rem;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: var(--text);
            font-family: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .alert-success { background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
    </style>
</head>
<body style="background: var(--bg);">

    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="brand" style="margin-bottom: 3rem; padding: 0 1rem;">RwandaHistory.</div>
            
            <a href="#" class="side-link active">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <a href="trainer_courses.php" class="side-link">
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
                    <h1>Welcome back, Trainer!</h1>
                    <p style="color: var(--text-dim);">Here's what's happening with your courses today.</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success" style="margin-bottom: 0;"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-error" style="margin-bottom: 0;"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    <?php endif; ?>
                    <button class="btn btn-outline"><i data-lucide="bell" size="20"></i></button>
                    <div style="width: 45px; height: 45px; background: var(--primary); border-radius: 50%;"></div>
                </div>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <h1><?= $total_students ?></h1>
                    <p style="color: var(--text-dim);">Total Students</p>
                </div>
                <div class="stat-card">
                    <h1><?= $total_courses ?></h1>
                    <p style="color: var(--text-dim);">Active Modules</p>
                </div>
                <div class="stat-card">
                    <h1><?= $active_exams ?></h1>
                    <p style="color: var(--text-dim);">Exams Created</p>
                </div>
                <div class="stat-card">
                    <h1>92%</h1>
                    <p style="color: var(--text-dim);">Avg. Pass Rate</p>
                </div>
            </div>

            <section style="margin-bottom: 4rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <div>
                        <h2>Live Sessions</h2>
                        <p style="color: var(--text-dim);">Request or start live sessions from your dashboard.</p>
                    </div>
                </div>

                <?php if ($live_msg): ?>
                    <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?= $live_msg ?></div>
                <?php endif; ?>

                <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
                    <form method="POST" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                        <input type="text" name="live_title" placeholder="Topic of your live session..." required class="form-control" style="flex: 1; min-width: 220px;">
                        <button type="submit" name="request_live" class="btn btn-primary" style="min-width: 180px;">Request Live</button>
                    </form>
                </div>

                <?php if (count($my_lives) > 0): ?>
                    <div style="display: grid; gap: 1rem;">
                        <?php foreach ($my_lives as $live): ?>
                            <div class="card" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                                <div>
                                    <strong><?= htmlspecialchars($live['title']) ?></strong>
                                    <p style="margin: 0.5rem 0 0; color: var(--text-dim); font-size: 0.9rem;">Status: <span style="color: <?= $live['status'] === 'approved' ? '#22c55e' : ($live['status'] === 'live' ? '#ef4444' : 'var(--text-dim)') ?>;"><?= ucfirst($live['status']) ?></span></p>
                                    <?php if ($live['join_code']): ?>
                                        <p style="margin: 0.35rem 0 0; color: var(--text-dim); font-size: 0.85rem;">Code: <strong><?= htmlspecialchars($live['join_code']) ?></strong></p>
                                    <?php endif; ?>
                                </div>
                                <?php if ($live['status'] === 'approved' || $live['status'] === 'live'): ?>
                                    <a href="live_room.php?code=<?= htmlspecialchars($live['join_code']) ?>" class="btn btn-primary" style="background: #ef4444; border-color: #ef4444;"><?= $live['status'] === 'live' ? 'Rejoin Live' : 'Start Live' ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card" style="color: var(--text-dim);">You have not requested any live sessions yet.</div>
                <?php endif; ?>
            </section>

            <section style="margin-bottom: 4rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>Quick Upload</h2>
                    <p style="color: var(--primary); font-weight: 600; cursor: pointer;">Upload Guidelines &rarr;</p>
                </div>

                <div class="upload-section" onclick="openModal()">
                    <i data-lucide="cloud-upload" size="48" style="color: var(--primary); margin-bottom: 1rem;"></i>
                    <h3>Upload New Course Content</h3>
                    <p style="color: var(--text-dim); margin-top: 0.5rem;">Drag and drop videos, PDFs, or audio files here.</p>
                    <button class="btn btn-primary" style="margin-top: 2rem;">Select Files</button>
                </div>
            </section>

            <!-- Upload Modal -->
            <div id="uploadModal" class="modal">
                <div class="modal-content">
                    <i data-lucide="x" class="close-modal" onclick="closeModal()"></i>
                    <h2 style="margin-bottom: 2rem;">Upload New Lesson</h2>
                    
                    <form action="upload_lesson.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Select Course (Module)</label>
                            <select name="module_id" class="form-control" required>
                                <?php foreach ($modules as $mod): ?>
                                    <option value="<?= $mod['id'] ?>"><?= htmlspecialchars($mod['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Lesson Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. The Legend of Gihanga" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Lesson Type</label>
                            <select name="content_type" id="content_type" class="form-control" required onchange="toggleContentInput()">
                                <option value="document">Document (can contain images/text)</option>
                                <option value="video">Video Only</option>
                                <option value="audio">Audio Only</option>
                                <option value="text">Rich Text</option>
                            </select>
                        </div>

                        <div class="form-group" id="text_input_group">
                            <label>Lesson Text Content</label>
                            <textarea name="content" class="form-control" rows="5" placeholder="Enter lesson text here..."></textarea>
                        </div>

                        <div class="form-group" id="file_input_group" style="display: none;">
                            <label id="file_label">Upload File</label>
                            <input type="file" name="media_file" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Order Number</label>
                            <input type="number" name="order_num" class="form-control" value="1">
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Upload Lesson</button>
                    </form>
                </div>
            </div>

            <section>
                <h2>Recent Activity</h2>
                <div class="glass" style="margin-top: 2rem; padding: 2rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 1.5rem; border-bottom: 1px solid var(--glass-border);">
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <i data-lucide="user-check" style="color: var(--primary);"></i>
                            <span><strong>Kamanzi Eric</strong> passed "Pre-Colonial Rwanda" exam.</span>
                        </div>
                        <span style="color: var(--text-dim); font-size: 0.8rem;">5 mins ago</span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 1.5rem 0; border-bottom: 1px solid var(--glass-border);">
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <i data-lucide="message-square" style="color: var(--primary);"></i>
                            <span>New discussion post in "Module 4" by <strong>Alice U.</strong></span>
                        </div>
                        <span style="color: var(--text-dim); font-size: 0.8rem;">12 mins ago</span>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        lucide.createIcons();

        function openModal() {
            document.getElementById('uploadModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('uploadModal').style.display = 'none';
        }

        function toggleContentInput() {
            const type = document.getElementById('content_type').value;
            const textGroup = document.getElementById('text_input_group');
            const fileGroup = document.getElementById('file_input_group');
            const fileLabel = document.getElementById('file_label');

            if (type === 'text') {
                textGroup.style.display = 'block';
                fileGroup.style.display = 'none';
            } else {
                textGroup.style.display = 'block'; // Keep text for description if needed
                fileGroup.style.display = 'block';
                fileLabel.innerText = `Upload ${type.charAt(0).toUpperCase() + type.slice(1)} File`;
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('uploadModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
