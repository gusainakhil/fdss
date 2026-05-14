<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - FDSS Dashboard</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">

    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            * {
                color: #000 !important;
                background: #fff !important;
                box-shadow: none !important;
                text-shadow: none !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            #navbar-placeholder,
            #sidebar-placeholder,
            #footer-placeholder,
            .page-header,
            .no-print {
                display: none !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
            }

            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }

            .content-card {
                border: 1px solid #000 !important;
                margin-bottom: 8px !important;
            }

            .report-title-strip {
                border: 1px solid #000 !important;
                border-bottom: none !important;
                font-size: 14px !important;
                padding: 8px 10px !important;
                text-align: center !important;
            }

            .report-grid {
                font-size: 10px !important;
            }

            .report-grid th,
            .report-grid td {
                border: 1px solid #000 !important;
                padding: 4px 6px !important;
            }
        }

        .report-filter-card {
            border-top: 3px solid #222;
        }

        .report-title-strip {
            background: #fff;
            color: #111;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            border: 1px solid #1d1d1d;
            border-bottom: none;
            padding: 10px 14px;
            font-size: 1.05rem;
            text-align: center;
        }

        .report-grid {
            font-size: 12px;
            margin-bottom: 0;
        }

        .report-grid thead th {
            background: #f4f4f4;
            color: #111;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
            border: 1px solid #2f2f2f;
        }

        .report-grid td {
            vertical-align: middle;
            white-space: nowrap;
            border: 1px solid #2f2f2f;
        }

        .report-grid td:last-child,
        .report-grid th:last-child {
            min-width: 220px;
            white-space: normal;
        }

        .ok-cell,
        .na-cell {
            color: #000;
            font-weight: 700;
        }

        .not-ok-cell {
            color: #000;
            font-weight: 700;
            background: #e9e9e9;
        }

        .report-summary strong {
            color: #111;
        }
    </style>
</head>
<body>
<?php include('includes/navbar.php'); ?>
<!-- <div id="navbar-placeholder"></div> -->

<div class="sidebar-container">
    <!-- <div id="sidebar-placeholder"></div> -->
    <?php include('includes/sidebar.php'); ?>

        <main class="main-content">
            <div class="page-header">
                <div>
                    <h1>Reports</h1>
                    <p class="page-header-subtitle">
                        <a href="index.php" class="text-primary text-decoration-none">Dashboard</a>
                        <span class="text-muted"> / </span>FDSS/FSDS Reports
                    </p>
                </div>
            </div>

            <div class="content-card report-filter-card no-print">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Select Date</label>
                            <input type="date" class="form-control" id="reportDate">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Report Type</label>
                            <select class="form-select" id="reportType">
                                <option value="FDSS">FDSS</option>
                                <option value="FSDS">FSDS</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" id="btnGenerate">
                                <i class="bi bi-funnel"></i> Generate Report
                            </button>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <button class="btn btn-outline-secondary w-100" onclick="window.print()">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-body d-flex flex-wrap justify-content-between gap-2 report-summary">
                    <div>
                        <strong>Selected Type:</strong>
                        <span class="badge badge-info ms-1" id="selectedType">FDSS</span>
                    </div>
                    <div>
                        <strong>Date:</strong>
                        <span id="selectedDate">-</span>
                    </div>
                    <div>
                        <strong>Total Records:</strong>
                        <span id="recordCount">0</span>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="report-title-strip" id="reportTitleStrip">
                    STATUS OF FDSS WORKING IN SMVB COACHING DEPOT
                </div>
                <div class="table-responsive">
                    <table class="table report-grid" id="reportTable">
                        <thead>
                            <tr>
                                <th>SL.</th>
                                <th>Train No.</th>
                                <th>Coach Type</th>
                                <th>Make</th>
                                <th>Hooter</th>
                                <th>Flasher Light</th>
                                <th>Smoke Sensor (GEN)</th>
                                <th>Smoke Sensor (CREW)</th>
                                <th>Smoke Sensor (GUARD)</th>
                                <th>Heat Sensor</th>
                                <th>Heat Detection Test</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody id="reportTableBody"></tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

           <?php include('includes/footer.php'); ?>

    <!-- <div id="footer-placeholder"></div> -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/layout.js"></script>

    <script>
        const reportData = {
            FDSS: [
                { train: '12295(1)', coach: 'SW LWLRRM', make: 'JK EXIM', hooter: 'OK', flasher: 'OK', smokeGen: 'OK', smokeCrew: 'OK', smokeGuard: 'OK', heat: 'OK', heatTest: 'OK', remarks: '' },
                { train: '12295(3)', coach: 'SW LWCBAC', make: 'SANROK', hooter: 'OK', flasher: 'OK', smokeGen: 'OK', smokeCrew: 'Defect', smokeGuard: 'OK', heat: 'OK', heatTest: 'NA', remarks: 'Heat sensor defect' },
                { train: '12295(4)', coach: 'SW LWLRRM', make: 'SANROK', hooter: 'OK', flasher: 'OK', smokeGen: 'OK', smokeCrew: 'OK', smokeGuard: 'OK', heat: 'OK', heatTest: 'OK', remarks: 'Nitrogen pressure low' },
                { train: '16585(2)', coach: 'SW LWLRRM', make: 'JK EXIM', hooter: 'OK', flasher: 'OK', smokeGen: 'OK', smokeCrew: 'OK', smokeGuard: 'OK', heat: 'OK', heatTest: 'NOT OK', remarks: 'Master board/display board issue' },
                { train: '06523', coach: 'SW LWLRRM', make: 'JK EXIM', hooter: 'OK', flasher: 'OK', smokeGen: 'OK', smokeCrew: 'OK', smokeGuard: 'OK', heat: 'OK', heatTest: 'NOT OK', remarks: 'DG tripping not happening' }
            ],
            FSDS: [
                { train: '12995(5)', coach: 'SW LWLRRM', make: 'JK EXIM', hooter: 'OK', flasher: 'OK', smokeGen: 'OK', smokeCrew: 'OK', smokeGuard: 'OK', heat: 'OK', heatTest: 'OK', remarks: '' },
                { train: '16597', coach: 'NR LWCBAC', make: 'SANROK', hooter: 'OK', flasher: 'OK', smokeGen: 'OK', smokeCrew: 'OK', smokeGuard: 'OK', heat: 'OK', heatTest: 'NA', remarks: '' },
                { train: '12253', coach: 'NC LWLRRM', make: 'SANROK', hooter: 'OK', flasher: 'OK', smokeGen: 'OK', smokeCrew: 'OK', smokeGuard: 'OK', heat: 'OK', heatTest: 'OK', remarks: 'Awaiting spares (battery)' },
                { train: '16223', coach: 'SW LWLRRM', make: 'JK EXIM', hooter: 'OK', flasher: 'OK', smokeGen: 'OK', smokeCrew: 'OK', smokeGuard: 'OK', heat: 'OK', heatTest: 'OK', remarks: '' },
                { train: '12295', coach: 'EC LWCBAC', make: 'SANROK', hooter: 'OK', flasher: 'OK', smokeGen: '01 broken', smokeCrew: 'OK', smokeGuard: 'OK', heat: 'OK', heatTest: 'OK', remarks: 'One smoke detector broken' }
            ]
        };

        function classifyStatus(value) {
            if (value === 'OK') return 'ok-cell';
            if (value === 'NA') return 'na-cell';
            return 'not-ok-cell';
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const [year, month, day] = dateStr.split('-');
            return `${day}-${month}-${year}`;
        }

        function renderReport() {
            const type = document.getElementById('reportType').value;
            const selectedDate = document.getElementById('reportDate').value;
            const rows = reportData[type] || [];
            const tbody = document.getElementById('reportTableBody');

            document.getElementById('selectedType').textContent = type;
            document.getElementById('selectedDate').textContent = formatDate(selectedDate);
            document.getElementById('recordCount').textContent = rows.length;
            document.getElementById('reportTitleStrip').textContent = `STATUS OF ${type} WORKING IN SMVB COACHING DEPOT`;

            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted py-4">No report data found.</td></tr>';
                return;
            }

            tbody.innerHTML = rows.map((row, index) => `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td class="text-center">${row.train}</td>
                    <td>${row.coach}</td>
                    <td class="text-center">${row.make}</td>
                    <td class="text-center ${classifyStatus(row.hooter)}">${row.hooter}</td>
                    <td class="text-center ${classifyStatus(row.flasher)}">${row.flasher}</td>
                    <td class="text-center ${classifyStatus(row.smokeGen)}">${row.smokeGen}</td>
                    <td class="text-center ${classifyStatus(row.smokeCrew)}">${row.smokeCrew}</td>
                    <td class="text-center ${classifyStatus(row.smokeGuard)}">${row.smokeGuard}</td>
                    <td class="text-center ${classifyStatus(row.heat)}">${row.heat}</td>
                    <td class="text-center ${classifyStatus(row.heatTest)}">${row.heatTest}</td>
                    <td>${row.remarks || '-'}</td>
                </tr>
            `).join('');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('reportDate').value = today;
            document.getElementById('btnGenerate').addEventListener('click', renderReport);
            renderReport();
        });
    </script>
</body>
</html>
