<?php

require __DIR__ . '/viewed-post-modal.php';

$saved = isset($_GET['saved']);

$ownerId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : $userId;
$isOwnProfile = $ownerId === $userId;

$tab = isset($_GET['draft_id']) ? 'drafts' : ($_GET['tab'] ?? 'posts');
if (!in_array($tab, ['posts', 'drafts', 'saved'], true)) {
    $tab = 'posts';
}

$stmt = $pdo->prepare('SELECT username, display_name, profile_pic FROM users WHERE id = ?');
$stmt->execute([$ownerId]);
$owner = $stmt->fetch(PDO::FETCH_ASSOC);

$myDrafts = [];
if ($isOwnProfile) {
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE author_id = ? AND is_posted = 0');
    $stmt->execute([$ownerId]);
    $myDrafts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$myPosts = $tab === 'posts' ? get_user_own_posts($pdo, $ownerId) : [];
$savedPosts = $tab === 'saved' ? get_user_saved_posts($pdo, $ownerId) : [];

$viewedDraft = null;
if (isset($_GET['draft_id'])) {
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? AND is_posted = 0');
    $stmt->execute([$_GET['draft_id']]);
    $viewedDraft = $stmt->fetch(PDO::FETCH_ASSOC);
}

function profile_query(array $overrides = []): string
{
    if (isset($_GET['tab']) && isset($overrides['tab']) && $_GET['tab'] != $overrides['tab']) {
        unset($_GET['post_id']);
    }

    $params = array_merge($_GET, $overrides);
    if (isset($params['tab'])) {
        switch ($params['tab']) {
            case 'drafts':
                unset($params['post_id'], $params['saved']);
                break;
            default:
                unset($params['draft_id'], $params['saved']);
                break;
        }
    }
    return http_build_query($params);
}

function first_image(string $imgUrlField): string
{
    $parts = array_filter(array_map('trim', explode(';', $imgUrlField)));
    return $parts[0] ?? '/images/placeholder.png';
}

function draft_images(string $imgUrlField): array
{
    $parts = array_values(array_filter(array_map('trim', explode(';', $imgUrlField))));
    return array_filter($parts, fn($url) => $url !== '/images/placeholder.png');
}

$viewedCity = $viewedDraft['loc'] ?? '';