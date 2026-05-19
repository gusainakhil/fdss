<style>
    body{background:#f4f6f9}
    .admin-shell{display:flex}
    .admin-sidebar{
        position:fixed;
        left:0;
        top:56px;
        bottom:0;
        width:250px;
        background:#fff;
        border-right:1px solid #dce4ec;
        padding:14px;
        display:flex;
        flex-direction:column;
        gap:14px;
        z-index:1010;
    }
    .admin-brand{
        display:flex;
        align-items:center;
        gap:10px;
        padding:10px;
        border:1px solid #e5edf4;
        background:#f8fbfd;
    }
    .admin-brand-icon{
        width:38px;
        height:38px;
        display:flex;
        align-items:center;
        justify-content:center;
        background:#0d6efd;
        color:#fff;
        font-size:18px;
    }
    .admin-brand strong{display:block;line-height:1.1}
    .admin-brand small{color:#6c757d;font-size:12px}
    .admin-nav,.admin-sidebar-footer{display:flex;flex-direction:column;gap:6px}
    .admin-nav a,.admin-sidebar-footer a{
        display:flex;
        align-items:center;
        gap:10px;
        padding:10px 11px;
        color:#334155;
        text-decoration:none;
        border:1px solid transparent;
        font-size:14px;
    }
    .admin-nav a:hover,.admin-sidebar-footer a:hover{background:#f3f7fb;border-color:#dce9f4}
    .admin-nav a.active{background:#eaf4ff;border-color:#bfdcf5;color:#0b5ed7;font-weight:700}
    .admin-sidebar-footer{margin-top:auto;border-top:1px solid #e8eef4;padding-top:12px}
    .admin-main{margin-left:250px;padding:18px;width:calc(100% - 250px)}
    .admin-stat{
        display:flex;
        align-items:center;
        gap:12px;
        min-height:96px;
        border:1px solid #dfe8f0;
        background:#fff;
        padding:14px;
    }
    .admin-stat-icon{
        width:46px;
        height:46px;
        display:flex;
        align-items:center;
        justify-content:center;
        color:#fff;
        background:#0d6efd;
        font-size:22px;
    }
    .admin-stat h6{margin:0;color:#64748b;font-size:12px;font-weight:700;text-transform:uppercase}
    .admin-stat h3{margin:2px 0 0;font-weight:800;color:#1f2937}
    .admin-stat p{margin:2px 0 0;color:#64748b;font-size:12px}
    .admin-action-card{
        border:1px solid #dfe8f0;
        background:#fff;
        padding:16px;
        min-height:142px;
        display:flex;
        flex-direction:column;
        justify-content:space-between;
    }
    .admin-action-card h5{font-size:15px;margin-bottom:6px}
    .admin-action-card p{font-size:13px;color:#64748b;margin-bottom:12px}
    .table td,.table th{vertical-align:middle}
    @media(max-width:992px){
        .admin-sidebar{position:static;width:100%;border-right:0;border-bottom:1px solid #dce4ec}
        .admin-shell{display:block}
        .admin-main{margin-left:0;width:100%}
    }
</style>
