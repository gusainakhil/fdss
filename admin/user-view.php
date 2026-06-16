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
        body { font-family: 'Georgia', serif; background: #fff; color: #111; font-size: 14px; }

        /* ── action bar ── */
        .action-bar {
            border-bottom: 1px solid #ccc;
            padding: 10px 24px; display: flex; justify-content: flex-end; gap: 8px;
        }
        .btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
            border: 1.5px solid #333; background: #fff; color: #333;
            text-decoration: none; font-family: Arial, sans-serif; transition: .15s;
        }
        .btn:hover { background: #111; color: #fff; border-color: #111; }
        .btn.primary { background: #111; color: #fff; border-color: #111; }
        .btn.primary:hover { background: #333; border-color: #333; }

        /* ── document wrapper ── */
        .doc { max-width: 780px; margin: 28px auto; background: #fff; border: 1px solid #999; padding-bottom: 32px; }

        /* ── header ── */
        .ir-banner { border-bottom: 2px solid #111; }
        .ir-top { display: flex; align-items: stretch; }
        .ir-logo-cell {
            width: 96px; min-width: 96px;
            display: flex; align-items: center; justify-content: center;
            padding: 14px 10px;
            border-right: 1px solid #999;
        }
        .ir-title-cell { flex: 1; padding: 16px 22px; border-right: none; }
        .ir-title-cell .ir-main { font-size: 1.15rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; font-family: Arial, sans-serif; }
        .ir-title-cell .ir-sub  { font-size: .78rem; color: #444; margin-top: 4px; letter-spacing: .02em; font-family: Arial, sans-serif; }
        .ir-loc { display: flex; border-top: 1px solid #ccc; }
        .ir-loc-item {
            flex: 1; padding: 8px 22px;
            border-right: 1px solid #ccc;
        }
        .ir-loc-item:last-child { border-right: none; }
        .ir-loc-item .loc-label { font-size: .62rem; letter-spacing: .1em; text-transform: uppercase; color: #777; margin-bottom: 2px; font-family: Arial, sans-serif; }
        .ir-loc-item .loc-val   { font-weight: 700; font-size: .82rem; font-family: Arial, sans-serif; }

        /* ── user strip ── */
        .user-strip {
            border-bottom: 1px solid #ccc;
            padding: 14px 24px;
            display: flex; align-items: center; gap: 14px;
        }
        .user-icon {
            width: 44px; height: 44px; border-radius: 50%;
            border: 2px solid #333;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.35rem; flex-shrink: 0; color: #333;
        }
        .user-strip h2 { font-size: 1.1rem; font-weight: 700; font-family: Arial, sans-serif; }
        .user-strip .usub { font-size: .8rem; color: #555; margin-top: 2px; font-family: Arial, sans-serif; }
        .status-badge {
            margin-left: auto; padding: 3px 12px;
            font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
            border: 1.5px solid #333; font-family: Arial, sans-serif;
        }
        .s-active   { color: #111; border-color: #111; }
        .s-inactive { color: #777; border-color: #777; }

        /* ── section ── */
        .section { border-top: 1px solid #ccc; }
        .sec-title {
            padding: 6px 20px; background: #f7f7f7;
            font-size: .68rem; font-weight: 700; letter-spacing: .12em;
            text-transform: uppercase; color: #333;
            border-bottom: 1px solid #ccc;
            display: flex; align-items: center; gap: 7px;
            font-family: Arial, sans-serif;
        }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; }
        .cell { padding: 13px 24px; border-bottom: 1px solid #eee; }
        .cell:nth-child(odd) { border-right: 1px solid #eee; }
        .cell.full { grid-column: 1/-1; border-right: none; }
        .lbl { font-size: .65rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #888; margin-bottom: 3px; font-family: Arial, sans-serif; }
        .val { font-size: .92rem; font-weight: 500; color: #111; word-break: break-word; }
        .val-warn { font-weight: 700; text-decoration: underline; }

        /* ── credentials ── */
        .cred-section { border: 1px solid #999; margin: 24px 24px 0; }
        .cred-head { border-bottom: 1px solid #999; padding: 9px 18px; font-size: .68rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; display: flex; align-items: center; gap: 7px; font-family: Arial, sans-serif; background: #f7f7f7; }
        .cred-body { padding: 18px; }
        .cred-row  { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .cred-row:last-child { margin-bottom: 0; }
        .cred-lbl  { width: 85px; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #777; flex-shrink: 0; font-family: Arial, sans-serif; }
        .cred-val  { flex: 1; border: 1px solid #ccc; padding: 7px 12px; font-family: 'Courier New', monospace; font-size: .88rem; font-weight: 600; color: #111; word-break: break-all; }
        .copy-btn  { background: #fff; border: 1px solid #aaa; padding: 5px 9px; cursor: pointer; color: #444; font-size: .8rem; flex-shrink: 0; transition: .15s; }
        .copy-btn:hover { background: #111; color: #fff; border-color: #111; }
        .cred-note { margin-top: 14px; padding: 9px 13px; border-left: 3px solid #555; font-size: .78rem; color: #333; font-family: Arial, sans-serif; }

        /* ── mobile app section ── */
        .app-section { border: 1px solid #999; margin: 15px 20px 0; }
        .app-head { border-bottom: 1px solid #999; padding: 9px 18px; font-size: .68rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; display: flex; align-items: center; gap: 7px; font-family: Arial, sans-serif; background: #f7f7f7; }
        /* .app-body { padding: 16px 18px; } */
        .app-steps { display: flex; flex-direction: column; gap: 12px; }
        .app-step  { display: flex; align-items: flex-start; gap: 14px; padding: 12px; border: 1px solid #e5e5e5; }
        .step-num  { width: 26px; height: 26px; border: 1.5px solid #333; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; flex-shrink: 0; font-family: Arial, sans-serif; }
        .step-text { flex: 1; }
        .step-title { font-size: .85rem; font-weight: 700; font-family: Arial, sans-serif; margin-bottom: 3px; }
        .step-desc  { font-size: .78rem; color: #444; font-family: Arial, sans-serif; line-height: 1.4; }
        .playstore-btn {
            display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;
            padding: 6px 14px; border: 1.5px solid #333; font-size: .75rem;
            font-weight: 700; font-family: Arial, sans-serif; color: #111;
            text-decoration: none; transition: .15s; align-self: center;
        }
        .playstore-btn:hover { background: #111; color: #fff; }
        .app-qr-note { margin-top: 14px; padding: 9px 13px; border-left: 3px solid #555; font-size: .78rem; color: #333; font-family: Arial, sans-serif; }

        /* ── print ── */
        @media print {
            body { background: #fff; }
            .action-bar { display: none !important; }
            .doc { margin: 0; border: none; max-width: 100%; }
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

    <!-- Header -->
    <div class="ir-banner">
        <div class="ir-top">
            <div class="ir-logo-cell">
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR5p8xE7dtdF6srpwKE_YHrf2_4auHB4AiUTQ&s" alt="logo" height="68px" width="78px">
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

    <!-- User strip -->
    <div class="user-strip">
        <div class="user-icon"><i class="bi bi-person-fill"></i></div>
        <div>
            <h2><?= htmlspecialchars($user['user_name']) ?></h2>
            <div class="usub"><?= htmlspecialchars($user['username']) ?><?php if ($user['designation']): ?> &nbsp;·&nbsp; <?= htmlspecialchars($user['designation']) ?><?php endif; ?></div>
        </div>
        <span class="status-badge <?= $user['status'] === 'Active' ? 's-active' : 's-inactive' ?>"><?= htmlspecialchars($user['status']) ?></span>
    </div>

    <!-- Personal -->
    <div class="section">
        <div class="sec-title"><i class="bi bi-person-lines-fill"></i> Personal Information</div>
        <div class="row2">
            <div class="cell"><div class="lbl">Full Name</div><div class="val"><?= htmlspecialchars($user['user_name']) ?></div></div>
            <div class="cell"><div class="lbl">Username</div><div class="val"><?= htmlspecialchars($user['username']) ?></div></div>
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

    <!-- Mobile App -->
    <div class="app-section">
        <div class="app-head"><i class="bi bi-phone"></i> Mobile Application</div>
        <div class="app-body">
            <div class="app-steps">
                <div class="app-step">
                    <div class="step-num">1</div>
                    <div class="step-text">
                        <div class="step-title">Download the App</div>
                        <div class="step-desc">Search <strong>FDSS / FSDS</strong> on Google Play Store and install the app.</div>
                    </div>
                    <a href="https://play.google.com/store/apps/" target="_blank" class="playstore-btn no-print">
                        <i class="bi bi-google-play"></i> Open Play Store
                    </a>
                </div>
                <!-- <div class="app-step">
                    <div class="step-num">2</div>
                    <div class="step-text">
                        <div class="step-title">Login with Your Credentials</div>
                        <div class="step-desc">Use the username and default password provided above to sign in.</div>
                    </div>
                </div> -->
                <div class="app-step">
                    <div class="step-num">2</div>
                    <div class="step-text">
                        <div class="step-title">Create Users from Dashboard</div>
                        <div class="step-desc"> go to the Dashboard to add and manage user accounts for your station.</div>
                    </div>
                </div>
            </div>
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
