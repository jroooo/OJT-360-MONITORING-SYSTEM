<?php
require_once __DIR__ . '/includes/bootstrap.php';

$company = require_company();
$pdo = db();
$companyId = (int) $company['id'];

$pageKey = 'evaluations';
$pageTitle = 'Evaluations';
$eyebrow = 'INTERN PERFORMANCE';

$errors = [];
$oldInput = [
    'student_id' => '',
    'score' => '',
    'feedback' => '',
];

if (is_post()) {
    require_csrf('evaluations.php');

    $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
    $scoreInput = trim((string) ($_POST['score'] ?? ''));
    $feedback = trim((string) ($_POST['feedback'] ?? ''));

    $oldInput = [
        'student_id' => $studentId ?: '',
        'score' => $scoreInput,
        'feedback' => $feedback,
    ];

    if (!$studentId || $studentId < 1) {
        $errors[] = 'Please select a valid student.';
    }

    if ($scoreInput === '' || !is_numeric($scoreInput)) {
        $errors[] = 'Please enter a valid evaluation score.';
    } elseif ((float) $scoreInput < 0 || (float) $scoreInput > 100) {
        $errors[] = 'The score must be between 0 and 100.';
    }

    if (mb_strlen($feedback) > 2000) {
        $errors[] = 'Feedback must not exceed 2,000 characters.';
    }

    if (!$errors) {
        $studentCheck = $pdo->prepare(
            'SELECT id
             FROM students
             WHERE id = :student_id
               AND company_id = :company_id
             LIMIT 1'
        );

        $studentCheck->execute([
            ':student_id' => $studentId,
            ':company_id' => $companyId,
        ]);

        if (!$studentCheck->fetch(PDO::FETCH_ASSOC)) {
            $errors[] = 'That student is not assigned to your company.';
        }
    }

    if (!$errors) {
        $score = round((float) $scoreInput, 2);
        $status = 'Completed';

        try {
            $pdo->beginTransaction();

            $existing = $pdo->prepare(
                'SELECT student_id
                 FROM evaluations
                 WHERE student_id = :student_id
                   AND company_id = :company_id
                 LIMIT 1'
            );

            $existing->execute([
                ':student_id' => $studentId,
                ':company_id' => $companyId,
            ]);

            if ($existing->fetch(PDO::FETCH_ASSOC)) {
                $saveEvaluation = $pdo->prepare(
                    'UPDATE evaluations
                     SET score = :score,
                         status = :status,
                         feedback = :feedback,
                         updated_at = NOW()
                     WHERE student_id = :student_id
                       AND company_id = :company_id'
                );
            } else {
                $saveEvaluation = $pdo->prepare(
                    'INSERT INTO evaluations
                        (student_id, company_id, score, status, feedback, updated_at)
                     VALUES
                        (:student_id, :company_id, :score, :status, :feedback, NOW())'
                );
            }

            $saveEvaluation->execute([
                ':student_id' => $studentId,
                ':company_id' => $companyId,
                ':score' => $score,
                ':status' => $status,
                ':feedback' => $feedback,
            ]);

            $syncStudent = $pdo->prepare(
                'UPDATE students
                 SET evaluation = :evaluation
                 WHERE id = :student_id
                   AND company_id = :company_id'
            );

            $syncStudent->execute([
                ':evaluation' => $status,
                ':student_id' => $studentId,
                ':company_id' => $companyId,
            ]);

            $pdo->commit();

            flash_set('success', 'Evaluation saved successfully.');
            redirect('evaluations.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Evaluation save failed: ' . $exception->getMessage());
            $errors[] = 'Unable to save the evaluation. Please check your database structure and try again.';
        }
    }
}

$stmt = $pdo->prepare(
    'SELECT
        s.id,
        s.first_name,
        s.last_name,
        s.student_id_no,
        s.program,
        s.avatar,
        s.department,
        s.completed_hours,
        s.required_hours,
        s.evaluation AS student_evaluation,
        ev.score,
        ev.status AS evaluation_status,
        ev.feedback,
        ev.updated_at
     FROM students s
     LEFT JOIN evaluations ev
        ON ev.student_id = s.id
       AND ev.company_id = :evaluation_company_id
     WHERE s.company_id = :student_company_id
     ORDER BY s.last_name ASC, s.first_name ASC'
);

$stmt->execute([
    ':evaluation_company_id' => $companyId,
    ':student_company_id' => $companyId,
]);

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalStudents = count($students);
$completedCount = 0;
$pendingCount = 0;
$scoreTotal = 0;
$scoredCount = 0;

foreach ($students as $student) {
    $hasScore = $student['score'] !== null && $student['score'] !== '';

    if ($hasScore) {
        $completedCount++;
        $scoreTotal += (float) $student['score'];
        $scoredCount++;
    } else {
        $pendingCount++;
    }
}

$averageScore = $scoredCount > 0 ? $scoreTotal / $scoredCount : 0;

function evaluation_score_display($score): string
{
    if ($score === null || $score === '') {
        return '—';
    }

    return rtrim(rtrim(number_format((float) $score, 2, '.', ''), '0'), '.') . '%';
}

function evaluation_student_name(array $student): string
{
    return trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
}

include __DIR__ . '/includes/layout_top.php';
?>

<style>
    .eval-page {
        width: 100%;
        max-width: 100%;
        color: #0f172a;
    }

    .eval-page *,
    .eval-page *::before,
    .eval-page *::after {
        box-sizing: border-box;
    }

    .eval-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 24px;
    }

    .eval-page-header h1 {
        margin: 0 0 7px;
        font-size: clamp(23px, 3vw, 30px);
        font-weight: 800;
        letter-spacing: -0.04em;
        color: #0f172a;
    }

    .eval-page-header p {
        margin: 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
    }

    .eval-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 9px;
        color: #0284c7;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .13em;
        text-transform: uppercase;
    }

    .eval-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .eval-stat {
        min-width: 0;
        padding: 20px;
        background: #fff;
        border: 1px solid #e8edf5;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .025);
        transition: background .2s, border-color .2s;
    }

    .eval-stat-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
    }

    .eval-stat-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }

    .eval-stat-icon {
        display: grid;
        place-items: center;
        flex-shrink: 0;
        width: 36px;
        height: 36px;
        border-radius: 11px;
        background: #eef2ff;
        color: #4338ca;
        font-size: 17px;
        font-weight: 800;
    }

    .eval-stat-number {
        margin-bottom: 5px;
        color: #0f172a;
        font-size: 29px;
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: -.04em;
    }

    .eval-stat-note {
        color: #94a3b8;
        font-size: 12px;
    }

    .eval-card {
        overflow: hidden;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 17px;
        box-shadow: 0 3px 12px rgba(15, 23, 42, .035);
        transition: background .2s, border-color .2s;
    }

    .eval-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        padding: 23px 24px;
        border-bottom: 1px solid #edf1f6;
    }

    .eval-card-heading h2 {
        margin: 0 0 6px;
        color: #0f172a;
        font-size: 17px;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    .eval-card-heading p {
        margin: 0;
        color: #64748b;
        font-size: 12px;
        line-height: 1.6;
    }

    .eval-roster-count {
        flex-shrink: 0;
        padding: 7px 11px;
        color: #4338ca;
        background: #eef2ff;
        border-radius: 9px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .eval-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        padding: 16px 24px;
        background: #fff;
        border-bottom: 1px solid #edf1f6;
    }

    .eval-search-wrap {
        position: relative;
        flex: 1 1 240px;
        max-width: 390px;
        min-width: 180px;
    }

    .eval-search-icon {
        position: absolute;
        top: 50%;
        left: 13px;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
        font-size: 15px;
    }

    .eval-search,
    .eval-filter {
        display: block;
        width: 100%;
        min-height: 42px;
        margin: 0;
        padding: 10px 13px;
        color: #334155;
        background: #fff;
        border: 1px solid #dbe3ee;
        border-radius: 10px;
        outline: none;
        font-family: inherit;
        font-size: 13px;
        transition: border-color .15s, box-shadow .15s;
    }

    .eval-search {
        padding-left: 38px;
    }

    .eval-search:focus,
    .eval-filter:focus {
        border-color: #818cf8;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, .1);
    }

    .eval-filter-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 0 0 190px;
    }

    .eval-filter-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .eval-filter {
        min-width: 0;
        cursor: pointer;
    }

    .eval-table-scroll {
        width: 100%;
        overflow-x: auto;
        overscroll-behavior-x: contain;
    }

    .eval-table {
        width: 100%;
        min-width: 920px;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
    }

    .eval-table thead th {
        padding: 13px 16px;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 1px solid #e8edf5;
        text-align: left;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .075em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .eval-table thead th:first-child,
    .eval-table tbody td:first-child {
        padding-left: 24px;
    }

    .eval-table tbody td {
        padding: 16px;
        border-bottom: 1px solid #f0f3f8;
        vertical-align: middle;
        color: #334155;
        font-size: 12px;
    }

    .eval-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .eval-table tbody tr {
        transition: background .15s ease;
    }

    .eval-table tbody tr:hover {
        background: #fafbff;
    }

    .eval-table .col-student { width: 27%; }
    .eval-table .col-id { width: 13%; }
    .eval-table .col-program { width: 16%; }
    .eval-table .col-hours { width: 12%; }
    .eval-table .col-score { width: 10%; }
    .eval-table .col-status { width: 11%; }
    .eval-table .col-action { width: 11%; }

    .eval-student-cell {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 0;
    }

    .eval-avatar {
        display: grid;
        place-items: center;
        flex-shrink: 0;
        width: 39px;
        height: 39px;
        overflow: hidden;
        border-radius: 12px;
        background: #eef2ff;
        color: #4338ca;
        font-size: 12px;
        font-weight: 800;
    }

    .eval-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .eval-student-details {
        min-width: 0;
    }

    .eval-student-name {
        overflow: hidden;
        margin-bottom: 4px;
        color: #0f172a;
        font-size: 12px;
        font-weight: 800;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .eval-student-sub {
        overflow: hidden;
        color: #94a3b8;
        font-size: 11px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .eval-id-text,
    .eval-program-text {
        display: block;
        overflow: hidden;
        color: #475569;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .eval-hours-main {
        color: #334155;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .eval-hours-sub {
        margin-top: 4px;
        color: #94a3b8;
        font-size: 10px;
        white-space: nowrap;
    }

    .eval-score {
        color: #0f172a;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
    }

    .eval-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        max-width: 100%;
        padding: 6px 9px;
        border-radius: 8px;
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
    }

    .eval-status::before {
        content: "";
        width: 6px;
        height: 6px;
        flex-shrink: 0;
        border-radius: 50%;
        background: currentColor;
    }

    .eval-status-completed {
        color: #047857;
        background: #d1fae5;
    }

    .eval-status-pending {
        color: #b45309;
        background: #fef3c7;
    }

    .eval-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 34px;
        padding: 8px 10px;
        color: #fff;
        background: #172554;
        border: 1px solid #172554;
        border-radius: 8px;
        cursor: pointer;
        font-family: inherit;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
        transition: background .15s, transform .15s;
    }

    .eval-action-btn:hover {
        background: #1e40af;
        transform: translateY(-1px);
    }

    .eval-empty {
        padding: 48px 20px !important;
        text-align: center;
    }

    .eval-empty-icon {
        display: grid;
        place-items: center;
        width: 48px;
        height: 48px;
        margin: 0 auto 13px;
        color: #64748b;
        background: #f1f5f9;
        border-radius: 15px;
        font-size: 22px;
    }

    .eval-empty-title {
        margin-bottom: 6px;
        color: #334155;
        font-size: 14px;
        font-weight: 800;
    }

    .eval-empty-description {
        color: #94a3b8;
        font-size: 12px;
    }

    .eval-table-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        padding: 15px 24px;
        color: #64748b;
        background: #fff;
        border-top: 1px solid #edf1f6;
        font-size: 11px;
    }

    .eval-scroll-hint {
        color: #94a3b8;
    }

    .eval-alert {
        margin-bottom: 18px;
        padding: 13px 16px;
        border: 1px solid;
        border-radius: 11px;
        font-size: 13px;
    }

    .eval-alert-error {
        color: #991b1b;
        background: #fef2f2;
        border-color: #fecaca;
    }

    .eval-modal {
        position: fixed;
        z-index: 10000;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(15, 23, 42, .58);
        backdrop-filter: blur(4px);
    }

    .eval-modal.is-open {
        display: flex;
    }

    .eval-modal-panel {
        width: 100%;
        max-width: 510px;
        max-height: calc(100vh - 40px);
        overflow-y: auto;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 19px;
        box-shadow: 0 25px 80px rgba(15, 23, 42, .25);
        animation: evalModalIn .18s ease-out;
    }

    @keyframes evalModalIn {
        from { opacity: 0; transform: translateY(8px) scale(.99); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .eval-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 15px;
        padding: 22px 24px;
        border-bottom: 1px solid #edf1f6;
    }

    .eval-modal-header h2 {
        margin: 0 0 5px;
        color: #0f172a;
        font-size: 19px;
        font-weight: 800;
    }

    .eval-modal-header p {
        margin: 0;
        color: #64748b;
        font-size: 12px;
    }

    .eval-modal-close {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        flex-shrink: 0;
        color: #64748b;
        background: #f1f5f9;
        border: 0;
        border-radius: 9px;
        cursor: pointer;
        font-size: 20px;
    }

    .eval-modal-body {
        padding: 22px 24px;
    }

    .eval-form-student {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 23px;
        padding: 14px;
        background: #f8fafc;
        border: 1px solid #e8edf5;
        border-radius: 12px;
    }

    .eval-form-student-name {
        margin-bottom: 4px;
        color: #0f172a;
        font-size: 13px;
        font-weight: 800;
    }

    .eval-form-student-sub {
        color: #64748b;
        font-size: 11px;
    }

    .eval-form-group {
        margin-bottom: 18px;
    }

    .eval-form-group label {
        display: block;
        margin-bottom: 8px;
        color: #334155;
        font-size: 12px;
        font-weight: 800;
    }

    .eval-form-group input,
    .eval-form-group textarea {
        display: block;
        width: 100%;
        margin: 0;
        padding: 12px 13px;
        color: #0f172a;
        background: #fff;
        border: 1px solid #dbe3ee;
        border-radius: 10px;
        outline: none;
        font-family: inherit;
        font-size: 13px;
    }

    .eval-form-group input:focus,
    .eval-form-group textarea:focus {
        border-color: #818cf8;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, .1);
    }

    .eval-form-group textarea {
        min-height: 115px;
        resize: vertical;
        line-height: 1.6;
    }

    .eval-form-help {
        margin-top: 7px;
        color: #94a3b8;
        font-size: 11px;
    }

    .eval-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 17px 24px;
        background: #f8fafc;
        border-top: 1px solid #edf1f6;
    }

    .eval-btn-secondary,
    .eval-btn-primary {
        min-height: 40px;
        padding: 10px 15px;
        border-radius: 9px;
        cursor: pointer;
        font-family: inherit;
        font-size: 12px;
        font-weight: 800;
    }

    .eval-btn-secondary {
        color: #475569;
        background: #fff;
        border: 1px solid #dbe3ee;
    }

    .eval-btn-primary {
        color: #fff;
        background: #172554;
        border: 1px solid #172554;
    }

    .eval-btn-primary:hover {
        background: #1e40af;
    }

    /* DARK MODE ADAPTATION */
    html[data-theme="dark"] .eval-page-header h1, .dark .eval-page-header h1,
    html[data-theme="dark"] .eval-card-heading h2, .dark .eval-card-heading h2,
    html[data-theme="dark"] .eval-modal-header h2, .dark .eval-modal-header h2,
    html[data-theme="dark"] .eval-form-student-name, .dark .eval-form-student-name,
    html[data-theme="dark"] .eval-student-name, .dark .eval-student-name,
    html[data-theme="dark"] .eval-score, .dark .eval-score,
    html[data-theme="dark"] .eval-stat-number, .dark .eval-stat-number { color: #f8fafc !important; }
    html[data-theme="dark"] .eval-page-header p, .dark .eval-page-header p,
    html[data-theme="dark"] .eval-card-heading p, .dark .eval-card-heading p,
    html[data-theme="dark"] .eval-modal-header p, .dark .eval-modal-header p,
    html[data-theme="dark"] .eval-stat-label, .dark .eval-stat-label,
    html[data-theme="dark"] .eval-stat-note, .dark .eval-stat-note,
    html[data-theme="dark"] .eval-filter-label, .dark .eval-filter-label,
    html[data-theme="dark"] .eval-table-footer, .dark .eval-table-footer,
    html[data-theme="dark"] .eval-empty-description, .dark .eval-empty-description,
    html[data-theme="dark"] .eval-student-sub, .dark .eval-student-sub { color: #94a3b8 !important; }
    html[data-theme="dark"] .eval-stat, .dark .eval-stat,
    html[data-theme="dark"] .eval-card, .dark .eval-card,
    html[data-theme="dark"] .eval-modal-panel, .dark .eval-modal-panel {
        background: #111c38 !important;
        border-color: #1e293b !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
    }
    html[data-theme="dark"] .eval-card-header, .dark .eval-card-header,
    html[data-theme="dark"] .eval-toolbar, .dark .eval-toolbar,
    html[data-theme="dark"] .eval-table-footer, .dark .eval-table-footer,
    html[data-theme="dark"] .eval-modal-header, .dark .eval-modal-header,
    html[data-theme="dark"] .eval-modal-footer, .dark .eval-modal-footer {
        border-color: #1e293b !important;
        background: #111c38 !important;
    }
    html[data-theme="dark"] .eval-search, .dark .eval-search,
    html[data-theme="dark"] .eval-filter, .dark .eval-filter,
    html[data-theme="dark"] .eval-form-group input, .dark .eval-form-group input,
    html[data-theme="dark"] .eval-form-group textarea, .dark .eval-form-group textarea {
        background: #16223f !important;
        border-color: #233354 !important;
        color: #f8fafc !important;
    }
    html[data-theme="dark"] .eval-table thead th, .dark .eval-table thead th {
        background: #16223f !important;
        color: #94a3b8 !important;
        border-bottom-color: #1e293b !important;
    }
    html[data-theme="dark"] .eval-table tbody td, .dark .eval-table tbody td {
        border-bottom-color: #1e293b !important;
        color: #e2e8f0 !important;
    }
    html[data-theme="dark"] .eval-table tbody tr:hover, .dark .eval-table tbody tr:hover {
        background: #16223f !important;
    }
    html[data-theme="dark"] .eval-form-student, .dark .eval-form-student {
        background: #16223f !important;
        border-color: #233354 !important;
    }
    html[data-theme="dark"] .eval-modal-close, .dark .eval-modal-close {
        background: #16223f !important;
        color: #94a3b8 !important;
        border-color: #233354 !important;
    }
    html[data-theme="dark"] .eval-btn-secondary, .dark .eval-btn-secondary {
        background: #16223f !important;
        border-color: #233354 !important;
        color: #f8fafc !important;
    }

    @media (max-width: 1100px) {
        .eval-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .eval-page-header {
            flex-direction: column;
            gap: 10px;
        }

        .eval-stat-grid {
            gap: 10px;
        }

        .eval-stat {
            padding: 15px;
        }

        .eval-stat-number {
            font-size: 25px;
        }

        .eval-stat-top {
            margin-bottom: 13px;
        }

        .eval-card-header {
            padding: 18px;
        }

        .eval-toolbar {
            align-items: stretch;
            padding: 14px 18px;
        }

        .eval-search-wrap {
            max-width: none;
            flex-basis: 100%;
        }

        .eval-filter-wrap {
            flex-basis: 100%;
        }

        .eval-table-footer {
            padding: 14px 18px;
        }

        .eval-modal {
            padding: 10px;
        }

        .eval-modal-panel {
            max-height: calc(100vh - 20px);
        }

        .eval-modal-header,
        .eval-modal-body {
            padding: 18px;
        }

        .eval-modal-footer {
            padding: 14px 18px;
        }
    }
</style>

<div class="eval-page">
    <div class="eval-page-header">
        <div>
            <div class="eval-eyebrow"><?= e($eyebrow) ?></div>
            <h1>Student Evaluations</h1>
            <p>Review intern performance, record scores, and provide feedback to help students improve.</p>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="eval-alert eval-alert-error" role="alert">
            <strong>Please check the following:</strong>
            <ul style="margin:8px 0 0;padding-left:20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="eval-stat-grid">
        <div class="eval-stat">
            <div class="eval-stat-top">
                <span class="eval-stat-label">Total Students</span>
                <span class="eval-stat-icon">♙</span>
            </div>
            <div class="eval-stat-number"><?= number_format($totalStudents) ?></div>
            <div class="eval-stat-note">Students assigned to your company</div>
        </div>

        <div class="eval-stat">
            <div class="eval-stat-top">
                <span class="eval-stat-label">Evaluations Completed</span>
                <span class="eval-stat-icon" style="color:#047857;background:#d1fae5;">✓</span>
            </div>
            <div class="eval-stat-number"><?= number_format($completedCount) ?></div>
            <div class="eval-stat-note">Students with a recorded score</div>
        </div>

        <div class="eval-stat">
            <div class="eval-stat-top">
                <span class="eval-stat-label">Pending Evaluations</span>
                <span class="eval-stat-icon" style="color:#b45309;background:#fef3c7;">◷</span>
            </div>
            <div class="eval-stat-number"><?= number_format($pendingCount) ?></div>
            <div class="eval-stat-note">Students still awaiting a score</div>
        </div>

        <div class="eval-stat">
            <div class="eval-stat-top">
                <span class="eval-stat-label">Average Score</span>
                <span class="eval-stat-icon" style="color:#0369a1;background:#e0f2fe;">%</span>
            </div>
            <div class="eval-stat-number">
                <?= $scoredCount > 0 ? e(number_format($averageScore, 1) . '%') : '—' ?>
            </div>
            <div class="eval-stat-note">Average of completed evaluations</div>
        </div>
    </div>

    <section class="eval-card">
        <div class="eval-card-header">
            <div class="eval-card-heading">
                <h2>Evaluation Roster</h2>
                <p>Manage student evaluations and review submitted feedback.</p>
            </div>
            <span class="eval-roster-count">
                <?= number_format($totalStudents) ?> <?= $totalStudents === 1 ? 'STUDENT' : 'STUDENTS' ?>
            </span>
        </div>

        <div class="eval-toolbar">
            <div class="eval-search-wrap">
                <span class="eval-search-icon">⌕</span>
                <input
                    type="search"
                    class="eval-search"
                    id="evalSearch"
                    placeholder="Search name, student ID, or program..."
                    aria-label="Search students"
                >
            </div>

            <div class="eval-filter-wrap">
                <label class="eval-filter-label" for="evalStatusFilter">Status</label>
                <select class="eval-filter" id="evalStatusFilter">
                    <option value="all">All evaluations</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
        </div>

        <div class="eval-table-scroll">
            <table class="eval-table">
                <thead>
                    <tr>
                        <th class="col-student">Student</th>
                        <th class="col-id">Student ID</th>
                        <th class="col-program">Program</th>
                        <th class="col-hours">OJT Hours</th>
                        <th class="col-score">Score</th>
                        <th class="col-status">Status</th>
                        <th class="col-action">Action</th>
                    </tr>
                </thead>

                <tbody id="evalTableBody">
                    <?php if (!$students): ?>
                        <tr>
                            <td colspan="7" class="eval-empty">
                                <div class="eval-empty-icon">♙</div>
                                <div class="eval-empty-title">No students assigned yet</div>
                                <div class="eval-empty-description">
                                    Students assigned to your company will appear here.
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <?php
                                $studentName = evaluation_student_name($student);
                                $studentIdNo = trim((string) ($student['student_id_no'] ?? ''));
                                $program = trim((string) ($student['program'] ?? ''));
                                $department = trim((string) ($student['department'] ?? ''));
                                $avatar = trim((string) ($student['avatar'] ?? ''));
                                $hasScore = $student['score'] !== null && $student['score'] !== '';
                                $status = $hasScore ? 'completed' : 'pending';
                                $hoursCompleted = (float) ($student['completed_hours'] ?? 0);
                                $hoursRequired = (float) ($student['required_hours'] ?? 0);
                                $studentInitials = initials($studentName);
                            ?>
                            <tr
                                class="eval-student-row"
                                data-search="<?= e(mb_strtolower($studentName . ' ' . $studentIdNo . ' ' . $program . ' ' . $department)) ?>"
                                data-status="<?= e($status) ?>"
                            >
                                <td>
                                    <div class="eval-student-cell">
                                        <div class="eval-avatar">
                                            <?php if ($avatar !== ''): ?>
                                                <img
                                                    src="<?= e($avatar) ?>"
                                                    alt="<?= e($studentName) ?>"
                                                    loading="lazy"
                                                    onerror="this.style.display='none';this.parentElement.textContent='<?= e($studentInitials) ?>';"
                                                >
                                            <?php else: ?>
                                                <?= e($studentInitials) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="eval-student-details">
                                            <div class="eval-student-name" title="<?= e($studentName) ?>">
                                                <?= e($studentName !== '' ? $studentName : 'Unnamed Student') ?>
                                            </div>
                                            <div class="eval-student-sub" title="<?= e($department) ?>">
                                                <?= e($department !== '' ? $department : 'OJT Intern') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="eval-id-text" title="<?= e($studentIdNo) ?>">
                                        <?= e($studentIdNo !== '' ? $studentIdNo : '—') ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="eval-program-text" title="<?= e($program) ?>">
                                        <?= e($program !== '' ? $program : '—') ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="eval-hours-main">
                                        <?= e(number_format($hoursCompleted, 0)) ?>
                                        <span style="color:#94a3b8;font-weight:500;">/ <?= e(number_format($hoursRequired, 0)) ?></span>
                                    </div>
                                    <div class="eval-hours-sub">completed / required</div>
                                </td>

                                <td>
                                    <span class="eval-score">
                                        <?= e(evaluation_score_display($student['score'])) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($hasScore): ?>
                                        <span class="eval-status eval-status-completed">Completed</span>
                                    <?php else: ?>
                                        <span class="eval-status eval-status-pending">Pending</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <button
                                        type="button"
                                        class="eval-action-btn"
                                        data-eval-open
                                        data-student-id="<?= (int) $student['id'] ?>"
                                        data-student-name="<?= e($studentName) ?>"
                                        data-student-number="<?= e($studentIdNo) ?>"
                                        data-student-program="<?= e($program) ?>"
                                        data-score="<?= e($student['score'] !== null ? (string) $student['score'] : '') ?>"
                                        data-feedback="<?= e((string) ($student['feedback'] ?? '')) ?>"
                                    >
                                        <?= $hasScore ? 'Edit' : 'Evaluate' ?>
                                        <span aria-hidden="true">→</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr id="evalNoResults" style="display:none;">
                            <td colspan="7" class="eval-empty">
                                <div class="eval-empty-icon">⌕</div>
                                <div class="eval-empty-title">No matching students</div>
                                <div class="eval-empty-description">Try changing your search or status filter.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="eval-table-footer">
            <span id="evalResultCount">
                Showing <?= number_format($totalStudents) ?> <?= $totalStudents === 1 ? 'student' : 'students' ?>
            </span>
            <span class="eval-scroll-hint">Scroll horizontally to view all columns on smaller screens.</span>
        </div>
    </section>
</div>

<div class="eval-modal" id="evalModal" aria-hidden="true">
    <div
        class="eval-modal-panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="evalModalTitle"
    >
        <div class="eval-modal-header">
            <div>
                <h2 id="evalModalTitle">Evaluate Student</h2>
                <p>Enter a performance score and optional feedback.</p>
            </div>
            <button type="button" class="eval-modal-close" id="evalModalClose" aria-label="Close modal">×</button>
        </div>

        <form method="post" action="evaluations.php" id="evalForm">
            <?= csrf_field() ?>

            <div class="eval-modal-body">
                <input type="hidden" name="student_id" id="evalStudentId" value="">

                <div class="eval-form-student">
                    <div class="eval-avatar" id="evalModalAvatar">ST</div>
                    <div>
                        <div class="eval-form-student-name" id="evalModalStudentName">Student name</div>
                        <div class="eval-form-student-sub" id="evalModalStudentInfo">Student details</div>
                    </div>
                </div>

                <div class="eval-form-group">
                    <label for="evalScore">Performance Score (%)</label>
                    <input
                        type="number"
                        id="evalScore"
                        name="score"
                        min="0"
                        max="100"
                        step="0.01"
                        placeholder="Enter a score from 0 to 100"
                        required
                    >
                    <div class="eval-form-help">Use a score from 0 to 100. Saving a score marks the evaluation as completed.</div>
                </div>

                <div class="eval-form-group">
                    <label for="evalFeedback">Feedback <span style="color:#94a3b8;font-weight:500;">(Optional)</span></label>
                    <textarea
                        id="evalFeedback"
                        name="feedback"
                        maxlength="2000"
                        placeholder="Write comments about the student's performance, strengths, and areas for improvement..."
                    ></textarea>
                    <div class="eval-form-help"><span id="evalFeedbackCount">0</span>/2000 characters</div>
                </div>
            </div>

            <div class="eval-modal-footer">
                <button type="button" class="eval-btn-secondary" id="evalCancel">Cancel</button>
                <button type="submit" class="eval-btn-primary">Save Evaluation</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    'use strict';

    const searchInput = document.getElementById('evalSearch');
    const statusFilter = document.getElementById('evalStatusFilter');
    const tableBody = document.getElementById('evalTableBody');
    const resultCount = document.getElementById('evalResultCount');
    const noResults = document.getElementById('evalNoResults');

    const modal = document.getElementById('evalModal');
    const closeButton = document.getElementById('evalModalClose');
    const cancelButton = document.getElementById('evalCancel');
    const form = document.getElementById('evalForm');

    const studentIdInput = document.getElementById('evalStudentId');
    const studentNameDisplay = document.getElementById('evalModalStudentName');
    const studentInfoDisplay = document.getElementById('evalModalStudentInfo');
    const avatarDisplay = document.getElementById('evalModalAvatar');
    const scoreInput = document.getElementById('evalScore');
    const feedbackInput = document.getElementById('evalFeedback');
    const feedbackCount = document.getElementById('evalFeedbackCount');

    let previousFocus = null;

    function filterRows() {
        if (!tableBody) return;

        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        const selectedStatus = statusFilter ? statusFilter.value : 'all';
        const rows = Array.from(tableBody.querySelectorAll('.eval-student-row'));

        let visible = 0;

        rows.forEach(function (row) {
            const searchableText = (row.dataset.search || '').toLowerCase();
            const rowStatus = row.dataset.status || 'pending';

            const matchesSearch = searchableText.includes(query);
            const matchesStatus = selectedStatus === 'all' || rowStatus === selectedStatus;
            const show = matchesSearch && matchesStatus;

            row.style.display = show ? '' : 'none';

            if (show) visible++;
        });

        if (noResults) {
            noResults.style.display = rows.length > 0 && visible === 0 ? '' : 'none';
        }

        if (resultCount) {
            resultCount.textContent = 'Showing ' + visible + ' of ' + rows.length +
                (rows.length === 1 ? ' student' : ' students');
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterRows);
    if (statusFilter) statusFilter.addEventListener('change', filterRows);

    function openModal(button) {
        if (!modal || !form) return;

        previousFocus = document.activeElement;

        studentIdInput.value = button.dataset.studentId || '';
        studentNameDisplay.textContent = button.dataset.studentName || 'Unnamed Student';

        const studentNumber = button.dataset.studentNumber || 'No student ID';
        const program = button.dataset.studentProgram || 'No program specified';
        studentInfoDisplay.textContent = studentNumber + ' · ' + program;

        const name = (button.dataset.studentName || '').trim();
        const initials = name
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(function (part) { return part.charAt(0).toUpperCase(); })
            .join('');

        avatarDisplay.textContent = initials || 'ST';
        scoreInput.value = button.dataset.score || '';
        feedbackInput.value = button.dataset.feedback || '';
        updateFeedbackCount();

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        window.setTimeout(function () {
            scoreInput.focus();
        }, 50);
    }

    function closeModal() {
        if (!modal) return;

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';

        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
    }

    function updateFeedbackCount() {
        if (feedbackCount && feedbackInput) {
            feedbackCount.textContent = String(feedbackInput.value.length);
        }
    }

    document.querySelectorAll('[data-eval-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(button);
        });
    });

    if (closeButton) closeButton.addEventListener('click', closeModal);
    if (cancelButton) cancelButton.addEventListener('click', closeModal);
    if (feedbackInput) feedbackInput.addEventListener('input', updateFeedbackCount);

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    if (form) {
        form.addEventListener('submit', function (event) {
            const score = Number(scoreInput.value);

            if (scoreInput.value.trim() === '' || !Number.isFinite(score) || score < 0 || score > 100) {
                event.preventDefault();
                scoreInput.focus();
                alert('Please enter a valid score between 0 and 100.');
            }
        });
    }

    filterRows();
})();
</script>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>