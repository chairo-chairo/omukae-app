# omukae-app

お迎えスケジュール管理アプリ。PHP + MySQL で動作し、Xサーバー等の共用サーバーに対応。

## 機能

- 月次スケジュール表示（閲覧者：ログイン不要）
- 管理者ログイン → 各種マスタ・スケジュール編集
- 事業者マスタ（利用曜日・送迎・早退設定）
- 下校時間ルール管理（曜日別）
- 祝日・休校日登録
- ママからの伝達メッセージ
- 注意事項

## 必要環境

- PHP 8.1 以上
- MySQL 5.7 以上 / MariaDB 10.3 以上

## セットアップ手順

### 1. DBを作成

MySQL に接続し、`sql/schema.sql` を実行してください。

```bash
mysql -u your_user -p < sql/schema.sql
```

### 2. DB設定ファイルを作成

```bash
cp config/db.sample.php config/db.php
```

`config/db.php` を編集して実際の接続情報を入力してください。

### 3. 管理者パスワードを設定

ブラウザで `setup.php` を開き、管理者メールアドレスとパスワードを入力して生成されたSQLを実行してください。

**⚠️ パスワード設定後は `setup.php` をサーバーから削除してください。**

### 4. ファイルをアップロード

FTP等でサーバーにファイルをアップロードします。`config/db.php` は `.gitignore` に含まれているため、手動でアップロードしてください。

## ファイル構成

```
omukae-app/
├── index.php              スケジュール表示（閲覧者向け）
├── login.php              管理者ログイン
├── logout.php             ログアウト
├── setup.php              初期パスワード設定（使用後削除）
├── config/
│   └── db.sample.php      DB設定テンプレート
├── includes/
│   ├── auth.php           認証ヘルパー
│   ├── db.php             DB接続
│   └── functions.php      共通関数
├── admin/
│   ├── index.php          管理ダッシュボード
│   ├── schedule.php       月次スケジュール編集
│   ├── providers.php      事業者マスタ管理
│   ├── rules.php          下校時間ルール
│   ├── notices.php        注意事項編集
│   ├── messages.php       ママからの伝達
│   └── users.php          ユーザー管理
└── sql/
    └── schema.sql         DBスキーマ＋初期データ
```
