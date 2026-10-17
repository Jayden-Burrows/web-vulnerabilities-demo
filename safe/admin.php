<?php

require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

require_login();
require_admin();

$pdo = get_db();

$users = $pdo->query('SELECT id, display_name, username, email, is_admin FROM users ORDER BY id')
    ->fetchAll(PDO::FETCH_ASSOC);

$drafts = $pdo->query(
    'SELECT posts.id, posts.msg, posts.loc, users.username AS author
     FROM posts JOIN users ON users.id = posts.author_id
     WHERE posts.is_posted = 0
     ORDER BY posts.post_date DESC'
)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <?= csrf_meta() ?>
    <title>Admin</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body class="theme-safe">
    <header class="app-header">
        <a class="logo" href="/">Live Demo</a>
        <form method="post" action="logout.php" class="logout-form"><?= csrf_field() ?><button type="submit"
                class="logout">Logout</button></form>
    </header>

    <main class="learn">
        <section class="vuln-section">
            <h1>Admin panel</h1>
            <p>Reached only because <code>admin_demo</code> is signed in; the account used to sign in to view this
                page genuinely has <code>is_admin = 1</code>.</p>

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
                                <td><?= e($u['id']) ?></td>
                                <td><?= e($u['display_name']) ?></td>
                                <td><?= e($u['username']) ?></td>
                                <td><?= e($u['email']) ?></td>
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
                                    <td><?= e($d['id']) ?></td>
                                    <td><?= e($d['author']) ?></td>
                                    <td><?= e($d['msg']) ?></td>
                                    <td><?= e($d['loc']) ?></td>
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