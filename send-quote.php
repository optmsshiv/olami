<?php
// ============================================================
// OLAMI — Contact Form Backend (PHP + PHPMailer + Bluehost Domain SMTP)
// Sends FROM info@olamibranding.us | Admin lead -> info@ + Gmail | Visitor gets confirmation
// File: send-quote.php  →  upload next to contact.html
// No watermark. Admin + Visitor both get premium HTML emails.
// ============================================================
header('Content-Type: application/json; charset=utf-8');

// --- Only allow POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// --- CONFIG — EDIT THE PASSWORD ONLY ---
// 1) Create this mailbox FIRST in Bluehost cPanel -> Email Accounts -> info@olamibranding.us
// 2) Paste that mailbox password below (NOT your cPanel password, NOT a Gmail password)
$SMTP_HOST = 'mail.olamibranding.us';        // Bluehost SMTP
$SMTP_USER = 'info@olamibranding.us';         // Domain mailbox that SENDS both emails
$SMTP_PASS = 'Secure@135'; // <-- CHANGE THIS to the mailbox password you created in Bluehost
$SMTP_PORT = 465;                              // 465 = SSL (Bluehost default). Use 587 + TLS if 465 fails.
$FROM_EMAIL = 'info@olamibranding.us';
$FROM_NAME  = 'OLAMI Signage & Branding';
// Admin lead goes to BOTH inboxes — domain inbox + Gmail backup
$ADMIN_EMAILS = ['info@olamibranding.us', 'olamibranding@gmail.com'];

// --- Collect + clean inputs ---
$name     = trim($_POST['from_name']     ?? '');
$business = trim($_POST['business_name'] ?? '');
$email    = trim($_POST['from_email']    ?? '');
$phone    = trim($_POST['phone']         ?? '');
$service  = trim($_POST['service']       ?? '');
$city     = trim($_POST['city']          ?? '');
$message  = trim($_POST['message']       ?? '');
$honeypot = trim($_POST['website']       ?? ''); // spam trap, must stay empty

// --- Spam trap: bots fill hidden field → silently "succeed", send nothing ---
if ($honeypot !== '') {
    echo json_encode(['ok' => true]);
    exit;
}

// --- Validation ---
if ($name === '' || $business === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Please fill name, business and a valid email.']);
    exit;
}

// --- Escape for safe HTML output ---
$e = fn($v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$name_e = $e($name); $business_e = $e($business); $email_e = $e($email);
$phone_e = $e($phone); $service_e = $e($service); $city_e = $e($city);
$message_e = nl2br($e($message));

// --- Shared email styles ---
$wrapTop = '<table width="100%" cellpadding="0" cellspacing="0" style="background:#F4F1EA;padding:24px 0;font-family:Arial,Helvetica,sans-serif"><tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:14px;overflow:hidden"><tr><td style="background:#0F2340;padding:28px 32px;text-align:center"><div style="font-size:28px;font-weight:800;letter-spacing:4px;color:#E8C97A">OLAMI</div><div style="font-size:11px;letter-spacing:3px;color:#ffffff;margin-top:4px">SIGNAGE &amp; BRANDING</div></td></tr><tr><td style="padding:28px 32px">';
$wrapBottom = '</td></tr><tr><td style="background:#0F2340;padding:16px 32px;text-align:center;color:#aaa;font-size:12px">OLAMI Signage &amp; Branding &bull; Serving All of Texas &bull; +1 (832) 807-0762</td></tr></table></td></tr></table>';
$row = fn($label, $val) => '<tr><td style="padding:9px 0;border-bottom:1px solid #eee;width:150px;color:#888">' . $label . '</td><td style="padding:9px 0;border-bottom:1px solid #eee;font-weight:bold">' . $val . '</td></tr>';

// --- EMAIL 1: ADMIN (full lead) ---
$adminBody = $wrapTop
  . '<h2 style="color:#0F2340;margin:0 0 6px;font-size:22px">New Quote Request</h2>'
  . '<p style="color:#666;margin:0 0 20px;font-size:14px">A visitor just submitted the contact form.</p>'
  . '<table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#333">'
  . $row('Name', $name_e)
  . $row('Business', $business_e)
  . $row('Email', '<a href="mailto:' . $email_e . '" style="color:#0F2340">' . $email_e . '</a>')
  . $row('Phone', $phone_e)
  . $row('Service Needed', $service_e)
  . $row('City', $city_e)
  . '</table>'
  . '<p style="background:#FAF8F3;border-left:4px solid #E8C97A;padding:14px 16px;font-size:14px;line-height:1.6;color:#333;margin:20px 0 0">' . $message_e . '</p>'
  . '<table cellpadding="0" cellspacing="0" style="margin-top:24px"><tr>'
  . '<td style="background:#E8C97A;border-radius:999px;padding:12px 22px"><a href="mailto:' . $email_e . '" style="color:#111;text-decoration:none;font-weight:bold;font-size:14px">Reply to Lead</a></td>'
  . '<td style="padding-left:10px;background:#0F2340;border-radius:999px;padding:12px 22px"><a href="tel:' . $phone_e . '" style="color:#fff;text-decoration:none;font-weight:bold;font-size:14px">Call Lead</a></td>'
  . '</tr></table>'
  . $wrapBottom;

// --- EMAIL 2: VISITOR (premium confirmation) ---
$visitorBody = $wrapTop
  . '<h2 style="color:#0F2340;margin:0 0 6px;font-size:22px">Thank you, ' . $name_e . '!</h2>'
  . '<p style="color:#555;font-size:15px;line-height:1.6;margin:0 0 18px">We received your quote request and will reply within <b>24 hours on business days</b>. Here is a summary of what you sent us:</p>'
  . '<table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#333">'
  . $row('Business', $business_e)
  . $row('Service Needed', $service_e)
  . $row('City', $city_e)
  . $row('Your Message', $message_e)
  . '</table>'
  . '<p style="background:#FAF8F3;border-left:4px solid #E8C97A;padding:14px 16px;font-size:14px;line-height:1.7;color:#333;margin:20px 0 0"><b>What happens next?</b><br>1. Our team reviews your project &amp; size<br>2. We call or email you with questions &amp; a free quote<br>3. Design, permits, fabrication &amp; install — one partner</p>'
  . '<table cellpadding="0" cellspacing="0" style="margin-top:24px"><tr>'
  . '<td style="background:#E8C97A;border-radius:999px;padding:12px 22px"><a href="tel:+18328070762" style="color:#111;text-decoration:none;font-weight:bold;font-size:14px">Call Us: +1 (832) 807-0762</a></td>'
  . '<td style="padding-left:10px;background:#0F2340;border-radius:999px;padding:12px 22px"><a href="mailto:info@olamibranding.us" style="color:#fff;text-decoration:none;font-weight:bold;font-size:14px">Email Us</a></td>'
  . '</tr></table>'
  . $wrapBottom;

// --- Send via PHPMailer + Gmail SMTP ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

try {
    // 1) ADMIN EMAIL — goes to BOTH info@olamibranding.us AND olamibranding@gmail.com
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = $SMTP_USER;
    $mail->Password = $SMTP_PASS;
    $mail->SMTPSecure = ($SMTP_PORT == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $SMTP_PORT;
    $mail->setFrom($FROM_EMAIL, 'OLAMI Website');
    foreach ($ADMIN_EMAILS as $a) { $mail->addAddress($a); }
    $mail->addReplyTo($email, $name); // Reply button in Gmail replies to the visitor
    $mail->isHTML(true);
    $mail->Subject = 'New Quote Request — ' . $business . ' (' . $service . ')';
    $mail->Body = $adminBody;
    $mail->AltBody = "New Quote Request\nName: $name\nBusiness: $business\nEmail: $email\nPhone: $phone\nService: $service\nCity: $city\nMessage: $message";
    $mail->send();

    // 2) VISITOR EMAIL — FROM info@olamibranding.us
    $mail2 = new PHPMailer(true);
    $mail2->isSMTP();
    $mail2->Host = $SMTP_HOST;
    $mail2->SMTPAuth = true;
    $mail2->Username = $SMTP_USER;
    $mail2->Password = $SMTP_PASS;
    $mail2->SMTPSecure = ($SMTP_PORT == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail2->Port = $SMTP_PORT;
    $mail2->setFrom($FROM_EMAIL, 'OLAMI Signage & Branding');
    $mail2->addAddress($email, $name);
    $mail2->addReplyTo($FROM_EMAIL, 'OLAMI Signage & Branding');
    $mail2->isHTML(true);
    $mail2->Subject = 'Thank you, ' . $name . ' — We received your quote request';
    $mail2->Body = $visitorBody;
    $mail2->AltBody = "Thank you, $name! We received your quote request for $service and will reply within 24 hours on business days. — OLAMI Signage & Branding, +1 (832) 807-0762";
    $mail2->send();

    echo json_encode(['ok' => true]);
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Mail failed. Please call +1 (832) 807-0762.']);
}
