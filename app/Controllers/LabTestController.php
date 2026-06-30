<?php
class LabTestController extends Controller {

    public function index(): void {
        requireLogin();
        requirePermission('lab_tests', 'view');
        $conn = db();

        // Detect optional columns
        $fileColumn = null; $hasFilePathColumn = false; $hasReportFileColumn = false;
        $c1 = $conn->query("SHOW COLUMNS FROM lab_tests LIKE 'file_path'");
        if ($c1 && $c1->num_rows > 0) { $hasFilePathColumn = true; $fileColumn = 'file_path'; }
        $c2 = $conn->query("SHOW COLUMNS FROM lab_tests LIKE 'report_file'");
        if ($c2 && $c2->num_rows > 0) { $hasReportFileColumn = true; if (!$fileColumn) $fileColumn = 'report_file'; }
        $hasFilePath = $fileColumn !== null;
        if ($hasFilePathColumn && $hasReportFileColumn)  $filePathSelect = "COALESCE(lt.file_path, lt.report_file) AS file_path";
        elseif ($hasFilePathColumn)                      $filePathSelect = "lt.file_path AS file_path";
        elseif ($hasReportFileColumn)                    $filePathSelect = "lt.report_file AS file_path";
        else                                             $filePathSelect = "'' AS file_path";

        $hasUploadedBy = false;
        $c3 = $conn->query("SHOW COLUMNS FROM lab_tests LIKE 'uploaded_by'");
        if ($c3 && $c3->num_rows > 0) $hasUploadedBy = true;

        $hasVisitId = false; $visitIdNullable = false;
        $c4 = $conn->query("SHOW COLUMNS FROM lab_tests LIKE 'visit_id'");
        if ($c4 && $c4->num_rows > 0) {
            $hasVisitId = true;
            $cinfo = $c4->fetch_assoc();
            $visitIdNullable = isset($cinfo['Null']) && strtoupper($cinfo['Null']) === 'YES';
            if (!$visitIdNullable) { $conn->query("ALTER TABLE lab_tests MODIFY COLUMN visit_id INT DEFAULT NULL"); $visitIdNullable = true; }
        }

        // POST: save
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_lab_test') {
            requirePermission('lab_tests', 'insert');
            $patient_id   = (int)($_POST['patient_id'] ?? 0);
            $test_name    = trim($_POST['test_name'] ?? '');
            $result_status = trim($_POST['result_status'] ?? 'Pending');
            $result_notes = trim($_POST['result_notes'] ?? '');
            $doctor_id    = (int)($_POST['doctor_id'] ?? $_SESSION['user_id']);
            $uploaded_by  = (int)$_SESSION['user_id'];

            $allowedStatuses = ['Pending','In Progress','Completed','Clear','Reactive'];
            $col = $conn->query("SHOW COLUMNS FROM lab_tests LIKE 'result_status'");
            if ($col && $col->num_rows > 0) { $r = $col->fetch_assoc(); if (isset($r['Type']) && stripos($r['Type'],'enum(') === 0) { preg_match_all("/'([^']+)'/", $r['Type'], $m); if (!empty($m[1])) $allowedStatuses = $m[1]; } }
            if (!in_array($result_status, $allowedStatuses, true)) $result_status = $allowedStatuses[0];

            $visit_id = (!empty($_POST['visit_id']) && (int)$_POST['visit_id'] > 0) ? (int)$_POST['visit_id'] : null;
            $useVisitId = $hasVisitId && $visit_id !== null;

            if ($patient_id && $test_name) {
                $file_path = null;
                if (!empty($_FILES['test_file']['name'])) {
                    $file = $_FILES['test_file'];
                    $allowed = ['application/pdf','image/jpeg','image/png','image/gif'];
                    if (in_array($file['type'], $allowed) && $file['size'] <= 5242880) {
                        $uploadDir = UPLOAD_DIR . 'lab_tests/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        $file_name = time() . '_' . basename($file['name']);
                        $file_path = 'uploads/lab_tests/' . $file_name;
                        move_uploaded_file($file['tmp_name'], $uploadDir . $file_name);
                    }
                }

                if ($hasFilePath && $hasUploadedBy && $useVisitId) {
                    $stmt = $conn->prepare("INSERT INTO lab_tests (patient_id,doctor_id,visit_id,test_name,result_status,result_notes,{$fileColumn},uploaded_by) VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->bind_param('iiissssi', $patient_id,$doctor_id,$visit_id,$test_name,$result_status,$result_notes,$file_path,$uploaded_by);
                } elseif ($hasFilePath && $hasUploadedBy && !$useVisitId) {
                    $stmt = $conn->prepare("INSERT INTO lab_tests (patient_id,doctor_id,test_name,result_status,result_notes,{$fileColumn},uploaded_by) VALUES (?,?,?,?,?,?,?)");
                    $stmt->bind_param('iissssi', $patient_id,$doctor_id,$test_name,$result_status,$result_notes,$file_path,$uploaded_by);
                } elseif ($hasFilePath && !$hasUploadedBy) {
                    $stmt = $conn->prepare("INSERT INTO lab_tests (patient_id,doctor_id,test_name,result_status,result_notes,{$fileColumn}) VALUES (?,?,?,?,?,?)");
                    $stmt->bind_param('iissss', $patient_id,$doctor_id,$test_name,$result_status,$result_notes,$file_path);
                } else {
                    $stmt = $conn->prepare('INSERT INTO lab_tests (patient_id,doctor_id,test_name,result_status,result_notes) VALUES (?,?,?,?,?)');
                    $stmt->bind_param('iisss', $patient_id,$doctor_id,$test_name,$result_status,$result_notes);
                }
                $stmt->execute();
                setFlash('success', 'Lab test recorded successfully.');
                header('Location: ' . BASE_URL . '/lab-tests');
                exit;
            }
        }

        // POST: delete
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_lab_test') {
            requirePermission('lab_tests', 'delete');
            $test_id = (int)($_POST['test_id'] ?? 0);
            if ($test_id) {
                if ($hasFilePath) {
                    $res = $conn->query("SELECT {$fileColumn} AS file_path FROM lab_tests WHERE id = $test_id");
                    if ($res && $row = $res->fetch_assoc()) {
                        if (!empty($row['file_path']) && file_exists($row['file_path'])) @unlink($row['file_path']);
                    }
                }
                $stmt = $conn->prepare('DELETE FROM lab_tests WHERE id = ?');
                $stmt->bind_param('i', $test_id);
                $stmt->execute();
                setFlash('success', 'Lab test deleted.');
                header('Location: ' . BASE_URL . '/lab-tests');
                exit;
            }
        }

        // POST: update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_lab_test') {
            requirePermission('lab_tests', 'update');
            $test_id = (int)($_POST['test_id'] ?? 0);
            $result_status = trim($_POST['result_status'] ?? 'Pending');
            $result_notes  = trim($_POST['result_notes'] ?? '');
            if ($test_id) {
                $stmt = $conn->prepare('UPDATE lab_tests SET result_status = ?, result_notes = ? WHERE id = ?');
                $stmt->bind_param('ssi', $result_status, $result_notes, $test_id);
                $stmt->execute();
                setFlash('success', 'Lab test updated.');
                header('Location: ' . BASE_URL . '/lab-tests');
                exit;
            }
        }

        // GET: list
        $search = trim($_GET['q'] ?? '');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;
        $where = '';
        if ($search !== '') {
            $like = '%' . $conn->real_escape_string($search) . '%';
            $where = "WHERE p.full_name LIKE '$like' OR lt.test_name LIKE '$like'";
        }
        $total      = $conn->query("SELECT COUNT(*) AS c FROM lab_tests lt JOIN patients p ON lt.patient_id = p.id $where")->fetch_assoc()['c'] ?? 0;
        $rows       = $conn->query("SELECT lt.*, p.full_name, p.file_number, u.full_name AS doctor_name, {$filePathSelect} FROM lab_tests lt JOIN patients p ON lt.patient_id = p.id LEFT JOIN users u ON lt.doctor_id = u.id $where ORDER BY lt.created_at DESC LIMIT $perPage OFFSET $offset");
        $totalPages = max(1, ceil($total / $perPage));
        $patients_result = $conn->query("SELECT id, full_name FROM patients ORDER BY full_name");

        $this->render('lab_tests/index', [
            'pageTitle'       => 'Lab Tests',
            'pageSubtitle'    => 'Manage and track patient laboratory test results.',
            'activePage'      => 'Lab Tests',
            'rows'            => $rows,
            'total'           => $total,
            'totalPages'      => $totalPages,
            'page'            => $page,
            'search'          => $search,
            'fileColumn'      => $fileColumn,
            'hasFilePath'     => $hasFilePath,
            'patients_result' => $patients_result,
            'conn'            => $conn,
        ]);
    }
}
