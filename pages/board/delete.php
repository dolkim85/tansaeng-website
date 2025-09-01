<?php
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Auth.php';

$id = $_GET['id'] ?? 0;
$isAdminDelete = $_GET['admin'] ?? 0;
$error = '';
$post = null;

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    $auth = Auth::getInstance();
    $currentUser = $auth->getCurrentUser();
    $isAdmin = $currentUser && $currentUser['user_level'] == 9;
    
    $sql = "SELECT * FROM board_posts WHERE id = ? AND status = 'active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    
    if (!$post) {
        header('Location: index.php');
        exit;
    }
    
} catch (Exception $e) {
    $error = '게시글을 불러오는데 실패했습니다.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    if ($isAdmin || password_verify($password, $post['password'])) {
        try {
            $pdo->beginTransaction();
            
            $sql = "SELECT file_path FROM board_attachments WHERE post_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $attachments = $stmt->fetchAll();
            
            foreach ($attachments as $attachment) {
                if ($attachment['file_path'] && file_exists(__DIR__ . '/../../' . $attachment['file_path'])) {
                    unlink(__DIR__ . '/../../' . $attachment['file_path']);
                }
            }
            
            $sql = "DELETE FROM board_attachments WHERE post_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            
            $sql = "UPDATE board_posts SET status = 'deleted' WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            
            $pdo->commit();
            header('Location: index.php?deleted=1');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollback();
            $error = '게시글 삭제에 실패했습니다.';
        }
    } else {
        $error = '비밀번호가 일치하지 않습니다.';
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시글 삭제 - 탄생</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        .delete-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .delete-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .delete-title {
            font-size: 2rem;
            margin: 0 0 10px 0;
            color: #dc3545;
        }
        
        .delete-subtitle {
            color: #666;
            margin: 0;
        }
        
        .post-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .post-title {
            font-size: 1.2rem;
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .post-meta {
            color: #666;
            font-size: 14px;
        }
        
        .delete-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-input:focus {
            border-color: #dc3545;
            outline: none;
        }
        
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .warning-box strong {
            color: #dc3545;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .admin-notice {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <main class="delete-container">
        <div class="delete-header">
            <h1 class="delete-title">게시글 삭제</h1>
            <p class="delete-subtitle">삭제된 게시글은 복구할 수 없습니다</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="post-info">
            <h3 class="post-title"><?= htmlspecialchars($post['title']) ?></h3>
            <div class="post-meta">
                작성자: <?= htmlspecialchars($post['author']) ?> | 
                작성일: <?= date('Y-m-d H:i', strtotime($post['created_at'])) ?>
            </div>
        </div>
        
        <?php if ($isAdmin && $isAdminDelete): ?>
            <div class="admin-notice">
                관리자 권한으로 삭제합니다. 비밀번호 확인 없이 삭제됩니다.
            </div>
            
            <form class="delete-form" method="post">
                <div class="warning-box">
                    <strong>주의:</strong> 이 게시글과 모든 첨부파일이 영구적으로 삭제됩니다. 
                    이 작업은 되돌릴 수 없습니다.
                </div>
                
                <div class="form-actions">
                    <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">취소</a>
                    <button type="submit" class="btn btn-danger">삭제하기</button>
                </div>
            </form>
        <?php else: ?>
            <form class="delete-form" method="post">
                <div class="warning-box">
                    <strong>주의:</strong> 이 게시글과 모든 첨부파일이 영구적으로 삭제됩니다. 
                    이 작업은 되돌릴 수 없습니다.
                </div>
                
                <div class="form-group">
                    <label class="form-label">비밀번호를 입력하세요 *</label>
                    <input type="password" name="password" class="form-input" required 
                           placeholder="게시글 작성시 입력한 비밀번호">
                </div>
                
                <div class="form-actions">
                    <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">취소</a>
                    <button type="submit" class="btn btn-danger">삭제하기</button>
                </div>
            </form>
        <?php endif; ?>
    </main>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="/assets/js/main.js"></script>
</body>
</html>