<?php

require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

// This page doesn't perform an authorization check to make sure the current user is an admin
require_login();

$pdo = get_db();

$users = $pdo->query('SELECT id, display_name, username, email, is_admin FROM users ORDER BY id')
    ->fetchAll(PDO::FETCH_ASSOC);

$drafts = $pdo->query(
    'SELECT posts.id, posts.msg, posts.loc, users.username AS author
     FROM posts JOIN users ON users.id = posts.author_id
     WHERE posts.is_posted = 0
     ORDER BY posts.id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Admin</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body class="theme-vuln">
    <header class="app-header">
        <a class="logo" href="/">Live Demo</a>
        <a href="logout.php" class="logout">Logout</a>
    </header>

    <main class="learn">
        <section class="vuln-section">
            <h1>Admin panel</h1>
            <p>Every user account and every draft post in the system, visible here to whoever loaded this page.</p>

            <h2>Users (<?= count($users) ?>)</h2>
            <div class="learn-table-wrap">
                <table class="learn-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Admin?</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['id']) ?></td>
                                <td><?= htmlspecialchars($u['display_name']) ?></td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= $u['is_admin'] ? 'Yes' : 'No' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h2>Everyone's drafts (<?= count($drafts) ?>)</h2>
            <?php if (!$drafts): ?>
                <p>No drafts right now.</p>
            <?php else: ?>
                <div class="learn-table-wrap">
                    <table class="learn-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Author</th>
                                <th>Message</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($drafts as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['id']) ?></td>
                                    <td><?= htmlspecialchars($d['author']) ?></td>
                                    <td><?= htmlspecialchars($d['msg']) ?></td>
                                    <td><?= htmlspecialchars($d['loc']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>