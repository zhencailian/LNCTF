<?php
/**
 * GET /api/admin/submissions
 * 管理员：查看所有提交流水
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../middleware/AdminMiddleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('仅支持 GET 请求', 405);
}

requireAdmin();
$db = getDB();

$page    = max(1, (int)getQuery('page', 1));
$perPage = min(100, max(1, (int)getQuery('per_page', 30)));
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];

$challengeId = getQuery('challenge_id');
if ($challengeId) {
    $where[] = 's.challenge_id = ?';
    $params[] = (int)$challengeId;
}

$userId = getQuery('user_id');
if ($userId) {
    $where[] = 's.user_id = ?';
    $params[] = (int)$userId;
}

$correct = getQuery('is_correct');
if ($correct !== null) {
    $where[] = 's.is_correct = ?';
    $params[] = (int)$correct;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("SELECT COUNT(*) FROM submissions s $whereClause");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$stmt = $db->prepare(
    "SELECT s.id, s.submitted_flag, s.is_correct, s.ip_address, s.created_at, s.solved_at,
            u.id AS user_id, u.username,
            c.id AS challenge_id, c.title AS challenge_title
     FROM submissions s
     JOIN users u ON s.user_id = u.id
     JOIN challenges c ON s.challenge_id = c.id
     $whereClause
     ORDER BY s.created_at DESC
     LIMIT ? OFFSET ?"
);
$allParams = array_merge($params, [$perPage, $offset]);
$stmt->execute($allParams);
$items = $stmt->fetchAll();

foreach ($items as &$item) {
    $item['id'] = (int)$item['id'];
    $item['is_correct'] = (bool)$item['is_correct'];
    $item['submitted_flag'] = substr($item['submitted_flag'], 0, 30) . (strlen($item['submitted_flag']) > 30 ? '...' : '');
}
unset($item);

jsonPaginated($items, $total, $page, $perPage);
