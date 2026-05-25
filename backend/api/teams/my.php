<?php
/**
 * GET /api/teams/my
 * 获取当前用户队伍简略信息（供前端 NavBar 使用）
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('仅支持 GET 请求', 405);
}

$payload = requireAuth();
$db = getDB();

$stmt = $db->prepare("SELECT u.team_id, t.name, t.score FROM users u LEFT JOIN teams t ON u.team_id = t.id WHERE u.id = ?");
$stmt->execute([$payload['sub']]);
$data = $stmt->fetch();

if ($data && $data['team_id']) {
    jsonSuccess([
        'team_id' => (int)$data['team_id'],
        'name'    => $data['name'],
        'score'   => (int)$data['score'],
    ]);
} else {
    jsonSuccess(null, '未加入队伍');
}
