<?php
/**
 * GET /api/notices/list
 * 获取公告列表
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('仅支持 GET 请求', 405);
}

$db = getDB();

$stmt = $db->query(
    "SELECT n.id, n.title, n.content, n.is_pinned, n.created_at, n.updated_at,
            u.username AS created_by_name
     FROM notices n
     JOIN users u ON n.created_by = u.id
     ORDER BY n.is_pinned DESC, n.created_at DESC
     LIMIT 20"
);
$notices = $stmt->fetchAll();

foreach ($notices as &$n) {
    $n['id'] = (int)$n['id'];
    $n['is_pinned'] = (bool)$n['is_pinned'];
}
unset($n);

jsonSuccess($notices);
