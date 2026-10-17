<?php

require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

require_login();

$pdo = get_db();
$userId = current_user_id();

$avatarDir = __DIR__ . '/../images/uploads/avatars/';
if (!is_dir($avatarDir)) {
    mkdir($avatarDir, 0755, true);
}

$message = '';
$importCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'avatar_file' && !empty($_FILES['avatar']['name'])) {
        // Trusts the browser's declared Content-Type for the part instead of checking the
        // file. It also keeps the attacker's own filename, so the extension can be whatever 
        // they chose (e.g. php)
        $file = $_FILES['avatar'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if ($file['error'] === UPLOAD_ERR_OK && in_array($file['type'], $allowedTypes, true)) {
            $fileName = basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $avatarDir . $fileName)) {
                $stmt = $pdo->prepare('UPDATE users SET profile_pic = ? WHERE id = ?');
                $stmt->execute(['/images/uploads/avatars/' . $fileName, $userId]);
                $message = 'Avatar updated from file upload.';
            }
        } else {
            $message = 'That file type is not allowed.';
        }
    } elseif ($action === 'avatar_url' && !empty($_POST['avatar_url'])) {
        // Fetches whatever URL the visitor supplies without any restriction on scheme or
        // destination. This includes internal addresses and the file:// scheme, so attackers
        // can use this feature to read local files or reach internal-only services.
        $url = $_POST['avatar_url'];
        $data = @file_get_contents($url);
        if ($data !== false) {
            $fileName = 'url-' . time() . '.img';
            file_put_contents($avatarDir . $fileName, $data);
            $stmt = $pdo->prepare('UPDATE users SET profile_pic = ? WHERE id = ?');
            $stmt->execute(['/images/uploads/avatars/' . $fileName, $userId]);
            $message = 'Avatar imported from URL. Fetched ' . strlen($data) . ' bytes.';

        } else {
            $message = 'Could not fetch that URL.';
        }
    } elseif ($action === 'import_xml' && !empty($_FILES['import']['tmp_name'])) {
        $xmlContent = file_get_contents($_FILES['import']['tmp_name']);
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        // tells the parser to substitute entities declared in the document's DOCTYPE wherever they're used
        $dom->substituteEntities = true;
        $xml = $dom->loadXML($xmlContent) ? simplexml_import_dom($dom) : false;
        if ($xml !== false) {
            foreach ($xml->post as $postNode) {
                $msg = (string) $postNode->msg;
                $loc = (string) $postNode->loc;
                $stmt = $pdo->prepare('INSERT INTO posts (author_id, img_url, msg, loc, post_date, is_posted) VALUES (?, ?, ?, ?, ?, 0)');
                $stmt->execute([$userId, '', $msg, $loc, date('Y-m-d')]);
                $importCount++;
            }
            $message = "Imported $importCount post(s) as drafts.";
        } else {
            $message = 'That XML file could not be parsed.';
        }
    }
}

// Exports each post
if (isset($_GET['export'])) {
    $stmt = $pdo->prepare('SELECT msg, loc, is_posted FROM posts WHERE author_id = ? ORDER BY id');
    $stmt->execute([$userId]);
    $xml = new SimpleXMLElement('<posts/>');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $post = $xml->addChild('post');
        $post->addChild('msg', htmlspecialchars($row['msg']));
        $post->addChild('loc', htmlspecialchars($row['loc']));
        $post->addChild('posted', $row['is_posted']);
    }
    header('Content-Type: application/xml');
    header('Content-Disposition: attachment; filename="my-posts.xml"');
    echo $xml->asXML();
    exit;
}

$user = $pdo->prepare('SELECT display_name, username, profile_pic FROM users WHERE id = ?');
$user->execute([$userId]);
$user = $user->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Account</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body class="theme-vuln">
    <header class="app-header">
        <a class="logo" href="/">Live Demo</a>
        <a href="logout.php" class="logout">Logout</a>
    </header>

    <main class="account-page">
        <h1>Account</h1>
        <p>Signed in as <strong><?= htmlspecialchars($user['display_name']) ?></strong>
            (<?= htmlspecialchars($user['username']) ?>)</p>

        <?php if ($message): ?>
            <p class="account-message"><strong><?= htmlspecialchars($message) ?></strong></p>
        <?php endif; ?>

        <section class="account-section">
            <h2>Avatar</h2>
            <img class="account-avatar-preview" src="<?= htmlspecialchars($user['profile_pic']) ?>"
                alt="Current avatar">

            <h3>Upload a file</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="avatar_file">
                <input type="file" name="avatar" accept="image/*" required>
                <button type="submit">Upload</button>
            </form>

            <h3>Import from a URL</h3>
            <form method="post">
                <input type="hidden" name="action" value="avatar_url">
                <input type="text" name="avatar_url" placeholder="https://example.com/photo.jpg" required data-straight
                    autocorrect="off" autocapitalize="off" spellcheck="false">
                <button type="submit">Fetch</button>
            </form>
        </section>

        <section class="account-section">
            <h2>Your posts</h2>
            <p><a href="account.php?export=1">Export all your posts as XML</a></p>

            <h3>Import posts from XML</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_xml">
                <input type="file" name="import" accept=".xml" required>
                <button type="submit">Import</button>
            </form>
        </section>
    </main>
</body>

</html>