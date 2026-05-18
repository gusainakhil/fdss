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
$message = '';
$message_type = '';

if ($inventory_id > 0) {
    $query = "SELECT inventory_id, item_code, item_name, quantity, category, status, remarks
              FROM fdss_Inventory_Management
              WHERE inventory_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $inventory_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_units') {
    $newSerials = $_POST['new_serial'] ?? [];
    $newModels = $_POST['new_model'] ?? [];
    $newPurchases = $_POST['new_purchase'] ?? [];
    $newWarrantyExpires = $_POST['new_warranty_expire'] ?? [];
    $newManufacturerIds = $_POST['new_manufacturer'] ?? [];
    $newNotesList = $_POST['new_notes'] ?? [];

    if ($inventory_id > 0) {
        $conn->begin_transaction();
        try {
            $insertQuery = "INSERT INTO fdds_inventory_unit 
                (inventory_id, user_id, serial_number, model_number, purchase_date, warranty_expire, manufacturer_id, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);

            $newRowCount = 0;
            for ($i = 0; $i < count($newSerials); $i++) {
                $serial = trim($newSerials[$i] ?? '');
                $model = trim($newModels[$i] ?? '');
                $purchase = trim($newPurchases[$i] ?? '');
                $warrantyExpire = trim($newWarrantyExpires[$i] ?? '');
                $manufacturerId = (int) ($newManufacturerIds[$i] ?? 0) ?: null;
                $note = trim($newNotesList[$i] ?? '');

                // Only insert if at least one field is filled
                if (!empty($serial) || !empty($model) || !empty($purchase) || !empty($warrantyExpire) || !empty($manufacturerId) || !empty($note)) {
                    $insertStmt->bind_param('iissssis', $inventory_id, $user_id, $serial, $model, $purchase, $warrantyExpire, $manufacturerId, $note);
                    $insertStmt->execute();
                    $newRowCount++;
                }
            }
            $insertStmt->close();

            // Update total quantity
            $getTotalQuery = "SELECT COUNT(*) as total FROM fdds_inventory_unit WHERE inventory_id = ? AND user_id = ?";
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
            $message = "Added {$newRowCount} new unit(s) successfully. Total count: {$totalCount}.";
            $message_type = 'success';
            
            // Reload existing units
            $existing_units = [];
            if ($item) {
                $unit_query = "SELECT unit_id, serial_number, model_number, purchase_date, warranty_expire, manufacturer_id, notes
                               FROM fdds_inventory_unit
                               WHERE inventory_id = ? AND user_id = ?
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
    $note = trim($_POST['edit_notes'] ?? '');

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
                $unit_query = "SELECT unit_id, serial_number, model_number, purchase_date, warranty_expire, manufacturer_id, notes
                               FROM fdds_inventory_unit
                               WHERE inventory_id = ? AND user_id = ?
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
    $unit_query = "SELECT unit_id, serial_number, model_number, purchase_date, warranty_expire, manufacturer_id, notes
                   FROM fdds_inventory_unit
                   WHERE inventory_id = ? AND user_id = ?
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
        .unit-row-count {
            min-width: 80px;
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
                        <strong>Component Name</strong>
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
                                    <th>OEM</th>
                                    <th>Notes</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="unitEntriesBody"></tbody>
                        </table>
                    </div>

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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-card-checklist"></i> Existing Units</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($existing_units)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm unit-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="unit-row-count">Unit #</th>
                                    <th>Serial Number</th>
                                    <th>Model Number</th>
                                    <th>Purchase Date</th>
                                    <th>Warranty Expire</th>
                                    <th>OEM</th>
                                    <th>Notes</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($existing_units as $idx => $unit): ?>
                                    <tr>
                                        <td class="align-middle"><?php echo $idx + 1; ?></td>
                                        <td><?php echo e($unit['serial_number']); ?></td>
                                        <td><?php echo e($unit['model_number']); ?></td>
                                        <td><?php echo e($unit['purchase_date']); ?></td>
                                        <td><?php echo e($unit['warranty_expire']); ?></td>
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
                                        <td><?php echo e($unit['notes']); ?></td>
                                        <td class="text-center align-middle">
                                            <button type="button" class="btn btn-sm btn-outline-primary editUnitBtn" 
                                                    data-unit-id="<?php echo e($unit['unit_id']); ?>"
                                                    data-serial="<?php echo e($unit['serial_number']); ?>"
                                                    data-model="<?php echo e($unit['model_number']); ?>"
                                                    data-purchase="<?php echo e($unit['purchase_date']); ?>"
                                                    data-warranty="<?php echo e($unit['warranty_expire']); ?>"
                                                    data-manufacturer="<?php echo e($unit['manufacturer_id']); ?>"
                                                    data-notes="<?php echo e($unit['notes']); ?>">
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
                    
                    <div class="mb-3">
                        <label for="editNotes" class="form-label">Notes</label>
                        <input type="text" class="form-control" id="editNotes" name="edit_notes" placeholder="Optional notes">
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

    const unitEntriesBody = document.getElementById('unitEntriesBody');
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    const addRowBtn = document.getElementById('addRowBtn');
    const submitCountBtn = document.getElementById('submitCountBtn');
    const unitCountForm = document.getElementById('unitCountForm');
    const resetUnitsBtn = document.getElementById('resetUnitsBtn');
    const downloadCsvBtn = document.getElementById('downloadCsvBtn');

    function createManufacturerOptions() {
        if (manufacturers.length === 0) {
            return '<option value="">Select manufacturer</option>' +
                '<option value="M1">Manufacturer A</option>' +
                '<option value="M2">Manufacturer B</option>' +
                '<option value="M3">Manufacturer C</option>';
        }

        return manufacturers.reduce((html, manufacturer) => {
            return html + `<option value="${manufacturer.id}">${manufacturer.label}</option>`;
        }, '<option value="">Select manufacturer</option>');
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
        const notesValue = escapeHtml(data.notes || '');

        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="align-middle">${index}</td>
            <td><input type="text" class="form-control form-control-sm" name="new_serial[]" placeholder="Serial #" value="${serialValue}"></td>
            <td><input type="text" class="form-control form-control-sm" name="new_model[]" placeholder="Model #" value="${modelValue}"></td>
            <td><input type="date" class="form-control form-control-sm" name="new_purchase[]" value="${purchaseValue}"></td>
            <td><input type="date" class="form-control form-control-sm" name="new_warranty_expire[]" value="${warrantyValue}"></td>
            <td>
                <select class="form-select form-select-sm" name="new_manufacturer[]">
                    ${optionsHtml}
                </select>
            </td>
            <td><input type="text" class="form-control form-control-sm" name="new_notes[]" placeholder="Optional notes" value="${notesValue}"></td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">
                    <i class="bi bi-dash-circle"></i>
                </button>
            </td>
        `;
        const select = row.querySelector('select');
        if (select && manufacturerValue) {
            select.value = manufacturerValue;
        }
        return row;
    }

    function buildRows() {
        unitEntriesBody.innerHTML = '';
        const optionsHtml = createManufacturerOptions();
        unitEntriesBody.appendChild(createRow(1, optionsHtml));
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
    }

    function removeRow(button) {
        const row = button.closest('tr');
        if (!row) return;
        row.remove();
        const rows = unitEntriesBody.querySelectorAll('tr');
        rows.forEach((tr, index) => {
            tr.querySelector('td:first-child').textContent = index + 1;
        });
    }

    function downloadCsv() {
        const rows = [['Unit #', 'Serial Number', 'Model Number', 'Purchase Date', 'Warranty Expire', 'Manufacturer', 'Notes']];
        const rowCount = getRowCount();
        for (let i = 1; i <= rowCount; i++) {
            const serial = document.querySelectorAll('[name="new_serial[]"]')[i - 1]?.value || '';
            const model = document.querySelectorAll('[name="new_model[]"]')[i - 1]?.value || '';
            const purchase = document.querySelectorAll('[name="new_purchase[]"]')[i - 1]?.value || '';
            const manufacturerSelect = document.querySelectorAll('[name="new_manufacturer[]"]')[i - 1];
            const manufacturer = manufacturerSelect ? manufacturerSelect.options[manufacturerSelect.selectedIndex]?.text || '' : '';
            const warranty = document.querySelectorAll('[name="new_warranty_expire[]"]')[i - 1]?.value || '';
            const notes = document.querySelectorAll('[name="new_notes[]"]')[i - 1]?.value || '';
            rows.push([i, serial, model, purchase, warranty, manufacturer, notes]);
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

    downloadCsvBtn?.addEventListener('click', downloadCsv);

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
            const notes = this.getAttribute('data-notes');

            document.getElementById('editUnitId').value = unitId;
            document.getElementById('editSerial').value = serial || '';
            document.getElementById('editModel').value = model || '';
            document.getElementById('editPurchase').value = purchase || '';
            document.getElementById('editWarrantyExpire').value = warranty || '';
            document.getElementById('editManufacturer').value = manufacturer || '';
            document.getElementById('editNotes').value = notes || '';

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
