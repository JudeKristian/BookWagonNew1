-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: bookwagon_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `bookwagon_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `bookwagon_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `bookwagon_db`;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'admin','$2y$10$1JodKVjSPuMw3kHbolKvx.KBZtfH1EIMSLP5gpm0tQAIESav8xTIy','2025-04-20 02:52:27');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=411 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,0,'','Failed login attempt for email: dripblitz29@gmail.com','',0,NULL,NULL,NULL,'2026-08-30 02:27:45','Failed Login'),(2,35,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-08-30 02:27:55','Google Registration'),(3,35,NULL,'User successfully setup 2FA.',NULL,0,NULL,NULL,NULL,'2026-08-30 02:28:22','2FA Setup'),(4,35,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-08-30 02:29:04','Logout'),(5,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-08-30 02:29:19','Google Login'),(6,35,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-08-30 02:31:41','Logout'),(7,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-08-30 02:31:56','Google Login'),(8,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-08-30 02:32:05','Google Login'),(9,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-08-30 02:32:11','2FA Login'),(10,35,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-08-30 02:32:17','Logout'),(11,0,NULL,'Failed login attempt for email: dripblitz29@gmail.com',NULL,0,NULL,NULL,NULL,'2026-08-30 02:39:36','Failed Login'),(12,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-08-30 02:39:43','Google Login'),(13,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-08-30 02:39:48','2FA Login'),(14,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-08-30 03:50:23','Admin Login'),(15,1,NULL,'Admin logged out.',NULL,0,NULL,NULL,NULL,'2026-08-30 03:50:29','Admin Logout'),(16,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-08-30 03:51:45','Admin Login'),(17,1,NULL,'Admin logged out.',NULL,0,NULL,NULL,NULL,'2026-08-30 04:05:26','Admin Logout'),(18,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-08-30 04:05:31','Admin Login'),(19,36,NULL,'New user registered.',NULL,0,NULL,NULL,NULL,'2026-08-30 04:21:23','Registration'),(20,36,NULL,'User successfully setup 2FA.',NULL,0,NULL,NULL,NULL,'2026-08-30 04:21:49','2FA Setup'),(21,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-08-30 13:11:45','Admin Login'),(22,36,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-08-31 09:57:18','2FA Login'),(23,36,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-08-31 10:50:00','Logout'),(24,36,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-08-31 11:02:50','Logout'),(25,0,NULL,'Failed login attempt for email: dripblitz29@gmail.com',NULL,0,NULL,NULL,NULL,'2026-08-31 11:03:10','Failed Login'),(26,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-08-31 11:03:30','Google Login'),(27,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-08-31 11:03:41','2FA Login'),(28,35,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-08-31 11:04:22','Logout'),(29,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-08-31 11:04:29','Google Login'),(30,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-08-31 11:04:36','2FA Login'),(31,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-08-31 11:04:54','Admin Login'),(32,35,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-08-31 11:22:16','Logout'),(33,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-08-31 11:22:25','Google Login'),(34,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-08-31 11:22:33','2FA Login'),(35,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-08-31 11:22:42','Admin Login'),(36,35,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-08-31 12:10:21','Logout'),(37,0,NULL,'Failed login attempt for email: dripblitz29@gmail.com',NULL,0,NULL,NULL,NULL,'2026-08-31 12:10:28','Failed Login'),(38,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-08-31 12:10:34','Google Login'),(39,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-08-31 12:11:06','2FA Login'),(40,35,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-08-31 12:11:36','Logout'),(41,0,NULL,'Failed login attempt for email: judekristian08@gmail.com',NULL,0,NULL,NULL,NULL,'2026-08-31 12:11:49','Failed Login'),(42,36,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-08-31 12:12:04','2FA Login'),(43,36,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-08-31 12:17:44','Logout'),(44,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-08-31 12:17:53','Google Login'),(45,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-08-31 12:18:03','2FA Login'),(46,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-05 06:06:53','Admin Login'),(47,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-05 06:10:31','Google Login'),(48,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-05 06:10:41','2FA Login'),(49,0,NULL,'Failed login attempt for email: judekristian08@gmail.com',NULL,0,NULL,NULL,NULL,'2026-09-05 07:08:34','Failed Login'),(50,0,NULL,'Failed login attempt for email: judekristian08@gmail.com',NULL,0,NULL,NULL,NULL,'2026-09-05 07:09:39','Failed Login'),(51,36,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-05 07:10:03','2FA Login'),(52,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-05 08:23:49','Google Login'),(53,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-05 08:24:06','2FA Login'),(54,36,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-08 11:13:04','Logout'),(55,0,NULL,'Failed login attempt for email: dripblitz29@gmail.com',NULL,0,NULL,NULL,NULL,'2026-09-08 11:13:16','Failed Login'),(56,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-08 11:13:22','Google Login'),(57,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-08 11:14:17','2FA Login'),(58,36,NULL,'Placed Order #123 | Total: Γé▒286.00 | Payment: QRPH (Ref: QRPH-20260908-7D6A98)',NULL,0,NULL,NULL,NULL,'2026-09-08 11:50:49','Order Placed'),(59,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-10 06:07:03','Admin Login'),(60,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-10 06:20:02','Google Login'),(61,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-10 06:20:45','2FA Login'),(62,35,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-10 06:21:25','Logout'),(63,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-10 06:21:33','Google Login'),(64,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-10 06:23:16','2FA Login'),(65,36,NULL,'Placed Order #124 | Total: Γé▒77.00 | Payment: QRPH (Ref: QRPH-20260910-9D561F)',NULL,0,NULL,NULL,NULL,'2026-09-10 07:06:48','Order Placed'),(66,36,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-10 07:07:49','Logout'),(67,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-10 07:07:58','Google Login'),(68,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-10 07:08:17','2FA Login'),(69,36,NULL,'Placed Order #125 | Total: Γé▒286.00 | Payment: QRPH (Ref: QRPH-20260910-7E4697)',NULL,0,NULL,NULL,NULL,'2026-09-10 07:16:51','Order Placed'),(70,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-10 07:54:18','Google Login'),(71,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-10 07:54:31','2FA Login'),(72,36,NULL,'Placed Order #126 | Total: Γé▒286.00 | Payment: QRPH (Ref: QRPH-20260910-9F88AC)',NULL,0,NULL,NULL,NULL,'2026-09-10 07:58:06','Order Placed'),(73,35,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-10 08:00:45','Logout'),(74,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-10 08:00:55','Google Login'),(75,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-10 08:01:08','2FA Login'),(76,35,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-10 08:02:13','Logout'),(77,37,NULL,'New user registered.',NULL,0,NULL,NULL,NULL,'2026-09-10 08:02:58','Registration'),(78,37,NULL,'User successfully setup 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-10 08:03:39','2FA Setup'),(79,37,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-10 08:05:43','Logout'),(80,35,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-10 08:05:53','Google Login'),(81,35,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-10 08:06:03','2FA Login'),(82,36,NULL,'Placed Order #127 | Total: Γé▒286.00 | Payment: QRPH (Ref: QRPH-20260910-1CFFC4)',NULL,0,NULL,NULL,NULL,'2026-09-10 08:07:33','Order Placed'),(83,36,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-10 08:25:14','Logout'),(84,38,NULL,'New user registered and verification email sent.',NULL,0,NULL,NULL,NULL,'2026-09-13 13:35:27','Registration'),(85,38,NULL,'User successfully verified their email address.',NULL,0,NULL,NULL,NULL,'2026-09-13 13:36:27','Email Verified'),(86,0,NULL,'Failed login attempt for email: dumpydummy02@gmail.com',NULL,0,NULL,NULL,NULL,'2026-09-13 13:36:48','Failed Login'),(87,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-13 13:39:23','Admin Login'),(88,38,NULL,'User successfully setup Email OTP 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-13 13:41:52','2FA Setup'),(89,38,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-13 13:43:17','Logout'),(90,38,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-13 13:43:47','2FA Login'),(91,0,NULL,'Failed login attempt for email: dumpydummy02@gmail.com',NULL,0,NULL,NULL,NULL,'2026-09-13 13:46:01','Failed Login'),(92,38,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-13 13:46:23','2FA Login'),(93,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-13 13:51:47','Admin Login'),(94,38,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-13 13:52:21','2FA Setup'),(95,38,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-13 13:52:44','Logout'),(96,38,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-13 13:53:11','2FA Login'),(97,38,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-13 13:53:43','2FA Setup'),(98,38,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-13 13:54:05','Logout'),(99,38,NULL,'User successfully logged in using a Recovery Code.',NULL,0,NULL,NULL,NULL,'2026-09-13 13:54:44','2FA Login'),(100,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-13 13:54:52','Admin Login'),(101,38,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-13 14:38:13','2FA Login'),(102,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-13 14:56:35','Admin Login'),(103,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-14 02:23:59','Admin Login'),(104,38,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-14 02:24:56','2FA Login'),(105,38,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-14 02:25:13','Logout'),(106,39,NULL,'New user registered and verification email sent.',NULL,0,NULL,NULL,NULL,'2026-09-14 02:26:19','Registration'),(107,40,NULL,'New user registered.',NULL,0,NULL,NULL,NULL,'2026-09-14 02:38:12','Registration'),(108,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-14 02:40:22','Admin Login'),(109,40,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-14 02:44:28','2FA Setup'),(110,40,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-14 02:44:36','Logout'),(111,40,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-14 02:45:01','2FA Login'),(112,40,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-14 02:47:40','2FA Login'),(113,38,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-14 02:51:19','2FA Login'),(114,38,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-14 02:51:38','2FA Login'),(115,38,NULL,'Placed Order #128 | Total: Γé▒286.00 | Payment: QRPH (Ref: uploads/receipts/receipt_38_1789354357.jpg)',NULL,0,NULL,NULL,NULL,'2026-09-14 02:52:37','Order Placed'),(116,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-14 02:52:55','Admin Login'),(117,1,NULL,'Approved payment receipt for Order #128',NULL,0,NULL,NULL,NULL,'2026-09-14 03:10:10','Verified Payment'),(118,38,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-14 03:10:42','2FA Login'),(119,40,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-14 03:11:03','2FA Login'),(120,38,NULL,'Buyer confirmed receipt for Item #126 (Order #128)',NULL,0,NULL,NULL,NULL,'2026-09-14 03:11:56','QR Confirmed'),(121,38,NULL,'Buyer confirmed receipt for Item #126 (Order #128)',NULL,0,NULL,NULL,NULL,'2026-09-14 03:14:29','QR Confirmed'),(122,38,NULL,'Buyer confirmed receipt for Item #126 (Order #128)',NULL,0,NULL,NULL,NULL,'2026-09-14 03:15:29','QR Confirmed'),(123,38,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-14 03:33:34','2FA Login'),(124,40,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-14 03:33:58','2FA Login'),(125,38,NULL,'Buyer confirmed receipt for Item #126 (Order #128)',NULL,0,NULL,NULL,NULL,'2026-09-14 03:35:20','QR Confirmed'),(126,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-14 03:37:27','Admin Login'),(127,1,NULL,'Admin processed manual refund for cancelled Item #126 (Order #128). Notes: 123456789',NULL,0,NULL,NULL,NULL,'2026-09-14 03:51:46','MANUAL_REFUND_PROCESSED'),(128,1,NULL,'Admin approved KYC ID Verification for User ID: 38.',NULL,0,NULL,NULL,NULL,'2026-09-14 04:03:32','KYC_APPROVED'),(129,38,NULL,'Placed Order #129 | Total: Γé▒286.00 | Payment: QRPH (Ref: uploads/receipts/receipt_38_1789358630.png)',NULL,0,NULL,NULL,NULL,'2026-09-14 04:03:50','Order Placed'),(130,1,NULL,'Approved payment receipt for Order #129',NULL,0,NULL,NULL,NULL,'2026-09-14 04:04:11','Verified Payment'),(131,1,NULL,'Approved payment receipt for Order #129',NULL,0,NULL,NULL,NULL,'2026-09-14 04:23:28','Verified Payment'),(132,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-14 04:24:32','Admin Login'),(133,1,NULL,'Admin processed manual refund for cancelled Item #127 (Order #129). Notes: 12345678',NULL,0,NULL,NULL,NULL,'2026-09-14 04:24:53','MANUAL_REFUND_PROCESSED'),(134,1,NULL,'Admin rejected KYC ID Verification for User ID: 36. Reason: ID is blurry or unreadable',NULL,0,NULL,NULL,NULL,'2026-09-14 04:25:23','KYC_REJECTED'),(135,38,NULL,'Placed Order #130 | Total: Γé▒286.00 | Payment: QRPH (Ref: uploads/receipts/receipt_38_1789359992.png)',NULL,0,NULL,NULL,NULL,'2026-09-14 04:26:32','Order Placed'),(136,1,NULL,'Approved payment receipt for Order #130',NULL,0,NULL,NULL,NULL,'2026-09-14 04:26:46','Verified Payment'),(137,38,NULL,'Buyer logged condition check and confirmed receipt for Item #128.',NULL,0,NULL,NULL,NULL,'2026-09-14 04:39:29','Condition Logged'),(138,40,NULL,'Seller finalized handover for Item #128 (Order #130)',NULL,0,NULL,NULL,NULL,'2026-09-14 04:39:39','QR Confirmed'),(139,38,NULL,'Buyer successfully received Item #128 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-14 04:39:39','Order Received'),(140,38,NULL,'Buyer initiated return for Rental #55 via dropoff',NULL,0,NULL,NULL,NULL,'2026-09-14 04:56:20','Return Initiated'),(141,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-14 05:36:22','Admin Login'),(142,38,NULL,'Placed Order #131 | Total: Γé▒286.00 | Payment: QRPH (Ref: uploads/receipts/receipt_38_1789364587.png)',NULL,0,NULL,NULL,NULL,'2026-09-14 05:43:07','Order Placed'),(143,1,NULL,'Approved payment receipt for Order #131',NULL,0,NULL,NULL,NULL,'2026-09-14 05:43:24','Verified Payment'),(144,38,NULL,'Buyer logged condition check and confirmed receipt for Item #129.',NULL,0,NULL,NULL,NULL,'2026-09-14 05:54:07','Condition Logged'),(145,38,NULL,'Buyer logged condition check and confirmed receipt for Item #129.',NULL,0,NULL,NULL,NULL,'2026-09-14 05:54:24','Condition Logged'),(146,40,NULL,'Seller finalized handover for Item #129 (Order #131)',NULL,0,NULL,NULL,NULL,'2026-09-14 05:54:30','QR Confirmed'),(147,38,NULL,'Buyer successfully received Item #129 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-14 05:54:30','Order Received'),(148,38,NULL,'Buyer initiated return for Rental #56 via dropoff',NULL,0,NULL,NULL,NULL,'2026-09-14 05:58:58','Return Initiated'),(149,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-14 06:24:48','Admin Login'),(150,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-14 06:34:03','Admin Login'),(151,0,NULL,'Failed login attempt for email: judelarroza2003@gmail.com',NULL,0,NULL,NULL,NULL,'2026-09-14 06:38:44','Failed Login'),(152,38,NULL,'Placed Order #132 | Total: Γé▒286.00 | Payment: QRPH (Ref: uploads/receipts/receipt_38_1789369730.png)',NULL,0,NULL,NULL,NULL,'2026-09-14 07:08:50','Order Placed'),(153,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-14 07:09:07','Admin Login'),(154,1,NULL,'Approved payment receipt for Order #132',NULL,0,NULL,NULL,NULL,'2026-09-14 07:09:15','Verified Payment'),(155,1,NULL,'Approved payment receipt for Order #132',NULL,0,NULL,NULL,NULL,'2026-09-14 07:09:38','Verified Payment'),(156,38,NULL,'Buyer logged condition check and confirmed receipt for Item #130.',NULL,0,NULL,NULL,NULL,'2026-09-14 07:10:15','Condition Logged'),(157,40,NULL,'Seller finalized handover for Item #130 (Order #132)',NULL,0,NULL,NULL,NULL,'2026-09-14 07:10:25','QR Confirmed'),(158,38,NULL,'Buyer successfully received Item #130 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-14 07:10:25','Order Received'),(159,38,NULL,'Buyer initiated return for Rental #57 via dropoff',NULL,0,NULL,NULL,NULL,'2026-09-14 07:11:00','Return Initiated'),(160,1,NULL,'Admin confirmed clean return for Rental #57. 100% Escrow deposit (Γé▒250.00) credited to renter\'s wallet. Refund Notes: Done',NULL,0,NULL,NULL,NULL,'2026-09-14 07:18:46','ESCROW_CLEAN_RELEASE'),(161,1,NULL,'Admin confirmed clean return for Rental #57. 100% Escrow deposit (Γé▒250.00) credited to renter\'s wallet. Refund Notes: Done',NULL,0,NULL,NULL,NULL,'2026-09-14 07:19:51','ESCROW_CLEAN_RELEASE'),(162,38,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-14 07:27:48','Logout'),(163,41,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-14 07:28:22','Google Registration'),(164,41,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-14 07:28:38','2FA Setup'),(165,41,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-14 07:46:20','Google Login'),(166,41,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-14 07:46:28','2FA Setup'),(167,41,NULL,'Placed Order #133 | Total: Γé▒286.00 | Payment: QRPH (Ref: uploads/receipts/receipt_41_1789372022.png)',NULL,0,NULL,NULL,NULL,'2026-09-14 07:47:02','Order Placed'),(168,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-14 07:47:20','Admin Login'),(169,1,NULL,'Approved payment receipt for Order #133',NULL,0,NULL,NULL,NULL,'2026-09-14 07:47:35','Verified Payment'),(170,41,NULL,'Buyer logged condition check and confirmed receipt for Item #131.',NULL,0,NULL,NULL,NULL,'2026-09-14 07:48:09','Condition Logged'),(171,40,NULL,'Seller finalized handover for Item #131 (Order #133)',NULL,0,NULL,NULL,NULL,'2026-09-14 07:48:11','QR Confirmed'),(172,41,NULL,'Buyer successfully received Item #131 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-14 07:48:11','Order Received'),(173,41,NULL,'Buyer initiated return for Rental #58 via dropoff',NULL,0,NULL,NULL,NULL,'2026-09-14 07:48:36','Return Initiated'),(174,1,NULL,'Admin confirmed clean return for Rental #58. 100% Escrow deposit (Γé▒250.00) credited to renter\'s wallet. Refund Notes: yes',NULL,0,NULL,NULL,NULL,'2026-09-14 07:49:24','ESCROW_CLEAN_RELEASE'),(175,1,NULL,'Admin confirmed clean return for Rental #58. 100% Escrow deposit (Γé▒250.00) credited to renter\'s wallet. Refund Notes: yes',NULL,0,NULL,NULL,NULL,'2026-09-14 07:50:05','ESCROW_CLEAN_RELEASE'),(176,42,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-14 11:45:25','Google Registration'),(177,42,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-14 11:45:43','2FA Setup'),(178,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-14 11:46:28','Admin Login'),(179,42,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-14 11:48:16','Logout'),(180,42,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-14 12:27:50','Google Login'),(181,42,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-14 12:28:15','2FA Setup'),(182,42,NULL,'Placed Order #134 | Total: ₱286.00 | Payment: QRPH (Ref: uploads/receipts/receipt_42_1789389171.jpg)',NULL,0,NULL,NULL,NULL,'2026-09-14 12:32:51','Order Placed'),(183,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-14 12:33:20','Admin Login'),(184,1,NULL,'Approved payment receipt for Order #134',NULL,0,NULL,NULL,NULL,'2026-09-14 12:33:41','Verified Payment'),(185,42,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-14 15:45:57','Google Login'),(186,42,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-14 15:46:49','2FA Setup'),(187,42,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-14 16:27:09','Google Login'),(188,42,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-14 16:27:50','2FA Setup'),(189,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-14 16:35:19','Admin Login'),(190,42,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-14 16:39:04','Google Login'),(191,42,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-14 16:39:27','2FA Setup'),(192,42,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-14 17:13:27','Logout'),(193,43,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-14 17:14:33','Google Registration'),(194,43,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-14 17:15:27','2FA Setup'),(195,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-14 17:22:35','Admin Login'),(196,43,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-15 03:25:29','Google Login'),(197,43,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-15 03:26:01','2FA Setup'),(198,44,NULL,'New user registered and verification email sent.',NULL,0,NULL,NULL,NULL,'2026-09-15 11:14:18','Registration'),(199,44,NULL,'User successfully verified their email address.',NULL,0,NULL,NULL,NULL,'2026-09-15 11:14:52','Email Verified'),(200,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-15 11:15:33','Admin Login'),(201,1,NULL,'Admin rejected KYC ID Verification for User ID: 41. Reason: ID is blurry or unreadable',NULL,0,NULL,NULL,NULL,'2026-09-15 11:15:53','KYC_REJECTED'),(202,1,NULL,'Admin rejected KYC ID Verification for User ID: 42. Reason: ID is blurry or unreadable',NULL,0,NULL,NULL,NULL,'2026-09-15 11:15:56','KYC_REJECTED'),(203,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-15 11:40:32','Admin Login'),(204,40,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-15 11:41:16','Logout'),(205,45,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-15 11:41:33','Google Registration'),(206,45,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-15 11:42:07','2FA Setup'),(207,45,NULL,'Placed Order #135 | Total: ₱527.00 | Payment: QRPH (Ref: uploads/receipts/receipt_45_1789472598.jpg)',NULL,0,NULL,NULL,NULL,'2026-09-15 11:43:18','Order Placed'),(208,1,NULL,'Approved payment receipt for Order #135',NULL,0,NULL,NULL,NULL,'2026-09-15 11:44:16','Verified Payment'),(209,45,NULL,'Buyer logged condition check and confirmed receipt for Item #133.',NULL,0,NULL,NULL,NULL,'2026-09-15 11:44:50','Condition Logged'),(210,44,NULL,'Seller finalized handover for Item #133 (Order #135)',NULL,0,NULL,NULL,NULL,'2026-09-15 11:44:58','QR Confirmed'),(211,45,NULL,'Buyer successfully received Item #133 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-15 11:44:58','Order Received'),(212,45,NULL,'Buyer initiated return for Rental #59 via dropoff',NULL,0,NULL,NULL,NULL,'2026-09-15 11:45:25','Return Initiated'),(213,1,NULL,'Admin confirmed clean return for Rental #59. 100% Escrow deposit (₱500.00) credited to renter\'s wallet. Refund Notes: yes',NULL,0,NULL,NULL,NULL,'2026-09-15 11:46:10','ESCROW_CLEAN_RELEASE'),(214,1,NULL,'Admin confirmed clean return for Rental #59. 100% Escrow deposit (₱500.00) credited to renter\'s wallet. Refund Notes: yes',NULL,0,NULL,NULL,NULL,'2026-09-15 11:46:14','ESCROW_CLEAN_RELEASE'),(215,1,NULL,'Admin confirmed clean return for Rental #59. 100% Escrow deposit (₱500.00) credited to renter\'s wallet. Refund Notes: as',NULL,0,NULL,NULL,NULL,'2026-09-15 11:46:25','ESCROW_CLEAN_RELEASE'),(216,45,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-15 11:59:04','Logout'),(217,46,NULL,'New user registered and verification email sent.',NULL,0,NULL,NULL,NULL,'2026-09-15 11:59:51','Registration'),(218,46,NULL,'User successfully verified their email address.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:00:03','Email Verified'),(219,46,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:00:24','Logout'),(220,46,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:01:07','2FA Setup'),(221,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:05:31','Admin Login'),(222,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:16:43','Admin Login'),(223,1,NULL,'Admin approved KYC ID Verification for User ID: 45.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:31:40','KYC_APPROVED'),(224,47,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-15 12:32:27','Google Registration'),(225,47,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:32:49','2FA Setup'),(226,47,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-15 12:33:35','Google Login'),(227,47,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:33:52','2FA Setup'),(228,47,NULL,'Placed Order #136 | Total: ₱527.00 | Payment: QRPH (Ref: uploads/receipts/receipt_47_1789475696.jpg)',NULL,0,NULL,NULL,NULL,'2026-09-15 12:34:56','Order Placed'),(229,44,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:35:49','2FA Setup'),(230,1,NULL,'Approved payment receipt for Order #136',NULL,0,NULL,NULL,NULL,'2026-09-15 12:40:08','Verified Payment'),(231,47,NULL,'Buyer logged condition check and confirmed receipt for Item #134.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:40:42','Condition Logged'),(232,44,NULL,'Seller finalized handover for Item #134 (Order #136)',NULL,0,NULL,NULL,NULL,'2026-09-15 12:40:47','QR Confirmed'),(233,47,NULL,'Buyer successfully received Item #134 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-15 12:40:47','Order Received'),(234,47,NULL,'Buyer initiated return for Rental #60 via dropoff',NULL,0,NULL,NULL,NULL,'2026-09-15 12:41:02','Return Initiated'),(235,1,NULL,'Admin logged out.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:41:34','Admin Logout'),(236,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:41:37','Admin Login'),(237,44,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:43:02','2FA Login'),(238,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:44:38','Admin Login'),(239,1,NULL,'Admin approved KYC ID Verification for User ID: 47.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:44:45','KYC_APPROVED'),(240,0,NULL,'Failed login attempt for email: Jkyrax29@gmail.com',NULL,0,NULL,NULL,NULL,'2026-09-15 12:52:17','Failed Login'),(241,44,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:52:41','2FA Login'),(242,47,NULL,'Buyer logged condition check and confirmed receipt for Item #134.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:53:29','Condition Logged'),(243,44,NULL,'Seller finalized handover for Item #134 (Order #136)',NULL,0,NULL,NULL,NULL,'2026-09-15 12:53:32','QR Confirmed'),(244,47,NULL,'Buyer successfully received Item #134 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-15 12:53:32','Order Received'),(245,47,NULL,'Buyer initiated return for Rental #61 via dropoff',NULL,0,NULL,NULL,NULL,'2026-09-15 12:54:02','Return Initiated'),(246,47,NULL,'Placed Order #137 | Total: ₱682.00 | Payment: QRPH (Ref: uploads/receipts/receipt_47_1789476975.png)',NULL,0,NULL,NULL,NULL,'2026-09-15 12:56:15','Order Placed'),(247,1,NULL,'Approved payment receipt for Order #137',NULL,0,NULL,NULL,NULL,'2026-09-15 12:56:29','Verified Payment'),(248,47,NULL,'Buyer logged condition check and confirmed receipt for Item #135.',NULL,0,NULL,NULL,NULL,'2026-09-15 12:57:03','Condition Logged'),(249,44,NULL,'Seller finalized handover for Item #135 (Order #137)',NULL,0,NULL,NULL,NULL,'2026-09-15 12:57:07','QR Confirmed'),(250,47,NULL,'Buyer successfully received Item #135 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-15 12:57:07','Order Received'),(251,47,NULL,'Buyer initiated return for Rental #62 via dropoff',NULL,0,NULL,NULL,NULL,'2026-09-15 12:57:26','Return Initiated'),(252,1,NULL,'Admin logged out.',NULL,0,NULL,NULL,NULL,'2026-09-15 13:04:28','Admin Logout'),(253,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-15 13:04:35','Admin Login'),(254,47,NULL,'Placed Order #138 | Total: ₱286.00 | Payment: QRPH (Ref: uploads/receipts/receipt_47_1789477646.jpg)',NULL,0,NULL,NULL,NULL,'2026-09-15 13:07:26','Order Placed'),(255,1,NULL,'Approved payment receipt for Order #138',NULL,0,NULL,NULL,NULL,'2026-09-15 13:07:35','Verified Payment'),(256,1,NULL,'Approved payment receipt for Order #138',NULL,0,NULL,NULL,NULL,'2026-09-15 13:07:36','Verified Payment'),(257,44,NULL,'Seller finalized handover for Item #136 (Order #138)',NULL,0,NULL,NULL,NULL,'2026-09-15 13:07:52','QR Confirmed'),(258,47,NULL,'Buyer successfully received Item #136 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-15 13:07:52','Order Received'),(259,47,NULL,'Buyer logged condition check and confirmed receipt for Item #136.',NULL,0,NULL,NULL,NULL,'2026-09-15 13:07:57','Condition Logged'),(260,47,NULL,'Buyer initiated return for Rental #63 via dropoff',NULL,0,NULL,NULL,NULL,'2026-09-15 13:08:07','Return Initiated'),(261,1,NULL,'Admin confirmed clean return for Rental #63. 100% Escrow deposit (₱250.00) credited to renter\'s wallet. Refund Notes: YES',NULL,0,NULL,NULL,NULL,'2026-09-15 13:08:59','ESCROW_CLEAN_RELEASE'),(262,47,NULL,'Placed Order #141 | Total: ₱364.00 | Payment: QRPH (Ref: uploads/receipts/receipt_47_1789478042.png)',NULL,0,NULL,NULL,NULL,'2026-09-15 13:14:02','Order Placed'),(263,1,NULL,'Approved payment receipt for Order #141',NULL,0,NULL,NULL,NULL,'2026-09-15 13:14:09','Verified Payment'),(264,44,NULL,'Seller finalized handover for Item #137 (Order #141)',NULL,0,NULL,NULL,NULL,'2026-09-15 13:14:32','QR Confirmed'),(265,47,NULL,'Buyer successfully received Item #137 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-15 13:14:32','Order Received'),(266,47,NULL,'Buyer logged condition check and confirmed receipt for Item #137.',NULL,0,NULL,NULL,NULL,'2026-09-15 13:14:37','Condition Logged'),(267,1,NULL,'Approved payment receipt for Order #141',NULL,0,NULL,NULL,NULL,'2026-09-15 13:14:52','Verified Payment'),(268,47,NULL,'Placed Order #142 | Total: ₱286.00 | Payment: QRPH (Ref: uploads/receipts/receipt_47_1789478164.png)',NULL,0,NULL,NULL,NULL,'2026-09-15 13:16:04','Order Placed'),(269,1,NULL,'Approved payment receipt for Order #142',NULL,0,NULL,NULL,NULL,'2026-09-15 13:16:29','Verified Payment'),(270,47,NULL,'Buyer logged condition check and confirmed receipt for Item #138.',NULL,0,NULL,NULL,NULL,'2026-09-15 13:16:45','Condition Logged'),(271,44,NULL,'Seller finalized handover for Item #138 (Order #142)',NULL,0,NULL,NULL,NULL,'2026-09-15 13:16:48','QR Confirmed'),(272,47,NULL,'Buyer successfully received Item #138 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-15 13:16:48','Order Received'),(273,47,NULL,'Buyer initiated return for Rental #64 via dropoff',NULL,0,NULL,NULL,NULL,'2026-09-15 13:16:58','Return Initiated'),(274,1,NULL,'Admin approved damage settlement for Rental #64. Deposit: ₱250.00 -> Seller Awarded: ₱10.00 | Student Refund: ₱240.00 (Credited to Wallet). Notes: Damage verified via photographic inspection; penalty approved.',NULL,0,NULL,NULL,NULL,'2026-09-15 13:20:42','ESCROW_DAMAGE_SETTLED'),(275,47,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-15 13:29:02','Logout'),(276,0,NULL,'Failed login attempt for email: toshirovinz@gmail.com',NULL,0,NULL,NULL,NULL,'2026-09-15 13:59:59','Failed Login'),(277,47,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-15 14:09:16','Google Login'),(278,48,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-15 14:10:21','Google Registration'),(279,48,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-15 14:13:30','Google Login'),(280,48,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-15 14:16:40','2FA Setup'),(281,48,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-15 14:16:58','Logout'),(282,49,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-15 14:19:49','Google Registration'),(283,49,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-15 14:20:39','Google Login'),(284,49,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-15 14:20:54','2FA Setup'),(285,49,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-15 14:24:22','2FA Setup'),(286,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-15 14:25:07','Admin Login'),(287,49,NULL,'Suspended user ID: 48',NULL,0,NULL,NULL,NULL,'2026-09-15 14:27:24','User Suspended'),(288,49,NULL,'Permanently deleted user ID: 48',NULL,0,NULL,NULL,NULL,'2026-09-15 14:27:36','User Deleted'),(289,50,NULL,'New user registered and verification email sent.',NULL,0,NULL,NULL,NULL,'2026-09-15 14:27:56','Registration'),(290,50,NULL,'User successfully verified their email address.',NULL,0,NULL,NULL,NULL,'2026-09-15 14:28:18','Email Verified'),(291,50,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-15 14:29:35','2FA Setup'),(292,1,NULL,'Admin logged out.',NULL,0,NULL,NULL,NULL,'2026-09-15 14:48:24','Admin Logout'),(293,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-15 14:48:27','Admin Login'),(294,0,NULL,'Failed login attempt for email: zenzenyu0@gmail.com',NULL,0,NULL,NULL,NULL,'2026-09-15 14:49:10','Failed Login'),(295,50,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-15 14:49:41','2FA Login'),(296,49,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-15 14:55:46','Google Login'),(297,49,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-15 14:56:01','2FA Setup'),(298,49,NULL,'Placed Order #143 | Total: ₱343.50 | Payment: QRPH (Ref: uploads/receipts/receipt_49_1789484187.jpg)',NULL,0,NULL,NULL,NULL,'2026-09-15 14:56:27','Order Placed'),(299,1,NULL,'Approved payment receipt for Order #143',NULL,0,NULL,NULL,NULL,'2026-09-15 14:56:46','Verified Payment'),(300,1,NULL,'Admin approved KYC ID Verification for User ID: 49.',NULL,0,NULL,NULL,NULL,'2026-09-15 14:57:07','KYC_APPROVED'),(301,50,NULL,'Seller finalized handover for Item #139 (Order #143)',NULL,0,NULL,NULL,NULL,'2026-09-15 14:57:21','QR Confirmed'),(302,49,NULL,'Buyer successfully received Item #139 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-15 14:57:21','Order Received'),(303,49,NULL,'Buyer logged condition check and confirmed receipt for Item #139.',NULL,0,NULL,NULL,NULL,'2026-09-15 14:57:25','Condition Logged'),(304,49,NULL,'Buyer initiated return for Rental #65 via dropoff',NULL,0,NULL,NULL,NULL,'2026-09-15 14:57:40','Return Initiated'),(305,1,NULL,'Admin confirmed clean return for Rental #65. 100% Escrow deposit (₱330.00) credited to renter\'s wallet. Refund Notes: yes',NULL,0,NULL,NULL,NULL,'2026-09-15 14:58:17','ESCROW_CLEAN_RELEASE'),(306,0,NULL,'Failed login attempt for email: jkyrax29@gmail.com',NULL,0,NULL,NULL,NULL,'2026-09-15 15:02:33','Failed Login'),(307,0,NULL,'Failed login attempt for email: jkyrax29@gmail.com',NULL,0,NULL,NULL,NULL,'2026-09-15 15:02:55','Failed Login'),(308,0,NULL,'Failed login attempt for email: jkyrax29@gmail.com',NULL,0,NULL,NULL,NULL,'2026-09-15 15:03:02','Failed Login'),(309,0,NULL,'Failed login attempt for email: jkyrax29@gmail.com',NULL,0,NULL,NULL,NULL,'2026-09-15 15:03:08','Failed Login'),(310,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-15 18:13:55','Admin Login'),(311,50,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-15 18:14:21','Google Login'),(312,44,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-15 18:16:52','2FA Login'),(313,46,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-15 18:17:58','Google Login'),(314,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-15 18:19:05','Admin Login'),(315,0,NULL,'Suspended user ID: 46',NULL,0,NULL,NULL,NULL,'2026-09-15 18:19:11','User Suspended'),(316,44,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-15 18:20:11','Logout'),(317,0,NULL,'Failed login attempt for email: toshirovinz@gmail.com',NULL,0,NULL,NULL,NULL,'2026-09-15 18:20:26','Failed Login'),(318,47,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-15 18:20:31','Google Login'),(319,47,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-15 18:20:43','2FA Setup'),(320,47,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-15 18:21:14','Logout'),(321,44,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-15 18:21:44','2FA Login'),(322,44,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-15 18:21:55','Logout'),(323,1,NULL,'Admin logged out.',NULL,0,NULL,NULL,NULL,'2026-09-15 18:47:13','Admin Logout'),(324,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-15 18:47:19','Admin Login'),(325,44,NULL,'User successfully passed 2FA.',NULL,0,NULL,NULL,NULL,'2026-09-15 18:49:01','2FA Login'),(326,0,NULL,'Failed login attempt for email: walawalawala2022@gmail.com',NULL,0,NULL,NULL,NULL,'2026-09-15 18:50:33','Failed Login'),(327,45,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-15 18:50:39','Google Login'),(328,45,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-15 18:50:53','2FA Setup'),(329,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-15 18:51:43','Admin Login'),(330,45,NULL,'Placed Order #144 | Total: ₱286.00 | Payment: QRPH (Ref: uploads/receipts/receipt_45_1789498357.jpg)',NULL,0,NULL,NULL,NULL,'2026-09-15 18:52:37','Order Placed'),(331,1,NULL,'Approved payment receipt for Order #144',NULL,0,NULL,NULL,NULL,'2026-09-15 18:52:42','Verified Payment'),(332,45,NULL,'Buyer logged condition check and confirmed receipt for Item #140.',NULL,0,NULL,NULL,NULL,'2026-09-15 18:53:14','Condition Logged'),(333,44,NULL,'Seller finalized handover for Item #140 (Order #144)',NULL,0,NULL,NULL,NULL,'2026-09-15 18:53:17','QR Confirmed'),(334,45,NULL,'Buyer successfully received Item #140 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-15 18:53:17','Order Received'),(335,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-16 04:10:20','Admin Login'),(336,51,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-16 04:11:49','Google Registration'),(337,51,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-16 04:12:33','2FA Setup'),(338,52,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-16 04:14:08','Google Registration'),(339,52,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-16 04:14:30','2FA Setup'),(340,51,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-16 04:19:07','Logout'),(341,53,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-16 04:19:39','Google Registration'),(342,51,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-16 04:19:59','Google Login'),(343,52,NULL,'User logged out.',NULL,0,NULL,NULL,NULL,'2026-09-16 04:20:29','Logout'),(344,52,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-16 04:20:58','Google Login'),(345,52,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-16 04:21:13','2FA Setup'),(346,0,NULL,'Suspended user ID: 51',NULL,0,NULL,NULL,NULL,'2026-09-16 04:21:30','User Suspended'),(347,0,NULL,'Permanently deleted user ID: 51',NULL,0,NULL,NULL,NULL,'2026-09-16 04:21:35','User Deleted'),(348,54,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-16 04:21:45','Google Registration'),(349,55,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-16 04:23:04','Google Registration'),(350,56,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-16 04:24:50','Google Registration'),(351,0,NULL,'Permanently deleted user ID: 51',NULL,0,NULL,NULL,NULL,'2026-09-16 04:26:15','User Deleted'),(352,0,NULL,'Permanently deleted user ID: 56',NULL,0,NULL,NULL,NULL,'2026-09-16 04:26:23','User Deleted'),(353,0,NULL,'Permanently deleted user ID: 56',NULL,0,NULL,NULL,NULL,'2026-09-16 04:26:28','User Deleted'),(354,0,NULL,'Permanently deleted user ID: 56',NULL,0,NULL,NULL,NULL,'2026-09-16 04:26:30','User Deleted'),(355,0,NULL,'Permanently deleted user ID: 55',NULL,0,NULL,NULL,NULL,'2026-09-16 04:26:35','User Deleted'),(356,0,NULL,'Permanently deleted user ID: 54',NULL,0,NULL,NULL,NULL,'2026-09-16 04:26:40','User Deleted'),(357,57,NULL,'New user registered and verification email sent.',NULL,0,NULL,NULL,NULL,'2026-09-16 04:27:29','Registration'),(358,57,NULL,'User successfully verified their email address.',NULL,0,NULL,NULL,NULL,'2026-09-16 04:27:51','Email Verified'),(359,57,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-16 04:28:19','2FA Setup'),(360,57,NULL,'Permanently deleted user ID: 54',NULL,0,NULL,NULL,NULL,'2026-09-16 04:28:27','User Deleted'),(361,58,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-16 04:28:45','Google Registration'),(362,59,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-16 04:29:21','Google Registration'),(363,57,NULL,'Permanently deleted user ID: 54',NULL,0,NULL,NULL,NULL,'2026-09-16 04:34:11','User Deleted'),(364,57,NULL,'Permanently deleted user ID: 59',NULL,0,NULL,NULL,NULL,'2026-09-16 04:34:31','User Deleted'),(365,57,NULL,'Permanently deleted user ID: 59',NULL,0,NULL,NULL,NULL,'2026-09-16 04:34:39','User Deleted'),(366,60,NULL,'User automatically registered via Google',NULL,0,NULL,NULL,NULL,'2026-09-16 04:35:23','Google Registration'),(367,60,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-16 04:35:37','2FA Setup'),(368,52,NULL,'User initiated Google login',NULL,0,NULL,NULL,NULL,'2026-09-16 04:40:15','Google Login'),(369,52,NULL,'User successfully setup Email OTP 2FA with Recovery Codes.',NULL,0,NULL,NULL,NULL,'2026-09-16 04:40:27','2FA Setup'),(370,60,NULL,'Placed Order #145 | Total: ₱3,162.00 | Payment: QRPH (Ref: uploads/receipts/receipt_60_1789533768.jpg)',NULL,0,NULL,NULL,NULL,'2026-09-16 04:42:48','Order Placed'),(371,1,NULL,'Approved payment receipt for Order #145',NULL,0,NULL,NULL,NULL,'2026-09-16 04:42:58','Verified Payment'),(372,60,NULL,'Buyer logged condition check and confirmed receipt for Item #141.',NULL,0,NULL,NULL,NULL,'2026-09-16 04:44:31','Condition Logged'),(373,52,NULL,'Seller finalized handover for Item #141 (Order #145)',NULL,0,NULL,NULL,NULL,'2026-09-16 04:44:41','QR Confirmed'),(374,60,NULL,'Buyer successfully received Item #141 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-16 04:44:41','Order Received'),(375,60,NULL,'Buyer initiated return for Rental #67 via dropoff',NULL,0,NULL,NULL,NULL,'2026-09-16 04:46:17','Return Initiated'),(376,1,NULL,'Admin confirmed clean return for Rental #67. 100% Escrow deposit (₱3,000.00) credited to renter\'s wallet. Refund Notes: YES',NULL,0,NULL,NULL,NULL,'2026-09-16 04:47:40','ESCROW_CLEAN_RELEASE'),(377,1,NULL,'Admin approved KYC ID Verification for User ID: 60.',NULL,0,NULL,NULL,NULL,'2026-09-16 05:00:13','KYC_APPROVED'),(378,60,NULL,'Placed Order #146 | Total: ₱3,013.50 | Payment: QRPH (Ref: uploads/receipts/receipt_60_1789534830.jpg)',NULL,0,NULL,NULL,NULL,'2026-09-16 05:00:30','Order Placed'),(379,52,NULL,'Seller finalized handover for Item #142 (Order #146)',NULL,0,NULL,NULL,NULL,'2026-09-16 05:00:44','QR Confirmed'),(380,60,NULL,'Buyer successfully received Item #142 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-16 05:00:44','Order Received'),(381,60,NULL,'Buyer initiated return for Rental #68 via dropoff',NULL,0,NULL,NULL,NULL,'2026-09-16 05:01:01','Return Initiated'),(382,1,NULL,'Approved payment receipt for Order #146',NULL,0,NULL,NULL,NULL,'2026-09-16 05:02:07','Verified Payment'),(383,1,NULL,'Approved payment receipt for Order #146',NULL,0,NULL,NULL,NULL,'2026-09-16 05:02:36','Verified Payment'),(384,1,NULL,'Admin approved damage settlement for Rental #68. Deposit: ₱3,000.00 -> Seller Awarded: ₱100.00 | Student Refund: ₱2,900.00 (Credited to Wallet). Notes: Damage verified via photographic inspection; penalty approved.',NULL,0,NULL,NULL,NULL,'2026-09-16 05:02:51','ESCROW_DAMAGE_SETTLED'),(385,52,NULL,'Seller finalized handover for Item #142 (Order #146)',NULL,0,NULL,NULL,NULL,'2026-09-16 05:03:50','QR Confirmed'),(386,60,NULL,'Buyer successfully received Item #142 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-16 05:03:50','Order Received'),(387,60,NULL,'Placed Order #147 | Total: ₱3,013.50 | Payment: QRPH (Ref: uploads/receipts/receipt_60_1789535098.png)',NULL,0,NULL,NULL,NULL,'2026-09-16 05:04:58','Order Placed'),(388,1,NULL,'Approved payment receipt for Order #147',NULL,0,NULL,NULL,NULL,'2026-09-16 05:05:15','Verified Payment'),(389,52,NULL,'Seller finalized handover for Item #143 (Order #147)',NULL,0,NULL,NULL,NULL,'2026-09-16 05:05:22','QR Confirmed'),(390,60,NULL,'Buyer successfully received Item #143 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-16 05:05:22','Order Received'),(391,52,NULL,'Seller finalized handover for Item #143 (Order #147)',NULL,0,NULL,NULL,NULL,'2026-09-16 05:05:41','QR Confirmed'),(392,60,NULL,'Buyer successfully received Item #143 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-16 05:05:41','Order Received'),(393,60,NULL,'Buyer logged condition check and confirmed receipt for Item #143.',NULL,0,NULL,NULL,NULL,'2026-09-16 05:06:01','Condition Logged'),(394,52,NULL,'Seller finalized handover for Item #143 (Order #147)',NULL,0,NULL,NULL,NULL,'2026-09-16 05:06:12','QR Confirmed'),(395,60,NULL,'Buyer successfully received Item #143 from Seller',NULL,0,NULL,NULL,NULL,'2026-09-16 05:06:12','Order Received'),(396,60,NULL,'Buyer initiated return for Rental #72 via dropoff',NULL,0,NULL,NULL,NULL,'2026-09-16 05:06:24','Return Initiated'),(397,60,NULL,'Buyer initiated return for Rental #70 via dropoff',NULL,0,NULL,NULL,NULL,'2026-09-16 05:07:13','Return Initiated'),(398,1,NULL,'Approved payment receipt for Order #147',NULL,0,NULL,NULL,NULL,'2026-09-16 05:07:44','Verified Payment'),(399,1,NULL,'Admin confirmed clean return for Rental #72. 100% Escrow deposit (₱3,000.00) credited to renter\'s wallet. Refund Notes: yes',NULL,0,NULL,NULL,NULL,'2026-09-16 05:07:51','ESCROW_CLEAN_RELEASE'),(400,1,NULL,'Admin confirmed clean return for Rental #70. 100% Escrow deposit (₱3,000.00) credited to renter\'s wallet. Refund Notes: yes',NULL,0,NULL,NULL,NULL,'2026-09-16 05:07:53','ESCROW_CLEAN_RELEASE'),(401,1,NULL,'Admin confirmed clean return for Rental #66. 100% Escrow deposit (₱250.00) credited to renter\'s wallet. Refund Notes: yes',NULL,0,NULL,NULL,NULL,'2026-09-16 05:07:56','ESCROW_CLEAN_RELEASE'),(402,1,NULL,'Admin successfully logged in.',NULL,0,NULL,NULL,NULL,'2026-09-16 05:28:41','Admin Login'),(403,1,'Admin Login','Admin successfully logged in.','::1',0,NULL,NULL,NULL,'2026-09-17 12:19:42','Admin Login'),(404,1,'Admin Logout','Admin logged out.','::1',0,NULL,NULL,NULL,'2026-09-17 12:21:38','Admin Logout'),(405,1,'Admin Login','Admin successfully logged in.','::1',0,NULL,NULL,NULL,'2026-09-18 08:08:27','Admin Login'),(406,1,'Admin Logout','Admin logged out.','::1',0,NULL,NULL,NULL,'2026-09-18 10:22:16','Admin Logout'),(407,1,'Admin Login','Admin successfully logged in.','::1',0,NULL,NULL,NULL,'2026-09-18 10:22:19','Admin Login'),(408,0,'RISK','CRITICAL ALERT: Unauthorized direct database price modification detected! Book ID #32 (Learning Piano The easy way) price changed from ₱150.00 to ₱450.00 (Rent: ₱18.00 -> ₱18.00) by DB user [root@localhost] without active web authorization.','127.0.0.1 (Direct DB)',0,NULL,NULL,NULL,'2026-09-18 10:32:42','RISK: DIRECT_PRICE_TAMPERING'),(409,1,'Admin Logout','Admin logged out.','::1',0,NULL,NULL,NULL,'2026-09-18 10:50:31','Admin Logout'),(410,1,'Admin Login','Admin successfully logged in.','::1',0,NULL,NULL,NULL,'2026-09-18 10:50:34','Admin Login');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_accounts`
--

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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_accounts`
--

LOCK TABLES `bank_accounts` WRITE;
/*!40000 ALTER TABLE `bank_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_buddies`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_buddies`
--

LOCK TABLES `book_buddies` WRITE;
/*!40000 ALTER TABLE `book_buddies` DISABLE KEYS */;
INSERT INTO `book_buddies` VALUES (1,3,1,'accepted','2025-05-12 15:27:45','2025-05-12 15:27:57');
/*!40000 ALTER TABLE `book_buddies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_collections`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_collections`
--

LOCK TABLES `book_collections` WRITE;
/*!40000 ALTER TABLE `book_collections` DISABLE KEYS */;
INSERT INTO `book_collections` VALUES (1,3,'X-men','Stan Lee','done_reading','Need to read that next episode ASAP!','uploads/book_images/book_6822055195b2f.jpg','2025-05-12 14:27:29','2025-05-12 14:27:29');
/*!40000 ALTER TABLE `book_collections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_images`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_images`
--

LOCK TABLES `book_images` WRITE;
/*!40000 ALTER TABLE `book_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `book_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_rentals`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_rentals`
--

LOCK TABLES `book_rentals` WRITE;
/*!40000 ALTER TABLE `book_rentals` DISABLE KEYS */;
INSERT INTO `book_rentals` VALUES (45,33,41,2,'2025-07-26 02:26:36','2025-08-02 02:26:36','2025-07-26 08:27:51','2025-07-26 08:26:55',1,'returned',15.00,'2025-07-26 00:26:36',120,0.00,0,0,'fair',NULL,NULL,0,NULL,NULL,'none'),(54,36,50,3,'2026-09-10 16:15:14','2026-09-17 16:15:14','2026-09-10 16:16:28','2026-09-10 16:15:52',1,'returned',36.00,'2026-09-10 08:15:14',127,0.00,0,0,'good',NULL,NULL,0,NULL,NULL,'none'),(55,38,51,5,'2026-09-14 12:39:39','2026-09-21 12:39:39','2026-09-14 13:36:13','2026-09-14 12:56:20',1,'returned',36.00,'2026-09-14 04:39:39',130,0.00,0,0,'good',NULL,'52b6b84221863f4721cf3938fdd32150',0,NULL,NULL,'none'),(56,38,51,5,'2026-09-14 13:54:30','2026-09-21 13:54:30','2026-09-14 13:59:25','2026-09-14 13:58:58',1,'returned',36.00,'2026-09-14 05:54:30',131,0.00,0,0,'good',NULL,'a03af5002fc3e3429bf4ba364328b039',0,NULL,NULL,'none'),(57,38,51,5,'2026-09-14 15:10:25','2026-09-21 15:10:25','2026-09-14 15:19:51','2026-09-14 15:11:00',1,'returned',36.00,'2026-09-14 07:10:25',132,0.00,0,0,'excellent',NULL,'6c04703c7dc387ce95f38fea1f10409e',0,NULL,NULL,'none'),(58,41,51,5,'2026-09-14 15:48:11','2026-09-21 15:48:11','2026-09-14 15:50:05','2026-09-14 15:48:36',1,'returned',36.00,'2026-09-14 07:48:11',133,0.00,0,0,'excellent',NULL,'a6024e5dfa2598dbbe79fe2b9b32339d',0,NULL,NULL,'none'),(59,45,52,8,'2026-09-15 19:44:58','2026-09-22 19:44:58','2026-09-15 19:46:25','2026-09-15 19:45:25',1,'returned',27.00,'2026-09-15 11:44:58',135,0.00,0,0,'excellent',NULL,'6b3342d28a491db366800d38ba332d4b',0,NULL,NULL,'none'),(60,47,52,8,'2026-09-15 20:40:47','2026-09-22 20:40:47','2026-09-15 20:41:17','2026-09-15 20:41:02',1,'returned',27.00,'2026-09-15 12:40:47',136,0.00,0,0,'excellent',NULL,'9a29ef7c4944dc4ea7d24841b70405da',0,NULL,NULL,'none'),(61,47,52,8,'2026-09-15 20:53:32','2026-09-22 20:53:32','2026-09-15 20:54:22','2026-09-15 20:54:02',1,'returned',27.00,'2026-09-15 12:53:32',136,0.00,0,0,'excellent','','2aa85f97d65bcddb777d99438287634f',0,NULL,NULL,'none'),(62,47,53,8,'2026-09-15 20:57:07','2026-12-08 20:57:07','2026-09-15 20:57:39','2026-09-15 20:57:26',12,'returned',432.00,'2026-09-15 12:57:07',137,0.00,0,0,'good',NULL,'8eca39cee9417c36558b24196c3c6cdf',0,NULL,NULL,'none'),(63,47,53,8,'2026-09-15 21:07:52','2026-09-22 21:07:52','2026-09-15 21:08:59','2026-09-15 21:08:07',1,'returned',36.00,'2026-09-15 13:07:52',138,0.00,0,0,'excellent',NULL,'52e0311ff5948469490a95b487c12dc8',0,NULL,NULL,'none'),(64,47,53,8,'2026-09-15 21:16:48','2026-09-22 21:16:48','2026-09-15 21:20:42','2026-09-15 21:16:58',1,'returned',36.00,'2026-09-15 13:16:48',142,0.00,0,0,'good',NULL,'cbe1e5f557f919389bc458239821bc82',0,NULL,NULL,'none'),(65,49,54,9,'2026-09-15 22:57:21','2026-09-22 22:57:21','2026-09-15 22:58:17','2026-09-15 22:57:40',1,'returned',13.50,'2026-09-15 14:57:21',143,0.00,0,0,'excellent',NULL,'5eaa1f3b7e1a3b71f09db9b70724ade0',0,NULL,NULL,'none'),(66,45,53,8,'2026-09-16 02:53:17','2026-09-23 02:53:17','2026-09-16 13:07:56',NULL,1,'returned',36.00,'2026-09-15 18:53:17',144,0.00,0,0,'good',NULL,NULL,0,NULL,NULL,'none'),(67,60,55,10,'2026-09-16 12:44:41','2026-12-09 12:44:41','2026-09-16 12:47:40','2026-09-16 12:46:17',12,'returned',162.00,'2026-09-16 04:44:41',145,0.00,0,0,'excellent',NULL,'9ce6af31bdee3d1726e416fc8801b69a',0,NULL,NULL,'none'),(68,60,55,10,'2026-09-16 13:00:44','2026-09-23 13:00:44','2026-09-16 13:02:51','2026-09-16 13:01:01',1,'returned',13.50,'2026-09-16 05:00:44',146,0.00,0,0,'damaged',NULL,'ce1819c5a2e3f35e32de18553b4975e4',0,NULL,NULL,'none'),(69,60,55,10,'2026-09-16 13:03:50','2026-09-23 13:03:50','2026-09-16 13:03:55',NULL,1,'returned',13.50,'2026-09-16 05:03:50',146,0.00,0,0,'good','',NULL,0,NULL,NULL,'none'),(70,60,55,10,'2026-09-16 13:05:22','2026-09-23 13:05:22','2026-09-16 13:07:53','2026-09-16 13:07:13',1,'returned',13.50,'2026-09-16 05:05:22',147,0.00,0,0,'good',NULL,'c0e8761ef034cbd383c63bfb41d0b7f5',0,NULL,NULL,'none'),(71,60,55,10,'2026-09-16 13:05:41','2026-09-23 13:05:41','2026-09-16 13:06:43',NULL,1,'returned',13.50,'2026-09-16 05:05:41',147,0.00,0,0,'good','',NULL,0,NULL,NULL,'none'),(72,60,55,10,'2026-09-16 13:06:12','2026-09-23 13:06:12','2026-09-16 13:07:51','2026-09-16 13:06:24',1,'returned',13.50,'2026-09-16 05:06:12',147,0.00,0,0,'excellent',NULL,'e6fb942552f6ac8d247a3918445427b0',0,NULL,NULL,'none');
/*!40000 ALTER TABLE `book_rentals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_returns`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_returns`
--

LOCK TABLES `book_returns` WRITE;
/*!40000 ALTER TABLE `book_returns` DISABLE KEYS */;
INSERT INTO `book_returns` VALUES (1,32,2,22,2,'dropoff','Drop-off return at Main Office (123 Book Street, Manila)','completed','2025-04-27 17:54:56','2025-04-27 17:55:40','2025-04-27 21:59:58',0,0,0.00,0.00,0.00,'Thank you for treating the book right','excellent',NULL,1),(2,31,2,22,2,'dropoff','{\"dropoff_location\":\"Mall Kiosk (SM Megamall, Level 3)\"}','completed','2025-04-27 18:57:13','2025-04-27 22:00:01','2025-04-27 22:00:45',0,0,0.00,0.00,0.00,'','excellent',NULL,NULL),(3,30,2,22,2,'dropoff','{\"dropoff_location\":\"Main Office (123 Book Street, Manila)\"}','completed','2025-04-27 21:20:20','2025-04-27 22:00:45','2025-04-27 22:00:48',0,0,0.00,0.00,0.00,'','excellent',NULL,NULL),(4,29,2,24,2,'dropoff','{\"dropoff_location\":\"Downtown Branch (456 Reading Ave, Quezon City)\"}','completed','2025-05-07 17:27:33','2025-05-07 17:28:16','2025-05-07 17:31:25',1,4,8.00,0.00,0.00,'','excellent','',NULL),(5,28,2,26,2,'dropoff','{\"dropoff_location\":\"Mall Kiosk (SM Megamall, Level 3)\"}','completed','2025-05-07 17:34:14','2025-05-07 17:34:48','2025-05-07 17:35:27',1,4,24.00,0.00,50.00,'water damage','fair','',NULL),(6,42,3,24,2,'dropoff','{\"dropoff_location\":\"Prime, Block 1 Lot 18 Susana Homes 3, Baliok, Davao City 8000\"}','completed','2025-05-07 19:24:05','2025-05-07 19:31:31','2025-05-07 19:31:35',0,0,0.00,0.00,0.00,'','good','',NULL),(7,41,3,22,2,'dropoff','{\"dropoff_location\":\"Prime, Block 1 Lot 18 Susana Homes 3, Baliok, Davao City 8000\"}','completed','2025-05-07 23:58:07','2025-05-07 23:58:27','2025-05-07 23:58:32',0,0,0.00,0.00,0.00,'','good','',NULL),(8,39,3,24,2,'dropoff','{\"dropoff_location\":\"Prime, Block 1 Lot 18 Susana Homes 3, Baliok, Davao City 8000\"}','completed','2025-05-08 00:01:49','2025-05-08 00:02:10','2025-05-08 00:02:14',0,0,0.00,0.00,0.00,'','good','',NULL),(9,43,3,24,2,'dropoff','{\"dropoff_location\":\"Prime, Block 1 Lot 18 Susana Homes 3, Baliok, Davao City 8000\"}','completed','2025-05-08 21:01:08','2025-05-08 21:01:21','2025-05-08 21:01:58',0,0,0.00,0.00,100.00,'','damaged','',NULL),(10,44,3,24,2,'dropoff','{\"dropoff_location\":\"Prime, Block 1 Lot 18 Susana Homes 3, Baliok, Davao City 8000\"}','completed','2025-05-08 21:08:23','2025-05-08 21:08:32','2025-05-08 21:19:52',0,0,0.00,0.00,50.00,'Basa ang book','fair','Gcash bayad',NULL),(11,27,2,26,2,'dropoff','{\"dropoff_location\":\"Prime, Block 1 Lot 18 Susana Homes 3, Baliok, Davao City 8000\"}','completed','2025-05-09 11:24:53','2025-05-09 11:25:00','2025-05-09 11:25:17',1,6,36.00,0.00,100.00,'Damage','damaged','Basa eh',NULL),(12,45,33,41,2,'dropoff','{\"dropoff_location\":\"Prime, Block 1 Lot 18 Susana Homes 3, Baliok, Davao City 8000\"}','completed','2025-07-26 08:26:55','2025-07-26 08:27:25','2025-07-26 08:27:51',0,0,0.00,0.00,50.00,'','fair','',NULL),(13,50,36,47,3,'dropoff','{\"dropoff_location\":\"Campus Meet-up\",\"dropoff_notes\":\"\"}','completed','2026-09-08 20:58:09','2026-09-08 21:06:57','2026-09-08 21:08:56',0,0,0.00,0.00,0.00,'','good','',NULL),(14,51,36,45,3,'dropoff','{\"dropoff_location\":\"Campus Meet-up\",\"dropoff_notes\":\"\"}','completed','2026-09-10 15:10:55','2026-09-10 15:11:03','2026-09-10 15:11:13',0,0,0.00,0.00,0.00,'','good','',NULL),(15,52,36,48,3,'dropoff','{\"dropoff_location\":\"Campus Meet-up\",\"dropoff_notes\":\"\"}','pending','2026-09-10 15:55:04',NULL,NULL,0,0,0.00,0.00,0.00,NULL,NULL,NULL,NULL),(16,54,36,50,3,'dropoff','{\"dropoff_location\":\"Campus Meet-up [Note: Note: [Campus Meet-up: Main Campus Gate]]\",\"dropoff_notes\":\"Note: [Campus Meet-up: Main Campus Gate]\"}','completed','2026-09-10 16:15:52','2026-09-10 16:16:17','2026-09-10 16:16:28',0,0,0.00,0.00,0.00,'','good','',NULL),(17,55,38,51,5,'dropoff','{\"dropoff_location\":\"Toril Park\",\"dropoff_notes\":\"\"}','completed','2026-09-14 12:56:20','2026-09-14 12:57:21','2026-09-14 13:36:13',0,0,0.00,0.00,0.00,'','good','',NULL),(18,56,38,51,5,'dropoff','{\"dropoff_location\":\"Toril Park\",\"dropoff_notes\":\"\"}','completed','2026-09-14 13:58:58','2026-09-14 13:59:12','2026-09-14 13:59:25',0,0,0.00,0.00,0.00,'','good','',NULL),(19,57,38,51,5,'dropoff','{\"dropoff_location\":\"Toril Park\",\"dropoff_notes\":\"\"}','completed','2026-09-14 15:11:00','2026-09-14 15:11:09','2026-09-14 15:19:51',0,0,0.00,0.00,0.00,'','excellent','',NULL),(20,58,41,51,5,'dropoff','{\"dropoff_location\":\"Toril Park\",\"dropoff_notes\":\"\"}','completed','2026-09-14 15:48:36','2026-09-14 15:48:47','2026-09-14 15:50:05',0,0,0.00,0.00,0.00,'','excellent','',NULL),(21,59,45,52,8,'dropoff','{\"dropoff_location\":\"Toril Park\",\"dropoff_notes\":\"\"}','completed','2026-09-15 19:45:25','2026-09-15 19:45:33','2026-09-15 19:46:25',0,0,0.00,0.00,0.00,'','excellent','',NULL),(22,60,47,52,8,'dropoff','{\"dropoff_location\":\"Toril Park\",\"dropoff_notes\":\"\"}','completed','2026-09-15 20:41:02','2026-09-15 20:41:12','2026-09-15 20:41:17',0,0,0.00,0.00,0.00,'','excellent','',NULL),(23,61,47,52,8,'dropoff','{\"dropoff_location\":\"Toril Park\",\"dropoff_notes\":\"\"}','completed','2026-09-15 20:54:02','2026-09-15 20:54:17','2026-09-15 20:54:22',0,0,0.00,0.00,0.00,'','excellent','',NULL),(24,62,47,53,8,'dropoff','{\"dropoff_location\":\"Toril\",\"dropoff_notes\":\"\"}','completed','2026-09-15 20:57:26','2026-09-15 20:57:35','2026-09-15 20:57:39',0,0,0.00,0.00,0.00,'','good','',NULL),(25,63,47,53,8,'dropoff','{\"dropoff_location\":\"Toril\",\"dropoff_notes\":\"\"}','completed','2026-09-15 21:08:07','2026-09-15 21:08:12','2026-09-15 21:08:59',0,0,0.00,0.00,0.00,'','excellent','',NULL),(26,64,47,53,8,'dropoff','{\"dropoff_location\":\"Toril\",\"dropoff_notes\":\"\"}','completed','2026-09-15 21:16:58','2026-09-15 21:17:15','2026-09-15 21:20:42',0,0,0.00,0.00,10.00,'','good','',NULL),(27,65,49,54,9,'dropoff','{\"dropoff_location\":\"ILOCANO VILLAGE\",\"dropoff_notes\":\"\"}','completed','2026-09-15 22:57:40','2026-09-15 22:57:45','2026-09-15 22:58:17',0,0,0.00,0.00,0.00,'','excellent','',NULL),(28,67,60,55,10,'dropoff','{\"dropoff_location\":\"Philippine Women\\\\\'s College of Davao Gate 1\",\"dropoff_notes\":\"\"}','completed','2026-09-16 12:46:17','2026-09-16 12:46:32','2026-09-16 12:47:40',0,0,0.00,0.00,0.00,'','excellent','YES',NULL),(29,68,60,55,10,'dropoff','{\"dropoff_location\":\"Philippine Women\\\\\'s College of Davao Gate 1 [Note: test]\",\"dropoff_notes\":\"test\"}','completed','2026-09-16 13:01:01','2026-09-16 13:01:08','2026-09-16 13:02:51',0,0,0.00,0.00,100.00,'DAMAGE','damaged','DAMAGE FRONT PAGE',NULL),(30,72,60,55,10,'dropoff','{\"dropoff_location\":\"Philippine Women\\\\\'s College of Davao Gate 1\",\"dropoff_notes\":\"\"}','completed','2026-09-16 13:06:24','2026-09-16 13:06:34','2026-09-16 13:07:51',0,0,0.00,0.00,0.00,'','excellent','',NULL),(31,70,60,55,10,'dropoff','{\"dropoff_location\":\"Philippine Women\\\\\'s College of Davao Gate 1\",\"dropoff_notes\":\"\"}','completed','2026-09-16 13:07:13','2026-09-16 13:07:19','2026-09-16 13:07:53',0,0,0.00,0.00,0.00,'','good','',NULL);
/*!40000 ALTER TABLE `book_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_swaps`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_swaps`
--

LOCK TABLES `book_swaps` WRITE;
/*!40000 ALTER TABLE `book_swaps` DISABLE KEYS */;
INSERT INTO `book_swaps` VALUES (1,2,'Harry Potter','J.K. Rowling','\\\"Harry Potter and the Sorcerer\\\'s Stone\\\" follows Harry Potter, an 11-year-old orphan, as he discovers his magical heritage and is invited to attend Hogwarts School of Witchcraft and Wizardry. He learns that his parents were murdered by the dark wizard Voldemort, and that he survived an attack by Voldemort, leaving him with a lightning bolt scar.','New','uploads/books/680eec3646071_images (15).jpg','swapped','2025-04-28 02:47:18',NULL,'2025-05-07 05:08:38'),(2,3,'Lord of the rings','J.R.R. Tolkien','\\\"The Lord of the Rings\\\" is a high fantasy epic novel written by J.R.R. Tolkien, originally published in three volumes between 1954 and 1955. It\\\'s a sequel to his earlier work, The Hobbit, and is considered one of the most influential fantasy series of all time.\\r\\n\\r\\n','New','uploads/books/6811093d88a52_the-fellowship-of-the-ring-the-lord-of-the-rings-book-1-1.jpg','swapped','2025-04-29 17:15:41',NULL,'2025-05-07 05:29:34'),(3,3,'Cars','Lil Chou','Story of a car','Good','uploads/books/68110ff67161a_displacement.png','swapped','2025-04-29 17:44:22',NULL,'2025-05-07 05:22:53'),(6,2,'Book ','Book','book','Like New','uploads/books/681d9f450c80d_13335037.jpg','swapped','2025-05-09 06:23:01',NULL,'2025-05-09 06:23:59'),(7,32,'Dog','Jostein Gaarder','Picture of a dog','Like New','uploads/books/6882294bc45ca_686351f5755ce16bdd351225f541f861.jpg','available','2025-07-24 12:38:35',NULL,'2025-07-24 12:38:35'),(8,3,'Meerkats','Lil Chou','Meerkats story','Like New','uploads/books/688419a8c99c3_fb06f253fc80603351450e2000d12fa6.jpg','requested','2025-07-25 23:56:24',NULL,'2025-11-05 02:52:47'),(9,1,'13123','123123','123123','New','uploads/books/690abbf5e7b87_335329057_928352408615142_7576276041348614687_n (1).jpg','requested','2025-11-05 02:52:37',NULL,'2025-11-05 02:54:58'),(10,34,'face','Albert einstein','123','New','uploads/books/690abc796bd4a_Gemini_Generated_Image_afkig9afkig9afki.png','available','2025-11-05 02:54:49',NULL,'2025-11-05 02:54:49');
/*!40000 ALTER TABLE `book_swaps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `books`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `books`
--

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES (32,1,'Learning Piano The easy way','Carmelita V. Jose','9014908-8-8-8','Non-Fiction','Music','Paperback','Good','Page pen writing ','New Releases',150.00,18.00,1,'\\\"Learning Piano the Easy Way: Method for Beginners\\\" by Carmelita V. Jose is a beginner-friendly piano instruction book designed to help new learners grasp the fundamentals of piano playing with ease. It offers a step-by-step method tailored especially for students with little to no prior musical background.','uploads/covers/681f2a90329e3_494336190_677657388319101_3334952234849700872_n.jpg','2025-05-10 10:29:36',10.00,10.00,0.90,100.00,30.00,30,'both',0.00,NULL,'Campus Meet-up','approved',NULL,NULL,NULL,1),(33,1,'Sophie\\\\\\\'s World','Jostein Gaarder','978-0374266431','Fiction','Literary Fiction','Paperback','Fair','Old looking cover','New Releases',120.00,10.00,1,'The story follows Sophie Amundsen, a 14-year-old girl living in Norway who begins receiving mysterious letters and philosophical questions like: \\\\\\\"Who are you?\\\\\\\" and \\\\\\\"Where does the world come from?\\\\\\\". These questions lead her on a journey through the major schools of thought and key philosophers???from Socrates, Plato, and Aristotle to Descartes, Kant, Marx, Darwin, and more.','uploads/covers/681f39ad38fb7_494358985_1205819447587730_1667705645995571232_n.jpg','2025-05-10 11:34:05',10.00,10.00,0.80,100.00,30.00,30,'both',0.00,NULL,'Campus Meet-up','approved',NULL,NULL,NULL,1),(34,1,'Getting Things Done on Your PC','DK (Dorling Kindersley)','0-7513-4645-4','Non-Fiction','Technology','Hardcover','Very Good','No damage','New Releases',150.00,20.00,1,'This is a practical guide that introduces users???especially beginners???to using a personal computer effectively. It likely includes tips on basic operations like email, using word processors, spreadsheets, internet browsing, handling files, and avoiding common PC problems like viruses. DK is known for their visually rich and easy-to-understand reference books.','uploads/covers/681f3bf42a7b6_494572311_494862676955856_7348947941193753978_n.jpg','2025-05-10 11:43:48',10.00,10.00,1.00,100.00,30.00,30,'both',0.00,NULL,'Campus Meet-up','approved',NULL,NULL,NULL,1),(35,1,'Mysteries of sherlock holmes','Sir Arthur Conan Doyle','0-394-85086-6','Fiction','Mystery','Paperback','Good','No damage','New Releases',50.00,10.00,0,'Step into the foggy streets of Victorian London and join the legendary detective Sherlock Holmes and his loyal companion Dr. John Watson as they unravel some of the most baffling crimes ever conceived. This thrilling collection brings together several of Holmes\\\' most gripping adventures, showcasing his unmatched powers of deduction, keen observation, and logical reasoning.','uploads/covers/681f3f8f2a7ee_bd45edcf-4226-41eb-a3f5-ef9702e360d8.jpg','2025-05-10 11:59:11',5.00,10.00,0.90,20.00,30.00,30,'both',0.00,NULL,'Campus Meet-up','approved',NULL,NULL,NULL,1),(37,1,'Paano Paamuin Ang Puso','Andrea Almonte','123123123123123','Fiction','Romance','Paperback','Fair',' ','New Releases',30.00,12.00,1,'0','uploads/covers/6823faf6d9dbb_Andrea1.jpg','2025-05-14 02:07:50',5.00,10.00,0.80,20.00,30.00,30,'both',0.00,NULL,'Campus Meet-up','approved',NULL,NULL,NULL,1),(38,1,'ISLA FUENTEBELLA 37 BOOK II','Andrea Almonte','1234512345','Fiction','Romance','Paperback','Fair','None','Most popular',10.00,5.00,1,'0','uploads/covers/6823fba47931e_ISLA.jpg','2025-05-14 02:10:44',5.00,10.00,0.80,10.00,30.00,30,'both',0.00,NULL,'Campus Meet-up','approved',NULL,NULL,NULL,1),(39,1,'Ikaw Ang Pag-ibig Na Para Sa Akin','Alyana Mendoza','123123123123','Fiction','Romance','Paperback','Fair','None','Most popular',10.00,5.00,1,'0','uploads/covers/6823fcbbb8dfa_Alyana.jpg','2025-05-14 02:15:23',5.00,10.00,0.80,10.00,30.00,30,'both',0.00,NULL,'Campus Meet-up','approved',NULL,NULL,NULL,1),(40,1,'Make it Real','Hanna Jossaine Aceveda','621-404-044-0','Fiction','Romance','Paperback','Poor','Poor Cover','Most popular',20.00,10.00,1,'0','uploads/covers/6823fd8ce821f_Make.jpg','2025-05-14 02:18:52',50.00,10.00,0.70,200.00,30.00,30,'both',0.00,NULL,'Campus Meet-up','approved',NULL,NULL,NULL,1),(41,1,'Ang Pasaway Kong BodyGuard','Mei Sanchez','9-789710-298822','Fiction','Drama','Paperback','Fair','None','Most popular',30.00,15.00,1,'0','uploads/covers/6823fe2e2cab7_Ang.jpg','2025-05-14 02:21:34',50.00,10.00,0.80,200.00,30.00,30,'both',0.00,NULL,'Campus Meet-up','approved',NULL,NULL,NULL,1),(42,1,'Para sa Broken Hearted','Marcelo Santos III','987-612-95432-4-8','Fiction','Romance','Paperback','Fair','None','New Releases',20.00,5.00,1,'0','uploads/covers/6823feaaa0614_Para.jpg','2025-05-14 02:23:38',50.00,10.00,0.80,200.00,30.00,30,'both',0.00,NULL,'Campus Meet-up','approved',NULL,NULL,NULL,1),(43,1,'Geometry in the real world','Karl Freidrich Jose D. Romero','1236578-123456','Non-Fiction','Education','Paperback','Fair','None','New Releases',20.00,5.00,0,'0','uploads/covers/6823ff0430a38_Geo.jpg','2025-05-14 02:25:08',50.00,10.00,0.80,200.00,30.00,30,'both',0.00,NULL,'Campus Meet-up','approved',NULL,NULL,NULL,1),(44,1,'ICT and Society 2nd Edition','Alvin Ramirez','987-9870-987-91','Non-Fiction','Education','Paperback','Fair','Last page paper damage','New Releases',20.00,5.00,1,'0','uploads/covers/6823ff669f0b4_ICT.jpg','2025-05-14 02:26:46',50.00,10.00,0.80,200.00,30.00,30,'both',0.00,NULL,'Campus Meet-up','approved',NULL,NULL,NULL,1),(50,35,'Nineteen Eighty-Four','George Orwell','123456','Fiction, Non-Fiction, Mystery','Dystopian, Memoir','Paperback','Good','No noticeable damage','New Releases',364.00,36.00,1,'Nineteen Eighty-Four (also published as 1984) is a dystopian and speculative fiction novel by the English writer George Orwell. It was published on 8 June 1949 by Secker & Warburg as Orwell\\\'s ninth and final completed book. Thematically, it centres on totalitarianism, mass surveillance and repressive regimentation of people and behaviours.[3][4] Nineteen Eighty-Four has often been regarded as a classic and has been acknowledged for its impact on 20th-century literature.','uploads/covers/6aa2652730e48_1984-nineteen-eighty-four-by-george-orwell.jpg','2026-09-10 08:07:03',30.00,10.00,0.90,250.00,30.00,30,'both',250.00,'','Philippine Women\\\'s College of Davao Gate 1','approved',NULL,NULL,NULL,1),(51,40,'Everything i know','Dolly Alberton','123456789','Fiction, Mystery','Personal Growth','Paperback','Good','No noticeable damage','New Releases',364.00,36.00,14,'Everything I Know About Love is about bad dates, good friends andΓÇöabove all elseΓÇö realizing that you are enough. ... Like Bridget Jones\\\\\\\\\\\\\\\' Diary','uploads/covers/6aa7610a811cd_images.jfif','2026-09-14 02:50:50',30.00,10.00,0.90,250.00,30.00,30,'both',250.00,'','Toril Park','approved',NULL,NULL,NULL,1),(52,44,'The Mountain Is You','Brianna Wiest','099557744111','Self-Help, Biography','Young Adult, Personal Growth','Paperback','Good','No noticeable damage','New Releases',689.00,27.00,10,'transformative book about self-sabotagewhy we do it, when we do it, and how to stop doing it for good. If you\\\'\\\'ve ever found yourself stuck, repeating toxic patterns, or resisting change even when you desperately want to grow, The Mountain Is You offers the clarity and strategy to overcome it.','uploads/covers/6aa92a324caa6_71AHFDEpkdL._UF1000,1000_QL80_.jpg','2026-09-15 11:21:22',20.00,10.00,0.90,500.00,30.00,30,'both',500.00,'','Toril Park','approved',NULL,NULL,NULL,1),(53,44,'Test','Test','123456','Non-Fiction, Thriller','Academic','Paperback','Good','No noticeable damage','New Releases',364.00,36.00,2,'Test','uploads/covers/6aa9403c51237_POSTER BOOKWAGON (2).png','2026-09-15 12:55:24',30.00,10.00,0.90,250.00,30.00,30,'both',250.00,'','Toril','approved',NULL,NULL,NULL,1),(54,50,'BOOKWAGON','JUDE','123456789','Non-Fiction, Thriller, Business','Personal Growth, Contemporary','Paperback','Good','No noticeable damage','New Releases',468.00,13.50,4,'BOOKWAGON BEST BOOK','uploads/covers/6aa957395924c_pngtree-stack-of-books-with-a-green-leaf-promoting-education-and-sustainability-png-image_15261930.png','2026-09-15 14:33:29',5.00,10.00,0.90,330.00,30.00,30,'both',330.00,'','ILOCANO VILLAGE','approved',NULL,NULL,NULL,1),(55,52,'TESTING ONLY','BOOKWAGON','123124121','Non-Fiction, Mystery, Thriller','Dystopian, Memoir','Paperback','Good','Pen / Highlighter markings','New Releases',3939.00,13.50,8,'TESTING','uploads/covers/6aaa1dfc04af2_photo_2026-06-25_19-55-44.jpg','2026-09-16 04:41:32',5.00,10.00,0.90,3000.00,30.00,30,'both',3000.00,'','Philippine Women\\\'s College of Davao Gate 1','approved',NULL,NULL,NULL,1);
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_audit_book_update
AFTER UPDATE ON books
FOR EACH ROW
BEGIN
    -- Check if price, rent price, or stock was modified
    IF (OLD.price != NEW.price OR OLD.rent_price != NEW.rent_price) THEN
        IF (@app_authorized IS NULL OR @app_authorized != 1) THEN
            -- Unauthorized direct SQL / phpMyAdmin tampering
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_audit_book_delete
BEFORE DELETE ON books
FOR EACH ROW
BEGIN
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `cart`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=190 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
INSERT INTO `cart` VALUES (152,3,41,1,'buy',1,'2025-05-14 02:27:34'),(156,31,41,1,'buy',1,'2025-05-14 04:58:53'),(157,28,41,1,'buy',1,'2025-05-14 05:52:58'),(158,1,41,1,'rent',1,'2025-07-24 11:30:24'),(179,46,52,1,'rent',1,'2026-09-15 12:01:19');
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversation_participants`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversation_participants`
--

LOCK TABLES `conversation_participants` WRITE;
/*!40000 ALTER TABLE `conversation_participants` DISABLE KEYS */;
INSERT INTO `conversation_participants` VALUES (2,1,1),(1,1,3),(4,2,2),(3,2,3),(6,3,3),(5,3,25),(8,4,3),(7,4,32),(9,5,44),(10,5,47);
/*!40000 ALTER TABLE `conversation_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversations`
--

LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
INSERT INTO `conversations` VALUES (1,'2025-05-13 12:43:29','2025-05-13 12:45:03'),(2,'2025-05-13 12:43:45','2025-05-13 12:43:45'),(3,'2025-05-13 23:22:14','2025-05-14 01:36:06'),(4,'2025-07-24 14:03:53','2025-07-24 14:03:53'),(5,'2026-09-15 18:20:05','2026-09-15 18:21:53');
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_methods`
--

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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_methods`
--

LOCK TABLES `delivery_methods` WRITE;
/*!40000 ALTER TABLE `delivery_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `delivery_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_categories`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_categories`
--

LOCK TABLES `forum_categories` WRITE;
/*!40000 ALTER TABLE `forum_categories` DISABLE KEYS */;
INSERT INTO `forum_categories` VALUES (1,'Book Discussions','Discuss your favorite books, authors, and literary works','fa-book-open','#3498db',1,'2025-05-10 12:59:47'),(2,'Reading Recommendations','Ask for and share book recommendations based on interests','fa-list','#2ecc71',2,'2025-05-10 12:59:47'),(3,'Book Reviews','Share your thoughts and reviews on books you\'ve read','fa-star','#f39c12',3,'2025-05-10 12:59:47'),(4,'Writing Corner','For aspiring writers to share their work and get feedback','fa-pen-fancy','#9b59b6',4,'2025-05-10 12:59:47'),(5,'Book Clubs','Find and join book clubs or start your own','fa-users','#e74c3c',5,'2025-05-10 12:59:47'),(6,'Literary Events','Discuss book fairs, author signings, and other literary events','fa-calendar','#1abc9c',6,'2025-05-10 12:59:47');
/*!40000 ALTER TABLE `forum_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_comments`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_comments`
--

LOCK TABLES `forum_comments` WRITE;
/*!40000 ALTER TABLE `forum_comments` DISABLE KEYS */;
INSERT INTO `forum_comments` VALUES (1,1,1,'Should try harry potter series',NULL,0,'2025-05-10 13:25:15','2025-05-10 13:25:15');
/*!40000 ALTER TABLE `forum_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_posts`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_posts`
--

LOCK TABLES `forum_posts` WRITE;
/*!40000 ALTER TABLE `forum_posts` DISABLE KEYS */;
INSERT INTO `forum_posts` VALUES (1,2,2,'Book recommendation','I already finished Lord of the ring series and looking for a new book to read any recommendation.','Fiction','active',14,0,'2025-05-10 13:19:03','2026-08-30 03:16:09'),(2,3,1,'Mysteries of sherlock holmes','A bit cliff hanger 7/10','fiction','active',11,0,'2025-05-10 13:26:40','2025-05-12 14:28:44'),(3,1,3,'Book recommendation','I\'m new in reading a recommendation would be good','Mystery','active',2,0,'2025-05-10 14:22:12','2025-05-14 03:46:49'),(4,2,45,'try','try','boook','active',2,0,'2026-09-15 11:52:33','2026-09-15 11:52:35');
/*!40000 ALTER TABLE `forum_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_user_interactions`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_user_interactions`
--

LOCK TABLES `forum_user_interactions` WRITE;
/*!40000 ALTER TABLE `forum_user_interactions` DISABLE KEYS */;
INSERT INTO `forum_user_interactions` VALUES (1,1,1,NULL,'like','2025-05-10 13:25:33'),(2,2,NULL,1,'like','2025-05-10 13:27:17');
/*!40000 ALTER TABLE `forum_user_interactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_history`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_history`
--

LOCK TABLES `login_history` WRITE;
/*!40000 ALTER TABLE `login_history` DISABLE KEYS */;
INSERT INTO `login_history` VALUES (1,36,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-31 10:50:42','success'),(2,35,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-31 11:03:41','success'),(3,35,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-31 11:04:36','success'),(4,35,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-31 11:22:33','success'),(5,35,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-31 12:11:06','success'),(6,36,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-31 12:12:04','success'),(7,35,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-31 12:18:03','success'),(8,35,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-05 06:10:41','success'),(9,36,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-05 07:10:03','success'),(10,36,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-05 08:17:34','success'),(11,35,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-05 08:24:06','success'),(12,36,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-08 11:00:49','success'),(13,35,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-08 11:14:17','success'),(14,36,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-08 11:14:27','success'),(15,35,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-10 06:20:45','success'),(16,35,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-10 06:23:16','success'),(17,36,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-10 06:23:53','success'),(18,36,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-10 06:53:46','success'),(19,35,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-10 07:08:17','success'),(20,36,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-10 07:09:20','success'),(21,35,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-10 07:54:31','success'),(22,36,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-10 07:54:53','success'),(23,35,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-10 08:01:08','success'),(24,37,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-10 08:03:08','success'),(25,35,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-10 08:06:03','success'),(26,36,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-13 13:21:19','success'),(27,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-13 13:37:00','success'),(28,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-13 13:43:47','success'),(29,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-13 13:46:23','success'),(30,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-13 13:53:11','success'),(31,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-13 13:54:44','success'),(32,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-13 14:38:13','success'),(33,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 02:24:56','success'),(34,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 02:43:47','success'),(35,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 02:45:01','success'),(36,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 02:47:40','success'),(37,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 02:51:19','success'),(38,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 02:51:38','success'),(39,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 03:10:42','success'),(40,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 03:11:03','success'),(41,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 03:33:34','success'),(42,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 03:33:58','success'),(43,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 04:23:18','success'),(44,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 04:23:36','success'),(45,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 04:56:55','success'),(46,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 05:29:52','success'),(47,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 05:35:51','success'),(48,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 06:21:36','success'),(49,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 06:34:40','success'),(50,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 06:38:51','success'),(51,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 07:07:19','success'),(52,38,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 07:08:26','success'),(53,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 07:32:54','success'),(54,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-14 07:47:46','success'),(55,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-14 12:29:20','success'),(56,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 11:12:44','success'),(57,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 11:12:50','success'),(58,44,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 11:15:05','success'),(59,40,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 11:32:01','success'),(60,44,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 11:39:47','success'),(61,44,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 11:50:01','success'),(62,46,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 12:00:13','success'),(63,44,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 12:43:02','success'),(64,44,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 12:52:41','success'),(65,49,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 14:24:22','success'),(66,50,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 14:29:35','success'),(67,50,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 14:49:41','success'),(68,49,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 14:56:01','success'),(69,44,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 18:16:52','success'),(70,47,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 18:20:43','success'),(71,44,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 18:21:44','success'),(72,44,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 18:49:01','success'),(73,45,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-15 18:50:53','success'),(75,52,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-16 04:14:30','success'),(76,52,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-16 04:21:13','success'),(77,57,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-16 04:28:19','success'),(78,60,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-16 04:35:37','success'),(79,52,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-16 04:40:27','success');
/*!40000 ALTER TABLE `login_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (1,1,3,'Hello',1,'2025-05-13 12:43:35'),(2,1,1,'Hi dangrey',1,'2025-05-13 12:45:03'),(3,3,25,'Hello boss dangrey',1,'2025-05-13 23:22:20'),(4,3,3,'Hello',0,'2025-05-14 01:36:06'),(5,5,44,'hello',1,'2026-09-15 18:20:09'),(6,5,47,'hi',1,'2026-09-15 18:20:52'),(7,5,47,'how r u',1,'2026-09-15 18:21:12'),(8,5,44,'ok fine',0,'2026-09-15 18:21:53');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,3,'buddy_request','Dangrey Cutie has sent you a book buddy request',1,'2025-05-12 15:27:45'),(2,3,1,'buddy_accepted','Ariel Del Rosario has accepted your book buddy request',1,'2025-05-12 15:27:57'),(3,40,1,'seller_approved','Congratulations! Your seller application for \'Seller\' has been approved. You can now start listing books.',0,'2026-09-14 02:46:57'),(4,40,1,'new_order','You have received a new order (Order #128)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-14 03:10:10'),(5,40,1,'new_order','You have received a new order (Order #129)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-14 04:04:11'),(6,40,1,'new_order','You have received a new order (Order #129)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-14 04:23:28'),(7,40,1,'new_order','You have received a new order (Order #130)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-14 04:26:46'),(8,40,1,'new_order','You have received a new order (Order #131)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-14 05:43:24'),(9,40,1,'new_order','You have received a new order (Order #132)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-14 07:09:15'),(10,40,1,'new_order','You have received a new order (Order #132)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-14 07:09:38'),(11,38,1,'payment_confirmed','Your withdrawal of Γé▒250.00 has been sent to your E-Wallet.',1,'2026-09-14 07:21:44'),(12,38,1,'payment_confirmed','Your withdrawal of Γé▒250.00 has been sent to your E-Wallet.',0,'2026-09-14 07:25:23'),(13,38,1,'payment_confirmed','Your withdrawal of Γé▒250.00 has been sent to your E-Wallet.',0,'2026-09-14 07:25:28'),(14,38,1,'payment_confirmed','Your withdrawal of Γé▒250.00 has been sent to your E-Wallet.',0,'2026-09-14 07:25:51'),(15,38,1,'payment_confirmed','Your withdrawal of Γé▒250.00 has been sent to your E-Wallet.',0,'2026-09-14 07:25:55'),(16,38,1,'payment_confirmed','Your withdrawal of Γé▒250.00 has been sent to your E-Wallet.',0,'2026-09-14 07:26:12'),(17,40,1,'new_order','You have received a new order (Order #133)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-14 07:47:35'),(18,40,1,'payment_confirmed','Your payout of Γé▒144.00 has been sent to your E-Wallet.',0,'2026-09-14 07:50:15'),(19,40,1,'new_order','You have received a new order (Order #134)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-14 12:33:41'),(25,43,1,'seller_rejected','Your seller application for \'bokbok\' was not approved at this time. Please check your application status or contact support for more details.',1,'2026-09-14 18:19:44'),(26,44,1,'seller_approved','Congratulations! Your seller application for \'Zenny\' has been approved. You can now start listing books.',1,'2026-09-15 11:18:41'),(27,44,1,'product_approved','Your product \'The Mountain Is You\' has been approved and is now visible in the marketplace.',1,'2026-09-15 11:40:36'),(28,44,1,'product_approved','Your product \'The Mountain Is You\' has been approved and is now visible in the marketplace.',1,'2026-09-15 11:43:32'),(29,44,1,'product_approved','Your product \'The Mountain Is You\' has been approved and is now visible in the marketplace.',1,'2026-09-15 11:43:34'),(30,44,1,'new_order','You have received a new order (Order #135)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-15 11:44:16'),(31,45,1,'payment_confirmed','Your withdrawal of ₱1,500.00 has been sent to your E-Wallet.',0,'2026-09-15 11:51:34'),(32,45,1,'payment_confirmed','Your withdrawal of ₱1,500.00 has been sent to your E-Wallet.',0,'2026-09-15 12:05:18'),(33,44,1,'new_order','You have received a new order (Order #136)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-15 12:40:08'),(34,44,1,'product_approved','Your product \'Test\' has been approved and is now visible in the marketplace.',1,'2026-09-15 12:55:32'),(35,44,1,'product_approved','Your product \'Test\' has been approved and is now visible in the marketplace.',1,'2026-09-15 12:55:53'),(36,44,1,'new_order','You have received a new order (Order #137)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-15 12:56:29'),(37,44,1,'new_order','You have received a new order (Order #138)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-15 13:07:35'),(38,44,1,'new_order','You have received a new order (Order #138)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-15 13:07:36'),(39,47,1,'payment_confirmed','Your withdrawal of ₱250.00 has been sent to your E-Wallet.',0,'2026-09-15 13:10:04'),(40,47,1,'payment_confirmed','Your withdrawal of ₱250.00 has been sent to your E-Wallet.',0,'2026-09-15 13:10:17'),(41,44,1,'new_order','You have received a new order (Order #141)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-15 13:14:09'),(42,44,1,'new_order','You have received a new order (Order #141)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-15 13:14:52'),(43,44,1,'new_order','You have received a new order (Order #142)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-15 13:16:29'),(44,50,1,'seller_approved','Congratulations! Your seller application for \'Creed Aventus\' has been approved. You can now start listing books.',1,'2026-09-15 14:31:58'),(45,50,1,'seller_approved','Congratulations! Your seller application for \'Creed Aventus\' has been approved. You can now start listing books.',1,'2026-09-15 14:33:36'),(46,50,1,'product_approved','Your product \'BOOKWAGON\' has been approved and is now visible in the marketplace.',1,'2026-09-15 14:55:14'),(47,50,1,'new_order','You have received a new order (Order #143)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-15 14:56:46'),(48,44,1,'new_order','You have received a new order (Order #144)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-15 18:52:42'),(49,52,1,'seller_approved','Congratulations! Your seller application for \'XIVVY\' has been approved. You can now start listing books.',1,'2026-09-16 04:18:30'),(50,52,1,'product_approved','Your product \'TESTING ONLY\' has been approved and is now visible in the marketplace.',1,'2026-09-16 04:41:47'),(51,52,1,'product_approved','Your product \'TESTING ONLY\' has been approved and is now visible in the marketplace.',1,'2026-09-16 04:42:53'),(52,52,1,'new_order','You have received a new order (Order #145)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-16 04:42:58'),(53,52,1,'new_order','You have received a new order (Order #146)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-16 05:02:07'),(54,52,1,'new_order','You have received a new order (Order #146)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-16 05:02:36'),(55,52,1,'new_order','You have received a new order (Order #147)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-16 05:05:15'),(56,52,1,'new_order','You have received a new order (Order #147)! Payment has been verified. Please check your pending orders for details.',0,'2026-09-16 05:07:44');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=144 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (116,117,43,1,1,'rent',1,5.00,'processing',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'none','none',NULL),(117,119,35,1,1,'rent',2,20.00,'processing',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'none','none',NULL),(118,120,41,1,1,'rent',1,15.00,'delivered',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'none','none',NULL),(125,127,50,35,1,'rent',1,36.00,'delivered',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'none','none',NULL),(126,128,51,40,1,'rent',1,36.00,'cancelled','uploads/conditions/condition_126_1789355791.jpg',NULL,'68e01c8a5e0047dc20232a76a8756740',NULL,NULL,0,NULL,NULL,'none','refunded','123456789'),(127,129,51,40,1,'rent',1,36.00,'cancelled',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'none','refunded','12345678'),(128,130,51,40,1,'rent',1,36.00,'delivered',NULL,'Nice',NULL,'confirmed',NULL,0,NULL,NULL,'none','none',NULL),(129,131,51,40,1,'rent',1,36.00,'delivered',NULL,'1312',NULL,'confirmed',NULL,0,NULL,NULL,'none','none',NULL),(130,132,51,40,1,'rent',1,36.00,'delivered',NULL,'yes',NULL,'confirmed',NULL,0,NULL,NULL,'none','none',NULL),(131,133,51,40,1,'rent',1,36.00,'delivered',NULL,'yes',NULL,'confirmed',NULL,0,NULL,NULL,'none','none',NULL),(132,134,51,40,1,'rent',1,36.00,'pending_meetup',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'none','none',NULL),(133,135,52,44,1,'rent',1,27.00,'delivered',NULL,'123123123',NULL,'confirmed',NULL,0,NULL,NULL,'none','none',NULL),(134,136,52,44,1,'rent',1,27.00,'returned',NULL,'123123123',NULL,'confirmed',NULL,0,NULL,NULL,'none','none',NULL),(135,137,53,44,1,'rent',12,432.00,'delivered',NULL,'1asdasdasd',NULL,'confirmed',NULL,0,NULL,NULL,'none','none',NULL),(136,138,53,44,1,'rent',1,36.00,'delivered',NULL,'123123123123',NULL,'confirmed',NULL,0,NULL,NULL,'none','none',NULL),(137,141,53,44,1,'buy',0,364.00,'delivered',NULL,'yes',NULL,'confirmed',NULL,0,NULL,NULL,'none','none',NULL),(138,142,53,44,1,'rent',1,36.00,'delivered',NULL,'123123',NULL,'confirmed',NULL,0,NULL,NULL,'none','none',NULL),(139,143,54,50,1,'rent',1,13.50,'delivered',NULL,'asd',NULL,'confirmed',NULL,0,NULL,NULL,'none','none',NULL),(140,144,53,44,1,'rent',1,36.00,'delivered',NULL,'asdasdasd',NULL,'confirmed',NULL,0,NULL,NULL,'none','none',NULL),(141,145,55,52,1,'rent',12,162.00,'delivered',NULL,'123123123123',NULL,'confirmed',NULL,0,NULL,NULL,'none','none',NULL),(142,146,55,52,1,'rent',1,13.50,'delivered',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'none','none',NULL),(143,147,55,52,1,'rent',1,13.50,'returned',NULL,'yes',NULL,'confirmed',NULL,0,NULL,NULL,'none','none',NULL);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (7,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 00:36:52',NULL,0.00,'pending',NULL,NULL,NULL,NULL,'',0.00),(8,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','ssssss','123123','8000','','2025-04-25 00:41:43',NULL,0.00,'pending',NULL,NULL,NULL,NULL,'pending',0.00),(9,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 11:25:13','cod',0.00,'paid','2025-04-25 11:37:23',NULL,NULL,NULL,'pending',100.00),(10,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 13:03:12','cod',0.00,'paid','2025-04-25 13:04:23',NULL,NULL,NULL,'pending',40.00),(11,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','123123','2025-04-25 13:48:35','cod',0.00,'paid','2025-04-25 13:49:21',NULL,NULL,NULL,'pending',30.00),(12,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 13:54:07','cod',0.00,'paid','2025-04-25 13:54:56',NULL,NULL,NULL,'pending',625.00),(13,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 14:58:43',NULL,0.00,'pending',NULL,NULL,NULL,NULL,'pending',300.00),(14,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 21:07:42','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',12.00),(15,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 21:45:54',NULL,0.00,'pending',NULL,NULL,NULL,NULL,'pending',625.00),(16,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','ssssss','Davao City','8000','','2025-04-25 21:47:30','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',625.00),(17,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 21:53:10',NULL,0.00,'pending',NULL,NULL,NULL,NULL,'pending',180.00),(18,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 21:54:34',NULL,0.00,'pending',NULL,NULL,NULL,NULL,'pending',210.00),(19,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 21:57:59',NULL,0.00,'pending',NULL,NULL,NULL,NULL,'pending',150.00),(20,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 22:06:54','cod',0.00,'paid','2025-04-25 22:07:31',NULL,NULL,NULL,'pending',625.00),(21,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 22:15:25',NULL,0.00,'pending',NULL,NULL,NULL,NULL,'pending',180.00),(22,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 22:17:31',NULL,0.00,'pending',NULL,NULL,NULL,NULL,'pending',240.00),(23,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 22:17:54',NULL,0.00,'pending',NULL,NULL,NULL,NULL,'pending',300.00),(25,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 22:45:14',NULL,0.00,'pending',NULL,NULL,NULL,NULL,'pending',231.00),(26,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 22:45:30','cod',0.00,'paid','2025-04-25 22:46:20',NULL,NULL,NULL,'pending',264.00),(27,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 22:54:11','cod',0.00,'paid','2025-04-25 23:03:29',NULL,NULL,NULL,'pending',231.00),(28,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 23:00:33','cod',0.00,'paid','2025-04-25 23:03:34',NULL,NULL,NULL,'',330.00),(29,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 23:27:35','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',132.00),(30,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 23:51:02','cod',0.00,'paid','2025-04-25 23:51:58',NULL,NULL,NULL,'pending',132.00),(31,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-25 23:53:28','cod',0.00,'paid','2025-04-26 00:05:53',NULL,NULL,NULL,'pending',462.00),(32,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 00:12:46','cod',0.00,'paid','2025-04-26 00:13:15',NULL,NULL,NULL,'pending',363.00),(33,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 01:30:49','cod',0.00,'paid','2025-04-26 01:31:13',NULL,NULL,NULL,'pending',297.00),(34,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 01:41:28','cod',0.00,'paid','2025-04-26 01:42:02',NULL,NULL,NULL,'pending',429.00),(35,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 01:54:24','cod',0.00,'paid','2025-04-26 01:54:50',NULL,NULL,NULL,'pending',330.00),(36,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 02:04:11','cod',0.00,'paid','2025-04-26 02:04:42',NULL,NULL,NULL,'pending',297.00),(37,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 02:09:11','cod',0.00,'paid','2025-04-26 02:10:15',NULL,NULL,NULL,'pending',132.00),(38,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','ssssss','Davao City','8000','','2025-04-26 02:18:36','cod',0.00,'paid','2025-04-26 02:20:57',NULL,NULL,NULL,'pending',264.00),(39,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','ssssss','Davao City','8000','','2025-04-26 12:03:04','cod',0.00,'paid','2025-04-26 12:09:22',NULL,NULL,NULL,'pending',198.00),(40,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 13:11:27','cod',0.00,'paid','2025-04-26 13:12:07',NULL,NULL,NULL,'pending',297.00),(41,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 13:56:12','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',297.00),(42,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 14:32:49','cod',0.00,'paid','2025-04-26 14:33:14',NULL,NULL,NULL,'pending',118.80),(43,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 14:50:00','cod',0.00,'paid','2025-04-26 14:50:29',NULL,NULL,NULL,'pending',660.00),(44,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 16:55:23','cod',0.00,'paid','2025-04-26 16:55:51',NULL,NULL,NULL,'pending',660.00),(45,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 17:31:26','cod',0.00,'paid','2025-04-26 17:32:01',NULL,NULL,NULL,'pending',110.00),(46,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 20:06:16','cod',0.00,'paid','2025-04-26 20:07:22',NULL,NULL,NULL,'pending',110.00),(47,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 20:25:18','cod',0.00,'paid','2025-04-26 20:25:51',NULL,NULL,NULL,'pending',687.50),(48,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','ssssss','Davao City','8000','','2025-04-26 20:27:40','cod',0.00,'paid','2025-04-26 20:28:10',NULL,NULL,NULL,'pending',687.50),(49,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 20:44:27','cod',0.00,'paid','2025-04-26 20:44:54',NULL,NULL,NULL,'pending',687.50),(50,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 20:45:41','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',687.50),(51,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 21:09:40','cod',0.00,'paid','2025-04-26 21:10:24',NULL,NULL,NULL,'pending',110.00),(52,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 21:12:42',NULL,0.00,'pending',NULL,NULL,NULL,NULL,'pending',110.00),(53,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 21:25:21','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',110.00),(54,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 21:35:00','cod',0.00,'paid','2025-04-26 21:36:02',NULL,NULL,NULL,'pending',110.00),(55,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 21:39:43','cod',0.00,'paid','2025-04-26 21:40:24',NULL,NULL,NULL,'pending',110.00),(56,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 21:40:01','cod',0.00,'paid','2025-04-26 21:40:31',NULL,NULL,NULL,'pending',55.00),(57,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 21:48:29','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',110.00),(58,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 21:52:22','cod',0.00,'paid','2025-04-26 22:11:43',NULL,NULL,NULL,'pending',176.00),(59,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','123','2025-04-26 22:11:21','cod',0.00,'paid','2025-04-26 22:25:40',NULL,NULL,NULL,'pending',110.00),(60,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 22:26:22','cod',0.00,'paid','2025-04-26 22:27:20',NULL,NULL,NULL,'pending',110.00),(61,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 22:28:04','cod',0.00,'paid','2025-04-26 22:28:42',NULL,NULL,NULL,'pending',110.00),(62,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 22:30:50','cod',0.00,'paid','2025-04-26 22:31:28',NULL,NULL,NULL,'pending',110.00),(63,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','ssssss','Davao City','8000','','2025-04-26 22:33:42','cod',0.00,'paid','2025-04-26 22:34:07',NULL,NULL,NULL,'pending',607.20),(64,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 22:37:39','cod',0.00,'paid','2025-04-26 22:38:00',NULL,NULL,NULL,'pending',607.20),(65,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 22:38:37','cod',0.00,'paid','2025-04-26 22:39:37',NULL,NULL,NULL,'pending',145.20),(66,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 22:46:23','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',132.00),(67,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 22:57:51','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',132.00),(68,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:01:14','cod',0.00,'paid','2025-04-26 23:01:41',NULL,NULL,NULL,'pending',105.60),(69,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:03:51','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',145.20),(70,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:05:40','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',607.20),(71,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:12:42','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',171.60),(72,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:17:07','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',132.00),(73,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:21:18','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',145.20),(74,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:22:50','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',145.20),(75,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:25:52','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',132.00),(76,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:26:17','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',607.20),(77,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:29:56','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',607.20),(78,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:30:20','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',132.00),(79,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:33:05','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',607.20),(80,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:33:31','cod',0.00,'paid','2025-04-26 23:34:04',NULL,NULL,NULL,'pending',158.40),(81,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:37:20','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',145.20),(82,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:38:24','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',607.20),(83,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:41:58','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',158.40),(84,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:42:15','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',607.20),(85,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:48:04','cod',0.00,'paid','2025-04-26 23:49:20',NULL,NULL,NULL,'pending',607.20),(86,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-26 23:48:42','cod',0.00,'paid','2025-04-26 23:49:13',NULL,NULL,NULL,'pending',92.40),(87,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-27 00:09:15',NULL,0.00,'pending',NULL,NULL,NULL,NULL,'pending',13.20),(88,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','AAAAA','Davao City','8000','','2025-04-27 00:10:05','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',118.80),(89,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-04-27 13:50:47',NULL,0.00,'pending',NULL,NULL,NULL,NULL,'pending',52.80),(90,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-04-27 13:51:56','cod',0.00,'paid','2025-04-27 13:55:30',NULL,NULL,NULL,'pending',158.40),(91,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-04-27 14:08:45','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',171.60),(92,3,'Dangrey','Cutie','Dangrey@gmail.com','009090909','puan davao city','Davao City','8000','','2025-05-07 17:37:08','pickup',0.00,'paid','2025-05-07 17:37:50',NULL,NULL,NULL,'',264.00),(93,3,'Dangrey','Cutie','Dangrey@gmail.com','09090909','puan davao city','Davao City','8000','','2025-05-07 17:38:58','pickup',0.00,'paid','2025-05-07 17:39:37',NULL,NULL,NULL,'pending',132.00),(94,3,'Dangrey','Cutie','Dangrey@gmail.com','09090909','puan davao city','Davao City','8000','','2025-05-07 18:25:56','cod',0.00,'paid','2025-05-07 18:26:24',NULL,NULL,NULL,'pending',132.00),(95,3,'Dangrey','Cutie','Dangrey@gmail.com','09090909','puan davao city','Davao City','8000','','2025-05-07 18:36:34','cod',0.00,'paid','2025-05-07 18:45:04',NULL,NULL,NULL,'pending',154.00),(96,3,'Dangrey','Cutie','Dangrey@gmail.com','09090909','puan davao city','Davao City','8000','','2025-05-08 11:50:59','pickup',0.00,'pending',NULL,NULL,NULL,NULL,'pending',121.00),(97,3,'Dangrey','Cutie','Dangrey@gmail.com','09090909','puan davao city','Davao City','8000','','2025-05-08 13:32:21','cod',0.00,'pending',NULL,NULL,NULL,NULL,'pending',66.00),(98,3,'Dangrey','Cutie','Dangrey@gmail.com','09090909','puan davao city','Davao City','8000','','2025-05-08 13:51:34','pickup',0.00,'pending',NULL,NULL,NULL,NULL,'pending',55.00),(99,3,'Dangrey','Cutie','Dangrey@gmail.com','09090909','puan davao city','Davao City','8000','','2025-05-08 13:56:36','pickup',0.00,'paid','2025-05-08 13:57:09',NULL,'Block 1 Lot 18 Susana Homes 3, Baliok, Davao City','2025-05-09','pending',132.00),(100,3,'Dangrey','Cutie','Dangrey@gmail.com','09090909','puan davao city','Davao City','8000','','2025-05-08 14:02:23','cod',60.00,'paid','2025-05-08 14:02:58',NULL,NULL,NULL,'pending',132.00),(101,3,'Dangrey','Cutie','Dangrey@gmail.com','09090909','puan davao city','Davao City','8000','','2025-05-08 14:16:43','pickup',0.00,'pending',NULL,NULL,'Block 1 Lot 18 Susana Homes 3, Baliok, Davao City','2025-05-09','pending',171.60),(102,3,'Dangrey','Cutie','Dangrey@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-05-08 20:27:28','pickup',0.00,'paid','2025-05-08 20:29:32',NULL,'Block 1 Lot 18 Susana Homes 3, Baliok, Davao City','2025-05-10','pending',110.00),(103,3,'Dangrey','Cutie','Dangrey@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-05-08 20:35:56','pickup',0.00,'paid','2025-05-08 20:36:47',NULL,'Block 1 Lot 18 Susana Homes 3, Baliok, Davao City','2025-05-10','pending',99.00),(104,3,'Dangrey','Cutie','Dangrey@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-05-08 20:43:17','pickup',0.00,'paid','2025-05-08 20:43:58',NULL,'Block 1 Lot 18 Susana Homes 3, Baliok, Davao City','2025-05-09','pending',121.00),(105,3,'Dangrey','Cutie','Dangrey@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-05-08 20:50:31','pickup',0.00,'pending',NULL,NULL,'Block 1 Lot 18 Susana Homes 3, Baliok, Davao City','2025-05-09','pending',143.00),(106,3,'Dangrey','Cutie','Dangrey@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-05-08 20:53:10','cod',60.00,'pending',NULL,NULL,NULL,NULL,'pending',99.00),(107,3,'Dangrey','Cutie','Dangrey@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-05-08 20:53:37','pickup',0.00,'pending',NULL,NULL,NULL,NULL,'pending',132.00),(108,3,'Dangrey','Cutie','Dangrey@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-05-08 20:59:17','pickup',0.00,'paid','2025-05-08 21:00:21',NULL,'Block 1 Lot 18 Susana Homes 3, Baliok, Davao City','2025-05-10','pending',99.00),(109,3,'Dangrey','Cutie','Dangrey@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-05-08 21:06:57','pickup',0.00,'paid','2025-05-08 21:07:31',NULL,'Block 1 Lot 18 Susana Homes 3, Baliok, Davao City','2025-05-10','pending',176.00),(110,3,'Dangrey','Cutie','Dangrey@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','None','2025-05-08 23:21:18','pickup',0.00,'pending',NULL,NULL,NULL,NULL,'pending',176.00),(111,3,'Dangrey','Cutie','Dangrey@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-05-08 23:22:26','pickup',0.00,'pending',NULL,NULL,'Block 1 Lot 18 Susana Homes 3, Baliok, Davao City','2025-05-10','pending',145.20),(112,3,'Dangrey','Cutie','Dangrey@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-05-08 23:24:08','pickup',0.00,'pending',NULL,NULL,'Block 1 Lot 18 Susana Homes 3, Baliok, Davao City','2025-05-10','pending',79.20),(113,3,'Dangrey','Cutie','Dangrey@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-05-08 23:27:17','pickup',0.00,'pending',NULL,NULL,'Block 1 Lot 18 Susana Homes 3, Baliok, Davao City','2025-05-10','pending',121.00),(114,2,'Ariel','Del Rosario','sss@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-05-09 11:24:30','pickup',0.00,'pending',NULL,NULL,'Block 1 Lot 18 Susana Homes 3, Baliok, Davao City','2025-05-11','pending',326.70),(117,32,'Kuku','kuku','kuku@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-07-24 19:32:25','pickup',0.00,'pending',NULL,NULL,'Block 1 Lot 18 Susana Homes 3, Baliok, Davao City','2025-07-25','pending',5.50),(119,32,'Kuku','kuku','kuku@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-07-24 22:01:29','pickup',0.00,'pending',NULL,NULL,'Block 1 Lot 18 Susana Homes 3, Baliok, Davao City','2025-07-25','pending',22.00),(120,33,'jude','Ariel Del Rosario','ariel@gmail.com','09611347193','Block 1 Lot 18 Susana Homes 3, Baliok','Davao City','8000','','2025-07-26 08:25:25','pickup',0.00,'paid','2025-07-26 08:26:13',NULL,'Block 1 Lot 18 Susana Homes 3, Baliok, Davao City','2025-07-27','pending',16.50),(123,36,'Jude Kristian','Larroza','judekristian08@gmail.com','0912456789','Toril','Davao City','8000','[Campus Meet-up] [Deposit Refund: 0912456789] ','2026-09-08 19:50:49','qrph',0.00,'paid','2026-09-08 13:50:49','QRPH-20260908-7D6A98',NULL,NULL,'delivered',286.00),(124,36,'Jude Kristian','Larroza','judekristian08@gmail.com','0978945612','Meet-up','','','[Campus Meet-up] [Deposit Refund: 09959121303] ','2026-09-10 15:06:48','qrph',0.00,'paid','2026-09-10 09:06:48','QRPH-20260910-9D561F',NULL,NULL,'delivered',77.00),(125,36,'Jude Kristian','Larroza','judekristian08@gmail.com','09874561232','Meet-up','','','[Campus Meet-up] [Deposit Refund: 09179346412] ','2026-09-10 15:16:51','qrph',0.00,'paid','2026-09-10 09:16:51','QRPH-20260910-7E4697',NULL,NULL,'delivered',286.00),(126,36,'Jude Kristian','Larroza','judekristian08@gmail.com','09789456123','Meet-up','','','[Campus Meet-up] [Deposit Refund: 09912468779] ','2026-09-10 15:58:06','qrph',0.00,'paid','2026-09-10 09:58:06','QRPH-20260910-9F88AC',NULL,NULL,'cancelled',286.00),(127,36,'Jude Kristian','Larroza','judekristian08@gmail.com','09929121303','Meet-up','','','[Campus Meet-up] [Deposit Refund: 09123456789] ','2026-09-10 16:07:33','qrph',0.00,'paid','2026-09-10 10:07:33','QRPH-20260910-1CFFC4',NULL,NULL,'delivered',286.00),(128,38,'Test','Tester','dumpydummy02@gmail.com','992-912-9784','Meet-up','','','[Campus Meet-up] ','2026-09-14 10:52:37','qrph',0.00,'paid','2026-09-14 04:52:37','uploads/receipts/receipt_38_1789354357.jpg',NULL,NULL,'cancelled',286.00),(129,38,'Test','Tester','dumpydummy02@gmail.com','992-912-9784','Meet-up','','','[Campus Meet-up] ','2026-09-14 12:03:50','qrph',0.00,'paid','2026-09-14 06:03:50','uploads/receipts/receipt_38_1789358630.png',NULL,NULL,'cancelled',286.00),(130,38,'Test','Tester','dumpydummy02@gmail.com','992-912-9784','Meet-up','','','[Campus Meet-up] ','2026-09-14 12:26:32','qrph',0.00,'paid','2026-09-14 06:26:32','uploads/receipts/receipt_38_1789359992.png',NULL,NULL,'delivered',286.00),(131,38,'Test','Tester','dumpydummy02@gmail.com','992-912-9784','Meet-up','','','[Campus Meet-up] ','2026-09-14 13:43:07','qrph',0.00,'paid','2026-09-14 07:43:07','uploads/receipts/receipt_38_1789364587.png',NULL,NULL,'delivered',286.00),(132,38,'Test','Tester','dumpydummy02@gmail.com','992-912-9784','Meet-up','','','[Campus Meet-up] ','2026-09-14 15:08:50','qrph',0.00,'paid','2026-09-14 09:08:50','uploads/receipts/receipt_38_1789369730.png',NULL,NULL,'delivered',286.00),(133,41,'Magnus','Magnataur','magnusmagnataur29@gmail.com','0992 912 1301','Meet-up','','','[Campus Meet-up] ','2026-09-14 15:47:02','qrph',0.00,'paid','2026-09-14 09:47:02','uploads/receipts/receipt_41_1789372022.png',NULL,NULL,'delivered',286.00),(134,42,'Alfaidz','Abdillah','abdillahalfaidz20@gmail.com','0992 912 9132','Meet-up','','','[Campus Meet-up] ','2026-09-14 20:32:51','qrph',0.00,'paid','2026-09-14 14:32:51','uploads/receipts/receipt_42_1789389171.jpg',NULL,NULL,'processing',286.00),(135,45,'wala','walawala','walawalawala2022@gmail.com','0992 912 3333','Meet-up','','','[Campus Meet-up] ','2026-09-15 19:43:18','qrph',0.00,'paid','2026-09-15 13:43:18','uploads/receipts/receipt_45_1789472598.jpg',NULL,NULL,'delivered',527.00),(136,47,'toshiro','vinz','toshirovinz@gmail.com','0987 784 5111','Meet-up','','','[Campus Meet-up] ','2026-09-15 20:34:56','qrph',0.00,'paid','2026-09-15 14:34:56','uploads/receipts/receipt_47_1789475696.jpg',NULL,NULL,'delivered',527.00),(137,47,'toshiro','vinz','toshirovinz@gmail.com','0912 345 6789','Meet-up','','','[Campus Meet-up] ','2026-09-15 20:56:15','qrph',0.00,'paid','2026-09-15 14:56:15','uploads/receipts/receipt_47_1789476975.png',NULL,NULL,'delivered',682.00),(138,47,'toshiro','vinz','toshirovinz@gmail.com','0912 345 6792','Meet-up','','','[Campus Meet-up] ','2026-09-15 21:07:26','qrph',0.00,'paid','2026-09-15 15:07:26','uploads/receipts/receipt_47_1789477646.jpg',NULL,NULL,'delivered',286.00),(141,47,'toshiro','vinz','toshirovinz@gmail.com','0912 456 7987','Meet-up','','','[Campus Meet-up] ','2026-09-15 21:14:02','qrph',0.00,'paid','2026-09-15 15:14:02','uploads/receipts/receipt_47_1789478042.png',NULL,NULL,'delivered',364.00),(142,47,'toshiro','vinz','toshirovinz@gmail.com','0912 345 6893','Meet-up','','','[Campus Meet-up] ','2026-09-15 21:16:04','qrph',0.00,'paid','2026-09-15 15:16:04','uploads/receipts/receipt_47_1789478164.png',NULL,NULL,'delivered',286.00),(143,49,'Judey','Kristian','judeykristian@gmail.com','0912 345 6789','Meet-up','','','[Campus Meet-up] ','2026-09-15 22:56:27','qrph',0.00,'paid','2026-09-15 16:56:27','uploads/receipts/receipt_49_1789484187.jpg',NULL,NULL,'delivered',343.50),(144,45,'wala','walawala','walawalawala2022@gmail.com','0912 345 6789','Meet-up','','','[Campus Meet-up] ','2026-09-16 02:52:37','qrph',0.00,'paid','2026-09-15 20:52:37','uploads/receipts/receipt_45_1789498357.jpg',NULL,NULL,'delivered',286.00),(145,60,'Fizzy','Wuzzy','wuzzyfizzy@gmail.com','0912 345 6789','Meet-up','','','[Campus Meet-up] ','2026-09-16 12:42:48','qrph',0.00,'paid','2026-09-16 06:42:48','uploads/receipts/receipt_60_1789533768.jpg',NULL,NULL,'delivered',3162.00),(146,60,'Fizzy','Wuzzy','wuzzyfizzy@gmail.com','0912 345 6789','Meet-up','','','[Campus Meet-up] ','2026-09-16 13:00:30','qrph',0.00,'paid','2026-09-16 07:00:30','uploads/receipts/receipt_60_1789534830.jpg',NULL,NULL,'delivered',3013.50),(147,60,'Fizzy','Wuzzy','wuzzyfizzy@gmail.com','0978 945 6123','Meet-up','','','[Campus Meet-up] ','2026-09-16 13:04:58','qrph',0.00,'paid','2026-09-16 07:04:58','uploads/receipts/receipt_60_1789535098.png',NULL,NULL,'processing',3013.50);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_logs`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=328 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_logs`
--

LOCK TABLES `payment_logs` WRITE;
/*!40000 ALTER TABLE `payment_logs` DISABLE KEYS */;
INSERT INTO `payment_logs` VALUES (14,7,1,'item_status_update','success',0.00,'Order item #7 status updated to: delivered','2025-04-24 16:39:19'),(15,7,1,'item_status_update','success',0.00,'Order item #7 status updated to: shipped','2025-04-24 16:39:22'),(16,7,1,'status_update','success',0.00,'Order status updated to: ','2025-04-24 16:39:24'),(17,7,1,'item_status_update','success',0.00,'Order item #7 status updated to: cancelled','2025-04-24 16:39:29'),(18,8,1,'item_status_update','success',0.00,'Order item #8 status updated to: cancelled','2025-04-24 16:44:56'),(19,9,1,'item_status_update','success',0.00,'Order item #9 status updated to: shipped','2025-04-25 03:28:43'),(20,9,1,'item_status_update','success',0.00,'Order item #9 status updated to: delivered','2025-04-25 03:37:21'),(21,9,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-25 03:37:23'),(22,10,1,'item_shipped','success',0.00,'Item #11 marked as shipped','2025-04-25 05:03:56'),(23,10,1,'item_shipped','success',0.00,'Item #10 marked as shipped','2025-04-25 05:03:58'),(24,10,2,'delivery_confirmation','success',0.00,'Customer confirmed receipt of item #10','2025-04-25 05:04:10'),(25,10,2,'delivery_confirmation','success',0.00,'Customer confirmed receipt of item #11','2025-04-25 05:04:16'),(26,10,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-25 05:04:23'),(27,11,1,'item_shipped','success',0.00,'Item #12 marked as shipped','2025-04-25 05:49:07'),(28,11,1,'payment_received','success',0.00,'Payment received for COD order upon delivery','2025-04-25 05:49:21'),(29,11,1,'pending_confirmation','success',0.00,'Item #12 marked for delivery, waiting for customer confirmation','2025-04-25 05:49:21'),(30,11,2,'delivery_confirmation','success',0.00,'Customer confirmed receipt of item #12','2025-04-25 05:54:23'),(31,12,1,'item_shipped','success',0.00,'Item #13 marked as shipped','2025-04-25 05:54:37'),(32,12,1,'payment_received','success',0.00,'Payment received for COD order upon delivery','2025-04-25 05:54:56'),(33,12,1,'pending_confirmation','success',0.00,'Item #13 marked for delivery, waiting for customer confirmation','2025-04-25 05:54:56'),(34,12,2,'delivery_confirmation','success',0.00,'Customer confirmed receipt of item #13','2025-04-25 05:55:09'),(35,13,1,'item_status_update','success',0.00,'Order item #14 status updated to: cancelled','2025-04-25 06:58:58'),(36,14,1,'item_status_update','success',0.00,'Order item #15 status updated to: cancelled','2025-04-25 13:45:10'),(37,15,1,'item_status_update','success',0.00,'Order item #16 status updated to: cancelled','2025-04-25 13:46:32'),(38,17,1,'item_status_update','success',0.00,'Order item #18 status updated to: cancelled','2025-04-25 13:53:24'),(39,20,1,'item_shipped','success',0.00,'Item #21 marked as shipped','2025-04-25 14:07:28'),(40,20,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-25 14:07:31'),(41,20,1,'pending_confirmation','success',0.00,'Item #21 marked for delivery, waiting for customer confirmation','2025-04-25 14:07:35'),(42,20,2,'delivery_confirmation','success',0.00,'Customer confirmed receipt of item #21','2025-04-25 14:07:44'),(43,19,1,'item_status_update','success',0.00,'Order item #20 status updated to: cancelled','2025-04-25 14:08:25'),(44,25,1,'item_status_update','success',0.00,'Order item #26 status updated to: cancelled','2025-04-25 14:46:16'),(45,26,1,'item_shipped','success',0.00,'Item #27 marked as shipped','2025-04-25 14:46:18'),(46,26,1,'payment_received','success',0.00,'Payment received for COD order upon delivery','2025-04-25 14:46:20'),(47,26,1,'pending_confirmation','success',0.00,'Item #27 marked for delivery, waiting for customer confirmation','2025-04-25 14:46:20'),(48,26,2,'delivery_confirmation','success',0.00,'Customer confirmed receipt of item #27','2025-04-25 14:46:27'),(49,23,1,'item_status_update','success',0.00,'Order item #24 status updated to: cancelled','2025-04-25 14:46:35'),(50,22,1,'item_status_update','success',0.00,'Order item #23 status updated to: cancelled','2025-04-25 14:46:37'),(51,21,1,'item_status_update','success',0.00,'Order item #22 status updated to: cancelled','2025-04-25 14:46:41'),(52,27,1,'item_shipped','success',0.00,'Item #28 marked as shipped','2025-04-25 15:03:25'),(53,27,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-25 15:03:29'),(54,28,1,'item_shipped','success',0.00,'Item #29 marked as shipped','2025-04-25 15:03:32'),(55,28,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-25 15:03:34'),(56,28,1,'status_update','success',0.00,'Order status updated to: ','2025-04-25 15:03:36'),(57,29,1,'item_status_update','success',0.00,'Order item #30 status updated to: cancelled','2025-04-25 15:28:11'),(58,16,1,'item_status_update','success',0.00,'Order item #17 status updated to: cancelled','2025-04-25 15:28:28'),(59,18,1,'item_shipped','success',0.00,'Item #19 marked as shipped','2025-04-25 15:40:15'),(60,18,1,'pending_confirmation','success',0.00,'Item #19 marked for delivery, waiting for customer confirmation','2025-04-25 15:40:39'),(61,18,2,'delivery_confirmation','success',0.00,'Customer confirmed receipt of item #19','2025-04-25 15:41:02'),(62,27,1,'pending_confirmation','success',0.00,'Item #28 marked for delivery, waiting for customer confirmation','2025-04-25 15:41:24'),(63,27,2,'delivery_confirmation','success',0.00,'Customer confirmed receipt of item #28','2025-04-25 15:41:29'),(64,30,1,'item_shipped','success',0.00,'Item #31 marked as shipped','2025-04-25 15:51:36'),(65,30,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-25 15:51:58'),(66,30,1,'pending_confirmation','success',0.00,'Item #31 marked for delivery, waiting for customer confirmation','2025-04-25 15:52:01'),(67,31,1,'item_shipped','success',0.00,'Item #32 marked as shipped','2025-04-25 16:05:51'),(68,31,1,'payment_received','success',0.00,'Payment received for COD order upon delivery','2025-04-25 16:05:53'),(69,31,1,'pending_confirmation','success',0.00,'Item #32 marked for delivery, waiting for customer confirmation','2025-04-25 16:05:53'),(70,32,1,'item_shipped','success',0.00,'Item #33 marked as shipped','2025-04-25 16:13:08'),(71,32,1,'item_status_update','success',0.00,'Order item #33 status updated to: processing','2025-04-25 16:13:10'),(72,32,1,'item_shipped','success',0.00,'Item #33 marked as shipped','2025-04-25 16:13:13'),(73,32,1,'payment_received','success',0.00,'Payment received for COD order upon delivery','2025-04-25 16:13:15'),(74,32,1,'pending_confirmation','success',0.00,'Item #33 marked for delivery, waiting for customer confirmation','2025-04-25 16:13:15'),(75,33,1,'item_shipped','success',0.00,'Item #34 marked as shipped','2025-04-25 17:31:08'),(76,33,1,'payment_received','success',0.00,'Payment received for COD order upon delivery','2025-04-25 17:31:13'),(77,33,1,'pending_confirmation','success',0.00,'Item #34 marked for delivery, waiting for customer confirmation','2025-04-25 17:31:13'),(78,34,1,'item_shipped','success',0.00,'Item #35 marked as shipped','2025-04-25 17:42:01'),(79,34,1,'payment_received','success',0.00,'Payment received for COD order upon delivery','2025-04-25 17:42:02'),(80,34,1,'pending_confirmation','success',0.00,'Item #35 marked for delivery, waiting for customer confirmation','2025-04-25 17:42:02'),(81,35,1,'item_shipped','success',0.00,'Item #36 marked as shipped','2025-04-25 17:54:49'),(82,35,1,'payment_received','success',0.00,'Payment received for COD order upon delivery','2025-04-25 17:54:51'),(83,35,1,'pending_confirmation','success',0.00,'Item #36 marked for delivery, waiting for customer confirmation','2025-04-25 17:54:51'),(84,36,1,'item_shipped','success',0.00,'Item #37 marked as shipped','2025-04-25 18:04:38'),(85,36,1,'payment_received','success',0.00,'Payment received for COD order upon delivery','2025-04-25 18:04:42'),(86,36,1,'pending_confirmation','success',0.00,'Item #37 marked for delivery, waiting for customer confirmation','2025-04-25 18:04:42'),(87,37,1,'item_shipped','success',0.00,'Item #38 marked as shipped','2025-04-25 18:10:13'),(88,37,1,'payment_received','success',0.00,'Payment received for COD order upon delivery','2025-04-25 18:10:15'),(89,37,1,'pending_confirmation','success',0.00,'Item #38 marked for delivery, waiting for customer confirmation','2025-04-25 18:10:15'),(90,38,1,'item_shipped','success',0.00,'Item #39 marked as shipped','2025-04-25 18:20:51'),(91,38,1,'payment_received','success',0.00,'Payment received for COD order upon delivery','2025-04-25 18:20:57'),(92,38,1,'pending_confirmation','success',0.00,'Item #39 marked for delivery, waiting for customer confirmation','2025-04-25 18:20:57'),(93,39,1,'item_shipped','success',0.00,'Item #40 marked as shipped','2025-04-26 04:09:21'),(94,39,1,'payment_received','success',0.00,'Payment received for COD order upon delivery','2025-04-26 04:09:22'),(95,39,1,'pending_confirmation','success',0.00,'Item #40 marked for delivery, waiting for customer confirmation','2025-04-26 04:09:23'),(96,40,1,'item_shipped','success',0.00,'Item #41 marked as shipped','2025-04-26 05:12:03'),(97,40,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 05:12:07'),(98,40,1,'pending_confirmation','success',0.00,'Item #41 marked for delivery, waiting for customer confirmation','2025-04-26 05:12:09'),(99,42,1,'item_shipped','success',0.00,'Item #43 marked as shipped','2025-04-26 06:33:11'),(100,42,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 06:33:14'),(101,42,1,'pending_confirmation','success',0.00,'Item #43 marked for delivery, waiting for customer confirmation','2025-04-26 06:33:16'),(102,43,1,'item_shipped','success',0.00,'Item #44 marked as shipped','2025-04-26 06:50:27'),(103,43,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 06:50:29'),(104,43,1,'pending_confirmation','success',0.00,'Item #44 marked for delivery, waiting for customer confirmation','2025-04-26 06:50:30'),(105,44,1,'item_shipped','success',0.00,'Item #45 marked as shipped','2025-04-26 08:55:49'),(106,44,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 08:55:51'),(107,44,1,'pending_confirmation','success',0.00,'Item #45 marked for delivery, waiting for customer confirmation','2025-04-26 08:55:53'),(108,45,1,'item_shipped','success',0.00,'Item #46 marked as shipped','2025-04-26 09:31:59'),(109,45,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 09:32:01'),(110,45,1,'pending_confirmation','success',0.00,'Item #46 marked for delivery, waiting for customer confirmation','2025-04-26 09:32:04'),(111,45,2,'purchase_confirmation','success',100.00,'Purchase confirmed for book: 24','2025-04-26 09:32:13'),(112,45,2,'purchase_confirmation','success',100.00,'Purchase confirmed for book: 24','2025-04-26 09:32:18'),(113,45,2,'purchase_confirmation','success',100.00,'Purchase confirmed for book: 24','2025-04-26 09:32:20'),(114,45,2,'purchase_confirmation','success',100.00,'Purchase confirmed for book: 24','2025-04-26 09:32:23'),(115,45,2,'purchase_confirmation','success',100.00,'Purchase confirmed for book: 24','2025-04-26 09:32:33'),(116,46,1,'item_shipped','success',0.00,'Item #47 marked as shipped','2025-04-26 12:07:20'),(117,46,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 12:07:22'),(118,46,1,'pending_confirmation','success',0.00,'Item #47 marked for delivery, waiting for customer confirmation','2025-04-26 12:07:25'),(119,47,1,'item_shipped','success',0.00,'Item #48 marked as shipped','2025-04-26 12:25:49'),(120,47,1,'payment_received','success',0.00,'Payment received for COD order upon delivery','2025-04-26 12:25:51'),(121,47,1,'pending_confirmation','success',0.00,'Item #48 marked for delivery, waiting for customer confirmation','2025-04-26 12:25:51'),(122,47,1,'item_status_update','success',0.00,'Order item #48 status updated to: cancelled','2025-04-26 12:26:07'),(123,48,1,'item_shipped','success',0.00,'Item #49 marked as shipped','2025-04-26 12:28:06'),(124,48,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 12:28:10'),(125,48,1,'pending_confirmation','success',0.00,'Item #49 marked for delivery, waiting for customer confirmation','2025-04-26 12:28:12'),(126,49,1,'item_shipped','success',0.00,'Item #50 marked as shipped','2025-04-26 12:44:51'),(127,49,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 12:44:54'),(128,49,1,'pending_confirmation','success',0.00,'Item #50 marked for delivery, waiting for customer confirmation','2025-04-26 12:44:56'),(129,50,1,'item_shipped','success',0.00,'Item #51 marked as shipped','2025-04-26 12:45:59'),(130,50,2,'purchase_confirmation','success',625.00,'Purchase confirmed for book: 26','2025-04-26 12:46:12'),(131,51,1,'item_shipped','success',0.00,'Item #52 marked as shipped','2025-04-26 13:10:22'),(132,51,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 13:10:24'),(133,51,1,'pending_confirmation','success',0.00,'Item #52 marked for delivery, waiting for customer confirmation','2025-04-26 13:10:27'),(134,53,1,'item_status_update','success',0.00,'Order item #54 status updated to: cancelled','2025-04-26 13:35:58'),(135,54,1,'item_shipped','success',0.00,'Item #55 marked as shipped','2025-04-26 13:36:00'),(136,54,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 13:36:02'),(137,54,1,'pending_confirmation','success',0.00,'Item #55 marked for delivery, waiting for customer confirmation','2025-04-26 13:36:03'),(138,55,1,'item_shipped','success',0.00,'Item #56 marked as shipped','2025-04-26 13:40:21'),(139,55,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 13:40:24'),(140,55,1,'pending_confirmation','success',0.00,'Item #56 marked for delivery, waiting for customer confirmation','2025-04-26 13:40:27'),(141,56,1,'item_shipped','success',0.00,'Item #57 marked as shipped','2025-04-26 13:40:30'),(142,56,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 13:40:31'),(143,56,1,'pending_confirmation','success',0.00,'Item #57 marked for delivery, waiting for customer confirmation','2025-04-26 13:40:33'),(144,56,2,'purchase_confirmation','success',50.00,'Purchase confirmed for book: 24','2025-04-26 13:40:39'),(145,55,2,'purchase_confirmation','success',100.00,'Purchase confirmed for book: 24','2025-04-26 13:40:43'),(146,56,2,'purchase_confirmation','success',50.00,'Purchase confirmed for book: 24','2025-04-26 13:40:45'),(147,55,2,'purchase_confirmation','success',100.00,'Purchase confirmed for book: 24','2025-04-26 13:40:47'),(148,56,2,'purchase_confirmation','success',50.00,'Purchase confirmed for book: 24','2025-04-26 13:43:12'),(149,55,2,'purchase_confirmation','success',100.00,'Purchase confirmed for book: 24','2025-04-26 13:43:15'),(150,56,2,'purchase_confirmation','success',50.00,'Purchase confirmed for book: 24','2025-04-26 13:45:27'),(151,55,2,'purchase_confirmation','success',100.00,'Purchase confirmed for book: 24','2025-04-26 13:45:29'),(152,56,2,'purchase_confirmation','success',50.00,'Purchase confirmed for book: 24','2025-04-26 13:45:33'),(153,56,2,'purchase_confirmation','success',50.00,'Purchase confirmed for book: 24','2025-04-26 13:46:06'),(154,56,2,'purchase_confirmation','success',50.00,'Purchase confirmed for book: 24','2025-04-26 13:46:11'),(155,56,2,'purchase_confirmation','success',50.00,'Purchase confirmed for book: 24','2025-04-26 13:46:19'),(156,56,2,'purchase_confirmation','success',50.00,'Purchase confirmed for book: 24','2025-04-26 13:55:32'),(157,55,2,'purchase_confirmation','success',100.00,'Purchase confirmed for book: 24','2025-04-26 13:55:34'),(158,56,2,'purchase_confirmation','success',50.00,'Purchase confirmed for book: 24','2025-04-26 13:55:36'),(159,56,2,'purchase_confirmation','success',50.00,'Purchase confirmed for book: 24','2025-04-26 14:00:04'),(160,56,2,'purchase_confirmation','success',50.00,'Purchase confirmed for book: 24','2025-04-26 14:01:56'),(161,56,2,'purchase_confirmation','success',50.00,'Purchase confirmed for book: 24','2025-04-26 14:07:07'),(162,58,1,'item_shipped','success',0.00,'Item #59 marked as shipped','2025-04-26 14:11:40'),(163,58,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 14:11:43'),(164,58,1,'pending_confirmation','success',0.00,'Item #59 marked for delivery, waiting for customer confirmation','2025-04-26 14:11:46'),(165,59,1,'item_shipped','success',0.00,'Item #60 marked as shipped','2025-04-26 14:25:37'),(166,59,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 14:25:40'),(167,59,1,'pending_confirmation','success',0.00,'Item #60 marked for delivery, waiting for customer confirmation','2025-04-26 14:25:42'),(168,60,1,'item_shipped','success',0.00,'Item #61 marked as shipped','2025-04-26 14:27:18'),(169,60,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 14:27:20'),(170,60,1,'pending_confirmation','success',0.00,'Item #61 marked for delivery, waiting for customer confirmation','2025-04-26 14:27:21'),(171,61,1,'item_shipped','success',0.00,'Item #62 marked as shipped','2025-04-26 14:28:39'),(172,61,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 14:28:42'),(173,61,1,'pending_confirmation','success',0.00,'Item #62 marked for delivery, waiting for customer confirmation','2025-04-26 14:28:43'),(174,62,1,'item_shipped','success',0.00,'Item #63 marked as shipped','2025-04-26 14:31:26'),(175,62,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 14:31:28'),(176,62,1,'pending_confirmation','success',0.00,'Item #63 marked for delivery, waiting for customer confirmation','2025-04-26 14:31:30'),(177,63,1,'item_shipped','success',0.00,'Item #64 marked as shipped','2025-04-26 14:34:05'),(178,63,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 14:34:07'),(179,63,1,'pending_confirmation','success',0.00,'Item #64 marked for delivery, waiting for customer confirmation','2025-04-26 14:34:08'),(180,64,1,'item_shipped','success',0.00,'Item #65 marked as shipped','2025-04-26 14:37:57'),(181,64,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 14:38:00'),(182,64,1,'pending_confirmation','success',0.00,'Item #65 marked for delivery, waiting for customer confirmation','2025-04-26 14:38:02'),(183,64,2,'purchase_confirmation','success',552.00,'Purchase confirmed for book: 22','2025-04-26 14:38:08'),(184,65,1,'item_shipped','success',0.00,'Item #66 marked as shipped','2025-04-26 14:39:35'),(185,65,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 14:39:37'),(186,65,1,'pending_confirmation','success',0.00,'Item #66 marked for delivery, waiting for customer confirmation','2025-04-26 14:39:39'),(187,65,2,'purchase_confirmation','success',132.00,'Purchase confirmed for book: 22','2025-04-26 14:39:46'),(188,68,1,'item_shipped','success',0.00,'Item #69 marked as shipped','2025-04-26 15:01:38'),(189,68,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 15:01:41'),(190,68,1,'pending_confirmation','success',0.00,'Item #69 marked for delivery, waiting for customer confirmation','2025-04-26 15:01:42'),(191,68,2,'purchase_confirmation','success',96.00,'Purchase confirmed for book: 22','2025-04-26 15:01:59'),(192,80,1,'item_shipped','success',0.00,'Item #81 marked as shipped','2025-04-26 15:33:56'),(193,80,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 15:34:04'),(194,80,1,'pending_confirmation','success',0.00,'Item #81 marked for delivery, waiting for customer confirmation','2025-04-26 15:34:05'),(195,80,2,'purchase_confirmation','success',144.00,'Purchase confirmed for book: 22','2025-04-26 15:34:13'),(196,86,1,'item_shipped','success',0.00,'Item #87 marked as shipped','2025-04-26 15:49:12'),(197,86,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 15:49:13'),(198,86,1,'pending_confirmation','success',0.00,'Item #87 marked for delivery, waiting for customer confirmation','2025-04-26 15:49:15'),(199,85,1,'item_shipped','success',0.00,'Item #86 marked as shipped','2025-04-26 15:49:17'),(200,85,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-26 15:49:20'),(201,85,1,'pending_confirmation','success',0.00,'Item #86 marked for delivery, waiting for customer confirmation','2025-04-26 15:49:22'),(202,85,2,'purchase_confirmation','success',552.00,'Purchase confirmed for book: 22','2025-04-26 15:49:34'),(203,90,1,'item_shipped','success',0.00,'Item #91 marked as shipped','2025-04-27 05:55:27'),(204,90,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-04-27 05:55:30'),(205,90,1,'pending_confirmation','success',0.00,'Item #91 marked for delivery, waiting for customer confirmation','2025-04-27 05:56:48'),(206,90,1,'pending_confirmation','success',0.00,'Item #91 marked for delivery, waiting for customer confirmation','2025-04-27 05:56:50'),(207,90,1,'pending_confirmation','success',0.00,'Item #91 marked for delivery, waiting for customer confirmation','2025-04-27 05:56:53'),(208,90,2,'book_return','success',0.00,'Book returned: Book of pocket','2025-04-27 08:44:40'),(209,86,2,'return_request','initiated',0.00,'Return request initiated: Book of pocket via Dropoff','2025-04-27 09:54:56'),(210,86,2,'return_received','success',0.00,'Book return received: Book of pocket','2025-04-27 09:55:40'),(211,63,2,'return_request','initiated',0.00,'Return request initiated: Book of pocket via Dropoff','2025-04-27 10:57:13'),(212,42,2,'return_request','initiated',0.00,'Return request initiated: Book of pocket via Dropoff','2025-04-27 13:20:20'),(213,46,2,'return_request','initiated',8.00,'Return request initiated: Book of PWC via Dropoff (Overdue: 4 days)','2025-05-07 09:27:33'),(214,48,2,'return_request','initiated',24.00,'Return request initiated: Horry Potter 1 via Dropoff (Overdue: 4 days)','2025-05-07 09:34:15'),(215,92,1,'item_shipped','success',0.00,'Item #93 marked as shipped','2025-05-07 09:37:48'),(216,92,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Pickup/Meetup order','2025-05-07 09:37:50'),(217,92,1,'pending_confirmation','success',0.00,'Item #93 marked for delivery, waiting for customer confirmation','2025-05-07 09:37:52'),(218,92,1,'status_update','success',0.00,'Order status updated to: ','2025-05-07 09:38:07'),(219,93,1,'item_shipped','success',0.00,'Item #94 marked as shipped','2025-05-07 09:39:35'),(220,93,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Pickup/Meetup order','2025-05-07 09:39:37'),(221,93,1,'pending_confirmation','success',0.00,'Item #94 marked for delivery, waiting for customer confirmation','2025-05-07 09:39:39'),(222,94,1,'item_shipped','success',0.00,'Item #95 marked as shipped','2025-05-07 10:26:21'),(223,94,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-05-07 10:26:24'),(224,94,1,'pending_confirmation','success',0.00,'Item #95 marked for delivery, waiting for customer confirmation','2025-05-07 10:26:26'),(225,95,1,'item_shipped','success',0.00,'Item #96 marked as shipped','2025-05-07 10:36:53'),(226,95,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-05-07 10:45:04'),(227,98,1,'item_status_update','success',0.00,'Order item #99 status updated to: cancelled','2025-05-08 05:55:58'),(228,97,1,'item_status_update','success',0.00,'Order item #98 status updated to: cancelled','2025-05-08 05:56:01'),(229,97,1,'item_status_update','success',0.00,'Order item #98 status updated to: cancelled','2025-05-08 05:57:01'),(230,96,1,'item_status_update','success',0.00,'Order item #97 status updated to: cancelled','2025-05-08 05:57:05'),(231,99,1,'item_shipped','success',0.00,'Item #100 marked as shipped','2025-05-08 05:57:07'),(232,99,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Pickup/Meetup order','2025-05-08 05:57:09'),(233,99,3,'purchase_confirmation','success',120.00,'Purchase confirmed for book: 24','2025-05-08 05:57:13'),(234,100,1,'item_shipped','success',0.00,'Item #101 marked as shipped','2025-05-08 06:02:56'),(235,100,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Cash on Delivery order','2025-05-08 06:02:58'),(236,100,3,'purchase_confirmation','success',120.00,'Purchase confirmed for book: 22','2025-05-08 06:03:03'),(237,102,1,'item_shipped','success',0.00,'Item #103 marked as shipped','2025-05-08 12:29:29'),(238,102,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Pickup/Meetup order','2025-05-08 12:29:32'),(239,103,1,'item_shipped','success',0.00,'Item #104 marked as shipped','2025-05-08 12:36:39'),(240,103,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Pickup/Meetup order','2025-05-08 12:36:47'),(241,103,3,'purchase_confirmation','success',90.00,'Purchase confirmed for book: 24','2025-05-08 12:36:52'),(242,102,3,'purchase_confirmation','success',100.00,'Purchase confirmed for book: 24','2025-05-08 12:36:56'),(243,104,1,'item_shipped','success',0.00,'Item #105 marked as shipped','2025-05-08 12:43:46'),(244,104,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Pickup/Meetup order','2025-05-08 12:43:58'),(245,104,3,'purchase_confirmation','success',110.00,'Purchase confirmed for book: 24','2025-05-08 12:44:01'),(246,108,3,'order_completion','success',99.00,'Order completed with payment method: pickup','2025-05-08 12:59:23'),(247,105,1,'item_status_update','success',0.00,'Order item #106 status updated to: cancelled','2025-05-08 13:00:05'),(248,106,1,'item_status_update','success',0.00,'Order item #107 status updated to: cancelled','2025-05-08 13:00:08'),(249,107,1,'item_status_update','success',0.00,'Order item #108 status updated to: cancelled','2025-05-08 13:00:11'),(250,108,1,'item_shipped','success',0.00,'Item #109 marked as shipped','2025-05-08 13:00:18'),(251,108,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Pickup/Meetup order','2025-05-08 13:00:21'),(252,101,1,'item_status_update','success',0.00,'Order item #102 status updated to: cancelled','2025-05-08 13:00:51'),(253,109,3,'order_completion','success',176.00,'Order completed with payment method: pickup','2025-05-08 13:07:05'),(254,109,1,'item_shipped','success',0.00,'Item #110 marked as shipped','2025-05-08 13:07:25'),(255,109,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Pickup/Meetup order','2025-05-08 13:07:31'),(256,111,3,'order_completion','success',145.20,'Order completed with payment method: pickup','2025-05-08 15:22:46'),(257,112,3,'order_completion','success',79.20,'Order completed with payment method: pickup','2025-05-08 15:25:59'),(258,113,3,'order_completion','success',121.00,'Order completed with payment method: pickup','2025-05-08 15:27:33'),(259,114,2,'order_completion','success',326.70,'Order completed with payment method: pickup','2025-05-09 03:24:36'),(260,117,32,'order_completion','success',5.50,'Order completed with payment method: pickup','2025-07-24 11:32:43'),(261,119,32,'order_completion','success',22.00,'Order completed with payment method: pickup','2025-07-24 14:01:41'),(262,120,33,'order_completion','success',16.50,'Order completed with payment method: pickup','2025-07-26 00:25:41'),(263,120,1,'payment_confirmation','success',0.00,'Cash payment confirmed for Pickup/Meetup order','2025-07-26 00:26:13'),(264,123,35,'item_shipped','success',0.00,'Item #121 marked as shipped','2026-09-08 11:59:21'),(265,123,35,'item_shipped','success',0.00,'Item #121 marked as shipped','2026-09-08 11:59:43'),(266,123,35,'item_shipped','success',0.00,'Item #121 marked as shipped','2026-09-08 11:59:48'),(267,123,35,'pending_confirmation','success',0.00,'Item #121 marked for delivery, waiting for customer confirmation','2026-09-08 12:00:15'),(268,123,35,'status_update','success',0.00,'Order status updated to: processing','2026-09-08 12:03:07'),(269,123,35,'status_update','success',0.00,'Order status updated to: processing','2026-09-08 12:03:09'),(270,123,35,'status_update','success',0.00,'Order status updated to: processing','2026-09-08 12:03:50'),(271,123,35,'status_update','success',0.00,'Seller updated status to: processing','2026-09-08 12:03:52'),(272,123,35,'status_update','success',0.00,'Seller updated status to: pending','2026-09-08 12:04:45'),(273,123,35,'status_update','success',0.00,'Seller updated status to: processing','2026-09-08 12:04:47'),(274,123,35,'status_update','success',0.00,'Seller updated status to: shipped','2026-09-08 12:07:03'),(275,123,35,'status_update','success',0.00,'Seller updated status to: delivered','2026-09-08 12:07:13'),(276,123,35,'status_update','success',0.00,'Seller updated status to: shipped','2026-09-08 12:07:22'),(277,123,35,'status_update','success',0.00,'Seller updated status to: shipped','2026-09-08 12:11:56'),(279,123,35,'status_update','success',0.00,'Seller updated status to: shipped','2026-09-08 12:14:56'),(281,123,36,'order_received','success',250.00,'Order #123 received & physical condition verified: Good (Sig: 45b08ccfa379ee05)','2026-09-08 12:31:11'),(282,124,35,'status_update','success',0.00,'Seller updated status to: pending','2026-09-10 07:09:06'),(283,124,35,'status_update','success',0.00,'Seller updated status to: processing','2026-09-10 07:09:10'),(284,124,35,'status_update','success',0.00,'Seller updated status to: cancelled','2026-09-10 07:09:38'),(285,124,35,'status_update','success',0.00,'Seller updated status to: shipped','2026-09-10 07:10:28'),(286,125,35,'item_shipped','success',0.00,'Item #123 marked as shipped','2026-09-10 07:54:38'),(287,126,35,'status_update','success',0.00,'Seller updated status to: shipped','2026-09-10 07:58:31'),(288,126,35,'status_update','success',0.00,'Seller updated status to: cancelled','2026-09-10 08:01:39'),(289,127,36,'qr_buyer_scanned','success',0.00,'Buyer scanned Seller QR and confirmed receipt for item #125.','2026-09-10 08:15:06'),(290,127,35,'qr_seller_scanned','success',0.00,'Seller scanned Buyer QR and finalized handover for item #125.','2026-09-10 08:15:14'),(291,128,38,'qr_buyer_scanned','success',0.00,'Buyer scanned Seller QR and confirmed receipt for item #126.','2026-09-14 03:11:56'),(292,128,38,'qr_buyer_scanned','success',0.00,'Buyer scanned Seller QR and confirmed receipt for item #126.','2026-09-14 03:14:29'),(293,128,38,'qr_buyer_scanned','success',0.00,'Buyer scanned Seller QR and confirmed receipt for item #126.','2026-09-14 03:15:29'),(294,128,38,'qr_buyer_scanned','success',0.00,'Buyer scanned Seller QR and confirmed receipt for item #126.','2026-09-14 03:35:20'),(295,128,40,'status_update','success',0.00,'Seller updated status to: cancelled','2026-09-14 03:37:16'),(296,129,40,'status_update','success',0.00,'Seller updated status to: cancelled','2026-09-14 04:24:23'),(297,130,40,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #128. Manual override used.','2026-09-14 04:39:39'),(298,131,40,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #129. Manual override used.','2026-09-14 05:54:30'),(299,132,40,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #130. Manual override used.','2026-09-14 07:10:25'),(300,133,40,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #131. Manual override used.','2026-09-14 07:48:11'),(301,135,44,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #133. Manual override used.','2026-09-15 11:44:58'),(302,136,44,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #134. Manual override used.','2026-09-15 12:40:47'),(303,136,44,'status_update','success',0.00,'Seller updated status to: pending','2026-09-15 12:53:10'),(304,136,44,'status_update','success',0.00,'Seller updated status to: pending_meetup','2026-09-15 12:53:12'),(305,136,44,'status_update','success',0.00,'Seller updated status to: processing','2026-09-15 12:53:13'),(306,136,44,'status_update','success',0.00,'Seller updated status to: pending_meetup','2026-09-15 12:53:14'),(307,136,44,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #134. Manual override used.','2026-09-15 12:53:32'),(308,137,44,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #135. Manual override used.','2026-09-15 12:57:07'),(309,138,44,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #136. Manual override used.','2026-09-15 13:07:52'),(310,141,44,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #137. Manual override used.','2026-09-15 13:14:32'),(311,142,44,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #138. Manual override used.','2026-09-15 13:16:48'),(312,141,44,'status_update','success',0.00,'Seller updated status to: delivered','2026-09-15 13:22:23'),(313,143,50,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #139. Manual override used.','2026-09-15 14:57:21'),(314,144,44,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #140. Manual override used.','2026-09-15 18:53:17'),(315,145,52,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #141. Manual override used.','2026-09-16 04:44:41'),(316,146,52,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #142. Manual override used.','2026-09-16 05:00:44'),(317,146,52,'status_update','success',0.00,'Seller updated status to: pending_meetup','2026-09-16 05:03:45'),(318,146,52,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #142. Manual override used.','2026-09-16 05:03:50'),(319,146,52,'status_update','success',0.00,'Seller updated status to: delivered','2026-09-16 05:04:24'),(320,147,52,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #143. Manual override used.','2026-09-16 05:05:22'),(321,147,52,'status_update','success',0.00,'Seller updated status to: pending_meetup','2026-09-16 05:05:38'),(322,147,52,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #143. Manual override used.','2026-09-16 05:05:41'),(323,147,52,'status_update','success',0.00,'Seller updated status to: pending_meetup','2026-09-16 05:05:49'),(324,147,52,'qr_seller_scanned','success',0.00,'Seller finalized handover for item #143. Manual override used.','2026-09-16 05:06:12'),(325,146,52,'status_update','success',0.00,'Seller updated status to: delivered','2026-09-16 05:06:52'),(326,145,52,'status_update','success',0.00,'Seller updated status to: delivered','2026-09-16 05:06:59'),(327,146,52,'status_update','success',0.00,'Seller updated status to: delivered','2026-09-16 05:07:02');
/*!40000 ALTER TABLE `payment_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_payouts`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_payouts`
--

LOCK TABLES `seller_payouts` WRITE;
/*!40000 ALTER TABLE `seller_payouts` DISABLE KEYS */;
INSERT INTO `seller_payouts` VALUES (1,5,144.00,'paid','GCash','0923456789','12345678','2026-09-14 15:49:49','2026-09-14 15:50:10');
/*!40000 ALTER TABLE `seller_payouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sellers`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sellers`
--

LOCK TABLES `sellers` WRITE;
/*!40000 ALTER TABLE `sellers` DISABLE KEYS */;
INSERT INTO `sellers` VALUES (2,1,'Prime','One Person Corporation','Prime','Ariel','','Del Rosario','Davao City','Block 1 Lot 18 Susana Homes 3, Baliok','8000','arielfranco8868@gmail.com','09611347193','uploads/shop_logos/1_unnamed (5).png','approved','2025-04-20 10:06:03','2025-04-20 10:06:12',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(3,35,'Thrift Book','Individual Book Owner','Thrift Book','Drip','','Blitz','Toril, Davao City, Davao del Sur','Toril','8000','dripblitz29@gmail.com','0912456789',NULL,'approved','2026-08-30 03:49:22','2026-08-31 11:19:31','passport','uploads/sellers/35/primary_front_1788061735_f11c6be6.jpg','uploads/sellers/35/primary_back_1788061735_b862cf9b.jpg','barangay_id','uploads/sellers/35/secondary_front_1788061735_b2811430.webp','uploads/sellers/35/secondary_back_1788061735_3cd3ff36.webp','uploads/sellers/35/selfie_1788061735_07e009e9.webp',''),(4,38,'Test','Student Seller','Test','Test','','Tester','Toril, Davao City, Davao del Sur','Toril Davao City','8000','dumpydummy02@gmail.com','992-912-9784',NULL,'rejected','2026-09-13 14:34:25','2026-09-14 16:42:18','national_id','uploads/sellers/38/primary_front_1789309810_14b61079.jpg','uploads/sellers/38/primary_back_1789309810_5c2aee0e.jpg','school_id','uploads/sellers/38/secondary_front_1789309810_3387285f.jpg','uploads/sellers/38/secondary_back_1789309810_3e777db6.jpg','uploads/sellers/38/selfie_1789309810_cae12013.jpg',''),(5,40,'Seller','Individual Book Owner','Seller','Sell','','Seller','Toril, Davao City, Davao del Sur','Toril Davao City','8000','judelarroza2003@gmail.com','991-125-1254',NULL,'approved','2026-09-14 02:46:26','2026-09-14 02:46:57','national_id','uploads/sellers/40/primary_front_1789353967_fb7b4aa2.png','uploads/sellers/40/primary_back_1789353967_40bb6eab.png','school_id','uploads/sellers/40/secondary_front_1789353967_b3e507ef.jpg','uploads/sellers/40/secondary_back_1789353967_aa9d3745.jpg','uploads/sellers/40/selfie_1789353967_60af3e92.jpg',''),(6,42,'bots','Individual Book Owner','bots','Alfaidz','','Abdillah','Toril, Davao City, Davao del Sur','purok10','8000','abdillahalfaidz20@gmail.com','0963 914 3035',NULL,'approved','2026-09-14 16:30:26','2026-09-14 16:36:00','national_id','uploads/sellers/42/primary_front_1789403399_9cd174f6.jpg','uploads/sellers/42/primary_back_1789403399_163d4afc.jpg','tin','uploads/sellers/42/secondary_front_1789403399_db74b793.jpg','uploads/sellers/42/secondary_back_1789403399_6f56c1e4.jpg','uploads/sellers/42/selfie_1789403399_21f889a0.jpg',''),(7,43,'bokbok','Individual Book Owner','bokbok','Tats','','Kamid','Catalunan Grande, Digos City, Davao del Sur','purok10','8000','kamidtats@gmail.com','0963 914 3035',NULL,'rejected','2026-09-14 17:21:22','2026-09-14 17:23:32','driver_license','uploads/sellers/43/primary_front_1789406380_a79f46f2.jpg','uploads/sellers/43/primary_back_1789406380_fdaf394c.jpg','tin','uploads/sellers/43/secondary_front_1789406380_b0c11224.jpg','uploads/sellers/43/secondary_back_1789406380_63517cde.jpg','uploads/sellers/43/selfie_1789406380_05633d83.jpg',''),(8,44,'Zenny','Individual Book Owner','Zenny','Jude','','Larroza','Toril, Davao City, Davao del Sur','Ilocano village','8000','jkyrax29@gmail.com','0912 457 8412',NULL,'approved','2026-09-15 11:18:15','2026-09-15 11:18:41','national_id','uploads/sellers/44/primary_front_1789471083_a269cbb4.jpg','uploads/sellers/44/primary_back_1789471083_a3b14e3c.jpg','tin','uploads/sellers/44/secondary_front_1789471083_8e0d8365.jpg','uploads/sellers/44/secondary_back_1789471083_f945f1f5.jpg','uploads/sellers/44/selfie_1789471083_aa69f115.jpg','https://www.facebook.com/paulangelo.ismael'),(9,50,'Creed Aventus','Bookstore / Library Hub','Creed Aventus','Creed','','Aventus','Toril, Davao City, Davao del Sur','2F6Q+FJG','8000','zenzenyu0@gmail.com','0978 945 6123',NULL,'approved','2026-09-15 14:31:48','2026-09-15 14:31:58','national_id','uploads/sellers/50/primary_front_1789482693_a70a3e14.png','uploads/sellers/50/primary_back_1789482693_22975a9c.png','tin','uploads/sellers/50/secondary_front_1789482693_f497ca8a.png','uploads/sellers/50/secondary_back_1789482693_e19ea2a5.png','uploads/sellers/50/selfie_1789482693_33075fbc.png',''),(10,52,'XIVVY','Individual Book Owner','XIVVY','Xiv','','Zenzen','Bajada, Davao City, Davao del Sur','Toril','8000','xivzenzen@gmail.com','0978 945 6123',NULL,'approved','2026-09-16 04:17:57','2026-09-16 04:18:30','passport','uploads/sellers/52/primary_front_1789532265_87231432.png','uploads/sellers/52/primary_back_1789532265_8feb9de9.png','philhealth','uploads/sellers/52/secondary_front_1789532265_2d420bf8.png','uploads/sellers/52/secondary_back_1789532265_4c4d9cd2.png','uploads/sellers/52/selfie_1789532265_7c9a478a.png',''),(11,61,'BookWagon Official Store',NULL,'BookWagon Official Store','BookWagon',NULL,'Seller',NULL,'Manila, Philippines',NULL,'seller@bookwagon.com','09123456789',NULL,'approved','2026-09-18 12:21:32','2026-09-18 12:21:32',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `sellers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `swap_logistics`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `swap_logistics`
--

LOCK TABLES `swap_logistics` WRITE;
/*!40000 ALTER TABLE `swap_logistics` DISABLE KEYS */;
INSERT INTO `swap_logistics` VALUES (1,3,'meetup','2025-05-08 13:29:00','UM matina','none','pending','2025-05-07 05:29:44','2025-05-07 05:29:44'),(2,1,'meetup','2025-05-08 13:31:00','Um matina campus','none','pending','2025-05-07 05:32:06','2025-05-07 05:32:06'),(3,2,'meetup','2025-05-08 16:34:00','UM matina','','pending','2025-05-07 05:32:40','2025-05-07 05:32:40'),(4,4,'meetup','2025-05-09 16:25:00','Davao','Davao','pending','2025-05-09 06:24:42','2025-05-09 06:24:42');
/*!40000 ALTER TABLE `swap_logistics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `swap_requests`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `swap_requests`
--

LOCK TABLES `swap_requests` WRITE;
/*!40000 ALTER TABLE `swap_requests` DISABLE KEYS */;
INSERT INTO `swap_requests` VALUES (1,3,1,2,'gumana ka ','accepted','2025-05-07 05:05:33','2025-05-07 05:08:38'),(2,2,3,3,'Pede','accepted','2025-05-07 05:22:43','2025-05-07 05:22:53'),(3,2,2,3,'Pede','accepted','2025-05-07 05:29:26','2025-05-07 05:29:34'),(4,3,6,2,'Book','accepted','2025-05-09 06:23:44','2025-05-09 06:23:59'),(5,1,8,3,'123123','pending','2025-11-05 02:52:47','2025-11-05 02:52:47'),(6,34,9,1,'123124','pending','2025-11-05 02:54:58','2025-11-05 02:54:58');
/*!40000 ALTER TABLE `swap_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_favorites`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_favorites`
--

LOCK TABLES `user_favorites` WRITE;
/*!40000 ALTER TABLE `user_favorites` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'arielfranco8868@gmail.com','$2y$10$IgpeNIIpUtrPbQN0d.zjf.ouBUJnIHJAjjMs5nNMqROJVw/v1AZ2G','','2025-04-10 04:12:21','2026-09-13 12:57:11','Ariel','','Del Rosario','seller','uploads/profile_pictures/1_unnamed (5).png','','A passionate book lover with an insatiable curiosity and a deep appreciation for the written word. Whether it???s the scent of a freshly printed novel or the well-worn pages of a timeless classic, books have always been a gateway to imagination, knowledge, and connection. From fiction and fantasy to history and memoir, this bibliophile finds joy in exploring diverse genres and sharing thoughtful reviews and recommendations with fellow readers. When not reading, they enjoy visiting bookstores, attending literary events, and curating an ever-growing to-be-read list.','Philippines','Davao City','8000','',NULL,NULL,NULL,0,NULL,18,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(2,'sss@gmail.com','$2y$10$gruaIux67gCzAFXXogFbrOcoO/tF47fKhV3Lv8bzWXNl8lpsNdrjS','','2025-04-18 14:20:50','2026-09-13 12:57:11','Ariel','sss','Del Rosario','user','uploads/profile_pictures/2_223-2232352_pwc-of-davao-philippine-womens-college-of-davao.png','09611347193','','Philippines','Davao City','8000','',NULL,NULL,NULL,0,NULL,1,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(3,'Dangrey@gmail.com','$2y$10$icbdQktrBr2ZMpkbhRB2I.djsCPwHvEydIf648rz0LUmY9yT7OZ2q','','2025-04-28 02:47:53','2026-09-13 12:57:11','Dangrey','','Cutie','user','uploads/profile_pictures/3_giphy.gif','','A passionate book lover with an insatiable curiosity and a deep appreciation for the written word. Whether it???s the scent of a freshly printed novel or the well-worn pages of a timeless classic, books have always been a gateway to imagination, knowledge, and connection. From fiction and fantasy to history and memoir, this bibliophile finds joy in exploring diverse genres and sharing thoughtful reviews and recommendations with fellow readers. When not reading, they enjoy visiting bookstores, attending literary events, and curating an ever-growing to-be-read list.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,13,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(4,'coco@gmail.com','$2y$10$xUlZ8CXj9SO0EnH2fNtIt.K63ZR0c3og0z562C8VNFyI4P4XSL8xe','','2025-05-12 04:07:21','2026-09-13 12:57:11','Kwak','Kwak','Del Rosario','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,2,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(25,'hi@gmail.com','$2y$10$giczyiCzKEiKByZc4D1npOyRURt0dxg9hrk4BLlJTo/VUPgEAWkWO','','2025-05-13 23:19:43','2026-09-13 12:57:11','hi','hi','hi','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,1,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(26,'123@gmail.com','$2y$10$o5f6Wun/kxOQbuzVfX9jue0jzYdI0i98pHzZHv5Ljzg5npYX1JGBa','','2025-05-13 23:39:50','2026-09-13 12:57:11','Bella','sss','Del Rosario','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,1,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(27,'reyarig00@gmail.com','$2y$10$5jrLFwK.6gSQLUPRzbdfFO5.Mca2LK5lhHkJnG.sMtsQ1Cg.6Jayy','','2025-05-14 02:50:24','2026-09-13 12:57:11','Reynald','Sonsona','Arig','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,2,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(28,'jude@gmail.com','$2y$10$LsJTbkOklhZEz23ntwaqyuyEy.YHAjU0ZJv/kAD7pIhUfG1Sgsdkm','','2025-05-14 03:36:35','2026-09-13 12:57:11','judekristian','galas','larroza','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,3,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(29,'MAMA@gmail.com','$2y$10$qQBW20MX2xTy2WZjAKpuZeR9ev.1PMAdkQgYTt1K8THbuHc4yS6Im','','2025-05-14 03:41:52','2026-09-13 12:57:11','POTANGINA','MO','PAKYU','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(30,'lester@gmail.com','$2y$10$9rcHLGnVX3GVRtFV33HwtODEmMIMoHnN80iFNrRI2RKyK06vuupOq','','2025-05-14 04:25:52','2026-09-13 12:57:11','Lester','','Andico','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,1,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(31,'awemj@gmail.com','$2y$10$T2slVEHeA8hnxmyhA3/R1O7myA/7R5dqmcrsGqT7dUJDP8yLFkH6C','','2025-05-14 04:56:36','2026-09-13 12:57:11','mj roz','abadiah','tsukigata','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,1,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(32,'kuku@gmail.com','$2y$10$GUMHNSPhqJ0cipj4.7/K2OrFwFh/lzeRd/XhO9kil.Y3JlbX3X1eK','','2025-07-24 11:31:10','2026-09-13 12:57:11','Kuku','kuku','kuku','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,4,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(33,'ariel@gmail.com','$2y$10$r67uyneL5Dw8dEK8grKi6e8mpcQBldbS7SQ46wRLFTFBH.RSErVnS','','2025-07-26 00:07:40','2026-09-13 12:57:11','jude','hi','Ariel Del Rosario','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,6,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(34,'kwk@gmail.com','$2y$10$wlUQoRoh7CwCLWHjPqHeNOrgno8dV21dBTtGU7xFL.sY2D6dGZm/m','','2025-11-05 02:53:47','2026-09-13 12:57:11','Lester','John','Andico','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,1,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(35,'dripblitz29@gmail.com','$2y$10$Ws1Qf2PNpbjvXf.aUmbYNO894dVzE.xR4iuCJLjt28GfaIjcCa1Tm','dripblitz29989','2026-08-30 02:27:55','2026-09-13 12:57:11','Drip',NULL,'Blitz','seller',NULL,'0912456789',NULL,NULL,NULL,'8000',NULL,NULL,'Toril','Davao City',0,NULL,16,'WFS4YW32CODBS5XV',0,'google','102216053378915502348','active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(36,'judekristian08@gmail.com','$2y$10$KT2OGjacnH8mI6aaQrDAF.4r.2ZbbeLtfpXswbM7i/yOy9IzIKpby','','2026-08-30 04:21:23','2026-09-14 04:25:23','Jude Kristian',NULL,'Larroza',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,13,'WUBEZCYPH4LFP5BR',0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(37,'Judey123@gmail.com','$2y$10$ss3EWvoK6DqBS8NbiWNhYOxBuSLI/nGUVC1vPN2px1qRoyfBXY8wi','','2026-09-10 08:02:58','2026-09-13 12:57:11','Judey',NULL,'Larroza',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,2,'M3CEPCFUIGWXTWKT',1,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(38,'dumpydummy02@gmail.com','$2y$10$w0DoWRUTsawnjCndLwevxO9l4VcZTf8l8CFaw/tplK8cvl1cH9VOm','','2026-09-13 13:35:27','2026-09-14 07:25:15','Test',NULL,'Tester',NULL,NULL,'992-912-9784',NULL,NULL,NULL,'8000',NULL,'GCash','Toril Davao City','Davao City',0,NULL,17,NULL,0,'local',NULL,'active','Test Lang','111111111111','uploads/sellers/38/payout_qr_1789309810_74b28729.jpg','verified','uploads/ids/id_38_1789354357.jpg',1,NULL,0,NULL,'[\"3339-ECEA\",\"077E-BB6B\",\"FD60-15EA\",\"FEA9-1BA6\"]',0.00),(40,'judelarroza2003@gmail.com','$2y$10$Uip2GVT9D0.HEYBtVCY2K.52Qo9UPY.8Hwr9sW5Cn6LtZQiopJhay','','2026-09-14 02:38:12','2026-09-15 11:32:01','Sell',NULL,'Seller','seller',NULL,'991-125-1254',NULL,NULL,NULL,'8000',NULL,'GCash','Toril Davao City','Davao City',0,NULL,16,NULL,0,'local',NULL,'active','Sell Seller','123456789','uploads/sellers/40/payout_qr_1789353967_97ee312b.png','unverified',NULL,1,NULL,0,NULL,'[\"F109-640C\",\"AE77-2102\",\"2A66-CF74\",\"6D7F-84C7\",\"2B65-ED3D\"]',0.00),(41,'magnusmagnataur29@gmail.com','$2y$10$g06D8PV/Waju8w8Y99wuOurTxd7Eb.R.c/n4RC16Iu6wB4QbBNkNm','magnusmagnataur29565','2026-09-14 07:28:22','2026-09-15 11:15:53','Magnus',NULL,'Magnataur','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Maya',NULL,NULL,0,NULL,0,NULL,0,'google','109966654851654734781','active','Magnus','0988554411','uploads/qr_codes/qr_41_1789370954.png','unverified',NULL,0,NULL,0,NULL,'[\"1F4D-249A\",\"EFEC-AA98\",\"FE1D-62A3\",\"EFCD-6B1F\",\"44E4-CD10\"]',500.00),(42,'abdillahalfaidz20@gmail.com','$2y$10$SX.ytjgxheX/cm2qVxm/UeS1mmmayrFUFZM0PWetK3YwJlofbDgGK','abdillahalfaidz20603','2026-09-14 11:45:25','2026-09-15 11:15:56','Alfaidz',NULL,'Abdillah','seller',NULL,'0963 914 3035',NULL,NULL,NULL,'8000',NULL,'GCash','purok10','Davao City',0,NULL,0,NULL,0,'google','110638350723228841658','active','Alfaidz Abdillah','0963 914 3035','uploads/sellers/42/payout_qr_1789403399_167c0fe8.jpg','unverified',NULL,0,NULL,0,NULL,'[\"741A-5672\",\"A274-5DF8\",\"4FD0-468C\",\"BC09-ED94\",\"F130-4473\"]',0.00),(43,'kamidtats@gmail.com','$2y$10$HyDiorJi3yQ0LdW5Ro3ZXeX3LGRgFAs/mdxeTzyf2bvG18zn3ScPe','kamidtats507','2026-09-14 17:14:33','2026-09-15 03:26:01','Tats',NULL,'Kamid','user',NULL,'0963 914 3035',NULL,NULL,NULL,'8000',NULL,'Maya','purok10','Digos City',0,NULL,0,NULL,1,'google','114447240975996658416','active','fsfafa','0963 914 3035','uploads/sellers/43/payout_qr_1789406380_7dcda89d.jpg','unverified',NULL,0,NULL,0,NULL,'[\"9345-7753\",\"071F-E051\",\"B370-B3CA\",\"694B-2D2B\",\"FF61-2708\"]',0.00),(44,'jkyrax29@gmail.com','$2y$10$enr9GSTXdqLbRWjVy56fKekCWbvxIqQRHCLU7Ccc1T3eketkelCQS','','2026-09-15 11:14:18','2026-09-15 18:49:06','Jude',NULL,'Larroza','seller',NULL,'0912 457 8412',NULL,NULL,NULL,'8000',NULL,'GCash','Ilocano village','Davao City',0,NULL,8,NULL,0,'local',NULL,'active','Jude Kristian','0912 547 8887','uploads/sellers/44/payout_qr_1789471083_b3b4d2bf.jpg','unverified',NULL,1,'e0fed745a837e2074c55b60fef347a37',0,NULL,'[\"015C-25F2\",\"8FD0-4CEE\",\"7B8C-6476\",\"69D1-9C12\",\"6E49-3BEA\"]',10.00),(45,'walawalawala2022@gmail.com','$2y$10$uHFWbvhkKZWTUkpBjQfUU.OBjFYVnEDTUAjedsgISqjH/N1wOYLGW','walawalawala2022608','2026-09-15 11:41:33','2026-09-16 05:07:56','wala',NULL,'walawala','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GCash',NULL,NULL,0,NULL,0,NULL,0,'google','103109969339223645389','active','Wala wala','0912 345 6789','uploads/qr_codes/qr_45_1789498282.png','verified','uploads/ids/id_45_1789472598.jpg',1,NULL,0,NULL,'[\"C9E5-6C13\",\"9F1A-E03B\",\"E713-CF81\",\"ADAD-E9B4\",\"3521-3AFD\"]',250.00),(46,'kyutlang29@gmail.com','$2y$10$Ut202OODmp9pAlBxFpdf3eKTKQLyD89e.9aOi6pFlWNQ1YzgIHYlK','','2026-09-15 11:59:51','2026-09-15 18:19:11','Testo',NULL,'Testri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,1,NULL,1,'google','104275986259679211725','suspended',NULL,NULL,NULL,'unverified',NULL,1,'6008fb59c3aca3f4311ac60fc5be6751',0,NULL,'[\"0667-90E3\",\"98E4-3ECC\",\"CE16-DBB6\",\"F6C3-CDE4\",\"663F-FE9A\"]',0.00),(47,'toshirovinz@gmail.com','$2y$10$mBLX.hO9e4FJByP16/fkfOvzlXwP0/3Z36c8LVKtPjBvYFCYAIOkO','toshirovinz557','2026-09-15 12:32:27','2026-09-15 18:20:43','toshiro',NULL,'vinz','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GCash',NULL,NULL,0,NULL,0,NULL,1,'google','104878838875726968265','active','Test Lang','0978 945 6123','uploads/qr_codes/qr_47_1789477785.jpg','verified','uploads/ids/id_47_1789475696.png',1,NULL,0,NULL,'[\"74E0-1493\",\"F1DA-B827\",\"C432-DF11\",\"E6D3-9D70\",\"459D-639F\"]',240.00),(49,'judeykristian@gmail.com','$2y$10$dtTIts6zMIzv/6/W8wj4JOMhifrDaaZqwgipZ51TjxYcvojmAs/fm','judeykristian492','2026-09-15 14:19:49','2026-09-15 14:58:17','Judey',NULL,'Kristian','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,1,'google','112450979022758636126','active',NULL,NULL,NULL,'verified','uploads/ids/id_49_1789484187.jpg',1,NULL,0,NULL,'[\"61B3-4C1E\",\"4EF3-737C\",\"8BE4-7764\",\"43AA-E5A3\",\"A0F2-B16C\"]',330.00),(50,'zenzenyu0@gmail.com','$2y$10$e5W6rN/N9xT6fU8DsM/r8OqIUbC2j6hjvJdalKlS4aCQRjB9kWBdO','','2026-09-15 14:27:56','2026-09-15 18:14:21','Creed',NULL,'Aventus','seller',NULL,'0978 945 6123',NULL,NULL,NULL,'8000',NULL,'GCash','2F6Q+FJG','Davao City',0,NULL,1,NULL,1,'google','104010557163950038963','active','Creed Aventus','0912 345 6789','uploads/sellers/50/payout_qr_1789482693_5fa0f3e4.png','unverified',NULL,1,'293f75d94e7c24c427412bf3f9e39a85',0,NULL,'[\"B3F4-0548\",\"72D4-F71D\",\"9884-411A\",\"8BE7-4F97\",\"7B46-828F\"]',0.00),(52,'xivzenzen@gmail.com','$2y$10$ZFWNC5Rtxo1OnbcDyxIWIOrhgrxlj5joHuO1rwwXQeCA3cw3MD6Dq','xivzenzen636','2026-09-16 04:14:08','2026-09-16 05:02:51','Xiv',NULL,'Zenzen','seller',NULL,'0978 945 6123',NULL,NULL,NULL,'8000',NULL,'GCash','Toril','Davao City',0,NULL,0,NULL,1,'google','104872817922553760314','active','HELLO','0912 312 4123','uploads/sellers/52/payout_qr_1789532265_ade4a82c.png','unverified',NULL,0,NULL,0,NULL,'[\"405D-704C\",\"2E26-9329\",\"FD1D-761C\",\"A792-C351\",\"4575-D18E\"]',100.00),(53,'zennyzen2022@gmail.com','$2y$10$d1pWSBKbc1WhO5dwyxN4x.CALJs6SdRlLl4gMmdyRD/7J.p3FXrym','zennyzen2022885','2026-09-16 04:19:39','2026-09-16 04:19:39','Zen',NULL,'Nyy','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'google','108461953239309850447','active',NULL,NULL,NULL,'unverified',NULL,0,NULL,0,NULL,NULL,0.00),(57,'xivsage@gmail.com','$2y$10$FK7fTBuriAGfOCeNBE13TOCYzjJKZjo5GH7jH6z/tiadrSeGHLA8G','','2026-09-16 04:27:29','2026-09-16 04:28:19','XIV',NULL,'SAGE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,1,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,'c45b51deb6a7a227c5fcc873a7b1f1e2',0,NULL,'[\"6DBA-E6C6\",\"5FF0-29FD\",\"A171-F623\",\"C6EF-74B7\",\"D2F8-DA97\"]',0.00),(58,'haleyvon2@gmail.com','$2y$10$cI56rGGH46wqd5E3.Jao5Ocl1PDu7kzrR9LAmtWfOomysTk7LfY8G','haleyvon2695','2026-09-16 04:28:45','2026-09-16 04:28:45','Haley',NULL,'Von','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'google','105627820506494507422','active',NULL,NULL,NULL,'unverified',NULL,0,NULL,0,NULL,NULL,0.00),(60,'wuzzyfizzy@gmail.com','$2y$10$kcfKFpLPTSINWTmseOu1fOt9rcSgQ5ujNpPIMmWvGxsfHNUq1rg52','wuzzyfizzy206','2026-09-16 04:35:23','2026-09-16 05:07:53','Fizzy',NULL,'Wuzzy','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,1,'google','102829638933375818710','active',NULL,NULL,NULL,'verified','uploads/ids/id_60_1789533768.png',1,NULL,0,NULL,'[\"FA46-07D2\",\"E4BB-F622\",\"020D-8CD6\",\"7252-6B8B\",\"6202-C0C3\"]',11900.00),(61,'seller@bookwagon.com','$2y$10$1JodKVjSPuMw3kHbolKvx.KBZtfH1EIMSLP5gpm0tQAIESav8xTIy','seller_demo','2026-09-18 12:21:32','2026-09-18 12:21:32','BookWagon',NULL,'Seller','seller',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,0.00),(62,'user@bookwagon.com','$2y$10$1JodKVjSPuMw3kHbolKvx.KBZtfH1EIMSLP5gpm0tQAIESav8xTIy','user_demo','2026-09-18 12:21:32','2026-09-18 12:21:32','BookWagon',NULL,'Student','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'local',NULL,'active',NULL,NULL,NULL,'unverified',NULL,1,NULL,0,NULL,NULL,2500.00);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_audit_user_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    -- Check wallet balance tampering
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

    -- Check privilege escalation (e.g. changing usertype to admin)
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `wallet_withdrawals`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_withdrawals`
--

LOCK TABLES `wallet_withdrawals` WRITE;
/*!40000 ALTER TABLE `wallet_withdrawals` DISABLE KEYS */;
INSERT INTO `wallet_withdrawals` VALUES (1,38,250.00,'paid','GCash','Test Lang - 111111111111','1234567890','2026-09-14 15:19:48','2026-09-14 15:21:39'),(2,38,250.00,'paid','GCash','Test Lang - 111111111111','1234567123','2026-09-14 15:25:15','2026-09-14 15:25:47'),(3,45,1500.00,'paid','GCash','Wala wala - 0912 345 6789','1234567890-','2026-09-15 19:51:17','2026-09-15 19:51:28'),(4,47,250.00,'paid','GCash','Test Lang - 0978 945 6123','1234556777','2026-09-15 21:09:50','2026-09-15 21:09:58');
/*!40000 ALTER TABLE `wallet_withdrawals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'bookwagon_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-18 20:29:46
