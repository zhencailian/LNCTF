<?php
/**
 * LNCTF 统一 JSON 响应封装
 */

/**
 * 成功响应
 */
function jsonSuccess($data = null, string $message = 'success', int $code = 200): void {
    http_response_code($code);
    echo json_encode([
        'code'    => $code,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * 错误响应
 */
function jsonError(string $message = 'error', int $code = 400, $errors = null): void {
    http_response_code($code);
    $resp = [
        'code'    => $code,
        'message' => $message,
    ];
    if ($errors !== null) {
        $resp['errors'] = $errors;
    }
    echo json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * 分页响应
 */
function jsonPaginated(array $items, int $total, int $page, int $perPage, string $message = 'success'): void {
    jsonSuccess([
        'items'    => $items,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => (int)ceil($total / $perPage),
    ], $message);
}
