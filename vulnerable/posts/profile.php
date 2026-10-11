<?php
require __DIR__ . '/logic/profile-logic.php';
require_login();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Profile</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/material-design-iconic-font@2.2.0/dist/css/material-design-iconic-font.min.css">
</head>

<body class="theme-vuln">

    <header class="app-header">
        <a class="logo" href="/vulnerable/">Live Demo</a>
        <a href="../logout.php" class="logout">Logout</a>
    </header>

    <main class="drafts-page">
        <div class="drafts-owner">
            <h2><?= htmlspecialchars($owner['display_name'] ?? '') ?></h2>
            <img src="<?= htmlspecialchars($owner['profile_pic'] ?? '') ?>"
                alt="<?= htmlspecialchars($owner['display_name'] ?? '') ?>">
            <p class="username">@<?= htmlspecialchars($owner['username'] ?? '') ?></p>
        </div>

        <div class="profile-tabs">
            <a href="?<?= profile_query(['tab' => 'posts']) ?>"
                class="profile-tab<?= $tab === 'posts' ? ' active' : '' ?>">My Posts</a>
            <?php if ($isOwnProfile): ?>
                <a href="?<?= profile_query(['tab' => 'drafts']) ?>"
                    class="profile-tab<?= $tab === 'drafts' ? ' active' : '' ?>">Drafts</a>
            <?php endif; ?>
            <a href="?<?= profile_query(['tab' => 'saved']) ?>"
                class="profile-tab<?= $tab === 'saved' ? ' active' : '' ?>">Saved Posts</a>
        </div>

        <?php if ($tab === 'posts'): ?>
            <div class="drafts-grid">
                <?php foreach ($myPosts as $p): ?>
                    <a class="draft-thumb" href="?<?= profile_query(['post_id' => $p['id']]) ?>">
                        <img src="<?= htmlspecialchars(first_image($p['img_url'])) ?>" alt="post image">
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if (empty($myPosts)): ?>
                <p class="drafts-hint">You haven't posted anything yet.</p>
            <?php endif; ?>

        <?php elseif ($tab === 'saved'): ?>
            <div class="drafts-grid">
                <?php foreach ($savedPosts as $p): ?>
                    <a class="draft-thumb" href="?<?= profile_query(['tab' => 'saved', 'post_id' => $p['id']]) ?>">
                        <img src="<?= htmlspecialchars(first_image($p['img_url'])) ?>" alt="post image">
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if (empty($savedPosts)): ?>
                <p class="drafts-hint">No saved posts yet.</p>
            <?php endif; ?>

        <?php else: ?>
            <?php if (!$isOwnProfile): ?>
                <p class="drafts-hint">Drafts are only visible on your own profile.</p>
            <?php else: ?>
                <div class="drafts-grid">
                    <?php foreach ($myDrafts as $d): ?>
                        <a class="draft-thumb" href="?<?= profile_query(['draft_id' => $d['id']]) ?>">
                            <img src="<?= htmlspecialchars(first_image($d['img_url'])) ?>" alt="draft image">
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($myDrafts)): ?>
                    <p class="drafts-hint">You don't have any drafts yet.</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($_GET['draft_id']) && !$viewedDraft): ?>
                <div id="error-modal" class="modal" style="<?= $postNotFound ? 'display:block;' : '' ?>">
                    <span class="close" onclick="document.getElementById('error-modal').style.display='none'">&times;</span>
                    <div class="modal-content">
                        <?php if ($postNotFound): ?>
                            <p class="draft-not-found">No draft found with ID
                                <?= htmlspecialchars($_GET['draft_id']) ?>.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div id="draft-modal" class="modal" style="<?= $viewedDraft ? 'display:block;' : '' ?>">
                <span class="close" onclick="document.getElementById('draft-modal').style.display='none'">&times;</span>
                <?php if ($viewedDraft): ?>
                    <div class="modal-content animate draft-edit-content">

                        <?php if ($saved): ?>
                            <p class="success">Draft saved.</p>
                        <?php endif; ?>

                        <form method="post" action="logic/process-post.php" enctype="multipart/form-data"
                            class="draft-edit-form">
                            <input type="hidden" name="draft_id" value="<?= htmlspecialchars($viewedDraft['id']) ?>">

                            <?php $images = draft_images($viewedDraft['img_url']); ?>
                            <?php if (!empty($images)): ?>
                                <label>Current images</label>
                                <div class="image-keep-grid">
                                    <?php foreach ($images as $img): ?>
                                        <label class="image-keep-item">
                                            <img src="<?= htmlspecialchars($img) ?>" alt="draft image">
                                            <span>
                                                <input type="checkbox" name="keep_images[]" value="<?= htmlspecialchars($img) ?>"
                                                    checked>
                                                Keep
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <label for="draft-image-upload">Add images</label>
                            <input type="file" name="my_files[]" id="draft-image-upload" accept="image/*" multiple>

                            <label for="draft-msg">Description</label>
                            <textarea name="msg" id="draft-msg" rows="4"
                                data-straight><?= htmlspecialchars($viewedDraft['msg']) ?></textarea>

                            <label for="location">Location</label>
                            <input type="text" name="location" id="location" placeholder="City, Region, Location"
                                autocomplete="off" data-straight value="<?= htmlspecialchars($viewedCity) ?>">

                            <div class="post-buttons">
                                <button id="del-btn" name="action" value="delete" type="submit">Delete <i
                                        class="fa-solid fa-trash"></i></button>
                                <button id="draft-btn" name="action" value="update" type="submit">Save <i
                                        class="fa-solid fa-floppy-disk"></i></button>
                                <button id="post-btn" name="action" value="post" type="submit">Post <i
                                        class="fa-solid fa-plus"></i></button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php if ($postNotFound): ?>
        <p class="post-not-found">No post found with the given ID.</p>
    <?php endif; ?>

    <div id="post-modal" class="modal" style="<?= $viewedPost ? 'display:block;' : '' ?>">
        <span class="close" onclick="document.getElementById('post-modal').style.display='none'">&times;</span>
        <?php if ($viewedPost): ?>
            <?php $viewedImages = post_images($viewedPost['img_url']); ?>
            <div class="modal-content animate">
                <div class="post-header">
                    <div class="profile-details">
                        <a href="?<?= profile_query(['user_id' => $authorId, 'tab' => 'posts']) ?>">
                            <img src="<?= htmlspecialchars($profilePic) ?>" alt="<?= htmlspecialchars($displayName) ?>">
                        </a>
                        <p><a class="author-name"
                                href="?<?= profile_query(['user_id' => $authorId, 'tab' => 'posts']) ?>"><?= $displayName ?></a>
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
        <span class="close" onclick="document.getElementById('create-modal').style.display='none'">&times;</span>
        <div class="modal-content animate create-modal-content">
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
    <script src="/js/straight-input.js"></script>
    <script src="js/create-post-modal.js"></script>
    <script src="js/save-post-btn.js"></script>
    <script src="js/delete-post-btn.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const locationField = document.getElementById('location');

            if (!locationField) return;
            if (locationField.value.trim() !== '') return;

            fetch('https://ipapi.co/json/')
                .then(response => response.json())
                .then(data => {
                    const parts = [data.city, data.region, data.country_name].filter(Boolean);
                    if (parts.length) {
                        locationField.value = parts.join(', ');
                    }
                })
                .catch(error => console.error('Error fetching location:', error));
        });
    </script>
    <?php include __DIR__ . '/../../tools/attacker-tools.php'; ?>
</body>

</html>