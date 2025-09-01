<?php
// 데이터베이스 연결을 선택적으로 처리
$currentUser = null;
$dbConnected = false;

try {
    require_once __DIR__ . '/../../classes/Auth.php';
    $auth = Auth::getInstance();
    
    // 로그인 확인
    if (!$auth->isLoggedIn()) {
        header('Location: /pages/auth/login.php');
        exit;
    }
    
    $currentUser = $auth->getCurrentUser();
    $db = Database::getInstance();
    $dbConnected = true;
    
} catch (Exception $e) {
    // 데이터베이스 연결 실패시 로그인 페이지로 리다이렉트
    error_log("Database connection failed: " . $e->getMessage());
    header('Location: /pages/auth/login.php');
    exit;
}

$message = '';
$messageType = '';

// 프로필 업데이트 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $dbConnected) {
    try {
        $name = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $bio = $_POST['bio'] ?? '';
        
        // 간단한 유효성 검사
        if (empty($name)) {
            throw new Exception('이름은 필수 항목입니다.');
        }
        
        // 데이터베이스 업데이트
        $db->update('users', [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'bio' => $bio
        ], $currentUser['id']);
        
        // 세션 업데이트
        $currentUser = $auth->getCurrentUser(); // 새로운 정보로 다시 로드
        
        $message = '프로필이 성공적으로 업데이트되었습니다.';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>내 정보 - 탄생</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <main class="profile-main">
        <div class="container">
            <div class="page-header">
                <h1>👤 내 정보</h1>
                <p>회원 정보를 관리하고 계정 설정을 변경하세요</p>
            </div>

            <div class="profile-content">
                <div class="profile-sidebar">
                    <div class="profile-card">
                        <div class="profile-avatar">
                            <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                        </div>
                        <h3><?= htmlspecialchars($currentUser['name']) ?></h3>
                        <p class="profile-email"><?= htmlspecialchars($currentUser['email']) ?></p>
                        <div class="profile-status">
                            <?php if ($currentUser['plant_analysis_permission']): ?>
                                <span class="status-badge active">🌱 식물분석 권한</span>
                            <?php else: ?>
                                <span class="status-badge inactive">식물분석 권한 없음</span>
                            <?php endif; ?>
                            <?php if ($currentUser['user_level'] == 9): ?>
                                <span class="status-badge admin">👑 관리자</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <nav class="profile-nav">
                        <a href="#profile-info" class="nav-link active" onclick="showTab('profile-info', this)">
                            <span class="nav-icon">📝</span>
                            <span>기본 정보</span>
                        </a>
                        <a href="#security" class="nav-link" onclick="showTab('security', this)">
                            <span class="nav-icon">🔒</span>
                            <span>보안 설정</span>
                        </a>
                        <a href="#orders" class="nav-link" onclick="showTab('orders', this)">
                            <span class="nav-icon">📦</span>
                            <span>주문 내역</span>
                        </a>
                        <a href="#plant-analysis" class="nav-link" onclick="showTab('plant-analysis', this)">
                            <span class="nav-icon">🌱</span>
                            <span>식물분석</span>
                        </a>
                    </nav>
                </div>

                <div class="profile-main-content">
                    <?php if (!empty($message)): ?>
                        <div class="message <?= $messageType ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Profile Info Tab -->
                    <div id="profile-info" class="tab-content active">
                        <h2>기본 정보</h2>
                        <form method="POST" class="profile-form">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">이름 *</label>
                                    <input type="text" id="name" name="name" required 
                                           value="<?= htmlspecialchars($currentUser['name']) ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">이메일</label>
                                    <input type="email" id="email" value="<?= htmlspecialchars($currentUser['email']) ?>" disabled>
                                    <small>이메일은 변경할 수 없습니다.</small>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="phone">연락처</label>
                                    <input type="tel" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">주소</label>
                                    <input type="text" id="address" name="address" 
                                           value="<?= htmlspecialchars($currentUser['address'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="bio">자기소개</label>
                                <textarea id="bio" name="bio" rows="4" 
                                          placeholder="간단한 자기소개를 작성해보세요..."><?= htmlspecialchars($currentUser['bio'] ?? '') ?></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">정보 업데이트</button>
                                <button type="reset" class="btn btn-outline">취소</button>
                            </div>
                        </form>
                    </div>

                    <!-- Security Tab -->
                    <div id="security" class="tab-content">
                        <h2>보안 설정</h2>
                        <div class="security-section">
                            <h3>비밀번호 변경</h3>
                            <form class="security-form">
                                <div class="form-group">
                                    <label for="current_password">현재 비밀번호</label>
                                    <input type="password" id="current_password" name="current_password">
                                </div>
                                <div class="form-group">
                                    <label for="new_password">새 비밀번호</label>
                                    <input type="password" id="new_password" name="new_password">
                                    <small>8자 이상, 영문과 숫자 조합</small>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">새 비밀번호 확인</label>
                                    <input type="password" id="confirm_password" name="confirm_password">
                                </div>
                                <button type="submit" class="btn btn-primary">비밀번호 변경</button>
                            </form>
                        </div>

                        <div class="security-section">
                            <h3>로그인 기록</h3>
                            <div class="login-history">
                                <div class="history-item">
                                    <div class="history-info">
                                        <span class="device">🖥️ Windows Chrome</span>
                                        <span class="time">2024-01-15 14:30</span>
                                    </div>
                                    <span class="location">서울, 한국</span>
                                </div>
                                <div class="history-item">
                                    <div class="history-info">
                                        <span class="device">📱 Mobile Safari</span>
                                        <span class="time">2024-01-14 09:15</span>
                                    </div>
                                    <span class="location">서울, 한국</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Tab -->
                    <div id="orders" class="tab-content">
                        <h2>주문 내역</h2>
                        <div class="orders-list">
                            <div class="order-item">
                                <div class="order-header">
                                    <span class="order-number">주문번호: #2024011501</span>
                                    <span class="order-date">2024.01.15</span>
                                    <span class="order-status completed">배송완료</span>
                                </div>
                                <div class="order-products">
                                    <div class="product-item">
                                        <span>탄생 프리미엄 배지 x 2</span>
                                        <span>50,000원</span>
                                    </div>
                                </div>
                                <div class="order-actions">
                                    <button class="btn btn-outline btn-sm">상세보기</button>
                                    <button class="btn btn-outline btn-sm">재주문</button>
                                </div>
                            </div>

                            <div class="order-item">
                                <div class="order-header">
                                    <span class="order-number">주문번호: #2024011201</span>
                                    <span class="order-date">2024.01.12</span>
                                    <span class="order-status shipping">배송중</span>
                                </div>
                                <div class="order-products">
                                    <div class="product-item">
                                        <span>토마토 전용 양액 x 1</span>
                                        <span>28,000원</span>
                                    </div>
                                </div>
                                <div class="order-actions">
                                    <button class="btn btn-outline btn-sm">배송조회</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Plant Analysis Tab -->
                    <div id="plant-analysis" class="tab-content">
                        <h2>식물분석 서비스</h2>
                        
                        <?php if ($currentUser['plant_analysis_permission']): ?>
                            <div class="analysis-status">
                                <div class="status-card active">
                                    <h3>✅ 식물분석 권한 보유</h3>
                                    <p>식물분석 서비스를 자유롭게 이용하실 수 있습니다.</p>
                                    <a href="/pages/plant_analysis/" class="btn btn-primary">식물분석 바로가기</a>
                                </div>
                                
                                <div class="analysis-stats">
                                    <h3>이용 현황</h3>
                                    <div class="stats-grid">
                                        <div class="stat-item">
                                            <span class="stat-number">15</span>
                                            <span class="stat-label">분석 횟수</span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-number">32</span>
                                            <span class="stat-label">촬영 이미지</span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-number">12일</span>
                                            <span class="stat-label">마지막 이용</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="analysis-status">
                                <div class="status-card inactive">
                                    <h3>❌ 식물분석 권한 없음</h3>
                                    <p>식물분석 서비스를 이용하려면 권한 신청이 필요합니다.</p>
                                    <a href="/pages/support/contact.php" class="btn btn-primary">권한 신청하기</a>
                                </div>
                                
                                <div class="permission-info">
                                    <h3>권한 신청 방법</h3>
                                    <ol>
                                        <li>문의하기를 통해 식물분석 권한 신청서 작성</li>
                                        <li>농장 정보 및 사용 목적 기재</li>
                                        <li>관리자 검토 후 2-3일 내 승인 처리</li>
                                        <li>승인 완료 후 서비스 이용 가능</li>
                                    </ol>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../../includes/footer.php'; ?>

    <script src="/assets/js/main.js"></script>
    <script>
        function showTab(tabId, element) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected tab and mark nav link as active
            document.getElementById(tabId).classList.add('active');
            element.classList.add('active');
        }
        
        // 보안 폼 제출 처리
        document.querySelector('.security-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('새 비밀번호가 일치하지 않습니다.');
                return;
            }
            
            if (newPassword.length < 8) {
                alert('비밀번호는 8자 이상이어야 합니다.');
                return;
            }
            
            // 실제 구현시 AJAX로 비밀번호 변경
            alert('비밀번호 변경 기능은 준비 중입니다.');
        });
    </script>
</body>
</html>

<style>
.profile-main {
    padding: 2rem 0;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
    padding: 3rem 0;
    background: linear-gradient(135deg, #E8F5E8 0%, #C8E6C9 100%);
    border-radius: 12px;
}

.page-header h1 {
    font-size: 2.5rem;
    color: #2E7D32;
    margin-bottom: 1rem;
}

.profile-content {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 3rem;
}

.profile-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    text-align: center;
    margin-bottom: 2rem;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    background: #4CAF50;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
    margin: 0 auto 1rem;
}

.profile-card h3 {
    color: #2E7D32;
    margin-bottom: 0.5rem;
}

.profile-email {
    color: #666;
    margin-bottom: 1rem;
}

.status-badge {
    display: block;
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    margin-bottom: 0.5rem;
}

.status-badge.active {
    background: #E8F5E8;
    color: #2E7D32;
}

.status-badge.inactive {
    background: #FFEBEE;
    color: #C62828;
}

.status-badge.admin {
    background: #FFF3E0;
    color: #FF6F00;
}

.profile-nav {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem;
    text-decoration: none;
    color: #333;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.3s ease;
}

.nav-link:last-child {
    border-bottom: none;
}

.nav-link:hover {
    background: #f8f9fa;
}

.nav-link.active {
    background: #E8F5E8;
    color: #2E7D32;
    border-left: 4px solid #4CAF50;
}

.nav-icon {
    margin-right: 0.8rem;
    font-size: 1.2rem;
}

.profile-main-content {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.message {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    text-align: center;
}

.message.success {
    background: #E8F5E8;
    color: #2E7D32;
    border: 1px solid #4CAF50;
}

.message.error {
    background: #FFEBEE;
    color: #C62828;
    border: 1px solid #F44336;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.tab-content h2 {
    color: #2E7D32;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f0f0f0;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #2E7D32;
    font-weight: 600;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.8rem;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #4CAF50;
}

.form-group input:disabled {
    background: #f8f9fa;
    color: #666;
}

.form-group small {
    color: #666;
    font-size: 0.9rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.security-section {
    margin-bottom: 3rem;
}

.security-section h3 {
    color: #2E7D32;
    margin-bottom: 1rem;
}

.login-history {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
}

.history-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.8rem 0;
    border-bottom: 1px solid #e0e0e0;
}

.history-item:last-child {
    border-bottom: none;
}

.history-info {
    display: flex;
    flex-direction: column;
}

.device {
    font-weight: 600;
    margin-bottom: 0.2rem;
}

.time {
    color: #666;
    font-size: 0.9rem;
}

.location {
    color: #4CAF50;
    font-size: 0.9rem;
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.order-item {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1.5rem;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.order-number {
    font-weight: 600;
    color: #2E7D32;
}

.order-status {
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
}

.order-status.completed {
    background: #E8F5E8;
    color: #2E7D32;
}

.order-status.shipping {
    background: #E3F2FD;
    color: #1976D2;
}

.product-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.order-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.analysis-status .status-card {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 2rem;
}

.analysis-status .status-card.active {
    background: #E8F5E8;
    border: 2px solid #4CAF50;
}

.analysis-status .status-card.inactive {
    background: #FFEBEE;
    border: 2px solid #F44336;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 1rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: white;
    border-radius: 8px;
}

.stat-number {
    display: block;
    font-size: 1.8rem;
    font-weight: bold;
    color: #4CAF50;
    margin-bottom: 0.3rem;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
}

.permission-info {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 8px;
}

.permission-info h3 {
    color: #2E7D32;
    margin-bottom: 1rem;
}

.permission-info ol {
    color: #333;
    padding-left: 1.5rem;
}

.permission-info li {
    margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
    .profile-content {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions,
    .order-actions {
        flex-direction: column;
    }
}
</style>