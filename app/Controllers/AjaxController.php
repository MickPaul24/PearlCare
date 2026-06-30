<?php
class AjaxController extends Controller {

    public function handle(): void {
        requireLogin();
        header('Content-Type: application/json');

        $action = $_REQUEST['action'] ?? '';
        $conn   = db();

        switch ($action) {

            case 'live_search':
                $query = trim($_POST['query'] ?? $_GET['query'] ?? '');
                if ($query === '') { $this->json(['success'=>true,'results'=>[]]); }
                $like = '%' . $conn->real_escape_string($query) . '%';
                $patients = [];
                $res = $conn->query("SELECT id,file_number,full_name,residence FROM patients WHERE full_name LIKE '$like' OR file_number LIKE '$like' OR residence LIKE '$like' ORDER BY created_at DESC LIMIT 8");
                if ($res) while ($r = $res->fetch_assoc()) $patients[] = ['type'=>'patient','id'=>$r['id'],'label'=>$r['full_name'],'sub'=>$r['file_number']];
                $drugs = [];
                $res = $conn->query("SELECT id,name,category FROM drugs WHERE name LIKE '$like' OR category LIKE '$like' ORDER BY stock_qty DESC LIMIT 6");
                if ($res) while ($r = $res->fetch_assoc()) $drugs[] = ['type'=>'drug','id'=>$r['id'],'label'=>$r['name'],'sub'=>$r['category']??''];
                $this->json(['success'=>true,'results'=>array_merge($patients,$drugs)]);
                break;

            case 'patient_delete':
                requirePermission('patients','delete');
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) $this->json(['success'=>false,'message'=>'Invalid patient ID.']);
                $stmt = $conn->prepare('DELETE FROM patients WHERE id=?');
                $stmt->bind_param('i',$id);
                $this->json($stmt->execute() ? ['success'=>true,'message'=>'Patient deleted.'] : ['success'=>false,'message'=>'Unable to delete.']);
                break;

            case 'queue_update':
                requirePermission('queue','update');
                $id = (int)($_POST['id'] ?? 0);
                $status = trim($_POST['status'] ?? '');
                if ($id<=0 || !in_array($status,['Waiting','In Consultation','Completed'],true)) $this->json(['success'=>false,'message'=>'Invalid queue update.']);
                $stmt = $conn->prepare('UPDATE queue SET queue_status=?,updated_at=NOW() WHERE id=?');
                $stmt->bind_param('si',$status,$id);
                $this->json($stmt->execute() ? ['success'=>true,'message'=>'Queue updated.'] : ['success'=>false,'message'=>'Unable to update.']);
                break;

            case 'settings_save':
                requirePermission('settings','update');
                $currency = trim($_POST['currency'] ?? 'UGX');
                $records  = (int)($_POST['records_per_page'] ?? 25);
                $disabled = json_encode($_POST['hidden_columns'] ?? []);
                $stmt = $conn->prepare('REPLACE INTO settings (`key`,`value`) VALUES (?,?),(?,?),(?,?)');
                $k1='currency';$v1=$currency; $k2='records_per_page';$v2=(string)$records; $k3='hidden_columns';$v3=$disabled;
                $stmt->bind_param('ssssss',$k1,$v1,$k2,$v2,$k3,$v3);
                $this->json($stmt->execute() ? ['success'=>true,'message'=>'Settings saved.'] : ['success'=>false,'message'=>'Unable to save.']);
                break;

            case 'upload_clinical_photo':
                requirePermission('patients','update');
                $patientId=(int)($_POST['patient_id']??0);
                $photoType=trim($_POST['photo_type']??'Other');
                $takenAt=trim($_POST['taken_at']??date('Y-m-d'));
                if ($patientId<=0) $this->json(['success'=>false,'error'=>'Invalid patient']);
                if (empty($_FILES['photo_file']['tmp_name'])||$_FILES['photo_file']['error']!==0) $this->json(['success'=>false,'error'=>'No file uploaded']);
                $allowed=['jpg','jpeg','png','gif','webp'];
                $ext=strtolower(pathinfo($_FILES['photo_file']['name'],PATHINFO_EXTENSION));
                if (!in_array($ext,$allowed)) $this->json(['success'=>false,'error'=>'Invalid file type']);
                if ($_FILES['photo_file']['size']>5242880) $this->json(['success'=>false,'error'=>'File too large (max 5MB)']);
                $uploadDir=UPLOAD_DIR.'clinical/'; if(!is_dir($uploadDir))mkdir($uploadDir,0755,true);
                $fileName='clinical_'.$patientId.'_'.uniqid().'.'.$ext;
                $filePath='uploads/clinical/'.$fileName;
                if (!move_uploaded_file($_FILES['photo_file']['tmp_name'],$uploadDir.$fileName)) $this->json(['success'=>false,'error'=>'Failed to save file']);
                $up=$_SESSION['user_id'];
                $sp=$conn->prepare("INSERT INTO patient_photos (patient_id,photo_type,file_path,taken_at,uploaded_by) VALUES (?,?,?,?,?)");
                $sp->bind_param('isssi',$patientId,$photoType,$filePath,$takenAt,$up);
                if (!$sp->execute()) $this->json(['success'=>false,'error'=>'Database error: '.$sp->error]);
                $sp->close();
                $this->json(['success'=>true,'message'=>'Photo uploaded','id'=>$conn->insert_id]);
                break;

            case 'delete_photo':
                requirePermission('patients','update');
                $photoId=(int)($_POST['photo_id']??0);
                if ($photoId<=0) $this->json(['success'=>false,'error'=>'Invalid photo ID']);
                $pr=$conn->prepare('SELECT file_path FROM patient_photos WHERE id=? LIMIT 1');
                $pr->bind_param('i',$photoId); $pr->execute();
                $prow=$pr->get_result()->fetch_assoc(); $pr->close();
                if (!$prow) $this->json(['success'=>false,'error'=>'Photo not found']);
                $conn->query("DELETE FROM patient_photos WHERE id=$photoId");
                $absPath=UPLOAD_DIR.substr($prow['file_path'],strlen('uploads/'));
                if (file_exists($absPath)) @unlink($absPath);
                $this->json(['success'=>true,'message'=>'Photo deleted']);
                break;

            case 'update_patient':
                requirePermission('patients','update');
                $patientId = (int)($_POST['patient_id'] ?? 0);
                if ($patientId<=0) $this->json(['success'=>false,'error'=>'Invalid patient ID']);
                $fullName = trim($_POST['full_name'] ?? '');
                if ($fullName==='') $this->json(['success'=>false,'error'=>'Full name is required']);
                $fileNumber=$_POST['file_number']??''; $age=(int)$_POST['age']; $gender=$_POST['gender']??''; $bloodType=$_POST['blood_type']??'';
                $phone=$_POST['phone']??''; $email=$_POST['email']??''; $residence=$_POST['residence']??''; $otherAllergies=$_POST['other_allergies']??'';
                $sulfaReactive=(int)($_POST['sulfa_reactive']??0); $penicillinAllergy=(int)($_POST['penicillin_allergy']??0); $latexAllergy=(int)($_POST['latex_allergy']??0);
                $stmt = $conn->prepare('UPDATE patients SET file_number=?,full_name=?,age=?,gender=?,blood_type=?,phone=?,email=?,residence=?,other_allergies=?,sulfa_reactive=?,penicillin_allergy=?,latex_allergy=?,updated_at=NOW() WHERE id=?');
                $stmt->bind_param('ssissssssiiii',$fileNumber,$fullName,$age,$gender,$bloodType,$phone,$email,$residence,$otherAllergies,$sulfaReactive,$penicillinAllergy,$latexAllergy,$patientId);
                if ($stmt->execute()) {
                    $uploadDir = UPLOAD_DIR;
                    if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
                    foreach (['photo_before'=>'before','photo_after'=>'after'] as $field=>$type) {
                        if (!empty($_FILES[$field]['tmp_name']) && $_FILES[$field]['error']===0) {
                            $ext = strtolower(pathinfo($_FILES[$field]['name'],PATHINFO_EXTENSION));
                            $fileName = 'patient_'.$patientId.'_'.$type.'.'.$ext;
                            if (move_uploaded_file($_FILES[$field]['tmp_name'],$uploadDir.$fileName)) {
                                $fp='uploads/'.$fileName; $td=date('Y-m-d'); $up=$_SESSION['user_id'];
                                $s2=$conn->prepare("INSERT INTO patient_photos (patient_id,photo_type,file_path,taken_at,uploaded_by) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE file_path=VALUES(file_path),taken_at=VALUES(taken_at)");
                                $s2->bind_param('isssi',$patientId,$type,$fp,$td,$up); $s2->execute(); $s2->close();
                            }
                        }
                    }
                    $this->json(['success'=>true,'message'=>'Patient updated successfully']);
                }
                $this->json(['success'=>false,'error'=>'Failed to update: '.$stmt->error]);
                break;

            case 'queue_next_patient':
                requirePermission('queue','update');
                $queueId = (int)($_POST['queue_id'] ?? 0);
                if ($queueId<=0) $this->json(['success'=>false,'message'=>'Invalid queue ID.']);
                $assignedRoom   = trim($_POST['assigned_room'] ?? '');

                // Load queue row to get temp patient data
                $qr=$conn->prepare('SELECT * FROM queue WHERE id=? AND queue_status=? LIMIT 1');
                $ws='Waiting'; $qr->bind_param('is',$queueId,$ws); $qr->execute();
                $qrow=$qr->get_result()->fetch_assoc(); $qr->close();
                if (!$qrow) $this->json(['success'=>false,'message'=>'Unable to move patient.']);

                $assignedDoctor = (int)($_POST['assigned_doctor'] ?? 0);
                if ($assignedDoctor <= 0) {
                    $assignedDoctor = (int)($qrow['assigned_doctor'] ?? $_SESSION['user_id'] ?? 0);
                }

                // Create patient record now so doctor can access profile during consultation
                $patientId=(int)($qrow['patient_id']??0);
                if ($patientId===0) {
                    $fn=trim($qrow['temp_file_number']??'');
                    // Check if already exists by file number
                    if ($fn!=='') {
                        $ck=$conn->prepare('SELECT id FROM patients WHERE file_number=? LIMIT 1');
                        $ck->bind_param('s',$fn); $ck->execute();
                        $ex=$ck->get_result()->fetch_assoc(); $ck->close();
                        if ($ex) $patientId=(int)$ex['id'];
                    }
                    if ($patientId===0) {
                        // Generate file number if missing
                        if ($fn==='') {
                            $res=$conn->query("SELECT MAX(CAST(SUBSTRING_INDEX(file_number,'-',-1) AS UNSIGNED)) AS m FROM patients WHERE file_number LIKE 'KSC-%'");
                            $fn='KSC-'.str_pad((($res->fetch_assoc()['m']??0)+1),4,'0',STR_PAD_LEFT);
                        }
                        $nm=trim($qrow['temp_full_name']??''); $ag=(int)($qrow['temp_age']??0);
                        $gn=$qrow['temp_gender']??''; $rs=$qrow['temp_residence']??'';
                        $ph=$qrow['temp_phone']??''; $em=$qrow['temp_email']??'';
                        $bt=$qrow['temp_blood_type']??'Unknown';
                        $su=(int)($qrow['temp_sulfa_reactive']??0); $pe=(int)($qrow['temp_penicillin_allergy']??0);
                        $la=(int)($qrow['temp_latex_allergy']??0); $oa=$qrow['temp_other_allergies']??'';
                        $up=$_SESSION['user_id'];
                        $ps=$conn->prepare("INSERT INTO patients (file_number,full_name,age,gender,residence,phone,email,blood_type,sulfa_reactive,penicillin_allergy,latex_allergy,other_allergies,status,registered_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'Active',?)");
                        $ps->bind_param('ssisssssiiiis',$fn,$nm,$ag,$gn,$rs,$ph,$em,$bt,$su,$pe,$la,$oa,$up);
                        if (!$ps->execute()) $this->json(['success'=>false,'message'=>'Unable to create patient record: '.$ps->error]);
                        $patientId=$conn->insert_id; $ps->close();
                    }
                }

                $stmt=$conn->prepare("UPDATE queue SET queue_status='In Consultation',patient_id=?,assigned_doctor=?,assigned_room=?,start_time=NOW(),updated_at=NOW() WHERE id=? AND queue_status='Waiting'");
                $stmt->bind_param('iisi',$patientId,$assignedDoctor,$assignedRoom,$queueId);
                $this->json($stmt->execute()&&$stmt->affected_rows>0 ? ['success'=>true,'message'=>'Patient moved to consultation.','patient_id'=>$patientId] : ['success'=>false,'message'=>'Unable to move patient.']);
                break;

            case 'queue_complete_consultation':
                requirePermission('queue','update');
                $queueId = (int)($_POST['queue_id'] ?? 0);
                if ($queueId<=0) $this->json(['success'=>false,'message'=>'Invalid queue ID.']);
                $stmt = $conn->prepare('SELECT * FROM queue WHERE id=? LIMIT 1');
                $stmt->bind_param('i',$queueId); $stmt->execute();
                $queueRow = $stmt->get_result()->fetch_assoc(); $stmt->close();
                if (!$queueRow) $this->json(['success'=>false,'message'=>'Queue item not found.']);
                if ($queueRow['queue_status']!=='In Consultation') $this->json(['success'=>false,'message'=>'Patient not in consultation.']);
                $fileNumber=$queueRow['temp_file_number']??''; $fullName=$queueRow['temp_full_name']??'';
                $age=(int)$queueRow['temp_age']; $gender=$queueRow['temp_gender']??''; $residence=$queueRow['temp_residence']??'';
                $phone=$queueRow['temp_phone']??''; $email=$queueRow['temp_email']??''; $bloodType=$queueRow['temp_blood_type']??'Unknown';
                $sulfaReactive=(int)$queueRow['temp_sulfa_reactive']; $penicillinAllergy=(int)$queueRow['temp_penicillin_allergy']; $latexAllergy=(int)$queueRow['temp_latex_allergy'];
                $otherAllergies=$queueRow['temp_other_allergies']??''; $visitType=trim($queueRow['temp_visit_type']??'Consultation');
                if ($visitType==='') $visitType='Consultation'; $visitType=mb_substr($visitType,0,120);
                $chiefComplaint=$queueRow['temp_chief_complaint']??''; $priority=$queueRow['priority']??'Routine';
                if (empty($queueRow['patient_id'])) {
                    if ($fileNumber!=='') { $ck=$conn->prepare('SELECT id FROM patients WHERE file_number=? LIMIT 1'); $ck->bind_param('s',$fileNumber); $ck->execute(); $exists=$ck->get_result()->num_rows>0; $ck->close(); } else $exists=false;
                    if ($fileNumber===''||$exists) { $res=$conn->query("SELECT MAX(CAST(SUBSTRING_INDEX(file_number,'-',-1) AS UNSIGNED)) AS maxnum FROM patients WHERE file_number LIKE 'KSC-%'"); $row=$res->fetch_assoc(); $fileNumber='KSC-'.str_pad(($row['maxnum']??0)+1,4,'0',STR_PAD_LEFT); }
                    $ps=$conn->prepare("INSERT INTO patients (file_number,full_name,age,gender,residence,phone,email,blood_type,sulfa_reactive,penicillin_allergy,latex_allergy,other_allergies,status,registered_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'Active',?)");
                    $ps->bind_param('ssisssssiiiis',$fileNumber,$fullName,$age,$gender,$residence,$phone,$email,$bloodType,$sulfaReactive,$penicillinAllergy,$latexAllergy,$otherAllergies,$_SESSION['user_id']);
                    if (!$ps->execute()) $this->json(['success'=>false,'message'=>'Unable to create patient: '.$ps->error]);
                    $patientId=$conn->insert_id; $ps->close();
                } else $patientId=(int)$queueRow['patient_id'];
                if (empty($queueRow['visit_id'])) {
                    $assignedDoctor=$queueRow['assigned_doctor']?:$_SESSION['user_id']; $visitDate=date('Y-m-d');
                    $vs=$conn->prepare("INSERT INTO visits (patient_id,doctor_id,visit_type,chief_complaint,visit_date) VALUES (?,?,?,?,?)");
                    $vs->bind_param('iisss',$patientId,$assignedDoctor,$visitType,$chiefComplaint,$visitDate);
                    if (!$vs->execute()) $this->json(['success'=>false,'message'=>'Unable to create visit: '.$vs->error]);
                    $visitId=$conn->insert_id; $vs->close();
                } else $visitId=(int)$queueRow['visit_id'];
                $us=$conn->prepare('UPDATE queue SET queue_status=?,end_time=NOW(),updated_at=NOW(),patient_id=?,visit_id=? WHERE id=?');
                $status='Completed'; $us->bind_param('siii',$status,$patientId,$visitId,$queueId);
                if (!$us->execute()) $this->json(['success'=>false,'message'=>'Unable to update queue: '.$us->error]); $us->close();
                $patientName=$fullName; if ($patientName===''&&$patientId>0) { $nr=$conn->query("SELECT full_name FROM patients WHERE id=$patientId LIMIT 1"); $nr=$nr->fetch_assoc(); $patientName=$nr['full_name']??'Patient'; }
                $amountPaid=$this->getFeeForPriority($priority); $amountDue=0; $paid=1; $notes='Auto-generated fee for '.$priority.' consultation';
                $fs=$conn->prepare("INSERT INTO finances (invoice_number,patient_name,category,amount_paid,amount_due,paid,notes) VALUES (?,?,?,?,?,?,?)");
                $invoiceNum=$this->generateInvoiceNumber($conn);
                $fs->bind_param('ssddiss',$invoiceNum,$patientName,$priority,$amountPaid,$amountDue,$paid,$notes);
                if (!$fs->execute()) $this->json(['success'=>false,'message'=>'Unable to create finance record: '.$fs->error]); $fs->close();
                $this->json(['success'=>true,'message'=>'Consultation completed.']);
                break;

            case 'search_queue_patients':
                $q = trim($_POST['q'] ?? '');
                if (strlen($q) < 2) { $this->json(['success'=>true,'patients'=>[]]); }
                $like = '%' . $conn->real_escape_string($q) . '%';
                $res = $conn->query("SELECT id,file_number,full_name,age,gender,phone,email,blood_type,residence,sulfa_reactive,penicillin_allergy,latex_allergy,other_allergies FROM patients WHERE full_name LIKE '$like' ORDER BY full_name ASC LIMIT 8");
                $patients = [];
                if ($res) while ($r = $res->fetch_assoc()) $patients[] = $r;
                $this->json(['success'=>true,'patients'=>$patients]);
                break;

            case 'queue_add_patient':
                requirePermission('queue','insert');
                $fileNumber=trim($_POST['file_number']??'');
                $existingPatientId=null;
                if ($fileNumber!=='') {
                    $ck=$conn->prepare('SELECT id FROM patients WHERE file_number=? LIMIT 1');
                    $ck->bind_param('s',$fileNumber); $ck->execute();
                    $ckRow=$ck->get_result()->fetch_assoc(); $ck->close();
                    if ($ckRow) $existingPatientId=(int)$ckRow['id'];
                }
                if ($fileNumber==='') { $res=$conn->query("SELECT MAX(id) AS maxid FROM patients"); $next=($res->fetch_assoc()['maxid']??0)+1; $fileNumber='KSC-'.str_pad($next,4,'0',STR_PAD_LEFT); }
                $fullName=trim($_POST['full_name']??''); $age=(int)($_POST['age']??0); $gender=$_POST['gender']??''; $residence=trim($_POST['residence']??'');
                $phone=trim($_POST['phone']??''); $email=trim($_POST['email']??''); $bloodType=$_POST['blood_type']??'Unknown';
                $sulfaReactive=(int)($_POST['sulfa_reactive']??0); $penicillinAllergy=(int)($_POST['penicillin_allergy']??0); $latexAllergy=(int)($_POST['latex_allergy']??0);
                $otherAllergies=trim($_POST['other_allergies']??''); $visitType=trim($_POST['visit_type']??''); $chiefComplaint=trim($_POST['chief_complaint']??'');
                $priority=$_POST['priority']??'Routine'; if (!in_array($priority,['Routine','Priority','Urgent'],true)) $priority='Routine';
                $assignedDoctor=(int)($_POST['assigned_doctor'] ?? $_SESSION['user_id'] ?? 0);
                if ($assignedDoctor <= 0) $assignedDoctor = (int)($_SESSION['user_id'] ?? 0);
                if ($fullName===''||$age<=0||$visitType==='') $this->json(['success'=>false,'message'=>'Full name, age, and visit type are required.']);
                $stmt=$conn->prepare("INSERT INTO queue (patient_id,temp_file_number,temp_full_name,temp_age,temp_gender,temp_residence,temp_phone,temp_email,temp_blood_type,temp_sulfa_reactive,temp_penicillin_allergy,temp_latex_allergy,temp_other_allergies,temp_visit_type,temp_chief_complaint,queue_status,priority,check_in_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Waiting',?,NOW())");
                $stmt->bind_param('ississsssiiiisss',$existingPatientId,$fileNumber,$fullName,$age,$gender,$residence,$phone,$email,$bloodType,$sulfaReactive,$penicillinAllergy,$latexAllergy,$otherAllergies,$visitType,$chiefComplaint,$priority);
                if (!$stmt->execute()) $this->json(['success'=>false,'message'=>'Unable to add patient: '.$stmt->error]);
                $queueId = $conn->insert_id;
                $stmt->close();
                if ($assignedDoctor > 0) {
                    $doctorStmt = $conn->prepare('UPDATE queue SET assigned_doctor=? WHERE id=?');
                    $doctorStmt->bind_param('ii',$assignedDoctor,$queueId);
                    $doctorStmt->execute();
                    $doctorStmt->close();
                }
                $this->json(['success'=>true,'message'=>'Patient added to queue!','queue_id'=>$queueId,'file_number'=>$fileNumber]);
                break;

            case 'get_patient_visits':
                $patientId=(int)($_POST['patient_id']??0);
                if ($patientId<=0) $this->json(['success'=>false,'message'=>'Invalid patient ID']);
                $pr=$conn->prepare('SELECT full_name,file_number,age,gender,phone FROM patients WHERE id=? LIMIT 1');
                $pr->bind_param('i',$patientId); $pr->execute();
                $patientRow=$pr->get_result()->fetch_assoc(); $pr->close();
                if (!$patientRow) $this->json(['success'=>false,'message'=>'Patient not found']);
                $vr=$conn->prepare("SELECT v.visit_type,v.visit_date,v.chief_complaint,v.notes,u.full_name AS doctor_name,(SELECT GROUP_CONCAT(d.name ORDER BY d.name SEPARATOR ', ') FROM drug_prescriptions dp JOIN drugs d ON d.id=dp.drug_id WHERE dp.patient_id=v.patient_id AND dp.created_at BETWEEN v.created_at AND DATE_ADD(v.created_at, INTERVAL 30 MINUTE)) AS prescribed_drugs FROM visits v LEFT JOIN users u ON u.id=v.doctor_id WHERE v.patient_id=? ORDER BY v.visit_date DESC LIMIT 20");
                $vr->bind_param('i',$patientId); $vr->execute();
                $visitRows=$vr->get_result()->fetch_all(MYSQLI_ASSOC); $vr->close();
                $this->json(['success'=>true,'patient'=>$patientRow,'visits'=>$visitRows]);
                break;

            case 'queue_clear_day':
                requirePermission('queue','update');
                $stmt=$conn->prepare("UPDATE queue SET queue_status='Waiting' WHERE DATE(check_in_time)=CURDATE() AND queue_status IN ('In Consultation','Completed')");
                $this->json($stmt->execute() ? ['success'=>true,'message'=>'Queue cleared.'] : ['success'=>false,'message'=>'Unable to clear queue.']);
                break;

            case 'get_queue_data':
                $waiting  = $conn->query("SELECT q.id,q.patient_id,q.visit_id,q.priority,q.check_in_time,COALESCE(p.full_name,q.temp_full_name) AS full_name,COALESCE(p.age,q.temp_age) AS age,COALESCE(v.visit_type,q.temp_visit_type) AS visit_type,COALESCE(v.chief_complaint,q.temp_chief_complaint) AS chief_complaint FROM queue q LEFT JOIN patients p ON p.id=q.patient_id LEFT JOIN visits v ON v.id=q.visit_id WHERE q.queue_status='Waiting' AND DATE(q.check_in_time)=CURDATE() ORDER BY FIELD(q.priority,'Urgent','Priority','Routine'),q.check_in_time ASC");
                $consulting=$conn->query("SELECT q.id,q.patient_id,q.visit_id,q.start_time,q.assigned_room,COALESCE(p.full_name,q.temp_full_name) AS full_name,COALESCE(v.visit_type,q.temp_visit_type) AS visit_type,u.full_name AS doctor_name FROM queue q LEFT JOIN patients p ON p.id=q.patient_id LEFT JOIN visits v ON v.id=q.visit_id LEFT JOIN users u ON u.id=q.assigned_doctor WHERE q.queue_status='In Consultation' AND DATE(q.check_in_time)=CURDATE() ORDER BY q.start_time ASC");
                $completed =$conn->query("SELECT q.id,q.patient_id,q.visit_id,q.end_time,COALESCE(p.full_name,q.temp_full_name) AS full_name,COALESCE(v.visit_type,q.temp_visit_type) AS visit_type FROM queue q LEFT JOIN patients p ON p.id=q.patient_id LEFT JOIN visits v ON v.id=q.visit_id WHERE q.queue_status='Completed' AND DATE(q.check_in_time)=CURDATE() ORDER BY q.end_time DESC LIMIT 10");
                $this->json(['success'=>true,'waiting'=>$waiting?$waiting->fetch_all(MYSQLI_ASSOC):[],'consulting'=>$consulting?$consulting->fetch_all(MYSQLI_ASSOC):[],'completed'=>$completed?$completed->fetch_all(MYSQLI_ASSOC):[]]);
                break;

            case 'update_visit':
                requirePermission('patients','update');
                $visitId=(int)($_POST['visit_id']??0); $visitType=trim($_POST['visit_type']??''); $visitDate=trim($_POST['visit_date']??'');
                $doctorId=(int)($_POST['doctor_id']??0); $notes=trim($_POST['notes']??''); $chiefComplaint=trim($_POST['chief_complaint']??'');
                $prescribedDrugs=$_POST['prescribed_drugs']??[];
                if ($visitId<=0) $this->json(['success'=>false,'error'=>'Invalid visit ID']);
                if ($visitType==='') $this->json(['success'=>false,'error'=>'Visit type is required']);
                if ($visitDate==='') $this->json(['success'=>false,'error'=>'Visit date is required']);
                if ($doctorId<=0) $this->json(['success'=>false,'error'=>'Doctor required']);
                $vr=$conn->prepare('SELECT patient_id,created_at FROM visits WHERE id=? LIMIT 1');
                $vr->bind_param('i',$visitId); $vr->execute();
                $vrow=$vr->get_result()->fetch_assoc(); $vr->close();
                if (!$vrow) $this->json(['success'=>false,'error'=>'Visit not found']);
                $patientId=(int)$vrow['patient_id']; $createdAt=$vrow['created_at'];
                $su=$conn->prepare('UPDATE visits SET visit_type=?,visit_date=?,doctor_id=?,chief_complaint=?,notes=? WHERE id=?');
                $su->bind_param('ssissi',$visitType,$visitDate,$doctorId,$chiefComplaint,$notes,$visitId);
                if (!$su->execute()) $this->json(['success'=>false,'error'=>'Failed to update: '.$su->error]); $su->close();
                $dd=$conn->prepare("DELETE FROM drug_prescriptions WHERE patient_id=? AND created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 30 MINUTE)");
                $dd->bind_param('iss',$patientId,$createdAt,$createdAt); $dd->execute(); $dd->close();
                if (!empty($prescribedDrugs)) {
                    $ps=$conn->prepare('INSERT INTO drug_prescriptions (patient_id,drug_id,prescribed_by,dose,duration,created_at) VALUES (?,?,?,?,?,?)');
                    $dose='As prescribed'; $dur='As per consultation';
                    foreach ($prescribedDrugs as $dId) { $dId=(int)$dId; if($dId>0){$ps->bind_param('iissss',$patientId,$dId,$doctorId,$dose,$dur,$createdAt);$ps->execute();} }
                    $ps->close();
                }
                $this->json(['success'=>true,'message'=>'Visit updated successfully']);
                break;

            case 'add_visit':
                requirePermission('patients','update');
                $patientId=(int)($_POST['patient_id']??0); $visitType=trim($_POST['visit_type']??''); $visitDate=trim($_POST['visit_date']??'');
                $doctorId=(int)($_POST['doctor_id']??0); $notes=trim($_POST['notes']??''); $prescribedDrugs=$_POST['prescribed_drugs']??[];
                if ($patientId<=0) $this->json(['success'=>false,'error'=>'Invalid patient ID']);
                if ($visitType==='') $this->json(['success'=>false,'error'=>'Visit type is required']);
                if ($visitDate==='') $this->json(['success'=>false,'error'=>'Visit date is required']);
                if ($doctorId<=0) $this->json(['success'=>false,'error'=>'Doctor required']);
                $ck=$conn->prepare('SELECT id FROM users WHERE id=? AND role=?'); $r='doctor'; $ck->bind_param('is',$doctorId,$r); $ck->execute();
                if ($ck->get_result()->num_rows===0) $this->json(['success'=>false,'error'=>'Invalid doctor']); $ck->close();
                $stmt=$conn->prepare('INSERT INTO visits (patient_id,doctor_id,visit_type,notes,visit_date,created_at) VALUES (?,?,?,?,?,NOW())');
                $stmt->bind_param('iisss',$patientId,$doctorId,$visitType,$notes,$visitDate);
                if ($stmt->execute()) {
                    $visitId=$conn->insert_id;
                    if (!empty($prescribedDrugs)) {
                        $ps=$conn->prepare('INSERT INTO drug_prescriptions (patient_id,drug_id,prescribed_by,dose,duration,created_at) VALUES (?,?,?,?,?,NOW())');
                        $dose='As prescribed'; $dur='As per consultation';
                        foreach ($prescribedDrugs as $drugId) { $drugId=(int)$drugId; if($drugId>0){$ps->bind_param('iisss',$patientId,$drugId,$doctorId,$dose,$dur);$ps->execute();} }
                        $ps->close();
                    }
                    $this->json(['success'=>true,'message'=>'Visit added successfully','visit_id'=>$visitId]);
                }
                $this->json(['success'=>false,'error'=>'Failed to create visit: '.$stmt->error]);
                break;

            case 'add_lab_test':
                requirePermission('lab_tests','insert');
                $patient_id=(int)($_POST['patient_id']??0); $test_name=trim($_POST['test_name']??''); $result_status=trim($_POST['result_status']??''); $result_notes=trim($_POST['result_notes']??'');
                if (!$patient_id||!$test_name||!$result_status) $this->json(['success'=>false,'error'=>'Missing required fields']);
                $file_path='';
                if (!empty($_FILES['test_file']['name'])) {
                    $file=$_FILES['test_file']; $allowed=['pdf','jpg','jpeg','png','gif']; $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
                    if (!in_array($ext,$allowed)) $this->json(['success'=>false,'error'=>'Invalid file type']);
                    if ($file['size']>5242880) $this->json(['success'=>false,'error'=>'File too large (max 5MB)']);
                    $uploadDir=UPLOAD_DIR.'lab_tests/'; if(!is_dir($uploadDir))mkdir($uploadDir,0755,true);
                    $fname=uniqid('lab_').'.'.$ext; $file_path='uploads/lab_tests/'.$fname;
                    if (!move_uploaded_file($file['tmp_name'],$uploadDir.$fname)) $this->json(['success'=>false,'error'=>'Failed to upload file']);
                }
                $colCheck=$conn->query("SHOW COLUMNS FROM lab_tests LIKE 'file_path'"); $fileColumn=null;
                if ($colCheck&&$colCheck->num_rows>0) $fileColumn='file_path';
                else { $cr=$conn->query("SHOW COLUMNS FROM lab_tests LIKE 'report_file'"); if($cr&&$cr->num_rows>0) $fileColumn='report_file'; }
                $hasFilePath=$fileColumn!==null;
                $doctor_id=(int)($_SESSION['user_id']??0);
                if ($hasFilePath) { $stmt=$conn->prepare("INSERT INTO lab_tests (patient_id,test_name,result_status,result_notes,{$fileColumn},doctor_id,created_at) VALUES (?,?,?,?,?,?,NOW())"); $stmt->bind_param('issssi',$patient_id,$test_name,$result_status,$result_notes,$file_path,$doctor_id); }
                else { $stmt=$conn->prepare('INSERT INTO lab_tests (patient_id,test_name,result_status,result_notes,doctor_id,created_at) VALUES (?,?,?,?,?,NOW())'); $stmt->bind_param('isssi',$patient_id,$test_name,$result_status,$result_notes,$doctor_id); }
                $this->json($stmt->execute() ? ['success'=>true,'message'=>'Lab test added'] : ['success'=>false,'error'=>'Database error: '.$stmt->error]);
                break;

            case 'get_drug_prescriptions':
                requirePermission('analytics','view');
                $drugName=trim($_POST['drug_name']??''); if($drugName==='') $this->json(['success'=>false,'error'=>'Drug name required']);
                $like='%'.$conn->real_escape_string($drugName).'%';
                $result=$conn->query("SELECT dp.id,p.full_name AS patient_name,DATE_FORMAT(dp.created_at,'%M %d, %Y') AS date,u.full_name AS doctor_name FROM drug_prescriptions dp JOIN patients p ON p.id=dp.patient_id JOIN drugs d ON d.id=dp.drug_id LEFT JOIN users u ON u.id=dp.prescribed_by WHERE d.name LIKE '$like' ORDER BY dp.created_at DESC LIMIT 20");
                $prescriptions=[]; if($result) while($r=$result->fetch_assoc()) $prescriptions[]=$r;
                $this->json(['success'=>true,'prescriptions'=>$prescriptions]);
                break;

            case 'get_lab_details':
                requirePermission('analytics','view');
                $labTestId=(int)($_POST['lab_test_id']??0); if($labTestId<=0) $this->json(['success'=>false,'error'=>'Invalid lab test ID']);
                $fpCol="'' AS file_path";
                $c1=$conn->query("SHOW COLUMNS FROM lab_tests LIKE 'file_path'"); $c2=$conn->query("SHOW COLUMNS FROM lab_tests LIKE 'report_file'");
                if ($c1&&$c1->num_rows>0&&$c2&&$c2->num_rows>0) $fpCol="COALESCE(lt.file_path,lt.report_file) AS file_path";
                elseif ($c1&&$c1->num_rows>0) $fpCol="lt.file_path AS file_path";
                elseif ($c2&&$c2->num_rows>0) $fpCol="lt.report_file AS file_path";
                $result=$conn->query("SELECT lt.id,lt.test_name,lt.result_status,lt.result_notes AS notes,{$fpCol},p.full_name AS patient_name,u.full_name AS doctor_name,DATE_FORMAT(lt.created_at,'%M %d, %Y %h:%i %p') AS date FROM lab_tests lt JOIN patients p ON p.id=lt.patient_id LEFT JOIN users u ON u.id=lt.doctor_id WHERE lt.id=$labTestId");
                if ($result&&$result->num_rows>0) $this->json(['success'=>true,'lab'=>$result->fetch_assoc()]);
                else $this->json(['success'=>false,'error'=>'Lab test not found']);
                break;

            case 'get_dashboard_config':
                requirePermission('settings','view');
                $conn->query("CREATE TABLE IF NOT EXISTS role_dashboard_config (id INT AUTO_INCREMENT PRIMARY KEY,role_key VARCHAR(50) NOT NULL,section_key VARCHAR(100) NOT NULL,is_enabled TINYINT(1) NOT NULL DEFAULT 1,section_order INT NOT NULL DEFAULT 0,UNIQUE KEY unique_role_section (role_key,section_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                // Seed any missing sections so the settings UI always shows all toggles
                $dcDefaults=['admin'=>['stats_cards'=>1,'weekly_visits_chart'=>1,'queue_donut'=>1,'revenue_chart'=>1,'lab_distribution_chart'=>1,'queue_list'=>1,'top_drugs'=>1,'recent_patients'=>1,'recent_tests_table'=>1],'doctor'=>['stats_cards'=>1,'weekly_visits_chart'=>1,'queue_donut'=>1,'revenue_chart'=>0,'lab_distribution_chart'=>1,'queue_list'=>1,'top_drugs'=>1,'recent_patients'=>0,'recent_tests_table'=>1],'nurse'=>['stats_cards'=>1,'weekly_visits_chart'=>0,'queue_donut'=>1,'revenue_chart'=>0,'lab_distribution_chart'=>1,'queue_list'=>1,'top_drugs'=>0,'recent_patients'=>1,'recent_tests_table'=>1],'receptionist'=>['stats_cards'=>1,'weekly_visits_chart'=>1,'queue_donut'=>1,'revenue_chart'=>0,'lab_distribution_chart'=>0,'queue_list'=>1,'top_drugs'=>0,'recent_patients'=>1,'recent_tests_table'=>0],'records'=>['stats_cards'=>1,'weekly_visits_chart'=>1,'queue_donut'=>0,'revenue_chart'=>1,'lab_distribution_chart'=>1,'queue_list'=>0,'top_drugs'=>0,'recent_patients'=>1,'recent_tests_table'=>1]];
                $dsi=$conn->prepare("INSERT IGNORE INTO role_dashboard_config (role_key,section_key,is_enabled) VALUES (?,?,?)");
                foreach($dcDefaults as $dr=>$ds){foreach($ds as $dk=>$de){$dsi->bind_param('ssi',$dr,$dk,$de);$dsi->execute();}}$dsi->close();
                $config=[]; $result=$conn->query("SELECT role_key,section_key,is_enabled FROM role_dashboard_config");
                if ($result) while($r=$result->fetch_assoc()) $config[$r['role_key'].'_'.$r['section_key']]=(bool)$r['is_enabled'];
                $this->json(['success'=>true,'config'=>$config]);
                break;

            case 'update_dashboard_config':
                requirePermission('settings','update');
                $sections=json_decode($_POST['dashboard_sections_config']??'[]',true);
                if (!is_array($sections)) $this->json(['success'=>false,'error'=>'Invalid format']);
                $conn->query("CREATE TABLE IF NOT EXISTS role_dashboard_config (id INT AUTO_INCREMENT PRIMARY KEY,role_key VARCHAR(50) NOT NULL,section_key VARCHAR(100) NOT NULL,is_enabled TINYINT(1) NOT NULL DEFAULT 1,section_order INT NOT NULL DEFAULT 0,UNIQUE KEY unique_role_section (role_key,section_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $conn->query("DELETE FROM role_dashboard_config");
                foreach ($sections as $cfg) {
                    $role=$cfg['role']??''; $section=$cfg['section']??''; $enabled=$cfg['enabled']?1:0;
                    $stmt=$conn->prepare('INSERT INTO role_dashboard_config (role_key,section_key,is_enabled,section_order) VALUES (?,?,?,0) ON DUPLICATE KEY UPDATE is_enabled=?');
                    $stmt->bind_param('ssii',$role,$section,$enabled,$enabled); $stmt->execute();
                }
                $this->json(['success'=>true,'message'=>'Dashboard configuration updated']);
                break;

            default:
                $this->json(['success'=>false,'message'=>'Unknown action.']);
        }
    }

    private function json(array $data): void {
        echo json_encode($data);
        exit;
    }

    private function generateInvoiceNumber(mysqli $conn): string {
        $prefix = 'FIN-' . date('Ymd');
        $like   = $conn->real_escape_string($prefix) . '-%';
        $result = $conn->query("SELECT invoice_number FROM finances WHERE invoice_number LIKE '$like' ORDER BY id DESC LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $parts = explode('-', $row['invoice_number']);
            return $prefix . '-' . str_pad((int)end($parts) + 1, 4, '0', STR_PAD_LEFT);
        }
        return $prefix . '-0001';
    }

    private function getFeeForPriority(string $priority): float {
        switch ($priority) {
            case 'Priority':  return (float)getSetting('finance_amount_priority', '150000');
            case 'Urgent':
            case 'Emergency': return (float)getSetting('finance_amount_urgent', '75000');
            default:          return (float)getSetting('finance_amount_routine', '75000');
        }
    }
}
