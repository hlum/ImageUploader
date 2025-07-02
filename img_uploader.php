<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

//Preflight requests　の処理
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(response_code: 200);
    exit;
}

// Postリクエストのみを許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(value: ['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// API KEY
require_once 'config.php';

// Configuration
$imgDir = 'imgs/';
$maxFileSize = 5 * 1024 * 1024; // 5MB
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// APIキーの検証
$headers = getallheaders();
$client_api_key = $headers['Authorization'] ?? $headers['authorization'] ?? null;

// Remove "Bearer " prefix if present
if ($client_api_key && strpos($client_api_key, 'Bearer ') === 0) {
    $client_api_key = substr($client_api_key, 7);
}

// Validate API key
if (empty($client_api_key)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'API Key is required']);
    exit;
}

if (!defined('API_KEY')) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'API Key is not defined in the server configuration']);
    exit;
}

if (!hash_equals(API_KEY, $client_api_key)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid API Key']);
    exit;
}

// Dir作成
if (!file_exists($imgDir)) {
    if (!mkdir($imgDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create upload directory']);
        exit;
    }
}

// 写真のデータチェック
$rawData = file_get_contents('php://input');
if (empty($rawData)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No image data provided']);
    exit;
}

// ファイルサイズのチェック
if (strlen($rawData) > $maxFileSize) {
    http_response_code(413);
    echo json_encode(['status' => 'error', 'message' => 'File size exceeds limit']);
    exit;
}

// ファイルフォーマットの検証
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->buffer($rawData);

if (!in_array($mimeType, $allowedMimeTypes)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid image format']);
    exit;
}

// イメージサイズの検証
$imageInfo = getimagesizefromstring($rawData);
if ($imageInfo === false) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid image data']);
    exit;
}

// ファイルタイプ
$extensions = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp'
];
$extension = $extensions[$mimeType] ?? 'jpg';

// ファイル名の生成
$imgName = bin2hex(random_bytes(16)) . '.' . $extension;
$imgPath = $imgDir . $imgName;

// ファイルの競合チェック
if (file_exists($imgPath)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'File generation conflict']);
    exit;
}

// 保存
$bytesWritten = file_put_contents($imgPath, $rawData, LOCK_EX);
if ($bytesWritten === false || $bytesWritten !== strlen($rawData)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save the image']);
    exit;
}

// ファイルの権限設定
chmod($imgPath, 0755);

// 写真のURL生成
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$imgURL = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/ImgAPI/' . $imgPath;

// Success response with additional metadata
echo json_encode([
    'status' => 'success',
    'message' => 'Image uploaded successfully',
    'url' => $imgURL,
    'size' => strlen($rawData),
    'type' => $mimeType
]);