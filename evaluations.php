<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$company = require_company();
$pdo = db();

$pageKey = 'evaluations';
$pageTitle = 'Evaluations';
$eyebrow = 'PERFORMANCE REVIEWS';

$errors = [];
$successMessage = flash_get('success') ?? null;

/*
|--------------------------------------------------------------------------
| EVALUATION CRITERIA
|--------------------------------------------------------------------------
*/

$criteria = [
    'attendance_punctuality' => 'Attendance & Punctuality',
    'quality_of_work'        => 'Quality of Work',
    'productivity'           => 'Productivity',
    'communication'          => 'Communication',
    'teamwork'               => 'Teamwork',
    'initiative'             => 'Initiative',
    'professionalism'        => 'Professionalism',
    'adaptability'           => 'Adaptability',
    'problem_solving'        => 'Problem Solving',
    'technical_skills'       => 'Technical Skills',
];

/*
|--------------------------------------------------------------------------
| DEFAULT INPUT
|--------------------------------------------------------------------------
*/

$oldInput = [
    'student_id'             => '',
    'attendance_punctuality' => '0',
    'quality_of_work'        => '0',
    'productivity'           => '0',
    'communication'          => '0',
    'teamwork'               => '0',
    'initiative'             => '0',
    'professionalism'        => '0',
    'adaptability'           => '0',
    'problem_solving'        => '0',
    'technical_skills'       => '0',
    'overall_performance'    => '',
    'strengths'              => '',
    'areas_for_improvement'  => '',
    'comments'               => '',
    'recommendation'         => '',
    'status'                 => 'Completed',
];

/*
|--------------------------------------------------------------------------
| SAVE EVALUATION
|--------------------------------------------------------------------------
*/

if (is_post()) {

    require_csrf('evaluations.php');

    $studentId = filter_input(
        INPUT_POST,
        'student_id',
        FILTER_VALIDATE_INT
    );

    $oldInput['student_id'] = $studentId ?: '';

    $status = trim(
        (string)($_POST['status'] ?? 'Completed')
    );

    if (!in_array($status, ['Draft', 'Completed'], true)) {
        $status = 'Completed';
    }

    $oldInput['status'] = $status;

    foreach ($criteria as $field => $label) {

        $value = $_POST[$field] ?? '0';

        if (
            !is_numeric($value) ||
            (float)$value < 0 ||
            (float)$value > 5 ||
            floor((float)$value) !== (float)$value
        ) {

            $errors[] =
                $label . ' must be a whole number from 0 to 5.';

            $oldInput[$field] = '0';

        } else {

            $oldInput[$field] =
                (string)(int)$value;
        }
    }

    $overallPerformance = trim(
        (string)($_POST['overall_performance'] ?? '')
    );

    $strengths = trim(
        (string)($_POST['strengths'] ?? '')
    );

    $areasForImprovement = trim(
        (string)($_POST['areas_for_improvement'] ?? '')
    );

    $comments = trim(
        (string)($_POST['comments'] ?? '')
    );

    $recommendation = trim(
        (string)($_POST['recommendation'] ?? '')
    );

    $oldInput['overall_performance'] = $overallPerformance;
    $oldInput['strengths'] = $strengths;
    $oldInput['areas_for_improvement'] = $areasForImprovement;
    $oldInput['comments'] = $comments;
    $oldInput['recommendation'] = $recommendation;

    $student = null;

    if (!$studentId || $studentId < 1) {

        $errors[] = 'Please select a student.';

    } else {

        $studentStmt = $pdo->prepare(
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
                institutional_email
             FROM students
             WHERE id = :id
             LIMIT 1'
        );

        $studentStmt->execute([
            ':id' => $studentId
        ]);

        $student =
            $studentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            $errors[] =
                'Selected student does not exist.';
        }
    }

    if (mb_strlen($overallPerformance) > 50) {
        $errors[] =
            'Overall performance cannot exceed 50 characters.';
    }

    if (mb_strlen($recommendation) > 100) {
        $errors[] =
            'Recommendation cannot exceed 100 characters.';
    }

    if (mb_strlen($strengths) > 10000) {
        $errors[] =
            'Strengths cannot exceed 10,000 characters.';
    }

    if (mb_strlen($areasForImprovement) > 10000) {
        $errors[] =
            'Areas for improvement cannot exceed 10,000 characters.';
    }

    if (mb_strlen($comments) > 10000) {
        $errors[] =
            'Comments cannot exceed 10,000 characters.';
    }

    if ($status === 'Completed') {

        if ($overallPerformance === '') {
            $errors[] =
                'Please select the overall performance.';
        }

        if ($recommendation === '') {
            $errors[] =
                'Please select a recommendation.';
        }
    }

    if (!$errors) {

        try {

            $ratingTotal = 0;

            foreach ($criteria as $field => $label) {

                $ratingTotal +=
                    (int)$oldInput[$field];
            }

            $overallRating =
                round($ratingTotal / 10, 2);

            $pdo->beginTransaction();

            $existingStmt = $pdo->prepare(
                'SELECT id
                 FROM intern_evaluations
                 WHERE student_db_id = :student_db_id
                 ORDER BY id DESC
                 LIMIT 1'
            );

            $existingStmt->execute([
                ':student_db_id' => $studentId
            ]);

            $existing =
                $existingStmt->fetch(PDO::FETCH_ASSOC);

            $evaluatorId = null;

            if ($existing) {

                $evaluationId =
                    (int)$existing['id'];

                $sql = '
                    UPDATE intern_evaluations
                    SET
                        evaluator_id = :evaluator_id,
                        evaluation_date = CURDATE(),

                        attendance_punctuality =
                            :attendance_punctuality,

                        quality_of_work =
                            :quality_of_work,

                        productivity =
                            :productivity,

                        communication =
                            :communication,

                        teamwork =
                            :teamwork,

                        initiative =
                            :initiative,

                        professionalism =
                            :professionalism,

                        adaptability =
                            :adaptability,

                        problem_solving =
                            :problem_solving,

                        technical_skills =
                            :technical_skills,

                        overall_rating =
                            :overall_rating,

                        overall_performance =
                            :overall_performance,

                        strengths =
                            :strengths,

                        areas_for_improvement =
                            :areas_for_improvement,

                        comments =
                            :comments,

                        recommendation =
                            :recommendation,

                        status =
                            :status,

                        updated_at =
                            CURRENT_TIMESTAMP

                    WHERE id = :id
                ';

                $stmt =
                    $pdo->prepare($sql);

                $stmt->execute([

                    ':evaluator_id' =>
                        $evaluatorId,

                    ':attendance_punctuality' =>
                        (int)$oldInput[
                            'attendance_punctuality'
                        ],

                    ':quality_of_work' =>
                        (int)$oldInput[
                            'quality_of_work'
                        ],

                    ':productivity' =>
                        (int)$oldInput[
                            'productivity'
                        ],

                    ':communication' =>
                        (int)$oldInput[
                            'communication'
                        ],

                    ':teamwork' =>
                        (int)$oldInput[
                            'teamwork'
                        ],

                    ':initiative' =>
                        (int)$oldInput[
                            'initiative'
                        ],

                    ':professionalism' =>
                        (int)$oldInput[
                            'professionalism'
                        ],

                    ':adaptability' =>
                        (int)$oldInput[
                            'adaptability'
                        ],

                    ':problem_solving' =>
                        (int)$oldInput[
                            'problem_solving'
                        ],

                    ':technical_skills' =>
                        (int)$oldInput[
                            'technical_skills'
                        ],

                    ':overall_rating' =>
                        $overallRating,

                    ':overall_performance' =>
                        $overallPerformance !== ''
                            ? $overallPerformance
                            : null,

                    ':strengths' =>
                        $strengths !== ''
                            ? $strengths
                            : null,

                    ':areas_for_improvement' =>
                        $areasForImprovement !== ''
                            ? $areasForImprovement
                            : null,

                    ':comments' =>
                        $comments !== ''
                            ? $comments
                            : null,

                    ':recommendation' =>
                        $recommendation !== ''
                            ? $recommendation
                            : null,

                    ':status' =>
                        $status,

                    ':id' =>
                        $evaluationId
                ]);

            } else {

                $sql = '
                    INSERT INTO intern_evaluations (
                        student_db_id,
                        evaluator_id,
                        evaluation_date,

                        attendance_punctuality,
                        quality_of_work,
                        productivity,
                        communication,
                        teamwork,
                        initiative,
                        professionalism,
                        adaptability,
                        problem_solving,
                        technical_skills,

                        overall_rating,
                        overall_performance,
                        strengths,
                        areas_for_improvement,
                        comments,
                        recommendation,
                        status
                    )

                    VALUES (
                        :student_db_id,
                        :evaluator_id,
                        CURDATE(),

                        :attendance_punctuality,
                        :quality_of_work,
                        :productivity,
                        :communication,
                        :teamwork,
                        :initiative,
                        :professionalism,
                        :adaptability,
                        :problem_solving,
                        :technical_skills,

                        :overall_rating,
                        :overall_performance,
                        :strengths,
                        :areas_for_improvement,
                        :comments,
                        :recommendation,
                        :status
                    )
                ';

                $stmt =
                    $pdo->prepare($sql);

                $stmt->execute([

                    ':student_db_id' =>
                        $studentId,

                    ':evaluator_id' =>
                        $evaluatorId,

                    ':attendance_punctuality' =>
                        (int)$oldInput[
                            'attendance_punctuality'
                        ],

                    ':quality_of_work' =>
                        (int)$oldInput[
                            'quality_of_work'
                        ],

                    ':productivity' =>
                        (int)$oldInput[
                            'productivity'
                        ],

                    ':communication' =>
                        (int)$oldInput[
                            'communication'
                        ],

                    ':teamwork' =>
                        (int)$oldInput[
                            'teamwork'
                        ],

                    ':initiative' =>
                        (int)$oldInput[
                            'initiative'
                        ],

                    ':professionalism' =>
                        (int)$oldInput[
                            'professionalism'
                        ],

                    ':adaptability' =>
                        (int)$oldInput[
                            'adaptability'
                        ],

                    ':problem_solving' =>
                        (int)$oldInput[
                            'problem_solving'
                        ],

                    ':technical_skills' =>
                        (int)$oldInput[
                            'technical_skills'
                        ],

                    ':overall_rating' =>
                        $overallRating,

                    ':overall_performance' =>
                        $overallPerformance !== ''
                            ? $overallPerformance
                            : null,

                    ':strengths' =>
                        $strengths !== ''
                            ? $strengths
                            : null,

                    ':areas_for_improvement' =>
                        $areasForImprovement !== ''
                            ? $areasForImprovement
                            : null,

                    ':comments' =>
                        $comments !== ''
                            ? $comments
                            : null,

                    ':recommendation' =>
                        $recommendation !== ''
                            ? $recommendation
                            : null,

                    ':status' =>
                        $status
                ]);
            }

            $pdo->commit();

            flash_set(
                'success',
                $status === 'Completed'
                    ? 'Evaluation saved successfully.'
                    : 'Evaluation draft saved successfully.'
            );

            redirect('evaluations.php');

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] =
                'Database error: ' . $e->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| LOAD STUDENTS + LATEST EVALUATION
|--------------------------------------------------------------------------
*/

$students = [];

try {

    $sql = '
        SELECT
            s.id,
            s.first_name,
            s.middle_name,
            s.last_name,
            s.course,
            s.mobile_number,
            s.school_name,
            s.complete_address,
            s.student_id,
            s.institutional_email,

            e.id AS evaluation_id,
            e.evaluation_date,

            e.attendance_punctuality,
            e.quality_of_work,
            e.productivity,
            e.communication,
            e.teamwork,
            e.initiative,
            e.professionalism,
            e.adaptability,
            e.problem_solving,
            e.technical_skills,

            e.overall_rating,
            e.overall_performance,
            e.strengths,
            e.areas_for_improvement,
            e.comments,
            e.recommendation,
            e.status AS evaluation_status

        FROM students s

        LEFT JOIN intern_evaluations e
            ON e.id = (
                SELECT MAX(e2.id)
                FROM intern_evaluations e2
                WHERE e2.student_db_id = s.id
            )

        ORDER BY
            s.last_name ASC,
            s.first_name ASC
    ';

    $stmt =
        $pdo->query($sql);

    $students =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    $errors[] =
        'Unable to load evaluations: ' .
        $e->getMessage();
}

$studentProgressById = [];

try {

    $progressStmt = $pdo->query(
        "
        SELECT
            s.id AS student_id,
            COALESCE(i.required_hours, 480) AS required_hours,
            COALESCE(
                (
                    SELECT SUM(
                        CASE
                            WHEN a.time_out IS NOT NULL THEN COALESCE(a.duration_minutes, 0)
                            ELSE GREATEST(0, TIMESTAMPDIFF(SECOND, a.time_in, NOW()) - COALESCE(a.break_seconds, 0)) / 60.0
                        END
                    )
                    FROM attendance a
                    WHERE a.student_pk = s.id
                ),
                0
            ) AS rendered_hours
        FROM students s
        LEFT JOIN internships i
            ON i.id = (
                SELECT MAX(i2.id)
                FROM internships i2
                WHERE i2.student_pk = s.id
            )
        "
    );

    foreach ($progressStmt->fetchAll(PDO::FETCH_ASSOC) as $progressRow) {
        $studentId = (int)($progressRow['student_id'] ?? 0);
        $requiredHours = (float)($progressRow['required_hours'] ?? 120);
        $renderedHours = (float)($progressRow['rendered_hours'] ?? 0);

        $studentProgressById[$studentId] = [
            'required_hours' => $requiredHours > 0 ? $requiredHours : 120,
            'rendered_hours' => max(0.0, $renderedHours),
            'percent' => $requiredHours > 0 ? min(100.0, ($renderedHours / $requiredHours) * 100) : 0.0,
        ];
    }

} catch (Throwable $e) {
    $studentProgressById = [];
}

if (isset($_GET['progress_student_id'])) {
    $progressStudentId = filter_input(INPUT_GET, 'progress_student_id', FILTER_VALIDATE_INT);

    if ($progressStudentId && $progressStudentId > 0) {
        $progressPayload = studentProgressSnapshot($pdo, $progressStudentId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($progressPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function studentProgressSnapshot(PDO $pdo, int $studentId): array {
    $result = [
        'student_id' => $studentId,
        'required_hours' => 120,
        'rendered_hours' => 0.0,
        'percent' => 0.0,
        'status' => 'Pending',
        'rating' => 0.0,
    ];

    try {
        $sql = "
            SELECT
                s.id AS student_id,
                COALESCE(i.required_hours, 480) AS required_hours,
                COALESCE(
                    (
                        SELECT SUM(
                            CASE
                                WHEN a.time_out IS NOT NULL THEN COALESCE(a.duration_minutes, 0)
                                ELSE GREATEST(0, TIMESTAMPDIFF(SECOND, a.time_in, NOW()) - COALESCE(a.break_seconds, 0)) / 60.0
                            END
                        )
                        FROM attendance a
                        WHERE a.student_pk = s.id
                    ),
                    0
                ) AS rendered_hours,
                (
                    SELECT e.status
                    FROM intern_evaluations e
                    WHERE e.student_db_id = s.id
                    ORDER BY e.id DESC
                    LIMIT 1
                ) AS evaluation_status,
                (
                    SELECT e.overall_rating
                    FROM intern_evaluations e
                    WHERE e.student_db_id = s.id
                    ORDER BY e.id DESC
                    LIMIT 1
                ) AS overall_rating
            FROM students s
            LEFT JOIN internships i
                ON i.id = (
                    SELECT MAX(i2.id)
                    FROM internships i2
                    WHERE i2.student_pk = s.id
                )
            WHERE s.id = :student_id
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $result;
        }

        $requiredHours = (float)($row['required_hours'] ?? 120);
        $renderedHours = (float)($row['rendered_hours'] ?? 0.0);
        $result['required_hours'] = $requiredHours > 0 ? $requiredHours : 120;
        $result['rendered_hours'] = max(0.0, $renderedHours);
        $result['percent'] = $result['required_hours'] > 0 ? min(100.0, ($result['rendered_hours'] / $result['required_hours']) * 100) : 0.0;
        $result['status'] = trim((string)($row['evaluation_status'] ?? 'Pending')) ?: 'Pending';
        $result['rating'] = (float)($row['overall_rating'] ?? 0.0);

    } catch (Throwable $e) {
        $result['status'] = 'Pending';
    }

    return $result;
}

function evaluationStudentName(array $student): string {
    $parts = [];
    foreach (['first_name', 'middle_name', 'last_name'] as $field) {
        if (!empty($student[$field])) {
            $parts[] = trim((string)$student[$field]);
        }
    }
    return trim(implode(' ', $parts));
}

function evaluationInitials(array $student): string {
    $first = trim((string)($student['first_name'] ?? ''));
    $last = trim((string)($student['last_name'] ?? ''));
    $initials = '';
    if ($first !== '') {
        $initials .= strtoupper(substr($first, 0, 1));
    }
    if ($last !== '') {
        $initials .= strtoupper(substr($last, 0, 1));
    }
    return $initials ?: 'ST';
}

function evaluationPercentage($rating): float {
    if ($rating === null || $rating === '') {
        return 0;
    }
    return round(((float)$rating / 5) * 100, 1);
}

function evaluationDate($date): string {
    if (!$date) {
        return '—';
    }
    $timestamp = strtotime((string)$date);
    if (!$timestamp) {
        return (string)$date;
    }
    return date('M d, Y', $timestamp);
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalStudents = count($students);
$completedCount = 0;
$draftCount = 0;
$pendingCount = 0;
$ratingTotal = 0;
$ratingCount = 0;

foreach ($students as $student) {
    $status = $student['evaluation_status'] ?? null;
    if ($status === 'Completed') {
        $completedCount++;
        if ($student['overall_rating'] !== null && $student['overall_rating'] !== '') {
            $ratingTotal += (float)$student['overall_rating'];
            $ratingCount++;
        }
    } elseif ($status === 'Draft') {
        $draftCount++;
    } else {
        $pendingCount++;
    }
}

$averageRating = $ratingCount > 0 ? round($ratingTotal / $ratingCount, 2) : 0;
$averagePercentage = round(($averageRating / 5) * 100, 1);

include __DIR__ . '/includes/layout_top.php';
?>

<style>
/* ============================================================
   EVALUATIONS PAGE - DUAL THEME SUPPORT
   ============================================================ */

.evaluations-page {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
    box-sizing: border-box;
    font-family: "Segoe UI", Inter, "Helvetica Neue", Arial, sans-serif;
}

.evaluations-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.evaluations-header-left {
    min-width: 0;
}

.evaluations-eyebrow {
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

.evaluations-eyebrow::before {
    content: "";
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--qr-brand, #2563eb);
    box-shadow: 0 0 10px rgba(37, 99, 235, 0.4);
}

.evaluations-header h1 {
    margin: 0;
    font-size: clamp(30px, 3.4vw, 42px);
    line-height: 1.12;
    letter-spacing: -0.04em;
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.evaluations-header p {
    margin: 10px 0 0;
    max-width: 720px;
    color: var(--qr-text-muted, #64748b);
    font-size: 14px;
    line-height: 1.65;
    font-weight: 400;
}

.evaluation-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.evaluation-stat {
    background: var(--qr-surface, #fff);
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 20px;
    padding: 22px;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
}

.evaluation-stat-label {
    color: var(--qr-text-subtle, #94a3b8);
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.evaluation-stat-value {
    margin-top: 8px;
    font-size: 32px;
    font-weight: 800;
    color: var(--qr-text, #0f172a);
    letter-spacing: -0.03em;
}

.evaluation-stat-sub {
    margin-top: 6px;
    color: var(--qr-text-muted, #64748b);
    font-size: 12px;
}

.evaluation-card {
    background: var(--qr-surface, #fff);
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
}

.evaluation-toolbar {
    display: flex;
    gap: 14px;
    padding: 20px 22px;
    border-bottom: 1px solid var(--qr-border, #e2e8f0);
    background: var(--qr-surface, #fff);
    align-items: center;
}

.evaluation-search {
    flex: 1;
}

.evaluation-search input,
.evaluation-filter {
    width: 100%;
    height: 44px;
    box-sizing: border-box;
    border: 1px solid var(--qr-border-strong, #cbd5e1);
    border-radius: 12px;
    padding: 0 14px;
    background: var(--qr-surface-alt, #f8fafc);
    color: var(--qr-text, #0f172a);
    font-size: 13px;
    outline: none;
}

.evaluation-filter {
    width: 180px;
}

.evaluation-table-wrap {
    overflow-x: auto;
}

.evaluation-table {
    width: 100%;
    min-width: 1000px;
    border-collapse: collapse;
}

.evaluation-table th {
    padding: 13px 16px;
    background: var(--qr-surface-alt, #f8fafc);
    color: var(--qr-text-subtle, #94a3b8);
    font-size: 10px;
    font-weight: 800;
    text-align: left;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border-bottom: 1px solid var(--qr-border, #e2e8f0);
    white-space: nowrap;
}

.evaluation-table td {
    padding: 15px 16px;
    border-bottom: 1px solid var(--qr-border, #e2e8f0);
    color: var(--qr-text-soft, #334155);
    font-size: 13px;
    vertical-align: middle;
}

.evaluation-table tbody tr:hover td {
    background: var(--qr-surface-alt, #f8fafc);
}

.evaluation-student {
    display: flex;
    align-items: center;
    gap: 12px;
}

.evaluation-avatar {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--qr-surface-alt, #f8fafc);
    color: var(--qr-brand, #2563eb);
    font-size: 13px;
    font-weight: 900;
    border: 1px solid var(--qr-border, #e2e8f0);
}

.evaluation-student-name {
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.evaluation-student-email {
    margin-top: 3px;
    color: var(--qr-text-muted, #64748b);
    font-size: 11px;
}

.evaluation-course {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 999px;
    background: var(--qr-surface-alt, #f1f5f9);
    color: var(--qr-text-muted, #475569);
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    border: 1px solid var(--qr-border, #e2e8f0);
}

.evaluation-rating {
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.evaluation-rating-percent {
    margin-top: 3px;
    color: var(--qr-text-muted, #64748b);
    font-size: 11px;
}

.evaluation-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.evaluation-status.completed {
    background: #16a34a;
    color: #ffffff;
}

.evaluation-status.draft {
    background: #f59e0b;
    color: #ffffff;
}

.evaluation-status.pending {
    background: var(--qr-surface-alt, #f1f5f9);
    color: var(--qr-text-muted, #64748b);
    border: 1px solid var(--qr-border, #e2e8f0);
}

.evaluation-button {
    border: 0;
    border-radius: 10px;
    padding: 8px 14px;
    background: #2563eb;
    color: #fff;
    cursor: pointer;
    font-size: 12px;
    font-weight: 800;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    transition: transform 0.15s ease, background 0.15s ease;
}

.evaluation-button:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.evaluation-row-actions {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 8px;
    flex-wrap: wrap;
}

.evaluation-progress-button {
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 10px;
    padding: 8px 12px;
    background: var(--qr-surface-alt, #f8fafc);
    color: var(--qr-text-soft, #334155);
    cursor: pointer;
    font-size: 12px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: background 0.15s ease;
}

.evaluation-progress-button:hover {
    background: var(--qr-surface-soft, #f1f5f9);
    border-color: var(--qr-border-strong, #cbd5e1);
}

.evaluation-footer {
    padding: 18px 22px;
    color: var(--qr-text-muted, #64748b);
    font-size: 12px;
}

/* MODALS */
.evaluation-modal,
.progress-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    padding: 20px;
    overflow-y: auto;
}

.evaluation-modal.open,
.progress-modal.open {
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

.evaluation-modal-box,
.progress-modal-box {
    width: min(860px, 100%);
    margin: 32px auto;
    background: var(--qr-surface, #fff);
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 25px 80px rgba(15, 23, 42, 0.25);
}

.progress-modal-header,
.evaluation-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    padding: 22px 24px;
    border-bottom: 1px solid var(--qr-border, #e2e8f0);
    background: var(--qr-surface, #fff);
}

.progress-modal-header h2,
.evaluation-modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.progress-modal-header p,
.evaluation-modal-header p {
    margin: 5px 0 0;
    color: var(--qr-text-muted, #64748b);
    font-size: 12px;
}

.progress-close,
.evaluation-close {
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: 10px;
    background: var(--qr-surface-alt, #f1f5f9);
    color: var(--qr-text-soft, #475569);
    font-size: 20px;
    cursor: pointer;
}

.progress-modal-body,
.evaluation-modal-body {
    padding: 24px;
    background: var(--qr-surface, #fff);
}

.progress-hero {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px;
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 16px;
    background: var(--qr-surface-alt, #f8fafc);
}

.progress-hero-avatar {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    background: #2563eb;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: 800;
}

.progress-hero-copy {
    display: flex;
    flex-direction: column;
    gap: 3px;
    flex: 1;
}

.progress-hero-name {
    font-size: 16px;
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.progress-hero-meta {
    color: var(--qr-text-muted, #64748b);
    font-size: 12px;
}

.progress-status-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
}

.progress-status-badge.completed { background: #16a34a; color: #fff; }
.progress-status-badge.draft { background: #f59e0b; color: #fff; }
.progress-status-badge.pending { background: var(--qr-surface-alt, #f1f5f9); color: var(--qr-text-muted, #64748b); border: 1px solid var(--qr-border, #e2e8f0); }

.progress-overview {
    display: grid;
    grid-template-columns: 210px 1fr;
    gap: 18px;
    margin-top: 20px;
}

.progress-ring-wrap {
    background: var(--qr-surface-alt, #f8fafc);
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 16px;
    padding: 18px 14px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress-ring {
    position: relative;
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: conic-gradient(#2563eb 0deg, #2563eb var(--progress-angle, 0deg), var(--qr-border, #e2e8f0) var(--progress-angle, 0deg), var(--qr-border, #e2e8f0) 360deg);
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress-ring::before {
    content: "";
    position: absolute;
    inset: 12px;
    border-radius: 50%;
    background: var(--qr-surface, #fff);
}

.progress-ring-value {
    position: relative;
    z-index: 1;
    text-align: center;
}

.progress-ring-value strong {
    display: block;
    font-size: 26px;
    line-height: 1;
    color: var(--qr-text, #0f172a);
    font-weight: 800;
}

.progress-ring-value span {
    display: block;
    margin-top: 5px;
    font-size: 10px;
    color: var(--qr-text-muted, #64748b);
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.progress-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.progress-metric {
    background: var(--qr-surface-alt, #f8fafc);
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 16px;
    padding: 16px;
}

.progress-metric-label {
    font-size: 11px;
    font-weight: 800;
    color: var(--qr-text-subtle, #94a3b8);
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.progress-metric-value {
    margin-top: 8px;
    font-size: 24px;
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.progress-metric-meta {
    margin-top: 4px;
    font-size: 11px;
    color: var(--qr-text-muted, #64748b);
}

.progress-section {
    margin-top: 20px;
}

.progress-section-title {
    margin: 0 0 12px;
    font-size: 14px;
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.progress-rows {
    display: grid;
    gap: 10px;
}

.progress-row {
    background: var(--qr-surface-alt, #f8fafc);
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 12px;
    padding: 12px 14px;
}

.progress-row-head {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 6px;
    font-size: 12px;
    color: var(--qr-text, #0f172a);
    font-weight: 700;
}

.progress-bar-track {
    width: 100%;
    height: 8px;
    border-radius: 999px;
    background: var(--qr-border, #e2e8f0);
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    border-radius: inherit;
    background: #2563eb;
}

.progress-milestones {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-top: 16px;
}

.progress-milestone {
    background: var(--qr-surface-alt, #f8fafc);
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 12px;
    padding: 12px;
    color: var(--qr-text-soft, #334155);
    font-size: 12px;
    font-weight: 700;
    text-align: center;
}

.progress-milestone.done { border-color: #16a34a; background: rgba(22, 163, 74, 0.08); color: #16a34a; }
.progress-milestone.active { border-color: #2563eb; background: rgba(37, 99, 235, 0.08); color: #2563eb; }

.evaluation-student-info {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 14px;
    margin-bottom: 24px;
    background: var(--qr-surface-alt, #f8fafc);
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 13px;
}

.evaluation-modal-name {
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.evaluation-modal-details {
    margin-top: 4px;
    color: var(--qr-text-muted, #64748b);
    font-size: 12px;
}

.evaluation-section {
    margin-top: 24px;
}

.evaluation-criteria {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.evaluation-criterion {
    padding: 13px;
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 11px;
    background: var(--qr-surface-alt, #f8fafc);
}

.evaluation-criterion label,
.evaluation-field label {
    display: block;
    margin-bottom: 7px;
    color: var(--qr-text-soft, #334155);
    font-size: 12px;
    font-weight: 700;
}

.evaluation-criterion select,
.evaluation-field select,
.evaluation-field textarea {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid var(--qr-border-strong, #cbd5e1);
    border-radius: 9px;
    padding: 10px;
    color: var(--qr-text, #0f172a);
    background: var(--qr-surface, #fff);
    font: inherit;
    font-size: 13px;
    outline: none;
}

.evaluation-field {
    margin-bottom: 15px;
}

.evaluation-field textarea {
    min-height: 100px;
    resize: vertical;
}

.evaluation-two-columns {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.evaluation-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 17px 24px;
    background: var(--qr-surface-alt, #f8fafc);
    border-top: 1px solid var(--qr-border, #e2e8f0);
}

.evaluation-cancel,
.evaluation-save {
    border: 0;
    border-radius: 10px;
    padding: 10px 18px;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}

.evaluation-cancel {
    background: var(--qr-border, #e2e8f0);
    color: var(--qr-text-soft, #334155);
}

.evaluation-save {
    background: #2563eb;
    color: #fff;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.evaluation-alert {
    padding: 13px 15px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: 13px;
}

.evaluation-alert.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.evaluation-alert.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

/* ============================================================
   DARK MODE OVERRIDES (DEEP NAVY)
   ============================================================ */
html[data-theme="dark"] .evaluations-page,
.dark .evaluations-page {
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

html[data-theme="dark"] .evaluations-header h1,
.dark .evaluations-header h1,
html[data-theme="dark"] .evaluation-stat-value,
.dark .evaluation-stat-value,
html[data-theme="dark"] .evaluation-student-name,
.dark .evaluation-student-name,
html[data-theme="dark"] .evaluation-rating,
.dark .evaluation-rating,
html[data-theme="dark"] .evaluation-modal-name,
.dark .evaluation-modal-name,
html[data-theme="dark"] .evaluation-section-title,
.dark .evaluation-section-title,
html[data-theme="dark"] .progress-modal-header h2,
.dark .progress-modal-header h2,
html[data-theme="dark"] .progress-hero-name,
.dark .progress-hero-name,
html[data-theme="dark"] .progress-section-title,
.dark .progress-section-title,
html[data-theme="dark"] .progress-metric-value,
.dark .progress-metric-value,
html[data-theme="dark"] .progress-ring-value strong,
.dark .progress-ring-value strong {
    color: #ffffff !important;
}

html[data-theme="dark"] .evaluation-stat,
.dark .evaluation-stat,
html[data-theme="dark"] .evaluation-card,
.dark .evaluation-card,
html[data-theme="dark"] .evaluation-modal-box,
.dark .evaluation-modal-box,
html[data-theme="dark"] .progress-modal-box,
.dark .progress-modal-box {
    background: var(--qr-surface) !important;
    border-color: var(--qr-border) !important;
}

html[data-theme="dark"] .evaluation-toolbar,
.dark .evaluation-toolbar,
html[data-theme="dark"] .evaluation-table th,
.dark .evaluation-table th,
html[data-theme="dark"] .evaluation-table td,
.dark .evaluation-table td,
html[data-theme="dark"] .evaluation-modal-header,
.dark .evaluation-modal-header,
html[data-theme="dark"] .evaluation-modal-footer,
.dark .evaluation-modal-footer,
html[data-theme="dark"] .evaluation-criterion,
.dark .evaluation-criterion,
html[data-theme="dark"] .progress-modal-header,
.dark .progress-modal-header,
html[data-theme="dark"] .progress-hero,
.dark .progress-hero,
html[data-theme="dark"] .progress-ring-wrap,
.dark .progress-ring-wrap,
html[data-theme="dark"] .progress-metric,
.dark .progress-metric,
html[data-theme="dark"] .progress-row,
.dark .progress-row,
html[data-theme="dark"] .progress-milestone,
.dark .progress-milestone,
html[data-theme="dark"] .evaluation-student-info,
.dark .evaluation-student-info {
    border-color: var(--qr-border) !important;
    background: var(--qr-surface-alt) !important;
}

html[data-theme="dark"] .evaluation-search input,
.dark .evaluation-search input,
html[data-theme="dark"] .evaluation-filter,
.dark .evaluation-filter,
html[data-theme="dark"] .evaluation-field select,
.dark .evaluation-field select,
html[data-theme="dark"] .evaluation-criterion select,
.dark .evaluation-criterion select,
html[data-theme="dark"] .evaluation-field textarea,
.dark .evaluation-field textarea {
    background: var(--qr-surface-alt) !important;
    border-color: var(--qr-border) !important;
    color: #ffffff !important;
}

html[data-theme="dark"] .evaluation-button,
.dark .evaluation-button,
html[data-theme="dark"] .evaluation-save,
.dark .evaluation-save {
    background: #2563eb !important;
    color: #ffffff !important;
}

html[data-theme="dark"] .evaluation-cancel,
.dark .evaluation-cancel {
    background: var(--qr-surface-alt) !important;
    color: var(--qr-text-soft) !important;
    border: 1px solid var(--qr-border) !important;
}

@media (max-width: 900px) {
    .evaluation-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 650px) {
    .evaluations-page { padding: 15px; }
    .evaluation-stats { grid-template-columns: 1fr; }
    .evaluation-toolbar { flex-direction: column; }
    .evaluation-filter { width: 100%; }
    .evaluation-criteria, .evaluation-two-columns, .progress-overview, .progress-grid, .progress-milestones { grid-template-columns: 1fr; }
}
</style>

<div class="evaluations-page">

    <div class="evaluations-header">
        <div class="evaluations-header-left">
            <div class="evaluations-eyebrow">
                <?= e($eyebrow) ?>
            </div>
            <h1>Intern Evaluations</h1>
            <p>Evaluate and monitor the performance of your interns.</p>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="evaluation-alert error">
            <strong>Please check the following:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="evaluation-alert success">
            <?= e((string)$successMessage) ?>
        </div>
    <?php endif; ?>

    <!-- STATISTICS -->
    <div class="evaluation-stats">
        <div class="evaluation-stat">
            <div class="evaluation-stat-label">Total Students</div>
            <div class="evaluation-stat-value"><?= number_format($totalStudents) ?></div>
            <div class="evaluation-stat-sub">Registered students</div>
        </div>

        <div class="evaluation-stat">
            <div class="evaluation-stat-label">Completed</div>
            <div class="evaluation-stat-value"><?= number_format($completedCount) ?></div>
            <div class="evaluation-stat-sub">Completed evaluations</div>
        </div>

        <div class="evaluation-stat">
            <div class="evaluation-stat-label">Pending</div>
            <div class="evaluation-stat-value"><?= number_format($pendingCount + $draftCount) ?></div>
            <div class="evaluation-stat-sub">Pending or draft</div>
        </div>

        <div class="evaluation-stat">
            <div class="evaluation-stat-label">Average Rating</div>
            <div class="evaluation-stat-value"><?= number_format($averageRating, 2) ?>/5</div>
            <div class="evaluation-stat-sub"><?= number_format($averagePercentage, 1) ?>% equivalent</div>
        </div>
    </div>

    <!-- EVALUATION TABLE -->
    <section class="evaluation-card">
        <div class="evaluation-toolbar">
            <div class="evaluation-search">
                <input type="search" id="evaluationSearch" placeholder="Search student, ID, course, or email..." autocomplete="off">
            </div>
            <select id="evaluationFilter" class="evaluation-filter">
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="Draft">Draft</option>
                <option value="Completed">Completed</option>
            </select>
        </div>

        <div class="evaluation-table-wrap">
            <table class="evaluation-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Student ID</th>
                        <th>Course</th>
                        <th>Overall Rating</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>View</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="evaluationTableBody">
                    <?php if (!$students): ?>
                        <tr>
                            <td colspan="8">
                                <div style="padding:50px; text-align:center; color:var(--qr-text-muted);">
                                    <strong>No students found.</strong>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($students as $student): ?>
                        <?php
                        $name = evaluationStudentName($student);
                        $initials = evaluationInitials($student);
                        $status = $student['evaluation_status'] ?? null;
                        $displayStatus = $status ?: 'Pending';
                        $rating = $student['overall_rating'] ?? null;
                        $percentage = evaluationPercentage($rating);

                        $studentProgress = $studentProgressById[(int)($student['id'] ?? 0)] ?? [
                            'required_hours' => 120,
                            'rendered_hours' => 0.0,
                            'percent' => 0.0,
                        ];

                        $statusClass = 'pending';
                        if ($status === 'Completed') {
                            $statusClass = 'completed';
                        } elseif ($status === 'Draft') {
                            $statusClass = 'draft';
                        }

                        $searchText = strtolower($name . ' ' . ($student['student_id'] ?? '') . ' ' . ($student['course'] ?? '') . ' ' . ($student['institutional_email'] ?? ''));
                        ?>
                        <tr
                            data-search="<?= e($searchText) ?>"
                            data-status="<?= e($status ?: 'pending') ?>"
                        >
                            <td>
                                <div class="evaluation-student">
                                    <div class="evaluation-avatar"><?= e($initials) ?></div>
                                    <div>
                                        <div class="evaluation-student-name"><?= e($name) ?></div>
                                        <div class="evaluation-student-email"><?= e($student['institutional_email'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= e((string)($student['student_id'] ?? '')) ?></td>
                            <td><span class="evaluation-course"><?= e((string)($student['course'] ?? '')) ?></span></td>
                            <td>
                                <?php if ($rating !== null && $rating !== ''): ?>
                                    <div class="evaluation-rating"><?= number_format((float)$rating, 2) ?>/5</div>
                                    <div class="evaluation-rating-percent"><?= number_format($percentage, 1) ?>%</div>
                                <?php else: ?>
                                    <div class="evaluation-rating">—</div>
                                    <div class="evaluation-rating-percent">Not evaluated</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="evaluation-status <?= e($statusClass) ?>">
                                    <?= e($displayStatus) ?>
                                </span>
                            </td>
                            <td><?= e(evaluationDate($student['evaluation_date'] ?? null)) ?></td>
                            <td>
                                <div class="evaluation-row-actions">
                                    <button
                                        type="button"
                                        class="evaluation-progress-button"
                                        data-open-progress
                                        data-student-id="<?= (int)$student['id'] ?>"
                                        data-name="<?= e($name) ?>"
                                        data-student-number="<?= e((string)($student['student_id'] ?? '')) ?>"
                                        data-course="<?= e((string)($student['course'] ?? '')) ?>"
                                        data-email="<?= e((string)($student['institutional_email'] ?? '')) ?>"
                                        data-initials="<?= e($initials) ?>"
                                        data-progress-score="<?= (float)($rating ?? 0) ?>"
                                        data-progress-status="<?= e((string)($status ?? 'Pending')) ?>"
                                        data-progress-rendered-hours="<?= e((string)number_format((float)($studentProgress['rendered_hours'] ?? 0), 2, '.', '')) ?>"
                                        data-progress-required-hours="<?= e((string)number_format((float)($studentProgress['required_hours'] ?? 120), 2, '.', '')) ?>"
                                    >
                                        <i class="fa-regular fa-eye"></i> View Progress
                                    </button>
                                </div>
                            </td>
                            <td>
                                <div class="evaluation-row-actions">
                                    <button
                                        type="button"
                                        class="evaluation-button"
                                        data-open-evaluation
                                        data-student-id="<?= (int)$student['id'] ?>"
                                        data-name="<?= e($name) ?>"
                                        data-student-number="<?= e((string)($student['student_id'] ?? '')) ?>"
                                        data-course="<?= e((string)($student['course'] ?? '')) ?>"
                                        data-email="<?= e((string)($student['institutional_email'] ?? '')) ?>"
                                        data-initials="<?= e($initials) ?>"
                                        data-attendance-punctuality="<?= (int)($student['attendance_punctuality'] ?? 0) ?>"
                                        data-quality-of-work="<?= (int)($student['quality_of_work'] ?? 0) ?>"
                                        data-productivity="<?= (int)($student['productivity'] ?? 0) ?>"
                                        data-communication="<?= (int)($student['communication'] ?? 0) ?>"
                                        data-teamwork="<?= (int)($student['teamwork'] ?? 0) ?>"
                                        data-initiative="<?= (int)($student['initiative'] ?? 0) ?>"
                                        data-professionalism="<?= (int)($student['professionalism'] ?? 0) ?>"
                                        data-adaptability="<?= (int)($student['adaptability'] ?? 0) ?>"
                                        data-problem-solving="<?= (int)($student['problem_solving'] ?? 0) ?>"
                                        data-technical-skills="<?= (int)($student['technical_skills'] ?? 0) ?>"
                                        data-overall-performance="<?= e((string)($student['overall_performance'] ?? '')) ?>"
                                        data-strengths="<?= e((string)($student['strengths'] ?? '')) ?>"
                                        data-areas="<?= e((string)($student['areas_for_improvement'] ?? '')) ?>"
                                        data-comments="<?= e((string)($student['comments'] ?? '')) ?>"
                                        data-recommendation="<?= e((string)($student['recommendation'] ?? '')) ?>"
                                        data-status="<?= e((string)($student['evaluation_status'] ?? '')) ?>"
                                    >
                                        <?= !empty($student['evaluation_id']) ? 'Edit' : 'Evaluate' ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="evaluation-footer">
            <span id="evaluationResultCount">
                Showing <?= number_format($totalStudents) ?> <?= $totalStudents === 1 ? 'student' : 'students' ?>
            </span>
        </div>
    </section>
</div>

<!-- PROGRESS MODAL -->
<div class="progress-modal" id="progressModal" aria-hidden="true">
    <div class="progress-modal-box" role="dialog" aria-modal="true" aria-labelledby="progressModalTitle">
        <div class="progress-modal-header">
            <div>
                <h2 id="progressModalTitle">Student Progress</h2>
                <p>Completion overview and evaluation snapshot</p>
            </div>
            <button type="button" class="progress-close" id="progressClose" aria-label="Close progress modal">×</button>
        </div>

        <div class="progress-modal-body">
            <div class="progress-hero">
                <div class="progress-hero-avatar" id="progressHeroAvatar">ST</div>
                <div class="progress-hero-copy">
                    <div class="progress-hero-name" id="progressHeroName">Student Name</div>
                    <div class="progress-hero-meta" id="progressHeroMeta">Student ID • Course</div>
                </div>
                <span class="progress-status-badge pending" id="progressStatusBadge">Pending</span>
            </div>

            <div class="progress-overview">
                <div class="progress-ring-wrap">
                    <div class="progress-ring" id="progressRing">
                        <div class="progress-ring-value">
                            <strong id="progressPercentValue">0%</strong>
                            <span>Progress</span>
                        </div>
                    </div>
                </div>

                <div class="progress-grid">
                    <div class="progress-metric">
                        <div class="progress-metric-label">Rendered Hours</div>
                        <div class="progress-metric-value" id="progressHoursValue">0.0h</div>
                        <div class="progress-metric-meta" id="progressHoursMeta">Target: 120h</div>
                    </div>
                    <div class="progress-metric">
                        <div class="progress-metric-label">Overall Rating</div>
                        <div class="progress-metric-value" id="progressRatingValue">0.00/5</div>
                        <div class="progress-metric-meta">Based on current evaluation</div>
                    </div>
                    <div class="progress-metric">
                        <div class="progress-metric-label">Milestones</div>
                        <div class="progress-metric-value" id="progressMilestonesValue">0/4</div>
                        <div class="progress-metric-meta">Completed checkpoints</div>
                    </div>
                    <div class="progress-metric">
                        <div class="progress-metric-label">Status</div>
                        <div class="progress-metric-value" id="progressStatusValue">Pending</div>
                        <div class="progress-metric-meta">Evaluation state</div>
                    </div>
                </div>
            </div>

            <div class="progress-section">
                <h3 class="progress-section-title">Completion Progress</h3>
                <div class="progress-rows">
                    <div class="progress-row">
                        <div class="progress-row-head"><span>Orientation</span><span id="progressRow1Label">0%</span></div>
                        <div class="progress-bar-track"><div class="progress-bar-fill" id="progressBar1" style="width:0%"></div></div>
                    </div>
                    <div class="progress-row">
                        <div class="progress-row-head"><span>Work Readiness</span><span id="progressRow2Label">0%</span></div>
                        <div class="progress-bar-track"><div class="progress-bar-fill" id="progressBar2" style="width:0%"></div></div>
                    </div>
                    <div class="progress-row">
                        <div class="progress-row-head"><span>Attendance & Tasks</span><span id="progressRow3Label">0%</span></div>
                        <div class="progress-bar-track"><div class="progress-bar-fill" id="progressBar3" style="width:0%"></div></div>
                    </div>
                    <div class="progress-row">
                        <div class="progress-row-head"><span>Final Review</span><span id="progressRow4Label">0%</span></div>
                        <div class="progress-bar-track"><div class="progress-bar-fill" id="progressBar4" style="width:0%"></div></div>
                    </div>
                </div>
            </div>

            <div class="progress-section">
                <h3 class="progress-section-title">Milestone Tracker</h3>
                <div class="progress-milestones">
                    <div class="progress-milestone pending">Orientation</div>
                    <div class="progress-milestone pending">Training</div>
                    <div class="progress-milestone pending">Task Delivery</div>
                    <div class="progress-milestone pending">Evaluation</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- EVALUATION MODAL -->
<div class="evaluation-modal" id="evaluationModal">
    <div class="evaluation-modal-box">
        <div class="evaluation-modal-header">
            <div>
                <h2>Intern Evaluation</h2>
                <p>Rate the intern from 0 to 5 for each criterion.</p>
            </div>
            <button type="button" class="evaluation-close" id="evaluationClose">×</button>
        </div>

        <form method="post" action="evaluations.php" id="evaluationForm">
            <?= csrf_field() ?>
            <div class="evaluation-modal-body">
                <input type="hidden" name="student_id" id="evaluationStudentId">

                <div class="evaluation-student-info">
                    <div class="evaluation-avatar" id="evaluationAvatar">ST</div>
                    <div>
                        <div class="evaluation-modal-name" id="evaluationStudentName">Student</div>
                        <div class="evaluation-modal-details" id="evaluationStudentDetails"></div>
                    </div>
                </div>

                <div class="evaluation-section">
                    <h3 class="evaluation-section-title">Performance Criteria</h3>
                    <div class="evaluation-criteria">
                        <?php foreach ($criteria as $field => $label): ?>
                            <div class="evaluation-criterion">
                                <label for="eval_<?= e($field) ?>"><?= e($label) ?></label>
                                <select id="eval_<?= e($field) ?>" name="<?= e($field) ?>">
                                    <option value="0">0 — Not Rated</option>
                                    <option value="1">1 — Poor</option>
                                    <option value="2">2 — Needs Improvement</option>
                                    <option value="3">3 — Satisfactory</option>
                                    <option value="4">4 — Very Good</option>
                                    <option value="5">5 — Excellent</option>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="evaluation-section">
                    <h3 class="evaluation-section-title">Overall Assessment</h3>
                    <div class="evaluation-two-columns">
                        <div class="evaluation-field">
                            <label>Overall Performance</label>
                            <select name="overall_performance" id="overallPerformance">
                                <option value="">Select performance</option>
                                <option value="Excellent">Excellent</option>
                                <option value="Very Good">Very Good</option>
                                <option value="Good">Good</option>
                                <option value="Satisfactory">Satisfactory</option>
                                <option value="Needs Improvement">Needs Improvement</option>
                                <option value="Poor">Poor</option>
                            </select>
                        </div>
                        <div class="evaluation-field">
                            <label>Recommendation</label>
                            <select name="recommendation" id="recommendation">
                                <option value="">Select recommendation</option>
                                <option value="Highly Recommended">Highly Recommended</option>
                                <option value="Recommended">Recommended</option>
                                <option value="Recommended with Improvement">Recommended with Improvement</option>
                                <option value="Not Recommended">Not Recommended</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="evaluation-section">
                    <h3 class="evaluation-section-title">Written Evaluation</h3>
                    <div class="evaluation-field">
                        <label>Strengths</label>
                        <textarea name="strengths" id="strengths" maxlength="10000" placeholder="Describe the intern's strengths..."></textarea>
                    </div>
                    <div class="evaluation-field">
                        <label>Areas for Improvement</label>
                        <textarea name="areas_for_improvement" id="areasForImprovement" maxlength="10000" placeholder="Describe areas for improvement..."></textarea>
                    </div>
                    <div class="evaluation-field">
                        <label>Comments</label>
                        <textarea name="comments" id="comments" maxlength="10000" placeholder="Additional comments..."></textarea>
                    </div>
                </div>

                <div class="evaluation-section">
                    <div class="evaluation-field">
                        <label>Status</label>
                        <select name="status" id="evaluationStatus">
                            <option value="Completed">Completed</option>
                            <option value="Draft">Draft</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="evaluation-modal-footer">
                <button type="button" class="evaluation-cancel" id="evaluationCancel">Cancel</button>
                <button type="submit" class="evaluation-save">Save Evaluation</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    'use strict';

    const modal = document.getElementById('evaluationModal');
    const closeButton = document.getElementById('evaluationClose');
    const cancelButton = document.getElementById('evaluationCancel');
    const form = document.getElementById('evaluationForm');
    const studentId = document.getElementById('evaluationStudentId');
    const studentName = document.getElementById('evaluationStudentName');
    const studentDetails = document.getElementById('evaluationStudentDetails');
    const avatar = document.getElementById('evaluationAvatar');
    const search = document.getElementById('evaluationSearch');
    const filter = document.getElementById('evaluationFilter');
    const resultCount = document.getElementById('evaluationResultCount');

    const criteria = [
        'attendance_punctuality',
        'quality_of_work',
        'productivity',
        'communication',
        'teamwork',
        'initiative',
        'professionalism',
        'adaptability',
        'problem_solving',
        'technical_skills'
    ];

    function snakeToCamel(value) {
        return value.replace(/_([a-z])/g, function (_, letter) {
            return letter.toUpperCase();
        });
    }

    document.querySelectorAll('[data-open-evaluation]').forEach(function (button) {
        button.addEventListener('click', function () {
            const data = button.dataset;
            studentId.value = data.studentId || '';
            studentName.textContent = data.name || 'Student';

            let details = '';
            if (data.studentNumber) details += data.studentNumber;
            if (data.course) {
                if (details !== '') details += ' • ';
                details += data.course;
            }
            if (data.email) {
                if (details !== '') details += ' • ';
                details += data.email;
            }
            studentDetails.textContent = details;
            avatar.textContent = data.initials || 'ST';

            criteria.forEach(function (field) {
                const element = document.getElementById('eval_' + field);
                if (!element) return;
                const datasetName = snakeToCamel(field);
                element.value = data[datasetName] ?? '0';
            });

            document.getElementById('overallPerformance').value = data.overallPerformance || '';
            document.getElementById('strengths').value = data.strengths || '';
            document.getElementById('areasForImprovement').value = data.areas || '';
            document.getElementById('comments').value = data.comments || '';
            document.getElementById('recommendation').value = data.recommendation || '';

            const savedStatus = data.status;
            document.getElementById('evaluationStatus').value = (savedStatus === 'Draft' || savedStatus === 'Completed') ? savedStatus : 'Completed';

            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        });
    });

    function closeModal() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    closeButton.addEventListener('click', closeModal);
    cancelButton.addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (modal.classList.contains('open')) closeModal();
            if (progressModal.classList.contains('open')) closeProgressModal();
        }
    });

    const progressModal = document.getElementById('progressModal');
    const progressClose = document.getElementById('progressClose');
    const progressName = document.getElementById('progressHeroName');
    const progressMeta = document.getElementById('progressHeroMeta');
    const progressAvatar = document.getElementById('progressHeroAvatar');
    const progressStatusBadge = document.getElementById('progressStatusBadge');
    const progressPercentValue = document.getElementById('progressPercentValue');
    const progressRing = document.getElementById('progressRing');
    const progressHoursValue = document.getElementById('progressHoursValue');
    const progressHoursMeta = document.getElementById('progressHoursMeta');
    const progressRatingValue = document.getElementById('progressRatingValue');
    const progressStatusValue = document.getElementById('progressStatusValue');
    const progressMilestonesValue = document.getElementById('progressMilestonesValue');

    const progressBars = [
        document.getElementById('progressBar1'),
        document.getElementById('progressBar2'),
        document.getElementById('progressBar3'),
        document.getElementById('progressBar4')
    ];

    const progressLabels = [
        document.getElementById('progressRow1Label'),
        document.getElementById('progressRow2Label'),
        document.getElementById('progressRow3Label'),
        document.getElementById('progressRow4Label')
    ];

    const progressMilestones = document.querySelectorAll('.progress-milestone');

    function closeProgressModal() {
        progressModal.classList.remove('open');
        document.body.style.overflow = '';
    }

    function setProgressState(button) {
        const evaluationScore = Number(button.dataset.progressScore || 0);
        const status = (button.dataset.progressStatus || 'Pending').trim() || 'Pending';
        const renderedHours = Number(button.dataset.progressRenderedHours || 0);
        const requiredHours = Number(button.dataset.progressRequiredHours || 120);
        const safeRequiredHours = requiredHours > 0 ? requiredHours : 120;
        const rawPercent = Math.min(100, Math.max(0, (renderedHours / safeRequiredHours) * 100));
        const progressWeights = [
            Math.min(100, rawPercent * 0.7),
            Math.min(100, rawPercent * 0.85),
            Math.min(100, rawPercent * 1),
            Math.min(100, rawPercent * 1.15)
        ];
        const completedCount = progressWeights.filter(v => v >= 100).length;

        progressName.textContent = button.dataset.name || 'Student';
        progressMeta.textContent = [button.dataset.studentNumber, button.dataset.course].filter(Boolean).join(' • ') || 'Student profile';
        progressAvatar.textContent = button.dataset.initials || 'ST';

        const statusClass = status === 'Completed' ? 'completed' : status === 'Draft' ? 'draft' : 'pending';
        progressStatusBadge.className = 'progress-status-badge ' + statusClass;
        progressStatusBadge.textContent = status || 'Pending';
        progressPercentValue.textContent = Math.round(rawPercent) + '%';
        progressRing.style.setProperty('--progress-angle', (rawPercent / 100) * 360 + 'deg');
        progressHoursValue.textContent = renderedHours.toFixed(1) + 'h';
        progressHoursMeta.textContent = 'Target: ' + safeRequiredHours.toFixed(0) + 'h';
        progressRatingValue.textContent = Number(evaluationScore || 0).toFixed(2) + '/5';
        progressStatusValue.textContent = status || 'Pending';
        progressMilestonesValue.textContent = completedCount + '/4';

        progressBars.forEach(function (bar, index) {
            bar.style.width = Math.min(100, Math.max(0, progressWeights[index] || 0)) + '%';
        });

        progressLabels.forEach(function (label, index) {
            label.textContent = Math.round(progressWeights[index] || 0) + '%';
        });

        progressMilestones.forEach(function (milestone, index) {
            const value = progressWeights[index] || 0;
            milestone.classList.remove('done', 'active');
            if (value >= 100) milestone.classList.add('done');
            else if (value >= 45) milestone.classList.add('active');
        });
    }

    function refreshProgressData(button) {
        const studentId = button.dataset.studentId;
        if (!studentId) {
            setProgressState(button);
            return;
        }

        fetch('evaluations.php?progress_student_id=' + encodeURIComponent(studentId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data && typeof data === 'object') {
                if (data.rendered_hours !== undefined) button.dataset.progressRenderedHours = Number(data.rendered_hours).toFixed(2);
                if (data.required_hours !== undefined) button.dataset.progressRequiredHours = Number(data.required_hours).toFixed(2);
                if (data.status !== undefined) button.dataset.progressStatus = data.status;
                if (data.rating !== undefined) button.dataset.progressScore = Number(data.rating).toFixed(2);
            }
            setProgressState(button);
        })
        .catch(() => setProgressState(button));
    }

    document.querySelectorAll('[data-open-progress]').forEach(function (button) {
        button.addEventListener('click', function () {
            refreshProgressData(button);
            progressModal.classList.add('open');
            progressModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        });
    });

    progressClose.addEventListener('click', closeProgressModal);
    progressModal.addEventListener('click', function (event) {
        if (event.target === progressModal) closeProgressModal();
    });

    function filterTable() {
        const searchValue = (search.value || '').toLowerCase().trim();
        const filterValue = filter.value;
        const rows = document.querySelectorAll('#evaluationTableBody tr[data-search]');
        let visible = 0;

        rows.forEach(function (row) {
            const rowSearch = (row.dataset.search || '').toLowerCase();
            const rowStatus = row.dataset.status || 'pending';
            const matchesSearch = searchValue === '' || rowSearch.includes(searchValue);
            let matchesStatus = true;

            if (filterValue !== 'all') {
                if (filterValue === 'pending') {
                    matchesStatus = rowStatus === 'pending';
                } else {
                    matchesStatus = rowStatus === filterValue;
                }
            }

            const show = matchesSearch && matchesStatus;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        resultCount.textContent = 'Showing ' + visible + ' ' + (visible === 1 ? 'student' : 'students');
    }

    search.addEventListener('input', filterTable);
    filter.addEventListener('change', filterTable);

    form.addEventListener('submit', function (event) {
        if (!studentId.value) {
            event.preventDefault();
            alert('Please select a student.');
            return;
        }

        for (let i = 0; i < criteria.length; i++) {
            const element = document.getElementById('eval_' + criteria[i]);
            const value = Number(element.value);
            if (!Number.isInteger(value) || value < 0 || value > 5) {
                event.preventDefault();
                alert('Each rating must be between 0 and 5.');
                element.focus();
                return;
            }
        }

        const status = document.getElementById('evaluationStatus').value;
        if (status === 'Completed') {
            const performance = document.getElementById('overallPerformance').value;
            const recommendation = document.getElementById('recommendation').value;
            if (!performance) {
                event.preventDefault();
                alert('Please select the overall performance.');
                return;
            }
            if (!recommendation) {
                event.preventDefault();
                alert('Please select the recommendation.');
                return;
            }
        }
    });

    filterTable();
})();
</script>

<?php
include __DIR__ . '/includes/layout_bottom.php';
?>