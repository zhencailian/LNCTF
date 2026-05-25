<?php
/**
 * POST /api/teams/create
 * 创建队伍
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

// 检查是否已有队伍
$stmt = $db->prepare("SELECT team_id FROM users WHERE id = ?");
$stmt->execute([$userId]);
if ($stmt->fetch()['team_id']) {
    jsonError('你已经在一个队伍中了，请先退出再创建', 409);
}

$name        = input('name');
$description = input('description', '');
$maxMembers  = min(10, max(2, (int)input('max_members', 5)));

if (empty($name) || strlen($name) < 2 || strlen($name) > 32) {
    jsonError('队伍名需 2-32 个字符', 422);
}

// 检查队名重复
$stmt = $db->prepare("SELECT id FROM teams WHERE name = ?");
$stmt->execute([$name]);
if ($stmt->fetch()) {
    jsonError('队伍名已被使用', 409);
}

$inviteCode = randomStr(8);

$db->beginTransaction();
try {
    $stmt = $db->prepare(
        "INSERT INTO teams (name, description, owner_id, invite_code, member_count, max_members, created_at)
         VALUES (?, ?, ?, ?, 1, ?, NOW())"
    );
    $stmt->execute([$name, $description, $userId, $inviteCode, $maxMembers]);
    $teamId = (int)$db->lastInsertId();

    // 加入队伍成员表
    $stmt = $db->prepare(
        "INSERT INTO team_members (team_id, user_id, role, joined_at) VALUES (?, ?, 'owner', NOW())"
    );
    $stmt->execute([$teamId, $userId]);

    // 更新用户 team_id
    $stmt = $db->prepare("UPDATE users SET team_id = ? WHERE id = ?");
    $stmt->execute([$teamId, $userId]);

    $db->commit();

    logActivity($userId, 'create_team', 'team', $teamId);

    jsonSuccess([
        'id'          => $teamId,
        'name'        => $name,
        'invite_code' => $inviteCode,
    ], '队伍创建成功', 201);

} catch (Exception $e) {
    $db->rollBack();
    jsonError('创建失败: ' . $e->getMessage(), 500);
}
