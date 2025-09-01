<?php
// Initialize session and auth before any output
$base_path = dirname(dirname(__DIR__));
require_once $base_path . '/classes/Auth.php';
$auth = Auth::getInstance();
$auth->requireAdmin();

require_once $base_path . '/classes/Database.php';

$success = '';
$error = '';

// 상품 추가 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $detailed_description = trim($_POST['detailed_description'] ?? '');
    $features = trim($_POST['features'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 1);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $weight = trim($_POST['weight'] ?? '');
    $dimensions = trim($_POST['dimensions'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // 이미지 URL 처리 (URL 입력 또는 파일 업로드)
    $image_url = trim($_POST['image_url'] ?? '');
    
    
    // 파일 업로드가 있는 경우 우선 처리
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = $base_path . '/uploads/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid('product_') . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                $image_url = '/uploads/products/' . $new_filename;
            } else {
                $error = '이미지 업로드에 실패했습니다.';
            }
        } else {
            $error = '지원하지 않는 이미지 형식입니다. (JPG, PNG, GIF, WebP만 가능)';
        }
    }
    
    if (empty($name)) {
        $error = '상품명을 입력해주세요.';
    } elseif ($price <= 0) {
        $error = '올바른 가격을 입력해주세요.';
    } elseif (!$error) {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // 기존 products 테이블에 필요한 컬럼들 추가
            try {
                // weight 컬럼 추가
                $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS weight VARCHAR(50)");
            } catch (Exception $e) {
                // 컬럼이 이미 존재하거나 문법 지원하지 않는 경우
                try {
                    $pdo->exec("ALTER TABLE products ADD COLUMN weight VARCHAR(50)");
                } catch (Exception $e2) {
                    // 이미 존재하는 경우 무시
                }
            }
            
            try {
                // dimensions 컬럼 추가
                $pdo->exec("ALTER TABLE products ADD COLUMN dimensions VARCHAR(100)");
            } catch (Exception $e) {
                // 이미 존재하는 경우 무시
            }
            
            try {
                // image_url 컬럼 추가
                $pdo->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(500)");
            } catch (Exception $e) {
                // 이미 존재하는 경우 무시
            }
            
            try {
                // is_featured 컬럼 추가
                $pdo->exec("ALTER TABLE products ADD COLUMN is_featured BOOLEAN DEFAULT FALSE");
            } catch (Exception $e) {
                // 이미 존재하는 경우 무시
            }
            
            // 카테고리 테이블이 없으면 생성
            $sql = "CREATE TABLE IF NOT EXISTS product_categories (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                display_order INT DEFAULT 0,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->exec($sql);
            
            // 기본 카테고리 삽입
            $sql = "INSERT IGNORE INTO product_categories (id, name, description) VALUES 
                    (1, '배지', '코코피트, 펄라이트 등 재배용 배지'),
                    (2, '농업용품', '농업에 필요한 각종 도구 및 용품'),
                    (3, '양액', '식물 성장에 필요한 영양액'),
                    (4, '기타', '기타 상품')";
            $pdo->exec($sql);
            
            // Get category name for backward compatibility
            $category_name = '일반';
            if ($category_id) {
                $cat_sql = "SELECT name FROM product_categories WHERE id = ?";
                $cat_stmt = $pdo->prepare($cat_sql);
                $cat_stmt->execute([$category_id]);
                $cat_result = $cat_stmt->fetchColumn();
                if ($cat_result) $category_name = $cat_result;
            }

            // Process features into JSON
            $features_array = [];
            if (!empty($features)) {
                $features_lines = array_filter(array_map('trim', explode("\n", $features)));
                $features_array = $features_lines;
            }
            $features_json = !empty($features_array) ? json_encode($features_array, JSON_UNESCAPED_UNICODE) : null;
            $media_json = null;
            
            // 상품 추가 (실제 테이블 구조에 맞게 stock 사용)
            $sql = "INSERT INTO products (name, description, detailed_description, features, price, category_id, stock, weight, dimensions, image_url, status, is_featured) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $name, $description, $detailed_description, $features_json, $price, $category_id, $stock_quantity, 
                $weight, $dimensions, $image_url, $status, $is_featured
            ]);
            
            $success = '상품이 성공적으로 등록되었습니다.';
            
            // 폼 데이터 초기화
            $name = $description = $detailed_description = $features = $weight = $dimensions = $image_url = '';
            $price = $stock_quantity = $category_id = 0;
            $status = 'active';
            $is_featured = false;
            
        } catch (Exception $e) {
            $error = '상품 등록에 실패했습니다: ' . $e->getMessage();
        }
    }
}

// 카테고리 목록 가져오기
$categories = [];
try {
    $pdo = Database::getInstance()->getConnection();
    $sql = "SELECT id, name FROM product_categories ORDER BY name";
    $stmt = $pdo->query($sql);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // 카테고리 테이블이 없는 경우 기본값 사용
    $categories = [
        ['id' => 1, 'name' => '배지'],
        ['id' => 2, 'name' => '농업용품'],
        ['id' => 3, 'name' => '양액'],
        ['id' => 4, 'name' => '기타']
    ];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>상품 추가 - 탄생 관리자</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/korean-editor.css">
    <script src="/assets/js/korean-editor.js"></script>
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        
        .admin-content {
            flex: 1;
            padding: 30px;
            margin-left: 250px;
        }
        
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .page-title {
            margin: 0;
            color: #333;
            font-size: 1.8rem;
        }
        
        .page-subtitle {
            color: #666;
            margin-top: 5px;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .required {
            color: #dc3545;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
        }
        
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }
        
        .form-checkbox input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .checkbox-text {
            color: #555;
            font-size: 14px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #1e7e34;
        }
        
        .btn-outline {
            background-color: white;
            color: #007bff;
            border: 1px solid #007bff;
        }
        
        .btn-outline:hover {
            background-color: #007bff;
            color: white;
        }
        
        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
        
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        
        .form-help {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        
        @media (max-width: 768px) {
            .admin-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1 class="page-title">📦 상품 추가</h1>
                <p class="page-subtitle">새로운 상품을 등록합니다</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>성공:</strong> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <strong>오류:</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="post" enctype="multipart/form-data">
                    <div class="form-section">
                        <h3 class="form-section-title">기본 정보</h3>
                        
                        <div class="form-group">
                            <label class="form-label" for="name">
                                상품명 <span class="required">*</span>
                            </label>
                            <input type="text" id="name" name="name" class="form-input" 
                                   value="<?= htmlspecialchars($name ?? '') ?>" required>
                            <div class="form-help">고객에게 표시될 상품명을 입력하세요.</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="description">상품 요약 설명</label>
                            <textarea id="description" name="description" class="form-input form-textarea" style="min-height: 80px;"
                                      placeholder="상품의 간단한 요약 설명 (목록에 표시됨)"><?= htmlspecialchars($description ?? '') ?></textarea>
                            <div class="form-help">상품 목록에서 표시될 간단한 설명을 작성하세요.</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="detailed_description">상품 상세 설명</label>
                            <textarea id="detailed_description" name="detailed_description" class="form-input form-textarea" data-korean-editor
                                      data-height="500px" data-upload-url="/admin/api/image_upload.php"
                                      placeholder="상품에 대한 자세한 설명을 입력하세요. 이미지, 글꼴, 색상 등을 자유롭게 편집할 수 있습니다."><?= htmlspecialchars($detailed_description ?? '') ?></textarea>
                            <div class="form-help">네이버 블로그 스타일 에디터로 상품 상세 설명을 작성하세요. 드래그 앤 드롭으로 이미지 업로드, 다양한 텍스트 서식, 링크, 표, 이모지 등을 사용할 수 있습니다.</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="features">상품 주요 특징</label>
                            <textarea id="features" name="features" class="form-input form-textarea" style="min-height: 100px;"
                                      placeholder="특징 1&#10;특징 2&#10;특징 3&#10;각 줄에 하나씩 입력하세요"><?= htmlspecialchars($features ?? '') ?></textarea>
                            <div class="form-help">상품의 주요 특징을 한 줄에 하나씩 입력하세요. 상세 페이지에서 리스트로 표시됩니다.</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">상품 메인 이미지</label>
                            
                            <div class="image-upload-container">
                                <div class="upload-method">
                                    <label class="upload-option">
                                        <input type="radio" name="image_method" value="url" checked onchange="toggleImageMethod('url')">
                                        <span>URL로 이미지 추가</span>
                                    </label>
                                    <label class="upload-option">
                                        <input type="radio" name="image_method" value="file" onchange="toggleImageMethod('file')">
                                        <span>컴퓨터에서 이미지 업로드</span>
                                    </label>
                                </div>
                                
                                <div id="url-input" class="input-section">
                                    <input type="url" id="image_url" name="image_url" class="form-input" 
                                           value="<?= htmlspecialchars($image_url ?? '') ?>"
                                           placeholder="https://example.com/image.jpg">
                                    <div class="form-help">외부 이미지 URL을 입력하세요.</div>
                                </div>
                                
                                <div id="file-input" class="input-section" style="display: none;">
                                    <input type="file" id="product_image" name="product_image" class="form-input" 
                                           accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                    <div class="form-help">JPG, PNG, GIF, WebP 형식의 이미지 파일을 선택하세요.</div>
                                </div>
                            </div>
                        </div>
                        
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="price">
                                    가격 (원) <span class="required">*</span>
                                </label>
                                <input type="number" id="price" name="price" class="form-input" 
                                       value="<?= htmlspecialchars($price ?? '') ?>" 
                                       min="0" step="100" required>
                                <div class="form-help">세금 포함 가격을 입력하세요.</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="category_id">카테고리</label>
                                <select id="category_id" name="category_id" class="form-select">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" 
                                                <?= (($category_id ?? 1) == $category['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-help">상품이 속할 카테고리를 선택하세요.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="form-section-title">재고 및 물리 정보</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="stock_quantity">재고 수량</label>
                                <input type="number" id="stock_quantity" name="stock_quantity" class="form-input" 
                                       value="<?= htmlspecialchars($stock_quantity ?? '0') ?>" min="0">
                                <div class="form-help">현재 보유 중인 재고 수량입니다.</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="weight">중량</label>
                                <input type="text" id="weight" name="weight" class="form-input" 
                                       value="<?= htmlspecialchars($weight ?? '') ?>"
                                       placeholder="예: 10kg, 500g">
                                <div class="form-help">상품의 무게를 입력하세요.</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="dimensions">크기/규격</label>
                            <input type="text" id="dimensions" name="dimensions" class="form-input" 
                                   value="<?= htmlspecialchars($dimensions ?? '') ?>"
                                   placeholder="예: 30x20x10cm, 50L">
                            <div class="form-help">상품의 크기나 규격을 입력하세요.</div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="form-section-title">상품 설정</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="status">상태</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="active" <?= (($status ?? 'active') === 'active') ? 'selected' : '' ?>>
                                        활성 (판매 중)
                                    </option>
                                    <option value="inactive" <?= (($status ?? 'active') === 'inactive') ? 'selected' : '' ?>>
                                        비활성 (판매 중단)
                                    </option>
                                </select>
                                <div class="form-help">상품의 현재 판매 상태입니다.</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-checkbox">
                                    <input type="checkbox" name="is_featured" 
                                           <?= ($is_featured ?? false) ? 'checked' : '' ?>>
                                    <span class="checkbox-text">추천 상품으로 설정</span>
                                </label>
                                <div class="form-help">메인 페이지에 추천 상품으로 표시됩니다.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">💾 상품 등록</button>
                        <a href="index.php" class="btn btn-outline">취소</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script>
        // 에디터는 자동으로 초기화됩니다 (data-korean-editor 속성 사용)
        
        function toggleImageMethod(method) {
            const urlInput = document.getElementById('url-input');
            const fileInput = document.getElementById('file-input');
            
            if (method === 'url') {
                urlInput.style.display = 'block';
                fileInput.style.display = 'none';
                document.getElementById('image_url').required = true;
                document.getElementById('product_image').required = false;
            } else {
                urlInput.style.display = 'none';
                fileInput.style.display = 'block';
                document.getElementById('image_url').required = false;
                document.getElementById('product_image').required = true;
            }
        }
    </script>
    
    <style>
        .image-upload-container {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .upload-method {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .upload-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .upload-option input[type="radio"] {
            margin: 0;
        }
        
        .input-section {
            transition: all 0.3s ease;
        }
        
        .input-section.hidden {
            display: none;
        }
    </style>
</body>
</html>