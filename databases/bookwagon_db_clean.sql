-- ========================================================
-- BookWagon Clean Starter Database (bookwagon_db_clean.sql)
-- Suitable for fresh deployment and GitHub cloning
-- Includes: 31 tables, autonomous MariaDB triggers, clean seed accounts
-- ========================================================

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `bookwagon_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `bookwagon_db`;

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `activity` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_accounts` (
  `account_id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(100) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `account_type` varchar(50) DEFAULT 'Savings',
  `branch` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `book_buddies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `book_buddies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `status` enum('pending','accepted') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_relationship` (`follower_id`,`following_id`),
  KEY `follower_idx` (`follower_id`),
  KEY `following_idx` (`following_id`),
  KEY `status_idx` (`status`),
  CONSTRAINT `fk_buddy_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_buddy_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `book_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `book_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `collection_type` enum('done_reading','wishlist','looking_for','book_hunt','need_to_read') NOT NULL,
  `notes` text DEFAULT NULL,
  `book_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `collection_type` (`collection_type`),
  CONSTRAINT `book_collections_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `book_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `book_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `image_type` varchar(50) DEFAULT 'additional',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`),
  KEY `idx_book` (`book_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `book_rentals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `book_rentals` (
  `rental_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `rental_date` datetime NOT NULL DEFAULT current_timestamp(),
  `due_date` datetime NOT NULL,
  `return_date` datetime DEFAULT NULL,
  `return_requested_date` datetime DEFAULT NULL,
  `rental_weeks` int(11) NOT NULL,
  `status` enum('active','returned','overdue','return_pending','disputed') DEFAULT 'active',
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_id` int(11) DEFAULT NULL,
  `late_fee` decimal(10,2) DEFAULT 0.00,
  `days_overdue` int(11) DEFAULT 0,
  `extensions_used` int(11) DEFAULT 0,
  `book_condition` enum('excellent','good','fair','damaged') DEFAULT 'good',
  `return_notes` text DEFAULT NULL,
  `return_token` varchar(64) DEFAULT NULL,
  `damage_reported` tinyint(1) DEFAULT 0,
  `damage_photo` varchar(255) DEFAULT NULL,
  `damage_notes` text DEFAULT NULL,
  `dispute_status` enum('none','pending_admin','resolved') DEFAULT 'none',
  PRIMARY KEY (`rental_id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`),
  KEY `seller_id` (`seller_id`),
  KEY `fk_rental_order` (`order_id`),
  CONSTRAINT `book_rentals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `book_rentals_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`),
  CONSTRAINT `book_rentals_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`),
  CONSTRAINT `fk_rental_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `book_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `book_returns` (
  `return_id` int(11) NOT NULL AUTO_INCREMENT,
  `rental_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `return_method` varchar(20) NOT NULL COMMENT 'dropoff or pickup',
  `return_details` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, in_transit, received, inspected, completed, cancelled',
  `request_date` datetime NOT NULL,
  `received_date` datetime DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `is_overdue` tinyint(1) NOT NULL DEFAULT 0,
  `days_overdue` int(11) NOT NULL DEFAULT 0,
  `late_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `additional_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `damage_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `damage_description` text DEFAULT NULL,
  `book_condition` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL COMMENT 'staff who processed the return',
  PRIMARY KEY (`return_id`),
  KEY `rental_id` (`rental_id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`),
  KEY `seller_id` (`seller_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `book_swaps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `book_swaps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `book_title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `condition` enum('New','Like New','Good','Fair') NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `status` enum('available','requested','swapped') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `genre` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `book_swaps_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `books` (
  `book_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `ISBN` varchar(20) DEFAULT NULL,
  `genre` varchar(255) DEFAULT '',
  `theme` varchar(255) DEFAULT '',
  `book_type` enum('Paperback','Hardcover','E-book','Audiobook') DEFAULT 'Paperback',
  `condition` enum('New','Like New','Very Good','Good','Fair','Poor') DEFAULT 'New',
  `damages` text DEFAULT NULL,
  `popularity` enum('Most popular','New Releases','Recommended','Customer favorites') DEFAULT 'New Releases',
  `price` decimal(10,2) DEFAULT NULL,
  `rent_price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `base_rental_fee` decimal(10,2) DEFAULT NULL,
  `handling_fee` decimal(10,2) DEFAULT NULL,
  `condition_multiplier` decimal(5,2) DEFAULT NULL,
  `book_value` decimal(10,2) DEFAULT NULL,
  `listing_fee` decimal(10,2) DEFAULT NULL,
  `markup_percentage` int(11) DEFAULT NULL,
  `listing_type` enum('both','sale','rent') DEFAULT 'both',
  `security_deposit` decimal(10,2) DEFAULT 0.00,
  `seller_note` text DEFAULT NULL,
  `meetup_location` varchar(255) DEFAULT 'Campus Meet-up',
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `rejection_reason` text DEFAULT NULL,
  `admin_feedback` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `seller_notified` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`book_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `books_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `purchase_type` enum('buy','rent') NOT NULL DEFAULT 'buy',
  `rental_weeks` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`cart_id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `conversation_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversation_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_participant` (`conversation_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `conversation_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversation_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `delivery_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_methods` (
  `method_id` int(11) NOT NULL AUTO_INCREMENT,
  `method_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `forum_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fa-comments',
  `color` varchar(20) DEFAULT '#d9b99b',
  `order_index` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `forum_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `likes` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`comment_id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `fk_comment_parent` FOREIGN KEY (`parent_id`) REFERENCES `forum_comments` (`comment_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_comment_post` FOREIGN KEY (`post_id`) REFERENCES `forum_posts` (`post_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `forum_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_posts` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `status` enum('active','closed','hidden') NOT NULL DEFAULT 'active',
  `views` int(11) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`post_id`),
  KEY `category_id` (`category_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_post_category` FOREIGN KEY (`category_id`) REFERENCES `forum_categories` (`category_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_post_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `forum_user_interactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_user_interactions` (
  `interaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `interaction_type` enum('like','bookmark','follow') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`interaction_id`),
  UNIQUE KEY `unique_interaction` (`user_id`,`post_id`,`comment_id`,`interaction_type`),
  KEY `post_id` (`post_id`),
  KEY `comment_id` (`comment_id`),
  CONSTRAINT `fk_interaction_comment` FOREIGN KEY (`comment_id`) REFERENCES `forum_comments` (`comment_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_interaction_post` FOREIGN KEY (`post_id`) REFERENCES `forum_posts` (`post_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_interaction_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `login_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `device_info` varchar(255) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'success',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `login_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `idx_conversation_date` (`conversation_id`,`created_at`),
  KEY `idx_unread_messages` (`is_read`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notification_user_idx` (`user_id`),
  KEY `notification_sender_idx` (`sender_id`),
  KEY `notification_read_status_idx` (`user_id`,`is_read`),
  CONSTRAINT `fk_notification_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `purchase_type` enum('buy','rent') NOT NULL DEFAULT 'buy',
  `rental_weeks` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','pending_meetup','shipped','shipped_pending_confirmation','delivered','return_pending','returned','disputed','cancelled') DEFAULT 'pending',
  `initial_condition_photo` varchar(255) DEFAULT NULL,
  `renter_condition_comments` text DEFAULT NULL,
  `seller_handover_token` varchar(64) DEFAULT NULL,
  `renter_handover_token` varchar(64) DEFAULT NULL,
  `renter_return_token` varchar(64) DEFAULT NULL,
  `damage_reported` tinyint(1) DEFAULT 0,
  `damage_photo` varchar(255) DEFAULT NULL,
  `damage_notes` text DEFAULT NULL,
  `dispute_status` enum('none','pending_admin','resolved') DEFAULT 'none',
  `refund_status` enum('none','pending','refunded') DEFAULT 'none',
  `refund_notes` text DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `order_id` (`order_id`),
  KEY `book_id` (`book_id`),
  KEY `seller_id` (`seller_id`),
  KEY `idx_purchase_type` (`purchase_type`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`),
  CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(50) DEFAULT NULL COMMENT 'Payment method (cod, pickup, bank_transfer, etc)',
  `shipping_fee` decimal(10,2) DEFAULT 0.00,
  `payment_status` varchar(50) DEFAULT 'pending' COMMENT 'Status of the payment (pending, paid, failed, etc)',
  `payment_date` datetime DEFAULT NULL COMMENT 'When the payment was made',
  `payment_receipt` varchar(255) DEFAULT NULL COMMENT 'Path to uploaded payment receipt file',
  `pickup_location` varchar(255) DEFAULT NULL COMMENT 'Selected pickup location for pickup/meetup',
  `pickup_date` date DEFAULT NULL COMMENT 'Selected pickup date for pickup/meetup',
  `order_status` enum('pending','processing','pending_meetup','shipped','delivered','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `payment_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'Payment action (payment_initiated, payment_received, etc)',
  `status` varchar(50) NOT NULL COMMENT 'Result of the action (success, failed, pending)',
  `amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Amount involved in this action',
  `details` text DEFAULT NULL COMMENT 'Additional details or notes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `payment_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `payment_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `seller_payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seller_payouts` (
  `payout_id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','rejected') DEFAULT 'pending',
  `payout_method` varchar(50) DEFAULT 'GCash',
  `payout_account` varchar(100) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `request_date` datetime DEFAULT current_timestamp(),
  `process_date` datetime DEFAULT NULL,
  PRIMARY KEY (`payout_id`),
  KEY `seller_id` (`seller_id`),
  CONSTRAINT `seller_payouts_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sellers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sellers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `shop_name` varchar(100) NOT NULL,
  `seller_type` varchar(50) DEFAULT NULL,
  `business_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `business_email` varchar(100) NOT NULL,
  `business_phone` varchar(20) DEFAULT NULL,
  `shop_logo` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `primary_id_type` varchar(100) DEFAULT NULL,
  `primary_id_front` varchar(255) DEFAULT NULL,
  `primary_id_back` varchar(255) DEFAULT NULL,
  `secondary_id_type` varchar(100) DEFAULT NULL,
  `secondary_id_front` varchar(255) DEFAULT NULL,
  `secondary_id_back` varchar(255) DEFAULT NULL,
  `selfie_image` varchar(255) DEFAULT NULL,
  `social_media` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_seller_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `swap_logistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `swap_logistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `delivery_method` enum('pickup','meetup') NOT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_id` (`request_id`),
  CONSTRAINT `swap_logistics_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `swap_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `swap_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `swap_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requester_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `requester_id` (`requester_id`),
  KEY `book_id` (`book_id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `swap_requests_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`),
  CONSTRAINT `swap_requests_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `book_swaps` (`id`),
  CONSTRAINT `swap_requests_ibfk_3` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `user_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `is_favorite` tinyint(1) DEFAULT 0,
  `is_bookmarked` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_book` (`user_id`,`book_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `user_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_favorites_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `firstname` varchar(50) DEFAULT NULL,
  `middlename` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `usertype` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city_state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `payout_provider` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL COMMENT 'User shipping address',
  `city` varchar(100) DEFAULT NULL COMMENT 'User city',
  `pickup_enabled` tinyint(1) DEFAULT 0 COMMENT 'Whether seller allows pickup (1) or not (0)',
  `pickup_locations` text DEFAULT NULL COMMENT 'JSON array of available pickup locations and times',
  `login_count` int(11) DEFAULT 0,
  `google2fa_secret` varchar(255) DEFAULT NULL,
  `is_2fa_enabled` tinyint(1) DEFAULT 0,
  `auth_provider` varchar(50) DEFAULT 'local',
  `google_oauth_id` varchar(100) DEFAULT NULL,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `payout_name` varchar(255) DEFAULT NULL,
  `payout_number` varchar(20) DEFAULT NULL,
  `payout_qr_code` varchar(255) DEFAULT NULL,
  `id_verified_status` enum('unverified','pending','verified') DEFAULT 'unverified',
  `id_image_path` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `lockout_until` datetime DEFAULT NULL,
  `recovery_codes` text DEFAULT NULL,
  `wallet_balance` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `wallet_withdrawals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wallet_withdrawals` (
  `withdrawal_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','rejected') DEFAULT 'pending',
  `payout_provider` varchar(50) DEFAULT NULL,
  `payout_account` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `request_date` datetime DEFAULT current_timestamp(),
  `process_date` datetime DEFAULT NULL,
  PRIMARY KEY (`withdrawal_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Autonomous MariaDB Intrusion Detection Triggers
-- --------------------------------------------------------

DELIMITER ;;
DROP TRIGGER IF EXISTS `trg_audit_book_update`;;
CREATE TRIGGER `trg_audit_book_update` AFTER UPDATE ON `books` FOR EACH ROW BEGIN
    IF (OLD.price != NEW.price OR OLD.rent_price != NEW.rent_price) THEN
        IF (@app_authorized IS NULL OR @app_authorized != 1) THEN
            INSERT INTO audit_logs (user_id, action, activity, details, ip_address)
            VALUES (
                COALESCE(@app_user_id, 0),
                'RISK',
                'RISK: DIRECT_PRICE_TAMPERING',
                CONCAT('CRITICAL ALERT: Unauthorized direct database price modification detected! Book ID #', NEW.book_id, ' (', SUBSTRING(NEW.title, 1, 30), ') price changed from ₱', FORMAT(OLD.price, 2), ' to ₱', FORMAT(NEW.price, 2), ' (Rent: ₱', FORMAT(OLD.rent_price, 2), ' -> ₱', FORMAT(NEW.rent_price, 2), ') by DB user [', CURRENT_USER(), '] without active web authorization.'),
                '127.0.0.1 (Direct DB)'
            );
        END IF;
    END IF;
END ;;

DROP TRIGGER IF EXISTS `trg_audit_book_delete`;;
CREATE TRIGGER `trg_audit_book_delete` BEFORE DELETE ON `books` FOR EACH ROW BEGIN
    IF (@app_authorized IS NULL OR @app_authorized != 1) THEN
        INSERT INTO audit_logs (user_id, action, activity, details, ip_address)
        VALUES (
            COALESCE(@app_user_id, 0),
            'RISK',
            'RISK: DIRECT_BOOK_DELETION',
            CONCAT('CRITICAL ALERT: Unauthorized direct database deletion! Book ID #', OLD.book_id, ' (', SUBSTRING(OLD.title, 1, 30), ') deleted by DB user [', CURRENT_USER(), '] without active web authorization.'),
            '127.0.0.1 (Direct DB)'
        );
    END IF;
END ;;

DROP TRIGGER IF EXISTS `trg_audit_user_update`;;
CREATE TRIGGER `trg_audit_user_update` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
    IF (OLD.wallet_balance != NEW.wallet_balance) THEN
        IF (@app_authorized IS NULL OR @app_authorized != 1) THEN
            INSERT INTO audit_logs (user_id, action, activity, details, ip_address)
            VALUES (
                NEW.id,
                'RISK',
                'RISK: DIRECT_WALLET_TAMPERING',
                CONCAT('CRITICAL ALERT: Direct wallet balance modification in database! User #', NEW.id, ' (', NEW.email, ') balance altered from ₱', FORMAT(OLD.wallet_balance, 2), ' to ₱', FORMAT(NEW.wallet_balance, 2), ' by DB user [', CURRENT_USER(), '].'),
                '127.0.0.1 (Direct DB)'
            );
        END IF;
    END IF;
    IF (OLD.usertype != NEW.usertype) THEN
        IF (@app_authorized IS NULL OR @app_authorized != 1) THEN
            INSERT INTO audit_logs (user_id, action, activity, details, ip_address)
            VALUES (
                NEW.id,
                'RISK',
                'RISK: PRIVILEGE_ESCALATION',
                CONCAT('CRITICAL ALERT: User role escalated directly in database! User #', NEW.id, ' (', NEW.email, ') changed from [', OLD.usertype, '] to [', NEW.usertype, '] by DB user [', CURRENT_USER(), '].'),
                '127.0.0.1 (Direct DB)'
            );
        END IF;
    END IF;
END ;;
DELIMITER ;

-- --------------------------------------------------------
-- Clean Baseline Seed Data for Testing & Evaluation
-- --------------------------------------------------------

INSERT INTO `admin` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$1JodKVjSPuMw3kHbolKvx.KBZtfH1EIMSLP5gpm0tQAIESav8xTIy', '2026-09-18 12:00:00');

INSERT INTO `users` (`id`, `email`, `password`, `username`, `created_at`, `updated_at`, `firstname`, `lastname`, `usertype`, `is_verified`, `is_2fa_enabled`, `auth_provider`, `status`, `wallet_balance`) VALUES
(1, 'seller@bookwagon.com', '$2y$10$1JodKVjSPuMw3kHbolKvx.KBZtfH1EIMSLP5gpm0tQAIESav8xTIy', 'seller_demo', '2026-09-18 12:00:00', '2026-09-18 12:00:00', 'BookWagon', 'Seller', 'seller', 1, 0, 'local', 'active', 0.00),
(2, 'user@bookwagon.com', '$2y$10$1JodKVjSPuMw3kHbolKvx.KBZtfH1EIMSLP5gpm0tQAIESav8xTIy', 'user_demo', '2026-09-18 12:00:00', '2026-09-18 12:00:00', 'BookWagon', 'Student', 'user', 1, 0, 'local', 'active', 2500.00);

INSERT INTO `sellers` (`id`, `user_id`, `shop_name`, `business_name`, `first_name`, `last_name`, `address`, `business_email`, `business_phone`, `status`, `created_at`) VALUES
(1, 1, 'BookWagon Official Store', 'BookWagon Official Store', 'BookWagon', 'Seller', 'University Campus / Manila', 'seller@bookwagon.com', '09123456789', 'approved', '2026-09-18 12:00:00');

INSERT INTO `forum_categories` (`category_id`, `name`, `description`, `color`, `icon`, `post_count`, `is_active`) VALUES
(1, 'General Discussion', 'Talk about all things books, literature, and reading habits.', '#3b82f6', 'fa-comments', 0, 1),
(2, 'Textbook Exchange & Rental', 'Discussions and requests for campus textbooks and course references.', '#10b981', 'fa-book', 0, 1),
(3, 'Book Reviews & Recommendations', 'Share your thoughts and reviews on recent books you have read.', '#f59e0b', 'fa-star', 0, 1),
(4, 'Book Swaps & Barter', 'Coordinate book swaps and barter trades with fellow students.', '#8b5cf6', 'fa-handshake', 0, 1);

INSERT INTO `books` (`book_id`, `user_id`, `title`, `author`, `genre`, `price`, `rent_price`, `stock`, `description`, `condition`, `listing_type`, `security_deposit`, `approval_status`, `meetup_location`) VALUES
(1, 1, 'Clean Code: A Handbook of Agile Software Craftsmanship', 'Robert C. Martin', 'Computer Science', 650.00, 75.00, 5, 'Even bad code can function. But if code is not clean, it can bring a development organization to its knees.', 'Like New', 'both', 300.00, 'approved', 'Main Library Campus'),
(2, 1, 'The Pragmatic Programmer', 'David Thomas, Andrew Hunt', 'Computer Science', 700.00, 80.00, 3, 'Straight from the programming trenches, The Pragmatic Programmer cuts through the increasing specialization.', 'Very Good', 'both', 350.00, 'approved', 'Main Library Campus'),
(3, 1, 'Introduction to Algorithms (CLRS)', 'Thomas H. Cormen', 'Academic', 1200.00, 120.00, 2, 'Comprehensive textbook on algorithms covering a broad range of data structures in depth.', 'Good', 'both', 500.00, 'approved', 'Engineering Building'),
(4, 1, 'To Kill a Mockingbird', 'Harper Lee', 'Classic Literature', 350.00, 45.00, 4, 'The unforgettable novel of a childhood in a sleepy Southern town and the crisis of conscience that rocked it.', 'Like New', 'both', 150.00, 'approved', 'Student Center Lounge'),
(5, 1, 'Atomic Habits', 'James Clear', 'Self-Help', 480.00, 50.00, 6, 'An easy & proven way to build good habits & break bad ones.', 'New', 'both', 200.00, 'approved', 'University Cafeteria');

SET FOREIGN_KEY_CHECKS=1;
