<?php
// 引入 Composer 的自動加載文件
require 'vendor/autoload.php';

// 使用 PHPMailer 和 Exception 類別
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 創建 PHPMailer 實例
$mail = new PHPMailer(true); // 啟用異常處理

try {
    // 設定郵件發送方式為 SMTP
    $mail->isSMTP();
    
    // 設定 SMTP 伺服器（使用 MailHog）
    $mail->Host = 'localhost';  // 使用 MailHog 的 SMTP 伺服器
    $mail->Port = 1025;         // 使用 MailHog 的 SMTP 端口
    $mail->SMTPAuth = false;    // 不需要身份驗證

    // 設定發件人
    $mail->setFrom('from@example.com', 'Mailer');
    
    // 設定收件人
    $mail->addAddress('recipient@example.com', 'Joe User');  // 收件人
    
    // 設定郵件主題
    $mail->Subject = 'Test Email';

    // 設定郵件內容
    $mail->Body = 'This is a test email sent via MailHog.';

    // 發送郵件
    $mail->send();
    echo 'Message has been sent'; // 如果成功，顯示此訊息
} catch (Exception $e) {
    // 捕捉異常並顯示錯誤訊息
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
