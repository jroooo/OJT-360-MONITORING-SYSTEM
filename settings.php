<?php
require_once __DIR__ . '/includes/bootstrap.php';

$company = require_company();
$cid = $company['id'];
$pdo = db();

if (is_post()) {
    require_csrf('settings.php');

    $action = $_POST['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($action === 'update_profile') {
        $v = [];
        foreach ([
            'company_name',
            'rep_first_name',
            'rep_middle_name',
            'rep_last_name',
            'rep_position',
            'contact_number',
            'representative_email'
        ] as $k) {
            $v[$k] = trim((string)($_POST[$k] ?? ''));
        }

        $em = strtolower($v['representative_email']);
        $profErr = '';

        if ($v['company_name'] === '' || mb_strlen($v['company_name']) < 2 || mb_strlen($v['company_name']) > 150) {
            $profErr = 'Company name must be 2 to 150 characters.';
        } elseif (!name_ok($v['rep_first_name']) || !name_ok($v['rep_last_name']) || ($v['rep_middle_name'] !== '' && !name_ok($v['rep_middle_name']))) {
            $profErr = 'Names may contain letters, spaces, apostrophes, periods and hyphens only (max 50 characters).';
        } elseif ($v['rep_position'] === '' || mb_strlen($v['rep_position']) > 100) {
            $profErr = 'Position is required (max 100 characters).';
        } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $v['contact_number'])) {
            $profErr = 'Enter a valid contact number.';
        } elseif (!filter_var($em, FILTER_VALIDATE_EMAIL) || strlen($em) > 100) {
            $profErr = 'Enter a valid representative email address.';
        } else {
            $d = $pdo->prepare('SELECT id FROM companies WHERE representative_email = ? AND id <> ? LIMIT 1');
            $d->execute([$em, $cid]);

            if ($d->fetch()) {
                $profErr = 'That representative email is already used by another account.';
            } else {
                $pdo->prepare(
                    'UPDATE companies
                     SET company_name = ?, rep_first_name = ?, rep_middle_name = ?, rep_last_name = ?, rep_position = ?, contact_number = ?, representative_email = ?
                     WHERE id = ?'
                )->execute([
                    $v['company_name'],
                    $v['rep_first_name'],
                    $v['rep_middle_name'] !== '' ? $v['rep_middle_name'] : null,
                    $v['rep_last_name'],
                    $v['rep_position'],
                    $v['contact_number'],
                    $em,
                    $cid
                ]);
            }
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => empty($profErr), 'message' => $profErr ?: 'Profile updated successfully!']);
            exit;
        }

        if (empty($profErr)) {
            flash_set('success', 'Profile updated.');
            redirect('settings.php');
        }
    } elseif ($action === 'change_password') {
        $cur = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $con = (string)($_POST['confirm_password'] ?? '');
        $hash = (string)($company['row']['password_hash'] ?? '');
        $pwErr = '';

        if (!password_verify($cur, $hash)) {
            $pwErr = 'Your current password is incorrect.';
        } elseif ($new !== $con) {
            $pwErr = 'New passwords do not match.';
        } elseif (($pe = validate_password_policy($new)) !== null) {
            $pwErr = $pe;
        } elseif ($new === $cur) {
            $pwErr = 'Choose a new password that is different from the current one.';
        } else {
            $pdo->prepare('UPDATE companies SET password_hash = ? WHERE id = ?')->execute([
                password_hash($new, PASSWORD_DEFAULT),
                $cid
            ]);
            session_regenerate_id(true);
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => empty($pwErr), 'message' => $pwErr ?: 'Password changed successfully!']);
            exit;
        }

        if (empty($pwErr)) {
            flash_set('success', 'Password changed successfully.');
            redirect('settings.php');
        }
    }
}

$r = $company['row'];
$val = fn($k) => $r[$k] ?? '';

$pageKey = 'settings';
$pageTitle = 'Settings';
$eyebrow = 'PREFERENCES';

require __DIR__ . '/includes/layout_top.php';
?>

<style>
/* =========================================
    OJT360 INTERACTIVE SETTINGS UI
========================================= */
.company-settings {
    --settings-border: #e2e8f0;
    --settings-muted: #64748b;
    --settings-text: #0f172a;
    --settings-primary: #0284c7;
    --settings-primary-light: #e0f2fe;
    padding-bottom: 28px;
}
.company-settings *, .company-settings *::before, .company-settings *::after { box-sizing: border-box; }
.company-settings .settings-intro { margin-bottom: 25px; }
.company-settings .settings-intro .sub { max-width: 650px; line-height: 1.7; color: var(--settings-muted); }

.company-settings .settings-layout {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    align-items: start;
}
.company-settings .settings-column { display: grid; gap: 20px; min-width: 0; }

.company-settings .settings-card {
    min-width: 0; overflow: hidden; padding: 0;
    border: 1px solid var(--settings-border);
    border-radius: 16px; background: #ffffff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.035);
    transition: box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s;
}
.company-settings .settings-card:hover { border-color: #cbd5e1; box-shadow: 0 5px 16px rgba(15, 23, 42, 0.045); }
.company-settings .settings-card-body { padding: 24px; }

.company-settings .field { min-width: 0; margin-bottom: 16px; }
.company-settings .field:last-child { margin-bottom: 0; }
.company-settings .field label { display: block; margin-bottom: 7px; color: #334155; font-size: 13px; font-weight: 650; }
.company-settings .field input {
    display: block; width: 100%; min-width: 0; min-height: 44px; padding: 10px 14px;
    border: 1px solid #cbd5e1; border-radius: 10px; outline: none; background: #ffffff;
    color: #0f172a; font: inherit; font-size: 13px; transition: border-color 0.18s ease, box-shadow 0.18s ease;
}
.company-settings .field input:hover { border-color: #94a3b8; }
.company-settings .field input:focus { border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12); }
.company-settings .field input[readonly] { background: #f8fafc; border-color: #e2e8f0; color: #64748b; cursor: not-allowed; }

.company-settings .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px; }

.company-settings .password-wrap { position: relative; }
.company-settings .password-wrap input { padding-right: 45px; }
.company-settings .password-toggle {
    position: absolute; top: 50%; right: 10px; display: flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; padding: 0; transform: translateY(-50%); border: 0; border-radius: 8px;
    background: transparent; color: #64748b; cursor: pointer;
}
.company-settings .password-toggle:hover { background: #f1f5f9; color: #0f172a; }

.company-settings .settings-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 20px; }
.company-settings .settings-submit {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 40px; padding: 10px 20px;
    border: 0; border-radius: 10px; background: #172554; color: #ffffff; font: inherit; font-size: 13px; font-weight: 700;
    cursor: pointer; box-shadow: 0 2px 4px rgba(2, 132, 199, 0.2); transition: background 0.18s ease;
}
.company-settings .settings-submit:hover { background: #1e40af; }

.company-settings .settings-feedback {
    display: none; align-items: flex-start; gap: 9px; margin-bottom: 16px; padding: 12px 14px;
    border-radius: 10px; font-size: 12px; line-height: 1.6;
}
.company-settings .settings-feedback.error { display: flex; border: 1px solid #fecaca; background: #fff5f5; color: #b91c1c; }
.company-settings .settings-feedback.success { display: flex; border: 1px solid #bbf7d0; background: #f0fdf4; color: #15803d; }

.company-settings .section-label { display: block; margin-bottom: 8px; color: #0284c7; font-size: 11px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
.company-settings .section-title { margin: 0 0 4px; color: var(--settings-text); font-size: 16px; font-weight: 750; }
.company-settings .section-desc { margin: 0 0 16px; color: var(--settings-muted); font-size: 12px; }

.company-settings .theme-options { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-bottom: 20px; }
.company-settings .theme-option {
    padding: 10px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff; text-align: left; cursor: pointer; transition: all 0.18s ease;
}
.company-settings .theme-option:hover { border-color: #94a3b8; }
.company-settings .theme-option[aria-pressed="true"] { border-color: #0284c7; background: #f0f9ff; box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15); }

.company-settings .theme-preview { display: grid; grid-template-columns: 28% 1fr; gap: 4px; height: 55px; padding: 5px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; }
.company-settings .theme-preview.dark { background: #0f172a; border-color: #334155; }
.company-settings .preview-sidebar { border-radius: 3px; background: #e2e8f0; }
.company-settings .theme-preview.dark .preview-sidebar { background: #334155; }
.company-settings .preview-content { display: grid; grid-template-rows: 10px 1fr; gap: 4px; }
.company-settings .preview-topbar { border-radius: 2px; background: #ffffff; }
.company-settings .theme-preview.dark .preview-topbar { background: #1e293b; }
.company-settings .preview-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 3px; }
.company-settings .preview-panels span { border-radius: 2px; background: #ffffff; border: 1px solid #e2e8f0; }
.company-settings .theme-preview.dark .preview-panels span { background: #1e293b; border-color: #334155; }

.company-settings .theme-name { display: flex; align-items: center; gap: 6px; margin-top: 8px; color: #1e293b; font-size: 12px; font-weight: 700; }
.company-settings .theme-name i { color: #0284c7; font-size: 11px; }
.company-settings .theme-description { display: block; margin-top: 2px; color: #64748b; font-size: 10px; }

.company-settings .setting-toggle-row { display: flex; align-items: center; justify-content: space-between; gap: 15px; padding-top: 15px; border-top: 1px solid #f1f5f9; margin-top: 15px; }
.company-settings .setting-toggle-copy strong { display: block; color: #1e293b; font-size: 13px; font-weight: 650; }
.company-settings .setting-toggle-copy span { display: block; margin-top: 2px; color: #64748b; font-size: 11px; }

.company-settings .switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
.company-settings .switch input { opacity: 0; width: 0; height: 0; }
.company-settings .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .2s; border-radius: 24px; }
.company-settings .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .2s; border-radius: 50%; }
.company-settings .switch input:checked + .slider { background-color: #0284c7; }
.company-settings .switch input:checked + .slider:before { transform: translateX(20px); }

.company-settings .settings-links { display: grid; }
.company-settings .settings-link { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f1f5f9; color: #334155; text-decoration: none; transition: color 0.18s ease; }
.company-settings .settings-link:last-child { border-bottom: none; padding-bottom: 0; }
.company-settings .settings-link:first-child { padding-top: 0; }
.company-settings .settings-link:hover { color: #0284c7; }
.company-settings .settings-link-icon { display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; background: #f1f5f9; color: #475569; font-size: 13px; }
.company-settings .settings-link:hover .settings-link-icon { background: #e0f2fe; color: #0284c7; }
.company-settings .settings-link-copy { flex: 1; min-width: 0; }
.company-settings .settings-link-copy strong { display: block; color: inherit; font-size: 12px; font-weight: 700; }
.company-settings .settings-link-copy span { display: block; margin-top: 2px; color: #64748b; font-size: 11px; }
.company-settings .settings-link-arrow { color: #94a3b8; font-size: 11px; }

/* DARK MODE ADAPTATION */
html[data-theme="dark"] .company-settings .title, .dark .company-settings .title,
html[data-theme="dark"] .company-settings .section-title, .dark .company-settings .section-title,
html[data-theme="dark"] .company-settings .field label, .dark .company-settings .field label,
html[data-theme="dark"] .company-settings .setting-toggle-copy strong, .dark .company-settings .setting-toggle-copy strong,
html[data-theme="dark"] .company-settings .settings-link-copy strong, .dark .company-settings .settings-link-copy strong,
html[data-theme="dark"] .company-settings .theme-name, .dark .company-settings .theme-name { color: #f8fafc !important; }
html[data-theme="dark"] .company-settings .sub, .dark .company-settings .sub,
html[data-theme="dark"] .company-settings .section-desc, .dark .company-settings .section-desc,
html[data-theme="dark"] .company-settings .field small, .dark .company-settings .field small,
html[data-theme="dark"] .company-settings .setting-toggle-copy span, .dark .company-settings .setting-toggle-copy span,
html[data-theme="dark"] .company-settings .settings-link-copy span, .dark .company-settings .settings-link-copy span,
html[data-theme="dark"] .company-settings .theme-description, .dark .company-settings .theme-description { color: #94a3b8 !important; }
html[data-theme="dark"] .company-settings .settings-card, .dark .company-settings .settings-card {
    background: #111c38 !important;
    border-color: #1e293b !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
}
html[data-theme="dark"] .company-settings .field input, .dark .company-settings .field input {
    background: #16223f !important;
    border-color: #233354 !important;
    color: #f8fafc !important;
}
html[data-theme="dark"] .company-settings .field input[readonly], .dark .company-settings .field input[readonly] {
    background: #0b1329 !important;
    border-color: #1e293b !important;
    color: #64748b !important;
}
html[data-theme="dark"] .company-settings .theme-option, .dark .company-settings .theme-option {
    background: #16223f !important;
    border-color: #233354 !important;
}
html[data-theme="dark"] .company-settings .theme-option[aria-pressed="true"], .dark .company-settings .theme-option[aria-pressed="true"] {
    background: #1e293b !important;
    border-color: #0284c7 !important;
}
html[data-theme="dark"] .company-settings .settings-link, .dark .company-settings .settings-link {
    border-bottom-color: #1e293b !important;
    color: #cbd5e1 !important;
}
html[data-theme="dark"] .company-settings .settings-link:hover, .dark .company-settings .settings-link:hover {
    background: #16223f !important;
}
html[data-theme="dark"] .company-settings .setting-toggle-row, .dark .company-settings .setting-toggle-row {
    border-top-color: #1e293b !important;
}

@media (max-width: 1000px) { .company-settings .settings-layout { grid-template-columns: minmax(0, 1fr); } }
@media (max-width: 640px) { .company-settings .theme-options, .company-settings .form-grid { grid-template-columns: minmax(0, 1fr); } }
</style>

<div class="company-settings">
    <div class="page-head settings-intro">
        <div>
            <div class="eyebrow"><?= e($eyebrow) ?></div>
            <h1 class="title">Settings<span class="dotc">.</span></h1>
            <p class="sub">Personalize the portal, set your company profile, and manage your account security dynamically.</p>
        </div>
    </div>

    <div class="settings-layout">
        <!-- COLUMN 1 -->
        <div class="settings-column">
            <!-- APPEARANCE & PREFERENCES -->
            <section class="card settings-card" id="appearance">
                <div class="settings-card-body">
                    <span class="section-label">Appearance</span>
                    <h2 class="section-title">Theme</h2>
                    <p class="section-desc">Choose the look that works best for your device.</p>

                    <div class="theme-options" id="companyThemeOptions" role="group" aria-label="Choose appearance theme">
                        <button class="theme-option" type="button" data-theme-set="light" aria-pressed="false">
                            <span class="theme-preview light" aria-hidden="true"><span class="preview-sidebar"></span><span class="preview-content"><span class="preview-topbar"></span><span class="preview-panels"><span></span><span></span></span></span></span>
                            <span class="theme-name"><i class="fa-solid fa-sun"></i>Light</span>
                            <span class="theme-description">Bright and clean</span>
                        </button>
                        <button class="theme-option" type="button" data-theme-set="dark" aria-pressed="false">
                            <span class="theme-preview dark" aria-hidden="true"><span class="preview-sidebar"></span><span class="preview-content"><span class="preview-topbar"></span><span class="preview-panels"><span></span><span></span></span></span></span>
                            <span class="theme-name"><i class="fa-solid fa-moon"></i>Dark</span>
                            <span class="theme-description">Easy on the eyes</span>
                        </button>
                        <button class="theme-option" type="button" data-theme-set="system" aria-pressed="false">
                            <span class="theme-preview light" aria-hidden="true"><span class="preview-sidebar"></span><span class="preview-content"><span class="preview-topbar"></span><span class="preview-panels"><span></span><span></span></span></span></span>
                            <span class="theme-name"><i class="fa-solid fa-desktop"></i>System</span>
                            <span class="theme-description">Match my device</span>
                        </button>
                    </div>

                    <div class="setting-toggle-row">
                        <div class="setting-toggle-copy">
                            <strong>Keep sidebar open</strong>
                            <span>Turn off to use the compact icon-only sidebar on desktop.</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="sidebarToggle" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </section>

            <!-- CHANGE PASSWORD -->
            <section class="card settings-card" id="security">
                <div class="settings-card-body">
                    <span class="section-label">Security</span>
                    <h2 class="section-title">Change password</h2>
                    <p class="section-desc">Keep your account protected with a secure password.</p>

                    <div id="passwordFeedback" class="settings-feedback">
                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                        <span class="msg"></span>
                    </div>

                    <form id="passwordForm" method="post" action="settings.php" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="field">
                            <label for="p_cur">Current password</label>
                            <div class="password-wrap">
                                <input id="p_cur" name="current_password" type="password" required autocomplete="current-password">
                                <button class="password-toggle" type="button" data-password-toggle="p_cur" aria-label="Toggle password visibility"><i class="fa-regular fa-eye"></i></button>
                            </div>
                        </div>

                        <div class="form-grid" style="margin-bottom: 16px;">
                            <div class="field" style="margin-bottom:0;">
                                <label for="p_new">New password</label>
                                <div class="password-wrap">
                                    <input id="p_new" name="new_password" type="password" required autocomplete="new-password">
                                    <button class="password-toggle" type="button" data-password-toggle="p_new" aria-label="Toggle password visibility"><i class="fa-regular fa-eye"></i></button>
                                </div>
                            </div>
                            <div class="field" style="margin-bottom:0;">
                                <label for="p_con">Confirm password</label>
                                <div class="password-wrap">
                                    <input id="p_con" name="confirm_password" type="password" required autocomplete="new-password">
                                    <button class="password-toggle" type="button" data-password-toggle="p_con" aria-label="Toggle password visibility"><i class="fa-regular fa-eye"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="settings-actions" style="margin-top:15px;">
                            <button class="settings-submit" type="submit">
                                <i class="fa-solid fa-lock" aria-hidden="true"></i> Update password
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <!-- COLUMN 2 -->
        <div class="settings-column">
            <!-- COMPANY PROFILE -->
            <section class="card settings-card" id="company-profile">
                <div class="settings-card-body">
                    <span class="section-label">Account</span>
                    <h2 class="section-title">Company profile</h2>
                    <p class="section-desc">Update your organization and representative details.</p>

                    <div id="profileFeedback" class="settings-feedback">
                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                        <span class="msg"></span>
                    </div>

                    <form id="profileForm" method="post" action="settings.php" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="field">
                            <label for="s_user">Login username</label>
                            <input id="s_user" type="text" value="<?= e($r['company_username']) ?>" readonly>
                        </div>

                        <div class="field">
                            <label for="s_cn">Company name</label>
                            <input id="s_cn" name="company_name" type="text" maxlength="150" required value="<?= e($val('company_name')) ?>">
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label for="s_fn">First name</label>
                                <input id="s_fn" name="rep_first_name" type="text" maxlength="50" required value="<?= e($val('rep_first_name')) ?>">
                            </div>
                            <div class="field">
                                <label for="s_ln">Last name</label>
                                <input id="s_ln" name="rep_last_name" type="text" maxlength="50" required value="<?= e($val('rep_last_name')) ?>">
                            </div>
                        </div>

                        <div class="field" style="margin-top: 16px;">
                            <label for="s_pos">Position</label>
                            <input id="s_pos" name="rep_position" type="text" maxlength="100" required value="<?= e($val('rep_position')) ?>">
                        </div>

                        <div class="form-grid" style="margin-top: 16px;">
                            <div class="field">
                                <label for="s_ct">Contact number</label>
                                <input id="s_ct" name="contact_number" type="tel" maxlength="20" required value="<?= e($val('contact_number')) ?>">
                            </div>
                            <div class="field">
                                <label for="s_em">Representative email</label>
                                <input id="s_em" name="representative_email" type="email" maxlength="100" required value="<?= e($val('representative_email')) ?>">
                            </div>
                        </div>

                        <div class="settings-actions">
                            <button class="settings-submit" type="submit">
                                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save profile
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- QUICK LINKS -->
            <section class="card settings-card">
                <div class="settings-card-body">
                    <span class="section-label">Navigation</span>
                    <h2 class="section-title">Quick links</h2>
                    <p class="section-desc">Jump directly to portal modules.</p>

                    <nav class="settings-links" aria-label="Settings quick links">
                        <a class="settings-link" href="assigned_students.php">
                            <span class="settings-link-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
                            <span class="settings-link-copy"><strong>Assigned Students</strong><span>Manage your interns.</span></span>
                            <i class="fa-solid fa-chevron-right settings-link-arrow" aria-hidden="true"></i>
                        </a>
                        <a class="settings-link" href="evaluations.php">
                            <span class="settings-link-icon"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i></span>
                            <span class="settings-link-copy"><strong>Evaluations</strong><span>Review intern performance.</span></span>
                            <i class="fa-solid fa-chevron-right settings-link-arrow" aria-hidden="true"></i>
                        </a>
                        <a class="settings-link" href="reports.php">
                            <span class="settings-link-icon"><i class="fa-solid fa-chart-column" aria-hidden="true"></i></span>
                            <span class="settings-link-copy"><strong>Reports &amp; Analytics</strong><span>View training reports.</span></span>
                            <i class="fa-solid fa-chevron-right settings-link-arrow" aria-hidden="true"></i>
                        </a>
                        <a class="settings-link" href="dynamic.php">
                            <span class="settings-link-icon"><i class="fa-solid fa-qrcode" aria-hidden="true"></i></span>
                            <span class="settings-link-copy"><strong>Dynamic QR Code</strong><span>Access attendance tools.</span></span>
                            <i class="fa-solid fa-chevron-right settings-link-arrow" aria-hidden="true"></i>
                        </a>
                        <a class="settings-link" href="help.php">
                            <span class="settings-link-icon"><i class="fa-solid fa-circle-question" aria-hidden="true"></i></span>
                            <span class="settings-link-copy"><strong>Help &amp; Support</strong><span>Find answers to questions.</span></span>
                            <i class="fa-solid fa-chevron-right settings-link-arrow" aria-hidden="true"></i>
                        </a>
                    </nav>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const themeButtons = Array.from(document.querySelectorAll('.company-settings [data-theme-set]'));
    function updateThemeSelection() {
        let currentTheme = 'light';
        try { const saved = localStorage.getItem('theme'); if (saved) currentTheme = saved; } catch (e) {}
        themeButtons.forEach(btn => btn.setAttribute('aria-pressed', String(btn.dataset.themeSet === currentTheme)));
    }
    themeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const theme = btn.dataset.themeSet;
            document.documentElement.dataset.theme = theme;
            try { localStorage.setItem('theme', theme); } catch (e) {}
            updateThemeSelection();
        });
    });
    updateThemeSelection();

    document.querySelectorAll('.company-settings [data-password-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;
            const showing = input.type === 'password';
            input.type = showing ? 'text' : 'password';
            button.querySelector('i').classList.toggle('fa-eye', !showing);
            button.querySelector('i').classList.toggle('fa-eye-slash', showing);
        });
    });

    function handleFormSubmission(formId, feedbackId) {
        const form = document.getElementById(formId);
        const feedback = document.getElementById(feedbackId);
        if (!form || !feedback) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(form);

            fetch('settings.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                feedback.className = 'settings-feedback ' + (data.success ? 'success' : 'error');
                feedback.querySelector('.msg').textContent = data.message;
                
                if (data.success && formId === 'passwordForm') {
                    form.reset();
                }
            })
            .catch(() => {
                feedback.className = 'settings-feedback error';
                feedback.querySelector('.msg').textContent = 'An unexpected error occurred. Please try again.';
            });
        });
    }

    handleFormSubmission('profileForm', 'profileFeedback');
    handleFormSubmission('passwordForm', 'passwordFeedback');
})();
</script>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>