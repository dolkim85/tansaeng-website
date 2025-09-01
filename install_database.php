<?php
// Fixed Database Installation Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = new PDO('mysql:host=localhost;dbname=tangsaeng_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n";
    
    // Drop all existing tables first
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $tables = ['user_login_logs', 'user_permissions', 'plant_analysis_logs', 'order_items', 'orders', 
               'cart', 'plant_analysis', 'plant_images', 'sensor_readings', 'contact_messages', 
               'notices', 'faq', 'raspberry_devices', 'products', 'categories', 'company_info', 
               'users', 'admin_users'];
    
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "Dropped table: $table\n";
        } catch (Exception $e) {
            // Ignore errors for non-existing tables
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Create tables in correct order
    echo "\nCreating tables...\n";
    
    // Company Information Table
    $pdo->exec("CREATE TABLE company_info (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        content TEXT,
        images JSON,
        videos JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "Created: company_info\n";
    
    // Users Table (Main user management)
    $pdo->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(50) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        user_level TINYINT DEFAULT 1 COMMENT '1: General, 2: Plant Analysis, 9: Admin',
        plant_analysis_permission BOOLEAN DEFAULT FALSE,
        email_verified BOOLEAN DEFAULT FALSE,
        verification_token VARCHAR(100),
        reset_token VARCHAR(100),
        reset_token_expires DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_user_level (user_level),
        INDEX idx_plant_permission (plant_analysis_permission)
    )");
    echo "Created: users\n";
    
    // Admin Users
    $pdo->exec("CREATE TABLE admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL,
        role VARCHAR(20) DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "Created: admin_users\n";
    
    // Product Categories
    $pdo->exec("CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        parent_id INT NULL,
        sort_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
        INDEX idx_parent_id (parent_id),
        INDEX idx_status (status)
    )");
    echo "Created: categories\n";
    
    // Products
    $pdo->exec("CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        name VARCHAR(200) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        stock INT DEFAULT 0,
        images JSON,
        specifications JSON,
        status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        INDEX idx_category_id (category_id),
        INDEX idx_status (status),
        INDEX idx_price (price)
    )");
    echo "Created: products\n";
    
    // Orders
    $pdo->exec("CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        status ENUM('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
        shipping_address TEXT,
        payment_method VARCHAR(50),
        payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_customer_id (customer_id),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    )");
    echo "Created: orders\n";
    
    // Order Items
    $pdo->exec("CREATE TABLE order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        INDEX idx_order_id (order_id)
    )");
    echo "Created: order_items\n";
    
    // Shopping Cart
    $pdo->exec("CREATE TABLE cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_product (user_id, product_id)
    )");
    echo "Created: cart\n";
    
    // Plant Images
    $pdo->exec("CREATE TABLE plant_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        original_filename VARCHAR(255),
        file_size INT,
        captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        analysis_status ENUM('pending', 'analyzing', 'completed', 'failed') DEFAULT 'pending',
        raspberry_id VARCHAR(50),
        metadata JSON,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_captured_at (captured_at),
        INDEX idx_analysis_status (analysis_status)
    )");
    echo "Created: plant_images\n";
    
    // Plant Analysis Results
    $pdo->exec("CREATE TABLE plant_analysis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_id INT NOT NULL,
        user_id INT NOT NULL,
        species VARCHAR(200),
        health_status VARCHAR(100),
        recommendations TEXT,
        confidence DECIMAL(5, 2),
        analysis_data JSON,
        analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (image_id) REFERENCES plant_images(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_image_id (image_id),
        INDEX idx_user_id (user_id)
    )");
    echo "Created: plant_analysis\n";
    
    // Sensor Readings
    $pdo->exec("CREATE TABLE sensor_readings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        raspberry_id VARCHAR(50),
        temperature DECIMAL(5, 2),
        humidity DECIMAL(5, 2),
        light_intensity DECIMAL(8, 2),
        ph_value DECIMAL(4, 2),
        ec_value DECIMAL(8, 2),
        recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        metadata JSON,
        INDEX idx_raspberry_id (raspberry_id),
        INDEX idx_recorded_at (recorded_at)
    )");
    echo "Created: sensor_readings\n";
    
    // User Login Logs
    $pdo->exec("CREATE TABLE user_login_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        user_agent TEXT,
        success BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_login_time (login_time)
    )");
    echo "Created: user_login_logs\n";
    
    // User Permissions History
    $pdo->exec("CREATE TABLE user_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        permission_type VARCHAR(50) NOT NULL,
        granted_by INT,
        granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        revoked_at TIMESTAMP NULL,
        notes TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_permission_type (permission_type)
    )");
    echo "Created: user_permissions\n";
    
    // Plant Analysis Activity Logs
    $pdo->exec("CREATE TABLE plant_analysis_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    )");
    echo "Created: plant_analysis_logs\n";
    
    // Raspberry Pi Devices
    $pdo->exec("CREATE TABLE raspberry_devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_name VARCHAR(100) NOT NULL,
        device_id VARCHAR(50) UNIQUE NOT NULL,
        ip_address VARCHAR(45),
        status ENUM('online', 'offline', 'maintenance') DEFAULT 'offline',
        last_contact TIMESTAMP,
        configuration JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_device_id (device_id),
        INDEX idx_status (status)
    )");
    echo "Created: raspberry_devices\n";
    
    // Support/FAQ
    $pdo->exec("CREATE TABLE faq (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(50),
        question TEXT NOT NULL,
        answer TEXT NOT NULL,
        sort_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created: faq\n";
    
    // Contact Messages
    $pdo->exec("CREATE TABLE contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(200),
        message TEXT NOT NULL,
        status ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created: contact_messages\n";
    
    // Notices - THIS WAS MISSING!
    $pdo->exec("CREATE TABLE notices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        author_id INT,
        status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
        published_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (author_id) REFERENCES admin_users(id) ON DELETE SET NULL
    )");
    echo "Created: notices\n";
    
    echo "\nInserting sample data...\n";
    
    // Insert default admin user (password: admin2025)
    $pdo->exec("INSERT INTO admin_users (username, password, email, role) 
                VALUES ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@tangsaeng.com', 'super_admin')");
    echo "Inserted: admin user\n";
    
    // Insert default user with admin privileges
    $pdo->exec("INSERT INTO users (name, email, password, user_level, plant_analysis_permission, created_at) 
                VALUES ('관리자', 'admin@tangsaeng.com', '\$2y\$10\$E4gF8VqmV7LR3X4Yr0bOIuOhGyR6B/m5kFv5T2xO7cPmLB8xYZv0u', 9, 1, NOW())");
    echo "Inserted: admin user profile\n";
    
    // Insert categories
    $pdo->exec("INSERT INTO categories (name, description, parent_id, sort_order) VALUES
                ('배지', '수경재배용 고급 배지', NULL, 1),
                ('양액', '작물별 맞춤형 양액', NULL, 2),
                ('장비', 'IoT 센서 및 모니터링 장비', NULL, 3),
                ('부자재', '재배에 필요한 각종 부자재', NULL, 4)");
    echo "Inserted: categories\n";
    
    // Insert products
    $pdo->exec("INSERT INTO products (name, category_id, description, price, stock, status) VALUES
                ('탄생 프리미엄 배지', 1, '최고급 코코피트와 펄라이트의 완벽한 조합으로 제작된 프리미엄 배지입니다.', 25000, 50, 'active'),
                ('토마토 전용 양액', 2, '토마토 재배에 최적화된 전용 양액으로 높은 수확량을 보장합니다.', 35000, 30, 'active'),
                ('IoT 센서 키트', 3, '온도, 습도, pH, EC를 실시간으로 모니터링할 수 있는 통합 센서 키트입니다.', 150000, 15, 'active'),
                ('오이 전용 양액', 2, '오이 재배에 최적화된 전용 양액', 32000, 25, 'active'),
                ('상추 전용 양액', 2, '상추류 재배에 특화된 양액', 28000, 40, 'active'),
                ('수경재배 키트', 3, '초보자를 위한 완전한 수경재배 키트', 89000, 12, 'active'),
                ('LED 식물 조명', 3, '식물 성장에 최적화된 전문 LED 조명', 120000, 8, 'active'),
                ('pH 측정기', 3, '정확한 pH 측정을 위한 디지털 측정기', 45000, 20, 'active')");
    echo "Inserted: products\n";
    
    // Insert notices
    $pdo->exec("INSERT INTO notices (title, content, status, published_at, created_at) VALUES
                ('탄생 스마트팜 오픈 안내', '새로운 AI 기반 스마트팜 시스템이 오픈되었습니다. 최신 IoT 기술과 식물분석 AI를 통해 더욱 스마트한 농업을 경험해보세요.', 'published', NOW(), NOW()),
                ('신규 회원 혜택 안내', '신규 회원 가입시 10% 할인 쿠폰과 무료배송 혜택을 제공합니다. 지금 가입하고 혜택을 받아보세요!', 'published', NOW(), NOW()),
                ('고객센터 운영 안내', '고객센터는 평일 오전 9시부터 오후 6시까지 운영됩니다. 궁금한 사항은 언제든지 문의해주세요.', 'published', NOW(), NOW())");
    echo "Inserted: notices\n";
    
    // Insert more sample data
    $pdo->exec("INSERT INTO sensor_readings (raspberry_id, temperature, humidity, light_intensity, ph_value, ec_value, recorded_at) VALUES
                ('RASP001', 22.5, 65.0, 450.0, 6.2, 1.8, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
                ('RASP001', 23.1, 64.2, 470.0, 6.1, 1.9, DATE_SUB(NOW(), INTERVAL 50 MINUTE)),
                ('RASP001', 22.8, 66.5, 420.0, 6.3, 1.7, DATE_SUB(NOW(), INTERVAL 40 MINUTE))");
    echo "Inserted: sensor readings\n";
    
    $pdo->exec("INSERT INTO faq (category, question, answer, sort_order, status) VALUES
                ('일반', '스마트팜 시스템은 어떻게 작동하나요?', 'IoT 센서를 통해 온도, 습도, pH 등을 실시간으로 모니터링하고, AI 기술로 식물 상태를 분석합니다.', 1, 'active'),
                ('기술', '식물분석 AI의 정확도는 얼마나 되나요?', '현재 95% 이상의 높은 정확도를 보이고 있으며, 지속적인 학습을 통해 개선되고 있습니다.', 2, 'active')");
    echo "Inserted: FAQ data\n";
    
    $pdo->exec("INSERT INTO raspberry_devices (device_name, device_id, ip_address, status, last_contact, configuration) VALUES
                ('메인 온실 모니터링', 'RASP001', '192.168.1.100', 'online', NOW(), '{\"sensors\": [\"temperature\", \"humidity\", \"ph\", \"ec\"], \"camera\": true, \"interval\": 300}')");
    echo "Inserted: raspberry device\n";
    
    $pdo->exec("INSERT INTO company_info (title, content, images, videos) VALUES
                ('회사 소개', '탄생은 최첨단 스마트팜 기술을 통해 지속가능한 농업의 미래를 만들어갑니다.', '[\"company1.jpg\", \"company2.jpg\"]', '[\"intro.mp4\"]')");
    echo "Inserted: company info\n";
    
    echo "\n=== DATABASE INSTALLATION COMPLETED SUCCESSFULLY! ===\n";
    
    // Verify all tables exist
    echo "\nVerifying all tables:\n";
    $result = $pdo->query('SHOW TABLES');
    while ($row = $result->fetch()) {
        echo "✓ " . $row[0] . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>