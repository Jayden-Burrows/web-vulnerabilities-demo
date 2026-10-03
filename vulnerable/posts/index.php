<?php

require __DIR__ . '/logic/viewed-post-modal.php';
require_login();

$filters = [
    'sort' => $_GET['sort'] ?? 'newest',
];
$posts = get_visible_posts($pdo, $filters);

function posts_query(array $overrides = []): string
{
    return http_build_query(array_merge($_GET, $overrides));
}

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Posts</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/material-design-iconic-font@2.2.0/dist/css/material-design-iconic-font.min.css">
</head>

<body>

    <header class="app-header">
        <a class="logo" href="/">Live Demo</a>
        <a href="../logout.php" class="logout">Logout</a>
    </header>

    <form method="get" class="posts-filter-bar">
        <i class="fa-solid fa-sort"></i>
        <select name="sort">
            <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="oldest" <?= $filters['sort'] === 'oldest' ? 'selected' : '' ?>>Oldest</option>
            <option value="most-saved" <?= $filters['sort'] === 'most-saved' ? 'selected' : '' ?>>Most Saved</option>
            <option value="least-saved" <?= $filters['sort'] === 'least-saved' ? 'selected' : '' ?>>Least Saved</option>
        </select>

        <button type="submit">Apply</button>
        <?php if ($filters['sort'] !== 'newest'): ?>
            <a class="posts-filter-clear" href="index.php">Clear</a>
        <?php endif; ?>
    </form>

    <main class="posts-grid">
        <?php if (empty($posts)): ?>
            <p class="posts-empty">No posts match those filters.</p>
        <?php endif; ?>
        <?php foreach ($posts as $post): ?>
            <?php $images = post_images($post['img_url']); ?>
            <a class="post-thumb" href="?<?= posts_query(['post_id' => $post['id']]) ?>">
                <img src="<?= htmlspecialchars($images[0] ?? '/images/placeholder.png') ?>" alt="post image" loading="lazy">
            </a>
        <?php endforeach; ?>
    </main>

    <div id="error-modal" class="modal" style="<?= $postNotFound ? 'display:block;' : '' ?>">
        <span class="close" onclick="document.getElementById('error-modal').style.display='none'">&times;</span>
        <div class="modal-content">
            <?php if ($postNotFound): ?>
                <p class="post-not-found">No post found with ID <?= htmlspecialchars($_GET['post_id']) ?>.</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="post-modal" class="modal" style="<?= $viewedPost ? 'display:block;' : '' ?>">
        <span class="close" onclick="document.getElementById('post-modal').style.display='none'">&times;</span>
        <?php if ($viewedPost): ?>
            <?php $viewedImages = post_images($viewedPost['img_url']); ?>
            <div id="post-<?= htmlspecialchars($viewedPost['id']) ?>" class="modal-content animate">
                <div class="post-header">
                    <div class="profile-details">
                        <a href="profile.php?<?= http_build_query(['user_id' => $authorId]) ?>">
                            <img src="<?= htmlspecialchars($profilePic) ?>" alt="<?= htmlspecialchars($displayName) ?>">
                        </a>
                        <p><a class="author-name"
                                href="profile.php?<?= http_build_query(['user_id' => $authorId]) ?>"><?= $displayName ?></a>
                        </p>
                    </div>
                    <?php if ($isAuthor): ?>
                        <button id="delete-btn" onclick="deletePost(<?= $viewedPost['id'] ?>)"><i
                                class="fa-solid fa-trash"></i></button>
                    <?php endif; ?>
                </div>
                <div class="post-carousel">
                    <?php foreach ($viewedImages as $img): ?>
                        <img src="<?= htmlspecialchars($img) ?>" alt="post image" class="post-carousel-img">
                    <?php endforeach; ?>
                </div>
                <?php if (count($viewedImages) > 1): ?>
                    <div class="post-carousel-dots">
                        <?php foreach ($viewedImages as $i => $img): ?>
                            <span class="dot"></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="post-content">
                    <p class="post-details">
                        <button id="save-btn" onclick="savePost(<?= htmlspecialchars($viewedPost['id']) ?>)" <?php if ($isSaved == 1): ?> class="saved" <?php endif; ?>>
                            <i class="fa-solid fa-thumbtack"></i>
                        </button>
                        <span>
                            <?= $numSaves ?>
                        </span>
                        <i class="fa-regular fa-calendar"></i> <span>
                            <?= $viewedPost["post_date"] ?>
                        </span>
                        <?php if ($viewedPost["loc"]): ?>
                            <i class="fa-regular fa-map"></i>
                            <span>
                                <?= $viewedPost["loc"] ?>
                            </span>
                        <?php endif; ?>
                    </p>
                    <?php if ($viewedPost["msg"]): ?>
                        <p><span class="author-name"><?= $displayName ?></span>
                            <span class="description"><?= $viewedPost["msg"] ?></span>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <nav class="bottom-nav">
        <a href="index.php"><i class="fa-solid fa-house"></i></a>
        <button type="button" id="create-btn"><i class="fa-solid fa-plus"></i></button>
        <a href="profile.php"><i class="fa-solid fa-user"></i></a>
    </nav>

    <div id="create-modal" class="modal">
        <div class="modal-content animate create-modal-content">
            <span class="close" onclick="document.getElementById('create-modal').style.display='none'">&times;</span>
            <h3>Create</h3>
            <div class="create-modal-options">
                <a class="create-modal-option" href="create-page.php">
                    <i class="zmdi zmdi-image-o"></i> New post
                </a>
                <a class="create-modal-option" href="profile.php?tab=drafts">
                    <i class="zmdi zmdi-edit"></i> Drafts
                </a>
            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/1cb5b7a573.js" crossorigin="anonymous"></script>
    <script src="js/create-post-modal.js"></script>
    <script src="js/save-post-btn.js"></script>
    <script src="js/delete-post-btn.js"></script>
    <?php include __DIR__ . '/../../tools/attacker-tools.php'; ?>
</body>

</html>