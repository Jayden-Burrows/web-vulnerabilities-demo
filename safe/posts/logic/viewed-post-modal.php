<?php

require __DIR__ . '/../../auth.php';
require __DIR__ . '/../../db.php';

require_login();

$pdo = get_db();
$userId = current_user_id();

$viewedPost = null;
$displayName = '';
$profilePic = '/images/placeholder.png';
$numSaves = 0;
$isSaved = 0;
$isAuthor = false;
$authorId = null;
$postNotFound = false;

$requestedPostId = str_param($_GET, 'post_id');

if (isset($_GET['post_id'])) {
    if (is_post_id($requestedPostId)) {
        $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? AND is_posted = 1');
        $stmt->execute([$requestedPostId]);
        $viewedPost = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$viewedPost) {
        $postNotFound = true;
    } else {
        $stmt = $pdo->prepare('SELECT id, display_name, profile_pic FROM users WHERE id = ?');
        $stmt->execute([$viewedPost['author_id']]);
        $author = $stmt->fetch(PDO::FETCH_ASSOC);
        $isAuthor = $viewedPost['author_id'] == $userId;
        $authorId = $author['id'] ?? null;
        $displayName = $author['display_name'] ?? 'Unknown';
        $profilePic = $author['profile_pic'] ?? '/images/placeholder.png';

        $stmt = $pdo->prepare('SELECT COUNT(*) as numSaves FROM saves WHERE post_id = ?');
        $stmt->execute([$viewedPost['id']]);
        $numSaves = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['numSaves'] ?? 0);

        $stmt = $pdo->prepare('SELECT COUNT(*) as isSaved FROM saves WHERE post_id = ? AND user_id = ?');
        $stmt->execute([$viewedPost['id'], $userId]);
        $isSaved = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['isSaved'] ?? 0);
    }
}

function post_images(string $imgUrlField): array
{
    return array_values(array_filter(array_map('trim', explode(';', $imgUrlField))));
}
