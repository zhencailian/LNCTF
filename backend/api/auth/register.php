<?php
/**
 * POST /api/auth/register
 * 用户注册
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('仅支持 POST 请求', 405);
}

$username = input('username');
$email    = input('email');
$password = input('password');

// 参数校验
$errors = [];
if (empty($username) || strlen($username) < 3 || strlen($username) > 32) {
    $errors[] = '用户名需 3-32 个字符';
}
if (!preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]+$/u', $username ?? '')) {
    $errors[] = '用户名只允许字母、数字、下划线和中文';
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = '邮箱格式不正确';
}
if (empty($password) || strlen($password) < 6 || strlen($password) > 128) {
    $errors[] = '密码需 6-128 个字符';
}
if (!empty($errors)) {
    jsonError('参数校验失败', 422, $errors);
}

$db = getDB();

try {
    // 检查用户名/邮箱是否已存在
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        jsonError('用户名或邮箱已被注册', 409);
    }

    // 创建用户
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare(
        "INSERT INTO users (username, email, password_hash, role, is_active, created_at)
         VALUES (?, ?, ?, 'user', 1, NOW())"
    );
    $stmt->execute([$username, $email, $passwordHash]);
    $userId = (int)$db->lastInsertId();

    // 记录日志
    logActivity($userId, 'register', 'user', $userId);

    jsonSuccess([
        'id'       => $userId,
        'username' => $username,
        'email'    => $email,
    ], '注册成功', 201);

} catch (Exception $e) {
    jsonError('注册失败: ' . $e->getMessage(), 500);
}
