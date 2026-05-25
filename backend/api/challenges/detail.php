<?php
/**
 * GET /api/challenges/detail/{id}
 * 获取题目详情（不暴露 Flag）
 *
 * URL 参数: id - 题目 ID
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('仅支持 GET 请求', 405);
}

$id = $GLOBALS['route_param'] ?? getQuery('id');
if (!$id || !is_numeric($id)) {
    jsonError('缺少题目 ID', 422);
}

$db = getDB();
$currentUser = getCurrentUser();

// 主查询
$stmt = $db->prepare(
    "SELECT c.*, cat.name AS category_name, cat.icon AS category_icon,
            u.username AS creator_name
     FROM challenges c
     LEFT JOIN categories cat ON c.category_id = cat.id
     LEFT JOIN users u ON c.created_by = u.id
     WHERE c.id = ? AND c.is_active = 1"
);
$stmt->execute([(int)$id]);
$challenge = $stmt->fetch();

if (!$challenge) {
    jsonError('题目不存在或未发布', 404);
}

// 获取标签
$stmt = $db->prepare(
    "SELECT t.name FROM tags t
     JOIN challenge_tags ct ON t.id = ct.tag_id
     WHERE ct.challenge_id = ?"
);
$stmt->execute([(int)$id]);
$tags = array_column($stmt->fetchAll(), 'name');

// 是否已解
$solved = false;
$solvedAt = null;
if ($currentUser) {
    $stmt = $db->prepare("SELECT solved_at, score_earned FROM solves WHERE user_id = ? AND challenge_id = ? LIMIT 1");
    $stmt->execute([$currentUser['sub'], (int)$id]);
    $solveData = $stmt->fetch();
    if ($solveData) {
        $solved = true;
        $solvedAt = $solveData['solved_at'];
    }
}

// 一血信息
$firstBlood = null;
if ($challenge['first_blood_id']) {
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$challenge['first_blood_id']]);
    $fb = $stmt->fetch();
    if ($fb) {
        $firstBlood = $fb['username'];
    }
}

// 动态计分
$dynamicScore = dynamicScore((int)$challenge['score'], (int)$challenge['solve_count']);

jsonSuccess([
    'id'              => (int)$challenge['id'],
    'title'           => $challenge['title'],
    'description'     => $challenge['description'],
    'category_name'   => $challenge['category_name'],
    'category_icon'   => $challenge['category_icon'],
    'difficulty'      => $challenge['difficulty'],
    'base_score'      => (int)$challenge['score'],
    'dynamic_score'   => $dynamicScore,
    'flag_hint'       => $challenge['flag_hint'],
    'attachment_url'  => $challenge['attachment_url'],
    'source_code_url' => $challenge['source_code_url'],
    'is_dockerized'   => (bool)$challenge['is_dockerized'],
    'docker_image'    => $challenge['docker_image'],
    'solve_count'     => (int)$challenge['solve_count'],
    'first_blood'     => $firstBlood,
    'tags'            => $tags,
    'solved'          => $solved,
    'solved_at'       => $solvedAt,
    'created_by'      => $challenge['creator_name'],
    'created_at'      => $challenge['created_at'],
]);
