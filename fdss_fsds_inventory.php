<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$selected_type = strtoupper(trim($_GET['type'] ?? 'FDSS'));
$allowed_types = ['FDSS', 'FSDS'];
if (!in_array($selected_type, $allowed_types, true)) {
    $selected_type = 'FDSS';
}

$items = [];

// Show all units where token_id is not blank (entered via bulk form = FDSS/FSDS)
$query = "SELECT
            im.item_code,
            im.item_name,
            im.category,
            iu.unit_id,
            iu.token_id,
            iu.serial_number,
            iu.model_number,
            iu.purchase_date,
            iu.Warranty_expire AS warranty_expire,
            iu.unit_status,
            iu.use_status,
            m.company_name,
            m.name AS manufacturer_contact,
            c.coach_no,
            c.coach_type,
            t.train_no,
            t.train_name
          FROM fdds_inventory_unit iu
          INNER JOIN fdss_Inventory_Management im
            ON im.inventory_id = iu.inventory_id
            AND im.user_id = iu.user_id
          LEFT JOIN fdss_coach_inventory ci
            ON ci.inventory_unit_id = iu.unit_id
            AND ci.user_id = iu.user_id
          LEFT JOIN fdss_train_coach c
            ON c.coach_id = ci.coach_id
            AND c.user_id = ci.user_id
          LEFT JOIN fdss_train_information t
            ON t.train_info_id = c.train_info_id
            AND t.user_id = c.user_id
          LEFT JOIN fdss_manufacturers m
            ON m.manufacturer_id = iu.manufacturer_id
          WHERE iu.user_id = ?
            AND UPPER(TRIM(im.category)) = ?
            AND iu.token_id IS NOT NULL
            AND iu.token_id != ''
          ORDER BY iu.token_id ASC, im.item_name ASC, iu.unit_id ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param('is', $user_id, $selected_type);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

// Count distinct tokens
$token_count = count(array_unique(array_column($items, 'token_id')));

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
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .report-table th,
        .report-table td {
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
            font-size: 0.72rem;
            padding: 4px 6px !important;
        }
        .token-separator td {
            background: #e8f4fd !important;
            font-weight: 600;
            font-size: 0.7rem;
            color: #0d6efd;
            letter-spacing: 0.5px;
            font-family: monospace;
            padding: 3px 6px !important;
        }
        .token-cell {
            font-family: monospace;
            font-size: 0.75rem;
            color: #0d6efd;
            letter-spacing: 1px;
        }
        .print-only { display: none; }

        @media print {
            @page { size: A4 landscape; margin: 12mm 10mm; }

            /* Hide everything except the report card */
            nav, aside, .page-header, .no-print,
            .sidebar-container > aside,
            #layoutNavbar, #layoutSidebar { display: none !important; }

            .sidebar-container { display: block !important; }
            .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .content-card { border: 0 !important; box-shadow: none !important; margin: 0 !important; padding: 0 !important; }
            .card-header { display: none !important; }
            .card-body { padding: 0 !important; }

            .print-only { display: block !important; }
            .table-wrapper { overflow: visible !important; }

            /* Print header */
            .print-header {
                display: flex !important;
                justify-content: space-between;
                align-items: flex-end;
                border-bottom: 2px solid #000;
                padding-bottom: 3mm;
                margin-bottom: 4mm;
                font-family: Arial, sans-serif;
            }
            .print-header-left h2 { font-size: 11pt; font-weight: 700; margin: 0 0 1mm; }
            .print-header-left p  { font-size: 6.5pt; margin: 0; }
            .print-header-right   { font-size: 6.5pt; text-align: right; }

            /* Table */
            #reportTable {
                width: 100% !important;
                table-layout: fixed;
                border-collapse: collapse !important;
                font-size: 5.5pt;
                font-family: Arial, sans-serif;
            }
            #reportTable th,
            #reportTable td {
                border: 1px solid #000 !important;
                padding: 1.5pt 2pt !important;
                text-align: center;
                vertical-align: middle;
                white-space: normal !important;
                word-break: break-word;
                color: #000 !important;
                background: none !important;
            }
            #reportTable thead th {
                background: none !important;
                color: #000 !important;
                font-size: 5.5pt;
                font-weight: 700;
                border-bottom: 2px solid #000 !important;
            }
            #reportTable td.text-start { text-align: left !important; }

            /* Token separator — bold top border only, no background */
            .token-separator td {
                background: none !important;
                color: #000 !important;
                font-weight: 700 !important;
                font-size: 5.5pt !important;
                border-top: 2px solid #000 !important;
                text-align: left !important;
            }

            /* Badges → plain text only */
            .badge {
                background: none !important;
                color: #000 !important;
                border: none !important;
                font-size: 5.5pt !important;
                padding: 0 !important;
                border-radius: 0 !important;
                font-weight: 600;
            }

            /* Column widths */
            #reportTable col:nth-child(1)  { width: 3%;  }  /* # */
            #reportTable col:nth-child(2)  { width: 5%;  }  /* Type */
            #reportTable col:nth-child(3)  { width: 7%;  }  /* Item Code */
            #reportTable col:nth-child(4)  { width: 13%; }  /* Item Name */
            #reportTable col:nth-child(5)  { width: 9%;  }  /* Serial */
            #reportTable col:nth-child(6)  { width: 8%;  }  /* Model */
            #reportTable col:nth-child(7)  { width: 10%; }  /* OEM */
            #reportTable col:nth-child(8)  { width: 7%;  }  /* Purchase */
            #reportTable col:nth-child(9)  { width: 7%;  }  /* Warranty */
            #reportTable col:nth-child(10) { width: 9%;  }  /* Unit Status */
            #reportTable col:nth-child(11) { width: 13%; }  /* Train */
            #reportTable col:nth-child(12) { width: 7%;  }  /* Coach No */
            #reportTable col:nth-child(13) { width: 7%;  }  /* Use Status */

            tr { page-break-inside: avoid; break-inside: avoid; }
            .token-separator { page-break-before: auto; }
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
                <p class="page-header-subtitle">All <?= e($selected_type) ?> units — <?= count($items) ?> item(s) across <?= $token_count ?> batch(es)</p>
            </div>
        </div>

        <!-- Filter + actions -->
        <div class="content-card mb-4 no-print">
            <div class="card-body">
                <div class="d-flex gap-3 align-items-end flex-wrap justify-content-between">
                    <form method="GET" class="d-flex gap-2 align-items-end">
                        <div>
                            <label class="form-label mb-1">Select Type</label>
                            <select name="type" class="form-select" style="min-width:140px" onchange="this.form.submit()">
                                <option value="FDSS" <?= $selected_type === 'FDSS' ? 'selected' : '' ?>>FDSS</option>
                                <option value="FSDS" <?= $selected_type === 'FSDS' ? 'selected' : '' ?>>FSDS</option>
                            </select>
                        </div>
                    </form>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary" id="printReportBtn"><i class="bi bi-printer"></i> Print</button>
                        <button class="btn btn-outline-success" id="exportExcelBtn"><i class="bi bi-file-earmark-excel"></i> Excel</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="content-card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-boxes"></i>
                    <?= e($selected_type) ?> Inventory
                    <span class="badge bg-secondary"><?= count($items) ?> items</span>
                    <span class="badge bg-primary"><?= $token_count ?> <?= $selected_type === 'FDSS' ? 'FDSS' : 'FSDS' ?> </span>
                </h5>
            </div>
            <div class="card-body" id="reportPrintArea">
                <!-- Print-only header -->
                <div class="print-only">
                    <div class="print-header">
                        <div class="print-header-left">
                            <h2>Indian Railways &mdash; FDSS Panel</h2>
                            <p><?= e($selected_type) ?> Inventory Report &nbsp;|&nbsp; <?= count($items) ?> Items &nbsp;|&nbsp; <?= $token_count ?> Batch(es)</p>
                        </div>
                        <div class="print-header-right">
                            Printed: <?= e(date('d-M-Y H:i')) ?>
                        </div>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="table table-hover table-bordered report-table" id="reportTable">
                        <colgroup>
                            <col><col><col><col><col>
                            <col><col><col><col><col>
                            <col><col><col>
                        </colgroup>
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Serial No.</th>
                                <th>Model No.</th>
                                <th>OEM</th>
                                <th>Purchase Date</th>
                                <th>Warranty Expire</th>
                                <th>Unit Status</th>
                                <th>Train</th>
                                <th>Coach No.</th>
                                <th>Use Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="13" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                    No <?= e($selected_type) ?> units found. Use <a href="add-fsds-fdds-inventory.php?category=<?= e($selected_type) ?>">Add Units</a> to create entries.
                                </td>
                            </tr>
                        <?php else: ?>

                            <?php
                            $prev_token = null;
                            $token_row_num = 0;
                            $global_idx = 0;
                            foreach ($items as $item):
                                // Group separator row when token changes
                                if ($item['token_id'] !== $prev_token):
                                    $prev_token = $item['token_id'];
                                    $token_row_num++;
                            ?>
                            <tr class="token-separator">
                                <td colspan="13">
                                    <i class="bi bi-tag-fill me-1"></i>
                                    <?= $selected_type === 'FDSS' ? 'FDSS' : 'FSDS' ?> <?= $token_row_num ?>: Token <?= e($item['token_id']) ?>
                                    &nbsp;&middot;&nbsp;
                                    <?= e($item['serial_number'] ?: '') ?>
                                    <?php if ($item['model_number']): ?> / <?= e($item['model_number']) ?><?php endif; ?>
                                    <?php if ($item['purchase_date']): ?> &nbsp;&middot;&nbsp; Purchased: <?= e($item['purchase_date']) ?><?php endif; ?>
                                </td>
                            </tr>
                            <?php endif; $global_idx++; ?>
                            <tr>
                                <td><?= $global_idx ?></td>
                                <td>
                                    <span class="badge <?= $item['category'] === 'FDSS' ? 'bg-danger' : 'bg-info text-dark' ?>">
                                        <?= e($item['category']) ?>
                                    </span>
                                </td>
                                <td><?= e($item['item_code']) ?></td>
                                <td class="text-start"><?= e($item['item_name']) ?></td>
                                <td><?= e($item['serial_number'] ?: '-') ?></td>
                                <td><?= e($item['model_number'] ?: '-') ?></td>
                                <td>
                                    <?php
                                        $mfg = trim(($item['company_name'] ?? '') . (!empty($item['manufacturer_contact']) ? ' - ' . $item['manufacturer_contact'] : ''));
                                        echo e($mfg ?: '-');
                                    ?>
                                </td>
                                <td><?= e($item['purchase_date'] ?: '-') ?></td>
                                <td>
                                    <?= !empty($item['warranty_expire'])
                                        ? e(date('Y-m-d', strtotime($item['warranty_expire'])))
                                        : '-' ?>
                                </td>
                                <td>
                                    <?php
                                        $us = $item['unit_status'] ?? '';
                                        $us_class = match($us) {
                                            'Working'                => 'bg-success',
                                            'Replacement'            => 'bg-warning text-dark',
                                            'Not_working'            => 'bg-danger',
                                            'warranty_claim_process' => 'bg-secondary',
                                            default                  => 'bg-light text-dark',
                                        };
                                        $us_label = match($us) {
                                            'warranty_claim_process' => 'Warranty Claim',
                                            'Not_working'            => 'Not Working',
                                            default                  => $us ?: '-',
                                        };
                                    ?>
                                    <span class="badge <?= $us_class ?>"><?= e($us_label) ?></span>
                                </td>
                                <td>
                                    <?= !empty($item['train_no'])
                                        ? e($item['train_no'] . ' — ' . $item['train_name'])
                                        : '<span class="text-muted">—</span>' ?>
                                </td>
                                <td><?= e($item['coach_no'] ?: '—') ?></td>
                                <td>
                                    <?php $in_use = (int)($item['use_status'] ?? 0) === 1; ?>
                                    <span class="badge <?= $in_use ? 'bg-secondary' : 'bg-success' ?>">
                                        <?= $in_use ? 'In Use' : 'In Stock' ?>
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
document.getElementById('printReportBtn')?.addEventListener('click', () => window.print());

document.getElementById('exportExcelBtn')?.addEventListener('click', function () {
    const table = document.getElementById('reportTable');
    if (!table) return;
    const html = `<html><head><meta charset="UTF-8"></head><body><h3><?= e($selected_type) ?> Inventory</h3>${table.outerHTML}</body></html>`;
    const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `<?= strtolower(e($selected_type)) ?>-inventory-${new Date().toISOString().slice(0,10)}.xls`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(a.href);
});
</script>
</body>
</html>
<?php $conn->close(); ?>