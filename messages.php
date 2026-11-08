<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$company = require_company();
$pdo = db();

$pageKey = 'messages';
$pageTitle = 'Messages';
$eyebrow = 'COMMUNICATION CENTER';

/*
|--------------------------------------------------------------------------
| Helper: JSON response
|--------------------------------------------------------------------------
*/
function messagesJson(array $data, int $status = 200): void
{
    http_response_code($status);

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Current Company
|--------------------------------------------------------------------------
*/
$companyId = (int)($company['id'] ?? 0);

if ($companyId <= 0) {
    http_response_code(403);
    exit('Invalid company account.');
}

/*
|--------------------------------------------------------------------------
| AJAX / API REQUESTS
|--------------------------------------------------------------------------
*/

$api = isset($_GET['api']) ? trim((string)$_GET['api']) : '';

if ($api !== '') {

    if ($api === 'employees') {

        try {

            $stmt = $pdo->query("
                SELECT
                    id,
                    employee_id,
                    first_name,
                    middle_name,
                    last_name,
                    department,
                    position,
                    email
                FROM employees
                ORDER BY first_name ASC, last_name ASC
            ");

            $employees = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                $fullName = trim(
                    ($row['first_name'] ?? '') . ' ' .
                    ($row['middle_name'] ?? '') . ' ' .
                    ($row['last_name'] ?? '')
                );

                $employees[] = [
                    'id' => (int)$row['id'],
                    'employee_id' => $row['employee_id'],
                    'name' => $fullName,
                    'department' => $row['department'],
                    'position' => $row['position'],
                    'email' => $row['email'],
                ];
            }

            messagesJson([
                'success' => true,
                'employees' => $employees
            ]);

        } catch (Throwable $e) {

            messagesJson([
                'success' => false,
                'message' => 'Unable to load employees.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    if ($api === 'conversations') {

        try {

            $stmt = $pdo->prepare("
                SELECT
                    e.id,
                    e.employee_id,
                    e.first_name,
                    e.middle_name,
                    e.last_name,
                    e.department,
                    e.position,
                    e.email,

                    (
                        SELECT m.message
                        FROM messages m
                        WHERE m.company_id = :company_id_1
                          AND m.employee_id = e.id
                        ORDER BY m.created_at DESC, m.id DESC
                        LIMIT 1
                    ) AS last_message,

                    (
                        SELECT m.created_at
                        FROM messages m
                        WHERE m.company_id = :company_id_2
                          AND m.employee_id = e.id
                        ORDER BY m.created_at DESC, m.id DESC
                        LIMIT 1
                    ) AS last_message_time,

                    (
                        SELECT COUNT(*)
                        FROM messages m
                        WHERE m.company_id = :company_id_3
                          AND m.employee_id = e.id
                          AND m.sender_type = 'employee'
                          AND m.is_read = 0
                    ) AS unread_count,

                    (
                        SELECT m.sender_type
                        FROM messages m
                        WHERE m.company_id = :company_id_4
                          AND m.employee_id = e.id
                        ORDER BY m.created_at DESC, m.id DESC
                        LIMIT 1
                    ) AS last_sender

                FROM employees e

                ORDER BY
                    last_message_time IS NULL ASC,
                    last_message_time DESC,
                    e.first_name ASC,
                    e.last_name ASC
            ");

            $stmt->execute([
                ':company_id_1' => $companyId,
                ':company_id_2' => $companyId,
                ':company_id_3' => $companyId,
                ':company_id_4' => $companyId,
            ]);

            $conversations = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                $fullName = trim(
                    ($row['first_name'] ?? '') . ' ' .
                    ($row['middle_name'] ?? '') . ' ' .
                    ($row['last_name'] ?? '')
                );

                $initials = '';

                if (!empty($row['first_name'])) {
                    $initials .= strtoupper(substr($row['first_name'], 0, 1));
                }

                if (!empty($row['last_name'])) {
                    $initials .= strtoupper(substr($row['last_name'], 0, 1));
                }

                $conversations[] = [
                    'id' => (int)$row['id'],
                    'employee_id' => $row['employee_id'],
                    'name' => $fullName,
                    'initials' => $initials ?: 'EM',
                    'department' => $row['department'],
                    'position' => $row['position'],
                    'email' => $row['email'],
                    'last_message' => $row['last_message'],
                    'last_message_time' => $row['last_message_time'],
                    'unread_count' => (int)$row['unread_count'],
                    'last_sender' => $row['last_sender'],
                ];
            }

            messagesJson([
                'success' => true,
                'conversations' => $conversations
            ]);

        } catch (Throwable $e) {

            messagesJson([
                'success' => false,
                'message' => 'Unable to load conversations.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    if ($api === 'thread') {

        $employeeId = isset($_GET['employee_id'])
            ? (int)$_GET['employee_id']
            : 0;

        if ($employeeId <= 0) {
            messagesJson([
                'success' => false,
                'message' => 'Invalid employee.'
            ], 400);
        }

        try {

            $employeeStmt = $pdo->prepare("
                SELECT
                    id,
                    employee_id,
                    first_name,
                    middle_name,
                    last_name,
                    department,
                    position,
                    email
                FROM employees
                WHERE id = ?
                LIMIT 1
            ");

            $employeeStmt->execute([$employeeId]);

            $employee = $employeeStmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                messagesJson([
                    'success' => false,
                    'message' => 'Employee not found.'
                ], 404);
            }

            $markStmt = $pdo->prepare("
                UPDATE messages
                SET is_read = 1
                WHERE company_id = ?
                  AND employee_id = ?
                  AND sender_type = 'employee'
                  AND is_read = 0
            ");

            $markStmt->execute([
                $companyId,
                $employeeId
            ]);

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    company_id,
                    employee_id,
                    sender_type,
                    sender_id,
                    message,
                    is_read,
                    created_at
                FROM messages
                WHERE company_id = ?
                  AND employee_id = ?
                ORDER BY created_at ASC, id ASC
            ");

            $stmt->execute([
                $companyId,
                $employeeId
            ]);

            $messages = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                $messages[] = [
                    'id' => (int)$row['id'],
                    'sender_type' => $row['sender_type'],
                    'sender_id' => (int)$row['sender_id'],
                    'message' => $row['message'],
                    'is_read' => (int)$row['is_read'],
                    'created_at' => $row['created_at'],
                ];
            }

            $employeeName = trim(
                ($employee['first_name'] ?? '') . ' ' .
                ($employee['middle_name'] ?? '') . ' ' .
                ($employee['last_name'] ?? '')
            );

            messagesJson([
                'success' => true,

                'employee' => [
                    'id' => (int)$employee['id'],
                    'employee_id' => $employee['employee_id'],
                    'name' => $employeeName,
                    'department' => $employee['department'],
                    'position' => $employee['position'],
                    'email' => $employee['email'],
                ],

                'messages' => $messages
            ]);

        } catch (Throwable $e) {

            messagesJson([
                'success' => false,
                'message' => 'Unable to load conversation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    if ($api === 'mark_read') {

        $employeeId = isset($_GET['employee_id'])
            ? (int)$_GET['employee_id']
            : 0;

        if ($employeeId <= 0) {
            messagesJson([
                'success' => false,
                'message' => 'Invalid employee.'
            ], 400);
        }

        try {

            $stmt = $pdo->prepare("
                UPDATE messages
                SET is_read = 1
                WHERE company_id = ?
                  AND employee_id = ?
                  AND sender_type = 'employee'
                  AND is_read = 0
            ");

            $stmt->execute([
                $companyId,
                $employeeId
            ]);

            messagesJson([
                'success' => true
            ]);

        } catch (Throwable $e) {

            messagesJson([
                'success' => false,
                'message' => 'Unable to mark messages as read.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    if ($api === 'send') {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            messagesJson([
                'success' => false,
                'message' => 'Invalid request method.'
            ], 405);
        }

        $employeeId = isset($_POST['employee_id'])
            ? (int)$_POST['employee_id']
            : 0;

        $message = trim((string)($_POST['message'] ?? ''));

        if ($employeeId <= 0) {

            messagesJson([
                'success' => false,
                'message' => 'Please select an employee.'
            ], 400);
        }

        if ($message === '') {

            messagesJson([
                'success' => false,
                'message' => 'Message cannot be empty.'
            ], 400);
        }

        if (mb_strlen($message) > 5000) {

            messagesJson([
                'success' => false,
                'message' => 'Message is too long. Maximum is 5000 characters.'
            ], 400);
        }

        try {

            $employeeCheck = $pdo->prepare("
                SELECT id
                FROM employees
                WHERE id = ?
                LIMIT 1
            ");

            $employeeCheck->execute([$employeeId]);

            if (!$employeeCheck->fetchColumn()) {

                messagesJson([
                    'success' => false,
                    'message' => 'Employee not found.'
                ], 404);
            }

            $stmt = $pdo->prepare("
                INSERT INTO messages
                (
                    company_id,
                    employee_id,
                    sender_type,
                    sender_id,
                    message,
                    is_read
                )
                VALUES
                (
                    ?,
                    ?,
                    'company',
                    ?,
                    ?,
                    0
                )
            ");

            $stmt->execute([
                $companyId,
                $employeeId,
                $companyId,
                $message
            ]);

            $messageId = (int)$pdo->lastInsertId();

            messagesJson([
                'success' => true,
                'message_id' => $messageId
            ]);

        } catch (Throwable $e) {

            messagesJson([
                'success' => false,
                'message' => 'Unable to send message.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    messagesJson([
        'success' => false,
        'message' => 'Unknown API request.'
    ], 404);
}

/*
|--------------------------------------------------------------------------
| Get initial employee list for page
|--------------------------------------------------------------------------
*/
$employeeStmt = $pdo->query("
    SELECT
        id,
        employee_id,
        first_name,
        middle_name,
        last_name,
        department,
        position,
        email
    FROM employees
    ORDER BY first_name ASC, last_name ASC
");

$employees = [];

while ($row = $employeeStmt->fetch(PDO::FETCH_ASSOC)) {

    $fullName = trim(
        ($row['first_name'] ?? '') . ' ' .
        ($row['middle_name'] ?? '') . ' ' .
        ($row['last_name'] ?? '')
    );

    $initials = '';

    if (!empty($row['first_name'])) {
        $initials .= strtoupper(substr($row['first_name'], 0, 1));
    }

    if (!empty($row['last_name'])) {
        $initials .= strtoupper(substr($row['last_name'], 0, 1));
    }

    $employees[] = [
        'id' => (int)$row['id'],
        'employee_id' => $row['employee_id'],
        'name' => $fullName,
        'initials' => $initials ?: 'EM',
        'department' => $row['department'],
        'position' => $row['position'],
        'email' => $row['email'],
    ];
}

require_once __DIR__ . '/includes/layout_top.php';

?>

<style>
/* ============================================================
   MESSAGES PAGE - DUAL THEME SUPPORT
   ============================================================ */

.messages-page {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
    font-family: "Segoe UI", Inter, "Helvetica Neue", Arial, sans-serif;
}

.messages-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.messages-header-left {
    min-width: 0;
}

.messages-eyebrow {
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

.messages-eyebrow::before {
    content: "";
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--qr-brand, #2563eb);
    box-shadow: 0 0 10px rgba(37, 99, 235, 0.4);
}

.messages-header h1 {
    margin: 0;
    font-size: clamp(30px, 3.4vw, 42px);
    line-height: 1.12;
    letter-spacing: -0.04em;
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.messages-header p {
    margin: 10px 0 0;
    max-width: 720px;
    color: var(--qr-text-muted, #64748b);
    font-size: 14px;
    line-height: 1.65;
    font-weight: 400;
}

.company-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    border-radius: 999px;
    background: var(--qr-surface-alt, #eff6ff);
    color: var(--qr-brand, #1d4ed8);
    font-size: 12px;
    font-weight: 700;
    border: 1px solid var(--qr-border, #e2e8f0);
}

.company-badge-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22c55e;
}

/* -------------------------------------------------------------------------- */
/* Main Messenger                                                              */
/* -------------------------------------------------------------------------- */

.messenger {
    display: grid;
    grid-template-columns: 330px minmax(0, 1fr);
    height: calc(100vh - 230px);
    min-height: 600px;
    background: var(--qr-surface, #ffffff);
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
}

/* -------------------------------------------------------------------------- */
/* Sidebar                                                                     */
/* -------------------------------------------------------------------------- */

.inbox-sidebar {
    border-right: 1px solid var(--qr-border, #e2e8f0);
    background: var(--qr-surface-alt, #f8fafc);
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.inbox-top {
    padding: 20px;
    border-bottom: 1px solid var(--qr-border, #e2e8f0);
    background: var(--qr-surface, #ffffff);
}

.inbox-title-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.inbox-title {
    font-size: 17px;
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.new-message-btn {
    border: 0;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #2563eb;
    color: white;
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    transition: transform 0.15s ease, background 0.15s ease;
}

.new-message-btn:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.search-box {
    position: relative;
}

.search-box input {
    width: 100%;
    height: 42px;
    border: 1px solid var(--qr-border-strong, #cbd5e1);
    border-radius: 12px;
    padding: 0 13px 0 38px;
    outline: none;
    background: var(--qr-surface-alt, #f8fafc);
    color: var(--qr-text, #0f172a);
    font-size: 13px;
}

.search-box input:focus {
    border-color: #3b82f6;
    background: var(--qr-surface, #ffffff);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.search-icon {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--qr-text-subtle, #94a3b8);
    font-size: 14px;
}

/* -------------------------------------------------------------------------- */
/* Filters                                                                     */
/* -------------------------------------------------------------------------- */

.filters {
    display: flex;
    gap: 6px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--qr-border, #e2e8f0);
    background: var(--qr-surface, #ffffff);
}

.filter-btn {
    border: 0;
    background: transparent;
    color: var(--qr-text-muted, #64748b);
    padding: 7px 10px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 11px;
    font-weight: 700;
}

.filter-btn:hover {
    background: var(--qr-surface-soft, #f1f5f9);
}

.filter-btn.active {
    background: var(--qr-surface-alt, #eff6ff);
    color: var(--qr-brand, #2563eb);
}

/* -------------------------------------------------------------------------- */
/* Conversation List                                                           */
/* -------------------------------------------------------------------------- */

.conversation-list {
    flex: 1;
    overflow-y: auto;
}

.conversation-item {
    padding: 15px 16px;
    display: flex;
    gap: 12px;
    cursor: pointer;
    border-bottom: 1px solid var(--qr-border, #e2e8f0);
    transition: background 0.15s ease;
    background: var(--qr-surface, #ffffff);
}

.conversation-item:hover {
    background: var(--qr-surface-alt, #f8fafc);
}

.conversation-item.active {
    background: var(--qr-surface-alt, #eff6ff);
    border-left: 3px solid var(--qr-brand, #2563eb);
    padding-left: 13px;
}

.avatar {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 12px;
    background: var(--qr-surface-alt, #f8fafc);
    color: var(--qr-brand, #2563eb);
    font-weight: 900;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--qr-border, #e2e8f0);
}

.conversation-content {
    flex: 1;
    min-width: 0;
}

.conversation-name-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
}

.conversation-name {
    font-size: 13px;
    font-weight: 800;
    color: var(--qr-text, #0f172a);
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.conversation-time {
    color: var(--qr-text-subtle, #94a3b8);
    font-size: 10px;
    white-space: nowrap;
}

.conversation-preview {
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.preview-text {
    color: var(--qr-text-muted, #64748b);
    font-size: 11px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.unread-badge {
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 999px;
    background: #2563eb;
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 9px;
    font-weight: 800;
}

.empty-list {
    padding: 45px 25px;
    text-align: center;
    color: var(--qr-text-muted, #94a3b8);
    font-size: 13px;
}

.empty-list strong {
    display: block;
    color: var(--qr-text-soft, #475569);
    margin-bottom: 5px;
}

/* -------------------------------------------------------------------------- */
/* Chat Area                                                                   */
/* -------------------------------------------------------------------------- */

.chat-panel {
    min-width: 0;
    display: flex;
    flex-direction: column;
    background: var(--qr-surface, #ffffff);
}

.chat-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 30px;
    color: var(--qr-text-muted, #94a3b8);
}

.chat-empty-icon {
    width: 70px;
    height: 70px;
    border-radius: 20px;
    background: var(--qr-surface-alt, #eff6ff);
    color: var(--qr-brand, #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-bottom: 18px;
    border: 1px solid var(--qr-border, #e2e8f0);
}

.chat-empty h3 {
    margin: 0 0 7px;
    color: var(--qr-text, #1e293b);
    font-size: 18px;
    font-weight: 800;
}

.chat-empty p {
    max-width: 380px;
    margin: 0;
    line-height: 1.6;
    font-size: 13px;
    color: var(--qr-text-muted, #64748b);
}

/* -------------------------------------------------------------------------- */
/* Chat Header                                                                 */
/* -------------------------------------------------------------------------- */

.chat-header {
    display: none;
    align-items: center;
    gap: 13px;
    padding: 17px 20px;
    border-bottom: 1px solid var(--qr-border, #e2e8f0);
    background: var(--qr-surface, #ffffff);
}

.chat-header.visible {
    display: flex;
}

.chat-user-info {
    flex: 1;
    min-width: 0;
}

.chat-user-name {
    font-size: 14px;
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.chat-user-meta {
    margin-top: 3px;
    color: var(--qr-text-muted, #64748b);
    font-size: 11px;
}

.online-indicator {
    width: 8px;
    height: 8px;
    background: #22c55e;
    border-radius: 50%;
    display: inline-block;
    margin-right: 4px;
}

/* -------------------------------------------------------------------------- */
/* Messages                                                                    */
/* -------------------------------------------------------------------------- */

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 25px;
    background: var(--qr-surface-alt, #f8fafc);
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.message-row {
    display: flex;
    width: 100%;
}

.message-row.company {
    justify-content: flex-end;
}

.message-row.employee {
    justify-content: flex-start;
}

.message-bubble {
    max-width: min(70%, 650px);
    padding: 11px 14px;
    border-radius: 15px;
    font-size: 13px;
    line-height: 1.55;
    white-space: pre-wrap;
    word-break: break-word;
}

.message-row.employee .message-bubble {
    background: var(--qr-surface, #ffffff);
    border: 1px solid var(--qr-border, #e2e8f0);
    color: var(--qr-text-soft, #334155);
    border-bottom-left-radius: 5px;
}

.message-row.company .message-bubble {
    background: #2563eb;
    color: #ffffff;
    border-bottom-right-radius: 5px;
}

.message-time {
    margin-top: 5px;
    font-size: 9px;
    color: var(--qr-text-subtle, #94a3b8);
}

.message-row.company .message-time {
    text-align: right;
}

.no-messages {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--qr-text-muted, #94a3b8);
    font-size: 13px;
    text-align: center;
}

/* -------------------------------------------------------------------------- */
/* Composer                                                                    */
/* -------------------------------------------------------------------------- */

.message-composer {
    display: none;
    padding: 14px 18px;
    border-top: 1px solid var(--qr-border, #e2e8f0);
    background: var(--qr-surface, #ffffff);
}

.message-composer.visible {
    display: block;
}

.composer-form {
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

.composer-form textarea {
    flex: 1;
    resize: none;
    min-height: 46px;
    max-height: 130px;
    padding: 13px 14px;
    border: 1px solid var(--qr-border-strong, #dbe3ee);
    border-radius: 12px;
    outline: none;
    font-family: inherit;
    font-size: 13px;
    background: var(--qr-surface-alt, #f8fafc);
    color: var(--qr-text, #0f172a);
}

.composer-form textarea:focus {
    border-color: #3b82f6;
    background: var(--qr-surface, #ffffff);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.send-btn {
    width: 46px;
    height: 46px;
    border: 0;
    border-radius: 12px;
    background: #2563eb;
    color: white;
    cursor: pointer;
    font-size: 17px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    transition: transform 0.15s ease, background 0.15s ease;
}

.send-btn:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.send-btn:disabled {
    opacity: .5;
    cursor: not-allowed;
}

.composer-help {
    margin-top: 7px;
    font-size: 10px;
    color: var(--qr-text-subtle, #94a3b8);
}

/* -------------------------------------------------------------------------- */
/* New Message Modal                                                           */
/* -------------------------------------------------------------------------- */

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

.modal-overlay.show {
    display: flex;
}

.modal {
    width: 100%;
    max-width: 480px;
    background: var(--qr-surface, #ffffff);
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 20px;
    box-shadow: 0 25px 80px rgba(15, 23, 42, 0.25);
    overflow: hidden;
}

.modal-header {
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--qr-border, #e2e8f0);
}

.modal-header h3 {
    margin: 0;
    font-size: 17px;
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.close-modal {
    width: 32px;
    height: 32px;
    border: 0;
    border-radius: 8px;
    background: var(--qr-surface-alt, #f1f5f9);
    color: var(--qr-text-muted, #64748b);
    cursor: pointer;
}

.modal-body {
    padding: 20px;
    background: var(--qr-surface, #ffffff);
}

.form-label {
    display: block;
    margin-bottom: 7px;
    font-size: 11px;
    font-weight: 800;
    color: var(--qr-text-soft, #475569);
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.employee-search {
    width: 100%;
    height: 42px;
    border: 1px solid var(--qr-border-strong, #dbe3ee);
    border-radius: 10px;
    padding: 0 12px;
    outline: none;
    font-family: inherit;
    font-size: 13px;
    background: var(--qr-surface-alt, #f8fafc);
    color: var(--qr-text, #0f172a);
}

.employee-search:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.employee-results {
    margin-top: 8px;
    max-height: 260px;
    overflow-y: auto;
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 11px;
    background: var(--qr-surface, #ffffff);
}

.employee-option {
    padding: 11px 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    border-bottom: 1px solid var(--qr-border, #f1f5f9);
}

.employee-option:last-child {
    border-bottom: 0;
}

.employee-option:hover {
    background: var(--qr-surface-alt, #eff6ff);
}

.employee-option-info {
    min-width: 0;
}

.employee-option-name {
    font-size: 12px;
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.employee-option-meta {
    margin-top: 3px;
    color: var(--qr-text-muted, #64748b);
    font-size: 10px;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--qr-border, #e2e8f0);
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    background: var(--qr-surface-alt, #f8fafc);
}

.cancel-btn,
.start-chat-btn {
    height: 38px;
    border-radius: 9px;
    padding: 0 15px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}

.cancel-btn {
    border: 1px solid var(--qr-border-strong, #dbe3ee);
    background: var(--qr-surface, #ffffff);
    color: var(--qr-text-muted, #64748b);
}

.start-chat-btn {
    border: 0;
    background: #2563eb;
    color: white;
}

.start-chat-btn:disabled {
    opacity: .45;
    cursor: not-allowed;
}

/* -------------------------------------------------------------------------- */
/* Toast                                                                       */
/* -------------------------------------------------------------------------- */

.toast {
    position: fixed;
    right: 24px;
    bottom: 24px;
    padding: 12px 16px;
    background: #0f172a;
    color: white;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    box-shadow: 0 12px 30px rgba(15, 23, 42, .2);
    transform: translateY(20px);
    opacity: 0;
    pointer-events: none;
    transition: .25s ease;
    z-index: 10000;
}

.toast.show {
    transform: translateY(0);
    opacity: 1;
}

.toast.success {
    background: #166534;
}

.toast.error {
    background: #991b1b;
}

/* -------------------------------------------------------------------------- */
/* Responsive                                                                  */
/* -------------------------------------------------------------------------- */

@media (max-width: 900px) {
    .messages-page { padding: 16px; }
    .messenger { grid-template-columns: 280px minmax(0, 1fr); }
}

@media (max-width: 700px) {
    .messages-header { flex-direction: column; }
    .messenger { grid-template-columns: 1fr; height: calc(100vh - 260px); }
    .inbox-sidebar { display: flex; }
    .chat-panel { display: none; }
    .messenger.chat-open .inbox-sidebar { display: none; }
    .messenger.chat-open .chat-panel { display: flex; }
    .message-bubble { max-width: 85%; }
}

/* ============================================================
   DARK MODE OVERRIDES (DEEP NAVY)
   ============================================================ */
html[data-theme="dark"] .messages-page,
.dark .messages-page {
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
}

html[data-theme="dark"] .messages-header h1,
.dark .messages-header h1,
html[data-theme="dark"] .conversation-name,
.dark .conversation-name,
html[data-theme="dark"] .chat-empty h3,
.dark .chat-empty h3,
html[data-theme="dark"] .chat-user-name,
.dark .chat-user-name,
html[data-theme="dark"] .employee-option-name,
.dark .employee-option-name,
html[data-theme="dark"] .modal-header h3,
.dark .modal-header h3,
html[data-theme="dark"] .inbox-title,
.dark .inbox-title {
    color: #ffffff !important;
}

html[data-theme="dark"] .messenger,
.dark .messenger {
    background: var(--qr-surface) !important;
    border-color: var(--qr-border) !important;
}

html[data-theme="dark"] .inbox-sidebar,
.dark .inbox-sidebar {
    background: var(--qr-surface-alt) !important;
    border-right-color: var(--qr-border) !important;
}

html[data-theme="dark"] .inbox-top,
.dark .inbox-top,
html[data-theme="dark"] .filters,
.dark .filters,
html[data-theme="dark"] .chat-header,
.dark .chat-header,
html[data-theme="dark"] .message-composer,
.dark .message-composer,
html[data-theme="dark"] .modal,
.dark .modal,
html[data-theme="dark"] .modal-body,
.dark .modal-body {
    background: var(--qr-surface) !important;
    border-color: var(--qr-border) !important;
}

html[data-theme="dark"] .conversation-item,
.dark .conversation-item {
    background: var(--qr-surface) !important;
    border-bottom-color: var(--qr-border) !important;
}

html[data-theme="dark"] .conversation-item:hover,
.dark .conversation-item:hover {
    background: var(--qr-surface-alt) !important;
}

html[data-theme="dark"] .conversation-item.active,
.dark .conversation-item.active {
    background: var(--qr-surface-alt) !important;
    border-left-color: var(--qr-brand) !important;
}

html[data-theme="dark"] .chat-panel,
.dark .chat-panel {
    background: var(--qr-surface) !important;
}

html[data-theme="dark"] .chat-messages,
.dark .chat-messages {
    background: var(--qr-surface-alt) !important;
}

html[data-theme="dark"] .message-row.employee .message-bubble,
.dark .message-row.employee .message-bubble {
    background: var(--qr-surface-alt) !important;
    border-color: var(--qr-border) !important;
    color: var(--qr-text-soft) !important;
}

html[data-theme="dark"] .message-row.company .message-bubble,
.dark .message-row.company .message-bubble {
    background: #2563eb !important;
    color: #ffffff !important;
}

html[data-theme="dark"] .search-box input,
.dark .search-box input,
html[data-theme="dark"] .employee-search,
.dark .employee-search,
html[data-theme="dark"] .composer-form textarea,
.dark .composer-form textarea {
    background: var(--qr-surface-alt) !important;
    border-color: var(--qr-border) !important;
    color: #ffffff !important;
}

html[data-theme="dark"] .filter-btn,
.dark .filter-btn,
html[data-theme="dark"] .cancel-btn,
.dark .cancel-btn {
    background: var(--qr-surface-alt) !important;
    border-color: var(--qr-border) !important;
    color: var(--qr-text-soft) !important;
}

html[data-theme="dark"] .filter-btn.active,
.dark .filter-btn.active {
    background: var(--qr-surface-alt) !important;
    color: var(--qr-brand) !important;
}

html[data-theme="dark"] .new-message-btn,
.dark .new-message-btn,
html[data-theme="dark"] .send-btn,
.dark .send-btn,
html[data-theme="dark"] .start-chat-btn,
.dark .start-chat-btn {
    background: #2563eb !important;
    color: #ffffff !important;
}

html[data-theme="dark"] .employee-option,
.dark .employee-option {
    background: var(--qr-surface) !important;
    border-bottom-color: var(--qr-border) !important;
}

html[data-theme="dark"] .employee-option:hover,
.dark .employee-option:hover {
    background: var(--qr-surface-alt) !important;
}

html[data-theme="dark"] .employee-results,
.dark .employee-results {
    background: var(--qr-surface) !important;
    border-color: var(--qr-border) !important;
}
</style>

<div class="messages-page">

    <div class="messages-header">
        <div class="messages-header-left">
            <div class="messages-eyebrow">
                <?= e($eyebrow) ?>
            </div>
            <h1>Messages</h1>
            <p>Communicate directly with OJT employees and manage your chats.</p>
        </div>

        <div class="company-badge">
            <span class="company-badge-dot"></span>
            <?= e((string)($company['company_name'] ?? 'Company')) ?>
        </div>
    </div>

    <div class="messenger" id="messenger">

        <!-- INBOX SIDEBAR -->
        <aside class="inbox-sidebar">
            <div class="inbox-top">
                <div class="inbox-title-row">
                    <div class="inbox-title">Chats</div>
                    <button
                        type="button"
                        class="new-message-btn"
                        id="newMessageBtn"
                        title="New message"
                    >
                        +
                    </button>
                </div>

                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input
                        type="text"
                        id="conversationSearch"
                        placeholder="Search chats..."
                        autocomplete="off"
                    >
                </div>
            </div>

            <div class="filters">
                <button type="button" class="filter-btn active" data-filter="all">All</button>
                <button type="button" class="filter-btn" data-filter="unread">Unread</button>
            </div>

            <div class="conversation-list" id="conversationList">
                <div class="empty-list">Loading chats...</div>
            </div>
        </aside>

        <!-- CHAT PANEL -->
        <section class="chat-panel">
            <div class="chat-empty" id="chatEmpty">
                <div class="chat-empty-icon">💬</div>
                <h3>Select a chat</h3>
                <p>Choose an employee from the chat list or start a new conversation.</p>
            </div>

            <div class="chat-header" id="chatHeader">
                <div class="avatar" id="chatAvatar">EM</div>
                <div class="chat-user-info">
                    <div class="chat-user-name" id="chatUserName">Employee</div>
                    <div class="chat-user-meta" id="chatUserMeta">Employee</div>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages" style="display:none;"></div>

            <div class="message-composer" id="messageComposer">
                <form class="composer-form" id="messageForm">
                    <textarea
                        id="messageInput"
                        name="message"
                        placeholder="Type your message..."
                        maxlength="5000"
                        rows="1"
                    ></textarea>
                    <button type="submit" class="send-btn" id="sendBtn" title="Send message">➤</button>
                </form>
                <div class="composer-help">Press Enter to send · Shift + Enter for a new line</div>
            </div>
        </section>

    </div>
</div>

<!-- NEW MESSAGE MODAL -->
<div class="modal-overlay" id="newMessageModal">
    <div class="modal">
        <div class="modal-header">
            <h3>New message</h3>
            <button type="button" class="close-modal" id="closeModalBtn">✕</button>
        </div>

        <div class="modal-body">
            <label class="form-label">Select employee</label>
            <input
                type="text"
                class="employee-search"
                id="employeeSearch"
                placeholder="Search employee..."
                autocomplete="off"
            >
            <div class="employee-results" id="employeeResults"></div>
        </div>

        <div class="modal-footer">
            <button type="button" class="cancel-btn" id="cancelModalBtn">Cancel</button>
            <button type="button" class="start-chat-btn" id="startChatBtn" disabled>Start chat</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
const companyId = <?= json_encode($companyId) ?>;
const initialEmployees = <?= json_encode($employees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

let conversations = [];
let employees = initialEmployees;
let selectedEmployeeId = null;
let selectedEmployee = null;
let activeFilter = 'all';
let threadTimer = null;
let conversationTimer = null;

const messenger = document.getElementById('messenger');
const conversationList = document.getElementById('conversationList');
const conversationSearch = document.getElementById('conversationSearch');
const chatEmpty = document.getElementById('chatEmpty');
const chatHeader = document.getElementById('chatHeader');
const chatAvatar = document.getElementById('chatAvatar');
const chatUserName = document.getElementById('chatUserName');
const chatUserMeta = document.getElementById('chatUserMeta');
const chatMessages = document.getElementById('chatMessages');
const messageComposer = document.getElementById('messageComposer');
const messageForm = document.getElementById('messageForm');
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');
const newMessageBtn = document.getElementById('newMessageBtn');
const newMessageModal = document.getElementById('newMessageModal');
const closeModalBtn = document.getElementById('closeModalBtn');
const cancelModalBtn = document.getElementById('cancelModalBtn');
const employeeSearch = document.getElementById('employeeSearch');
const employeeResults = document.getElementById('employeeResults');
const startChatBtn = document.getElementById('startChatBtn');
const toast = document.getElementById('toast');

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value === null || value === undefined ? '' : String(value);
    return div.innerHTML;
}

function showToast(message, type = 'success') {
    toast.textContent = message;
    toast.className = 'toast show ' + type;
    setTimeout(() => { toast.className = 'toast'; }, 2500);
}

function formatMessageTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return dateString;
    return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
}

function formatConversationTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '';
    const now = new Date();
    if (date.toDateString() === now.toDateString()) {
        return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }
    return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
}

function initialsFromName(name) {
    const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return 'EM';
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

async function loadConversations() {
    try {
        const response = await fetch('messages.php?api=conversations', { credentials: 'same-origin', cache: 'no-store' });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Unable to load chats.');
        conversations = data.conversations || [];
        renderConversations();
    } catch (error) {
        conversationList.innerHTML = `<div class="empty-list"><strong>Unable to load</strong>Please refresh the page.</div>`;
    }
}

function renderConversations() {
    const search = conversationSearch.value.trim().toLowerCase();
    let filtered = conversations.filter(item => {
        const matchesSearch = !search || item.name.toLowerCase().includes(search) || String(item.employee_id).toLowerCase().includes(search) || String(item.department || '').toLowerCase().includes(search);
        const matchesFilter = activeFilter === 'all' || (activeFilter === 'unread' && Number(item.unread_count) > 0);
        return matchesSearch && matchesFilter;
    });

    if (filtered.length === 0) {
        conversationList.innerHTML = `
            <div class="empty-list">
                <strong>${activeFilter === 'unread' ? 'No unread messages' : 'No chats'}</strong>
                ${activeFilter === 'all' ? 'Click + to start a new chat.' : 'You are all caught up.'}
            </div>
        `;
        return;
    }

    conversationList.innerHTML = filtered.map(item => {
        const active = Number(item.id) === Number(selectedEmployeeId);
        const preview = item.last_message ? item.last_message : 'No messages yet';
        const unread = Number(item.unread_count || 0);

        return `
            <div class="conversation-item ${active ? 'active' : ''}" data-employee-id="${Number(item.id)}">
                <div class="avatar">${escapeHtml(item.initials || 'EM')}</div>
                <div class="conversation-content">
                    <div class="conversation-name-row">
                        <div class="conversation-name">${escapeHtml(item.name)}</div>
                        <div class="conversation-time">${escapeHtml(formatConversationTime(item.last_message_time))}</div>
                    </div>
                    <div class="conversation-preview">
                        <div class="preview-text">${item.last_sender === 'company' ? 'You: ' : ''}${escapeHtml(preview)}</div>
                        ${unread > 0 ? `<span class="unread-badge">${unread > 99 ? '99+' : unread}</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');

    document.querySelectorAll('.conversation-item').forEach(item => {
        item.addEventListener('click', () => {
            openConversation(Number(item.dataset.employeeId));
        });
    });
}

async function openConversation(employeeId) {
    selectedEmployeeId = Number(employeeId);
    selectedEmployee = employees.find(e => Number(e.id) === selectedEmployeeId) || null;

    messenger.classList.add('chat-open');
    chatEmpty.style.display = 'none';
    chatHeader.classList.add('visible');
    chatMessages.style.display = 'flex';
    messageComposer.classList.add('visible');

    await loadThread();
    renderConversations();
    startThreadPolling();
}

async function loadThread() {
    if (!selectedEmployeeId) return;
    try {
        const response = await fetch('messages.php?api=thread&employee_id=' + encodeURIComponent(selectedEmployeeId), { credentials: 'same-origin', cache: 'no-store' });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Unable to load messages.');

        selectedEmployee = data.employee;
        const name = data.employee.name || 'Employee';
        chatAvatar.textContent = initialsFromName(name);
        chatUserName.textContent = name;
        chatUserMeta.innerHTML = `<span class="online-indicator"></span>${escapeHtml(data.employee.position || 'Employee')}${data.employee.department ? ' · ' + escapeHtml(data.employee.department) : ''}`;

        renderMessages(data.messages || []);
        await loadConversations();
    } catch (error) {
        chatMessages.innerHTML = `<div class="no-messages">Unable to load this conversation.</div>`;
    }
}

function renderMessages(messages) {
    if (!messages.length) {
        chatMessages.innerHTML = `<div class="no-messages">No messages yet.<br>Start the conversation below.</div>`;
        return;
    }

    chatMessages.innerHTML = messages.map(item => {
        const isCompany = item.sender_type === 'company';
        return `
            <div class="message-row ${isCompany ? 'company' : 'employee'}">
                <div>
                    <div class="message-bubble">${escapeHtml(item.message)}</div>
                    <div class="message-time">${escapeHtml(formatMessageTime(item.created_at))}</div>
                </div>
            </div>
        `;
    }).join('');

    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function startThreadPolling() {
    if (threadTimer) clearInterval(threadTimer);
    threadTimer = setInterval(() => { if (selectedEmployeeId) loadThread(); }, 4000);
}

function startConversationPolling() {
    if (conversationTimer) clearInterval(conversationTimer);
    conversationTimer = setInterval(() => { loadConversations(); }, 5000);
}

messageForm.addEventListener('submit', async function(event) {
    event.preventDefault();
    if (!selectedEmployeeId) {
        showToast('Select an employee first.', 'error');
        return;
    }

    const message = messageInput.value.trim();
    if (!message) return;

    sendBtn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('employee_id', selectedEmployeeId);
        formData.append('message', message);

        const response = await fetch('messages.php?api=send', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Unable to send message.');

        messageInput.value = '';
        autoResizeTextarea();
        await loadThread();
        await loadConversations();
    } catch (error) {
        showToast(error.message || 'Unable to send message.', 'error');
    } finally {
        sendBtn.disabled = false;
        messageInput.focus();
    }
});

messageInput.addEventListener('keydown', function(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        messageForm.requestSubmit();
    }
});

function autoResizeTextarea() {
    messageInput.style.height = 'auto';
    messageInput.style.height = Math.min(messageInput.scrollHeight, 130) + 'px';
}

messageInput.addEventListener('input', autoResizeTextarea);
conversationSearch.addEventListener('input', renderConversations);

document.querySelectorAll('.filter-btn').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        activeFilter = button.dataset.filter || 'all';
        renderConversations();
    });
});

function openNewMessageModal() {
    newMessageModal.classList.add('show');
    employeeSearch.value = '';
    selectedModalEmployee = null;
    startChatBtn.disabled = true;
    renderEmployeeResults();
    setTimeout(() => employeeSearch.focus(), 100);
}

function closeNewMessageModal() {
    newMessageModal.classList.remove('show');
}

let selectedModalEmployee = null;

newMessageBtn.addEventListener('click', openNewMessageModal);
closeModalBtn.addEventListener('click', closeNewMessageModal);
cancelModalBtn.addEventListener('click', closeNewMessageModal);
newMessageModal.addEventListener('click', function(event) {
    if (event.target === newMessageModal) closeNewMessageModal();
});

employeeSearch.addEventListener('input', renderEmployeeResults);

function renderEmployeeResults() {
    const search = employeeSearch.value.trim().toLowerCase();
    const filtered = employees.filter(employee => {
        return !search || employee.name.toLowerCase().includes(search) || String(employee.employee_id).toLowerCase().includes(search) || String(employee.department || '').toLowerCase().includes(search) || String(employee.position || '').toLowerCase().includes(search);
    });

    if (filtered.length === 0) {
        employeeResults.innerHTML = `<div class="empty-list"><strong>No employees found</strong>Try another search.</div>`;
        return;
    }

    employeeResults.innerHTML = filtered.map(employee => {
        return `
            <div class="employee-option" data-id="${Number(employee.id)}">
                <div class="avatar">${escapeHtml(employee.initials || initialsFromName(employee.name))}</div>
                <div class="employee-option-info">
                    <div class="employee-option-name">${escapeHtml(employee.name)}</div>
                    <div class="employee-option-meta">
                        ${escapeHtml(employee.employee_id)} · ${escapeHtml(employee.position || 'Employee')}${employee.department ? ' · ' + escapeHtml(employee.department) : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');

    document.querySelectorAll('.employee-option').forEach(option => {
        option.addEventListener('click', () => {
            selectedModalEmployee = employees.find(e => Number(e.id) === Number(option.dataset.id)) || null;
            document.querySelectorAll('.employee-option').forEach(item => { item.style.background = ''; });
            option.style.background = 'var(--qr-surface-alt, #eff6ff)';
            startChatBtn.disabled = !selectedModalEmployee;
        });
    });
}

startChatBtn.addEventListener('click', async function() {
    if (!selectedModalEmployee) return;
    const employeeId = Number(selectedModalEmployee.id);
    closeNewMessageModal();
    await openConversation(employeeId);
});

async function initializeMessages() {
    renderEmployeeResults();
    await loadConversations();
    startConversationPolling();
}

initializeMessages();
</script>

<?php
if (file_exists(__DIR__ . '/includes/layout_bottom.php')) {
    require_once __DIR__ . '/includes/layout_bottom.php';
}
?>