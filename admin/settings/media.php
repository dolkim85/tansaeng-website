<?php
// Initialize session and auth before any output
$base_path = dirname(dirname(__DIR__));
require_once $base_path . '/classes/Auth.php';
$auth = Auth::getInstance();
$auth->requireAdmin();

require_once $base_path . '/classes/Database.php';

$success = '';
$error = '';

// 미디어 설정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = Database::getInstance()->getConnection();
        
        // 설정 테이블 생성 (존재하지 않는 경우)
        $sql = "CREATE TABLE IF NOT EXISTS site_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(50) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        
        // 파일 업로드 처리
        $upload_dir = $base_path . '/uploads/media/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $settings = [];
        
        // 로고 업로드 처리
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = 'logo.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                // 기존 로고 파일들 삭제
                foreach (glob($upload_dir . 'logo.*') as $old_logo) {
                    unlink($old_logo);
                }
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                    $settings['site_logo'] = '/uploads/media/' . $new_filename;
                }
            } else {
                $error = '로고는 이미지 파일만 업로드 가능합니다.';
            }
        }
        
        // AI 식물분석 동영상 업로드
        if (isset($_FILES['plant_analysis_video']) && $_FILES['plant_analysis_video']['error'] === UPLOAD_ERR_OK) {
            $file_extension = strtolower(pathinfo($_FILES['plant_analysis_video']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['mp4', 'webm', 'ogg'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = 'plant_analysis_video.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                // 기존 파일 삭제
                foreach (glob($upload_dir . 'plant_analysis_video.*') as $old_file) {
                    unlink($old_file);
                }
                
                if (move_uploaded_file($_FILES['plant_analysis_video']['tmp_name'], $upload_path)) {
                    $settings['plant_analysis_video'] = '/uploads/media/' . $new_filename;
                }
            } else {
                $error = 'AI 식물분석 동영상은 mp4, webm, ogg 형식만 가능합니다.';
            }
        }
        
        // 회사 소개 영상 업로드
        if (isset($_FILES['company_intro_video']) && $_FILES['company_intro_video']['error'] === UPLOAD_ERR_OK) {
            $file_extension = strtolower(pathinfo($_FILES['company_intro_video']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['mp4', 'webm', 'ogg'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = 'company_intro_video.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                // 기존 파일 삭제
                foreach (glob($upload_dir . 'company_intro_video.*') as $old_file) {
                    unlink($old_file);
                }
                
                if (move_uploaded_file($_FILES['company_intro_video']['tmp_name'], $upload_path)) {
                    $settings['company_intro_video'] = '/uploads/media/' . $new_filename;
                }
            } else {
                $error = '회사 소개 영상은 mp4, webm, ogg 형식만 가능합니다.';
            }
        }
        
        // 메인 배경 이미지들 업로드 (히어로 슬라이드)
        for ($i = 1; $i <= 3; $i++) {
            $field_name = "hero_image_$i";
            if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK) {
                $file_extension = strtolower(pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $new_filename = "hero_$i." . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    // 기존 파일 삭제
                    foreach (glob($upload_dir . "hero_$i.*") as $old_file) {
                        unlink($old_file);
                    }
                    
                    if (move_uploaded_file($_FILES[$field_name]['tmp_name'], $upload_path)) {
                        $settings["hero_image_$i"] = '/uploads/media/' . $new_filename;
                    }
                }
            }
        }
        
        // 텍스트 설정들 처리
        $text_settings = [
            'plant_analysis_title' => trim($_POST['plant_analysis_title'] ?? ''),
            'plant_analysis_description' => trim($_POST['plant_analysis_description'] ?? ''),
            'company_intro_title' => trim($_POST['company_intro_title'] ?? ''),
            'company_intro_description' => trim($_POST['company_intro_description'] ?? ''),
            'hero_1_title' => trim($_POST['hero_1_title'] ?? ''),
            'hero_1_subtitle' => trim($_POST['hero_1_subtitle'] ?? ''),
            'hero_1_cta_text' => trim($_POST['hero_1_cta_text'] ?? ''),
            'hero_1_cta_link' => trim($_POST['hero_1_cta_link'] ?? ''),
            'hero_2_title' => trim($_POST['hero_2_title'] ?? ''),
            'hero_2_subtitle' => trim($_POST['hero_2_subtitle'] ?? ''),
            'hero_2_cta_text' => trim($_POST['hero_2_cta_text'] ?? ''),
            'hero_2_cta_link' => trim($_POST['hero_2_cta_link'] ?? ''),
            'hero_3_title' => trim($_POST['hero_3_title'] ?? ''),
            'hero_3_subtitle' => trim($_POST['hero_3_subtitle'] ?? ''),
            'hero_3_cta_text' => trim($_POST['hero_3_cta_text'] ?? ''),
            'hero_3_cta_link' => trim($_POST['hero_3_cta_link'] ?? ''),
        ];
        
        $settings = array_merge($settings, $text_settings);
        
        // 데이터베이스에 설정 저장
        foreach ($settings as $key => $value) {
            $sql = "INSERT INTO site_settings (setting_key, setting_value) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$key, $value, $value]);
        }
        
        $success = '미디어 설정이 저장되었습니다.';
        
    } catch (Exception $e) {
        $error = '설정 저장에 실패했습니다: ' . $e->getMessage();
    }
}

// 현재 설정값 불러오기
$current_settings = [];
try {
    $pdo = Database::getInstance()->getConnection();
    
    $sql = "SELECT setting_key, setting_value FROM site_settings";
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // 테이블이 없는 경우 무시
}

// 기본값 설정
$defaults = [
    'site_logo' => '/assets/images/logo.png',
    'plant_analysis_title' => 'AI 식물분석 서비스',
    'plant_analysis_description' => '라즈베리파이 카메라와 AI 기술을 활용하여 식물의 건강상태를 실시간으로 분석하고 관리할 수 있습니다.',
    'plant_analysis_video' => '/assets/videos/plant-analysis-demo.mp4',
    'company_intro_title' => '탄생 소개 영상',
    'company_intro_description' => '우리의 기술과 비전을 영상으로 만나보세요',
    'company_intro_video' => '/assets/videos/company-intro.mp4',
    'hero_1_title' => '탄생 스마트팜 배지',
    'hero_1_subtitle' => '최고 품질의 수경재배용 배지로 건강한 농작물을 키워보세요',
    'hero_1_cta_text' => '제품 보기',
    'hero_1_cta_link' => '/pages/products/media.php',
    'hero_image_1' => '/assets/images/banners/hero-1.jpg',
    'hero_2_title' => 'AI 식물분석 시스템',
    'hero_2_subtitle' => '첨단 기술로 식물의 건강상태를 정확하게 분석합니다',
    'hero_2_cta_text' => '분석하기',
    'hero_2_cta_link' => '/pages/plant_analysis/',
    'hero_image_2' => '/assets/images/banners/hero-2.jpg',
    'hero_3_title' => '스마트팜 솔루션',
    'hero_3_subtitle' => '라즈베리파이와 AI 기술이 결합된 스마트한 농업',
    'hero_3_cta_text' => '자세히 보기',
    'hero_3_cta_link' => '/pages/company/about.php',
    'hero_image_3' => '/assets/images/banners/hero-3.jpg',
];

foreach ($defaults as $key => $default) {
    if (!isset($current_settings[$key])) {
        $current_settings[$key] = $default;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>미디어 관리 - 탄생 관리자</title>
    <link rel="stylesheet" href="/assets/css/main.css">
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
        
        .settings-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
        
        .form-section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8f9fa;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .form-input.textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .file-input {
            border: 2px dashed #ddd;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
            transition: border-color 0.3s ease;
        }
        
        .file-input:hover {
            border-color: #007bff;
        }
        
        .file-input input[type="file"] {
            margin-bottom: 10px;
        }
        
        .current-file {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }
        
        .current-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .current-video {
            max-width: 300px;
            max-height: 200px;
            margin-top: 10px;
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
            background-color: transparent;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .hero-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
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
                <h1 class="page-title">🎬 미디어 관리</h1>
                <p class="page-subtitle">사이트 로고, 동영상, 이미지 등을 관리합니다</p>
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
            
            <form class="settings-form" method="post" enctype="multipart/form-data">
                
                <!-- 로고 설정 -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <span>🏷️</span> 사이트 로고
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label" for="logo">로고 파일</label>
                        <div class="file-input">
                            <input type="file" id="logo" name="logo" accept="image/*">
                            <div class="form-help">PNG, JPG, SVG 파일 지원 (권장 크기: 200x60px)</div>
                        </div>
                        <?php if (!empty($current_settings['site_logo'])): ?>
                            <div class="current-file">
                                <strong>현재 로고:</strong>
                                <img src="<?= htmlspecialchars($current_settings['site_logo']) ?>" 
                                     alt="현재 로고" class="current-image">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 메인 히어로 섹션 -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <span>🎯</span> 메인 페이지 히어로 섹션
                    </h3>
                    
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                    <div class="hero-section">
                        <h4>히어로 슬라이드 <?= $i ?></h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="hero_<?= $i ?>_title">제목</label>
                                <input type="text" id="hero_<?= $i ?>_title" name="hero_<?= $i ?>_title" class="form-input" 
                                       value="<?= htmlspecialchars($current_settings["hero_{$i}_title"]) ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="hero_<?= $i ?>_cta_text">버튼 텍스트</label>
                                <input type="text" id="hero_<?= $i ?>_cta_text" name="hero_<?= $i ?>_cta_text" class="form-input" 
                                       value="<?= htmlspecialchars($current_settings["hero_{$i}_cta_text"]) ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="hero_<?= $i ?>_subtitle">부제목</label>
                            <input type="text" id="hero_<?= $i ?>_subtitle" name="hero_<?= $i ?>_subtitle" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings["hero_{$i}_subtitle"]) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="hero_<?= $i ?>_cta_link">버튼 링크</label>
                            <input type="text" id="hero_<?= $i ?>_cta_link" name="hero_<?= $i ?>_cta_link" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings["hero_{$i}_cta_link"]) ?>"
                                   placeholder="/pages/products/ 또는 https://example.com">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="hero_image_<?= $i ?>">배경 이미지</label>
                            <div class="file-input">
                                <input type="file" id="hero_image_<?= $i ?>" name="hero_image_<?= $i ?>" accept="image/*">
                                <div class="form-help">권장 크기: 1920x1080px</div>
                            </div>
                            <?php if (!empty($current_settings["hero_image_$i"])): ?>
                                <div class="current-file">
                                    <strong>현재 이미지:</strong>
                                    <img src="<?= htmlspecialchars($current_settings["hero_image_$i"]) ?>" 
                                         alt="히어로 이미지 <?= $i ?>" class="current-image">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
                
                <!-- AI 식물분석 섹션 -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <span>🤖</span> AI 식물분석 섹션
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="plant_analysis_title">섹션 제목</label>
                            <input type="text" id="plant_analysis_title" name="plant_analysis_title" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings['plant_analysis_title']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="plant_analysis_video">데모 영상</label>
                            <div class="file-input">
                                <input type="file" id="plant_analysis_video" name="plant_analysis_video" accept="video/*">
                                <div class="form-help">MP4, WebM, OGG 형식 지원</div>
                            </div>
                            <?php if (!empty($current_settings['plant_analysis_video'])): ?>
                                <div class="current-file">
                                    <strong>현재 동영상:</strong>
                                    <video controls class="current-video">
                                        <source src="<?= htmlspecialchars($current_settings['plant_analysis_video']) ?>" 
                                                type="video/<?= pathinfo($current_settings['plant_analysis_video'], PATHINFO_EXTENSION) ?>">
                                        브라우저가 비디오를 지원하지 않습니다.
                                    </video>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="plant_analysis_description">섹션 설명</label>
                        <textarea id="plant_analysis_description" name="plant_analysis_description" class="form-input textarea"
                                  placeholder="AI 식물분석 서비스에 대한 설명을 입력하세요"><?= htmlspecialchars($current_settings['plant_analysis_description']) ?></textarea>
                    </div>
                </div>
                
                <!-- 회사 소개 영상 섹션 -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <span>🏢</span> 회사 소개 영상 섹션
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="company_intro_title">섹션 제목</label>
                            <input type="text" id="company_intro_title" name="company_intro_title" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings['company_intro_title']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="company_intro_video">소개 영상</label>
                            <div class="file-input">
                                <input type="file" id="company_intro_video" name="company_intro_video" accept="video/*">
                                <div class="form-help">MP4, WebM, OGG 형식 지원</div>
                            </div>
                            <?php if (!empty($current_settings['company_intro_video'])): ?>
                                <div class="current-file">
                                    <strong>현재 동영상:</strong>
                                    <video controls class="current-video">
                                        <source src="<?= htmlspecialchars($current_settings['company_intro_video']) ?>" 
                                                type="video/<?= pathinfo($current_settings['company_intro_video'], PATHINFO_EXTENSION) ?>">
                                        브라우저가 비디오를 지원하지 않습니다.
                                    </video>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="company_intro_description">섹션 설명</label>
                        <textarea id="company_intro_description" name="company_intro_description" class="form-input textarea"
                                  placeholder="회사 소개 영상에 대한 설명을 입력하세요"><?= htmlspecialchars($current_settings['company_intro_description']) ?></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">💾 설정 저장</button>
                    <a href="/admin/settings/" class="btn btn-outline">⚙️ 기본 설정으로 돌아가기</a>
                    <button type="button" onclick="previewChanges()" class="btn btn-primary">👁️ 미리보기</button>
                </div>
            </form>
        </main>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script>
        function previewChanges() {
            // 새 창에서 메인페이지 미리보기
            window.open('/', '_blank');
        }
        
        // 파일 선택시 미리보기
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;
                
                const preview = this.parentElement.nextElementSibling;
                if (!preview || !preview.classList.contains('current-file')) {
                    return;
                }
                
                if (file.type.startsWith('image/')) {
                    const img = preview.querySelector('img');
                    if (img) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            img.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                } else if (file.type.startsWith('video/')) {
                    const video = preview.querySelector('video source');
                    if (video) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            video.src = e.target.result;
                            video.parentElement.load();
                        };
                        reader.readAsDataURL(file);
                    }
                }
            });
        });
    </script>
</body>
</html>