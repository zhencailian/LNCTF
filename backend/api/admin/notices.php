<?php
/**
 * CRUD /api/admin/notices
 * 管理员：公告管理
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
        $stmt = $db->query(
            "SELECT n.*, u.username AS created_by_name
             FROM notices n JOIN users u ON n.created_by = u.id
             ORDER BY n.is_pinned DESC, n.created_at DESC"
        );
        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['is_pinned'] = (bool)$item['is_pinned'];
        }
        jsonSuccess($items);
        break;

    case 'POST':
        $title   = input('title');
        $content = input('content');
        $pinned  = (int)(bool)input('is_pinned', false);

        if (empty($title) || empty($content)) jsonError('标题和内容不能为空', 422);

        $stmt = $db->prepare("INSERT INTO notices (title, content, is_pinned, created_by, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $content, $pinned, $payload['sub']]);

        logActivity($payload['sub'], 'create_notice', 'notice', (int)$db->lastInsertId());
        jsonSuccess(['id' => (int)$db->lastInsertId()], '公告发布成功', 201);
        break;

    case 'PUT':
        $id      = (int)(getQuery('id') ?: input('id'));
        $title   = input('title');
        $content = input('content');
        $pinned  = input('is_pinned');

        if (!$id) jsonError('缺少公告 ID', 422);

        $fields = [];
        $params = [];
        if ($title !== null)   { $fields[] = 'title = ?';   $params[] = $title; }
        if ($content !== null) { $fields[] = 'content = ?'; $params[] = $content; }
        if ($pinned !== null)  { $fields[] = 'is_pinned = ?'; $params[] = (int)$pinned; }
        if (empty($fields)) jsonError('没有要更新的字段', 422);

        $fields[] = 'updated_at = NOW()';
        $params[] = $id;
        $db->prepare("UPDATE notices SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);

        jsonSuccess(null, '公告更新成功');
        break;

    case 'DELETE':
        $id = (int)(getQuery('id') ?: input('id'));
        if (!$id) jsonError('缺少公告 ID', 422);
        $db->prepare("DELETE FROM notices WHERE id = ?")->execute([$id]);
        jsonSuccess(null, '公告已删除');
        break;

    default:
        jsonError('不支持的请求方法', 405);
}
