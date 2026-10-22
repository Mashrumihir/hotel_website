<?php
require_once __DIR__ . '/config.php';

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // First connect without DB to check/create DB
        $dsnNoDb = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
        $tmpPdo = new PDO($dsnNoDb, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $tmpPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Now connect to hotel_ease database
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);

        // Auto-migrate tables if not created yet
        autoMigrateDatabase($pdo);

        return $pdo;
    } catch (PDOException $e) {
        // If DB server is unreachable, log error
        error_log("Database Connection Error: " . $e->getMessage());
        return null;
    }
}

function autoMigrateDatabase($pdo) {
    // 1. Admins Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admins` (
        `admin_id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) UNIQUE NOT NULL,
        `email` VARCHAR(100) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Customers Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `customers` (
        `customer_id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `gender` ENUM('Male', 'Female', 'Other') DEFAULT 'Male',
        `mobile` VARCHAR(20) NOT NULL,
        `email` VARCHAR(150) UNIQUE NOT NULL,
        `address` TEXT NULL,
        `id_proof` VARCHAR(100) NULL,
        `password` VARCHAR(255) NOT NULL,
        `plain_password` VARCHAR(150) NULL,
        `profile_pic` VARCHAR(255) NULL,
        `pref_language` VARCHAR(10) DEFAULT 'en',
        `pref_currency` VARCHAR(10) DEFAULT 'INR',
        `pref_timezone` VARCHAR(50) DEFAULT 'UTC',
        `pref_date_format` VARCHAR(20) DEFAULT 'Y-m-d',
        `save_booking_info` TINYINT(1) DEFAULT 1,
        `show_recommended` TINYINT(1) DEFAULT 1,
        `remember_room_type` TINYINT(1) DEFAULT 1,
        `enable_personalized` TINYINT(1) DEFAULT 1,
        `notif_email` TINYINT(1) DEFAULT 1,
        `notif_reminders` TINYINT(1) DEFAULT 1,
        `notif_offers` TINYINT(1) DEFAULT 0,
        `notif_price_alerts` TINYINT(1) DEFAULT 0,
        `theme_preference` VARCHAR(20) DEFAULT 'light',
        `status` ENUM('Active', 'Blocked', 'Inactive') DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Room Types Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `room_types` (
        `room_type_id` INT AUTO_INCREMENT PRIMARY KEY,
        `type_name` VARCHAR(100) UNIQUE NOT NULL,
        `description` TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 4. Rooms Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `rooms` (
        `room_id` INT AUTO_INCREMENT PRIMARY KEY,
        `room_no` VARCHAR(20) UNIQUE NOT NULL,
        `room_type_id` INT NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `capacity` INT NOT NULL DEFAULT 2,
        `description` TEXT NULL,
        `amenities` TEXT NULL,
        `image` VARCHAR(255) NULL,
        `floor` VARCHAR(20) NULL DEFAULT '1st Floor',
        `bed_type` VARCHAR(50) NULL DEFAULT 'King Bed',
        `status` ENUM('Available', 'Reserved', 'Occupied', 'Maintenance') DEFAULT 'Available',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`room_type_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 5. Bookings Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `bookings` (
        `booking_id` INT AUTO_INCREMENT PRIMARY KEY,
        `customer_id` INT NOT NULL,
        `room_id` INT NOT NULL,
        `check_in` DATE NOT NULL,
        `check_out` DATE NOT NULL,
        `adults` INT DEFAULT 1,
        `children` INT DEFAULT 0,
        `special_request` TEXT NULL,
        `booking_status` ENUM('Pending', 'Confirmed', 'Checked-In', 'Checked-Out', 'Cancelled') DEFAULT 'Pending',
        `total_amount` DECIMAL(10,2) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE CASCADE,
        FOREIGN KEY (`room_id`) REFERENCES `rooms`(`room_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 6. Payments Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `payments` (
        `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
        `booking_id` INT NOT NULL,
        `amount` DECIMAL(10,2) NOT NULL,
        `payment_mode` ENUM('Cash', 'Card', 'UPI', 'Online Payment') DEFAULT 'Card',
        `transaction_id` VARCHAR(100) NULL,
        `payment_details` TEXT NULL,
        `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `payment_status` ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Paid',
        FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 7. Employees Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `employees` (
        `employee_id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `position` VARCHAR(100) NOT NULL,
        `mobile` VARCHAR(20) NOT NULL,
        `email` VARCHAR(150) UNIQUE NOT NULL,
        `profile_pic` VARCHAR(255) NULL,
        `salary` DECIMAL(10,2) NOT NULL,
        `joining_date` DATE NOT NULL,
        `status` ENUM('Active', 'Inactive', 'On Leave') DEFAULT 'Active'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 8. Contact Messages Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `contact_messages` (
        `message_id` INT AUTO_INCREMENT PRIMARY KEY,
        `customer_id` INT NULL,
        `booking_id` INT NULL,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(150) NOT NULL,
        `phone` VARCHAR(30) NULL,
        `subject` VARCHAR(255) NULL,
        `category` VARCHAR(100) DEFAULT 'General Inquiry',
        `message` TEXT NOT NULL,
        `status` ENUM('Unread', 'Read', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Unread',
        `admin_reply` TEXT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 9. Notifications Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notifications` (
        `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_type` ENUM('Admin', 'Customer') DEFAULT 'Customer',
        `user_id` INT NOT NULL,
        `title` VARCHAR(200) NOT NULL,
        `message` TEXT NOT NULL,
        `is_read` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 10. Wishlist Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `wishlist` (
        `wishlist_id` INT AUTO_INCREMENT PRIMARY KEY,
        `customer_id` INT NOT NULL,
        `room_id` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE CASCADE,
        FOREIGN KEY (`room_id`) REFERENCES `rooms`(`room_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 11. FAQs Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `faqs` (
        `faq_id` INT AUTO_INCREMENT PRIMARY KEY,
        `question` VARCHAR(255) NOT NULL,
        `answer` TEXT NOT NULL,
        `category` VARCHAR(100) DEFAULT 'General',
        `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
        `sort_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 12. Reviews Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `reviews` (
        `review_id` INT AUTO_INCREMENT PRIMARY KEY,
        `booking_id` INT NULL,
        `customer_id` INT NOT NULL,
        `room_id` INT NULL,
        `rating` INT NOT NULL DEFAULT 5,
        `comment` TEXT NULL,
        `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Approved',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Ensure missing columns exist on legacy installations
    $alterQueries = [
        "ALTER TABLE `payments` ADD COLUMN `transaction_id` VARCHAR(100) NULL AFTER `payment_mode`",
        "ALTER TABLE `payments` ADD COLUMN `payment_details` TEXT NULL AFTER `transaction_id`",
        "ALTER TABLE `employees` ADD COLUMN `profile_pic` VARCHAR(255) NULL AFTER `email`",
        "ALTER TABLE `customers` ADD COLUMN `plain_password` VARCHAR(150) NULL AFTER `password`",
        "ALTER TABLE `customers` ADD COLUMN `profile_pic` VARCHAR(255) NULL AFTER `plain_password`",
        "ALTER TABLE `customers` ADD COLUMN `pref_language` VARCHAR(10) DEFAULT 'en'",
        "ALTER TABLE `customers` ADD COLUMN `pref_currency` VARCHAR(10) DEFAULT 'INR'",
        "ALTER TABLE `customers` ADD COLUMN `pref_timezone` VARCHAR(50) DEFAULT 'UTC'",
        "ALTER TABLE `customers` ADD COLUMN `pref_date_format` VARCHAR(20) DEFAULT 'Y-m-d'",
        "ALTER TABLE `customers` ADD COLUMN `theme_preference` VARCHAR(20) DEFAULT 'light'",
        "ALTER TABLE `rooms` ADD COLUMN `floor` VARCHAR(20) NULL DEFAULT '1st Floor'",
        "ALTER TABLE `rooms` ADD COLUMN `bed_type` VARCHAR(50) NULL DEFAULT 'King Bed'",
        "ALTER TABLE `contact_messages` ADD COLUMN `customer_id` INT NULL AFTER `message_id`",
        "ALTER TABLE `contact_messages` ADD COLUMN `booking_id` INT NULL AFTER `customer_id`",
        "ALTER TABLE `contact_messages` ADD COLUMN `category` VARCHAR(100) DEFAULT 'General Inquiry'",
        "ALTER TABLE `contact_messages` ADD COLUMN `subject` VARCHAR(255) NULL",
        "ALTER TABLE `contact_messages` ADD COLUMN `status` ENUM('Unread', 'Read', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Unread'",
        "ALTER TABLE `contact_messages` ADD COLUMN `admin_reply` TEXT NULL",
        "ALTER TABLE `contact_messages` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];

    foreach ($alterQueries as $q) {
        try {
            $pdo->exec($q);
        } catch (Exception $e) {
            // Ignore duplicate column errors
        }
    }

    // Seed Data if tables are empty
    seedDatabaseData($pdo);
}

function seedDatabaseData($pdo) {
    // 1. Seed Admin (Username: admin, Password: admin123)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `admins`");
        if ($stmt->fetchColumn() == 0) {
            $hashedPass = password_hash('admin123', PASSWORD_BCRYPT);
            $pdo->exec("INSERT INTO `admins` (`username`, `email`, `password`) VALUES ('admin', 'admin@hotelease.com', '{$hashedPass}')");
        }
    } catch (Exception $e) {
        error_log("Seed Admins Error: " . $e->getMessage());
    }

    // 2. Seed Customers (Password: user123)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `customers`");
        $hashedUser = password_hash('user123', PASSWORD_BCRYPT);
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO `customers` (`name`, `gender`, `mobile`, `email`, `address`, `id_proof`, `password`, `plain_password`) VALUES 
            ('Mihir Mashru', 'Male', '+91 9876543210', 'mashrumihir15@gmail.com', 'Rajkot, Gujarat', 'Aadhar Card - 482910293841', '{$hashedUser}', 'user123'),
            ('Mihir Mashru', 'Male', '+91 9876543210', 'mihir@example.com', 'Rajkot, Gujarat', 'Aadhar Card - 482910293841', '{$hashedUser}', 'user123')");
        } else {
            $pdo->exec("UPDATE `customers` SET `password` = '{$hashedUser}', `plain_password` = 'user123' WHERE `email` IN ('mashrumihir15@gmail.com', 'mihir@example.com')");
        }
        $pdo->exec("UPDATE `customers` SET `plain_password` = 'user123' WHERE `plain_password` IS NULL OR `plain_password` = ''");
    } catch (Exception $e) {
        error_log("Seed Customers Error: " . $e->getMessage());
    }

    // 3. Seed Room Types
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `room_types`");
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO `room_types` (`room_type_id`, `type_name`, `description`) VALUES 
            (1, 'Standard Room', 'Comfortable entry-level luxury room with queen bed and workstation.'),
            (2, 'Deluxe Room', 'Spacious deluxe room featuring ocean views and marble bath.'),
            (3, 'Super Deluxe', 'Premium floor room with private sundeck and complimentary lounge access.'),
            (4, 'Executive Suite', 'Full luxury suite with separate living room, jacuzzi, and butler service.'),
            (5, 'Royal Suite', 'Penthouse master suite with panoramic vistas and private plunge pool.'),
            (6, 'Apartment Suite', 'Multi-bedroom suite designed for luxury family stays with full kitchen.');");
        }
    } catch (Exception $e) {
        error_log("Seed Room Types Error: " . $e->getMessage());
    }

    // 4. Seed Rooms
    try {
        $roomsData = [
            ['101', 1, 3500.00, 2, 'Cozy standard room with plush linens and high speed Wi-Fi.', 'Wi-Fi, Smart TV, AC, Mini Bar', 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1000&q=80', 'Available'],
            ['102', 2, 4500.00, 2, 'Deluxe room with glass balcony and ocean sunset view.', 'Wi-Fi, Jacuzzi, Smart TV, Balcony, Coffee Bar', 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1000&q=80', 'Occupied'],
            ['103', 3, 6500.00, 3, 'Super Deluxe suite enveloped in tropical greenery with sundeck.', 'Free Wi-Fi, Private Pool, Butler, Gym Pass', 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1000&q=80', 'Reserved'],
            ['104', 4, 8500.00, 4, 'Executive suite featuring master king bed and private lounge.', 'High-Speed Wi-Fi, 24/7 Butler, Wine Bar, Jacuzzi', 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&w=1000&q=80', 'Available'],
            ['105', 5, 14500.00, 6, 'Royal penthouse suite with heated pool and beach access.', 'Private Pool, Beach Access, Chef Service, Golf Cart', 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=1000&q=80', 'Available'],
            ['106', 6, 11000.00, 5, 'Spacious apartment suite tailored for family holidays.', 'Full Kitchen, 2 King Bedrooms, Kids Zone, Spa', 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=1000&q=80', 'Maintenance']
        ];
        
        $chkStmt = $pdo->prepare("SELECT COUNT(*) FROM `rooms` WHERE `room_no` = :rno");
        $insStmt = $pdo->prepare("INSERT INTO `rooms` (`room_no`, `room_type_id`, `price`, `capacity`, `description`, `amenities`, `image`, `status`) VALUES (:rno, :rtid, :prc, :cap, :dsc, :amn, :img, :sts)");
        
        foreach ($roomsData as $r) {
            $chkStmt->execute(['rno' => $r[0]]);
            if ($chkStmt->fetchColumn() == 0) {
                $insStmt->execute([
                    'rno' => $r[0], 'rtid' => $r[1], 'prc' => $r[2], 'cap' => $r[3],
                    'dsc' => $r[4], 'amn' => $r[5], 'img' => $r[6], 'sts' => $r[7]
                ]);
            }
        }
    } catch (Exception $e) {
        error_log("Seed Rooms Error: " . $e->getMessage());
    }

    // 5. Seed Employees
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `employees`");
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO `employees` (`name`, `position`, `mobile`, `email`, `salary`, `joining_date`, `status`) VALUES 
            ('Rajesh Kumar', 'General Manager', '+91 9823456789', 'rajesh@hotelease.com', 75000.00, '2023-01-10', 'Active'),
            ('Priya Sharma', 'Front Desk Supervisor', '+91 9812345678', 'priya@hotelease.com', 45000.00, '2023-04-15', 'Active'),
            ('Sunil Verma', 'Executive Chef', '+91 9834567890', 'sunil@hotelease.com', 60000.00, '2023-06-01', 'Active');");
        }
    } catch (Exception $e) {
        error_log("Seed Employees Error: " . $e->getMessage());
    }

    // 6. Seed FAQs
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `faqs`");
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO `faqs` (`question`, `answer`, `category`, `sort_order`, `status`) VALUES 
            ('How can I cancel my hotel booking?', 'You can cancel your hotel reservation anytime by navigating to My Bookings on your dashboard. Locate the specific reservation and click the Cancel Booking button.', 'Cancellation', 1, 'Active'),
            ('How can I modify my reservation?', 'To modify check-in dates, guest counts, or room type preferences, contact our 24/7 concierge front desk team or submit a support ticket.', 'Reservation', 2, 'Active'),
            ('Where can I see my booking details?', 'All active, completed, and upcoming hotel bookings are listed under the My Bookings dashboard page.', 'Reservation', 3, 'Active'),
            ('How do I request a refund?', 'Refunds for eligible cancelled stays are automatically processed. Funds reflect in your account within 3 to 5 business days.', 'Billing', 4, 'Active'),
            ('How can I download my invoice?', 'Digital tax invoices are available for instant download once your payment is confirmed.', 'Billing', 5, 'Active');");
        }
    } catch (Exception $e) {
        error_log("Seed FAQs Error: " . $e->getMessage());
    }

    // 7. Seed Notifications
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `notifications`");
        if ($stmt->fetchColumn() == 0) {
            $custStmt = $pdo->query("SELECT `customer_id` FROM `customers` LIMIT 1");
            $custId = $custStmt->fetchColumn() ?: 1;
            $pdo->exec("INSERT INTO `notifications` (`user_type`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES 
            ('Customer', {$custId}, 'Booking Confirmed!', 'Your reservation #HE-102 for Deluxe Room 102 has been successfully confirmed.', 0, NOW()),
            ('Customer', {$custId}, 'Payment Successful', 'Payment of ₹14,930.00 via Card was processed successfully.', 1, NOW() - INTERVAL 1 DAY),
            ('Customer', {$custId}, 'Welcome to Hotel Ease', 'Enjoy 20% off on spa & dining services during your luxury stay.', 1, NOW() - INTERVAL 2 DAY)");
        }
    } catch (Exception $e) {
        error_log("Seed Notifications Error: " . $e->getMessage());
    }

    // 8. Seed Wishlist (Dynamically using existing room IDs to avoid Foreign Key violations)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `wishlist`");
        if ($stmt->fetchColumn() == 0) {
            $custStmt = $pdo->query("SELECT `customer_id` FROM `customers` LIMIT 1");
            $custId = $custStmt->fetchColumn();
            
            $roomStmt = $pdo->query("SELECT `room_id` FROM `rooms` LIMIT 2");
            $roomIds = $roomStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if ($custId && !empty($roomIds)) {
                $insW = $pdo->prepare("INSERT IGNORE INTO `wishlist` (`customer_id`, `room_id`, `created_at`) VALUES (:cid, :rid, NOW())");
                foreach ($roomIds as $rId) {
                    $insW->execute(['cid' => $custId, 'rid' => $rId]);
                }
            }
        }
    } catch (Exception $e) {
        error_log("Seed Wishlist Error: " . $e->getMessage());
    }
}
?>
