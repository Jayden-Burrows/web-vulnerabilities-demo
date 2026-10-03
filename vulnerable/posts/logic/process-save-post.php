<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');

    require __DIR__ . '/../../auth.php';
    require_login();

    require __DIR__ . '/../../db.php';

    $userId = current_user_id();

    $input = json_decode(file_get_contents('php://input'), true);

    $action = $_GET['action'] ?? '';

    $postId = (int) $input['post_id'] ?? 0;

    if (!$postId || !$userId || !$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit;
    }

    try {
        $pdo = get_db();

        if ($action === 'insert') {
            $stmt = $pdo->prepare('INSERT OR IGNORE INTO saves (user_id, post_id) VALUES (?, ?)');
            $stmt->execute([$userId, $postId]);
        } else if ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM saves WHERE user_id = ? AND post_id = ?');
            $stmt->execute([$userId, $postId]);
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) AS numSaves FROM saves WHERE post_id = ?');
        $countStmt->execute([$postId]);
        $saveData = $countStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'numSaves' => (int) ($saveData['numSaves'] ?? 0)
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        $error = "SQL Error: " . $e->getMessage();
        echo json_encode(['success' => false, 'error' => 'Database error' . $error]);
    }
} else {
    http_response_code(500);
}
