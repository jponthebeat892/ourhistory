<?php
require_once 'db.php';

// Handle Trainer Registration
$registration_success = null;
if (isset($_POST['register_trainer'])) {
    $name = $_POST['trainer_name'];
    $email = $_POST['trainer_email'];
    $password = password_hash($_POST['trainer_password'], PASSWORD_DEFAULT);
    $login_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6)); // Generate 6-char code

    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, login_code) VALUES (?, ?, ?, 'trainer', ?)");
        $stmt->execute([$name, $email, $password, $login_code]);
        $registration_success = "Trainer registered successfully! Code: <strong>$login_code</strong>";
        
        // Log activity
        $stmt = $pdo->prepare("INSERT INTO activity_log (activity, details) VALUES ('Trainer Registration', 'Admin registered trainer $name ($email)')");
        $stmt->execute();
    } catch (PDOException $e) {
        $registration_success = "Error: " . $e->getMessage();
    }
}

// Handle Signature Saving
if (isset($_POST['save_signature'])) {
    $signature_data = $_POST['signature_data'];
    $stmt = $pdo->prepare("INSERT INTO settings (id, admin_signature) VALUES (1, ?) ON DUPLICATE KEY UPDATE admin_signature = ?");
    $stmt->execute([$signature_data, $signature_data]);
    
    // Log activity
    $stmt = $pdo->prepare("INSERT INTO activity_log (activity, details) VALUES ('Signature Update', 'Admin updated official signature')");
    $stmt->execute();
}

// Handle Live Session Approval
$live_msg = null;
if (isset($_POST['approve_live'])) {
    $session_id = $_POST['session_id'];
    $join_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    $stmt = $pdo->prepare("UPDATE live_sessions SET status = 'approved', join_code = ? WHERE id = ?");
    $stmt->execute([$join_code, $session_id]);
    $live_msg = "Live session approved! Join code: <strong>$join_code</strong>";
}

// Fetch pending live sessions
$pending_lives = $pdo->query("SELECT l.*, u.full_name FROM live_sessions l JOIN users u ON l.trainer_id = u.id WHERE l.status = 'pending' ORDER BY l.created_at ASC")->fetchAll();

// Fetch Activities
$activities = $pdo->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10")->fetchAll();

// Fetch Settings
$stmt = $pdo->prepare("SELECT admin_signature FROM settings WHERE id = 1");
$stmt->execute();
$settings = $stmt->fetch();
$existing_signature = $settings['admin_signature'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Rwanda History</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .admin-layout { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
        .sidebar { background: #0f1115; border-right: 1px solid var(--glass-border); padding: 2rem; }
        .main-content { padding: 3rem; }
        .card { background: var(--surface); border-radius: 20px; padding: 2rem; border: 1px solid var(--glass-border); margin-bottom: 2rem; }
        
        /* Signature Canvas */
        #sig-canvas { border: 2px dashed var(--glass-border); border-radius: 10px; background: #fff; cursor: crosshair; }
        .canvas-container { margin-top: 1rem; }

        input { width: 100%; padding: 0.8rem; margin-bottom: 1rem; background: var(--glass); border: 1px solid var(--glass-border); color: #fff; border-radius: 8px; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: var(--text-dim); }
        
        .activity-item { padding: 1rem; border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; }
        .activity-item:last-child { border-bottom: none; }
    </style>
</head>
<body>

    <div class="admin-layout">
        <aside class="sidebar">
            <div class="brand" style="margin-bottom: 3rem;">Admin Panel</div>
            <nav style="display: flex; flex-direction: column; gap: 1rem;">
                <a href="#" class="btn btn-outline" style="text-align: left; border: none;"><i data-lucide="users"></i> Trainers</a>
                <a href="#" class="btn btn-outline" style="text-align: left; border: none;"><i data-lucide="activity"></i> System Activity</a>
                <a href="#" class="btn btn-outline" style="text-align: left; border: none;"><i data-lucide="award"></i> Certificates</a>
                <a href="index.php" class="btn btn-outline" style="text-align: left; border: none; margin-top: 2rem; color: #f87171;"><i data-lucide="log-out"></i> Exit</a>
            </nav>
        </aside>

        <main class="main-content">
            <h1 style="margin-bottom: 2rem;">Platform Oversight</h1>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Register Trainer -->
                <div class="card">
                    <h3><i data-lucide="user-plus"></i> Register New Trainer</h3>
                    <p style="color: var(--text-dim); font-size: 0.9rem; margin-bottom: 1.5rem;">Create an account and generate a login code.</p>
                    
                    <?php if ($registration_success): ?>
                        <div class="glass" style="padding: 1rem; margin-bottom: 1rem; border-color: var(--primary); color: var(--primary);">
                            <?= $registration_success ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="trainer_name" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="trainer_email" required>
                        </div>
                        <div class="form-group">
                            <label>Temporary Password</label>
                            <input type="password" name="trainer_password" required>
                        </div>
                        <button type="submit" name="register_trainer" class="btn btn-primary" style="width: 100%;">Register & Generate Code</button>
                    </form>
                </div>

                <!-- Signature Canvas -->
                <div class="card">
                    <h3><i data-lucide="pen-tool"></i> Digital Signature</h3>
                    <p style="color: var(--text-dim); font-size: 0.9rem; margin-bottom: 1rem;">Draw your official signature for certificates.</p>
                    
                    <div class="canvas-container">
                        <canvas id="sig-canvas" width="400" height="150"></canvas>
                        <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                            <button id="sig-clearBtn" class="btn btn-outline">Clear</button>
                            <form method="POST" id="sig-form">
                                <input type="hidden" name="signature_data" id="sig-dataInput">
                                <button type="submit" name="save_signature" id="sig-submitBtn" class="btn btn-primary">Save Signature</button>
                            </form>
                        </div>
                    </div>
                    
                    <?php if ($existing_signature): ?>
                    <div style="margin-top: 2rem; border-top: 1px solid var(--glass-border); padding-top: 1rem;">
                        <p style="font-size: 0.8rem; color: var(--text-dim);">Current saved signature:</p>
                        <img src="<?= $existing_signature ?>" style="max-width: 200px; background: white; border-radius: 5px; margin-top: 0.5rem;">
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Live Session Requests -->
            <div class="card">
                <h3><i data-lucide="video"></i> Live Session Requests</h3>
                <p style="color: var(--text-dim); font-size: 0.9rem; margin-bottom: 1.5rem;">Review and approve teacher requests to host live sessions.</p>
                
                <?php if ($live_msg): ?>
                    <div class="glass" style="padding: 1rem; margin-bottom: 1.5rem; border-color: var(--primary); color: var(--primary);">
                        <?= $live_msg ?>
                    </div>
                <?php endif; ?>

                <?php if (count($pending_lives) > 0): ?>
                    <table style="width: 100%; text-align: left; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <th style="padding: 1rem 0; color: var(--text-dim);">Trainer</th>
                                <th style="padding: 1rem 0; color: var(--text-dim);">Topic/Title</th>
                                <th style="padding: 1rem 0; color: var(--text-dim);">Requested On</th>
                                <th style="padding: 1rem 0; text-align: right; color: var(--text-dim);">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_lives as $live): ?>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <td style="padding: 1rem 0;"><strong><?= htmlspecialchars($live['full_name']) ?></strong></td>
                                <td style="padding: 1rem 0;"><?= htmlspecialchars($live['title']) ?></td>
                                <td style="padding: 1rem 0; font-size: 0.9rem; color: var(--text-dim);"><?= date('M d, Y H:i', strtotime($live['created_at'])) ?></td>
                                <td style="padding: 1rem 0; text-align: right;">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="session_id" value="<?= $live['id'] ?>">
                                        <button type="submit" name="approve_live" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.85rem; border-color: #22c55e; color: #22c55e;">Approve</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; border: 1px dashed var(--glass-border); border-radius: 12px; color: var(--text-dim);">
                        No pending live session requests.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Activity Logs -->
            <div class="card">
                <h3><i data-lucide="list"></i> Recent System Activities</h3>
                <div style="margin-top: 1.5rem;">
                    <?php foreach ($activities as $act): ?>
                    <div class="activity-item">
                        <div>
                            <strong><?= htmlspecialchars($act['activity']) ?></strong>
                            <p style="color: var(--text-dim); font-size: 0.85rem;"><?= htmlspecialchars($act['details']) ?></p>
                        </div>
                        <span style="font-size: 0.75rem; color: var(--text-dim);"><?= date('M d, H:i', strtotime($act['created_at'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        // Signature Canvas Logic
        (function() {
            window.requestAnimFrame = (function (callback) {
                return window.requestAnimationFrame || 
                    window.webkitRequestAnimationFrame ||
                    window.mozRequestAnimationFrame ||
                    window.oRequestAnimationFrame ||
                    window.msRequestAnimaitonFrame ||
                    function (callback) {
                        window.setTimeout(callback, 1000/60);
                    };
            })();

            var canvas = document.getElementById("sig-canvas");
            var ctx = canvas.getContext("2d");
            ctx.strokeStyle = "#222222";
            ctx.lineWidth = 4;

            var drawing = false;
            var mousePos = { x:0, y:0 };
            var lastPos = mousePos;

            canvas.addEventListener("mousedown", function (e) {
                drawing = true;
                lastPos = getMousePos(canvas, e);
            }, false);

            canvas.addEventListener("mouseup", function (e) {
                drawing = false;
            }, false);

            canvas.addEventListener("mousemove", function (e) {
                mousePos = getMousePos(canvas, e);
            }, false);

            // Add touch events for mobile
            canvas.addEventListener("touchstart", function (e) {
                mousePos = getTouchPos(canvas, e);
                var touch = e.touches[0];
                var mouseEvent = new MouseEvent("mousedown", {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            }, false);

            canvas.addEventListener("touchend", function (e) {
                var mouseEvent = new MouseEvent("mouseup", {});
                canvas.dispatchEvent(mouseEvent);
            }, false);

            canvas.addEventListener("touchmove", function (e) {
                var touch = e.touches[0];
                var mouseEvent = new MouseEvent("mousemove", {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            }, false);

            function getMousePos(canvasDom, mouseEvent) {
                var rect = canvasDom.getBoundingClientRect();
                return {
                    x: mouseEvent.clientX - rect.left,
                    y: mouseEvent.clientY - rect.top
                };
            }

            function getTouchPos(canvasDom, touchEvent) {
                var rect = canvasDom.getBoundingClientRect();
                return {
                    x: touchEvent.touches[0].clientX - rect.left,
                    y: touchEvent.touches[0].clientY - rect.top
                };
            }

            function renderCanvas() {
                if (drawing) {
                    ctx.moveTo(lastPos.x, lastPos.y);
                    ctx.lineTo(mousePos.x, mousePos.y);
                    ctx.stroke();
                    lastPos = mousePos;
                }
            }

            (function drawLoop () {
                requestAnimFrame(drawLoop);
                renderCanvas();
            })();

            document.getElementById("sig-clearBtn").addEventListener("click", function (e) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.beginPath();
            }, false);

            document.getElementById("sig-submitBtn").addEventListener("click", function (e) {
                var dataUrl = canvas.toDataURL();
                document.getElementById("sig-dataInput").value = dataUrl;
            }, false);

        })();
    </script>
</body>
</html>
