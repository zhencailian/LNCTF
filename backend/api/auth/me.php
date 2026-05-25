<?php
/**
 * GET /api/auth/me
 * 获取当前登录用户信息
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('仅支持 GET 请求', 405);
}

$payload = requireAuth();

// 数据库连接保护
try {
    $db = getDB();
} catch (Exception $e) {
    jsonError('数据库连接失败', 500);
}

$stmt = $db->prepare(
    "SELECT u.id, u.username, u.email, u.role, u.avatar_url, u.score, u.is_active,
            u.last_login_at, u.created_at, u.team_id, t.name AS team_name
     FROM users u
     LEFT JOIN teams t ON u.team_id = t.id
     WHERE u.id = ?"
);
$stmt->execute([$payload['sub']]);
$user = $stmt->fetch();

if (!$user) {
    jsonError('用户不存在', 404);
}

// 获取统计信息
$stmt = $db->prepare("SELECT COUNT(*) AS solved_count FROM solves WHERE user_id = ?");
$stmt->execute([$payload['sub']]);
$solvedCount = (int)$stmt->fetch()['solved_count'];

jsonSuccess([
    'id'           => (int)$user['id'],
    'username'     => $user['username'],
    'email'        => $user['email'],
    'role'         => $user['role'],
    'avatar_url'   => $user['avatar_url'],
    'score'        => (int)$user['score'],
    'is_active'    => (bool)$user['is_active'],
    'team_id'      => $user['team_id'] ? (int)$user['team_id'] : null,
    'team_name'    => $user['team_name'],
    'solved_count' => $solvedCount,
    'last_login_at' => $user['last_login_at'],
    'created_at'    => $user['created_at'],
]);
