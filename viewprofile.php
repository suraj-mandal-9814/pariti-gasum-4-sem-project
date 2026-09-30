<?php

require_once __DIR__ . '/config/db.php';

$viewedUserId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
	'options' => ['min_range' => 1],
]);
$viewedProfile = null;
$profileInterests = [];

if ($viewedUserId !== false && $viewedUserId !== null) {
	$profileStatement = getDBConnection()->prepare(
		'SELECT u.id, u.username, p.full_name, p.age, p.gender, p.country, p.city,
				p.religion, p.bio, p.profile_pic
		 FROM users u
		 INNER JOIN profiles p ON p.user_id = u.id
		 WHERE u.id = ? AND u.role = \'user\' AND u.status = \'approved\'
		 LIMIT 1'
	);
	$profileStatement->execute([$viewedUserId]);
	$viewedProfile = $profileStatement->fetch() ?: null;

	if ($viewedProfile !== null) {
		$interestStatement = getDBConnection()->prepare(
			'SELECT interest_name FROM interests WHERE user_id = ? ORDER BY interest_name'
		);
		$interestStatement->execute([$viewedUserId]);
		$profileInterests = $interestStatement->fetchAll(PDO::FETCH_COLUMN);
	}
}

function viewProfileEscape(?string $value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

?>

<style>
	.member-profile {
		max-width: 760px;
		margin: 0 auto;
		padding: 28px;
		border: 1px solid #f0e3e8;
		border-radius: 16px;
		background: #fff;
		box-shadow: 0 6px 24px rgba(69, 23, 45, 0.06);
	}

	.member-profile-header {
		display: flex;
		align-items: center;
		gap: 20px;
		margin-bottom: 24px;
	}

	.member-profile-avatar {
		display: grid;
		width: 112px;
		height: 112px;
		flex: 0 0 112px;
		overflow: hidden;
		place-items: center;
		border-radius: 50%;
		background: #ffced6;
		color: #611;
		font-size: 38px;
		font-weight: 700;
	}

	.member-profile-avatar img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.member-profile h1 {
		margin: 0 0 6px;
	}

	.member-profile-meta,
	.member-profile-username {
		color: #777;
	}

	.member-profile-detail {
		padding: 14px 0;
		border-top: 1px solid #eee;
	}

	.member-profile-interests {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
		margin-top: 10px;
	}

	.member-profile-interest {
		padding: 6px 11px;
		border-radius: 20px;
		background: #fff0f4;
		color: #8d3d58;
		font-size: 14px;
	}

	.member-profile-back {
		display: inline-block;
		margin-top: 20px;
		color: #8d3d58;
		font-weight: 700;
	}

	@media (max-width: 520px) {
		.member-profile-header {
			align-items: flex-start;
			flex-direction: column;
		}
	}
</style>

<?php if ($viewedProfile === null): ?>
	<section class="member-profile">
		<h1>Profile not found</h1>
		<p>This member may no longer be available.</p>
		<a class="member-profile-back" href="index.php?page=search">Back to search</a>
	</section>
<?php else: ?>
	<?php
	$displayName = (string) ($viewedProfile['full_name'] ?: $viewedProfile['username']);
	$profilePicture = (string) ($viewedProfile['profile_pic'] ?? '');
	$location = implode(', ', array_filter([
		trim((string) ($viewedProfile['city'] ?? '')),
		trim((string) ($viewedProfile['country'] ?? '')),
	]));
	?>
	<article class="member-profile">
		<header class="member-profile-header">
			<div class="member-profile-avatar">
				<?php if ($profilePicture !== '' && $profilePicture !== 'default.png'): ?>
					<img src="uploads/profile/<?= viewProfileEscape(basename($profilePicture)) ?>"
						alt="<?= viewProfileEscape($displayName) ?>'s profile picture">
				<?php else: ?>
					<?= viewProfileEscape(strtoupper(substr($displayName, 0, 1))) ?>
				<?php endif; ?>
			</div>
			<div>
				<h1><?= viewProfileEscape($displayName) ?></h1>
				<p class="member-profile-username">@<?= viewProfileEscape($viewedProfile['username']) ?></p>
				<p class="member-profile-meta">
					<?php if (!empty($viewedProfile['age'])): ?>
						<?= (int) $viewedProfile['age'] ?> years old
					<?php endif; ?>
					<?php if (!empty($viewedProfile['age']) && $location !== ''): ?> &middot; <?php endif; ?>
					<?= viewProfileEscape($location) ?>
				</p>
			</div>
		</header>

		<?php if (!empty($viewedProfile['gender'])): ?>
			<p class="member-profile-detail"><strong>Gender:</strong>
				<?= viewProfileEscape(ucfirst((string) $viewedProfile['gender'])) ?></p>
		<?php endif; ?>
		<?php if (!empty($viewedProfile['religion'])): ?>
			<p class="member-profile-detail"><strong>Religion:</strong>
				<?= viewProfileEscape($viewedProfile['religion']) ?></p>
		<?php endif; ?>
		<?php if (!empty($viewedProfile['bio'])): ?>
			<div class="member-profile-detail">
				<strong>About</strong>
				<p><?= nl2br(viewProfileEscape($viewedProfile['bio'])) ?></p>
			</div>
		<?php endif; ?>
		<?php if ($profileInterests !== []): ?>
			<div class="member-profile-detail">
				<strong>Interests</strong>
				<div class="member-profile-interests">
					<?php foreach ($profileInterests as $interest): ?>
						<span class="member-profile-interest"><?= viewProfileEscape($interest) ?></span>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<a class="member-profile-back" href="index.php?page=messages&amp;user_id=<?= (int) $viewedProfile['id'] ?>">Send a message</a>

		<a class="member-profile-back" href="index.php?page=search">Back to search</a>
	</article>
<?php endif; ?>
