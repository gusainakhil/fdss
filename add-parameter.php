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
$allowed_categories = ['FDSSPARA', 'FSDSPARA'];
$item_code_prefix = 'INV-WC-';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function ensure_parameter_categories(mysqli $conn): void
{
    $result = $conn->query("SHOW COLUMNS FROM fdss_Inventory_Management LIKE 'category'");
    if (!$result || $result->num_rows === 0) {
        return;
    }

    $column = $result->fetch_assoc();
    $type = $column['Type'] ?? '';
    if (stripos($type, 'enum(') !== 0) {
        return;
    }

    preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $matches);
    $values = array_map('stripcslashes', $matches[1] ?? []);
    $required = ['Primary', 'Secondary', 'FDSS', 'FSDS', 'FDSSPARA', 'FSDSPARA'];
    $merged = array_values(array_unique(array_merge($values, $required)));

    if ($merged === $values) {
        return;
    }

    $enum_values = array_map(function ($value) use ($conn) {
        return "'" . $conn->real_escape_string($value) . "'";
    }, $merged);

    $conn->query("ALTER TABLE fdss_Inventory_Management MODIFY category ENUM(" . implode(',', $enum_values) . ") NOT NULL");
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

    $next_number = ((int) ($row['last_number'] ?? 0)) + 1;

    return $prefix . str_pad((string) $next_number, 3, '0', STR_PAD_LEFT);
}

ensure_parameter_categories($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_parameters') {
    $category = $_POST['category'] ?? 'FDSSPARA';
    $category = in_array($category, $allowed_categories, true) ? $category : 'FDSSPARA';
    $item_codes = $_POST['item_code'] ?? [];
    $parameter_names = $_POST['parameter_name'] ?? [];

    $clean_rows = [];
    for ($i = 0; $i < count($parameter_names); $i++) {
        $item_code = trim($item_codes[$i] ?? '');
        $name = trim($parameter_names[$i] ?? '');

        if ($item_code === '' && $name === '') {
            continue;
        }

        if ($item_code === '') {
            $message = 'Please enter item code in row ' . ($i + 1) . '.';
            $message_type = 'danger';
            break;
        }

        if ($name === '') {
            $message = 'Please enter parameter name in row ' . ($i + 1) . '.';
            $message_type = 'danger';
            break;
        }

        $clean_rows[] = [
            'item_code' => $item_code,
            'name' => $name,
        ];
    }

    if ($message_type !== 'danger') {
        if (empty($clean_rows)) {
            $message = 'Please enter at least one item code and parameter name.';
            $message_type = 'danger';
        } elseif (count(array_unique(array_column($clean_rows, 'item_code'))) !== count($clean_rows)) {
            $message = 'Duplicate item code found in submitted rows.';
            $message_type = 'danger';
        }
    }

    if ($message_type !== 'danger') {
        $conn->begin_transaction();

        try {
            $duplicate_query = "SELECT inventory_id FROM fdss_Inventory_Management WHERE item_code = ?";
            $duplicate_stmt = $conn->prepare($duplicate_query);

            $insert_query = "INSERT INTO fdss_Inventory_Management
                (item_code, item_name, quantity, status, category, user_id, remarks)
                VALUES (?, ?, NULL, 'Working', ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $inserted_count = 0;

            foreach ($clean_rows as $row) {
                $remarks = '';
                $duplicate_stmt->bind_param("s", $row['item_code']);
                $duplicate_stmt->execute();
                $duplicate_result = $duplicate_stmt->get_result();
                if ($duplicate_result->num_rows > 0) {
                    throw new Exception('Item code already exists: ' . $row['item_code']);
                }

                $stmt->bind_param(
                    "sssis",
                    $row['item_code'],
                    $row['name'],
                    $category,
                    $user_id,
                    $remarks
                );
                if (!$stmt->execute()) {
                    throw new Exception($stmt->error ?: 'Unable to save parameter row.');
                }
                $inserted_count++;
            }

            $duplicate_stmt->close();
            $stmt->close();
            $conn->commit();

            $message = $inserted_count . ' parameter row(s) added successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error adding parameter: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

$parameter_items = [];
$parameter_query = "SELECT inventory_id, item_code, item_name, category, created_at, updated_at
                    FROM fdss_Inventory_Management
                    WHERE user_id = ?
                    AND category IN ('FDSSPARA', 'FSDSPARA')
                    ORDER BY inventory_id DESC";
$parameter_stmt = $conn->prepare($parameter_query);
$parameter_stmt->bind_param("i", $user_id);
$parameter_stmt->execute();
$parameter_result = $parameter_stmt->get_result();
while ($row = $parameter_result->fetch_assoc()) {
    $parameter_items[] = $row;
}
$parameter_stmt->close();

$next_item_code = generate_next_item_code($conn, $item_code_prefix);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Parameter - FDSS Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        .parameter-table input {
            min-width: 220px;
        }
        .parameter-row-count {
            width: 64px;
            min-width: 64px;
            text-align: center;
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
                <h1>Add Parameter</h1>
                <p class="page-header-subtitle">Add FDSSPARA and FSDSPARA parameter rows</p>
            </div>
            <div class="page-header-actions">
                <a href="inventory.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Inventory
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo e($message_type); ?> alert-dismissible fade show" role="alert">
                <?php echo e($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-sliders"></i> Parameter Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="parameterForm">
                    <input type="hidden" name="action" value="add_parameters">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Select Category <span class="text-danger">*</span></label>
                            <select class="form-select" name="category" required>
                                <option value="FDSSPARA">FDSS Parameter</option>
                                <option value="FSDSPARA">FSDS Parameter</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover parameter-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="parameter-row-count">#</th>
                                    <th>Item Code</th>
                                    <th>Parameter Name</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="parameterRows"></tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
                            <i class="bi bi-plus-circle"></i> Add Row
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-save"></i> Save Parameters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="content-card mt-4">
            <div class="card-header">
                <h5><i class="bi bi-list-check"></i> Saved Parameters (<?php echo count($parameter_items); ?> Total)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Parameter Name</th>
                                <th>Category</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($parameter_items)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No parameters added yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($parameter_items as $parameter): ?>
                                    <tr>
                                        <td><?php echo e($parameter['item_code']); ?></td>
                                        <td><?php echo e($parameter['item_name']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $parameter['category'] === 'FDSSPARA' ? 'badge-success' : 'badge-info'; ?>">
                                                <?php echo e($parameter['category']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $parameter['created_at'] ? e(date('Y-m-d h:i A', strtotime($parameter['created_at']))) : '-'; ?>
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

<?php include('includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/layout.js"></script>
<script>
const parameterRows = document.getElementById('parameterRows');
const addRowBtn = document.getElementById('addRowBtn');
const nextItemCode = <?php echo json_encode($next_item_code); ?>;

function getSuggestedItemCode(index) {
    const match = nextItemCode.match(/^(.*?)(\d+)$/);
    if (!match) {
        return '';
    }

    const nextNumber = Number(match[2]) + index;
    return match[1] + String(nextNumber).padStart(match[2].length, '0');
}

function updateRowNumbers() {
    Array.from(parameterRows.querySelectorAll('tr')).forEach(function (row, index) {
        row.querySelector('.parameter-row-count').textContent = index + 1;
    });
}

function addParameterRow() {
    const row = document.createElement('tr');
    const rowIndex = parameterRows.querySelectorAll('tr').length;
    row.innerHTML = `
        <td class="parameter-row-count align-middle"></td>
        <td>
            <input type="text" class="form-control" name="item_code[]" value="${getSuggestedItemCode(rowIndex)}" placeholder="Enter item code" required>
        </td>
        <td>
            <input type="text" class="form-control" name="parameter_name[]" placeholder="Enter parameter name" required>
        </td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    parameterRows.appendChild(row);
    updateRowNumbers();
}

addRowBtn.addEventListener('click', addParameterRow);

parameterRows.addEventListener('click', function (event) {
    const removeButton = event.target.closest('.remove-row-btn');
    if (!removeButton) {
        return;
    }

    if (parameterRows.querySelectorAll('tr').length === 1) {
        const row = removeButton.closest('tr');
        row.querySelector('input[name="item_code[]"]').value = getSuggestedItemCode(0);
        row.querySelector('input[name="parameter_name[]"]').value = '';
        return;
    }

    removeButton.closest('tr').remove();
    updateRowNumbers();
});

addParameterRow();
</script>
</body>
</html>

<?php
$conn->close();
?>
