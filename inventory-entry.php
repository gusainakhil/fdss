<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FDSS / FSDS Entry - FDSS Dashboard</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        .empty-state {
            color: #6c7a86;
            padding: 26px 12px;
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
                <h1>FDSS / FSDS Entry</h1>
                <p class="page-header-subtitle">Add simple FDSS / FSDS inventory entries</p>
            </div>
            <div class="page-header-actions">
                <button class="btn btn-primary" id="addEntryBtn" data-bs-toggle="modal" data-bs-target="#entryModal">
                    <i class="bi bi-plus-circle"></i> Add Entry
                </button>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-boxes"></i>
                    Entry List (<span id="entryCount">0</span> Total)
                </h5>
            </div>

            <div class="card-body">
                <div class="table-wrapper">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Entry Name</th>
                                <th>Type</th>
                                <th>Serial No.</th>
                                <th>Model No.</th>
                                <th>OEM</th>
                                <th>Purchase Date</th>
                                <th>Warranty Date</th>
                                <th>Status</th>
                                <th>Added On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody id="entryTableBody">
                            <tr>
                                <td colspan="10" class="empty-state">
                                    No entry found. Click "Add Entry" to create one.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="entryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="entryForm">
                <input type="hidden" id="editingEntryId" value="">

                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="form-label">Entry Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="entryName" placeholder="Enter inventory entry" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="entryType" required>
                            <option value="FDSS">FDSS</option>
                            <option value="FSDS">FSDS</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Serial Number</label>
                        <input type="text" class="form-control" id="serialNumber" placeholder="Enter serial number">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Model Number</label>
                        <input type="text" class="form-control" id="modelNumber" placeholder="Enter model number">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">OEM</label>
                        <input type="text" class="form-control" id="oemName" placeholder="Enter OEM name">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" class="form-control" id="purchaseDate">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Warranty Date</label>
                                <input type="date" class="form-control" id="warrantyDate">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="entryStatus" required>
                            <option value="In Inventory">In Inventory</option>
                            <option value="In Use">In Use</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitEntryBtn">Add Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/layout.js"></script>

<script>
const entryModalEl = document.getElementById('entryModal');
const entryModal = bootstrap.Modal.getOrCreateInstance(entryModalEl);
const entryForm = document.getElementById('entryForm');
const addEntryBtn = document.getElementById('addEntryBtn');
const modalTitle = entryModalEl.querySelector('.modal-title');
const submitEntryBtn = document.getElementById('submitEntryBtn');
const entryTableBody = document.getElementById('entryTableBody');
const entryCount = document.getElementById('entryCount');
const entries = [];

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function resetEntryForm() {
    document.getElementById('editingEntryId').value = '';
    document.getElementById('entryName').value = '';
    document.getElementById('entryType').value = 'FDSS';
    document.getElementById('serialNumber').value = '';
    document.getElementById('modelNumber').value = '';
    document.getElementById('oemName').value = '';
    document.getElementById('purchaseDate').value = '';
    document.getElementById('warrantyDate').value = '';
    document.getElementById('entryStatus').value = 'In Inventory';

    modalTitle.textContent = 'Add Entry';
    submitEntryBtn.textContent = 'Add Entry';
}

function renderEntries() {
    entryCount.textContent = entries.length;

    if (entries.length === 0) {
        entryTableBody.innerHTML = `
            <tr>
                <td colspan="10" class="empty-state">
                    No entry found. Click "Add Entry" to create one.
                </td>
            </tr>
        `;
        return;
    }

    entryTableBody.innerHTML = entries.map((entry) => {
        const typeBadgeClass = entry.type === 'FDSS' ? 'badge-danger' : 'badge-info';
        const statusBadgeClass = entry.status === 'In Use' ? 'badge-success' : 'badge-warning';

        return `
            <tr>
                <td>${escapeHtml(entry.name)}</td>
                <td><span class="badge ${typeBadgeClass}">${escapeHtml(entry.type)}</span></td>
                <td>${escapeHtml(entry.serialNumber || '-')}</td>
                <td>${escapeHtml(entry.modelNumber || '-')}</td>
                <td>${escapeHtml(entry.oem || '-')}</td>
                <td>${escapeHtml(entry.purchaseDate || '-')}</td>
                <td>${escapeHtml(entry.warrantyDate || '-')}</td>
                <td><span class="badge ${statusBadgeClass}">${escapeHtml(entry.status)}</span></td>
                <td>${escapeHtml(entry.addedOn)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="editEntry('${entry.id}')" data-bs-toggle="modal" data-bs-target="#entryModal">
                        <i class="bi bi-pencil"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function editEntry(id) {
    const entry = entries.find((item) => item.id === id);

    if (!entry) {
        return;
    }

    document.getElementById('editingEntryId').value = entry.id;
    document.getElementById('entryName').value = entry.name;
    document.getElementById('entryType').value = entry.type;
    document.getElementById('serialNumber').value = entry.serialNumber;
    document.getElementById('modelNumber').value = entry.modelNumber;
    document.getElementById('oemName').value = entry.oem;
    document.getElementById('purchaseDate').value = entry.purchaseDate;
    document.getElementById('warrantyDate').value = entry.warrantyDate;
    document.getElementById('entryStatus').value = entry.status;

    modalTitle.textContent = 'Edit Entry';
    submitEntryBtn.textContent = 'Update Entry';
}

entryForm.addEventListener('submit', function (event) {
    event.preventDefault();

    const editingId = document.getElementById('editingEntryId').value;
    const name = document.getElementById('entryName').value.trim();
    const type = document.getElementById('entryType').value;
    const serialNumber = document.getElementById('serialNumber').value.trim();
    const modelNumber = document.getElementById('modelNumber').value.trim();
    const oem = document.getElementById('oemName').value.trim();
    const purchaseDate = document.getElementById('purchaseDate').value;
    const warrantyDate = document.getElementById('warrantyDate').value;
    const status = document.getElementById('entryStatus').value;

    if (!name) {
        document.getElementById('entryName').focus();
        return;
    }

    if (editingId) {
        const entry = entries.find((item) => item.id === editingId);

        if (entry) {
            entry.name = name;
            entry.type = type;
            entry.serialNumber = serialNumber;
            entry.modelNumber = modelNumber;
            entry.oem = oem;
            entry.purchaseDate = purchaseDate;
            entry.warrantyDate = warrantyDate;
            entry.status = status;
        }
    } else {
        entries.unshift({
            id: `entry-${Date.now()}`,
            name,
            type,
            serialNumber,
            modelNumber,
            oem,
            purchaseDate,
            warrantyDate,
            status,
            addedOn: new Date().toLocaleDateString('en-IN'),
        });
    }

    renderEntries();
    entryModal.hide();
    resetEntryForm();
});

addEntryBtn.addEventListener('click', resetEntryForm);
entryModalEl.addEventListener('hidden.bs.modal', resetEntryForm);
</script>

</body>
</html>
