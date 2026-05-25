<?php
/**
 * GET /api/teams/info
 * 获取队伍详情，支持指定队伍ID或查询当前用户队伍
 *
 * Query 参数:
 *   id - 队伍ID（可选，不传则返回当前用户队伍）
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('仅支持 GET 请求', 405);
}

$payload = requireAuth();
$userId = (int)$payload['sub'];
$db = getDB();

$teamId = getQuery('id');
if (!$teamId) {
    // 查当前用户队伍
    $stmt = $db->prepare("SELECT team_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row || !$row['team_id']) {
        jsonError('你不在任何队伍中', 404);
    }
    $teamId = $row['team_id'];
}

$stmt = $db->prepare(
    "SELECT t.*, u.username AS owner_name
     FROM teams t
     LEFT JOIN users u ON t.owner_id = u.id
     WHERE t.id = ?"
);
$stmt->execute([(int)$teamId]);
$team = $stmt->fetch();

if (!$team) {
    jsonError('队伍不存在', 404);
}

// 成员列表
$stmt = $db->prepare(
    "SELECT u.id, u.username, u.avatar_url, u.score, tm.role, tm.joined_at
     FROM team_members tm
     JOIN users u ON tm.user_id = u.id
     WHERE tm.team_id = ?
     ORDER BY tm.role ASC, u.score DESC"
);
$stmt->execute([(int)$teamId]);
$members = $stmt->fetchAll();

foreach ($members as &$m) {
    $m['id'] = (int)$m['id'];
    $m['score'] = (int)$m['score'];
}
unset($m);

jsonSuccess([
    'id'           => (int)$team['id'],
    'name'         => $team['name'],
    'description'  => $team['description'],
    'owner_name'   => $team['owner_name'],
    'owner_id'     => (int)$team['owner_id'],
    'score'        => (int)$team['score'],
    'member_count' => (int)$team['member_count'],
    'max_members'  => (int)$team['max_members'],
    'invite_code'  => $team['invite_code'],
    'is_owner'     => (int)$team['owner_id'] === $userId,
    'members'      => $members,
    'created_at'   => $team['created_at'],
]);
