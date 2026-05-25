<?php
/**
 * POST /api/writeups/create
 * 创建/更新 WriteUp
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

$challengeId = (int)input('challenge_id');
$content     = input('content');
$isPublic    = (bool)input('is_public', false);

if (!$challengeId || !$content) {
    jsonError('缺少题目 ID 或 WriteUp 内容', 422);
}

// 验证该用户解过此题
$stmt = $db->prepare("SELECT id FROM solves WHERE user_id = ? AND challenge_id = ?");
$stmt->execute([$userId, $challengeId]);
if (!$stmt->fetch()) {
    jsonError('只有解题后才能提交 WriteUp', 403);
}

// 插入或更新
$stmt = $db->prepare(
    "INSERT INTO writeups (challenge_id, user_id, content, is_public, created_at, updated_at)
     VALUES (?, ?, ?, ?, NOW(), NOW())
     ON DUPLICATE KEY UPDATE content = VALUES(content), is_public = VALUES(is_public), updated_at = NOW()"
);
$stmt->execute([$challengeId, $userId, $content, $isPublic ? 1 : 0]);

logActivity($userId, 'write_writeup', 'challenge', $challengeId);
jsonSuccess(null, 'WriteUp 保存成功');
