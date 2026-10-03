<?php
/*
|--------------------------------------------------------------------------
| OJT360 - Employee Dashboard
|--------------------------------------------------------------------------
| Single-file version: employee_dashboard.php
| Front-end prototype based on the supplied Figma screenshots.
|
| IMPORTANT:
| - Overview keeps Company Management.
| - Interns page is student-focused only; NO Company Management there.
| - Interns page includes completed-hours tracking (e.g. 48 hrs / 360 hrs).
| - Employee can add an intern student.
| - Sidebar pages: Overview, Interns, Evaluations, Messages, Reports & Analytics.
| - Help & Support and Sign Out are included.
|
| Replace the sample arrays with your MySQL/PHP data when you connect this
| page to your existing OJT360 database.
|--------------------------------------------------------------------------
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJT360 - Employee Dashboard</title>

    <style>
        :root{
            --navy:#082b49;
            --navy-2:#0a3d60;
            --blue:#1197d2;
            --blue-2:#1aa8df;
            --light-blue:#eaf6fc;
            --page:#f4f7fa;
            --card:#ffffff;
            --border:#dbe5ed;
            --text:#17324d;
            --muted:#708397;
            --green:#16966a;
            --green-bg:#e8f7f0;
            --yellow:#c58a16;
            --yellow-bg:#fff6df;
            --red:#c84b55;
            --red-bg:#ffebed;
            --purple:#7656c9;
            --purple-bg:#f1edff;
            --shadow:0 4px 16px rgba(13,45,72,.07);
            --radius:10px;
        }

        *{box-sizing:border-box}

        html,body{
            margin:0;
            min-height:100%;
            font-family:Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color:var(--text);
            background:var(--page);
        }

        button,input,select,textarea{font:inherit}

        button{cursor:pointer}

        .app{
            min-height:100vh;
            display:flex;
        }

        /* =========================
           SIDEBAR
        ========================== */
        .sidebar{
            position:fixed;
            left:0;
            top:0;
            bottom:0;
            width:256px;
            background:#062a4a;
            color:#fff;
            display:flex;
            flex-direction:column;
            z-index:100;
            transform:translateX(-100%);
            transition:transform .30s cubic-bezier(.4,0,.2,1);
            box-shadow:12px 0 32px rgba(3,28,50,.20);
            overflow:hidden;
        }

        .sidebar.open{
            transform:translateX(0);
        }

        .sidebar-overlay{
            position:fixed;
            inset:0;
            background:rgba(2,20,35,.48);
            z-index:90;
            opacity:0;
            visibility:hidden;
            pointer-events:none;
            transition:opacity .30s ease, visibility .30s ease;
        }

        .sidebar-overlay.show{
            opacity:1;
            visibility:visible;
            pointer-events:auto;
        }

        .brand{
            height:76px;
            padding:0 18px 0 24px;
            display:flex;
            align-items:center;
            gap:12px;
            border-bottom:1px solid rgba(255,255,255,.08);
            flex:none;
        }

        .sidebar-close{
            margin-left:auto;
            width:32px;
            height:32px;
            border:0;
            border-radius:7px;
            background:transparent;
            color:#9fb7c9;
            display:grid;
            place-items:center;
            font-size:23px;
            line-height:1;
            transition:.18s ease;
        }

        .sidebar-close:hover{
            background:rgba(255,255,255,.08);
            color:#fff;
            transform:rotate(90deg);
        }

        .brand-mark{
            width:30px;
            height:30px;
            position:relative;
        }

        .brand-mark span{
            position:absolute;
            width:9px;
            height:9px;
            border:2px solid #27b9eb;
            border-radius:50%;
            background:transparent;
        }

        .brand-mark span:nth-child(1){left:0;top:4px}
        .brand-mark span:nth-child(2){right:0;top:4px}
        .brand-mark span:nth-child(3){left:0;bottom:3px}
        .brand-mark span:nth-child(4){right:0;bottom:3px}

        .brand-text{
            font-size:21px;
            font-weight:800;
            letter-spacing:-.5px;
        }

        .sidebar-nav{
            padding:18px 12px;
        }

        .nav-link{
            width:100%;
            border:0;
            background:transparent;
            color:#b9cad9;
            min-height:44px;
            padding:0 14px;
            border-radius:8px;
            display:flex;
            align-items:center;
            gap:13px;
            text-align:left;
            font-size:13px;
            font-weight:600;
            margin-bottom:4px;
            transition:background .18s ease,color .18s ease,transform .18s ease;
        }

        .nav-link:hover{
            transform:translateX(2px);
        }

        .nav-link:hover{
            background:rgba(255,255,255,.06);
            color:#fff;
        }

        .nav-link.active{
            background:#174d79;
            color:#fff;
            box-shadow:inset 3px 0 0 #28bce9;
        }

        .nav-link.active .nav-icon{
            color:#32c1ee;
        }

        .nav-icon{
            width:18px;
            text-align:center;
            font-size:16px;
            flex:none;
        }

        .sidebar-bottom{
            margin-top:auto;
            padding:14px 12px 16px;
        }

        .help-link{
            border-top:1px solid rgba(255,255,255,.08);
            padding-top:12px;
        }

        .profile{
            margin-top:14px;
            padding:13px 8px 2px;
            border-top:1px solid rgba(255,255,255,.1);
            display:flex;
            align-items:center;
            gap:10px;
            cursor:pointer;
            border-radius:8px;
            transition:.18s ease;
        }

        .profile:hover{
            background:rgba(255,255,255,.06);
        }

        .avatar{
            width:34px;
            height:34px;
            border-radius:50%;
            display:grid;
            place-items:center;
            background:#1f719b;
            color:#fff;
            font-size:12px;
            font-weight:800;
            flex:none;
        }

        .profile-name{
            font-size:12px;
            font-weight:700;
            color:#fff;
        }

        .profile-role{
            font-size:10px;
            color:#a9bdcc;
            margin-top:2px;
        }

        /* =========================
           MAIN
        ========================== */
        .main{
            margin-left:0;
            min-width:0;
            flex:1;
        }

        .topbar{
            height:70px;
            background:#fff;
            border-bottom:1px solid var(--border);
            display:flex;
            align-items:center;
            gap:14px;
            padding:0 22px;
            position:sticky;
            top:0;
            z-index:40;
        }

        .topbar-brand{
            display:flex;
            align-items:center;
            gap:10px;
            margin-right:auto;
            user-select:none;
        }

        .topbar-brand .brand-mark{
            width:28px;
            height:28px;
        }

        .topbar-brand .brand-text{
            color:#092d4c;
            font-size:20px;
        }

        .sync{
            font-size:11px;
            color:#708397;
            display:flex;
            align-items:center;
            gap:6px;
        }

        .sync-dot{
            width:6px;
            height:6px;
            background:#26ad78;
            border-radius:50%;
        }

        .icon-button{
            width:34px;
            height:34px;
            border:1px solid var(--border);
            border-radius:50%;
            background:#fff;
            color:#60778b;
            display:grid;
            place-items:center;
        }

        .top-profile{
            display:flex;
            align-items:center;
            gap:9px;
            padding-left:3px;
        }

        .top-profile .avatar{
            width:34px;
            height:34px;
            background:#e7f1f8;
            color:#12628c;
        }

        .content{
            width:100%;
            max-width:1480px;
            margin:0 auto;
            padding:28px 24px 34px;
        }

        .page-heading h1{
            letter-spacing:-.35px;
            font-weight:500;
        }

        .section-card,
        .chart-card,
        .stat-card{
            box-shadow:0 3px 14px rgba(13,45,72,.055);
        }

        .page{
            display:none;
            animation:fade .18s ease;
        }

        .page.active{display:block}

        @keyframes fade{
            from{opacity:.3;transform:translateY(3px)}
            to{opacity:1;transform:none}
        }

        .hero{
            background:linear-gradient(110deg,#073250,#07496d);
            color:#fff;
            border-radius:9px;
            padding:26px 28px;
            margin-bottom:14px;
            min-height:102px;
            box-shadow:var(--shadow);
        }

        .eyebrow{
            text-transform:uppercase;
            letter-spacing:1px;
            font-size:9px;
            font-weight:800;
            color:#6cc6e8;
            margin-bottom:7px;
        }

        .hero h1{
            font-size:18px;
            margin:0 0 5px;
            font-weight:650;
        }

        .hero p{
            margin:0;
            font-size:10px;
            color:#a9d0e2;
        }

        .page-heading{
            display:flex;
            justify-content:space-between;
            align-items:flex-end;
            gap:20px;
            margin-bottom:18px;
        }

        .page-heading h1{
            font-size:23px;
            margin:0 0 4px;
        }

        .page-heading p{
            margin:0;
            font-size:11px;
            color:var(--muted);
        }

        .btn{
            border:0;
            border-radius:6px;
            min-height:35px;
            padding:0 15px;
            font-size:11px;
            font-weight:700;
            transition:.18s;
        }

        .btn-primary{
            background:#087fbd;
            color:#fff;
        }

        .btn-primary:hover{background:#066c9f}

        .btn-light{
            background:#fff;
            color:#456174;
            border:1px solid var(--border);
        }

        .btn-light:hover{background:#f5f8fb}

        .btn-danger{
            background:var(--red-bg);
            color:var(--red);
        }

        /* =========================
           CARDS
        ========================== */
        .stat-grid{
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:10px;
            margin-bottom:14px;
        }

        .stat-card{
            background:#fff;
            border:1px solid var(--border);
            border-radius:9px;
            min-height:75px;
            padding:14px;
            display:flex;
            align-items:flex-start;
            gap:10px;
            box-shadow:var(--shadow);
        }

        .stat-icon{
            width:31px;
            height:31px;
            border-radius:8px;
            display:grid;
            place-items:center;
            font-size:14px;
            background:#e8f4fa;
            color:#087fbd;
            flex:none;
        }

        .stat-icon.amber{background:#fff4df;color:#c58a16}
        .stat-icon.purple{background:#f1edff;color:#7656c9}
        .stat-icon.green{background:#e8f7f0;color:#16966a}

        .stat-label{
            font-size:8px;
            text-transform:uppercase;
            letter-spacing:.6px;
            color:#8395a5;
            margin-bottom:4px;
        }

        .stat-value{
            font-size:17px;
            font-weight:750;
            line-height:1;
        }

        .stat-sub{
            font-size:8px;
            color:#8b9baa;
            margin-top:5px;
        }

        .section-card{
            background:#fff;
            border:1px solid var(--border);
            border-radius:9px;
            box-shadow:var(--shadow);
            margin-bottom:14px;
            overflow:hidden;
        }

        .section-head{
            padding:16px 16px 12px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
        }

        .section-kicker{
            text-transform:uppercase;
            letter-spacing:.8px;
            color:#1484b8;
            font-size:8px;
            font-weight:800;
            margin-bottom:4px;
        }

        .section-title{
            margin:0;
            font-size:15px;
            font-weight:700;
        }

        .section-sub{
            margin:3px 0 0;
            color:#8395a5;
            font-size:9px;
        }

        /* =========================
           TABLES
        ========================== */
        .table-wrap{
            width:100%;
            overflow-x:auto;
        }

        table{
            width:100%;
            border-collapse:collapse;
            min-width:760px;
        }

        th,td{
            padding:11px 12px;
            text-align:left;
            border-top:1px solid #edf1f4;
            font-size:10px;
            white-space:nowrap;
        }

        th{
            color:#7c8f9f;
            font-size:8px;
            text-transform:uppercase;
            letter-spacing:.5px;
            font-weight:800;
            background:#fbfcfd;
        }

        td{
            color:#38556d;
        }

        tbody tr:hover{background:#fbfdff}

        .person{
            display:flex;
            align-items:center;
            gap:9px;
        }

        .mini-avatar{
            width:27px;
            height:27px;
            border-radius:8px;
            display:grid;
            place-items:center;
            background:#edf5fa;
            color:#147ba8;
            font-size:8px;
            font-weight:800;
            flex:none;
        }

        .person-name{
            font-weight:700;
            color:#29465e;
            font-size:10px;
        }

        .person-sub{
            font-size:8px;
            color:#8a9aaa;
            margin-top:2px;
        }

        .badge{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:19px;
            padding:0 8px;
            border-radius:20px;
            font-size:8px;
            font-weight:750;
        }

        .badge.green{color:#13825c;background:var(--green-bg)}
        .badge.yellow{color:#a97712;background:var(--yellow-bg)}
        .badge.red{color:#b63f49;background:var(--red-bg)}
        .badge.blue{color:#0875a9;background:#e8f5fc}
        .badge.purple{color:#6849b8;background:var(--purple-bg)}

        .action-row{
            display:flex;
            align-items:center;
            gap:6px;
        }

        .small-btn{
            min-height:27px;
            border-radius:5px;
            border:1px solid var(--border);
            background:#fff;
            color:#4d697e;
            padding:0 9px;
            font-size:8px;
            font-weight:700;
        }

        .small-btn.primary{
            background:#087fbd;
            border-color:#087fbd;
            color:#fff;
        }

        /* =========================
           OVERVIEW
        ========================== */
        .toolbar{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            padding:0 12px 12px;
        }

        .search{
            width:270px;
            max-width:100%;
            height:33px;
            border:1px solid var(--border);
            border-radius:6px;
            padding:0 11px;
            outline:none;
            font-size:10px;
            color:var(--text);
        }

        .search:focus{
            border-color:#67b9dc;
            box-shadow:0 0 0 3px rgba(8,127,189,.08);
        }

        .filter-group{
            display:flex;
            gap:4px;
        }

        .filter{
            height:29px;
            padding:0 9px;
            border:1px solid var(--border);
            background:#fff;
            border-radius:5px;
            color:#778b9b;
            font-size:8px;
        }

        .filter.active{
            color:#087fbd;
            background:#edf8fd;
            border-color:#b8dff0;
        }

        /* =========================
           INTERN PAGE
        ========================== */
        .hours-box{
            min-width:145px;
        }

        .hours-line{
            display:flex;
            justify-content:space-between;
            gap:10px;
            font-size:8px;
            margin-bottom:5px;
        }

        .hours-line strong{
            color:#294c63;
            font-size:9px;
        }

        .progress{
            width:100%;
            height:5px;
            background:#e8eef2;
            border-radius:10px;
            overflow:hidden;
        }

        .progress span{
            display:block;
            height:100%;
            background:#1697bf;
            border-radius:10px;
        }

        .progress-meta{
            font-size:8px;
            color:#7c8f9f;
            text-align:right;
            margin-top:4px;
        }

        /* =========================
           EVALUATIONS
        ========================== */
        .evaluation-roster{
            padding:0 12px 12px;
        }

        .eval-row{
            display:grid;
            grid-template-columns:2.2fr 1fr .8fr .8fr auto;
            align-items:center;
            gap:15px;
            min-height:57px;
            border-top:1px solid #edf1f4;
        }

        .eval-hours{
            font-weight:750;
            font-size:10px;
        }

        .week{
            font-size:9px;
            color:#8193a2;
        }

        .feedback{
            padding:17px 16px;
            border-top:1px solid #edf1f4;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
        }

        .feedback h3{
            margin:0 0 4px;
            font-size:13px;
        }

        .feedback p{
            margin:0;
            font-size:9px;
            color:#8294a3;
        }

        /* =========================
           MESSAGES
        ========================== */
        .message-layout{
            display:grid;
            grid-template-columns:320px 1fr;
            min-height:570px;
        }

        .conversation-list{
            border-right:1px solid var(--border);
            min-width:0;
        }

        .conversation-search{
            padding:12px;
            border-bottom:1px solid var(--border);
        }

        .conversation{
            padding:12px;
            border-bottom:1px solid #edf1f4;
            cursor:pointer;
        }

        .conversation.active{
            background:#eef8fd;
            border-left:3px solid #0b89c5;
            padding-left:9px;
        }

        .conversation-top{
            display:flex;
            justify-content:space-between;
            gap:8px;
        }

        .conversation-name{
            font-size:10px;
            font-weight:750;
        }

        .conversation-time{
            font-size:8px;
            color:#93a1ad;
        }

        .conversation-msg{
            margin-top:4px;
            font-size:8px;
            color:#7b8e9e;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .chat{
            display:flex;
            flex-direction:column;
        }

        .chat-head{
            padding:13px 16px;
            border-bottom:1px solid var(--border);
            display:flex;
            align-items:center;
            justify-content:space-between;
        }

        .chat-name{
            font-size:11px;
            font-weight:750;
        }

        .chat-status{
            font-size:8px;
            color:#15986c;
            margin-top:2px;
        }

        .chat-body{
            flex:1;
            padding:26px;
            background:#fbfcfd;
            display:flex;
            flex-direction:column;
            gap:12px;
        }

        .bubble{
            max-width:68%;
            padding:10px 12px;
            border-radius:9px;
            font-size:9px;
            line-height:1.5;
            background:#fff;
            border:1px solid var(--border);
        }

        .bubble.me{
            margin-left:auto;
            background:#087fbd;
            border-color:#087fbd;
            color:#fff;
        }

        .chat-input{
            border-top:1px solid var(--border);
            padding:10px;
            display:flex;
            gap:7px;
        }

        .chat-input input{
            flex:1;
            height:34px;
            border:1px solid var(--border);
            border-radius:6px;
            padding:0 10px;
            font-size:9px;
            outline:none;
        }

        /* =========================
           REPORTS
        ========================== */
        .report-grid{
            display:grid;
            grid-template-columns:1.4fr 1fr;
            gap:12px;
        }

        .chart-card{
            background:#fff;
            border:1px solid var(--border);
            border-radius:9px;
            padding:16px;
            box-shadow:var(--shadow);
        }

        .chart-title{
            font-size:11px;
            font-weight:750;
            margin-bottom:4px;
        }

        .chart-sub{
            font-size:8px;
            color:#8a9aaa;
            margin-bottom:16px;
        }

        .bars{
            height:190px;
            display:flex;
            align-items:flex-end;
            gap:15px;
            padding:0 12px 24px;
            border-bottom:1px solid #dfe7ed;
        }

        .bar-col{
            flex:1;
            height:100%;
            display:flex;
            align-items:flex-end;
            justify-content:center;
            position:relative;
        }

        .bar{
            width:58%;
            max-width:44px;
            min-height:15px;
            background:#147f9e;
            border-radius:4px 4px 0 0;
        }

        .bar-label{
            position:absolute;
            bottom:-19px;
            font-size:8px;
            color:#8395a5;
        }

        .donut-wrap{
            min-height:250px;
            display:grid;
            place-items:center;
            position:relative;
        }

        .donut{
            width:145px;
            height:145px;
            border-radius:50%;
            background:conic-gradient(#1387ae 0 92%, #dfe8ed 92% 100%);
            position:relative;
        }

        .donut:after{
            content:"";
            position:absolute;
            inset:25px;
            background:#fff;
            border-radius:50%;
        }

        .donut-text{
            position:absolute;
            inset:0;
            display:grid;
            place-items:center;
            z-index:2;
            font-weight:800;
            font-size:20px;
        }

        .legend{
            display:flex;
            justify-content:center;
            gap:15px;
            margin-top:10px;
            font-size:8px;
            color:#758a9b;
        }

        .legend span:before{
            content:"";
            display:inline-block;
            width:7px;
            height:7px;
            border-radius:50%;
            background:#1387ae;
            margin-right:5px;
        }

        .legend span:last-child:before{
            background:#dfe8ed;
        }

        /* =========================
           SUPPORT
        ========================== */
        .support-hero{
            background:linear-gradient(110deg,#073250,#07496d);
            color:#fff;
            border-radius:9px;
            text-align:center;
            padding:28px 20px;
            box-shadow:var(--shadow);
        }

        .support-hero h1{
            margin:0 0 5px;
            font-size:20px;
        }

        .support-hero p{
            margin:0 auto 16px;
            max-width:540px;
            color:#a8d1e4;
            font-size:9px;
        }

        .support-search{
            width:430px;
            max-width:90%;
            height:36px;
            border:0;
            border-radius:6px;
            padding:0 12px;
            font-size:10px;
            outline:none;
        }

        .support-links{
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:9px;
            margin:10px 0 14px;
        }

        .support-link-card{
            background:#fff;
            border:1px solid var(--border);
            border-radius:8px;
            padding:14px;
            min-height:68px;
            box-shadow:var(--shadow);
        }

        .support-link-card strong{
            display:block;
            font-size:9px;
            margin-bottom:4px;
        }

        .support-link-card span{
            color:#899aa8;
            font-size:8px;
        }

        .support-form{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:15px;
        }

        .form-label{
            display:block;
            font-size:9px;
            color:#61788b;
            font-weight:700;
            margin-bottom:6px;
        }

        .field{
            width:100%;
            border:1px solid var(--border);
            border-radius:6px;
            min-height:35px;
            padding:7px 10px;
            outline:none;
            font-size:9px;
        }

        textarea.field{
            min-height:105px;
            resize:vertical;
        }

        .full{grid-column:1/-1}

        /* =========================
           MODAL
        ========================== */
        .modal-backdrop{
            position:fixed;
            inset:0;
            background:rgba(2,20,35,.58);
            display:none;
            align-items:center;
            justify-content:center;
            padding:20px;
            z-index:100;
        }

        .modal-backdrop.show{display:flex}

        .modal{
            width:560px;
            max-width:100%;
            max-height:90vh;
            overflow:auto;
            background:#fff;
            border-radius:11px;
            box-shadow:0 20px 60px rgba(0,0,0,.25);
        }

        .modal-head{
            padding:17px 19px;
            border-bottom:1px solid var(--border);
            display:flex;
            align-items:center;
            justify-content:space-between;
        }

        .modal-head h2{
            margin:0;
            font-size:15px;
        }

        .close{
            border:0;
            background:transparent;
            color:#718596;
            font-size:20px;
        }

        .modal-body{
            padding:18px;
        }

        .form-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:13px;
        }

        .form-group.full{grid-column:1/-1}

        .modal-foot{
            padding:12px 18px;
            border-top:1px solid var(--border);
            display:flex;
            justify-content:flex-end;
            gap:8px;
        }

        .empty{
            text-align:center;
            padding:40px 20px;
            color:#8b9ba9;
            font-size:10px;
        }

        /* =========================
           PROFILE
        ========================== */
        .profile-trigger{
            cursor:pointer;
            border:0;
            background:transparent;
            text-align:left;
        }

        .profile-modal{
            width:620px;
        }

        .profile-cover{
            height:92px;
            background:linear-gradient(110deg,#073250,#087fbd);
            position:relative;
        }

        .profile-avatar-large{
            width:76px;
            height:76px;
            border-radius:50%;
            display:grid;
            place-items:center;
            background:#e7f7fd;
            color:#087fbd;
            border:5px solid #fff;
            font-size:22px;
            font-weight:800;
            position:absolute;
            left:22px;
            bottom:-38px;
            box-shadow:0 5px 18px rgba(0,0,0,.14);
        }

        .profile-header-info{
            padding:50px 22px 16px;
            border-bottom:1px solid var(--border);
        }

        .profile-header-info h2{
            margin:0 0 3px;
            font-size:17px;
        }

        .profile-header-info p{
            margin:0;
            color:var(--muted);
            font-size:9px;
        }

        .profile-section{
            padding:18px 22px;
        }

        .profile-section-title{
            font-size:10px;
            text-transform:uppercase;
            letter-spacing:.7px;
            color:#1484b8;
            font-weight:800;
            margin-bottom:12px;
        }

        .profile-actions{
            display:flex;
            gap:8px;
            justify-content:flex-end;
        }

        .toast{
            position:fixed;
            right:22px;
            bottom:22px;
            background:#062a4a;
            color:#fff;
            padding:11px 15px;
            border-radius:8px;
            font-size:10px;
            font-weight:700;
            box-shadow:0 10px 30px rgba(0,0,0,.18);
            opacity:0;
            transform:translateY(12px);
            pointer-events:none;
            transition:.25s ease;
            z-index:300;
        }

        .toast.show{
            opacity:1;
            transform:none;
        }


        /* =========================
           OJT360 AI ASSISTANT
        ========================== */
        .ai-launcher{
            position:fixed;
            right:26px;
            bottom:24px;
            width:50px;
            height:50px;
            border:3px solid #fff;
            border-radius:50%;
            background:#1197d2;
            color:#fff;
            display:grid;
            place-items:center;
            box-shadow:0 7px 22px rgba(8,89,128,.30);
            z-index:160;
            transition:transform .2s ease, box-shadow .2s ease;
        }

        .ai-launcher:hover{
            transform:translateY(-2px) scale(1.03);
            box-shadow:0 10px 28px rgba(8,89,128,.36);
        }

        .ai-launcher-icon{
            font-size:21px;
            line-height:1;
            transform:translateY(-1px);
        }

        .ai-panel{
            position:fixed;
            right:28px;
            bottom:84px;
            width:365px;
            height:515px;
            background:#fff;
            border:1px solid #dbe5ed;
            border-radius:15px;
            overflow:hidden;
            box-shadow:0 18px 55px rgba(3,35,58,.24);
            z-index:170;
            display:flex;
            flex-direction:column;
            opacity:0;
            visibility:hidden;
            transform:translateY(14px) scale(.98);
            transform-origin:bottom right;
            pointer-events:none;
            transition:opacity .22s ease, transform .22s ease, visibility .22s ease;
        }

        .ai-panel.open{
            opacity:1;
            visibility:visible;
            transform:translateY(0) scale(1);
            pointer-events:auto;
        }

        .ai-head{
            min-height:68px;
            padding:0 16px 0 18px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            color:#fff;
            background:linear-gradient(135deg,#073653,#0c6877);
        }

        .ai-title-wrap{
            display:flex;
            align-items:center;
            gap:10px;
        }

        .ai-avatar{
            width:34px;
            height:34px;
            border-radius:50%;
            display:grid;
            place-items:center;
            background:rgba(255,255,255,.13);
            border:1px solid rgba(255,255,255,.18);
            font-size:16px;
        }

        .ai-title{
            font-size:13px;
            font-weight:800;
            letter-spacing:-.15px;
        }

        .ai-online{
            display:flex;
            align-items:center;
            gap:5px;
            margin-top:2px;
            font-size:8px;
            color:#bcefe4;
        }

        .ai-online-dot{
            width:6px;
            height:6px;
            border-radius:50%;
            background:#39c993;
        }

        .ai-close{
            width:30px;
            height:30px;
            border:0;
            border-radius:7px;
            background:transparent;
            color:#fff;
            font-size:18px;
            display:grid;
            place-items:center;
            transition:.18s ease;
        }

        .ai-close:hover{
            background:rgba(255,255,255,.12);
            transform:rotate(90deg);
        }

        .ai-body{
            flex:1;
            min-height:0;
            overflow-y:auto;
            padding:18px 14px 14px;
            background:#f7fafc;
        }

        .ai-row{
            display:flex;
            gap:8px;
            align-items:flex-start;
            margin-bottom:12px;
        }

        .ai-row.user{
            justify-content:flex-end;
        }

        .ai-mini-avatar{
            width:25px;
            height:25px;
            border-radius:50%;
            background:#e5f5fb;
            color:#1197d2;
            display:grid;
            place-items:center;
            flex:none;
            font-size:12px;
        }

        .ai-bubble{
            max-width:80%;
            padding:10px 12px;
            border-radius:11px;
            background:#fff;
            border:1px solid #e5edf2;
            color:#49657a;
            font-size:9px;
            line-height:1.55;
            box-shadow:0 3px 10px rgba(15,54,78,.045);
        }

        .ai-row.user .ai-bubble{
            background:#1197d2;
            border-color:#1197d2;
            color:#fff;
            border-bottom-right-radius:4px;
        }

        .ai-row:not(.user) .ai-bubble{
            border-bottom-left-radius:4px;
        }

        .ai-time{
            display:block;
            margin-top:5px;
            color:#9aaab6;
            font-size:7px;
        }

        .ai-row.user .ai-time{
            color:rgba(255,255,255,.72);
        }

        .ai-suggestions{
            display:flex;
            flex-wrap:wrap;
            gap:7px;
            margin:4px 0 12px 33px;
        }

        .ai-suggestion{
            border:1px solid #cfe5ef;
            background:#fff;
            color:#1488b8;
            border-radius:16px;
            padding:6px 10px;
            font-size:8px;
            transition:.18s ease;
        }

        .ai-suggestion:hover{
            background:#eef8fc;
            border-color:#9ed4e7;
        }

        .ai-foot{
            padding:10px;
            border-top:1px solid #e1e9ee;
            background:#fff;
            display:flex;
            align-items:center;
            gap:7px;
        }

        .ai-input{
            flex:1;
            height:37px;
            border:1px solid #dce7ed;
            border-radius:8px;
            padding:0 11px;
            outline:none;
            color:#355269;
            font-size:9px;
            background:#fff;
        }

        .ai-input:focus{
            border-color:#78c4df;
            box-shadow:0 0 0 3px rgba(17,151,210,.08);
        }

        .ai-send{
            width:37px;
            height:37px;
            border:0;
            border-radius:8px;
            background:#1197d2;
            color:#fff;
            display:grid;
            place-items:center;
            font-size:16px;
            transition:.18s ease;
        }

        .ai-send:hover{
            background:#087fbd;
            transform:translateY(-1px);
        }

        @media(max-width:520px){
            .ai-panel{
                right:12px;
                left:12px;
                bottom:78px;
                width:auto;
                height:calc(100vh - 112px);
                max-height:560px;
            }

            .ai-launcher{
                right:16px;
                bottom:16px;
            }
        }

        /* =========================
           MENU / RESPONSIVE
        ========================== */
        .menu-toggle{
            display:grid;
            place-items:center;
            width:40px;
            height:40px;
            border:1px solid var(--border);
            background:#fff;
            border-radius:8px;
            color:#087fbd;
            flex:none;
            transition:.18s ease;
        }

        .menu-toggle:hover{
            border-color:#9ed2e9;
            background:#f2f9fd;
            transform:translateY(-1px);
        }

        .menu-toggle .hamburger{
            width:18px;
            height:14px;
            position:relative;
            display:block;
        }

        .menu-toggle .hamburger span{
            position:absolute;
            left:0;
            width:18px;
            height:2px;
            border-radius:2px;
            background:currentColor;
        }

        .menu-toggle .hamburger span:nth-child(1){top:0}
        .menu-toggle .hamburger span:nth-child(2){top:6px}
        .menu-toggle .hamburger span:nth-child(3){top:12px}

        @media(max-width:1000px){
            .stat-grid{grid-template-columns:repeat(2,1fr)}
            .report-grid{grid-template-columns:1fr}
            .support-links{grid-template-columns:repeat(2,1fr)}
        }

        @media(max-width:780px){
            .sidebar{width:256px}

            .topbar{
                padding:0 15px;
            }

            .topbar-brand .brand-text{font-size:18px}
            .topbar-brand .brand-mark{width:26px;height:26px}

            .content{padding:20px 15px 32px}

            .page-heading{
                align-items:flex-start;
                flex-direction:column;
            }

            .message-layout{grid-template-columns:1fr}
            .conversation-list{border-right:0;border-bottom:1px solid var(--border)}
            .conversation-list .conversation:nth-of-type(n+4){display:none}

            .eval-row{
                grid-template-columns:1.8fr 1fr auto;
                gap:8px;
            }

            .eval-row .week{display:none}

            .support-form{grid-template-columns:1fr}
            .support-form .full{grid-column:auto}
        }

        @media(max-width:520px){
            .stat-grid{grid-template-columns:1fr}
            .support-links{grid-template-columns:1fr}
            .hero{padding:21px}
            .form-grid{grid-template-columns:1fr}
            .form-group.full{grid-column:auto}
        }
    </style>
</head>

<body>
<div class="app">

    <!-- =========================================================
         SIDEBAR
    ========================================================== -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-mark">
                <span></span><span></span><span></span><span></span>
            </div>
            <div class="brand-text">OJT360</div>
            <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar" title="Close sidebar">&times;</button>
        </div>

        <nav class="sidebar-nav">
            <button class="nav-link active" data-page="overview">
                <span class="nav-icon">▦</span>
                <span>Overview</span>
            </button>

            <button class="nav-link" data-page="interns">
                <span class="nav-icon">♙</span>
                <span>Interns</span>
            </button>

            <button class="nav-link" data-page="evaluations">
                <span class="nav-icon">▤</span>
                <span>Evaluations</span>
            </button>

            <button class="nav-link" data-page="messages">
                <span class="nav-icon">➤</span>
                <span>Messages</span>
            </button>

            <button class="nav-link" data-page="reports">
                <span class="nav-icon">▥</span>
                <span>Reports &amp; Analytics</span>
            </button>
        </nav>

        <div class="sidebar-bottom">
            <button class="nav-link help-link" data-page="support">
                <span class="nav-icon">?</span>
                <span>Help &amp; Support</span>
            </button>

            <button class="nav-link" id="signOutBtn">
                <span class="nav-icon">↪</span>
                <span>Sign out</span>
            </button>

            <div class="profile" id="sidebarProfile" role="button" tabindex="0" title="Open profile">
                <div class="avatar">DE</div>
                <div>
                    <div class="profile-name">Dr. Elena Santos</div>
                    <div class="profile-role">Employee</div>
                </div>
            </div>
        </div>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- =========================================================
         MAIN
    ========================================================== -->
    <main class="main">

        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle sidebar" title="Toggle sidebar">
                <span class="hamburger"><span></span><span></span><span></span></span>
            </button>

            <div class="topbar-brand" aria-label="OJT360">
                <div class="brand-mark">
                    <span></span><span></span><span></span><span></span>
                </div>
                <div class="brand-text">OJT360</div>
            </div>

            <div class="sync">
                <span class="sync-dot"></span>
                <span>All changes synced</span>
            </div>

            <button class="icon-button" title="Notifications">♧</button>

            <button class="top-profile profile-trigger" id="topProfileBtn" title="Open profile">
                <div class="avatar">DE</div>
                <div>
                    <div class="profile-name" style="color:#29465e">Dr. Elena Santos</div>
                    <div class="profile-role">Employee</div>
                </div>
            </button>
        </header>

        <div class="content">

            <!-- =================================================
                 OVERVIEW
            ================================================== -->
            <section class="page active" id="page-overview">

                <div class="hero">
                    <div class="eyebrow">Employee Workspace</div>
                    <h1>Manage companies and student assignments.</h1>
                    <p>Manage partner companies, monitor company status, and assign students to their internship companies.</p>
                </div>

                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon">A</div>
                        <div>
                            <div class="stat-label">Active Companies</div>
                            <div class="stat-value" id="activeCompanyCount">2</div>
                            <div class="stat-sub">Available for assignment</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon amber">A</div>
                        <div>
                            <div class="stat-label">Archived Companies</div>
                            <div class="stat-value" id="archivedCompanyCount">1</div>
                            <div class="stat-sub">Historical partners</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">A</div>
                        <div>
                            <div class="stat-label">Assigned Students</div>
                            <div class="stat-value" id="assignedCount">2</div>
                            <div class="stat-sub">4 students total</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon purple">U</div>
                        <div>
                            <div class="stat-label">Unassigned Students</div>
                            <div class="stat-value" id="unassignedCount">2</div>
                            <div class="stat-sub">Awaiting company placement</div>
                        </div>
                    </div>
                </div>

                <!-- COMPANY MANAGEMENT IS ONLY HERE ON OVERVIEW -->
                <div class="section-card">
                    <div class="section-head">
                        <div>
                            <div class="section-kicker">Company Management</div>
                            <h2 class="section-title">Companies</h2>
                            <p class="section-sub">Manage registered internship companies and their current status.</p>
                        </div>
                        <button class="btn btn-primary" id="addCompanyBtn">+ Add Company</button>
                    </div>

                    <div class="toolbar">
                        <input class="search" id="companySearch" placeholder="Search companies...">

                        <div class="filter-group">
                            <button class="filter active" data-company-filter="all">All</button>
                            <button class="filter" data-company-filter="active">Active</button>
                            <button class="filter" data-company-filter="archived">Archived</button>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Contact Person</th>
                                    <th>Contact Email</th>
                                    <th>Students</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="companyTable"></tbody>
                        </table>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-head">
                        <div>
                            <div class="section-kicker">Placement Management</div>
                            <h2 class="section-title">Student Assignments</h2>
                            <p class="section-sub">Assign students to their internship company.</p>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Student ID</th>
                                    <th>Program</th>
                                    <th>Assigned Company</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="assignmentTable"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- =================================================
                 INTERNS
                 NO COMPANY MANAGEMENT HERE
            ================================================== -->
            <section class="page" id="page-interns">

                <div class="page-heading">
                    <div>
                        <div class="eyebrow" style="color:#1688ba">Placement Management</div>
                        <h1>Intern Assignments</h1>
                        <p>View and manage student internship information, placement status, and completed hours.</p>
                    </div>

                    <button class="btn btn-primary" id="addInternBtn">+ Add Intern Student</button>
                </div>

                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon">T</div>
                        <div>
                            <div class="stat-label">Total Interns</div>
                            <div class="stat-value">4</div>
                            <div class="stat-sub">Registered internship students</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">A</div>
                        <div>
                            <div class="stat-label">Assigned Students</div>
                            <div class="stat-value">2</div>
                            <div class="stat-sub">Placed with a company</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon amber">U</div>
                        <div>
                            <div class="stat-label">Unassigned Students</div>
                            <div class="stat-value">2</div>
                            <div class="stat-sub">Awaiting company placement</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon purple">A</div>
                        <div>
                            <div class="stat-label">Average Progress</div>
                            <div class="stat-value">50%</div>
                            <div class="stat-sub">Across all internship hours</div>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-head">
                        <div>
                            <div class="section-kicker">Student Information</div>
                            <h2 class="section-title">Student Assignments</h2>
                            <p class="section-sub">Student details, internship company, status, and completed hours.</p>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Student ID</th>
                                    <th>Program</th>
                                    <th>Assigned Company</th>
                                    <th>Status</th>
                                    <th>Working Hours</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="internTable"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- =================================================
                 EVALUATIONS
            ================================================== -->
            <section class="page" id="page-evaluations">

                <div class="page-heading">
                    <div>
                        <div class="eyebrow" style="color:#1688ba">Performance</div>
                        <h1>Evaluations</h1>
                        <p>Review intern progress and submit structured performance feedback.</p>
                    </div>

                    <button class="btn btn-primary" id="startEvaluationBtn">Start evaluation</button>
                </div>

                <div class="section-card">
                    <div class="section-head">
                        <div>
                            <div class="section-kicker">Intern Roster</div>
                            <h2 class="section-title">Attendance &amp; evaluation status</h2>
                            <p class="section-sub">Monitor internship hours and evaluation progress.</p>
                        </div>

                        <div class="action-row">
                            <input class="search" id="evalSearch" style="width:180px" placeholder="Search intern...">
                            <button class="small-btn">Filter</button>
                            <button class="small-btn">Export</button>
                        </div>
                    </div>

                    <div class="evaluation-roster" id="evaluationRoster"></div>

                    <div class="feedback">
                        <div>
                            <h3>Structured feedback that supports growth</h3>
                            <p>Rate technical proficiency, communication, reliability, and initiative. Drafts are saved automatically until submission.</p>
                        </div>
                        <button class="btn btn-primary" id="feedbackBtn">Start evaluation</button>
                    </div>
                </div>
            </section>

            <!-- =================================================
                 MESSAGES
            ================================================== -->
            <section class="page" id="page-messages">

                <div class="page-heading">
                    <div>
                        <div class="eyebrow" style="color:#1688ba">Communication</div>
                        <h1>Company coordination</h1>
                        <p>Documented conversations between company partners and OJT employees.</p>
                    </div>

                    <button class="btn btn-primary" id="newMessageBtn">+ New message</button>
                </div>

                <div class="section-card message-layout">
                    <div class="conversation-list">
                        <div class="conversation-search">
                            <input class="search" style="width:100%" placeholder="Search conversations...">
                        </div>

                        <div class="conversation active" data-chat="Northstar Digital Labs">
                            <div class="conversation-top">
                                <div class="conversation-name">Northstar Digital Labs</div>
                                <div class="conversation-time">9:41 AM</div>
                            </div>
                            <div class="conversation-msg">Can we confirm the schedule?</div>
                        </div>

                        <div class="conversation" data-chat="Byteworks Solutions">
                            <div class="conversation-top">
                                <div class="conversation-name">Byteworks Solutions</div>
                                <div class="conversation-time">Yesterday</div>
                            </div>
                            <div class="conversation-msg">The evaluation form is ready.</div>
                        </div>

                        <div class="conversation" data-chat="Pacific Tech Systems">
                            <div class="conversation-top">
                                <div class="conversation-name">Pacific Tech Systems</div>
                                <div class="conversation-time">Mon</div>
                            </div>
                            <div class="conversation-msg">Thanks for the update.</div>
                        </div>
                    </div>

                    <div class="chat">
                        <div class="chat-head">
                            <div>
                                <div class="chat-name" id="chatName">Northstar Digital Labs</div>
                                <div class="chat-status">● Active now • 1 assigned intern</div>
                            </div>
                            <button class="small-btn">⋮</button>
                        </div>

                        <div class="chat-body" id="chatBody">
                            <div class="bubble">Good morning! Can we confirm the adjusted schedule for this week?</div>
                            <div class="bubble me">Yes, the schedule was approved. I have updated the placement record as well.</div>
                            <div class="bubble">Perfect, thank you. We will make sure her attendance logs follow the new hours.</div>
                        </div>

                        <div class="chat-input">
                            <button class="small-btn">＋</button>
                            <input id="messageInput" placeholder="Write a message...">
                            <button class="btn btn-primary" id="sendMessageBtn">Send</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =================================================
                 REPORTS
            ================================================== -->
            <section class="page" id="page-reports">

                <div class="page-heading">
                    <div>
                        <div class="eyebrow" style="color:#1688ba">Analytics</div>
                        <h1>Reports &amp; insights</h1>
                        <p>Track internship outcomes and generate administration-ready reports.</p>
                    </div>

                    <button class="btn btn-primary" id="exportReportBtn">+ Export report</button>
                </div>

                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon">H</div>
                        <div>
                            <div class="stat-label">Hours Logged</div>
                            <div class="stat-value">8,214</div>
                            <div class="stat-sub">+12.5% this semester</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">J</div>
                        <div>
                            <div class="stat-label">Journal Submission</div>
                            <div class="stat-value">92%</div>
                            <div class="stat-sub">Above 85% target</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon purple">E</div>
                        <div>
                            <div class="stat-label">Evaluation Avg.</div>
                            <div class="stat-value">4.6 / 5</div>
                            <div class="stat-sub">Across 24 interns</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">C</div>
                        <div>
                            <div class="stat-label">Completion Forecast</div>
                            <div class="stat-value">96%</div>
                            <div class="stat-sub">On-time projection</div>
                        </div>
                    </div>
                </div>

                <div class="report-grid">
                    <div class="chart-card">
                        <div class="chart-title">Internship progress</div>
                        <div class="chart-sub">Hours logged by month</div>

                        <div class="bars">
                            <div class="bar-col"><div class="bar" style="height:32%"></div><div class="bar-label">Oct</div></div>
                            <div class="bar-col"><div class="bar" style="height:45%"></div><div class="bar-label">Nov</div></div>
                            <div class="bar-col"><div class="bar" style="height:41%"></div><div class="bar-label">Dec</div></div>
                            <div class="bar-col"><div class="bar" style="height:62%"></div><div class="bar-label">Jan</div></div>
                            <div class="bar-col"><div class="bar" style="height:58%"></div><div class="bar-label">Feb</div></div>
                            <div class="bar-col"><div class="bar" style="height:78%"></div><div class="bar-label">Mar</div></div>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-title">Submission health</div>
                        <div class="chart-sub">Current journal submissions</div>

                        <div class="donut-wrap">
                            <div class="donut">
                                <div class="donut-text">92%</div>
                            </div>
                        </div>

                        <div class="legend">
                            <span>Submitted 184</span>
                            <span>Late 12</span>
                            <span>Not submitted 4</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =================================================
                 SUPPORT
            ================================================== -->
            <section class="page" id="page-support">

                <div class="support-hero">
                    <div class="eyebrow">OJT360 Support</div>
                    <h1>How can we help?</h1>
                    <p>Find answers about attendance, journals, evaluations, and account access.</p>
                    <input class="support-search" placeholder="Search help articles...">
                </div>

                <div class="support-links">
                    <div class="support-link-card">
                        <strong>QR attendance</strong>
                        <span>Connecting scanning and internship attendance.</span>
                    </div>
                    <div class="support-link-card">
                        <strong>Journal guide</strong>
                        <span>Daily logs, journal entries, and submission.</span>
                    </div>
                    <div class="support-link-card">
                        <strong>Evaluations</strong>
                        <span>Performance reviews and feedback.</span>
                    </div>
                    <div class="support-link-card">
                        <strong>Account &amp; access</strong>
                        <span>Login, passwords, and profile settings.</span>
                    </div>
                </div>

                <div class="section-card" style="padding:16px">
                    <div class="support-form">
                        <div>
                            <div class="form-label">Still need help?</div>
                            <h3 style="margin:0 0 4px;font-size:13px">Send a request to the support team</h3>
                            <p style="font-size:9px;color:#8597a6;margin:0">Describe the issue and we will respond within one working day.</p>
                        </div>

                        <div>
                            <label class="form-label">Issue type</label>
                            <select class="field">
                                <option>Choose an issue type</option>
                                <option>Attendance</option>
                                <option>Student assignment</option>
                                <option>Evaluation</option>
                                <option>Account access</option>
                            </select>

                            <label class="form-label" style="margin-top:9px">What happened?</label>
                            <textarea class="field" placeholder="Tell us what happened..."></textarea>

                            <button class="btn btn-primary" style="width:100%;margin-top:8px" id="supportBtn">Send request</button>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </main>
</div>

<!-- =============================================================
     USER PROFILE MODAL
============================================================== -->
<div class="modal-backdrop" id="profileModal">
    <div class="modal profile-modal">
        <div class="profile-cover">
            <div class="profile-avatar-large" id="profileAvatarLarge">DE</div>
        </div>

        <div class="profile-header-info">
            <h2 id="profileDisplayName">Dr. Elena Santos</h2>
            <p id="profileDisplayRole">Employee • OJT360</p>
        </div>

        <form id="profileForm">
            <div class="profile-section">
                <div class="profile-section-title">Personal information</div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full name</label>
                        <input class="field" id="profileName" name="profileName" value="Dr. Elena Santos" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <input class="field" id="profileRole" name="profileRole" value="Employee" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input class="field" id="profileEmail" name="profileEmail" type="email" value="elena.santos@ojt360.com" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact number</label>
                        <input class="field" id="profileContact" name="profileContact" value="+63 9XX XXX XXXX">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Department / Office</label>
                        <input class="field" id="profileDepartment" name="profileDepartment" value="OJT Coordination Office">
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-light" data-close="profileModal">Cancel</button>
                <button class="btn btn-primary" type="submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>


<!-- =============================================================
     OJT360 AI ASSISTANT
============================================================== -->
<button class="ai-launcher" id="aiLauncher" aria-label="Open OJT360 Assistant" title="OJT360 Assistant">
    <span class="ai-launcher-icon">✦</span>
</button>

<section class="ai-panel" id="aiPanel" aria-label="OJT360 Assistant">
    <div class="ai-head">
        <div class="ai-title-wrap">
            <div class="ai-avatar">✦</div>
            <div>
                <div class="ai-title">OJT360 Assistant</div>
                <div class="ai-online">
                    <span class="ai-online-dot"></span>
                    <span>Online</span>
                </div>
            </div>
        </div>

        <button class="ai-close" id="aiClose" aria-label="Close assistant">&times;</button>
    </div>

    <div class="ai-body" id="aiBody">
        <div class="ai-row">
            <div class="ai-mini-avatar">✦</div>
            <div class="ai-bubble">
                Hi! I can help with OJT guidelines, attendance, journals, evaluations, and student placement. What do you need?
            </div>
        </div>

        <div class="ai-suggestions">
            <button class="ai-suggestion" data-question="What are the final journal requirements?">Final journal requirements</button>
            <button class="ai-suggestion" data-question="How does QR attendance work?">QR scan help</button>
            <button class="ai-suggestion" data-question="How do I assign a student to a company?">Student assignment</button>
        </div>
    </div>

    <form class="ai-foot" id="aiForm">
        <input class="ai-input" id="aiInput" autocomplete="off" placeholder="Ask OJT360...">
        <button class="ai-send" type="submit" aria-label="Send message">↑</button>
    </form>
</section>

<div class="toast" id="toast"></div>

<!-- =============================================================
     ADD COMPANY MODAL
============================================================== -->
<div class="modal-backdrop" id="companyModal">
    <div class="modal">
        <div class="modal-head">
            <h2>Add Company</h2>
            <button class="close" data-close="companyModal">&times;</button>
        </div>

        <form id="companyForm">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Company name</label>
                        <input class="field" name="company" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact person</label>
                        <input class="field" name="contact" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact email</label>
                        <input class="field" type="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="field" name="status">
                            <option value="active">Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-light" data-close="companyModal">Cancel</button>
                <button class="btn btn-primary">Add Company</button>
            </div>
        </form>
    </div>
</div>

<!-- =============================================================
     ADD INTERN MODAL
============================================================== -->
<div class="modal-backdrop" id="internModal">
    <div class="modal">
        <div class="modal-head">
            <h2>Add Intern Student</h2>
            <button class="close" data-close="internModal">&times;</button>
        </div>

        <form id="internForm">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Student name</label>
                        <input class="field" name="name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Student ID</label>
                        <input class="field" name="studentId" maxlength="20" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Program</label>
                        <input class="field" name="program" placeholder="BSIT - Web Development" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Assigned company</label>
                        <select class="field" name="company">
                            <option value="">Not Assigned</option>
                            <option>ABC Technology Solutions</option>
                            <option>XYZ Corporation</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Internship start date</label>
                        <input class="field" type="date" name="startDate">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Required hours</label>
                        <input class="field" type="number" name="requiredHours" value="360" min="1" required>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Completed hours</label>
                        <input class="field" type="number" name="completedHours" value="0" min="0">
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-light" data-close="internModal">Cancel</button>
                <button class="btn btn-primary">Add Intern Student</button>
            </div>
        </form>
    </div>
</div>

<script>
/* ================================================================
   SAMPLE DATA
   Replace this with PHP/MySQL data later.
================================================================ */

let companies = [
    {
        id:1,
        name:"ABC Technology Solutions",
        location:"Tacloban City, Leyte",
        contact:"Maria Santos",
        email:"maria@abctechcompany.com",
        students:1,
        status:"active"
    },
    {
        id:2,
        name:"XYZ Corporation",
        location:"Palo, Leyte",
        contact:"John Reyes",
        email:"john@xyzcorp.com",
        students:1,
        status:"active"
    },
    {
        id:3,
        name:"ABC Trading",
        location:"Ormoc City, Leyte",
        contact:"Michael Cruz",
        email:"michael@abctrading.com",
        students:0,
        status:"archived"
    }
];

let students = [
    {
        id:1,
        name:"Angela Mercado",
        studentId:"2026-001",
        program:"BSIT - Web Development",
        company:"ABC Technology Solutions",
        status:"Assigned",
        completed:48,
        required:360,
        week:"Week 12",
        evaluation:"On track"
    },
    {
        id:2,
        name:"Joshua Reyes",
        studentId:"2026-002",
        program:"BSIT - Networking",
        company:"Not Assigned",
        status:"Unassigned",
        completed:16,
        required:360,
        week:"Week 11",
        evaluation:"Needs placement"
    },
    {
        id:3,
        name:"Camille Dizon",
        studentId:"2026-003",
        program:"BSIT - Web Development",
        company:"XYZ Corporation",
        status:"Assigned",
        completed:201,
        required:360,
        week:"Week 14",
        evaluation:"On track"
    },
    {
        id:4,
        name:"Nathan Flores",
        studentId:"2026-004",
        program:"BSIT - Systems Development",
        company:"Not Assigned",
        status:"Unassigned",
        completed:65,
        required:360,
        week:"Week 10",
        evaluation:"Needs placement"
    }
];

/* ================================================================
   HELPERS
================================================================ */

function initials(name){
    return name
        .split(" ")
        .map(part => part[0])
        .join("")
        .slice(0,2)
        .toUpperCase();
}

function escapeHtml(value){
    return String(value)
        .replaceAll("&","&amp;")
        .replaceAll("<","&lt;")
        .replaceAll(">","&gt;")
        .replaceAll('"',"&quot;")
        .replaceAll("'","&#039;");
}

function progressPercent(completed, required){
    if(!required || required <= 0) return 0;
    return Math.min(100, Math.round((completed / required) * 100));
}

/* ================================================================
   SIDEBAR / PAGE NAVIGATION
================================================================ */

const navLinks = document.querySelectorAll(".nav-link[data-page]");
const pages = document.querySelectorAll(".page");
const app = document.querySelector(".app");
const sidebar = document.getElementById("sidebar");
const sidebarOverlay = document.getElementById("sidebarOverlay");
const menuToggle = document.getElementById("menuToggle");

function closeSidebar(){
    sidebar.classList.remove("open");
    sidebarOverlay.classList.remove("show");
}

function openSidebar(){
    sidebar.classList.add("open");
    sidebarOverlay.classList.add("show");
}

function showPage(pageName){
    pages.forEach(page => {
        page.classList.toggle("active", page.id === "page-" + pageName);
    });

    navLinks.forEach(link => {
        link.classList.toggle("active", link.dataset.page === pageName);
    });

    closeSidebar();
    window.scrollTo({top:0,behavior:"smooth"});
}

navLinks.forEach(link => {
    link.addEventListener("click", () => showPage(link.dataset.page));
});

menuToggle.addEventListener("click", () => {
    if(sidebar.classList.contains("open")){
        closeSidebar();
    }else{
        openSidebar();
    }
});

document.getElementById("sidebarClose").addEventListener("click", closeSidebar);
sidebarOverlay.addEventListener("click", closeSidebar);


/* ================================================================
   OVERVIEW - COMPANY TABLE
================================================================ */

let companyFilter = "all";

function renderCompanies(){
    const search = document.getElementById("companySearch").value.trim().toLowerCase();
    const tbody = document.getElementById("companyTable");

    let filtered = companies.filter(company => {
        const matchesSearch =
            company.name.toLowerCase().includes(search) ||
            company.contact.toLowerCase().includes(search) ||
            company.email.toLowerCase().includes(search);

        const matchesFilter =
            companyFilter === "all" ||
            company.status === companyFilter;

        return matchesSearch && matchesFilter;
    });

    if(!filtered.length){
        tbody.innerHTML = `
            <tr>
                <td colspan="6">
                    <div class="empty">No companies found.</div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filtered.map(company => `
        <tr>
            <td>
                <div class="person">
                    <div class="mini-avatar">${initials(company.name)}</div>
                    <div>
                        <div class="person-name">${escapeHtml(company.name)}</div>
                        <div class="person-sub">${escapeHtml(company.location)}</div>
                    </div>
                </div>
            </td>

            <td>${escapeHtml(company.contact)}</td>
            <td>${escapeHtml(company.email)}</td>
            <td>${company.students} ${company.students === 1 ? "Student" : "Students"}</td>

            <td>
                <span class="badge ${company.status === "active" ? "green" : "yellow"}">
                    ${company.status === "active" ? "Active" : "Archived"}
                </span>
            </td>

            <td>
                <div class="action-row">
                    <button class="small-btn" onclick="viewCompany(${company.id})">View</button>

                    ${
                        company.status === "active"
                        ? `<button class="small-btn" onclick="archiveCompany(${company.id})">Archive</button>`
                        : `<button class="small-btn" onclick="activateCompany(${company.id})">Activate</button>`
                    }
                </div>
            </td>
        </tr>
    `).join("");
}

document.getElementById("companySearch").addEventListener("input", renderCompanies);

document.querySelectorAll("[data-company-filter]").forEach(button => {
    button.addEventListener("click", () => {
        document.querySelectorAll("[data-company-filter]").forEach(b => b.classList.remove("active"));
        button.classList.add("active");
        companyFilter = button.dataset.companyFilter;
        renderCompanies();
    });
});

function viewCompany(id){
    const company = companies.find(c => c.id === id);
    if(!company) return;

    alert(
        "Company Details\\n\\n" +
        "Company: " + company.name + "\\n" +
        "Contact: " + company.contact + "\\n" +
        "Email: " + company.email + "\\n" +
        "Students: " + company.students + "\\n" +
        "Status: " + company.status
    );
}

function archiveCompany(id){
    const company = companies.find(c => c.id === id);
    if(!company) return;

    if(!confirm("Archive " + company.name + "?")) return;

    company.status = "archived";
    renderCompanies();
    updateOverviewStats();
}

function activateCompany(id){
    const company = companies.find(c => c.id === id);
    if(!company) return;

    company.status = "active";
    renderCompanies();
    updateOverviewStats();
}

function updateOverviewStats(){
    const active = companies.filter(c => c.status === "active").length;
    const archived = companies.filter(c => c.status === "archived").length;
    const assigned = students.filter(s => s.status === "Assigned").length;
    const unassigned = students.filter(s => s.status !== "Assigned").length;

    document.getElementById("activeCompanyCount").textContent = active;
    document.getElementById("archivedCompanyCount").textContent = archived;
    document.getElementById("assignedCount").textContent = assigned;
    document.getElementById("unassignedCount").textContent = unassigned;
}

/* ================================================================
   OVERVIEW - ASSIGNMENTS
================================================================ */

function renderAssignments(){
    const tbody = document.getElementById("assignmentTable");

    tbody.innerHTML = students.map(student => `
        <tr>
            <td>
                <div class="person">
                    <div class="mini-avatar">${initials(student.name)}</div>
                    <div>
                        <div class="person-name">${escapeHtml(student.name)}</div>
                    </div>
                </div>
            </td>

            <td>${escapeHtml(student.studentId)}</td>
            <td>${escapeHtml(student.program)}</td>
            <td>${escapeHtml(student.company)}</td>

            <td>
                <span class="badge ${student.status === "Assigned" ? "green" : "yellow"}">
                    ${student.status}
                </span>
            </td>

            <td>
                ${
                    student.status === "Assigned"
                    ? `<button class="small-btn" onclick="manageAssignment(${student.id})">Manage Assignment</button>`
                    : `<button class="small-btn primary" onclick="assignCompany(${student.id})">Assign Company</button>`
                }
            </td>
        </tr>
    `).join("");
}

function assignCompany(id){
    const student = students.find(s => s.id === id);
    if(!student) return;

    const activeCompanies = companies.filter(c => c.status === "active");

    if(!activeCompanies.length){
        alert("There are no active companies available for assignment.");
        return;
    }

    const choices = activeCompanies.map((c,index) => `${index + 1}. ${c.name}`).join("\\n");
    const answer = prompt(
        "Choose an active company by number:\\n\\n" +
        choices
    );

    const index = Number(answer) - 1;

    if(!Number.isInteger(index) || !activeCompanies[index]) return;

    const selected = activeCompanies[index];

    student.company = selected.name;
    student.status = "Assigned";

    selected.students += 1;

    renderAssignments();
    renderInterns();
    renderCompanies();
    renderEvaluations();
    updateOverviewStats();
}

function manageAssignment(id){
    const student = students.find(s => s.id === id);
    if(!student) return;

    if(!confirm(
        student.name +
        " is assigned to " +
        student.company +
        ".\\n\\nDo you want to remove this assignment?"
    )) return;

    const company = companies.find(c => c.name === student.company);
    if(company && company.students > 0) company.students--;

    student.company = "Not Assigned";
    student.status = "Unassigned";

    renderAssignments();
    renderInterns();
    renderCompanies();
    renderEvaluations();
    updateOverviewStats();
}

/* ================================================================
   INTERN PAGE
================================================================ */

function renderInterns(){
    const tbody = document.getElementById("internTable");

    tbody.innerHTML = students.map(student => {
        const pct = progressPercent(student.completed, student.required);

        return `
            <tr>
                <td>
                    <div class="person">
                        <div class="mini-avatar">${initials(student.name)}</div>
                        <div>
                            <div class="person-name">${escapeHtml(student.name)}</div>
                            <div class="person-sub">${escapeHtml(student.studentId)}</div>
                        </div>
                    </div>
                </td>

                <td>${escapeHtml(student.studentId)}</td>
                <td>${escapeHtml(student.program)}</td>
                <td>${escapeHtml(student.company)}</td>

                <td>
                    <span class="badge ${student.status === "Assigned" ? "green" : "yellow"}">
                        ${student.status}
                    </span>
                </td>

                <td>
                    <div class="hours-box">
                        <div class="hours-line">
                            <strong>${student.completed} hrs / ${student.required} hrs</strong>
                            <span>${pct}%</span>
                        </div>
                        <div class="progress">
                            <span style="width:${pct}%"></span>
                        </div>
                        <div class="progress-meta">${student.required - student.completed > 0 ? (student.required - student.completed) + " hrs remaining" : "Completed"}</div>
                    </div>
                </td>

                <td>
                    ${
                        student.status === "Assigned"
                        ? `<button class="small-btn" onclick="viewIntern(${student.id})">Manage Assignment</button>`
                        : `<button class="small-btn primary" onclick="assignCompany(${student.id})">Assign Company</button>`
                    }
                </td>
            </tr>
        `;
    }).join("");
}

function viewIntern(id){
    const student = students.find(s => s.id === id);
    if(!student) return;

    alert(
        "Student Information\\n\\n" +
        "Name: " + student.name + "\\n" +
        "Student ID: " + student.studentId + "\\n" +
        "Program: " + student.program + "\\n" +
        "Company: " + student.company + "\\n" +
        "Status: " + student.status + "\\n" +
        "Working Hours: " + student.completed + " / " + student.required + " hours"
    );
}

/* ================================================================
   ADD COMPANY
================================================================ */

document.getElementById("addCompanyBtn").addEventListener("click", () => {
    document.getElementById("companyModal").classList.add("show");
});

document.getElementById("companyForm").addEventListener("submit", e => {
    e.preventDefault();

    const form = new FormData(e.target);

    const company = {
        id:Date.now(),
        name:form.get("company"),
        location:"Location not set",
        contact:form.get("contact"),
        email:form.get("email"),
        students:0,
        status:form.get("status")
    };

    companies.push(company);

    e.target.reset();
    closeModal("companyModal");

    renderCompanies();
    updateOverviewStats();
});

/* ================================================================
   ADD INTERN
================================================================ */

document.getElementById("addInternBtn").addEventListener("click", () => {
    document.getElementById("internModal").classList.add("show");
});

document.getElementById("internForm").addEventListener("submit", e => {
    e.preventDefault();

    const form = new FormData(e.target);

    const required = Number(form.get("requiredHours")) || 360;
    const completed = Math.max(0, Number(form.get("completedHours")) || 0);
    const company = form.get("company") || "Not Assigned";

    const student = {
        id:Date.now(),
        name:form.get("name"),
        studentId:form.get("studentId"),
        program:form.get("program"),
        company:company,
        status:company === "Not Assigned" ? "Unassigned" : "Assigned",
        completed:Math.min(completed,required),
        required:required,
        week:"Week 1",
        evaluation:"Not evaluated"
    };

    students.push(student);

    if(company !== "Not Assigned"){
        const existingCompany = companies.find(c => c.name === company);
        if(existingCompany) existingCompany.students++;
    }

    e.target.reset();

    document.querySelector('#internForm [name="requiredHours"]').value = 360;
    document.querySelector('#internForm [name="completedHours"]').value = 0;

    closeModal("internModal");

    renderInterns();
    renderAssignments();
    renderEvaluations();
    renderCompanies();
    updateOverviewStats();
});

/* ================================================================
   EVALUATIONS
================================================================ */

function renderEvaluations(){
    const roster = document.getElementById("evaluationRoster");

    roster.innerHTML = students.map(student => `
        <div class="eval-row">
            <div class="person">
                <div class="mini-avatar">${initials(student.name)}</div>
                <div>
                    <div class="person-name">${escapeHtml(student.name)}</div>
                    <div class="person-sub">${escapeHtml(student.program)}</div>
                </div>
            </div>

            <div class="eval-hours">${student.completed} / ${student.required} hrs</div>

            <div class="week">${student.week}</div>

            <div>
                <span class="badge ${student.status === "Assigned" ? "green" : "yellow"}">
                    ${student.status === "Assigned" ? "On track" : "Awaiting placement"}
                </span>
            </div>

            <button class="small-btn">Review</button>
        </div>
    `).join("");
}

document.getElementById("evalSearch").addEventListener("input", function(){
    const query = this.value.toLowerCase();

    document.querySelectorAll("#evaluationRoster .eval-row").forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(query) ? "grid" : "none";
    });
});

document.getElementById("startEvaluationBtn").addEventListener("click", () => {
    alert("Evaluation workflow opened.");
});

document.getElementById("feedbackBtn").addEventListener("click", () => {
    alert("Evaluation form opened.");
});

/* ================================================================
   MESSAGES
================================================================ */

document.querySelectorAll(".conversation").forEach(conversation => {
    conversation.addEventListener("click", () => {
        document.querySelectorAll(".conversation").forEach(c => c.classList.remove("active"));
        conversation.classList.add("active");

        document.getElementById("chatName").textContent = conversation.dataset.chat;
    });
});

document.getElementById("sendMessageBtn").addEventListener("click", sendMessage);

document.getElementById("messageInput").addEventListener("keydown", e => {
    if(e.key === "Enter"){
        e.preventDefault();
        sendMessage();
    }
});

function sendMessage(){
    const input = document.getElementById("messageInput");
    const value = input.value.trim();

    if(!value) return;

    const bubble = document.createElement("div");
    bubble.className = "bubble me";
    bubble.textContent = value;

    document.getElementById("chatBody").appendChild(bubble);
    input.value = "";

    const body = document.getElementById("chatBody");
    body.scrollTop = body.scrollHeight;
}

document.getElementById("newMessageBtn").addEventListener("click", () => {
    alert("New message window opened.");
});

/* ================================================================
   REPORTS
================================================================ */

document.getElementById("exportReportBtn").addEventListener("click", () => {
    alert("Report export started. Connect this button to your PHP export endpoint.");
});

/* ================================================================
   SUPPORT
================================================================ */

document.getElementById("supportBtn").addEventListener("click", () => {
    alert("Your support request has been submitted.");
});

/* ================================================================
   SIGN OUT
================================================================ */

document.getElementById("signOutBtn").addEventListener("click", () => {
    if(confirm("Are you sure you want to sign out?")){
        // Replace with your real logout URL.
        window.location.href = "login.php";
    }
});

/* ================================================================
   USER PROFILE
================================================================ */

const profileModal = document.getElementById("profileModal");
const profileForm = document.getElementById("profileForm");
const profileName = document.getElementById("profileName");
const profileRole = document.getElementById("profileRole");
const profileEmail = document.getElementById("profileEmail");
const profileContact = document.getElementById("profileContact");
const profileDepartment = document.getElementById("profileDepartment");

function openProfile(){
    profileModal.classList.add("show");
}

document.getElementById("topProfileBtn").addEventListener("click", openProfile);
document.getElementById("sidebarProfile").addEventListener("click", openProfile);
document.getElementById("sidebarProfile").addEventListener("keydown", e => {
    if(e.key === "Enter" || e.key === " "){
        e.preventDefault();
        openProfile();
    }
});

profileForm.addEventListener("submit", e => {
    e.preventDefault();

    const name = profileName.value.trim();
    const role = profileRole.value.trim();
    const email = profileEmail.value.trim();
    const contact = profileContact.value.trim();
    const department = profileDepartment.value.trim();

    if(!name || !role || !email){
        showToast("Please complete the required profile fields.");
        return;
    }

    const initialsText = initials(name);

    document.querySelectorAll(".profile-name").forEach(el => {
        if(el.closest(".top-profile") || el.closest(".profile")){
            el.textContent = name;
        }
    });

    document.querySelectorAll(".profile-role").forEach(el => {
        el.textContent = role;
    });

    document.querySelectorAll(".avatar").forEach(el => {
        el.textContent = initialsText;
    });

    document.getElementById("profileAvatarLarge").textContent = initialsText;
    document.getElementById("profileDisplayName").textContent = name;
    document.getElementById("profileDisplayRole").textContent = role + " • OJT360";

    profileModal.classList.remove("show");
    showToast("Profile updated successfully.");

    console.log({
        name,
        role,
        email,
        contact,
        department
    });
});

function showToast(message){
    const toast = document.getElementById("toast");
    toast.textContent = message;
    toast.classList.add("show");

    clearTimeout(window.__toastTimer);
    window.__toastTimer = setTimeout(() => {
        toast.classList.remove("show");
    }, 2500);
}


/* ================================================================
   OJT360 AI ASSISTANT
================================================================ */

const aiLauncher = document.getElementById("aiLauncher");
const aiPanel = document.getElementById("aiPanel");
const aiClose = document.getElementById("aiClose");
const aiBody = document.getElementById("aiBody");
const aiForm = document.getElementById("aiForm");
const aiInput = document.getElementById("aiInput");

function openAssistant(){
    aiPanel.classList.add("open");
    setTimeout(() => aiInput.focus(), 180);
}

function closeAssistant(){
    aiPanel.classList.remove("open");
}

function addAssistantMessage(message, isUser = false){
    const row = document.createElement("div");
    row.className = "ai-row" + (isUser ? " user" : "");

    if(!isUser){
        const avatar = document.createElement("div");
        avatar.className = "ai-mini-avatar";
        avatar.textContent = "✦";
        row.appendChild(avatar);
    }

    const bubble = document.createElement("div");
    bubble.className = "ai-bubble";
    bubble.textContent = message;

    const time = document.createElement("span");
    time.className = "ai-time";
    time.textContent = new Date().toLocaleTimeString([], {
        hour:"numeric",
        minute:"2-digit"
    });

    bubble.appendChild(time);
    row.appendChild(bubble);
    aiBody.appendChild(row);
    aiBody.scrollTop = aiBody.scrollHeight;
}

function getAssistantReply(question){
    const q = question.toLowerCase();

    if(q.includes("journal")){
        return "For the OJT journal, keep your daily activities, dates, hours completed, learning reflections, and required signatures or approvals. Follow the current OJT manual used by your program.";
    }

    if(q.includes("qr") || q.includes("attendance")){
        return "For QR attendance, the intern should scan the assigned attendance QR using a phone while connected to the required network or service. The system should record the time and associate it with the correct OJT placement.";
    }

    if(q.includes("assign") || q.includes("company") || q.includes("placement")){
        return "Open Overview, go to Company Management, open the company, then assign the selected intern to that company. The intern's placement should show the assigned company and status.";
    }

    if(q.includes("evaluation")){
        return "Evaluations can be prepared from the Evaluations page. Select the intern, review the required criteria, add feedback, and save or submit the evaluation according to your workflow.";
    }

    if(q.includes("intern")){
        return "The Interns page is focused on student placement and OJT progress. You can review assigned companies, completed hours, status, and student information there.";
    }

    if(q.includes("report")){
        return "Reports & Analytics can be used to review internship progress, hours, placement information, and administration-ready summaries.";
    }

    if(q.includes("help") || q.includes("support")){
        return "You can open Help & Support from the sidebar to submit an issue or request assistance from the OJT coordination team.";
    }

    return "I can help with OJT journals, QR attendance, evaluations, student placement, companies, reports, and OJT360 navigation. Try asking about one of those topics.";
}

aiLauncher.addEventListener("click", () => {
    if(aiPanel.classList.contains("open")){
        closeAssistant();
    }else{
        openAssistant();
    }
});

aiClose.addEventListener("click", closeAssistant);

document.querySelectorAll(".ai-suggestion").forEach(button => {
    button.addEventListener("click", () => {
        const question = button.dataset.question;
        addAssistantMessage(question, true);

        setTimeout(() => {
            addAssistantMessage(getAssistantReply(question));
        }, 280);
    });
});

aiForm.addEventListener("submit", e => {
    e.preventDefault();

    const question = aiInput.value.trim();
    if(!question) return;

    addAssistantMessage(question, true);
    aiInput.value = "";

    setTimeout(() => {
        addAssistantMessage(getAssistantReply(question));
    }, 280);
});

/* ================================================================
   MODALS
================================================================ */

function closeModal(id){
    document.getElementById(id).classList.remove("show");
}

document.querySelectorAll("[data-close]").forEach(button => {
    button.addEventListener("click", () => closeModal(button.dataset.close));
});

document.querySelectorAll(".modal-backdrop").forEach(backdrop => {
    backdrop.addEventListener("click", e => {
        if(e.target === backdrop){
            backdrop.classList.remove("show");
        }
    });
});

document.addEventListener("keydown", e => {
    if(e.key === "Escape"){
        document.querySelectorAll(".modal-backdrop.show").forEach(modal => {
            modal.classList.remove("show");
        });
        closeSidebar();
    }
});

/* ================================================================
   INITIAL RENDER
================================================================ */

renderCompanies();
renderAssignments();
renderInterns();
renderEvaluations();
updateOverviewStats();
</script>

</body>
</html>
