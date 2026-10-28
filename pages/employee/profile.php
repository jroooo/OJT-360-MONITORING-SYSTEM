
<?php /* Adviser profile page fragment. */ ?>

<section class="page" id="page-profile">

<!-- Profile cover: same structure as student profile -->
<div class="employee-profile-cover"></div>

<!-- Profile header -->
<section class="employee-profile-hero">
    <div class="employee-profile-avatar">
        <?= $employeeAvatarHtml ?>
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

    <button class="employee-profile-photo-action" type="button" id="employeeChangePhoto">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 8.5A1.5 1.5 0 0 1 5.5 7h2l1.2-2h6.6l1.2 2h2A1.5 1.5 0 0 1 20 8.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-9Z" />
            <circle cx="12" cy="12.5" r="3.25" />
        </svg>
        Change photo
    </button>
    <input type="file" id="employeeProfilePhotoInput" accept="image/jpeg,image/png,image/webp" hidden>
</section>

<!-- Account statistics -->
<div class="employee-profile-stats">
    <article>
        <span class="employee-profile-icon">♙</span>
        <small>Account type</small>
        <strong>
            <?= htmlspecialchars($displayPosition ?: 'Adviser', ENT_QUOTES, 'UTF-8') ?>
        </strong>
    </article>

    <article>
        <span class="employee-profile-icon">✉</span>
        <small>Email address</small>
        <strong>
            <?= htmlspecialchars($displayEmail ?: 'Not provided', ENT_QUOTES, 'UTF-8') ?>
        </strong>
    </article>

    <article>
        <span class="employee-profile-icon">▦</span>
        <small>Department</small>
        <strong>
            <?= htmlspecialchars($displayDepartment ?: 'Not provided', ENT_QUOTES, 'UTF-8') ?>
        </strong>
    </article>

    <article>
        <span class="employee-profile-icon">✓</span>
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
                <p><?= htmlspecialchars($middleName ?: '—', ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div>
                <label>Last name</label>
                <p><?= htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div>
                <label>Email address</label>
                <p><?= htmlspecialchars($displayEmail ?: '—', ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div>
                <label>Contact number</label>
                <p><?= htmlspecialchars($displayContact ?: '—', ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div>
                <label>Username</label>
                <p><?= htmlspecialchars($displayUsername ?: '—', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <a class="employee-profile-action" href="employee_settings.php">
            Manage account settings <span>›</span>
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
            Open Settings <span>›</span>
        </a>
    </section>

</div>

</section>
