<?php
session_start();
require_once 'config/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$train_info_id = isset($_GET['train_info_id']) ? (int) $_GET['train_info_id'] : 0;
$coach_no = trim($_GET['coach_no'] ?? '');

if ($train_info_id < 0 || $coach_no === '') {
    die("Invalid coach details.");
}

if ($train_info_id <= 0) {
    $train_info_id = null;
}

$message = '';
$message_type = '';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| FETCH COACH DETAILS
|--------------------------------------------------------------------------
*/

$coach_query = "SELECT 
                    c.coach_id,
                    c.coach_no,
                    c.coach_type,
                    c.status,
                    c.train_info_id,
                    t.train_no,
                    t.train_name
                FROM fdss_train_coach c
                LEFT JOIN fdss_train_information t 
                    ON t.train_info_id = c.train_info_id
                WHERE c.coach_no = ?
                AND c.user_id = ?";

if ($train_info_id !== null) {
    $coach_query .= " AND c.train_info_id = ?";
}

$coach_query .= "\n                LIMIT 1";

$stmt = $conn->prepare($coach_query);

if ($train_info_id !== null) {
    $stmt->bind_param("sii", $coach_no, $user_id, $train_info_id);
} else {
    $stmt->bind_param("si", $coach_no, $user_id);
}

$stmt->execute();

$coach_result = $stmt->get_result();

if ($coach_result->num_rows === 0) {
    die("Coach not found.");
}

$coach = $coach_result->fetch_assoc();

$stmt->close();

/*
|--------------------------------------------------------------------------
| FETCH MASTER INVENTORY ITEMS
|--------------------------------------------------------------------------
*/

$master_inventory = [];

$master_query = "SELECT inventory_id, item_code, item_name, category
                 FROM fdss_Inventory_Management
                 WHERE user_id = ?
                 ORDER BY item_name ASC";

$stmt = $conn->prepare($master_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$master_result = $stmt->get_result();

while ($row = $master_result->fetch_assoc()) {
    $master_inventory[] = $row;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| FETCH AVAILABLE INVENTORY UNITS
|--------------------------------------------------------------------------
*/

$inventory_units = [];

$unit_query = "SELECT 
                    iu.unit_id,
                    iu.inventory_id,
                    iu.serial_number,
                    iu.model_number,
                    iu.purchase_date,
                    iu.Warranty_expire,
                    iu.notes,
                    im.item_name,
                    m.company_name,
                    ci.id AS assigned_id,
                    tc.coach_no AS assigned_coach_no
               FROM fdds_inventory_unit iu
               INNER JOIN fdss_Inventory_Management im 
                    ON im.inventory_id = iu.inventory_id
               LEFT JOIN fdss_manufacturers m 
                    ON m.manufacturer_id = iu.manufacturer_id
               LEFT JOIN fdss_coach_inventory ci 
                    ON ci.inventory_unit_id = iu.unit_id 
                    AND ci.user_id = iu.user_id
               LEFT JOIN fdss_train_coach tc
                    ON tc.coach_id = ci.coach_id
               WHERE iu.user_id = ?
               ORDER BY im.item_name ASC, iu.unit_id DESC";

$stmt = $conn->prepare($unit_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$unit_result = $stmt->get_result();

while ($row = $unit_result->fetch_assoc()) {
    $inventory_units[] = $row;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| ADD / UPDATE / DELETE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | ADD INVENTORY
    |--------------------------------------------------------------------------
    */

    if ($action === 'add_inventory') {

        $inventory_unit_id = (int) ($_POST['inventory_unit_id'] ?? 0);
        $status = $_POST['status'] ?? 'Active';

        if ($inventory_unit_id <= 0) {
            $message = "Please select an inventory unit.";
            $message_type = "danger";
        } else {
            $check_query = "SELECT ci.id
                            FROM fdss_coach_inventory ci
                            WHERE ci.inventory_unit_id = ?
                            AND ci.user_id = ?
                            LIMIT 1";

            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("ii", $inventory_unit_id, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $message = "This inventory unit is already assigned to a coach.";
                $message_type = "danger";
            } else {
                $unit_check_query = "SELECT unit_id FROM fdds_inventory_unit WHERE unit_id = ? AND user_id = ?";
                $unit_check_stmt = $conn->prepare($unit_check_query);
                $unit_check_stmt->bind_param("ii", $inventory_unit_id, $user_id);
                $unit_check_stmt->execute();
                $unit_check_result = $unit_check_stmt->get_result();

                if ($unit_check_result->num_rows === 0) {
                    $message = "Selected inventory unit not found.";
                    $message_type = "danger";
                } else {
                    $insert_query = "INSERT INTO fdss_coach_inventory
                        (coach_id, inventory_unit_id, user_id, status)
                        VALUES (?, ?, ?, ?)";

                    $stmt = $conn->prepare($insert_query);
                    $stmt->bind_param("iiis", $coach['coach_id'], $inventory_unit_id, $user_id, $status);

                    if ($stmt->execute()) {
                        $message = "Inventory assigned successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error assigning inventory.";
                        $message_type = "danger";
                    }

                    $stmt->close();
                }

                $unit_check_stmt->close();
            }

            $check_stmt->close();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE INVENTORY
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'edit_inventory') {

        $coach_inventory_id = (int) ($_POST['coach_inventory_id'] ?? 0);
        $inventory_unit_id = (int) ($_POST['inventory_unit_id'] ?? 0);
        $status = $_POST['status'] ?? 'Active';

        if ($coach_inventory_id <= 0 || $inventory_unit_id <= 0) {
            $message = "Please select an inventory unit.";
            $message_type = "danger";
        } else {
            $duplicate_query = "SELECT id
                                FROM fdss_coach_inventory
                                WHERE inventory_unit_id = ?
                                AND user_id = ?
                                AND id != ?
                                LIMIT 1";
            $duplicate_stmt = $conn->prepare($duplicate_query);
            $duplicate_stmt->bind_param("iii", $inventory_unit_id, $user_id, $coach_inventory_id);
            $duplicate_stmt->execute();
            $duplicate_result = $duplicate_stmt->get_result();

            if ($duplicate_result->num_rows > 0) {
                $message = "This inventory unit is already assigned to a coach.";
                $message_type = "danger";
            } else {
                $update_query = "UPDATE fdss_coach_inventory SET
                                    inventory_unit_id = ?,
                                    status = ?
                                 WHERE id = ?
                                 AND coach_id = ?
                                 AND user_id = ?";

                $stmt = $conn->prepare($update_query);
                $stmt->bind_param(
                    "isiii",
                    $inventory_unit_id,
                    $status,
                    $coach_inventory_id,
                    $coach['coach_id'],
                    $user_id
                );

                if ($stmt->execute()) {
                    $message = "Inventory assignment updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error updating inventory.";
                    $message_type = "danger";
                }

                $stmt->close();
            }

            $duplicate_stmt->close();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE INVENTORY
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'delete_inventory') {

        $coach_inventory_id = (int) ($_POST['coach_inventory_id'] ?? 0);

        $delete_query = "DELETE FROM fdss_coach_inventory
                         WHERE id = ?
                         AND coach_id = ?
                         AND user_id = ?";

        $stmt = $conn->prepare($delete_query);

        $stmt->bind_param("iii", $coach_inventory_id, $coach['coach_id'], $user_id);

        if ($stmt->execute()) {

            $message = "Inventory deleted successfully!";
            $message_type = "success";

        } else {

            $message = "Error deleting inventory.";
            $message_type = "danger";
        }

        $stmt->close();
    }
}

/*
|--------------------------------------------------------------------------
| FETCH INVENTORY ITEMS
|--------------------------------------------------------------------------
*/

$inventory_items = [];

$list_query = "SELECT 
                    ci.id AS coach_inventory_id,
                    ci.inventory_unit_id,
                    ci.status,
                    ci.created_at,
                    ci.updated_at,
                    iu.inventory_id,
                    iu.serial_number,
                    iu.model_number,
                    iu.purchase_date,
                    iu.Warranty_expire,
                    iu.notes,
                    im.item_name AS tool_name,
                    m.company_name
               FROM fdss_coach_inventory ci
               INNER JOIN fdds_inventory_unit iu 
                    ON iu.unit_id = ci.inventory_unit_id
               INNER JOIN fdss_Inventory_Management im
                    ON im.inventory_id = iu.inventory_id
               LEFT JOIN fdss_manufacturers m 
                    ON m.manufacturer_id = iu.manufacturer_id
               WHERE ci.coach_id = ?
               AND ci.user_id = ?";

$list_query .= "\n               ORDER BY ci.id DESC";

$stmt = $conn->prepare($list_query);
$stmt->bind_param("ii", $coach['coach_id'], $user_id);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $inventory_items[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Coach Inventory Details - FDSS Dashboard
    </title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <link href="assets/css/styles.css"
          rel="stylesheet">

</head>

<body>

<?php include('includes/navbar.php'); ?>

<div class="sidebar-container">

<?php include('includes/sidebar.php'); ?>

<main class="main-content">

    <div class="page-header">

        <div>

            <h1>
                Coach Inventory Detail
            </h1>

            <p class="page-header-subtitle">
                Inventory and expiry status for selected coach
            </p>

        </div>

        <div class="page-header-actions">

            <button class="btn btn-primary"
                    id="addInventoryBtn"
                    data-bs-toggle="modal"
                    data-bs-target="#inventoryModal">

                <i class="bi bi-plus-circle"></i>
                Add Coach Component

            </button>

            <a class="btn btn-primary"
               href="coaches.php">

                <i class="bi bi-arrow-left"></i>
                Back To Coaches

            </a>

        </div>

    </div>

    <?php if ($message): ?>

        <div class="alert alert-<?php echo e($message_type); ?> alert-dismissible fade show">

            <?php echo e($message); ?>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"></button>

        </div>

    <?php endif; ?>

    <div class="content-card">

        <div class="card-header">
            <h5>Coach Detail Summary</h5>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-lg-3 col-md-6">

                    <div class="quick-box">

                        <h6>Coach Number</h6>

                        <p>
                            <strong>
                                <?php echo e($coach['coach_no']); ?>
                            </strong>
                        </p>

                    </div>

                </div>

                <div class="col-lg-3 col-md-6">

                    <div class="quick-box">

                        <h6>Assigned Train</h6>

                        <p>

                            <strong>

                                <?php if (!empty($coach['train_info_id']) && !empty($coach['train_no'])): ?>
                                    <?php echo e($coach['train_no']); ?> - <?php echo e($coach['train_name']); ?>
                                <?php else: ?>
                                    Detached
                                <?php endif; ?>

                            </strong>

                        </p>

                    </div>

                </div>

                <div class="col-lg-3 col-md-6">

                    <div class="quick-box">

                        <h6>Coach Type</h6>

                        <p>
                            <strong>
                                <?php echo e($coach['coach_type']); ?>
                            </strong>
                        </p>

                    </div>

                </div>

                <div class="col-lg-3 col-md-6">

                    <div class="quick-box">

                        <h6>Overall FDSS Status</h6>

                        <p>

                            <span class="badge <?php echo ($coach['status'] === 'Active') ? 'badge-success' : 'badge-danger'; ?>">

                                <?php echo e($coach['status']); ?>

                            </span>

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="content-card">

        <div class="card-header">

            <h5>

                Coach Components Installed In

                <?php echo e($coach['coach_no']); ?>

            </h5>

        </div>

        <div class="card-body">

            <div class="table-wrapper">

                <table class="table table-hover">

                    <thead>

                    <tr>

                        <th>Component Name</th>
                        <th>OEM</th>
                        <th>Model Number</th>
                        <th>Serial Number</th>
                        <th>Assigned Coach</th>
                        <th>Purchase Date</th>
                        <th>Warranty Expire</th>
                        <th>Notes</th>
                        <th>Status</th>
                        <th>Actions</th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($inventory_items)): ?>

                        <tr>

                            <td colspan="10"
                                class="text-center text-muted py-4">

                                No inventory found for this coach.

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($inventory_items as $item): ?>

                            <tr>

                                <td>
                                    <?php echo e($item['tool_name']); ?>
                                </td>

                                <td>
                                    <?php echo e($item['company_name'] ?? '-'); ?>
                                </td>

                                <td>
                                    <?php echo e($item['model_number']); ?>
                                </td>

                                <td>
                                    <?php echo e($item['serial_number']); ?>
                                </td>

                                <td>

                                    <?php echo e($coach['coach_no']); ?>

                                    <?php if (!empty($coach['train_info_id']) && !empty($coach['train_no'])): ?>
                                        (Train: <?php echo e($coach['train_no']); ?>)
                                    <?php else: ?>
                                        (Detached)
                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php echo $item['purchase_date']
                                        ? date('d M Y', strtotime($item['purchase_date']))
                                        : '-'; ?>

                                </td>

                                <td>

                                    <?php echo $item['Warranty_expire']
                                        ? date('d M Y', strtotime($item['Warranty_expire']))
                                        : '-'; ?>

                                </td>

                                <td>
                                    <?php echo e($item['notes'] ?: '-'); ?>
                                </td>

                                <td>

                                    <span class="badge <?php echo ($item['status'] === 'Expired') ? 'badge-danger' : 'badge-success'; ?>">

                                        <?php echo e($item['status']); ?>

                                    </span>

                                </td>

                                <td>

                                    <button
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="editInventory(
                                            '<?php echo e($item['coach_inventory_id']); ?>',
                                            '<?php echo e($item['inventory_id']); ?>',
                                            '<?php echo e($item['inventory_unit_id']); ?>',
                                            '<?php echo e($item['status']); ?>'
                                        )"
                                        data-bs-toggle="modal"
                                        data-bs-target="#inventoryModal">

                                        <i class="bi bi-pencil"></i>

                                    </button>
<!-- 
                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="deleteInventory('<?php echo e($item['coach_inventory_id']); ?>')">

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

<div class="modal fade"
     id="inventoryModal"
     tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title"
                    id="inventoryModalTitle">

                    Add Coach Component 

                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"></button>

            </div>

            <form method="POST">

                <input type="hidden"
                       name="action"
                       id="formAction"
                       value="add_inventory">

                <input type="hidden"
                       name="coach_inventory_id"
                       id="coachInventoryId">

                <div class="modal-body">

	                    <div class="mb-3">

	                        <label class="form-label">
	                            Component
	                        </label>

	                        <select class="form-select"
	                                id="inventoryItemId"
	                                required>

	                            <option value="">
	                                Select Component
	                            </option>

	                            <?php foreach ($master_inventory as $tool): ?>

	                                <option value="<?php echo e($tool['inventory_id']); ?>">

	                                    <?php echo e($tool['item_name']); ?>
	                                    <?php if (!empty($tool['item_code'])): ?>
	                                        (<?php echo e($tool['item_code']); ?>)
	                                    <?php endif; ?>
	                                    <?php if (!empty($tool['category'])): ?>
	                                        - <?php echo e($tool['category']); ?>
	                                    <?php endif; ?>

	                                </option>

	                            <?php endforeach; ?>

	                        </select>

	                    </div>

	                    <div class="mb-3">

	                        <label class="form-label">
	                            Inventory Unit
	                        </label>

	                        <select class="form-select"
                                id="inventoryUnitId"
                                name="inventory_unit_id"
                                required>

                            <option value="">
                                Select Inventory Unit
                            </option>

                            <?php foreach ($inventory_units as $unit): ?>

	                                <option value="<?php echo e($unit['unit_id']); ?>"
	                                        data-inventory-id="<?php echo e($unit['inventory_id']); ?>"
	                                        <?php echo (!empty($unit['assigned_id']) && (int) $unit['assigned_id'] !== 0) ? 'data-assigned="1"' : ''; ?>>

                                    <?php echo e($unit['item_name']); ?>
                                    <?php if (!empty($unit['serial_number'])): ?>
                                        - SN: <?php echo e($unit['serial_number']); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($unit['model_number'])): ?>
                                        - Model: <?php echo e($unit['model_number']); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($unit['assigned_coach_no'])): ?>
                                        (Assigned: <?php echo e($unit['assigned_coach_no']); ?>)
                                    <?php endif; ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select class="form-select"
                                id="inventoryStatus"
                                name="status">

                            <option value="Active">
                                Active
                            </option>

                            <option value="Expired">
                                Expired
                            </option>

                        </select>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                            class="btn btn-primary"
                            id="inventorySubmitBtn">

                        Save

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<form method="POST"
      id="deleteForm"
      style="display:none;">

    <input type="hidden"
           name="action"
           value="delete_inventory">

    <input type="hidden"
           name="coach_inventory_id"
           id="deleteInventoryId">

</form>

<?php include('includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script src="assets/js/layout.js"></script>

<script>

const inventoryModalTitle = document.getElementById('inventoryModalTitle');
const inventorySubmitBtn = document.getElementById('inventorySubmitBtn');
const inventoryItemId = document.getElementById('inventoryItemId');
const inventoryUnitId = document.getElementById('inventoryUnitId');

function filterInventoryUnits(selectedUnitId = '') {

    const selectedInventoryId = inventoryItemId.value;

    Array.from(inventoryUnitId.options).forEach(function (option) {

        if (option.value === '') {
            option.hidden = false;
            return;
        }

        option.hidden = option.dataset.inventoryId !== selectedInventoryId;
    });

    if (selectedUnitId) {
        inventoryUnitId.value = selectedUnitId;
    } else {
        inventoryUnitId.value = '';
    }
}
	
function resetInventoryForm() {

    document.getElementById('coachInventoryId').value = '';

    inventoryItemId.value = '';

    filterInventoryUnits();

    document.getElementById('inventoryStatus').value = 'Active';

    document.getElementById('formAction').value = 'add_inventory';

    inventoryModalTitle.textContent = 'Add Coach Component';

    inventorySubmitBtn.textContent = 'Save';
}

function editInventory(
    id,
    inventoryId,
    inventoryUnitId,
    status
) {

    document.getElementById('coachInventoryId').value = id;

    document.getElementById('inventoryItemId').value = inventoryId;

    filterInventoryUnits(inventoryUnitId);

    document.getElementById('inventoryStatus').value = status;

    document.getElementById('formAction').value = 'edit_inventory';

    inventoryModalTitle.textContent = 'Edit Coach Inventory';

    inventorySubmitBtn.textContent = 'Update Inventory';
}

function deleteInventory(id) {

    if (confirm('Delete this inventory item?')) {

        document.getElementById('deleteInventoryId').value = id;

        document.getElementById('deleteForm').submit();
    }
}

document.getElementById('addInventoryBtn')
    .addEventListener('click', resetInventoryForm);

inventoryItemId.addEventListener('change', function () {
    filterInventoryUnits();
});

document.getElementById('inventoryModal')
    .addEventListener('hidden.bs.modal', resetInventoryForm);

filterInventoryUnits();

</script>

</body>
</html>

<?php
$conn->close();
?>
