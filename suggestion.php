<?php

require_once __DIR__ . '/config/db.php';

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$db = getDBConnection();

$profileStatement = $db->prepare(
    'SELECT city, country, age
     FROM profiles
     WHERE user_id = ?
     LIMIT 1'
);
$profileStatement->execute([$currentUserId]);
$currentProfile = $profileStatement->fetch() ?: [];

$currentCity = trim((string) ($currentProfile['city'] ?? ''));
$currentCountry = trim((string) ($currentProfile['country'] ?? ''));
$currentAge = (int) ($currentProfile['age'] ?? 0);

$suggestionStatement = $db->prepare(
    'SELECT u.id, u.username, p.full_name, p.age, p.gender, p.city,
            p.country, p.bio, p.profile_pic,
            (CASE WHEN ? <> \'\' AND p.city = ? THEN 3 ELSE 0 END
             + CASE WHEN ? <> \'\' AND p.country = ? THEN 2 ELSE 0 END) AS match_score
     FROM users u
     INNER JOIN profiles p ON p.user_id = u.id
     WHERE u.id <> ? AND u.role = \'user\' AND u.status = \'approved\'
     ORDER BY match_score DESC,
              CASE WHEN ? > 0 AND p.age IS NOT NULL THEN ABS(p.age - ?) ELSE 999 END ASC,
              COALESCE(NULLIF(p.full_name, \'\'), u.username) ASC
     LIMIT 12'
);
$suggestionStatement->execute([
    $currentCity,
    $currentCity,
    $currentCountry,
    $currentCountry,
    $currentUserId,
    $currentAge,
    $currentAge,
]);
$suggestions = $suggestionStatement->fetchAll();

function suggestionEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

?>

<style>
    .suggestions-page {
        max-width: 1100px;
        margin: 0 auto;
    }

    .suggestions-intro {
        margin-bottom: 22px;
    }

    .suggestions-intro p {
        color: #777;
    }

    .suggestion-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 18px;
    }

    .suggestion-card {
        overflow: hidden;
        padding: 20px;
        border: 1px solid #f0e3e8;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 6px 24px rgba(69, 23, 45, 0.06);
    }

    .suggestion-avatar {
        display: grid;
        width: 84px;
        height: 84px;
        margin: 0 auto 14px;
        overflow: hidden;
        place-items: center;
        border: 3px solid #fff0f4;
        border-radius: 50%;
        background: linear-gradient(135deg, #dd8aa0, #ffced6);
        color: #611;
        font-size: 28px;
        font-weight: 700;
    }

    .suggestion-profile-link {
        display: block;
        width: fit-content;
        margin: 0 auto;
        text-decoration: none;
    }

    .suggestion-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .suggestion-card h2 {
        margin: 0;
        text-align: center;
        font-size: 19px;
    }

    .suggestion-card h2 a {
        color: inherit;
        text-decoration: none;
    }

    .suggestion-card h2 a:hover,
    .suggestion-card h2 a:focus-visible {
        color: #8d3d58;
        text-decoration: underline;
    }

    .suggestion-meta {
        margin: 8px 0;
        color: #777;
        text-align: center;
        font-size: 14px;
    }

    .suggestion-bio {
        min-height: 42px;
        color: #555;
        font-size: 14px;
        line-height: 1.5;
    }

    .suggestion-action {
        display: block;
        margin-top: 16px;
        padding: 10px 14px;
        border-radius: 24px;
        background: linear-gradient(90deg, #ff2d6f, #ff6f9f);
        color: #fff;
        font-weight: 700;
        text-align: center;
        text-decoration: none;
    }

    .suggestion-action--profile {
        border: 1px solid #8d3d58;
        background: #fff;
        color: #8d3d58;
    }

    .suggestions-empty {
        padding: 24px;
        border: 1px solid #f0e3e8;
        border-radius: 14px;
        background: #fff;
        color: #666;
        text-align: center;
    }

    @media (max-width: 600px) {
        .suggestion-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="suggestions-page">
    <header class="suggestions-intro">
        <h1>Friend Suggestions</h1>
        <p>Meet approved members nearby and start a conversation.</p>
    </header>

    <?php if ($suggestions === []): ?>
        <p class="suggestions-empty">There are no other members to suggest right now. Please check back later.</p>
    <?php else: ?>
        <div class="suggestion-grid">
            <?php foreach ($suggestions as $suggestion): ?>
                <?php
                $displayName = (string) ($suggestion['full_name'] ?: $suggestion['username']);
                $profilePicture = (string) ($suggestion['profile_pic'] ?? '');
                $location = implode(', ', array_filter([
                    trim((string) ($suggestion['city'] ?? '')),
                    trim((string) ($suggestion['country'] ?? '')),
                ]));
                ?>
                <article class="suggestion-card">
                    <a class="suggestion-profile-link"
                        href="index.php?page=viewprofile&amp;id=<?= (int) $suggestion['id'] ?>"
                        aria-label="View <?= suggestionEscape($displayName) ?>'s profile">
                        <div class="suggestion-avatar">
                            <?php if ($profilePicture !== '' && $profilePicture !== 'default.png'): ?>
                                <img
                                    src="uploads/profile/<?= suggestionEscape(basename($profilePicture)) ?>"
                                    alt="<?= suggestionEscape($displayName) ?>'s profile picture"
                                >
                            <?php else: ?>
                                <?= suggestionEscape(strtoupper(substr($displayName, 0, 1))) ?>
                            <?php endif; ?>
                        </div>
                    </a>
                    <h2><a href="index.php?page=viewprofile&amp;id=<?= (int) $suggestion['id'] ?>">
                        <?= suggestionEscape($displayName) ?>
                    </a></h2>
                    <p class="suggestion-meta">
                        <?php if (!empty($suggestion['age'])): ?>
                            <?= (int) $suggestion['age'] ?> years old
                        <?php endif; ?>
                        <?php if (!empty($suggestion['age']) && $location !== ''): ?> &middot; <?php endif; ?>
                        <?= suggestionEscape($location !== '' ? $location : 'Location not added') ?>
                    </p>
                    <p class="suggestion-bio">
                        <?= suggestionEscape((string) ($suggestion['bio'] ?: 'Say hello and get to know each other.')) ?>
                    </p>
                    <a class="suggestion-action suggestion-action--profile"
                        href="index.php?page=viewprofile&amp;id=<?= (int) $suggestion['id'] ?>">View profile</a>
                    <a class="suggestion-action"
                        href="index.php?page=messages&amp;user_id=<?= (int) $suggestion['id'] ?>">Say hello</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
