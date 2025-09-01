<?php
// 사이트 설정 불러오기
$footer_settings = [];
try {
    if (!isset($pdo)) {
        require_once __DIR__ . '/../classes/Database.php';
        $db = Database::getInstance();
        $pdo = $db->getConnection();
    }
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $footer_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // 데이터베이스 연결 실패시 기본값 사용
}

// 기본값 설정
$footer_site_name = $footer_settings['site_name'] ?? '탄생';
$footer_company_desc = $footer_settings['footer_company_desc'] ?? '스마트팜 배지 제조 전문회사로서 최고 품질의 제품과 혁신적인 AI 기술을 통해 미래 농업을 선도합니다.';
$footer_address = $footer_settings['footer_address'] ?? '서울특별시 강남구 테헤란로 123';
$footer_phone = $footer_settings['contact_phone'] ?? '02-0000-0000';
$footer_fax = $footer_settings['footer_fax'] ?? '02-0000-0001';
$footer_email = $footer_settings['contact_email'] ?? 'info@tangsaeng.com';
$footer_social_facebook = $footer_settings['footer_social_facebook'] ?? '#';
$footer_social_instagram = $footer_settings['footer_social_instagram'] ?? '#';
$footer_social_youtube = $footer_settings['footer_social_youtube'] ?? '#';
$footer_social_blog = $footer_settings['footer_social_blog'] ?? '#';
$footer_business_hours_weekday = $footer_settings['footer_business_hours_weekday'] ?? '평일: 09:00 - 18:00';
$footer_business_hours_saturday = $footer_settings['footer_business_hours_saturday'] ?? '토요일: 09:00 - 13:00';
$footer_business_hours_holiday = $footer_settings['footer_business_hours_holiday'] ?? '일요일/공휴일: 휴무';
$footer_copyright = $footer_settings['footer_copyright'] ?? '© 2024 탄생(Tangsaeng). All rights reserved.';

// 푸터 메뉴 설정
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
    ['name' => '공지사항', 'url' => '/pages/support/notice.php'],
    ['name' => '관리자모드', 'url' => '/admin/', 'style' => 'color: #ff6b6b;']
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

// 데이터베이스에서 메뉴 불러오기
$footer_menu_products = json_decode($footer_settings['footer_menu_products'] ?? '', true) ?: $default_products;
$footer_menu_services = json_decode($footer_settings['footer_menu_services'] ?? '', true) ?: $default_services;
$footer_menu_company = json_decode($footer_settings['footer_menu_company'] ?? '', true) ?: $default_company;
$footer_menu_legal = json_decode($footer_settings['footer_menu_legal'] ?? '', true) ?: $default_legal;
?>
<footer class="main-footer">
    <div class="footer-content">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3><?= htmlspecialchars($footer_site_name) ?></h3>
                    <p><?= htmlspecialchars($footer_company_desc) ?></p>
                    <div class="social-links">
                        <a href="<?= htmlspecialchars($footer_social_facebook) ?>" aria-label="페이스북">📘</a>
                        <a href="<?= htmlspecialchars($footer_social_instagram) ?>" aria-label="인스타그램">📷</a>
                        <a href="<?= htmlspecialchars($footer_social_youtube) ?>" aria-label="유튜브">📺</a>
                        <a href="<?= htmlspecialchars($footer_social_blog) ?>" aria-label="블로그">📝</a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>제품</h4>
                    <ul>
                        <?php foreach ($footer_menu_products as $item): ?>
                        <li><a href="<?= htmlspecialchars($item['url']) ?>" <?= isset($item['style']) ? 'style="' . htmlspecialchars($item['style']) . '"' : '' ?>><?= htmlspecialchars($item['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>서비스</h4>
                    <ul>
                        <?php foreach ($footer_menu_services as $item): ?>
                        <li><a href="<?= htmlspecialchars($item['url']) ?>" <?= isset($item['style']) ? 'style="' . htmlspecialchars($item['style']) . '"' : '' ?>><?= htmlspecialchars($item['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>회사정보</h4>
                    <ul>
                        <?php foreach ($footer_menu_company as $item): ?>
                        <li><a href="<?= htmlspecialchars($item['url']) ?>" <?= isset($item['style']) ? 'style="' . htmlspecialchars($item['style']) . '"' : '' ?>><?= htmlspecialchars($item['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>연락처</h4>
                    <div class="contact-info">
                        <p>📍 <?= htmlspecialchars($footer_address) ?></p>
                        <p>📞 <?= htmlspecialchars($footer_phone) ?></p>
                        <p>📠 <?= htmlspecialchars($footer_fax) ?></p>
                        <p>✉️ <?= htmlspecialchars($footer_email) ?></p>
                    </div>
                    
                    <div class="business-hours">
                        <h5>운영시간</h5>
                        <p><?= htmlspecialchars($footer_business_hours_weekday) ?></p>
                        <p><?= htmlspecialchars($footer_business_hours_saturday) ?></p>
                        <p><?= htmlspecialchars($footer_business_hours_holiday) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-content">
                <p><?= htmlspecialchars($footer_copyright) ?></p>
                <div class="footer-links">
                    <?php foreach ($footer_menu_legal as $item): ?>
                    <a href="<?= htmlspecialchars($item['url']) ?>" <?= isset($item['style']) ? 'style="' . htmlspecialchars($item['style']) . '"' : '' ?>><?= htmlspecialchars($item['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Scroll to Top Button -->
<button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
    <span>↑</span>
</button>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p>로딩 중...</p>
    </div>
</div>