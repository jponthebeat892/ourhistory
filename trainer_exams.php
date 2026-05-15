<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is a trainer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

$trainer_id = $_SESSION['user_id'];
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : null;

// Handle Exam Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_exam'])) {
    $module_id = $_POST['module_id'];
    $title = $_POST['title'];
    $passing_score = $_POST['passing_score'];

    $stmt = $pdo->prepare("INSERT INTO exams (module_id, title, passing_score) VALUES (?, ?, ?)");
    $stmt->execute([$module_id, $title, $passing_score]);
    $_SESSION['success'] = "Exam created successfully!";
    header("Location: trainer_exams.php");
    exit();
}

// Handle Adding a Question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $q_exam_id = $_POST['exam_id'];
    $question_text = $_POST['question_text'];
    $options = [
        $_POST['option_0'],
        $_POST['option_1'],
        $_POST['option_2'],
        $_POST['option_3']
    ];
    $correct_answer = $_POST['correct_answer']; // This is the index (0-3)
    
    $options_json = json_encode($options);

    $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, question_type, options, correct_answer) VALUES (?, ?, 'mcq', ?, ?)");
    $stmt->execute([$q_exam_id, $question_text, $options_json, $correct_answer]);
    
    $_SESSION['success'] = "Question added successfully!";
    header("Location: trainer_exams.php?exam_id=" . $q_exam_id);
    exit();
}

// Fetch trainer's modules and their exams
$stmt = $pdo->prepare("
    SELECT m.id as module_id, m.title as module_title, e.id as exam_id, e.title as exam_title, e.passing_score
    FROM modules m
    LEFT JOIN exams e ON m.id = e.module_id
    WHERE m.trainer_id = ?
    ORDER BY m.order_num ASC
");
$stmt->execute([$trainer_id]);
$modules_exams = $stmt->fetchAll();

// If viewing a specific exam, fetch its questions
$questions = [];
$current_exam = null;
if ($exam_id) {
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND module_id IN (SELECT id FROM modules WHERE trainer_id = ?)");
    $stmt->execute([$exam_id, $trainer_id]);
    $current_exam = $stmt->fetch();

    if ($current_exam) {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
        $stmt->execute([$exam_id]);
        $questions = $stmt->fetchAll();
    } else {
        header("Location: trainer_exams.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exams Management - Trainer</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .dashboard-layout { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
        .sidebar { background: var(--surface); border-right: 1px solid var(--glass-border); padding: 2rem; display: flex; flex-direction: column; gap: 0.5rem; }
        .side-link { display: flex; align-items: center; gap: 1rem; padding: 1rem; border-radius: 12px; color: var(--text-dim); text-decoration: none; transition: var(--transition); }
        .side-link:hover, .side-link.active { background: var(--glass); color: var(--primary); }
        .main-content { padding: 4rem; }
        
        .card { background: var(--surface); border: 1px solid var(--glass-border); border-radius: 15px; padding: 2rem; margin-bottom: 2rem; }
        
        .list-group { display: flex; flex-direction: column; gap: 1rem; }
        .list-item { background: var(--glass); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; }
        
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: var(--text-dim); font-size: 0.9rem; }
        .form-control { width: 100%; padding: 1rem; background: var(--glass); border: 1px solid var(--glass-border); border-radius: 12px; color: var(--text); }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: var(--surface); padding: 3rem; border-radius: 24px; width: 100%; max-width: 600px; border: 1px solid var(--glass-border); position: relative; max-height: 90vh; overflow-y: auto; }
        .close-modal { position: absolute; top: 1.5rem; right: 1.5rem; cursor: pointer; color: var(--text-dim); }
        
        .alert { padding: 1rem; border-radius: 12px; margin-bottom: 2rem; }
        .alert-success { background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); }
    </style>
</head>
<body style="background: var(--bg);">

    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="brand" style="margin-bottom: 3rem; padding: 0 1rem;">RwandaHistory.</div>
            
            <a href="trainer_dashboard.php" class="side-link">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <a href="trainer_courses.php" class="side-link">
                <i data-lucide="book-open"></i> My Courses
            </a>
            <a href="trainer_exams.php" class="side-link active">
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
                    <h1><?= $exam_id ? "Manage Exam Questions" : "Exams Management" ?></h1>
                    <p style="color: var(--text-dim);"><?= $exam_id ? htmlspecialchars($current_exam['title']) : "Create and manage exams for your courses." ?></p>
                </div>
                <div>
                    <?php if ($exam_id): ?>
                        <a href="trainer_exams.php" class="btn btn-outline">Back to Exams</a>
                    <?php endif; ?>
                </div>
            </header>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (!$exam_id): ?>
                <!-- List Modules and Exams -->
                <div class="card">
                    <h3 style="margin-bottom: 1.5rem;">Your Modules</h3>
                    <div class="list-group">
                        <?php foreach ($modules_exams as $mod): ?>
                            <div class="list-item">
                                <div>
                                    <h4><?= htmlspecialchars($mod['module_title']) ?></h4>
                                    <?php if ($mod['exam_id']): ?>
                                        <p style="color: #22c55e; font-size: 0.85rem; margin-top: 0.25rem;">Exam: <?= htmlspecialchars($mod['exam_title']) ?> (Pass: <?= $mod['passing_score'] ?>%)</p>
                                    <?php else: ?>
                                        <p style="color: var(--text-dim); font-size: 0.85rem; margin-top: 0.25rem;">No exam created yet.</p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($mod['exam_id']): ?>
                                        <a href="trainer_exams.php?exam_id=<?= $mod['exam_id'] ?>" class="btn btn-primary">Manage Questions</a>
                                    <?php else: ?>
                                        <button class="btn btn-outline" onclick="openCreateModal(<?= $mod['module_id'] ?>)">Create Exam</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($modules_exams) === 0): ?>
                            <p style="color: var(--text-dim);">You have not created any courses yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Create Exam Modal -->
                <div id="createModal" class="modal">
                    <div class="modal-content">
                        <i data-lucide="x" class="close-modal" onclick="closeModal('createModal')"></i>
                        <h2 style="margin-bottom: 2rem;">Create New Exam</h2>
                        <form method="POST">
                            <input type="hidden" name="create_exam" value="1">
                            <input type="hidden" name="module_id" id="modal_module_id" value="">
                            <div class="form-group">
                                <label>Exam Title</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Pre-Colonial History Final" required>
                            </div>
                            <div class="form-group">
                                <label>Passing Score (%)</label>
                                <input type="number" name="passing_score" class="form-control" value="70" min="1" max="100" required>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Create Exam</button>
                        </form>
                    </div>
                </div>

            <?php else: ?>
                <!-- Manage Questions for specific Exam -->
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h3>Questions (<?= count($questions) ?>)</h3>
                        <button class="btn btn-primary" onclick="openModal('addQuestionModal')">+ Add Question</button>
                    </div>

                    <div class="list-group">
                        <?php foreach ($questions as $idx => $q): 
                            $opts = json_decode($q['options'], true);
                        ?>
                            <div class="list-item" style="flex-direction: column; align-items: flex-start; gap: 1rem;">
                                <div><strong>Q<?= $idx + 1 ?>:</strong> <?= htmlspecialchars($q['question_text']) ?></div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; width: 100%; color: var(--text-dim); font-size: 0.9rem;">
                                    <?php foreach ($opts as $o_idx => $o_text): ?>
                                        <div style="<?= $q['correct_answer'] == $o_idx ? 'color: #22c55e; font-weight: bold;' : '' ?>">
                                            <?= $o_idx + 1 ?>. <?= htmlspecialchars($o_text) ?>
                                            <?= $q['correct_answer'] == $o_idx ? '(Correct)' : '' ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($questions) === 0): ?>
                            <p style="color: var(--text-dim); text-align: center; padding: 2rem;">No questions added yet. Click "+ Add Question" to begin.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add Question Modal -->
                <div id="addQuestionModal" class="modal">
                    <div class="modal-content">
                        <i data-lucide="x" class="close-modal" onclick="closeModal('addQuestionModal')"></i>
                        <h2 style="margin-bottom: 2rem;">Add Question</h2>
                        <form method="POST">
                            <input type="hidden" name="add_question" value="1">
                            <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
                            
                            <div class="form-group">
                                <label>Question Text</label>
                                <textarea name="question_text" class="form-control" rows="3" required></textarea>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label>Option 1</label>
                                    <input type="text" name="option_0" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Option 2</label>
                                    <input type="text" name="option_1" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Option 3</label>
                                    <input type="text" name="option_2" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Option 4</label>
                                    <input type="text" name="option_3" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Correct Answer (Marking Guide)</label>
                                <select name="correct_answer" class="form-control" required>
                                    <option value="0">Option 1</option>
                                    <option value="1">Option 2</option>
                                    <option value="2">Option 3</option>
                                    <option value="3">Option 4</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%;">Save Question</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();

        function openCreateModal(moduleId) {
            document.getElementById('modal_module_id').value = moduleId;
            openModal('createModal');
        }

        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
