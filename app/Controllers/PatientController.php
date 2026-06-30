<?php
class PatientController extends Controller {

    public function index(): void {
        requireLogin();
        requirePermission('patients', 'view');
        $conn = db();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_patient') {
            $fileNumber = trim($_POST['file_number']);
            if ($fileNumber === '') {
                $res = $conn->query("SELECT MAX(id) AS maxid FROM patients");
                $next = ($res->fetch_assoc()['maxid'] ?? 0) + 1;
                $fileNumber = 'KSC-' . str_pad($next, 4, '0', STR_PAD_LEFT);
            }
            $fullName          = trim($_POST['full_name']);
            $age               = (int)$_POST['age'];
            $gender            = $_POST['gender'];
            $residence         = trim($_POST['residence']);
            $phone             = trim($_POST['phone'] ?? '');
            $email             = trim($_POST['email'] ?? '');
            $bloodType         = $_POST['blood_type'] ?? 'Unknown';
            $sulfaReactive     = (int)($_POST['sulfa_reactive_value'] ?? 0);
            $penicillinAllergy = (int)($_POST['penicillin_allergy_value'] ?? 0);
            $latexAllergy      = (int)($_POST['latex_allergy_value'] ?? 0);
            $otherAllergies    = trim($_POST['other_allergies'] ?? '');
            $registeredBy      = $_SESSION['user_id'];

            $stmt = $conn->prepare("INSERT INTO patients (file_number,full_name,age,gender,residence,phone,email,blood_type,sulfa_reactive,penicillin_allergy,latex_allergy,other_allergies,status,registered_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'Active',?)");
            $stmt->bind_param("ssisssssiiiis", $fileNumber,$fullName,$age,$gender,$residence,$phone,$email,$bloodType,$sulfaReactive,$penicillinAllergy,$latexAllergy,$otherAllergies,$registeredBy);
            $stmt->execute();
            $newPatientId = $conn->insert_id;
            $stmt->close();

            $uploadDir = UPLOAD_DIR;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            foreach (['photo_before' => 'before', 'photo_after' => 'after'] as $field => $type) {
                if (!empty($_FILES[$field]['tmp_name']) && $_FILES[$field]['error'] === 0) {
                    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
                    $fileName = 'patient_' . $newPatientId . '_' . $type . '.' . $ext;
                    if (move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $fileName)) {
                        $filePath = 'uploads/' . $fileName;
                        $takenDate = date('Y-m-d');
                        $uploader  = $_SESSION['user_id'];
                        $stmt2 = $conn->prepare("INSERT INTO patient_photos (patient_id,photo_type,file_path,taken_at,uploaded_by) VALUES (?,?,?,?,?)");
                        $stmt2->bind_param("isssi", $newPatientId, $type, $filePath, $takenDate, $uploader);
                        $stmt2->execute();
                        $stmt2->close();
                    }
                }
            }
            setFlash('success', 'Patient "' . $fullName . '" registered! File: ' . $fileNumber);
            header('Location: ' . BASE_URL . '/patients');
            exit;
        }

        $search  = trim($_GET['q'] ?? '');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset  = ($page - 1) * $perPage;

        if ($search !== '') {
            $like = '%' . $conn->real_escape_string($search) . '%';
            $total = $conn->query("SELECT COUNT(*) AS c FROM patients WHERE full_name LIKE '$like' OR file_number LIKE '$like' OR residence LIKE '$like'")->fetch_assoc()['c'];
            $rows  = $conn->query("SELECT * FROM patients WHERE full_name LIKE '$like' OR file_number LIKE '$like' OR residence LIKE '$like' ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
        } else {
            $total = $conn->query("SELECT COUNT(*) AS c FROM patients")->fetch_assoc()['c'];
            $rows  = $conn->query("SELECT * FROM patients ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
        }
        $totalPages = max(1, ceil($total / $perPage));

        $statsRow = $conn->query("SELECT COUNT(*) AS total, SUM(status='Active') AS active, SUM(created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS new_reg FROM patients")->fetch_assoc();
        $pendingReports = $conn->query("SELECT COUNT(*) AS c FROM lab_tests WHERE result_status = 'Pending'")->fetch_assoc()['c'];

        $this->render('patients/index', [
            'pageTitle'      => 'Patients',
            'activePage'     => 'Patients',
            'rows'           => $rows,
            'total'          => $total,
            'totalPages'     => $totalPages,
            'page'           => $page,
            'perPage'        => $perPage,
            'search'         => $search,
            'statsRow'       => $statsRow,
            'pendingReports' => $pendingReports,
        ]);
    }

    public function profile(): void {
        requireLogin();
        requirePermission('patients', 'view');
        $conn = db();

        $patientId = (int)($_GET['id'] ?? 0);
        $patient = null;
        if ($patientId > 0) {
            $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $patientId);
            $stmt->execute();
            $patient = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        if (!$patient) {
            $patient = ['id'=>1,'file_number'=>'KSC-2490','full_name'=>'Sarah J. Namugabo','age'=>28,'gender'=>'Female','blood_type'=>'O Positive','phone'=>'+256 701 445 200','email'=>'sarah@email.com','residence'=>'Ntinda, Kampala','sulfa_reactive'=>0,'penicillin_allergy'=>1,'latex_allergy'=>1,'other_allergies'=>'','status'=>'Active','created_at'=>'2022-10-15 09:00:00'];
            $patientId = 1;
        }

        $allPhotos = [];
        $photoResult = $conn->query("SELECT * FROM patient_photos WHERE patient_id = $patientId ORDER BY taken_at DESC, id DESC");
        if ($photoResult) { while ($ph = $photoResult->fetch_assoc()) $allPhotos[] = $ph; }

        $visits = $conn->query("SELECT v.*, u.full_name AS doctor_name, (SELECT GROUP_CONCAT(d.name ORDER BY d.name SEPARATOR ', ') FROM drug_prescriptions dp JOIN drugs d ON d.id = dp.drug_id WHERE dp.patient_id = v.patient_id AND dp.created_at BETWEEN v.created_at AND DATE_ADD(v.created_at, INTERVAL 30 MINUTE)) AS prescribed_drugs, (SELECT GROUP_CONCAT(dp2.drug_id ORDER BY dp2.drug_id SEPARATOR ',') FROM drug_prescriptions dp2 WHERE dp2.patient_id = v.patient_id AND dp2.created_at BETWEEN v.created_at AND DATE_ADD(v.created_at, INTERVAL 30 MINUTE)) AS prescribed_drug_ids FROM visits v JOIN users u ON u.id = v.doctor_id WHERE v.patient_id = $patientId ORDER BY v.visit_date DESC LIMIT 10");
        $visitOptions = $conn->query("SELECT id, visit_date, visit_type FROM visits WHERE patient_id = $patientId ORDER BY visit_date DESC LIMIT 50");

        $hasVisitId = false; $visitIdNullable = false;
        $vc = $conn->query("SHOW COLUMNS FROM lab_tests LIKE 'visit_id'");
        if ($vc && $vc->num_rows > 0) { $hasVisitId = true; $vrow = $vc->fetch_assoc(); $visitIdNullable = isset($vrow['Null']) && strtoupper($vrow['Null']) === 'YES'; }

        $hasFilePathColumn = false; $hasReportFileColumn = false;
        $colFp = $conn->query("SHOW COLUMNS FROM lab_tests LIKE 'file_path'");
        if ($colFp && $colFp->num_rows > 0) $hasFilePathColumn = true;
        $colRf = $conn->query("SHOW COLUMNS FROM lab_tests LIKE 'report_file'");
        if ($colRf && $colRf->num_rows > 0) $hasReportFileColumn = true;
        if ($hasFilePathColumn && $hasReportFileColumn)  $filePathSelect = "COALESCE(lt.file_path, lt.report_file) AS file_path";
        elseif ($hasFilePathColumn)                      $filePathSelect = "lt.file_path AS file_path";
        elseif ($hasReportFileColumn)                    $filePathSelect = "lt.report_file AS file_path";
        else                                             $filePathSelect = "'' AS file_path";

        $labTests = $conn->query("SELECT lt.*, u.full_name AS doctor_name, {$filePathSelect} FROM lab_tests lt JOIN users u ON u.id = lt.doctor_id WHERE lt.patient_id = $patientId ORDER BY lt.created_at DESC LIMIT 5");

        $this->render('patients/profile', [
            'pageTitle'        => 'Patient: ' . $patient['full_name'],
            'activePage'       => 'Patients',
            'patient'          => $patient,
            'patientId'        => $patientId,
            'allPhotos'        => $allPhotos,
            'visits'           => $visits,
            'visitOptions'     => $visitOptions,
            'hasVisitId'       => $hasVisitId,
            'visitIdNullable'  => $visitIdNullable,
            'labTests'         => $labTests,
            'conn'             => $conn,
        ]);
    }

    public function print(): void {
        requireLogin();
        requirePermission('patients', 'view');
        $conn = db();

        $patientId = (int)($_GET['id'] ?? 0);
        if ($patientId <= 0) { header('Location: ' . BASE_URL . '/patients'); exit; }
        $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $patientId);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$patient) { header('Location: ' . BASE_URL . '/patients'); exit; }

        $visits = $conn->query("SELECT v.visit_date, v.visit_type, v.chief_complaint, v.notes, u.full_name AS doctor_name, (SELECT GROUP_CONCAT(d.name ORDER BY d.name SEPARATOR ', ') FROM drug_prescriptions dp JOIN drugs d ON d.id = dp.drug_id WHERE dp.patient_id = v.patient_id AND dp.created_at BETWEEN v.created_at AND DATE_ADD(v.created_at, INTERVAL 30 MINUTE)) AS prescribed_drugs FROM visits v JOIN users u ON u.id = v.doctor_id WHERE v.patient_id = $patientId ORDER BY v.visit_date DESC LIMIT 20");

        require __DIR__ . '/../../app/Views/patients/print.php';
    }
}
