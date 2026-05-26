<?php
session_start();
require_once 'config/db.php';

// // Ensure backend table exists for inventory unit details
// $createUnitTableSql = "CREATE TABLE IF NOT EXISTS fdds_inventory_unit (
//     unit_id INT AUTO_INCREMENT PRIMARY KEY,
//     inventory_id INT NOT NULL,
//     user_id INT NOT NULL,
//     serial_number VARCHAR(255) DEFAULT NULL,
//     model_number VARCHAR(255) DEFAULT NULL,
//     purchase_date DATE DEFAULT NULL,
//     warranty_expire DATE DEFAULT NULL,
//     manufacturer_id INT DEFAULT NULL,
//     notes TEXT,
//     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//     INDEX (inventory_id),
//     INDEX (user_id),
//     INDEX (manufacturer_id)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
// $conn->query($createUnitTableSql);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$inventory_id = (int) ($_GET['inventory_id'] ?? 0);
$item = null;
$manufacturers = [];
$existing_units = [];
$trains = [];
$coaches = [];
$parameter_items = [];
$message = '';
$message_type = '';
$item_category = '';
$parameter_category = '';
$unit_has_use_status = false;
$unit_has_inventory_parameter_id = false;

$use_status_check = $conn->query("SHOW COLUMNS FROM fdds_inventory_unit LIKE 'use_status'");
if ($use_status_check && $use_status_check->num_rows > 0) {
    $unit_has_use_status = true;
}

$parameter_column_check = $conn->query("SHOW COLUMNS FROM fdds_inventory_unit LIKE 'inventory_parameter_id'");
if ($parameter_column_check && $parameter_column_check->num_rows > 0) {
    $unit_has_inventory_parameter_id = true;
}

if ($inventory_id > 0) {
    $query = "SELECT inventory_id, item_code, item_name, quantity, category, status, remarks, user_id
              FROM fdss_Inventory_Management
              WHERE inventory_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $inventory_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();
}

if ($item) {
    $item_category = strtoupper(trim((string) $item['category']));
}

if ($item && in_array($item_category, ['FDSS', 'FSDS'], true)) {
    $parameter_category = $item_category === 'FDSS' ? 'FDSSPARA' : 'FSDSPARA';
    $parameter_query = "SELECT inventory_id, item_code, item_name
                        FROM fdss_Inventory_Management
                        WHERE user_id = ? AND UPPER(TRIM(category)) = ?
                        ORDER BY inventory_id ASC";
    $parameter_stmt = $conn->prepare($parameter_query);
    $parameter_stmt->bind_param('is', $user_id, $parameter_category);
    $parameter_stmt->execute();
    $parameter_result = $parameter_stmt->get_result();
    while ($parameter_row = $parameter_result->fetch_assoc()) {
        $parameter_items[] = $parameter_row;
    }
    $parameter_stmt->close();

    if (empty($parameter_items)) {
        $fallback_parameter_query = "SELECT inventory_id, item_code, item_name
                                     FROM fdss_Inventory_Management
                                     WHERE UPPER(TRIM(category)) = ?
                                     ORDER BY inventory_id ASC";
        $fallback_parameter_stmt = $conn->prepare($fallback_parameter_query);
        $fallback_parameter_stmt->bind_param('s', $parameter_category);
        $fallback_parameter_stmt->execute();
        $fallback_parameter_result = $fallback_parameter_stmt->get_result();
        while ($parameter_row = $fallback_parameter_result->fetch_assoc()) {
            $parameter_items[] = $parameter_row;
        }
        $fallback_parameter_stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_units') {
    $newSerials = $_POST['new_serial'] ?? [];
    $newModels = $_POST['new_model'] ?? [];
    $newPurchases = $_POST['new_purchase'] ?? [];
    $newWarrantyExpires = $_POST['new_warranty_expire'] ?? [];
    $newManufacturerIds = $_POST['new_manufacturer'] ?? [];
    $newCoachIds = $_POST['new_coach_id'] ?? [];
    $newParameterIds = $_POST['new_parameter_id'] ?? [];
    $newParameterParentRows = $_POST['new_parameter_parent_row'] ?? [];
    $newParameterSerials = $_POST['new_parameter_serial'] ?? [];
    $newParameterModels = $_POST['new_parameter_model'] ?? [];
    $newParameterPurchases = $_POST['new_parameter_purchase'] ?? [];
    $newParameterWarrantyExpires = $_POST['new_parameter_warranty_expire'] ?? [];
    $newParameterManufacturerIds = $_POST['new_parameter_manufacturer'] ?? [];
    $newParameterCoachIds = $_POST['new_parameter_coach_id'] ?? [];

    if ($inventory_id > 0) {
        $conn->begin_transaction();
        try {
            if (!$unit_has_inventory_parameter_id && !empty($parameter_items)) {
                throw new Exception('inventory_parameter_id column was not found in fdds_inventory_unit.');
            }

            if ($unit_has_inventory_parameter_id && $unit_has_use_status) {
                $insertQuery = "INSERT INTO fdds_inventory_unit
                    (inventory_id, user_id, inventory_parameter_id, serial_number, model_number, purchase_date, warranty_expire, manufacturer_id, notes, use_status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            } elseif ($unit_has_inventory_parameter_id) {
                $insertQuery = "INSERT INTO fdds_inventory_unit
                    (inventory_id, user_id, inventory_parameter_id, serial_number, model_number, purchase_date, warranty_expire, manufacturer_id, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            } elseif ($unit_has_use_status) {
                $insertQuery = "INSERT INTO fdds_inventory_unit
                    (inventory_id, user_id, serial_number, model_number, purchase_date, warranty_expire, manufacturer_id, notes, use_status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            } else {
                $insertQuery = "INSERT INTO fdds_inventory_unit
                    (inventory_id, user_id, serial_number, model_number, purchase_date, warranty_expire, manufacturer_id, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            }
            $insertStmt = $conn->prepare($insertQuery);
            $coachCheckStmt = $conn->prepare("SELECT coach_id FROM fdss_train_coach WHERE coach_id = ? AND user_id = ? LIMIT 1");
            $assignStmt = $conn->prepare("INSERT INTO fdss_coach_inventory (coach_id, inventory_unit_id, user_id, status) VALUES (?, ?, ?, 'Active')");

            $newRowCount = 0;
            $newParameterRowCount = 0;
            $assignedRowCount = 0;
            $validParentRows = [];
            $parentUnitIds = [];
            $validParameterIds = array_flip(array_map('intval', array_column($parameter_items, 'inventory_id')));
            for ($i = 0; $i < count($newSerials); $i++) {
                $serial = trim($newSerials[$i] ?? '');
                $model = trim($newModels[$i] ?? '');
                $purchase = trim($newPurchases[$i] ?? '');
                $warrantyExpire = trim($newWarrantyExpires[$i] ?? '');
                $manufacturerId = (int) ($newManufacturerIds[$i] ?? 0) ?: null;
                $note = '';
                $coachId = (int) ($newCoachIds[$i] ?? 0);
                $useStatus = $coachId > 0 ? 1 : 0;

                // Only insert if at least one field is filled
                if (!empty($serial) || !empty($model) || !empty($purchase) || !empty($warrantyExpire) || !empty($manufacturerId) || $coachId > 0) {
                    if ($coachId > 0) {
                        $coachCheckStmt->bind_param('ii', $coachId, $user_id);
                        $coachCheckStmt->execute();
                        $coachCheckResult = $coachCheckStmt->get_result();

                        if ($coachCheckResult->num_rows === 0) {
                            throw new Exception('Selected coach was not found.');
                        }

                        $coachCheckResult->free();
                    }

                    $inventoryParameterId = null;
                    if ($unit_has_inventory_parameter_id && $unit_has_use_status) {
                        $insertStmt->bind_param('iiissssisi', $inventory_id, $user_id, $inventoryParameterId, $serial, $model, $purchase, $warrantyExpire, $manufacturerId, $note, $useStatus);
                    } elseif ($unit_has_inventory_parameter_id) {
                        $insertStmt->bind_param('iiissssis', $inventory_id, $user_id, $inventoryParameterId, $serial, $model, $purchase, $warrantyExpire, $manufacturerId, $note);
                    } elseif ($unit_has_use_status) {
                        $insertStmt->bind_param('iissssisi', $inventory_id, $user_id, $serial, $model, $purchase, $warrantyExpire, $manufacturerId, $note, $useStatus);
                    } else {
                        $insertStmt->bind_param('iissssis', $inventory_id, $user_id, $serial, $model, $purchase, $warrantyExpire, $manufacturerId, $note);
                    }
                    if (!$insertStmt->execute()) {
                        throw new Exception($insertStmt->error ?: 'Unable to save unit.');
                    }
                    $newUnitId = (int) $conn->insert_id;
                    $validParentRows[$i] = true;
                    $parentUnitIds[$i] = $newUnitId;

                    if ($coachId > 0) {
                        $assignStmt->bind_param('iii', $coachId, $newUnitId, $user_id);
                        if (!$assignStmt->execute()) {
                            throw new Exception($assignStmt->error ?: 'Unable to assign unit to coach.');
                        }
                        $assignedRowCount++;
                    }

                    $newRowCount++;
                }
            }

            if ($unit_has_inventory_parameter_id) {
                for ($i = 0; $i < count($newParameterIds); $i++) {
                    $parameterId = (int) ($newParameterIds[$i] ?? 0);
                    $parentRowIndex = (int) ($newParameterParentRows[$i] ?? -1);
                    $serial = trim($newParameterSerials[$i] ?? '');
                    $model = trim($newParameterModels[$i] ?? '');
                    $purchase = trim($newParameterPurchases[$i] ?? '');
                    $warrantyExpire = trim($newParameterWarrantyExpires[$i] ?? '');
                    $manufacturerId = (int) ($newParameterManufacturerIds[$i] ?? 0) ?: null;
                    $note = '';
                    $coachId = (int) ($newParameterCoachIds[$i] ?? 0);
                    $useStatus = $coachId > 0 ? 1 : 0;

                    if ($parameterId <= 0 || !isset($validParameterIds[$parameterId]) || empty($validParentRows[$parentRowIndex]) || empty($parentUnitIds[$parentRowIndex])) {
                        continue;
                    }

                    $parentUnitId = $parentUnitIds[$parentRowIndex];

                    if ($coachId > 0) {
                        $coachCheckStmt->bind_param('ii', $coachId, $user_id);
                        $coachCheckStmt->execute();
                        $coachCheckResult = $coachCheckStmt->get_result();

                        if ($coachCheckResult->num_rows === 0) {
                            throw new Exception('Selected coach was not found.');
                        }

                        $coachCheckResult->free();
                    }

                    if ($unit_has_use_status) {
                        $insertStmt->bind_param('iiissssisi', $parameterId, $user_id, $parentUnitId, $serial, $model, $purchase, $warrantyExpire, $manufacturerId, $note, $useStatus);
                    } else {
                        $insertStmt->bind_param('iiissssis', $parameterId, $user_id, $parentUnitId, $serial, $model, $purchase, $warrantyExpire, $manufacturerId, $note);
                    }

                    if (!$insertStmt->execute()) {
                        throw new Exception($insertStmt->error ?: 'Unable to save parameter unit.');
                    }

                    $newUnitId = (int) $conn->insert_id;

                    if ($coachId > 0) {
                        $assignStmt->bind_param('iii', $coachId, $newUnitId, $user_id);
                        if (!$assignStmt->execute()) {
                            throw new Exception($assignStmt->error ?: 'Unable to assign parameter unit to coach.');
                        }
                        $assignedRowCount++;
                    }

                    $newParameterRowCount++;
                }
            }
            $assignStmt->close();
            $coachCheckStmt->close();
            $insertStmt->close();

            // Update total quantity
            $parameter_filter = $unit_has_inventory_parameter_id ? " AND inventory_parameter_id IS NULL" : "";
            $getTotalQuery = "SELECT COUNT(*) as total FROM fdds_inventory_unit WHERE inventory_id = ? AND user_id = ?" . $parameter_filter;
            $getTotalStmt = $conn->prepare($getTotalQuery);
            $getTotalStmt->bind_param('ii', $inventory_id, $user_id);
            $getTotalStmt->execute();
            $totalResult = $getTotalStmt->get_result()->fetch_assoc();
            $totalCount = $totalResult['total'];
            $getTotalStmt->close();

            $updateInventoryQuery = "UPDATE fdss_Inventory_Management SET quantity = ? WHERE inventory_id = ? AND user_id = ?";
            $updateInventoryStmt = $conn->prepare($updateInventoryQuery);
            $updateInventoryStmt->bind_param('iii', $totalCount, $inventory_id, $user_id);
            $updateInventoryStmt->execute();
            $updateInventoryStmt->close();

            $conn->commit();
            $message = "Added {$newRowCount} new unit(s) and {$newParameterRowCount} parameter unit(s) successfully. {$assignedRowCount} assigned to coach. Total count: {$totalCount}.";
            $message_type = 'success';
            
            // Reload existing units
            $existing_units = [];
            if ($item) {
                $parameter_filter = $unit_has_inventory_parameter_id ? " AND inventory_parameter_id IS NULL" : "";
                $unit_query = "SELECT unit_id, serial_number, model_number, purchase_date, warranty_expire, manufacturer_id, notes
                               FROM fdds_inventory_unit
                               WHERE inventory_id = ? AND user_id = ?
                               $parameter_filter
                               ORDER BY unit_id ASC";
                $unit_stmt = $conn->prepare($unit_query);
                $unit_stmt->bind_param('ii', $inventory_id, $user_id);
                $unit_stmt->execute();
                $unit_result = $unit_stmt->get_result();
                while ($unit_row = $unit_result->fetch_assoc()) {
                    $existing_units[] = $unit_row;
                }
                $unit_stmt->close();
            }
            $item['quantity'] = $totalCount;
            
            // Redirect to clear POST data and prevent duplicate submission
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error saving units: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_unit') {
    $unitId = (int) ($_POST['unit_id'] ?? 0);
    $serial = trim($_POST['edit_serial'] ?? '');
    $model = trim($_POST['edit_model'] ?? '');
    $purchase = trim($_POST['edit_purchase'] ?? '');
    $warrantyExpire = trim($_POST['edit_warranty_expire'] ?? '');
    $manufacturerId = (int) ($_POST['edit_manufacturer'] ?? 0) ?: null;
    $note = '';

    if ($unitId > 0 && $inventory_id > 0) {
        $updateQuery = "UPDATE fdds_inventory_unit 
                        SET serial_number = ?, model_number = ?, purchase_date = ?, warranty_expire = ?, manufacturer_id = ?, notes = ?
                        WHERE unit_id = ? AND inventory_id = ? AND user_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('ssssisiii', $serial, $model, $purchase, $warrantyExpire, $manufacturerId, $note, $unitId, $inventory_id, $user_id);
        
        if ($updateStmt->execute()) {
            $message = "Unit updated successfully.";
            $message_type = 'success';
            
            // Reload existing units
            $existing_units = [];
            if ($item) {
                $parameter_filter = $unit_has_inventory_parameter_id ? " AND inventory_parameter_id IS NULL" : "";
                $unit_query = "SELECT unit_id, serial_number, model_number, purchase_date, warranty_expire, manufacturer_id, notes
                               FROM fdds_inventory_unit
                               WHERE inventory_id = ? AND user_id = ?
                               $parameter_filter
                               ORDER BY unit_id ASC";
                $unit_stmt = $conn->prepare($unit_query);
                $unit_stmt->bind_param('ii', $inventory_id, $user_id);
                $unit_stmt->execute();
                $unit_result = $unit_stmt->get_result();
                while ($unit_row = $unit_result->fetch_assoc()) {
                    $existing_units[] = $unit_row;
                }
                $unit_stmt->close();
            }
            
            // Redirect to clear POST data and prevent duplicate submission
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $message = "Error updating unit: " . $updateStmt->error;
            $message_type = 'danger';
        }
        $updateStmt->close();
    }
}

if ($item) {
    $use_status_select = $unit_has_use_status ? 'iu.use_status' : '0 AS use_status';
    $unit_query = "SELECT
                       iu.unit_id,
                       iu.serial_number,
                       iu.model_number,
                       iu.purchase_date,
                       iu.warranty_expire,
                       iu.manufacturer_id,
                       iu.notes,
                       $use_status_select,
                       ci.coach_id,
                       c.coach_no,
                       t.train_no,
                       t.train_name
                   FROM fdds_inventory_unit iu
                   LEFT JOIN fdss_coach_inventory ci
                       ON ci.inventory_unit_id = iu.unit_id
                       AND ci.user_id = iu.user_id
                   LEFT JOIN fdss_train_coach c
                       ON c.coach_id = ci.coach_id
                       AND c.user_id = iu.user_id
                   LEFT JOIN fdss_train_information t
                       ON t.train_info_id = c.train_info_id
                       AND t.user_id = iu.user_id
                   WHERE iu.inventory_id = ? AND iu.user_id = ?
                   " . ($unit_has_inventory_parameter_id ? "AND iu.inventory_parameter_id IS NULL" : "") . "
                   ORDER BY (ci.coach_id IS NULL) DESC, iu.unit_id ASC";
    $unit_stmt = $conn->prepare($unit_query);
    $unit_stmt->bind_param('ii', $inventory_id, $user_id);
    $unit_stmt->execute();
    $unit_result = $unit_stmt->get_result();
    while ($unit_row = $unit_result->fetch_assoc()) {
        $existing_units[] = $unit_row;
    }
    $unit_stmt->close();
}

$train_query = "SELECT train_info_id, train_no, train_name
                FROM fdss_train_information
                WHERE user_id = ? AND status = 'Active'
                ORDER BY train_no ASC";
$stmt = $conn->prepare($train_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $trains[] = $row;
}
$stmt->close();

$coach_query = "SELECT coach_id, coach_no, coach_type, train_info_id
                FROM fdss_train_coach
                WHERE user_id = ? AND status = 'Active'
                ORDER BY coach_no ASC";
$stmt = $conn->prepare($coach_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $coaches[] = $row;
}
$stmt->close();

$manufacturer_query = "SELECT manufacturer_id, company_name, name FROM fdss_manufacturers WHERE user_id = ? ORDER BY company_name";
$stmt = $conn->prepare($manufacturer_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $manufacturers[] = $row;
}
$stmt->close();

if (!$item) {
    header('Location: inventory.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Unit Details - FDSS Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        .unit-table input,
        .unit-table select {
            min-width: 150px;
        }
        .unit-table th {
            white-space: nowrap;
        }
        .parameter-unit-table input,
        .parameter-unit-table select {
            min-width: 150px;
        }
        .parameter-unit-table th {
            white-space: nowrap;
        }
        .unit-row-count {
            width: 42px;
            min-width: 42px;
            max-width: 42px;
            text-align: center;
        }
        .print-only {
            display: none;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #existingUnitsPrintArea,
            #existingUnitsPrintArea * {
                visibility: visible;
            }
            #existingUnitsPrintArea {
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
            .table-responsive {
                overflow: visible !important;
            }
            table {
                width: 100% !important;
                font-size: 11px;
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
                <h1>Inventory Unit Details</h1>
                <!-- <p class="page-header-subtitle">UI view for individual unit entries. Data is not saved to a separate backend table.</p> -->
            </div>
            <div class="page-header-actions">
                <a href="inventory.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Inventory
                </a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo e($message_type ?: 'info'); ?> alert-dismissible fade show" role="alert">
                <?php echo e($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- <div class="alert alert-warning">
            <strong>Note:</strong> This page is a UI-only view for serial/model entry, purchase date, and manufacturer selection. It does not save this data to a separate database table.
        </div> -->

        <div class="content-card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-box-seam"></i> Item Summary</h5>
            </div>
            <div class="card-body">
                <div class="row gy-3">
                    <div class="col-md-4">
                        <strong>Item Code</strong>
                        <p><?php echo e($item['item_code']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <strong>Inventory Name</strong>
                        <p><?php echo e($item['item_name']); ?></p>
                    </div>
                    <div class="col-md-2">
                        <strong>Quantity</strong>
                        <p><?php echo e($item['quantity']); ?></p>
                    </div>
                    <div class="col-md-2">
                        <strong>Category</strong>
                        <p><?php echo e($item['category']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-plus-circle"></i> Add New Units</h5>
                <div>
                    <button class="btn btn-sm btn-secondary me-2" id="resetUnitsHeaderBtn">
                        <i class="bi bi-arrow-clockwise"></i> Reset Values
                    </button>
                    <button class="btn btn-sm btn-primary" id="scrollTopBtn">
                        <i class="bi bi-arrow-up"></i> Top
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" id="unitCountForm">
                    <input type="hidden" name="action" value="save_units">

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover unit-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="unit-row-count">Unit #</th>
                                    <th>Serial Number</th>
                                    <th>Model Number</th>
                                    <th>Purchase Date</th>
                                    <th>Warranty Expire</th>
                                    <th>Train</th>
                                    <th>Coach</th>
                                    <th>OEM</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="unitEntriesBody"></tbody>
                        </table>
                    </div>

                    <?php
                        $inline_category = strtoupper(trim((string) ($item['category'] ?? '')));
                        $inline_parameter_category = $inline_category === 'FDSS' ? 'FDSSPARA' : ($inline_category === 'FSDS' ? 'FSDSPARA' : '');
                        $inline_parameter_items = $parameter_items;

                        if ($inline_parameter_category !== '' && empty($inline_parameter_items)) {
                            $inline_parameter_query = "SELECT inventory_id, item_code, item_name
                                                       FROM fdss_Inventory_Management
                                                       WHERE UPPER(TRIM(category)) = ?
                                                       ORDER BY inventory_id ASC";
                            $inline_parameter_stmt = $conn->prepare($inline_parameter_query);
                            $inline_parameter_stmt->bind_param('s', $inline_parameter_category);
                            $inline_parameter_stmt->execute();
                            $inline_parameter_result = $inline_parameter_stmt->get_result();
                            while ($inline_parameter_row = $inline_parameter_result->fetch_assoc()) {
                                $inline_parameter_items[] = $inline_parameter_row;
                            }
                            $inline_parameter_stmt->close();
                        }
                    ?>
                    <?php if ($inline_parameter_category !== ''): ?>
                        <div class="mt-4" id="parameterUnitSection">
                            <h6 class="mb-3">
                                <i class="bi bi-list-check"></i>
                                <?php echo e($inline_parameter_category); ?> Parameters
                            </h6>

                            <?php if (empty($inline_parameter_items)): ?>
                                <div class="alert alert-warning mb-0">
                                    No <?php echo e($inline_parameter_category); ?> parameters found. Add parameters from Add Parameter page first.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover parameter-unit-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="unit-row-count">Unit #</th>
                                                <th>Parameter</th>
                                                <th>Serial Number</th>
                                                <th>Model Number</th>
                                                <th>Purchase Date</th>
                                                <th>Warranty Expire</th>
                                                <th>Train</th>
                                                <th>Coach</th>
                                                <th>OEM</th>
                                            </tr>
                                        </thead>
                                        <tbody id="parameterEntriesBody">
                                            <?php foreach ($inline_parameter_items as $inline_parameter): ?>
                                                <tr>
                                                    <td class="align-middle">1</td>
                                                    <td class="align-middle">
                                                        <strong><?php echo e($inline_parameter['item_name']); ?></strong>
                                                        <div class="small text-muted"><?php echo e($inline_parameter['item_code']); ?></div>
                                                    </td>
                                                    <td colspan="7" class="text-muted">Fill Add New Units row to auto-fill this parameter.</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php /*
                    <?php if (in_array($item_category, ['FDSS', 'FSDS'], true)): ?>
                    <div class="mt-4" id="parameterUnitSection">
                        <h6 class="mb-3">
                            <i class="bi bi-list-check"></i>
                            <?php echo e($parameter_category); ?> Parameters
                        </h6>
                        <?php if (empty($parameter_items)): ?>
                            <div class="alert alert-warning mb-0">
                                No <?php echo e($parameter_category); ?> parameters found. Add parameters from Add Parameter page first.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover parameter-unit-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="unit-row-count">Unit #</th>
                                            <th>Parameter</th>
                                            <th>Serial Number</th>
                                            <th>Model Number</th>
                                            <th>Purchase Date</th>
                                            <th>Warranty Expire</th>
                                            <th>Train</th>
                                            <th>Coach</th>
                                            <th>OEM</th>
                                        </tr>
                                    </thead>
                                    <tbody id="parameterEntriesBody">
                                        <?php foreach ($parameter_items as $parameter): ?>
                                            <tr>
                                                <td class="align-middle">1</td>
                                                <td class="align-middle">
                                                    <strong><?php echo e($parameter['item_name']); ?></strong>
                                                    <div class="small text-muted"><?php echo e($parameter['item_code']); ?></div>
                                                </td>
                                                <td colspan="7" class="text-muted">Fill Add New Units row to auto-fill this parameter.</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    */ ?>

                    <div class="mt-3 d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary me-2" id="addRowBtn">
                                <i class="bi bi-plus-circle"></i> Add Row
                            </button>
                            <button type="submit" class="btn btn-sm btn-primary" id="submitCountBtn">
                                <i class="bi bi-save"></i> Save New Units
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <h5><i class="bi bi-card-checklist"></i> Existing Units</h5>
                <div class="d-flex gap-2 no-print">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="printExistingUnitsBtn">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" id="exportExistingUnitsBtn">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                </div>
            </div>
            <div class="card-body" id="existingUnitsPrintArea">
                <div class="print-only text-center mb-3">
                    <h3>Existing Units</h3>
                    <p><?php echo e($item['item_code'] . ' - ' . $item['item_name']); ?></p>
                </div>
                <?php if (!empty($existing_units)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm unit-table" id="existingUnitsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="unit-row-count">Unit #</th>
                                    <th>Serial Number</th>
                                    <th>Model Number</th>
                                    <th>Purchase Date</th>
                                    <th>Warranty Expire</th>
                                    <th>Train</th>
                                    <th>Coach</th>
                                    <th>OEM</th>
                                    <th>Status</th>
                                    <th class="text-center no-print no-export">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($existing_units as $idx => $unit): ?>
                                    <?php
                                        $is_used = !empty($unit['coach_no']) || (isset($unit['use_status']) && (int) $unit['use_status'] === 1);
                                    ?>
                                    <tr>
                                        <td class="align-middle"><?php echo $idx + 1; ?></td>
                                        <td><?php echo e($unit['serial_number']); ?></td>
                                        <td><?php echo e($unit['model_number']); ?></td>
                                        <td><?php echo e($unit['purchase_date']); ?></td>
                                        <td><?php echo e($unit['warranty_expire']); ?></td>
                                        <td>
                                            <?php
                                                if (!empty($unit['coach_id'])) {
                                                    echo !empty($unit['train_no'])
                                                        ? e($unit['train_no'] . ' - ' . $unit['train_name'])
                                                        : 'Detached';
                                                } else {
                                                    echo '-';
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo !empty($unit['coach_no']) ? e($unit['coach_no']) : '-'; ?></td>
                                        <td>
                                            <?php 
                                                if (!empty($unit['manufacturer_id'])) {
                                                    $mfg = array_filter($manufacturers, fn($m) => $m['manufacturer_id'] == $unit['manufacturer_id']);
                                                    if (!empty($mfg)) {
                                                        $m = reset($mfg);
                                                        echo e(trim($m['company_name'] . ($m['name'] ? ' - ' . $m['name'] : '')));
                                                    }
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($is_used): ?>
                                                <span class="badge bg-secondary">Used</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">In_inventory</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle no-print no-export">
                                            <button type="button" class="btn btn-sm btn-outline-primary editUnitBtn" 
                                                    data-unit-id="<?php echo e($unit['unit_id']); ?>"
                                                    data-serial="<?php echo e($unit['serial_number']); ?>"
                                                    data-model="<?php echo e($unit['model_number']); ?>"
                                                    data-purchase="<?php echo e($unit['purchase_date']); ?>"
                                                    data-warranty="<?php echo e($unit['warranty_expire']); ?>"
                                                    data-manufacturer="<?php echo e($unit['manufacturer_id']); ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- <div class="mt-3 d-flex justify-content-start gap-2">
                        <button type="button" class="btn btn-sm btn-secondary" id="resetUnitsBtn">
                            <i class="bi bi-arrow-clockwise"></i> Reset Values
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="downloadCsvBtn">
                            <i class="bi bi-download"></i> Download CSV
                        </button>
                    </div> -->
                <?php else: ?>
                    <p class="text-muted">No units added yet.</p>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<!-- Edit Unit Modal -->
<div class="modal fade" id="editUnitModal" tabindex="-1" aria-labelledby="editUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUnitModalLabel">Edit Unit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editUnitForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_unit">
                    <input type="hidden" name="unit_id" id="editUnitId" value="">
                    
                    <div class="mb-3">
                        <label for="editSerial" class="form-label">Serial Number</label>
                        <input type="text" class="form-control" id="editSerial" name="edit_serial" placeholder="Serial #">
                    </div>
                    
                    <div class="mb-3">
                        <label for="editModel" class="form-label">Model Number</label>
                        <input type="text" class="form-control" id="editModel" name="edit_model" placeholder="Model #">
                    </div>
                    
                    <div class="mb-3">
                        <label for="editPurchase" class="form-label">Purchase Date</label>
                        <input type="date" class="form-control" id="editPurchase" name="edit_purchase">
                    </div>

                    <div class="mb-3">
                        <label for="editWarrantyExpire" class="form-label">Warranty Expire</label>
                        <input type="date" class="form-control" id="editWarrantyExpire" name="edit_warranty_expire">
                    </div>
                    
                    <div class="mb-3">
                        <label for="editManufacturer" class="form-label">OEM</label>
                        <select class="form-select" id="editManufacturer" name="edit_manufacturer">
                            <option value="">Select OEM</option>
                            <?php foreach ($manufacturers as $m): ?>
                                <option value="<?php echo e($m['manufacturer_id']); ?>">
                                    <?php echo e(trim($m['company_name'] . ($m['name'] ? ' - ' . $m['name'] : ''))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/layout.js"></script>
<script>
    const manufacturers = <?php echo json_encode(array_map(function ($m) {
        return [
            'id' => $m['manufacturer_id'],
            'label' => trim($m['company_name'] . ($m['name'] ? ' - ' . $m['name'] : '')),
        ];
    }, $manufacturers), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const trains = <?php echo json_encode(array_map(function ($train) {
        return [
            'id' => (string) $train['train_info_id'],
            'label' => trim($train['train_no'] . ' - ' . $train['train_name']),
        ];
    }, $trains), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const coaches = <?php echo json_encode(array_map(function ($coach) {
        return [
            'id' => (string) $coach['coach_id'],
            'train_info_id' => $coach['train_info_id'] === null ? '' : (string) $coach['train_info_id'],
            'label' => trim($coach['coach_no'] . ($coach['coach_type'] ? ' - ' . $coach['coach_type'] : '')),
        ];
    }, $coaches), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const parameterItems = <?php echo json_encode(array_map(function ($parameter) {
        return [
            'id' => (string) $parameter['inventory_id'],
            'code' => $parameter['item_code'],
            'name' => $parameter['item_name'],
        ];
    }, $parameter_items), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    const unitEntriesBody = document.getElementById('unitEntriesBody');
    const parameterEntriesBody = document.getElementById('parameterEntriesBody');
    const parameterUnitSection = document.getElementById('parameterUnitSection');
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    const addRowBtn = document.getElementById('addRowBtn');
    const submitCountBtn = document.getElementById('submitCountBtn');
    const unitCountForm = document.getElementById('unitCountForm');
    const resetUnitsBtn = document.getElementById('resetUnitsBtn');
    const downloadCsvBtn = document.getElementById('downloadCsvBtn');
    const printExistingUnitsBtn = document.getElementById('printExistingUnitsBtn');
    const exportExistingUnitsBtn = document.getElementById('exportExistingUnitsBtn');

    function createManufacturerOptions() {
        if (manufacturers.length === 0) {
            return '<option value="">Select manufacturer</option>' +
                '<option value="M1">Manufacturer A</option>' +
                '<option value="M2">Manufacturer B</option>' +
                '<option value="M3">Manufacturer C</option>';
        }

        return manufacturers.reduce((html, manufacturer) => {
            return html + `<option value="${escapeHtml(manufacturer.id)}">${escapeHtml(manufacturer.label)}</option>`;
        }, '<option value="">Select manufacturer</option>');
    }

    function createTrainOptions() {
        return trains.reduce((html, train) => {
            return html + `<option value="${escapeHtml(train.id)}">${escapeHtml(train.label)}</option>`;
        }, '<option value="">No assignment</option><option value="Detached">Detached</option>');
    }

    function getCoachesForTrain(trainValue) {
        if (!trainValue) {
            return [];
        }

        return coaches.filter(coach => {
            if (trainValue === 'Detached') {
                return coach.train_info_id === '';
            }

            return coach.train_info_id === trainValue;
        });
    }

    function setCoachOptions(row, selectedCoachId = '') {
        const trainSelect = row.querySelector('[name="new_train_info_id[]"]');
        const coachSelect = row.querySelector('[name="new_coach_id[]"]');
        const trainValue = trainSelect ? trainSelect.value : '';
        const matchingCoaches = getCoachesForTrain(trainValue);

        coachSelect.innerHTML = matchingCoaches.reduce((html, coach) => {
            return html + `<option value="${escapeHtml(coach.id)}">${escapeHtml(coach.label)}</option>`;
        }, '<option value="">No coach</option>');

        coachSelect.disabled = false;

        if (selectedCoachId) {
            coachSelect.value = selectedCoachId;
        }
    }

    function setParameterCoachOptions(row, selectedCoachId = '') {
        const trainSelect = row.querySelector('.parameter-train-select');
        const coachSelect = row.querySelector('[name="new_parameter_coach_id[]"]');
        const trainValue = trainSelect ? trainSelect.value : '';
        const matchingCoaches = getCoachesForTrain(trainValue);

        coachSelect.innerHTML = matchingCoaches.reduce((html, coach) => {
            return html + `<option value="${escapeHtml(coach.id)}">${escapeHtml(coach.label)}</option>`;
        }, '<option value="">No coach</option>');

        coachSelect.disabled = false;

        if (selectedCoachId) {
            coachSelect.value = selectedCoachId;
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function createRow(index, optionsHtml, data = {}) {
        const serialValue = escapeHtml(data.serial_number || '');
        const modelValue = escapeHtml(data.model_number || '');
        const purchaseValue = escapeHtml(data.purchase_date || '');
        const warrantyValue = escapeHtml(data.warranty_expire || '');
        const manufacturerValue = escapeHtml(data.manufacturer_id || '');
        const trainOptionsHtml = createTrainOptions();

        const row = document.createElement('tr');
        row.dataset.rowIndex = String(index - 1);
        row.innerHTML = `
            <td class="align-middle">${index}</td>
            <td><input type="text" class="form-control form-control-sm unit-serial-input" name="new_serial[]" placeholder="Serial #" value="${serialValue}"></td>
            <td><input type="text" class="form-control form-control-sm unit-model-input" name="new_model[]" placeholder="Model #" value="${modelValue}"></td>
            <td><input type="date" class="form-control form-control-sm unit-purchase-input" name="new_purchase[]" value="${purchaseValue}"></td>
            <td><input type="date" class="form-control form-control-sm unit-warranty-input" name="new_warranty_expire[]" value="${warrantyValue}"></td>
            <td>
                <select class="form-select form-select-sm unit-train-select" name="new_train_info_id[]">
                    ${trainOptionsHtml}
                </select>
            </td>
            <td>
                <select class="form-select form-select-sm unit-coach-select" name="new_coach_id[]">
                    <option value="">No coach</option>
                </select>
            </td>
            <td>
                <select class="form-select form-select-sm unit-manufacturer-select" name="new_manufacturer[]">
                    ${optionsHtml}
                </select>
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">
                    <i class="bi bi-dash-circle"></i>
                </button>
            </td>
        `;
        const select = row.querySelector('[name="new_manufacturer[]"]');
        if (select && manufacturerValue) {
            select.value = manufacturerValue;
        }
        setCoachOptions(row);
        return row;
    }

    function createParameterRow(parentIndex, displayIndex, parameter, parentRow) {
        const optionsHtml = createManufacturerOptions();
        const trainOptionsHtml = createTrainOptions();
        const row = document.createElement('tr');
        row.dataset.parentIndex = String(parentIndex);
        row.innerHTML = `
            <td class="align-middle">${displayIndex}</td>
            <td class="align-middle">
                <strong>${escapeHtml(parameter.name)}</strong>
                <div class="small text-muted">${escapeHtml(parameter.code)}</div>
                <input type="hidden" name="new_parameter_parent_row[]" value="${escapeHtml(parentIndex)}">
                <input type="hidden" name="new_parameter_id[]" value="${escapeHtml(parameter.id)}">
            </td>
            <td><input type="text" class="form-control form-control-sm parameter-serial-input" name="new_parameter_serial[]" placeholder="Serial #"></td>
            <td><input type="text" class="form-control form-control-sm parameter-model-input" name="new_parameter_model[]" placeholder="Model #"></td>
            <td><input type="date" class="form-control form-control-sm parameter-purchase-input" name="new_parameter_purchase[]"></td>
            <td><input type="date" class="form-control form-control-sm parameter-warranty-input" name="new_parameter_warranty_expire[]"></td>
            <td>
                <select class="form-select form-select-sm parameter-train-select" name="new_parameter_train_info_id[]">
                    ${trainOptionsHtml}
                </select>
            </td>
            <td>
                <select class="form-select form-select-sm parameter-coach-select" name="new_parameter_coach_id[]">
                    <option value="">No coach</option>
                </select>
            </td>
            <td>
                <select class="form-select form-select-sm parameter-manufacturer-select" name="new_parameter_manufacturer[]">
                    ${optionsHtml}
                </select>
            </td>
        `;
        row.querySelector('.parameter-train-select')?.addEventListener('change', () => {
            setParameterCoachOptions(row);
        });
        syncParameterRow(row, parentRow);
        return row;
    }

    function getParentRowValues(parentRow) {
        return {
            serial: parentRow.querySelector('.unit-serial-input')?.value || '',
            model: parentRow.querySelector('.unit-model-input')?.value || '',
            purchase: parentRow.querySelector('.unit-purchase-input')?.value || '',
            warranty: parentRow.querySelector('.unit-warranty-input')?.value || '',
            train: parentRow.querySelector('.unit-train-select')?.value || '',
            coach: parentRow.querySelector('.unit-coach-select')?.value || '',
            manufacturer: parentRow.querySelector('.unit-manufacturer-select')?.value || '',
        };
    }

    function syncParameterRow(parameterRow, parentRow) {
        const values = getParentRowValues(parentRow);

        parameterRow.querySelector('.parameter-serial-input').value = values.serial;
        parameterRow.querySelector('.parameter-model-input').value = values.model;
        parameterRow.querySelector('.parameter-purchase-input').value = values.purchase;
        parameterRow.querySelector('.parameter-warranty-input').value = values.warranty;
        parameterRow.querySelector('.parameter-train-select').value = values.train;
        parameterRow.querySelector('.parameter-manufacturer-select').value = values.manufacturer;
        setParameterCoachOptions(parameterRow, values.coach);
    }

    function syncParameterRowsForParent(parentIndex) {
        if (!parameterEntriesBody || parameterItems.length === 0) {
            return;
        }

        const parentRow = unitEntriesBody.querySelector(`tr[data-row-index="${parentIndex}"]`);
        if (!parentRow) {
            return;
        }

        parameterEntriesBody.querySelectorAll(`tr[data-parent-index="${parentIndex}"]`).forEach(row => {
            syncParameterRow(row, parentRow);
        });
    }

    function rebuildParameterRows() {
        if (!parameterEntriesBody || parameterItems.length === 0) {
            if (parameterUnitSection) {
                parameterUnitSection.style.display = 'none';
            }
            return;
        }

        if (parameterUnitSection) {
            parameterUnitSection.style.display = '';
        }

        parameterEntriesBody.innerHTML = '';
        unitEntriesBody.querySelectorAll('tr').forEach((parentRow, rowIndex) => {
            parentRow.dataset.rowIndex = String(rowIndex);
            parentRow.querySelector('td:first-child').textContent = rowIndex + 1;

            parameterItems.forEach(parameter => {
                parameterEntriesBody.appendChild(createParameterRow(rowIndex, rowIndex + 1, parameter, parentRow));
            });
        });
    }

    function renumberUnitRows() {
        unitEntriesBody.querySelectorAll('tr').forEach((tr, index) => {
            tr.dataset.rowIndex = String(index);
            tr.querySelector('td:first-child').textContent = index + 1;
        });
    }

    function buildRows() {
        unitEntriesBody.innerHTML = '';
        const optionsHtml = createManufacturerOptions();
        unitEntriesBody.appendChild(createRow(1, optionsHtml));
        rebuildParameterRows();
        updateTotalUnits();
    }

    function resetRows() {
        const inputs = unitEntriesBody.querySelectorAll('input, select');
        inputs.forEach(input => {
            if (input.tagName.toLowerCase() === 'select') {
                input.selectedIndex = 0;
            } else {
                input.value = '';
            }
        });
        unitEntriesBody.querySelectorAll('tr').forEach(row => {
            setCoachOptions(row);
        });
        rebuildParameterRows();
    }

    function getRowCount() {
        return unitEntriesBody.querySelectorAll('tr').length;
    }

    function updateTotalUnits() {
        // No longer needed for count tracking
    }

    function addRow() {
        const rowCount = getRowCount();
        const optionsHtml = createManufacturerOptions();
        const newRow = createRow(rowCount + 1, optionsHtml);
        unitEntriesBody.appendChild(newRow);
        rebuildParameterRows();
    }

    function removeRow(button) {
        const row = button.closest('tr');
        if (!row) return;
        row.remove();
        renumberUnitRows();
        rebuildParameterRows();
    }

    function downloadCsv() {
        const rows = [['Unit #', 'Serial Number', 'Model Number', 'Purchase Date', 'Warranty Expire', 'Train', 'Coach', 'Manufacturer']];
        const rowCount = getRowCount();
        for (let i = 1; i <= rowCount; i++) {
            const serial = document.querySelectorAll('[name="new_serial[]"]')[i - 1]?.value || '';
            const model = document.querySelectorAll('[name="new_model[]"]')[i - 1]?.value || '';
            const purchase = document.querySelectorAll('[name="new_purchase[]"]')[i - 1]?.value || '';
            const trainSelect = document.querySelectorAll('[name="new_train_info_id[]"]')[i - 1];
            const train = trainSelect ? trainSelect.options[trainSelect.selectedIndex]?.text || '' : '';
            const coachSelect = document.querySelectorAll('[name="new_coach_id[]"]')[i - 1];
            const coach = coachSelect ? coachSelect.options[coachSelect.selectedIndex]?.text || '' : '';
            const manufacturerSelect = document.querySelectorAll('[name="new_manufacturer[]"]')[i - 1];
            const manufacturer = manufacturerSelect ? manufacturerSelect.options[manufacturerSelect.selectedIndex]?.text || '' : '';
            const warranty = document.querySelectorAll('[name="new_warranty_expire[]"]')[i - 1]?.value || '';
            rows.push([i, serial, model, purchase, warranty, train, coach, manufacturer]);
        }

        const csvContent = rows.map(r => r.map(v => `"${(v || '').toString().replace(/"/g, '""')}"`).join(',')).join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `inventory_${<?php echo json_encode($item['item_code']); ?>}_units.csv`;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportExistingUnitsExcel() {
        const sourceTable = document.getElementById('existingUnitsTable');

        if (!sourceTable) {
            return;
        }

        const exportTable = sourceTable.cloneNode(true);
        exportTable.querySelectorAll('.no-export').forEach(element => element.remove());

        const workbookHtml = `
            <html>
                <head>
                    <meta charset="UTF-8">
                </head>
                <body>
                    <h3>Existing Units</h3>
                    <p>${escapeHtml(<?php echo json_encode($item['item_code'] . ' - ' . $item['item_name']); ?>)}</p>
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
        link.download = `existing-units-${<?php echo json_encode($item['item_code']); ?>}-${date}.xls`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    resetUnitsBtn?.addEventListener('click', () => {
        resetRows();
    });

    const resetUnitsHeaderBtn = document.getElementById('resetUnitsHeaderBtn');
    if (resetUnitsHeaderBtn) {
        resetUnitsHeaderBtn.addEventListener('click', () => {
            resetRows();
        });
    }

    addRowBtn?.addEventListener('click', () => {
        addRow();
    });

    unitEntriesBody?.addEventListener('input', event => {
        const row = event.target.closest('tr');
        if (!row) {
            return;
        }

        syncParameterRowsForParent(row.dataset.rowIndex);
    });

    unitEntriesBody?.addEventListener('change', event => {
        const row = event.target.closest('tr');
        if (!row) {
            return;
        }

        if (event.target.classList.contains('unit-train-select')) {
            setCoachOptions(row);
        }

        syncParameterRowsForParent(row.dataset.rowIndex);
    });

    downloadCsvBtn?.addEventListener('click', downloadCsv);

    printExistingUnitsBtn?.addEventListener('click', () => {
        window.print();
    });

    exportExistingUnitsBtn?.addEventListener('click', exportExistingUnitsExcel);

    scrollTopBtn?.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Edit unit modal functionality
    const editUnitModal = new bootstrap.Modal(document.getElementById('editUnitModal'), {});
    const editUnitForm = document.getElementById('editUnitForm');
    const editButtons = document.querySelectorAll('.editUnitBtn');

    editButtons.forEach(btn => { 
        btn.addEventListener('click', function() {
            const unitId = this.getAttribute('data-unit-id');
            const serial = this.getAttribute('data-serial');
            const model = this.getAttribute('data-model');
            const purchase = this.getAttribute('data-purchase');
            const warranty = this.getAttribute('data-warranty');
            const manufacturer = this.getAttribute('data-manufacturer');

            document.getElementById('editUnitId').value = unitId;
            document.getElementById('editSerial').value = serial || '';
            document.getElementById('editModel').value = model || '';
            document.getElementById('editPurchase').value = purchase || '';
            document.getElementById('editWarrantyExpire').value = warranty || '';
            document.getElementById('editManufacturer').value = manufacturer || '';

            editUnitModal.show();
        });
    });

    editUnitForm.addEventListener('submit', function(e) {
        e.preventDefault();
        this.submit();
    });

    buildRows();
</script>
</body>
</html>
