<?php

require __DIR__ . '/../../auth.php';

require __DIR__ . '/../../db.php';
$pdo = get_db();

$userId = current_user_id();

$viewedPost = null;
$displayName = '';
$numSaves = 0;
$isSaved = 0;
$authorId = null;
$postNotFound = false;

if (isset($_GET['post_id'])) {
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? AND is_posted = 1');
    $stmt->execute([$_GET['post_id']]);
    $viewedPost = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$viewedPost) {
        $postNotFound = true;
    }

    if ($viewedPost) {
        $stmt = $pdo->prepare('SELECT id, display_name, profile_pic FROM users WHERE id = ?');
        $stmt->execute([$viewedPost['author_id']]);
        $author = $stmt->fetch(PDO::FETCH_ASSOC);
        $isAuthor = $author['id'] == $userId ? 1 : 0;
        $authorId = $author['id'] ?? null;
        $displayName = $author['display_name'] ?? 'Unknown';
        $profilePic = $author['profile_pic'] ?? '/images/placeholder.png';

        $stmt = $pdo->prepare('SELECT COUNT(*) as numSaves FROM saves WHERE post_id = ?');
        $stmt->execute([$_GET['post_id']]);
        $numSaves = $stmt->fetch(PDO::FETCH_ASSOC)['numSaves'] ?? 0;

        $stmt = $pdo->prepare('SELECT COUNT(*) as isSaved FROM saves WHERE post_id = ? AND user_id = ?');
        $stmt->execute([$_GET['post_id'], $userId]);
        $isSaved = $stmt->fetch(PDO::FETCH_ASSOC)['isSaved'] ?? 0;
    }
}

function post_images(string $imgUrlField): array
{
    return array_values(array_filter(array_map('trim', explode(';', $imgUrlField))));
}