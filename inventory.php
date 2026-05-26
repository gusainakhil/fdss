<?php
session_start();
require_once 'config/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';
$user_id = (int)$_SESSION['user_id'];
$item_code_prefix = 'INV-WC-';
$allowed_categories = ['FDSS', 'FSDS', 'Primary', 'Secondary'];

if (isset($_GET['parameter_added'])) {
    $added_count = max(0, (int) $_GET['parameter_added']);
    if ($added_count > 0) {
        $message = $added_count . " parameter row(s) added successfully!";
        $message_type = "success";
    }
}

function generate_next_item_code(mysqli $conn, string $prefix): string
{
    $like_pattern = $prefix . '%';
    $regex_pattern = '^' . preg_quote($prefix, '/') . '[0-9]+$';
    $query = "SELECT MAX(CAST(SUBSTRING(item_code, ?) AS UNSIGNED)) AS last_number
              FROM fdss_Inventory_Management
              WHERE item_code LIKE ?
              AND item_code REGEXP ?";

    $stmt = $conn->prepare($query);
    $start_position = strlen($prefix) + 1;
    $stmt->bind_param("iss", $start_position, $like_pattern, $regex_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    $next_number = ((int)($row['last_number'] ?? 0)) + 1;

    return $prefix . str_pad((string)$next_number, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_inventory') {
        $item_code = trim($_POST['item_code'] ?? '');
        $item_name = trim($_POST['item_name'] ?? '');
        $status = $_POST['status'] ?? 'Working';
        $category = $_POST['category'] ?? 'Primary';
        $category = in_array($category, $allowed_categories, true) ? $category : 'FDSS';
        $remarks = trim($_POST['remarks'] ?? '');

        if ($item_code === '' || $item_name === '') {
            $message = "Please fill all required fields.";
            $message_type = "danger";
        } else {
            $check_query = "SELECT inventory_id FROM fdss_Inventory_Management WHERE item_code = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("s", $item_code);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $message = "Item code already exists!";
                $message_type = "danger";
            } else {
                $insert_query = "INSERT INTO fdss_Inventory_Management 
                    (item_code, item_name, quantity, status, category, user_id, remarks) 
                    VALUES (?, ?, NULL, ?, ?, ?, ?)";

                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param(
                    "ssssis",
                    $item_code,
                    $item_name,
                    $status,
                    $category,
                    $user_id, 
                    $remarks
                );

                if ($stmt->execute()) {
                    $message = "Component item added successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error adding item: " . $stmt->error;
                    $message_type = "danger";
                }

                $stmt->close();
            }

            $check_stmt->close();
        }
    }

    elseif ($action === 'edit_inventory') {
        $inventory_id = (int)($_POST['inventory_id'] ?? 0);
        $item_name = trim($_POST['item_name'] ?? '');
        $status = $_POST['status'] ?? 'Working';
        $category = $_POST['category'] ?? 'Primary';
        $category = in_array($category, $allowed_categories, true) ? $category : 'FDSS';
        $remarks = trim($_POST['remarks'] ?? '');

        $check_query = "SELECT inventory_id, item_code FROM fdss_Inventory_Management
                        WHERE user_id = ? AND inventory_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $user_id, $inventory_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            $message = "Inventory item not found.";
            $message_type = "danger";
        } else {
            $item_code = $check_result->fetch_assoc()['item_code'];
            $update_query = "UPDATE fdss_Inventory_Management 
                SET item_name = ?, status = ?, category = ?, remarks = ?, last_updated = CURRENT_TIMESTAMP
                WHERE inventory_id = ? AND user_id = ?";

            $stmt = $conn->prepare($update_query);
            $stmt->bind_param(
                "ssssii",
                $item_name,
                $status,
                $category,
                $remarks,
                $inventory_id,
                $user_id
            );

            if ($stmt->execute()) {
                $message = "Component item updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating item: " . $stmt->error;
                $message_type = "danger";
            }

            $stmt->close();
        }

        $check_stmt->close();
    }

    elseif ($action === 'delete_inventory') {
        $inventory_id = (int)($_POST['inventory_id'] ?? 0);

        $delete_query = "DELETE FROM fdss_Inventory_Management WHERE inventory_id = ? AND user_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $inventory_id, $user_id);

        if ($stmt->execute()) {
            $message = "Component item deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting item.";
            $message_type = "danger";
        }

        $stmt->close();
    }
}

$inventory_items = [];

$query = "SELECT inventory_id, item_code, item_name, status, category, user_id, last_updated, remarks, created_at, updated_at
          FROM fdss_Inventory_Management
          WHERE user_id = ?
          AND category IN ('FDSS', 'FSDS', 'Primary', 'Secondary')
          ORDER BY inventory_id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $inventory_items[] = $row;
}

$stmt->close();
$next_item_code = generate_next_item_code($conn, $item_code_prefix);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - FDSS Dashboard</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>

<?php include('includes/navbar.php'); ?>

<div class="sidebar-container">
    <?php include('includes/sidebar.php'); ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>Inventory Management</h1>
                <p class="page-header-subtitle">Manage stock levels and track components</p>
                <p class="text-danger mb-0">
                   Firstly, add the parameters of FDSS and FSDS <a href="add-parameter.php" class="text-danger fw-semibold">click here</a>
                </p>
            </div>
            <div class="page-header-actions">
                <a href="add-parameter.php" class="btn btn-outline-primary">
                    <i class="bi bi-sliders"></i> Add Parameter
                </a>
                <button class="btn btn-primary" id="addInventoryBtn" data-bs-toggle="modal" data-bs-target="#inventoryModal">
                    <i class="bi bi-plus-circle"></i> Add Inventory
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-boxes"></i>
                    Inventory List (<?php echo count($inventory_items); ?> Total)
                </h5>
            </div>

            <div class="card-body">
                <div class="table-wrapper">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Inventory Name</th>
                                <th>Category</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if (empty($inventory_items)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No components found. Click "Add Component" to create one.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inventory_items as $item): ?>
                                <?php
                                    $details_page = in_array($item['category'], ['FDSS', 'FSDS'], true)
                                        ? 'add-fsds-fdds-inventory.php'
                                        : 'inventory-item-details.php';
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($details_page); ?>?inventory_id=<?php echo htmlspecialchars($item['inventory_id']); ?>" target="_blank" class="text-decoration-none">
                                            <?php echo htmlspecialchars($item['item_code']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo in_array($item['category'], ['Primary', 'FDSS'], true) ? 'badge-success' : 'badge-info'; ?>">
                                            <?php echo htmlspecialchars($item['category']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $item['last_updated'] ? date('Y-m-d h:i A', strtotime($item['last_updated'])) : '-'; ?>
                                    </td>
                                    <td>
                                        <button 
                                            class="btn btn-sm btn-outline-primary"
                                            onclick="editInventory(
                                                '<?php echo htmlspecialchars($item['inventory_id']); ?>',
                                                '<?php echo htmlspecialchars(addslashes($item['item_code'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($item['item_name'])); ?>',
                                                '<?php echo htmlspecialchars($item['status']); ?>',
                                                '<?php echo htmlspecialchars($item['category']); ?>',
                                                '<?php echo htmlspecialchars(addslashes($item['remarks'] ?? '')); ?>'
                                            )"
                                            data-bs-toggle="modal"
                                            data-bs-target="#inventoryModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <!-- Delete hidden as per your previous UI -->
                                        <!--
                                        <button 
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="deleteInventory('<?php echo htmlspecialchars($item['inventory_id']); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        -->
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

<div class="modal fade" id="inventoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="add_inventory">
                <input type="hidden" name="inventory_id" id="inventoryId" value="">

                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="form-label">Item Code <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control"
                            id="itemCode"
                            name="item_code"
                            value="<?php echo htmlspecialchars($next_item_code); ?>"
                            required
                        >
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Inventory Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="itemName" name="item_name" placeholder="Enter Inventory name" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="itemCategory" name="category" required>
                            <option value="FDSS">FDSS</option>
                            <option value="FSDS">FSDS</option>
                            <option value="Primary">Primary</option>
                            <option value="Secondary">Secondary</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="itemStatus" name="status" required>
                            <option value="Working">Working</option>
                            <option value="Needs Check">Needs Check</option>
                            <option value="Not Working">Not Working</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" id="itemRemarks" name="remarks" rows="3" placeholder="Enter remarks"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitInventoryBtn">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="action" value="delete_inventory">
    <input type="hidden" name="inventory_id" id="deleteInventoryId">
</form>

<?php include('includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/layout.js"></script>

<script>
const inventoryModalEl = document.getElementById('inventoryModal');
const inventoryModal = bootstrap.Modal.getOrCreateInstance(inventoryModalEl);

const addInventoryBtn = document.getElementById('addInventoryBtn');
const modalTitle = inventoryModalEl.querySelector('.modal-title');
const submitInventoryBtn = document.getElementById('submitInventoryBtn');
const formAction = document.getElementById('formAction');
const nextItemCode = <?php echo json_encode($next_item_code); ?>;

function resetInventoryForm() {
    document.getElementById('inventoryId').value = '';
    document.getElementById('itemCode').value = nextItemCode;
    document.getElementById('itemName').value = '';
    document.getElementById('itemCategory').value = 'FDSS';
    document.getElementById('itemStatus').value = 'Working';
    document.getElementById('itemRemarks').value = '';

    formAction.value = 'add_inventory';
    modalTitle.textContent = 'Add Inventory Item';
    submitInventoryBtn.textContent = 'Add Item';
}

function editInventory(id, itemCode, itemName, status, category, remarks) {
    document.getElementById('inventoryId').value = id;
    document.getElementById('itemCode').value = itemCode;
    document.getElementById('itemName').value = itemName;
    document.getElementById('itemStatus').value = status;
    document.getElementById('itemCategory').value = category;
    document.getElementById('itemRemarks').value = remarks;

    formAction.value = 'edit_inventory';
    modalTitle.textContent = 'Edit Component Item';
    submitInventoryBtn.textContent = 'Update Item';
}

function deleteInventory(id) {
    if (confirm('Are you sure you want to delete this inventory item?')) {
        document.getElementById('deleteInventoryId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

addInventoryBtn.addEventListener('click', function () {
    resetInventoryForm();
});

inventoryModalEl.addEventListener('hidden.bs.modal', function () {
    resetInventoryForm();
});
</script>

</body>
</html>

<?php
$conn->close();
?>
