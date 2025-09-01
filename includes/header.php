<?php
// 데이터베이스 연결을 선택적으로 처리
if (!isset($currentUser)) {
    $currentUser = null;
    if (!isset($auth)) {
        try {
            require_once __DIR__ . '/../classes/Auth.php';
            $auth = Auth::getInstance();
            $currentUser = $auth->getCurrentUser();
        } catch (Exception $e) {
            // 데이터베이스 연결 실패시 계속 진행
            $currentUser = null;
        }
    } else {
        try {
            $currentUser = $auth->getCurrentUser();
        } catch (Exception $e) {
            $currentUser = null;
        }
    }
}

// 사이트 설정 불러오기
$site_settings = [];
try {
    require_once __DIR__ . '/../classes/Database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $site_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // 데이터베이스 연결 실패시 기본값 사용
}

// 기본값 설정
$header_phone = $site_settings['contact_phone'] ?? '02-0000-0000';
$header_email = $site_settings['contact_email'] ?? 'info@tangsaeng.com';
$site_name = $site_settings['site_name'] ?? '탄생';
$site_logo = $site_settings['site_logo'] ?? '/assets/images/company/logo.png';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<header class="main-header">
    <div class="header-top">
        <div class="container">
            <div class="header-info">
                <span>📞 <?= htmlspecialchars($header_phone) ?></span>
                <span>✉️ <?= htmlspecialchars($header_email) ?></span>
            </div>
            <div class="header-auth">
                <?php if ($currentUser): ?>
                    <span>안녕하세요, <?= htmlspecialchars($currentUser['name']) ?>님</span>
                    <a href="/pages/auth/profile.php">내 정보</a>
                    <?php if ($currentUser['plant_analysis_permission']): ?>
                        <a href="/pages/plant_analysis/" class="plant-analysis-link">식물분석</a>
                    <?php endif; ?>
                    <?php if ($currentUser['user_level'] == 9): ?>
                        <a href="/admin/" class="admin-link">관리자</a>
                    <?php endif; ?>
                    <a href="/pages/auth/logout.php">로그아웃</a>
                <?php else: ?>
                    <a href="/pages/auth/login.php">로그인</a>
                    <a href="/pages/auth/register.php">회원가입</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="header-main">
        <div class="container">
            <div class="header-brand">
                <a href="/">
                    <img src="<?= htmlspecialchars($site_logo) ?>" alt="<?= htmlspecialchars($site_name) ?>" class="logo" onerror="this.src='/assets/images/company/logo.png'">
                    <span class="brand-text"><?= htmlspecialchars($site_name) ?></span>
                </a>
            </div>
            
            <nav class="main-navigation">
                <ul class="nav-menu" id="navMenu">
                    <li class="<?= $currentPage === 'index' ? 'active' : '' ?>">
                        <a href="/">홈</a>
                    </li>
                    <li class="dropdown <?= in_array($currentPage, ['about', 'history', 'team']) ? 'active' : '' ?>">
                        <a href="/pages/company/about.php">기업소개</a>
                        <ul class="dropdown-menu">
                            <li><a href="/pages/company/about.php">회사소개</a></li>
                            <li><a href="/pages/company/history.php">연혁</a></li>
                            <li><a href="/pages/company/team.php">팀소개</a></li>
                        </ul>
                    </li>
                    <li class="dropdown <?= in_array($currentPage, ['media', 'technology', 'usage']) ? 'active' : '' ?>">
                        <a href="/pages/products/media.php">배지설명</a>
                        <ul class="dropdown-menu">
                            <li><a href="/pages/products/media.php">배지소개</a></li>
                            <li><a href="/pages/products/technology.php">기술정보</a></li>
                            <li><a href="/pages/products/usage.php">사용법</a></li>
                        </ul>
                    </li>
                    <li class="<?= strpos($_SERVER['REQUEST_URI'], '/store/') !== false ? 'active' : '' ?>">
                        <a href="/pages/store/">스토어</a>
                    </li>
                    <li class="<?= strpos($_SERVER['REQUEST_URI'], '/board/') !== false ? 'active' : '' ?>">
                        <a href="/pages/board/">게시판</a>
                    </li>
                    <li class="dropdown <?= in_array($currentPage, ['faq', 'contact', 'notice']) ? 'active' : '' ?>">
                        <a href="/pages/support/faq.php">고객지원</a>
                        <ul class="dropdown-menu">
                            <li><a href="/pages/support/faq.php">FAQ</a></li>
                            <li><a href="/pages/support/contact.php">문의하기</a></li>
                            <li><a href="/pages/support/notice.php">공지사항</a></li>
                        </ul>
                    </li>
                    <li class="<?= strpos($_SERVER['REQUEST_URI'], '/plant_analysis/') !== false ? 'active' : '' ?>">
                        <a href="/pages/plant_analysis/">식물분석</a>
                    </li>
                </ul>
            </nav>
            
            <div class="header-actions">
                <?php if ($currentUser): ?>
                <a href="/pages/store/cart.php" class="cart-button">
                    <span class="cart-icon">🛒</span>
                    <span class="cart-count" id="cartCount">0</span>
                </a>
                <?php endif; ?>
                
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </div>
</header>