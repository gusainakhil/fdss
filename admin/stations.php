<?php
require_once __DIR__ . '/_auth.php';

$active_page = 'stations';
$message = '';
$message_type = '';

function admin_division_matches_zone($conn, $division_id, $zone_id)
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM fdss_divisions WHERE division_id = ? AND zone_id = ?");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ii', $division_id, $zone_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return (int) ($row['total'] ?? 0) > 0;
}

$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($request_method === 'POST') {
    $action = $_POST['action'] ?? '';
    $zone_id = (int) ($_POST['zone_id'] ?? 0);
    $division_id = (int) ($_POST['division_id'] ?? 0);
    $station_name = trim($_POST['station_name'] ?? '');

    if ($zone_id <= 0 || $division_id <= 0 || $station_name === '') {
        $message = 'Please select zone, division and enter station name.';
        $message_type = 'danger';
    } elseif (!admin_division_matches_zone($conn, $division_id, $zone_id)) {
        $message = 'Selected division does not belong to selected zone.';
        $message_type = 'danger';
    } elseif ($action === 'add_station') {
        $stmt = $conn->prepare("INSERT INTO fdss_stations (station_name, division_id, status) VALUES (?, ?, 'Active')");

        if ($stmt) {
            $stmt->bind_param('si', $station_name, $division_id);

            if ($stmt->execute()) {
                $message = 'Station added successfully.';
                $message_type = 'success';
            } else {
                $message = 'Unable to add station. It may already exist in this division.';
                $message_type = 'danger';
            }

            $stmt->close();
        }
    } elseif ($action === 'edit_station') {
        $station_id = (int) ($_POST['station_id'] ?? 0);

        if ($station_id <= 0) {
            $message = 'Invalid station selected.';
            $message_type = 'danger';
        } else {
            $stmt = $conn->prepare("UPDATE fdss_stations SET station_name = ?, division_id = ? WHERE station_id = ?");

            if ($stmt) {
                $stmt->bind_param('sii', $station_name, $division_id, $station_id);

                if ($stmt->execute()) {
                    $message = 'Station updated successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Unable to update station. It may already exist in this division.';
                    $message_type = 'danger';
                }

                $stmt->close();
            }
        }
    }
}

$zones = [];
$result = $conn->query("SELECT zone_id, zone_name FROM fdss_zones ORDER BY zone_name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $zones[] = $row;
    }
}

$divisions = [];
$result = $conn->query("SELECT division_id, division_name, zone_id FROM fdss_divisions ORDER BY division_name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $divisions[] = $row;
    }
}

$stations = [];
$sql = "SELECT
            st.station_id,
            st.station_name,
            st.division_id,
            st.status,
            st.created_at,
            d.division_name,
            d.zone_id,
            z.zone_name
        FROM fdss_stations st
        INNER JOIN fdss_divisions d ON d.division_id = st.division_id
        INNER JOIN fdss_zones z ON z.zone_id = d.zone_id
        ORDER BY z.zone_name ASC, d.division_name ASC, st.station_name ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stations[] = $row;
    }
}

$division_json = json_encode($divisions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stations - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
    <?php include __DIR__ . '/_styles.php'; ?>
</head>
<body class="admin-page-body">
<?php include __DIR__ . '/navbar.php'; ?>

<div class="admin-shell">
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <div>
                <h1>Stations</h1>
                <p class="page-header-subtitle">Create and edit railway stations by zone and division</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo e($message_type); ?> alert-dismissible fade show">
                <?php echo e($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-building-add"></i> Add Station</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3 align-items-end station-form">
                    <input type="hidden" name="action" value="add_station">
                    <div class="col-lg-4">
                        <label class="form-label">Zone</label>
                        <select class="form-select zone-select" name="zone_id" required>
                            <option value="">Select Zone</option>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?php echo e($zone['zone_id']); ?>"><?php echo e($zone['zone_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Division</label>
                        <select class="form-select division-select" name="division_id" required disabled>
                            <option value="">Select Division</option>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Station Name</label>
                        <input type="text" class="form-control" name="station_name" placeholder="Enter station name" required>
                    </div>
                    <div class="col-lg-2">
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-check2"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-buildings"></i> Station List</h5>
                <span class="text-muted small"><?php echo count($stations); ?> records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Station</th>
                            <th>Division</th>
                            <th>Zone</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($stations)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No stations found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($stations as $station): ?>
                                <tr>
                                    <td><strong><?php echo e($station['station_name']); ?></strong></td>
                                    <td><?php echo e($station['division_name']); ?></td>
                                    <td><?php echo e($station['zone_name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $station['status'] === 'Active' ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                            <?php echo e($station['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e(date('d M Y', strtotime($station['created_at']))); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#editStationModal<?php echo e($station['station_id']); ?>">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
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

<?php foreach ($stations as $station): ?>
    <div class="modal fade" id="editStationModal<?php echo e($station['station_id']); ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" class="station-form">
                    <input type="hidden" name="action" value="edit_station">
                    <input type="hidden" name="station_id" value="<?php echo e($station['station_id']); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Station</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Zone</label>
                                <select class="form-select zone-select" name="zone_id" required>
                                    <option value="">Select Zone</option>
                                    <?php foreach ($zones as $zone): ?>
                                        <option value="<?php echo e($zone['zone_id']); ?>" <?php echo (int) $station['zone_id'] === (int) $zone['zone_id'] ? 'selected' : ''; ?>>
                                            <?php echo e($zone['zone_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Division</label>
                                <select class="form-select division-select" name="division_id" data-selected-division="<?php echo e($station['division_id']); ?>" required>
                                    <option value="">Select Division</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Station Name</label>
                                <input type="text" class="form-control" name="station_name" value="<?php echo e($station['station_name']); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2"></i> Update Station
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
    const adminDivisions = <?php echo $division_json ?: '[]'; ?>;

    function populateDivisionSelect(form) {
        const zoneSelect = form.querySelector('.zone-select');
        const divisionSelect = form.querySelector('.division-select');

        if (!zoneSelect || !divisionSelect) {
            return;
        }

        const selectedZone = zoneSelect.value;
        const selectedDivision = divisionSelect.dataset.selectedDivision || '';
        divisionSelect.innerHTML = '<option value="">Select Division</option>';
        divisionSelect.disabled = selectedZone === '';

        adminDivisions
            .filter((division) => String(division.zone_id) === String(selectedZone))
            .forEach((division) => {
                const option = document.createElement('option');
                option.value = division.division_id;
                option.textContent = division.division_name;

                if (String(division.division_id) === String(selectedDivision)) {
                    option.selected = true;
                }

                divisionSelect.appendChild(option);
            });
    }

    document.querySelectorAll('.station-form').forEach((form) => {
        populateDivisionSelect(form);

        const zoneSelect = form.querySelector('.zone-select');
        const divisionSelect = form.querySelector('.division-select');

        if (zoneSelect && divisionSelect) {
            zoneSelect.addEventListener('change', () => {
                divisionSelect.dataset.selectedDivision = '';
                populateDivisionSelect(form);
            });
        }
    });
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/layout.js"></script>
</body>
</html>
<?php $conn->close(); ?>
