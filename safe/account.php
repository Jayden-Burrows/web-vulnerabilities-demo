<?php

require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

use Ramsey\Uuid\Uuid;

require_login();

$pdo = get_db();
$userId = current_user_id();

$avatarDir = __DIR__ . '/../images/uploads/avatars/';
if (!is_dir($avatarDir)) {
    mkdir($avatarDir, 0755, true);
}

function is_fetchable_url(string $url): bool
{
    $parts = parse_url($url);
    // Only accepts http/https
    if (!$parts || empty($parts['scheme']) || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
        return false;
    }
    if (empty($parts['host'])) {
        return false;
    }
    // get the IP address of the host name
    $ip = filter_var($parts['host'], FILTER_VALIDATE_IP) ? $parts['host'] : gethostbyname($parts['host']);
    // Check that the given IP address is a valid IP address, not in the private address range or the reserved range
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

function save_avatar_if_real_image(PDO $pdo, int $userId, string $tmpPath, string $avatarDir): bool
{
    // Get the provided MIME type of the image
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpPath);

    $allowedMimeTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

    // Check if the mime type is allowed
    if (!isset($allowedMimeTypes[$mimeType])) {
        return false;
    }

    $image = match ($mimeType) {
        'image/jpeg' => @imagecreatefromjpeg($tmpPath),
        'image/png' => @imagecreatefrompng($tmpPath),
        'image/gif' => @imagecreatefromgif($tmpPath),
        'image/webp' => @imagecreatefromwebp($tmpPath)
    };

    if ($image === false) {
        return false;
    }

    $fileName = bin2hex(random_bytes(16) . '.' . $allowedMimeTypes[$mimeType]);
    $destination = $avatarDir . $fileName;

    $saved = match ($mimeType) {
        'image/jpeg' => imagejpeg($image, $destination),
        'image/png' => imagepng($image, $destination),
        'image/gif' => imagegif($image, $destination),
        'image/webp' => imagewebp($image, $destination)
    };
    
    if (!$saved) {
        return false;
    }

    $stmt = $pdo->prepare('UPDATE users set profile_pic = ? WHERE id = ?');
    $stmt-> execute(['/images/uploads/avatars/' . $fileName, $userId]);
    return true;
}

$message = '';
$importCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $message = 'Your session expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'avatar_file' && !empty($_FILES['avatar']['name'])) {
            $file = $_FILES['avatar'];
            $message = ($file['error'] === UPLOAD_ERR_OK && save_avatar_if_real_image($pdo, $userId, $file['tmp_name'], $avatarDir))
                ? 'Avatar updated from file upload.'
                : "That file isn't a recognized image.";
        } elseif ($action === 'avatar_url' && !empty($_POST['avatar_url'])) {
            $url = $_POST['avatar_url'];
            if (!is_fetchable_url($url)) {
                $message = 'That URL is not allowed.';
            } else {
                // prevent HTTP redirects from provided URL
                $context = stream_context_create(['http' => ['follow_location' => 0, 'timeout' => 5]]);
                $data = @file_get_contents($url, false, $context);
                // if PHP can read the file, then create a temporary file with a unique name and move it
                // into the tmp directory
                $tmp = $data !== false ? tempnam(sys_get_temp_dir(), 'avatar') : false;
                if ($tmp !== false) {
                    file_put_contents($tmp, $data);
                }
                $message = ($tmp !== false && save_avatar_if_real_image($pdo, $userId, $tmp, $avatarDir))
                    ? 'Avatar imported from URL.'
                    : "That URL didn't return a recognized image.";
                if ($tmp !== false) {
                    unlink($tmp);
                }
            }
        } elseif ($action === 'import_xml' && !empty($_FILES['import']['tmp_name'])) {
            $xmlContent = file_get_contents($_FILES['import']['tmp_name']);
            // Reject XML files with <!DOCTYPE to prevent XXE Attacks
            if (stripos($xmlContent, '<!DOCTYPE') !== false) {
                $message = 'That XML file is not allowed (DOCTYPE declarations are rejected).';
            } else {
                // Suppress standard errors from showing up in the app logs
                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($xmlContent);
                // if a valid SimpleXMLElement can be parsed from the file
                if ($xml !== false) {
                    // for each post in the xml, insert the post into the database
                    foreach ($xml->post as $postNode) {
                        $msg = (string) $postNode->msg;
                        $loc = (string) $postNode->loc;
                        $pdo->prepare('INSERT INTO posts (id, author_id, img_url, msg, loc, post_date, is_posted) VALUES (?, ?, ?, ?, ?, ?, 0)')
                            ->execute([Uuid::uuid4()->toString(), $userId, '', $msg, $loc, date('Y-m-d')]);
                        $importCount++;
                    }
                    $message = "Imported $importCount post(s) as drafts.";
                } else {
                    $message = 'That XML file could not be parsed.';
                }
            }
        }
    }
}

// Export the posts as xml
if (isset($_GET['export'])) {
    $stmt = $pdo->prepare('SELECT msg, loc, is_posted FROM posts WHERE author_id = ? ORDER BY post_date');
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
    <?= csrf_meta() ?>
    <title>Account</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body class="theme-safe">
    <header class="app-header">
        <a class="logo" href="/">Live Demo</a>
        <form method="post" action="logout.php" class="logout-form">
            <?= csrf_field() ?><button type="submit" class="logout">Logout</button>
        </form>
    </header>

    <main class="account-page">
        <h1>Account</h1>
        <p>Signed in as <strong>
                <?= e($user['display_name']) ?>
            </strong> (
            <?= e($user['username']) ?>)
        </p>

        <?php if ($message): ?>
            <p class="account-message"><strong>
                    <?= e($message) ?>
                </strong></p>
        <?php endif; ?>

        <section class="account-section">
            <h2>Avatar</h2>
            <img class="account-avatar-preview" src="<?= e($user['profile_pic']) ?>" alt="Current avatar">

            <h3>Upload a file</h3>
            <form method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="avatar_file">
                <input type="file" name="avatar" accept="image/*" required>
                <button type="submit">Upload</button>
            </form>

            <h3>Import from a URL</h3>
            <form method="post">
                <?= csrf_field() ?>
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
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="import_xml">
                <input type="file" name="import" accept=".xml" required>
                <button type="submit">Import</button>
            </form>
        </section>
    </main>
</body>

</html>