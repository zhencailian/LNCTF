<?php
/**
 * GET /api/profile/solves
 * 获取当前用户的解题记录
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('仅支持 GET 请求', 405);
}

$payload = requireAuth();
$userId = (int)(getQuery('user_id') ?: $payload['sub']);
$db = getDB();

$page    = max(1, (int)getQuery('page', 1));
$perPage = min(50, max(1, (int)getQuery('per_page', 20)));
$offset  = ($page - 1) * $perPage;

$stmt = $db->prepare("SELECT COUNT(*) FROM solves WHERE user_id = ?");
$stmt->execute([$userId]);
$total = (int)$stmt->fetchColumn();

$stmt = $db->prepare(
    "SELECT s.challenge_id, s.score_earned, s.solve_order, s.solved_at,
            c.title, c.difficulty, c.score AS base_score,
            cat.name AS category_name, cat.icon AS category_icon
     FROM solves s
     JOIN challenges c ON s.challenge_id = c.id
     LEFT JOIN categories cat ON c.category_id = cat.id
     WHERE s.user_id = ?
     ORDER BY s.solved_at DESC
     LIMIT ? OFFSET ?"
);
$stmt->execute([$userId, $perPage, $offset]);
$items = $stmt->fetchAll();

foreach ($items as &$item) {
    $item['challenge_id'] = (int)$item['challenge_id'];
    $item['score_earned'] = (int)$item['score_earned'];
    $item['solve_order'] = (int)$item['solve_order'];
    $item['base_score'] = (int)$item['base_score'];
}
unset($item);

jsonPaginated($items, $total, $page, $perPage);
