<aside class="admin-sidebar">
    <div class="sidebar-content">
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="/admin/" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/admin/') === 0 && substr_count($_SERVER['REQUEST_URI'], '/') === 1 ? 'active' : '' ?>">
                        <span class="nav-icon">📊</span>
                        <span class="nav-text">대시보드</span>
                    </a>
                </li>
                
                <li class="nav-section">사용자 관리</li>
                <li class="nav-item">
                    <a href="/admin/users/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/users/') === 0 ? 'active' : '' ?>">
                        <span class="nav-icon">👥</span>
                        <span class="nav-text">사용자 목록</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/admin/users/permissions.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'permissions.php' ? 'active' : '' ?>">
                        <span class="nav-icon">🔑</span>
                        <span class="nav-text">권한 관리</span>
                    </a>
                </li>
                
                <li class="nav-section">상품 및 주문</li>
                <li class="nav-item">
                    <a href="/admin/products/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/products/') === 0 ? 'active' : '' ?>">
                        <span class="nav-icon">📦</span>
                        <span class="nav-text">상품 관리</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/admin/orders/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/orders/') === 0 ? 'active' : '' ?>">
                        <span class="nav-icon">🛒</span>
                        <span class="nav-text">주문 관리</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/admin/customers/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/customers/') === 0 ? 'active' : '' ?>">
                        <span class="nav-icon">👤</span>
                        <span class="nav-text">고객 관리</span>
                    </a>
                </li>
                
                <li class="nav-section">게시판 관리</li>
                <li class="nav-item">
                    <a href="/admin/board/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/board/') === 0 ? 'active' : '' ?>">
                        <span class="nav-icon">📝</span>
                        <span class="nav-text">게시글 관리</span>
                    </a>
                </li>
                
                <li class="nav-section">식물분석</li>
                <li class="nav-item">
                    <a href="/admin/plant_analysis/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/plant_analysis/') === 0 ? 'active' : '' ?>">
                        <span class="nav-icon">🌱</span>
                        <span class="nav-text">분석 현황</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/admin/plant_analysis/user_permissions.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'user_permissions.php' ? 'active' : '' ?>">
                        <span class="nav-icon">🔐</span>
                        <span class="nav-text">분석 권한 관리</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/admin/plant_analysis/analysis_logs.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'analysis_logs.php' ? 'active' : '' ?>">
                        <span class="nav-icon">📋</span>
                        <span class="nav-text">분석 로그</span>
                    </a>
                </li>
                
                <li class="nav-section">시스템</li>
                <li class="nav-item">
                    <a href="/admin/settings/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/settings/') === 0 && !strpos($_SERVER['REQUEST_URI'], '/admin/settings/company') && !strpos($_SERVER['REQUEST_URI'], '/admin/settings/media') ? 'active' : '' ?>">
                        <span class="nav-icon">⚙️</span>
                        <span class="nav-text">기본 설정</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/admin/settings/company.php" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/settings/company') === 0 ? 'active' : '' ?>">
                        <span class="nav-icon">🏢</span>
                        <span class="nav-text">회사 소개 관리</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/admin/settings/media.php" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/settings/media') === 0 ? 'active' : '' ?>">
                        <span class="nav-icon">🎬</span>
                        <span class="nav-text">미디어 관리</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/admin/settings/footer.php" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/settings/footer') === 0 ? 'active' : '' ?>">
                        <span class="nav-icon">🦶</span>
                        <span class="nav-text">푸터 관리</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/admin/support/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/support/') === 0 ? 'active' : '' ?>">
                        <span class="nav-icon">💬</span>
                        <span class="nav-text">고객지원 관리</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <div class="system-info">
                <div class="info-item">
                    <span class="info-label">시스템 상태</span>
                    <span class="info-value status-online">정상</span>
                </div>
                <div class="info-item">
                    <span class="info-label">버전</span>
                    <span class="info-value">v1.0</span>
                </div>
            </div>
        </div>
    </div>
</aside>