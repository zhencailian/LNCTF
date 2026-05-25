<?php
/**
 * CRUD /api/admin/categories
 * 管理员：分类管理
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
            "SELECT c.*,
                    (SELECT COUNT(*) FROM challenges WHERE category_id = c.id) AS challenge_count
             FROM categories c ORDER BY c.sort_order"
        );
        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['challenge_count'] = (int)$item['challenge_count'];
            $item['sort_order'] = (int)$item['sort_order'];
        }
        jsonSuccess($items);
        break;

    case 'POST':
        $name = input('name');
        $desc = input('description', '');
        $icon = input('icon');
        $sort = (int)input('sort_order', 99);

        if (empty($name)) jsonError('分类名不能为空', 422);

        $stmt = $db->prepare("INSERT INTO categories (name, description, icon, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $desc, $icon, $sort]);

        logActivity($payload['sub'], 'create_category', 'category', (int)$db->lastInsertId());
        jsonSuccess(['id' => (int)$db->lastInsertId()], '分类创建成功', 201);
        break;

    case 'PUT':
        $id   = (int)(getQuery('id') ?: input('id'));
        $name = input('name');
        $desc = input('description');
        $icon = input('icon');
        $sort = input('sort_order');

        if (!$id) jsonError('缺少分类 ID', 422);

        $fields = [];
        $params = [];
        foreach (['name', 'description', 'icon'] as $f) {
            $v = input($f);
            if ($v !== null) { $fields[] = "$f = ?"; $params[] = $v; }
        }
        if ($sort !== null) { $fields[] = 'sort_order = ?'; $params[] = (int)$sort; }
        if (empty($fields)) jsonError('没有要更新的字段', 422);

        $params[] = $id;
        $db->prepare("UPDATE categories SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);

        jsonSuccess(null, '分类更新成功');
        break;

    case 'DELETE':
        $id = (int)(getQuery('id') ?: input('id'));
        if (!$id) jsonError('缺少分类 ID', 422);

        // 检查是否有题目
        $stmt = $db->prepare("SELECT COUNT(*) FROM challenges WHERE category_id = ?");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            jsonError('该分类下有题目，无法删除', 409);
        }

        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        jsonSuccess(null, '分类已删除');
        break;

    default:
        jsonError('不支持的请求方法', 405);
}
