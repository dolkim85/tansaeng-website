<?php
// Initialize session and auth before any output
$base_path = dirname(dirname(__DIR__));
require_once $base_path . '/classes/Auth.php';
$auth = Auth::getInstance();
$auth->requireAdmin();

require_once $base_path . '/classes/Database.php';

$success = '';
$error = '';

// 설정값 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name'] ?? '');
    $site_description = trim($_POST['site_description'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    
    // 푸터 설정
    $footer_company_desc = trim($_POST['footer_company_desc'] ?? '');
    $footer_social_facebook = trim($_POST['footer_social_facebook'] ?? '#');
    $footer_social_instagram = trim($_POST['footer_social_instagram'] ?? '#');
    $footer_social_youtube = trim($_POST['footer_social_youtube'] ?? '#');
    $footer_social_blog = trim($_POST['footer_social_blog'] ?? '#');
    $footer_address = trim($_POST['footer_address'] ?? '');
    $footer_fax = trim($_POST['footer_fax'] ?? '');
    $footer_business_hours_weekday = trim($_POST['footer_business_hours_weekday'] ?? '');
    $footer_business_hours_saturday = trim($_POST['footer_business_hours_saturday'] ?? '');
    $footer_business_hours_holiday = trim($_POST['footer_business_hours_holiday'] ?? '');
    $footer_copyright = trim($_POST['footer_copyright'] ?? '');
    
    if (empty($site_name)) {
        $error = '사이트명을 입력해주세요.';
    } elseif (empty($contact_email)) {
        $error = '연락처 이메일을 입력해주세요.';
    } else {
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
            
            // 설정값 저장/업데이트
            $settings = [
                'site_name' => $site_name,
                'site_description' => $site_description,
                'contact_email' => $contact_email,
                'contact_phone' => $contact_phone,
                'address' => $address,
                'company_name' => $company_name,
                'footer_company_desc' => $footer_company_desc,
                'footer_social_facebook' => $footer_social_facebook,
                'footer_social_instagram' => $footer_social_instagram,
                'footer_social_youtube' => $footer_social_youtube,
                'footer_social_blog' => $footer_social_blog,
                'footer_address' => $footer_address,
                'footer_fax' => $footer_fax,
                'footer_business_hours_weekday' => $footer_business_hours_weekday,
                'footer_business_hours_saturday' => $footer_business_hours_saturday,
                'footer_business_hours_holiday' => $footer_business_hours_holiday,
                'footer_copyright' => $footer_copyright
            ];
            
            foreach ($settings as $key => $value) {
                $sql = "INSERT INTO site_settings (setting_key, setting_value) 
                        VALUES (?, ?) 
                        ON DUPLICATE KEY UPDATE setting_value = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$key, $value, $value]);
            }
            
            $success = '설정이 저장되었습니다.';
            
        } catch (Exception $e) {
            $error = '설정 저장에 실패했습니다: ' . $e->getMessage();
        }
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
    'site_name' => '탄생',
    'site_description' => '스마트팜 배지 제조 전문회사',
    'contact_email' => 'info@tangsaeng.com',
    'contact_phone' => '02-0000-0000',
    'address' => '',
    'company_name' => '탄생',
    'footer_company_desc' => '스마트팜 배지 제조 전문회사로서 최고 품질의 제품과 혁신적인 AI 기술을 통해 미래 농업을 선도합니다.',
    'footer_social_facebook' => '#',
    'footer_social_instagram' => '#',
    'footer_social_youtube' => '#',
    'footer_social_blog' => '#',
    'footer_address' => '서울특별시 강남구 테헤란로 123',
    'footer_fax' => '02-0000-0001',
    'footer_business_hours_weekday' => '평일: 09:00 - 18:00',
    'footer_business_hours_saturday' => '토요일: 09:00 - 13:00',
    'footer_business_hours_holiday' => '일요일/공휴일: 휴무',
    'footer_copyright' => '© 2024 탄생(Tangsaeng). All rights reserved.'
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
    <title>사이트 설정 - 탄생 관리자</title>
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
        
        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
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
        
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .admin-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .form-row,
            .settings-grid {
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
                <h1 class="page-title">⚙️ 사이트 설정</h1>
                <p class="page-subtitle">웹사이트의 기본 설정을 관리합니다</p>
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
            
            <form class="settings-form" method="post">
                <div class="form-section">
                    <h3 class="form-section-title">헤더 & 기본 정보</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="site_name">사이트명 *</label>
                            <input type="text" id="site_name" name="site_name" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings['site_name']) ?>" required>
                            <div class="form-help">브라우저 탭과 헤더에 표시될 사이트명입니다.</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="company_name">회사명</label>
                            <input type="text" id="company_name" name="company_name" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings['company_name']) ?>">
                            <div class="form-help">공식 회사명을 입력하세요.</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="site_description">사이트 설명</label>
                        <textarea id="site_description" name="site_description" class="form-input textarea"
                                  placeholder="사이트에 대한 간단한 설명을 입력하세요"><?= htmlspecialchars($current_settings['site_description']) ?></textarea>
                        <div class="form-help">검색엔진과 소셜미디어에서 표시될 설명입니다.</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="contact_email">헤더 연락처 이메일 *</label>
                            <input type="email" id="contact_email" name="contact_email" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings['contact_email']) ?>" required>
                            <div class="form-help">헤더에 표시될 이메일 주소입니다.</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="contact_phone">헤더 연락처 전화번호</label>
                            <input type="tel" id="contact_phone" name="contact_phone" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings['contact_phone']) ?>">
                            <div class="form-help">헤더에 표시될 전화번호입니다.</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="form-section-title">푸터 정보</h3>
                    
                    <div class="form-group">
                        <label class="form-label" for="footer_company_desc">회사 소개 문구</label>
                        <textarea id="footer_company_desc" name="footer_company_desc" class="form-input textarea"
                                  placeholder="푸터에 표시될 회사 소개 문구를 입력하세요"><?= htmlspecialchars($current_settings['footer_company_desc']) ?></textarea>
                        <div class="form-help">푸터 상단에 표시될 회사 소개 문구입니다.</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="footer_address">푸터 주소</label>
                            <input type="text" id="footer_address" name="footer_address" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings['footer_address']) ?>">
                            <div class="form-help">푸터에 표시될 회사 주소입니다.</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="footer_fax">팩스번호</label>
                            <input type="tel" id="footer_fax" name="footer_fax" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings['footer_fax']) ?>">
                            <div class="form-help">푸터에 표시될 팩스번호입니다.</div>
                        </div>
                    </div>
                    
                    <h4 class="form-section-title" style="font-size: 1rem; margin: 2rem 0 1rem 0;">소셜 링크</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="footer_social_facebook">페이스북 링크</label>
                            <input type="url" id="footer_social_facebook" name="footer_social_facebook" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings['footer_social_facebook']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="footer_social_instagram">인스타그램 링크</label>
                            <input type="url" id="footer_social_instagram" name="footer_social_instagram" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings['footer_social_instagram']) ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="footer_social_youtube">유튜브 링크</label>
                            <input type="url" id="footer_social_youtube" name="footer_social_youtube" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings['footer_social_youtube']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="footer_social_blog">블로그 링크</label>
                            <input type="url" id="footer_social_blog" name="footer_social_blog" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings['footer_social_blog']) ?>">
                        </div>
                    </div>
                    
                    <h4 class="form-section-title" style="font-size: 1rem; margin: 2rem 0 1rem 0;">운영시간</h4>
                    <div class="form-group">
                        <label class="form-label" for="footer_business_hours_weekday">평일 운영시간</label>
                        <input type="text" id="footer_business_hours_weekday" name="footer_business_hours_weekday" class="form-input" 
                               value="<?= htmlspecialchars($current_settings['footer_business_hours_weekday']) ?>"
                               placeholder="예: 평일: 09:00 - 18:00">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="footer_business_hours_saturday">토요일 운영시간</label>
                            <input type="text" id="footer_business_hours_saturday" name="footer_business_hours_saturday" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings['footer_business_hours_saturday']) ?>"
                                   placeholder="예: 토요일: 09:00 - 13:00">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="footer_business_hours_holiday">휴일 안내</label>
                            <input type="text" id="footer_business_hours_holiday" name="footer_business_hours_holiday" class="form-input" 
                                   value="<?= htmlspecialchars($current_settings['footer_business_hours_holiday']) ?>"
                                   placeholder="예: 일요일/공휴일: 휴무">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="footer_copyright">저작권 표시</label>
                        <input type="text" id="footer_copyright" name="footer_copyright" class="form-input" 
                               value="<?= htmlspecialchars($current_settings['footer_copyright']) ?>"
                               placeholder="예: © 2024 탄생(Tangsaeng). All rights reserved.">
                        <div class="form-help">푸터 하단에 표시될 저작권 정보입니다.</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">💾 설정 저장</button>
                </div>
            </form>
            
            <!-- 추가 설정 메뉴 -->
            <div class="additional-settings" style="margin-top: 30px;">
                <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="color: #333; margin-bottom: 20px;">🔗 추가 설정 메뉴</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                        <a href="/admin/settings/company.php" style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f8f9fa; border-radius: 6px; text-decoration: none; color: #333; transition: background 0.3s ease;">
                            <span style="font-size: 1.5rem;">🏢</span>
                            <div>
                                <strong>회사 소개 관리</strong>
                                <div style="font-size: 0.9rem; color: #666;">메인페이지 회사 소개 편집</div>
                            </div>
                        </a>
                        
                        <a href="/admin/settings/media.php" style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f8f9fa; border-radius: 6px; text-decoration: none; color: #333; transition: background 0.3s ease;">
                            <span style="font-size: 1.5rem;">🎬</span>
                            <div>
                                <strong>미디어 관리</strong>
                                <div style="font-size: 0.9rem; color: #666;">로고, 동영상, 이미지 관리</div>
                            </div>
                        </a>
                        
                        <a href="/admin/settings/footer.php" style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f8f9fa; border-radius: 6px; text-decoration: none; color: #333; transition: background 0.3s ease;">
                            <span style="font-size: 1.5rem;">🦶</span>
                            <div>
                                <strong>푸터 관리</strong>
                                <div style="font-size: 0.9rem; color: #666;">푸터 메뉴 및 정보 관리</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>