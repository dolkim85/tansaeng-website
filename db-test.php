<?php
// PlanetScale 데이터베이스 연결 테스트
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>데이터베이스 연결 테스트</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 PlanetScale 데이터베이스 연결 테스트</h1>
        
        <div class="info">
            <strong>환경변수 확인:</strong><br>
            DB_HOST: <?= $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '❌ 미설정' ?><br>
            DB_NAME: <?= $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '❌ 미설정' ?><br>
            DB_USER: <?= $_ENV['DB_USER'] ?? getenv('DB_USER') ?: '❌ 미설정' ?><br>
            DB_PASS: <?= !empty($_ENV['DB_PASS'] ?? getenv('DB_PASS')) ? '✅ 설정됨' : '❌ 미설정' ?><br>
        </div>

        <?php
        try {
            require_once __DIR__ . '/classes/Database.php';
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            echo '<div class="success">✅ 데이터베이스 연결 성공!</div>';
            
            // 테이블 존재 확인
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo '<div class="info">';
            echo '<strong>발견된 테이블:</strong><br>';
            if (empty($tables)) {
                echo '⚠️ 테이블이 없습니다. 데이터베이스 설치가 필요합니다.<br>';
                echo '<a href="/install_database.php" style="color: #007bff;">데이터베이스 설치하기</a>';
            } else {
                foreach ($tables as $table) {
                    echo "• {$table}<br>";
                }
            }
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">❌ 데이터베이스 연결 실패:</div>';
            echo '<div class="info">' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <hr>
        <p><a href="/test.php">← PHP 테스트로 돌아가기</a> | <a href="/index.php">메인 사이트로 이동 →</a></p>
    </div>
</body>
</html>