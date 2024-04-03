<?php

namespace Helpers;


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Helpers\Settings;


require '../vendor/autoload.php';




class SendVerificationMail
{
    public static function sendVerification(string $toAddress, string $toName ,string $verificationUrl)
    {

        // 例外を有効にして PHPMailer を起動します。
        $mail = new PHPMailer(true);

        try {
            // サーバの設定
            $mail->isSMTP();                                      // SMTPを使用するようにメーラーを設定します。
            $mail->Host       = 'smtp.gmail.com';                 // GmailのSMTPサーバ
            $mail->SMTPAuth   = true;                             // SMTP認証を有効にします。
            $mail->Username   = Settings::env('SENDER_EMAIL');   // SMTPユーザー名
            $mail->Password   = Settings::env('APP_PASS');                  // SMTPパスワード
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // 必要に応じてTLS暗号化を有効にします。
            $mail->Port       = 587;                              // 接続先のTCPポート

            // 受信者
            $mail->setFrom(Settings::env('SENDER_EMAIL'), 'Test Sender'); // 送信者設定
            $mail->addAddress($toAddress, $toName);          // 受信者を追加します。

            $mail->Subject = 'User Info Verification Mail';

            // HTMLコンテンツ
            $mail->isHTML(); // メール形式をHTMLに設定します。
            ob_start();
            //test.phpに$verificationUrlを引き渡す
            $verificationLink = $verificationUrl;
            include('../Views/mail/verification-mail.php');
            $mail->Body = ob_get_clean();

            // 本文は、相手のメールプロバイダーがHTMLをサポートしていない場合に備えて、シンプルなテキストで構成されています。
            $mail->AltBody = "Please click a link below to verify your account.  ". $verificationUrl;

            $mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}
