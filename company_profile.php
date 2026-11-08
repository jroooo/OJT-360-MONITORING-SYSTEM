<?php
require_once __DIR__ . '/includes/bootstrap.php';

$company = require_company();
$cid = (int) ($company['id'] ?? 0);
$pdo = db();

try {
    $profileColumn = $pdo->query("SHOW COLUMNS FROM companies LIKE 'profile_image'");
    if (!$profileColumn->fetch()) {
        $pdo->exec("ALTER TABLE companies ADD COLUMN profile_image VARCHAR(255) NULL AFTER representative_email");
    }
} catch (Throwable $e) {
    // Ignore if column already exists
}

if (is_post()) {
    require_csrf('company_profile.php');

    $action = $_POST['action'] ?? '';

    if ($action === 'upload_profile_image') {
        $targetDir = __DIR__ . '/uploads/company_profile';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $error = '';
        $profileUpload = $_FILES['profile_picture'] ?? null;
        $profileUploadError = is_array($profileUpload) ? (int) ($profileUpload['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;

        if ($profileUploadError === UPLOAD_ERR_NO_FILE) {
            redirect('company_profile.php');
        }

        if (empty($profileUpload['name']) || !isset($profileUpload['tmp_name']) || !is_uploaded_file($profileUpload['tmp_name'])) {
            $error = 'Please choose a profile picture to upload.';
        } else {
            $file = $profileUpload;
            $tmp = $file['tmp_name'];
            $name = (string) ($file['name'] ?? 'profile.jpg');
            $size = (int) ($file['size'] ?? 0);
            $type = strtolower((string) ($file['type'] ?? ''));

            if (!in_array($type, ['image/jpeg', 'image/png', 'image/webp'], true) && !preg_match('/\.(jpe?g|png|webp)$/i', $name)) {
                $error = 'Only JPG, PNG, and WEBP images are allowed.';
            } elseif ($size <= 0 || $size > 2 * 1024 * 1024) {
                $error = 'Profile picture must be smaller than 2MB.';
            } elseif (!is_readable($tmp)) {
                $error = 'The uploaded file could not be read.';
            } else {
                $imageInfo = @getimagesize($tmp);
                if ($imageInfo === false) {
                    $error = 'The uploaded file is not a valid image.';
                } else {
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if ($ext === '') {
                        $ext = 'jpg';
                    }

                    $filename = 'company_' . $cid . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $destination = $targetDir . '/' . $filename;

                    if (!move_uploaded_file($tmp, $destination)) {
                        $error = 'The profile picture could not be saved.';
                    } else {
                        $relative = 'uploads/company_profile/' . $filename;
                        $pdo->prepare('UPDATE companies SET profile_image = ? WHERE id = ?')->execute([$relative, $cid]);

                        $oldImage = (string) ($company['profile_image'] ?? '');
                        if ($oldImage !== '' && $oldImage !== $relative) {
                            $oldAbsolute = __DIR__ . '/' . ltrim($oldImage, '/');
                            if (is_file($oldAbsolute)) {
                                @unlink($oldAbsolute);
                            }
                        }

                        flash_set('success', 'Profile photo updated successfully.');
                        redirect('company_profile.php');
                    }
                }
            }
        }

        if ($error !== '') {
            flash_set('error', $error);
            redirect('company_profile.php');
        }
    }

    if ($action === 'delete_profile_image') {
        $oldImage = (string) ($company['profile_image'] ?? '');
        if ($oldImage !== '') {
            $oldAbsolute = __DIR__ . '/' . ltrim($oldImage, '/');
            if (is_file($oldAbsolute)) {
                @unlink($oldAbsolute);
            }
            $pdo->prepare('UPDATE companies SET profile_image = NULL WHERE id = ?')->execute([$cid]);
        }

        flash_set('success', 'Profile photo removed.');
        redirect('company_profile.php');
    }

    if ($action === 'update_profile') {
        $fields = [
            'company_name',
            'rep_first_name',
            'rep_middle_name',
            'rep_last_name',
            'rep_position',
            'contact_number',
            'representative_email',
        ];

        $values = [];
        foreach ($fields as $field) {
            $values[$field] = trim((string) ($_POST[$field] ?? ''));
        }

        $email = strtolower($values['representative_email']);
        $error = '';

        if ($values['company_name'] === '' || mb_strlen($values['company_name']) < 2 || mb_strlen($values['company_name']) > 150) {
            $error = 'Company name must be 2 to 150 characters.';
        } elseif (!name_ok($values['rep_first_name']) || !name_ok($values['rep_last_name']) || ($values['rep_middle_name'] !== '' && !name_ok($values['rep_middle_name']))) {
            $error = 'Names may contain letters, spaces, apostrophes, periods, and hyphens only.';
        } elseif ($values['rep_position'] === '' || mb_strlen($values['rep_position']) > 100) {
            $error = 'Position is required and must be 100 characters or fewer.';
        } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $values['contact_number'])) {
            $error = 'Enter a valid contact number.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            $error = 'Enter a valid representative email address.';
        } else {
            $existing = $pdo->prepare('SELECT id FROM companies WHERE representative_email = ? AND id <> ? LIMIT 1');
            $existing->execute([$email, $cid]);

            if ($existing->fetch()) {
                $error = 'That representative email is already used by another account.';
            } else {
                $pdo->prepare(
                    'UPDATE companies
                     SET company_name = ?, rep_first_name = ?, rep_middle_name = ?, rep_last_name = ?, rep_position = ?, contact_number = ?, representative_email = ?
                     WHERE id = ?'
                )->execute([
                    $values['company_name'],
                    $values['rep_first_name'],
                    $values['rep_middle_name'] !== '' ? $values['rep_middle_name'] : null,
                    $values['rep_last_name'],
                    $values['rep_position'],
                    $values['contact_number'],
                    $email,
                    $cid,
                ]);

                flash_set('success', 'Profile updated successfully.');
                redirect('company_profile.php');
            }
        }

        if ($error !== '') {
            flash_set('error', $error);
            redirect('company_profile.php');
        }
    }

    if ($action === 'change_password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        $hash = (string) ($company['row']['password_hash'] ?? '');

        $error = '';

        if (!password_verify($current, $hash)) {
            $error = 'Your current password is incorrect.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif (($policyError = validate_password_policy($new)) !== null) {
            $error = $policyError;
        } elseif ($new === $current) {
            $error = 'Choose a new password that is different from the current one.';
        } else {
            $pdo->prepare('UPDATE companies SET password_hash = ? WHERE id = ?')->execute([
                password_hash($new, PASSWORD_DEFAULT),
                $cid,
            ]);
            session_regenerate_id(true);
            flash_set('success', 'Password changed successfully.');
            redirect('company_profile.php');
        }

        if ($error !== '') {
            flash_set('error', $error);
            redirect('company_profile.php');
        }
    }
}

$companyRow = $company['row'];
$profileImage = (string) ($companyRow['profile_image'] ?? $company['profile_image'] ?? '');
$profileImagePath = $profileImage !== '' ? __DIR__ . '/' . ltrim($profileImage, '/') : '';
$hasProfileImage = $profileImage !== '' && is_file($profileImagePath);

$pageKey = 'profile';
$pageTitle = 'User Profile';
$eyebrow = 'COMPANY PROFILE';

require __DIR__ . '/includes/layout_top.php';
?>

<style>
.company-profile-page {
    --profile-border: #e2e8f0;
    --profile-muted: #64748b;
    --profile-text: #0f172a;
    --profile-card: #ffffff;
    --profile-bg: #f8fafc;
    --profile-accent: #2563eb;
    --profile-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
    
    padding-bottom: 32px;
    background: var(--profile-bg);
    color: var(--profile-text);
}

.company-profile-page * { box-sizing: border-box; }

.company-profile-page .profile-cover {
    position: relative;
    height: 180px;
    border-radius: 20px 20px 0 0;
    overflow: hidden;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
}

.company-profile-page .profile-card-container {
    background: var(--profile-card);
    border: 1px solid var(--profile-border);
    border-radius: 20px;
    box-shadow: var(--profile-shadow);
    overflow: hidden;
    margin-bottom: 24px;
}

.company-profile-page .profile-hero {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    padding: 0 28px 24px;
}

.company-profile-page .profile-avatar-container {
    position: relative;
    margin-top: -64px;
    margin-bottom: 12px;
}

.company-profile-page .profile-avatar-wrap {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 130px;
    height: 130px;
    border-radius: 50%;
    background: var(--profile-card);
    border: 4px solid var(--profile-card);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.company-profile-page .profile-avatar-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.company-profile-page .profile-avatar-fallback {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #2563eb;
    color: #ffffff;
    font-weight: 800;
    font-size: 2.25rem;
}

.company-profile-page .profile-summary h1 {
    margin: 0;
    color: var(--profile-text);
    font-size: 1.75rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.2;
}

.company-profile-page .profile-summary .muted {
    margin: 6px 0 12px;
    color: var(--profile-muted);
    font-size: 13px;
}

.company-profile-page .profile-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.company-profile-page .pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
}

.company-profile-page .pill.blue {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #dbeafe;
}

.company-profile-page .pill.gray {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.company-profile-page .profile-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    padding-bottom: 4px;
}

.company-profile-page .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 16px;
    border-radius: 12px;
    border: 1px solid var(--profile-border);
    background: #ffffff;
    color: #334155;
    font: inherit;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.15s ease;
}

.company-profile-page .btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.company-profile-page .btn.primary {
    background: #2563eb;
    border-color: #2563eb;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.company-profile-page .btn.primary:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

/* ============================================================
   MODAL POPUP STYLING (Aesthetic & Interactive)
   ============================================================ */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(6px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    padding: 20px;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.modal-overlay.show {
    display: flex;
    opacity: 1;
}

.modal-card {
    width: 100%;
    max-width: 440px;
    background: var(--profile-card);
    border: 1px solid var(--profile-border);
    border-radius: 24px;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.3);
    overflow: hidden;
    transform: scale(0.95);
    transition: transform 0.2s ease;
}

.modal-overlay.show .modal-card {
    transform: scale(1);
}

.modal-header {
    padding: 22px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--profile-border);
}

.modal-header h3 {
    margin: 0;
    font-size: 17px;
    font-weight: 800;
    color: var(--profile-text);
}

.modal-close {
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 10px;
    background: #f1f5f9;
    color: var(--profile-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s ease;
}

.modal-close:hover {
    background: #e2e8f0;
    color: var(--profile-text);
}

.modal-body {
    padding: 24px;
    text-align: center;
}

.modal-preview-wrap {
    width: 110px;
    height: 110px;
    margin: 0 auto 18px;
    border-radius: 50%;
    border: 3px solid #2563eb;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.15);
}

.modal-preview-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.modal-preview-fallback {
    font-size: 2rem;
    font-weight: 800;
    color: #2563eb;
}

.modal-body p {
    margin: 0 0 16px;
    font-size: 13px;
    color: var(--profile-muted);
    line-height: 1.5;
}

.modal-dropzone {
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    padding: 20px;
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.15s ease;
}

.modal-dropzone:hover {
    border-color: #2563eb;
    background: #eff6ff;
}

.modal-dropzone i {
    font-size: 28px;
    color: #2563eb;
    margin-bottom: 8px;
    display: block;
}

.modal-dropzone span {
    font-size: 12px;
    font-weight: 700;
    color: #334155;
    display: block;
}

.modal-dropzone small {
    font-size: 11px;
    color: var(--profile-muted);
    margin-top: 4px;
    display: block;
}

.modal-footer {
    padding: 16px 24px;
    background: #f8fafc;
    border-top: 1px solid var(--profile-border);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.company-profile-page .profile-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.company-profile-page .stat-card {
    background: var(--profile-card);
    border: 1px solid var(--profile-border);
    border-radius: 16px;
    padding: 20px;
    box-shadow: var(--profile-shadow);
}

.company-profile-page .stat-card .label {
    font-size: 11px;
    font-weight: 700;
    color: var(--profile-muted);
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.company-profile-page .stat-card .value {
    font-size: 14px;
    font-weight: 800;
    color: var(--profile-text);
    display: flex;
    align-items: center;
    gap: 8px;
}

.company-profile-page .profile-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 24px;
}

.company-profile-page .profile-card {
    background: var(--profile-card);
    border: 1px solid var(--profile-border);
    border-radius: 20px;
    box-shadow: var(--profile-shadow);
    padding: 24px;
}

.company-profile-page .panel-head {
    margin-bottom: 20px;
}

.company-profile-page .eyebrow {
    display: block;
    margin-bottom: 4px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #2563eb;
}

.company-profile-page .panel-head h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 800;
    color: var(--profile-text);
}

.company-profile-page .profile-form,
.company-profile-page .password-form {
    display: grid;
    gap: 16px;
}

.company-profile-page .field {
    display: grid;
    gap: 7px;
}

.company-profile-page .field label {
    font-size: 12px;
    font-weight: 700;
    color: #334155;
}

.company-profile-page .field input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    padding: 10px 14px;
    background: #f8fafc;
    color: var(--profile-text);
    font: inherit;
    font-size: 13px;
    min-height: 44px;
}

.company-profile-page .field input:focus {
    outline: none;
    border-color: #3b82f6;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.company-profile-page .form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.company-profile-page .form-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
    margin-top: 8px;
}

.company-profile-page .file-input {
    position: absolute;
    width: 1px; height: 1px; overflow: hidden; opacity: 0;
}

.company-profile-page .password-wrap { position: relative; }
.company-profile-page .password-wrap input { padding-right: 40px; }
.company-profile-page .password-toggle {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    border: 0; background: transparent; color: var(--profile-muted); cursor: pointer;
    width: 30px; height: 30px; border-radius: 8px;
}
.company-profile-page .password-toggle:hover { background: #f1f5f9; color: var(--profile-text); }

.danger-btn {
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #dc2626;
    border-radius: 12px;
    padding: 0 16px;
    min-height: 40px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}
.danger-btn:hover { background: #fee2e2; }

/* ============================================================
   DARK MODE OVERRIDES (DEEP NAVY)
   ============================================================ */
html[data-theme="dark"] .company-profile-page,
.dark .company-profile-page {
    --profile-bg: #0b0f19;
    --profile-surface: #111c38;
    --profile-surface-alt: #0d152c;
    --profile-surface-soft: #172342;
    --profile-border: #1e293b;
    --profile-border-strong: #2b3d58;
    --profile-text: #f8fafc;
    --profile-text-soft: #e2e8f0;
    --profile-muted: #cbd5e1;
    --profile-subtle: #94a3b8;
    --profile-brand: #60a5fa;
    --profile-card: #111c38;
    --profile-shadow: 0 15px 45px rgba(0, 0, 0, 0.28);
}

html[data-theme="dark"] .company-profile-page .profile-summary h1,
.dark .company-profile-page .profile-summary h1,
html[data-theme="dark"] .company-profile-page .panel-head h2,
.dark .company-profile-page .panel-head h2,
html[data-theme="dark"] .company-profile-page .stat-card .value,
.dark .company-profile-page .stat-card .value,
html[data-theme="dark"] .company-profile-page .modal-header h3,
.dark .company-profile-page .modal-header h3 {
    color: #ffffff !important;
}

html[data-theme="dark"] .company-profile-page .field label,
.dark .company-profile-page .field label {
    color: var(--profile-text-soft) !important;
}

html[data-theme="dark"] .company-profile-page .field input,
.dark .company-profile-page .field input {
    background: var(--profile-surface-alt) !important;
    border-color: var(--profile-border) !important;
    color: #ffffff !important;
}

html[data-theme="dark"] .company-profile-page .modal-card,
.dark .company-profile-page .modal-card {
    background: var(--profile-surface) !important;
    border-color: var(--profile-border) !important;
}

html[data-theme="dark"] .company-profile-page .modal-header,
.dark .company-profile-page .modal-header,
html[data-theme="dark"] .company-profile-page .modal-footer,
.dark .company-profile-page .modal-footer {
    background: var(--profile-surface) !important;
    border-color: var(--profile-border) !important;
}

html[data-theme="dark"] .company-profile-page .modal-dropzone,
.dark .company-profile-page .modal-dropzone {
    background: var(--profile-surface-alt) !important;
    border-color: var(--profile-border) !important;
}

html[data-theme="dark"] .company-profile-page .modal-close,
.dark .company-profile-page .modal-close {
    background: var(--profile-surface-alt) !important;
    color: var(--profile-muted) !important;
}

html[data-theme="dark"] .company-profile-page .btn,
.dark .company-profile-page .btn {
    background: var(--profile-surface-alt);
    border-color: var(--profile-border);
    color: var(--profile-text-soft);
}

html[data-theme="dark"] .company-profile-page .btn:hover,
.dark .company-profile-page .btn:hover {
    background: var(--profile-surface-soft);
    border-color: var(--profile-border-strong);
    color: #ffffff;
}

html[data-theme="dark"] .company-profile-page .btn.primary,
.dark .company-profile-page .btn.primary {
    background: #2563eb !important;
    border-color: #2563eb !important;
    color: #ffffff !important;
}

html[data-theme="dark"] .company-profile-page .pill.gray,
.dark .company-profile-page .pill.gray {
    background: var(--profile-surface-alt) !important;
    color: var(--profile-muted) !important;
    border-color: var(--profile-border) !important;
}

@media (max-width: 1024px) {
    .company-profile-page .profile-stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .company-profile-page .profile-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .company-profile-page .profile-hero { flex-direction: column; align-items: flex-start; }
    .company-profile-page .profile-actions { width: 100%; }
    .company-profile-page .profile-actions .btn { flex: 1; }
    .company-profile-page .profile-stats-grid { grid-template-columns: 1fr; }
    .company-profile-page .form-grid { grid-template-columns: 1fr; }
}
</style>

<div class="company-profile-page">
    <div class="profile-card-container">
        <div class="profile-cover"></div>

        <div class="profile-hero">
            <div style="display: flex; flex-direction: column;">
                <div class="profile-avatar-container">
                    <div class="profile-avatar-wrap">
                        <?php if ($hasProfileImage): ?>
                            <img src="<?= e($profileImage) ?>?v=<?= (int) filemtime($profileImagePath) ?>" alt="Company profile picture">
                        <?php else: ?>
                            <div class="profile-avatar-fallback"><?= e($company['initials']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-summary">
                    <h1><?= e($company['representative']) ?></h1>
                    <p class="muted"><?= e($company['name']) ?> &middot; <?= e($companyRow['rep_position'] ?? $company['role']) ?></p>
                    <div class="profile-tags">
                        <span class="pill blue"><i class="fa-solid fa-building"></i> Company Partner</span>
                        <span class="pill gray"><i class="fa-solid fa-envelope"></i> <?= e($company['email']) ?></span>
                    </div>
                </div>
            </div>

            <div class="profile-actions">
                <a class="btn" href="company_dashboard.php"><i class="fa-solid fa-arrow-left"></i> Back to dashboard</a>
                <button type="button" class="btn primary" id="openPhotoModalBtn">
                    <i class="fa-solid fa-camera"></i> Edit photo
                </button>
            </div>
        </div>
    </div>

    <!-- AESTHETIC INTERACTIVE PHOTO MODAL -->
    <div class="modal-overlay" id="photoModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Update profile photo</h3>
                <button type="button" class="modal-close" id="closePhotoModalBtn"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form method="post" action="company_profile.php" enctype="multipart/form-data" id="modalPhotoForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="upload_profile_image">
                <input id="modalPhotoInput" type="file" name="profile_picture" class="file-input" accept="image/jpeg,image/png,image/webp">

                <div class="modal-body">
                    <div class="modal-preview-wrap" id="modalPreviewWrap">
                        <?php if ($hasProfileImage): ?>
                            <img src="<?= e($profileImage) ?>?v=<?= (int) filemtime($profileImagePath) ?>" alt="Preview" id="modalPreviewImg">
                        <?php else: ?>
                            <span class="modal-preview-fallback" id="modalPreviewFallback"><?= e($company['initials']) ?></span>
                        <?php endif; ?>
                    </div>

                    <p id="modalPhotoStatus">Choose a clear JPG, PNG, or WEBP image up to 2MB.</p>

                    <div class="modal-dropzone" id="modalDropzone">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>Click to choose photo</span>
                        <small>Supports JPG, PNG, WEBP (Max 2MB)</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <?php if ($hasProfileImage): ?>
                        <button type="submit" form="deletePhotoForm" class="danger-btn"><i class="fa-solid fa-trash"></i> Remove photo</button>
                    <?php endif; ?>
                    <button type="button" class="btn" id="cancelPhotoModalBtn">Cancel</button>
                    <button type="submit" class="btn primary" id="savePhotoModalBtn"><i class="fa-solid fa-floppy-disk"></i> Save photo</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($hasProfileImage): ?>
    <form id="deletePhotoForm" method="post" action="company_profile.php" style="display: none;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete_profile_image">
    </form>
    <?php endif; ?>

    <!-- 4 Summary Status Cards -->
    <div class="profile-stats-grid">
        <div class="stat-card">
            <div class="label">Account type</div>
            <div class="value">Company Partner</div>
        </div>
        <div class="stat-card">
            <div class="label">Email address</div>
            <div class="value" style="word-break: break-all;"><?= e($company['email']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Department</div>
            <div class="value"><?= e($companyRow['rep_position'] ?? 'IT Department') ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Account status</div>
            <div class="value" style="color: #16a34a;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #16a34a; display: inline-block;"></span> Active session
            </div>
        </div>
    </div>

    <!-- 2-Column Grid: Profile Information & Change Password -->
    <div class="profile-grid">
        <!-- Personal & Company Details Form -->
        <section class="profile-card">
            <div class="panel-head">
                <span class="eyebrow">Personal Details</span>
                <h2>Account information</h2>
            </div>

            <form method="post" action="company_profile.php" class="profile-form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="field">
                    <label for="company_name">Company name</label>
                    <input id="company_name" name="company_name" type="text" value="<?= e($companyRow['company_name'] ?? '') ?>" maxlength="150" required>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="rep_first_name">Representative first name</label>
                        <input id="rep_first_name" name="rep_first_name" type="text" value="<?= e($companyRow['rep_first_name'] ?? '') ?>" maxlength="50" required>
                    </div>
                    <div class="field">
                        <label for="rep_last_name">Representative last name</label>
                        <input id="rep_last_name" name="rep_last_name" type="text" value="<?= e($companyRow['rep_last_name'] ?? '') ?>" maxlength="50" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="rep_middle_name">Middle name</label>
                        <input id="rep_middle_name" name="rep_middle_name" type="text" value="<?= e($companyRow['rep_middle_name'] ?? '') ?>" maxlength="50">
                    </div>
                    <div class="field">
                        <label for="rep_position">Position</label>
                        <input id="rep_position" name="rep_position" type="text" value="<?= e($companyRow['rep_position'] ?? '') ?>" maxlength="100" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="contact_number">Contact number</label>
                        <input id="contact_number" name="contact_number" type="tel" value="<?= e($companyRow['contact_number'] ?? '') ?>" maxlength="20" required>
                    </div>
                    <div class="field">
                        <label for="representative_email">Representative email</label>
                        <input id="representative_email" name="representative_email" type="email" value="<?= e($companyRow['representative_email'] ?? '') ?>" maxlength="100" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn primary"><i class="fa-solid fa-floppy-disk"></i> Save changes</button>
                </div>
            </form>
        </section>

        <!-- Security & Change Password -->
        <section class="profile-card">
            <div class="panel-head">
                <span class="eyebrow">Security</span>
                <h2>Change password</h2>
            </div>

            <form method="post" action="company_profile.php" class="password-form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="field">
                    <label for="current_password">Current password</label>
                    <div class="password-wrap">
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" data-toggle-password="current_password" aria-label="Toggle visibility"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>

                <div class="field">
                    <label for="new_password">New password</label>
                    <div class="password-wrap">
                        <input id="new_password" name="new_password" type="password" autocomplete="new-password" required>
                        <button type="button" class="password-toggle" data-toggle-password="new_password" aria-label="Toggle visibility"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>

                <div class="field">
                    <label for="confirm_password">Confirm new password</label>
                    <div class="password-wrap">
                        <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
                        <button type="button" class="password-toggle" data-toggle-password="confirm_password" aria-label="Toggle visibility"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>

                <div class="form-actions" style="margin-top: 38px;">
                    <button type="submit" class="btn primary"><i class="fa-solid fa-lock"></i> Update password</button>
                </div>
            </form>
        </section>
    </div>
</div>

<script>
(function () {
    'use strict';

    const openPhotoModalBtn = document.getElementById('openPhotoModalBtn');
    const closePhotoModalBtn = document.getElementById('closePhotoModalBtn');
    const cancelPhotoModalBtn = document.getElementById('cancelPhotoModalBtn');
    const photoModal = document.getElementById('photoModal');
    const modalDropzone = document.getElementById('modalDropzone');
    const modalPhotoInput = document.getElementById('modalPhotoInput');
    const modalPreviewWrap = document.getElementById('modalPreviewWrap');
    const modalPhotoStatus = document.getElementById('modalPhotoStatus');

    if (openPhotoModalBtn && photoModal) {
        openPhotoModalBtn.addEventListener('click', function () {
            photoModal.classList.add('show');
        });
    }

    function closeModal() {
        if (photoModal) photoModal.classList.remove('show');
    }

    if (closePhotoModalBtn) closePhotoModalBtn.addEventListener('click', closeModal);
    if (cancelPhotoModalBtn) cancelPhotoModalBtn.addEventListener('click', closeModal);
    if (photoModal) {
        photoModal.addEventListener('click', function (e) {
            if (e.target === photoModal) closeModal();
        });
    }

    if (modalDropzone && modalPhotoInput) {
        modalDropzone.addEventListener('click', function () {
            modalPhotoInput.click();
        });
    }

    if (modalPhotoInput) {
        modalPhotoInput.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file || !file.type.startsWith('image/')) return;

            if (modalPhotoStatus) {
                modalPhotoStatus.textContent = "Selected: " + file.name;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                const result = e.target && e.target.result;
                if (typeof result === 'string') {
                    modalPreviewWrap.innerHTML = '<img src="' + result + '" alt="New Preview">';
                }
            };
            reader.readAsDataURL(file);
        });
    }

    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.getElementById(this.getAttribute('data-toggle-password'));
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye', !isPassword);
                icon.classList.toggle('fa-eye-slash', isPassword);
            }
        });
    });
})();
</script>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>