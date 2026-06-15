<?php
/**
 * LNCTF 数据库配置
 */

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'lnctf');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '123456');
define('DB_CHARSET', 'utf8mb4');

// JWT Secret（生产环境请修改）
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'lnctf_jwt_secret_change_in_production_2024');
define('JWT_EXPIRY', 86400 * 7); // Token 有效期 7 天

// 题目附件路径
define('UPLOAD_BASE', dirname(__DIR__) . '/public/uploads');
define('UPLOAD_URL_BASE', '/uploads');

/**
 * 获取 PDO 数据库连接
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $pdo = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }

    return $pdo;
}