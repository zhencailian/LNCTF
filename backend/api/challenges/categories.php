<?php
/**
 * GET /api/challenges/categories
 * 获取所有分类
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('仅支持 GET 请求', 405);
}

$db = getDB();

$stmt = $db->query(
    "SELECT c.id, c.name, c.description, c.icon, c.sort_order,
            (SELECT COUNT(*) FROM challenges WHERE category_id = c.id AND is_active = 1) AS challenge_count
     FROM categories c
     ORDER BY c.sort_order ASC"
);
$categories = $stmt->fetchAll();

foreach ($categories as &$cat) {
    $cat['id'] = (int)$cat['id'];
    $cat['challenge_count'] = (int)$cat['challenge_count'];
}
unset($cat);

jsonSuccess($categories);
