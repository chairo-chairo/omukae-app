-- お迎えスケジュール管理アプリ DBスキーマ
-- MySQL 5.7+ / MariaDB 10.3+

CREATE DATABASE IF NOT EXISTS omukae CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE omukae;

-- ユーザー管理
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(255)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    role          ENUM('admin','viewer') NOT NULL DEFAULT 'viewer',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 注意事項（常に最新1件を使用）
CREATE TABLE notices (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    content    TEXT NOT NULL DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 下校時間ルール（曜日別: 1=月 〜 5=金）
CREATE TABLE dismissal_rules (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week    TINYINT      NOT NULL COMMENT '1=月,2=火,3=水,4=木,5=金',
    label          VARCHAR(100) NOT NULL,
    dismissal_time TIME         NOT NULL,
    pickup_start   TIME         NOT NULL,
    pickup_end     TIME         NOT NULL,
    UNIQUE KEY uq_dow (day_of_week)
);

-- 事業者マスタ
CREATE TABLE providers (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    color_key     VARCHAR(50)  NOT NULL COMMENT 'ef / honey / arte / ...',
    phone         VARCHAR(30)  DEFAULT NULL,
    address       TEXT         DEFAULT NULL,
    service_hours VARCHAR(100) DEFAULT NULL,
    sort_order    INT          NOT NULL DEFAULT 0,
    active        TINYINT(1)   NOT NULL DEFAULT 1
);

-- 事業者×曜日ルール（送迎・早退設定）
CREATE TABLE provider_day_rules (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    provider_id      INT      NOT NULL,
    day_of_week      TINYINT  NOT NULL COMMENT '1=月,2=火,3=水,4=木,5=金',
    pickup_available TINYINT(1) NOT NULL DEFAULT 0,
    early_leave      TINYINT(1) NOT NULL DEFAULT 0,
    early_leave_time TIME     DEFAULT NULL COMMENT '早退時のお迎え時間',
    UNIQUE KEY uq_provider_dow (provider_id, day_of_week),
    FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
);

-- 日次スケジュール（月次の実データ・上書き）
CREATE TABLE schedule_entries (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    entry_date      DATE        NOT NULL,
    provider_id     INT         DEFAULT NULL COMMENT 'NULLはデフォルト通り',
    pickup_override TINYINT(1)  DEFAULT NULL COMMENT 'NULL=デフォルト / 1=送迎あり / 0=送迎なし',
    no_school       TINYINT(1)  NOT NULL DEFAULT 0 COMMENT '1=休校・学校お休み',
    note            VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY uq_date (entry_date),
    FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE SET NULL
);

-- 祝日・学校行事による休校
CREATE TABLE holidays (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE         NOT NULL UNIQUE,
    label        VARCHAR(100) NOT NULL
);

-- ママからの伝達
CREATE TABLE messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    content    TEXT       NOT NULL,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP  DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 初期データ
-- ============================================================

INSERT INTO notices (content) VALUES ('教室まで迎えにきてください');

INSERT INTO dismissal_rules (day_of_week, label, dismissal_time, pickup_start, pickup_end) VALUES
(1, '月・火・木・金（5時間授業）',           '14:35:00', '14:40:00', '14:50:00'),
(2, '月・火・木・金（5時間授業）',           '14:35:00', '14:40:00', '14:50:00'),
(3, '水曜日（5時間授業・掃除なし時間短縮）', '14:20:00', '14:25:00', '14:30:00'),
(4, '月・火・木・金（5時間授業）',           '14:35:00', '14:40:00', '14:50:00'),
(5, '月・火・木・金（5時間授業）',           '14:35:00', '14:40:00', '14:50:00');

INSERT INTO providers (name, color_key, phone, address, service_hours, sort_order) VALUES
('放課後デイ エフ',  'ef',    '050-XXXX-XXXX', '住所を入力してください', '10:00〜19:00', 1),
('ウェルハニーバン', 'honey', '086-XXX-XXXX',  '住所を入力してください', '9:00〜19:00',  2),
('あるてみす',       'arte',  NULL,            '住所を入力してください', '9:00〜18:00',  3);

-- エフ: 月（送迎なし・早退なし）
INSERT INTO provider_day_rules (provider_id, day_of_week, pickup_available, early_leave, early_leave_time) VALUES
(1, 1, 0, 0, NULL);

-- ウェルハニーバン: 火（送迎あり・早退）/ 木（送迎なし・早退）
INSERT INTO provider_day_rules (provider_id, day_of_week, pickup_available, early_leave, early_leave_time) VALUES
(2, 2, 1, 1, '13:30:00'),
(2, 4, 0, 1, '13:30:00');

-- あるてみす: 水（送迎あり）/ 金（送迎あり）
INSERT INTO provider_day_rules (provider_id, day_of_week, pickup_available, early_leave, early_leave_time) VALUES
(3, 3, 1, 0, NULL),
(3, 5, 1, 0, NULL);

INSERT INTO messages (content, is_active) VALUES ('現在、伝達事項はありません', 1);
