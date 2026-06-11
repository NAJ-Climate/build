<?php
/**
 * NAJ Catalog Editor & PDF Generator
 * Handles catalog CRUD, image upload, and PDF output
 */

$jsonFile = __DIR__ . '/catalog.json';

// CORS & Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// ─── ACTION: DOWNLOAD PDF ───────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    header("Content-Type: text/html; charset=utf-8");

    if (!file_exists($jsonFile)) {
        echo "<p>Catalog data not found.</p>";
        exit;
    }

    $raw = file_get_contents($jsonFile);
    $catalog = json_decode($raw, true);

    if (!$catalog || !isset($catalog['products'])) {
        echo "<p>Catalog data is empty or corrupt.</p>";
        exit;
    }

    $h = function($s) {
        return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    };

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl  = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/';

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <style>
            @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700&display=swap');
            * { box-sizing: border-box; }
            body { font-family: 'DM Sans', sans-serif; margin: 0; padding: 0; color: #07063d; }
            .page { padding: 40px; page-break-after: always; }
            .header { border-bottom: 2px solid #2FB7E5; padding-bottom: 20px; margin-bottom: 40px; }
            .brand { color: #2FB7E5; font-size: 12px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; }
            .title { font-size: 32px; margin-top: 10px; font-weight: 700; }
            
            .product-card { 
                margin-bottom: 30px; 
                border: 1px solid #eee; 
                border-radius: 12px; 
                overflow: hidden;
                display: table;
                width: 100%;
                page-break-inside: avoid;
            }
            .product-image { 
                display: table-cell;
                width: 30%;
                padding: 20px;
                vertical-align: middle;
                text-align: center;
            }
            .product-details { 
                display: table-cell;
                width: 70%;
                padding: 20px;
                vertical-align: top;
            }
            .product-category { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }
            .product-name { font-size: 20px; margin: 5px 0; font-weight: 700; }
            .product-variant { font-size: 12px; color: #666; margin-bottom: 10px; }
            .product-desc { font-size: 13px; line-height: 1.6; color: #444; margin-bottom: 15px; }
            
            .specs { width: 100%; border-collapse: collapse; font-size: 11px; }
            .specs td { padding: 6px 10px; border: 1px solid #f0f0f0; }
            .spec-label { background: #fafafa; font-weight: bold; width: 30%; color: #888; text-transform: uppercase; font-size: 9px; }
            
            @media print {
                .no-print { display: none !important; }
                body { background: white; }
                .product-card { border-color: #ddd; }
                img { max-width: 100%; }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="background: #2FB7E5; color: white; padding: 15px; text-align: center; font-weight: bold;">
            CATALOG PREVIEW: Use Ctrl+P (Cmd+P) and "Save as PDF" for the best result.
        </div>
        
        <div class="page">
            <div class="header">
                <div class="brand">NAJ Technologies</div>
                <div class="title">Product Catalog 2026</div>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">Climate Solutions Engineered for Africa</div>
            </div>

            <?php foreach ($catalog['products'] as $p):
                $imgSrc = $h($p['img'] ?? '');
                // Make image paths absolute for PDF rendering
                if ($imgSrc && $imgSrc[0] === '/') {
                    $imgSrc = $baseUrl . ltrim($imgSrc, '/');
                }
            ?>
                <div class="product-card">
                    <div class="product-image" style="background: linear-gradient(135deg, <?=$h($p['bgFrom'] ?? '#f1f5f9')?>, <?=$h($p['bgTo'] ?? '#e2e8f0')?>);">
                        <div style="font-weight: bold; color: <?=$h($p['accentHex'] ?? '#475569')?>;"><?=$h($p['brand'] ?? '')?></div>
                        <?php if ($imgSrc): ?>
                            <img src="<?=$imgSrc?>" alt="<?=$h($p['name'] ?? 'Product')?>" style="width: 100px; height: auto; margin-top: 10px;">
                        <?php endif; ?>
                    </div>
                    <div class="product-details">
                        <div class="product-category" style="color: <?=$h($p['accentHex'] ?? '#475569')?>;"><?=$h($p['category'] ?? '')?></div>
                        <div class="product-name"><?=$h($p['name'] ?? '')?></div>
                        <div class="product-variant"><?=$h($p['variant'] ?? '')?></div>
                        <div class="product-desc"><?=$h($p['description'] ?? '')?></div>
                        
                        <?php if (!empty($p['specs'])): ?>
                        <table class="specs">
                            <?php
                            $specs = $p['specs'];
                            $chunks = array_chunk($specs, 2);
                            foreach ($chunks as $chunk):
                            ?>
                            <tr>
                                <?php foreach ($chunk as $spec): ?>
                                    <td class="spec-label"><?=$h($spec[0] ?? '')?></td>
                                    <td><?=$h($spec[1] ?? '')?></td>
                                <?php endforeach; ?>
                                <?php if (count($chunk) < 2): ?><td></td><td></td><?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div style="margin-top: 40px; font-size: 10px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
                &copy; <?=date('Y')?> NAJ Technologies. All rights reserved. | najtechnologies.com
            </div>
        </div>
    </body>
    </html>
    <?php
    echo ob_get_clean();
    exit;
}

// ─── ACTION: UPLOAD IMAGE ────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'upload') {
    header("Content-Type: application/json");
    
    if (!isset($_FILES['image'])) {
        http_response_code(400);
        echo json_encode(["error" => "No image file provided"]);
        exit;
    }

    $uploadDir = __DIR__ . '/img/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $file = $_FILES['image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid file type. Allowed: jpg, jpeg, png, gif, webp"]);
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(["error" => "File too large. Max 5MB"]);
        exit;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(["error" => "Upload failed with code " . $file['error']]);
        exit;
    }

    // Validate actual image content (not just extension)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowedMimes)) {
        http_response_code(400);
        echo json_encode(["error" => "File content is not a valid image (detected: $mime)"]);
        exit;
    }

    // Generate unique filename preserving original name for reference
    $origName = pathinfo($file['name'], PATHINFO_FILENAME);
    $origName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $origName);
    $origName = substr($origName, 0, 40); // limit length
    $filename = $origName . '_' . uniqid() . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        echo json_encode([
            "success" => true,
            "path" => '/img/products/' . $filename,
            "filename" => $filename
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to save uploaded file. Check directory permissions."]);
    }
    exit;
}

// ─── ACTION: LIST UPLOADED IMAGES ───────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'list-images') {
    header("Content-Type: application/json");
    $uploadDir = __DIR__ . '/img/products/';
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $images = [];

    if (is_dir($uploadDir)) {
        $files = scandir($uploadDir);
        foreach ($files as $f) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExt)) {
                $images[] = '/img/products/' . $f;
            }
        }
    }

    echo json_encode(["images" => $images]);
    exit;
}

// ─── ACTION: DELETE UPLOADED IMAGE ──────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'delete-image') {
    header("Content-Type: application/json");

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    $path = is_array($data) ? ($data['path'] ?? '') : '';

    if (!$path || strpos($path, '/img/products/') !== 0) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid image path"]);
        exit;
    }

    // Security: prevent path traversal
    $filename = basename($path);
    $fullPath = __DIR__ . '/img/products/' . $filename;

    if (!file_exists($fullPath)) {
        http_response_code(404);
        echo json_encode(["error" => "File not found"]);
        exit;
    }

    if (unlink($fullPath)) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to delete file"]);
    }
    exit;
}

// ─── CATALOG JSON API ────────────────────────────────────────────────────────
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists($jsonFile)) {
        echo json_encode(["products" => [], "presets" => []]);
        exit;
    }
    $raw = file_get_contents($jsonFile);
    $data = json_decode($raw, true);
    if (!$data) {
        // Return empty structure if JSON is corrupt
        echo json_encode(["products" => [], "presets" => []]);
        exit;
    }
    // Always ensure presets key exists
    if (!isset($data['presets'])) $data['presets'] = [];
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data || !isset($data['products'])) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid catalog data"]);
        exit;
    }

    // Ensure presets key is always present
    if (!isset($data['presets'])) $data['presets'] = [];

    // Acquire an exclusive write lock so concurrent requests don't corrupt the file
    $written = file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);

    if ($written !== false) {
        echo json_encode(["success" => true, "message" => "Catalog saved successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to write to catalog.json."]);
    }
    exit;
}
