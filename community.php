<?php
session_start();
require_once 'db.php';

// Handle Live Session Request (Trainer)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_live']) && $_SESSION['role'] === 'trainer') {
    $title = $_POST['live_title'];
    $trainer_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("INSERT INTO live_sessions (trainer_id, title) VALUES (?, ?)");
    $stmt->execute([$trainer_id, $title]);
    $msg = "Live session requested successfully! Awaiting admin approval.";
}

// Fetch Live Sessions based on role
$my_lives = [];
$active_lives = [];
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'trainer') {
        $stmt = $pdo->prepare("SELECT * FROM live_sessions WHERE trainer_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $my_lives = $stmt->fetchAll();
    } else {
        // Students/others see active live sessions
        $stmt = $pdo->query("SELECT l.*, u.full_name FROM live_sessions l JOIN users u ON l.trainer_id = u.id WHERE l.status = 'live' ORDER BY l.created_at DESC");
        $active_lives = $stmt->fetchAll();
    }
}

// Fetch some mock forum posts
$posts = [
    ['user' => 'Gasana M.', 'title' => 'The significance of Ubwiru in modern governance', 'comments' => 12, 'time' => '2h ago'],
    ['user' => 'Ingabire J.', 'title' => 'Reflections on the reconstruction after 1994', 'comments' => 24, 'time' => '5h ago'],
    ['user' => 'Karemera A.', 'title' => 'Digital archiving of oral history traditions', 'comments' => 8, 'time' => '1d ago']
];

$workshops = [
    ['title' => 'Genealogy of Rwandan Kings', 'date' => 'May 20, 2026', 'instructor' => 'Dr. Mugenzi'],
    ['title' => 'Digital Mapping of Historical Sites', 'date' => 'June 05, 2026', 'instructor' => 'Eng. Umutoni']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community - Rwanda History</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .community-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
            padding: 4rem 5%;
        }
        .post-card {
            background: var(--surface);
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            border: 1px solid var(--glass-border);
            transition: var(--transition);
        }
        .post-card:hover {
            border-color: var(--primary);
        }
        .workshop-card {
            background: var(--glass);
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 1rem;
            border: 1px solid var(--glass-border);
        }
        .tab-nav {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--glass-border);
            padding-bottom: 1rem;
        }
        .tab-link {
            color: var(--text-dim);
            text-decoration: none;
            font-weight: 600;
            position: relative;
        }
        .tab-link.active {
            color: var(--primary);
        }
        .tab-link.active::after {
            content: '';
            position: absolute;
            bottom: -1rem;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--primary);
        }
        @media (max-width: 992px) {
            .community-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <nav>
        <a href="index.php" class="brand">RwandaHistory.</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="courses.php">Courses</a>
            <a href="exams.php">Exams</a>
            <a href="certificates.php">Certificates</a>
            <a href="community.php" class="active">Community</a>
        </div>
    </nav>

    <main class="community-layout">
        <section>
            <div class="tab-nav">
                <a href="#" class="tab-link active">Discussion Forums</a>
                <a href="#" class="tab-link">Announcements</a>
                <a href="#" class="tab-link">Study Groups</a>
            </div>

            <!-- Live Session Panel -->
            <?php if (isset($_SESSION['role'])): ?>
                <div class="glass" style="padding: 2rem; border-radius: 20px; margin-bottom: 2rem; border: 1px solid var(--primary);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2 style="display: flex; align-items: center; gap: 0.5rem;"><i data-lucide="video" style="color: var(--primary);"></i> Live Sessions</h2>
                    </div>

                    <?php if (isset($msg)): ?>
                        <div style="background: rgba(34, 197, 94, 0.1); color: #22c55e; padding: 1rem; border-radius: 12px; margin-bottom: 1rem; border: 1px solid rgba(34, 197, 94, 0.2);">
                            <?= $msg ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($_SESSION['role'] === 'trainer'): ?>
                        <form method="POST" style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                            <input type="text" name="live_title" placeholder="Topic of your live session..." required style="flex: 1; padding: 1rem; background: var(--surface); border: 1px solid var(--glass-border); border-radius: 12px; color: #fff;">
                            <button type="submit" name="request_live" class="btn btn-primary" style="white-space: nowrap;">Request Live</button>
                        </form>
                        
                        <?php if (count($my_lives) > 0): ?>
                            <h4>My Live Requests</h4>
                            <div style="margin-top: 1rem;">
                                <?php foreach ($my_lives as $live): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: var(--surface); border-radius: 12px; margin-bottom: 0.5rem; border: 1px solid var(--glass-border);">
                                        <div>
                                            <strong><?= htmlspecialchars($live['title']) ?></strong>
                                            <span style="font-size: 0.8rem; color: var(--text-dim); margin-left: 1rem;">Status: <span style="color: <?= $live['status'] === 'approved' ? '#22c55e' : ($live['status'] === 'live' ? '#ef4444' : 'var(--primary)') ?>;"><?= ucfirst($live['status']) ?></span></span>
                                        </div>
                                        <?php if ($live['status'] === 'approved'): ?>
                                            <div style="display: flex; gap: 1rem; align-items: center;">
                                                <span style="font-size: 0.85rem; color: var(--text-dim);">Code: <strong><?= $live['join_code'] ?></strong></span>
                                                <a href="live_room.php?code=<?= $live['join_code'] ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem; background: #ef4444; border-color: #ef4444;">Start Live</a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($live['status'] === 'live'): ?>
                                            <a href="live_room.php?code=<?= $live['join_code'] ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem; background: #ef4444; border-color: #ef4444;">Rejoin Live</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--text-dim); font-size: 0.9rem;">You haven't requested any live sessions yet.</p>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Student View -->
                        <?php if (count($active_lives) > 0): ?>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <?php foreach ($active_lives as $live): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; background: var(--surface); border-radius: 12px; border: 1px solid rgba(239, 68, 68, 0.3);">
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <div style="width: 12px; height: 12px; background: #ef4444; border-radius: 50%; box-shadow: 0 0 10px #ef4444;"></div>
                                            <div>
                                                <strong><?= htmlspecialchars($live['title']) ?></strong>
                                                <p style="font-size: 0.8rem; color: var(--text-dim);">Hosted by <?= htmlspecialchars($live['full_name']) ?></p>
                                            </div>
                                        </div>
                                        <a href="live_room.php?code=<?= $live['join_code'] ?>" class="btn btn-primary" style="background: #ef4444; border-color: #ef4444;">Join Live</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--text-dim); font-size: 0.9rem;">No live sessions are currently active. Check back later.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2>Recent Discussions</h2>
                <button class="btn btn-primary">Start Discussion</button>
            </div>

            <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 40px; height: 40px; background: var(--glass); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid var(--primary);">
                            <i data-lucide="user" size="20" style="color: var(--primary);"></i>
                        </div>
                        <div>
                            <p style="font-weight: 600;"><?= $post['user'] ?></p>
                            <p style="font-size: 0.8rem; color: var(--text-dim);"><?= $post['time'] ?></p>
                        </div>
                    </div>
                    <button style="background: transparent; border: none; color: var(--text-dim); cursor: pointer;">
                        <i data-lucide="more-horizontal"></i>
                    </button>
                </div>
                <h3><?= htmlspecialchars($post['title']) ?></h3>
                <div style="margin-top: 1.5rem; display: flex; gap: 1.5rem; color: var(--text-dim); font-size: 0.9rem;">
                    <span style="display: flex; align-items: center; gap: 0.5rem;"><i data-lucide="message-square" size="18"></i> <?= $post['comments'] ?> Comments</span>
                    <span style="display: flex; align-items: center; gap: 0.5rem;"><i data-lucide="thumbs-up" size="18"></i> Like</span>
                    <span style="display: flex; align-items: center; gap: 0.5rem;"><i data-lucide="share-2" size="18"></i> Share</span>
                </div>
            </div>
            <?php endforeach; ?>
        </section>

        <aside>
            <div class="glass" style="padding: 2rem; margin-bottom: 2rem;">
                <h3>Upcoming Workshops</h3>
                <p style="color: var(--text-dim); font-size: 0.9rem; margin-bottom: 1.5rem;">Join live sessions with historians.</p>
                
                <?php foreach ($workshops as $workshop): ?>
                <div class="workshop-card">
                    <p style="color: var(--primary); font-size: 0.8rem; font-weight: 800;"><?= $workshop['date'] ?></p>
                    <h4 style="margin: 0.5rem 0;"><?= $workshop['title'] ?></h4>
                    <p style="font-size: 0.8rem; color: var(--text-dim);">By <?= $workshop['instructor'] ?></p>
                    <button class="btn btn-outline" style="width: 100%; margin-top: 1rem; padding: 0.5rem;">Register</button>
                </div>
                <?php endforeach; ?>
                
                <a href="#" style="color: var(--primary); text-decoration: none; font-size: 0.9rem; font-weight: 600;">View All Workshops &rarr;</a>
            </div>

            <div class="glass" style="padding: 2rem;">
                <h3>Top Contributors</h3>
                <div style="margin-top: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div style="width: 32px; height: 32px; background: #c5a059; border-radius: 50%;"></div>
                        <span>Prof. Kalisa</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div style="width: 32px; height: 32px; background: #a68546; border-radius: 50%;"></div>
                        <span>Mutoni S.</span>
                    </div>
                </div>
            </div>
        </aside>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
