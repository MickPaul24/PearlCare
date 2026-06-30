<?php
class QueueController extends Controller {

    public function index(): void {
        requireLogin();
        $conn = db();

        $today = date('Y-m-d');
        if (($_SESSION['queue_last_clear_date'] ?? null) !== $today) {
            $_SESSION['queue_last_clear_date'] = $today;
        }

        $waitingResult = $conn->query("
            SELECT q.id, q.patient_id, q.visit_id, q.priority, q.check_in_time,
                   COALESCE(p.full_name, q.temp_full_name) AS full_name,
                   COALESCE(p.age, q.temp_age) AS age,
                   COALESCE(v.visit_type, q.temp_visit_type) AS visit_type,
                   COALESCE(v.chief_complaint, q.temp_chief_complaint) AS chief_complaint
            FROM queue q
            LEFT JOIN patients p ON p.id = q.patient_id
            LEFT JOIN visits v ON v.id = q.visit_id
            WHERE q.queue_status = 'Waiting' AND DATE(q.check_in_time) = CURDATE()
            ORDER BY FIELD(q.priority, 'Urgent', 'Priority', 'Routine'), q.check_in_time ASC
        ");

        $consultResult = $conn->query("
            SELECT q.id, q.patient_id, q.visit_id, q.start_time, q.assigned_room, q.assigned_doctor,
                   COALESCE(p.full_name, q.temp_full_name) AS full_name,
                   COALESCE(v.visit_type, q.temp_visit_type) AS visit_type,
                   u.full_name AS doctor_name
            FROM queue q
            LEFT JOIN patients p ON p.id = q.patient_id
            LEFT JOIN visits v ON v.id = q.visit_id
            LEFT JOIN users u ON u.id = q.assigned_doctor
            WHERE q.queue_status = 'In Consultation' AND DATE(q.check_in_time) = CURDATE()
            ORDER BY q.start_time ASC
        ");

        $completedResult = $conn->query("
            SELECT q.id, q.patient_id, q.visit_id, q.end_time,
                   COALESCE(p.full_name, q.temp_full_name) AS full_name,
                   COALESCE(v.visit_type, q.temp_visit_type) AS visit_type
            FROM queue q
            LEFT JOIN patients p ON p.id = q.patient_id
            LEFT JOIN visits v ON v.id = q.visit_id
            WHERE q.queue_status = 'Completed' AND DATE(q.check_in_time) = CURDATE()
            ORDER BY q.end_time DESC LIMIT 10
        ");

        $waiting   = $waitingResult   ? $waitingResult->fetch_all(MYSQLI_ASSOC)   : [];
        $consult   = $consultResult   ? $consultResult->fetch_all(MYSQLI_ASSOC)   : [];
        $completed = $completedResult ? $completedResult->fetch_all(MYSQLI_ASSOC) : [];

        $currentUserRow = $conn->query("SELECT * FROM users WHERE id = " . (int)$_SESSION['user_id'])->fetch_assoc();
        $currentUserName = $currentUserRow['full_name'] ?? 'Doctor';

        $doctorsResult = $conn->query("SELECT id, full_name FROM users WHERE is_active = 1 AND role IN ('doctor','admin') ORDER BY full_name ASC");
        $doctorOptions = [];
        if ($doctorsResult) {
            while ($doctor = $doctorsResult->fetch_assoc()) {
                $doctorOptions[] = $doctor;
            }
        }

        $this->render('queue/index', [
            'pageTitle'       => 'Queue',
            'activePage'      => 'Queue',
            'waiting'         => $waiting,
            'consult'         => $consult,
            'completed'       => $completed,
            'currentUserName' => $currentUserName,
            'doctorOptions'   => $doctorOptions,
        ]);
    }
}
