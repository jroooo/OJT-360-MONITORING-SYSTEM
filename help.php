<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$company = require_company();

$pageKey = 'help';
$pageTitle = 'Help & Support';
$eyebrow = 'SUPPORT';

require __DIR__ . '/includes/layout_top.php';
?>

<style>
/* ============================================================
   HELP & SUPPORT PAGE - DUAL THEME SUPPORT
   ============================================================ */

.help-page {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
    box-sizing: border-box;
    font-family: "Segoe UI", Inter, "Helvetica Neue", Arial, sans-serif;
}

.help-page .page-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.help-page .eyebrow {
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

.help-page .eyebrow::before {
    content: "";
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--qr-brand, #2563eb);
    box-shadow: 0 0 10px rgba(37, 99, 235, 0.4);
}

.help-page .title {
    margin: 0;
    font-size: clamp(30px, 3.4vw, 42px);
    line-height: 1.12;
    letter-spacing: -0.04em;
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.help-page .dotc {
    color: var(--qr-brand, #2563eb);
}

.help-page .sub {
    margin: 10px 0 0;
    max-width: 720px;
    color: var(--qr-text-muted, #64748b);
    font-size: 14px;
    line-height: 1.65;
    font-weight: 400;
}

.help-page .faq {
    overflow: hidden;
    padding: 0;
    border: 1px solid var(--qr-border, #e2e8f0);
    border-radius: 20px;
    background: var(--qr-surface, #ffffff);
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
    transition: background 0.2s, border-color 0.2s;
}

.help-page .faq .card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    padding: 22px 24px;
    border-bottom: 1px solid var(--qr-border, #e2e8f0);
    background: var(--qr-surface, #ffffff);
}

.help-page .faq .card-head h2 {
    margin: 0;
    color: var(--qr-text, #0f172a);
    font-size: 18px;
    font-weight: 800;
}

.help-page .faq-search-wrap {
    padding: 18px 24px;
    border-bottom: 1px solid var(--qr-border, #f1f5f9);
    background: var(--qr-surface-alt, #f8fafc);
}

.help-page .faq-search {
    display: block;
    width: 100%;
    max-width: 440px;
    height: 44px;
    padding: 0 14px;
    border: 1px solid var(--qr-border-strong, #cbd5e1);
    border-radius: 12px;
    outline: none;
    background: var(--qr-surface, #ffffff);
    color: var(--qr-text, #0f172a);
    font: inherit;
    font-size: 13px;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.help-page .faq-search::placeholder {
    color: var(--qr-text-subtle, #94a3b8);
}

.help-page .faq-search:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.help-page .faq details {
    margin: 0;
    padding: 0 24px;
    border-bottom: 1px solid var(--qr-border, #edf2f7);
    background: var(--qr-surface, #ffffff);
    transition: background-color 0.18s ease;
}

.help-page .faq details:last-of-type {
    border-bottom: none;
}

.help-page .faq details:hover {
    background: var(--qr-surface-alt, #f8fafc);
}

.help-page .faq details[open] {
    background: var(--qr-surface-alt, #f8fafc);
}

.help-page .faq summary {
    position: relative;
    padding: 19px 32px 19px 0;
    color: var(--qr-text-soft, #1e293b);
    font-size: 14px;
    font-weight: 700;
    line-height: 1.6;
    cursor: pointer;
    list-style: none;
    transition: color 0.18s ease;
}

.help-page .faq summary::-webkit-details-marker {
    display: none;
}

.help-page .faq summary::after {
    content: "+";
    position: absolute;
    top: 50%;
    right: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border: 1px solid var(--qr-border, #e0e7ff);
    border-radius: 8px;
    background: var(--qr-surface-alt, #eef2ff);
    color: var(--qr-brand, #2563eb);
    font-size: 16px;
    font-weight: 700;
    line-height: 1;
    transform: translateY(-50%);
    transition: background-color 0.18s ease, transform 0.18s ease;
}

.help-page .faq details[open] summary {
    color: var(--qr-brand, #2563eb);
}

.help-page .faq details[open] summary::after {
    content: "\2212";
    background: var(--qr-surface-alt, #eef2ff);
}

.help-page .faq details p {
    margin: -2px 0 20px;
    padding-right: 24px;
    color: var(--qr-text-muted, #64748b);
    font-size: 13px;
    line-height: 1.8;
}

.help-page .faq details a {
    color: var(--qr-brand, #2563eb);
    font-weight: 700;
    text-decoration: none;
    text-underline-offset: 3px;
}

.help-page .faq details a:hover {
    color: #1d4ed8;
    text-decoration: underline;
}

.help-page .faq-search-status {
    margin: 9px 0 0;
    color: var(--qr-text-muted, #64748b);
    font-size: 12px;
}

.help-page .faq-empty {
    display: none;
    padding: 36px 24px;
    color: var(--qr-text-muted, #64748b);
    font-size: 13px;
    text-align: center;
}

.help-page .faq-empty.visible {
    display: block;
}

/* ============================================================
   DARK MODE OVERRIDES (DEEP NAVY)
   ============================================================ */
html[data-theme="dark"] .help-page,
.dark .help-page {
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

html[data-theme="dark"] .help-page .title,
.dark .help-page .title,
html[data-theme="dark"] .help-page .faq .card-head h2,
.dark .help-page .faq .card-head h2,
html[data-theme="dark"] .help-page .faq summary,
.dark .help-page .faq summary {
    color: #ffffff !important;
}

html[data-theme="dark"] .help-page .faq,
.dark .help-page .faq {
    background: var(--qr-surface) !important;
    border-color: var(--qr-border) !important;
    box-shadow: 0 15px 45px rgba(0, 0, 0, 0.28) !important;
}

html[data-theme="dark"] .help-page .faq .card-head,
.dark .help-page .faq .card-head,
html[data-theme="dark"] .help-page .faq-search-wrap,
.dark .help-page .faq-search-wrap,
html[data-theme="dark"] .help-page .faq details,
.dark .help-page .faq details {
    border-color: var(--qr-border) !important;
    background: var(--qr-surface) !important;
}

html[data-theme="dark"] .help-page .faq details:hover,
.dark .help-page .faq details:hover,
html[data-theme="dark"] .help-page .faq details[open],
.dark .help-page .faq details[open] {
    background: var(--qr-surface-alt) !important;
}

html[data-theme="dark"] .help-page .faq-search,
.dark .help-page .faq-search {
    background: var(--qr-surface-alt) !important;
    border-color: var(--qr-border) !important;
    color: #ffffff !important;
}

html[data-theme="dark"] .help-page .faq summary::after,
.dark .help-page .faq summary::after {
    background: var(--qr-surface-alt) !important;
    border-color: var(--qr-border) !important;
    color: var(--qr-brand) !important;
}

@media (max-width: 600px) {
    .help-page { padding: 15px; }
    .help-page .faq .card-head { padding: 18px; }
    .help-page .faq-search-wrap { padding: 15px 18px; }
    .help-page .faq details { padding-right: 18px; padding-left: 18px; }
    .help-page .faq summary { padding-top: 17px; padding-bottom: 17px; font-size: 13px; }
    .help-page .faq details p { padding-right: 0; font-size: 13px; }
}
</style>

<div class="help-page">

    <div class="page-head">
        <div>
            <div class="eyebrow">
                <?= e($eyebrow) ?>
            </div>

            <h1 class="title">
                Help &amp; Support<span class="dotc">.</span>
            </h1>

            <p class="sub">
                Quick answers for using the OJT360 Company Portal.
            </p>
        </div>
    </div>

    <section class="card faq">

        <div class="card-head">
            <h2>Frequently asked questions</h2>
        </div>

        <div class="faq-search-wrap">
            <input
                type="search"
                id="faqSearch"
                class="faq-search"
                placeholder="Search questions..."
                aria-label="Search frequently asked questions"
                autocomplete="off"
            >

            <p
                class="faq-search-status"
                id="faqSearchStatus"
                aria-live="polite"
            >
                Browse the questions below or search for a topic.
            </p>
        </div>

        <details open>
            <summary>How do I add an intern?</summary>

            <p>
                Go to
                <a href="assigned_students.php?add=1">
                    <b>Assigned Students</b>
                </a>
                and choose <b>Add Intern</b>. Student ID, name and program
                are required. A Student ID can only be registered once.
            </p>
        </details>

        <details>
            <summary>How do I record an evaluation?</summary>

            <p>
                Open
                <a href="evaluations.php"><b>Evaluations</b></a>,
                choose <b>Evaluate</b> beside an intern, enter a score
                from 0 to 100 and feedback, then save. You can edit it later.
            </p>
        </details>

        <details>
            <summary>How is attendance recorded?</summary>

            <p>
                On
                <a href="dynamic_qr.php"><b>Dynamic QR Code</b></a>
                you can time interns in and out manually. QR-based attendance
                will be enabled when the Student and Company portals are integrated.
            </p>
        </details>

        <details>
            <summary>Where do the report numbers come from?</summary>

            <p>
                <a href="reports.php"><b>Reports &amp; Analytics</b></a>
                totals are calculated live from your interns' hours, attendance
                and evaluation records. The CSV download uses the same data.
            </p>
        </details>

        <details>
            <summary>How do I change my password or profile?</summary>

            <p>
                Open
                <a href="settings.php"><b>Settings</b></a>
                to update your company and representative details or change your password.
            </p>
        </details>

        <details>
            <summary>Why can't I see Student or Employee features?</summary>

            <p>
                This portal covers company tools only. Messaging and attendance
                links to the Student and Employee portals will be added in a later phase.
            </p>
        </details>

        <div class="faq-empty" id="faqEmpty">
            No matching questions found. Try a different search term.
        </div>

    </section>

</div>

<script>
(function () {
    'use strict';

    const searchInput = document.getElementById('faqSearch');
    const searchStatus = document.getElementById('faqSearchStatus');
    const emptyMessage = document.getElementById('faqEmpty');
    const faqCard = document.querySelector('.help-page .faq');

    if (!searchInput || !searchStatus || !emptyMessage || !faqCard) {
        return;
    }

    const questions = Array.from(faqCard.querySelectorAll('details'));

    function filterQuestions() {
        const query = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        questions.forEach(function (item) {
            const searchableText = item.textContent.toLowerCase();
            const matches = searchableText.includes(query);

            item.hidden = !matches;

            if (matches) {
                visibleCount++;
                if (query.length > 0) {
                    item.open = true;
                }
            }
        });

        emptyMessage.classList.toggle('visible', visibleCount === 0);

        if (query.length === 0) {
            searchStatus.textContent =
                'Browse the questions below or search for a topic.';
        } else if (visibleCount === 1) {
            searchStatus.textContent = '1 matching question found.';
        } else {
            searchStatus.textContent =
                visibleCount + ' matching questions found.';
        }
    }

    searchInput.addEventListener('input', filterQuestions);
})();
</script>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>