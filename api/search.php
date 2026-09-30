<?php

$filters = [
    'name' => trim($_GET['name'] ?? ''),
];

$results = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = mysqli_connect(
        getenv('DB_HOST') ?: 'localhost',
        getenv('DB_USER') ?: 'root',
        getenv('DB_PASSWORD') ?: '',
        getenv('DB_NAME') ?: 'pirati ghasum'
    );

    if (!$conn) {
        $error = 'Unable to connect to the database.';
    } else {
        mysqli_set_charset($conn, 'utf8mb4');
        $conditions = ['u.role = \'user\'', 'u.status = \'approved\''];
        $types = '';
        $values = [];

        if ($filters['name'] !== '') {
            $conditions[] = '(u.username LIKE ? OR p.full_name LIKE ?)';
            $types .= 'ss';
            $namePattern = '%' . $filters['name'] . '%';
            $values[] = $namePattern;
            $values[] = $namePattern;
        }

        $query = 'SELECT u.id, u.username, p.full_name, p.age, p.gender, p.country, p.city,
                         p.religion, p.bio, p.profile_pic
                  FROM profiles p
                  INNER JOIN users u ON u.id = p.user_id
                  WHERE ' . implode(' AND ', $conditions) . '
                  ORDER BY p.full_name';
        $stmt = mysqli_prepare($conn, $query);

        if (!$stmt) {
            $error = 'Unable to process the search.';
        } else {
            if ($types !== '') {
                mysqli_stmt_bind_param($stmt, $types, ...$values);
            }
            if (!mysqli_stmt_execute($stmt)) {
                $error = 'Unable to process the search.';
            } else {
                $result = mysqli_stmt_get_result($stmt);
                $results = mysqli_fetch_all($result, MYSQLI_ASSOC);
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_close($conn);
    }
}

function escaped(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<style>
    .search-page {
        max-width: 900px;
        margin: 0 auto;
        color: #29201f;
    }

    .search-page .search-container {
        padding: 24px;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 4px 18px #00000012;
    }

    .search-page label {
        display: flex;
        flex-direction: column;
        gap: 6px;
        font-weight: 600;
    }

    .search-page input {
        padding: 10px;
        border: 1px solid #d7cdca;
        border-radius: 6px;
        font: inherit;
    }

    .search-page button {
        margin-top: 16px;
        padding: 11px 20px;
        border: 0;
        border-radius: 6px;
        background: #8d3d58;
        color: #fff;
        font-weight: 700;
        cursor: pointer;
    }

    .search-page .error {
        color: #a21d1d;
    }

    .search-page .results {
        margin-top: 28px;
    }

    .search-page .profile {
        display: flex;
        align-items: flex-start;
        gap: 18px;
        padding: 18px 0;
        border-top: 1px solid #eee;
    }

    .search-page .profile-avatar {
        width: 76px;
        height: 76px;
        flex: 0 0 76px;
        overflow: hidden;
        border-radius: 50%;
        background: #ffced6;
        object-fit: cover;
    }

    .search-page .profile-details h3 {
        margin: 0 0 6px;
    }

    .search-page .profile-details p {
        margin: 6px 0;
    }

    @media (max-width: 600px) {
        .search-page .search-container {
            padding: 16px;
        }
    }
</style>

<section class="search-page">
    <div class="search-container">
        <h1>Advanced Search</h1>
        <p>Search members by name or username.</p>

        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escaped($error) ?></p>
        <?php endif; ?>

        <form method="get" action="index.php?page=search">
            <label>Name or Username
                <input type="search" name="name" value="<?= escaped($filters['name']) ?>"
                    placeholder="Search by name or username">
            </label>
            <button type="submit" name="search" value="1">Search Now</button>
        </form>

        <?php if (isset($_GET['search']) && $error === ''): ?>
            <section class="results" aria-live="polite">
                <h2><?= count($results) ?> member<?= count($results) === 1 ? '' : 's' ?> found</h2>
                <?php foreach ($results as $profile): ?>
                    <article class="profile">
                        <a href="index.php?page=viewprofile&amp;id=<?= (int) $profile['id'] ?>" aria-label="View <?= escaped($profile['full_name'] ?: $profile['username']) ?>'s profile">
                            <?php if (!empty($profile['profile_pic']) && $profile['profile_pic'] !== 'default.png'): ?>
                                <img class="profile-avatar" src="uploads/profile/<?= escaped(basename($profile['profile_pic'])) ?>"
                                    alt="<?= escaped($profile['full_name'] ?: $profile['username']) ?>'s profile picture">
                            <?php else: ?>
                                <span class="profile-avatar" style="display:grid;place-items:center;font-size:28px;font-weight:700;color:#611">
                                    <?= escaped(strtoupper(substr((string) ($profile['full_name'] ?: $profile['username']), 0, 1))) ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="profile-details">
                            <h3><a href="index.php?page=viewprofile&amp;id=<?= (int) $profile['id'] ?>">
                                <?= escaped($profile['full_name'] ?: $profile['username']) ?>
                            </a></h3>
                            <p>@<?= escaped($profile['username']) ?>
                                <?php if (!empty($profile['age'])): ?> · <?= (int) $profile['age'] ?> years old<?php endif; ?>
                            </p>
                            <p><?= escaped(ucfirst((string) ($profile['gender'] ?? ''))) ?> ·
                                <?= escaped(implode(', ', array_filter([$profile['city'] ?? '', $profile['country'] ?? '']))) ?></p>
                            <?php if (!empty($profile['religion'])): ?>
                                <p>Religion: <?= escaped($profile['religion']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($profile['bio'])): ?>
                                <p><?= escaped($profile['bio']) ?></p>
                            <?php endif; ?>
                            <a href="index.php?page=viewprofile&amp;id=<?= (int) $profile['id'] ?>">View full profile</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>
</section>
