# Recursion-Email Verification System


# 概要

教材のcompart-parts アプリケーションを拡張し、新規ユーザー登録時にユーザーのメールアドレスに署名付きURLを送信してメールアドレスの有効性を確認する機能を追加した。

# 機能

1. ユーザー登録時にPHPMailerを使用してメールアドレスに署名付きURLを送信する。
  
2. ユーザーが署名付きURLをクリックすると、ミドルウェアを介して URL 署名の有効性の確認、リンクの期限確認を行う。(GET ルート /verify/email)
   
3. 検証後、登録されたメールアドレスの有効化を行う。

4. リンクが期限切れの場合、署名付きURLの再送信画面に遷移。

5. 未検証ユーザーが要ログイン画面に遷移しようとすると、署名付きURLの再送信画面に自動遷移。
   



# 開発環境の構築

1. Postfix のインストール
以下を参考に設定
https://www.tutorialspoint.com/configure-postfix-with-gmail-on-ubuntu

2. 環境変数ファイルの準備
   　.env ファイルをルートフォルダ直下に用意し、以下を記述して保存する。

```
DATABASE_NAME=practice_db
DATABASE_USER=任意のユーザー名
DATABASE_USER_PASSWORD=任意のパスワード
MEMCACHED_HOST="localhost"
MEMCACHED_PORT=11211
DATABASE_DRIVER="memcached"
SIGNATURE_SECRET_KEY=任意のキー
APP_PASS=Googleアカウントのアプリパスワード
SENDER_EMAIL=Gmailアドレス


```

5. DB Migration 実行
   　以下を実行して初期テーブルの構築。

```
docker-compose exec web php console migrate --init
```

5. 初期データ挿入
   　以下を実行して初期テーブルの構築。

```
docker-compose exec web php console migrate --init
```


6. 動作確認
   　publicフォルダに移動して以下を実行し、localhost:8000/register　にアクセスして確認

```
   php -S localhost:8000 index.php

```
