<?php
require_once __DIR__ . '/includes/bootstrap.php';

$company = require_company();
$pdo = db();
$companyId = (int) $company['id'];

$pageKey = 'students';
$pageTitle = 'Assigned Students';
$eyebrow = 'STUDENT MANAGEMENT';

/* Save assignment */
if (is_post()) {
    require_csrf('assigned_students.php');

    $studentNo = trim((string)($_POST['student_id_no'] ?? ''));
    $department = trim((string)($_POST['department'] ?? ''));
    $building = trim((string)($_POST['building'] ?? ''));

    if (
        $studentNo === '' ||
        $department === '' ||
        $building === '' ||
        mb_strlen($department) > 100 ||
        mb_strlen($building) > 100
    ) {
        flash_set('error', 'Please select a student and complete the assignment fields.');
        redirect('assigned_students.php');
    }

    $stmt = $pdo->prepare(
        'UPDATE students
         SET department = ?, building = ?
         WHERE student_id_no = ? AND company_id = ?'
    );
    $stmt->execute([$department, $building, $studentNo, $companyId]);

    flash_set(
        $stmt->rowCount() >= 0 ? 'success' : 'error',
        'Assignment saved successfully.'
    );
    redirect('assigned_students.php');
}

/* Load only this company's students */
$stmt = $pdo->prepare(
    'SELECT id, first_name, last_name, student_id_no, program,
            avatar, completed_hours, required_hours, attendance,
            evaluation, department, building
     FROM students
     WHERE company_id = ?
     ORDER BY last_name, first_name'
);
$stmt->execute([$companyId]);
$students = $stmt->fetchAll();

$pending = count(array_filter(
    $students,
    fn($s) => ($s['evaluation'] ?? 'Pending') !== 'Completed'
));

include __DIR__ . '/includes/layout_top.php';
?>

<style>
.as-page{max-width:1500px;margin:auto}
.as-heading{display:flex;justify-content:space-between;align-items:end;gap:16px;flex-wrap:wrap;margin-bottom:24px}
.as-eyebrow{font-size:11px;letter-spacing:.16em;font-weight:800;color:#0284c7}
.as-title{font-size:clamp(27px,3vw,36px);font-weight:850;letter-spacing:-.04em;margin:6px 0;color:#0f172a}
.as-muted{color:#64748b}
.as-panel{background:#fff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 4px 15px #0f172a08;overflow:hidden;transition:background .2s, border-color .2s}
.as-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:14px;margin-bottom:20px}
.as-stat{padding:20px}
.as-stat strong{display:block;font-size:29px;margin-top:7px;color:#0f172a}
.as-toolbar{padding:19px 21px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}
.as-search{padding:11px 13px;border:1px solid #dbe3ee;border-radius:11px;min-width:240px;max-width:100%;background:#f8fafc;outline:none;color:inherit}
.as-search:focus,.as-field input:focus,.as-field select:focus{border-color:#60a5fa;box-shadow:0 0 0 3px #dbeafe}
.as-primary{border:0;border-radius:11px;background:#172554;color:white;padding:11px 16px;font-weight:750;cursor:pointer;transition:.2s}
.as-primary:hover{background:#1e40af;transform:translateY(-1px)}
.as-button{border:1px solid #dbe3ee;border-radius:9px;padding:8px 12px;background:white;font-weight:700;font-size:12px;cursor:pointer;color:#334155}
.as-button:hover{background:#eff6ff;border-color:#93c5fd}
.as-scroll{overflow:auto}
.as-table{width:100%;min-width:850px;border-collapse:collapse}
.as-table th{background:#f8fafc;color:#64748b;text-transform:uppercase;font-size:10px;letter-spacing:.08em;text-align:left;padding:14px}
.as-table td{padding:14px;border-top:1px solid #f1f5f9;font-size:13px}
.as-table tbody tr:hover td{background:#fbfdff}
.as-person{display:flex;align-items:center;gap:10px}
.as-avatar{width:39px;height:39px;border-radius:12px;background:#e0f2fe;color:#075985;display:grid;place-items:center;font-weight:800;flex-shrink:0}
.as-pill{display:inline-flex;border-radius:99px;padding:5px 9px;font-size:11px;font-weight:750}
.as-green{background:#dcfce7;color:#166534}
.as-amber{background:#fef3c7;color:#92400e}
.as-gray{background:#f1f5f9;color:#475569}
.as-progress{height:7px;width:105px;max-width:100%;background:#e2e8f0;border-radius:99px;overflow:hidden}
.as-progress span{display:block;height:100%;background:linear-gradient(90deg,#0ea5e9,#4f46e5);border-radius:99px}
.as-modal{display:none;position:fixed;inset:0;background:#0f172a80;z-index:9999;align-items:center;justify-content:center;padding:16px}
.as-modal.open{display:flex}
.as-modal-card{background:#fff;width:min(510px,100%);border-radius:20px;overflow:hidden;box-shadow:0 25px 80px #0f172a40;animation:asEnter .18s ease-out}
@keyframes asEnter{from{opacity:0;transform:translateY(8px) scale(.99)}to{opacity:1;transform:translateY(0) scale(1)}}
.as-modal-body{padding:22px;display:grid;gap:16px}
.as-field label{display:block;font-size:12px;font-weight:750;margin-bottom:7px;color:#334155}
.as-field input,.as-field select{width:100%;padding:12px;border:1px solid #dbe3ee;border-radius:10px;background:white;outline:none;color:inherit}
.as-empty{padding:42px 20px;text-align:center;color:#64748b}

/* =========================================
   DARK MODE ADAPTATION (Matches Dashboard)
========================================= */
html[data-theme="dark"] .as-title,
.dark .as-title {
    color: #f8fafc !important;
}
html[data-theme="dark"] .as-muted,
.dark .as-muted {
    color: #94a3b8 !important;
}
html[data-theme="dark"] .as-panel,
.dark .as-panel {
    background: #111c38 !important;
    border-color: #1e293b !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
}
html[data-theme="dark"] .as-stat strong,
.dark .as-stat strong {
    color: #f8fafc !important;
}
html[data-theme="dark"] .as-toolbar,
.dark .as-toolbar {
    border-bottom-color: #1e293b !important;
}
html[data-theme="dark"] .as-search,
.dark .as-search {
    background: #16223f !important;
    border-color: #233354 !important;
    color: #f8fafc !important;
}
html[data-theme="dark"] .as-search::placeholder,
.dark .as-search::placeholder {
    color: #64748b !important;
}
html[data-theme="dark"] .as-table th,
.dark .as-table th {
    background: #16223f !important;
    color: #94a3b8 !important;
    border-bottom: 1px solid #1e293b !important;
}
html[data-theme="dark"] .as-table td,
.dark .as-table td {
    border-top-color: #1e293b !important;
    color: #e2e8f0 !important;
}
html[data-theme="dark"] .as-table tbody tr:hover td,
.dark .as-table tbody tr:hover td {
    background: #16223f !important;
}
html[data-theme="dark"] .as-button,
.dark .as-button {
    background: #16223f !important;
    border-color: #233354 !important;
    color: #f8fafc !important;
}
html[data-theme="dark"] .as-button:hover,
.dark .as-button:hover {
    background: #1e293b !important;
    border-color: #0284c7 !important;
}
html[data-theme="dark"] .as-modal-card,
.dark .as-modal-card {
    background: #111c38 !important;
    border: 1px solid #1e293b !important;
}
html[data-theme="dark"] .as-field label,
.dark .as-field label {
    color: #cbd5e1 !important;
}
html[data-theme="dark"] .as-field input,
html[data-theme="dark"] .as-field select,
.dark .as-field input,
.dark .as-field select {
    background: #16223f !important;
    border-color: #233354 !important;
    color: #f8fafc !important;
}
html[data-theme="dark"] .as-progress,
.dark .as-progress {
    background: #1e293b !important;
}

@media(max-width:600px){.as-search{width:100%;min-width:0}.as-heading{align-items:start}.as-primary{width:100%}}
</style>

<div class="as-page">

    <div class="as-heading">
        <div>
            <div class="as-eyebrow">STUDENT ROSTER</div>
            <h1 class="as-title">Assigned Students</h1>
            <div class="as-muted">Manage workplace assignments and monitor intern progress.</div>
        </div>
        <button class="as-primary" type="button" onclick="openAssignment()">
            + Edit assignment
        </button>
    </div>

    <div class="as-stats">
        <div class="as-panel as-stat">
            <div class="as-muted" style="font-size:12px">Assigned interns</div>
            <strong><?= count($students) ?></strong>
            <div class="as-muted" style="font-size:12px;margin-top:5px">Students under your company</div>
        </div>

        <div class="as-panel as-stat">
            <div class="as-muted" style="font-size:12px">Reviews not completed</div>
            <strong style="color:#fbbf24"><?= $pending ?></strong>
            <div class="as-muted" style="font-size:12px;margin-top:5px">Evaluation follow-up</div>
        </div>
    </div>

    <section class="as-panel">
        <div class="as-toolbar">
            <div>
                <strong>Intern directory</strong>
                <div class="as-muted" style="font-size:12px;margin-top:5px">
                    Search by student name, ID, or program.
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
                        <th>Program</th>
                        <th>Assignment</th>
                        <th>Hours progress</th>
                        <th>Attendance</th>
                        <th>Evaluation</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="asRows">
                <?php foreach ($students as $s): ?>
                    <?php
                    $name = trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? ''));
                    $done = (float)($s['completed_hours'] ?? 0);
                    $required = (float)($s['required_hours'] ?? 0);
                    $percent = $required > 0
                        ? min(100, max(0, (int)round($done / $required * 100)))
                        : 0;
                    $evaluation = $s['evaluation'] ?? 'Pending';
                    $initials = strtoupper(
                        mb_substr($s['first_name'] ?? 'S', 0, 1) .
                        mb_substr($s['last_name'] ?? '', 0, 1)
                    );
                    $searchText = mb_strtolower(
                        $name . ' ' .
                        ($s['student_id_no'] ?? '') . ' ' .
                        ($s['program'] ?? '')
                    );
                    $editData = [
                        'studentNo' => $s['student_id_no'] ?? '',
                        'department' => $s['department'] ?? '',
                        'building' => $s['building'] ?? ''
                    ];
                    ?>
                    <tr class="as-row" data-search="<?= e($searchText) ?>">
                        <td>
                            <div class="as-person">
                                <div class="as-avatar">
                                    <?= e(($s['avatar'] ?? '') ?: $initials) ?>
                                </div>
                                <div>
                                    <strong><?= e($name) ?></strong>
                                    <div class="as-muted" style="font-size:11px;margin-top:3px">
                                        <?= e($s['student_id_no'] ?? '') ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td><?= e($s['program'] ?? '—') ?></td>

                        <td>
                            <strong><?= e(($s['department'] ?? '') ?: 'Not assigned') ?></strong>
                            <div class="as-muted" style="font-size:11px;margin-top:3px">
                                <?= e($s['building'] ?? '') ?>
                            </div>
                        </td>

                        <td>
                            <div style="font-size:11px;margin-bottom:6px">
                                <?= e((string)$done) ?> / <?= e((string)$required) ?> hrs
                                · <?= $percent ?>%
                            </div>
                            <div class="as-progress">
                                <span style="width:<?= $percent ?>%"></span>
                            </div>
                        </td>

                        <td>
                            <span class="as-pill as-gray">
                                <?= e($s['attendance'] ?? '—') ?>
                            </span>
                        </td>

                        <td>
                            <span class="as-pill <?= $evaluation === 'Completed' ? 'as-green' : ($evaluation === 'Pending' ? 'as-amber' : 'as-gray') ?>">
                                <?= e($evaluation) ?>
                            </span>
                        </td>

                        <td>
                            <button
                                type="button"
                                class="as-button"
                                data-assignment="<?= e(json_encode($editData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>"
                                onclick="editAssignment(this)"
                            >Edit</button>
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
                <p style="font-size:13px">Students assigned to your company will appear here.</p>
            </div>
        <?php endif; ?>

        <div class="as-toolbar" style="border-bottom:0">
            <span class="as-muted" style="font-size:12px">
                Showing <strong id="asCount"><?= count($students) ?></strong>
                of <?= count($students) ?> students
            </span>
        </div>
    </section>
</div>

<div class="as-modal" id="asModal" role="dialog" aria-modal="true" aria-labelledby="asModalTitle">
    <div class="as-modal-card">
        <div class="as-toolbar">
            <div>
                <strong id="asModalTitle">Update student assignment</strong>
                <div class="as-muted" style="font-size:12px;margin-top:4px">
                    Update the department and building for this student.
                </div>
            </div>
            <button class="as-button" type="button" onclick="closeAssignment()" aria-label="Close">✕</button>
        </div>

        <form method="post" action="assigned_students.php" class="as-modal-body">
            <?= csrf_field() ?>

            <div class="as-field">
                <label for="asStudent">Student</label>
                <select name="student_id_no" id="asStudent" required>
                    <option value="">Choose student</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= e($s['student_id_no'] ?? '') ?>">
                            <?= e(trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) . ' — ' . ($s['student_id_no'] ?? '')) ?>
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

            <div style="display:flex;justify-content:flex-end;gap:9px;flex-wrap:wrap">
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

asModal.addEventListener('click', function (event) {
    if (event.target === asModal) closeAssignment();
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeAssignment();
});

document.getElementById('asSearch').addEventListener('input', function () {
    const query = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll('.as-row');
    let visible = 0;

    rows.forEach(function (row) {
        const matches = row.dataset.search.includes(query);
        row.hidden = !matches;
        if (matches) visible++;
    });

    document.getElementById('asCount').textContent = visible;
});
</script>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>