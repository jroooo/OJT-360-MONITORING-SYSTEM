<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$company = require_company();
$pdo = db();

$pageKey = 'evaluations';
$pageTitle = 'Evaluations';

$errors = [];
$successMessage = flash_get('success') ?? null;

/*
|--------------------------------------------------------------------------
| EVALUATION CRITERIA
|--------------------------------------------------------------------------
| Based on the intern_evaluations table.
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
    'initiative'              => '0',
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

    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    $status = trim(
        (string)($_POST['status'] ?? 'Completed')
    );

    if (!in_array($status, ['Draft', 'Completed'], true)) {
        $status = 'Completed';
    }

    $oldInput['status'] = $status;

    /*
    |--------------------------------------------------------------------------
    | RATINGS
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | TEXT FIELDS
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | VALIDATE STUDENT
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | TEXT LENGTH VALIDATION
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | COMPLETED VALIDATION
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | SAVE TO intern_evaluations
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        try {

            /*
            |--------------------------------------------------------------------------
            | CALCULATE OVERALL RATING
            |--------------------------------------------------------------------------
            */

            $ratingTotal = 0;

            foreach ($criteria as $field => $label) {

                $ratingTotal +=
                    (int)$oldInput[$field];
            }

            $overallRating =
                round($ratingTotal / 10, 2);

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | FIND LATEST EVALUATION FOR STUDENT
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | EVALUATOR ID
            |--------------------------------------------------------------------------
            |
            | evaluator_id is nullable according to the table.
            |
            */

            $evaluatorId = null;

            /*
            |--------------------------------------------------------------------------
            | UPDATE EXISTING EVALUATION
            |--------------------------------------------------------------------------
            */

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

            }

            /*
            |--------------------------------------------------------------------------
            | INSERT NEW EVALUATION
            |--------------------------------------------------------------------------
            */

            else {

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

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function evaluationStudentName(
    array $student
): string {

    $parts = [];

    foreach (
        ['first_name', 'middle_name', 'last_name']
        as $field
    ) {

        if (!empty($student[$field])) {

            $parts[] =
                trim(
                    (string)$student[$field]
                );
        }
    }

    return trim(
        implode(' ', $parts)
    );
}

function evaluationInitials(
    array $student
): string {

    $first =
        trim(
            (string)(
                $student['first_name'] ?? ''
            )
        );

    $last =
        trim(
            (string)(
                $student['last_name'] ?? ''
            )
        );

    $initials = '';

    if ($first !== '') {

        $initials .=
            strtoupper(
                substr($first, 0, 1)
            );
    }

    if ($last !== '') {

        $initials .=
            strtoupper(
                substr($last, 0, 1)
            );
    }

    return $initials ?: 'ST';
}

function evaluationPercentage(
    $rating
): float {

    if (
        $rating === null ||
        $rating === ''
    ) {

        return 0;
    }

    return round(
        ((float)$rating / 5) * 100,
        1
    );
}

function evaluationDate(
    $date
): string {

    if (!$date) {
        return '—';
    }

    $timestamp =
        strtotime(
            (string)$date
        );

    if (!$timestamp) {
        return (string)$date;
    }

    return date(
        'M d, Y',
        $timestamp
    );
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalStudents =
    count($students);

$completedCount = 0;
$draftCount = 0;
$pendingCount = 0;

$ratingTotal = 0;
$ratingCount = 0;

foreach ($students as $student) {

    $status =
        $student['evaluation_status']
        ?? null;

    if ($status === 'Completed') {

        $completedCount++;

        if (
            $student['overall_rating'] !== null &&
            $student['overall_rating'] !== ''
        ) {

            $ratingTotal +=
                (float)$student['overall_rating'];

            $ratingCount++;
        }

    } elseif ($status === 'Draft') {

        $draftCount++;

    } else {

        $pendingCount++;
    }
}

$averageRating =
    $ratingCount > 0
        ? round(
            $ratingTotal / $ratingCount,
            2
        )
        : 0;

$averagePercentage =
    round(
        ($averageRating / 5) * 100,
        1
    );

include __DIR__ . '/includes/layout_top.php';

?>

<style>

.evaluations-page {
    width: 100%;
    padding: 24px;
    box-sizing: border-box;
}

.evaluations-header {
    margin-bottom: 24px;
}

.evaluations-header h1 {
    margin: 0;
    font-size: 30px;
    font-weight: 800;
    color: #0f172a;
}

.evaluations-header p {
    margin: 7px 0 0;
    color: #64748b;
    font-size: 14px;
}

.evaluation-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 22px;
}

.evaluation-stat {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 18px;
}

.evaluation-stat-label {
    color: #64748b;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
}

.evaluation-stat-value {
    margin-top: 8px;
    font-size: 28px;
    font-weight: 800;
    color: #0f172a;
}

.evaluation-stat-sub {
    margin-top: 6px;
    color: #94a3b8;
    font-size: 12px;
}

.evaluation-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    overflow: hidden;
}

.evaluation-toolbar {
    display: flex;
    gap: 12px;
    padding: 18px;
    border-bottom: 1px solid #e2e8f0;
}

.evaluation-search {
    flex: 1;
}

.evaluation-search input,
.evaluation-filter {
    width: 100%;
    height: 44px;
    box-sizing: border-box;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 0 13px;
    background: #fff;
    color: #0f172a;
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
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 800;
    text-align: left;
    text-transform: uppercase;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}

.evaluation-table td {
    padding: 15px 16px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    font-size: 13px;
    vertical-align: middle;
}

.evaluation-student {
    display: flex;
    align-items: center;
    gap: 11px;
}

.evaluation-avatar {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e0e7ff;
    color: #3730a3;
    font-size: 12px;
    font-weight: 800;
}

.evaluation-student-name {
    font-weight: 800;
    color: #0f172a;
}

.evaluation-student-email {
    margin-top: 3px;
    color: #94a3b8;
    font-size: 11px;
}

.evaluation-course {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 7px;
    background: #f1f5f9;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
}

.evaluation-rating {
    font-weight: 800;
    color: #0f172a;
}

.evaluation-rating-percent {
    margin-top: 3px;
    color: #64748b;
    font-size: 11px;
}

.evaluation-status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
}

.evaluation-status.completed {
    background: #dcfce7;
    color: #166534;
}

.evaluation-status.draft {
    background: #fef3c7;
    color: #92400e;
}

.evaluation-status.pending {
    background: #f1f5f9;
    color: #64748b;
}

.evaluation-button {
    border: 0;
    border-radius: 9px;
    padding: 9px 13px;
    background: #0f172a;
    color: #fff;
    cursor: pointer;
    font-size: 12px;
    font-weight: 800;
}

.evaluation-button:hover {
    background: #1e293b;
}

.evaluation-footer {
    padding: 15px 18px;
    color: #64748b;
    font-size: 12px;
}

/*
|--------------------------------------------------------------------------
| MODAL
|--------------------------------------------------------------------------
*/

.evaluation-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(15, 23, 42, .60);
    padding: 20px;
    overflow-y: auto;
}

.evaluation-modal.open {
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

.evaluation-modal-box {
    width: min(900px, 100%);
    margin: 25px auto;
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(0, 0, 0, .25);
}

.evaluation-modal-header {
    display: flex;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
}

.evaluation-modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: #0f172a;
}

.evaluation-modal-header p {
    margin: 5px 0 0;
    color: #64748b;
    font-size: 12px;
}

.evaluation-close {
    width: 35px;
    height: 35px;
    border: 0;
    border-radius: 8px;
    background: #f1f5f9;
    color: #475569;
    font-size: 21px;
    cursor: pointer;
}

.evaluation-modal-body {
    padding: 24px;
}

.evaluation-student-info {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 14px;
    margin-bottom: 24px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 13px;
}

.evaluation-modal-name {
    font-weight: 800;
    color: #0f172a;
}

.evaluation-modal-details {
    margin-top: 4px;
    color: #64748b;
    font-size: 12px;
}

.evaluation-section {
    margin-top: 24px;
}

.evaluation-section-title {
    margin: 0 0 13px;
    color: #0f172a;
    font-size: 14px;
    font-weight: 800;
}

.evaluation-criteria {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.evaluation-criterion {
    padding: 13px;
    border: 1px solid #e2e8f0;
    border-radius: 11px;
}

.evaluation-criterion label,
.evaluation-field label {
    display: block;
    margin-bottom: 7px;
    color: #334155;
    font-size: 12px;
    font-weight: 700;
}

.evaluation-criterion select,
.evaluation-field select,
.evaluation-field textarea {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    padding: 10px;
    color: #0f172a;
    background: #fff;
    font: inherit;
    font-size: 13px;
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
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.evaluation-cancel,
.evaluation-save {
    border: 0;
    border-radius: 9px;
    padding: 10px 17px;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}

.evaluation-cancel {
    background: #e2e8f0;
    color: #334155;
}

.evaluation-save {
    background: #0f172a;
    color: #fff;
}

.evaluation-alert {
    padding: 13px 15px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: 13px;
}

.evaluation-alert.error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.evaluation-alert.success {
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.evaluation-alert ul {
    margin: 7px 0 0;
    padding-left: 20px;
}

@media (max-width: 900px) {

    .evaluation-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 650px) {

    .evaluations-page {
        padding: 15px;
    }

    .evaluation-stats {
        grid-template-columns: 1fr;
    }

    .evaluation-toolbar {
        flex-direction: column;
    }

    .evaluation-filter {
        width: 100%;
    }

    .evaluation-criteria,
    .evaluation-two-columns {
        grid-template-columns: 1fr;
    }
}

</style>


<div class="evaluations-page">

    <div class="evaluations-header">

        <h1>Intern Evaluations</h1>

        <p>
            Evaluate and monitor the performance of your interns.
        </p>

    </div>


    <?php if ($errors): ?>

        <div class="evaluation-alert error">

            <strong>
                Please check the following:
            </strong>

            <ul>

                <?php foreach ($errors as $error): ?>

                    <li>
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <?php if ($successMessage): ?>

        <div class="evaluation-alert success">

            <?= htmlspecialchars(
                (string)$successMessage,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

    <?php endif; ?>


    <!-- =========================================================
         STATISTICS
    ========================================================== -->

    <div class="evaluation-stats">

        <div class="evaluation-stat">

            <div class="evaluation-stat-label">
                Total Students
            </div>

            <div class="evaluation-stat-value">
                <?= number_format($totalStudents) ?>
            </div>

            <div class="evaluation-stat-sub">
                Registered students
            </div>

        </div>


        <div class="evaluation-stat">

            <div class="evaluation-stat-label">
                Completed
            </div>

            <div class="evaluation-stat-value">
                <?= number_format($completedCount) ?>
            </div>

            <div class="evaluation-stat-sub">
                Completed evaluations
            </div>

        </div>


        <div class="evaluation-stat">

            <div class="evaluation-stat-label">
                Pending
            </div>

            <div class="evaluation-stat-value">
                <?= number_format(
                    $pendingCount + $draftCount
                ) ?>
            </div>

            <div class="evaluation-stat-sub">
                Pending or draft
            </div>

        </div>


        <div class="evaluation-stat">

            <div class="evaluation-stat-label">
                Average Rating
            </div>

            <div class="evaluation-stat-value">
                <?= number_format(
                    $averageRating,
                    2
                ) ?>/5
            </div>

            <div class="evaluation-stat-sub">
                <?= number_format(
                    $averagePercentage,
                    1
                ) ?>% equivalent
            </div>

        </div>

    </div>


    <!-- =========================================================
         EVALUATION TABLE
    ========================================================== -->

    <section class="evaluation-card">

        <div class="evaluation-toolbar">

            <div class="evaluation-search">

                <input
                    type="search"
                    id="evaluationSearch"
                    placeholder="Search student, ID, course, or email..."
                    autocomplete="off"
                >

            </div>


            <select
                id="evaluationFilter"
                class="evaluation-filter"
            >

                <option value="all">
                    All Status
                </option>

                <option value="pending">
                    Pending
                </option>

                <option value="Draft">
                    Draft
                </option>

                <option value="Completed">
                    Completed
                </option>

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
                        <th>Action</th>

                    </tr>

                </thead>


                <tbody id="evaluationTableBody">

                    <?php if (!$students): ?>

                        <tr>

                            <td colspan="7">

                                <div
                                    style="
                                        padding:50px;
                                        text-align:center;
                                        color:#64748b;
                                    "
                                >

                                    <strong>
                                        No students found.
                                    </strong>

                                </div>

                            </td>

                        </tr>

                    <?php endif; ?>


                    <?php foreach ($students as $student): ?>

                        <?php

                        $name =
                            evaluationStudentName(
                                $student
                            );

                        $initials =
                            evaluationInitials(
                                $student
                            );

                        $status =
                            $student[
                                'evaluation_status'
                            ] ?? null;

                        $displayStatus =
                            $status ?: 'Pending';

                        $rating =
                            $student[
                                'overall_rating'
                            ] ?? null;

                        $percentage =
                            evaluationPercentage(
                                $rating
                            );

                        $statusClass = 'pending';

                        if ($status === 'Completed') {

                            $statusClass = 'completed';

                        } elseif ($status === 'Draft') {

                            $statusClass = 'draft';
                        }

                        $searchText =
                            strtolower(
                                $name . ' ' .
                                ($student['student_id'] ?? '') . ' ' .
                                ($student['course'] ?? '') . ' ' .
                                ($student['institutional_email'] ?? '')
                            );

                        ?>


                        <!-- FIXED: only ONE closing > -->

                        <tr
                            data-search="<?= htmlspecialchars(
                                $searchText,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            data-status="<?= htmlspecialchars(
                                $status ?: 'pending',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                            <td>

                                <div class="evaluation-student">

                                    <div class="evaluation-avatar">

                                        <?= htmlspecialchars(
                                            $initials,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </div>


                                    <div>

                                        <div class="evaluation-student-name">

                                            <?= htmlspecialchars(
                                                $name,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </div>


                                        <div class="evaluation-student-email">

                                            <?= htmlspecialchars(
                                                $student[
                                                    'institutional_email'
                                                ] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </div>

                                    </div>

                                </div>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    (string)(
                                        $student['student_id'] ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <td>

                                <span class="evaluation-course">

                                    <?= htmlspecialchars(
                                        (string)(
                                            $student['course'] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?php if (
                                    $rating !== null &&
                                    $rating !== ''
                                ): ?>

                                    <div class="evaluation-rating">

                                        <?= number_format(
                                            (float)$rating,
                                            2
                                        ) ?>/5

                                    </div>

                                    <div class="evaluation-rating-percent">

                                        <?= number_format(
                                            $percentage,
                                            1
                                        ) ?>%

                                    </div>

                                <?php else: ?>

                                    <div class="evaluation-rating">
                                        —
                                    </div>

                                    <div class="evaluation-rating-percent">
                                        Not evaluated
                                    </div>

                                <?php endif; ?>

                            </td>


                            <td>

                                <span
                                    class="
                                        evaluation-status
                                        <?= htmlspecialchars(
                                            $statusClass,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    "
                                >

                                    <?= htmlspecialchars(
                                        $displayStatus,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    evaluationDate(
                                        $student[
                                            'evaluation_date'
                                        ] ?? null
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <td>

                                <button
                                    type="button"
                                    class="evaluation-button"
                                    data-open-evaluation

                                    data-student-id="<?= (int)$student['id'] ?>"

                                    data-name="<?= htmlspecialchars(
                                        $name,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"

                                    data-student-number="<?= htmlspecialchars(
                                        (string)(
                                            $student['student_id'] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"

                                    data-course="<?= htmlspecialchars(
                                        (string)(
                                            $student['course'] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"

                                    data-email="<?= htmlspecialchars(
                                        (string)(
                                            $student[
                                                'institutional_email'
                                            ] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"

                                    data-initials="<?= htmlspecialchars(
                                        $initials,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"

                                    data-attendance-punctuality="<?= (int)(
                                        $student[
                                            'attendance_punctuality'
                                        ] ?? 0
                                    ) ?>"

                                    data-quality-of-work="<?= (int)(
                                        $student[
                                            'quality_of_work'
                                        ] ?? 0
                                    ) ?>"

                                    data-productivity="<?= (int)(
                                        $student[
                                            'productivity'
                                        ] ?? 0
                                    ) ?>"

                                    data-communication="<?= (int)(
                                        $student[
                                            'communication'
                                        ] ?? 0
                                    ) ?>"

                                    data-teamwork="<?= (int)(
                                        $student[
                                            'teamwork'
                                        ] ?? 0
                                    ) ?>"

                                    data-initiative="<?= (int)(
                                        $student[
                                            'initiative'
                                        ] ?? 0
                                    ) ?>"

                                    data-professionalism="<?= (int)(
                                        $student[
                                            'professionalism'
                                        ] ?? 0
                                    ) ?>"

                                    data-adaptability="<?= (int)(
                                        $student[
                                            'adaptability'
                                        ] ?? 0
                                    ) ?>"

                                    data-problem-solving="<?= (int)(
                                        $student[
                                            'problem_solving'
                                        ] ?? 0
                                    ) ?>"

                                    data-technical-skills="<?= (int)(
                                        $student[
                                            'technical_skills'
                                        ] ?? 0
                                    ) ?>"

                                    data-overall-performance="<?= htmlspecialchars(
                                        (string)(
                                            $student[
                                                'overall_performance'
                                            ] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"

                                    data-strengths="<?= htmlspecialchars(
                                        (string)(
                                            $student[
                                                'strengths'
                                            ] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"

                                    data-areas="<?= htmlspecialchars(
                                        (string)(
                                            $student[
                                                'areas_for_improvement'
                                            ] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"

                                    data-comments="<?= htmlspecialchars(
                                        (string)(
                                            $student[
                                                'comments'
                                            ] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"

                                    data-recommendation="<?= htmlspecialchars(
                                        (string)(
                                            $student[
                                                'recommendation'
                                            ] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"

                                    data-status="<?= htmlspecialchars(
                                        (string)(
                                            $student[
                                                'evaluation_status'
                                            ] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                                    <?= !empty(
                                        $student['evaluation_id']
                                    )
                                        ? 'Edit'
                                        : 'Evaluate' ?>

                                </button>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>


        <div class="evaluation-footer">

            <span id="evaluationResultCount">

                Showing
                <?= number_format($totalStudents) ?>
                <?= $totalStudents === 1
                    ? 'student'
                    : 'students' ?>

            </span>

        </div>

    </section>

</div>


<!-- =========================================================
     EVALUATION MODAL
========================================================== -->

<div
    class="evaluation-modal"
    id="evaluationModal"
>

    <div class="evaluation-modal-box">


        <div class="evaluation-modal-header">

            <div>

                <h2>
                    Intern Evaluation
                </h2>

                <p>
                    Rate the intern from 0 to 5 for each criterion.
                </p>

            </div>


            <button
                type="button"
                class="evaluation-close"
                id="evaluationClose"
            >
                ×
            </button>

        </div>


        <form
            method="post"
            action="evaluations.php"
            id="evaluationForm"
        >

            <?= csrf_field() ?>


            <div class="evaluation-modal-body">


                <input
                    type="hidden"
                    name="student_id"
                    id="evaluationStudentId"
                >


                <div class="evaluation-student-info">

                    <div
                        class="evaluation-avatar"
                        id="evaluationAvatar"
                    >
                        ST
                    </div>


                    <div>

                        <div
                            class="evaluation-modal-name"
                            id="evaluationStudentName"
                        >
                            Student
                        </div>


                        <div
                            class="evaluation-modal-details"
                            id="evaluationStudentDetails"
                        ></div>

                    </div>

                </div>


                <!-- PERFORMANCE CRITERIA -->

                <div class="evaluation-section">

                    <h3 class="evaluation-section-title">
                        Performance Criteria
                    </h3>


                    <div class="evaluation-criteria">

                        <?php foreach (
                            $criteria as $field => $label
                        ): ?>

                            <div class="evaluation-criterion">

                                <label
                                    for="eval_<?= htmlspecialchars(
                                        $field,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        $label,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </label>


                                <select
                                    id="eval_<?= htmlspecialchars(
                                        $field,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    name="<?= htmlspecialchars(
                                        $field,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                                    <option value="0">
                                        0 — Not Rated
                                    </option>

                                    <option value="1">
                                        1 — Poor
                                    </option>

                                    <option value="2">
                                        2 — Needs Improvement
                                    </option>

                                    <option value="3">
                                        3 — Satisfactory
                                    </option>

                                    <option value="4">
                                        4 — Very Good
                                    </option>

                                    <option value="5">
                                        5 — Excellent
                                    </option>

                                </select>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>


                <!-- OVERALL ASSESSMENT -->

                <div class="evaluation-section">

                    <h3 class="evaluation-section-title">
                        Overall Assessment
                    </h3>


                    <div class="evaluation-two-columns">


                        <div class="evaluation-field">

                            <label>
                                Overall Performance
                            </label>


                            <select
                                name="overall_performance"
                                id="overallPerformance"
                            >

                                <option value="">
                                    Select performance
                                </option>

                                <option value="Excellent">
                                    Excellent
                                </option>

                                <option value="Very Good">
                                    Very Good
                                </option>

                                <option value="Good">
                                    Good
                                </option>

                                <option value="Satisfactory">
                                    Satisfactory
                                </option>

                                <option value="Needs Improvement">
                                    Needs Improvement
                                </option>

                                <option value="Poor">
                                    Poor
                                </option>

                            </select>

                        </div>


                        <div class="evaluation-field">

                            <label>
                                Recommendation
                            </label>


                            <select
                                name="recommendation"
                                id="recommendation"
                            >

                                <option value="">
                                    Select recommendation
                                </option>

                                <option value="Highly Recommended">
                                    Highly Recommended
                                </option>

                                <option value="Recommended">
                                    Recommended
                                </option>

                                <option value="Recommended with Improvement">
                                    Recommended with Improvement
                                </option>

                                <option value="Not Recommended">
                                    Not Recommended
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <!-- WRITTEN EVALUATION -->

                <div class="evaluation-section">

                    <h3 class="evaluation-section-title">
                        Written Evaluation
                    </h3>


                    <div class="evaluation-field">

                        <label>
                            Strengths
                        </label>


                        <textarea
                            name="strengths"
                            id="strengths"
                            maxlength="10000"
                            placeholder="Describe the intern's strengths..."
                        ></textarea>

                    </div>


                    <div class="evaluation-field">

                        <label>
                            Areas for Improvement
                        </label>


                        <textarea
                            name="areas_for_improvement"
                            id="areasForImprovement"
                            maxlength="10000"
                            placeholder="Describe areas for improvement..."
                        ></textarea>

                    </div>


                    <div class="evaluation-field">

                        <label>
                            Comments
                        </label>


                        <textarea
                            name="comments"
                            id="comments"
                            maxlength="10000"
                            placeholder="Additional comments..."
                        ></textarea>

                    </div>

                </div>


                <!-- STATUS -->

                <div class="evaluation-section">

                    <div class="evaluation-field">

                        <label>
                            Status
                        </label>


                        <select
                            name="status"
                            id="evaluationStatus"
                        >

                            <option value="Completed">
                                Completed
                            </option>

                            <option value="Draft">
                                Draft
                            </option>

                        </select>

                    </div>

                </div>

            </div>


            <!-- MODAL FOOTER -->

            <div class="evaluation-modal-footer">

                <button
                    type="button"
                    class="evaluation-cancel"
                    id="evaluationCancel"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="evaluation-save"
                >
                    Save Evaluation
                </button>

            </div>

        </form>

    </div>

</div>


<script>

(function () {

    'use strict';


    /*
    |--------------------------------------------------------------------------
    | ELEMENTS
    |--------------------------------------------------------------------------
    */

    const modal =
        document.getElementById(
            'evaluationModal'
        );

    const closeButton =
        document.getElementById(
            'evaluationClose'
        );

    const cancelButton =
        document.getElementById(
            'evaluationCancel'
        );

    const form =
        document.getElementById(
            'evaluationForm'
        );

    const studentId =
        document.getElementById(
            'evaluationStudentId'
        );

    const studentName =
        document.getElementById(
            'evaluationStudentName'
        );

    const studentDetails =
        document.getElementById(
            'evaluationStudentDetails'
        );

    const avatar =
        document.getElementById(
            'evaluationAvatar'
        );

    const search =
        document.getElementById(
            'evaluationSearch'
        );

    const filter =
        document.getElementById(
            'evaluationFilter'
        );

    const resultCount =
        document.getElementById(
            'evaluationResultCount'
        );


    /*
    |--------------------------------------------------------------------------
    | CRITERIA
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | SNAKE CASE -> CAMEL CASE
    |--------------------------------------------------------------------------
    */

    function snakeToCamel(value) {

        return value.replace(
            /_([a-z])/g,
            function (_, letter) {

                return letter.toUpperCase();

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | OPEN MODAL
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '[data-open-evaluation]'
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        const data =
                            button.dataset;


                        /*
                        | STUDENT INFORMATION
                        */

                        studentId.value =
                            data.studentId || '';


                        studentName.textContent =
                            data.name || 'Student';


                        let details = '';

                        if (data.studentNumber) {

                            details +=
                                data.studentNumber;
                        }

                        if (data.course) {

                            if (details !== '') {
                                details += ' • ';
                            }

                            details +=
                                data.course;
                        }

                        if (data.email) {

                            if (details !== '') {
                                details += ' • ';
                            }

                            details +=
                                data.email;
                        }

                        studentDetails.textContent =
                            details;


                        avatar.textContent =
                            data.initials || 'ST';


                        /*
                        |--------------------------------------------------------------------------
                        | LOAD RATINGS
                        |--------------------------------------------------------------------------
                        */

                        criteria.forEach(
                            function (field) {

                                const element =
                                    document.getElementById(
                                        'eval_' + field
                                    );

                                if (!element) {
                                    return;
                                }


                                const datasetName =
                                    snakeToCamel(
                                        field
                                    );


                                element.value =
                                    data[
                                        datasetName
                                    ] ?? '0';

                            }
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | LOAD TEXT FIELDS
                        |--------------------------------------------------------------------------
                        */

                        document.getElementById(
                            'overallPerformance'
                        ).value =
                            data.overallPerformance || '';


                        document.getElementById(
                            'strengths'
                        ).value =
                            data.strengths || '';


                        document.getElementById(
                            'areasForImprovement'
                        ).value =
                            data.areas || '';


                        document.getElementById(
                            'comments'
                        ).value =
                            data.comments || '';


                        document.getElementById(
                            'recommendation'
                        ).value =
                            data.recommendation || '';


                        /*
                        |--------------------------------------------------------------------------
                        | LOAD STATUS
                        |--------------------------------------------------------------------------
                        */

                        const savedStatus =
                            data.status;


                        document.getElementById(
                            'evaluationStatus'
                        ).value =
                            savedStatus === 'Draft' ||
                            savedStatus === 'Completed'
                                ? savedStatus
                                : 'Completed';


                        /*
                        |--------------------------------------------------------------------------
                        | SHOW MODAL
                        |--------------------------------------------------------------------------
                        */

                        modal.classList.add(
                            'open'
                        );

                        document.body.style.overflow =
                            'hidden';

                    }
                );

            }
        );


    /*
    |--------------------------------------------------------------------------
    | CLOSE MODAL
    |--------------------------------------------------------------------------
    */

    function closeModal() {

        modal.classList.remove(
            'open'
        );

        document.body.style.overflow =
            '';

    }


    closeButton.addEventListener(
        'click',
        closeModal
    );


    cancelButton.addEventListener(
        'click',
        closeModal
    );


    modal.addEventListener(
        'click',
        function (event) {

            if (
                event.target === modal
            ) {

                closeModal();

            }

        }
    );


    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape' &&
                modal.classList.contains('open')
            ) {

                closeModal();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | SEARCH + FILTER
    |--------------------------------------------------------------------------
    */

    function filterTable() {

        const searchValue =
            (
                search.value || ''
            )
            .toLowerCase()
            .trim();


        const filterValue =
            filter.value;


        const rows =
            document.querySelectorAll(
                '#evaluationTableBody tr[data-search]'
            );


        let visible = 0;


        rows.forEach(
            function (row) {

                const rowSearch =
                    (
                        row.dataset.search || ''
                    ).toLowerCase();


                const rowStatus =
                    row.dataset.status ||
                    'pending';


                const matchesSearch =
                    searchValue === '' ||
                    rowSearch.includes(
                        searchValue
                    );


                let matchesStatus = true;


                if (
                    filterValue !== 'all'
                ) {

                    if (
                        filterValue === 'pending'
                    ) {

                        matchesStatus =
                            rowStatus === 'pending';

                    } else {

                        matchesStatus =
                            rowStatus ===
                            filterValue;
                    }

                }


                const show =
                    matchesSearch &&
                    matchesStatus;


                row.style.display =
                    show ? '' : 'none';


                if (show) {
                    visible++;
                }

            }
        );


        resultCount.textContent =
            'Showing ' +
            visible +
            ' ' +
            (
                visible === 1
                    ? 'student'
                    : 'students'
            );

    }


    search.addEventListener(
        'input',
        filterTable
    );


    filter.addEventListener(
        'change',
        filterTable
    );


    /*
    |--------------------------------------------------------------------------
    | FORM VALIDATION
    |--------------------------------------------------------------------------
    */

    form.addEventListener(
        'submit',
        function (event) {

            /*
            |--------------------------------------------------------------------------
            | STUDENT
            |--------------------------------------------------------------------------
            */

            if (!studentId.value) {

                event.preventDefault();

                alert(
                    'Please select a student.'
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | VALIDATE 10 RATINGS
            |--------------------------------------------------------------------------
            */

            for (
                let i = 0;
                i < criteria.length;
                i++
            ) {

                const element =
                    document.getElementById(
                        'eval_' +
                        criteria[i]
                    );


                const value =
                    Number(
                        element.value
                    );


                if (
                    !Number.isInteger(value) ||
                    value < 0 ||
                    value > 5
                ) {

                    event.preventDefault();

                    alert(
                        'Each rating must be between 0 and 5.'
                    );

                    element.focus();

                    return;
                }

            }


            /*
            |--------------------------------------------------------------------------
            | COMPLETED VALIDATION
            |--------------------------------------------------------------------------
            */

            const status =
                document.getElementById(
                    'evaluationStatus'
                ).value;


            if (
                status === 'Completed'
            ) {

                const performance =
                    document.getElementById(
                        'overallPerformance'
                    ).value;


                const recommendation =
                    document.getElementById(
                        'recommendation'
                    ).value;


                if (!performance) {

                    event.preventDefault();

                    alert(
                        'Please select the overall performance.'
                    );

                    return;
                }


                if (!recommendation) {

                    event.preventDefault();

                    alert(
                        'Please select the recommendation.'
                    );

                    return;
                }

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | INITIAL FILTER
    |--------------------------------------------------------------------------
    */

    filterTable();


})();

</script>


<?php

include __DIR__ . '/includes/layout_bottom.php';

?>