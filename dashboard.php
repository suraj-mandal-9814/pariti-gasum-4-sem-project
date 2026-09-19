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

        <a
            href="index.php?page=suggestion"
            class="btn-primary"
        >
            Find Matches
        </a>

    </div>

</div>



<div class="stats-row">


    <!-- MATCHES -->

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



    <!-- UNREAD CHAT -->

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



    <!-- ALERTS -->

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



    <!-- PROFILE PROGRESS -->

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
