<?php

$filters = [
    'gender' => $_GET['gender'] ?? '',
    'min_age' => $_GET['min_age'] ?? '',
    'max_age' => $_GET['max_age'] ?? '',
    'country' => trim($_GET['country'] ?? ''),
    'city' => trim($_GET['city'] ?? ''),
    'religion' => trim($_GET['religion'] ?? ''),
    'interest' => trim($_GET['interest'] ?? ''),
];

$allowedGenders = ['male', 'female', 'other'];
$results = [];
$error = '';

$minAge = filter_var($filters['min_age'], FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 18, 'max_range' => 120],
]);
$maxAge = filter_var($filters['max_age'], FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 18, 'max_range' => 120],
]);

if ($filters['gender'] !== '' && !in_array($filters['gender'], $allowedGenders, true)) {
    $filters['gender'] = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    if ($filters['min_age'] !== '' && $minAge === false) {
        $error = 'Minimum age must be between 18 and 120.';
    } elseif ($filters['max_age'] !== '' && $maxAge === false) {
        $error = 'Maximum age must be between 18 and 120.';
    } elseif ($minAge !== false && $maxAge !== false && $minAge > $maxAge) {
        $error = 'Minimum age cannot be greater than maximum age.';
    } else {
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = mysqli_connect(
            getenv('DB_HOST') ?: 'localhost',
            getenv('DB_USER') ?: 'root',
            getenv('DB_PASSWORD') ?: '',
            getenv('DB_NAME') ?: 'dating_website'
        );

        if (!$conn) {
            $error = 'Unable to connect to the database.';
        } else {
            mysqli_set_charset($conn, 'utf8mb4');
            $conditions = ['u.role = \'user\'', 'u.status = \'approved\''];
            $types = '';
            $values = [];

            if ($filters['gender'] !== '') {
                $conditions[] = 'p.gender = ?';
                $types .= 's';
                $values[] = $filters['gender'];
            }
            if ($minAge !== false) {
                $conditions[] = 'p.age >= ?';
                $types .= 'i';
                $values[] = $minAge;
            }
            if ($maxAge !== false) {
                $conditions[] = 'p.age <= ?';
                $types .= 'i';
                $values[] = $maxAge;
            }
            foreach (['country', 'city', 'religion'] as $field) {
                if ($filters[$field] !== '') {
                    $conditions[] = "p.$field LIKE ?";
                    $types .= 's';
                    $values[] = '%' . $filters[$field] . '%';
                }
            }
            if ($filters['interest'] !== '') {
                $conditions[] = 'EXISTS (
                    SELECT 1 FROM interests i
                    WHERE i.user_id = p.user_id AND i.interest_name LIKE ?
                )';
                $types .= 's';
                $values[] = '%' . $filters['interest'] . '%';
            }

            $query = 'SELECT p.full_name, p.age, p.gender, p.country, p.city,
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
}

function escaped(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Search | Pirati Gasum</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f8f5f3; color: #29201f; }
        .search-container { max-width: 760px; margin: 40px auto; padding: 28px; background: #fff; border-radius: 12px; box-shadow: 0 4px 18px #00000012; }
        .filters { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        label { display: flex; flex-direction: column; gap: 6px; font-weight: 600; }
        input, select { padding: 10px; border: 1px solid #d7cdca; border-radius: 6px; font: inherit; }
        .full-width { grid-column: 1 / -1; }
        button { margin-top: 20px; padding: 11px 20px; border: 0; border-radius: 6px; background: #8d3d58; color: #fff; font-weight: 700; cursor: pointer; }
        .error { color: #a21d1d; }
        .results { margin-top: 28px; }
        .profile { padding: 14px 0; border-top: 1px solid #eee; }
        @media (max-width: 600px) { .filters { grid-template-columns: 1fr; } .full-width { grid-column: auto; } }
    </style>
</head>
<body>
<main class="search-container">
    <h1>Advanced Search</h1>
    <p>Use real-time filters to locate members matching your criteria.</p>

    <?php if ($error !== ''): ?>
        <p class="error" role="alert"><?= escaped($error) ?></p>
    <?php endif; ?>

    <form method="get" action="<?= escaped($_SERVER['PHP_SELF']) ?>">
        <div class="filters">
            <label>Gender
                <select name="gender">
                    <option value="">Any</option>
                    <?php foreach ($allowedGenders as $gender): ?>
                        <option value="<?= $gender ?>" <?= $filters['gender'] === $gender ? 'selected' : '' ?>>
                            <?= ucfirst($gender) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Min Age
                <input type="number" name="min_age" min="18" max="120" value="<?= escaped((string) $filters['min_age']) ?>">
            </label>
            <label>Max Age
                <input type="number" name="max_age" min="18" max="120" value="<?= escaped((string) $filters['max_age']) ?>">
            </label>
            <label>Country
                <input type="text" name="country" value="<?= escaped($filters['country']) ?>">
            </label>
            <label>City
                <input type="text" name="city" value="<?= escaped($filters['city']) ?>">
            </label>
            <label>Religion
                <input type="text" name="religion" value="<?= escaped($filters['religion']) ?>">
            </label>
            <label class="full-width">Interest Key
                <input type="text" name="interest" value="<?= escaped($filters['interest']) ?>">
            </label>
        </div>
        <button type="submit" name="search" value="1">Search Now</button>
    </form>

    <?php if (isset($_GET['search']) && $error === ''): ?>
        <section class="results" aria-live="polite">
            <h2><?= count($results) ?> member<?= count($results) === 1 ? '' : 's' ?> found</h2>
            <?php foreach ($results as $profile): ?>
                <article class="profile">
                    <h3><?= escaped($profile['full_name']) ?>, <?= (int) $profile['age'] ?></h3>
                    <p><?= escaped(ucfirst($profile['gender'])) ?> · <?= escaped($profile['city']) ?>, <?= escaped($profile['country']) ?></p>
                    <?php if ($profile['religion'] !== null && $profile['religion'] !== ''): ?>
                        <p>Religion: <?= escaped($profile['religion']) ?></p>
                    <?php endif; ?>
                    <?php if ($profile['bio'] !== null && $profile['bio'] !== ''): ?>
                        <p><?= escaped($profile['bio']) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
