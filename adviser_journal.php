<?php
session_start();

if (
    !isset($_SESSION["employee_logged_in"]) ||
    $_SESSION["employee_logged_in"] !== true ||
    empty($_SESSION["employee_id"])
) {
    header("Location: login.php");
    exit;
}

$journalIdInput = filter_input(INPUT_GET, "journal_id", FILTER_UNSAFE_RAW);
if ($journalIdInput === null) {
    $journalIdInput = $_GET["journal_id"] ?? null;
}
$journalId = filter_var($journalIdInput, FILTER_VALIDATE_INT, [
    "options" => ["min_range" => 1]
]);
$journal = null;

if (!$journalId) {
    http_response_code(400);
} else {
    require_once __DIR__ . "/config/database.php";

    $journalStmt = $conn->prepare("
        SELECT
            journal.id,
            journal.student_pk,
            journal.week_start,
            journal.title,
            journal.content,
            journal.status,
            student.first_name,
            student.middle_name,
            student.last_name,
            student.student_id,
            (
                SELECT internship.company_name
                FROM internships AS internship
                WHERE internship.student_pk = student.id
                ORDER BY internship.id DESC
                LIMIT 1
            ) AS company_name
        FROM journals AS journal
        INNER JOIN students AS student ON student.id = journal.student_pk
        WHERE journal.id = ?
        LIMIT 1
    ");
    $journalStmt->bind_param("i", $journalId);
    $journalStmt->execute();
    $journal = $journalStmt->get_result()->fetch_assoc();
    $journalStmt->close();

    if (!$journal) {
        http_response_code(404);
    }
}

function adviserJournalEscape(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function adviserJournalContent(string $html, int $studentId): string
{
    if (!class_exists("DOMDocument")) {
        return "<p>" . nl2br(adviserJournalEscape(strip_tags($html))) . "</p>";
    }

    $document = new DOMDocument("1.0", "UTF-8");
    $previousErrorMode = libxml_use_internal_errors(true);
    $document->loadHTML(
        '<?xml encoding="utf-8" ?><div id="journal-root">' . $html . "</div>",
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrorMode);
    $root = $document->getElementById("journal-root");
    if (!$root) {
        return "";
    }

    $allowedTags = [
        "p", "br", "strong", "b", "em", "i", "u", "s", "strike", "sub", "sup",
        "ul", "ol", "li", "h1", "h2", "h3", "blockquote", "div", "span", "a",
        "img", "table", "thead", "tbody", "tr", "td", "th", "hr"
    ];
    $droppedTags = [
        "script", "style", "iframe", "object", "embed", "form", "input", "button",
        "textarea", "select", "link", "meta", "svg", "math", "base", "audio",
        "video", "canvas", "noscript", "template"
    ];
    $allowedStyles = [
        "text-align", "font-family", "font-size", "font-weight", "font-style",
        "text-decoration", "color", "background-color", "line-height", "width",
        "height", "max-width", "vertical-align", "margin", "padding", "text-indent",
        "border", "border-collapse", "border-color", "border-width", "border-style",
        "padding-left", "list-style-type"
    ];

    $sanitizeNode = function (DOMNode $parent) use (
        &$sanitizeNode,
        $allowedTags,
        $droppedTags,
        $allowedStyles,
        $studentId
    ): void {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof DOMComment) {
                $parent->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, $droppedTags, true)) {
                $parent->removeChild($child);
                continue;
            }
            if (!in_array($tag, $allowedTags, true)) {
                $sanitizeNode($child);
                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);
                continue;
            }

            $attributes = [];
            foreach ($child->attributes as $attribute) {
                $attributes[] = [$attribute->name, $attribute->value];
            }
            foreach ($attributes as [$name, $value]) {
                $child->removeAttribute($name);
                $name = strtolower($name);

                if ($name === "style") {
                    $safeDeclarations = [];
                    foreach (explode(";", $value) as $declaration) {
                        $parts = explode(":", $declaration, 2);
                        if (count($parts) !== 2) {
                            continue;
                        }
                        $property = strtolower(trim($parts[0]));
                        $styleValue = trim($parts[1]);
                        if (
                            in_array($property, $allowedStyles, true) &&
                            $styleValue !== "" &&
                            strlen($styleValue) <= 140 &&
                            !preg_match('/[<>`\\\\]|url\s*\(|expression|javascript|@import|behavior|binding/i', $styleValue)
                        ) {
                            $safeDeclarations[] = $property . ":" . $styleValue;
                        }
                    }
                    if ($safeDeclarations) {
                        $child->setAttribute("style", implode(";", $safeDeclarations));
                    }
                } elseif ($name === "href" && $tag === "a" && preg_match('/^(https?:\/\/|mailto:)/i', $value)) {
                    $child->setAttribute("href", $value);
                    $child->setAttribute("target", "_blank");
                    $child->setAttribute("rel", "noopener noreferrer");
                } elseif ($name === "src" && $tag === "img") {
                    $pattern = '#^uploads/journal/' . $studentId . '/[A-Za-z0-9._-]+$#';
                    if (preg_match($pattern, $value)) {
                        $child->setAttribute("src", "ojt360-student-dashboard/" . $value);
                    }
                } elseif ($name === "alt" && $tag === "img") {
                    $child->setAttribute("alt", mb_substr($value, 0, 160));
                } elseif (
                    in_array($name, ["colspan", "rowspan"], true) &&
                    ctype_digit($value) &&
                    (int)$value > 0 &&
                    (int)$value < 30
                ) {
                    $child->setAttribute($name, $value);
                }
            }

            if ($tag === "img" && !$child->hasAttribute("src")) {
                $parent->removeChild($child);
                continue;
            }
            $sanitizeNode($child);
        }
    };

    $sanitizeNode($root);
    $content = "";
    foreach ($root->childNodes as $child) {
        $content .= $document->saveHTML($child);
    }
    return $content;
}

$studentName = $journal
    ? trim(implode(" ", array_filter([
        $journal["first_name"],
        $journal["middle_name"],
        $journal["last_name"]
    ], static fn ($name): bool => trim((string)$name) !== "")))
    : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $journal ? adviserJournalEscape($journal["title"]) . " | OJT 360" : "Journal not found | OJT 360" ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .adviser-journal-page{min-height:100vh;padding:32px 18px 56px}
        .adviser-journal-wrap{width:min(100%,960px);margin:0 auto}
        .adviser-journal-back{display:inline-flex;align-items:center;gap:8px;margin-bottom:20px;text-decoration:none}
        .adviser-journal-card{overflow:hidden;background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:var(--shadow)}
        .adviser-journal-head{padding:26px 32px;border-bottom:1px solid var(--line)}
        .adviser-journal-eyebrow{margin-bottom:8px;color:var(--blue);font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
        .adviser-journal-head h1{margin:0 0 16px;font-size:26px;line-height:1.3;overflow-wrap:anywhere}
        .adviser-journal-meta{display:flex;flex-wrap:wrap;gap:8px 22px;color:var(--muted);font-size:13px}
        .adviser-journal-meta strong{color:var(--ink)}
        .adviser-journal-content{min-height:420px;padding:38px clamp(22px,7vw,72px);color:#172033;background:#fff;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.65;overflow-wrap:anywhere}
        .adviser-journal-content p{margin:0 0 .8em}
        .adviser-journal-content h1,.adviser-journal-content h2,.adviser-journal-content h3{line-height:1.3}
        .adviser-journal-content img{max-width:100%;height:auto}
        .adviser-journal-content table{width:100%;border-collapse:collapse}
        .adviser-journal-content th,.adviser-journal-content td{padding:7px;border:1px solid #cbd5e1}
        .adviser-journal-empty{padding:48px 28px;text-align:center;color:var(--muted)}
        @media(max-width:600px){.adviser-journal-page{padding:18px 12px 32px}.adviser-journal-head{padding:22px 20px}.adviser-journal-head h1{font-size:22px}.adviser-journal-content{min-height:300px;padding:26px 20px}}
    </style>
</head>
<body>
<main class="adviser-journal-page">
    <div class="adviser-journal-wrap">
        <a class="btn btn-light adviser-journal-back" href="adviser_companies.php">&larr; Back to Companies</a>
        <article class="adviser-journal-card">
            <?php if ($journal): ?>
                <header class="adviser-journal-head">
                    <div class="adviser-journal-eyebrow">Weekly OJT Journal</div>
                    <h1><?= adviserJournalEscape($journal["title"]) ?></h1>
                    <div class="adviser-journal-meta">
                        <span><strong>Student:</strong> <?= adviserJournalEscape($studentName) ?> (<?= adviserJournalEscape($journal["student_id"]) ?>)</span>
                        <span><strong>Company:</strong> <?= adviserJournalEscape($journal["company_name"] ?: "Unassigned") ?></span>
                        <span><strong>Week starting:</strong> <?= adviserJournalEscape($journal["week_start"]) ?></span>
                        <span><strong>Status:</strong> <?= adviserJournalEscape(ucfirst($journal["status"])) ?></span>
                    </div>
                </header>
                <section class="adviser-journal-content" aria-label="Journal draft">
                    <?= adviserJournalContent((string)$journal["content"], (int)$journal["student_pk"]) ?>
                </section>
            <?php else: ?>
                <div class="adviser-journal-empty">
                    <h1>Journal not found</h1>
                    <p>This journal may have been removed or the link is invalid.</p>
                </div>
            <?php endif; ?>
        </article>
    </div>
</main>
</body>
</html>
