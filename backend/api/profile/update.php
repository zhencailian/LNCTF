<?php
/**
 * POST /api/profile/update
 * 更新个人资料
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('仅支持 POST 请求', 405);
}

$payload = requireAuth();
$userId = (int)$payload['sub'];
$db = getDB();

$username  = input('username');
$email     = input('email');
$password  = input('password');
$avatarUrl = input('avatar_url');

$updates = [];
$params  = [];

if ($username) {
    if (strlen($username) < 3 || strlen($username) > 32) {
        jsonError('用户名需 3-32 个字符', 422);
    }
    // 检查重名
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $userId]);
    if ($stmt->fetch()) {
        jsonError('用户名已被使用', 409);
    }
    $updates[] = 'username = ?';
    $params[] = $username;
}

if ($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('邮箱格式不正确', 422);
    }
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        jsonError('邮箱已被使用', 409);
    }
    $updates[] = 'email = ?';
    $params[] = $email;
}

if ($password) {
    if (strlen($password) < 6) {
        jsonError('密码至少 6 个字符', 422);
    }
    $updates[] = 'password_hash = ?';
    $params[] = password_hash($password, PASSWORD_BCRYPT);
}

if ($avatarUrl !== null) {
    $updates[] = 'avatar_url = ?';
    $params[] = $avatarUrl;
}

if (empty($updates)) {
    jsonError('没有要更新的内容', 422);
}

$updates[] = 'updated_at = NOW()';
$params[] = $userId;

$sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute($params);

logActivity($userId, 'update_profile', 'user', $userId);
jsonSuccess(null, '资料更新成功');
