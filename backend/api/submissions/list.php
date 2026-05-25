<?php
/**
 * GET /api/submissions/list
 * 获取当前用户的提交记录
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('仅支持 GET 请求', 405);
}

$payload = requireAuth();
$db = getDB();

$page    = max(1, (int)getQuery('page', 1));
$perPage = min(50, max(1, (int)getQuery('per_page', 20)));
$offset  = ($page - 1) * $perPage;

// 总数
$stmt = $db->prepare("SELECT COUNT(*) FROM submissions WHERE user_id = ?");
$stmt->execute([$payload['sub']]);
$total = (int)$stmt->fetchColumn();

// 列表
$stmt = $db->prepare(
    "SELECT s.id, s.challenge_id, s.submitted_flag, s.is_correct, s.solved_at, s.created_at,
            c.title AS challenge_title
     FROM submissions s
     JOIN challenges c ON s.challenge_id = c.id
     WHERE s.user_id = ?
     ORDER BY s.created_at DESC
     LIMIT ? OFFSET ?"
);
$stmt->execute([$payload['sub'], $perPage, $offset]);
$items = $stmt->fetchAll();

// 脱敏显示的 Flag
foreach ($items as &$item) {
    $item['id'] = (int)$item['id'];
    $item['challenge_id'] = (int)$item['challenge_id'];
    $item['is_correct'] = (bool)$item['is_correct'];
    // 只显示前 10 个字符
    $item['flag_preview'] = mb_substr($item['submitted_flag'], 0, 10) . '...';
    unset($item['submitted_flag']);
}
unset($item);

jsonPaginated($items, $total, $page, $perPage);
