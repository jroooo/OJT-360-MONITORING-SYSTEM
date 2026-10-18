<?php
require_once __DIR__ . '/includes/bootstrap.php';

$company = require_company();

$pageKey = 'messages';
$pageTitle = 'Messages';
$eyebrow = 'COMMUNICATION CENTER';

include __DIR__ . '/includes/layout_top.php';

$sampleMessages = [
    [
        'id' => 1,
        'name' => 'Alexandra Reyes',
        'role' => 'OJT Intern',
        'initials' => 'AR',
        'color' => 'indigo',
        'subject' => 'Question about my OJT schedule',
        'message' => 'Good morning! I would like to clarify my assigned schedule for next week. Should I follow the same time-in and time-out schedule?',
        'time' => '9:42 AM',
        'date' => 'Today',
        'unread' => true,
        'category' => 'students',
        'online' => true,
        'history' => [
            [
                'from' => 'them',
                'time' => '9:42 AM',
                'text' => 'Good morning! I would like to clarify my assigned schedule for next week. Should I follow the same time-in and time-out schedule? Thank you!'
            ]
        ]
    ],
    [
        'id' => 2,
        'name' => 'Michael Santos',
        'role' => 'OJT Intern',
        'initials' => 'MS',
        'color' => 'blue',
        'subject' => 'Weekly accomplishment report',
        'message' => 'I have prepared my weekly accomplishment report for review. Please let me know if there are any revisions needed.',
        'time' => 'Yesterday',
        'date' => 'Yesterday',
        'unread' => true,
        'category' => 'students',
        'online' => false,
        'history' => [
            [
                'from' => 'them',
                'time' => 'Yesterday, 3:18 PM',
                'text' => 'Good afternoon! I have prepared my weekly accomplishment report for review. Please let me know if there are any revisions needed.'
            ]
        ]
    ],
    [
        'id' => 3,
        'name' => 'Jamie Cruz',
        'role' => 'OJT Intern',
        'initials' => 'JC',
        'color' => 'violet',
        'subject' => 'Attendance clarification',
        'message' => 'Thank you for clarifying my attendance record. I will make sure to follow the proper procedure moving forward.',
        'time' => 'Yesterday',
        'date' => 'Yesterday',
        'unread' => false,
        'category' => 'students',
        'online' => false,
        'history' => [
            [
                'from' => 'them',
                'time' => 'Yesterday, 10:10 AM',
                'text' => 'Good morning! I noticed a discrepancy in my attendance record and would like to ask for clarification.'
            ],
            [
                'from' => 'me',
                'time' => 'Yesterday, 10:35 AM',
                'text' => 'Please coordinate with your assigned supervisor so the attendance record can be checked.'
            ],
            [
                'from' => 'them',
                'time' => 'Yesterday, 10:40 AM',
                'text' => 'Thank you for clarifying my attendance record. I will make sure to follow the proper procedure moving forward.'
            ]
        ]
    ],
    [
        'id' => 4,
        'name' => 'OJT Coordination',
        'role' => 'System notification',
        'initials' => 'OC',
        'color' => 'emerald',
        'subject' => 'Reminder: Weekly progress updates',
        'message' => 'Please ensure that intern progress updates are reviewed regularly.',
        'time' => 'Oct 14',
        'date' => 'Oct 14',
        'unread' => false,
        'category' => 'system',
        'online' => false,
        'history' => [
            [
                'from' => 'them',
                'time' => 'Oct 14, 8:00 AM',
                'text' => 'Please ensure that intern progress updates are reviewed regularly. Thank you for supporting your assigned interns.'
            ]
        ]
    ],
    [
        'id' => 5,
        'name' => 'Daniel Garcia',
        'role' => 'OJT Intern',
        'initials' => 'DG',
        'color' => 'amber',
        'subject' => 'Request for supervisor feedback',
        'message' => 'May I request feedback regarding my performance during this training period?',
        'time' => 'Oct 13',
        'date' => 'Oct 13',
        'unread' => false,
        'category' => 'students',
        'online' => false,
        'history' => [
            [
                'from' => 'them',
                'time' => 'Oct 13, 2:15 PM',
                'text' => 'Good afternoon! May I request feedback regarding my performance during this training period? I would appreciate any suggestions for improvement.'
            ]
        ]
    ]
];
?>

<style>
.messages-page {
    color: #0f172a;
    padding-bottom: 28px;
}
.messages-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    margin-bottom: 22px;
}
.messages-hero h1 {
    font-size: 29px;
    letter-spacing: -.04em;
    font-weight: 800;
    margin: 0 0 7px;
    color: #0f172a;
}
.messages-hero p {
    font-size: 13px;
    color: #64748b;
    margin: 0;
    line-height: 1.7;
}
.messages-eyebrow {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .14em;
    color: #0284c7;
    margin-bottom: 8px;
}
.messages-preview-note {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 13px 15px;
    border: 1px solid #c7d2fe;
    border-radius: 12px;
    background: #eef2ff;
    color: #3730a3;
    font-size: 11px;
    line-height: 1.7;
    margin-bottom: 20px;
}
.messages-preview-note i {
    margin-top: 3px;
}
.messages-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.messages-stat {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 17px;
    border: 1px solid #e2e8f0;
    border-radius: 15px;
    background: white;
    box-shadow: 0 4px 14px #0f172a08;
    transition: background .2s, border-color .2s;
}
.messages-stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    font-size: 16px;
}
.messages-stat-icon.indigo {
    background: #eef2ff;
    color: #4338ca;
}
.messages-stat-icon.blue {
    background: #eff6ff;
    color: #2563eb;
}
.messages-stat-icon.emerald {
    background: #d1fae5;
    color: #047857;
}
.messages-stat-label {
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 5px;
}
.messages-stat-value {
    font-size: 23px;
    font-weight: 800;
    line-height: 1.1;
    color: #0f172a;
}
.messages-workspace {
    display: grid;
    grid-template-columns: minmax(290px, .86fr) minmax(0, 1.4fr);
    min-height: 600px;
    overflow: hidden;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    box-shadow: 0 4px 14px #0f172a08;
    transition: background .2s, border-color .2s;
}
.messages-inbox {
    min-width: 0;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
}
.messages-inbox-header {
    padding: 20px;
    border-bottom: 1px solid #edf2f7;
}
.messages-section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}
.messages-section-heading h2 {
    font-size: 15px;
    font-weight: 800;
    margin: 0 0 4px;
    color: #0f172a;
}
.messages-section-heading p {
    color: #94a3b8;
    font-size: 11px;
    margin: 0;
}
.messages-compose-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 10px 13px;
    border: 1px solid #172554;
    border-radius: 9px;
    background: #172554;
    color: white;
    font-size: 11px;
    font-weight: 800;
    cursor: pointer;
    transition: background .2s, transform .2s;
}
.messages-compose-btn:hover {
    background: #1e40af;
    transform: translateY(-1px);
}
.messages-search {
    display: flex;
    align-items: center;
    gap: 9px;
    height: 42px;
    padding: 0 12px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fafc;
    color: #94a3b8;
}
.messages-search:focus-within {
    border-color: #a5b4fc;
    box-shadow: 0 0 0 3px #e0e7ff;
}
.messages-search input {
    width: 100%;
    min-width: 0;
    border: 0;
    outline: 0;
    background: transparent;
    color: #0f172a;
    font-size: 12px;
}
.messages-search input::placeholder {
    color: #94a3b8;
}
.messages-filters {
    display: flex;
    gap: 5px;
    padding: 12px 15px;
    border-bottom: 1px solid #edf2f7;
    overflow-x: auto;
}
.messages-filter {
    padding: 8px 11px;
    border: 1px solid transparent;
    border-radius: 8px;
    background: transparent;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
    cursor: pointer;
}
.messages-filter:hover {
    background: #f8fafc;
    color: #0284c7;
}
.messages-filter.active {
    background: #eef2ff;
    border-color: #e0e7ff;
    color: #0284c7;
}
.messages-list {
    flex: 1;
}
.messages-conversation {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    width: 100%;
    padding: 16px 17px;
    border: 0;
    border-bottom: 1px solid #f1f5f9;
    border-left: 3px solid transparent;
    background: white;
    color: inherit;
    text-align: left;
    cursor: pointer;
    transition: background .15s;
}
.messages-conversation:hover {
    background: #f8fafc;
}
.messages-conversation.active {
    border-left-color: #0284c7;
    background: #f0f9ff;
}
.messages-avatar {
    position: relative;
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    border-radius: 13px;
    font-size: 11px;
    font-weight: 800;
}
.messages-avatar.indigo {
    background: #e0e7ff;
    color: #4338ca;
}
.messages-avatar.blue {
    background: #dbeafe;
    color: #1d4ed8;
}
.messages-avatar.violet {
    background: #ede9fe;
    color: #6d28d9;
}
.messages-avatar.emerald {
    background: #d1fae5;
    color: #047857;
}
.messages-avatar.amber {
    background: #fef3c7;
    color: #92400e;
}
.messages-online {
    position: absolute;
    right: -2px;
    bottom: -2px;
    width: 11px;
    height: 11px;
    border: 2px solid white;
    border-radius: 50%;
    background: #22c55e;
}
.messages-conversation-body {
    flex: 1;
    min-width: 0;
}
.messages-conversation-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 5px;
}
.messages-conversation-name {
    overflow: hidden;
    color: #1e293b;
    font-size: 12px;
    font-weight: 800;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.messages-conversation-time {
    flex-shrink: 0;
    color: #94a3b8;
    font-size: 9px;
}
.messages-conversation-subject {
    overflow: hidden;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-bottom: 4px;
}
.messages-conversation-snippet {
    overflow: hidden;
    color: #94a3b8;
    font-size: 10px;
    line-height: 1.6;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
.messages-unread-dot {
    display: inline-block;
    width: 7px;
    height: 7px;
    flex-shrink: 0;
    border-radius: 50%;
    background: #0284c7;
}
.messages-no-results {
    padding: 36px 18px;
    color: #94a3b8;
    font-size: 12px;
    text-align: center;
    line-height: 1.8;
}
.messages-preview {
    display: flex;
    flex-direction: column;
    min-width: 0;
    background: white;
}
.messages-preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 20px 23px;
    border-bottom: 1px solid #edf2f7;
}
.messages-person {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.messages-person-info {
    min-width: 0;
}
.messages-person-name {
    overflow: hidden;
    color: #0f172a;
    font-size: 13px;
    font-weight: 800;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.messages-person-role {
    color: #94a3b8;
    font-size: 10px;
    margin-top: 4px;
}
.messages-preview-actions {
    display: flex;
    gap: 7px;
    flex-shrink: 0;
}
.messages-icon-btn {
    display: grid;
    place-items: center;
    width: 35px;
    height: 35px;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    background: white;
    color: #64748b;
    cursor: pointer;
}
.messages-icon-btn:hover {
    border-color: #c7d2fe;
    background: #eef2ff;
    color: #4338ca;
}
.messages-subject-bar {
    padding: 17px 23px;
    background: #fafbff;
    border-bottom: 1px solid #edf2f7;
}
.messages-subject-label {
    color: #94a3b8;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    margin-bottom: 5px;
}
.messages-subject {
    color: #1e293b;
    font-size: 13px;
    font-weight: 800;
}
.messages-thread {
    flex: 1;
    min-height: 290px;
    padding: 25px 23px;
    background:
        radial-gradient(circle at 1px 1px, #e2e8f0 1px, transparent 0);
    background-size: 20px 20px;
}
.messages-date-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 25px;
    color: #94a3b8;
    font-size: 9px;
    font-weight: 700;
}
.messages-date-divider::before,
.messages-date-divider::after {
    content: "";
    height: 1px;
    flex: 1;
    background: #e2e8f0;
}
.messages-bubble-row {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    margin-bottom: 19px;
}
.messages-bubble-row.mine {
    justify-content: flex-end;
}
.messages-mini-avatar {
    display: grid;
    place-items: center;
    width: 29px;
    height: 29px;
    flex-shrink: 0;
    border-radius: 9px;
    background: #e0e7ff;
    color: #4338ca;
    font-size: 9px;
    font-weight: 800;
}
.messages-bubble-wrap {
    max-width: min(82%, 460px);
}
.messages-bubble-sender {
    color: #64748b;
    font-size: 10px;
    font-weight: 700;
    margin: 0 0 6px 3px;
}
.messages-bubble {
    padding: 12px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 4px 14px 14px 14px;
    background: white;
    color: #475569;
    font-size: 12px;
    line-height: 1.8;
    overflow-wrap: anywhere;
    box-shadow: 0 2px 6px #0f172a05;
}
.messages-bubble-row.mine .messages-bubble {
    border-color: #172554;
    border-radius: 14px 4px 14px 14px;
    background: #172554;
    color: white;
}
.messages-bubble-time {
    color: #94a3b8;
    font-size: 9px;
    margin-top: 6px;
}
.messages-bubble-row.mine .messages-bubble-time {
    text-align: right;
}
.messages-reply-area {
    padding: 17px 22px 20px;
    border-top: 1px solid #edf2f7;
    background: white;
}
.messages-reply-disabled {
    padding: 13px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 11px;
    background: #f8fafc;
    color: #94a3b8;
    font-size: 11px;
    line-height: 1.7;
}
.messages-reply-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
    color: #94a3b8;
    font-size: 10px;
}
.messages-reply-send {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 12px;
    border: 0;
    border-radius: 8px;
    background: #cbd5e1;
    color: white;
    font-size: 10px;
    font-weight: 800;
    cursor: not-allowed;
}
.messages-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 18px;
    background: #0f172a80;
    backdrop-filter: blur(4px);
}
.messages-modal-backdrop.open {
    display: flex;
}
.messages-modal {
    width: 100%;
    max-width: 500px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    background: white;
    box-shadow: 0 25px 80px #0f172a30;
}
.messages-modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 15px;
    padding: 22px;
    border-bottom: 1px solid #edf2f7;
}
.messages-modal-header h2 {
    margin: 0 0 6px;
    color: #0f172a;
    font-size: 17px;
    font-weight: 800;
}
.messages-modal-header p {
    margin: 0;
    color: #64748b;
    font-size: 11px;
    line-height: 1.6;
}
.messages-modal-body {
    padding: 22px;
}
.messages-form-field {
    margin-bottom: 17px;
}
.messages-form-field label {
    display: block;
    margin-bottom: 7px;
    color: #334155;
    font-size: 11px;
    font-weight: 800;
}
.messages-form-field input,
.messages-form-field select,
.messages-form-field textarea {
    display: block;
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #dbe3ee;
    border-radius: 9px;
    outline: none;
    background: white;
    color: #334155;
    font: inherit;
    font-size: 12px;
}
.messages-form-field textarea {
    min-height: 110px;
    resize: vertical;
}
.messages-form-field input:focus,
.messages-form-field select:focus,
.messages-form-field textarea:focus {
    border-color: #818cf8;
    box-shadow: 0 0 0 3px #e0e7ff;
}
.messages-modal-notice {
    padding: 11px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    background: #f8fafc;
    color: #64748b;
    font-size: 10px;
    line-height: 1.7;
}
.messages-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    padding: 15px 22px;
    border-top: 1px solid #edf2f7;
    background: #fafbfc;
}
.messages-modal-cancel {
    padding: 10px 13px;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    background: white;
    color: #475569;
    font-size: 11px;
    font-weight: 800;
    cursor: pointer;
}
.messages-modal-submit {
    padding: 10px 13px;
    border: 0;
    border-radius: 9px;
    background: #cbd5e1;
    color: white;
    font-size: 11px;
    font-weight: 800;
    cursor: not-allowed;
}

/* DARK MODE ADAPTATION */
html[data-theme="dark"] .messages-hero h1, .dark .messages-hero h1,
html[data-theme="dark"] .messages-section-heading h2, .dark .messages-section-heading h2,
html[data-theme="dark"] .messages-conversation-name, .dark .messages-conversation-name,
html[data-theme="dark"] .messages-person-name, .dark .messages-person-name,
html[data-theme="dark"] .messages-subject, .dark .messages-subject,
html[data-theme="dark"] .messages-stat-value, .dark .messages-stat-value,
html[data-theme="dark"] .messages-modal-header h2, .dark .messages-modal-header h2 { color: #f8fafc !important; }
html[data-theme="dark"] .messages-hero p, .dark .messages-hero p,
html[data-theme="dark"] .messages-section-heading p, .dark .messages-section-heading p,
html[data-theme="dark"] .messages-stat-label, .dark .messages-stat-label,
html[data-theme="dark"] .messages-conversation-subject, .dark .messages-conversation-subject,
html[data-theme="dark"] .messages-conversation-snippet, .dark .messages-conversation-snippet,
html[data-theme="dark"] .messages-modal-header p, .dark .messages-modal-header p { color: #94a3b8 !important; }
html[data-theme="dark"] .messages-stat, .dark .messages-stat,
html[data-theme="dark"] .messages-workspace, .dark .messages-workspace,
html[data-theme="dark"] .messages-preview, .dark .messages-preview,
html[data-theme="dark"] .messages-modal, .dark .messages-modal {
    background: #111c38 !important;
    border-color: #1e293b !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
}
html[data-theme="dark"] .messages-inbox, .dark .messages-inbox { border-right-color: #1e293b !important; }
html[data-theme="dark"] .messages-inbox-header, .dark .messages-inbox-header,
html[data-theme="dark"] .messages-filters, .dark .messages-filters,
html[data-theme="dark"] .messages-preview-header, .dark .messages-preview-header,
html[data-theme="dark"] .messages-subject-bar, .dark .messages-subject-bar,
html[data-theme="dark"] .messages-reply-area, .dark .messages-reply-area,
html[data-theme="dark"] .messages-modal-header, .dark .messages-modal-header,
html[data-theme="dark"] .messages-modal-footer, .dark .messages-modal-footer {
    border-color: #1e293b !important;
    background: #111c38 !important;
}
html[data-theme="dark"] .messages-search, .dark .messages-search,
html[data-theme="dark"] .messages-form-field input, .dark .messages-form-field input,
html[data-theme="dark"] .messages-form-field select, .dark .messages-form-field select,
html[data-theme="dark"] .messages-form-field textarea, .dark .messages-form-field textarea {
    background: #16223f !important;
    border-color: #233354 !important;
    color: #f8fafc !important;
}
html[data-theme="dark"] .messages-conversation, .dark .messages-conversation {
    background: #111c38 !important;
    border-bottom-color: #1e293b !important;
}
html[data-theme="dark"] .messages-conversation:hover, .dark .messages-conversation:hover {
    background: #16223f !important;
}
html[data-theme="dark"] .messages-conversation.active, .dark .messages-conversation.active {
    background: #16223f !important;
    border-left-color: #0284c7 !important;
}
html[data-theme="dark"] .messages-thread, .dark .messages-thread {
    background: #0b1329 !important;
    background-image: radial-gradient(circle at 1px 1px, #1e293b 1px, transparent 0) !important;
}
html[data-theme="dark"] .messages-bubble, .dark .messages-bubble {
    background: #16223f !important;
    border-color: #233354 !important;
    color: #e2e8f0 !important;
}
html[data-theme="dark"] .messages-bubble-row.mine .messages-bubble, .dark .messages-bubble-row.mine .messages-bubble {
    background: #0284c7 !important;
    border-color: #0284c7 !important;
    color: #fff !important;
}
html[data-theme="dark"] .messages-icon-btn, .dark .messages-icon-btn,
html[data-theme="dark"] .messages-modal-cancel, .dark .messages-modal-cancel {
    background: #16223f !important;
    border-color: #233354 !important;
    color: #f8fafc !important;
}

@media(max-width:1050px) {
    .messages-workspace {
        grid-template-columns: minmax(260px, .9fr) minmax(0, 1.1fr);
    }
    .messages-inbox-header {
        padding: 16px;
    }
    .messages-conversation {
        padding: 14px 12px;
    }
}
@media(max-width:780px) {
    .messages-stats {
        gap: 10px;
    }
    .messages-stat {
        padding: 13px;
        gap: 9px;
    }
    .messages-stat-icon {
        width: 35px;
        height: 35px;
    }
    .messages-stat-value {
        font-size: 20px;
    }
    .messages-workspace {
        grid-template-columns: 1fr;
    }
    .messages-inbox {
        border-right: 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .messages-list {
        max-height: 390px;
        overflow-y: auto;
    }
    .messages-preview {
        min-height: 500px;
    }
}
@media(max-width:540px) {
    .messages-hero {
        flex-direction: column;
    }
    .messages-hero h1 {
        font-size: 24px;
    }
    .messages-stats {
        grid-template-columns: 1fr;
    }
    .messages-stat {
        padding: 13px 15px;
    }
    .messages-preview-header {
        padding: 15px;
    }
    .messages-subject-bar {
        padding: 14px 16px;
    }
    .messages-thread {
        padding: 20px 13px;
    }
    .messages-reply-area {
        padding: 14px;
    }
    .messages-bubble-wrap {
        max-width: 88%;
    }
    .messages-section-heading {
        align-items: flex-start;
    }
}
</style>

<div class="messages-page">

    <div class="messages-hero">
        <div>
            <div class="messages-eyebrow"><?= e($eyebrow) ?></div>
            <h1>Messages</h1>
            <p>Keep in touch with your interns and stay updated on important conversations.</p>
        </div>

        <button type="button" class="messages-compose-btn" id="openCompose">
            <i class="fa-solid fa-pen-to-square"></i>
            Compose message
        </button>
    </div>

    <div class="messages-preview-note">
        <i class="fa-solid fa-circle-info"></i>
        <div>
            <strong>Design preview mode.</strong>
            The conversations shown here are sample data for the UI only. Messaging, sending, and employee dashboard integration are not connected yet.
        </div>
    </div>

    <div class="messages-stats">
        <div class="messages-stat">
            <div class="messages-stat-icon indigo">
                <i class="fa-regular fa-envelope"></i>
            </div>
            <div>
                <div class="messages-stat-label">Total conversations</div>
                <div class="messages-stat-value"><?= count($sampleMessages) ?></div>
            </div>
        </div>

        <div class="messages-stat">
            <div class="messages-stat-icon blue">
                <i class="fa-solid fa-envelope-open-text"></i>
            </div>
            <div>
                <div class="messages-stat-label">Unread messages</div>
                <div class="messages-stat-value">
                    <?= count(array_filter($sampleMessages, fn($m) => $m['unread'])) ?>
                </div>
            </div>
        </div>

        <div class="messages-stat">
            <div class="messages-stat-icon emerald">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="messages-stat-label">Intern conversations</div>
                <div class="messages-stat-value">
                    <?= count(array_filter($sampleMessages, fn($m) => $m['category'] === 'students')) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="messages-workspace">

        <section class="messages-inbox">
            <div class="messages-inbox-header">
                <div class="messages-section-heading">
                    <div>
                        <h2>Inbox</h2>
                        <p>Your recent conversations</p>
                    </div>

                    <span style="font-size:10px;font-weight:800;color:#64748b;background:#f1f5f9;padding:6px 9px;border-radius:8px">
                        <?= count($sampleMessages) ?> messages
                    </span>
                </div>

                <label class="messages-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" id="messageSearch" placeholder="Search messages..." autocomplete="off">
                </label>
            </div>

            <div class="messages-filters">
                <button class="messages-filter active" type="button" data-filter="all">All</button>
                <button class="messages-filter" type="button" data-filter="unread">Unread</button>
                <button class="messages-filter" type="button" data-filter="students">Interns</button>
                <button class="messages-filter" type="button" data-filter="system">System</button>
            </div>

            <div class="messages-list" id="messagesList">
                <?php foreach ($sampleMessages as $message): ?>
                    <button
                        type="button"
                        class="messages-conversation<?= $message['id'] === 1 ? ' active' : '' ?>"
                        data-id="<?= (int) $message['id'] ?>"
                        data-name="<?= e(strtolower($message['name'])) ?>"
                        data-subject="<?= e(strtolower($message['subject'])) ?>"
                        data-message="<?= e(strtolower($message['message'])) ?>"
                        data-category="<?= e($message['category']) ?>"
                        data-unread="<?= $message['unread'] ? '1' : '0' ?>"
                    >
                        <div class="messages-avatar <?= e($message['color']) ?>">
                            <?= e($message['initials']) ?>
                            <?php if ($message['online']): ?>
                                <span class="messages-online"></span>
                            <?php endif; ?>
                        </div>

                        <div class="messages-conversation-body">
                            <div class="messages-conversation-top">
                                <span class="messages-conversation-name"><?= e($message['name']) ?></span>
                                <span class="messages-conversation-time"><?= e($message['time']) ?></span>
                            </div>

                            <div class="messages-conversation-subject">
                                <?= e($message['subject']) ?>
                            </div>

                            <div class="messages-conversation-snippet">
                                <?= e($message['message']) ?>
                            </div>
                        </div>

                        <?php if ($message['unread']): ?>
                            <span class="messages-unread-dot" title="Sample unread message"></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>

                <div class="messages-no-results" id="messagesNoResults" style="display:none">
                    <i class="fa-regular fa-folder-open" style="font-size:22px;display:block;margin-bottom:10px"></i>
                    No conversations match your search.
                </div>
            </div>
        </section>

        <section class="messages-preview">
            <div class="messages-preview-header">
                <div class="messages-person">
                    <div class="messages-avatar indigo" id="previewAvatar">AR</div>
                    <div class="messages-person-info">
                        <div class="messages-person-name" id="previewName">Alexandra Reyes</div>
                        <div class="messages-person-role" id="previewRole">
                            <i class="fa-solid fa-user-graduate" style="margin-right:4px"></i>
                            OJT Intern · Sample conversation
                        </div>
                    </div>
                </div>

                <div class="messages-preview-actions">
                    <button class="messages-icon-btn" type="button" title="Search conversations" id="focusSearch">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    <button class="messages-icon-btn" type="button" title="Conversation details" id="showDetails">
                        <i class="fa-solid fa-circle-info"></i>
                    </button>
                </div>
            </div>

            <div class="messages-subject-bar">
                <div class="messages-subject-label">Conversation subject</div>
                <div class="messages-subject" id="previewSubject">Question about my OJT schedule</div>
            </div>

            <div class="messages-thread" id="messagesThread">
                <div class="messages-date-divider" id="previewDate">Today · Sample messages</div>

                <div id="messageBubbles">
                    <div class="messages-bubble-row">
                        <div class="messages-mini-avatar">AR</div>
                        <div class="messages-bubble-wrap">
                            <div class="messages-bubble-sender">Alexandra Reyes</div>
                            <div class="messages-bubble">
                                Good morning! I would like to clarify my assigned schedule for next week. Should I follow the same time-in and time-out schedule? Thank you!
                            </div>
                            <div class="messages-bubble-time">9:42 AM</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="messages-reply-area">
                <div class="messages-reply-disabled">
                    <i class="fa-solid fa-lock" style="margin-right:5px"></i>
                    Replying will be available when the messaging backend is connected.
                </div>

                <div class="messages-reply-footer">
                    <span><i class="fa-solid fa-shield-halved" style="margin-right:4px"></i> UI preview only</span>
                    <button class="messages-reply-send" type="button" disabled>
                        Send <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </section>

    </div>
</div>

<div class="messages-modal-backdrop" id="composeModal" role="dialog" aria-modal="true" aria-labelledby="composeTitle">
    <div class="messages-modal">
        <div class="messages-modal-header">
            <div>
                <h2 id="composeTitle">Compose message</h2>
                <p>Prepare a message for an intern or employee.</p>
            </div>

            <button class="messages-icon-btn" type="button" id="closeCompose" aria-label="Close compose window">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="messages-modal-body">
            <div class="messages-form-field">
                <label for="composeRecipient">Recipient</label>
                <select id="composeRecipient">
                    <option value="">Select a recipient</option>
                    <option>OJT Intern</option>
                    <option>Employee</option>
                    <option>All Interns</option>
                </select>
            </div>

            <div class="messages-form-field">
                <label for="composeSubject">Subject</label>
                <input id="composeSubject" type="text" placeholder="Enter message subject">
            </div>

            <div class="messages-form-field">
                <label for="composeBody">Message</label>
                <textarea id="composeBody" placeholder="Write your message here..."></textarea>
            </div>

            <div class="messages-modal-notice">
                <i class="fa-solid fa-circle-info" style="margin-right:5px"></i>
                This is a UI prototype. The message will not be sent, saved, or delivered to the employee dashboard.
            </div>
        </div>

        <div class="messages-modal-footer">
            <button class="messages-modal-cancel" type="button" id="cancelCompose">Close</button>
            <button class="messages-modal-submit" type="button" disabled>
                <i class="fa-solid fa-paper-plane" style="margin-right:5px"></i>
                Send message
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const conversations = <?= json_encode($sampleMessages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    const list = document.getElementById('messagesList');
    const search = document.getElementById('messageSearch');
    const filters = document.querySelectorAll('.messages-filter');
    const noResults = document.getElementById('messagesNoResults');

    const previewAvatar = document.getElementById('previewAvatar');
    const previewName = document.getElementById('previewName');
    const previewRole = document.getElementById('previewRole');
    const previewSubject = document.getElementById('previewSubject');
    const previewDate = document.getElementById('previewDate');
    const messageBubbles = document.getElementById('messageBubbles');

    const modal = document.getElementById('composeModal');
    const openCompose = document.getElementById('openCompose');
    const closeCompose = document.getElementById('closeCompose');
    const cancelCompose = document.getElementById('cancelCompose');

    let activeFilter = 'all';
    let activeId = 1;

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

    function selectConversation(id) {
        const conversation = conversations.find(function (item) {
            return item.id === id;
        });

        if (!conversation) return;

        activeId = id;

        document.querySelectorAll('.messages-conversation').forEach(function (button) {
            button.classList.toggle('active', Number(button.dataset.id) === id);
        });

        previewAvatar.className = 'messages-avatar ' + conversation.color;
        previewAvatar.textContent = conversation.initials;
        previewName.textContent = conversation.name;

        const roleIcon = conversation.category === 'system'
            ? 'fa-bell'
            : 'fa-user-graduate';

        previewRole.innerHTML =
            '<i class="fa-solid ' + roleIcon + '" style="margin-right:4px"></i>' +
            escapeHtml(conversation.role) + ' · Sample conversation';

        previewSubject.textContent = conversation.subject;
        previewDate.textContent = conversation.date + ' · Sample messages';

        messageBubbles.innerHTML = conversation.history.map(function (message) {
            const isMine = message.from === 'me';
            const senderName = isMine ? 'Company' : conversation.name;
            const initials = isMine ? 'CO' : conversation.initials;

            return `
                <div class="messages-bubble-row ${isMine ? 'mine' : ''}">
                    ${isMine ? '' : `
                        <div class="messages-mini-avatar">${escapeHtml(initials)}</div>
                    `}
                    <div class="messages-bubble-wrap">
                        <div class="messages-bubble-sender">${escapeHtml(senderName)}</div>
                        <div class="messages-bubble">${escapeHtml(message.text)}</div>
                        <div class="messages-bubble-time">${escapeHtml(message.time)}</div>
                    </div>
                    ${isMine ? `
                        <div class="messages-mini-avatar" style="background:#172554;color:white">CO</div>
                    ` : ''}
                </div>
            `;
        }).join('');

        document.getElementById('showDetails').onclick = function () {
            window.alert(
                'Sample conversation details\n\n' +
                'Name: ' + conversation.name + '\n' +
                'Type: ' + conversation.role + '\n' +
                'Subject: ' + conversation.subject + '\n\n' +
                'Real conversation details will be available after backend integration.'
            );
        };
    }

    function applyFilters() {
        const query = search.value.trim().toLowerCase();
        const buttons = list.querySelectorAll('.messages-conversation');
        let visibleCount = 0;

        buttons.forEach(function (button) {
            const id = Number(button.dataset.id);
            const matchesSearch =
                button.dataset.name.includes(query) ||
                button.dataset.subject.includes(query) ||
                button.dataset.message.includes(query);

            let matchesFilter = true;

            if (activeFilter === 'unread') {
                matchesFilter = button.dataset.unread === '1';
            } else if (activeFilter === 'students' || activeFilter === 'system') {
                matchesFilter = button.dataset.category === activeFilter;
            }

            const visible = matchesSearch && matchesFilter;

            button.style.display = visible ? 'flex' : 'none';

            if (visible) visibleCount++;
            if (!visible && id === activeId) {
                button.classList.remove('active');
            }
        });

        noResults.style.display = visibleCount === 0 ? 'block' : 'none';

        const activeButton = list.querySelector(
            '.messages-conversation[data-id="' + activeId + '"]'
        );

        if (activeButton && activeButton.style.display === 'none') {
            const firstVisible = Array.from(buttons).find(function (button) {
                return button.style.display !== 'none';
            });

            if (firstVisible) {
                selectConversation(Number(firstVisible.dataset.id));
            }
        }
    }

    list.querySelectorAll('.messages-conversation').forEach(function (button) {
        button.addEventListener('click', function () {
            selectConversation(Number(button.dataset.id));
        });
    });

    filters.forEach(function (button) {
        button.addEventListener('click', function () {
            activeFilter = button.dataset.filter;

            filters.forEach(function (filterButton) {
                filterButton.classList.toggle('active', filterButton === button);
            });

            applyFilters();
        });
    });

    search.addEventListener('input', applyFilters);

    document.getElementById('focusSearch').addEventListener('click', function () {
        search.focus();
    });

    function showModal() {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        document.getElementById('composeRecipient').focus();
    }

    function hideModal() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
        openCompose.focus();
    }

    openCompose.addEventListener('click', showModal);
    closeCompose.addEventListener('click', hideModal);
    cancelCompose.addEventListener('click', hideModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            hideModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('open')) {
            hideModal();
        }
    });

    selectConversation(activeId);
})();
</script>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>