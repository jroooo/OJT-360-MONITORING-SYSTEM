
<?php
$profileFlash = $_SESSION['employee_profile_flash'] ?? null;
unset($_SESSION['employee_profile_flash']);
?>

<div class="employee-profile-cover"></div>

<section class="employee-profile-hero">
    <div class="employee-profile-avatar-shell">
        <div class="employee-profile-avatar" id="employeeProfileAvatarPreview">
            <?php if ($employeeHasProfileImage && $employeeProfileImageUrl !== ''): ?>
                <img src="<?= htmlspecialchars($employeeProfileImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Employee profile photo">
            <?php else: ?>
                <?= htmlspecialchars($displayInitials, ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
        </div>

        <form method="post" action="employee_profile.php" enctype="multipart/form-data" class="employee-profile-upload-form">
            <input type="hidden" name="profile_action" value="upload_profile_image">
            <input id="employeeProfileUpload" class="employee-profile-file-input" type="file" name="profile_picture" accept="image/jpeg,image/png,image/webp">
            <label class="employee-profile-upload-trigger" for="employeeProfileUpload" title="Upload profile photo" aria-label="Upload profile photo">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V5m0 0 4 4m-4-4-4 4M4 16.5v2.25A1.25 1.25 0 0 0 5.25 20h13.5A1.25 1.25 0 0 0 20 18.75V16.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </label>
            <button type="submit" class="employee-profile-save-photo" name="profile_action" value="upload_profile_image">Save photo</button>
            <?php if ($employeeHasProfileImage): ?>
                <button type="submit" class="employee-profile-remove-photo" name="profile_action" value="delete_profile_image" formnovalidate>Remove</button>
            <?php endif; ?>
        </form>
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

<?php if ($profileFlash): ?>
    <div class="employee-profile-flash <?= htmlspecialchars($profileFlash['type'] ?? 'success', ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars((string)($profileFlash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="employee-profile-stats">
    <article>
        <span class="employee-profile-icon">◉</span>
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
        <span class="employee-profile-icon">⌂</span>
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
            Manage account settings <span>→</span>
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
            Open Settings <span>→</span>
        </a>
    </section>

</div>

<script>
(function () {
    const input = document.getElementById('employeeProfileUpload');
    const avatar = document.getElementById('employeeProfileAvatarPreview');
    if (!input || !avatar) return;

    input.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            return;
        }

        const reader = new FileReader();
        reader.onload = function (event) {
            avatar.innerHTML = '<img src="' + event.target.result + '" alt="Profile preview">';
        };
        reader.readAsDataURL(file);
    });
})();
</script>
