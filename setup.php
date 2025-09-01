<?php
// 데이터베이스 자동 설정 페이지
session_start();

$step = $_GET['step'] ?? 1;
$message = '';
$error = '';

// Step 1: 데이터베이스 연결 테스트
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1) {
    $host = $_POST['host'] ?? 'localhost';
    $dbname = $_POST['dbname'] ?? 'tangsaeng_db';
    $username = $_POST['username'] ?? 'root';
    $password = $_POST['password'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 데이터베이스 생성
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
        
        // 설정 파일 업데이트
        $configContent = "<?php
// Database Configuration
class DatabaseConfig {
    private static \$host = '$host';
    private static \$dbname = '$dbname';
    private static \$username = '$username';
    private static \$password = '$password';
    
    public static function getConnection() {
        try {
            \$dsn = \"mysql:host=\" . self::\$host . \";dbname=\" . self::\$dbname . \";charset=utf8mb4\";
            \$pdo = new PDO(\$dsn, self::\$username, self::\$password);
            \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return \$pdo;
        } catch (PDOException \$e) {
            throw new Exception(\"데이터베이스 연결 실패: \" . \$e->getMessage());
        }
    }
}
?>";
        
        file_put_contents(__DIR__ . '/config/database.php', $configContent);
        
        $_SESSION['db_config'] = compact('host', 'dbname', 'username', 'password');
        header('Location: setup.php?step=2');
        exit;
        
    } catch (Exception $e) {
        $error = "데이터베이스 연결 실패: " . $e->getMessage();
    }
}

// Step 2: 테이블 생성
if ($step == 2) {
    if (!isset($_SESSION['db_config'])) {
        header('Location: setup.php?step=1');
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $config = $_SESSION['db_config'];
            $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4", 
                          $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Use the fixed installation script
            $installScript = __DIR__ . '/install_database.php';
            if (!file_exists($installScript)) {
                throw new Exception("설치 스크립트를 찾을 수 없습니다: $installScript");
            }
            
            // Execute the installation script
            ob_start();
            try {
                include $installScript;
                $output = ob_get_contents();
            } catch (Exception $e) {
                ob_end_clean();
                throw $e;
            }
            ob_end_clean();
            $message = "데이터베이스 테이블이 성공적으로 생성되었습니다!";
            
        } catch (Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollback();
            }
            $error = "테이블 생성 실패: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>탄생 시스템 설정</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        .setup-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .setup-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .setup-header h1 {
            color: #4CAF50;
            margin-bottom: 1rem;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 3rem;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 1rem;
            font-weight: bold;
        }
        .step.active {
            background: #4CAF50;
            color: white;
        }
        .step.completed {
            background: #2E7D32;
            color: white;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2E7D32;
        }
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-group input:focus {
            border-color: #4CAF50;
            outline: none;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background: #E8F5E8;
            color: #2E7D32;
            border: 1px solid #4CAF50;
        }
        .alert-error {
            background: #FFEBEE;
            color: #D32F2F;
            border: 1px solid #f44336;
        }
        .test-accounts {
            background: #F3E5F5;
            padding: 2rem;
            border-radius: 12px;
            margin-top: 2rem;
        }
        .test-accounts h3 {
            color: #7B1FA2;
            margin-bottom: 1rem;
        }
        .account-info {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .quick-link {
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .quick-link:hover {
            transform: translateY(-4px);
            text-decoration: none;
            color: white;
        }
        .requirements {
            background: #E3F2FD;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .requirements h3 {
            color: #1976D2;
            margin-bottom: 1rem;
        }
        .req-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .req-item.ok {
            color: #4CAF50;
        }
        .req-item.error {
            color: #f44336;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>🌱 탄생 시스템 설정</h1>
            <p>스마트팜 웹사이트의 모든 기능을 테스트할 수 있도록 설정합니다</p>
        </div>

        <div class="step-indicator">
            <div class="step <?= $step >= 1 ? 'active' : '' ?>">1</div>
            <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">2</div>
            <div class="step <?= $step >= 3 ? 'active' : '' ?>">3</div>
        </div>

        <?php if ($step == 1): ?>
        <!-- Step 1: 데이터베이스 연결 -->
        <h2>1단계: 데이터베이스 연결 설정</h2>
        
        <div class="requirements">
            <h3>📋 시스템 요구사항</h3>
            <?php
            $phpVersion = version_compare(PHP_VERSION, '7.4.0', '>=');
            $pdoMysql = extension_loaded('pdo_mysql');
            $mbstring = extension_loaded('mbstring');
            $gd = extension_loaded('gd');
            ?>
            <div class="req-item <?= $phpVersion ? 'ok' : 'error' ?>">
                <?= $phpVersion ? '✅' : '❌' ?> PHP 7.4+ (현재: <?= PHP_VERSION ?>)
            </div>
            <div class="req-item <?= $pdoMysql ? 'ok' : 'error' ?>">
                <?= $pdoMysql ? '✅' : '❌' ?> PDO MySQL 확장
            </div>
            <div class="req-item <?= $mbstring ? 'ok' : 'error' ?>">
                <?= $mbstring ? '✅' : '❌' ?> Mbstring 확장
            </div>
            <div class="req-item <?= $gd ? 'ok' : 'error' ?>">
                <?= $gd ? '✅' : '❌' ?> GD 확장
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="host">데이터베이스 호스트</label>
                <input type="text" id="host" name="host" value="localhost" required>
                <small>XAMPP/WAMP 사용시: localhost</small>
            </div>
            
            <div class="form-group">
                <label for="dbname">데이터베이스 이름</label>
                <input type="text" id="dbname" name="dbname" value="tangsaeng_db" required>
                <small>자동으로 생성됩니다</small>
            </div>
            
            <div class="form-group">
                <label for="username">사용자명</label>
                <input type="text" id="username" name="username" value="root" required>
                <small>XAMPP 기본값: root</small>
            </div>
            
            <div class="form-group">
                <label for="password">비밀번호</label>
                <input type="password" id="password" name="password" value="">
                <small>XAMPP는 보통 비밀번호가 없습니다</small>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                데이터베이스 연결 테스트 및 생성
            </button>
        </form>

        <?php elseif ($step == 2): ?>
        <!-- Step 2: 테이블 생성 -->
        <h2>2단계: 데이터베이스 테이블 생성</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <div style="text-align: center; margin: 2rem 0;">
                <a href="setup.php?step=3" class="btn btn-success btn-lg">다음 단계로 →</a>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!$message): ?>
        <p>데이터베이스 연결이 성공했습니다! 이제 필요한 테이블을 생성합니다.</p>
        
        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;">
            <h4>생성될 테이블:</h4>
            <ul>
                <li>users (사용자)</li>
                <li>admin_users (관리자)</li>
                <li>products (상품)</li>
                <li>categories (카테고리)</li>
                <li>plant_images (식물 이미지)</li>
                <li>plant_analysis (식물 분석)</li>
                <li>sensor_readings (센서 데이터)</li>
                <li>기타 시스템 테이블들...</li>
            </ul>
        </div>
        
        <form method="post">
            <button type="submit" class="btn btn-success btn-lg" style="width: 100%;">
                테이블 생성 시작
            </button>
        </form>
        <?php endif; ?>

        <?php else: ?>
        <!-- Step 3: 완료 및 테스트 -->
        <h2>🎉 설정 완료!</h2>
        
        <div class="alert alert-success">
            <strong>축하합니다!</strong> 탄생 스마트팜 시스템이 성공적으로 설정되었습니다.
        </div>

        <div class="test-accounts">
            <h3>🔐 테스트 계정 정보</h3>
            
            <div class="account-info">
                <h4>👤 일반 사용자 / 식물분석 권한자</h4>
                <p><strong>이메일:</strong> admin@tangsaeng.com</p>
                <p><strong>비밀번호:</strong> admin2025</p>
                <p><small>식물분석 시스템에 접근 가능</small></p>
            </div>
            
            <div class="account-info">
                <h4>👨‍💼 관리자</h4>
                <p><strong>사용자명:</strong> admin</p>
                <p><strong>비밀번호:</strong> admin2025</p>
                <p><small>관리자 패널에 접근 가능</small></p>
            </div>
        </div>

        <div class="quick-links">
            <a href="/" class="quick-link">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🏠</div>
                <div>메인 홈페이지</div>
            </a>
            
            <a href="/pages/auth/login.php" class="quick-link">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔑</div>
                <div>사용자 로그인</div>
            </a>
            
            <a href="/pages/store/" class="quick-link">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🛒</div>
                <div>온라인 스토어</div>
            </a>
            
            <a href="/pages/plant_analysis/" class="quick-link">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🌱</div>
                <div>식물분석 시스템</div>
            </a>
            
            <a href="/admin/" class="quick-link">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">⚙️</div>
                <div>관리자 패널</div>
            </a>
            
            <a href="/admin/login.php" class="quick-link">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">👨‍💼</div>
                <div>관리자 로그인</div>
            </a>
        </div>

        <div style="background: #E8F5E8; padding: 2rem; border-radius: 12px; margin-top: 2rem; text-align: center;">
            <h3 style="color: #2E7D32; margin-bottom: 1rem;">🚀 모든 준비가 완료되었습니다!</h3>
            <p>위의 링크들을 클릭하여 각 기능을 테스트해보세요.</p>
            <p><small>문제가 발생하면 이 설정 페이지를 다시 실행하실 수 있습니다.</small></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>