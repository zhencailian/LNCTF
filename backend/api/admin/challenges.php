<?php
/**
 * CRUD /api/admin/challenges
 * 管理员：题目管理
 *
 * GET    → 列表（支持筛选/分页）
 * POST   → 创建
 * PUT    → 更新（需 ?id=xxx）
 * DELETE → 删除（需 ?id=xxx）
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../middleware/AdminMiddleware.php';

$payload = requireAdmin();
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleList($db);
        break;
    case 'POST':
        handleCreate($db, $payload);
        break;
    case 'PUT':
        handleUpdate($db, $payload);
        break;
    case 'DELETE':
        handleDelete($db);
        break;
    default:
        jsonError('不支持的请求方法', 405);
}

// ======================== GET: 列表 ========================
function handleList($db) {
    $page    = max(1, (int)getQuery('page', 1));
    $perPage = min(100, max(1, (int)getQuery('per_page', 20)));
    $offset  = ($page - 1) * $perPage;

    $where  = [];
    $params = [];

    // 分类筛选
    $catId = getQuery('category_id');
    if ($catId) {
        $where[] = 'c.category_id = ?';
        $params[] = (int)$catId;
    }

    $difficulty = getQuery('difficulty');
    if ($difficulty) {
        $where[] = 'c.difficulty = ?';
        $params[] = $difficulty;
    }

    $active = getQuery('is_active');
    if ($active !== null) {
        $where[] = 'c.is_active = ?';
        $params[] = (int)$active;
    }

    // 搜索
    $keyword = getQuery('keyword');
    if ($keyword) {
        $where[] = 'c.title LIKE ?';
        $params[] = '%' . $keyword . '%';
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $total = (int)$db->query("SELECT COUNT(*) FROM challenges c $whereClause")->fetchColumn();

    $sql = "SELECT c.*, cat.name AS category_name
            FROM challenges c
            LEFT JOIN categories cat ON c.category_id = cat.id
            $whereClause
            ORDER BY c.created_at DESC
            LIMIT $perPage OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    foreach ($items as &$item) {
        $item['id'] = (int)$item['id'];
        $item['category_id'] = (int)$item['category_id'];
        $item['score'] = (int)$item['score'];
        $item['solve_count'] = (int)$item['solve_count'];
        $item['is_active'] = (bool)$item['is_active'];
        $item['is_dockerized'] = (bool)$item['is_dockerized'];
        $item['flag'] = '*** 隐藏 ***';
    }
    unset($item);

    jsonPaginated($items, $total, $page, $perPage);
}

// ======================== POST: 创建 ========================
function handleCreate($db, $payload) {
    $title       = input('title');
    $description = input('description');
    $categoryId  = (int)input('category_id');
    $difficulty  = input('difficulty', 'easy');
    $score       = (int)input('score', 100);
    $flag        = input('flag');
    $flagHint    = input('flag_hint');
    $attachUrl   = input('attachment_url');

    if (empty($title) || empty($description) || empty($flag)) {
        jsonError('标题、描述、Flag 为必填项', 422);
    }
    if (!in_array($difficulty, ['easy', 'medium', 'hard', 'expert'])) {
        jsonError('难度等级无效', 422);
    }
    if ($categoryId <= 0) {
        jsonError('请选择有效的题目分类', 422);
    }

    // 哈希 Flag
    $hashedFlag = password_hash($flag, PASSWORD_BCRYPT);

    $stmt = $db->prepare(
        "INSERT INTO challenges (title, description, category_id, difficulty, score, flag, flag_hint, attachment_url, is_active, created_by, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())"
    );
    $stmt->execute([$title, $description, $categoryId, $difficulty, $score, $hashedFlag, $flagHint, $attachUrl, $payload['sub']]);
    $id = (int)$db->lastInsertId();

    logActivity($payload['sub'], 'create_challenge', 'challenge', $id);

    jsonSuccess(['id' => $id], '题目创建成功', 201);
}

// ======================== PUT: 更新 ========================
function handleUpdate($db, $payload) {
    $id = (int)(getQuery('id') ?: input('id'));
    if (!$id) jsonError('缺少题目 ID', 422);

    // 检查存在
    $stmt = $db->prepare("SELECT id FROM challenges WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) jsonError('题目不存在', 404);

    $fields = [];
    $params = [];

    foreach (['title', 'description', 'flag_hint', 'attachment_url', 'source_code_url', 'docker_image', 'docker_port_mapping'] as $f) {
        $v = input($f);
        if ($v !== null) {
            $fields[] = "$f = ?";
            $params[] = $v;
        }
    }

    $categoryId = input('category_id');
    if ($categoryId !== null) {
        $fields[] = 'category_id = ?';
        $params[] = (int)$categoryId;
    }

    $difficulty = input('difficulty');
    if ($difficulty && in_array($difficulty, ['easy', 'medium', 'hard', 'expert'])) {
        $fields[] = 'difficulty = ?';
        $params[] = $difficulty;
    }

    $score = input('score');
    if ($score !== null) {
        $fields[] = 'score = ?';
        $params[] = (int)$score;
    }

    $isActive = input('is_active');
    if ($isActive !== null) {
        $fields[] = 'is_active = ?';
        $params[] = (int)$isActive;
    }

    $flag = input('flag');
    if ($flag) {
        $fields[] = 'flag = ?';
        $params[] = password_hash($flag, PASSWORD_BCRYPT);
    }

    if (empty($fields)) {
        jsonError('没有要更新的字段', 422);
    }

    $fields[] = 'updated_at = NOW()';
    $params[] = $id;

    $sql = "UPDATE challenges SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    logActivity($payload['sub'], 'update_challenge', 'challenge', $id);
    jsonSuccess(null, '题目更新成功');
}

// ======================== DELETE: 删除 ========================
function handleDelete($db) {
    $id = (int)(getQuery('id') ?: input('id'));
    if (!$id) jsonError('缺少题目 ID', 422);

    $stmt = $db->prepare("DELETE FROM challenges WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        jsonError('题目不存在', 404);
    }

    jsonSuccess(null, '题目已删除');
}
