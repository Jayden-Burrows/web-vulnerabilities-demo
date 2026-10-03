<?php
function processImgs(): array
{
    $imgUrls = [];

    $uploadDir = __DIR__ . '/../../../images/uploads/';
    $uploadUrlPrefix = '/images/uploads/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $maxSize = 5 * 1024 * 1024;
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!empty($_FILES['my_files']['name'][0])) {
        foreach ($_FILES['my_files']['name'] as $key => $name) {
            $tmpName = $_FILES['my_files']['tmp_name'][$key];
            $fileSize = $_FILES['my_files']['size'][$key];
            $fileError = $_FILES['my_files']['error'][$key];
            $fileExt = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if ($fileError !== UPLOAD_ERR_OK) {
                echo "Error uploading file: " . htmlspecialchars($name) . "<br>";
                continue;
            }

            if ($fileSize > $maxSize) {
                echo "File is too large: " . htmlspecialchars($name) . "<br>";
                continue;
            }

            if (!in_array($fileExt, $allowedExt, true)) {
                echo "File type not allowed: " . htmlspecialchars($name) . "<br>";
                continue;
            }

            $newFileName = uniqid('file_', true) . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($tmpName, $destination)) {
                $imgUrls[] = $uploadUrlPrefix . $newFileName;
            }
        }
    }

    return $imgUrls;
}

function resolve_images(): array
{
    $kept = array_values(array_filter(array_map('trim', $_POST['keep_images'] ?? [])));
    return [...$kept, ...processImgs()];
}