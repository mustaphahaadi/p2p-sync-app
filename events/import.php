<?php
/**
 * Bulk Import / Calendar Parsing
 * Smart Reminder System
 */
$pageTitle = 'Import Academic Calendar';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/parser.php';
requireAdmin();

$step = 1;
$parsedEvents = [];
$errors = [];
$academic_year = $_POST['academic_year'] ?? date('Y') . '/' . (date('Y') + 1);
$semester = $_POST['semester'] ?? 'First Semester';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    }

    $action = $_POST['action'] ?? '';

    // STEP 1: Process Upload / Pasted Text
    if (empty($errors) && $action === 'process' && isset($_POST['import_method'])) {
        $step = 2;
        $rawText = '';

        if ($_POST['import_method'] === 'paste') {
            $rawText = $_POST['pasted_text'] ?? '';
        } elseif ($_POST['import_method'] === 'upload' && isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['file_upload']['tmp_name'];
            $fileType = mime_content_type($fileTmp);
            $fileName = $_FILES['file_upload']['name'];

            if (strpos($fileName, '.csv') !== false || $fileType === 'text/csv' || $fileType === 'text/plain') {
                $rawText = file_get_contents($fileTmp);
                // Basic CSV conversion to expected string format: "1 Title start-end"
                if (strpos($fileName, '.csv') !== false) {
                    $handle = fopen($fileTmp, "r");
                    $convertedText = "";
                    $i = 1;
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (count($data) >= 2) {
                            $convertedText .= "$i " . $data[0] . " " . $data[1] . "\n";
                            $i++;
                        }
                    }
                    fclose($handle);
                    $rawText = $convertedText;
                }
            } elseif (strpos($fileName, '.pdf') !== false || $fileType === 'application/pdf') {
                // Try using pdftotext from shell
                $cmd = "pdftotext " . escapeshellarg($fileTmp) . " - 2>/dev/null";
                $output = shell_exec($cmd);
                if ($output) {
                    $rawText = $output;
                } else {
                    $errors[] = "Failed to parse PDF. Please ensure your server supports pdftotext or use the Paste Text method instead.";
                    $step = 1;
                }
            } else {
                $errors[] = "Unsupported file format. Please upload PDF, CSV, or TXT.";
                $step = 1;
            }
        }

        if ($step === 2) {
            $parsedEvents = parseAcademicCalendarText($rawText, $academic_year, $semester);
            if (empty($parsedEvents)) {
                $errors[] = "No valid events were found in the provided input. Remember to follow the format: '[Number] [Activity Name] [Date(s) with Year]'.";
                $step = 1; // Go back to start
            }
        }
    }

    // STEP 2: Save to Database
    if (empty($errors) && $action === 'save') {
        $pdo = getDBConnection();
        $savedCount = 0;
        
        $titles = $_POST['title'] ?? [];
        $start_dates = $_POST['start_date'] ?? [];
        $end_dates = $_POST['end_date'] ?? [];
        $departments = $_POST['department'] ?? [];
        $categories = $_POST['category'] ?? [];
        $include = $_POST['include'] ?? [];

        foreach ($include as $index => $val) {
            if ($val === 'yes' && !empty($titles[$index]) && !empty($start_dates[$index])) {
                $t = sanitize($titles[$index]);
                $sDate = $start_dates[$index];
                $eDate = !empty($end_dates[$index]) ? $end_dates[$index] : null;
                $dept = sanitize($departments[$index]);
                $cat = sanitize($categories[$index]);

                $stmt = $pdo->prepare("
                    INSERT INTO events (title, event_date, end_date, academic_year, semester, department, category, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $t, $sDate, $eDate, $academic_year, $semester, $dept, $cat, $_SESSION['user_id']
                ]);
                $savedCount++;
            }
        }

        setFlash('success', "$savedCount events successfully imported!");
        redirect('events/index.php');
    }
}

// Ensure at least 1 empty row if no parsed events (for manual entry)
if ($step === 2 && empty($parsedEvents)) {
    // Should generally not hit this unless specifically requested by a completely empty submission somehow, 
    // but just in case:
     $parsedEvents[] = [
         'title' => '', 'start_date' => date('Y-m-d'), 'end_date' => '', 'academic_year' => $academic_year, 'semester' => $semester, 'raw_date' => ''
     ];
}
?>

<div class="page-content">
    <div class="page-header fade-in">
        <div class="d-flex align-items-center gap-3">
            <a href="<?= BASE_URL ?>events/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h1><i class="bi bi-cloud-arrow-up me-2" style="color:var(--primary)"></i> Bulk Import Events</h1>
                <p>Import academic calendar entries automatically.</p>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger fade-in">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $e): ?>
                    <li><?= $e ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
    <!-- STEP 1: UPLOAD / PASTE FORM -->
    <div class="row">
        <div class="col-lg-8">
            <div class="content-card fade-in">
                <div class="content-card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="process">

                        <h5 class="mb-3">1. Set Global Properties</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Academic Year</label>
                                <input type="text" name="academic_year" class="form-control" value="<?= htmlspecialchars($academic_year) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Semester</label>
                                <select name="semester" class="form-select">
                                    <option <?= $semester === 'First Semester' ? 'selected' : '' ?>>First Semester</option>
                                    <option <?= $semester === 'Second Semester' ? 'selected' : '' ?>>Second Semester</option>
                                    <option <?= $semester === 'Vacation' ? 'selected' : '' ?>>Vacation</option>
                                </select>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3">2. Choose Import Method</h5>

                        <!-- Tabs -->
                        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="pills-upload-tab" data-bs-toggle="pill" data-bs-target="#pills-upload" type="button" role="tab">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> File Upload
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="pills-paste-tab" data-bs-toggle="pill" data-bs-target="#pills-paste" type="button" role="tab">
                                    <i class="bi bi-clipboard me-1"></i> Paste Text
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="pills-tabContent">
                            <!-- Tab: Upload -->
                            <div class="tab-pane fade show active" id="pills-upload" role="tabpanel">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Upload a PDF or CSV file (Ensure format matches numeric bullets)</label>
                                    <input type="file" name="file_upload" class="form-control" accept=".pdf,.csv,.txt">
                                </div>
                                <input type="hidden" name="import_method" id="import_method_input" value="upload">
                            </div>
                            
                            <!-- Tab: Paste -->
                            <div class="tab-pane fade" id="pills-paste" role="tabpanel">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Copy and Paste the text directly from the PDF document.</label>
                                    <textarea name="pasted_text" class="form-control" rows="8" placeholder="e.g.&#10;1 Registration Begins 12th Jan 2026&#10;2 Exams 15th - 20th Feb 2026"></textarea>
                                </div>
                            </div>
                        </div>

                        <hr class="mt-4">
                        <button type="submit" class="btn btn-primary-solid px-4">
                            Process Data <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="alert alert-info" style="font-size:0.85rem;">
                <h6><i class="bi bi-info-circle me-1"></i> Format Guide</h6>
                <p>The parser expects data in the following format (often found in academic PDFs):</p>
                <code>[Number] [Activity] [Date or Date Range]</code>
                <p class="mt-2 mb-0"><strong>Examples:</strong></p>
                <ul class="mb-0 ps-3 text-muted">
                    <li>1 All Students Report on Campus 8th Jan. 2026</li>
                    <li>2 Fresh Students start lectures 19th Jan. 2026</li>
                    <li>3 Mid-Semester Exams 7th Mar. – 15th Mar. 2026</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        // Update hidden input when tabs change
        document.querySelectorAll('button[data-bs-toggle="pill"]').forEach(btn => {
            btn.addEventListener('shown.bs.tab', event => {
                const method = event.target.id === 'pills-upload-tab' ? 'upload' : 'paste';
                let input = document.getElementById('import_method_input');
                if(!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'import_method';
                    input.id = 'import_method_input';
                    document.querySelector('form').appendChild(input);
                }
                input.value = method;
            });
        });
    </script>

    <?php elseif ($step === 2): ?>
    <!-- STEP 2: PREVIEW & EDIT GRID -->
    <div class="content-card fade-in">
        <div class="content-card-header bg-light">
            <h5><i class="bi bi-table me-2"></i> Review & Save Events (<?= count($parsedEvents) ?> found)</h5>
        </div>
        <div class="content-card-body p-0">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="academic_year" value="<?= htmlspecialchars($academic_year) ?>">
                <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">

                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle mb-0" id="eventsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px"><input class="form-check-input" type="checkbox" id="checkAll" checked></th>
                                <th style="width:30%">Title</th>
                                <th style="width:15%">Start Date</th>
                                <th style="width:15%">End Date</th>
                                <th style="width:20%">Department</th>
                                <th style="width:15%">Category</th>
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="eventsBody">
                            <?php foreach ($parsedEvents as $index => $event): ?>
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input row-check" type="checkbox" name="include[<?= $index ?>]" value="yes" checked>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="title[<?= $index ?>]" value="<?= sanitize($event['title']) ?>" required>
                                    <small class="text-muted" style="font-size:0.7rem">Extracted from: <em><?= sanitize($event['raw_date']) ?></em></small>
                                </td>
                                <td>
                                    <input type="date" class="form-control form-control-sm" name="start_date[<?= $index ?>]" value="<?= $event['start_date'] ?>" required>
                                </td>
                                <td>
                                    <input type="date" class="form-control form-control-sm" name="end_date[<?= $index ?>]" value="<?= $event['end_date'] ?>">
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" name="department[<?= $index ?>]">
                                        <option value="All">All</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" name="category[<?= $index ?>]">
                                        <option value="other">Other</option>
                                        <option value="lecture">Lecture</option>
                                        <option value="exam" <?= stripos($event['title'], 'exam') !== false ? 'selected' : '' ?>>Exam</option>
                                        <option value="registration" <?= stripos($event['title'], 'registrat') !== false ? 'selected' : '' ?>>Registration</option>
                                        <option value="deadline" <?= stripos($event['title'], 'deadline') !== false ? 'selected' : '' ?>>Deadline</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm text-danger remove-row"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-top d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-primary-custom" id="addRow">
                        <i class="bi bi-plus me-1"></i> Add Manual Row
                    </button>
                    
                    <div class="d-flex gap-2">
                        <a href="import.php" class="btn btn-outline-secondary">Back</a>
                        <button type="submit" class="btn btn-primary-solid px-4">
                            <i class="bi bi-save me-1"></i> Save Selected Events
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Departments for clone -->
    <?php
        $pdo = getDBConnection();
        $deptStmt = $pdo->query("SELECT DISTINCT department FROM events ORDER BY department");
        $departments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);
    ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Populate department dropdowns dynamically in JS for simplicity (so we don't break PHP in strings)
            const deptOptions = `
                <option value="All">All Departments</option>
                <?php foreach($departments as $d): if(empty($d)) continue; ?>
                <option value="<?= sanitize($d) ?>"><?= sanitize($d) ?></option>
                <?php endforeach; ?>
                <option value="Computer Science">Computer Science</option>
                <option value="Engineering">Engineering</option>
                <option value="Business">Business</option>
            `;
            
            // Because original loop didn't have full deps logic, we update the existing selects
            document.querySelectorAll('select[name^="department"]').forEach(sel => {
                sel.innerHTML = deptOptions;
            });

            let indexCounter = <?= count($parsedEvents) ?>;

            // Check All
            document.getElementById('checkAll').addEventListener('change', function(e) {
                document.querySelectorAll('.row-check').forEach(cb => {
                    cb.checked = e.target.checked;
                });
            });

            // Remove Row
            document.getElementById('eventsBody').addEventListener('click', function(e) {
                if(e.target.closest('.remove-row')) {
                    e.target.closest('tr').remove();
                }
            });

            // Add Row
            document.getElementById('addRow').addEventListener('click', function() {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <div class="form-check">
                            <input class="form-check-input row-check" type="checkbox" name="include[${indexCounter}]" value="yes" checked>
                        </div>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" name="title[${indexCounter}]" placeholder="Event Name" required>
                    </td>
                    <td>
                        <input type="date" class="form-control form-control-sm" name="start_date[${indexCounter}]" required>
                    </td>
                    <td>
                        <input type="date" class="form-control form-control-sm" name="end_date[${indexCounter}]">
                    </td>
                    <td>
                        <select class="form-select form-select-sm" name="department[${indexCounter}]">
                            ${deptOptions}
                        </select>
                    </td>
                    <td>
                        <select class="form-select form-select-sm" name="category[${indexCounter}]">
                            <option value="other">Other</option>
                            <option value="lecture">Lecture</option>
                            <option value="exam">Exam</option>
                            <option value="registration">Registration</option>
                            <option value="deadline">Deadline</option>
                        </select>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm text-danger remove-row"><i class="bi bi-trash"></i></button>
                    </td>
                `;
                document.getElementById('eventsBody').appendChild(tr);
                indexCounter++;
            });
        });
    </script>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
