<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$selected_type = $_GET['type'] ?? 'FDSS';
$allowed_types = ['FDSS', 'FSDS'];

if (!in_array($selected_type, $allowed_types, true)) {
    $selected_type = 'FDSS';
}

$items = [];

$query = "SELECT
            im.item_code,
            im.item_name,
            im.category,
            im.status AS inventory_status,
            iu.unit_id,
            iu.serial_number,
            iu.model_number,
            iu.purchase_date,
            iu.Warranty_expire AS warranty_expire,
            iu.use_status,
            m.company_name,
            m.name AS manufacturer_contact,
            c.coach_no,
            c.coach_type,
            t.train_no,
            t.train_name
          FROM fdss_coach_inventory ci
          INNER JOIN fdds_inventory_unit iu
            ON iu.unit_id = ci.inventory_unit_id
            AND iu.user_id = ci.user_id
          INNER JOIN fdss_Inventory_Management im
            ON im.inventory_id = iu.inventory_id
            AND im.user_id = iu.user_id
          INNER JOIN fdss_train_coach c
            ON c.coach_id = ci.coach_id
            AND c.user_id = ci.user_id
          LEFT JOIN fdss_train_information t
            ON t.train_info_id = c.train_info_id
            AND t.user_id = ci.user_id
          LEFT JOIN fdss_manufacturers m
            ON m.manufacturer_id = iu.manufacturer_id
          WHERE ci.user_id = ?
            AND im.category = ?
          ORDER BY t.train_no ASC, c.coach_no ASC, im.item_name ASC, iu.unit_id ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param('is', $user_id, $selected_type);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

$stmt->close();

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FDSS / FSDS Inventory - FDSS Dashboard</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        .report-toolbar {
            display: flex;
            gap: 10px;
            align-items: end;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .report-filter {
            max-width: 260px;
        }
        .report-table th,
        .report-table td {
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }
        .print-only {
            display: none;
        }
        @media print {
            @page {
                size: A4 landscape;
                margin: 6mm;
            }
            body * {
                visibility: hidden;
            }
            body {
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            #reportPrintArea,
            #reportPrintArea * {
                visibility: visible;
            }
            #reportPrintArea {
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
            .content-card,
            .card-body {
                border: 0 !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .print-only h3 {
                font-size: 14px;
                margin: 0 0 2px;
            }
            .print-only p {
                font-size: 9px;
                margin: 0 0 5px;
            }
            table {
                width: 100% !important;
                table-layout: fixed;
                font-size: 7px;
                line-height: 1.12;
                border-collapse: collapse !important;
            }
            .report-table th,
            .report-table td {
                padding: 2px 3px !important;
                white-space: normal;
                overflow-wrap: anywhere;
                word-break: break-word;
            }
            .badge {
                font-size: 6px;
                padding: 2px 3px;
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
                <h1>FDSS / FSDS Inventory</h1>
                <p class="page-header-subtitle">Assigned FDSS and FSDS inventory details by coach</p>
            </div>
        </div>

        <div class="content-card mb-4 no-print">
            <div class="card-body">
                <div class="report-toolbar">
                    <form method="GET" class="report-filter">
                        <label for="typeFilter" class="form-label">Select Type</label>
                        <select id="typeFilter" name="type" class="form-select" onchange="this.form.submit()">
                            <option value="FDSS" <?php echo $selected_type === 'FDSS' ? 'selected' : ''; ?>>FDSS</option>
                            <option value="FSDS" <?php echo $selected_type === 'FSDS' ? 'selected' : ''; ?>>FSDS</option>
                        </select>
                    </form>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" id="printReportBtn">
                            <i class="bi bi-printer"></i> Print
                        </button>
                        <button type="button" class="btn btn-outline-success" id="exportExcelBtn">
                            <i class="bi bi-file-earmark-excel"></i> Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-boxes"></i>
                    <?php echo e($selected_type); ?> Assigned Inventory (<?php echo count($items); ?> Total)
                </h5>
            </div>

            <div class="card-body" id="reportPrintArea">
                <div class="print-only text-center mb-3">
                    <h3><?php echo e($selected_type); ?> Assigned Inventory</h3>
                    <p>Generated on <?php echo e(date('Y-m-d')); ?></p>
                </div>

                <div class="table-wrapper">
                    <table class="table table-hover table-bordered report-table" id="reportTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Item Code</th>
                                <th>Inventory Name</th>
                                <th>Serial No.</th>
                                <th>Model No.</th>
                                <th>Train</th>
                                <th>Coach No.</th>
                                <th>Coach Type</th>
                                <th>OEM</th>
                                <th>Purchase Date</th>
                                <th>Warranty Expire</th>
                                <th>Inventory Status</th>
                                <th>Use Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="14" class="text-center text-muted py-4">
                                        No assigned <?php echo e($selected_type); ?> inventory found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $idx => $item): ?>
                                    <tr>
                                        <td><?php echo $idx + 1; ?></td>
                                        <td>
                                            <span class="badge <?php echo $item['category'] === 'FDSS' ? 'bg-danger' : 'bg-info'; ?>">
                                                <?php echo e($item['category']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo e($item['item_code']); ?></td>
                                        <td><?php echo e($item['item_name']); ?></td>
                                        <td><?php echo e($item['serial_number'] ?: '-'); ?></td>
                                        <td><?php echo e($item['model_number'] ?: '-'); ?></td>
                                        <td>
                                            <?php echo !empty($item['train_no'])
                                                ? e($item['train_no'] . ' - ' . $item['train_name'])
                                                : 'Detached'; ?>
                                        </td>
                                        <td><?php echo e($item['coach_no']); ?></td>
                                        <td><?php echo e($item['coach_type'] ?: '-'); ?></td>
                                        <td>
                                            <?php
                                                $manufacturer = trim(($item['company_name'] ?? '') . (!empty($item['manufacturer_contact']) ? ' - ' . $item['manufacturer_contact'] : ''));
                                                echo e($manufacturer ?: '-');
                                            ?>
                                        </td>
                                        <td><?php echo e($item['purchase_date'] ?: '-'); ?></td>
                                        <td>
                                            <?php echo !empty($item['warranty_expire'])
                                                ? e(date('Y-m-d', strtotime($item['warranty_expire'])))
                                                : '-'; ?>
                                        </td>
                                        <td><?php echo e($item['inventory_status'] ?: '-'); ?></td>
                                        <td>
                                            <?php $is_used = (int) ($item['use_status'] ?? 0) === 1; ?>
                                            <span class="badge <?php echo $is_used ? 'bg-secondary' : 'bg-success'; ?>">
                                                <?php echo $is_used ? 'Used' : 'In_inventory'; ?>
                                            </span>
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
const printReportBtn = document.getElementById('printReportBtn');
const exportExcelBtn = document.getElementById('exportExcelBtn');

function exportReportExcel() {
    const sourceTable = document.getElementById('reportTable');

    if (!sourceTable) {
        return;
    }

    const workbookHtml = `
        <html>
            <head>
                <meta charset="UTF-8">
            </head>
            <body>
                <h3><?php echo e($selected_type); ?> Assigned Inventory</h3>
                ${sourceTable.outerHTML}
            </body>
        </html>
    `;
    const blob = new Blob([workbookHtml], {
        type: 'application/vnd.ms-excel;charset=utf-8;'
    });
    const link = document.createElement('a');
    const date = new Date().toISOString().slice(0, 10);

    link.href = URL.createObjectURL(blob);
    link.download = `<?php echo strtolower(e($selected_type)); ?>-assigned-inventory-${date}.xls`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
}

printReportBtn?.addEventListener('click', () => {
    window.print();
});

exportExcelBtn?.addEventListener('click', exportReportExcel);
</script>

</body>
</html>

<?php
$conn->close();
?>
