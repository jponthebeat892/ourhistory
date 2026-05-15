<?php
session_start();
require_once 'db.php';

$return_page = 'index.php';
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'trainer') {
        $return_page = 'trainer_dashboard.php';
    } elseif ($_SESSION['role'] === 'student') {
        $return_page = 'students_dashboard.php';
    }
}

if (!isset($_SESSION['user_id']) || !isset($_GET['code'])) {
    header("Location: $return_page");
    exit();
}

$code = $_GET['code'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch the session
$stmt = $pdo->prepare("SELECT l.*, u.full_name as trainer_name FROM live_sessions l JOIN users u ON l.trainer_id = u.id WHERE l.join_code = ?");
$stmt->execute([$code]);
$live = $stmt->fetch();

if (!$live || $live['status'] === 'ended' || $live['status'] === 'pending') {
    $_SESSION['error'] = "This live session is no longer active or does not exist.";
    header("Location: $return_page");
    exit();
}

// If the trainer joins an approved session, set it to live
if ($role === 'trainer' && $live['trainer_id'] == $user_id && $live['status'] === 'approved') {
    $stmt = $pdo->prepare("UPDATE live_sessions SET status = 'live' WHERE id = ?");
    $stmt->execute([$live['id']]);
    $live['status'] = 'live'; // Update local variable for UI
}

// If it's still approved and a student tries to join, they shouldn't be here since the button only shows when it's live, but just in case:
if ($role !== 'trainer' && $live['status'] !== 'live') {
    $_SESSION['error'] = "The trainer has not started this session yet.";
    header("Location: $return_page");
    exit();
}

// End session handler (Trainer only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['end_session']) && $role === 'trainer' && $live['trainer_id'] == $user_id) {
    $stmt = $pdo->prepare("UPDATE live_sessions SET status = 'ended' WHERE id = ?");
    $stmt->execute([$live['id']]);
    header("Location: community.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($live['title']) ?> - Live Session</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .live-container {
            display: grid;
            grid-template-columns: 1fr 300px;
            height: 100vh;
            background: #000;
        }
        .video-area {
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .main-video {
            flex: 1;
            background: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .video-controls {
            background: var(--surface);
            padding: 1rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
            border-top: 1px solid var(--glass-border);
        }
        .control-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }
        .control-btn:hover { background: var(--primary); border-color: var(--primary); }
        .control-btn.danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.2); }
        .control-btn.danger:hover { background: #ef4444; color: #fff; }

        .chat-area {
            background: var(--surface);
            border-left: 1px solid var(--glass-border);
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            padding: 1rem;
            border-bottom: 1px solid var(--glass-border);
            font-weight: 600;
        }
        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .message { font-size: 0.9rem; }
        .message strong { color: var(--primary); }
        .chat-input {
            padding: 1rem;
            border-top: 1px solid var(--glass-border);
            display: flex;
            gap: 0.5rem;
        }
        .chat-input input {
            flex: 1;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            padding: 0.8rem;
            border-radius: 12px;
            color: #fff;
        }
        .chat-input button {
            background: var(--primary);
            border: none;
            padding: 0.8rem;
            border-radius: 12px;
            color: #111;
            cursor: pointer;
        }

        .live-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: #ef4444;
            color: #fff;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .live-dot {
            width: 8px;
            height: 8px;
            background: #fff;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="live-container">
        <div class="video-area">
            <div class="main-video">
                <div class="live-badge"><div class="live-dot"></div> LIVE</div>
                
                <div style="text-align: center;">
                    <div style="width: 120px; height: 120px; background: var(--surface); border-radius: 50%; margin: 0 auto 1.5rem auto; display: flex; align-items: center; justify-content: center; border: 2px solid var(--primary);">
                        <i data-lucide="user" size="64" style="color: var(--text-dim);"></i>
                    </div>
                    <h2><?= htmlspecialchars($live['title']) ?></h2>
                    <p style="color: var(--text-dim); margin-top: 0.5rem;">Instructor: <?= htmlspecialchars($live['trainer_name']) ?></p>
                    
                    <?php if ($role === 'trainer'): ?>
                        <p style="color: var(--primary); margin-top: 2rem; font-size: 0.9rem;">(Mock Interface: Camera and Microphone would be active here)</p>
                    <?php endif; ?>
                </div>

                <!-- Top Right Controls -->
                <a href="<?= htmlspecialchars($return_page) ?>" style="position: absolute; top: 20px; right: 20px; color: #fff; text-decoration: none; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 12px;"><i data-lucide="arrow-left" size="18" style="vertical-align: middle;"></i> Back</a>
            </div>

            <div class="video-controls">
                <button class="control-btn"><i data-lucide="mic"></i></button>
                <button class="control-btn"><i data-lucide="video"></i></button>
                <button class="control-btn"><i data-lucide="monitor-up"></i></button>
                <button class="control-btn"><i data-lucide="message-circle"></i></button>
                
                <?php if ($role === 'trainer' && $live['trainer_id'] == $user_id): ?>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="end_session" value="1">
                        <button type="submit" class="control-btn danger" title="End Live Session"><i data-lucide="phone-off"></i></button>
                    </form>
                <?php else: ?>
                    <a href="community.php" class="control-btn danger" title="Leave Session"><i data-lucide="phone-off"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-area">
            <div class="chat-header">
                Live Chat
            </div>
            <div class="chat-messages">
                <div class="message">
                    <strong>System:</strong> Welcome to the live session!
                </div>
                <div class="message">
                    <strong><?= htmlspecialchars($live['trainer_name']) ?> (Instructor):</strong> Hello everyone, we will begin shortly.
                </div>
            </div>
            <div class="chat-input">
                <input type="text" placeholder="Type a message...">
                <button><i data-lucide="send" size="18"></i></button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
