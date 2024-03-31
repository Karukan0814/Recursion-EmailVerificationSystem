<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

// 例外を有効にして PHPMailer を起動します。
$mail = new PHPMailer(true);

try {
    // サーバの設定
    $mail->isSMTP();                                      // SMTPを使用するようにメーラーを設定します。
    $mail->Host       = 'smtp.gmail.com';                 // GmailのSMTPサーバ
    $mail->SMTPAuth   = true;                             // SMTP認証を有効にします。
    $mail->Username   = 'your_gmail_account@gmail.com';   // SMTPユーザー名
    $mail->Password   = 'your_password';                  // SMTPパスワード
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // 必要に応じてTLS暗号化を有効にします。
    $mail->Port       = 587;                              // 接続先のTCPポート

    // 受信者
    $mail->setFrom('your_gmail_account@gmail.com', 'SpaceApp'); // 送信者設定
    $mail->addAddress('example@gmail.com', 'My User');          // 受信者を追加します。

    $mail->Subject = 'Hello World, From PHP Mailer!';

    // HTMLコンテンツ
    $mail->isHTML(); // メール形式をHTMLに設定します。
    ob_start();
    include('../Views/mail/test.php');
    $mail->Body = ob_get_clean();

    // 本文は、相手のメールプロバイダーがHTMLをサポートしていない場合に備えて、シンプルなテキストで構成されています。
    $mail->AltBody = file_get_contents('../Views/mail/test.txt');

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}