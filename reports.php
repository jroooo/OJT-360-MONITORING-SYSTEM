
<?php
require_once __DIR__ . '/includes/bootstrap.php';

$company = require_company();

$pageKey = 'reports';
$pageTitle = 'Reports';
$eyebrow = 'REPORTS & ANALYTICS';

include __DIR__ . '/includes/layout_top.php';

$reportSamples = [
    [
        'id' => 1,
        'title' => 'Daily Attendance Report',
        'description' => 'Review intern attendance and daily time-in records.',
        'category' => 'attendance',
        'icon' => 'fa-calendar-check',
        'color' => 'indigo',
        'format' => 'PDF',
        'updated' => 'Today',
        'period' => 'Daily',
        'status' => 'Preview'
    ],
    [
        'id' => 2,
        'title' => 'Weekly Attendance Summary',
        'description' => 'A summarized view of attendance across the week.',
        'category' => 'attendance',
        'icon' => 'fa-chart-column',
        'color' => 'blue',
        'format' => 'PDF',
        'updated' => 'Today',
        'period' => 'Weekly',
        'status' => 'Preview'
    ],
    [
        'id' => 3,
        'title' => 'Intern Progress Report',
        'description' => 'Monitor progress and training milestones.',
        'category' => 'interns',
        'icon' => 'fa-user-graduate',
        'color' => 'violet',
        'format' => 'PDF',
        'updated' => 'Yesterday',
        'period' => 'Monthly',
        'status' => 'Preview'
    ],
    [
        'id' => 4,
        'title' => 'Training Hours Report',
        'description' => 'Review accumulated training hours and time records.',
        'category' => 'attendance',
        'icon' => 'fa-clock',
        'color' => 'emerald',
        'format' => 'Excel',
        'updated' => 'Yesterday',
        'period' => 'Monthly',
        'status' => 'Preview'
    ],
    [
        'id' => 5,
        'title' => 'Intern Evaluation Summary',
        'description' => 'Organize evaluation summaries for review.',
        'category' => 'interns',
        'icon' => 'fa-clipboard-check',
        'color' => 'amber',
        'format' => 'PDF',
        'updated' => 'Oct 14',
        'period' => 'Monthly',
        'status' => 'Preview'
    ],
    [
        'id' => 6,
        'title' => 'Company Activity Summary',
        'description' => 'A high-level overview of company OJT activity.',
        'category' => 'company',
        'icon' => 'fa-building',
        'color' => 'slate',
        'format' => 'PDF',
        'updated' => 'Oct 13',
        'period' => 'Monthly',
        'status' => 'Preview'
    ]
];
?>

<style>
.reports-page{color:#0f172a;padding-bottom:28px}
.reports-hero{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:22px}
.reports-hero h1{font-size:29px;letter-spacing:-.04em;font-weight:800;margin:0 0 7px;color:#0f172a}
.reports-hero p{font-size:13px;color:#64748b;margin:0;line-height:1.7}
.reports-eyebrow{font-size:10px;font-weight:800;letter-spacing:.14em;color:#0284c7;margin-bottom:8px}
.reports-note{display:flex;gap:10px;align-items:flex-start;padding:13px 15px;border:1px solid #c7d2fe;border-radius:12px;background:#eef2ff;color:#3730a3;font-size:11px;line-height:1.7;margin-bottom:20px}
.reports-note i{margin-top:3px}
.reports-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px;margin-bottom:20px}
.reports-stat{display:flex;align-items:center;gap:13px;padding:17px;border:1px solid #e2e8f0;border-radius:15px;background:white;box-shadow:0 4px 14px #0f172a08;transition:background .2s,border-color .2s}
.reports-stat-icon{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;flex-shrink:0;font-size:16px}
.reports-stat-icon.indigo{background:#eef2ff;color:#4338ca}
.reports-stat-icon.blue{background:#eff6ff;color:#2563eb}
.reports-stat-icon.emerald{background:#d1fae5;color:#047857}
.reports-stat-label{font-size:11px;color:#64748b;font-weight:600;margin-bottom:5px}
.reports-stat-value{font-size:23px;font-weight:800;line-height:1.1;color:#0f172a}
.reports-toolbar{display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap;padding:17px 20px;border-bottom:1px solid #edf2f7}
.reports-toolbar-title h2{font-size:15px;font-weight:800;margin:0 0 5px;color:#0f172a}
.reports-toolbar-title p{font-size:11px;color:#94a3b8;margin:0}
.reports-toolbar-controls{display:flex;align-items:center;gap:9px;flex-wrap:wrap}
.reports-search{display:flex;align-items:center;gap:8px;height:39px;padding:0 11px;border:1px solid #e2e8f0;border-radius:9px;background:#f8fafc;color:#94a3b8}
.reports-search:focus-within{border-color:#a5b4fc;box-shadow:0 0 0 3px #e0e7ff}
.reports-search input{width:175px;max-width:100%;border:0;outline:0;background:transparent;font-size:11px;color:#0f172a}
.reports-select,.reports-date{height:39px;padding:0 10px;border:1px solid #e2e8f0;border-radius:9px;background:white;color:#475569;font-size:11px;outline:none}
.reports-select:focus,.reports-date:focus{border-color:#a5b4fc}
.reports-tabs{display:flex;gap:6px;padding:13px 20px;border-bottom:1px solid #edf2f7;overflow-x:auto}
.reports-tab{padding:8px 12px;border:1px solid transparent;border-radius:8px;background:transparent;color:#64748b;font-size:11px;font-weight:700;white-space:nowrap;cursor:pointer}
.reports-tab:hover{background:#f8fafc;color:#0284c7}
.reports-tab.active{background:#eef2ff;border-color:#e0e7ff;color:#0284c7}
.reports-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;padding:20px}
.reports-card{display:flex;flex-direction:column;min-width:0;padding:18px;border:1px solid #e2e8f0;border-radius:14px;background:white;transition:border-color .2s,box-shadow .2s,transform .2s,background .2s}
.reports-card:hover{border-color:#c7d2fe;box-shadow:0 8px 24px #0f172a0b;transform:translateY(-2px)}
.reports-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:17px}
.reports-card-icon{width:43px;height:43px;border-radius:12px;display:grid;place-items:center;flex-shrink:0;font-size:16px}
.reports-card-icon.indigo{background:#eef2ff;color:#4338ca}
.reports-card-icon.blue{background:#eff6ff;color:#2563eb}
.reports-card-icon.violet{background:#ede9fe;color:#6d28d9}
.reports-card-icon.emerald{background:#d1fae5;color:#047857}
.reports-card-icon.amber{background:#fef3c7;color:#92400e}
.reports-card-icon.slate{background:#f1f5f9;color:#475569}
.reports-format{padding:5px 7px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;color:#64748b;font-size:9px;font-weight:800}
.reports-card h3{font-size:13px;font-weight:800;color:#1e293b;margin:0 0 7px;line-height:1.5}
.reports-card p{font-size:11px;color:#64748b;line-height:1.7;margin:0 0 17px}
.reports-card-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:12px;border-top:1px solid #f1f5f9;margin-top:auto}
.reports-meta-item{font-size:10px;color:#94a3b8}
.reports-preview-badge{display:inline-flex;align-items:center;gap:5px;padding:5px 7px;border-radius:6px;background:#eef2ff;color:#4338ca;font-size:9px;font-weight:800}
.reports-card-actions{display:flex;gap:8px;margin-top:13px}
.reports-action{display:inline-flex;align-items:center;justify-content:center;gap:7px;flex:1;min-height:37px;padding:9px 10px;border:1px solid #dbe3ee;border-radius:8px;background:white;color:#475569;font-size:10px;font-weight:800;cursor:pointer;transition:background .2s,color .2s}
.reports-action:hover{background:#f8fafc;border-color:#c7d2fe;color:#0284c7}
.reports-action.primary{background:#172554;border-color:#172554;color:white}
.reports-action.primary:hover{background:#1e40af}
.reports-empty{grid-column:1/-1;padding:45px 18px;text-align:center;color:#94a3b8;font-size:12px;line-height:1.8}
.reports-empty i{display:block;font-size:24px;margin-bottom:10px}
.reports-bottom{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 20px;border-top:1px solid #edf2f7;color:#94a3b8;font-size:10px}
.reports-modal-backdrop{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:18px;background:#0f172a80;backdrop-filter:blur(4px)}
.reports-modal-backdrop.open{display:flex}
.reports-modal{width:100%;max-width:520px;max-height:85vh;overflow-y:auto;border:1px solid #e2e8f0;border-radius:18px;background:white;box-shadow:0 25px 80px #0f172a30}
.reports-modal-header{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;padding:21px;border-bottom:1px solid #edf2f7}
.reports-modal-header h2{font-size:16px;font-weight:800;margin:0 0 5px;color:#0f172a}
.reports-modal-header p{font-size:11px;line-height:1.6;color:#64748b;margin:0}
.reports-modal-close{display:grid;place-items:center;width:34px;height:34px;border:1px solid #e2e8f0;border-radius:9px;background:white;color:#64748b;cursor:pointer}
.reports-modal-body{padding:22px}
.reports-modal-icon{width:50px;height:50px;display:grid;place-items:center;border-radius:14px;background:#eef2ff;color:#4338ca;font-size:19px;margin-bottom:15px}
.reports-modal-info{padding:14px;border:1px solid #e2e8f0;border-radius:11px;background:#f8fafc;margin-top:17px}
.reports-modal-info div{display:flex;justify-content:space-between;gap:15px;padding:7px 0;font-size:11px}
.reports-modal-info div+div{border-top:1px solid #e2e8f0}
.reports-modal-info span{color:#64748b}
.reports-modal-info strong{color:#334155;text-align:right}
.reports-modal-footer{display:flex;justify-content:flex-end;gap:9px;padding:15px 21px;border-top:1px solid #edf2f7;background:#fafbfc}
.reports-modal-btn{padding:10px 13px;border:1px solid #e2e8f0;border-radius:9px;background:white;color:#475569;font-size:11px;font-weight:800;cursor:pointer}
.reports-modal-btn.primary{background:#172554;border-color:#172554;color:white}

/* DARK MODE ADAPTATION */
html[data-theme="dark"] .reports-hero h1,
.dark .reports-hero h1,
html[data-theme="dark"] .reports-toolbar-title h2,
.dark .reports-toolbar-title h2,
html[data-theme="dark"] .reports-card h3,
.dark .reports-card h3,
html[data-theme="dark"] .reports-stat-value,
.dark .reports-stat-value,
html[data-theme="dark"] .reports-modal-header h2,
.dark .reports-modal-header h2 {
    color: #f8fafc !important;
}

html[data-theme="dark"] .reports-hero p,
.dark .reports-hero p,
html[data-theme="dark"] .reports-toolbar-title p,
.dark .reports-toolbar-title p,
html[data-theme="dark"] .reports-card p,
.dark .reports-card p,
html[data-theme="dark"] .reports-stat-label,
.dark .reports-stat-label,
html[data-theme="dark"] .reports-meta-item,
.dark .reports-meta-item,
html[data-theme="dark"] .reports-modal-header p,
.dark .reports-modal-header p {
    color: #94a3b8 !important;
}

html[data-theme="dark"] .reports-stat,
.dark .reports-stat,
html[data-theme="dark"] .reports-card,
.dark .reports-card,
html[data-theme="dark"] .reports-modal,
.dark .reports-modal,
html[data-theme="dark"] .reports-page > section {
    background: #111c38 !important;
    border-color: #1e293b !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
}

html[data-theme="dark"] .reports-toolbar,
.dark .reports-toolbar,
html[data-theme="dark"] .reports-tabs,
.dark .reports-tabs,
html[data-theme="dark"] .reports-bottom,
.dark .reports-bottom,
html[data-theme="dark"] .reports-modal-header,
.dark .reports-modal-header,
html[data-theme="dark"] .reports-modal-footer,
.dark .reports-modal-footer {
    border-color: #1e293b !important;
    background: #111c38 !important;
}

html[data-theme="dark"] .reports-search,
.dark .reports-search,
html[data-theme="dark"] .reports-select,
.dark .reports-select,
html[data-theme="dark"] .reports-date,
.dark .reports-date {
    background: #16223f !important;
    border-color: #233354 !important;
    color: #f8fafc !important;
}

html[data-theme="dark"] .reports-action,
.dark .reports-action,
html[data-theme="dark"] .reports-modal-close,
.dark .reports-modal-close,
html[data-theme="dark"] .reports-modal-btn,
.dark .reports-modal-btn {
    background: #16223f !important;
    border-color: #233354 !important;
    color: #f8fafc !important;
}

html[data-theme="dark"] .reports-action.primary,
.dark .reports-action.primary,
html[data-theme="dark"] .reports-modal-btn.primary,
.dark .reports-modal-btn.primary {
    background: #0284c7 !important;
    border-color: #0284c7 !important;
    color: #fff !important;
}

html[data-theme="dark"] .reports-modal-info,
.dark .reports-modal-info {
    background: #16223f !important;
    border-color: #233354 !important;
}

html[data-theme="dark"] .reports-modal-info div,
.dark .reports-modal-info div {
    border-top-color: #233354 !important;
}

html[data-theme="dark"] .reports-format,
.dark .reports-format {
    background: #16223f !important;
    border-color: #233354 !important;
    color: #94a3b8 !important;
}

html[data-theme="dark"] .reports-note {
    background: #172554 !important;
    border-color: #3730a3 !important;
    color: #c7d2fe !important;
}

html[data-theme="dark"] .reports-tab.active {
    background: #1e3a5f !important;
    border-color: #334e78 !important;
    color: #7dd3fc !important;
}

html[data-theme="dark"] .reports-tab:hover {
    background: #16223f !important;
    color: #7dd3fc !important;
}

@media(max-width:1100px) {
    .reports-grid {
        grid-template-columns: repeat(2,minmax(0,1fr));
    }
}

@media(max-width:780px) {
    .reports-stats {
        gap: 10px;
    }

    .reports-stat {
        padding: 13px;
        gap: 9px;
    }

    .reports-stat-icon {
        width: 35px;
        height: 35px;
    }

    .reports-stat-value {
        font-size: 20px;
    }

    .reports-toolbar {
        align-items: flex-start;
    }

    .reports-toolbar-controls {
        width: 100%;
    }

    .reports-search {
        flex: 1;
    }

    .reports-search input {
        width: 100%;
    }
}

@media(max-width:540px) {
    .reports-hero {
        flex-direction: column;
    }

    .reports-hero h1 {
        font-size: 24px;
    }

    .reports-stats {
        grid-template-columns: 1fr;
    }

    .reports-stat {
        padding: 13px 15px;
    }

    .reports-grid {
        grid-template-columns: 1fr;
        padding: 13px;
    }

    .reports-toolbar {
        padding: 15px;
    }

    .reports-toolbar-controls {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    .reports-search {
        grid-column: 1/-1;
    }

    .reports-search input {
        width: 100%;
    }

    .reports-date {
        width: 100%;
        min-width: 0;
    }

    .reports-select {
        width: 100%;
    }

    .reports-tabs {
        padding: 12px;
    }

    .reports-bottom {
        padding: 13px;
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>

<div class="reports-page">

    <div class="reports-hero">
        <div>
            <div class="reports-eyebrow"><?= e($eyebrow) ?></div>
            <h1>Reports &amp; Analytics</h1>
            <p>Explore attendance summaries, intern progress, and company training reports.</p>
        </div>
    </div>

    <div class="reports-note">
        <i class="fa-solid fa-circle-info"></i>
        <div>
            <strong>Preview mode.</strong>
            These report cards use sample information for the UI prototype. Actual report generation, database filters, and downloads will be connected in a later step.
        </div>
    </div>

    <div class="reports-stats">
        <div class="reports-stat">
            <div class="reports-stat-icon indigo"><i class="fa-regular fa-folder-open"></i></div>
            <div>
                <div class="reports-stat-label">Available report templates</div>
                <div class="reports-stat-value"><?= count($reportSamples) ?></div>
            </div>
        </div>

        <div class="reports-stat">
            <div class="reports-stat-icon blue"><i class="fa-solid fa-calendar-check"></i></div>
            <div>
                <div class="reports-stat-label">Attendance templates</div>
                <div class="reports-stat-value">
                    <?= count(array_filter($reportSamples, fn($r) => $r['category'] === 'attendance')) ?>
                </div>
            </div>
        </div>

        <div class="reports-stat">
            <div class="reports-stat-icon emerald"><i class="fa-solid fa-user-graduate"></i></div>
            <div>
                <div class="reports-stat-label">Intern &amp; company templates</div>
                <div class="reports-stat-value">
                    <?= count(array_filter($reportSamples, fn($r) => in_array($r['category'], ['interns', 'company'], true))) ?>
                </div>
            </div>
        </div>
    </div>

    <section style="background:white;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;box-shadow:0 4px 14px #0f172a08;transition:background .2s,border-color .2s;">
        <div class="reports-toolbar">
            <div class="reports-toolbar-title">
                <h2>Report Library</h2>
                <p>Choose a template to preview its details.</p>
            </div>

            <div class="reports-toolbar-controls">
                <label class="reports-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" id="reportSearch" placeholder="Search reports..." autocomplete="off">
                </label>

                <select class="reports-select" id="reportPeriod" aria-label="Filter report period">
                    <option value="all">All periods</option>
                    <option value="Daily">Daily</option>
                    <option value="Weekly">Weekly</option>
                    <option value="Monthly">Monthly</option>
                </select>
            </div>
        </div>

        <div class="reports-tabs">
            <button type="button" class="reports-tab active" data-category="all">All Reports</button>
            <button type="button" class="reports-tab" data-category="attendance">Attendance</button>
            <button type="button" class="reports-tab" data-category="interns">Intern Reports</button>
            <button type="button" class="reports-tab" data-category="company">Company Reports</button>
        </div>

        <div class="reports-grid" id="reportsGrid">
            <?php foreach ($reportSamples as $report): ?>
                <article
                    class="reports-card"
                    data-id="<?= (int) $report['id'] ?>"
                    data-title="<?= e(strtolower($report['title'])) ?>"
                    data-description="<?= e(strtolower($report['description'])) ?>"
                    data-category="<?= e($report['category']) ?>"
                    data-period="<?= e($report['period']) ?>"
                >
                    <div class="reports-card-top">
                        <div class="reports-card-icon <?= e($report['color']) ?>">
                            <i class="fa-solid <?= e($report['icon']) ?>"></i>
                        </div>

                        <span class="reports-format"><?= e($report['format']) ?></span>
                    </div>

                    <h3><?= e($report['title']) ?></h3>
                    <p><?= e($report['description']) ?></p>

                    <div class="reports-card-meta">
                        <span class="reports-meta-item">
                            <i class="fa-regular fa-clock" style="margin-right:4px"></i>
                            <?= e($report['period']) ?>
                        </span>

                        <span class="reports-preview-badge">
                            <i class="fa-regular fa-eye"></i>
                            UI preview
                        </span>
                    </div>

                    <div class="reports-card-actions">
                        <button
                            type="button"
                            class="reports-action primary report-preview-btn"
                            data-report-id="<?= (int) $report['id'] ?>"
                        >
                            <i class="fa-regular fa-eye"></i>
                            Preview
                        </button>

                        <button
                            type="button"
                            class="reports-action report-export-btn"
                            data-report-id="<?= (int) $report['id'] ?>"
                        >
                            <i class="fa-solid fa-download"></i>
                            Export
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>

            <div class="reports-empty" id="reportsEmpty" style="display:none">
                <i class="fa-regular fa-folder-open"></i>
                No reports match your filters.
                <div>Try another category or search term.</div>
            </div>
        </div>

        <div class="reports-bottom">
            <span id="reportsCount">Showing <?= count($reportSamples) ?> report templates</span>
            <span><i class="fa-solid fa-shield-halved" style="margin-right:4px"></i> Sample data only · Not connected to database</span>
        </div>
    </section>
</div>

<div class="reports-modal-backdrop" id="reportModal" role="dialog" aria-modal="true" aria-labelledby="reportModalTitle">
    <div class="reports-modal">
        <div class="reports-modal-header">
            <div>
                <h2 id="reportModalTitle">Report preview</h2>
                <p>Review the report template details.</p>
            </div>

            <button type="button" class="reports-modal-close" id="closeReportModal" aria-label="Close report preview">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="reports-modal-body" id="reportModalBody"></div>

        <div class="reports-modal-footer">
            <button type="button" class="reports-modal-btn" id="cancelReportModal">Close</button>

            <button type="button" class="reports-modal-btn primary" id="modalExportBtn">
                <i class="fa-solid fa-download" style="margin-right:5px"></i>
                Export report
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const reports = <?= json_encode($reportSamples, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    const search = document.getElementById('reportSearch');
    const period = document.getElementById('reportPeriod');
    const cards = Array.from(document.querySelectorAll('.reports-card'));
    const tabs = document.querySelectorAll('.reports-tab');
    const empty = document.getElementById('reportsEmpty');
    const count = document.getElementById('reportsCount');

    const modal = document.getElementById('reportModal');
    const modalTitle = document.getElementById('reportModalTitle');
    const modalBody = document.getElementById('reportModalBody');
    const closeModalBtn = document.getElementById('closeReportModal');
    const cancelModalBtn = document.getElementById('cancelReportModal');
    const modalExportBtn = document.getElementById('modalExportBtn');

    let activeCategory = 'all';
    let selectedReportId = null;

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[character];
        });
    }

    function applyFilters() {
        const query = search.value.trim().toLowerCase();
        const selectedPeriod = period.value;
        let visible = 0;

        cards.forEach(function (card) {
            const categoryMatch =
                activeCategory === 'all' ||
                card.dataset.category === activeCategory;

            const periodMatch =
                selectedPeriod === 'all' ||
                card.dataset.period === selectedPeriod;

            const searchMatch =
                card.dataset.title.includes(query) ||
                card.dataset.description.includes(query);

            const show = categoryMatch && periodMatch && searchMatch;

            card.style.display = show ? 'flex' : 'none';

            if (show) visible++;
        });

        empty.style.display = visible === 0 ? 'block' : 'none';

        count.textContent =
            'Showing ' + visible + ' report template' +
            (visible === 1 ? '' : 's');
    }

    function openReportModal(id, exportMode) {
        const report = reports.find(function (item) {
            return Number(item.id) === Number(id);
        });

        if (!report) return;

        selectedReportId = Number(id);

        modalTitle.textContent = exportMode ? 'Export report' : 'Report preview';

        modalBody.innerHTML = `
            <div class="reports-modal-icon">
                <i class="fa-solid ${escapeHtml(report.icon)}"></i>
            </div>

            <h3 style="font-size:15px;font-weight:800;margin:0 0 8px;">
                ${escapeHtml(report.title)}
            </h3>

            <p style="font-size:12px;line-height:1.8;margin:0">
                ${escapeHtml(report.description)}
            </p>

            <div class="reports-modal-info">
                <div>
                    <span>Report category</span>
                    <strong>${escapeHtml(report.category.charAt(0).toUpperCase() + report.category.slice(1))}</strong>
                </div>
                <div>
                    <span>Reporting period</span>
                    <strong>${escapeHtml(report.period)}</strong>
                </div>
                <div>
                    <span>Suggested format</span>
                    <strong>${escapeHtml(report.format)}</strong>
                </div>
                <div>
                    <span>Data source</span>
                    <strong>Sample preview</strong>
                </div>
                <div>
                    <span>Availability</span>
                    <strong style="color:#0284c7">UI prototype only</strong>
                </div>
            </div>

            <div class="reports-modal-info" style="background:#eef2ff;border-color:#c7d2fe;color:#3730a3">
                <div style="display:block;border:0;padding:0;line-height:1.8">
                    <i class="fa-solid fa-circle-info" style="margin-right:5px"></i>
                    ${exportMode
                        ? 'Real export will be enabled after report data and file generation are connected.'
                        : 'This preview displays template information only. No real attendance or intern records are loaded.'}
                </div>
            </div>
        `;

        modalExportBtn.style.display = exportMode ? 'none' : 'inline-block';
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        closeModalBtn.focus();
    }

    function closeModal() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
        selectedReportId = null;
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activeCategory = tab.dataset.category;

            tabs.forEach(function (item) {
                item.classList.toggle('active', item === tab);
            });

            applyFilters();
        });
    });

    search.addEventListener('input', applyFilters);
    period.addEventListener('change', applyFilters);

    document.querySelectorAll('.report-preview-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            openReportModal(Number(button.dataset.reportId), false);
        });
    });

    document.querySelectorAll('.report-export-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            openReportModal(Number(button.dataset.reportId), true);
        });
    });

    closeModalBtn.addEventListener('click', closeModal);
    cancelModalBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('open')) {
            closeModal();
        }
    });

    modalExportBtn.addEventListener('click', function () {
        if (selectedReportId !== null) {
            openReportModal(selectedReportId, true);
        }
    });

    applyFilters();
})();
</script>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
