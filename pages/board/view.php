<?php
// Initialize session and auth before any output
$base_path = dirname(dirname(__DIR__));
require_once $base_path . '/classes/Auth.php';
$auth = Auth::getInstance();

require_once $base_path . '/classes/Database.php';

$id = $_GET['id'] ?? 0;
$error = '';
$success = '';

if (!$id) {
    header('Location: index.php');
    exit;
}

// 답글 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_reply_submit'])) {
    $reply_id = $_POST['reply_id'] ?? 0;
    $edit_content = trim($_POST['edit_content'] ?? '');
    $edit_password = $_POST['edit_password'] ?? '';
    
    if (empty($edit_content)) {
        $error = '답글 내용을 입력해주세요.';
    } elseif (empty($edit_password)) {
        $error = '비밀번호를 입력해주세요.';
    } else {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // 비밀번호 확인
            $sql = "SELECT password FROM board_replies WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$reply_id]);
            $reply = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($reply && password_verify($edit_password, $reply['password'])) {
                $sql = "UPDATE board_replies SET content = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$edit_content, $reply_id]);
                
                $success = '답글이 수정되었습니다.';
            } else {
                $error = '비밀번호가 일치하지 않습니다.';
            }
            
        } catch (Exception $e) {
            $error = '답글 수정에 실패했습니다.';
        }
    }
}

// 답글 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reply_submit'])) {
    $reply_id = $_POST['reply_id'] ?? 0;
    $delete_password = $_POST['delete_password'] ?? '';
    
    if (empty($delete_password)) {
        $error = '비밀번호를 입력해주세요.';
    } else {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // 비밀번호 확인
            $sql = "SELECT password FROM board_replies WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$reply_id]);
            $reply = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($reply && password_verify($delete_password, $reply['password'])) {
                $sql = "DELETE FROM board_replies WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$reply_id]);
                
                $success = '답글이 삭제되었습니다.';
            } else {
                $error = '비밀번호가 일치하지 않습니다.';
            }
            
        } catch (Exception $e) {
            $error = '답글 삭제에 실패했습니다.';
        }
    }
}

// 답글 작성 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_submit'])) {
    $reply_content = trim($_POST['reply_content'] ?? '');
    $reply_author = trim($_POST['reply_author'] ?? '');
    $reply_password = $_POST['reply_password'] ?? '';
    $reply_email = trim($_POST['reply_email'] ?? '');
    $is_private = isset($_POST['is_private']) ? 1 : 0;
    
    if (empty($reply_content)) {
        $error = '답글 내용을 입력해주세요.';
    } elseif (empty($reply_author)) {
        $error = '작성자명을 입력해주세요.';
    } elseif (empty($reply_password)) {
        $error = '비밀번호를 입력해주세요.';
    } else {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            $sql = "INSERT INTO board_replies (post_id, content, author, password, email, is_private) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $id,
                $reply_content,
                $reply_author,
                password_hash($reply_password, PASSWORD_DEFAULT),
                $reply_email,
                $is_private
            ]);
            
            $success = '답글이 등록되었습니다.';
            $_POST = []; // 폼 초기화
            
        } catch (Exception $e) {
            $error = '답글 등록에 실패했습니다.';
        }
    }
}

try {
    $pdo = Database::getInstance()->getConnection();
    $auth = Auth::getInstance();
    $currentUser = $auth->getCurrentUser();
    $isAdmin = $currentUser && $currentUser['user_level'] == 9;
    
    $sql = "UPDATE board_posts SET views = views + 1 WHERE id = ? AND status = 'active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    $sql = "SELECT * FROM board_posts WHERE id = ? AND status = 'active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    
    if (!$post) {
        header('Location: index.php');
        exit;
    }
    
    $sql = "SELECT * FROM board_attachments WHERE post_id = ? ORDER BY id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $attachments = $stmt->fetchAll();
    
    // 답글 가져오기
    $sql = "SELECT r.*, p.author as post_author, p.email as post_author_email 
            FROM board_replies r 
            JOIN board_posts p ON r.post_id = p.id 
            WHERE r.post_id = ? AND r.status = 'active' 
            ORDER BY r.created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $replies = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = '게시글을 불러오는데 실패했습니다.';
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title'] ?? '게시글') ?> - 탄생</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        .view-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .post-header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .post-title {
            font-size: 2rem;
            margin: 0 0 20px 0;
            color: #333;
        }
        
        .post-badges {
            margin-bottom: 15px;
        }
        
        .notice-badge {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-right: 8px;
        }
        
        .review-badge {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-right: 8px;
        }
        
        .post-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        
        .meta-left {
            display: flex;
            gap: 20px;
        }
        
        .meta-right {
            display: flex;
            gap: 10px;
        }
        
        .post-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            line-height: 1.8;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .post-attachments {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .attachments-title {
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }
        
        .attachment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .attachment-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
        }
        
        .attachment-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
            cursor: pointer;
        }
        
        .attachment-info {
            padding: 10px;
            text-align: center;
        }
        
        .attachment-name {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            word-break: break-all;
        }
        
        .attachment-download {
            font-size: 12px;
            color: #007bff;
            text-decoration: none;
        }
        
        .attachment-download:hover {
            text-decoration: underline;
        }
        
        .video-placeholder {
            width: 100%;
            height: 150px;
            background: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }
        
        .post-actions {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        
        .action-left {
            display: flex;
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
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-outline {
            background-color: white;
            color: #007bff;
            border: 1px solid #007bff;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
        }
        
        .modal-image {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }
        
        .modal-video {
            width: 100%;
            height: auto;
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .modal-close:hover {
            color: #ccc;
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
        
        /* 답글 스타일 */
        .replies-section {
            margin-top: 40px;
        }
        
        .replies-header {
            background: white;
            padding: 20px 30px;
            border-radius: 8px 8px 0 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-bottom: 1px solid #eee;
        }
        
        .replies-header h3 {
            margin: 0;
            color: #333;
            font-size: 1.2rem;
        }
        
        .replies-list {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .reply-item {
            padding: 20px 30px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .reply-item:last-child {
            border-bottom: none;
        }
        
        .reply-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .reply-author {
            font-weight: bold;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .reply-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .reply-date {
            color: #666;
            font-size: 13px;
        }
        
        .reply-actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-small {
            padding: 4px 8px;
            font-size: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-edit:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .btn-delete:hover {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }
        
        .reply-edit-form,
        .reply-delete-form {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: 1px solid #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        
        .reply-content {
            line-height: 1.6;
            color: #555;
        }
        
        .private-content {
            font-style: italic;
            color: #888;
        }
        
        .private-badge {
            background: #ffc107;
            color: #212529;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .reply-form-section {
            background: white;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 1px;
        }
        
        .reply-form-section h4 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 1.1rem;
        }
        
        .reply-form {
            max-width: none;
        }
        
        .reply-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .form-checkbox input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .checkbox-text {
            color: #555;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        @media (max-width: 768px) {
            .post-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .post-actions {
                flex-direction: column;
            }
            
            .attachment-grid {
                grid-template-columns: 1fr;
            }
            
            .replies-header,
            .replies-list .reply-item,
            .reply-form-section {
                padding: 15px 20px;
            }
            
            .reply-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <main class="view-container">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php else: ?>
            <div class="post-header">
                <div class="post-badges">
                    <?php if ($post['is_notice']): ?>
                        <span class="notice-badge">공지</span>
                    <?php endif; ?>
                    <?php if ($post['post_type'] === 'review'): ?>
                        <span class="review-badge">리뷰</span>
                    <?php endif; ?>
                </div>
                
                <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>
                
                <div class="post-meta">
                    <div class="meta-left">
                        <span>작성자: <?= htmlspecialchars($post['author']) ?></span>
                        <span>작성일: <?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></span>
                    </div>
                    <div class="meta-right">
                        <span>조회수: <?= number_format($post['views']) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="post-content">
                <?= nl2br(htmlspecialchars($post['content'])) ?>
            </div>
            
            <?php if (!empty($attachments)): ?>
                <div class="post-attachments">
                    <div class="attachments-title">첨부파일 (<?= count($attachments) ?>개)</div>
                    <div class="attachment-grid">
                        <?php foreach ($attachments as $attachment): ?>
                            <div class="attachment-item">
                                <?php if (strpos($attachment['file_type'], 'image/') === 0): ?>
                                    <img src="<?= $attachment['file_path'] ?>" 
                                         alt="<?= htmlspecialchars($attachment['original_filename']) ?>"
                                         class="attachment-preview"
                                         onclick="openModal('<?= $attachment['file_path'] ?>', 'image')">
                                <?php elseif (strpos($attachment['file_type'], 'video/') === 0): ?>
                                    <div class="video-placeholder" 
                                         onclick="openModal('<?= $attachment['file_path'] ?>', 'video')">
                                        ▶
                                    </div>
                                <?php endif; ?>
                                
                                <div class="attachment-info">
                                    <div class="attachment-name"><?= htmlspecialchars($attachment['original_filename']) ?></div>
                                    <a href="<?= $attachment['file_path'] ?>" download="<?= htmlspecialchars($attachment['original_filename']) ?>" 
                                       class="attachment-download">다운로드</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="post-actions">
                <div class="action-left">
                    <a href="index.php" class="btn btn-outline">목록으로</a>
                </div>
                <div class="action-right">
                    <a href="edit.php?id=<?= $post['id'] ?>" class="btn btn-secondary">수정</a>
                    <?php if ($isAdmin): ?>
                        <a href="delete.php?id=<?= $post['id'] ?>&admin=1" class="btn btn-danger" 
                           onclick="return confirm('정말 삭제하시겠습니까?')">삭제</a>
                    <?php else: ?>
                        <a href="delete.php?id=<?= $post['id'] ?>" class="btn btn-danger" 
                           onclick="return confirm('정말 삭제하시겠습니까?')">삭제</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 답글 섹션 -->
            <div class="replies-section">
                <div class="replies-header">
                    <h3>답글 (<?= count($replies) ?>개)</h3>
                </div>
                
                <!-- 답글 목록 -->
                <?php if (!empty($replies)): ?>
                    <div class="replies-list">
                        <?php foreach ($replies as $reply): ?>
                            <div class="reply-item">
                                <?php 
                                // 비공개 답글 표시 권한 확인
                                $canViewReply = true;
                                if ($reply['is_private']) {
                                    // 관리자, 원글 작성자, 답글 작성자만 볼 수 있음
                                    $canViewReply = $isAdmin || 
                                                   ($currentUser && $currentUser['email'] === $reply['post_author_email']) ||
                                                   ($reply['email'] && $currentUser && $currentUser['email'] === $reply['email']);
                                }
                                ?>
                                
                                <?php if ($canViewReply): ?>
                                    <div class="reply-header">
                                        <div class="reply-author">
                                            <?= htmlspecialchars($reply['author']) ?>
                                            <?php if ($reply['is_private']): ?>
                                                <span class="private-badge">비공개</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="reply-meta">
                                            <span class="reply-date">
                                                <?= date('Y-m-d H:i', strtotime($reply['created_at'])) ?>
                                                <?php if ($reply['updated_at'] && $reply['updated_at'] !== $reply['created_at']): ?>
                                                    <small>(수정됨)</small>
                                                <?php endif; ?>
                                            </span>
                                            <div class="reply-actions">
                                                <button type="button" class="btn-small btn-edit" onclick="showEditForm(<?= $reply['id'] ?>, '<?= htmlspecialchars(str_replace(['\'', '"', '\n', '\r'], ['\\\'', '\\"', '\\n', '\\r'], $reply['content'])) ?>')">수정</button>
                                                <button type="button" class="btn-small btn-delete" onclick="showDeleteForm(<?= $reply['id'] ?>)">삭제</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="reply-content" id="reply-content-<?= $reply['id'] ?>">
                                        <?= nl2br(htmlspecialchars($reply['content'])) ?>
                                    </div>
                                    
                                    <!-- 수정 폼 (숨김) -->
                                    <div class="reply-edit-form" id="edit-form-<?= $reply['id'] ?>" style="display: none;">
                                        <form method="post">
                                            <input type="hidden" name="reply_id" value="<?= $reply['id'] ?>">
                                            <div class="form-group">
                                                <textarea name="edit_content" class="form-input reply-textarea" required><?= htmlspecialchars($reply['content']) ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <input type="password" name="edit_password" class="form-input" placeholder="비밀번호" required>
                                            </div>
                                            <div class="form-buttons">
                                                <button type="submit" name="edit_reply_submit" class="btn btn-primary">수정하기</button>
                                                <button type="button" class="btn btn-outline" onclick="hideEditForm(<?= $reply['id'] ?>)">취소</button>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- 삭제 폼 (숨김) -->
                                    <div class="reply-delete-form" id="delete-form-<?= $reply['id'] ?>" style="display: none;">
                                        <form method="post">
                                            <input type="hidden" name="reply_id" value="<?= $reply['id'] ?>">
                                            <div class="form-group">
                                                <label>답글을 삭제하시겠습니까?</label>
                                                <input type="password" name="delete_password" class="form-input" placeholder="비밀번호" required>
                                            </div>
                                            <div class="form-buttons">
                                                <button type="submit" name="delete_reply_submit" class="btn btn-danger">삭제하기</button>
                                                <button type="button" class="btn btn-outline" onclick="hideDeleteForm(<?= $reply['id'] ?>)">취소</button>
                                            </div>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="reply-private">
                                        <div class="reply-header">
                                            <div class="reply-author">
                                                <?= htmlspecialchars($reply['author']) ?>
                                                <span class="private-badge">비공개</span>
                                            </div>
                                            <div class="reply-date">
                                                <?= date('Y-m-d H:i', strtotime($reply['created_at'])) ?>
                                            </div>
                                        </div>
                                        <div class="reply-content private-content">
                                            🔒 비공개 답글입니다. 원글 작성자와 답글 작성자만 볼 수 있습니다.
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 답글 작성 폼 -->
                <div class="reply-form-section">
                    <h4>답글 작성</h4>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <form class="reply-form" method="post">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">작성자 *</label>
                                <input type="text" name="reply_author" class="form-input" required 
                                       value="<?= htmlspecialchars($_POST['reply_author'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">비밀번호 *</label>
                                <input type="password" name="reply_password" class="form-input" required 
                                       placeholder="수정/삭제시 필요합니다">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">이메일</label>
                            <input type="email" name="reply_email" class="form-input" 
                                   value="<?= htmlspecialchars($_POST['reply_email'] ?? '') ?>"
                                   placeholder="답변 알림을 받으시려면 입력하세요">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">답글 내용 *</label>
                            <textarea name="reply_content" class="form-input reply-textarea" required 
                                      placeholder="답글을 입력하세요..."><?= htmlspecialchars($_POST['reply_content'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-checkbox">
                                <input type="checkbox" name="is_private" <?= isset($_POST['is_private']) ? 'checked' : '' ?>>
                                <span class="checkbox-text">비공개 답글 (원글 작성자와 나만 볼 수 있음)</span>
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="reply_submit" class="btn btn-primary">답글 등록</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- Modal for images and videos -->
    <div id="mediaModal" class="modal" onclick="closeModal()">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <div class="modal-content" onclick="event.stopPropagation()">
            <img id="modalImage" class="modal-image" style="display: none;">
            <video id="modalVideo" class="modal-video" controls style="display: none;">
                <source id="modalVideoSource" src="" type="">
                브라우저가 비디오를 지원하지 않습니다.
            </video>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="/assets/js/main.js"></script>
    <script>
        function openModal(src, type) {
            const modal = document.getElementById('mediaModal');
            const modalImage = document.getElementById('modalImage');
            const modalVideo = document.getElementById('modalVideo');
            const modalVideoSource = document.getElementById('modalVideoSource');
            
            if (type === 'image') {
                modalImage.src = src;
                modalImage.style.display = 'block';
                modalVideo.style.display = 'none';
            } else if (type === 'video') {
                modalVideoSource.src = src;
                modalVideo.load();
                modalVideo.style.display = 'block';
                modalImage.style.display = 'none';
            }
            
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            const modal = document.getElementById('mediaModal');
            const modalVideo = document.getElementById('modalVideo');
            
            modal.style.display = 'none';
            modalVideo.pause();
            document.body.style.overflow = '';
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        
        // 답글 수정 폼 표시/숨김
        function showEditForm(replyId, content) {
            document.getElementById('reply-content-' + replyId).style.display = 'none';
            document.getElementById('edit-form-' + replyId).style.display = 'block';
            document.getElementById('delete-form-' + replyId).style.display = 'none';
        }
        
        function hideEditForm(replyId) {
            document.getElementById('reply-content-' + replyId).style.display = 'block';
            document.getElementById('edit-form-' + replyId).style.display = 'none';
        }
        
        // 답글 삭제 폼 표시/숨김
        function showDeleteForm(replyId) {
            document.getElementById('reply-content-' + replyId).style.display = 'none';
            document.getElementById('delete-form-' + replyId).style.display = 'block';
            document.getElementById('edit-form-' + replyId).style.display = 'none';
        }
        
        function hideDeleteForm(replyId) {
            document.getElementById('reply-content-' + replyId).style.display = 'block';
            document.getElementById('delete-form-' + replyId).style.display = 'none';
        }
    </script>
</body>
</html>