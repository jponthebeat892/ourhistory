<?php
session_start();
require_once 'db.php';

$module_id = isset($_GET['module_id']) ? (int) $_GET['module_id'] : 1;

// Fetch module info
$stmt = $pdo->prepare("SELECT title FROM modules WHERE id = ?");
$stmt->execute([$module_id]);
$module = $stmt->fetch();

// Fetch exam for this module
$stmt = $pdo->prepare("SELECT * FROM exams WHERE module_id = ?");
$stmt->execute([$module_id]);
$exam = $stmt->fetch();

$questions = [];
$passing_score = 70;

if ($exam) {
    $passing_score = $exam['passing_score'];
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
    $stmt->execute([$exam['id']]);
    $questions = $stmt->fetchAll();
} else {
    die("<div style='text-align:center; padding: 50px; font-family: sans-serif; background: #0a0b10; color: #fff; min-height: 100vh;'><h2>No Exam Available</h2><p style='color: var(--text-dim);'>The trainer has not created an exam for this module yet.</p><a href='courses.php' style='color: #c5a059; text-decoration: none; font-weight: bold;'>&larr; Return to Courses</a></div>");
}

$is_submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$score = 0;
$total = count($questions);
$percent = 0;
$passed = false;

if ($is_submitted && $total > 0) {
    foreach ($questions as $q) {
        if (isset($_POST['q' . $q['id']]) && $_POST['q' . $q['id']] == $q['correct_answer']) {
            $score++;
        }
    }
    $percent = ($score / $total) * 100;
    $passed = $percent >= $passing_score;

    // Log Activity
    $user_id = $_SESSION['user_id'] ?? 0;
    $status_str = $passed ? 'Passed' : 'Failed';
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, activity, details) VALUES (?, 'Exam Submission', 'Student $status_str exam for module $module_id with score $percent%')");
    $stmt->execute([$user_id]);

    if ($passed && $user_id > 0) {
        // Check if certificate already exists
        $stmt = $pdo->prepare("SELECT id FROM certificates WHERE user_id = ? AND module_id = ?");
        $stmt->execute([$user_id, $module_id]);
        if (!$stmt->fetch()) {
            $cert_code = "RH-" . strtoupper(substr(md5($module_id . $user_id . time()), 0, 8));
            $stmt = $pdo->prepare("INSERT INTO certificates (user_id, module_id, cert_code) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $module_id, $cert_code]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam: <?= htmlspecialchars($module['title'] ?? 'Module') ?> - Rwanda History</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .exam-container {
            max-width: 800px;
            margin: 4rem auto;
            padding: 0 5%;
        }

        .question-card {
            background: var(--surface);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border: 1px solid var(--glass-border);
        }

        .option {
            display: block;
            padding: 1rem;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            margin-top: 1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .option:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
        }

        .option input {
            margin-right: 1rem;
        }

        .result-card {
            text-align: center;
            padding: 4rem;
            background: var(--surface);
            border-radius: 30px;
            border: 1px solid var(--glass-border);
        }

        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 8px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 800;
            margin: 2rem auto;
            color: var(--primary);
        }
    </style>
</head>

<body>

    <nav>
        <a href="index.php" class="brand">RwandaHistory.</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="courses.php">Courses</a>
            <a href="exams.php" class="active">Exams</a>
            <a href="certificates.php">Certificates</a>
            <a href="community.php">Community</a>
        </div>
    </nav>

    <div class="exam-container">
        <?php if (!$is_submitted): ?>
            <header style="margin-bottom: 3rem;">
                <h1 style="font-size: 2.5rem;"><?= htmlspecialchars($module['title'] ?? 'History Exam') ?></h1>
                <p style="color: var(--text-dim);">Please answer all questions. Passing score is 70%.</p>
            </header>

            <form method="POST">
                <?php foreach ($questions as $q): ?>
                    <div class="question-card">
                        <h3 style="margin-bottom: 1.5rem;">Question <?= $q['id'] ?>: <?= htmlspecialchars($q['question']) ?>
                        </h3>
                        <?php foreach ($q['options'] as $idx => $opt): ?>
                            <label class="option">
                                <input type="radio" name="q<?= $q['id'] ?>" value="<?= $idx ?>" required>
                                <?= htmlspecialchars($opt) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <div style="text-align: center; margin-top: 4rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 1rem 4rem; font-size: 1.1rem;">Submit
                        Exam</button>
                </div>
            </form>
        <?php else: ?>
            <div class="result-card glass">
                <i data-lucide="<?= $passed ? 'check-circle' : 'alert-circle' ?>" size="64"
                    style="color: <?= $passed ? '#4ade80' : '#f87171' ?>;"></i>
                <h1 style="margin-top: 1rem;"><?= $passed ? 'Congratulations!' : 'Keep Practicing' ?></h1>
                <p style="color: var(--text-dim);">
                    <?= $passed ? 'You have successfully passed the exam.' : 'You did not reach the 70% threshold.' ?></p>

                <div class="score-circle">
                    <?= round($percent) ?>%
                </div>

                <p style="margin-bottom: 2rem;">Score: <?= $score ?> / <?= $total ?></p>

                <?php if ($passed): ?>
                    <a href="certificate.php?module_id=<?= $module_id ?>" class="btn btn-primary">Generate Certificate</a>
                <?php else: ?>
                    <a href="exam.php?module_id=<?= $module_id ?>" class="btn btn-outline">Retry Exam</a>
                <?php endif; ?>
                <br><br>
                <a href="index.php" style="color: var(--text-dim); text-decoration: none;">Return Home</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>