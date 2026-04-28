<?php
require_once 'auth.php';
requireLogin();
require_once 'csv_helper.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mailer.php'); exit;
}

$to       = trim($_POST['to']       ?? '');
$subject  = trim($_POST['subject']  ?? '');
$body     = trim($_POST['body']     ?? '');
$priority = (int)($_POST['priority'] ?? 3);

if (!$to || !$subject || !$body) {
    setFlash('Please fill in all required fields.', 'error');
    header('Location: mailer.php'); exit;
}

// Save uploaded attachments
function saveUpload(array $file, string $dir): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
    $path = $dir . uniqid('f_', true) . '_' . $safe;
    return move_uploaded_file($file['tmp_name'], $path) ? $path : null;
}

$attachments = [];
$attachNames = [];
if (!empty($_FILES['attachments']['name'][0])) {
    foreach ($_FILES['attachments']['name'] as $i => $name) {
        $path = saveUpload([
            'name'     => $name,
            'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
            'error'    => $_FILES['attachments']['error'][$i],
        ], UPLOAD_DIR);
        if ($path) {
            $attachments[] = ['path' => $path, 'name' => $name];
            $attachNames[] = $name;
        }
    }
}

$htmlBody = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>'
    . '<body style="font-family:Arial,sans-serif;font-size:14px;color:#222;line-height:1.7;">'
    . $body . '</body></html>';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
    $mail->Priority   = $priority;

    foreach (explode(',', $to) as $addr) {
        $addr = trim($addr);
        if (filter_var($addr, FILTER_VALIDATE_EMAIL)) $mail->addAddress($addr);
    }

    $mail->Subject = $subject;
    $mail->isHTML(true);
    $mail->Body    = $htmlBody;
    $mail->AltBody = strip_tags($body);

    foreach ($attachments as $att) {
        $mail->addAttachment($att['path'], $att['name']);
    }

    $mail->send();

    mailLog(
        $_SESSION['user_id'], $to, $subject,
        strip_tags($body), (string)$priority,
        implode(', ', $attachNames)
    );
    setFlash('Email sent successfully! 🎉', 'success');

} catch (Exception $e) {
    setFlash('Send error: ' . $mail->ErrorInfo, 'error');
    logEvent('mail_error', $_SESSION['user_id'], $mail->ErrorInfo);
} finally {
    foreach ($attachments as $att) {
        if (file_exists($att['path'])) unlink($att['path']);
    }
}

header('Location: mailer.php'); exit;
