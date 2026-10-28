
<?php /* Adviser profile page fragment. */ ?>

<!-- Profile cover: same structure as student profile -->
<div class="employee-profile-cover"></div>

<!-- Profile header -->
<section class="employee-profile-hero">
    <div class="employee-profile-avatar">
        <?= htmlspecialchars($displayInitials, ENT_QUOTES, 'UTF-8') ?>
    </div>

    <div class="employee-profile-identity">
        <h1><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></h1>

        <p>
            <?= htmlspecialchars(
                $displayDepartment !== ''
                    ? $displayDepartment
                    : 'OJT360 Employee Portal',
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>

        <div class="employee-profile-tags">
            <span><?= htmlspecialchars($displayPosition, ENT_QUOTES, 'UTF-8') ?></span>
            <span>
                Employee ID:
                <?= htmlspecialchars((string)$displayEmployeeId, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
    </div>
</section>

<!-- Account statistics -->
<div class="employee-profile-stats">
    <article>
        <span class="employee-profile-icon">ΓÖÖ</span>
        <small>Account type</small>
        <strong>
            <?= htmlspecialchars($displayPosition ?: 'Adviser', ENT_QUOTES, 'UTF-8') ?>
        </strong>
    </article>

    <article>
        <span class="employee-profile-icon">Γ£ë</span>
        <small>Email address</small>
        <strong>
            <?= htmlspecialchars($displayEmail ?: 'Not provided', ENT_QUOTES, 'UTF-8') ?>
        </strong>
    </article>

    <article>
        <span class="employee-profile-icon">Γûª</span>
        <small>Department</small>
        <strong>
            <?= htmlspecialchars($displayDepartment ?: 'Not provided', ENT_QUOTES, 'UTF-8') ?>
        </strong>
    </article>

    <article>
        <span class="employee-profile-icon">Γ£ô</span>
        <small>Account status</small>
        <strong>Active session</strong>
    </article>
</div>

<!-- Personal details and work information -->
<div class="employee-profile-columns">

    <section class="employee-profile-card">
        <div class="employee-profile-eyebrow">PERSONAL DETAILS</div>
        <h2>Account information</h2>

        <div class="employee-profile-fields">
            <div>
                <label>First name</label>
                <p><?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div>
                <label>Middle name</label>
                <p><?= htmlspecialchars($middleName ?: 'ΓÇö', ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div>
                <label>Last name</label>
                <p><?= htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div>
                <label>Email address</label>
                <p><?= htmlspecialchars($displayEmail ?: 'ΓÇö', ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div>
                <label>Contact number</label>
                <p><?= htmlspecialchars($displayContact ?: 'ΓÇö', ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div>
                <label>Username</label>
                <p><?= htmlspecialchars($displayUsername ?: 'ΓÇö', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <a class="employee-profile-action" href="employee_settings.php">
            Manage account settings <span>ΓÇ║</span>
        </a>
    </section>

    <section class="employee-profile-card employee-profile-about">
        <div class="employee-profile-eyebrow">ADVISER ACCOUNT</div>
        <h2>Work information</h2>

        <div class="employee-profile-detail">
            <span>Position</span>
            <strong><?= htmlspecialchars($displayPosition ?: 'Adviser', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>

        <div class="employee-profile-detail">
            <span>Department</span>
            <strong><?= htmlspecialchars($displayDepartment ?: 'Not provided', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>

        <div class="employee-profile-detail">
            <span>Employee ID</span>
            <strong><?= htmlspecialchars((string)$displayEmployeeId, ENT_QUOTES, 'UTF-8') ?></strong>
        </div>

        <p class="employee-profile-note">
            Your profile details are taken from your adviser account.
            To change account preferences or your password, open Settings.
        </p>

        <a class="employee-profile-action" href="employee_settings.php">
            Open Settings <span>ΓÇ║</span>
        </a>
    </section>

</div>
