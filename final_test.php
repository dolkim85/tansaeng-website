<?php
// Final Complete System Test
echo "🌱 TANGSAENG SMART FARM - FINAL SYSTEM TEST 🌱\n";
echo "================================================\n\n";

$allPassed = true;

// Test 1: Database Connection
echo "🗄️  Database Connection Test\n";
try {
    require_once __DIR__ . '/config/database.php';
    $pdo = DatabaseConfig::getConnection();
    echo "   ✅ Database connection: SUCCESS\n";
} catch (Exception $e) {
    echo "   ❌ Database connection: FAILED - " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test 2: User Login
echo "\n👤 User Login Test\n";
try {
    require_once __DIR__ . '/classes/User.php';
    $user = new User();
    $userData = $user->login('admin@tangsaeng.com', 'admin2025');
    echo "   ✅ User login: SUCCESS\n";
    echo "   ✅ User: " . $userData['name'] . " (Level: " . $userData['user_level'] . ")\n";
} catch (Exception $e) {
    echo "   ❌ User login: FAILED - " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test 3: Admin Login
echo "\n👨‍💼 Admin Login Test\n";
try {
    require_once __DIR__ . '/classes/Admin.php';
    $admin = new Admin();
    $adminData = $admin->login('admin', 'admin2025');
    echo "   ✅ Admin login: SUCCESS\n";
    echo "   ✅ Admin: " . $adminData['username'] . " (Role: " . $adminData['role'] . ")\n";
} catch (Exception $e) {
    echo "   ❌ Admin login: FAILED - " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test 4: Tables Check
echo "\n📋 Database Tables Test\n";
try {
    $requiredTables = [
        'users', 'admin_users', 'categories', 'products', 'notices', 
        'user_login_logs', 'plant_analysis', 'sensor_readings'
    ];
    
    $result = $pdo->query('SHOW TABLES');
    $existingTables = [];
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $existingTables[] = $row[0];
    }
    
    $missing = array_diff($requiredTables, $existingTables);
    if (empty($missing)) {
        echo "   ✅ All required tables exist (" . count($requiredTables) . "/" . count($requiredTables) . ")\n";
    } else {
        echo "   ❌ Missing tables: " . implode(', ', $missing) . "\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ Tables check: FAILED - " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test 5: Data Integrity
echo "\n📊 Data Integrity Test\n";
try {
    $userCount = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $adminCount = $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    $productCount = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $noticeCount = $pdo->query('SELECT COUNT(*) FROM notices')->fetchColumn();
    
    echo "   ✅ Users: $userCount\n";
    echo "   ✅ Admins: $adminCount\n";
    echo "   ✅ Products: $productCount\n";
    echo "   ✅ Notices: $noticeCount\n";
    
    if ($userCount > 0 && $adminCount > 0 && $productCount > 0 && $noticeCount > 0) {
        echo "   ✅ Data integrity: SUCCESS\n";
    } else {
        echo "   ❌ Some tables are empty\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ Data integrity check: FAILED - " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test 6: Security Test
echo "\n🔒 Security Test\n";
try {
    $user = new User();
    
    // Test invalid login
    try {
        $user->login('fake@email.com', 'wrongpassword');
        echo "   ❌ Security: FAILED - Invalid login should be rejected\n";
        $allPassed = false;
    } catch (Exception $e) {
        echo "   ✅ Invalid login rejection: SUCCESS\n";
    }
    
    // Test password verification
    $result = $pdo->query("SELECT password FROM users WHERE email = 'admin@tangsaeng.com'")->fetch();
    if (password_verify('admin2025', $result['password'])) {
        echo "   ✅ Password verification: SUCCESS\n";
    } else {
        echo "   ❌ Password verification: FAILED\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ Security test: FAILED - " . $e->getMessage() . "\n";
    $allPassed = false;
}

echo "\n================================================\n";

if ($allPassed) {
    echo "🎉 ALL TESTS PASSED! SYSTEM IS FULLY OPERATIONAL! 🎉\n\n";
    
    echo "🌐 Ready to Use:\n";
    echo "   • Homepage Login: http://your-domain/pages/auth/login.php\n";
    echo "   • Admin Panel: http://your-domain/admin/login.php\n";
    echo "   • Plant Analysis: http://your-domain/pages/plant_analysis/\n";
    echo "   • Online Store: http://your-domain/pages/store/\n\n";
    
    echo "🔑 Login Credentials:\n";
    echo "   • User: admin@tangsaeng.com / admin2025\n";
    echo "   • Admin: admin / admin2025\n\n";
    
    echo "✨ The TANGSAENG Smart Farm system is ready for production!\n";
} else {
    echo "❌ SOME TESTS FAILED. PLEASE CHECK THE ERRORS ABOVE.\n";
}

echo "\n================================================\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
?>