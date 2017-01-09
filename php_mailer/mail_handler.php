<?php
//  Require connection information and PHPMailer library
require_once('email_config.php');
require('phpmailer/PHPMailer/PHPMailerAutoload.php');

//  Validate POST inputs
$message = [];
$output = [
    'success' => null,
    'messages' => []
];
//  Sanitize name field
$message['name'] = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
if (empty($message['name'])) {
    $output['success'] = false;
    $output['messages'][] = 'missing name key';
}
//  Validate email field
$message['email'] = filter_var($_POST['email'], FILTER_VALIDATE_REGEXP, ['options'=>['regexp'=>'/^(?=[A-Z0-9][A-Z0-9@._%+-]{5,253}$)[A-Z0-9._%+-]{1,64}@(?:(?=[A-Z0-9-]{1,63}\.)[A-Z0-9]+(?:-[A-Z0-9]+)*\.){1,8}[A-Z]{2,63}$/i']]);
if (empty($message['email'])) {
    $output['success'] = false;
    $output['messages'][] = 'invalid email key';
}
//  Sanitize phone field
$message['phone'] = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
if (empty($message['phone'])) {
//    $output['success'] = false;
    $output['messages'][] = 'missing phone key';
}
//  Sanitize message field
$message['message'] = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
if (empty($message['message'])) {
    $output['success'] = false;
    $output['messages'][] = 'missing message key';
}
if ($output['success'] !== null) {
    http_response_code(400);
    echo json_encode($output);
    exit();
}
$message['message'] .= "\n\nPhone Number: " . $message['phone'];
$message['subject'] = str_replace(["\r", "\n"], [" ", " "], $message['message']); //  Remove newline characters from email subject
$message['subject'] = trim($message['subject']); //  Trim whitespace on ends
$message['subject'] = preg_replace('/[ \t]{2,}/', ' ', $message['subject']); //  Condense whitespace
if (strlen($output['subject']) > 78) {
    $message['subject'] = substr($message['subject'], 0, 75) . '...'; //  Limit size of subject to 78 characters
}

$message['message'] = nl2br($message['message']); //  Convert newline characters to line break html tags

//  Set up email object
$mail = new PHPMailer;
//$mail->SMTPDebug = 3;                               // Enable verbose debug output

$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
$mail->SMTPAuth = true;                               // Enable SMTP authentication

$mail->Username = EMAIL_USER;                 // SMTP username
$mail->Password = EMAIL_PASS;                 // SMTP password
$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
$mail->Port = 587;                                    // TCP port to connect to
$options = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    )
);
$mail->smtpConnect($options);
$mail->From = $message['email'];
$mail->FromName = $message['name'];
$mail->addAddress(EMAIL_USER, EMAIL_USERNAME);     // Add a recipient
$mail->addReplyTo($message['email'], $message['name']);

$mail->isHTML(true);                                  // Set email format to HTML

$mail->Subject = $message['subject'];
$mail->Body    = $message['message'];
$mail->AltBody = htmlentities($message['message']);

//  Attempt email send, output result to client
if(!$mail->send()) {
    http_response_code(400);  //comment out when debugging
    $output['success'] = false;
    $output['messages'][] = $mail->ErrorInfo;
} else {
    $output['success'] = true;
}
echo json_encode($output);
?>
