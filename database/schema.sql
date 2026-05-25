-- ============================================================
-- LNCTF 训练平台 · 完整数据库建表脚本
-- 引擎: MySQL 8.0+  |  字符集: utf8mb4  |  排序: utf8mb4_unicode_ci
-- ============================================================

CREATE DATABASE IF NOT EXISTS lnctf
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;
USE lnctf;

-- ============================================================
-- 1. 用户表
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(64)     NOT NULL UNIQUE,
    email           VARCHAR(128)    NOT NULL UNIQUE,
    password_hash   VARCHAR(255)    NOT NULL COMMENT 'bcrypt 哈希',
    role            ENUM('admin','user') NOT NULL DEFAULT 'user',
    team_id         INT UNSIGNED    NULL COMMENT '所属队伍ID',
    avatar_url      VARCHAR(255)    NULL,
    score           INT             NOT NULL DEFAULT 0 COMMENT '总分（冗余/排行用）',
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    last_login_at   DATETIME        NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_team_id (team_id),
    INDEX idx_users_score (score DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. 分类表
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(64)     NOT NULL UNIQUE COMMENT 'Web/Pwn/Reverse/Crypto/Misc',
    description TEXT            NULL,
    icon        VARCHAR(64)     NULL COMMENT '图标标识符',
    sort_order  INT             NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 默认分类
INSERT INTO categories (name, description, icon, sort_order) VALUES
    ('Web',      'Web 安全：SQL注入、XSS、SSRF、文件上传等',     '🌐', 1),
    ('Pwn',      '二进制漏洞利用：栈溢出、堆利用、ROP等',        '💥', 2),
    ('Reverse',  '逆向工程：PE/ELF分析、脱壳、算法还原等',      '🔍', 3),
    ('Crypto',   '密码学：古典密码、对称/非对称加密、哈希等',    '🔐', 4),
    ('Misc',     ' miscellaneous：隐写、流量分析、社会工程等',   '📦', 5)
ON DUPLICATE KEY UPDATE name=name;

-- ============================================================
-- 3. 题目表
-- ============================================================
CREATE TABLE IF NOT EXISTS challenges (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    title               VARCHAR(200)    NOT NULL,
    description         TEXT            NOT NULL,
    category_id         INT UNSIGNED    NOT NULL,
    difficulty          ENUM('easy','medium','hard','expert') NOT NULL DEFAULT 'easy',
    score               INT             NOT NULL DEFAULT 100 COMMENT '基础分值',
    flag                VARCHAR(255)    NOT NULL COMMENT 'bcrypt 哈希存储',
    flag_hint           VARCHAR(255)    NULL COMMENT 'Flag 格式提示，如 LNCTF{...}',
    attachment_url      VARCHAR(255)    NULL COMMENT '附件相对路径',
    source_code_url     VARCHAR(255)    NULL COMMENT '源码/Dockerfile路径',
    is_active           TINYINT(1)      NOT NULL DEFAULT 1,
    is_dockerized       TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '是否动态容器化',
    docker_image        VARCHAR(128)    NULL,
    docker_port_mapping VARCHAR(64)     NULL,
    solve_count         INT             NOT NULL DEFAULT 0 COMMENT '解题人数',
    first_blood_id      INT UNSIGNED    NULL COMMENT '一血获得者',
    created_by          INT UNSIGNED    NOT NULL COMMENT '创建者（管理员）',
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id)    REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (first_blood_id) REFERENCES users(id)     ON DELETE SET NULL,
    FOREIGN KEY (created_by)     REFERENCES users(id)     ON DELETE RESTRICT,

    INDEX idx_challenges_category (category_id),
    INDEX idx_challenges_active (is_active),
    INDEX idx_challenges_score (score),
    INDEX idx_challenges_difficulty (difficulty),
    FULLTEXT INDEX ft_challenges_search (title, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. 标签表
-- ============================================================
CREATE TABLE IF NOT EXISTS tags (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(32)     NOT NULL UNIQUE COMMENT '如 sql-injection, xxe, rop',
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. 题目-标签关联表
-- ============================================================
CREATE TABLE IF NOT EXISTS challenge_tags (
    challenge_id    INT UNSIGNED NOT NULL,
    tag_id          INT UNSIGNED NOT NULL,
    PRIMARY KEY (challenge_id, tag_id),
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id)        REFERENCES tags(id)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. 队伍表（★ 必须于 submissions/solves 之前创建）
-- ============================================================
CREATE TABLE IF NOT EXISTS teams (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(128)    NOT NULL UNIQUE,
    description     TEXT            NULL,
    owner_id        INT UNSIGNED    NOT NULL,
    invite_code     VARCHAR(64)     NOT NULL UNIQUE,
    score           INT             NOT NULL DEFAULT 0,
    member_count    TINYINT UNSIGNED NOT NULL DEFAULT 1,
    max_members     TINYINT UNSIGNED NOT NULL DEFAULT 5,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. 队伍成员表
-- ============================================================
CREATE TABLE IF NOT EXISTS team_members (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    team_id     INT UNSIGNED    NOT NULL,
    user_id     INT UNSIGNED    NOT NULL,
    role        ENUM('owner','member') NOT NULL DEFAULT 'member',
    joined_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    UNIQUE KEY uk_team_user (team_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. 提交记录表
-- ============================================================
CREATE TABLE IF NOT EXISTS submissions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED    NOT NULL,
    challenge_id    INT UNSIGNED    NOT NULL,
    team_id         INT UNSIGNED    NULL,
    submitted_flag  VARCHAR(255)    NOT NULL COMMENT '提交的明文Flag',
    is_correct      TINYINT(1)      NOT NULL DEFAULT 0,
    ip_address      VARCHAR(45)     NULL,
    solved_at       DATETIME        NULL COMMENT '首次正确时记录',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id)      REFERENCES teams(id)      ON DELETE SET NULL,

    INDEX idx_sub_user_challenge (user_id, challenge_id),
    INDEX idx_sub_challenge_time (challenge_id, created_at),
    INDEX idx_sub_correct (is_correct)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. 解题记录表（一题一用户/队伍只记一次）
-- ============================================================
CREATE TABLE IF NOT EXISTS solves (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED    NOT NULL,
    challenge_id    INT UNSIGNED    NOT NULL,
    team_id         INT UNSIGNED    NULL,
    score_earned    INT             NOT NULL DEFAULT 0 COMMENT '实际获得分数',
    solve_order     INT             NOT NULL COMMENT '第几个解出（用于动态计分）',
    solved_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id)      REFERENCES teams(id)      ON DELETE SET NULL,

    UNIQUE KEY uk_user_challenge (user_id, challenge_id),
    UNIQUE KEY uk_team_challenge (team_id, challenge_id),
    INDEX idx_solves_challenge_order (challenge_id, solve_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. 公告表
-- ============================================================
CREATE TABLE IF NOT EXISTS notices (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200)    NOT NULL,
    content     TEXT            NOT NULL,
    is_pinned   TINYINT(1)      NOT NULL DEFAULT 0,
    created_by  INT UNSIGNED    NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_notices_pinned (is_pinned DESC, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. WriteUp 表
-- ============================================================
CREATE TABLE IF NOT EXISTS writeups (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    challenge_id    INT UNSIGNED    NOT NULL,
    user_id         INT UNSIGNED    NOT NULL,
    content         LONGTEXT        NOT NULL COMMENT 'Markdown 内容',
    is_public       TINYINT(1)      NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE,

    UNIQUE KEY uk_challenge_user (challenge_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. 操作日志表
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_log (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED    NULL,
    action      VARCHAR(64)     NOT NULL COMMENT 'login / submit / create_challenge 等',
    target_type VARCHAR(32)     NULL COMMENT 'challenge / user / team / notice',
    target_id   INT UNSIGNED    NULL,
    detail      JSON            NULL,
    ip_address  VARCHAR(45)     NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_log_user (user_id),
    INDEX idx_log_action (action),
    INDEX idx_log_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. 爬虫日志表
-- ============================================================
CREATE TABLE IF NOT EXISTS crawl_logs (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    source      VARCHAR(64)     NOT NULL COMMENT '数据源标识',
    source_url  VARCHAR(255)    NULL,
    total       INT             NOT NULL DEFAULT 0,
    success     INT             NOT NULL DEFAULT 0,
    skipped     INT             NOT NULL DEFAULT 0,
    failed      INT             NOT NULL DEFAULT 0,
    started_at  DATETIME        NOT NULL,
    finished_at DATETIME        NULL,
    status      ENUM('running','completed','failed') NOT NULL DEFAULT 'running',
    log_detail  JSON            NULL,
    INDEX idx_crawl_status (status),
    INDEX idx_crawl_started (started_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 初始化管理员账户（密码: password）
-- 生产环境请立即修改！
-- ============================================================
INSERT INTO users (username, email, password_hash, role, is_active)
VALUES ('admin', 'admin@lnctf.local',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin', 1)
ON DUPLICATE KEY UPDATE username=username;
