<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/SMTP.php';

header("Content-Type: application/json");

// Allow only POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
    exit;
}

// Read JSON body
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data["fields"])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

$fromName = $data["fromName"] ?? "Website Contact";
$subject = $data["subject"] ?? "New Enquiry";
$fields = $data["fields"];

$SMTP_HOST = "smtp-relay.brevo.com";
$SMTP_PORT = 587;
$SMTP_USER = "814ed7001@smtp-brevo.com";
$SMTP_PASS = "yEdzUZ3MqvpQ9fTj";

// HTML email table
$htmlContent = "<table border='1' cellspacing='0' cellpadding='10' style='border-collapse: collapse; width: 100%; max-width: 600px; margin: 20px 0; border: 1px solid #ddd;'>";
foreach ($fields as $field) {
    $label = htmlspecialchars($field['label'] ?? '');
    $value = nl2br(htmlspecialchars($field['value'] ?? ''));
    $htmlContent .= "
        <tr>
            <td style='padding: 12px; border: 1px solid #ddd; background-color: #f4f4f4;'>$label</td>
            <td style='padding: 12px; border: 1px solid #ddd;'>$value</td>
        </tr>";
}
$htmlContent .= "</table>";

// Send with PHPMailer
$mail = new PHPMailer(true);

// $mail->SMTPDebug = SMTP::DEBUG_SERVER;
// $mail->Debugoutput = function ($str, $level) {
//     file_put_contents(
//         __DIR__ . "/phpmailer_debug.log",
//         date("Y-m-d H:i:s") . " [level $level] $str\n",
//         FILE_APPEND
//     );
// };

try {
    $mail->isSMTP();
    $mail->Host       = $SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = $SMTP_USER;
    $mail->Password   = $SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $SMTP_PORT;

    $mail->setFrom("verligte@gmail.com", $fromName);
    $mail->addAddress("info@amshr.com");
    $mail->addAddress("dheeraj@amshr.com");

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $htmlContent;

    if ($mail->send()) {
        echo json_encode(["status" => "success", "message" => "Email sent successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $mail->ErrorInfo]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $mail->ErrorInfo ?: $e->getMessage()
    ]);
}
