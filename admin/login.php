<?php
// Initialize session and auth before any output
$base_path = __DIR__ . '/..';
require_once $base_path . '/classes/Auth.php';

$error = '';
$auth = Auth::getInstance();

// If already logged in as admin, redirect to dashboard
if ($auth->isLoggedIn() && $auth->isAdmin()) {
    header('Location: /admin/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '사용자명과 비밀번호를 입력해주세요.';
    } else {
        try {
            // Try to login with Auth class
            $loginResult = $auth->login($username, $password);
            
            if ($loginResult && $auth->isAdmin()) {
                header('Location: /admin/');
                exit;
            } else {
                $error = '관리자 권한이 없습니다.';
            }
        } catch (Exception $e) {
            $error = '로그인에 실패했습니다: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 로그인 - 탄생</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-login-body">
    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-login-header">
                <h1>탄생 관리자</h1>
                <p>시스템 관리자 로그인</p>
            </div>
            
            <form method="post" class="admin-login-form">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="username">사용자명</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">비밀번호</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">로그인</button>
            </form>
            
            <div class="admin-login-footer">
                <a href="/">일반 사이트로 돌아가기</a>
            </div>
        </div>
    </div>
    
    <script>
        // Simple brute force protection
        let loginAttempts = 0;
        const maxAttempts = 5;
        
        document.querySelector('form').addEventListener('submit', function(e) {
            loginAttempts++;
            if (loginAttempts > maxAttempts) {
                alert('너무 많은 로그인 시도로 인해 5분간 차단됩니다.');
                e.preventDefault();
                setTimeout(() => {
                    loginAttempts = 0;
                }, 300000); // 5 minutes
            }
        });
    </script>
</body>
</html>