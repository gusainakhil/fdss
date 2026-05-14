<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditor Inspection Report - FDSS Dashboard</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="assets/css/styles.css" rel="stylesheet">

    <style>
        /* ── Print styles ── */
        @media print {
            /* Reset entire page to B&W */
            * {
                color: #000 !important;
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                box-shadow: none !important;
                text-shadow: none !important;
            }

            /* Hide everything except the report */
            body * { visibility: hidden; }
            #printSection, #printSection * { visibility: visible; }

            /* Page setup */
            @page {
                size: A4 portrait;
                margin: 15mm 12mm 15mm 12mm;
            }

            body {
                padding: 0 !important;
                margin: 0 !important;
                font-family: 'Times New Roman', Times, serif !important;
                font-size: 11pt !important;
            }

            /* Position the report to fill the page */
            #printSection {
                position: fixed;
                top: 0; left: 0;
                width: 100%;
                padding: 0;
                border: none !important;
            }

            #printSection .card-body {
                padding: 0 !important;
            }

            /* Hide non-print elements */
            .no-print,
            #navbar-placeholder,
            #sidebar-placeholder,
            #footer-placeholder,
            .sidebar-container > .main-content > .page-header,
            #listSection,
            #reportSection > div:last-child { display: none !important; }

            /* ── Report header ── */
            .report-header-title {
                font-size: 18pt !important;
                font-weight: bold !important;
                letter-spacing: 2px !important;
                border-bottom: 2px solid #000 !important;
            }

            /* Emblem/logo row (if any) */
            .report-logo { display: block !important; }

            /* ── Info box ── */
            .report-info-box {
                border: 1.5px solid #000 !important;
                width: 100% !important;
                margin-bottom: 10px !important;
            }
            .report-info-box td {
                font-size: 10pt !important;
                padding: 5px 10px !important;
                border: none !important;
            }
            .report-info-box td:first-child {
                border-right: 1px solid #000 !important;
            }

            /* ── Section title ── */
            .section-title {
                font-size: 10pt !important;
                font-weight: bold !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                border-bottom: 1.5px solid #000 !important;
                padding-bottom: 3px !important;
                margin: 12px 0 6px !important;
            }

            /* ── Checklist table ── */
            .inspection-table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 9.5pt !important;
            }
            .inspection-table th {
                background: #e0e0e0 !important;
                border: 1px solid #000 !important;
                font-weight: bold !important;
                padding: 5px 6px !important;
                text-align: center !important;
            }
            .inspection-table td {
                border: 1px solid #000 !important;
                padding: 5px 6px !important;
                vertical-align: middle !important;
            }

            /* Condition badges → plain B&W boxes */
            .badge-ok, .badge-issue, .badge-na {
                background: #fff !important;
                color: #000 !important;
                border: 1px solid #000 !important;
                padding: 2px 8px !important;
                font-weight: bold !important;
                font-size: 9pt !important;
                border-radius: 0 !important;
                display: inline-block !important;
            }

            /* Photo thumbnails */
            .photo-thumb {
                border: 1px solid #000 !important;
                font-size: 8pt !important;
                color: #000 !important;
                background: #f5f5f5 !important;
                width: 54px !important;
                height: 40px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            .photo-thumb.has-photo {
                background: #e0e0e0 !important;
            }

            /* ── Remarks box ── */
            #rpt-remarks {
                border: 1px solid #000 !important;
                padding: 8px !important;
                font-size: 10pt !important;
                min-height: 50px !important;
            }

            /* ── Signature table ── */
            .signature-table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-top: 4px !important;
            }
            .signature-table th {
                border: 1px solid #000 !important;
                padding: 5px !important;
                font-size: 9.5pt !important;
                font-weight: bold !important;
                background: #e0e0e0 !important;
                text-align: center !important;
            }
            .signature-table td {
                height: 70px !important;
                border: 1px solid #000 !important;
            }

            /* Page break control */
            .inspection-table { page-break-inside: avoid; }
        }

        /* ── Report card ── */
        #reportSection { display: none; }

        .report-header-title {
            font-size: 1.4rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .report-info-box {
            border: 1px solid #333;
            border-radius: 0;
        }
        .report-info-box td {
            padding: 5px 12px;
            font-size: 0.88rem;
            border: none;
        }
        .inspection-table th {
            background: #f0f0f0;
            font-size: 0.82rem;
            text-align: center;
            border: 1px solid #ccc;
        }
        .inspection-table td {
            border: 1px solid #ccc;
            font-size: 0.83rem;
            vertical-align: middle;
        }
        .section-title {
            font-weight: 700;
            font-size: 0.88rem;
            text-transform: uppercase;
            border-bottom: 1px solid #333;
            padding-bottom: 4px;
            margin: 18px 0 10px;
        }
        .signature-table th {
            background: #f5f5f5;
            font-size: 0.82rem;
            border: 1px solid #ccc;
            text-align: center;
        }
        .signature-table td {
            height: 56px;
            border: 1px solid #ccc;
        }
        .photo-thumb {
            width: 54px;
            height: 44px;
            border: 1px solid #aaa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            color: #666;
            margin: auto;
        }
        .photo-thumb.has-photo {
            background: #e8f4fd;
        }
        .badge-ok   { background: #d4edda; color: #155724; font-weight: 600; padding: 3px 10px; border-radius: 4px; }
        .badge-issue{ background: #f8d7da; color: #721c24; font-weight: 600; padding: 3px 10px; border-radius: 4px; }
        .badge-na   { background: #e2e3e5; color: #383d41; font-weight: 600; padding: 3px 10px; border-radius: 4px; }

        /* ── Today list ── */
        .today-badge {
            background: #0d6efd;
            color: #fff;
            font-size: 0.75rem;
            padding: 2px 10px;
            border-radius: 20px;
        }
        .status-inprogress { background:#fff3cd; color:#856404; border-radius:20px; padding:2px 10px; font-size:0.78rem; font-weight:600; }
        .status-completed  { background:#d1e7dd; color:#0a3622; border-radius:20px; padding:2px 10px; font-size:0.78rem; font-weight:600; }
        .status-pending    { background:#f8d7da; color:#58151c; border-radius:20px; padding:2px 10px; font-size:0.78rem; font-weight:600; }
    </style>
</head>
<body>
<?php include('includes/navbar.php'); ?>
<!-- <div id="navbar-placeholder"></div> -->

<div class="sidebar-container">
    <!-- <div id="sidebar-placeholder"></div> -->
    <?php include('includes/sidebar.php'); ?>

        <!-- MAIN CONTENT -->
        <main class="main-content">

            <!-- PAGE HEADER -->
            <div class="page-header no-print">
                <div>
                    <h1>Auditor Inspection Report</h1>
                    <p class="page-header-subtitle">
                        <a href="index.php" class="text-primary text-decoration-none">Dashboard</a>
                        <span class="text-muted"> / </span>Inspection Report
                    </p>
                </div>
            </div>

            <!-- ── FILTER CARD ── -->
            <div class="content-card no-print mb-3">
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Select Train No.</label>
                            <select class="form-select" id="filterTrain">
                                <option value="">-- All Trains --</option>
                                <option value="12423 - Rajdhani Exp">12423 - Rajdhani Exp</option>
                                <option value="12951 - Mumbai Rajdhani">12951 - Mumbai Rajdhani</option>
                                <option value="12301 - Howrah Rajdhani">12301 - Howrah Rajdhani</option>
                                <option value="12002 - Bhopal Shatabdi">12002 - Bhopal Shatabdi</option>
                                <option value="22691 - Rajdhani Express">22691 - Rajdhani Express</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Select Coach</label>
                            <select class="form-select" id="filterCoach">
                                <option value="">-- All Coaches --</option>
                                <option value="NR-204112 / B1">NR-204112 / B1</option>
                                <option value="NR-193452 / A1">NR-193452 / A1</option>
                                <option value="NR-182341 / S1">NR-182341 / S1</option>
                                <option value="NR-271823 / S3">NR-271823 / S3</option>
                                <option value="NR-301456 / H1">NR-301456 / H1</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date</label>
                            <div class="input-group">
                                <input type="date" class="form-control" id="filterDate">
                                <button class="btn btn-outline-secondary" id="btnToday" title="Select Today">
                                    <i class="bi bi-calendar-check"></i> Today
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" onclick="searchReport()">
                                <i class="bi bi-search"></i> Search Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── TODAY'S INSPECTIONS LIST ── -->
            <div id="listSection" class="no-print">
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-clipboard2-check me-2 text-primary"></i>
                            Today's Inspections
                            <span class="today-badge ms-2" id="listDateLabel">09 May 2026</span>
                        </h5>
                        <span class="text-muted" style="font-size:0.85rem" id="recordCount">Showing 4 records</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="inspectionListTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>S.No.</th>
                                        <th>Train No.</th>
                                        <th>Coach No.</th>
                                        <th>Auditor</th>
                                        <th>Date</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="inspectionListBody">
                                    <!-- Rows filled by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── DETAILED INSPECTION REPORT ── -->
            <div id="reportSection">
                <div id="printSection" class="content-card">
                    <div class="card-body">

                        <!-- Report Header -->
                        <div class="text-center mb-3" style="border-bottom:2px solid #333; padding-bottom:10px;">
                            <!-- IR wheel emblem (print-friendly SVG) -->
                            <div class="mb-1">
                                <!-- <svg width="52" height="52" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" style="display:inline-block;">
                                    <circle cx="50" cy="50" r="46" fill="none" stroke="#333" stroke-width="4"/>
                                    <circle cx="50" cy="50" r="10" fill="#333"/>
                                    
                                    <g stroke="#333" stroke-width="3">
                                        <line x1="50" y1="4" x2="50" y2="40"/>
                                        <line x1="50" y1="60" x2="50" y2="96"/>
                                        <line x1="4" y1="50" x2="40" y2="50"/>
                                        <line x1="60" y1="50" x2="96" y2="50"/>
                                        <line x1="17.2" y1="17.2" x2="40.9" y2="40.9"/>
                                        <line x1="59.1" y1="59.1" x2="82.8" y2="82.8"/>
                                        <line x1="82.8" y1="17.2" x2="59.1" y2="40.9"/>
                                        <line x1="40.9" y1="59.1" x2="17.2" y2="82.8"/>
                                        <line x1="7.7" y1="29.3" x2="38.2" y2="44.8"/>
                                        <line x1="61.8" y1="55.2" x2="92.3" y2="70.7"/>
                                        <line x1="29.3" y1="7.7" x2="44.8" y2="38.2"/>
                                        <line x1="55.2" y1="61.8" x2="70.7" y2="92.3"/>
                                    </g>
                                </svg> -->
                            </div>
                            <div class="report-header-title">Indian Railways</div>
                            <div style="font-size:1rem; font-weight:600; letter-spacing:0.5px; margin-top:2px;">Fire Detection &amp; Suppression System (FDSS)</div>
                            <div style="font-size:0.82rem; color:#555; margin-top:2px; letter-spacing:0.3px;">FDSS Equipment Inspection Report</div>
                        </div>

                        <!-- Train / Auditor Info -->
                        <div class="report-info-box mb-0">
                            <table class="w-100">
                                <tr>
                                    <td style="width:50%; border-right:1px solid #ccc;">
                                        <strong>Train No :</strong> <span id="rpt-train">12423 - Rajdhani Exp</span><br>
                                        <strong>Coach No :</strong> <span id="rpt-coach">NR-204112 / B1</span><br>
                                        <strong>Railway Zone :</strong> <span id="rpt-zone">Northern Railway</span><br>
                                        <strong>Station :</strong> <span id="rpt-station">NDLS - New Delhi Station</span>
                                    </td>
                                    <td>
                                        <strong>Auditor Name :</strong> <span id="rpt-auditor">Ramesh Kumar (AUD-001)</span><br>
                                        <strong>Date :</strong> <span id="rpt-date">09 May 2026</span><br>
                                        <strong>Start Time :</strong> <span id="rpt-start">10:15 AM</span> &nbsp;
                                        <strong>End Time :</strong> <span id="rpt-end">11:05 AM</span><br>
                                        <strong>Status :</strong> <span id="rpt-status">Completed</span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Tools Inspection Table -->
                        <div class="section-title mt-3">Tools / Equipment Inspection Checklist</div>
                        <div class="table-responsive">
                            <table class="table inspection-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">S.No.</th>
                                        <th>Tool Details</th>
                                        <th style="width:130px;">Serial No.</th>
                                        <th style="width:90px;">Condition</th>
                                        <th>Remarks</th>
                                        <th style="width:90px;">Photo Evidence</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center">1</td>
                                        <td>
                                            <strong>Hooter</strong><br>
                                            <small class="text-muted">FDSS Alert System</small>
                                        </td>
                                        <td id="s1" class="text-center">HT-2024-001</td>
                                        <td class="text-center"><span id="c1" class="badge-ok">OK</span></td>
                                        <td id="r1">Functioning properly, audible alarm tested.</td>
                                        <td><div id="p1" class="photo-thumb has-photo">Photo</div></td>
                                    </tr>
                                    <tr>
                                        <td class="text-center">2</td>
                                        <td>
                                            <strong>Flasher Light</strong><br>
                                            <small class="text-muted">FDSS Visual Alert</small>
                                        </td>
                                        <td id="s2" class="text-center">FL-2024-002</td>
                                        <td class="text-center"><span id="c2" class="badge-ok">OK</span></td>
                                        <td id="r2">Flashing correctly, no fault detected.</td>
                                        <td><div id="p2" class="photo-thumb has-photo">Photo</div></td>
                                    </tr>
                                    <tr>
                                        <td class="text-center">3</td>
                                        <td>
                                            <strong>Smoke Sensor (Genset Area)</strong><br>
                                            <small class="text-muted">FDSS Smoke Detection</small>
                                        </td>
                                        <td id="s3" class="text-center">SS-GEN-003</td>
                                        <td class="text-center"><span id="c3" class="badge-issue">Issue</span></td>
                                        <td id="r3">Blinking red error light. Needs replacement.</td>
                                        <td><div id="p3" class="photo-thumb has-photo">Photo</div></td>
                                    </tr>
                                    <tr>
                                        <td class="text-center">4</td>
                                        <td>
                                            <strong>Smoke Sensor (Crew Area)</strong><br>
                                            <small class="text-muted">FDSS Smoke Detection</small>
                                        </td>
                                        <td id="s4" class="text-center">SS-CRW-004</td>
                                        <td class="text-center"><span id="c4" class="badge-ok">OK</span></td>
                                        <td id="r4">Sensor responsive, self-test passed.</td>
                                        <td><div id="p4" class="photo-thumb has-photo">Photo</div></td>
                                    </tr>
                                    <tr>
                                        <td class="text-center">5</td>
                                        <td>
                                            <strong>Smoke Sensor (Guard Area)</strong><br>
                                            <small class="text-muted">FDSS Smoke Detection</small>
                                        </td>
                                        <td id="s5" class="text-center">SS-GRD-005</td>
                                        <td class="text-center"><span id="c5" class="badge-ok">OK</span></td>
                                        <td id="r5">No fault, clean sensor head.</td>
                                        <td><div id="p5" class="photo-thumb has-photo">Photo</div></td>
                                    </tr>
                                    <tr>
                                        <td class="text-center">6</td>
                                        <td>
                                            <strong>Heat Sensor (Genset / Kitchen Area)</strong><br>
                                            <small class="text-muted">FDSS Heat Detection</small>
                                        </td>
                                        <td id="s6" class="text-center">HS-GEN-006</td>
                                        <td class="text-center"><span id="c6" class="badge-ok">OK</span></td>
                                        <td id="r6">Temperature threshold tested and verified.</td>
                                        <td><div id="p6" class="photo-thumb has-photo">Photo</div></td>
                                    </tr>
                                    <tr>
                                        <td class="text-center">7</td>
                                        <td>
                                            <strong>Heat Detection Test – LWLRRM</strong><br>
                                            <small class="text-muted">Engine shutdown on temp raise</small>
                                        </td>
                                        <td id="s7" class="text-center">HD-LWL-007</td>
                                        <td class="text-center"><span id="c7" class="badge-na">N/A</span></td>
                                        <td id="r7">Deferred to next service cycle.</td>
                                        <td><div id="p7" class="photo-thumb">No Photo</div></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Final Remarks -->
                        <div class="section-title">Final Inspection Remarks</div>
                        <div style="border:1px solid #ccc; padding:10px; min-height:60px; font-size:0.88rem;" id="rpt-remarks">
                            Inspection completed. Smoke Sensor (Genset Area) requires replacement before next run.
                            All other FDSS components are operational.
                        </div>

                        <!-- Verification / Signature -->
                        <div class="section-title">Verification / Signature</div>
                        <table class="table signature-table mb-0">
                            <thead>
                                <tr>
                                    <th>Auditor Signature</th>
                                    <th>Supervisor Signature</th>
                                    <th>Railway Officer Signature</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>

                    </div><!-- /card-body -->
                </div><!-- /printSection -->

                <!-- Report Actions -->
                <div class="d-flex justify-content-end gap-2 mt-3 no-print">
                    <button class="btn btn-outline-secondary" onclick="goBack()">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                    <button class="btn btn-dark" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Report
                    </button>
                </div>
            </div>
            <!-- /reportSection -->

        </main>
    </div>

           <?php include('includes/footer.php'); ?>

    <!-- <div id="footer-placeholder"></div> -->

    <!-- Bootstrap 5 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

    <!-- Layout JS -->
    <script src="assets/js/layout.js"></script>

    <script>
        /* ──────────────────────────────────────────
           Sample inspection data (today = 09 May 2026)
        ────────────────────────────────────────── */
        const today = '2026-05-09';
        const allInspections = [
            {
                id: 1, train: '12423 - Rajdhani Exp', coach: 'NR-204112 / B1',
                auditor: 'Ramesh Kumar (AUD-001)', date: '2026-05-09',
                start: '10:15 AM', end: '11:05 AM', status: 'Completed',
                zone: 'Northern Railway', station: 'NDLS - New Delhi Station',
                remarks: 'Inspection completed. Smoke Sensor (Genset Area) requires replacement before next run. All other FDSS components are operational.',
                tools: [
                    { s:'HT-2024-001', c:'OK',    r:'Functioning properly, audible alarm tested.', photo:true },
                    { s:'FL-2024-002', c:'OK',    r:'Flashing correctly, no fault detected.',       photo:true },
                    { s:'SS-GEN-003', c:'Issue',  r:'Blinking red error light. Needs replacement.', photo:true },
                    { s:'SS-CRW-004', c:'OK',    r:'Sensor responsive, self-test passed.',          photo:true },
                    { s:'SS-GRD-005', c:'OK',    r:'No fault, clean sensor head.',                  photo:true },
                    { s:'HS-GEN-006', c:'OK',    r:'Temperature threshold tested and verified.',    photo:true },
                    { s:'HD-LWL-007', c:'N/A',   r:'Deferred to next service cycle.',              photo:false }
                ]
            },
            {
                id: 2, train: '12951 - Mumbai Rajdhani', coach: 'NR-193452 / A1',
                auditor: 'Sunil Verma (AUD-002)', date: '2026-05-09',
                start: '09:00 AM', end: '09:50 AM', status: 'Completed',
                zone: 'Western Railway', station: 'MMCT - Mumbai Central',
                remarks: 'All FDSS components functional. No issues found.',
                tools: [
                    { s:'HT-2024-011', c:'OK',   r:'Alarm tested OK.',                             photo:true },
                    { s:'FL-2024-012', c:'OK',   r:'Flasher operational.',                          photo:true },
                    { s:'SS-GEN-013', c:'OK',    r:'No smoke detected, sensor clean.',              photo:true },
                    { s:'SS-CRW-014', c:'OK',    r:'Self-test passed.',                             photo:true },
                    { s:'SS-GRD-015', c:'OK',    r:'Clear, no fault.',                              photo:true },
                    { s:'HS-GEN-016', c:'OK',    r:'Heat sensor within limits.',                    photo:true },
                    { s:'HD-LWL-017', c:'OK',    r:'Engine shutdown test verified.',               photo:true }
                ]
            },
            {
                id: 3, train: '12301 - Howrah Rajdhani', coach: 'NR-182341 / S1',
                auditor: 'Priya Sharma (AUD-003)', date: '2026-05-09',
                start: '11:30 AM', end: null, status: 'In Progress',
                zone: 'Eastern Railway', station: 'HWH - Howrah Junction',
                remarks: '',
                tools: [
                    { s:'HT-2024-021', c:'OK',   r:'Alarm tested.',                                photo:true },
                    { s:'FL-2024-022', c:'Issue', r:'Dim flicker, bulb may need replacement.',    photo:true },
                    { s:'SS-GEN-023', c:'OK',    r:'Functioning normally.',                        photo:false},
                    { s:'SS-CRW-024', c:'OK',    r:'OK',                                           photo:false},
                    { s:'SS-GRD-025', c:'OK',    r:'OK',                                           photo:false},
                    { s:'HS-GEN-026', c:'N/A',   r:'Pending check.',                              photo:false},
                    { s:'HD-LWL-027', c:'N/A',   r:'Pending check.',                              photo:false}
                ]
            },
            {
                id: 4, train: '12002 - Bhopal Shatabdi', coach: 'NR-271823 / S3',
                auditor: 'Anil Tiwari (AUD-004)', date: '2026-05-09',
                start: '08:00 AM', end: null, status: 'Pending',
                zone: 'North Central Railway', station: 'AGC - Agra Cantt',
                remarks: '',
                tools: [
                    { s:'HT-2024-031', c:'N/A', r:'-', photo:false},
                    { s:'FL-2024-032', c:'N/A', r:'-', photo:false},
                    { s:'SS-GEN-033', c:'N/A', r:'-', photo:false},
                    { s:'SS-CRW-034', c:'N/A', r:'-', photo:false},
                    { s:'SS-GRD-035', c:'N/A', r:'-', photo:false},
                    { s:'HS-GEN-036', c:'N/A', r:'-', photo:false},
                    { s:'HD-LWL-037', c:'N/A', r:'-', photo:false}
                ]
            },
            /* ── previous day record ── */
            {
                id: 5, train: '22691 - Rajdhani Express', coach: 'NR-301456 / H1',
                auditor: 'Deepak Nair (AUD-005)', date: '2026-05-08',
                start: '14:00 PM', end: '14:55 PM', status: 'Completed',
                zone: 'South Western Railway', station: 'SBC - KSR Bengaluru',
                remarks: 'All systems cleared.',
                tools: [
                    { s:'HT-2023-041', c:'OK',   r:'OK',                                           photo:true },
                    { s:'FL-2023-042', c:'OK',   r:'OK',                                           photo:true },
                    { s:'SS-GEN-043', c:'OK',    r:'OK',                                           photo:true },
                    { s:'SS-CRW-044', c:'OK',    r:'OK',                                           photo:true },
                    { s:'SS-GRD-045', c:'OK',    r:'OK',                                           photo:true },
                    { s:'HS-GEN-046', c:'Issue', r:'Calibration drift detected, flagged.',         photo:true },
                    { s:'HD-LWL-047', c:'OK',    r:'Engine shutdown verified.',                    photo:true }
                ]
            }
        ];

        /* ──────────────────────────────────────────
           Init: set today's date in filter
        ────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('filterDate').value = today;
            document.getElementById('btnToday').addEventListener('click', () => {
                document.getElementById('filterDate').value = today;
                searchReport();
            });
            renderList(today, '', '');
        });

        /* ──────────────────────────────────────────
           Format date nicely: "09 May 2026"
        ────────────────────────────────────────── */
        function fmtDate(d) {
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const [y,m,day] = d.split('-');
            return `${day} ${months[+m-1]} ${y}`;
        }

        /* ──────────────────────────────────────────
           Status badge HTML
        ────────────────────────────────────────── */
        function statusBadge(s) {
            if (s === 'Completed')  return `<span class="status-completed">${s}</span>`;
            if (s === 'In Progress') return `<span class="status-inprogress">${s}</span>`;
            return `<span class="status-pending">${s}</span>`;
        }

        /* ──────────────────────────────────────────
           Render the list view
        ────────────────────────────────────────── */
        function renderList(date, train, coach) {
            let filtered = allInspections.filter(r => r.date === date);
            if (train)  filtered = filtered.filter(r => r.train === train);
            if (coach)  filtered = filtered.filter(r => r.coach === coach);

            document.getElementById('listDateLabel').textContent = fmtDate(date);
            document.getElementById('recordCount').textContent = `Showing ${filtered.length} record${filtered.length !== 1 ? 's' : ''}`;

            const tbody = document.getElementById('inspectionListBody');
            if (filtered.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">No inspections found for selected filters.</td></tr>`;
                return;
            }
            tbody.innerHTML = filtered.map((r, i) => `
                <tr>
                    <td>${i + 1}</td>
                    <td><strong>${r.train}</strong></td>
                    <td>${r.coach}</td>
                    <td>${r.auditor}</td>
                    <td>${fmtDate(r.date)}</td>
                    <td>${r.start}</td>
                    <td>${r.end ?? '—'}</td>
                    <td>${statusBadge(r.status)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="viewReport(${r.id})">
                            <i class="bi bi-eye"></i> View Report
                        </button>
                    </td>
                </tr>`).join('');
        }

        /* ──────────────────────────────────────────
           Search button handler
        ────────────────────────────────────────── */
        function searchReport() {
            const date  = document.getElementById('filterDate').value || today;
            const train = document.getElementById('filterTrain').value;
            const coach = document.getElementById('filterCoach').value;

            // If specific coach chosen → show report directly
            if (coach) {
                const match = allInspections.find(r => r.date === date && r.coach === coach && (!train || r.train === train));
                if (match) { viewReport(match.id); return; }
            }

            // Otherwise show list
            document.getElementById('reportSection').style.display = 'none';
            document.getElementById('listSection').style.display   = 'block';
            renderList(date, train, coach);
        }

        /* ──────────────────────────────────────────
           View a detailed report by id
        ────────────────────────────────────────── */
        function viewReport(id) {
            const r = allInspections.find(x => x.id === id);
            if (!r) return;

            // Fill header info
            document.getElementById('rpt-train').textContent   = r.train;
            document.getElementById('rpt-coach').textContent   = r.coach;
            document.getElementById('rpt-zone').textContent    = r.zone;
            document.getElementById('rpt-station').textContent = r.station;
            document.getElementById('rpt-auditor').textContent = r.auditor;
            document.getElementById('rpt-date').textContent    = fmtDate(r.date);
            document.getElementById('rpt-start').textContent   = r.start;
            document.getElementById('rpt-end').textContent     = r.end ?? '—';
            document.getElementById('rpt-status').textContent  = r.status;
            document.getElementById('rpt-remarks').textContent = r.remarks || '—';

            // Fill tool rows
            r.tools.forEach((t, i) => {
                const n = i + 1;
                document.getElementById(`s${n}`).textContent = t.s;
                document.getElementById(`r${n}`).textContent = t.r;
                const cEl = document.getElementById(`c${n}`);
                cEl.textContent  = t.c;
                cEl.className    = t.c === 'OK' ? 'badge-ok' : t.c === 'Issue' ? 'badge-issue' : 'badge-na';
                const pEl = document.getElementById(`p${n}`);
                pEl.textContent  = t.photo ? 'Photo' : 'No Photo';
                pEl.className    = `photo-thumb${t.photo ? ' has-photo' : ''}`;
            });

            // Show/hide sections
            document.getElementById('listSection').style.display   = 'none';
            document.getElementById('reportSection').style.display = 'block';
        }

        /* ──────────────────────────────────────────
           Back button
        ────────────────────────────────────────── */
        function goBack() {
            document.getElementById('reportSection').style.display = 'none';
            document.getElementById('listSection').style.display   = 'block';
        }
    </script>
</body>
</html>
