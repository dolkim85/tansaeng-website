<?php
// Initialize session and auth before any output
$base_path = dirname(dirname(__DIR__));
require_once $base_path . '/classes/Auth.php';
$auth = Auth::getInstance();
$auth->requireAdmin();

require_once $base_path . '/classes/Database.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;
$search = $_GET['search'] ?? '';

$customers = [];
$total_customers = 0;
$total_pages = 0;
$error = '';

try {
    $pdo = Database::getInstance()->getConnection();
    
    $where_conditions = ["user_level < 9"];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $params = ["%$search%", "%$search%", "%$search%"];
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // 총 고객 수 조회
    $count_sql = "SELECT COUNT(*) FROM users $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_customers = $stmt->fetchColumn();
    
    $total_pages = ceil($total_customers / $per_page);
    
    // 고객 목록 조회
    $per_page_int = (int) $per_page;
    $offset_int = (int) $offset;
    $sql = "SELECT id, name, email, phone, created_at, last_login, user_level, plant_analysis_permission, 
                   CASE WHEN last_login IS NOT NULL THEN 'active' ELSE 'inactive' END as status 
            FROM users $where_clause
            ORDER BY created_at DESC 
            LIMIT $per_page_int OFFSET $offset_int";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "고객 정보를 불러오는데 실패했습니다: " . $e->getMessage();
    $customers = [];
    $total_customers = 0;
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>고객 관리 - 탄생 관리자</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        
        .admin-content {
            flex: 1;
            padding: 30px;
            margin-left: 250px;
        }
        
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .page-title {
            margin: 0;
            color: #333;
            font-size: 1.8rem;
        }
        
        .page-subtitle {
            color: #666;
            margin-top: 5px;
        }
        
        .search-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .search-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            max-width: 400px;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
        }
        
        .btn-outline {
            background-color: white;
            color: #007bff;
            border: 1px solid #007bff;
        }
        
        .btn-outline:hover {
            background-color: #007bff;
            color: white;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #1e7e34;
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .permission-badge {
            background-color: #d1ecf1;
            color: #0c5460;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .pagination-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            text-decoration: none;
            color: #007bff;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background-color: #e9ecef;
        }
        
        .pagination .current {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            border-left: 4px solid;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-data-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .no-data-text {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1 class="page-title">👥 고객 관리</h1>
                <p class="page-subtitle">등록된 고객 정보를 관리합니다</p>
            </div>
            
            <div class="search-section">
                <form class="search-form" method="get">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="이름, 이메일, 전화번호로 검색하세요..." class="search-input">
                    <button type="submit" class="btn btn-primary">🔍 검색</button>
                    <?php if ($search): ?>
                        <a href="index.php" class="btn btn-outline">전체보기</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <strong>오류:</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="table-container">
                <?php if (empty($customers)): ?>
                    <div class="no-data">
                        <div class="no-data-icon">👥</div>
                        <div class="no-data-text">
                            <?= $search ? '검색 결과가 없습니다.' : '등록된 고객이 없습니다.' ?>
                        </div>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="80">번호</th>
                                <th>이름</th>
                                <th>이메일</th>
                                <th width="120">전화번호</th>
                                <th width="120">가입일</th>
                                <th width="120">최근 로그인</th>
                                <th width="80">등급</th>
                                <th width="100">식물분석</th>
                                <th width="80">상태</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $index => $customer): ?>
                                <tr>
                                    <td><?= $total_customers - ($page - 1) * $per_page - $index ?></td>
                                    <td><strong><?= htmlspecialchars($customer['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($customer['email']) ?></td>
                                    <td><?= htmlspecialchars($customer['phone'] ?? '-') ?></td>
                                    <td><?= date('Y-m-d', strtotime($customer['created_at'])) ?></td>
                                    <td><?= $customer['last_login'] ? date('Y-m-d H:i', strtotime($customer['last_login'])) : '없음' ?></td>
                                    <td>
                                        <?php
                                        $level_names = [1 => '일반', 5 => 'VIP', 9 => '관리자'];
                                        echo $level_names[$customer['user_level']] ?? '알 수 없음';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($customer['plant_analysis_permission']): ?>
                                            <span class="permission-badge">허용</span>
                                        <?php else: ?>
                                            <span>-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $customer['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                                            <?= $customer['status'] === 'active' ? '활성' : '비활성' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination-section">
                    <div class="pagination">
                        <?php
                        $page_group = ceil($page / 10);
                        $start_page = ($page_group - 1) * 10 + 1;
                        $end_page = min($start_page + 9, $total_pages);
                        ?>
                        
                        <?php if ($start_page > 1): ?>
                            <a href="?page=1<?= $search ? '&search=' . urlencode($search) : '' ?>">처음</a>
                            <a href="?page=<?= $start_page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">이전</a>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <a href="?page=<?= $end_page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">다음</a>
                            <a href="?page=<?= $total_pages ?><?= $search ? '&search=' . urlencode($search) : '' ?>">마지막</a>
                        <?php endif; ?>
                    </div>
                    
                    <div style="text-align: center; margin-top: 15px; color: #666; font-size: 14px;">
                        <?= $page ?>페이지 / 총 <?= $total_pages ?>페이지 
                        (<?= number_format($total_customers) ?>명 중 
                        <?= ($page - 1) * $per_page + 1 ?>-<?= min($page * $per_page, $total_customers) ?>번째)
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>