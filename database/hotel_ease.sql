-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2026 at 07:33 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hotel_ease`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'admin', 'hotelease@admin.com', '$2y$10$iH.RwTFTnaor/Gmmm6WjNuyeLdFyuiJTsxjgm2R4Top8dYhnEst2e', '2026-09-23 04:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `adults` int(11) DEFAULT 1,
  `children` int(11) DEFAULT 0,
  `special_request` text DEFAULT NULL,
  `booking_status` enum('Pending','Confirmed','Checked-In','Checked-Out','Cancelled') DEFAULT 'Pending',
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `customer_id`, `room_id`, `check_in`, `check_out`, `adults`, `children`, `special_request`, `booking_status`, `total_amount`, `created_at`) VALUES
(100, 2, 1, '2026-10-07', '2026-10-08', 1, 0, 'Primary Guest Age: 20 yrs', 'Confirmed', 4130.00, '2026-10-07 09:27:44'),
(101, 2, 2, '2026-10-07', '2026-10-08', 1, 0, 'Primary Guest Age: 25 yrs', 'Confirmed', 5310.00, '2026-10-07 09:29:51');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('Unread','Replied','Closed') DEFAULT 'Unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `booking_id` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT 'General Inquiry',
  `admin_reply` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT 'Male',
  `mobile` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `address` text DEFAULT NULL,
  `id_proof` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `plain_password` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT NULL,
  `pref_language` varchar(10) DEFAULT 'en',
  `pref_currency` varchar(10) DEFAULT 'INR',
  `pref_timezone` varchar(50) DEFAULT 'Asia/Kolkata',
  `pref_date_format` varchar(20) DEFAULT 'DD/MM/YYYY',
  `save_booking_info` tinyint(1) DEFAULT 1,
  `show_recommended` tinyint(1) DEFAULT 1,
  `remember_room_type` tinyint(1) DEFAULT 1,
  `enable_personalized` tinyint(1) DEFAULT 1,
  `notif_email` tinyint(1) DEFAULT 1,
  `notif_reminders` tinyint(1) DEFAULT 1,
  `notif_offers` tinyint(1) DEFAULT 1,
  `notif_price_alerts` tinyint(1) DEFAULT 0,
  `theme_preference` varchar(20) DEFAULT 'light',
  `status` varchar(20) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `name`, `gender`, `mobile`, `email`, `address`, `id_proof`, `password`, `plain_password`, `created_at`, `profile_pic`, `pref_language`, `pref_currency`, `pref_timezone`, `pref_date_format`, `save_booking_info`, `show_recommended`, `remember_room_type`, `enable_personalized`, `notif_email`, `notif_reminders`, `notif_offers`, `notif_price_alerts`, `theme_preference`, `status`) VALUES
(2, 'Himaskhi', 'Female', '1236547890', 'mashrumihir15@gmail.com', 'Patel colony', 'cbvcbv', '$2y$10$Lvs60tZG31AgzzKVABJ/4eJrzX0v1dJybVhQV8FXLAa6srHgYbVce', 'user123', '2026-09-22 14:23:33', '/hotel_website/uploads/profiles/user_2_1791388421.jpg', 'en', 'INR', 'Asia/Kolkata', 'DD/MM/YYYY', 1, 1, 1, 1, 1, 1, 1, 0, 'light', 'Active'),
(8, 'himakshi mashru', 'Female', '9265212688', 'himakshimashru@gmail.com', 'Patel colony', 'Aadhar Card - 1522', '$2y$10$gv6AHAWPBGMfyv8HYIk7IOF7HvvMKQzC4/QHId6i/A/7injF7DJXW', 'Mihir@2001', '2026-10-07 15:46:06', NULL, 'en', 'INR', 'Asia/Kolkata', 'DD/MM/YYYY', 1, 1, 1, 1, 1, 1, 1, 0, 'light', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `position` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `salary` decimal(10,2) NOT NULL,
  `joining_date` date NOT NULL,
  `status` enum('Active','Inactive','On Leave') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `name`, `position`, `mobile`, `email`, `profile_pic`, `salary`, `joining_date`, `status`) VALUES
(1, 'Rajesh Kumar', 'General Manager', '+91 9823456789', 'rajesh@hotelease.com', NULL, 75000.00, '2023-01-10', 'Active'),
(2, 'Priya Sharma', 'Front Desk Supervisor', '+91 9812345678', 'priya@hotelease.com', NULL, 45000.00, '2023-04-15', 'Active'),
(3, 'Sunil Verma', 'Executive Chef', '+91 9834567890', 'sunil@hotelease.com', NULL, 60000.00, '2023-06-01', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `faq_id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(100) DEFAULT 'General',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`faq_id`, `question`, `answer`, `category`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'How can I cancel my hotel booking?', 'You can cancel your hotel reservation anytime by navigating to My Bookings on your dashboard. Locate the specific reservation and click the Cancel Booking button. Cancellations made 24 hours prior to check-in qualify for a 100% full refund.', 'Cancellation', 'Active', 1, '2026-09-29 23:24:55', '2026-09-29 23:24:55'),
(2, 'How can I modify my reservation?', 'To modify check-in dates, guest counts, or room type preferences, submit a support request referencing your Booking ID or contact our 24/7 concierge front desk team directly.', 'Reservation', 'Active', 2, '2026-09-29 23:24:55', '2026-09-29 23:24:55'),
(3, 'Where can I see my booking details?', 'All active, completed, and upcoming hotel bookings are listed under the My Bookings dashboard page. Click View Details on any booking card to inspect payment receipts and check-in timelines.', 'Reservation', 'Active', 3, '2026-09-29 23:24:55', '2026-09-29 23:24:55'),
(4, 'How do I request a refund?', 'Refunds for eligible cancelled stays are automatically initiated immediately upon cancellation. Funds reflect in your account within 3 to 5 business days.', 'Billing', 'Active', 4, '2026-09-29 23:24:55', '2026-09-29 23:24:55'),
(5, 'How can I download my invoice?', 'Digital tax invoices are available for instant download once your payment is confirmed. Go to My Bookings, open your reservation summary, and click Download Invoice (PDF).', 'Billing', 'Active', 5, '2026-09-29 23:24:55', '2026-09-29 23:24:55'),
(6, 'What should I do if I forgot my password?', 'If you are logged in, update your security credentials under Settings -> Privacy & Security. If locked out, click \"Forgot Password?\" on the guest sign-in screen.', 'Account', 'Active', 6, '2026-09-29 23:24:55', '2026-09-29 23:24:55'),
(7, 'How can I contact the hotel?', 'You can reach our 24/7 concierge front desk by calling our hotline or emailing concierge@hotelease.com. For instant message tracking, submit an online ticket.', 'General', 'Active', 7, '2026-09-29 23:24:55', '2026-09-29 23:24:55'),
(8, 'What should I do if I have a problem during my stay?', 'For immediate room service, housekeeping, or technical assistance during your stay, dial 0 from your in-room telephone or send a priority ticket via the mobile concierge portal.', 'Support', 'Active', 8, '2026-09-29 23:24:55', '2026-09-29 23:24:55');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_type` enum('Admin','Customer') DEFAULT 'Customer',
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_type`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 'Customer', 3, 'Booking Confirmed!', 'Your reservation #HE-102 for Deluxe Room 102 has been successfully confirmed.', 0, '2026-10-01 07:26:32'),
(2, 'Customer', 3, 'Payment Successful', 'Payment of ₹14,930.00 via Card was processed successfully.', 1, '2026-09-30 07:26:32'),
(3, 'Customer', 3, 'Welcome to Hotel Ease', 'Enjoy 20% off on spa & dining services during your luxury stay.', 1, '2026-09-29 07:26:32'),
(4, 'Customer', 2, 'Booking Confirmed (Pay at Desk)', 'Reservation #HE-73 is Confirmed! Payment of ₹4,130.00 is scheduled for Front Desk Check-In.', 0, '2026-10-02 13:27:03'),
(5, 'Customer', 2, 'Support Ticket Response', 'Official response posted for support ticket #1001: aefsgf', 0, '2026-10-06 16:57:42'),
(6, 'Customer', 6, 'Booking Confirmed (Pay at Desk)', 'Reservation #HE-98 is Confirmed! Payment of ₹4,130.00 is scheduled for Front Desk Check-In.', 0, '2026-10-07 03:19:15'),
(7, 'Customer', 2, 'Booking Confirmed (Pay at Desk)', 'Reservation #HE-99 is Confirmed! Payment of ₹4,130.00 is scheduled for Front Desk Check-In.', 0, '2026-10-07 03:41:41'),
(8, 'Customer', 2, 'Payment Successful', 'Payment of ₹4,130.00 via UPI was successful! Reservation #HE-100 is Confirmed.', 0, '2026-10-07 09:27:55'),
(9, 'Customer', 2, 'Payment Successful', 'Payment of ₹5,310.00 via Card was successful! Reservation #HE-101 is Confirmed.', 0, '2026-10-07 09:30:04');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_mode` enum('Cash','Card','UPI','Online Payment') DEFAULT 'Card',
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_details` text DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('Pending','Paid','Failed','Refunded') DEFAULT 'Paid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `amount`, `payment_mode`, `transaction_id`, `payment_details`, `payment_date`, `payment_status`) VALUES
(54, 100, 4130.00, 'UPI', 'UPI-559D9DCBF1', 'UPI ID: gpay@okaxis', '2026-10-07 09:27:55', 'Paid'),
(55, 101, 5310.00, 'Card', 'CARD-370AE3050F', 'Card ending in 2342 (xbvxcvbvb)', '2026-10-07 09:30:04', 'Paid');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_no` varchar(20) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 2,
  `description` text DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('Available','Reserved','Occupied','Maintenance') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `floor` varchar(50) DEFAULT 'Floor 1',
  `bed_type` varchar(50) DEFAULT 'King Bed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_no`, `room_type_id`, `price`, `capacity`, `description`, `amenities`, `image`, `status`, `created_at`, `floor`, `bed_type`) VALUES
(1, '101', 1, 3500.00, 2, 'Cozy standard room with plush linens and high speed Wi-Fi.', 'Wi-Fi, Smart TV, AC, Mini Bar', 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&amp;fit=crop&amp;w=1000&amp;q=80', 'Available', '2026-09-22 14:08:28', 'Floor 1', 'King Bed'),
(2, '102', 2, 4500.00, 2, 'Deluxe room with glass balcony and ocean sunset view.', 'Wi-Fi, Jacuzzi, Smart TV, Balcony, Coffee Bar', 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&amp;fit=crop&amp;w=1000&amp;q=80', 'Available', '2026-09-22 14:08:28', 'Floor 2', 'King Bed'),
(3, '103', 3, 6500.00, 3, 'Super Deluxe suite enveloped in tropical greenery with sundeck.', 'Free Wi-Fi, Private Pool, Butler, Gym Pass', 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&amp;fit=crop&amp;w=1000&amp;q=80', 'Available', '2026-09-22 14:08:28', 'Floor 2', 'King Bed'),
(4, '104', 4, 8500.00, 4, 'Executive suite featuring master king bed and private lounge.', 'High-Speed Wi-Fi, 24/7 Butler, Wine Bar, Jacuzzi', 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&amp;fit=crop&amp;w=1000&amp;q=80', 'Available', '2026-09-22 14:08:28', 'Floor 4', 'King Bed'),
(7, '105', 5, 14500.00, 6, 'Royal penthouse suite with heated pool and beach access.', 'Private Pool, Beach Access, Chef Service, Golf Cart', 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=1000&q=80', 'Available', '2026-10-07 17:25:54', 'Floor 1', 'King Bed'),
(8, '106', 6, 11000.00, 5, 'Spacious apartment suite tailored for family holidays.', 'Full Kitchen, 2 King Bedrooms, Kids Zone, Spa', 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=1000&q=80', 'Maintenance', '2026-10-07 17:25:54', 'Floor 1', 'King Bed');

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

CREATE TABLE `room_types` (
  `room_type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`room_type_id`, `type_name`, `description`) VALUES
(1, 'Standard Room', 'Comfortable entry-level luxury room with queen bed and workstation.'),
(2, 'Deluxe Room', 'Spacious deluxe room featuring ocean views and marble bath.'),
(3, 'Super Deluxe', 'Premium floor room with private sundeck and complimentary lounge access.'),
(4, 'Executive Suite', 'Full luxury suite with separate living room, jacuzzi, and butler service.'),
(5, 'Royal Suite', 'Penthouse master suite with panoramic vistas and private plunge pool.'),
(6, 'Apartment Suite', 'Multi-bedroom suite designed for luxury family stays with full kitchen.');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`wishlist_id`, `customer_id`, `room_id`, `created_at`) VALUES
(219, 8, 1, '2026-10-07 17:25:54'),
(220, 8, 2, '2026-10-07 17:25:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`faq_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_no` (`room_no`),
  ADD KEY `room_type_id` (`room_type_id`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`room_type_id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `room_id` (`room_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `faq_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `room_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=221;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`room_type_id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
