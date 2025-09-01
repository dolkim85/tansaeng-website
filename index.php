<?php
// 데이터베이스 연결을 선택적으로 처리
$currentUser = null;
$dbConnected = false;
$featuredProducts = [];
$productCategories = [];
$latestNews = [];

try {
    require_once __DIR__ . '/classes/Auth.php';
    require_once __DIR__ . '/classes/Database.php';
    $auth = Auth::getInstance();
    $currentUser = $auth->getCurrentUser();
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $dbConnected = true;
    
    // 관리자에서 등록한 추천 상품 가져오기 (최대 3개)
    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN product_categories c ON p.category_id = c.id 
        WHERE p.is_featured = 1 AND p.status = 'active' 
        ORDER BY p.created_at DESC 
        LIMIT 3
    ");
    $featuredProducts = $stmt->fetchAll();
    
    // 카테고리 정보 가져오기
    $stmt = $pdo->query("
        SELECT * FROM product_categories 
        WHERE status = 'active' 
        ORDER BY name 
        LIMIT 3
    ");
    $productCategories = $stmt->fetchAll();
    
    // 최신 공지사항/뉴스 가져오기
    $stmt = $pdo->query("
        SELECT id, title, content, created_at 
        FROM board_posts 
        WHERE status = 'active' AND (is_notice = 1 OR post_type = 'general')
        ORDER BY is_notice DESC, created_at DESC 
        LIMIT 3
    ");
    $latestNews = $stmt->fetchAll();
    
} catch (Exception $e) {
    // 데이터베이스 연결 실패시 계속 진행
    error_log("Database connection failed: " . $e->getMessage());
}

// 사이트 설정값 불러오기
$site_settings = [];
if ($dbConnected) {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $site_settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        error_log("Failed to load site settings: " . $e->getMessage());
    }
}

// 기본값 설정
$defaults = [
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

// 설정값과 기본값 합치기
$settings = array_merge($defaults, $site_settings);

$heroSlides = [
    [
        'image' => $settings['hero_image_1'],
        'title' => $settings['hero_1_title'],
        'subtitle' => $settings['hero_1_subtitle'],
        'cta_text' => $settings['hero_1_cta_text'],
        'cta_link' => $settings['hero_1_cta_link']
    ],
    [
        'image' => $settings['hero_image_2'],
        'title' => $settings['hero_2_title'],
        'subtitle' => $settings['hero_2_subtitle'],
        'cta_text' => $settings['hero_2_cta_text'],
        'cta_link' => $settings['hero_2_cta_link']
    ],
    [
        'image' => $settings['hero_image_3'],
        'title' => $settings['hero_3_title'],
        'subtitle' => $settings['hero_3_subtitle'],
        'cta_text' => $settings['hero_3_cta_text'],
        'cta_link' => $settings['hero_3_cta_link']
    ]
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>탄생 - 스마트팜 배지 제조회사</title>
    <meta name="description" content="탄생은 최고 품질의 수경재배용 배지를 제조하는 스마트팜 전문 회사입니다. AI 식물분석 시스템과 함께하는 혁신적인 농업 솔루션을 경험하세요.">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/home.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-slider" id="heroSlider">
                <?php foreach ($heroSlides as $index => $slide): ?>
                <div class="hero-slide <?= $index === 0 ? 'active' : '' ?>">
                    <div class="hero-background" style="background-image: url('<?= $slide['image'] ?>')"></div>
                    <div class="hero-content">
                        <div class="container">
                            <h1><?= htmlspecialchars($slide['title']) ?></h1>
                            <p><?= htmlspecialchars($slide['subtitle']) ?></p>
                            <a href="<?= $slide['cta_link'] ?>" class="btn btn-primary btn-lg"><?= htmlspecialchars($slide['cta_text']) ?></a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="hero-controls">
                <button class="hero-prev" onclick="changeSlide(-1)">&#8249;</button>
                <button class="hero-next" onclick="changeSlide(1)">&#8250;</button>
            </div>
            <div class="hero-indicators">
                <?php foreach ($heroSlides as $index => $slide): ?>
                <span class="indicator <?= $index === 0 ? 'active' : '' ?>" onclick="currentSlide(<?= $index + 1 ?>)"></span>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Company Introduction -->
        <section class="intro-section">
            <div class="container">
                <?php
                // 회사 소개 정보 가져오기
                $company_intro = null;
                if ($dbConnected) {
                    try {
                        $stmt = $pdo->query("SELECT title, content FROM company_info ORDER BY id DESC LIMIT 1");
                        $company_intro = $stmt->fetch();
                    } catch (Exception $e) {
                        error_log("Failed to load company intro: " . $e->getMessage());
                    }
                }
                
                // 기본값 설정
                if (!$company_intro) {
                    $company_intro = [
                        'title' => '탄생과 함께하는 스마트팜',
                        'content' => '혁신적인 기술과 최고 품질의 배지로 미래 농업을 선도합니다'
                    ];
                }
                ?>
                <div class="section-header">
                    <h2><?= htmlspecialchars($company_intro['title']) ?></h2>
                    <div class="company-description">
                        <?= nl2br(htmlspecialchars(mb_substr($company_intro['content'], 0, 200))) ?><?= mb_strlen($company_intro['content']) > 200 ? '...' : '' ?>
                    </div>
                </div>
                
                <div class="intro-grid">
                    <div class="intro-item">
                        <div class="intro-icon">🌱</div>
                        <h3>잘자라 배지</h3>
                        <p>식물이 좋아하는 배지로 건강한 작물을 키워보세요</p>
                    </div>
                    <div class="intro-item">
                        <div class="intro-icon">🤖</div>
                        <h3>AI 식물분석</h3>
                        <p>첨단 AI 기술과 라즈베리파이 카메라를 활용한 실시간 식물 건강상태 분석 서비스</p>
                    </div>
                    <div class="intro-item">
                        <div class="intro-icon">📱</div>
                        <h3>스마트 모니터링</h3>
                        <p>온도, 습도, pH, EC 등 환경 데이터를 실시간으로 모니터링하고 관리</p>
                    </div>
                </div>
                
                <?php if (mb_strlen($company_intro['content']) > 200): ?>
                <div class="intro-more">
                    <a href="/pages/company/about.php" class="btn btn-outline">회사 소개 더보기</a>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Featured Products -->
        <section class="products-section">
            <div class="container">
                <div class="section-header">
                    <h2>주요 제품</h2>
                    <p>탄생의 프리미엄 배지와 농업용품을 만나보세요</p>
                </div>
                
                <div class="products-grid">
                    <?php if (!empty($featuredProducts)): ?>
                        <?php foreach ($featuredProducts as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?= !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : '/assets/images/products/placeholder.jpg' ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>" 
                                     loading="lazy"
                                     onerror="this.src='/assets/images/products/placeholder.jpg'">
                            </div>
                            <div class="product-info">
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <p><?= htmlspecialchars(mb_substr($product['description'] ?? '', 0, 60) . '...') ?></p>
                                <div class="product-price">
                                    <span class="price"><?= number_format($product['price']) ?>원</span>
                                </div>
                                <a href="/pages/store/products.php?category=<?= $product['category_id'] ?>" class="btn btn-outline">자세히 보기</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- 기본 샘플 상품 (데이터베이스 연결 실패시) -->
                        <div class="product-card">
                            <div class="product-image">
                                <img src="/assets/images/products/placeholder.jpg" alt="잘자라 배지" loading="lazy">
                            </div>
                            <div class="product-info">
                                <h3>잘자라 배지</h3>
                                <p>재생 섬유를 활용한 배지로 뛰어난 통기성과 보습성을 제공합니다</p>
                                <a href="/pages/store/" class="btn btn-outline">자세히 보기</a>
                            </div>
                        </div>
                        
                        <div class="product-card">
                            <div class="product-image">
                                <img src="/assets/images/products/placeholder.jpg" alt="하나로 배지" loading="lazy">
                            </div>
                            <div class="product-info">
                                <h3>하나로 배지</h3>
                                <p>우수한 배수성과 통기성으로 뿌리 건강을 최적화하는 전문 배지</p>
                                <a href="/pages/store/" class="btn btn-outline">자세히 보기</a>
                            </div>
                        </div>
                        
                        <div class="product-card">
                            <div class="product-image">
                                <img src="/assets/images/products/placeholder.jpg" alt="AI 카메라" loading="lazy">
                            </div>
                            <div class="product-info">
                                <h3>AI 카메라</h3>
                                <p>작물 관찰, 나만의 농가를 위한 식물박사</p>
                                <a href="/pages/store/" class="btn btn-outline">자세히 보기</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="section-cta">
                    <a href="/pages/store/" class="btn btn-primary btn-lg">전체 제품 보기</a>
                </div>
            </div>
        </section>

        <!-- Plant Analysis Section -->
        <section class="analysis-section">
            <div class="container">
                <div class="analysis-content">
                    <div class="analysis-text">
                        <h2><?= htmlspecialchars($settings['plant_analysis_title'] ?? 'AI 식물분석 서비스') ?></h2>
                        <p><?= htmlspecialchars($settings['plant_analysis_description'] ?? '라즈베리파이 카메라와 AI 기술을 활용하여 식물의 건강상태를 실시간으로 분석하고 관리할 수 있습니다.') ?></p>
                        
                        <ul class="feature-list">
                            <li>✓ 실시간 식물 촬영 및 모니터링</li>
                            <li>✓ AI 기반 병충해 및 영양상태 진단</li>
                            <li>✓ 환경 센서 데이터 통합 분석</li>
                            <li>✓ 맞춤형 관리 솔루션 제공</li>
                        </ul>
                        
                        <?php if ($currentUser && $currentUser['plant_analysis_permission']): ?>
                            <a href="/pages/plant_analysis/" class="btn btn-success btn-lg">분석 시작하기</a>
                        <?php elseif ($currentUser): ?>
                            <p class="permission-notice">식물분석 서비스는 권한 승인이 필요한 서비스입니다. 관리자에게 문의하세요.</p>
                            <a href="/pages/support/contact.php" class="btn btn-outline">권한 신청하기</a>
                        <?php else: ?>
                            <p class="login-notice">식물분석 서비스를 이용하려면 로그인이 필요합니다.</p>
                            <a href="/pages/auth/login.php" class="btn btn-primary">로그인</a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="analysis-visual">
                        <div class="analysis-demo">
                            <?php if (!empty($settings['plant_analysis_video']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $settings['plant_analysis_video'])): ?>
                                <video autoplay muted loop style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                                    <source src="<?= htmlspecialchars($settings['plant_analysis_video']) ?>" 
                                            type="video/<?= pathinfo($settings['plant_analysis_video'], PATHINFO_EXTENSION) ?>">
                                    브라우저가 비디오를 지원하지 않습니다.
                                </video>
                            <?php else: ?>
                                <img src="/assets/images/company/plant-analysis-demo.jpg" alt="식물분석 데모" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Company Video -->
        <section class="video-section">
            <div class="container">
                <div class="section-header">
                    <h2><?= htmlspecialchars($settings['company_intro_title'] ?? '탄생 소개 영상') ?></h2>
                    <p><?= htmlspecialchars($settings['company_intro_description'] ?? '우리의 기술과 비전을 영상으로 만나보세요') ?></p>
                </div>
                
                <div class="video-container">
                    <?php if (!empty($settings['company_intro_video']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $settings['company_intro_video'])): ?>
                        <video autoplay muted loop style="width: 100%; height: 400px; object-fit: cover; border-radius: 12px;">
                            <source src="<?= htmlspecialchars($settings['company_intro_video']) ?>" 
                                    type="video/<?= pathinfo($settings['company_intro_video'], PATHINFO_EXTENSION) ?>">
                            브라우저가 비디오를 지원하지 않습니다.
                        </video>
                    <?php else: ?>
                        <div class="video-placeholder">
                            <img src="/assets/images/company/video-thumbnail.jpg" alt="회사 소개 영상" loading="lazy" style="width: 100%; height: 400px; object-fit: cover; border-radius: 12px;">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- News & Updates -->
        <section class="news-section">
            <div class="container">
                <div class="section-header">
                    <h2>새소식 및 업데이트</h2>
                    <p>탄생의 최신 소식을 확인하세요</p>
                </div>
                
                <div class="news-grid">
                    <?php if (!empty($latestNews)): ?>
                        <?php foreach ($latestNews as $news): ?>
                        <article class="news-item">
                            <div class="news-date"><?= date('Y.m.d', strtotime($news['created_at'])) ?></div>
                            <h3><?= htmlspecialchars($news['title']) ?></h3>
                            <p><?= htmlspecialchars(mb_substr(strip_tags($news['content']), 0, 80) . '...') ?></p>
                            <a href="/pages/support/notice_detail.php?id=<?= $news['id'] ?>" class="read-more">자세히 보기</a>
                        </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- 기본 샘플 뉴스 (데이터베이스 연결 실패시) -->
                        <article class="news-item">
                            <div class="news-date">2024.12.15</div>
                            <h3>새로운 AI 식물분석 시스템 출시</h3>
                            <p>더욱 정확하고 빠른 식물 건강 분석이 가능한 새로운 시스템이 출시되었습니다.</p>
                            <a href="/pages/support/notice.php" class="read-more">자세히 보기</a>
                        </article>
                        
                        <article class="news-item">
                            <div class="news-date">2024.12.10</div>
                            <h3>친환경 배지 제품 라인업 확장</h3>
                            <p>다양한 작물에 최적화된 새로운 친환경 배지 제품들이 추가되었습니다.</p>
                            <a href="/pages/support/notice.php" class="read-more">자세히 보기</a>
                        </article>
                        
                        <article class="news-item">
                            <div class="news-date">2024.12.05</div>
                            <h3>스마트팜 기술 세미나 개최</h3>
                            <p>전문가와 함께하는 스마트팜 기술 세미나가 개최됩니다.</p>
                            <a href="/pages/support/notice.php" class="read-more">자세히 보기</a>
                        </article>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/home.js"></script>
</body>
</html>