<?php
/**
 * POST /api/teams/join
 * 通过邀请码加入队伍
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

// 是否已有队伍
$stmt = $db->prepare("SELECT team_id FROM users WHERE id = ?");
$stmt->execute([$userId]);
if ($stmt->fetch()['team_id']) {
    jsonError('你已经在一个队伍中了', 409);
}

$inviteCode = input('invite_code');
if (empty($inviteCode)) {
    jsonError('请输入邀请码', 422);
}

// 查找队伍
$stmt = $db->prepare("SELECT * FROM teams WHERE invite_code = ?");
$stmt->execute([$inviteCode]);
$team = $stmt->fetch();

if (!$team) {
    jsonError('邀请码无效', 404);
}

if ((int)$team['member_count'] >= (int)$team['max_members']) {
    jsonError('队伍已满（最大 ' . $team['max_members'] . ' 人）', 409);
}

$db->beginTransaction();
try {
    $stmt = $db->prepare("INSERT INTO team_members (team_id, user_id, role, joined_at) VALUES (?, ?, 'member', NOW())");
    $stmt->execute([$team['id'], $userId]);

    $stmt = $db->prepare("UPDATE teams SET member_count = member_count + 1 WHERE id = ?");
    $stmt->execute([$team['id']]);

    $stmt = $db->prepare("UPDATE users SET team_id = ? WHERE id = ?");
    $stmt->execute([$team['id'], $userId]);

    $db->commit();

    logActivity($userId, 'join_team', 'team', $team['id']);

    jsonSuccess([
        'team_id'   => (int)$team['id'],
        'team_name' => $team['name'],
    ], '加入队伍成功');

} catch (Exception $e) {
    $db->rollBack();
    jsonError('加入失败: ' . $e->getMessage(), 500);
}
