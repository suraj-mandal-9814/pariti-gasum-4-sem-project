<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

$displayName = $_SESSION['username'] ?? 'Guest';
$profilePicture = '';

if (isset($_SESSION['user_id'])) {
    $profileStatement = getDBConnection()->prepare(
        'SELECT profile_pic FROM profiles WHERE user_id = ? LIMIT 1'
    );
    $profileStatement->execute([(int) $_SESSION['user_id']]);
    $profilePicture = (string) ($profileStatement->fetchColumn() ?: '');
}

$avatarLetter = strtoupper(substr($displayName, 0, 1));
?>
<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>PIRATI GASUM</title>


    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@600&display=swap"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="Frontend/assets/css/dashboard.css?v=2"
    >

</head>


<body>

<div class="app">


    <!-- =================================
         ASIDE BAR
    ================================== -->

    <aside class="sidebar">


        <!-- LOGO -->

        <div class="logo">

            <div class="logo-mark">
                ❤
            </div>

            <div class="logo-text">
                PIRATI GASUM
            </div>

        </div>


        <!-- PROFILE -->

        <div class="profile">

            <div class="avatar">
                <?php if ($profilePicture !== '' && $profilePicture !== 'default.png'): ?>
                    <img
                        src="uploads/profile/<?= htmlspecialchars(basename($profilePicture), ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>'s profile picture"
                    >
                <?php else: ?>
                    <?= htmlspecialchars($avatarLetter, ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>

            <div class="pname">
                <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div class="psub">
                Welcome back!
            </div>

        </div>


        <!-- NAVIGATION -->

        <nav class="side-nav">


            <!-- DASHBOARD -->

            <a
                href="index.php?page=dashboard"
                class="nav-item <?= $page === 'dashboard' ? 'active' : '' ?>"
            >

                <span class="icon">
                    ▦
                </span>

                Dashboard

            </a>


            <!-- EDIT PROFILE -->

            <a
                href="index.php?page=edit"
                class="nav-item <?= $page === 'edit' ? 'active' : '' ?>"
            >

                <span class="icon">
                    ✎
                </span>

                Edit Profile

            </a>


            <!-- SUGGESTIONS -->

            <a
                href="index.php?page=suggestion"
                class="nav-item <?= $page === 'suggestion' ? 'active' : '' ?>"
            >

                <span class="icon">
                    ♡
                </span>

                Suggested Matches

            </a>


            <!-- SEARCH -->


            <!-- MESSAGES -->

            <a
                href="index.php?page=messages"
                class="nav-item <?= $page === 'messages' ? 'active' : '' ?>"
            >

                <span class="icon">
                    💬
                </span>

                Messages

            </a>


            <!-- NOTIFICATIONS -->

            <a
                href="index.php?page=notification"
                class="nav-item <?= $page === 'notification' ? 'active' : '' ?>"
            >

                <span class="icon">
                    🔔
                </span>

                Notifications

            </a>


        </nav>

        
        <!-- LOGOUT -->

        <div class="sidebar-footer">

            <a
                class="logout"
                href="logout.php"
            >
                Logout
            </a>

        </div>




    </aside>



    <!-- =================================
         MAIN
    ================================== -->

    <main class="main">


        <!-- TOP BAR -->

        <header class="topbar">


            <div class="top-links">


                <a href="index.php?page=dashboard">
                    Dashboard
                </a>


                <a href="index.php?page=suggestion">
                    Find Matches
                </a>


                <a href="index.php?page=search">
                    Search
                </a>


                <a href="index.php?page=messages">
                    Messages
                </a>


                <a href="index.php?page=notification">
                    Notifications
                </a>


            </div>
        </header>



        <!-- =================================
             OUTLET
             
             This is equivalent to React:
             <Outlet />
        ================================== -->

        <section class="content">

            <?php

            include $content;

            ?>

        </section>


    </main>


</div>

</body>

</html>
