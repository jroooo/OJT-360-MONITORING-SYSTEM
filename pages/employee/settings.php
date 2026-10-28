<?php /* Employee dashboard page fragment; included by dashboard(employee).php. */ ?>
<!-- =================================================

                 SETTINGS

            ================================================== -->

            <section class="page" id="page-settings">

                <div class="page-heading">

                    <div>

                        <div class="section-kicker">Preferences</div>

                        <h1>Settings<span class="settings-title-dot">.</span></h1>

                        <p>Personalize the portal, set your work schedule, and manage your account.</p>

                    </div>

                </div>

 

                <div class="settings-grid">

                    <section class="section-card settings-card">

                        <div class="settings-card-heading">

                            <div class="section-kicker">Appearance</div>

                            <h2 class="section-title">Theme</h2>

                        </div>

 

                        <div class="settings-themes" role="group" aria-label="Color theme">

                            <button class="settings-theme-card" type="button" data-settings-theme="light" aria-pressed="false">

                                <span class="settings-theme-preview light"><i></i><i></i><i></i></span>

                                <strong>Γÿ╝ Light</strong>

                                <small>Bright and clean</small>

                            </button>

                            <button class="settings-theme-card" type="button" data-settings-theme="dark" aria-pressed="false">

                                <span class="settings-theme-preview dark"><i></i><i></i><i></i></span>

                                <strong>Γÿ╛ Dark</strong>

                                <small>Easy on the eyes</small>

                            </button>

                            <button class="settings-theme-card" type="button" data-settings-theme="system" aria-pressed="false">

                                <span class="settings-theme-preview system"><i></i><i></i><i></i></span>

                                <strong>Γûú System</strong>

                                <small>Match my device</small>

                            </button>

                        </div>

 

                        <div class="settings-preference-row">

                            <span class="settings-preference-icon">Γÿ░</span>

                            <span class="settings-preference-copy">

                                <strong>Keep sidebar open</strong>

                                <small>Turn off to use the compact icon-only sidebar on desktop.</small>

                            </span>

                            <label class="settings-switch">

                                <input type="checkbox" id="settingsSidebarToggle">

                                <span></span>

                            </label>

                        </div>

                        <div class="settings-preference-row">

                            <span class="settings-preference-icon">╧ƒ</span>

                            <span class="settings-preference-copy">

                                <strong>Reduce motion</strong>

                                <small>Calms animations such as transitions and page scrolling.</small>

                            </span>

                            <label class="settings-switch">

                                <input type="checkbox" id="settingsMotionToggle">

                                <span></span>

                            </label>

                        </div>

                    </section>

 

                    <section class="section-card settings-card">

                        <div class="settings-card-heading">

                            <div class="section-kicker">Work Schedule</div>

                            <h2 class="section-title">Attendance &amp; hours goal</h2>

                        </div>

                        <p class="settings-help-text">Your shift start decides whether a Time In counts as on time on the dashboard.</p>

 

                        <form id="settingsScheduleForm" class="settings-form">

                            <div class="settings-fields three">

                                <label>Shift starts

                                    <input class="field" type="time" name="shift_start" value="08:00" required>

                                </label>

                                <label>Grace period (min)

                                    <input class="field" type="number" name="grace_minutes" min="0" max="120" value="15" required>

                                </label>

                                <label>Daily target (hours)

                                    <input class="field" type="number" name="daily_target" min="1" max="16" step="0.5" value="8" required>

                                </label>

                            </div>

                            <div class="settings-actions">

                                <span class="settings-local-note">Saved on this device</span>

                                <button class="btn btn-primary" type="submit">Save schedule</button>

                            </div>

                        </form>

 

                        <hr class="settings-divider">

 

                        <form id="settingsGoalForm" class="settings-form">

                            <label>Required OJT hours

                                <input class="field" type="number" name="required_hours" min="1" max="2000" step="1" value="480" required>

                                <small class="settings-field-help">Defined by your school. Used for your progress ring and goal bar.</small>

                            </label>

                            <div class="settings-actions">

                                <span class="settings-local-note">Saved on this device</span>

                                <button class="btn btn-primary" type="submit">Update goal</button>

                            </div>

                        </form>

                    </section>

 

                    <section class="section-card settings-card">

                        <div class="settings-card-heading">

                            <div class="section-kicker">Security</div>

                            <h2 class="section-title">Change password</h2>

                        </div>

                        <form class="settings-form" method="POST" action="dashboard(employee).php?section=settings">

                            <input type="hidden" name="settings_action" value="change_password">

                            <input type="hidden" name="settings_csrf" value="<?= $settingsCsrf ?>">

                            <label>Current password

                                <input class="field" type="password" name="current_password" autocomplete="current-password" required>

                            </label>

                            <div class="settings-fields two">

                                <label>New password

                                    <input class="field" type="password" name="new_password" minlength="8" autocomplete="new-password" required>

                                </label>

                                <label>Confirm new password

                                    <input class="field" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>

                                </label>

                            </div>

                            <div class="settings-actions">

                                <span class="settings-local-note">Use at least 8 characters</span>

                                <button class="btn btn-primary" type="submit">Update password</button>

                            </div>

                        </form>

                    </section>

 

                    <section class="section-card settings-card">

                        <div class="settings-card-heading">

                            <div class="section-kicker">Account</div>

                            <h2 class="section-title">Quick links</h2>

                        </div>

                        <div class="settings-quick-links">

                            <button type="button" data-settings-page="interns"><span>ΓÖÖ</span> Intern assignments <b>ΓÇ║</b></button>

                            <button type="button" data-settings-page="evaluations"><span>Γûñ</span> Evaluations <b>ΓÇ║</b></button>

                            <button type="button" data-settings-page="companies"><span>≡ƒÅó</span> Companies &amp; Placements <b>ΓÇ║</b></button>

                            <button type="button" data-settings-page="support"><span>?</span> Help &amp; support <b>ΓÇ║</b></button>

                        </div>

                        <div class="settings-account-summary">

                            <div class="avatar"><?= $displayInitials ?></div>

                            <div><strong><?= $displayName ?></strong><small><?= $displayEmail ?></small></div>

                            <button class="small-btn" type="button" id="settingsProfileButton">Edit profile</button>

                        </div>

                    </section>

                </div>

            </section>

 

        </div>

    </main>

</div>

 

<!-- =============================================================

     USER PROFILE MODAL

============================================================== -->

<div class="modal-backdrop" id="profileModal">

    <div class="modal profile-modal">

        <div class="profile-cover">

            <div class="profile-avatar-large" id="profileAvatarLarge">DE</div>

        </div>

 

        <div class="profile-header-info">

            <h2 id="profileDisplayName">Dr. Elena Santos</h2>

            <p id="profileDisplayRole">Employee ΓÇó OJT360</p>

        </div>

 

        <form id="profileForm">

            <div class="profile-section">

                <div class="profile-section-title">Personal information</div>

 

                <div class="form-grid">

                    <div class="form-group">

                        <label class="form-label">Full name</label>

                        <input class="field" id="profileName" name="profileName" value="<?= $displayName ?>" required>

                    </div>

 

                    <div class="form-group">

                        <label class="form-label">Role</label>

                        <input class="field" id="profileRole" name="profileRole" value="Employee" required>

                    </div>

 

                    <div class="form-group">

                        <label class="form-label">Email</label>

                        <input class="field" id="profileEmail" name="profileEmail" type="email" value="<?= $displayEmail ?>" required>

                    </div>

 

                    <div class="form-group">

                        <label class="form-label">Contact number</label>

                        <input class="field" id="profileContact" name="profileContact" value="<?= $displayContact ?>">

                    </div>

 

                    <div class="form-group full">

                        <label class="form-label">Department / Office</label>

                        <input class="field" id="profileDepartment" name="profileDepartment" value="<?= $displayDepartment ?>">

                    </div>

                </div>

            </div>

 

            <div class="modal-foot">

                <button type="button" class="btn btn-light" data-close="profileModal">Cancel</button>

                <button class="btn btn-primary" type="submit">Save Changes</button>

            </div>

        </form>

    </div>

</div>

 

