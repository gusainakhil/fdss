<?php 
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if user role is ORG_ADMIN
if ($_SESSION['role'] !== 'ORG_ADMIN') {
    header('Location: login.php?access=denied');
    exit;
}

$message = '';
$message_type = '';

// Handle Add/Edit Auditor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_auditor') {
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $email = $conn->real_escape_string($_POST['email']);
       
        $designation = $conn->real_escape_string($_POST['designation']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $password = $_POST['password'];
        $status = $_POST['status'];
        
        // Auto-generate username from name + 2 random digits
        $name_parts = explode(' ', trim($full_name));
        $first_name = strtolower(str_replace(' ', '', $name_parts[0]));
        $last_name = strtolower(str_replace(' ', '', $name_parts[1] ?? ''));
        $random_numbers = rand(10, 99);
        $username = $first_name . '_' . $last_name . '_' . $random_numbers;
        
        // Check if username already exists
        $check_query = "SELECT user_id FROM fdss_users WHERE username = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $message = "Username already exists. Please try again.";
            $message_type = "danger";
        } else {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $station_id    = null;

            // Handle profile image upload
            $profile_path  = null;
            $upload_debug  = '';
            if (!isset($_FILES['profile']) || empty($_FILES['profile']['name'])) {
                $upload_debug = 'NO_FILE_RECEIVED';
            } elseif ($_FILES['profile']['error'] !== UPLOAD_ERR_OK) {
                $upload_debug = 'UPLOAD_ERROR_CODE:' . $_FILES['profile']['error'];
            } else {
                $ext = strtolower(pathinfo($_FILES['profile']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
                    $upload_debug = 'BAD_EXT:' . $ext;
                } elseif ($_FILES['profile']['size'] > 2 * 1024 * 1024) {
                    $upload_debug = 'FILE_TOO_LARGE';
                } else {
                    $upload_dir = __DIR__ . '/uploads/profiles/';
                    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
                    $file_name = 'profile_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    if (move_uploaded_file($_FILES['profile']['tmp_name'], $upload_dir . $file_name)) {
                        $profile_path = 'uploads/profiles/' . $file_name;
                        $upload_debug = 'SUCCESS:' . $profile_path;
                    } else {
                        $upload_debug = 'MOVE_FAILED — dir:' . $upload_dir . ' writable:' . (is_writable($upload_dir) ? 'yes' : 'no');
                    }
                }
            }

            // Check if profile column exists (auto-create if missing)
            $col_res = $conn->query("SHOW COLUMNS FROM fdss_users LIKE 'profile'");
            if ($col_res && $col_res->num_rows === 0) {
                $conn->query("ALTER TABLE fdss_users ADD COLUMN profile VARCHAR(255) DEFAULT NULL");
            }

            $insert_query = "INSERT INTO fdss_users (user_name, username, email, password_hash, phone, designation, role, station_id, status, created_by_user_id, profile) VALUES (?, ?, ?, ?, ?, ?, 'AUDITOR', ?, ?, ?, ?)";
            $insert_stmt  = $conn->prepare($insert_query);
            $insert_stmt->bind_param(
                "ssssssisis",
                $full_name, $username, $email, $password_hash,
                $phone, $designation, $station_id, $status,
                $_SESSION['user_id'], $profile_path
            );

            if ($insert_stmt->execute()) {
                $message = "Auditor added! Username: <strong>$username</strong> | Upload: <code>$upload_debug</code>";
                $message_type = "success";
            } else {
                $message = "DB Error: " . $conn->error . " | Upload: <code>$upload_debug</code>";
                $message_type = "danger";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
    
    elseif ($action === 'edit_auditor') {
        $user_id = intval($_POST['user_id']);
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $email = $conn->real_escape_string($_POST['email']);

        $designation = $conn->real_escape_string($_POST['designation']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $status = $_POST['status'];
        
        // Verify that this auditor was created by the current user
        $verify_query = "SELECT user_id FROM fdss_users WHERE user_id = ? AND role = 'AUDITOR' AND created_by_user_id = ?";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param("ii", $user_id, $_SESSION['user_id']);
        $verify_stmt->execute();
        
        if ($verify_stmt->get_result()->num_rows === 0) {
            $message = "You don't have permission to edit this auditor.";
            $message_type = "danger";
        } else {
            $update_query = "UPDATE fdss_users SET user_name = ?, email = ?, phone = ?, designation = ?, status = ? WHERE user_id = ? AND role = 'AUDITOR' AND created_by_user_id = ?";
            $update_stmt = $conn->prepare($update_query);
           $update_stmt->bind_param(
    "sssssii",
    $full_name,
    $email,
    $phone,
    $designation,
    $status,
    $user_id,
    $_SESSION['user_id']
);    
            if ($update_stmt->execute()) {
                $message = "Auditor updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating auditor: " . $conn->error;
                $message_type = "danger";
            }
            $update_stmt->close();
        }
        $verify_stmt->close();
    }
    
    elseif ($action === 'delete_auditor') {
        $user_id = intval($_POST['user_id']);
        
        // Verify that this auditor was created by the current user
        $verify_query = "SELECT user_id FROM fdss_users WHERE user_id = ? AND role = 'AUDITOR' AND created_by_user_id = ?";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param("ii", $user_id, $_SESSION['user_id']);
        $verify_stmt->execute();
        
        if ($verify_stmt->get_result()->num_rows === 0) {
            $message = "You don't have permission to delete this auditor.";
            $message_type = "danger";
        } else {
            $delete_query = "DELETE FROM fdss_users WHERE user_id = ? AND role = 'AUDITOR' AND created_by_user_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("ii", $user_id, $_SESSION['user_id']);
            
            if ($delete_stmt->execute()) {
                $message = "Auditor deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting auditor: " . $conn->error;
                $message_type = "danger";
            }
            $delete_stmt->close();
        }
        $verify_stmt->close();
    }
}

// Fetch all auditors created by the logged-in user from database
$auditors = [];
$query = "SELECT u.user_id, u.user_name, u.email, u.designation, u.username, u.status, u.phone,
                 u.created_by_user_id, u.created_at, u.profile,
                 org.user_name AS org_name,
                 st.station_name, d.division_name, z.zone_name
          FROM fdss_users u
          LEFT JOIN fdss_users org ON org.user_id = u.created_by_user_id
          LEFT JOIN fdss_stations st ON st.station_id = org.station_id
          LEFT JOIN fdss_divisions d ON d.division_id = st.division_id
          LEFT JOIN fdss_zones z ON z.zone_id = d.zone_id
          WHERE u.role = 'AUDITOR' AND u.created_by_user_id = ?
          ORDER BY u.user_id DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $auditors[] = $row;
    }
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditor Management - FDSS Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
<?php include('includes/navbar.php'); ?>
<!-- <div id="navbar-placeholder"></div> -->

<div class="sidebar-container">
    <!-- <div id="sidebar-placeholder"></div> -->
    <?php include('includes/sidebar.php'); ?>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <h1>Auditor Management</h1>
                    <p class="page-header-subtitle">Manage railway auditors and assignments</p>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary" id="addAuditorBtn" data-bs-toggle="modal" data-bs-target="#auditorModal">
                        <i class="bi bi-plus-circle"></i> Add Auditor
                    </button>
                </div>
            </div>

            <!-- Display Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?php echo ($message_type === 'success') ? 'check-circle' : 'exclamation-circle'; ?>-fill"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- AUDITORS TABLE -->
            <div class="content-card">
                <div class="card-header">
                    <h5><i class="bi bi-person-badge"></i> Auditors List (<?php echo count($auditors); ?> Total)</h5>
                </div>
                <div class="card-body">
                    <div class="table-wrapper">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Username</th>
                                    <th>Designation</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($auditors)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No auditors found. Click "Add Auditor" to create one.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($auditors as $auditor): ?>
                                        <tr data-id="<?php echo htmlspecialchars($auditor['user_id']); ?>">
                                            <td><?php echo htmlspecialchars($auditor['user_id']); ?></td>
                                            <td><?php echo htmlspecialchars($auditor['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($auditor['email']); ?></td>
                                            <td><code><?php echo htmlspecialchars($auditor['username']); ?></code></td>
                                            <td><?php echo htmlspecialchars($auditor['designation'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($auditor['phone'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php $status_class = ($auditor['status'] === 'Active') ? 'badge-success' : 'badge-danger'; ?>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($auditor['status']); ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editAuditor(<?php echo $auditor['user_id']; ?>)" data-bs-toggle="modal" data-bs-target="#auditorModal">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-dark"
                                                        onclick="showIdCard(this)"
                                                        data-bs-toggle="modal" data-bs-target="#idCardModal"
                                                        data-id="<?php echo $auditor['user_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($auditor['user_name']); ?>"
                                                        data-designation="<?php echo htmlspecialchars($auditor['designation'] ?? ''); ?>"
                                                        data-username="<?php echo htmlspecialchars($auditor['username']); ?>"
                                                        data-phone="<?php echo htmlspecialchars($auditor['phone'] ?? ''); ?>"
                                                        data-email="<?php echo htmlspecialchars($auditor['email'] ?? ''); ?>"
                                                        data-status="<?php echo htmlspecialchars($auditor['status']); ?>"
                                                        data-station="<?php echo htmlspecialchars($auditor['station_name'] ?? ''); ?>"
                                                        data-division="<?php echo htmlspecialchars($auditor['division_name'] ?? ''); ?>"
                                                        data-zone="<?php echo htmlspecialchars($auditor['zone_name'] ?? ''); ?>"
                                                        data-issued="<?php echo $auditor['created_at'] ? date('d M Y', strtotime($auditor['created_at'])) : date('d M Y'); ?>"
                                                        data-profile="<?php echo htmlspecialchars($auditor['profile'] ?? ''); ?>">
                                                        <i class="bi bi-person-badge"></i> ID Card
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- AUDITOR MODAL -->
    <div class="modal fade" id="auditorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Auditor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction" value="add_auditor">
                    <input type="hidden" name="user_id" id="editingAuditorId" value="">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="auditorName" name="full_name" placeholder="Enter full name" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="auditorEmail" name="email" placeholder="Enter email address" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="auditorPhone" name="phone" placeholder="Enter phone number">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Designation</label>
                            <input type="text" class="form-control" id="auditorDesignation" name="designation" placeholder="Enter designation">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="auditorStatus" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group mb-3" id="profileGroup">
                            <label class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" id="auditorProfile" name="profile" accept="image/*">
                            <div id="profilePreview" class="mt-2" style="display:none; text-align:center;">
                                <img id="profilePreviewImg" src="" alt="Preview"
                                     style="width:80px;height:80px;object-fit:cover;border-radius:50%;border:2px solid #003580;">
                            </div>
                            <small class="text-muted">Max 2 MB. JPG, PNG, WebP, GIF supported.</small>
                        </div>
                        <div class="form-group mb-3" id="passwordGroup">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="auditorPassword" name="password" placeholder="Enter password" required>
                            <small class="text-muted d-block mt-2">Username will be auto-generated from name + 2 random digits (e.g., akhil_gusain_23)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitAuditorBtn">Add Auditor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ID CARD MODAL -->
    <div class="modal fade" id="idCardModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content border-0 bg-transparent shadow-none">
                <div class="modal-header border-0 pb-0 justify-content-end">
                    <button type="button" class="btn btn-sm btn-light me-1" onclick="printIdCard()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-1 pb-3 px-2">
                    <div id="idCardFront"><!-- injected by JS --></div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* ID card base */
        .id-card {
            width: 360px; margin: 0 auto;
            border-radius: 12px; overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,.28);
            font-family: Arial, sans-serif;
            background: #fff;
            border: 1px solid #ccc;
        }
        /* top band */
        .idc-top {
            background: #003580;
            padding: 14px 16px 10px;
            display: flex; align-items: center; gap: 12px;
            border-bottom: 4px solid #f5a623;
        }
        .idc-top-logo svg { width: 42px; height: 42px; flex-shrink:0; }
        .idc-top-text { color: #fff; line-height: 1.3; }
        .idc-top-text .t1 { font-size: .78rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .idc-top-text .t2 { font-size: .64rem; opacity: .8; }

        /* body */
        .idc-body { padding: 16px 18px 12px; }
        .idc-photo-row { display: flex; gap: 16px; align-items: flex-start; }
        .idc-photo {
            width: 76px; height: 88px; flex-shrink: 0;
            border: 3px solid #003580; border-radius: 6px;
            background: #e8eef8;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.6rem; color: #003580;
        }
        .idc-info { flex: 1; }
        .idc-name { font-size: 1rem; font-weight: 700; color: #111; line-height: 1.25; }
        .idc-desig { font-size: .72rem; color: #003580; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-top: 3px; }
        .idc-role-badge {
            display: inline-block; margin-top: 5px;
            padding: 2px 9px; border-radius: 20px;
            background: #f5a623; color: #111;
            font-size: .62rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
        }

        /* details table */
        .idc-table { width: 100%; margin-top: 12px; border-collapse: collapse; font-size: .75rem; }
        .idc-table tr td { padding: 4px 2px; vertical-align: top; }
        .idc-table tr td:first-child { color: #888; font-weight: 600; width: 85px; text-transform: uppercase; font-size: .67rem; letter-spacing: .05em; }
        .idc-table tr td:last-child { color: #111; font-weight: 500; }
        .idc-divider { border: none; border-top: 1px solid #e8e8e8; margin: 10px 0 8px; }

        /* bottom band */
        .idc-bottom {
            background: #002060;
            padding: 8px 16px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .idc-bottom .loc { font-size: .65rem; color: rgba(255,255,255,.8); line-height: 1.5; }
        .idc-bottom .loc strong { display: block; color: #fff; font-size: .72rem; }
        .idc-status-dot {
            width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
        }
        .dot-active   { background: #4ade80; }
        .dot-inactive { background: #f87171; }

        /* barcode strip */
        .idc-barcode {
            background: #f5f5f5;
            padding: 8px 16px;
            display: flex; align-items: center; justify-content: space-between;
            border-top: 1px solid #e0e0e0;
        }
        .idc-barcode .bars { display: flex; gap: 2px; align-items: flex-end; height: 22px; }
        .idc-barcode .bars span {
            display: inline-block; width: 2px; background: #333;
        }
        .idc-barcode .uid { font-family: 'Courier New', monospace; font-size: .65rem; color: #555; letter-spacing: .1em; }

        /* print */
        @media print {
            body * { visibility: hidden !important; }
            #idCardPrintArea, #idCardPrintArea * { visibility: visible !important; }
            #idCardPrintArea {
                position: fixed; top: 10mm; left: 50%; transform: translateX(-50%);
                z-index: 9999;
            }
            .modal-header { display: none !important; }
        }
    </style>

    <?php include('includes/footer.php'); ?>

    <!-- <div id="footer-placeholder"></div> -->

    <!-- Bootstrap 5 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/layout.js"></script>
    
    <script>
        const auditorModalEl = document.getElementById('auditorModal');
        const auditorModal = bootstrap.Modal.getOrCreateInstance(auditorModalEl);
        const auditorForm = auditorModalEl.querySelector('form');
        const addAuditorBtn = document.getElementById('addAuditorBtn');
        const modalTitle = auditorModalEl.querySelector('.modal-title');
        const submitAuditorBtn = document.getElementById('submitAuditorBtn');
        const formAction = document.getElementById('formAction');
        const passwordGroup = document.getElementById('passwordGroup');

        function resetForm() {
            auditorForm.reset();
            document.getElementById('editingAuditorId').value = '';
            formAction.value = 'add_auditor';
            modalTitle.textContent = 'Add New Auditor';
            submitAuditorBtn.textContent = 'Add Auditor';
            if (passwordGroup) passwordGroup.style.display = 'block';
            document.getElementById('auditorPassword').required = true;
        }

        function editAuditor(userId) {
            // Fetch auditor data from the table row
            const row = document.querySelector('tr[data-id="' + userId + '"]');
            if (!row) return;

            const cells = row.querySelectorAll('td');
            document.getElementById('auditorName').value = cells[1].textContent.trim();
            document.getElementById('auditorEmail').value = cells[2].textContent.trim();
            document.getElementById('auditorPhone').value = cells[5].textContent.trim();
            document.getElementById('auditorDesignation').value = cells[4].textContent.trim();
            document.getElementById('auditorStatus').value = cells[6].querySelector('.badge').textContent.trim();
            
            document.getElementById('editingAuditorId').value = userId;
            formAction.value = 'edit_auditor';
            modalTitle.textContent = 'Edit Auditor';
            submitAuditorBtn.textContent = 'Update Auditor';
            if (passwordGroup) passwordGroup.style.display = 'none';
            document.getElementById('auditorPassword').required = false;
        }

        function deleteAuditor(userId) {
            if (!confirm('Are you sure you want to delete this auditor?')) {
                return;
            }

            // Create a hidden form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_auditor">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        addAuditorBtn.addEventListener('click', function() {
            resetForm();
            auditorModal.show();
        });

        /* ── ID CARD ── */
        function showIdCard(btn) {
            const d = btn.dataset;
            const uid = 'AUD-' + String(d.id).padStart(5, '0');

            // Generate random-looking barcode bars
            const barHeights = [18,10,18,14,22,8,18,12,20,10,16,22,10,18,14,8,20,16,12,18];
            const bars = barHeights.map(h =>
                `<span style="height:${h}px"></span>`
            ).join('');

            const statusDot = d.status === 'Active' ? 'dot-active' : 'dot-inactive';

            const html = `
            <div id="idCardPrintArea">
            <div class="id-card">

                <!-- top band -->
                <div class="idc-top">
                    <div class="idc-top-logo">
                     <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTMSC9E6Pi54WGmLwxPJ5lTJUDiUgMQAqhl6w&s" alt="IR Logo" width="40" height="40"> 
                    </div>
                    <div class="idc-top-text">
                        <div class="t1">Indian Railways</div>
                        <div class="t2">FDSS / FSDS — Fire Detection &amp; Suppression System</div>
                    </div>
                </div>

                <!-- body -->
                <div class="idc-body">
                    <div class="idc-photo-row">
                        <div class="idc-photo">${d.profile
                            ? `<img src="${escHtml(d.profile)}" style="width:70px;height:82px;object-fit:cover;border-radius:4px;">`
                            : '<i class="bi bi-person-fill"></i>'}</div>
                        <div class="idc-info">
                            <div class="idc-name">${escHtml(d.name)}</div>
                            <div class="idc-desig">${escHtml(d.designation || 'Auditor')}</div>
                            <span class="idc-role-badge">Auditor</span>
                        </div>
                    </div>
                    <hr class="idc-divider">
                    <table class="idc-table">
                        <tr><td>ID</td><td><strong>${uid}</strong></td></tr>
                        <tr><td>Username</td><td>${escHtml(d.username)}</td></tr>
                        <tr><td>Phone</td><td>${escHtml(d.phone || '—')}</td></tr>
                        <tr><td>Email</td><td>${escHtml(d.email || '—')}</td></tr>
                        <tr><td>Issued</td><td>${escHtml(d.issued)}</td></tr>
                    </table>
                </div>

                <!-- bottom location band -->
                <div class="idc-bottom">
                    <div class="loc">
                        <strong>${escHtml(d.zone || '—')}</strong>
                        ${escHtml(d.division || '—')} · ${escHtml(d.station || '—')}
                    </div>
                    <div class="idc-status-dot ${statusDot}" title="${escHtml(d.status)}"></div>
                </div>

                <!-- barcode strip -->
                <div class="idc-barcode">
                    <div class="bars">${bars}</div>
                    <span class="uid">${uid}</span>
                </div>

            </div>
            </div>`;

            document.getElementById('idCardFront').innerHTML = html;
        }

        function printIdCard() {
            window.print();
        }

        function escHtml(str) {
            return String(str || '')
                .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        // Close modal event - reset form
        auditorModalEl.addEventListener('hidden.bs.modal', function() {
            resetForm();
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
