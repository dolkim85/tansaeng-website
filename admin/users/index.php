<?php
// Initialize session and auth before any output
$base_path = dirname(dirname(__DIR__));
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/User.php';

$auth = Auth::getInstance();
$auth->requireAdmin();

$user = new User();
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$search = trim($_GET['search'] ?? '');

$users = $user->getAllUsers($page, $limit, $search);
$totalUsers = $user->getUserCount($search);
$totalPages = ceil($totalUsers / $limit);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>사용자 관리 - 탄생 관리자</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-content">
                <div class="page-header">
                    <div class="page-title">
                        <h1>사용자 관리</h1>
                        <p>등록된 사용자 목록을 관리하고 권한을 설정할 수 있습니다</p>
                    </div>
                    <div class="page-actions">
                        <a href="permissions.php" class="btn btn-secondary">권한 관리</a>
                        <a href="export.php" class="btn btn-outline">📊 데이터 내보내기</a>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="table-controls">
                    <div class="search-box">
                        <form method="get" class="search-form">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="이름 또는 이메일로 검색...">
                            <button type="submit" class="btn btn-primary">검색</button>
                            <?php if ($search): ?>
                            <a href="?" class="btn btn-outline">초기화</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="table-info">
                        <span>총 <?= number_format($totalUsers) ?>명의 사용자</span>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>이름</th>
                                <th>이메일</th>
                                <th>연락처</th>
                                <th>사용자 레벨</th>
                                <th>식물분석 권한</th>
                                <th>가입일</th>
                                <th>최근 로그인</th>
                                <th>상태</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="10" class="no-data">
                                    <?= $search ? '검색 결과가 없습니다.' : '등록된 사용자가 없습니다.' ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($users as $userData): ?>
                            <tr>
                                <td><?= $userData['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($userData['name']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($userData['email']) ?></td>
                                <td><?= htmlspecialchars($userData['phone'] ?? '-') ?></td>
                                <td>
                                    <span class="user-level level-<?= $userData['user_level'] ?>">
                                        <?php
                                        switch($userData['user_level']) {
                                            case 1: echo '일반 사용자'; break;
                                            case 2: echo '식물분석 권한자'; break;
                                            case 9: echo '관리자'; break;
                                            default: echo '미정의';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="permission-badge <?= $userData['plant_analysis_permission'] ? 'granted' : 'denied' ?>">
                                        <?= $userData['plant_analysis_permission'] ? '승인' : '미승인' ?>
                                    </span>
                                </td>
                                <td><?= date('Y-m-d', strtotime($userData['created_at'])) ?></td>
                                <td><?= $userData['last_login'] ? date('Y-m-d H:i', strtotime($userData['last_login'])) : '없음' ?></td>
                                <td>
                                    <span class="status-badge <?= $userData['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $userData['is_active'] ? '활성' : '비활성' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="detail.php?id=<?= $userData['id'] ?>" class="btn btn-sm btn-outline" title="상세보기">👁️</a>
                                        <a href="edit.php?id=<?= $userData['id'] ?>" class="btn btn-sm btn-secondary" title="수정">✏️</a>
                                        <?php if ($userData['plant_analysis_permission']): ?>
                                        <button onclick="togglePermission(<?= $userData['id'] ?>, 'revoke')" class="btn btn-sm btn-warning" title="권한 해제">🔒</button>
                                        <?php else: ?>
                                        <button onclick="togglePermission(<?= $userData['id'] ?>, 'grant')" class="btn btn-sm btn-success" title="권한 부여">🔓</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-outline">이전</a>
                    <?php endif; ?>
                    
                    <div class="page-numbers">
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                        <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                           class="btn <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-outline">다음</a>
                    <?php endif; ?>
                </div>
                <div class="pagination-info">
                    <span><?= number_format($totalUsers) ?>명 중 <?= number_format(($page - 1) * $limit + 1) ?>-<?= number_format(min($page * $limit, $totalUsers)) ?>명 표시</span>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/admin.js"></script>
    <script>
        function togglePermission(userId, action) {
            const actionText = action === 'grant' ? '권한을 부여' : '권한을 해제';
            
            if (!confirm(`정말로 이 사용자의 식물분석 ${actionText}하시겠습니까?`)) {
                return;
            }
            
            TangsaengApp.showLoading();
            
            fetch('/admin/api/toggle_permission.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: userId,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                TangsaengApp.hideLoading();
                
                if (data.success) {
                    TangsaengApp.showAlert('권한이 성공적으로 변경되었습니다.', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    TangsaengApp.showAlert(data.message || '권한 변경에 실패했습니다.', 'error');
                }
            })
            .catch(error => {
                TangsaengApp.hideLoading();
                TangsaengApp.showAlert('서버 오류가 발생했습니다.', 'error');
                console.error('Error:', error);
            });
        }
    </script>
    
    <style>
        /* 사용자 관리 페이지 커스텀 스타일 - admin.css 기본 레이아웃 유지 */
        
        /* 테이블 관련 스타일만 추가 */
        .table-controls {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .search-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .search-form input {
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            width: 300px;
        }
        
        .search-form input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        .table-info {
            color: #666;
            font-size: 0.9rem;
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        
        .admin-table th {
            background: #f8f9fa;
            color: #333;
            font-weight: 600;
            padding: 1rem 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.85rem;
        }
        
        .admin-table td {
            padding: 1rem 0.75rem;
            border-bottom: 1px solid #f1f3f4;
            vertical-align: middle;
            font-size: 0.9rem;
        }
        
        .admin-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .no-data {
            text-align: center;
            color: #666;
            padding: 3rem 1rem;
            font-size: 1rem;
        }
        
        /* 뱃지 스타일 */
        .user-level, .permission-badge, .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .level-1 {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .level-2 {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .level-9 {
            background: #ffecb3;
            color: #f57f17;
        }
        
        .permission-badge.granted {
            background: #c8e6c9;
            color: #2e7d32;
        }
        
        .permission-badge.denied {
            background: #ffcdd2;
            color: #c62828;
        }
        
        .status-badge.active {
            background: #c8e6c9;
            color: #2e7d32;
        }
        
        .status-badge.inactive {
            background: #ffcdd2;
            color: #c62828;
        }
    </style>
</body>
</html>