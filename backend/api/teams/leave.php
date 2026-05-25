<?php
/**
 * POST /api/teams/leave
 * 退出队伍
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

// 获取用户队伍
$stmt = $db->prepare("SELECT u.team_id, t.owner_id FROM users u LEFT JOIN teams t ON u.team_id = t.id WHERE u.id = ?");
$stmt->execute([$userId]);
$data = $stmt->fetch();

if (!$data || !$data['team_id']) {
    jsonError('你不在任何队伍中', 409);
}

$teamId = (int)$data['team_id'];

// 队长不能直接退出（需转让或解散）
if ((int)$data['owner_id'] === $userId) {
    jsonError('你是队长，请先转让队长或解散队伍', 409);
}

$db->beginTransaction();
try {
    $stmt = $db->prepare("DELETE FROM team_members WHERE team_id = ? AND user_id = ?");
    $stmt->execute([$teamId, $userId]);

    $stmt = $db->prepare("UPDATE teams SET member_count = member_count - 1 WHERE id = ?");
    $stmt->execute([$teamId]);

    $stmt = $db->prepare("UPDATE users SET team_id = NULL WHERE id = ?");
    $stmt->execute([$userId]);

    $db->commit();

    logActivity($userId, 'leave_team', 'team', $teamId);
    jsonSuccess(null, '已退出队伍');

} catch (Exception $e) {
    $db->rollBack();
    jsonError('退出失败: ' . $e->getMessage(), 500);
}
