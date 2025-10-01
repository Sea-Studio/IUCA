-- MySQL dump 10.13  Distrib 5.6.50, for Linux (x86_64)
--
-- Host: localhost    Database: iucakyys
-- ------------------------------------------------------
-- Server version	5.6.50-log

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
-- Table structure for table `authentication_types`
--

DROP TABLE IF EXISTS `authentication_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `authentication_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(50) NOT NULL,
  `icon` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authentication_types`
--

LOCK TABLES `authentication_types` WRITE;
/*!40000 ALTER TABLE `authentication_types` DISABLE KEYS */;
INSERT INTO `authentication_types` VALUES (1,'未认证','unverified','gray_v.svg'),(2,'普通认证','normal','orange_v.svg'),(3,'高级认证','premium','red_v.svg'),(4,'企业认证','enterprise','blue_v.svg');
/*!40000 ALTER TABLE `authentication_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blacklist`
--

DROP TABLE IF EXISTS `blacklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blacklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_info` text NOT NULL,
  `contact_type` varchar(50) NOT NULL,
  `reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blacklist`
--

LOCK TABLES `blacklist` WRITE;
/*!40000 ALTER TABLE `blacklist` DISABLE KEYS */;
INSERT INTO `blacklist` VALUES (1,'1','phone','1','2025-10-01 04:59:45');
/*!40000 ALTER TABLE `blacklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `idc_info`
--

DROP TABLE IF EXISTS `idc_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `idc_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idc_name` varchar(100) NOT NULL,
  `website` varchar(255) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `contact_type` varchar(50) NOT NULL,
  `contact_info` text NOT NULL,
  `email` varchar(100) NOT NULL,
  `logo_filename` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'normal',
  `authentication` varchar(20) NOT NULL DEFAULT 'unverified',
  `tags` text,
  `remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_blacklisted` tinyint(4) NOT NULL DEFAULT '0',
  `operator_type_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `operator_type_id` (`operator_type_id`),
  CONSTRAINT `idc_info_ibfk_1` FOREIGN KEY (`operator_type_id`) REFERENCES `operator_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `idc_info`
--

LOCK TABLES `idc_info` WRITE;
/*!40000 ALTER TABLE `idc_info` DISABLE KEYS */;
INSERT INTO `idc_info` VALUES (10,'普通演示','https://测试.com','1','wechat','普通演示','iucakyys@tencent.name','','normal','unverified','','','2025-10-01 04:37:15','2025-10-01 05:38:36',1,NULL),(15,'高级认证演示','https://测试.com','','qq','高级认证演示','iucakyys@tencent.name','','normal','premium','','','2025-10-01 04:50:51','2025-10-01 05:37:50',0,7),(16,'企业认证演示','https://测试.com','','other','企业认证演示','iucakyys@tencent.name','','runaway','enterprise','','','2025-10-01 04:51:25','2025-10-01 05:36:58',0,NULL),(17,'普通认证演示','https://测试.com','','other','普通认证演示','iucakyys@tencent.name','','unknown','normal','','','2025-10-01 04:51:35','2025-10-01 05:36:02',0,4),(18,'倒闭状态演示','https://测试.com','','qq','倒闭状态演示','iucakyys@tencent.name','','closed','unverified','','','2025-10-01 04:58:29','2025-10-01 05:35:21',0,NULL),(19,'异常状态演示','https://测试.com','','phone','异常状态演示','iucakyys@tencent.name','','abnormal','unverified','','','2025-10-01 04:59:56','2025-10-01 05:22:53',0,NULL),(20,'高级认证演示','https://高级认证演示.com','','phone','高级认证演示','iucakyys@tencent.name','','normal','premium','','','2025-10-01 05:01:37','2025-10-01 05:22:29',0,NULL);
/*!40000 ALTER TABLE `idc_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `idcbsm`
--

DROP TABLE IF EXISTS `idcbsm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `idcbsm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(100) NOT NULL,
  `idc_info_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `identifier` (`identifier`),
  KEY `idc_info_id` (`idc_info_id`),
  CONSTRAINT `idcbsm_ibfk_1` FOREIGN KEY (`idc_info_id`) REFERENCES `idc_info` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `idcbsm`
--

LOCK TABLES `idcbsm` WRITE;
/*!40000 ALTER TABLE `idcbsm` DISABLE KEYS */;
INSERT INTO `idcbsm` VALUES (1,'JHGC7JTDW6',10,'2025-10-01 04:37:15'),(2,'48CRMC3F7L',15,'2025-10-01 04:50:51'),(3,'SHNZNIIOWR',16,'2025-10-01 04:51:25'),(4,'1W6VL5GPTZ',17,'2025-10-01 04:51:35'),(5,'8NZMR3AQSR',18,'2025-10-01 04:58:29'),(6,'38EF7C63DV',19,'2025-10-01 04:59:56'),(7,'UF7FGDZY32',20,'2025-10-01 05:01:37');
/*!40000 ALTER TABLE `idcbsm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `operator_types`
--

DROP TABLE IF EXISTS `operator_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `operator_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `operator_types`
--

LOCK TABLES `operator_types` WRITE;
/*!40000 ALTER TABLE `operator_types` DISABLE KEYS */;
INSERT INTO `operator_types` VALUES (1,'个体工商户有证经营','individual_licensed','2025-10-01 04:04:44'),(2,'个体工商户无证经营','individual_unlicensed','2025-10-01 04:04:44'),(3,'企业有证经营','enterprise_licensed','2025-10-01 04:04:44'),(4,'企业无证经营','enterprise_unlicensed','2025-10-01 04:04:44'),(5,'个人经营','personal','2025-10-01 04:04:44'),(6,'非营利性企业有证经营','nonprofit_licensed','2025-10-01 04:04:44'),(7,'非营利性企业无证经营','nonprofit_unlicensed','2025-10-01 04:04:44'),(8,'未知','unknown','2025-10-01 04:04:44');
/*!40000 ALTER TABLE `operator_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `status_types`
--

DROP TABLE IF EXISTS `status_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `status_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `status_types`
--

LOCK TABLES `status_types` WRITE;
/*!40000 ALTER TABLE `status_types` DISABLE KEYS */;
INSERT INTO `status_types` VALUES (4,'倒闭'),(3,'未知'),(1,'正常'),(2,'跑路');
/*!40000 ALTER TABLE `status_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `is_admin` tinyint(4) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$lkNlydE5YsjHbauNCW4Su.d4xXDYQ6/WswouQI7Ge0g/5gBfU26a2','admin@example.com',1,'2025-10-01 04:04:44');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'iucakyys'
--

--
-- Dumping routines for database 'iucakyys'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-01 13:38:53
