<?php
// ComposerでPHPMailerをインストールした場合、以下の行を追加
// Composerを使わない場合、PHPMailerのファイルを直接読み込む（例: require_once 'path/to/PHPMailer/src/PHPMailer.php'; など）
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP; // SMTPを使う場合

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ==== reCAPTCHA v3 検証（ここから追加） ====
    $recaptcha_secret_key = '6LdkloErAAAAAOO87ka79VZLvjhmy6ADwuW-gWRw'; // ここをあなたの「シークレットキー」に置き換える
    $recaptcha_token = isset($_POST['recaptcha_token']) ? $_POST['recaptcha_token'] : '';

    if (empty($recaptcha_token)) {
        // トークンがない場合はスパムと判断
        header('Location: index.html'); // トップページにリダイレクト
        exit;
    }

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $recaptcha_secret_key,
        'response' => $recaptcha_token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] // オプション: ユーザーのIPアドレス
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result, true);

    // reCAPTCHAの検証結果をチェック
    // scoreは0.0から1.0までの値で、1.0が人間である可能性が最も高い
    // 閾値は0.5-0.7が一般的だが、サイトの特性に合わせて調整
    $recaptcha_threshold = 0.5; // スコアの閾値（必要に応じて調整）

    if (!isset($response['success']) || $response['success'] !== true || $response['score'] < $recaptcha_threshold) {
        // reCAPTCHA検証失敗、またはスコアが低い場合はスパムと判断
        error_log("reCAPTCHA検証失敗: スコア={$response['score']}, Reason=".(json_encode($response))); // デバッグ用にログ出力
        header('Location: index.html'); // トップページにリダイレクト
        exit;
    }
    // ==== reCAPTCHA v3 検証（ここまで追加） ====


    // フォームデータの取得とサニタイズ
    $name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : 'N/A';
    $furigana = isset($_POST['furigana']) ? htmlspecialchars($_POST['furigana']) : 'N/A';
    $company = isset($_POST['company']) ? htmlspecialchars($_POST['company']) : 'N/A';
    $email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : 'N/A';
    $tel = isset($_POST['tel']) ? htmlspecialchars($_POST['tel']) : 'N/A';
    $inquiry_type = isset($_POST['inquiry_type']) ? htmlspecialchars($_POST['inquiry_type']) : 'N/A';
    $message = isset($_POST['message']) ? htmlspecialchars($_POST['message']) : 'N/A';

    // メール本文の作成
    $email_body = "ウェブサイトからのお問い合わせがありました。\n\n";
    $email_body .= "お名前: " . $name . "\n";
    $email_body .= "フリガナ: " . $furigana . "\n";
    $email_body .= "会社名・団体名: " . $company . "\n";
    $email_body .= "メールアドレス: " . $email . "\n";
    $email_body .= "電話番号: " . $tel . "\n";
    $email_body .= "お問い合わせ種別: " . $inquiry_type . "\n\n";
    $email_body .= "お問い合わせ内容:\n" . $message . "\n";

    $mail = new PHPMailer(true); // 例外を有効にする

    try {
        // デバッグ出力 (本番環境ではコメントアウトまたは無効にしてください)
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // サーバーからの詳細な通信ログを表示

        // SMTP設定
        $mail->isSMTP(); // SMTPを使用する
        // $mail->Host       = 'smtp.gmail.com';
        $mail->Host       = 'mail1024.onamae.ne.jp'; // 設定をあなたのSMTPホストに置き換えてください (例: smtp.gmail.com, smtp.mail.yahoo.co.jpなど)
        $mail->Password   = 'shakishaki_831'; // 設定をあなたのSMTPパスワードに置き換えてください
        $mail->SMTPAuth   = true; // SMTP認証を有効にする
        $mail->Username   = 'yuya.hirata@minorica-agri.com'; // 設定をあなたのSMTPユーザー名（メールアドレス）に置き換えてください
        // $mail->Password   = 'Fzsm2525';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS (STARTTLS) 暗号化を有効にする
        $mail->Port       = 587; // SSLの場合は465, STARTTLSの場合は587 (設定をあなたのポートに置き換えてください)
        $mail->CharSet = 'UTF-8'; // 文字コードをUTF-8に設定
        $mail->Encoding = 'base64'; // エンコーディングを設定



        // 送信者情報 (SMTPサーバーに許可されたFromアドレスを設定してください)
        // 多くのSMTPサーバーでは、認証に使用したアカウントのメールアドレスをFromに設定する必要があります
        $mail->setFrom('info@minorica-agri.com', '株式会社MINORICAウェブサイト'); // 設定をあなたの送信元メールアドレスと表示名に置き換えてください
        $mail->addReplyTo($email, $name); // ユーザーのメールアドレスを返信先に設定

        // 受信者情報
        $mail->addAddress('yuya.hirata@minorica-agri.com', '担当者様'); // 設定をあなたの受信者メールアドレスと表示名に置き換えてください

        // 件名
        $mail->Subject = 'ウェブサイトからのお問い合わせ: ' . $inquiry_type;

        // 本文 (プレーンテキスト)
        $mail->Body = $email_body;

        $mail->send();

         // === 自動返信メールの送信（ここから追加） ===
        // 送信設定をクリアし、新しいメールを作成
        $mail->clearAddresses();
        $mail->clearAttachments();
        $mail->clearCustomHeaders();
        // $mail->clearReplyTo();

        // 自動返信メールの送信者と受信者
        $mail->setFrom('yuya.hirata@minorica-agri.com', '株式会社MINORICA'); // 送信元を会社のメールアドレスに設定
        $mail->addAddress($email, $name); // お問い合わせされた方のメールアドレスを受信者に設定
        $mail->addReplyTo('yuya.hirata@minorica-agri.com', '株式会社MINORICA'); // 返信先を会社のアドレスに設定

        // 自動返信メールの件名
        $mail->Subject = 'お問い合わせありがとうございます（株式会社MINORICA）';

        // 自動返信メールの本文
        $auto_reply_body = "この度は、お問い合わせいただきありがとうございます。\n\n";
        $auto_reply_body .= "2営業日以内に担当者より返信いたしますので、今しばらくお待ちください。\n\n";
        $auto_reply_body .= "------ お問い合わせ内容 ------\n";
        $auto_reply_body .= $email_body; // お問い合わせ内容を引用
        $auto_reply_body .= "------------------------------\n\n";
        $auto_reply_body .= "株式会社MINORICA\n";
        $auto_reply_body .= "ウェブサイト: https://minorica-agri.com\n"; // 必要に応じて正しいURLに
        $auto_reply_body .= "メール: yuya.hirata@minorica-agri.com\n"; // 必要に応じて正しいアドレスに

        $mail->Body = $auto_reply_body;

        $mail->send(); // 自動返信メールを送信
        // === 自動返信メールの送信（ここまで追加） ===

        // 送信成功時の処理
        echo "<!DOCTYPE html>";
        echo "<html lang='ja'>";
        echo "<head>";
        echo "    <meta charset='UTF-8'>";
        echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        echo "    <title>お問い合わせ完了</title>";
        echo "    <style>";
        echo "        body { font-family: 'Arial', sans-serif; text-align: center; padding-top: 50px; background-color: #f9f9f9; color: #333; }";
        echo "        .container { background-color: #fff; margin: 50px auto; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 500px; }";
        echo "        h1 { color: #327d31; }";
        echo "        p { margin-bottom: 20px; }";
        echo "        .btn { display: inline-block; background-color: #327d31; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; }";
        echo "        .btn:hover { background-color: #2c6f2c; }";
        echo "    </style>";
        echo "</head>";
        echo "<body>";
        echo "    <div class='container'>";
        echo "        <h1>お問い合わせありがとうございます！</h1>";
        echo "        <p>お問い合わせ内容が正常に送信されました。</p>";
        echo "        <p>内容を確認後、担当者よりご連絡させていただきます。</p>";
        echo "        <a href='index.html' class='btn'>トップページに戻る</a>";
        echo "    </div>";
        echo "</body>";
        echo "</html>";

    } catch (Exception $e) {
        // 送信失敗時の処理
        // エラーの詳細をログに残す（本番環境ではユーザーには表示しない）
        error_log("メール送信エラー: {$mail->ErrorInfo}");

        echo "<!DOCTYPE html>";
        echo "<html lang='ja'>";
        echo "<head>";
        echo "    <meta charset='UTF-8'>";
        echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        echo "    <title>送信エラー</title>";
        echo "    <style>";
        echo "        body { font-family: 'Arial', sans-serif; text-align: center; padding-top: 50px; background-color: #f9f9f9; color: #333; }";
        echo "        .container { background-color: #fff; margin: 50px auto; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 500px; }";
        echo "        h1 { color: #d9534f; }";
        echo "        p { margin-bottom: 20px; }";
        echo "        .btn { display: inline-block; background-color: #327d31; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; }";
        echo "        .btn:hover { background-color: #2c6f2c; }";
        echo "    </style>";
    }
} else {
    // POSTリクエスト以外で直接アクセスされた場合
    header("Location: index.html"); // トップページにリダイレクト
    exit;
}
?>