<?php
// Initialize session and auth before any output
$base_path = dirname(dirname(__DIR__));
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Database.php';

$auth = Auth::getInstance();
$auth->requireAdmin();

$post_id = $_GET['id'] ?? 0;

if (!$post_id) {
    header('Location: index.php');
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Check if post exists and is active
    $sql = "SELECT id, title FROM board_posts WHERE id = ? AND status = 'active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        header('Location: index.php?error=post_not_found');
        exit;
    }
    
    // Soft delete the post
    $sql = "UPDATE board_posts SET status = 'deleted', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$post_id]);
    
    header('Location: index.php?success=deleted');
    exit;
    
} catch (Exception $e) {
    header('Location: index.php?error=delete_failed');
    exit;
}
?>