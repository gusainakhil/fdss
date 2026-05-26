<?php
session_start();
require_once 'config/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$message = '';
$message_type = '';

$type_column_check = $conn->query("SHOW COLUMNS FROM fdss_train_coach LIKE 'Type'");
if ($type_column_check && $type_column_check->num_rows === 0) {
    $conn->query("ALTER TABLE fdss_train_coach ADD `Type` varchar(20) DEFAULT NULL AFTER coach_type");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_coach') {
        $train_info_id = (int) ($_POST['train_info_id'] ?? 0) ?: null;
        $coach_no = trim($_POST['coach_no'] ?? '');
        $coach_type = trim($_POST['coach_type'] ?? '');
        $type = substr(trim($_POST['type'] ?? ''), 0, 20);
        $status = $_POST['status'] ?? 'Active';
        $next_inspection_date = !empty($_POST['next_inspection_date']) ? $_POST['next_inspection_date'] : null;

        if ($coach_no === '') {
            $message = "Please fill all required fields.";
            $message_type = "danger";
        } else {
            $check_query = "SELECT coach_id FROM fdss_train_coach WHERE coach_no = ? AND user_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("si", $coach_no, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $message = "This coach already exists.";
                $message_type = "danger";
            } else {
                $insert_query = "INSERT INTO fdss_train_coach 
                    (train_info_id, coach_no, coach_type, `Type`, user_id, status, next_inspection_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("isssiss", $train_info_id, $coach_no, $coach_type, $type, $user_id, $status, $next_inspection_date);

                if ($stmt->execute()) {
                    $message = "Coach added successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error adding coach.";
                    $message_type = "danger";
                }

                $stmt->close();
            }

            $check_stmt->close();
        }
    }

    elseif ($action === 'edit_coach') {
        $coach_id = (int) ($_POST['coach_id'] ?? 0);
        $coach_no = trim($_POST['coach_no'] ?? '');
        $coach_type = trim($_POST['coach_type'] ?? '');
        $type = substr(trim($_POST['type'] ?? ''), 0, 20);
        $status = $_POST['status'] ?? 'Active';
        $next_inspection_date = !empty($_POST['next_inspection_date']) ? $_POST['next_inspection_date'] : null;

        $check_query = "SELECT coach_id FROM fdss_train_coach 
                        WHERE coach_no = ? AND user_id = ? AND coach_id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("sii", $coach_no, $user_id, $coach_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = "This coach already exists.";
            $message_type = "danger";
        } else {
            $update_query = "UPDATE fdss_train_coach 
                SET coach_no = ?, coach_type = ?, `Type` = ?, status = ?, next_inspection_date = ?
                WHERE coach_id = ? AND user_id = ?";

            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sssssii", $coach_no, $coach_type, $type, $status, $next_inspection_date, $coach_id, $user_id);

            if ($stmt->execute()) {
                $message = "Coach updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating coach: " . $stmt->error;
                $message_type = "danger";
            }

            $stmt->close();
        }

        $check_stmt->close();
    }

    elseif ($action === 'delete_coach') {
        $coach_id = (int) ($_POST['coach_id'] ?? 0);

        $delete_query = "DELETE FROM fdss_train_coach WHERE coach_id = ? AND user_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $coach_id, $user_id);

        if ($stmt->execute()) {
            $message = "Coach deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting coach. Inventory or schedule may be linked.";
            $message_type = "danger";
        }

        $stmt->close();
    }

    elseif ($action === 'assign_train') {
        $coach_id = (int) ($_POST['coach_id'] ?? 0);
        $train_info_id = (int) ($_POST['train_info_id'] ?? 0) ?: null;

        if ($coach_id > 0) {
            $assign_query = "UPDATE fdss_train_coach SET train_info_id = ? WHERE coach_id = ? AND user_id = ?";
            $stmt = $conn->prepare($assign_query);
            $stmt->bind_param("iii", $train_info_id, $coach_id, $user_id);

            if ($stmt->execute()) {
                $message = "Train assignment updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating train assignment.";
                $message_type = "danger";
            }

            $stmt->close();
        }
    }
}

$trains = [];
$train_query = "SELECT train_info_id, train_no, train_name 
                FROM fdss_train_information 
                WHERE user_id = ? AND status = 'Active'
                ORDER BY train_no ASC";

$stmt = $conn->prepare($train_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$train_result = $stmt->get_result();

while ($row = $train_result->fetch_assoc()) {
    $trains[] = $row;
}

$stmt->close();

$selected_train = $_GET['train_info_id'] ?? 'all';

$coaches = [];

$query = "SELECT 
            c.coach_id,
            c.train_info_id,
            c.coach_no,
            c.coach_type,
            c.`Type` AS coach_body_type,
            c.status,
            c.next_inspection_date,
            t.train_no,
            t.train_name,
            COUNT(ci.id) AS total_inventory,
            SUM(CASE WHEN ci.status = 'Active' THEN 1 ELSE 0 END) AS active_inventory,
            SUM(CASE WHEN ci.status = 'Expired' THEN 1 ELSE 0 END) AS expired_inventory,
            SUM(CASE 
                WHEN iu.Warranty_expire IS NOT NULL 
                AND DATE(iu.Warranty_expire) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                THEN 1 ELSE 0 
            END) AS expire_soon
          FROM fdss_train_coach c
          LEFT JOIN fdss_train_information t ON c.train_info_id = t.train_info_id
          LEFT JOIN fdss_coach_inventory ci 
            ON ci.coach_id = c.coach_id
            AND ci.user_id = c.user_id
          LEFT JOIN fdds_inventory_unit iu
            ON iu.unit_id = ci.inventory_unit_id
          WHERE c.user_id = ?";

if ($selected_train !== 'all') {
    if ($selected_train === 'Detached') {
        $query .= " AND c.train_info_id IS NULL";
    } else {
        $query .= " AND c.train_info_id = ?";
    }
}

$query .= " GROUP BY c.coach_id ORDER BY c.coach_id DESC";

$stmt = $conn->prepare($query);

if ($selected_train !== 'all' && $selected_train !== 'Detached') {
    $selected_train_id = (int) $selected_train;
    $stmt->bind_param("ii", $user_id, $selected_train_id);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $coaches[] = $row;
}

$stmt->close();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coach Management - FDSS Dashboard</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        .table-hover th,
        .table-hover td {
            text-align: center;
            vertical-align: middle;
        }
        .table-hover td form {
            margin-bottom: 0;
        }
        .print-only {
            display: none;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #coachesPrintArea,
            #coachesPrintArea * {
                visibility: visible;
            }
            #coachesPrintArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block;
            }
            .table-wrapper {
                overflow: visible !important;
            }
            table {
                width: 100% !important;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<?php include('includes/navbar.php'); ?>

<div class="sidebar-container">
    <?php include('includes/sidebar.php'); ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>Coach Management</h1>
                <p class="page-header-subtitle">Manage coaches and track components status</p>
            </div>
            <div class="page-header-actions">
                <button class="btn btn-primary" id="addCoachBtn" data-bs-toggle="modal" data-bs-target="#coachModal">
                    <i class="bi bi-plus-circle"></i> Add Coach
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo e($message_type); ?> alert-dismissible fade show" role="alert">
                <?php echo e($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label for="trainFilter" class="form-label">Select Train Number</label>
                        <select id="trainFilter" name="train_info_id" class="form-select" onchange="this.form.submit()">
                            <option value="all">All Trains</option>
                            <!-- <option value="Detached" <?php echo $selected_train === 'Detached' ? 'selected' : ''; ?>>Detached</option> -->
                            <?php foreach ($trains as $train): ?>
                                <option value="<?php echo e($train['train_info_id']); ?>" 
                                    <?php echo ((string)$selected_train === (string)$train['train_info_id']) ? 'selected' : ''; ?>>
                                    <?php echo e($train['train_no'] . ' - ' . $train['train_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="coachFilter" class="form-label">Filter Coach</label>
                        <select id="coachFilter" class="form-select">
                            <option value="">All Coaches</option>
                            <option value="Detached">Detached</option>
                            <?php foreach ($coaches as $coach): ?>
                                <option value="<?php echo e($coach['coach_no']); ?>"><?php echo e($coach['coach_no']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="rowCountSelect" class="form-label">Show Rows</label>
                        <select id="rowCountSelect" class="form-select">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="30">30</option>
                            <option value="0">All</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <h5>
                    <i class="bi bi-box-seam"></i>
                    Coaches List (<?php echo count($coaches); ?> Total)
                </h5>
                <div class="d-flex gap-2 no-print">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" id="exportExcelBtn">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                </div>
            </div>

            <div class="card-body" id="coachesPrintArea">
                <div class="print-only text-center mb-3">
                    <h3>Coaches List</h3>
                    <p>Generated on <?php echo e(date('Y-m-d')); ?></p>
                </div>
                <div class="table-wrapper">
                    <table class="table table-hover" id="coachesTable">
                        <thead>
                            <tr>
                                <th>Coach No.</th>
                                <th>Coach Type</th>
                                
                                <th>Train Assigned</th>
                                <th class="no-print no-export">Assign Train</th>
                                <th>Total Components</th>
                                <th>Active Components</th>
                                <th>Expired Components</th>
                                <th>Expire Soon</th>
                                <!-- <th>Status Detached / Intact </th> -->
                                <th>Next Inspection</th>
                                <th class="no-print no-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($coaches)): ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    No coaches found. Click "Add Coach" to create one.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($coaches as $coach): ?>
                                <tr>
                                    <td>
                                        <a 
                                            class=""
                                            href="coach-inventory.php?train_info_id=<?php echo e($coach['train_info_id']); ?>&coach_no=<?php echo urlencode($coach['coach_no']); ?>">
                                            <?php echo e($coach['coach_no']); ?>
                                           
                                        </a>
                                    </td>
                                    <td><?php echo e($coach['coach_type'] ?: '-'); ?></td>
                                  
                                    <td><?php echo e($coach['train_info_id'] ? $coach['train_no'] . ' - ' . $coach['train_name'] : 'Detached'); ?></td>
                                    <td class="no-print no-export">
                                        <form method="POST" class="d-flex gap-1">
                                            <input type="hidden" name="action" value="assign_train">
                                            <input type="hidden" name="coach_id" value="<?php echo e($coach['coach_id']); ?>">
                                            <select name="train_info_id" class="form-select form-select-sm">
                                                <option value="">Detached</option>
                                                <?php foreach ($trains as $train): ?>
                                                    <option value="<?php echo e($train['train_info_id']); ?>" <?php echo ($coach['train_info_id'] == $train['train_info_id']) ? 'selected' : ''; ?>>
                                                        <?php echo e($train['train_no'] . ' - ' . $train['train_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                        </form>
                                    </td>
                                    <td><?php echo e($coach['total_inventory'] ?? 0); ?></td>
                                    <td><span class="badge badge-success"><?php echo e($coach['active_inventory'] ?? 0); ?></span></td>
                                    <td><span class="badge badge-danger"><?php echo e($coach['expired_inventory'] ?? 0); ?></span></td>
                                    <td><span class="badge badge-warning"><?php echo e($coach['expire_soon'] ?? 0); ?></span></td>
                                    <!-- <td>
                                        <?php $status_class = ($coach['status'] === 'Active') ? 'badge-success' : 'badge-danger'; ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo e($coach['status']); ?></span>
                                    </td> -->
                                    <td>
                                        <?php echo $coach['next_inspection_date'] ? e(date('Y-m-d', strtotime($coach['next_inspection_date']))) : '-'; ?>
                                    </td>
                                    <td class="no-print no-export">
                                        <!-- <a 
                                            class="btn btn-sm btn-info"
                                            href="coach-inventory.php?train_info_id=<?php echo e($coach['train_info_id']); ?>&coach_no=<?php echo urlencode($coach['coach_no']); ?>">
                                            <i class="bi bi-box-seam"></i>
                                        </a> -->

                                        <button 
                                            class="btn btn-sm btn-outline-primary"
                                            onclick="editCoach(
                                                <?php echo e(json_encode((string) $coach['coach_id'])); ?>,
                                                <?php echo e(json_encode((string) $coach['coach_no'])); ?>,
                                                <?php echo e(json_encode((string) $coach['coach_type'])); ?>,
                                                <?php echo e(json_encode((string) $coach['coach_body_type'])); ?>,
                                                <?php echo e(json_encode((string) $coach['status'])); ?>,
                                                <?php echo e(json_encode((string) $coach['next_inspection_date'])); ?>
                                            )"
                                            data-bs-toggle="modal"
                                            data-bs-target="#coachModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <!-- <button 
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="deleteCoach('<?php echo e($coach['coach_id']); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button> -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="coachModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Coach</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="add_coach">
                <input type="hidden" name="coach_id" id="coachId" value="">

                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="form-label">Coach Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="coachNo" name="coach_no" placeholder="e.g., 2332" required>
                    </div>
                     <div class="form-group mb-3">
                        <label class="form-label">Type</label>
                        <input type="text" class="form-control" id="coachBodyType" name="type" maxlength="20" placeholder="e.g., SWLWACCN1">
                    </div>


                    <div class="form-group mb-3">
                        <label class="form-label">Coach Type</label>
                        <select class="form-select" id="coachType" name="coach_type">
                            <option value="">Select Type</option>
                            <option value="FDSS">FDSS</option>
                            <option value="FSDS">FSDS</option>
                            
                        </select>
                    </div>

                   
                    <div class="form-group mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="coachStatus" name="status" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Next Inspection Date</label>
                        <input type="date" class="form-control" id="nextInspectionDate" name="next_inspection_date">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitCoachBtn">Add Coach</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="action" value="delete_coach">
    <input type="hidden" name="coach_id" id="deleteCoachId">
</form>

<?php include('includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/layout.js"></script>

<script>
const coachModalEl = document.getElementById('coachModal');
const addCoachBtn = document.getElementById('addCoachBtn');
const modalTitle = coachModalEl.querySelector('.modal-title');
const submitCoachBtn = document.getElementById('submitCoachBtn');
const formAction = document.getElementById('formAction');

function resetCoachForm() {
    document.getElementById('coachId').value = '';
    document.getElementById('coachNo').value = '';
    document.getElementById('coachType').value = '';
    document.getElementById('coachBodyType').value = '';
    document.getElementById('coachStatus').value = 'Active';
    document.getElementById('nextInspectionDate').value = '';

    formAction.value = 'add_coach';
    modalTitle.textContent = 'Add New Coach';
    submitCoachBtn.textContent = 'Add Coach';
}

function editCoach(id, coachNo, coachType, coachBodyType, status, nextInspectionDate) {
    document.getElementById('coachId').value = id;
    document.getElementById('coachNo').value = coachNo;
    document.getElementById('coachType').value = coachType;
    document.getElementById('coachBodyType').value = coachBodyType;
    document.getElementById('coachStatus').value = status;
    document.getElementById('nextInspectionDate').value = nextInspectionDate;

    formAction.value = 'edit_coach';
    modalTitle.textContent = 'Edit Coach';
    submitCoachBtn.textContent = 'Update Coach';
}

function deleteCoach(id) {
    if (confirm('Are you sure you want to delete this coach?')) {
        document.getElementById('deleteCoachId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

addCoachBtn.addEventListener('click', resetCoachForm);

const coachFilter = document.getElementById('coachFilter');
const rowCountSelect = document.getElementById('rowCountSelect');
const exportExcelBtn = document.getElementById('exportExcelBtn');

function applyCoachFilters() {
    const filterValue = coachFilter ? coachFilter.value : '';
    const pageSize = rowCountSelect ? parseInt(rowCountSelect.value, 10) : 0;
    const rows = Array.from(document.querySelectorAll('table.table tbody tr'));
    let visibleCount = 0;

    rows.forEach(row => {
        const coachNoCell = row.querySelector('td:first-child');
        const trainAssignedCell = row.querySelector('td:nth-child(4)');
        const coachNo = coachNoCell ? coachNoCell.textContent.trim() : '';
        const trainAssigned = trainAssignedCell ? trainAssignedCell.textContent.trim() : '';
        let matches = true;

        if (filterValue === 'Detached') {
            matches = trainAssigned === 'Detached';
        } else if (filterValue) {
            matches = coachNo === filterValue;
        }

        if (matches) {
            visibleCount += 1;
            row.style.display = (pageSize > 0 && visibleCount > pageSize) ? 'none' : '';
        } else {
            row.style.display = 'none';
        }
    });
}

if (coachFilter) {
    coachFilter.addEventListener('change', applyCoachFilters);
}

if (rowCountSelect) {
    rowCountSelect.addEventListener('change', applyCoachFilters);
}

applyCoachFilters();

function downloadCoachesExcel() {
    const sourceTable = document.getElementById('coachesTable');

    if (!sourceTable) {
        return;
    }

    const exportTable = sourceTable.cloneNode(true);

    exportTable.querySelectorAll('.no-export').forEach(element => element.remove());
    Array.from(exportTable.tBodies[0].rows).forEach((row, index) => {
        const originalRow = sourceTable.tBodies[0].rows[index];

        if (originalRow && originalRow.style.display === 'none') {
            row.remove();
        }
    });

    const workbookHtml = `
        <html>
            <head>
                <meta charset="UTF-8">
            </head>
            <body>
                <h3>Coaches List</h3>
                ${exportTable.outerHTML}
            </body>
        </html>
    `;

    const blob = new Blob([workbookHtml], {
        type: 'application/vnd.ms-excel;charset=utf-8;'
    });
    const link = document.createElement('a');
    const date = new Date().toISOString().slice(0, 10);

    link.href = URL.createObjectURL(blob);
    link.download = `coaches-list-${date}.xls`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
}

if (exportExcelBtn) {
    exportExcelBtn.addEventListener('click', downloadCoachesExcel);
}

coachModalEl.addEventListener('hidden.bs.modal', resetCoachForm);
</script>

</body>
</html>

<?php
$conn->close();
?>
