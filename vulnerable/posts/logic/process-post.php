<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');

    require __DIR__ . '/../../auth.php';
    require_login();

    require __DIR__ . '/../../db.php';
    require __DIR__ . '/process-post-helpers.php';

    $userId = current_user_id();

    $draftId = $_POST['draft_id'] ?? '';

    $msg = $_POST['msg'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $action = $_POST['action'] ?? '';

    // This handles deleting a published post through the trash
    // can icon. This delete button sends data differently than
    // pressing the delete button on the draft form.
    $jsonData = json_decode(file_get_contents('php://input'), true);

    $postId = $jsonData['post_id'] ?? $draftId;
    $action = $jsonData['action'] ?? $action;

    try {
        $pdo = get_db();

        if ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ?');
            $stmt->execute([$postId]);

            $redirect = '../profile.php?tab=posts';
        } elseif ($action === 'update') {
            $imgUrl = implode('; ', resolve_images());

            $stmt = $pdo->prepare('UPDATE posts SET img_url = ?, msg = ?, loc = ? WHERE id = ?');
            $stmt->execute([$imgUrl, $msg, $location, $draftId]);

            $redirect = '../profile.php?tab=drafts';
        } elseif ($action === 'save-draft') {
            $imgUrls = resolve_images();
            if (empty($imgUrls)) {
                throw new Exception('Users must upload at least one image!');
            }
            $imgUrl = implode('; ', $imgUrls);

            $stmt = $pdo->prepare('INSERT INTO posts (author_id, img_url, msg, loc, post_date, is_posted) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $imgUrl, $msg, $location, '', 0]);

            $redirect = '../profile.php?tab=drafts';
        } elseif ($action === 'post') {
            $postDate = date('m-d-Y');

            if ($draftId !== '') {
                $imgUrls = resolve_images();
                if (empty($imgUrls)) {
                    $imgUrls[] = '/images/placeholder.png';
                }
                $imgUrl = implode('; ', $imgUrls);

                $stmt = $pdo->prepare('UPDATE posts SET img_url = ?, msg = ?, loc = ?, post_date = ?, is_posted = 1 WHERE id = ?');
                $stmt->execute([$imgUrl, $msg, $location, $postDate, $draftId]);
            } else {
                $imgUrls = resolve_images();
                if (empty($imgUrls)) {
                    throw new Exception('Users must upload at least one image!');
                }
                $imgUrl = implode('; ', $imgUrls);

                $stmt = $pdo->prepare('INSERT INTO posts (author_id, img_url, msg, loc, post_date, is_posted) VALUES (?, ?, ?, ?, ?, 1)');
                $stmt->execute([$userId, $imgUrl, $msg, $location, $postDate]);
            }

            $redirect = '../profile.php?tab=posts';
        } else {
            throw new Exception('Action ' . $action . ' not defined');
        }

        header('Location: ' . $redirect);
        exit();
    } catch (PDOException $e) {
        http_response_code(500);
        $error = "SQL Error: " . $e->getMessage();
        echo json_encode(['success' => false, 'error' => 'Database error' . $error]);
    }
}