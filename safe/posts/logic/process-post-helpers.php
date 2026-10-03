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
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!empty($_FILES['my_files']['name'][0])) {
        foreach ($_FILES['my_files']['name'] as $key => $name) {
            $tmpName = $_FILES['my_files']['tmp_name'][$key];
            $fileSize = $_FILES['my_files']['size'][$key];
            $fileError = $_FILES['my_files']['error'][$key];
            $fileExt = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if ($fileError !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Error uploading file: ' . $name);
            }
            if ($fileSize > $maxSize) {
                throw new RuntimeException('File is too large: ' . $name);
            }
            if (!in_array($fileExt, $allowedExt, true)) {
                throw new RuntimeException('File type not allowed: ' . $name);
            }
            $info = @getimagesize($tmpName);
            if ($info === false || !in_array($info['mime'], $allowedMime, true)) {
                throw new RuntimeException('File is not a valid image: ' . $name);
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

function resolve_images(string $existingImgUrlField = ''): array
{
    $existing = array_values(array_filter(array_map('trim', explode(';', $existingImgUrlField))));
    $requested = $_POST['keep_images'] ?? [];
    $requested = is_array($requested) ? $requested : [];

    $kept = array_values(array_intersect(
        array_filter(array_map('trim', array_filter($requested, 'is_string'))),
        $existing
    ));

    return [...$kept, ...processImgs()];
}
