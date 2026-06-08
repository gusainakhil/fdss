<?php
require_once __DIR__ . '/_auth.php';

$user_id = (int) ($_GET['user_id'] ?? 0);
if ($user_id <= 0) {
    header('Location: users.php');
    exit;
}

$user = null;
$stmt = $conn->prepare("SELECT
    u.user_id, u.user_name, u.username, u.email, u.phone,
    u.designation, u.role, u.status, u.start_date, u.end_date, u.created_at,
    st.station_name, d.division_name, z.zone_name
FROM fdss_users u
LEFT JOIN fdss_stations st ON st.station_id = u.station_id
LEFT JOIN fdss_divisions d ON d.division_id = st.division_id
LEFT JOIN fdss_zones z ON z.zone_id = d.zone_id
WHERE u.user_id = ? AND u.role = 'ORG_ADMIN'
LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$user) {
    header('Location: users.php');
    exit;
}

$login_url      = 'https://fdss.beatleanalytics.in/';
$default_pass   = '123456';

$days_left = 0;
if (!empty($user['end_date'])) {
    $diff = (new DateTime(date('Y-m-d')))->diff(new DateTime($user['end_date']));
    $days_left = max(0, (int) $diff->format('%r%a'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details — <?= htmlspecialchars($user['user_name']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f2f2f2; color: #111; font-size: 14px; }

        /* ── browser-only action bar ── */
        .action-bar {
            background: #fff; border-bottom: 1px solid #ccc;
            padding: 9px 24px; display: flex; justify-content: flex-end; gap: 8px;
        }
        .btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
            border-radius: 3px; border: 1.5px solid #003580; background: #fff; color: #003580;
            text-decoration: none; transition: .15s;
        }
        .btn:hover, .btn.primary { background: #003580; color: #fff; }
        .btn.primary:hover { background: #002560; border-color: #002560; }

        /* ── document wrapper ── */
        .doc { max-width: 780px; margin: 28px auto; background: #fff; border: 1px solid #bbb; padding-bottom: 32px; }

        /* ── IR header banner ── */
        .ir-banner {
            background: #003580;
            color: #fff;
            padding: 0;
        }
        .ir-top {
            display: flex; align-items: center; gap: 0;
            border-bottom: 3px solid #f5a623;
        }
        .ir-logo-cell {
            width: 90px; min-width: 90px;
            background: #fff;
            display: flex; align-items: center; justify-content: center;
            padding: 14px 10px;
            border-right: 3px solid #f5a623;
        }
        .ir-logo-cell svg { width: 54px; height: 54px; }
        .ir-title-cell { flex: 1; padding: 14px 22px; }
        .ir-title-cell .ir-main { font-size: 1.12rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .ir-title-cell .ir-sub  { font-size: .78rem; opacity: .85; margin-top: 3px; letter-spacing: .02em; }
        .ir-loc {
            display: flex; gap: 0;
            background: #002060;
            font-size: .75rem;
        }
        .ir-loc-item {
            flex: 1; padding: 7px 22px;
            border-right: 1px solid rgba(255,255,255,.15);
        }
        .ir-loc-item:last-child { border-right: none; }
        .ir-loc-item .loc-label { font-size: .62rem; letter-spacing: .1em; text-transform: uppercase; opacity: .65; margin-bottom: 2px; }
        .ir-loc-item .loc-val   { font-weight: 700; font-size: .82rem; }

        /* ── user name strip ── */
        .user-strip {
            background: #f5a623;
            padding: 12px 24px;
            display: flex; align-items: center; gap: 14px;
        }
        .user-icon {
            width: 44px; height: 44px; border-radius: 50%;
            background: #003580; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.35rem; flex-shrink: 0;
        }
        .user-strip h2 { font-size: 1.1rem; font-weight: 700; color: #111; }
        .user-strip .usub { font-size: .8rem; color: #333; margin-top: 2px; }
        .status-badge {
            margin-left: auto; padding: 3px 12px; border-radius: 2px;
            font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
        }
        .s-active   { background: #003580; color: #fff; }
        .s-inactive { background: #888;    color: #fff; }

        /* ── section ── */
        .section { border-top: 1px solid #ddd; }
        .sec-title {
            background: #f0f4fb; padding: 8px 24px;
            font-size: .7rem; font-weight: 700; letter-spacing: .12em;
            text-transform: uppercase; color: #003580;
            border-bottom: 1px solid #ddd;
            display: flex; align-items: center; gap: 7px;
        }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; }
        .cell { padding: 13px 24px; border-bottom: 1px solid #f0f0f0; }
        .cell:nth-child(odd) { border-right: 1px solid #f0f0f0; }
        .cell.full { grid-column: 1/-1; border-right: none; }
        .lbl { font-size: .67rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #999; margin-bottom: 3px; }
        .val { font-size: .92rem; font-weight: 500; color: #111; word-break: break-word; }
        .val-warn { color: #c0392b; font-weight: 700; }

        /* ── credentials ── */
        .cred-section { border-top: 2px solid #003580; margin: 20px 24px 0; border-radius: 4px; overflow: hidden; }
        .cred-head { background: #003580; color: #fff; padding: 10px 18px; font-size: .7rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; display: flex; align-items: center; gap: 7px; }
        .cred-body { padding: 18px; background: #f8faff; }
        .cred-row  { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .cred-row:last-child { margin-bottom: 0; }
        .cred-lbl  { width: 85px; font-size: .67rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #888; flex-shrink: 0; }
        .cred-val  { flex: 1; background: #fff; border: 1px solid #ccc; border-radius: 3px; padding: 7px 12px; font-family: 'Courier New', monospace; font-size: .88rem; font-weight: 600; color: #111; word-break: break-all; }
        .copy-btn  { background: #fff; border: 1px solid #bbb; border-radius: 3px; padding: 5px 9px; cursor: pointer; color: #555; font-size: .8rem; flex-shrink: 0; transition: .15s; }
        .copy-btn:hover { background: #003580; color: #fff; border-color: #003580; }
        .cred-note { margin-top: 14px; padding: 9px 13px; background: #fff3cd; border-left: 3px solid #f5a623; font-size: .78rem; color: #6b4c00; border-radius: 0 3px 3px 0; }

        /* ── print ── */
        @media print {
            body { background: #fff; }
            .action-bar { display: none !important; }
            .doc { margin: 0; border: none; max-width: 100%; }
            .ir-banner, .ir-top, .ir-loc, .user-strip, .sec-title, .cred-head {
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
            .copy-btn { display: none !important; }
        }
    </style>
</head>
<body>

<!-- browser-only action bar -->
<div class="action-bar no-print">
    <a href="users.php" class="btn"><i class="bi bi-arrow-left"></i> Back</a>
    <button class="btn primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>

<div class="doc">

    <!-- Indian Railways header -->
    <div class="ir-banner">
        <div class="ir-top">
            <div class="ir-logo-cell">
                <!-- IR wheel logo (SVG) -->
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR5p8xE7dtdF6srpwKE_YHrf2_4auHB4AiUTQ&s" alt="logo" height="80px" width="90px"> 
            </div>
            <div class="ir-title-cell">
                <div class="ir-main">Indian Railways</div>
                <div class="ir-sub">FDSS / FSDS — Fire Detection &amp; Suppression System</div>
            </div>
        </div>
        <div class="ir-loc">
            <div class="ir-loc-item">
                <div class="loc-label">Zone</div>
                <div class="loc-val"><?= htmlspecialchars($user['zone_name'] ?: '—') ?></div>
            </div>
            <div class="ir-loc-item">
                <div class="loc-label">Division</div>
                <div class="loc-val"><?= htmlspecialchars($user['division_name'] ?: '—') ?></div>
            </div>
            <div class="ir-loc-item">
                <div class="loc-label">Station</div>
                <div class="loc-val"><?= htmlspecialchars($user['station_name'] ?: '—') ?></div>
            </div>
            <div class="ir-loc-item">
                <div class="loc-label">Issue Date</div>
                <div class="loc-val"><?= date('d M Y') ?></div>
            </div>
        </div>
    </div>

    <!-- User name strip -->
    <div class="user-strip">
        <div class="user-icon"><i class="bi bi-person-fill"></i></div>
        <div>
            <h2><?= htmlspecialchars($user['user_name']) ?></h2>
            <div class="usub">@<?= htmlspecialchars($user['username']) ?><?php if ($user['designation']): ?> &nbsp;·&nbsp; <?= htmlspecialchars($user['designation']) ?><?php endif; ?></div>
        </div>
        <span class="status-badge <?= $user['status'] === 'Active' ? 's-active' : 's-inactive' ?>"><?= htmlspecialchars($user['status']) ?></span>
    </div>

    <!-- Personal -->
    <div class="section">
        <div class="sec-title"><i class="bi bi-person-lines-fill"></i> Personal Information</div>
        <div class="row2">
            <div class="cell"><div class="lbl">Full Name</div><div class="val"><?= htmlspecialchars($user['user_name']) ?></div></div>
            <div class="cell"><div class="lbl">Username</div><div class="val">@<?= htmlspecialchars($user['username']) ?></div></div>
            <div class="cell"><div class="lbl">Email</div><div class="val"><?= htmlspecialchars($user['email'] ?: '—') ?></div></div>
            <div class="cell"><div class="lbl">Phone</div><div class="val"><?= htmlspecialchars($user['phone'] ?: '—') ?></div></div>
            <div class="cell"><div class="lbl">Designation</div><div class="val"><?= htmlspecialchars($user['designation'] ?: '—') ?></div></div>
            <div class="cell"><div class="lbl">Role</div><div class="val"><?= htmlspecialchars($user['role']) ?></div></div>
        </div>
    </div>

    <!-- Subscription -->
    <div class="section">
        <div class="sec-title"><i class="bi bi-calendar-check-fill"></i> Subscription Details</div>
        <div class="row2">
            <div class="cell"><div class="lbl">Start Date</div><div class="val"><?= $user['start_date'] ? date('d M Y', strtotime($user['start_date'])) : '—' ?></div></div>
            <div class="cell"><div class="lbl">End Date</div><div class="val"><?= $user['end_date'] ? date('d M Y', strtotime($user['end_date'])) : '—' ?></div></div>
            <div class="cell"><div class="lbl">Days Remaining</div><div class="val <?= $days_left <= 10 ? 'val-warn' : '' ?>"><?= $days_left ?> days</div></div>
            <div class="cell"><div class="lbl">Account Created</div><div class="val"><?= $user['created_at'] ? date('d M Y', strtotime($user['created_at'])) : '—' ?></div></div>
        </div>
    </div>

    <!-- Credentials -->
    <div class="cred-section">
        <div class="cred-head"><i class="bi bi-shield-lock-fill"></i> Login Credentials</div>
        <div class="cred-body">
            <div class="cred-row">
                <span class="cred-lbl">Portal</span>
                <span class="cred-val" id="credLink"><?= htmlspecialchars($login_url) ?></span>
                <button class="copy-btn no-print" onclick="copyText('credLink',this)"><i class="bi bi-clipboard"></i></button>
            </div>
            <div class="cred-row">
                <span class="cred-lbl">Username</span>
                <span class="cred-val" id="credUser"><?= htmlspecialchars($user['username']) ?></span>
                <button class="copy-btn no-print" onclick="copyText('credUser',this)"><i class="bi bi-clipboard"></i></button>
            </div>
            <div class="cred-row">
                <span class="cred-lbl">Password</span>
                <span class="cred-val" id="credPass"><?= htmlspecialchars($default_pass) ?></span>
                <button class="copy-btn no-print" onclick="copyText('credPass',this)"><i class="bi bi-clipboard"></i></button>
            </div>
            <div class="cred-note"><i class="bi bi-exclamation-triangle-fill"></i> Default password. User must change it after first login.</div>
        </div>
    </div>

</div>

<script>
function copyText(id, btn) {
    navigator.clipboard.writeText(document.getElementById(id).textContent.trim()).then(() => {
        btn.innerHTML = '<i class="bi bi-check2"></i>';
        setTimeout(() => { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1800);
    });
}
</script>
</body>
</html>
<?php $conn->close(); ?>
