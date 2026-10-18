<?php
require_once __DIR__ . '/includes/bootstrap.php';

$company = require_company();

$pageKey = 'help';
$pageTitle = 'Help & Support';
$eyebrow = 'SUPPORT';

require __DIR__ . '/includes/layout_top.php';
?>

<style>
/* =========================================
   OJT360 HELP & SUPPORT
========================================= */

.help-page {
    width: 100%;
}

.help-page .page-head {
    margin-bottom: 24px;
}

.help-page .sub {
    color: #64748b;
    line-height: 1.7;
}

.help-page .faq {
    overflow: hidden;
    padding: 0;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.035);
    transition: background .2s, border-color .2s;
}

.help-page .faq .card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    padding: 22px 24px;
    border-bottom: 1px solid #e2e8f0;
}

.help-page .faq .card-head h2 {
    margin: 0;
    color: #0f172a;
    font-size: 17px;
    font-weight: 700;
}

.help-page .faq-search-wrap {
    padding: 18px 24px;
    border-bottom: 1px solid #f1f5f9;
    background: #fbfcfe;
}

.help-page .faq-search {
    display: block;
    width: 100%;
    max-width: 440px;
    padding: 11px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    outline: none;
    background: #ffffff;
    color: #0f172a;
    font: inherit;
    font-size: 14px;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.help-page .faq-search::placeholder {
    color: #94a3b8;
}

.help-page .faq-search:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
}

.help-page .faq details {
    margin: 0;
    padding: 0 24px;
    border-bottom: 1px solid #edf2f7;
    background: #ffffff;
    transition: background-color 0.18s ease;
}

.help-page .faq details:last-of-type {
    border-bottom: none;
}

.help-page .faq details:hover {
    background: #fcfcff;
}

.help-page .faq details[open] {
    background: #fafaff;
}

.help-page .faq summary {
    position: relative;
    padding: 19px 32px 19px 0;
    color: #1e293b;
    font-size: 14px;
    font-weight: 650;
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
    width: 25px;
    height: 25px;
    border: 1px solid #e0e7ff;
    border-radius: 7px;
    background: #eef2ff;
    color: #4338ca;
    font-size: 18px;
    font-weight: 500;
    line-height: 1;
    transform: translateY(-50%);
    transition: background-color 0.18s ease, transform 0.18s ease;
}

.help-page .faq details[open] summary {
    color: #0284c7;
}

.help-page .faq details[open] summary::after {
    content: "\2212";
    background: #e0e7ff;
}

.help-page .faq details p {
    margin: -2px 0 20px;
    padding-right: 24px;
    color: #64748b;
    font-size: 14px;
    line-height: 1.8;
}

.help-page .faq details a {
    color: #0284c7;
    font-weight: 650;
    text-decoration: none;
    text-underline-offset: 3px;
}

.help-page .faq details a:hover {
    color: #0369a1;
    text-decoration: underline;
}

.help-page .faq-search-status {
    margin: 9px 0 0;
    color: #64748b;
    font-size: 12px;
}

.help-page .faq-empty {
    display: none;
    padding: 26px 24px;
    color: #64748b;
    font-size: 14px;
    text-align: center;
}

.help-page .faq-empty.visible {
    display: block;
}

/* DARK MODE ADAPTATION */
html[data-theme="dark"] .help-page .title, .dark .help-page .title,
html[data-theme="dark"] .help-page .faq .card-head h2, .dark .help-page .faq .card-head h2,
html[data-theme="dark"] .help-page .faq summary, .dark .help-page .faq summary { color: #f8fafc !important; }
html[data-theme="dark"] .help-page .sub, .dark .help-page .sub,
html[data-theme="dark"] .help-page .faq-search-status, .dark .help-page .faq-search-status,
html[data-theme="dark"] .help-page .faq details p, .dark .help-page .faq details p { color: #94a3b8 !important; }
html[data-theme="dark"] .help-page .faq, .dark .help-page .faq {
    background: #111c38 !important;
    border-color: #1e293b !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
}
html[data-theme="dark"] .help-page .faq .card-head, .dark .help-page .faq .card-head,
html[data-theme="dark"] .help-page .faq-search-wrap, .dark .help-page .faq-search-wrap {
    border-bottom-color: #1e293b !important;
    background: #111c38 !important;
}
html[data-theme="dark"] .help-page .faq-search, .dark .help-page .faq-search {
    background: #16223f !important;
    border-color: #233354 !important;
    color: #f8fafc !important;
}
html[data-theme="dark"] .help-page .faq details, .dark .help-page .faq details {
    background: #111c38 !important;
    border-bottom-color: #1e293b !important;
}
html[data-theme="dark"] .help-page .faq details:hover, .dark .help-page .faq details:hover,
html[data-theme="dark"] .help-page .faq details[open], .dark .help-page .faq details[open] {
    background: #16223f !important;
}

@media (max-width: 600px) {
    .help-page .faq .card-head {
        padding: 18px;
    }

    .help-page .faq-search-wrap {
        padding: 15px 18px;
    }

    .help-page .faq details {
        padding-right: 18px;
        padding-left: 18px;
    }

    .help-page .faq summary {
        padding-top: 17px;
        padding-bottom: 17px;
        font-size: 13px;
    }

    .help-page .faq details p {
        padding-right: 0;
        font-size: 13px;
    }
}
</style>

<div class="help-page">

    <div class="page-head">
        <div>
            <div class="eyebrow" style="color:#0284c7;font-size:11px;font-weight:800;letter-spacing:.16em;">SUPPORT</div>

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
                <a href="dynamic.php"><b>Dynamic QR Code</b></a>
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