<?php
/**
 * GET /api/challenges/list
 * 获取题目列表（支持分类/难度筛选）
 *
 * Query 参数:
 *   category   - 分类ID或名称
 *   difficulty - easy/medium/hard/expert
 *   tag        - 标签名
 *   status     - solved/unsolved (需登录)
 *   keyword    - 搜索关键词
 *   page       - 页码 (默认1)
 *   per_page   - 每页条数 (默认20)
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

// 构建查询
$where  = ['c.is_active = 1'];
$params = [];

// 分类筛选
$category = getQuery('category');
if ($category) {
    if (is_numeric($category)) {
        $where[] = 'c.category_id = ?';
        $params[] = (int)$category;
    } else {
        $where[] = 'cat.name = ?';
        $params[] = $category;
    }
}

// 难度筛选
$difficulty = getQuery('difficulty');
if ($difficulty && in_array($difficulty, ['easy', 'medium', 'hard', 'expert'])) {
    $where[] = 'c.difficulty = ?';
    $params[] = $difficulty;
}

// 标签筛选
$tag = getQuery('tag');
if ($tag) {
    $where[] = 'c.id IN (SELECT ct.challenge_id FROM challenge_tags ct JOIN tags t ON ct.tag_id = t.id WHERE t.name = ?)';
    $params[] = $tag;
}

// 搜索关键词
$keyword = getQuery('keyword');
if ($keyword) {
    $where[] = '(c.title LIKE ? OR c.description LIKE ?)';
    $kw = '%' . $keyword . '%';
    $params[] = $kw;
    $params[] = $kw;
}

// 已解/未解筛选
$status = getQuery('status');
if ($status === 'solved' && $currentUser) {
    $where[] = 'c.id IN (SELECT challenge_id FROM solves WHERE user_id = ?)';
    $params[] = $currentUser['sub'];
} elseif ($status === 'unsolved' && $currentUser) {
    $where[] = 'c.id NOT IN (SELECT challenge_id FROM solves WHERE user_id = ?)';
    $params[] = $currentUser['sub'];
}

$whereClause = implode(' AND ', $where);

// 总条数
$countSql = "SELECT COUNT(*) FROM challenges c
             LEFT JOIN categories cat ON c.category_id = cat.id
             WHERE $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

// 分页
$page     = max(1, (int)getQuery('page', 1));
$perPage  = min(50, max(1, (int)getQuery('per_page', 20)));
$offset   = ($page - 1) * $perPage;

// 主查询
$sql = "SELECT c.id, c.title, c.difficulty, c.score, c.solve_count,
               c.flag_hint, c.attachment_url, c.is_dockerized,
               cat.name AS category_name, cat.icon AS category_icon
        FROM challenges c
        LEFT JOIN categories cat ON c.category_id = cat.id
        WHERE $whereClause
        ORDER BY cat.sort_order ASC, c.score ASC
        LIMIT ? OFFSET ?";
$allParams = array_merge($params, [$perPage, $offset]);
$stmt = $db->prepare($sql);
$stmt->execute($allParams);
$challenges = $stmt->fetchAll();

// 标记是否已解
foreach ($challenges as &$ch) {
    $ch['id'] = (int)$ch['id'];
    $ch['score'] = (int)$ch['score'];
    $ch['solve_count'] = (int)$ch['solve_count'];
    $ch['is_dockerized'] = (bool)$ch['is_dockerized'];
    $ch['solved'] = false;
    $ch['solved_at'] = null;

    if ($currentUser) {
        $s = $db->prepare("SELECT solved_at FROM solves WHERE user_id = ? AND challenge_id = ? LIMIT 1");
        $s->execute([$currentUser['sub'], $ch['id']]);
        $solve = $s->fetch();
        if ($solve) {
            $ch['solved'] = true;
            $ch['solved_at'] = $solve['solved_at'];
        }
    }
}
unset($ch);

jsonPaginated($challenges, $total, $page, $perPage);
