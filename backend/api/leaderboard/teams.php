<?php
/**
 * GET /api/leaderboard/teams
 * 队伍排行榜
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('仅支持 GET 请求', 405);
}

$db = getDB();

$page    = max(1, (int)getQuery('page', 1));
$perPage = min(100, max(1, (int)getQuery('per_page', 50)));
$offset  = ($page - 1) * $perPage;

$total = (int)$db->query("SELECT COUNT(*) FROM teams")->fetchColumn();

$stmt = $db->prepare(
    "SELECT t.id, t.name, t.score, t.member_count,
            u.username AS owner_name
     FROM teams t
     LEFT JOIN users u ON t.owner_id = u.id
     ORDER BY t.score DESC
     LIMIT ? OFFSET ?"
);
$stmt->execute([$perPage, $offset]);
$teams = $stmt->fetchAll();

$rank = $offset + 1;
foreach ($teams as &$team) {
    $team['id'] = (int)$team['id'];
    $team['score'] = (int)$team['score'];
    $team['member_count'] = (int)$team['member_count'];
    $team['rank'] = $rank++;
}
unset($team);

jsonPaginated($teams, $total, $page, $perPage);
