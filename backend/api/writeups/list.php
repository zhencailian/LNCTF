<?php
/**
 * GET /api/writeups/list
 * 获取 WriteUp 列表
 *
 * Query 参数:
 *   challenge_id - 筛选某题目的 WriteUp
 *   user_id      - 筛选某用户的 WriteUp
 *   page         - 页码
 *   per_page     - 每页条数
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('仅支持 GET 请求', 405);
}

$db = getDB();
$currentUser = getCurrentUser();

$where  = ['w.is_public = 1'];
$params = [];

$challengeId = getQuery('challenge_id');
if ($challengeId) {
    $where[] = 'w.challenge_id = ?';
    $params[] = (int)$challengeId;
}

$userId = getQuery('user_id');
if ($userId) {
    $where[] = 'w.user_id = ?';
    $params[] = (int)$userId;
}

// 自己的私有 WriteUp 也能看到
if ($currentUser) {
    $where[0] = '(w.is_public = 1 OR w.user_id = ' . $currentUser['sub'] . ')';
}

$whereClause = implode(' AND ', $where);

$page    = max(1, (int)getQuery('page', 1));
$perPage = min(50, max(1, (int)getQuery('per_page', 20)));
$offset  = ($page - 1) * $perPage;

$stmt = $db->prepare("SELECT COUNT(*) FROM writeups w WHERE $whereClause");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$stmt = $db->prepare(
    "SELECT w.id, w.challenge_id, w.is_public, w.created_at, w.updated_at,
            c.title AS challenge_title, c.difficulty,
            u.id AS author_id, u.username AS author_name
     FROM writeups w
     JOIN challenges c ON w.challenge_id = c.id
     JOIN users u ON w.user_id = u.id
     WHERE $whereClause
     ORDER BY w.created_at DESC
     LIMIT ? OFFSET ?"
);
$allParams = array_merge($params, [$perPage, $offset]);
$stmt->execute($allParams);
$items = $stmt->fetchAll();

foreach ($items as &$item) {
    $item['id'] = (int)$item['id'];
    $item['challenge_id'] = (int)$item['challenge_id'];
    $item['is_public'] = (bool)$item['is_public'];
}
unset($item);

jsonPaginated($items, $total, $page, $perPage);
