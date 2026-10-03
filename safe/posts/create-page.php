<?php
require __DIR__ . "/../auth.php";
require_login();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Create Post</title>
    <link rel="stylesheet" href="/safe/css/style.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/material-design-iconic-font@2.2.0/dist/css/material-design-iconic-font.min.css">
</head>

<body>

    <header class="app-header">
        <a class="logo" href="/safe/">Live Demo</a>
        <a href="../logout.php" class="logout">Logout</a>
    </header>

    <main class="create-form">
        <h2>Create a post</h2>

        <form method="post" action="logic/process-post.php" enctype="multipart/form-data">
            <label for="image-upload">Images</label>
            <input type="file" name="my_files[]" id="image-upload" accept="image/*" multiple required>
            <br>

            <label for="msg">Message</label>
            <textarea name="msg" id="msg" rows="4" placeholder="What's on your mind?"></textarea>
            <br>

            <label for="location">Location</label>
            <input type="text" name="location" id="location" placeholder="City, Region, Location" autocomplete="off">
            <br>

            <div class="post-buttons">
                <button id="draft-btn" name="action" value="save-draft" type="submit">Save Draft</button>
                <button id="post-btn" name="action" value="post" type="submit">Post</button>
            </div>
        </form>
    </main>

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
</body>

</html>