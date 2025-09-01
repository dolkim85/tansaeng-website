<?php
// Initialize session and auth before any output
$base_path = __DIR__ . '/..';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Database.php';

$auth = Auth::getInstance();
$auth->requireAdmin();

$currentUser = $auth->getCurrentUser();

// Get comprehensive dashboard stats
try {
    $pdo = Database::getInstance()->getConnection();
    
    // User stats
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_level < 9");
    $total_users = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_level < 9 AND is_active = 1");
    $active_users = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE plant_analysis_permission = 1");
    $plant_analysis_users = $stmt->fetchColumn();
    
    // Post stats
    $stmt = $pdo->query("SELECT COUNT(*) FROM board_posts WHERE status = 'active'");
    $total_posts = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM board_posts WHERE status = 'active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $recent_posts = $stmt->fetchColumn();
    
    // Order stats (if table exists)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
        $total_orders = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'");
        $pending_orders = $stmt->fetchColumn();
    } catch (Exception $e) {
        $total_orders = 0;
        $pending_orders = 0;
    }
    
    // Product stats (if table exists)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
        $total_products = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM products");
        $active_products = $stmt->fetchColumn();
    } catch (Exception $e) {
        $total_products = 0;
        $active_products = 0;
    }
    
    // Plant analysis stats (if table exists)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM plant_analysis_results");
        $plant_images = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM plant_analysis_results WHERE status = 'completed'");
        $completed_analyses = $stmt->fetchColumn();
    } catch (Exception $e) {
        $plant_images = 0;
        $completed_analyses = 0;
    }
    
    // Recent data queries
    try {
        // Recent user registrations
        $stmt = $pdo->query("SELECT name, email, created_at FROM users WHERE user_level < 9 ORDER BY created_at DESC LIMIT 5");
        $recent_registrations = $stmt->fetchAll();
        
        // Recent orders (if table exists)
        try {
            $stmt = $pdo->query("SELECT total_amount, customer_name, order_status as status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
            $recent_orders = $stmt->fetchAll();
        } catch (Exception $e) {
            $recent_orders = [];
        }
        
        // Recent plant analyses (if table exists)
        try {
            $stmt = $pdo->query("SELECT par.plant_species as species, par.health_status, par.processed_at as analyzed_at, u.name as user_name 
                               FROM plant_analysis_results par 
                               LEFT JOIN users u ON par.user_id = u.id 
                               ORDER BY par.processed_at DESC LIMIT 5");
            $recent_plant_analyses = $stmt->fetchAll();
        } catch (Exception $e) {
            $recent_plant_analyses = [];
        }
        
    } catch (Exception $e) {
        $recent_registrations = [];
        $recent_orders = [];
        $recent_plant_analyses = [];
    }
    
    $stats = [
        'total_users' => $total_users,
        'active_users' => $active_users,
        'plant_analysis_users' => $plant_analysis_users,
        'total_posts' => $total_posts,
        'recent_posts' => $recent_posts,
        'total_orders' => $total_orders,
        'pending_orders' => $pending_orders,
        'total_products' => $total_products,
        'active_products' => $active_products,
        'plant_images' => $plant_images,
        'completed_analyses' => $completed_analyses,
        'recent_registrations' => $recent_registrations,
        'recent_orders' => $recent_orders,
        'recent_plant_analyses' => $recent_plant_analyses
    ];
    
} catch (Exception $e) {
    $stats = [
        'total_users' => 0,
        'active_users' => 0,
        'plant_analysis_users' => 0,
        'total_posts' => 0,
        'recent_posts' => 0,
        'total_orders' => 0,
        'pending_orders' => 0,
        'total_products' => 0,
        'active_products' => 0,
        'plant_images' => 0,
        'completed_analyses' => 0,
        'recent_registrations' => [],
        'recent_orders' => [],
        'recent_plant_analyses' => []
    ];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 대시보드 - 탄생</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-content">
                <div class="page-header">
                    <h1>관리자 대시보드</h1>
                    <p>탄생 스마트팜 시스템 현황을 한눈에 확인하세요</p>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-info">
                            <h3><?= number_format($stats['total_users']) ?>명</h3>
                            <p>전체 사용자</p>
                            <small>활성 사용자: <?= number_format($stats['active_users']) ?>명</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🌱</div>
                        <div class="stat-info">
                            <h3><?= number_format($stats['plant_analysis_users']) ?>명</h3>
                            <p>식물분석 권한자</p>
                            <small>전체 사용자 중 <?= round(($stats['plant_analysis_users'] / max($stats['total_users'], 1)) * 100, 1) ?>%</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🛒</div>
                        <div class="stat-info">
                            <h3><?= number_format($stats['total_orders']) ?>건</h3>
                            <p>전체 주문</p>
                            <small>대기중: <?= number_format($stats['pending_orders']) ?>건</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-info">
                            <h3><?= number_format($stats['total_products']) ?>개</h3>
                            <p>등록 상품</p>
                            <small>판매중: <?= number_format($stats['active_products']) ?>개</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📸</div>
                        <div class="stat-info">
                            <h3><?= number_format($stats['plant_images']) ?>장</h3>
                            <p>식물 이미지</p>
                            <small>분석완료: <?= number_format($stats['completed_analyses']) ?>건</small>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="dashboard-grid">
                    <!-- Recent User Registrations -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>최근 회원가입</h3>
                            <a href="/admin/users/" class="btn btn-outline btn-sm">전체보기</a>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($stats['recent_registrations'])): ?>
                                <div class="recent-list">
                                    <?php foreach ($stats['recent_registrations'] as $user): ?>
                                    <div class="recent-item">
                                        <div class="recent-info">
                                            <strong><?= htmlspecialchars($user['name']) ?></strong>
                                            <small><?= htmlspecialchars($user['email']) ?></small>
                                        </div>
                                        <div class="recent-time">
                                            <?= date('m/d H:i', strtotime($user['created_at'])) ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-data">최근 가입한 사용자가 없습니다.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>최근 주문</h3>
                            <a href="/admin/orders/" class="btn btn-outline btn-sm">전체보기</a>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($stats['recent_orders'])): ?>
                                <div class="recent-list">
                                    <?php foreach ($stats['recent_orders'] as $order): ?>
                                    <div class="recent-item">
                                        <div class="recent-info">
                                            <strong><?= number_format($order['total_amount']) ?>원</strong>
                                            <small><?= htmlspecialchars($order['customer_name'] ?? '고객') ?></small>
                                        </div>
                                        <div class="recent-meta">
                                            <span class="order-status status-<?= $order['status'] ?>"><?= $order['status'] ?></span>
                                            <small><?= date('m/d H:i', strtotime($order['created_at'])) ?></small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-data">최근 주문이 없습니다.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Plant Analysis -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>최근 식물분석</h3>
                            <a href="/admin/plant_analysis/" class="btn btn-outline btn-sm">전체보기</a>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($stats['recent_plant_analyses'])): ?>
                                <div class="recent-list">
                                    <?php foreach ($stats['recent_plant_analyses'] as $analysis): ?>
                                    <div class="recent-item">
                                        <div class="recent-info">
                                            <strong><?= htmlspecialchars($analysis['species'] ?? '미분류') ?></strong>
                                            <small><?= htmlspecialchars($analysis['user_name']) ?></small>
                                        </div>
                                        <div class="recent-meta">
                                            <span class="health-status status-<?= $analysis['health_status'] ?>"><?= $analysis['health_status'] ?></span>
                                            <small><?= date('m/d H:i', strtotime($analysis['analyzed_at'])) ?></small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-data">최근 분석 결과가 없습니다.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>빠른 작업</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="/admin/users/" class="quick-action">
                                <div class="action-icon">👥</div>
                                <span>사용자 관리</span>
                            </a>
                            <a href="/admin/users/permissions.php" class="quick-action">
                                <div class="action-icon">🔑</div>
                                <span>권한 관리</span>
                            </a>
                            <a href="/admin/products/" class="quick-action">
                                <div class="action-icon">📦</div>
                                <span>상품 관리</span>
                            </a>
                            <a href="/admin/orders/" class="quick-action">
                                <div class="action-icon">🛒</div>
                                <span>주문 관리</span>
                            </a>
                            <a href="/admin/plant_analysis/" class="quick-action">
                                <div class="action-icon">🌱</div>
                                <span>식물분석 관리</span>
                            </a>
                            <a href="/admin/board/" class="quick-action">
                                <div class="action-icon">📝</div>
                                <span>게시판 관리</span>
                            </a>
                            <a href="/admin/settings/" class="quick-action">
                                <div class="action-icon">⚙️</div>
                                <span>시스템 설정</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/admin.js"></script>
</body>
</html>