-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: localhost    Database: africhart_emr
-- ------------------------------------------------------
-- Server version	8.0.45-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `audit_logs_user_id_index` (`user_id`),
  KEY `audit_logs_created_at_index` (`created_at`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,2,'Dr. Emeka Okafor','updated','App\\Models\\Consultation',1,'Completed consultation ACH-C-20260610-0001 for Chioma Adaeze Nwosu',NULL,NULL,NULL,'2026-06-10 20:23:03'),(2,2,'Dr. Emeka Okafor','updated','App\\Models\\Consultation',2,'Completed consultation ACH-C-20260610-0002 for Emeka Chukwuemeka Obi',NULL,NULL,NULL,'2026-06-07 20:23:03'),(3,4,'Front Desk — Chioma','updated','App\\Models\\Invoice',2,'Marked invoice ACH-INV-20260610-0002 as paid',NULL,NULL,NULL,'2026-06-07 21:23:03'),(4,2,'Dr. Emeka Okafor','updated','App\\Models\\Consultation',3,'Completed consultation ACH-C-20260610-0003 for Fatima Bello Mohammed',NULL,NULL,NULL,'2026-06-04 20:23:03'),(5,4,'Front Desk — Chioma','updated','App\\Models\\Invoice',3,'Marked invoice ACH-INV-20260610-0003 as paid',NULL,NULL,NULL,'2026-06-04 21:23:03'),(6,2,'Dr. Emeka Okafor','updated','App\\Models\\Consultation',4,'Completed consultation ACH-C-20260610-0004 for Oluwaseun David Adeyemi',NULL,NULL,NULL,'2026-06-01 20:23:04'),(7,2,'Dr. Emeka Okafor','updated','App\\Models\\Consultation',5,'Completed consultation ACH-C-20260610-0005 for Amina Yusuf Ibrahim',NULL,NULL,NULL,'2026-05-29 20:23:04'),(8,4,'Front Desk — Chioma','updated','App\\Models\\Invoice',5,'Marked invoice ACH-INV-20260610-0005 as paid',NULL,NULL,NULL,'2026-05-29 21:23:04'),(9,2,'Dr. Emeka Okafor','updated','App\\Models\\Consultation',6,'Completed consultation ACH-C-20260610-0006 for Chinedu Ikenna Eze',NULL,NULL,NULL,'2026-05-26 20:23:04'),(10,4,'Front Desk — Chioma','updated','App\\Models\\Invoice',6,'Marked invoice ACH-INV-20260610-0006 as paid',NULL,NULL,NULL,'2026-05-26 21:23:04'),(11,2,'Dr. Emeka Okafor','updated','App\\Models\\Consultation',7,'Completed consultation ACH-C-20260610-0007 for Aisha Binta Abubakar',NULL,NULL,NULL,'2026-05-23 20:23:04'),(12,2,'Dr. Emeka Okafor','updated','App\\Models\\Consultation',8,'Completed consultation ACH-C-20260610-0008 for Tunde Babatunde Ogunleye',NULL,NULL,NULL,'2026-05-20 20:23:04'),(13,4,'Front Desk — Chioma','updated','App\\Models\\Invoice',8,'Marked invoice ACH-INV-20260610-0008 as paid',NULL,NULL,NULL,'2026-05-20 21:23:04'),(14,2,'Dr. Emeka Okafor','updated','App\\Models\\Consultation',9,'Completed consultation ACH-C-20260610-0009 for Ngozi Blessing Okonkwo',NULL,NULL,NULL,'2026-05-17 20:23:04'),(15,4,'Front Desk — Chioma','updated','App\\Models\\Invoice',9,'Marked invoice ACH-INV-20260610-0009 as paid',NULL,NULL,NULL,'2026-05-17 21:23:04'),(16,2,'Dr. Emeka Okafor','updated','App\\Models\\Consultation',10,'Completed consultation ACH-C-20260610-0010 for Yakubu Musa Danladi',NULL,NULL,NULL,'2026-05-14 20:23:04'),(17,2,'Dr. Emeka Okafor','updated','App\\Models\\Consultation',11,'Completed consultation ACH-C-20260610-0011 for Funke Adebisi Oladipo',NULL,NULL,NULL,'2026-05-11 20:23:04'),(18,4,'Front Desk — Chioma','updated','App\\Models\\Invoice',11,'Marked invoice ACH-INV-20260610-0011 as paid',NULL,NULL,NULL,'2026-05-11 21:23:04'),(19,2,'Dr. Emeka Okafor','updated','App\\Models\\Consultation',12,'Completed consultation ACH-C-20260610-0012 for Obinna Francis Nwankwo',NULL,NULL,NULL,'2026-05-08 20:23:04'),(20,4,'Front Desk — Chioma','updated','App\\Models\\Invoice',12,'Marked invoice ACH-INV-20260610-0012 as paid',NULL,NULL,NULL,'2026-05-08 21:23:04');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consultations`
--

DROP TABLE IF EXISTS `consultations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consultations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `doctor_id` bigint unsigned NOT NULL,
  `chief_complaint` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `clinical_notes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `diagnosis` text COLLATE utf8mb4_unicode_ci,
  `plan` text COLLATE utf8mb4_unicode_ci,
  `status` enum('in_progress','completed','follow_up') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_progress',
  `temperature` decimal(4,1) DEFAULT NULL,
  `blood_pressure` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pulse_rate` int DEFAULT NULL,
  `weight` decimal(5,1) DEFAULT NULL,
  `height` decimal(5,1) DEFAULT NULL,
  `vitals_notes` text COLLATE utf8mb4_unicode_ci,
  `consultation_id` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `consultations_consultation_id_unique` (`consultation_id`),
  KEY `consultations_patient_id_index` (`patient_id`),
  KEY `consultations_doctor_id_index` (`doctor_id`),
  KEY `consultations_status_index` (`status`),
  KEY `consultations_consultation_id_index` (`consultation_id`),
  KEY `consultations_created_at_index` (`created_at`),
  CONSTRAINT `consultations_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `consultations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consultations`
--

LOCK TABLES `consultations` WRITE;
/*!40000 ALTER TABLE `consultations` DISABLE KEYS */;
INSERT INTO `consultations` VALUES (1,1,2,'Fever and chills for 3 days','Patient examined. Malaria suspected; managed accordingly.','Malaria','Prescribe medication, advise rest and review if symptoms persist.','completed',36.5,'118/78',68,60.0,160.0,NULL,'ACH-C-20260610-0001','2026-06-10 20:23:03','2026-06-10 20:23:03'),(2,2,2,'Persistent headache for 2 days','Patient examined. Tension headache suspected; managed accordingly.','Tension headache','Prescribe medication, advise rest and review if symptoms persist.','completed',36.9,'119/79',69,61.0,161.0,NULL,'ACH-C-20260610-0002','2026-06-07 20:23:03','2026-06-07 20:23:03'),(3,3,2,'Routine BP review','Patient examined. Hypertension suspected; managed accordingly.','Hypertension','Prescribe medication, advise rest and review if symptoms persist.','completed',37.3,'120/80',70,62.0,162.0,NULL,'ACH-C-20260610-0003','2026-06-04 20:23:03','2026-06-04 20:23:03'),(4,4,2,'Epigastric pain after meals','Patient examined. Peptic ulcer disease suspected; managed accordingly.','Peptic ulcer disease','Prescribe medication, advise rest and review if symptoms persist.','completed',37.7,'121/81',71,63.0,163.0,NULL,'ACH-C-20260610-0004','2026-06-01 20:23:04','2026-06-01 20:23:04'),(5,5,2,'Sore throat and cough','Patient examined. Upper respiratory infection suspected; managed accordingly.','Upper respiratory infection','Prescribe medication, advise rest and review if symptoms persist.','completed',38.1,'122/82',72,64.0,164.0,NULL,'ACH-C-20260610-0005','2026-05-29 20:23:04','2026-05-29 20:23:04'),(6,6,2,'Follow-up, fasting glucose elevated','Patient examined. Type 2 diabetes suspected; managed accordingly.','Type 2 diabetes','Prescribe medication, advise rest and review if symptoms persist.','completed',36.5,'123/83',73,65.0,165.0,NULL,'ACH-C-20260610-0006','2026-05-26 20:23:04','2026-05-26 20:23:04'),(7,7,2,'Fever and chills for 3 days','Patient examined. Malaria suspected; managed accordingly.','Malaria','Prescribe medication, advise rest and review if symptoms persist.','completed',36.9,'124/78',74,66.0,166.0,NULL,'ACH-C-20260610-0007','2026-05-23 20:23:04','2026-05-23 20:23:04'),(8,8,2,'Persistent headache for 2 days','Patient examined. Tension headache suspected; managed accordingly.','Tension headache','Prescribe medication, advise rest and review if symptoms persist.','completed',37.3,'125/79',75,67.0,167.0,NULL,'ACH-C-20260610-0008','2026-05-20 20:23:04','2026-05-20 20:23:04'),(9,9,2,'Routine BP review','Patient examined. Hypertension suspected; managed accordingly.','Hypertension','Prescribe medication, advise rest and review if symptoms persist.','completed',37.7,'126/80',76,68.0,168.0,NULL,'ACH-C-20260610-0009','2026-05-17 20:23:04','2026-05-17 20:23:04'),(10,10,2,'Epigastric pain after meals','Patient examined. Peptic ulcer disease suspected; managed accordingly.','Peptic ulcer disease','Prescribe medication, advise rest and review if symptoms persist.','completed',38.1,'127/81',77,69.0,169.0,NULL,'ACH-C-20260610-0010','2026-05-14 20:23:04','2026-05-14 20:23:04'),(11,11,2,'Sore throat and cough','Patient examined. Upper respiratory infection suspected; managed accordingly.','Upper respiratory infection','Prescribe medication, advise rest and review if symptoms persist.','completed',36.5,'118/82',78,70.0,170.0,NULL,'ACH-C-20260610-0011','2026-05-11 20:23:04','2026-05-11 20:23:04'),(12,12,2,'Follow-up, fasting glucose elevated','Patient examined. Type 2 diabetes suspected; managed accordingly.','Type 2 diabetes','Prescribe medication, advise rest and review if symptoms persist.','completed',36.9,'119/83',79,71.0,171.0,NULL,'ACH-C-20260610-0012','2026-05-08 20:23:04','2026-05-08 20:23:04');
/*!40000 ALTER TABLE `consultations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `amount` decimal(12,2) NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'service',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_index` (`invoice_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_items`
--

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
INSERT INTO `invoice_items` VALUES (1,1,'Consultation Fee',5000.00,1,5000.00,'service','2026-06-10 20:23:03','2026-06-10 20:23:03'),(2,1,'Artemether/Lumefantrine 20/120mg × 6',200.00,6,1200.00,'medication','2026-06-10 20:23:03','2026-06-10 20:23:03'),(3,2,'Consultation Fee',5000.00,1,5000.00,'service','2026-06-10 20:23:03','2026-06-10 20:23:03'),(4,2,'Paracetamol 500mg × 15',350.00,15,5250.00,'medication','2026-06-10 20:23:03','2026-06-10 20:23:03'),(5,3,'Consultation Fee',5000.00,1,5000.00,'service','2026-06-10 20:23:03','2026-06-10 20:23:03'),(6,3,'Amlodipine 5mg × 30',500.00,30,15000.00,'medication','2026-06-10 20:23:03','2026-06-10 20:23:03'),(7,4,'Consultation Fee',5000.00,1,5000.00,'service','2026-06-10 20:23:04','2026-06-10 20:23:04'),(8,4,'Omeprazole 20mg × 14',650.00,14,9100.00,'medication','2026-06-10 20:23:04','2026-06-10 20:23:04'),(9,5,'Consultation Fee',5000.00,1,5000.00,'service','2026-06-10 20:23:04','2026-06-10 20:23:04'),(10,5,'Amoxicillin 500mg × 21',800.00,21,16800.00,'medication','2026-06-10 20:23:04','2026-06-10 20:23:04'),(11,6,'Consultation Fee',5000.00,1,5000.00,'service','2026-06-10 20:23:04','2026-06-10 20:23:04'),(12,6,'Metformin 500mg × 60',200.00,60,12000.00,'medication','2026-06-10 20:23:04','2026-06-10 20:23:04'),(13,7,'Consultation Fee',5000.00,1,5000.00,'service','2026-06-10 20:23:04','2026-06-10 20:23:04'),(14,7,'Artemether/Lumefantrine 20/120mg × 6',350.00,6,2100.00,'medication','2026-06-10 20:23:04','2026-06-10 20:23:04'),(15,8,'Consultation Fee',5000.00,1,5000.00,'service','2026-06-10 20:23:04','2026-06-10 20:23:04'),(16,8,'Paracetamol 500mg × 15',500.00,15,7500.00,'medication','2026-06-10 20:23:04','2026-06-10 20:23:04'),(17,9,'Consultation Fee',5000.00,1,5000.00,'service','2026-06-10 20:23:04','2026-06-10 20:23:04'),(18,9,'Amlodipine 5mg × 30',650.00,30,19500.00,'medication','2026-06-10 20:23:04','2026-06-10 20:23:04'),(19,10,'Consultation Fee',5000.00,1,5000.00,'service','2026-06-10 20:23:04','2026-06-10 20:23:04'),(20,10,'Omeprazole 20mg × 14',800.00,14,11200.00,'medication','2026-06-10 20:23:04','2026-06-10 20:23:04'),(21,11,'Consultation Fee',5000.00,1,5000.00,'service','2026-06-10 20:23:04','2026-06-10 20:23:04'),(22,11,'Amoxicillin 500mg × 21',200.00,21,4200.00,'medication','2026-06-10 20:23:04','2026-06-10 20:23:04'),(23,12,'Consultation Fee',5000.00,1,5000.00,'service','2026-06-10 20:23:04','2026-06-10 20:23:04'),(24,12,'Metformin 500mg × 60',350.00,60,21000.00,'medication','2026-06-10 20:23:04','2026-06-10 20:23:04');
/*!40000 ALTER TABLE `invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `consultation_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `invoice_number` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('draft','issued','paid','partially_paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `payment_method` enum('cash','transfer','card','insurance','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  KEY `invoices_consultation_id_foreign` (`consultation_id`),
  KEY `invoices_created_by_foreign` (`created_by`),
  KEY `invoices_patient_id_index` (`patient_id`),
  KEY `invoices_invoice_number_index` (`invoice_number`),
  KEY `invoices_status_index` (`status`),
  KEY `invoices_created_at_index` (`created_at`),
  CONSTRAINT `invoices_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `invoices_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,1,1,4,'ACH-INV-20260610-0001',6200.00,0.00,0.00,6200.00,'draft',NULL,NULL,NULL,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(2,2,2,4,'ACH-INV-20260610-0002',10250.00,0.00,0.00,10250.00,'paid','transfer','2026-06-07 21:23:03',NULL,'2026-06-07 20:23:03','2026-06-10 20:23:03'),(3,3,3,4,'ACH-INV-20260610-0003',20000.00,0.00,0.00,20000.00,'paid','card','2026-06-04 21:23:03',NULL,'2026-06-04 20:23:03','2026-06-10 20:23:03'),(4,4,4,4,'ACH-INV-20260610-0004',14100.00,0.00,0.00,14100.00,'draft',NULL,NULL,NULL,'2026-06-01 20:23:04','2026-06-01 20:23:04'),(5,5,5,4,'ACH-INV-20260610-0005',21800.00,0.00,0.00,21800.00,'paid','transfer','2026-05-29 21:23:04',NULL,'2026-05-29 20:23:04','2026-06-10 20:23:04'),(6,6,6,4,'ACH-INV-20260610-0006',17000.00,0.00,0.00,17000.00,'paid','card','2026-05-26 21:23:04',NULL,'2026-05-26 20:23:04','2026-06-10 20:23:04'),(7,7,7,4,'ACH-INV-20260610-0007',7100.00,0.00,0.00,7100.00,'draft',NULL,NULL,NULL,'2026-05-23 20:23:04','2026-05-23 20:23:04'),(8,8,8,4,'ACH-INV-20260610-0008',12500.00,0.00,0.00,12500.00,'paid','transfer','2026-05-20 21:23:04',NULL,'2026-05-20 20:23:04','2026-06-10 20:23:04'),(9,9,9,4,'ACH-INV-20260610-0009',24500.00,0.00,0.00,24500.00,'paid','card','2026-05-17 21:23:04',NULL,'2026-05-17 20:23:04','2026-06-10 20:23:04'),(10,10,10,4,'ACH-INV-20260610-0010',16200.00,0.00,0.00,16200.00,'draft',NULL,NULL,NULL,'2026-05-14 20:23:04','2026-05-14 20:23:04'),(11,11,11,4,'ACH-INV-20260610-0011',9200.00,0.00,0.00,9200.00,'paid','transfer','2026-05-11 21:23:04',NULL,'2026-05-11 20:23:04','2026-06-10 20:23:04'),(12,12,12,4,'ACH-INV-20260610-0012',26000.00,0.00,0.00,26000.00,'paid','card','2026-05-08 21:23:04',NULL,'2026-05-08 20:23:04','2026-06-10 20:23:04');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_06_07_145125_add_role_to_users_table',1),(5,'2026_06_07_145125_create_patients_table',1),(6,'2026_06_07_171717_add_email_verification_code_to_users_table',1),(7,'2026_06_10_100001_create_consultations_table',1),(8,'2026_06_10_100002_create_prescriptions_table',1),(9,'2026_06_10_100003_create_invoices_table',1),(10,'2026_06_10_100004_create_invoice_items_table',1),(11,'2026_06_10_100005_create_patient_queue_table',1),(12,'2026_06_10_100006_create_audit_logs_table',1),(13,'2026_06_10_144607_create_personal_access_tokens_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_queue`
--

DROP TABLE IF EXISTS `patient_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `checked_in_by` bigint unsigned NOT NULL,
  `assigned_doctor_id` bigint unsigned DEFAULT NULL,
  `status` enum('waiting','in_consultation','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'waiting',
  `queue_number` int NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `checked_in_at` timestamp NOT NULL,
  `seen_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_queue_patient_id_foreign` (`patient_id`),
  KEY `patient_queue_checked_in_by_foreign` (`checked_in_by`),
  KEY `patient_queue_status_created_at_index` (`status`,`created_at`),
  KEY `patient_queue_checked_in_at_index` (`checked_in_at`),
  KEY `patient_queue_assigned_doctor_id_index` (`assigned_doctor_id`),
  CONSTRAINT `patient_queue_assigned_doctor_id_foreign` FOREIGN KEY (`assigned_doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_queue_checked_in_by_foreign` FOREIGN KEY (`checked_in_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `patient_queue_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_queue`
--

LOCK TABLES `patient_queue` WRITE;
/*!40000 ALTER TABLE `patient_queue` DISABLE KEYS */;
INSERT INTO `patient_queue` VALUES (1,25,4,2,'in_consultation',1,'Fever','2026-06-10 20:23:04','2026-06-10 20:23:04',NULL,'2026-06-10 20:23:04','2026-06-10 20:23:04'),(2,24,4,NULL,'waiting',2,'Follow-up','2026-06-10 20:23:04',NULL,NULL,'2026-06-10 20:23:04','2026-06-10 20:23:04'),(3,23,4,2,'waiting',3,'New complaint','2026-06-10 20:23:04',NULL,NULL,'2026-06-10 20:23:04','2026-06-10 20:23:04'),(4,22,4,NULL,'waiting',4,'Lab results','2026-06-10 20:23:04',NULL,NULL,'2026-06-10 20:23:04','2026-06-10 20:23:04');
/*!40000 ALTER TABLE `patient_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `blood_group` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `allergies` text COLLATE utf8mb4_unicode_ci,
  `patient_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registered_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patients_patient_id_unique` (`patient_id`),
  KEY `patients_registered_by_foreign` (`registered_by`),
  KEY `patients_full_name_index` (`full_name`),
  KEY `patients_phone_index` (`phone`),
  KEY `patients_patient_id_index` (`patient_id`),
  CONSTRAINT `patients_registered_by_foreign` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (1,'Chioma Adaeze Nwosu','1990-03-14','08031234501','O+','Penicillin','ACH-20260610-0001',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(2,'Emeka Chukwuemeka Obi','1985-07-22','08031234502','A+',NULL,'ACH-20260610-0002',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(3,'Fatima Bello Mohammed','1978-11-02','08031234503','B+','Sulfa drugs','ACH-20260610-0003',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(4,'Oluwaseun David Adeyemi','1995-06-30','08031234504','AB+',NULL,'ACH-20260610-0004',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(5,'Amina Yusuf Ibrahim','2000-01-18','08031234505','O-','Dust, pollen','ACH-20260610-0005',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(6,'Chinedu Ikenna Eze','1982-09-09','08031234506','A-',NULL,'ACH-20260610-0006',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(7,'Aisha Binta Abubakar','1993-12-25','08031234507','B-','Latex','ACH-20260610-0007',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(8,'Tunde Babatunde Ogunleye','1975-04-11','08031234508','O+',NULL,'ACH-20260610-0008',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(9,'Ngozi Blessing Okonkwo','1988-08-07','08031234509','AB-','Aspirin','ACH-20260610-0009',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(10,'Yakubu Musa Danladi','1970-02-28','08031234510','A+',NULL,'ACH-20260610-0010',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(11,'Funke Adebisi Oladipo','1997-05-16','08031234511','O+','Peanuts','ACH-20260610-0011',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(12,'Obinna Francis Nwankwo','1991-10-03','08031234512','B+',NULL,'ACH-20260610-0012',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(13,'Hauwa Sadiya Garba','2003-07-21','08031234513','A+',NULL,'ACH-20260610-0013',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(14,'Adewale Samuel Akinola','1986-03-19','08031234514','O-','Shellfish','ACH-20260610-0014',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(15,'Uchenna Grace Okoro','1999-09-12','08031234515','AB+',NULL,'ACH-20260610-0015',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(16,'Ibrahim Suleiman Bako','1968-12-01','08031234516','B-','Penicillin','ACH-20260610-0016',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(17,'Yetunde Folake Bakare','1994-06-08','08031234517','O+',NULL,'ACH-20260610-0017',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(18,'Kelechi Promise Okafor','2001-02-23','08031234518','A-',NULL,'ACH-20260610-0018',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(19,'Maryam Zainab Abdullahi','1980-10-30','08031234519','B+','Iodine','ACH-20260610-0019',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(20,'Segun Olalekan Adekunle','1973-08-15','08031234520','O+',NULL,'ACH-20260610-0020',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(21,'Ifeoma Patricia Uche','1996-04-27','08031234521','AB+',NULL,'ACH-20260610-0021',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(22,'Abdulrahman Idris Musa','1987-11-19','08031234522','A+','Eggs','ACH-20260610-0022',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(23,'Blessing Chidinma Obi','2005-06-05','08031234523','O-',NULL,'ACH-20260610-0023',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(24,'Sani Kabiru Yusuf','1965-01-09','08031234524','B+','Codeine','ACH-20260610-0024',1,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(25,'Omolara Deborah Ajayi','1992-09-29','08031234525','A+',NULL,'ACH-20260610-0025',1,'2026-06-10 20:23:03','2026-06-10 20:23:03');
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prescriptions`
--

DROP TABLE IF EXISTS `prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prescriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint unsigned NOT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `prescribed_by` bigint unsigned NOT NULL,
  `medication_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dosage` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'oral',
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `quantity` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prescriptions_prescribed_by_foreign` (`prescribed_by`),
  KEY `prescriptions_consultation_id_index` (`consultation_id`),
  KEY `prescriptions_patient_id_index` (`patient_id`),
  CONSTRAINT `prescriptions_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescriptions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `prescriptions_prescribed_by_foreign` FOREIGN KEY (`prescribed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescriptions`
--

LOCK TABLES `prescriptions` WRITE;
/*!40000 ALTER TABLE `prescriptions` DISABLE KEYS */;
INSERT INTO `prescriptions` VALUES (1,1,1,2,'Artemether/Lumefantrine','20/120mg','2 times daily for 3 days','3 days','oral',NULL,6,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(2,2,2,2,'Paracetamol','500mg','3 times daily','5 days','oral',NULL,15,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(3,3,3,2,'Amlodipine','5mg','Once daily','30 days','oral',NULL,30,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(4,4,4,2,'Omeprazole','20mg','Once daily','14 days','oral',NULL,14,'2026-06-10 20:23:04','2026-06-10 20:23:04'),(5,5,5,2,'Amoxicillin','500mg','3 times daily','7 days','oral',NULL,21,'2026-06-10 20:23:04','2026-06-10 20:23:04'),(6,6,6,2,'Metformin','500mg','2 times daily','30 days','oral',NULL,60,'2026-06-10 20:23:04','2026-06-10 20:23:04'),(7,7,7,2,'Artemether/Lumefantrine','20/120mg','2 times daily for 3 days','3 days','oral',NULL,6,'2026-06-10 20:23:04','2026-06-10 20:23:04'),(8,8,8,2,'Paracetamol','500mg','3 times daily','5 days','oral',NULL,15,'2026-06-10 20:23:04','2026-06-10 20:23:04'),(9,9,9,2,'Amlodipine','5mg','Once daily','30 days','oral',NULL,30,'2026-06-10 20:23:04','2026-06-10 20:23:04'),(10,10,10,2,'Omeprazole','20mg','Once daily','14 days','oral',NULL,14,'2026-06-10 20:23:04','2026-06-10 20:23:04'),(11,11,11,2,'Amoxicillin','500mg','3 times daily','7 days','oral',NULL,21,'2026-06-10 20:23:04','2026-06-10 20:23:04'),(12,12,12,2,'Metformin','500mg','2 times daily','30 days','oral',NULL,60,'2026-06-10 20:23:04','2026-06-10 20:23:04');
/*!40000 ALTER TABLE `prescriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'doctor',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `email_verification_code` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verification_code_expires_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin User','admin@africhart.com','admin','2026-06-10 20:23:03',NULL,NULL,'$2y$12$7gYgXWoj0BZS3tqe00x4Pu70EmY7y/079T4O0VJpdVfXbDz1KJdiq',NULL,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(2,'Dr. Emeka Okafor','doctor@africhart.com','doctor','2026-06-10 20:23:03',NULL,NULL,'$2y$12$YyZJpjHDYcYO0je0jL.VL.K3L0pUd3qMB4oloX25gOK2zSYuTLJEW',NULL,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(3,'Nurse Amina','nurse@africhart.com','nurse','2026-06-10 20:23:03',NULL,NULL,'$2y$12$0Eh8gKIXjsAuqLfJhEjsPu7aX/HXRGEJNjRseqVdIc6mp8kBzudBm',NULL,'2026-06-10 20:23:03','2026-06-10 20:23:03'),(4,'Front Desk — Chioma','reception@africhart.com','receptionist','2026-06-10 20:23:03',NULL,NULL,'$2y$12$uLOL3fdt0mqGaMGYx5I5yOtD/tNOIWyXWwKY3yz4IEjAyUL0mTps2',NULL,'2026-06-10 20:23:03','2026-06-10 20:23:03');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-10 22:37:09
