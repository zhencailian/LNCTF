<?php
/**
 * CRUD /api/admin/users
 * 管理员：用户管理
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
        $page    = max(1, (int)getQuery('page', 1));
        $perPage = min(100, max(1, (int)getQuery('per_page', 20)));
        $offset  = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        $keyword = getQuery('keyword');
        if ($keyword) {
            $where[] = '(u.username LIKE ? OR u.email LIKE ?)';
            $kw = '%' . $keyword . '%';
            $params[] = $kw;
            $params[] = $kw;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int)$db->query("SELECT COUNT(*) FROM users u $whereClause")->fetchColumn();

        $stmt = $db->prepare(
            "SELECT u.id, u.username, u.email, u.role, u.score, u.is_active, u.last_login_at, u.created_at,
                    (SELECT COUNT(*) FROM solves WHERE user_id = u.id) AS solved_count
             FROM users u
             $whereClause
             ORDER BY u.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $allParams = array_merge($params, [$perPage, $offset]);
        $stmt->execute($allParams);
        $items = $stmt->fetchAll();

        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['score'] = (int)$item['score'];
            $item['solved_count'] = (int)$item['solved_count'];
            $item['is_active'] = (bool)$item['is_active'];
        }
        unset($item);

        jsonPaginated($items, $total, $page, $perPage);
        break;

    case 'PUT':
        $userId  = (int)(getQuery('id') ?: input('id'));
        $role    = input('role');
        $isActive = input('is_active');

        if (!$userId) jsonError('缺少用户 ID', 422);

        $fields = [];
        $params = [];

        if ($role && in_array($role, ['admin', 'user'])) {
            $fields[] = 'role = ?';
            $params[] = $role;
        }
        if ($isActive !== null) {
            $fields[] = 'is_active = ?';
            $params[] = (int)$isActive;
        }

        if (empty($fields)) jsonError('没有要更新的字段', 422);

        $fields[] = 'updated_at = NOW()';
        $params[] = $userId;

        $db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);

        logActivity($payload['sub'], 'update_user', 'user', $userId);
        jsonSuccess(null, '用户更新成功');
        break;

    case 'DELETE':
        $userId = (int)(getQuery('id') ?: input('id'));
        if (!$userId) jsonError('缺少用户 ID', 422);
        if ($userId === (int)$payload['sub']) {
            jsonError('不能删除自己', 409);
        }

        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
        jsonSuccess(null, '用户已删除');
        break;

    default:
        jsonError('不支持的请求方法', 405);
}
