<?php
/**
 * POST /api/teams/dismiss
 * 解散队伍（仅队长）
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

// 获取用户的队伍及角色
$stmt = $db->prepare(
    "SELECT t.id, t.owner_id FROM teams t
     JOIN users u ON u.team_id = t.id
     WHERE u.id = ?"
);
$stmt->execute([$userId]);
$team = $stmt->fetch();

if (!$team) {
    jsonError('你不在任何队伍中', 409);
}

if ((int)$team['owner_id'] !== $userId) {
    jsonError('只有队长才能解散队伍', 403);
}

$teamId = (int)$team['id'];

$db->beginTransaction();
try {
    // 将所有成员 team_id 置空
    $stmt = $db->prepare("UPDATE users SET team_id = NULL WHERE team_id = ?");
    $stmt->execute([$teamId]);

    // 删除成员关系
    $stmt = $db->prepare("DELETE FROM team_members WHERE team_id = ?");
    $stmt->execute([$teamId]);

    // 删除队伍
    $stmt = $db->prepare("DELETE FROM teams WHERE id = ?");
    $stmt->execute([$teamId]);

    $db->commit();

    logActivity($userId, 'dismiss_team', 'team', $teamId);
    jsonSuccess(null, '队伍已解散');

} catch (Exception $e) {
    $db->rollBack();
    jsonError('解散失败: ' . $e->getMessage(), 500);
}
