<?php
// Vercel PHP 작동 테스트
echo "<h1>🎉 PHP가 정상 작동합니다!</h1>";
echo "<p>현재 시간: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP 버전: " . phpversion() . "</p>";
echo "<p>서버 정보: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// 기본 HTML 구조
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>탄생 - PHP 테스트</title>
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
        .success {
            color: #28a745;
            font-size: 1.2em;
            margin-bottom: 20px;
        }
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
        <h1>🚀 탄생 웹사이트 - Vercel PHP 테스트</h1>
        <div class="success">✅ PHP가 정상적으로 작동하고 있습니다!</div>
        
        <div class="info">
            <strong>현재 시간:</strong> <?= date('Y-m-d H:i:s') ?><br>
            <strong>PHP 버전:</strong> <?= phpversion() ?><br>
            <strong>서버:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?>
        </div>
        
        <h2>다음 단계</h2>
        <ol>
            <li>이 페이지가 보인다면 PHP가 정상 작동</li>
            <li>환경변수 설정 후 메인 사이트 활성화</li>
            <li>도메인 연결</li>
        </ol>
        
        <p><a href="/index.php" style="color: #007bff;">메인 사이트로 이동</a></p>
    </div>
</body>
</html>