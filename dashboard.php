<?php
require_once __DIR__ . '/config/db.php';

if (empty($_SESSION['profile_picture_csrf'])) {
    $_SESSION['profile_picture_csrf'] = bin2hex(random_bytes(32));
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$db = getDBConnection();
$db->exec(
    'CREATE TABLE IF NOT EXISTS user_photos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_photos_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);
$photosStatement = $db->prepare('SELECT id, file_name FROM user_photos WHERE user_id = ? ORDER BY created_at DESC, id DESC');
$photosStatement->execute([$userId]);
$savedPhotos = $photosStatement->fetchAll();
$profilePictureMessage = (string) ($_SESSION['profile_picture_message'] ?? '');
unset($_SESSION['profile_picture_message']);
?>

<style>
    .dashboard-picture-card {
        margin: 24px 0;
        padding: 22px;
        border: 1px solid #f0e3e8;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 6px 24px rgba(69, 23, 45, 0.06);
    }

    .dashboard-photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 14px;
        margin-top: 22px;
    }

    .dashboard-photo-item {
        overflow: hidden;
        border: 1px solid #f0e3e8;
        border-radius: 12px;
        background: #fff;
    }

    .dashboard-photo-item img {
        display: block;
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
    }

    .dashboard-photo-item form {
        padding: 8px;
    }

    .dashboard-photo-item button {
        width: 100%;
        padding: 8px;
        border: 0;
        border-radius: 7px;
        background: #f3e8ec;
        color: #8d3d58;
        font: inherit;
        cursor: pointer;
    }

    .dashboard-picture-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 12px;
    }

    .dashboard-picture-actions button {
        padding: 10px 14px;
        border: 0;
        border-radius: 8px;
        background: #8d3d58;
        color: #fff;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }

    .dashboard-picture-message {
        margin: 10px 0;
        color: #176b35;
    }

    .dashboard-picture-error {
        color: #9b1c1c;
    }

</style>

<div class="greeting">

    <div>

        <h1>
            Hello,
            <span><?= htmlspecialchars($_SESSION['username'] ?? 'Guest', ENT_QUOTES, 'UTF-8') ?></span>!
        </h1>

        <p>
            Welcome back to pirati gasum.
            Find out who is matching with you today.
        </p>

    </div>


    <div>

        <a href="index.php?page=suggestion" class="btn-primary">
            Find Matches
        </a>

    </div>

</div>

<section class="dashboard-picture-card" aria-labelledby="dashboard-picture-title">
    <h2 id="dashboard-picture-title">My saved photos</h2>
    <p>Add photos to your dashboard gallery. These photos are saved separately and will not replace your profile picture. You can save up to 12 photos.</p>

    <?php if ($profilePictureMessage !== ''): ?>
        <p class="dashboard-picture-message" role="status">
            <?= htmlspecialchars($profilePictureMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if (count($savedPhotos) < 12): ?>
        <form method="post" action="api/profile-picture.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['profile_picture_csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="add">
            <label for="dashboard-photos">Choose photos (JPG, PNG, or WebP; up to 5 MB each)</label>
            <input id="dashboard-photos" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
            <div class="dashboard-picture-actions">
                <button type="submit">Save photos</button>
            </div>
        </form>
    <?php else: ?>
        <p>You have reached the 12-photo limit. Remove a saved photo to add another.</p>
    <?php endif; ?>

    <?php if ($savedPhotos !== []): ?>
        <div class="dashboard-photo-grid">
            <?php foreach ($savedPhotos as $savedPhoto): ?>
                <article class="dashboard-photo-item">
                    <img src="uploads/profile/<?= htmlspecialchars(basename($savedPhoto['file_name']), ENT_QUOTES, 'UTF-8') ?>"
                        alt="Saved photo">
                    <form method="post" action="api/profile-picture.php">
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['profile_picture_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="photo_id" value="<?= (int) $savedPhoto['id'] ?>">
                        <button type="submit">Remove photo</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>You have not saved any extra photos yet.</p>
    <?php endif; ?>
</section>



<div class="stats-row">



    <div class="stat-card">

        <div class="stat-title">
            MATCHES
        </div>

        <div class="stat-number">
            0
        </div>

        <div class="stat-icon">
            ❤
        </div>

    </div>



    

    <div class="stat-card">

        <div class="stat-title">
            UNREAD CHAT
        </div>

        <div class="stat-number">
            0
        </div>

        <div class="stat-icon">
            💬
        </div>

    </div>



    

    <div class="stat-card">

        <div class="stat-title">
            ALERTS
        </div>

        <div class="stat-number">
            1
        </div>

        <div class="stat-icon">
            🔔
        </div>

    </div>



    

    <div class="stat-card">

        <div class="stat-title">
            PROFILE PROGRESS
        </div>

        <div class="stat-number">
            58%
        </div>

        <div class="stat-icon">
            👤
        </div>

    </div>


</div>