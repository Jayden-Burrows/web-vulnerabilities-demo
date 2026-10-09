<?php

require __DIR__ . '/../../auth.php';
require __DIR__ . '/../../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_fail('Method not allowed.', 405);
}

require_login(true);
$userId = current_user_id();

$input = json_decode(file_get_contents('php://input'), true);
$input = is_array($input) ? $input : [];

$action = str_param($_GET, 'action');
$postId = is_string($input['post_id'] ?? null) ? $input['post_id'] : '';

if (!in_array($action, ['insert', 'delete'], true) || !is_post_id($postId)) {
    json_fail('Invalid parameters.', 400);
}

try {
    $pdo = get_db();

    $check = $pdo->prepare('SELECT 1 FROM posts WHERE id = ? AND is_posted = 1');
    $check->execute([$postId]);
    if (!$check->fetchColumn()) {
        json_fail('Post not found.', 404);
    }

    if ($action === 'insert') {
        $stmt = $pdo->prepare('INSERT OR IGNORE INTO saves (user_id, post_id) VALUES (?, ?)');
    } else {
        $stmt = $pdo->prepare('DELETE FROM saves WHERE user_id = ? AND post_id = ?');
    }
    $stmt->execute([$userId, $postId]);

    $countStmt = $pdo->prepare('SELECT COUNT(*) AS numSaves FROM saves WHERE post_id = ?');
    $countStmt->execute([$postId]);

    json_out([
        'success' => true,
        'numSaves' => (int) $countStmt->fetchColumn(),
    ]);
} catch (Throwable $e) {
    error_log('process-save-post failed: ' . $e->getMessage());
    json_fail('Something went wrong.', 500);
}
