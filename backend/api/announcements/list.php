<?php
/**
 * GET /api/announcements/list
 * 获取公告列表
 *
 * Query 参数:
 *   limit - 返回条数（默认 20，最大 50）
 *
 * 返回:
 *   { code: 200, message: "success", data: [ { id, title, content, is_important, created_at } ] }
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('仅支持 GET 请求', 405);
}

$db = getDB();

$limit = min(50, max(1, (int)getQuery('limit', 20)));

$stmt = $db->prepare(
    "SELECT a.id, a.title, a.content, a.is_important, a.created_at
     FROM announcements a
     ORDER BY a.is_important DESC, a.created_at DESC
     LIMIT ?"
);
$stmt->execute([$limit]);
$announcements = $stmt->fetchAll();

foreach ($announcements as &$a) {
    $a['id'] = (int)$a['id'];
    $a['is_important'] = (bool)$a['is_important'];
}
unset($a);

jsonSuccess($announcements);
