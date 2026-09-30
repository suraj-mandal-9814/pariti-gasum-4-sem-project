<?php

require_once __DIR__ . '/../include/authcheck.php';

function profilePictureRedirect(string $message): void
{
    $_SESSION['profile_picture_message'] = $message;
    header('Location: ../index.php?page=dashboard', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    profilePictureRedirect('Use the dashboard to manage saved photos.');
}

$expectedToken = (string) ($_SESSION['profile_picture_csrf'] ?? '');
$submittedToken = (string) ($_POST['csrf_token'] ?? '');
if ($expectedToken === '' || !hash_equals($expectedToken, $submittedToken)) {
    profilePictureRedirect('Your request could not be verified. Please try again.');
}

$userId = (int) $_SESSION['user_id'];
$action = (string) ($_POST['action'] ?? '');
$db = getDBConnection();

try {
    $db->exec(
        'CREATE TABLE IF NOT EXISTS user_photos (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_photos_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $uploadDirectory = __DIR__ . '/../uploads/profile';

    if ($action === 'delete') {
        $photoId = filter_var($_POST['photo_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($photoId === false || $photoId === null) {
            profilePictureRedirect('The selected photo is invalid.');
        }

        $photoStatement = $db->prepare('SELECT file_name FROM user_photos WHERE id = ? AND user_id = ? LIMIT 1');
        $photoStatement->execute([$photoId, $userId]);
        $fileName = $photoStatement->fetchColumn();
        if ($fileName === false) {
            profilePictureRedirect('That saved photo was not found.');
        }

        $deleteStatement = $db->prepare('DELETE FROM user_photos WHERE id = ? AND user_id = ?');
        $deleteStatement->execute([$photoId, $userId]);
        $photoPath = $uploadDirectory . DIRECTORY_SEPARATOR . basename((string) $fileName);
        if (is_file($photoPath)) {
            unlink($photoPath);
        }

        profilePictureRedirect('Saved photo removed. Your profile picture was not changed.');
    }

    if ($action !== 'add') {
        profilePictureRedirect('Choose photos to save or remove a saved photo.');
    }

    if (!isset($_FILES['photos']) || !is_array($_FILES['photos']['name'] ?? null)) {
        profilePictureRedirect('Choose at least one photo to save.');
    }

    $files = [];
    foreach ($_FILES['photos']['name'] as $index => $originalName) {
        $uploadError = $_FILES['photos']['error'][$index] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($uploadError !== UPLOAD_ERR_OK) {
            profilePictureRedirect('One of the photos could not be uploaded.');
        }
        $files[] = [
            'tmp_name' => $_FILES['photos']['tmp_name'][$index] ?? '',
            'size' => (int) ($_FILES['photos']['size'][$index] ?? 0),
        ];
    }

    if ($files === []) {
        profilePictureRedirect('Choose at least one photo to save.');
    }

    $countStatement = $db->prepare('SELECT COUNT(*) FROM user_photos WHERE user_id = ?');
    $countStatement->execute([$userId]);
    $savedCount = (int) $countStatement->fetchColumn();
    if ($savedCount + count($files) > 12) {
        profilePictureRedirect('You can save up to 12 photos. Remove a photo before adding more.');
    }

    $allowedImageTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];
    $validatedFiles = [];
    foreach ($files as $file) {
        if ($file['size'] > 5 * 1024 * 1024) {
            profilePictureRedirect('Each photo must be 5 MB or smaller.');
        }
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false || !isset($allowedImageTypes[$imageInfo[2]])) {
            profilePictureRedirect('Only valid JPG, PNG, or WebP photos are allowed.');
        }
        $validatedFiles[] = [
            'tmp_name' => $file['tmp_name'],
            'extension' => $allowedImageTypes[$imageInfo[2]],
        ];
    }

    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        profilePictureRedirect('The photo upload folder is unavailable.');
    }

    $savedFilePaths = [];
    $db->beginTransaction();
    try {
        $insertStatement = $db->prepare('INSERT INTO user_photos (user_id, file_name) VALUES (?, ?)');
        foreach ($validatedFiles as $file) {
            $fileName = bin2hex(random_bytes(16)) . '.' . $file['extension'];
            $filePath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new RuntimeException('Could not save uploaded photo.');
            }
            $savedFilePaths[] = $filePath;
            $insertStatement->execute([$userId, $fileName]);
        }
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        foreach ($savedFilePaths as $savedFilePath) {
            if (is_file($savedFilePath)) {
                unlink($savedFilePath);
            }
        }
        throw $exception;
    }

    profilePictureRedirect(count($validatedFiles) . ' photo(s) saved to your dashboard. Your profile picture was not changed.');
} catch (Throwable $exception) {
    profilePictureRedirect('Unable to save or remove photos right now. Please try again.');
}
