<?php
// Initialize session and auth before any output
$base_path = dirname(dirname(__DIR__));
require_once $base_path . '/classes/Auth.php';
$auth = Auth::getInstance();
$auth->requireAdmin();

require_once $base_path . '/classes/Database.php';

$success = '';
$error = '';

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_footer_settings') {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // 기본 푸터 설정 업데이트
            $basic_settings = [
                'footer_company_desc' => $_POST['footer_company_desc'] ?? '',
                'footer_address' => $_POST['footer_address'] ?? '',
                'footer_phone' => $_POST['footer_phone'] ?? '',
                'footer_fax' => $_POST['footer_fax'] ?? '',
                'footer_email' => $_POST['footer_email'] ?? '',
                'footer_business_hours_weekday' => $_POST['footer_business_hours_weekday'] ?? '',
                'footer_business_hours_saturday' => $_POST['footer_business_hours_saturday'] ?? '',
                'footer_business_hours_holiday' => $_POST['footer_business_hours_holiday'] ?? '',
                'footer_copyright' => $_POST['footer_copyright'] ?? ''
            ];
            
            foreach ($basic_settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([$key, $value]);
            }
            
            $success = '기본 푸터 설정이 업데이트되었습니다.';
        } catch (Exception $e) {
            $error = '설정 업데이트 중 오류가 발생했습니다: ' . $e->getMessage();
        }
    }
    
    elseif ($action === 'update_social_links') {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // 소셜 링크 업데이트
            $social_settings = [
                'footer_social_facebook' => $_POST['footer_social_facebook'] ?? '',
                'footer_social_instagram' => $_POST['footer_social_instagram'] ?? '',
                'footer_social_youtube' => $_POST['footer_social_youtube'] ?? '',
                'footer_social_blog' => $_POST['footer_social_blog'] ?? ''
            ];
            
            foreach ($social_settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([$key, $value]);
            }
            
            $success = '소셜 링크가 업데이트되었습니다.';
        } catch (Exception $e) {
            $error = '소셜 링크 업데이트 중 오류가 발생했습니다: ' . $e->getMessage();
        }
    }
    
    elseif ($action === 'update_footer_menus') {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // 푸터 메뉴 업데이트 
            $menu_sections = [
                'footer_menu_products' => json_encode($_POST['footer_menu_products'] ?? []),
                'footer_menu_services' => json_encode($_POST['footer_menu_services'] ?? []),
                'footer_menu_company' => json_encode($_POST['footer_menu_company'] ?? []),
                'footer_menu_legal' => json_encode($_POST['footer_menu_legal'] ?? [])
            ];
            
            foreach ($menu_sections as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([$key, $value]);
            }
            
            $success = '푸터 메뉴가 업데이트되었습니다.';
        } catch (Exception $e) {
            $error = '푸터 메뉴 업데이트 중 오류가 발생했습니다: ' . $e->getMessage();
        }
    }
}

// 현재 설정 가져오기
$current_settings = [];
try {
    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch()) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $error = '설정을 불러오는 중 오류가 발생했습니다: ' . $e->getMessage();
}

// 기본값 설정
$footer_company_desc = $current_settings['footer_company_desc'] ?? '스마트팜 배지 제조 전문회사로서 최고 품질의 제품과 혁신적인 AI 기술을 통해 미래 농업을 선도합니다.';
$footer_address = $current_settings['footer_address'] ?? '서울특별시 강남구 테헤란로 123';
$footer_phone = $current_settings['footer_phone'] ?? '02-0000-0000';
$footer_fax = $current_settings['footer_fax'] ?? '02-0000-0001';
$footer_email = $current_settings['footer_email'] ?? 'info@tangsaeng.com';
$footer_business_hours_weekday = $current_settings['footer_business_hours_weekday'] ?? '평일: 09:00 - 18:00';
$footer_business_hours_saturday = $current_settings['footer_business_hours_saturday'] ?? '토요일: 09:00 - 13:00';
$footer_business_hours_holiday = $current_settings['footer_business_hours_holiday'] ?? '일요일/공휴일: 휴무';
$footer_copyright = $current_settings['footer_copyright'] ?? '© 2024 탄생(Tangsaeng). All rights reserved.';

$footer_social_facebook = $current_settings['footer_social_facebook'] ?? '';
$footer_social_instagram = $current_settings['footer_social_instagram'] ?? '';
$footer_social_youtube = $current_settings['footer_social_youtube'] ?? '';
$footer_social_blog = $current_settings['footer_social_blog'] ?? '';

// 푸터 메뉴 기본값
$default_products = [
    ['name' => '배지소개', 'url' => '/pages/products/media.php'],
    ['name' => '코코피트 배지', 'url' => '/pages/store/category.php?category=1'],
    ['name' => '펄라이트 배지', 'url' => '/pages/store/category.php?category=1'],
    ['name' => '양액', 'url' => '/pages/store/category.php?category=3'],
    ['name' => '농업용품', 'url' => '/pages/store/category.php?category=2']
];

$default_services = [
    ['name' => '식물분석', 'url' => '/pages/plant_analysis/'],
    ['name' => '기술정보', 'url' => '/pages/products/technology.php'],
    ['name' => 'FAQ', 'url' => '/pages/support/faq.php'],
    ['name' => '기술지원', 'url' => '/pages/support/contact.php'],
    ['name' => '공지사항', 'url' => '/pages/support/notice.php']
];

$default_company = [
    ['name' => '회사소개', 'url' => '/pages/company/about.php'],
    ['name' => '연혁', 'url' => '/pages/company/history.php'],
    ['name' => '팀소개', 'url' => '/pages/company/team.php']
];

$default_legal = [
    ['name' => '개인정보처리방침', 'url' => '/pages/legal/privacy.php'],
    ['name' => '이용약관', 'url' => '/pages/legal/terms.php'],
    ['name' => '사이트맵', 'url' => '/sitemap.php']
];

$footer_menu_products = json_decode($current_settings['footer_menu_products'] ?? '', true) ?: $default_products;
$footer_menu_services = json_decode($current_settings['footer_menu_services'] ?? '', true) ?: $default_services;
$footer_menu_company = json_decode($current_settings['footer_menu_company'] ?? '', true) ?: $default_company;
$footer_menu_legal = json_decode($current_settings['footer_menu_legal'] ?? '', true) ?: $default_legal;
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>푸터 관리 - 탄생 관리자</title>
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
        
        .settings-section { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .settings-section h3 { margin-bottom: 1.5rem; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 0.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #555; }
        .form-input, .form-textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-textarea { min-height: 100px; resize: vertical; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; margin: 0.25rem; transition: all 0.3s; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #1e7e34; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-outline { background: white; color: #007bff; border: 1px solid #007bff; }
        .btn-outline:hover { background: #007bff; color: white; }
        
        .menu-item { border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 4px; background: #f8f9fa; }
        .menu-inputs { display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end; }
        .menu-section { margin-bottom: 3rem; }
        
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 6px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .tabs { display: flex; border-bottom: 2px solid #dee2e6; margin-bottom: 2rem; }
        .tab { padding: 1rem 2rem; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.3s; }
        .tab:hover { background: #f8f9fa; }
        .tab.active { border-bottom-color: #007bff; background: #f8f9fa; font-weight: 600; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        @media (max-width: 768px) {
            .admin-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1 class="page-title">🦶 푸터 관리</h1>
                <p class="page-subtitle">메인 페이지 푸터의 내용과 메뉴를 관리합니다</p>
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

            <!-- 탭 메뉴 -->
            <div class="tabs">
                <div class="tab active" onclick="showTab('basic-settings')">기본 정보</div>
                <div class="tab" onclick="showTab('social-links')">소셜 링크</div>
                <div class="tab" onclick="showTab('menu-settings')">메뉴 관리</div>
            </div>

            <!-- 기본 설정 -->
            <div id="basic-settings" class="tab-content active">
                <div class="settings-section">
                    <h3>📋 기본 정보 설정</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="update_footer_settings">
                        
                        <div class="form-group">
                            <label class="form-label">회사 설명</label>
                            <textarea name="footer_company_desc" class="form-textarea" placeholder="회사 소개 문구를 입력하세요"><?= htmlspecialchars($footer_company_desc) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">주소</label>
                            <input type="text" name="footer_address" class="form-input" 
                                   value="<?= htmlspecialchars($footer_address) ?>" placeholder="회사 주소">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">전화번호</label>
                            <input type="text" name="footer_phone" class="form-input" 
                                   value="<?= htmlspecialchars($footer_phone) ?>" placeholder="02-0000-0000">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">팩스번호</label>
                            <input type="text" name="footer_fax" class="form-input" 
                                   value="<?= htmlspecialchars($footer_fax) ?>" placeholder="02-0000-0001">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">이메일</label>
                            <input type="email" name="footer_email" class="form-input" 
                                   value="<?= htmlspecialchars($footer_email) ?>" placeholder="info@tangsaeng.com">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">평일 운영시간</label>
                            <input type="text" name="footer_business_hours_weekday" class="form-input" 
                                   value="<?= htmlspecialchars($footer_business_hours_weekday) ?>" placeholder="평일: 09:00 - 18:00">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">토요일 운영시간</label>
                            <input type="text" name="footer_business_hours_saturday" class="form-input" 
                                   value="<?= htmlspecialchars($footer_business_hours_saturday) ?>" placeholder="토요일: 09:00 - 13:00">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">휴무일</label>
                            <input type="text" name="footer_business_hours_holiday" class="form-input" 
                                   value="<?= htmlspecialchars($footer_business_hours_holiday) ?>" placeholder="일요일/공휴일: 휴무">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">저작권 표시</label>
                            <input type="text" name="footer_copyright" class="form-input" 
                                   value="<?= htmlspecialchars($footer_copyright) ?>" placeholder="© 2024 탄생(Tangsaeng). All rights reserved.">
                        </div>
                        
                        <button type="submit" class="btn btn-success">기본 정보 저장</button>
                    </form>
                </div>
            </div>

            <!-- 소셜 링크 -->
            <div id="social-links" class="tab-content">
                <div class="settings-section">
                    <h3>🔗 소셜 링크 설정</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="update_social_links">
                        
                        <div class="form-group">
                            <label class="form-label">📘 페이스북 링크</label>
                            <input type="url" name="footer_social_facebook" class="form-input" 
                                   value="<?= htmlspecialchars($footer_social_facebook) ?>" placeholder="https://facebook.com/tangsaeng">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">📷 인스타그램 링크</label>
                            <input type="url" name="footer_social_instagram" class="form-input" 
                                   value="<?= htmlspecialchars($footer_social_instagram) ?>" placeholder="https://instagram.com/tangsaeng">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">📺 유튜브 링크</label>
                            <input type="url" name="footer_social_youtube" class="form-input" 
                                   value="<?= htmlspecialchars($footer_social_youtube) ?>" placeholder="https://youtube.com/@tangsaeng">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">📝 블로그 링크</label>
                            <input type="url" name="footer_social_blog" class="form-input" 
                                   value="<?= htmlspecialchars($footer_social_blog) ?>" placeholder="https://blog.tangsaeng.com">
                        </div>
                        
                        <button type="submit" class="btn btn-success">소셜 링크 저장</button>
                    </form>
                </div>
            </div>

            <!-- 메뉴 설정 -->
            <div id="menu-settings" class="tab-content">
                <form method="post">
                    <input type="hidden" name="action" value="update_footer_menus">
                    
                    <!-- 제품 메뉴 -->
                    <div class="menu-section">
                        <div class="settings-section">
                            <h3>📦 제품 메뉴</h3>
                            <div id="products-menu">
                                <?php foreach ($footer_menu_products as $index => $item): ?>
                                <div class="menu-item">
                                    <div class="menu-inputs">
                                        <input type="text" name="footer_menu_products[<?= $index ?>][name]" 
                                               class="form-input" placeholder="메뉴명" 
                                               value="<?= htmlspecialchars($item['name']) ?>">
                                        <input type="text" name="footer_menu_products[<?= $index ?>][url]" 
                                               class="form-input" placeholder="링크 URL" 
                                               value="<?= htmlspecialchars($item['url']) ?>">
                                        <button type="button" class="btn btn-danger" onclick="removeMenuItem(this)">삭제</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-outline" onclick="addMenuItem('products-menu', 'footer_menu_products')">+ 제품 메뉴 추가</button>
                        </div>
                    </div>

                    <!-- 서비스 메뉴 -->
                    <div class="menu-section">
                        <div class="settings-section">
                            <h3>🔧 서비스 메뉴</h3>
                            <div id="services-menu">
                                <?php foreach ($footer_menu_services as $index => $item): ?>
                                <div class="menu-item">
                                    <div class="menu-inputs">
                                        <input type="text" name="footer_menu_services[<?= $index ?>][name]" 
                                               class="form-input" placeholder="메뉴명" 
                                               value="<?= htmlspecialchars($item['name']) ?>">
                                        <input type="text" name="footer_menu_services[<?= $index ?>][url]" 
                                               class="form-input" placeholder="링크 URL" 
                                               value="<?= htmlspecialchars($item['url']) ?>">
                                        <button type="button" class="btn btn-danger" onclick="removeMenuItem(this)">삭제</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-outline" onclick="addMenuItem('services-menu', 'footer_menu_services')">+ 서비스 메뉴 추가</button>
                        </div>
                    </div>

                    <!-- 회사정보 메뉴 -->
                    <div class="menu-section">
                        <div class="settings-section">
                            <h3>🏢 회사정보 메뉴</h3>
                            <div id="company-menu">
                                <?php foreach ($footer_menu_company as $index => $item): ?>
                                <div class="menu-item">
                                    <div class="menu-inputs">
                                        <input type="text" name="footer_menu_company[<?= $index ?>][name]" 
                                               class="form-input" placeholder="메뉴명" 
                                               value="<?= htmlspecialchars($item['name']) ?>">
                                        <input type="text" name="footer_menu_company[<?= $index ?>][url]" 
                                               class="form-input" placeholder="링크 URL" 
                                               value="<?= htmlspecialchars($item['url']) ?>">
                                        <button type="button" class="btn btn-danger" onclick="removeMenuItem(this)">삭제</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-outline" onclick="addMenuItem('company-menu', 'footer_menu_company')">+ 회사정보 메뉴 추가</button>
                        </div>
                    </div>

                    <!-- 법적 정보 메뉴 -->
                    <div class="menu-section">
                        <div class="settings-section">
                            <h3>⚖️ 법적 정보 메뉴</h3>
                            <div id="legal-menu">
                                <?php foreach ($footer_menu_legal as $index => $item): ?>
                                <div class="menu-item">
                                    <div class="menu-inputs">
                                        <input type="text" name="footer_menu_legal[<?= $index ?>][name]" 
                                               class="form-input" placeholder="메뉴명" 
                                               value="<?= htmlspecialchars($item['name']) ?>">
                                        <input type="text" name="footer_menu_legal[<?= $index ?>][url]" 
                                               class="form-input" placeholder="링크 URL" 
                                               value="<?= htmlspecialchars($item['url']) ?>">
                                        <button type="button" class="btn btn-danger" onclick="removeMenuItem(this)">삭제</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-outline" onclick="addMenuItem('legal-menu', 'footer_menu_legal')">+ 법적 정보 메뉴 추가</button>
                        </div>
                    </div>

                    <div class="settings-section">
                        <button type="submit" class="btn btn-success">💾 모든 메뉴 저장</button>
                        <a href="/" class="btn btn-outline" target="_blank">🌐 푸터 미리보기</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function showTab(tabName) {
            // 모든 탭 숨기기
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // 선택된 탭 보여주기
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function addMenuItem(containerId, fieldName) {
            const container = document.getElementById(containerId);
            const menuItems = container.querySelectorAll('.menu-item');
            const newIndex = menuItems.length;
            
            const newItem = document.createElement('div');
            newItem.className = 'menu-item';
            newItem.innerHTML = `
                <div class="menu-inputs">
                    <input type="text" name="${fieldName}[${newIndex}][name]" 
                           class="form-input" placeholder="메뉴명">
                    <input type="text" name="${fieldName}[${newIndex}][url]" 
                           class="form-input" placeholder="링크 URL">
                    <button type="button" class="btn btn-danger" onclick="removeMenuItem(this)">삭제</button>
                </div>
            `;
            
            container.appendChild(newItem);
        }
        
        function removeMenuItem(button) {
            const menuItem = button.closest('.menu-item');
            menuItem.remove();
            
            // 인덱스 재정렬
            const container = menuItem.closest('.settings-section').querySelector('[id$="-menu"]');
            const fieldName = container.id.replace('-menu', '').replace('-', '_');
            const items = container.querySelectorAll('.menu-item');
            
            items.forEach((item, index) => {
                const nameInput = item.querySelector('input[name*="[name]"]');
                const urlInput = item.querySelector('input[name*="[url]"]');
                
                nameInput.name = `footer_menu_${fieldName}[${index}][name]`;
                urlInput.name = `footer_menu_${fieldName}[${index}][url]`;
            });
        }
    </script>
</body>
</html>