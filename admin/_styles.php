<style>
    :root{
        --admin-bg:#eef3f7;
        --admin-panel:#ffffff;
        --admin-panel-soft:#f7fafc;
        --admin-ink:#243447;
        --admin-muted:#677789;
        --admin-line:#d8e2ea;
        --admin-line-strong:#c5d3df;
        --admin-primary:#2f7db8;
        --admin-primary-dark:#215f8e;
        --admin-teal:#188b86;
        --admin-amber:#b77616;
        --admin-red:#bb4a4a;
        --admin-sidebar:#172536;
        --admin-sidebar-soft:#22364d;
        --admin-shadow:0 8px 24px rgba(33,54,75,.08);
    }

    body.admin-page-body{
        background:var(--admin-bg);
        color:var(--admin-ink);
        font-family:Arial,sans-serif;
        padding-top:64px;
    }

    .admin-topbar{
        min-height:64px;
        background:var(--admin-panel);
        border-bottom:1px solid var(--admin-line);
        box-shadow:0 2px 14px rgba(33,54,75,.06);
        z-index:1030;
    }

    .admin-topbar .navbar-brand{
        color:var(--admin-ink) !important;
        gap:10px;
        font-size:16px;
        letter-spacing:0;
    }

    .admin-topbar-brand-icon,
    .admin-brand-icon{
        width:38px;
        height:38px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        background:var(--admin-primary);
        color:#fff;
        border-radius:6px;
        font-size:18px;
    }

    .admin-topbar .badge{
        border:1px solid var(--admin-line);
        background:var(--admin-panel-soft) !important;
        color:var(--admin-muted) !important;
    }

    .admin-user-btn{
        border:1px solid var(--admin-line-strong);
        color:var(--admin-ink);
        background:#fff;
        border-radius:6px;
    }

    .admin-user-btn:hover,
    .admin-user-btn:focus{
        border-color:var(--admin-primary);
        color:var(--admin-primary-dark);
        background:#f4f9fd;
    }

    .admin-topbar .dropdown-menu{
        border:1px solid var(--admin-line);
        border-radius:8px;
        box-shadow:var(--admin-shadow);
        padding:8px;
    }

    .admin-topbar .dropdown-item{
        border-radius:6px;
        color:var(--admin-ink);
        font-size:13px;
        font-weight:600;
        padding:9px 10px;
    }

    .admin-topbar .dropdown-item:hover{
        background:#eef6fb;
        color:var(--admin-primary-dark);
    }

    .admin-shell{
        display:flex;
        min-height:calc(100vh - 64px);
    }

    .admin-sidebar{
        position:fixed;
        left:0;
        top:64px;
        bottom:0;
        width:268px;
        background:var(--admin-sidebar);
        border-right:1px solid rgba(255,255,255,.08);
        padding:16px;
        display:flex;
        flex-direction:column;
        gap:16px;
        z-index:1020;
        overflow-y:auto;
    }

    .admin-brand{
        display:flex;
        align-items:center;
        gap:12px;
        padding:12px;
        border:1px solid rgba(255,255,255,.1);
        background:var(--admin-sidebar-soft);
        border-radius:8px;
    }

    .admin-brand strong{
        display:block;
        color:#fff;
        line-height:1.1;
        font-size:15px;
    }

    .admin-brand small{
        display:block;
        color:#aab9c8;
        font-size:12px;
        margin-top:3px;
    }

    .admin-nav,
    .admin-sidebar-footer{
        display:flex;
        flex-direction:column;
        gap:7px;
    }

    .admin-nav a,
    .admin-sidebar-footer a{
        min-height:42px;
        display:flex;
        align-items:center;
        gap:11px;
        padding:10px 12px;
        color:#d6e0ea;
        text-decoration:none;
        border:1px solid transparent;
        border-radius:6px;
        font-size:14px;
        font-weight:600;
    }

    .admin-nav a i,
    .admin-sidebar-footer a i{
        width:18px;
        text-align:center;
        font-size:16px;
    }

    .admin-nav a:hover,
    .admin-sidebar-footer a:hover{
        background:rgba(255,255,255,.08);
        border-color:rgba(255,255,255,.1);
        color:#fff;
    }

    .admin-nav a.active{
        background:#fff;
        border-color:#fff;
        color:var(--admin-primary-dark);
        box-shadow:0 8px 18px rgba(0,0,0,.12);
    }

    .admin-sidebar-footer{
        margin-top:auto;
        border-top:1px solid rgba(255,255,255,.1);
        padding-top:14px;
    }

    .admin-sidebar-footer a.text-danger{
        color:#ffd0d0 !important;
    }

    .admin-main{
        margin-left:268px;
        width:calc(100% - 268px);
        padding:22px;
    }

    .admin-main .page-header{
        background:var(--admin-panel);
        border:1px solid var(--admin-line);
        border-left:4px solid var(--admin-primary);
        border-radius:8px;
        box-shadow:var(--admin-shadow);
        padding:18px 20px;
        margin-bottom:18px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:16px;
    }

    .admin-main .page-header h1{
        margin:0;
        color:var(--admin-ink);
        font-size:24px;
        font-weight:800;
        letter-spacing:0;
    }

    .admin-main .page-header-subtitle{
        margin:5px 0 0;
        color:var(--admin-muted);
        font-size:13px;
    }

    .admin-main .page-header-actions{
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }

    .admin-main .content-card,
    .admin-action-card,
    .admin-stat{
        background:var(--admin-panel);
        border:1px solid var(--admin-line);
        border-radius:8px;
        box-shadow:var(--admin-shadow);
        margin-bottom:16px;
    }

    .admin-main .content-card .card-header{
        background:var(--admin-panel-soft);
        border-bottom:1px solid var(--admin-line);
        border-radius:8px 8px 0 0;
        padding:13px 16px;
    }

    .admin-main .content-card .card-header h5{
        margin:0;
        color:var(--admin-ink);
        font-size:15px;
        font-weight:800;
        display:flex;
        align-items:center;
        gap:8px;
    }

    .admin-main .content-card .card-body{
        padding:16px;
    }

    .admin-stat{
        min-height:108px;
        display:flex;
        align-items:center;
        gap:13px;
        padding:15px;
        overflow:hidden;
    }

    .admin-stat-icon{
        width:48px;
        height:48px;
        flex:0 0 48px;
        display:flex;
        align-items:center;
        justify-content:center;
        color:#fff;
        background:var(--admin-primary);
        border-radius:7px;
        font-size:22px;
    }

    .admin-stat h6{
        margin:0;
        color:var(--admin-muted);
        font-size:11px;
        font-weight:800;
        text-transform:uppercase;
    }

    .admin-stat h3{
        margin:3px 0 0;
        color:var(--admin-ink);
        font-size:27px;
        font-weight:800;
        line-height:1;
    }

    .admin-stat p{
        margin:5px 0 0;
        color:var(--admin-muted);
        font-size:12px;
        line-height:1.3;
    }

    .admin-action-card{
        min-height:146px;
        padding:17px;
        display:flex;
        flex-direction:column;
        justify-content:space-between;
        gap:14px;
    }

    .admin-action-card h5{
        margin-bottom:7px;
        color:var(--admin-ink);
        font-size:15px;
        font-weight:800;
    }

    .admin-action-card p{
        margin:0;
        color:var(--admin-muted);
        font-size:13px;
        line-height:1.45;
    }

    .admin-main .form-label{
        color:var(--admin-muted);
        font-size:12px;
        font-weight:800;
        text-transform:uppercase;
    }

    .admin-main .form-control,
    .admin-main .form-select{
        min-height:40px;
        border-color:var(--admin-line-strong);
        border-radius:6px;
        font-size:13px;
    }

    .admin-main .form-control:focus,
    .admin-main .form-select:focus{
        border-color:var(--admin-primary);
        box-shadow:0 0 0 .2rem rgba(47,125,184,.14);
    }

    .admin-main .btn,
    .admin-topbar .btn{
        border-radius:6px;
        font-size:13px;
        font-weight:700;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        white-space:nowrap;
    }

    .admin-main .btn-primary{
        background:var(--admin-primary);
        border-color:var(--admin-primary);
    }

    .admin-main .btn-primary:hover{
        background:var(--admin-primary-dark);
        border-color:var(--admin-primary-dark);
    }

    .admin-main .btn-outline-primary{
        color:var(--admin-primary-dark);
        border-color:var(--admin-primary);
    }

    .admin-main .btn-outline-primary:hover{
        color:#fff;
        background:var(--admin-primary);
        border-color:var(--admin-primary);
    }

    .admin-main .alert{
        border-radius:8px;
        border:1px solid transparent;
        box-shadow:var(--admin-shadow);
    }

    .admin-main .table{
        margin:0;
        color:var(--admin-ink);
        font-size:13px;
    }

    .admin-main .table thead th{
        background:#e8f1f7;
        color:#28526f;
        border-bottom:1px solid var(--admin-line-strong);
        font-size:11px;
        font-weight:800;
        text-transform:uppercase;
        white-space:nowrap;
    }

    .admin-main .table td,
    .admin-main .table th{
        padding:11px 13px;
        vertical-align:middle;
        border-color:var(--admin-line);
    }

    .admin-main .table-hover tbody tr:hover{
        background:#f5f9fc;
    }

    .admin-main .badge{
        border-radius:5px;
        padding:6px 8px;
        font-size:11px;
        font-weight:800;
    }

    .admin-main .text-bg-info{
        background:#dff4f2 !important;
        color:#106966 !important;
    }

    .admin-main .text-bg-success{
        background:#e2f4e8 !important;
        color:#22663e !important;
    }

    .admin-main .text-bg-secondary{
        background:#edf1f5 !important;
        color:#5e6d7c !important;
    }

    .admin-page-body .modal-content{
        border:1px solid var(--admin-line);
        border-radius:8px;
        box-shadow:0 18px 45px rgba(33,54,75,.22);
    }

    .admin-page-body .modal-header,
    .admin-page-body .modal-footer{
        background:var(--admin-panel-soft);
        border-color:var(--admin-line);
    }

    .admin-page-body .modal-title{
        color:var(--admin-ink);
        font-size:16px;
        font-weight:800;
    }

    .admin-main .table-bordered>:not(caption)>*>*{
        border-color:var(--admin-line);
    }

    @media(max-width:992px){
        body.admin-page-body{
            padding-top:64px;
        }

        .admin-sidebar{
            position:static;
            width:100%;
            border-right:0;
            border-bottom:1px solid rgba(255,255,255,.08);
        }

        .admin-shell{
            display:block;
        }

        .admin-main{
            margin-left:0;
            width:100%;
            padding:16px;
        }

        .admin-main .page-header{
            align-items:flex-start;
            flex-direction:column;
        }
    }

    @media(max-width:576px){
        .admin-main{
            padding:12px;
        }

        .admin-main .page-header{
            padding:15px;
        }

        .admin-main .page-header h1{
            font-size:21px;
        }

        .admin-stat{
            min-height:96px;
        }
    }
</style>
