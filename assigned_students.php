<?php

require_once __DIR__ . '/includes/bootstrap.php';

$company = require_company();
$pdo = db();

$companyId = (int)($company['id'] ?? 0);

$pageKey = 'students';
$pageTitle = 'Assigned Students';
$eyebrow = 'STUDENT MANAGEMENT';

/*
|--------------------------------------------------------------------------
| Assignment storage
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['company_student_assignments'])) {
    $_SESSION['company_student_assignments'] = [];
}

/*
|--------------------------------------------------------------------------
| Save assignment
|--------------------------------------------------------------------------
*/

if (is_post()) {

    require_csrf('assigned_students.php');

    $studentNo = trim((string)($_POST['student_id'] ?? ''));
    $department = trim((string)($_POST['department'] ?? ''));
    $building = trim((string)($_POST['building'] ?? ''));

    if (
        $studentNo === '' ||
        $department === '' ||
        $building === '' ||
        mb_strlen($department) > 100 ||
        mb_strlen($building) > 100
    ) {

        flash_set(
            'error',
            'Please select a student and complete the assignment fields.'
        );

        redirect('assigned_students.php');
    }

    $check = $pdo->prepare(
        'SELECT id
         FROM students
         WHERE student_id = ?
         LIMIT 1'
    );

    $check->execute([$studentNo]);

    $studentExists = $check->fetchColumn();

    if (!$studentExists) {

        flash_set(
            'error',
            'The selected student could not be found.'
        );

        redirect('assigned_students.php');
    }

    $assignmentKey = $companyId . ':' . $studentNo;

    $_SESSION['company_student_assignments'][$assignmentKey] = [
        'company_id' => $companyId,
        'student_id' => $studentNo,
        'department' => $department,
        'building' => $building,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    flash_set(
        'success',
        'Assignment saved successfully.'
    );

    redirect('assigned_students.php');
}

/*
|--------------------------------------------------------------------------
| Load students
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        id,
        first_name,
        middle_name,
        last_name,
        course,
        mobile_number,
        school_name,
        complete_address,
        student_id,
        institutional_email,
        created_at
     FROM students
     ORDER BY last_name, first_name'
);

$stmt->execute();

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Attach session assignments
|--------------------------------------------------------------------------
*/

$assignments = $_SESSION['company_student_assignments'] ?? [];

foreach ($students as &$student) {

    $studentNo = (string)($student['student_id'] ?? '');

    $assignmentKey = $companyId . ':' . $studentNo;

    $student['department'] = '';
    $student['building'] = '';

    if (isset($assignments[$assignmentKey])) {

        $student['department'] =
            (string)($assignments[$assignmentKey]['department'] ?? '');

        $student['building'] =
            (string)($assignments[$assignmentKey]['building'] ?? '');
    }
}

unset($student);

/*
|--------------------------------------------------------------------------
| Count assigned students
|--------------------------------------------------------------------------
*/

$assignedCount = 0;

foreach ($students as $student) {

    if (
        trim((string)($student['department'] ?? '')) !== '' ||
        trim((string)($student['building'] ?? '')) !== ''
    ) {
        $assignedCount++;
    }
}

include __DIR__ . '/includes/layout_top.php';

?>

<style>

/* ============================================================
   ASSIGNED STUDENTS PAGE - DUAL THEME SUPPORT
   ============================================================ */

.as-page {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
    font-family: "Segoe UI", Inter, "Helvetica Neue", Arial, sans-serif;
}

.as-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.as-header-left {
    min-width: 0;
}

.as-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: var(--qr-brand, #2563eb);
    margin-bottom: 8px;
    line-height: 1.4;
}

.as-eyebrow::before {
    content: "";
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--qr-brand, #2563eb);
    box-shadow: 0 0 10px rgba(37, 99, 235, 0.4);
}

.as-title {
    margin: 0;
    font-size: clamp(30px, 3.4vw, 42px);
    line-height: 1.12;
    letter-spacing: -0.04em;
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.as-muted {
    color: var(--qr-text-muted, #64748b);
    font-size: 14px;
    line-height: 1.65;
    margin-top: 10px;
}

.as-panel {
    background: var(--qr-surface, #ffffff);
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 20px;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
    overflow: hidden;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.as-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.as-stat {
    padding: 22px;
}

.as-stat strong {
    display: block;
    font-size: 32px;
    margin-top: 8px;
    color: var(--qr-text, #0f172a);
    font-weight: 800;
    letter-spacing: -0.03em;
}

.as-toolbar {
    padding: 20px 22px;
    border-bottom: 1px solid var(--qr-border, #e2e8f0);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    background: var(--qr-surface, #ffffff);
}

.as-search {
    padding: 11px 14px;
    border: 1px solid var(--qr-border-strong, #cbd5e1);
    border-radius: 12px;
    min-width: 260px;
    max-width: 100%;
    background: var(--qr-surface-alt, #f8fafc);
    outline: none;
    color: inherit;
    font-size: 13px;
}

.as-search:focus,
.as-field input:focus,
.as-field select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.as-primary {
    border: 0;
    border-radius: 13px;
    background: #2563eb;
    color: white;
    padding: 0 18px;
    height: 46px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.2);
    transition: transform 0.15s ease, background 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.as-primary:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.as-button {
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 10px;
    padding: 8px 14px;
    background: var(--qr-surface-alt, #f8fafc);
    font-weight: 700;
    font-size: 12px;
    cursor: pointer;
    color: var(--qr-text-soft, #334155);
    transition: background 0.15s ease;
}

.as-button:hover {
    background: var(--qr-surface-soft, #f1f5f9);
    border-color: var(--qr-border-strong, #cbd5e1);
}

.as-scroll {
    overflow: auto;
}

.as-table {
    width: 100%;
    min-width: 950px;
    border-collapse: collapse;
}

.as-table th {
    background: var(--qr-surface-alt, #f8fafc);
    color: var(--qr-text-subtle, #94a3b8);
    text-transform: uppercase;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-align: left;
    padding: 14px;
    border-bottom: 1px solid var(--qr-border, #e2e8f0);
}

.as-table td {
    padding: 15px 14px;
    border-top: 1px solid var(--qr-border, #e2e8f0);
    font-size: 13px;
    color: var(--qr-text-soft, #334155);
}

.as-table tbody tr:hover td {
    background: var(--qr-surface-alt, #f8fafc);
}

.as-person {
    display: flex;
    align-items: center;
    gap: 12px;
}

.as-avatar {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: var(--qr-surface-alt, #f8fafc);
    color: var(--qr-brand, #2563eb);
    display: grid;
    place-items: center;
    font-weight: 900;
    font-size: 13px;
    flex-shrink: 0;
    border: 1px solid var(--qr-border, #e2e8f0);
}

.as-pill {
    display: inline-flex;
    align-items: center;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.as-green {
    background: #16a34a;
    color: #ffffff;
}

.as-amber {
    background: #f59e0b;
    color: #ffffff;
}

.as-gray {
    background: var(--qr-surface-alt, #f1f5f9);
    color: var(--qr-text-muted, #64748b);
    border: 1px solid var(--qr-border, #e2e8f0);
}

.as-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.as-modal.open {
    display: flex;
}

.as-modal-card {
    background: var(--qr-surface, #fff);
    width: min(510px, 100%);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 25px 80px rgba(15, 23, 42, 0.25);
    border: 1px solid var(--qr-border, #e2e8f0);
    animation: asEnter 0.18s ease-out;
}

@keyframes asEnter {
    from {
        opacity: 0;
        transform: translateY(8px) scale(0.99);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.as-modal-body {
    padding: 24px;
    display: grid;
    gap: 16px;
    background: var(--qr-surface, #fff);
}

.as-field label {
    display: block;
    font-size: 12px;
    font-weight: 750;
    margin-bottom: 7px;
    color: var(--qr-text-soft, #334155);
}

.as-field input,
.as-field select {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--qr-border-strong, #cbd5e1);
    border-radius: 10px;
    background: var(--qr-surface-alt, #fff);
    outline: none;
    color: var(--qr-text, inherit);
    font-size: 13px;
}

.as-empty {
    padding: 60px 20px;
    text-align: center;
    color: var(--qr-text-muted, #64748b);
}

/* ============================================================
   DARK MODE OVERRIDES (DEEP NAVY)
   ============================================================ */
html[data-theme="dark"] .as-page,
.dark .as-page {
    --qr-bg: #0b0f19;
    --qr-surface: #111c38;
    --qr-surface-alt: #0d152c;
    --qr-surface-soft: #172342;
    --qr-border: #1e293b;
    --qr-border-strong: #2b3d58;
    --qr-text: #f8fafc;
    --qr-text-soft: #e2e8f0;
    --qr-text-muted: #cbd5e1;
    --qr-text-subtle: #94a3b8;
    --qr-brand: #60a5fa;
    --qr-brand-strong: #bfdbfe;
    --qr-shadow: 0 18px 42px rgba(2, 6, 23, 0.45);
}

html[data-theme="dark"] .as-title,
.dark .as-title,
html[data-theme="dark"] .as-stat strong,
.dark .as-stat strong,
html[data-theme="dark"] .as-modal-card strong,
.dark .as-modal-card strong {
    color: #ffffff !important;
}

html[data-theme="dark"] .as-muted,
.dark .as-muted {
    color: var(--qr-text-muted) !important;
}

html[data-theme="dark"] .as-panel,
.dark .as-panel {
    background: var(--qr-surface) !important;
    border-color: var(--qr-border) !important;
}

html[data-theme="dark"] .as-toolbar,
.dark .as-toolbar {
    background: var(--qr-surface) !important;
    border-bottom-color: var(--qr-border) !important;
}

html[data-theme="dark"] .as-search,
.dark .as-search {
    background: var(--qr-surface-alt) !important;
    border-color: var(--qr-border) !important;
    color: #ffffff !important;
}

html[data-theme="dark"] .as-table th,
.dark .as-table th {
    background: var(--qr-surface-alt) !important;
    color: var(--qr-text-subtle) !important;
    border-bottom: 1px solid var(--qr-border) !important;
}

html[data-theme="dark"] .as-table td,
.dark .as-table td {
    border-top-color: var(--qr-border) !important;
    color: var(--qr-text-soft) !important;
}

html[data-theme="dark"] .as-table tbody tr:hover td,
.dark .as-table tbody tr:hover td {
    background: var(--qr-surface-alt) !important;
}

html[data-theme="dark"] .as-button,
.dark .as-button {
    background: var(--qr-surface-alt) !important;
    border-color: var(--qr-border) !important;
    color: var(--qr-text-soft) !important;
}

html[data-theme="dark"] .as-button:hover,
.dark .as-button:hover {
    background: var(--qr-surface-soft) !important;
    border-color: var(--qr-border-strong) !important;
    color: #ffffff !important;
}

html[data-theme="dark"] .as-modal-card,
.dark .as-modal-card {
    background: var(--qr-surface) !important;
    border: 1px solid var(--qr-border) !important;
}

html[data-theme="dark"] .as-field label,
.dark .as-field label {
    color: var(--qr-text-muted) !important;
}

html[data-theme="dark"] .as-field input,
html[data-theme="dark"] .as-field select,
.dark .as-field input,
.dark .as-field select {
    background: var(--qr-surface-alt) !important;
    border-color: var(--qr-border) !important;
    color: #ffffff !important;
}

@media(max-width:600px) {
    .as-search {
        width: 100%;
        min-width: 0;
    }
    .as-header {
        align-items: start;
    }
    .as-primary {
        width: 100%;
    }
}

</style>

<div class="as-page">

    <div class="as-header">
        <div class="as-header-left">
            <div class="as-eyebrow">
                <?= e($eyebrow) ?>
            </div>
            <h1 class="as-title">
                Assigned Students
            </h1>
            <div class="as-muted">
                Manage workplace assignments and view registered student information.
            </div>
        </div>

        <button
            class="as-primary"
            type="button"
            onclick="openAssignment()"
        >
            + Edit assignment
        </button>
    </div>

    <!-- Statistics -->
    <div class="as-stats">
        <div class="as-panel as-stat">
            <div class="as-muted" style="font-size:12px">Registered students</div>
            <strong><?= count($students) ?></strong>
            <div class="as-muted" style="font-size:12px;margin-top:5px">Students in your database</div>
        </div>

        <div class="as-panel as-stat">
            <div class="as-muted" style="font-size:12px">Assigned students</div>
            <strong><?= $assignedCount ?></strong>
            <div class="as-muted" style="font-size:12px;margin-top:5px">With workplace assignment</div>
        </div>

        <div class="as-panel as-stat">
            <div class="as-muted" style="font-size:12px">Course types</div>
            <strong>
                <?php
                $courses = [];
                foreach ($students as $student) {
                    $course = trim((string)($student['course'] ?? ''));
                    if ($course !== '') {
                        $courses[$course] = true;
                    }
                }
                echo count($courses);
                ?>
            </strong>
            <div class="as-muted" style="font-size:12px;margin-top:5px">Different courses</div>
        </div>
    </div>

    <!-- Student directory -->
    <section class="as-panel">
        <div class="as-toolbar">
            <div>
                <strong>Student directory</strong>
                <div class="as-muted" style="font-size:12px;margin-top:5px">
                    Search by student name, ID, or course.
                </div>
            </div>

            <input
                class="as-search"
                id="asSearch"
                type="search"
                placeholder="⌕ Search students..."
                autocomplete="off"
                aria-label="Search students"
            >
        </div>

        <div class="as-scroll">
            <table class="as-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Student ID</th>
                        <th>Course</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Assignment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="asRows">
                    <?php foreach ($students as $s): ?>
                        <?php
                        $firstName = trim((string)($s['first_name'] ?? ''));
                        $lastName = trim((string)($s['last_name'] ?? ''));
                        $middleName = trim((string)($s['middle_name'] ?? ''));
                        $name = trim($firstName . ' ' . ($middleName !== '' ? $middleName . ' ' : '') . $lastName);
                        $initials = strtoupper(mb_substr($firstName ?: 'S', 0, 1) . mb_substr($lastName ?: '', 0, 1));
                        $studentId = (string)($s['student_id'] ?? '');
                        $course = (string)($s['course'] ?? '');
                        $email = (string)($s['institutional_email'] ?? '');
                        $mobile = (string)($s['mobile_number'] ?? '');
                        $department = (string)($s['department'] ?? '');
                        $building = (string)($s['building'] ?? '');

                        $searchText = mb_strtolower($name . ' ' . $studentId . ' ' . $course . ' ' . $email . ' ' . $mobile);
                        $editData = [
                            'studentNo' => $studentId,
                            'department' => $department,
                            'building' => $building
                        ];
                        ?>
                        <tr class="as-row" data-search="<?= e($searchText) ?>">
                            <td>
                                <div class="as-person">
                                    <div class="as-avatar"><?= e($initials) ?></div>
                                    <div>
                                        <strong><?= e($name) ?></strong>
                                        <div class="as-muted" style="font-size:11px;margin-top:3px"><?= e($s['school_name'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><strong><?= e($studentId) ?></strong></td>
                            <td><?= e($course ?: '—') ?></td>
                            <td><span style="font-size:12px"><?= e($email ?: '—') ?></span></td>
                            <td><?= e($mobile ?: '—') ?></td>
                            <td>
                                <?php if ($department !== '' || $building !== ''): ?>
                                    <strong><?= e($department !== '' ? $department : 'Assigned') ?></strong>
                                    <?php if ($building !== ''): ?>
                                        <div class="as-muted" style="font-size:11px;margin-top:3px"><?= e($building) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="as-pill as-gray">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="as-button"
                                    data-assignment="<?= e(json_encode($editData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>"
                                    onclick="editAssignment(this)"
                                >
                                    Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!$students): ?>
            <div class="as-empty">
                <div style="font-size:32px;margin-bottom:10px">♧</div>
                <strong>No students found yet</strong>
                <p style="font-size:13px">Students registered in your database will appear here.</p>
            </div>
        <?php endif; ?>

        <div class="as-toolbar" style="border-bottom:0">
            <span class="as-muted" style="font-size:12px">
                Showing <strong id="asCount" style="color:var(--qr-text);"><?= count($students) ?></strong> of <?= count($students) ?> students
            </span>
        </div>
    </section>
</div>

<!-- Assignment Modal -->
<div class="as-modal" id="asModal" role="dialog" aria-modal="true" aria-labelledby="asModalTitle">
    <div class="as-modal-card">
        <div class="as-toolbar">
            <div>
                <strong id="asModalTitle" style="color:var(--qr-text); font-size: 15px;">Update student assignment</strong>
                <div class="as-muted" style="font-size:12px;margin-top:4px">Assign a department and building for this student.</div>
            </div>
            <button class="as-button" type="button" onclick="closeAssignment()" aria-label="Close">✕</button>
        </div>

        <form method="post" action="assigned_students.php" class="as-modal-body">
            <?= csrf_field() ?>

            <div class="as-field">
                <label for="asStudent">Student</label>
                <select name="student_id" id="asStudent" required>
                    <option value="">Choose student</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= e($s['student_id'] ?? '') ?>">
                            <?= e(trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? ''))) ?> — <?= e($s['student_id'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="as-field">
                <label for="asDepartment">Department</label>
                <input id="asDepartment" name="department" maxlength="100" required placeholder="e.g. IT Department">
            </div>

            <div class="as-field">
                <label for="asBuilding">Building / Unit</label>
                <input id="asBuilding" name="building" maxlength="100" required placeholder="e.g. Main Office">
            </div>

            <div style="display:flex;justify-content:flex-end;gap:9px;flex-wrap:wrap; margin-top: 8px;">
                <button class="as-button" type="button" onclick="closeAssignment()">Cancel</button>
                <button class="as-primary" type="submit">Save assignment</button>
            </div>
        </form>
    </div>
</div>

<script>
const asModal = document.getElementById('asModal');

function openAssignment() {
    document.getElementById('asStudent').value = '';
    document.getElementById('asDepartment').value = '';
    document.getElementById('asBuilding').value = '';
    asModal.classList.add('open');
}

function closeAssignment() {
    asModal.classList.remove('open');
}

function editAssignment(button) {
    try {
        const data = JSON.parse(button.dataset.assignment);
        document.getElementById('asStudent').value = data.studentNo || '';
        document.getElementById('asDepartment').value = data.department || '';
        document.getElementById('asBuilding').value = data.building || '';
        asModal.classList.add('open');
    } catch (error) {
        alert('Unable to open this assignment. Please refresh the page and try again.');
    }
}

asModal.addEventListener('click', function(event) {
    if (event.target === asModal) {
        closeAssignment();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAssignment();
    }
});

document.getElementById('asSearch').addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll('.as-row');
    let visible = 0;

    rows.forEach(function(row) {
        const matches = row.dataset.search.includes(query);
        row.hidden = !matches;
        if (matches) {
            visible++;
        }
    });

    document.getElementById('asCount').textContent = visible;
});
</script>

<?php
include __DIR__ . '/includes/layout_bottom.php';
?>