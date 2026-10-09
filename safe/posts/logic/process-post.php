<?php

use Ramsey\Uuid\Uuid;

require __DIR__ . '/../../auth.php';
require __DIR__ . '/../../db.php';
require __DIR__ . '/process-post-helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_fail('Method not allowed.', 405);
}

require_login(true);
$userId = current_user_id();

// Two kinds of caller:
//  - the trash-can button sends JSON (fetch)   -> we answer with JSON
//  - the post/draft forms send form data       -> we redirect on success
$jsonData = json_decode(file_get_contents('php://input'), true);
$isJsonRequest = is_array($jsonData);
$jsonData = $isJsonRequest ? $jsonData : [];

$action = $jsonData['action'] ?? $_POST['action'] ?? '';
$postId = $jsonData['post_id'] ?? $_POST['draft_id'] ?? '';
$msg = str_param($_POST, 'msg');
$location = trim(str_param($_POST, 'location'));

if (!is_string($action) || !is_string($postId)) {
    json_fail('Invalid parameters.', 400);
}

if ($postId !== '' && !is_post_id($postId)) {
    json_fail('Post not found.', 404);
}

try {
    $pdo = get_db();
    $redirect = '../profile.php?tab=posts';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ? AND author_id = ?');
        $stmt->execute([$postId, $userId]);
        if ($stmt->rowCount() === 0) {
            json_fail('Post not found.', 404);
        }
        $pdo->prepare('DELETE FROM saves WHERE post_id = ?')->execute([$postId]);
        $redirect = '../profile.php?tab=posts';
    } elseif ($action === 'update') {
        $draft = get_owned_post($pdo, $postId, $userId, 0);
        if (!$draft) {
            json_fail('Draft not found.', 404);
        }
        $imgUrls = resolve_images($draft['img_url']);
        if (empty($imgUrls)) {
            $imgUrls[] = '/images/placeholder.png';
        }
        $stmt = $pdo->prepare('UPDATE posts SET img_url = ?, msg = ?, loc = ? WHERE id = ? AND author_id = ? AND is_posted = 0');
        $stmt->execute([implode('; ', $imgUrls), $msg, $location, $postId, $userId]);
        $redirect = '../profile.php?tab=drafts';
    } elseif ($action === 'save-draft') {
        $imgUrls = resolve_images();
        if (empty($imgUrls)) {
            throw new RuntimeException('Users must upload at least one image!');
        }
        $stmt = $pdo->prepare('INSERT INTO posts (id, author_id, img_url, msg, loc, post_date, is_posted) VALUES (?, ?, ?, ?, ?, ?, 0)');
        $stmt->execute([Uuid::uuid4()->toString(), $userId, implode('; ', $imgUrls), $msg, $location, '']);
        $redirect = '../profile.php?tab=drafts';
    } elseif ($action === 'post') {
        $postDate = date('m-d-Y');

        if ($postId !== '') {
            $draft = get_owned_post($pdo, $postId, $userId, 0);
            if (!$draft) {
                json_fail('Draft not found.', 404);
            }
            $imgUrls = resolve_images($draft['img_url']);
            if (empty($imgUrls)) {
                $imgUrls[] = '/images/placeholder.png';
            }
            $stmt = $pdo->prepare('UPDATE posts SET img_url = ?, msg = ?, loc = ?, post_date = ?, is_posted = 1 WHERE id = ? AND author_id = ? AND is_posted = 0');
            $stmt->execute([implode('; ', $imgUrls), $msg, $location, $postDate, $postId, $userId]);
        } else {
            $imgUrls = resolve_images();
            if (empty($imgUrls)) {
                throw new RuntimeException('Users must upload at least one image!');
            }
            $stmt = $pdo->prepare('INSERT INTO posts (id, author_id, img_url, msg, loc, post_date, is_posted) VALUES (?, ?, ?, ?, ?, ?, 1)');
            $stmt->execute([Uuid::uuid4()->toString(), $userId, implode('; ', $imgUrls), $msg, $location, $postDate]);
        }
        $redirect = '../profile.php?tab=posts';
    } else {
        json_fail('Unknown action.', 400);
    }

    if ($isJsonRequest) {
        json_out(['success' => true, 'redirect' => 'profile.php?tab=posts']);
    }
    header('Location: ' . $redirect);
    exit;
} catch (RuntimeException $e) {
    json_fail($e->getMessage(), 400);
} catch (Throwable $e) {
    error_log('process-post failed: ' . $e->getMessage());
    json_fail('Something went wrong.', 500);
}
