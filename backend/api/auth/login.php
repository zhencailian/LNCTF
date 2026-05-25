<?php
/**
 * POST /api/auth/login
 * 用户登录
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('仅支持 POST 请求', 405);
}

$username = input('username');
$password = input('password');

if (empty($username) || empty($password)) {
    jsonError('请输入用户名和密码', 422);
}

// 数据库连接保护
try {
    $db = getDB();
} catch (Exception $e) {
    jsonError('数据库连接失败，请检查 MySQL 是否已启动', 500);
}

// 支持用户名或邮箱登录
$stmt = $db->prepare(
    "SELECT id, username, email, password_hash, role, avatar_url, score, is_active
     FROM users WHERE (username = ? OR email = ?) LIMIT 1"
);
$stmt->execute([$username, $username]);
$user = $stmt->fetch();

if (!$user) {
    jsonError('用户名或密码错误', 401);
}

if (!$user['is_active']) {
    jsonError('账户已被禁用，请联系管理员', 403);
}

if (!password_verify($password, $user['password_hash'])) {
    // 记录失败尝试
    logActivity($user['id'], 'login_failed', 'user', $user['id']);
    jsonError('用户名或密码错误', 401);
}

// 更新最后登录时间
$stmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
$stmt->execute([$user['id']]);

// 生成 Token
$token = generateToken((int)$user['id'], $user['role']);

// 记录日志
logActivity($user['id'], 'login', 'user', $user['id']);

jsonSuccess([
    'token' => $token,
    'user'  => [
        'id'         => (int)$user['id'],
        'username'   => $user['username'],
        'email'      => $user['email'],
        'role'       => $user['role'],
        'avatar_url' => $user['avatar_url'],
        'score'      => (int)$user['score'],
    ],
], '登录成功');
