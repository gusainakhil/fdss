<?php
session_start();
require_once 'config/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$train_info_id = (int) ($_GET['train_info_id'] ?? 0);
$coach_no = trim($_GET['coach_no'] ?? '');

if ($train_info_id <= 0 || $coach_no === '') {
    die("Invalid coach details.");
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
                    c.coach_no,
                    c.coach_type,
                    c.status,
                    t.train_no,
                    t.train_name
                FROM fdss_train_coach c
                INNER JOIN fdss_train_information t 
                    ON t.train_info_id = c.train_info_id
                WHERE c.train_info_id = ?
                AND c.coach_no = ?
                AND c.user_id = ?
                LIMIT 1";

$stmt = $conn->prepare($coach_query);
$stmt->bind_param("isi", $train_info_id, $coach_no, $user_id);
$stmt->execute();

$coach_result = $stmt->get_result();

if ($coach_result->num_rows === 0) {
    die("Coach not found.");
}

$coach = $coach_result->fetch_assoc();

$stmt->close();

/*
|--------------------------------------------------------------------------
| FETCH MASTER INVENTORY TOOLS
|--------------------------------------------------------------------------
*/

$master_inventory = [];

$master_query = "SELECT inventory_id, item_name 
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
| FETCH MANUFACTURERS
|--------------------------------------------------------------------------
*/

$manufacturers = [];

$manufacturer_query = "SELECT manufacturer_id, company_name 
                       FROM fdss_manufacturers
                       WHERE user_id = ? AND status = 'Active'
                       ORDER BY company_name ASC";

$stmt = $conn->prepare($manufacturer_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$manufacturer_result = $stmt->get_result();

while ($row = $manufacturer_result->fetch_assoc()) {
    $manufacturers[] = $row;
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

        $tool_name = trim($_POST['tool_name'] ?? '');
        $manufacturer_id = !empty($_POST['manufacturer_id']) ? (int) $_POST['manufacturer_id'] : null;
        $model_number = trim($_POST['model_number'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');

        $installed_date = !empty($_POST['installed_date'])
            ? $_POST['installed_date']
            : null;

        $expiry_date = !empty($_POST['expiry_date'])
            ? $_POST['expiry_date']
            : null;

        $last_service_date = !empty($_POST['last_service_date'])
            ? $_POST['last_service_date']
            : null;

        $next_service_date = !empty($_POST['next_service_date'])
            ? $_POST['next_service_date']
            : null;

        $remarks = trim($_POST['remarks'] ?? '');

        $status = $_POST['status'] ?? 'Active';

        $insert_query = "INSERT INTO fdss_coach_inventory
        (
            train_info_id,
            coach_no,
            tool_name,
            manufacturer_id,
            model_number,
            serial_number,
            installed_date,
            expiry_date,
            last_service_date,
            next_service_date,
            remarks,
            user_id,
            status
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";

        $stmt = $conn->prepare($insert_query);

        $stmt->bind_param(
            "issssssssssis",
            $train_info_id,
            $coach_no,
            $tool_name,
            $manufacturer_id,
            $model_number,
            $serial_number,
            $installed_date,
            $expiry_date,
            $last_service_date,
            $next_service_date,
            $remarks,
            $user_id,
            $status
        );

        if ($stmt->execute()) {

            $message = "Inventory added successfully!";
            $message_type = "success";

        } else {

            $message = "Error adding inventory.";
            $message_type = "danger";
        }

        $stmt->close();
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE INVENTORY
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'edit_inventory') {

        $coach_inventory_id = (int) ($_POST['coach_inventory_id'] ?? 0);

        $tool_name = trim($_POST['tool_name'] ?? '');
        $manufacturer_id = !empty($_POST['manufacturer_id']) ? (int) $_POST['manufacturer_id'] : null;
        $model_number = trim($_POST['model_number'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');

        $installed_date = !empty($_POST['installed_date'])
            ? $_POST['installed_date']
            : null;

        $expiry_date = !empty($_POST['expiry_date'])
            ? $_POST['expiry_date']
            : null;

        $last_service_date = !empty($_POST['last_service_date'])
            ? $_POST['last_service_date']
            : null;

        $next_service_date = !empty($_POST['next_service_date'])
            ? $_POST['next_service_date']
            : null;

        $remarks = trim($_POST['remarks'] ?? '');

        $status = $_POST['status'] ?? 'Active';

        $update_query = "UPDATE fdss_coach_inventory SET

            tool_name = ?,
            manufacturer_id = ?,
            model_number = ?,
            serial_number = ?,
            installed_date = ?,
            expiry_date = ?,
            last_service_date = ?,
            next_service_date = ?,
            remarks = ?,
            status = ?

            WHERE coach_inventory_id = ?
            AND user_id = ?";

        $stmt = $conn->prepare($update_query);

        $stmt->bind_param(
            "ssssssssssii",
            $tool_name,
            $manufacturer_id,
            $model_number,
            $serial_number,
            $installed_date,
            $expiry_date,
            $last_service_date,
            $next_service_date,
            $remarks,
            $status,
            $coach_inventory_id,
            $user_id
        );

        if ($stmt->execute()) {

            $message = "Inventory updated successfully!";
            $message_type = "success";

        } else {

            $message = "Error updating inventory.";
            $message_type = "danger";
        }

        $stmt->close();
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE INVENTORY
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'delete_inventory') {

        $coach_inventory_id = (int) ($_POST['coach_inventory_id'] ?? 0);

        $delete_query = "DELETE FROM fdss_coach_inventory
                         WHERE coach_inventory_id = ?
                         AND user_id = ?";

        $stmt = $conn->prepare($delete_query);

        $stmt->bind_param("ii", $coach_inventory_id, $user_id);

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

$list_query = "SELECT ci.*, m.company_name
               FROM fdss_coach_inventory ci
               LEFT JOIN fdss_manufacturers m ON ci.manufacturer_id = m.manufacturer_id
               WHERE ci.train_info_id = ?
               AND ci.coach_no = ?
               AND ci.user_id = ?
               ORDER BY ci.coach_inventory_id DESC";

$stmt = $conn->prepare($list_query);

$stmt->bind_param(
    "isi",
    $train_info_id,
    $coach_no,
    $user_id
);

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

                                <?php echo e($coach['train_no']); ?>
                                -
                                <?php echo e($coach['train_name']); ?>

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
                        <th>Manufacturer</th>
                        <th>Model Number</th>
                        <th>Serial Number</th>
                        <th>Assigned Coach</th>
                        <th>Installed Date</th>
                        <th>Expiry Date</th>
                        <th>Last Service</th>
                        <th>Next Service</th>
                        <th>Remarks</th>
                        <th>Status</th>
                        <th>Actions</th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($inventory_items)): ?>

                        <tr>

                            <td colspan="12"
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

                                    (Train:

                                    <?php echo e($coach['train_no']); ?>

                                    )

                                </td>

                                <td>

                                    <?php echo $item['installed_date']
                                        ? date('d M Y', strtotime($item['installed_date']))
                                        : '-'; ?>

                                </td>

                                <td>

                                    <?php echo $item['expiry_date']
                                        ? date('d M Y', strtotime($item['expiry_date']))
                                        : '-'; ?>

                                </td>

                                <td>

                                    <?php echo $item['last_service_date']
                                        ? date('d M Y', strtotime($item['last_service_date']))
                                        : '-'; ?>

                                </td>

                                <td>

                                    <?php echo $item['next_service_date']
                                        ? date('d M Y', strtotime($item['next_service_date']))
                                        : '-'; ?>

                                </td>

                                <td>
                                    <?php echo e($item['remarks']); ?>
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
                                            '<?php echo e(addslashes($item['tool_name'])); ?>',
                                            '<?php echo e($item['manufacturer_id']); ?>',
                                            '<?php echo e(addslashes($item['model_number'])); ?>',
                                            '<?php echo e(addslashes($item['serial_number'])); ?>',
                                            '<?php echo e($item['installed_date']); ?>',
                                            '<?php echo e($item['expiry_date']); ?>',
                                            '<?php echo e($item['last_service_date']); ?>',
                                            '<?php echo e($item['next_service_date']); ?>',
                                            '<?php echo e(addslashes($item['remarks'])); ?>',
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
                            Component Name
                        </label>

                        <select class="form-select"
                                id="toolName"
                                name="tool_name"
                                required>

                            <option value="">
                                Select Tool
                            </option>

                            <?php foreach ($master_inventory as $tool): ?>

                                <option value="<?php echo e($tool['item_name']); ?>">

                                    <?php echo e($tool['item_name']); ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Manufacturer
                        </label>

                        <select class="form-select"
                                id="manufacturerId"
                                name="manufacturer_id">

                            <option value="">
                                Select Manufacturer
                            </option>

                            <?php foreach ($manufacturers as $manufacturer): ?>

                                <option value="<?php echo e($manufacturer['manufacturer_id']); ?>">

                                    <?php echo e($manufacturer['company_name']); ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Model Number
                        </label>

                        <input type="text"
                               class="form-control"
                               id="modelNumber"
                               name="model_number">

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Serial Number
                        </label>

                        <input type="text"
                               class="form-control"
                               id="serialNumber"
                               name="serial_number">

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Installed Date
                        </label>

                        <input type="date"
                               class="form-control"
                               id="installedDate"
                               name="installed_date">

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Expiry Date
                        </label>

                        <input type="date"
                               class="form-control"
                               id="expiryDate"
                               name="expiry_date">

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Last Service Date
                        </label>

                        <input type="date"
                               class="form-control"
                               id="lastServiceDate"
                               name="last_service_date">

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Next Service Date
                        </label>

                        <input type="date"
                               class="form-control"
                               id="nextServiceDate"
                               name="next_service_date">

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Remarks
                        </label>

                        <textarea class="form-control"
                                  id="remarks"
                                  name="remarks"
                                  rows="3"></textarea>

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

function resetInventoryForm() {

    document.getElementById('coachInventoryId').value = '';

    document.getElementById('toolName').value = '';

    document.getElementById('manufacturerId').value = '';

    document.getElementById('modelNumber').value = '';

    document.getElementById('serialNumber').value = '';

    document.getElementById('installedDate').value = '';

    document.getElementById('expiryDate').value = '';

    document.getElementById('lastServiceDate').value = '';

    document.getElementById('nextServiceDate').value = '';

    document.getElementById('remarks').value = '';

    document.getElementById('inventoryStatus').value = 'Active';

    document.getElementById('formAction').value = 'add_inventory';

    inventoryModalTitle.textContent = 'Add Coach Component';

    inventorySubmitBtn.textContent = 'Save';
}

function editInventory(
    id,
    toolName,
    manufacturerId,
    modelNumber,
    serialNumber,
    installedDate,
    expiryDate,
    lastServiceDate,
    nextServiceDate,
    remarks,
    status
) {

    document.getElementById('coachInventoryId').value = id;

    document.getElementById('toolName').value = toolName;

    document.getElementById('manufacturerId').value = manufacturerId;

    document.getElementById('modelNumber').value = modelNumber;

    document.getElementById('serialNumber').value = serialNumber;

    document.getElementById('installedDate').value = installedDate;

    document.getElementById('expiryDate').value = expiryDate;

    document.getElementById('lastServiceDate').value = lastServiceDate;

    document.getElementById('nextServiceDate').value = nextServiceDate;

    document.getElementById('remarks').value = remarks;

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

document.getElementById('inventoryModal')
    .addEventListener('hidden.bs.modal', resetInventoryForm);

</script>

</body>
</html>

<?php
$conn->close();
?>