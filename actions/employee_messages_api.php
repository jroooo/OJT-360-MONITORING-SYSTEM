<?php
/* Extracted from dashboard(employee).php; runs in the dashboard request scope. */
/* =========================================================

   MESSAGES API (MYSQL)

========================================================= */

if (isset($_GET["message_api"])) {

    header("Content-Type: application/json; charset=utf-8");

 

    $jsonResponse = static function (array $payload, int $status = 200): void {

        http_response_code($status);

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;

    };

 

    $messageApi = (string)$_GET["message_api"];

    $currentEmployeeDbId = (int)$employee["id"];

 

    try {

        if ($messageApi === "companies") {

            $stmt = $conn->prepare("

                SELECT

                    c.id,

                    c.company_name,

                    c.company_code,

                    c.contact_person,

                    c.contact_number,

                    c.email,

                    c.status,

                    registered.id AS registered_company_id,

                    (

                        SELECT m.message

                        FROM messages m

                        WHERE m.company_id = registered.id

                          AND m.employee_id = ?

                        ORDER BY m.created_at DESC, m.id DESC

                        LIMIT 1

                    ) AS last_message,

                    (

                        SELECT m.created_at

                        FROM messages m

                        WHERE m.company_id = registered.id

                          AND m.employee_id = ?

                        ORDER BY m.created_at DESC, m.id DESC

                        LIMIT 1

                    ) AS last_message_time,

                    (

                        SELECT COUNT(*)

                        FROM messages m

                        WHERE m.company_id = registered.id

                          AND m.employee_id = ?

                          AND m.sender_type = 'company'

                          AND m.is_read = 0

                    ) AS unread_count

                FROM company c

                LEFT JOIN companies registered

                    ON registered.company_name = c.company_name

                ORDER BY last_message_time IS NULL ASC,

                         last_message_time DESC,

                         c.company_name ASC

            ");

 

            if (!$stmt) {

                $jsonResponse(["success" => false, "message" => "Unable to load company conversations."], 500);

            }

 

            $stmt->bind_param("iii", $currentEmployeeDbId, $currentEmployeeDbId, $currentEmployeeDbId);

            $stmt->execute();

            $result = $stmt->get_result();

            $rows = [];

 

            while ($row = $result->fetch_assoc()) {

                $rows[] = [

                    "id" => (int)$row["id"],

                    "name" => $row["company_name"],

                    "code" => $row["company_code"] ?? "",

                    "contact" => $row["contact_person"] ?? "",

                    "phone" => $row["contact_number"] ?? "",

                    "email" => $row["email"] ?? "",

                    "status" => $row["status"] ?? "inactive",

                    "registeredCompanyId" => $row["registered_company_id"] !== null ? (int)$row["registered_company_id"] : null,

                    "lastMessage" => $row["last_message"] ?? "",

                    "lastMessageTime" => $row["last_message_time"] ?? null,

                    "unreadCount" => (int)($row["unread_count"] ?? 0)

                ];

            }

 

            $stmt->close();

            $jsonResponse(["success" => true, "companies" => $rows]);

        }

 

        $companyId = filter_input(INPUT_GET, "company_id", FILTER_VALIDATE_INT);

        if (!$companyId || $companyId < 1) {

            $jsonResponse(["success" => false, "message" => "Invalid company."], 400);

        }

 

        $resolve = $conn->prepare("

            SELECT registered.id AS registered_company_id

            FROM company c

            INNER JOIN companies registered

                ON registered.company_name = c.company_name

            WHERE c.id = ?

            LIMIT 1

        ");

 

        if (!$resolve) {

            $jsonResponse(["success" => false, "message" => "Unable to resolve company."], 500);

        }

 

        $resolve->bind_param("i", $companyId);

        $resolve->execute();

        $resolveResult = $resolve->get_result();

        $companyRow = $resolveResult->fetch_assoc();

        $resolve->close();

 

        if (!$companyRow) {

            $jsonResponse([

                "success" => false,

                "message" => "This company has not registered a Company account yet."

            ], 404);

        }

 

        $registeredCompanyId = (int)$companyRow["registered_company_id"];

 

        if ($messageApi === "thread") {

            $mark = $conn->prepare("

                UPDATE messages

                SET is_read = 1

                WHERE company_id = ?

                  AND employee_id = ?

                  AND sender_type = 'company'

                  AND is_read = 0

            ");

            if ($mark) {

                $mark->bind_param("ii", $registeredCompanyId, $currentEmployeeDbId);

                $mark->execute();

                $mark->close();

            }

 

            $stmt = $conn->prepare("

                SELECT id, company_id, employee_id, sender_type, sender_id,

                       message, is_read, created_at

                FROM messages

                WHERE company_id = ?

                  AND employee_id = ?

                ORDER BY created_at ASC, id ASC

            ");

 

            if (!$stmt) {

                $jsonResponse(["success" => false, "message" => "Unable to load messages."], 500);

            }

 

            $stmt->bind_param("ii", $registeredCompanyId, $currentEmployeeDbId);

            $stmt->execute();

            $result = $stmt->get_result();

            $messages = [];

 

            while ($row = $result->fetch_assoc()) {

                $messages[] = [

                    "id" => (int)$row["id"],

                    "sender_type" => $row["sender_type"],

                    "sender_id" => (int)$row["sender_id"],

                    "message" => $row["message"],

                    "is_read" => (int)$row["is_read"],

                    "created_at" => $row["created_at"]

                ];

            }

 

            $stmt->close();

            $jsonResponse(["success" => true, "messages" => $messages]);

        }

 

        if ($messageApi === "send") {

            if ($_SERVER["REQUEST_METHOD"] !== "POST") {

                $jsonResponse(["success" => false, "message" => "Invalid request method."], 405);

            }

 

            $message = trim((string)($_POST["message"] ?? ""));

            if ($message === "") {

                $jsonResponse(["success" => false, "message" => "Message cannot be empty."], 400);

            }

 

            if (mb_strlen($message) > 5000) {

                $jsonResponse(["success" => false, "message" => "Message is too long."], 400);

            }

 

            $stmt = $conn->prepare("

                INSERT INTO messages

                    (company_id, employee_id, sender_type, sender_id, message, is_read)

                VALUES

                    (?, ?, 'employee', ?, ?, 0)

            ");

 

            if (!$stmt) {

                $jsonResponse(["success" => false, "message" => "Unable to send message."], 500);

            }

 

            $stmt->bind_param("iiis", $registeredCompanyId, $currentEmployeeDbId, $currentEmployeeDbId, $message);

            $stmt->execute();

            $newId = (int)$conn->insert_id;

            $stmt->close();

 

            $jsonResponse([

                "success" => true,

                "message" => [

                    "id" => $newId,

                    "sender_type" => "employee",

                    "sender_id" => $currentEmployeeDbId,

                    "message" => $message,

                    "is_read" => 0,

                    "created_at" => date("Y-m-d H:i:s")

                ]

            ]);

        }

 

        if ($messageApi === "mark_read") {

            $stmt = $conn->prepare("

                UPDATE messages

                SET is_read = 1

                WHERE company_id = ?

                  AND employee_id = ?

                  AND sender_type = 'company'

                  AND is_read = 0

            ");

 

            if (!$stmt) {

                $jsonResponse(["success" => false, "message" => "Unable to mark messages as read."], 500);

            }

 

            $stmt->bind_param("ii", $registeredCompanyId, $currentEmployeeDbId);

            $stmt->execute();

            $stmt->close();

 

            $jsonResponse(["success" => true]);

        }

 

        $jsonResponse(["success" => false, "message" => "Unknown message API action."], 400);

    } catch (Throwable $exception) {

        error_log("Employee messages API error: " . $exception->getMessage());

        $jsonResponse(["success" => false, "message" => "A server error occurred while processing the message request."], 500);

    }

}

 

