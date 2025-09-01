<?php
require_once __DIR__ . '/classes/Database.php';

echo "<h1>🌱 탄생 스마트팜 웹사이트 서버 상태</h1><hr>";

// 서버 정보
echo "<h2>📡 서버 정보</h2>";
echo "<ul>";
echo "<li><strong>웹서버:</strong> PHP " . phpversion() . " 개발서버</li>";
echo "<li><strong>주소:</strong> <a href='http://localhost:8080'>http://localhost:8080</a></li>";
echo "<li><strong>시간:</strong> " . date('Y-m-d H:i:s') . "</li>";
echo "</ul>";

// 데이터베이스 상태
echo "<h2>🗄️ 데이터베이스 상태</h2>";
try {
    $pdo = Database::getInstance()->getConnection();
    echo "<ul>";
    echo "<li><strong>MySQL 연결:</strong> ✅ 성공</li>";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM board_posts WHERE status = 'active'");
    $post_count = $stmt->fetchColumn();
    echo "<li><strong>게시글 수:</strong> {$post_count}개</li>";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM board_replies WHERE status = 'active'");
    $reply_count = $stmt->fetchColumn();
    echo "<li><strong>답글 수:</strong> {$reply_count}개</li>";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_level = 9");
    $admin_count = $stmt->fetchColumn();
    echo "<li><strong>관리자 계정:</strong> {$admin_count}개</li>";
    
    echo "</ul>";
} catch (Exception $e) {
    echo "<ul><li><strong>MySQL 연결:</strong> ❌ 실패 - " . $e->getMessage() . "</li></ul>";
}

echo "<hr>";

// 주요 페이지 링크
echo "<h2>🔗 주요 페이지 링크</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;'>";

// 사용자 페이지
echo "<div style='border: 1px solid #ddd; padding: 20px; border-radius: 8px;'>";
echo "<h3>👥 사용자 페이지</h3>";
echo "<ul>";
echo "<li><a href='http://localhost:8080' target='_blank'>🏠 메인 홈페이지</a></li>";
echo "<li><a href='http://localhost:8080/pages/board/' target='_blank'>📝 게시판</a></li>";
echo "<li><a href='http://localhost:8080/pages/board/write.php' target='_blank'>✏️ 글쓰기</a></li>";
echo "<li><a href='http://localhost:8080/pages/board/view.php?id=1' target='_blank'>👁️ 게시글 보기 (답글 포함)</a></li>";
echo "<li><a href='http://localhost:8080/pages/auth/login.php' target='_blank'>🔐 사용자 로그인</a></li>";
echo "</ul>";
echo "</div>";

// 관리자 페이지
echo "<div style='border: 1px solid #ddd; padding: 20px; border-radius: 8px;'>";
echo "<h3>⚙️ 관리자 페이지</h3>";
echo "<ul>";
echo "<li><a href='http://localhost:8080/admin/login.php' target='_blank'>🔑 관리자 로그인</a></li>";
echo "<li><a href='http://localhost:8080/admin/' target='_blank'>📊 관리자 대시보드</a></li>";
echo "<li><a href='http://localhost:8080/admin/board/' target='_blank'>📝 게시글 관리</a></li>";
echo "<li><a href='http://localhost:8080/admin/users/' target='_blank'>👥 사용자 관리</a></li>";
echo "<li><a href='http://localhost:8080/admin/products/' target='_blank'>📦 상품 관리</a></li>";
echo "<li><a href='http://localhost:8080/admin/orders/' target='_blank'>🛒 주문 관리</a></li>";
echo "<li><a href='http://localhost:8080/admin/plant_analysis/' target='_blank'>🌱 식물분석 관리</a></li>";
echo "</ul>";
echo "</div>";

echo "</div>";

// 계정 정보
echo "<h2>🔐 계정 정보</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>관리자 계정</h3>";
echo "<ul>";
echo "<li><strong>이메일:</strong> admin@tangsaeng.com</li>";
echo "<li><strong>비밀번호:</strong> admin2025</li>";
echo "<li><strong>권한 레벨:</strong> 9 (최고 관리자)</li>";
echo "</ul>";
echo "</div>";

// 기능 상태
echo "<h2>⚡ 구현된 주요 기능</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;'>";

$features = [
    "게시판 시스템" => ["글 작성/수정/삭제", "목록 표시/검색", "첨부파일 업로드"],
    "답글 시스템" => ["공개 답글", "비공개 답글", "권한별 열람 제어"],
    "관리자 기능" => ["게시글 관리", "사용자 관리", "상품/주문 관리"],
    "식물분석" => ["AI 분석 현황", "센서 데이터", "권한 관리"]
];

foreach ($features as $category => $items) {
    echo "<div style='border-left: 4px solid #28a745; padding: 15px; background: #f8fff8;'>";
    echo "<h4>$category</h4><ul>";
    foreach ($items as $item) {
        echo "<li>✅ $item</li>";
    }
    echo "</ul></div>";
}

echo "</div>";

echo "<hr>";
echo "<div style='text-align: center; padding: 20px; background: #e8f5e8; border-radius: 8px;'>";
echo "<h3>🎉 시스템이 정상적으로 실행 중입니다!</h3>";
echo "<p>웹브라우저에서 <strong><a href='http://localhost:8080'>http://localhost:8080</a></strong>으로 접속하세요.</p>";
echo "</div>";

// 스타일 추가
echo "<style>";
echo "body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 40px; background: #f5f5f5; }";
echo "h1 { color: #2c5530; }";
echo "h2 { color: #4a7c54; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px; }";
echo "h3 { color: #5a8c5f; }";
echo "a { color: #2c5530; text-decoration: none; }";
echo "a:hover { text-decoration: underline; }";
echo "ul { padding-left: 20px; }";
echo "li { margin: 5px 0; }";
echo "</style>";
?>