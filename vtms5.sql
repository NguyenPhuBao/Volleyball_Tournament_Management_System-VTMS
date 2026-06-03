-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: vtms
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB
DROP DATABASE IF EXISTS vtms;

CREATE DATABASE vtms
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE vtms;

SET NAMES utf8mb4;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bangdau`
--

DROP TABLE IF EXISTS `bangdau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bangdau` (
  `idbangdau` int(11) NOT NULL AUTO_INCREMENT,
  `idgiaidau` int(11) NOT NULL,
  `idvongdau` int(11) NOT NULL,
  `tenbang` varchar(100) NOT NULL,
  `mota` varchar(500) DEFAULT NULL,
  `thoigianbatdau` date DEFAULT NULL,
  `thoigianketthuc` date DEFAULT NULL,
  `so_doi_toi_da` int(11) DEFAULT NULL,
  `thutu` int(11) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_PHAN_CONG',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`idbangdau`),
  UNIQUE KEY `uq_bangdau_ten` (`idvongdau`,`tenbang`),
  KEY `fk_bangdau_giaidau` (`idgiaidau`),
  KEY `idx_bangdau_vong_trangthai` (`idvongdau`,`trangthai`,`thutu`),
  CONSTRAINT `fk_bangdau_giaidau` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bangdau_vong` FOREIGN KEY (`idvongdau`) REFERENCES `vongdau` (`idvongdau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_bangdau_trangthai` CHECK (`trangthai` in ('CHO_PHAN_CONG','HOAT_DONG','DA_KHOA','DA_XOA')),
  CONSTRAINT `chk_bangdau_ngay` CHECK (`thoigianbatdau` is null or `thoigianketthuc` is null or `thoigianketthuc` > `thoigianbatdau`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bangdau`
--

LOCK TABLES `bangdau` WRITE;
/*!40000 ALTER TABLE `bangdau` DISABLE KEYS */;
/*!40000 ALTER TABLE `bangdau` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_bangdau_bi
BEFORE INSERT ON bangdau
FOR EACH ROW
BEGIN
    DECLARE v_giaidau INT;
    DECLARE v_cobang TINYINT;
    SELECT idgiaidau, co_bangdau INTO v_giaidau, v_cobang FROM vongdau WHERE idvongdau = NEW.idvongdau;
    IF v_giaidau <> NEW.idgiaidau THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bảng đấu phải thuộc đúng giải của vòng đấu.';
    END IF;
    IF v_cobang <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chỉ tạo bảng đấu cho vòng đấu có co_bangdau = 1.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `bangxephang`
--

DROP TABLE IF EXISTS `bangxephang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bangxephang` (
  `idbangxephang` int(11) NOT NULL AUTO_INCREMENT,
  `idgiaidau` int(11) NOT NULL,
  `idvongdau` int(11) DEFAULT NULL,
  `idbangdau` int(11) DEFAULT NULL,
  `tenbangxephang` varchar(300) NOT NULL,
  `phamvi` varchar(50) NOT NULL DEFAULT 'TOAN_GIAI',
  `trangthai` varchar(50) NOT NULL DEFAULT 'BAN_NHAP',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycongbo` datetime DEFAULT NULL,
  PRIMARY KEY (`idbangxephang`),
  UNIQUE KEY `uq_bxh_scope` (`idgiaidau`,`idvongdau`,`idbangdau`,`tenbangxephang`),
  KEY `fk_bxh_vong` (`idvongdau`),
  KEY `fk_bxh_bang` (`idbangdau`),
  CONSTRAINT `fk_bxh_bang` FOREIGN KEY (`idbangdau`) REFERENCES `bangdau` (`idbangdau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bxh_giaidau` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bxh_vong` FOREIGN KEY (`idvongdau`) REFERENCES `vongdau` (`idvongdau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_bxh_phamvi` CHECK (`phamvi` in ('TOAN_GIAI','THEO_VONG','THEO_BANG')),
  CONSTRAINT `chk_bxh_trangthai` CHECK (`trangthai` in ('BAN_NHAP','DA_CONG_BO','DA_CAP_NHAT')),
  CONSTRAINT `chk_bxh_ngaycongbo` CHECK (`ngaycongbo` is null or `ngaycongbo` >= `ngaytao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bangxephang`
--

LOCK TABLES `bangxephang` WRITE;
/*!40000 ALTER TABLE `bangxephang` DISABLE KEYS */;
/*!40000 ALTER TABLE `bangxephang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bantochuc`
--

DROP TABLE IF EXISTS `bantochuc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bantochuc` (
  `idbantochuc` int(11) NOT NULL AUTO_INCREMENT,
  `idnguoidung` int(11) NOT NULL,
  `idcapbantochuc` int(11) NOT NULL,
  `idkhuvucquanly` int(11) NOT NULL,
  `iddonvi` int(11) DEFAULT NULL,
  `idbantochuccha` int(11) DEFAULT NULL,
  `donvi` varchar(300) NOT NULL,
  `chucvu` varchar(200) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_XAC_NHAN',
  PRIMARY KEY (`idbantochuc`),
  UNIQUE KEY `idnguoidung` (`idnguoidung`),
  KEY `fk_btc_capbtc` (`idcapbantochuc`),
  KEY `fk_btc_khuvuc` (`idkhuvucquanly`),
  KEY `fk_btc_cha` (`idbantochuccha`),
  KEY `idx_btc_donvi` (`iddonvi`),
  CONSTRAINT `fk_btc_capbtc` FOREIGN KEY (`idcapbantochuc`) REFERENCES `capbantochuc` (`idcapbantochuc`) ON UPDATE CASCADE,
  CONSTRAINT `fk_btc_cha` FOREIGN KEY (`idbantochuccha`) REFERENCES `bantochuc` (`idbantochuc`) ON UPDATE CASCADE,
  CONSTRAINT `fk_btc_donvi` FOREIGN KEY (`iddonvi`) REFERENCES `donvi` (`iddonvi`) ON UPDATE CASCADE,
  CONSTRAINT `fk_btc_khuvuc` FOREIGN KEY (`idkhuvucquanly`) REFERENCES `khuvuc` (`idkhuvuc`) ON UPDATE CASCADE,
  CONSTRAINT `fk_btc_nguoidung` FOREIGN KEY (`idnguoidung`) REFERENCES `nguoidung` (`idnguoidung`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_btc_trangthai` CHECK (`trangthai` in ('HOAT_DONG','CHO_XAC_NHAN','TAM_KHOA','NGUNG_HOAT_DONG'))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bantochuc`
--

LOCK TABLES `bantochuc` WRITE;
/*!40000 ALTER TABLE `bantochuc` DISABLE KEYS */;
INSERT INTO `bantochuc` VALUES (1,1,1,1,1,NULL,'Liên đoàn Bóng chuyền Việt Nam','BTC','HOAT_DONG'),(2,2,2,3,4,1,'Sở VH-TT Hà Nội','BTC','HOAT_DONG'),(3,3,2,1034,20,1,'Sở VH-TT Đà Nẵng','BTC','HOAT_DONG'),(4,4,2,2,2,1,'Sở VH-TT Thành phố Hồ Chí Minh','BTC','HOAT_DONG'),(5,5,3,1037,26,4,'Trung tâm VH-TT Phường Bình Dương','BTC','HOAT_DONG'),(6,6,3,1038,28,4,'Trung tâm VH-TT Phường Vũng Tàu','BTC','HOAT_DONG');
/*!40000 ALTER TABLE `bantochuc` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_bantochuc_bi
BEFORE INSERT ON bantochuc
FOR EACH ROW
BEGIN
    DECLARE v_capbtc VARCHAR(50);
    DECLARE v_capkv VARCHAR(50);
    DECLARE v_thutu_btc INT;
    DECLARE v_thutu_cha INT;
    DECLARE v_donvi_khuvuc INT;
    DECLARE v_donvi_trangthai VARCHAR(50);
    DECLARE v_la_cap_thap_nhat TINYINT(1);

    SELECT c.capkhuvucquanly, c.thutu, k.capkhuvuc, cq.la_cap_thap_nhat
      INTO v_capbtc, v_thutu_btc, v_capkv, v_la_cap_thap_nhat
      FROM capbantochuc c
      JOIN khuvuc k ON k.idkhuvuc = NEW.idkhuvucquanly
      JOIN capchinhquyen cq ON cq.macap = k.capkhuvuc
     WHERE c.idcapbantochuc = NEW.idcapbantochuc;

    IF v_capbtc <> v_capkv THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cap BTC phai khop cap khu vuc quan ly.';
    END IF;

    IF NEW.iddonvi IS NULL THEN
        IF v_la_cap_thap_nhat <> 1 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'BTC khong thuoc don vi chi duoc tao o cap thap nhat.';
        END IF;
    ELSE
        SELECT idkhuvuc, trangthai
          INTO v_donvi_khuvuc, v_donvi_trangthai
          FROM donvi
         WHERE iddonvi = NEW.iddonvi;

        IF v_donvi_trangthai <> 'HOAT_DONG' THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Don vi cua BTC phai dang hoat dong.';
        END IF;

        IF v_donvi_khuvuc <> NEW.idkhuvucquanly THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Don vi cua BTC khong thuoc khu vuc quan ly cua BTC.';
        END IF;
    END IF;

    IF NEW.idbantochuccha IS NOT NULL THEN
        SELECT c.thutu
          INTO v_thutu_cha
          FROM bantochuc b
          JOIN capbantochuc c ON c.idcapbantochuc = b.idcapbantochuc
         WHERE b.idbantochuc = NEW.idbantochuccha;

        IF v_thutu_cha >= v_thutu_btc THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'BTC cha phai co cap cao hon BTC con.';
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
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_bantochuc_bu
BEFORE UPDATE ON bantochuc
FOR EACH ROW
BEGIN
    DECLARE v_capbtc VARCHAR(50);
    DECLARE v_capkv VARCHAR(50);
    DECLARE v_thutu_btc INT;
    DECLARE v_thutu_cha INT;
    DECLARE v_donvi_khuvuc INT;
    DECLARE v_donvi_trangthai VARCHAR(50);
    DECLARE v_la_cap_thap_nhat TINYINT(1);

    SELECT c.capkhuvucquanly, c.thutu, k.capkhuvuc, cq.la_cap_thap_nhat
      INTO v_capbtc, v_thutu_btc, v_capkv, v_la_cap_thap_nhat
      FROM capbantochuc c
      JOIN khuvuc k ON k.idkhuvuc = NEW.idkhuvucquanly
      JOIN capchinhquyen cq ON cq.macap = k.capkhuvuc
     WHERE c.idcapbantochuc = NEW.idcapbantochuc;

    IF v_capbtc <> v_capkv THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cap BTC phai khop cap khu vuc quan ly.';
    END IF;

    IF NEW.iddonvi IS NULL THEN
        IF v_la_cap_thap_nhat <> 1 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'BTC khong thuoc don vi chi duoc tao o cap thap nhat.';
        END IF;
    ELSE
        SELECT idkhuvuc, trangthai
          INTO v_donvi_khuvuc, v_donvi_trangthai
          FROM donvi
         WHERE iddonvi = NEW.iddonvi;

        IF v_donvi_trangthai <> 'HOAT_DONG' THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Don vi cua BTC phai dang hoat dong.';
        END IF;

        IF v_donvi_khuvuc <> NEW.idkhuvucquanly THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Don vi cua BTC khong thuoc khu vuc quan ly cua BTC.';
        END IF;
    END IF;

    IF NEW.idbantochuccha IS NOT NULL THEN
        SELECT c.thutu
          INTO v_thutu_cha
          FROM bantochuc b
          JOIN capbantochuc c ON c.idcapbantochuc = b.idcapbantochuc
         WHERE b.idbantochuc = NEW.idbantochuccha;

        IF v_thutu_cha >= v_thutu_btc THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'BTC cha phai co cap cao hon BTC con.';
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `baocaosuco`
--

DROP TABLE IF EXISTS `baocaosuco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `baocaosuco` (
  `idbaocao` int(11) NOT NULL AUTO_INCREMENT,
  `idtrandau` int(11) NOT NULL,
  `idtrongtai` int(11) NOT NULL,
  `tieude` varchar(300) NOT NULL,
  `noidung` varchar(2000) NOT NULL,
  `minhchung` varchar(500) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'DA_GUI',
  `ngaybaocao` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idbaocao`),
  KEY `fk_bcsc_tran` (`idtrandau`),
  KEY `fk_bcsc_trongtai` (`idtrongtai`),
  CONSTRAINT `fk_bcsc_tran` FOREIGN KEY (`idtrandau`) REFERENCES `trandau` (`idtrandau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bcsc_trongtai` FOREIGN KEY (`idtrongtai`) REFERENCES `trongtai` (`idtrongtai`) ON UPDATE CASCADE,
  CONSTRAINT `chk_bcsc_trangthai` CHECK (`trangthai` in ('DA_GUI','DA_TIEP_NHAN','DA_XU_LY','TU_CHOI'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `baocaosuco`
--

LOCK TABLES `baocaosuco` WRITE;
/*!40000 ALTER TABLE `baocaosuco` DISABLE KEYS */;
/*!40000 ALTER TABLE `baocaosuco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `capbantochuc`
--

DROP TABLE IF EXISTS `capbantochuc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `capbantochuc` (
  `idcapbantochuc` int(11) NOT NULL AUTO_INCREMENT,
  `macapbantochuc` varchar(50) NOT NULL,
  `tencapbantochuc` varchar(200) NOT NULL,
  `capkhuvucquanly` varchar(50) NOT NULL,
  `thutu` int(11) NOT NULL,
  `mota` varchar(1000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'HOAT_DONG',
  PRIMARY KEY (`idcapbantochuc`),
  UNIQUE KEY `macapbantochuc` (`macapbantochuc`),
  KEY `fk_capbtc_khuvuc_capchinhquyen` (`capkhuvucquanly`),
  CONSTRAINT `fk_capbtc_khuvuc_capchinhquyen` FOREIGN KEY (`capkhuvucquanly`) REFERENCES `capchinhquyen` (`macap`) ON UPDATE CASCADE,
  CONSTRAINT `fk_capbtc_ma_capchinhquyen` FOREIGN KEY (`macapbantochuc`) REFERENCES `capchinhquyen` (`macap`) ON UPDATE CASCADE,
  CONSTRAINT `chk_capbtc_thutu` CHECK (`thutu` > 0),
  CONSTRAINT `chk_capbtc_trangthai` CHECK (`trangthai` in ('HOAT_DONG','NGUNG_SU_DUNG'))
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `capbantochuc`
--

LOCK TABLES `capbantochuc` WRITE;
/*!40000 ALTER TABLE `capbantochuc` DISABLE KEYS */;
INSERT INTO `capbantochuc` VALUES (1,'QUOC_GIA','Ban tổ chức cấp quốc gia','QUOC_GIA',1,'BTC quản lý phạm vi quốc gia.','HOAT_DONG'),(2,'TINH_THANH','Ban tổ chức cấp tỉnh/thành','TINH_THANH',2,'BTC quản lý phạm vi tỉnh/thành.','HOAT_DONG'),(3,'XA_PHUONG','Ban tổ chức cấp xã/phường','XA_PHUONG',3,'BTC quản lý phạm vi xã/phường.','HOAT_DONG');
/*!40000 ALTER TABLE `capbantochuc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `capchinhquyen`
--

DROP TABLE IF EXISTS `capchinhquyen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `capchinhquyen` (
  `macap` varchar(50) NOT NULL,
  `tencap` varchar(200) NOT NULL,
  `macapcha` varchar(50) DEFAULT NULL,
  `thutu` int(11) NOT NULL,
  `la_cap_thap_nhat` tinyint(1) NOT NULL DEFAULT 0,
  `mota` varchar(1000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'HOAT_DONG',
  PRIMARY KEY (`macap`),
  UNIQUE KEY `uq_capchinhquyen_thutu` (`thutu`),
  KEY `idx_capchinhquyen_capcha` (`macapcha`),
  CONSTRAINT `fk_capchinhquyen_capcha` FOREIGN KEY (`macapcha`) REFERENCES `capchinhquyen` (`macap`) ON UPDATE CASCADE,
  CONSTRAINT `chk_capchinhquyen_thutu` CHECK (`thutu` > 0),
  CONSTRAINT `chk_capchinhquyen_bool` CHECK (`la_cap_thap_nhat` in (0,1)),
  CONSTRAINT `chk_capchinhquyen_trangthai` CHECK (`trangthai` in ('HOAT_DONG','NGUNG_SU_DUNG'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `capchinhquyen`
--

LOCK TABLES `capchinhquyen` WRITE;
/*!40000 ALTER TABLE `capchinhquyen` DISABLE KEYS */;
INSERT INTO `capchinhquyen` VALUES ('QUOC_GIA','Quốc gia',NULL,1,0,'Cấp quản lý quốc gia.','HOAT_DONG'),('TINH_THANH','Tỉnh/thành','QUOC_GIA',2,0,'Cấp tỉnh/thành trực thuộc quốc gia.','HOAT_DONG'),('XA_PHUONG','Xã/phường','TINH_THANH',3,1,'Cấp thấp nhất hiện tại sau thay đổi địa giới.','HOAT_DONG');
/*!40000 ALTER TABLE `capchinhquyen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `capgiaidau`
--

DROP TABLE IF EXISTS `capgiaidau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `capgiaidau` (
  `idcapgiaidau` int(11) NOT NULL AUTO_INCREMENT,
  `idcapgiaidau_cha` int(11) DEFAULT NULL,
  `thutu_cap` int(11) DEFAULT NULL,
  `macapgiaidau` varchar(50) NOT NULL,
  `tencapgiaidau` varchar(200) NOT NULL,
  `capkhuvucphamvi` varchar(50) NOT NULL,
  `capdoituongthamgia` varchar(50) NOT NULL,
  `apdung_bangdau_macdinh` tinyint(1) NOT NULL DEFAULT 0,
  `mota` varchar(1000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'HOAT_DONG',
  PRIMARY KEY (`idcapgiaidau`),
  UNIQUE KEY `macapgiaidau` (`macapgiaidau`),
  KEY `fk_capgd_scope_capchinhquyen` (`capkhuvucphamvi`),
  KEY `fk_capgd_participant_capchinhquyen` (`capdoituongthamgia`),
  KEY `idx_capgiaidau_cha` (`idcapgiaidau_cha`),
  KEY `idx_capgiaidau_thutu` (`thutu_cap`),
  CONSTRAINT `fk_capgd_ma_capchinhquyen` FOREIGN KEY (`macapgiaidau`) REFERENCES `capchinhquyen` (`macap`) ON UPDATE CASCADE,
  CONSTRAINT `fk_capgd_participant_capchinhquyen` FOREIGN KEY (`capdoituongthamgia`) REFERENCES `capchinhquyen` (`macap`) ON UPDATE CASCADE,
  CONSTRAINT `fk_capgd_scope_capchinhquyen` FOREIGN KEY (`capkhuvucphamvi`) REFERENCES `capchinhquyen` (`macap`) ON UPDATE CASCADE,
  CONSTRAINT `fk_capgiaidau_cha` FOREIGN KEY (`idcapgiaidau_cha`) REFERENCES `capgiaidau` (`idcapgiaidau`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_capgd_trangthai` CHECK (`trangthai` in ('HOAT_DONG','NGUNG_SU_DUNG'))
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `capgiaidau`
--

LOCK TABLES `capgiaidau` WRITE;
/*!40000 ALTER TABLE `capgiaidau` DISABLE KEYS */;
INSERT INTO `capgiaidau` VALUES (1,NULL,1,'QUOC_GIA','Giải cấp quốc gia','QUOC_GIA','TINH_THANH',0,'Giải cấp quốc gia chọn đội đại diện từ tỉnh/thành.','HOAT_DONG'),(2,1,2,'TINH_THANH','Giải cấp tỉnh/thành','TINH_THANH','XA_PHUONG',1,'Giải cấp tỉnh/thành chọn đội đại diện từ xã/phường.','HOAT_DONG'),(3,2,3,'XA_PHUONG','Giải cấp xã/phường','XA_PHUONG','XA_PHUONG',1,'Giải cấp xã/phường cho các đội cùng cấp tham gia.','HOAT_DONG');
/*!40000 ALTER TABLE `capgiaidau` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chitietbangxephang`
--

DROP TABLE IF EXISTS `chitietbangxephang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chitietbangxephang` (
  `idchitietbxh` int(11) NOT NULL AUTO_INCREMENT,
  `idbangxephang` int(11) NOT NULL,
  `iddoibong` int(11) NOT NULL,
  `hang` int(11) NOT NULL,
  `sotran` int(11) NOT NULL DEFAULT 0,
  `thang` int(11) NOT NULL DEFAULT 0,
  `thua` int(11) NOT NULL DEFAULT 0,
  `sosetthang` int(11) NOT NULL DEFAULT 0,
  `sosetthua` int(11) NOT NULL DEFAULT 0,
  `diem` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`idchitietbxh`),
  UNIQUE KEY `uq_ctbxh_doi` (`idbangxephang`,`iddoibong`),
  UNIQUE KEY `uq_ctbxh_hang` (`idbangxephang`,`hang`),
  KEY `fk_ctbxh_doi` (`iddoibong`),
  CONSTRAINT `fk_ctbxh_bxh` FOREIGN KEY (`idbangxephang`) REFERENCES `bangxephang` (`idbangxephang`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ctbxh_doi` FOREIGN KEY (`iddoibong`) REFERENCES `doibong` (`iddoibong`) ON UPDATE CASCADE,
  CONSTRAINT `chk_ctbxh_hang` CHECK (`hang` > 0),
  CONSTRAINT `chk_ctbxh_nonnegative` CHECK (`sotran` >= 0 and `thang` >= 0 and `thua` >= 0 and `sosetthang` >= 0 and `sosetthua` >= 0 and `diem` >= 0),
  CONSTRAINT `chk_ctbxh_tongtran` CHECK (`sotran` >= `thang` + `thua`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chitietbangxephang`
--

LOCK TABLES `chitietbangxephang` WRITE;
/*!40000 ALTER TABLE `chitietbangxephang` DISABLE KEYS */;
/*!40000 ALTER TABLE `chitietbangxephang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chitietdoihinh`
--

DROP TABLE IF EXISTS `chitietdoihinh`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chitietdoihinh` (
  `idchitietdoihinh` int(11) NOT NULL AUTO_INCREMENT,
  `iddoihinh` int(11) NOT NULL,
  `idvandongvien` int(11) NOT NULL,
  `vitri` varchar(100) NOT NULL,
  `sothutu` int(11) DEFAULT NULL,
  `ghichu` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`idchitietdoihinh`),
  UNIQUE KEY `uq_ctdh_vdv` (`iddoihinh`,`idvandongvien`),
  UNIQUE KEY `uq_ctdh_sothutu` (`iddoihinh`,`sothutu`),
  KEY `fk_ctdh_vdv` (`idvandongvien`),
  CONSTRAINT `fk_ctdh_doihinh` FOREIGN KEY (`iddoihinh`) REFERENCES `doihinh` (`iddoihinh`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ctdh_vdv` FOREIGN KEY (`idvandongvien`) REFERENCES `vandongvien` (`idvandongvien`) ON UPDATE CASCADE,
  CONSTRAINT `chk_ctdh_vitri` CHECK (`vitri` in ('CHU_CONG','PHU_CONG','CHUYEN_HAI','DOI_CHUYEN','LIBERO','DOI_TRU')),
  CONSTRAINT `chk_ctdh_sothutu` CHECK (`sothutu` is null or `sothutu` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chitietdoihinh`
--

LOCK TABLES `chitietdoihinh` WRITE;
/*!40000 ALTER TABLE `chitietdoihinh` DISABLE KEYS */;
INSERT INTO `chitietdoihinh` VALUES (1,1,1,'CHU_CONG',1,'Seed đội hình nam đủ 6 VĐV.'),(2,1,2,'PHU_CONG',2,'Seed đội hình nam đủ 6 VĐV.'),(3,1,3,'CHUYEN_HAI',3,'Seed đội hình nam đủ 6 VĐV.'),(4,1,4,'DOI_CHUYEN',4,'Seed đội hình nam đủ 6 VĐV.'),(5,1,5,'LIBERO',5,'Seed đội hình nam đủ 6 VĐV.'),(6,1,6,'DOI_TRU',6,'Seed đội hình nam đủ 6 VĐV.'),(7,2,7,'CHU_CONG',1,'Seed đội hình nam đủ 6 VĐV.'),(8,2,8,'PHU_CONG',2,'Seed đội hình nam đủ 6 VĐV.'),(9,2,9,'CHUYEN_HAI',3,'Seed đội hình nam đủ 6 VĐV.'),(10,2,10,'DOI_CHUYEN',4,'Seed đội hình nam đủ 6 VĐV.'),(11,2,11,'LIBERO',5,'Seed đội hình nam đủ 6 VĐV.'),(12,2,12,'DOI_TRU',6,'Seed đội hình nam đủ 6 VĐV.'),(13,3,13,'CHU_CONG',1,'Seed đội hình nam đủ 6 VĐV.'),(14,3,14,'PHU_CONG',2,'Seed đội hình nam đủ 6 VĐV.'),(15,3,15,'CHUYEN_HAI',3,'Seed đội hình nam đủ 6 VĐV.'),(16,3,16,'DOI_CHUYEN',4,'Seed đội hình nam đủ 6 VĐV.'),(17,3,17,'LIBERO',5,'Seed đội hình nam đủ 6 VĐV.'),(18,3,18,'DOI_TRU',6,'Seed đội hình nam đủ 6 VĐV.'),(19,4,19,'CHU_CONG',1,'Seed đội hình nam đủ 6 VĐV.'),(20,4,20,'PHU_CONG',2,'Seed đội hình nam đủ 6 VĐV.'),(21,4,21,'CHUYEN_HAI',3,'Seed đội hình nam đủ 6 VĐV.'),(22,4,22,'DOI_CHUYEN',4,'Seed đội hình nam đủ 6 VĐV.'),(23,4,23,'LIBERO',5,'Seed đội hình nam đủ 6 VĐV.'),(24,4,24,'DOI_TRU',6,'Seed đội hình nam đủ 6 VĐV.'),(25,5,25,'CHU_CONG',1,'Seed đội hình nam đủ 6 VĐV.'),(26,5,26,'PHU_CONG',2,'Seed đội hình nam đủ 6 VĐV.'),(27,5,27,'CHUYEN_HAI',3,'Seed đội hình nam đủ 6 VĐV.'),(28,5,28,'DOI_CHUYEN',4,'Seed đội hình nam đủ 6 VĐV.'),(29,5,29,'LIBERO',5,'Seed đội hình nam đủ 6 VĐV.'),(30,5,30,'DOI_TRU',6,'Seed đội hình nam đủ 6 VĐV.'),(31,6,31,'CHU_CONG',1,'Seed đội hình nam đủ 6 VĐV.'),(32,6,32,'PHU_CONG',2,'Seed đội hình nam đủ 6 VĐV.'),(33,6,33,'CHUYEN_HAI',3,'Seed đội hình nam đủ 6 VĐV.'),(34,6,34,'DOI_CHUYEN',4,'Seed đội hình nam đủ 6 VĐV.'),(35,6,35,'LIBERO',5,'Seed đội hình nam đủ 6 VĐV.'),(36,6,36,'DOI_TRU',6,'Seed đội hình nam đủ 6 VĐV.'),(37,7,37,'CHU_CONG',1,'Seed đội hình nam đủ 6 VĐV.'),(38,7,38,'PHU_CONG',2,'Seed đội hình nam đủ 6 VĐV.'),(39,7,39,'CHUYEN_HAI',3,'Seed đội hình nam đủ 6 VĐV.'),(40,7,40,'DOI_CHUYEN',4,'Seed đội hình nam đủ 6 VĐV.'),(41,7,41,'LIBERO',5,'Seed đội hình nam đủ 6 VĐV.'),(42,7,42,'DOI_TRU',6,'Seed đội hình nam đủ 6 VĐV.'),(43,8,43,'CHU_CONG',1,'Seed đội hình nam đủ 6 VĐV.'),(44,8,44,'PHU_CONG',2,'Seed đội hình nam đủ 6 VĐV.'),(45,8,45,'CHUYEN_HAI',3,'Seed đội hình nam đủ 6 VĐV.'),(46,8,46,'DOI_CHUYEN',4,'Seed đội hình nam đủ 6 VĐV.'),(47,8,47,'LIBERO',5,'Seed đội hình nam đủ 6 VĐV.'),(48,8,48,'DOI_TRU',6,'Seed đội hình nam đủ 6 VĐV.'),(49,9,49,'CHU_CONG',1,'Seed đội hình nam đủ 6 VĐV.'),(50,9,50,'PHU_CONG',2,'Seed đội hình nam đủ 6 VĐV.'),(51,9,51,'CHUYEN_HAI',3,'Seed đội hình nam đủ 6 VĐV.'),(52,9,52,'DOI_CHUYEN',4,'Seed đội hình nam đủ 6 VĐV.'),(53,9,53,'LIBERO',5,'Seed đội hình nam đủ 6 VĐV.'),(54,9,54,'DOI_TRU',6,'Seed đội hình nam đủ 6 VĐV.'),(55,10,55,'CHU_CONG',1,'Seed đội hình nam đủ 6 VĐV.'),(56,10,56,'PHU_CONG',2,'Seed đội hình nam đủ 6 VĐV.'),(57,10,57,'CHUYEN_HAI',3,'Seed đội hình nam đủ 6 VĐV.'),(58,10,58,'DOI_CHUYEN',4,'Seed đội hình nam đủ 6 VĐV.'),(59,10,59,'LIBERO',5,'Seed đội hình nam đủ 6 VĐV.'),(60,10,60,'DOI_TRU',6,'Seed đội hình nam đủ 6 VĐV.'),(61,11,61,'CHU_CONG',1,'Seed đội hình nam đủ 6 VĐV.'),(62,11,62,'PHU_CONG',2,'Seed đội hình nam đủ 6 VĐV.'),(63,11,63,'CHUYEN_HAI',3,'Seed đội hình nam đủ 6 VĐV.'),(64,11,64,'DOI_CHUYEN',4,'Seed đội hình nam đủ 6 VĐV.'),(65,11,65,'LIBERO',5,'Seed đội hình nam đủ 6 VĐV.'),(66,11,66,'DOI_TRU',6,'Seed đội hình nam đủ 6 VĐV.'),(67,12,67,'CHU_CONG',1,'Seed đội hình nam đủ 6 VĐV.'),(68,12,68,'PHU_CONG',2,'Seed đội hình nam đủ 6 VĐV.'),(69,12,69,'CHUYEN_HAI',3,'Seed đội hình nam đủ 6 VĐV.'),(70,12,70,'DOI_CHUYEN',4,'Seed đội hình nam đủ 6 VĐV.'),(71,12,71,'LIBERO',5,'Seed đội hình nam đủ 6 VĐV.'),(72,12,72,'DOI_TRU',6,'Seed đội hình nam đủ 6 VĐV.');
/*!40000 ALTER TABLE `chitietdoihinh` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dangkygiaidau`
--

DROP TABLE IF EXISTS `dangkygiaidau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dangkygiaidau` (
  `iddangky` int(11) NOT NULL AUTO_INCREMENT,
  `idgiaidau` int(11) NOT NULL,
  `iddoibong` int(11) NOT NULL,
  `idhuanluyenvien` int(11) NOT NULL,
  `iddoihinh` int(11) DEFAULT NULL,
  `iddieukien` int(11) DEFAULT NULL,
  `nguon_dang_ky` varchar(50) NOT NULL DEFAULT 'TU_DANG_KY',
  `ngaydangky` datetime NOT NULL DEFAULT current_timestamp(),
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_DUYET',
  `lydotuchoi` varchar(1000) DEFAULT NULL,
  `lydo_xet_tu_cach` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`iddangky`),
  UNIQUE KEY `uq_dkgd_doi` (`idgiaidau`,`iddoibong`),
  KEY `fk_dkgd_doibong` (`iddoibong`),
  KEY `fk_dkgd_hlv` (`idhuanluyenvien`),
  KEY `idx_dkgd_dieukien_v2` (`iddieukien`),
  KEY `idx_dangkygiaidau_iddoihinh` (`iddoihinh`),
  CONSTRAINT `fk_dangkygiaidau_doihinh` FOREIGN KEY (`iddoihinh`) REFERENCES `doihinh` (`iddoihinh`) ON DELETE SET NULL,
  CONSTRAINT `fk_dkgd_dieukien_v2` FOREIGN KEY (`iddieukien`) REFERENCES `doidudieukienthamgia` (`iddieukien`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_dkgd_doibong` FOREIGN KEY (`iddoibong`) REFERENCES `doibong` (`iddoibong`) ON UPDATE CASCADE,
  CONSTRAINT `fk_dkgd_giaidau` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON UPDATE CASCADE,
  CONSTRAINT `fk_dkgd_hlv` FOREIGN KEY (`idhuanluyenvien`) REFERENCES `huanluyenvien` (`idhuanluyenvien`) ON UPDATE CASCADE,
  CONSTRAINT `chk_dkgd_trangthai` CHECK (`trangthai` in ('CHO_DUYET','DA_DUYET','TU_CHOI','DA_HUY')),
  CONSTRAINT `chk_dkgd_lydo` CHECK (`trangthai` <> 'TU_CHOI' or `lydotuchoi` is not null)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dangkygiaidau`
--

LOCK TABLES `dangkygiaidau` WRITE;
/*!40000 ALTER TABLE `dangkygiaidau` DISABLE KEYS */;
INSERT INTO `dangkygiaidau` VALUES (1,1,9,9,9,NULL,'TU_DANG_KY','2026-05-25 16:17:18','DA_DUYET',NULL,NULL),(2,1,11,11,11,NULL,'TU_DANG_KY','2026-05-25 16:17:34','DA_DUYET',NULL,NULL);
/*!40000 ALTER TABLE `dangkygiaidau` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_dkgd_dieukien_bi_v2
BEFORE INSERT ON dangkygiaidau
FOR EACH ROW
BEGIN
    DECLARE v_giai INT;
    DECLARE v_doi INT;
    DECLARE v_status VARCHAR(50);

    IF NEW.nguon_dang_ky NOT IN ('TU_DANG_KY','DUOC_MOI','BTC_THEM','HE_THONG_DE_XUAT') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'nguon_dang_ky khong nam trong bo ma chuan.';
    END IF;

    IF NEW.iddieukien IS NOT NULL THEN
        SELECT idgiaidau, iddoibong, trangthai INTO v_giai, v_doi, v_status
        FROM doidudieukienthamgia
        WHERE iddieukien = NEW.iddieukien;
        IF v_giai <> NEW.idgiaidau OR v_doi <> NEW.iddoibong THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dieu kien tham gia khong khop voi ho so dang ky.';
        END IF;
        IF v_status IN ('TU_CHOI','HUY_TU_CACH','HET_HAN') THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Doi bong khong co tu cach hop le de dang ky giai.';
        END IF;
    ELSE
        IF NEW.nguon_dang_ky IN ('DUOC_MOI','HE_THONG_DE_XUAT') THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dang ky DUOC_MOI/HE_THONG_DE_XUAT phai gan iddieukien.';
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
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_dangkygiaidau_bi
BEFORE INSERT ON dangkygiaidau
FOR EACH ROW
BEGIN
    DECLARE v_scope INT;
    DECLARE v_giai_cap INT;
    DECLARE v_team_kv INT;
    DECLARE v_team_status VARCHAR(50);
    DECLARE v_team_cap_nguon INT;
    DECLARE v_team_cap_duoc_tham_gia INT;
    DECLARE v_team_cap_duoc_tham_gia_hethan DATE;
    DECLARE v_team_cap_hieu_luc INT;

    SELECT gd.idkhuvucphamvi, gd.idcapgiaidau
    INTO v_scope, v_giai_cap
    FROM giaidau gd
    WHERE gd.idgiaidau = NEW.idgiaidau;

    SELECT
        d.idkhuvucdaidien,
        d.trangthai,
        cgnguon.idcapgiaidau,
        d.idcapgiaidau_duoc_tham_gia,
        d.ngayhethan_capgiaidau_duoc_tham_gia
    INTO
        v_team_kv,
        v_team_status,
        v_team_cap_nguon,
        v_team_cap_duoc_tham_gia,
        v_team_cap_duoc_tham_gia_hethan
    FROM doibong d
    JOIN khuvuc k ON k.idkhuvuc = d.idkhuvucdaidien
    LEFT JOIN capgiaidau cgnguon ON cgnguon.macapgiaidau = k.capkhuvuc
    WHERE d.iddoibong = NEW.iddoibong;

    IF v_team_status <> 'HOAT_DONG' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chi doi dang hoat dong moi duoc dang ky giai.';
    END IF;

    IF v_team_cap_duoc_tham_gia IS NOT NULL
       AND (v_team_cap_nguon IS NULL OR v_team_cap_duoc_tham_gia < v_team_cap_nguon)
       AND (v_team_cap_duoc_tham_gia_hethan IS NULL OR v_team_cap_duoc_tham_gia_hethan >= CURRENT_DATE) THEN
        SET v_team_cap_hieu_luc = v_team_cap_duoc_tham_gia;
    ELSE
        SET v_team_cap_hieu_luc = v_team_cap_nguon;
    END IF;

    IF v_team_cap_hieu_luc IS NULL OR v_giai_cap < v_team_cap_hieu_luc THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Doi bong chua duoc duyet tham gia cap giai nay.';
    END IF;

    IF fn_khuvuc_la_con(v_team_kv, v_scope) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Doi dang ky khong thuoc pham vi khu vuc cua giai.';
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
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_dkgd_dieukien_bu_v2
BEFORE UPDATE ON dangkygiaidau
FOR EACH ROW
BEGIN
    DECLARE v_giai INT;
    DECLARE v_doi INT;
    DECLARE v_status VARCHAR(50);

    IF NEW.nguon_dang_ky NOT IN ('TU_DANG_KY','DUOC_MOI','BTC_THEM','HE_THONG_DE_XUAT') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'nguon_dang_ky khong nam trong bo ma chuan.';
    END IF;

    IF NEW.iddieukien IS NOT NULL THEN
        SELECT idgiaidau, iddoibong, trangthai INTO v_giai, v_doi, v_status
        FROM doidudieukienthamgia
        WHERE iddieukien = NEW.iddieukien;
        IF v_giai <> NEW.idgiaidau OR v_doi <> NEW.iddoibong THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dieu kien tham gia khong khop voi ho so dang ky.';
        END IF;
        IF v_status IN ('TU_CHOI','HUY_TU_CACH','HET_HAN') THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Doi bong khong co tu cach hop le de dang ky giai.';
        END IF;
    ELSE
        IF NEW.nguon_dang_ky IN ('DUOC_MOI','HE_THONG_DE_XUAT') THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dang ky DUOC_MOI/HE_THONG_DE_XUAT phai gan iddieukien.';
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `decutucachthamgia`
--

DROP TABLE IF EXISTS `decutucachthamgia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `decutucachthamgia` (
  `iddecu` int(11) NOT NULL AUTO_INCREMENT,
  `iddoibong` int(11) NOT NULL,
  `idthanhtich` int(11) NOT NULL,
  `idgiaidau_nguon` int(11) NOT NULL,
  `idgiaidau_dich` int(11) NOT NULL,
  `idcapgiaidau_nguon` int(11) NOT NULL,
  `idcapgiaidau_dich` int(11) NOT NULL,
  `idbantochuc_decu` int(11) NOT NULL,
  `idbantochuc_nhan` int(11) NOT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'DU_DIEU_KIEN',
  `lydo_xet` varchar(1000) DEFAULT NULL,
  `ghichu_decu` varchar(1000) DEFAULT NULL,
  `lydo_xacnhan` varchar(1000) DEFAULT NULL,
  `idnguoi_danhdau` int(11) DEFAULT NULL,
  `idnguoi_decu` int(11) DEFAULT NULL,
  `idnguoi_xacnhan` int(11) DEFAULT NULL,
  `ngay_danhdau` datetime DEFAULT NULL,
  `ngay_decu` datetime DEFAULT NULL,
  `ngay_xacnhan` datetime DEFAULT NULL,
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL,
  PRIMARY KEY (`iddecu`),
  UNIQUE KEY `uq_decu_thanhtich_giai` (`idthanhtich`,`idgiaidau_dich`),
  KEY `idx_decu_doi` (`iddoibong`),
  KEY `idx_decu_nguon` (`idgiaidau_nguon`),
  KEY `idx_decu_dich` (`idgiaidau_dich`),
  KEY `idx_decu_btc_decu` (`idbantochuc_decu`),
  KEY `idx_decu_btc_nhan` (`idbantochuc_nhan`),
  KEY `idx_decu_trangthai` (`trangthai`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decutucachthamgia`
--

LOCK TABLES `decutucachthamgia` WRITE;
/*!40000 ALTER TABLE `decutucachthamgia` DISABLE KEYS */;
INSERT INTO `decutucachthamgia` VALUES (1,1,1,101,102,3,2,3,104,'DA_XAC_NHAN','Đã xem HLV và toàn bộ VĐV đang tham gia của đội.','Đề cử đội đủ điều kiện tham gia giải cấp cao hơn.','Đội hợp lệ, xác nhận suất tham gia cấp cao hơn.',4,4,10247,'2026-05-22 22:13:24','2026-05-22 22:20:14','2026-05-23 14:04:29','2026-05-22 22:13:24','2026-05-23 14:04:29'),(2,1,1,101,114,3,2,3,104,'DA_XAC_NHAN','Đã xem HLV và toàn bộ VĐV đang tham gia của đội.','Đề cử đội đủ điều kiện tham gia giải cấp cao hơn.','Đội hợp lệ, xác nhận suất tham gia cấp cao hơn.',4,4,10247,'2026-05-23 14:04:11','2026-05-23 14:04:14','2026-05-23 14:04:31','2026-05-23 14:04:11','2026-05-23 14:04:31'),(3,3,2,102,103,2,1,104,1,'DA_DE_CU','Đã xem HLV và toàn bộ VĐV đang tham gia của đội.','Đề cử đội đủ điều kiện tham gia giải cấp cao hơn.',NULL,10247,10247,NULL,'2026-05-23 15:11:21','2026-05-23 15:11:26',NULL,'2026-05-23 15:11:21','2026-05-23 15:11:26');
/*!40000 ALTER TABLE `decutucachthamgia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `diemset`
--

DROP TABLE IF EXISTS `diemset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `diemset` (
  `iddiemset` int(11) NOT NULL AUTO_INCREMENT,
  `idketqua` int(11) NOT NULL,
  `setthu` int(11) NOT NULL,
  `diemdoi1` int(11) NOT NULL DEFAULT 0,
  `diemdoi2` int(11) NOT NULL DEFAULT 0,
  `doithangset` int(11) NOT NULL,
  PRIMARY KEY (`iddiemset`),
  UNIQUE KEY `uq_diemset` (`idketqua`,`setthu`),
  KEY `fk_diemset_doithang` (`doithangset`),
  CONSTRAINT `fk_diemset_doithang` FOREIGN KEY (`doithangset`) REFERENCES `doibong` (`iddoibong`) ON UPDATE CASCADE,
  CONSTRAINT `fk_diemset_ketqua` FOREIGN KEY (`idketqua`) REFERENCES `ketquatrandau` (`idketqua`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_diemset_setthu` CHECK (`setthu` between 1 and 5),
  CONSTRAINT `chk_diemset_diem` CHECK (`diemdoi1` >= 0 and `diemdoi2` >= 0 and `diemdoi1` <> `diemdoi2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `diemset`
--

LOCK TABLES `diemset` WRITE;
/*!40000 ALTER TABLE `diemset` DISABLE KEYS */;
/*!40000 ALTER TABLE `diemset` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dieuchinhketqua`
--

DROP TABLE IF EXISTS `dieuchinhketqua`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dieuchinhketqua` (
  `iddieuchinh` int(11) NOT NULL AUTO_INCREMENT,
  `idketqua` int(11) NOT NULL,
  `diemcu` varchar(500) NOT NULL,
  `diemmoi` varchar(500) NOT NULL,
  `lydo` varchar(1000) NOT NULL,
  `minhchung` varchar(500) DEFAULT NULL,
  `idnguoichinhsua` int(11) DEFAULT NULL,
  `ngaychinhsua` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`iddieuchinh`),
  KEY `fk_dckq_ketqua` (`idketqua`),
  KEY `fk_dckq_taikhoan` (`idnguoichinhsua`),
  CONSTRAINT `fk_dckq_ketqua` FOREIGN KEY (`idketqua`) REFERENCES `ketquatrandau` (`idketqua`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_dckq_taikhoan` FOREIGN KEY (`idnguoichinhsua`) REFERENCES `taikhoan` (`idtaikhoan`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dieuchinhketqua`
--

LOCK TABLES `dieuchinhketqua` WRITE;
/*!40000 ALTER TABLE `dieuchinhketqua` DISABLE KEYS */;
/*!40000 ALTER TABLE `dieuchinhketqua` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dieukienthamgiagiai`
--

DROP TABLE IF EXISTS `dieukienthamgiagiai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dieukienthamgiagiai` (
  `iddieukienthamgia` int(11) NOT NULL AUTO_INCREMENT,
  `idgiaidau` int(11) NOT NULL,
  `idquytac` int(11) DEFAULT NULL,
  `ten_dieukien` varchar(300) NOT NULL,
  `capdoituongthamgia` varchar(50) NOT NULL,
  `yeu_cau_thanh_tich` varchar(50) NOT NULL DEFAULT 'KHONG_YEU_CAU',
  `idcapgiaidau_thanh_tich_nguon` int(11) DEFAULT NULL,
  `hang_toi_thieu_duoc_phep` int(11) DEFAULT NULL,
  `so_mua_giai_gan_nhat_duoc_tinh` int(11) DEFAULT NULL,
  `chi_tinh_giai_chinh_thuc` tinyint(1) NOT NULL DEFAULT 1,
  `bat_buoc_cung_khuvuc` tinyint(1) NOT NULL DEFAULT 1,
  `cho_phep_btc_duyet_ngoai_le` tinyint(1) NOT NULL DEFAULT 1,
  `mota` varchar(1500) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'HOAT_DONG',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL,
  PRIMARY KEY (`iddieukienthamgia`),
  UNIQUE KEY `uq_dktg_giai_ten` (`idgiaidau`,`ten_dieukien`),
  KEY `idx_dktg_giai` (`idgiaidau`),
  KEY `idx_dktg_quytac` (`idquytac`),
  KEY `idx_dktg_capnguon` (`idcapgiaidau_thanh_tich_nguon`),
  KEY `idx_dktg_capdoi` (`capdoituongthamgia`),
  CONSTRAINT `fk_dktg_capdoi_capchinhquyen` FOREIGN KEY (`capdoituongthamgia`) REFERENCES `capchinhquyen` (`macap`) ON UPDATE CASCADE,
  CONSTRAINT `fk_dktg_capnguon` FOREIGN KEY (`idcapgiaidau_thanh_tich_nguon`) REFERENCES `capgiaidau` (`idcapgiaidau`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_dktg_giaidau` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_dktg_quytac` FOREIGN KEY (`idquytac`) REFERENCES `quytacchondoi` (`idquytac`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_dktg_yeucau_v2` CHECK (`yeu_cau_thanh_tich` in ('KHONG_YEU_CAU','VO_DICH','A_QUAN','HANG_BA','TOP_N','THEO_XEP_HANG','BTC_CHON','DAC_CACH')),
  CONSTRAINT `chk_dktg_hang_v2` CHECK (`hang_toi_thieu_duoc_phep` is null or `hang_toi_thieu_duoc_phep` >= 1),
  CONSTRAINT `chk_dktg_mua_v2` CHECK (`so_mua_giai_gan_nhat_duoc_tinh` is null or `so_mua_giai_gan_nhat_duoc_tinh` >= 1),
  CONSTRAINT `chk_dktg_bool_v2` CHECK (`chi_tinh_giai_chinh_thuc` in (0,1) and `bat_buoc_cung_khuvuc` in (0,1) and `cho_phep_btc_duyet_ngoai_le` in (0,1)),
  CONSTRAINT `chk_dktg_trangthai_v2` CHECK (`trangthai` in ('HOAT_DONG','TAM_NGUNG','NGUNG_SU_DUNG')),
  CONSTRAINT `chk_dktg_req_logic_v2` CHECK (`yeu_cau_thanh_tich` in ('KHONG_YEU_CAU','BTC_CHON','DAC_CACH') and `idcapgiaidau_thanh_tich_nguon` is null or `yeu_cau_thanh_tich` in ('VO_DICH','A_QUAN','HANG_BA','TOP_N','THEO_XEP_HANG') and `idcapgiaidau_thanh_tich_nguon` is not null),
  CONSTRAINT `chk_dktg_topn_logic_v2` CHECK (`yeu_cau_thanh_tich` <> 'TOP_N' or `yeu_cau_thanh_tich` = 'TOP_N' and `hang_toi_thieu_duoc_phep` is not null and `hang_toi_thieu_duoc_phep` >= 1)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dieukienthamgiagiai`
--

LOCK TABLES `dieukienthamgiagiai` WRITE;
/*!40000 ALTER TABLE `dieukienthamgiagiai` DISABLE KEYS */;
INSERT INTO `dieukienthamgiagiai` VALUES (1,1,1,'Điều kiện tham gia - Không yêu cầu thành tích #1 - 6a14138e058fd','XA_PHUONG','KHONG_YEU_CAU',NULL,NULL,NULL,0,1,0,NULL,'NGUNG_SU_DUNG','2026-05-25 16:17:02','2026-05-25 16:32:15'),(2,1,2,'Điều kiện tham gia - Không yêu cầu thành tích #1 - 6a1413d421544','XA_PHUONG','KHONG_YEU_CAU',NULL,NULL,NULL,0,1,0,NULL,'NGUNG_SU_DUNG','2026-05-25 16:18:12','2026-05-25 16:32:15'),(3,1,3,'Điều kiện tham gia - Không yêu cầu thành tích #1 - 6a14146980236','XA_PHUONG','KHONG_YEU_CAU',NULL,NULL,NULL,0,1,0,NULL,'NGUNG_SU_DUNG','2026-05-25 16:20:41','2026-05-25 16:32:15'),(4,1,4,'Điều kiện tham gia - Không yêu cầu thành tích #1 - 6a1416fdd30ed','XA_PHUONG','KHONG_YEU_CAU',NULL,NULL,NULL,0,1,0,NULL,'NGUNG_SU_DUNG','2026-05-25 16:31:41','2026-05-25 16:32:15'),(5,1,5,'Điều kiện tham gia - Không yêu cầu thành tích #1 - 6a14171fbb89e','XA_PHUONG','KHONG_YEU_CAU',NULL,NULL,NULL,0,1,0,NULL,'HOAT_DONG','2026-05-25 16:32:15',NULL);
/*!40000 ALTER TABLE `dieukienthamgiagiai` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_dieukienthamgiagiai_bi_v2
BEFORE INSERT ON dieukienthamgiagiai
FOR EACH ROW
BEGIN
    DECLARE v_capdoi VARCHAR(50);
    DECLARE v_capnguon_ma VARCHAR(50);

    SELECT cg.capdoituongthamgia INTO v_capdoi
    FROM giaidau gd
    JOIN capgiaidau cg ON cg.idcapgiaidau = gd.idcapgiaidau
    WHERE gd.idgiaidau = NEW.idgiaidau;

    IF NEW.capdoituongthamgia <> v_capdoi THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dieu kien tham gia phai khop cap doi tuong tham gia cua cap giai.';
    END IF;

    IF NEW.idquytac IS NOT NULL AND NOT EXISTS (
        SELECT 1 FROM quytacchondoi qt WHERE qt.idquytac = NEW.idquytac AND qt.idgiaidau = NEW.idgiaidau
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Quy tac chon doi khong thuoc giai dau cua dieu kien tham gia.';
    END IF;

    IF NEW.yeu_cau_thanh_tich IN ('VO_DICH','A_QUAN','HANG_BA','TOP_N','THEO_XEP_HANG') THEN
        SELECT macapgiaidau INTO v_capnguon_ma FROM capgiaidau WHERE idcapgiaidau = NEW.idcapgiaidau_thanh_tich_nguon;
        IF v_capnguon_ma <> v_capdoi THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cap giai thanh tich nguon phai khop cap doi tuong tham gia.';
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
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_dieukienthamgiagiai_bu_v2
BEFORE UPDATE ON dieukienthamgiagiai
FOR EACH ROW
BEGIN
    DECLARE v_capdoi VARCHAR(50);
    DECLARE v_capnguon_ma VARCHAR(50);

    SELECT cg.capdoituongthamgia INTO v_capdoi
    FROM giaidau gd
    JOIN capgiaidau cg ON cg.idcapgiaidau = gd.idcapgiaidau
    WHERE gd.idgiaidau = NEW.idgiaidau;

    IF NEW.capdoituongthamgia <> v_capdoi THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dieu kien tham gia phai khop cap doi tuong tham gia cua cap giai.';
    END IF;

    IF NEW.idquytac IS NOT NULL AND NOT EXISTS (
        SELECT 1 FROM quytacchondoi qt WHERE qt.idquytac = NEW.idquytac AND qt.idgiaidau = NEW.idgiaidau
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Quy tac chon doi khong thuoc giai dau cua dieu kien tham gia.';
    END IF;

    IF NEW.yeu_cau_thanh_tich IN ('VO_DICH','A_QUAN','HANG_BA','TOP_N','THEO_XEP_HANG') THEN
        SELECT macapgiaidau INTO v_capnguon_ma FROM capgiaidau WHERE idcapgiaidau = NEW.idcapgiaidau_thanh_tich_nguon;
        IF v_capnguon_ma <> v_capdoi THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cap giai thanh tich nguon phai khop cap doi tuong tham gia.';
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `dieukienthamgiagiai_thanhtich`
--

DROP TABLE IF EXISTS `dieukienthamgiagiai_thanhtich`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dieukienthamgiagiai_thanhtich` (
  `iddieukien_thanhtich` bigint(20) NOT NULL AUTO_INCREMENT,
  `iddieukienthamgia` int(11) NOT NULL,
  `ma_thanhtich` varchar(50) NOT NULL,
  `hang_tuong_ung` int(11) DEFAULT NULL,
  `trangthai` varchar(30) NOT NULL DEFAULT 'HOAT_DONG',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`iddieukien_thanhtich`),
  UNIQUE KEY `uq_dktggtt_dieukien_thanhtich` (`iddieukienthamgia`,`ma_thanhtich`),
  KEY `idx_dktggtt_dieukien` (`iddieukienthamgia`,`trangthai`),
  CONSTRAINT `fk_dktggtt_dieukien` FOREIGN KEY (`iddieukienthamgia`) REFERENCES `dieukienthamgiagiai` (`iddieukienthamgia`) ON DELETE CASCADE,
  CONSTRAINT `chk_dktggtt_hang` CHECK (`hang_tuong_ung` is null or `hang_tuong_ung` >= 1),
  CONSTRAINT `chk_dktggtt_trangthai` CHECK (`trangthai` in ('HOAT_DONG','NGUNG_AP_DUNG')),
  CONSTRAINT `chk_dktggtt_ma_thanhtich` CHECK (`ma_thanhtich` in ('VO_DICH','A_QUAN','HANG_BA','TOP_4','TOP_8','TOP_N','THAM_DU','KHAC'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dieukienthamgiagiai_thanhtich`
--

LOCK TABLES `dieukienthamgiagiai_thanhtich` WRITE;
/*!40000 ALTER TABLE `dieukienthamgiagiai_thanhtich` DISABLE KEYS */;
/*!40000 ALTER TABLE `dieukienthamgiagiai_thanhtich` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dieulegiaidau`
--

DROP TABLE IF EXISTS `dieulegiaidau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dieulegiaidau` (
  `iddieule` int(11) NOT NULL AUTO_INCREMENT,
  `idgiaidau` int(11) NOT NULL,
  `tieude` varchar(300) NOT NULL,
  `noidung` varchar(3000) DEFAULT NULL,
  `filedinhkem` varchar(500) DEFAULT NULL,
  `so_doi_toi_thieu` int(11) NOT NULL DEFAULT 2,
  `so_doi_toi_da` int(11) NOT NULL,
  `so_vdv_toi_thieu_moi_doi` int(11) NOT NULL DEFAULT 6,
  `so_vdv_toi_da_moi_doi` int(11) NOT NULL DEFAULT 14,
  `thoi_gian_mo_dang_ky` datetime DEFAULT NULL,
  `thoi_gian_dong_dang_ky` datetime DEFAULT NULL,
  `cho_phep_dang_ky_tu_do` tinyint(1) NOT NULL DEFAULT 1,
  `yeu_cau_duyet_dang_ky` tinyint(1) NOT NULL DEFAULT 1,
  `le_phi_tham_gia` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quy_dinh_bo_cuoc` varchar(1000) DEFAULT NULL,
  `quy_dinh_khieu_nai` varchar(1000) DEFAULT NULL,
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`iddieule`),
  UNIQUE KEY `idgiaidau` (`idgiaidau`),
  CONSTRAINT `fk_dieule_giaidau` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_dieule_doi` CHECK (`so_doi_toi_thieu` >= 2 and `so_doi_toi_da` >= `so_doi_toi_thieu`),
  CONSTRAINT `chk_dieule_time` CHECK (`thoi_gian_dong_dang_ky` is null or `thoi_gian_mo_dang_ky` is null or `thoi_gian_dong_dang_ky` >= `thoi_gian_mo_dang_ky`),
  CONSTRAINT `chk_dieule_vdv` CHECK (`so_vdv_toi_thieu_moi_doi` between 6 and 14 and `so_vdv_toi_da_moi_doi` between 6 and 14 and `so_vdv_toi_da_moi_doi` >= `so_vdv_toi_thieu_moi_doi`),
  CONSTRAINT `chk_dieule_lephi` CHECK (`le_phi_tham_gia` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dieulegiaidau`
--

LOCK TABLES `dieulegiaidau` WRITE;
/*!40000 ALTER TABLE `dieulegiaidau` DISABLE KEYS */;
INSERT INTO `dieulegiaidau` VALUES (5,1,'Điều lệ giải đấu','---VTMS_DIEU_LE_META---\n{\"le_phi_tham_gia\":\"0\",\"loai_doi_duoc_tham_gia\":\"\"}',NULL,2,10,6,14,NULL,NULL,1,1,0.00,NULL,NULL,'2026-05-25 16:32:15');
/*!40000 ALTER TABLE `dieulegiaidau` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doibong`
--

DROP TABLE IF EXISTS `doibong`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doibong` (
  `iddoibong` int(11) NOT NULL AUTO_INCREMENT,
  `tendoibong` varchar(300) NOT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `idkhuvucdaidien` int(11) NOT NULL,
  `idcapgiaidau_duoc_tham_gia` int(11) DEFAULT NULL,
  `ngayhethan_capgiaidau_duoc_tham_gia` date DEFAULT NULL,
  `diaphuong` varchar(300) DEFAULT NULL,
  `mota` varchar(1000) DEFAULT NULL,
  `idhuanluyenvien` int(11) NOT NULL,
  `diem_xep_hang` decimal(10,2) NOT NULL DEFAULT 0.00,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_DUYET',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL,
  PRIMARY KEY (`iddoibong`),
  UNIQUE KEY `tendoibong` (`tendoibong`),
  KEY `fk_doibong_khuvuc` (`idkhuvucdaidien`),
  KEY `fk_doibong_hlv` (`idhuanluyenvien`),
  KEY `idx_doibong_cap_duoc_tham_gia` (`idcapgiaidau_duoc_tham_gia`),
  KEY `idx_doibong_cap_duoc_tham_gia_hethan` (`ngayhethan_capgiaidau_duoc_tham_gia`),
  CONSTRAINT `fk_doibong_cap_duoc_tham_gia` FOREIGN KEY (`idcapgiaidau_duoc_tham_gia`) REFERENCES `capgiaidau` (`idcapgiaidau`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_doibong_hlv` FOREIGN KEY (`idhuanluyenvien`) REFERENCES `huanluyenvien` (`idhuanluyenvien`) ON UPDATE CASCADE,
  CONSTRAINT `fk_doibong_khuvuc` FOREIGN KEY (`idkhuvucdaidien`) REFERENCES `khuvuc` (`idkhuvuc`) ON UPDATE CASCADE,
  CONSTRAINT `chk_doibong_trangthai` CHECK (`trangthai` in ('HOAT_DONG','CHO_DUYET','TAM_KHOA','GIAI_THE'))
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doibong`
--

LOCK TABLES `doibong` WRITE;
/*!40000 ALTER TABLE `doibong` DISABLE KEYS */;
INSERT INTO `doibong` (`iddoibong`,`tendoibong`,`logo`,`idkhuvucdaidien`,`idcapgiaidau_duoc_tham_gia`,`diaphuong`,`mota`,`idhuanluyenvien`,`diem_xep_hang`,`trangthai`,`ngaytao`,`ngaycapnhat`) VALUES (1,'doi_quocgia_01',NULL,1,1,'Việt Nam','Đội bóng cấp quốc gia thuộc Liên đoàn Bóng chuyền Việt Nam.',1,0.00,'HOAT_DONG','2026-05-25 04:13:55',NULL),(2,'doi_quocgia_02',NULL,1,1,'Việt Nam','Đội bóng cấp quốc gia thuộc Liên đoàn Bóng chuyền Việt Nam.',2,0.00,'HOAT_DONG','2026-05-25 04:13:55',NULL),(3,'doi_hn_01',NULL,3,2,'Hà Nội','Đội bóng cấp tỉnh/thành thuộc Hà Nội.',3,0.00,'HOAT_DONG','2026-05-25 04:13:55',NULL),(4,'doi_hn_02',NULL,3,2,'Hà Nội','Đội bóng cấp tỉnh/thành thuộc Hà Nội.',4,0.00,'HOAT_DONG','2026-05-25 04:13:55',NULL),(5,'doi_dn_01',NULL,1034,2,'Đà Nẵng','Đội bóng cấp tỉnh/thành thuộc Đà Nẵng.',5,0.00,'HOAT_DONG','2026-05-25 04:13:55',NULL),(6,'doi_dn_02',NULL,1034,2,'Đà Nẵng','Đội bóng cấp tỉnh/thành thuộc Đà Nẵng.',6,0.00,'HOAT_DONG','2026-05-25 04:13:55',NULL),(7,'doi_hcm_01',NULL,2,2,'Hồ Chí Minh','Đội bóng cấp tỉnh/thành thuộc Hồ Chí Minh.',7,0.00,'HOAT_DONG','2026-05-25 04:13:55',NULL),(8,'doi_hcm_02',NULL,2,2,'Hồ Chí Minh','Đội bóng cấp tỉnh/thành thuộc Hồ Chí Minh.',8,0.00,'HOAT_DONG','2026-05-25 04:13:55',NULL),(9,'doi_phuong_binhduong_01',NULL,1037,3,'Phường Bình Dương','Đội bóng đại diện Trung tâm TDTT Phường Bình Dương.',9,0.00,'HOAT_DONG','2026-05-25 04:13:55',NULL),(10,'doi_phuong_vungtau_01',NULL,1038,3,'Phường Vũng Tàu','Đội bóng đại diện Trung tâm TDTT Phường Vũng Tàu.',10,0.00,'HOAT_DONG','2026-05-25 04:13:55',NULL),(11,'doi_tunhan_binhduong_01',NULL,1037,3,'Phường Bình Dương','Đội bóng tư nhân Phường Bình Dương.',11,0.00,'HOAT_DONG','2026-05-25 04:13:55',NULL),(12,'doi_tunhan_vungtau_01',NULL,1038,3,'Phường Vũng Tàu','Đội bóng tư nhân Phường Vũng Tàu.',12,0.00,'HOAT_DONG','2026-05-25 04:13:55',NULL);
/*!40000 ALTER TABLE `doibong` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doidudieukienthamgia`
--

DROP TABLE IF EXISTS `doidudieukienthamgia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doidudieukienthamgia` (
  `iddieukien` int(11) NOT NULL AUTO_INCREMENT,
  `idgiaidau` int(11) NOT NULL,
  `iddoibong` int(11) NOT NULL,
  `iddieukienthamgia` int(11) DEFAULT NULL,
  `idsuat` int(11) DEFAULT NULL,
  `idthanhtich` int(11) DEFAULT NULL,
  `nguon_dieukien` varchar(50) NOT NULL,
  `lydo_dieukien` varchar(1000) DEFAULT NULL,
  `diem_xet_duyet` decimal(10,2) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'DU_DIEU_KIEN',
  `ngay_xac_nhan` datetime NOT NULL DEFAULT current_timestamp(),
  `idnguoixacnhan` int(11) DEFAULT NULL,
  `ghichu` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`iddieukien`),
  UNIQUE KEY `uq_ddk_giai_doi_v2` (`idgiaidau`,`iddoibong`),
  KEY `idx_ddk_giai_v2` (`idgiaidau`),
  KEY `idx_ddk_doi_v2` (`iddoibong`),
  KEY `idx_ddk_dktg_v2` (`iddieukienthamgia`),
  KEY `idx_ddk_suat_v2` (`idsuat`),
  KEY `idx_ddk_thanhtich_v2` (`idthanhtich`),
  KEY `idx_ddk_taikhoan_v2` (`idnguoixacnhan`),
  CONSTRAINT `fk_ddk_dktg_v2` FOREIGN KEY (`iddieukienthamgia`) REFERENCES `dieukienthamgiagiai` (`iddieukienthamgia`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ddk_doi_v2` FOREIGN KEY (`iddoibong`) REFERENCES `doibong` (`iddoibong`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ddk_giai_v2` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ddk_suat_v2` FOREIGN KEY (`idsuat`) REFERENCES `suatthamdu` (`idsuat`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ddk_taikhoan_v2` FOREIGN KEY (`idnguoixacnhan`) REFERENCES `taikhoan` (`idtaikhoan`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ddk_thanhtich_v2` FOREIGN KEY (`idthanhtich`) REFERENCES `thanhtichdoibong` (`idthanhtich`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_ddk_nguon_v2` CHECK (`nguon_dieukien` in ('THANH_TICH','XEP_HANG','SUAT_THAM_DU','BTC_CHON','DAC_CACH','DANG_KY_TU_DO')),
  CONSTRAINT `chk_ddk_trangthai_v2` CHECK (`trangthai` in ('DU_DIEU_KIEN','DA_MOI','DA_DANG_KY','DA_DUYET','TU_CHOI','HUY_TU_CACH','HET_HAN')),
  CONSTRAINT `chk_ddk_diem_v2` CHECK (`diem_xet_duyet` is null or `diem_xet_duyet` >= 0),
  CONSTRAINT `chk_ddk_source_required_v2` CHECK (`nguon_dieukien` = 'THANH_TICH' and `idthanhtich` is not null or `nguon_dieukien` = 'SUAT_THAM_DU' and `idsuat` is not null or `nguon_dieukien` in ('XEP_HANG','BTC_CHON','DAC_CACH','DANG_KY_TU_DO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doidudieukienthamgia`
--

LOCK TABLES `doidudieukienthamgia` WRITE;
/*!40000 ALTER TABLE `doidudieukienthamgia` DISABLE KEYS */;
/*!40000 ALTER TABLE `doidudieukienthamgia` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_doidudieukien_bi_v2
BEFORE INSERT ON doidudieukienthamgia
FOR EACH ROW
BEGIN
    DECLARE v_giai_khuvuc INT;
    DECLARE v_cap_doi_yeucau VARCHAR(50);
    DECLARE v_doi_khuvuc INT;
    DECLARE v_doi_cap VARCHAR(50);
    DECLARE v_doi_status VARCHAR(50);
    DECLARE v_req VARCHAR(50);
    DECLARE v_capnguon INT;
    DECLARE v_hangmax INT;
    DECLARE v_dktg_giai INT;
    DECLARE v_tt_doi INT;
    DECLARE v_tt_giai INT;
    DECLARE v_tt_cap INT;
    DECLARE v_tt_hang INT;
    DECLARE v_tt_status VARCHAR(50);
    DECLARE v_suat_giai INT;

    SELECT g.idkhuvucphamvi, cg.capdoituongthamgia INTO v_giai_khuvuc, v_cap_doi_yeucau
    FROM giaidau g
    JOIN capgiaidau cg ON cg.idcapgiaidau = g.idcapgiaidau
    WHERE g.idgiaidau = NEW.idgiaidau;

    SELECT db.idkhuvucdaidien, kv.capkhuvuc, db.trangthai INTO v_doi_khuvuc, v_doi_cap, v_doi_status
    FROM doibong db
    JOIN khuvuc kv ON kv.idkhuvuc = db.idkhuvucdaidien
    WHERE db.iddoibong = NEW.iddoibong;

    IF v_doi_status <> 'HOAT_DONG' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chi doi bong HOAT_DONG moi duoc xet tu cach tham gia.';
    END IF;

    IF NEW.iddieukienthamgia IS NOT NULL THEN
        SELECT idgiaidau, capdoituongthamgia, yeu_cau_thanh_tich, idcapgiaidau_thanh_tich_nguon, hang_toi_thieu_duoc_phep
        INTO v_dktg_giai, v_cap_doi_yeucau, v_req, v_capnguon, v_hangmax
        FROM dieukienthamgiagiai
        WHERE iddieukienthamgia = NEW.iddieukienthamgia;
        IF v_dktg_giai <> NEW.idgiaidau THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dieu kien tham gia khong thuoc giai dau dang xet.';
        END IF;
    ELSE
        SET v_req = 'KHONG_YEU_CAU';
        SET v_capnguon = NULL;
        SET v_hangmax = NULL;
    END IF;

    IF v_doi_cap <> v_cap_doi_yeucau THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Doi bong khong dung cap doi tuong tham gia cua giai.';
    END IF;

    IF v_doi_khuvuc <> v_giai_khuvuc AND fn_khuvuc_la_con(v_doi_khuvuc, v_giai_khuvuc) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Doi bong khong nam trong pham vi khu vuc cua giai.';
    END IF;

    IF NEW.nguon_dieukien = 'THANH_TICH' THEN
        IF NEW.idthanhtich IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nguon THANH_TICH bat buoc co idthanhtich.';
        END IF;
        SELECT iddoibong, idgiaidau, idcapgiaidau, hang_dat_duoc, trangthai
        INTO v_tt_doi, v_tt_giai, v_tt_cap, v_tt_hang, v_tt_status
        FROM thanhtichdoibong
        WHERE idthanhtich = NEW.idthanhtich;
        IF v_tt_doi <> NEW.iddoibong THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Thanh tich khong thuoc doi bong dang xet.';
        END IF;
        IF v_tt_status <> 'HOP_LE' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Thanh tich khong o trang thai hop le.';
        END IF;
        IF v_capnguon IS NOT NULL AND v_tt_cap <> v_capnguon THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Thanh tich khong dung cap giai nguon yeu cau.';
        END IF;
        IF v_req = 'VO_DICH' AND v_tt_hang <> 1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dieu kien VO_DICH yeu cau hang_dat_duoc = 1.';
        END IF;
        IF v_req = 'A_QUAN' AND v_tt_hang <> 2 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dieu kien A_QUAN yeu cau hang_dat_duoc = 2.';
        END IF;
        IF v_req = 'HANG_BA' AND v_tt_hang <> 3 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dieu kien HANG_BA yeu cau hang_dat_duoc = 3.';
        END IF;
        IF v_req IN ('TOP_N','THEO_XEP_HANG') AND v_hangmax IS NOT NULL AND v_tt_hang > v_hangmax THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Thu hang thanh tich khong dat dieu kien tham gia.';
        END IF;
    END IF;

    IF NEW.nguon_dieukien = 'SUAT_THAM_DU' THEN
        IF NEW.idsuat IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nguon SUAT_THAM_DU bat buoc co idsuat.';
        END IF;
        SELECT idgiaidau_dich INTO v_suat_giai FROM suatthamdu WHERE idsuat = NEW.idsuat;
        IF v_suat_giai <> NEW.idgiaidau THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Suat tham du khong thuoc giai dau dich dang xet.';
        END IF;
    END IF;

    IF NEW.nguon_dieukien IN ('BTC_CHON','DAC_CACH') THEN
        IF NEW.lydo_dieukien IS NULL OR LENGTH(TRIM(NEW.lydo_dieukien)) = 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'BTC_CHON/DAC_CACH bat buoc co lydo_dieukien.';
        END IF;
        IF NEW.idnguoixacnhan IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'BTC_CHON/DAC_CACH bat buoc co idnguoixacnhan.';
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
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_doidudieukien_bu_v2
BEFORE UPDATE ON doidudieukienthamgia
FOR EACH ROW
BEGIN
    DECLARE v_giai_khuvuc INT;
    DECLARE v_cap_doi_yeucau VARCHAR(50);
    DECLARE v_doi_khuvuc INT;
    DECLARE v_doi_cap VARCHAR(50);
    DECLARE v_doi_status VARCHAR(50);
    DECLARE v_req VARCHAR(50);
    DECLARE v_capnguon INT;
    DECLARE v_hangmax INT;
    DECLARE v_dktg_giai INT;
    DECLARE v_tt_doi INT;
    DECLARE v_tt_giai INT;
    DECLARE v_tt_cap INT;
    DECLARE v_tt_hang INT;
    DECLARE v_tt_status VARCHAR(50);
    DECLARE v_suat_giai INT;

    SELECT g.idkhuvucphamvi, cg.capdoituongthamgia INTO v_giai_khuvuc, v_cap_doi_yeucau
    FROM giaidau g
    JOIN capgiaidau cg ON cg.idcapgiaidau = g.idcapgiaidau
    WHERE g.idgiaidau = NEW.idgiaidau;

    SELECT db.idkhuvucdaidien, kv.capkhuvuc, db.trangthai INTO v_doi_khuvuc, v_doi_cap, v_doi_status
    FROM doibong db
    JOIN khuvuc kv ON kv.idkhuvuc = db.idkhuvucdaidien
    WHERE db.iddoibong = NEW.iddoibong;

    IF v_doi_status <> 'HOAT_DONG' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chi doi bong HOAT_DONG moi duoc xet tu cach tham gia.';
    END IF;

    IF NEW.iddieukienthamgia IS NOT NULL THEN
        SELECT idgiaidau, capdoituongthamgia, yeu_cau_thanh_tich, idcapgiaidau_thanh_tich_nguon, hang_toi_thieu_duoc_phep
        INTO v_dktg_giai, v_cap_doi_yeucau, v_req, v_capnguon, v_hangmax
        FROM dieukienthamgiagiai
        WHERE iddieukienthamgia = NEW.iddieukienthamgia;
        IF v_dktg_giai <> NEW.idgiaidau THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dieu kien tham gia khong thuoc giai dau dang xet.';
        END IF;
    ELSE
        SET v_req = 'KHONG_YEU_CAU';
        SET v_capnguon = NULL;
        SET v_hangmax = NULL;
    END IF;

    IF v_doi_cap <> v_cap_doi_yeucau THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Doi bong khong dung cap doi tuong tham gia cua giai.';
    END IF;

    IF v_doi_khuvuc <> v_giai_khuvuc AND fn_khuvuc_la_con(v_doi_khuvuc, v_giai_khuvuc) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Doi bong khong nam trong pham vi khu vuc cua giai.';
    END IF;

    IF NEW.nguon_dieukien = 'THANH_TICH' THEN
        IF NEW.idthanhtich IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nguon THANH_TICH bat buoc co idthanhtich.';
        END IF;
        SELECT iddoibong, idgiaidau, idcapgiaidau, hang_dat_duoc, trangthai
        INTO v_tt_doi, v_tt_giai, v_tt_cap, v_tt_hang, v_tt_status
        FROM thanhtichdoibong
        WHERE idthanhtich = NEW.idthanhtich;
        IF v_tt_doi <> NEW.iddoibong THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Thanh tich khong thuoc doi bong dang xet.';
        END IF;
        IF v_tt_status <> 'HOP_LE' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Thanh tich khong o trang thai hop le.';
        END IF;
        IF v_capnguon IS NOT NULL AND v_tt_cap <> v_capnguon THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Thanh tich khong dung cap giai nguon yeu cau.';
        END IF;
        IF v_req = 'VO_DICH' AND v_tt_hang <> 1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dieu kien VO_DICH yeu cau hang_dat_duoc = 1.';
        END IF;
        IF v_req = 'A_QUAN' AND v_tt_hang <> 2 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dieu kien A_QUAN yeu cau hang_dat_duoc = 2.';
        END IF;
        IF v_req = 'HANG_BA' AND v_tt_hang <> 3 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dieu kien HANG_BA yeu cau hang_dat_duoc = 3.';
        END IF;
        IF v_req IN ('TOP_N','THEO_XEP_HANG') AND v_hangmax IS NOT NULL AND v_tt_hang > v_hangmax THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Thu hang thanh tich khong dat dieu kien tham gia.';
        END IF;
    END IF;

    IF NEW.nguon_dieukien = 'SUAT_THAM_DU' THEN
        IF NEW.idsuat IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nguon SUAT_THAM_DU bat buoc co idsuat.';
        END IF;
        SELECT idgiaidau_dich INTO v_suat_giai FROM suatthamdu WHERE idsuat = NEW.idsuat;
        IF v_suat_giai <> NEW.idgiaidau THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Suat tham du khong thuoc giai dau dich dang xet.';
        END IF;
    END IF;

    IF NEW.nguon_dieukien IN ('BTC_CHON','DAC_CACH') THEN
        IF NEW.lydo_dieukien IS NULL OR LENGTH(TRIM(NEW.lydo_dieukien)) = 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'BTC_CHON/DAC_CACH bat buoc co lydo_dieukien.';
        END IF;
        IF NEW.idnguoixacnhan IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'BTC_CHON/DAC_CACH bat buoc co idnguoixacnhan.';
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `doihinh`
--

DROP TABLE IF EXISTS `doihinh`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doihinh` (
  `iddoihinh` int(11) NOT NULL AUTO_INCREMENT,
  `iddoibong` int(11) NOT NULL,
  `idgiaidau` int(11) DEFAULT NULL,
  `tendoihinh` varchar(300) NOT NULL,
  `gioitinh` varchar(20) NOT NULL DEFAULT 'NAM',
  `la_doihinh_chinh` tinyint(1) NOT NULL DEFAULT 0,
  `trangthai` varchar(50) NOT NULL DEFAULT 'BAN_NHAP',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL,
  PRIMARY KEY (`iddoihinh`),
  KEY `idx_doihinh_doi` (`iddoibong`),
  KEY `idx_doihinh_giaidau` (`idgiaidau`),
  KEY `idx_doihinh_doi_ten` (`iddoibong`,`tendoihinh`),
  CONSTRAINT `fk_doihinh_doi` FOREIGN KEY (`iddoibong`) REFERENCES `doibong` (`iddoibong`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_doihinh_giaidau` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON DELETE SET NULL,
  CONSTRAINT `chk_doihinh_trangthai` CHECK (`trangthai` in ('BAN_NHAP','DA_CHOT','DA_CAP_NHAT'))
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doihinh`
--

LOCK TABLES `doihinh` WRITE;
/*!40000 ALTER TABLE `doihinh` DISABLE KEYS */;
INSERT INTO `doihinh` VALUES (1,1,NULL,'Đội hình chính - doi_quocgia_01','NAM',1,'DA_CHOT','2026-05-25 04:13:55',NULL),(2,2,NULL,'Đội hình chính - doi_quocgia_02','NAM',1,'DA_CHOT','2026-05-25 04:13:55',NULL),(3,3,NULL,'Đội hình chính - doi_hn_01','NAM',1,'DA_CHOT','2026-05-25 04:13:55',NULL),(4,4,NULL,'Đội hình chính - doi_hn_02','NAM',1,'DA_CHOT','2026-05-25 04:13:55',NULL),(5,5,NULL,'Đội hình chính - doi_dn_01','NAM',1,'DA_CHOT','2026-05-25 04:13:55',NULL),(6,6,NULL,'Đội hình chính - doi_dn_02','NAM',1,'DA_CHOT','2026-05-25 04:13:55',NULL),(7,7,NULL,'Đội hình chính - doi_hcm_01','NAM',1,'DA_CHOT','2026-05-25 04:13:55',NULL),(8,8,NULL,'Đội hình chính - doi_hcm_02','NAM',1,'DA_CHOT','2026-05-25 04:13:55',NULL),(9,9,NULL,'Đội hình chính - doi_phuong_binhduong_01','NAM',1,'DA_CHOT','2026-05-25 04:13:55',NULL),(10,10,NULL,'Đội hình chính - doi_phuong_vungtau_01','NAM',1,'DA_CHOT','2026-05-25 04:13:55',NULL),(11,11,NULL,'Đội hình chính - doi_tunhan_binhduong_01','NAM',1,'DA_CHOT','2026-05-25 04:13:55',NULL),(12,12,NULL,'Đội hình chính - doi_tunhan_vungtau_01','NAM',1,'DA_CHOT','2026-05-25 04:13:55',NULL);
/*!40000 ALTER TABLE `doihinh` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doitrongbang`
--

DROP TABLE IF EXISTS `doitrongbang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doitrongbang` (
  `iddoitrongbang` int(11) NOT NULL AUTO_INCREMENT,
  `idbangdau` int(11) NOT NULL,
  `iddoibong` int(11) NOT NULL,
  `seed_no` int(11) DEFAULT NULL,
  `trangthai` varchar(30) NOT NULL DEFAULT 'HOAT_DONG',
  `ngaythem` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`iddoitrongbang`),
  UNIQUE KEY `uq_dtb` (`idbangdau`,`iddoibong`),
  UNIQUE KEY `uq_doitrongbang_seed` (`idbangdau`,`seed_no`),
  KEY `fk_dtb_doi` (`iddoibong`),
  KEY `idx_doitrongbang_bang_trangthai` (`idbangdau`,`trangthai`),
  CONSTRAINT `fk_dtb_bang` FOREIGN KEY (`idbangdau`) REFERENCES `bangdau` (`idbangdau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_dtb_doi` FOREIGN KEY (`iddoibong`) REFERENCES `doibong` (`iddoibong`) ON UPDATE CASCADE,
  CONSTRAINT `chk_doitrongbang_trangthai` CHECK (`trangthai` in ('HOAT_DONG','TAM_LOAI','DA_XOA'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doitrongbang`
--

LOCK TABLES `doitrongbang` WRITE;
/*!40000 ALTER TABLE `doitrongbang` DISABLE KEYS */;
/*!40000 ALTER TABLE `doitrongbang` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_doitrongbang_bi
BEFORE INSERT ON doitrongbang
FOR EACH ROW
BEGIN
    DECLARE v_vong INT;
    DECLARE v_count INT;
    SELECT idvongdau INTO v_vong FROM bangdau WHERE idbangdau = NEW.idbangdau;
    SELECT COUNT(*) INTO v_count FROM doitrongvongdau
    WHERE idvongdau = v_vong AND iddoibong = NEW.iddoibong;
    IF v_count = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Đội trong bảng phải thuộc danh sách đội của vòng đấu.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `doitrongvongdau`
--

DROP TABLE IF EXISTS `doitrongvongdau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doitrongvongdau` (
  `iddoitrongvong` int(11) NOT NULL AUTO_INCREMENT,
  `idvongdau` int(11) NOT NULL,
  `iddoibong` int(11) NOT NULL,
  `seed_no` int(11) DEFAULT NULL,
  `thuhang_vongtruoc` int(11) DEFAULT NULL,
  `nguonvao` varchar(100) NOT NULL DEFAULT 'DANG_KY',
  `trangthai` varchar(50) NOT NULL DEFAULT 'HOP_LE',
  `ngaythem` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`iddoitrongvong`),
  UNIQUE KEY `uq_dtvong` (`idvongdau`,`iddoibong`),
  UNIQUE KEY `uq_dtvong_seed` (`idvongdau`,`seed_no`),
  KEY `fk_dtvong_doi` (`iddoibong`),
  CONSTRAINT `fk_dtvong_doi` FOREIGN KEY (`iddoibong`) REFERENCES `doibong` (`iddoibong`) ON UPDATE CASCADE,
  CONSTRAINT `fk_dtvong_vong` FOREIGN KEY (`idvongdau`) REFERENCES `vongdau` (`idvongdau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_dtvong_seed` CHECK (`seed_no` is null or `seed_no` > 0),
  CONSTRAINT `chk_dtvong_nguon` CHECK (`nguonvao` in ('DANG_KY','BXH_VONG_TRUOC','BTC_CHON','HE_THONG_CHON','DAC_CACH')),
  CONSTRAINT `chk_dtvong_trangthai` CHECK (`trangthai` in ('HOP_LE','BI_LOAI','DI_TIEP','CHO_XAC_NHAN'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doitrongvongdau`
--

LOCK TABLES `doitrongvongdau` WRITE;
/*!40000 ALTER TABLE `doitrongvongdau` DISABLE KEYS */;
/*!40000 ALTER TABLE `doitrongvongdau` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_doitrongvong_bi
BEFORE INSERT ON doitrongvongdau
FOR EACH ROW
BEGIN
    DECLARE v_giaidau INT;
    DECLARE v_count INT;
    SELECT idgiaidau INTO v_giaidau FROM vongdau WHERE idvongdau = NEW.idvongdau;
    SELECT COUNT(*) INTO v_count FROM dangkygiaidau
    WHERE idgiaidau = v_giaidau AND iddoibong = NEW.iddoibong AND trangthai = 'DA_DUYET';
    IF v_count = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Đội trong vòng đấu phải là đội đã được duyệt đăng ký giải.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `donnghitrongtai`
--

DROP TABLE IF EXISTS `donnghitrongtai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `donnghitrongtai` (
  `iddonnghi` int(11) NOT NULL AUTO_INCREMENT,
  `idtrongtai` int(11) NOT NULL,
  `tungay` date NOT NULL,
  `denngay` date NOT NULL,
  `lydo` varchar(1000) NOT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_DUYET',
  `ngaygui` datetime NOT NULL DEFAULT current_timestamp(),
  `ngayxuly` datetime DEFAULT NULL,
  PRIMARY KEY (`iddonnghi`),
  KEY `fk_dntt_trongtai` (`idtrongtai`),
  CONSTRAINT `fk_dntt_trongtai` FOREIGN KEY (`idtrongtai`) REFERENCES `trongtai` (`idtrongtai`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_dntt_ngay` CHECK (`denngay` >= `tungay`),
  CONSTRAINT `chk_dntt_xuly` CHECK (`ngayxuly` is null or `ngayxuly` >= `ngaygui`),
  CONSTRAINT `chk_dntt_trangthai` CHECK (`trangthai` in ('CHO_DUYET','DA_DUYET','TU_CHOI','DA_HUY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donnghitrongtai`
--

LOCK TABLES `donnghitrongtai` WRITE;
/*!40000 ALTER TABLE `donnghitrongtai` DISABLE KEYS */;
/*!40000 ALTER TABLE `donnghitrongtai` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donnghivandongvien`
--

DROP TABLE IF EXISTS `donnghivandongvien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `donnghivandongvien` (
  `iddonnghi` int(11) NOT NULL AUTO_INCREMENT,
  `idvandongvien` int(11) NOT NULL,
  `idtrandau` int(11) DEFAULT NULL,
  `tungay` date NOT NULL,
  `denngay` date NOT NULL,
  `lydo` varchar(1000) NOT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_DUYET',
  `ngaygui` datetime NOT NULL DEFAULT current_timestamp(),
  `ngayxuly` datetime DEFAULT NULL,
  `idnguoixuly` int(11) DEFAULT NULL,
  PRIMARY KEY (`iddonnghi`),
  KEY `fk_dnvdv_vdv` (`idvandongvien`),
  KEY `fk_dnvdv_tran` (`idtrandau`),
  KEY `fk_dnvdv_nguoixuly` (`idnguoixuly`),
  CONSTRAINT `fk_dnvdv_nguoixuly` FOREIGN KEY (`idnguoixuly`) REFERENCES `taikhoan` (`idtaikhoan`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_dnvdv_tran` FOREIGN KEY (`idtrandau`) REFERENCES `trandau` (`idtrandau`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_dnvdv_vdv` FOREIGN KEY (`idvandongvien`) REFERENCES `vandongvien` (`idvandongvien`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_dnvdv_ngay` CHECK (`denngay` >= `tungay`),
  CONSTRAINT `chk_dnvdv_xuly` CHECK (`ngayxuly` is null or `ngayxuly` >= `ngaygui`),
  CONSTRAINT `chk_dnvdv_trangthai` CHECK (`trangthai` in ('CHO_DUYET','DA_DUYET','TU_CHOI','DA_HUY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donnghivandongvien`
--

LOCK TABLES `donnghivandongvien` WRITE;
/*!40000 ALTER TABLE `donnghivandongvien` DISABLE KEYS */;
/*!40000 ALTER TABLE `donnghivandongvien` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donvi`
--

DROP TABLE IF EXISTS `donvi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `donvi` (
  `iddonvi` int(11) NOT NULL AUTO_INCREMENT,
  `madonvi` varchar(100) NOT NULL,
  `tendonvi` varchar(300) NOT NULL,
  `idloaidonvi` int(11) NOT NULL,
  `idkhuvuc` int(11) NOT NULL,
  `iddonvicha` int(11) DEFAULT NULL,
  `mota` varchar(1000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'HOAT_DONG',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL,
  PRIMARY KEY (`iddonvi`),
  UNIQUE KEY `uq_donvi_ma` (`madonvi`),
  KEY `idx_donvi_loai` (`idloaidonvi`),
  KEY `idx_donvi_khuvuc` (`idkhuvuc`),
  KEY `idx_donvi_cha` (`iddonvicha`),
  CONSTRAINT `fk_donvi_cha` FOREIGN KEY (`iddonvicha`) REFERENCES `donvi` (`iddonvi`) ON UPDATE CASCADE,
  CONSTRAINT `fk_donvi_khuvuc` FOREIGN KEY (`idkhuvuc`) REFERENCES `khuvuc` (`idkhuvuc`) ON UPDATE CASCADE,
  CONSTRAINT `fk_donvi_loaidonvi` FOREIGN KEY (`idloaidonvi`) REFERENCES `loaidonvi` (`idloaidonvi`) ON UPDATE CASCADE,
  CONSTRAINT `chk_donvi_trangthai` CHECK (`trangthai` in ('HOAT_DONG','TAM_DUNG','NGUNG_SU_DUNG'))
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donvi`
--

LOCK TABLES `donvi` WRITE;
/*!40000 ALTER TABLE `donvi` DISABLE KEYS */;
INSERT INTO `donvi` VALUES (1,'LDBCVN','Liên đoàn Bóng chuyền Việt Nam',1,1,NULL,'Đơn vị cấp quốc gia có quyền tổ chức giải và duyệt đề cử.','HOAT_DONG','2026-05-22 10:33:15','2026-05-25 04:13:54'),(2,'SO_VHTT_TPHCM','Sở VH-TT Thành phố Hồ Chí Minh',2,2,1,'Đơn vị tổ chức giải cấp tỉnh/thành.','HOAT_DONG','2026-05-22 10:47:00','2026-05-25 04:13:54'),(3,'TT_HL_TDTT_TPHCM','Trung tâm huấn luyện và thi đấu TDTT Thành phố Hồ Chí Minh',3,2,2,'Đơn vị cấp tỉnh/thành có đội và BTC đại diện đăng ký thi đấu.','HOAT_DONG','2026-05-22 10:47:00',NULL),(4,'SO_VHTT_HANOI','Sở VH-TT Hà Nội',2,3,1,'Đơn vị tổ chức giải cấp tỉnh/thành.','HOAT_DONG','2026-05-22 10:47:00','2026-05-25 04:13:54'),(5,'TT_HL_TDTT_HANOI','Trung tâm huấn luyện và thi đấu TDTT Thành phố Hà Nội',3,3,4,'Đơn vị cấp tỉnh/thành có đội và BTC đại diện đăng ký thi đấu.','HOAT_DONG','2026-05-22 10:47:00',NULL),(10,'TT_VHTT_PHUONG_SAI_GON','Trung tâm VH-TT Phường Sài Gòn',5,20,2,'Đơn vị tổ chức giải cấp phường/xã.','HOAT_DONG','2026-05-22 10:47:00','2026-05-25 04:13:54'),(11,'TT_TDTT_PHUONG_SAI_GON','Trung tâm TDTT Phường Sài Gòn',4,20,10,'Đơn vị huấn luyện cấp phường/xã.','HOAT_DONG','2026-05-22 10:47:00','2026-05-25 04:13:54'),(12,'NVH_TN_PHUONG_SAI_GON','Nhà văn hóa thiếu nhi Phường Sài Gòn',6,20,10,'Đơn vị cấp xã/phường có BTC đại diện đăng ký đội.','HOAT_DONG','2026-05-22 10:47:00',NULL),(13,'TT_VHTT_PHUONG_BEN_THANH','Trung tâm VH-TT Phường Bến Thành',5,21,2,'Đơn vị cấp xã/phường có thẩm quyền tổ chức giải.','HOAT_DONG','2026-05-22 10:47:00',NULL),(14,'TT_VHTT_PHUONG_HOAN_KIEM','Trung tâm VH-TT Phường Hoàn Kiếm',5,30,4,'Đơn vị cấp xã/phường có thẩm quyền tổ chức giải.','HOAT_DONG','2026-05-22 10:47:00',NULL),(15,'TT_TDTT_PHUONG_BEN_THANH','Trung tâm TDTT Phường Bến Thành',4,21,13,'Đơn vị cấp xã/phường phụ trách tuyển chọn và đào tạo đội bóng tại Phường Bến Thành, Thành phố Hồ Chí Minh.','HOAT_DONG','2026-05-22 13:39:43',NULL),(16,'TT_TDTT_PHUONG_HOAN_KIEM','Trung tâm TDTT Phường Hoàn Kiếm',4,30,14,'Đơn vị cấp xã/phường phụ trách tuyển chọn và đào tạo đội bóng tại Phường Hoàn Kiếm, Thành phố Hà Nội.','HOAT_DONG','2026-05-22 13:39:43',NULL),(19,'TT_TDTT_HANOI','Trung tâm TDTT Hà Nội',3,3,4,'Đơn vị huấn luyện và đào tạo cấp tỉnh/thành.','HOAT_DONG','2026-05-25 04:13:54',NULL),(20,'SO_VHTT_DANANG','Sở VH-TT Đà Nẵng',2,1034,1,'Đơn vị tổ chức giải cấp tỉnh/thành.','HOAT_DONG','2026-05-25 04:13:54',NULL),(21,'TT_TDTT_DANANG','Trung tâm TDTT Đà Nẵng',3,1034,20,'Đơn vị huấn luyện và đào tạo cấp tỉnh/thành.','HOAT_DONG','2026-05-25 04:13:54',NULL),(23,'TT_TDTT_TPHCM','Trung tâm TDTT Thành phố Hồ Chí Minh',3,2,2,'Đơn vị huấn luyện và đào tạo cấp tỉnh/thành.','HOAT_DONG','2026-05-25 04:13:54',NULL),(26,'TT_VHTT_PHUONG_BINH_DUONG','Trung tâm VH-TT Phường Bình Dương',5,1037,2,'Đơn vị tổ chức giải cấp phường/xã.','HOAT_DONG','2026-05-25 04:13:54',NULL),(27,'TT_TDTT_PHUONG_BINH_DUONG','Trung tâm TDTT Phường Bình Dương',4,1037,26,'Đơn vị huấn luyện cấp phường/xã.','HOAT_DONG','2026-05-25 04:13:54',NULL),(28,'TT_VHTT_PHUONG_VUNG_TAU','Trung tâm VH-TT Phường Vũng Tàu',5,1038,2,'Đơn vị tổ chức giải cấp phường/xã.','HOAT_DONG','2026-05-25 04:13:54',NULL),(29,'TT_TDTT_PHUONG_VUNG_TAU','Trung tâm TDTT Phường Vũng Tàu',4,1038,28,'Đơn vị huấn luyện cấp phường/xã.','HOAT_DONG','2026-05-25 04:13:54',NULL),(30,'TU_NHAN_BINH_DUONG_01','Tư nhân Bình Dương 01',7,1037,NULL,'Đơn vị đội bóng tư nhân cấp phường/xã.','HOAT_DONG','2026-05-25 04:13:54',NULL),(31,'TU_NHAN_VUNG_TAU_01','Tư nhân Vũng Tàu 01',7,1038,NULL,'Đơn vị đội bóng tư nhân cấp phường/xã.','HOAT_DONG','2026-05-25 04:13:54',NULL);
/*!40000 ALTER TABLE `donvi` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_donvi_bi
BEFORE INSERT ON donvi
FOR EACH ROW
BEGIN
    DECLARE v_cap_loai VARCHAR(50);
    DECLARE v_cap_khuvuc VARCHAR(50);
    DECLARE v_loai_trangthai VARCHAR(50);

    SELECT macapapdung, trangthai
      INTO v_cap_loai, v_loai_trangthai
      FROM loaidonvi
     WHERE idloaidonvi = NEW.idloaidonvi;

    SELECT capkhuvuc
      INTO v_cap_khuvuc
      FROM khuvuc
     WHERE idkhuvuc = NEW.idkhuvuc;

    IF v_loai_trangthai <> 'HOAT_DONG' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Loai don vi phai dang hoat dong.';
    END IF;

    IF v_cap_loai <> v_cap_khuvuc THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Loai don vi khong khop cap khu vuc.';
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
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_donvi_bu
BEFORE UPDATE ON donvi
FOR EACH ROW
BEGIN
    DECLARE v_cap_loai VARCHAR(50);
    DECLARE v_cap_khuvuc VARCHAR(50);
    DECLARE v_loai_trangthai VARCHAR(50);

    SELECT macapapdung, trangthai
      INTO v_cap_loai, v_loai_trangthai
      FROM loaidonvi
     WHERE idloaidonvi = NEW.idloaidonvi;

    SELECT capkhuvuc
      INTO v_cap_khuvuc
      FROM khuvuc
     WHERE idkhuvuc = NEW.idkhuvuc;

    IF v_loai_trangthai <> 'HOAT_DONG' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Loai don vi phai dang hoat dong.';
    END IF;

    IF v_cap_loai <> v_cap_khuvuc THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Loai don vi khong khop cap khu vuc.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `giaidau`
--

DROP TABLE IF EXISTS `giaidau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `giaidau` (
  `idgiaidau` int(11) NOT NULL AUTO_INCREMENT,
  `tengiaidau` varchar(300) NOT NULL,
  `mota` varchar(1000) DEFAULT NULL,
  `idcapgiaidau` int(11) NOT NULL,
  `idkhuvucphamvi` int(11) NOT NULL,
  `idbantochuc` int(11) NOT NULL,
  `idluat` int(11) NOT NULL,
  `thoigianbatdau` datetime NOT NULL,
  `thoigianketthuc` datetime NOT NULL,
  `quymo` int(11) NOT NULL,
  `quymo_tu_dong` tinyint(1) NOT NULL DEFAULT 1,
  `quymo_ghi_chu` varchar(500) DEFAULT NULL,
  `hinhanh` varchar(500) DEFAULT NULL,
  `hinhanh_kieu` varchar(20) DEFAULT NULL,
  `hinhanh_ten_goc` varchar(255) DEFAULT NULL,
  `tinhchat` varchar(100) NOT NULL DEFAULT 'CHINH_THUC',
  `gioitinh` varchar(20) NOT NULL DEFAULT 'NAM',
  `trangthai` varchar(50) NOT NULL DEFAULT 'NHAP',
  `trangthaidangky` varchar(50) NOT NULL DEFAULT 'CHUA_MO',
  `trangthaithietlap` varchar(50) NOT NULL DEFAULT 'DANG_THIET_LAP',
  `ghichu_diadiem` varchar(500) DEFAULT NULL,
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL,
  PRIMARY KEY (`idgiaidau`),
  UNIQUE KEY `uq_giaidau_ten_ngay` (`tengiaidau`,`thoigianbatdau`),
  KEY `idx_giaidau_cap_khuvuc` (`idcapgiaidau`,`idkhuvucphamvi`),
  KEY `fk_giaidau_khuvuc` (`idkhuvucphamvi`),
  KEY `fk_giaidau_btc` (`idbantochuc`),
  KEY `fk_giaidau_luat` (`idluat`),
  CONSTRAINT `fk_giaidau_btc` FOREIGN KEY (`idbantochuc`) REFERENCES `bantochuc` (`idbantochuc`) ON UPDATE CASCADE,
  CONSTRAINT `fk_giaidau_cap` FOREIGN KEY (`idcapgiaidau`) REFERENCES `capgiaidau` (`idcapgiaidau`) ON UPDATE CASCADE,
  CONSTRAINT `fk_giaidau_khuvuc` FOREIGN KEY (`idkhuvucphamvi`) REFERENCES `khuvuc` (`idkhuvuc`) ON UPDATE CASCADE,
  CONSTRAINT `fk_giaidau_luat` FOREIGN KEY (`idluat`) REFERENCES `luatthidau` (`idluat`) ON UPDATE CASCADE,
  CONSTRAINT `chk_giaidau_quymo` CHECK (`quymo` > 0),
  CONSTRAINT `chk_giaidau_tinhchat` CHECK (`tinhchat` in ('CHINH_THUC','GIAO_HUU','PHONG_TRAO','NOI_BO','MO_RONG')),
  CONSTRAINT `chk_giaidau_trangthai` CHECK (`trangthai` in ('NHAP','CHUA_CONG_BO','DA_CONG_BO','DANG_DIEN_RA','DA_KET_THUC','DA_HUY')),
  CONSTRAINT `chk_giaidau_dangky` CHECK (`trangthaidangky` in ('CHUA_MO','DANG_MO','DA_DONG')),
  CONSTRAINT `chk_giaidau_thietlap` CHECK (`trangthaithietlap` in ('DANG_THIET_LAP','DA_KHOA_DOI','DA_TAO_CAU_TRUC','DA_TAO_TRAN','DA_CONG_BO_LICH')),
  CONSTRAINT `chk_giaidau_thoigian` CHECK (`thoigianketthuc` > `thoigianbatdau`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `giaidau`
--

LOCK TABLES `giaidau` WRITE;
/*!40000 ALTER TABLE `giaidau` DISABLE KEYS */;
INSERT INTO `giaidau` VALUES (1,'Giai bóng chuyền chính thức Phường Bình Dương 2026',NULL,3,1037,5,1,'2026-05-25 16:37:00','2026-05-25 16:40:00',10,1,NULL,'/uploads/tournaments/tournament_20260525_163141_df25cb37a091.jpg',NULL,NULL,'CHINH_THUC','NAM','DA_HUY','DA_DONG','DANG_THIET_LAP',NULL,'2026-05-25 16:17:02','2026-05-25 16:32:52');
/*!40000 ALTER TABLE `giaidau` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_giaidau_bi
BEFORE INSERT ON giaidau
FOR EACH ROW
BEGIN
    DECLARE v_capphamvi VARCHAR(50);
    DECLARE v_capkv VARCHAR(50);
    DECLARE v_btc_cap INT;
    DECLARE v_btc_kv INT;
    DECLARE v_btc_donvi INT;
    DECLARE v_btc_status VARCHAR(50);
    DECLARE v_donvi_tochuc TINYINT(1);
    DECLARE v_count INT;

    SELECT capkhuvucphamvi
      INTO v_capphamvi
      FROM capgiaidau
     WHERE idcapgiaidau = NEW.idcapgiaidau;

    SELECT capkhuvuc
      INTO v_capkv
      FROM khuvuc
     WHERE idkhuvuc = NEW.idkhuvucphamvi;

    IF v_capphamvi <> v_capkv THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Khu vuc pham vi khong khop cap giai dau.';
    END IF;

    SELECT idcapbantochuc, idkhuvucquanly, iddonvi, trangthai
      INTO v_btc_cap, v_btc_kv, v_btc_donvi, v_btc_status
      FROM bantochuc
     WHERE idbantochuc = NEW.idbantochuc;

    IF v_btc_status <> 'HOAT_DONG' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'BTC phai hoat dong moi duoc tao giai.';
    END IF;

    IF v_btc_kv <> NEW.idkhuvucphamvi THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'BTC chi duoc tao giai trong khu vuc minh quan ly.';
    END IF;

    IF v_btc_donvi IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'BTC dai dien tu nhan khong duoc to chuc giai.';
    END IF;

    SELECT l.duoc_to_chuc_giai
      INTO v_donvi_tochuc
      FROM donvi d
      JOIN loaidonvi l ON l.idloaidonvi = d.idloaidonvi
     WHERE d.iddonvi = v_btc_donvi
       AND d.trangthai = 'HOAT_DONG'
       AND l.trangthai = 'HOAT_DONG';

    IF COALESCE(v_donvi_tochuc, 0) <> 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Don vi cua BTC khong co tham quyen to chuc giai.';
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM quyencapbtc_capgiaidau
     WHERE idcapbantochuc = v_btc_cap
       AND idcapgiaidau = NEW.idcapgiaidau
       AND duoc_tao_giai = 1;

    IF v_count = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cap BTC khong co quyen tao cap giai dau nay.';
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
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_giaidau_bu
BEFORE UPDATE ON giaidau
FOR EACH ROW
BEGIN
    DECLARE v_capphamvi VARCHAR(50);
    DECLARE v_capkv VARCHAR(50);
    DECLARE v_btc_cap INT;
    DECLARE v_btc_kv INT;
    DECLARE v_btc_donvi INT;
    DECLARE v_btc_status VARCHAR(50);
    DECLARE v_donvi_tochuc TINYINT(1);
    DECLARE v_count INT;

    SELECT capkhuvucphamvi
      INTO v_capphamvi
      FROM capgiaidau
     WHERE idcapgiaidau = NEW.idcapgiaidau;

    SELECT capkhuvuc
      INTO v_capkv
      FROM khuvuc
     WHERE idkhuvuc = NEW.idkhuvucphamvi;

    IF v_capphamvi <> v_capkv THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Khu vuc pham vi khong khop cap giai dau.';
    END IF;

    SELECT idcapbantochuc, idkhuvucquanly, iddonvi, trangthai
      INTO v_btc_cap, v_btc_kv, v_btc_donvi, v_btc_status
      FROM bantochuc
     WHERE idbantochuc = NEW.idbantochuc;

    IF v_btc_status <> 'HOAT_DONG' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'BTC phai hoat dong moi duoc cap nhat giai.';
    END IF;

    IF v_btc_kv <> NEW.idkhuvucphamvi THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'BTC chi duoc quan ly giai trong khu vuc minh quan ly.';
    END IF;

    IF v_btc_donvi IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'BTC dai dien tu nhan khong duoc quan ly giai.';
    END IF;

    SELECT l.duoc_to_chuc_giai
      INTO v_donvi_tochuc
      FROM donvi d
      JOIN loaidonvi l ON l.idloaidonvi = d.idloaidonvi
     WHERE d.iddonvi = v_btc_donvi
       AND d.trangthai = 'HOAT_DONG'
       AND l.trangthai = 'HOAT_DONG';

    IF COALESCE(v_donvi_tochuc, 0) <> 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Don vi cua BTC khong co tham quyen quan ly giai.';
    END IF;

    SELECT COUNT(*)
      INTO v_count
      FROM quyencapbtc_capgiaidau
     WHERE idcapbantochuc = v_btc_cap
       AND idcapgiaidau = NEW.idcapgiaidau
       AND duoc_quan_ly = 1;

    IF v_count = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cap BTC khong co quyen quan ly cap giai dau nay.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `huanluyenvien`
--

DROP TABLE IF EXISTS `huanluyenvien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `huanluyenvien` (
  `idhuanluyenvien` int(11) NOT NULL AUTO_INCREMENT,
  `idnguoidung` int(11) NOT NULL,
  `idkhuvuccongtac` int(11) DEFAULT NULL,
  `iddonvi` int(11) DEFAULT NULL,
  `la_hlv_tu_nhan` tinyint(1) NOT NULL DEFAULT 0,
  `donvicongtac` varchar(300) DEFAULT NULL,
  `bangcap` varchar(300) DEFAULT NULL,
  `kinhnghiem` int(11) NOT NULL DEFAULT 0,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_DUYET',
  PRIMARY KEY (`idhuanluyenvien`),
  UNIQUE KEY `idnguoidung` (`idnguoidung`),
  KEY `idx_hlv_khuvuccongtac` (`idkhuvuccongtac`),
  KEY `idx_hlv_donvi` (`iddonvi`),
  CONSTRAINT `fk_hlv_donvi` FOREIGN KEY (`iddonvi`) REFERENCES `donvi` (`iddonvi`) ON UPDATE CASCADE,
  CONSTRAINT `fk_hlv_khuvuccongtac` FOREIGN KEY (`idkhuvuccongtac`) REFERENCES `khuvuc` (`idkhuvuc`) ON UPDATE CASCADE,
  CONSTRAINT `fk_hlv_nguoidung` FOREIGN KEY (`idnguoidung`) REFERENCES `nguoidung` (`idnguoidung`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_hlv_kinhnghiem` CHECK (`kinhnghiem` >= 0),
  CONSTRAINT `chk_hlv_trangthai` CHECK (`trangthai` in ('CHO_DUYET','DA_XAC_NHAN','BI_HUY_TU_CACH','NGUNG_HOAT_DONG')),
  CONSTRAINT `chk_hlv_tu_nhan` CHECK (`la_hlv_tu_nhan` in (0,1))
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `huanluyenvien`
--

LOCK TABLES `huanluyenvien` WRITE;
/*!40000 ALTER TABLE `huanluyenvien` DISABLE KEYS */;
INSERT INTO `huanluyenvien` VALUES (1,7,1,1,0,'Liên đoàn Bóng chuyền Việt Nam','Chứng chỉ HLV cơ sở',5,'DA_XAC_NHAN'),(2,8,1,1,0,'Liên đoàn Bóng chuyền Việt Nam','Chứng chỉ HLV cơ sở',5,'DA_XAC_NHAN'),(3,9,3,19,0,'Trung tâm TDTT Hà Nội','Chứng chỉ HLV cơ sở',5,'DA_XAC_NHAN'),(4,10,3,19,0,'Trung tâm TDTT Hà Nội','Chứng chỉ HLV cơ sở',5,'DA_XAC_NHAN'),(5,11,1034,21,0,'Trung tâm TDTT Đà Nẵng','Chứng chỉ HLV cơ sở',5,'DA_XAC_NHAN'),(6,12,1034,21,0,'Trung tâm TDTT Đà Nẵng','Chứng chỉ HLV cơ sở',5,'DA_XAC_NHAN'),(7,13,2,23,0,'Trung tâm TDTT Thành phố Hồ Chí Minh','Chứng chỉ HLV cơ sở',5,'DA_XAC_NHAN'),(8,14,2,23,0,'Trung tâm TDTT Thành phố Hồ Chí Minh','Chứng chỉ HLV cơ sở',5,'DA_XAC_NHAN'),(9,15,1037,27,0,'Trung tâm TDTT Phường Bình Dương','Chứng chỉ HLV cơ sở',5,'DA_XAC_NHAN'),(10,16,1038,29,0,'Trung tâm TDTT Phường Vũng Tàu','Chứng chỉ HLV cơ sở',5,'DA_XAC_NHAN'),(11,17,1037,NULL,1,'Tư nhân Bình Dương 01','Chứng chỉ HLV cơ sở',5,'DA_XAC_NHAN'),(12,18,1038,NULL,1,'Tư nhân Vũng Tàu 01','Chứng chỉ HLV cơ sở',5,'DA_XAC_NHAN');
/*!40000 ALTER TABLE `huanluyenvien` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_huanluyenvien_bi
BEFORE INSERT ON huanluyenvien
FOR EACH ROW
BEGIN
    DECLARE v_donvi_khuvuc INT;
    DECLARE v_donvi_trangthai VARCHAR(50);
    DECLARE v_la_cap_thap_nhat TINYINT(1);

    IF NEW.iddonvi IS NULL THEN
        IF NEW.la_hlv_tu_nhan <> 1 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'HLV khong tu nhan phai thuoc mot don vi.';
        END IF;

        IF NEW.idkhuvuccongtac IS NULL THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'HLV tu nhan phai co khu vuc cong tac.';
        END IF;

        SELECT cq.la_cap_thap_nhat
          INTO v_la_cap_thap_nhat
          FROM khuvuc k
          JOIN capchinhquyen cq ON cq.macap = k.capkhuvuc
         WHERE k.idkhuvuc = NEW.idkhuvuccongtac;

        IF v_la_cap_thap_nhat <> 1 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'HLV tu nhan chi duoc o cap thap nhat.';
        END IF;
    ELSE
        IF NEW.la_hlv_tu_nhan <> 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'HLV thuoc don vi khong duoc danh dau tu nhan.';
        END IF;

        SELECT idkhuvuc, trangthai
          INTO v_donvi_khuvuc, v_donvi_trangthai
          FROM donvi
         WHERE iddonvi = NEW.iddonvi;

        IF v_donvi_trangthai <> 'HOAT_DONG' THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Don vi cua HLV phai dang hoat dong.';
        END IF;

        IF NEW.idkhuvuccongtac IS NULL OR NEW.idkhuvuccongtac <> v_donvi_khuvuc THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Khu vuc cong tac cua HLV phai khop don vi.';
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
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_huanluyenvien_bu
BEFORE UPDATE ON huanluyenvien
FOR EACH ROW
BEGIN
    DECLARE v_donvi_khuvuc INT;
    DECLARE v_donvi_trangthai VARCHAR(50);
    DECLARE v_la_cap_thap_nhat TINYINT(1);

    IF NEW.iddonvi IS NULL THEN
        IF NEW.la_hlv_tu_nhan <> 1 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'HLV khong tu nhan phai thuoc mot don vi.';
        END IF;

        IF NEW.idkhuvuccongtac IS NULL THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'HLV tu nhan phai co khu vuc cong tac.';
        END IF;

        SELECT cq.la_cap_thap_nhat
          INTO v_la_cap_thap_nhat
          FROM khuvuc k
          JOIN capchinhquyen cq ON cq.macap = k.capkhuvuc
         WHERE k.idkhuvuc = NEW.idkhuvuccongtac;

        IF v_la_cap_thap_nhat <> 1 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'HLV tu nhan chi duoc o cap thap nhat.';
        END IF;
    ELSE
        IF NEW.la_hlv_tu_nhan <> 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'HLV thuoc don vi khong duoc danh dau tu nhan.';
        END IF;

        SELECT idkhuvuc, trangthai
          INTO v_donvi_khuvuc, v_donvi_trangthai
          FROM donvi
         WHERE iddonvi = NEW.iddonvi;

        IF v_donvi_trangthai <> 'HOAT_DONG' THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Don vi cua HLV phai dang hoat dong.';
        END IF;

        IF NEW.idkhuvuccongtac IS NULL OR NEW.idkhuvuccongtac <> v_donvi_khuvuc THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Khu vuc cong tac cua HLV phai khop don vi.';
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `ketquatrandau`
--

DROP TABLE IF EXISTS `ketquatrandau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ketquatrandau` (
  `idketqua` int(11) NOT NULL AUTO_INCREMENT,
  `idtrandau` int(11) NOT NULL,
  `iddoithang` int(11) DEFAULT NULL,
  `iddoithua` int(11) DEFAULT NULL,
  `diemdoi1` int(11) NOT NULL DEFAULT 0,
  `diemdoi2` int(11) NOT NULL DEFAULT 0,
  `sosetdoi1` int(11) NOT NULL DEFAULT 0,
  `sosetdoi2` int(11) NOT NULL DEFAULT 0,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_CONG_BO',
  `ngayghinhan` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycongbo` datetime DEFAULT NULL,
  `idnguoighinhan` int(11) DEFAULT NULL,
  PRIMARY KEY (`idketqua`),
  UNIQUE KEY `idtrandau` (`idtrandau`),
  KEY `fk_kqtd_doithang` (`iddoithang`),
  KEY `fk_kqtd_doithua` (`iddoithua`),
  KEY `fk_kqtd_nguoighinhan` (`idnguoighinhan`),
  CONSTRAINT `fk_kqtd_doithang` FOREIGN KEY (`iddoithang`) REFERENCES `doibong` (`iddoibong`) ON UPDATE CASCADE,
  CONSTRAINT `fk_kqtd_doithua` FOREIGN KEY (`iddoithua`) REFERENCES `doibong` (`iddoibong`) ON UPDATE CASCADE,
  CONSTRAINT `fk_kqtd_nguoighinhan` FOREIGN KEY (`idnguoighinhan`) REFERENCES `taikhoan` (`idtaikhoan`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_kqtd_tran` FOREIGN KEY (`idtrandau`) REFERENCES `trandau` (`idtrandau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_kqtd_diem` CHECK (`diemdoi1` >= 0 and `diemdoi2` >= 0 and `sosetdoi1` >= 0 and `sosetdoi2` >= 0),
  CONSTRAINT `chk_kqtd_set` CHECK (`sosetdoi1` <= 5 and `sosetdoi2` <= 5),
  CONSTRAINT `chk_kqtd_trangthai` CHECK (`trangthai` in ('CHO_CONG_BO','DA_CONG_BO','DA_DIEU_CHINH','BI_HUY')),
  CONSTRAINT `chk_kqtd_congbo` CHECK (`ngaycongbo` is null or `ngaycongbo` >= `ngayghinhan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ketquatrandau`
--

LOCK TABLES `ketquatrandau` WRITE;
/*!40000 ALTER TABLE `ketquatrandau` DISABLE KEYS */;
/*!40000 ALTER TABLE `ketquatrandau` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_ketqua_bi
BEFORE INSERT ON ketquatrandau
FOR EACH ROW
BEGIN
    DECLARE v_doi1 INT;
    DECLARE v_doi2 INT;
    DECLARE v_status VARCHAR(50);
    SELECT COALESCE(t.iddoibong1, s1.iddoibong), COALESCE(t.iddoibong2, s2.iddoibong), t.trangthai
    INTO v_doi1, v_doi2, v_status
    FROM trandau t
    LEFT JOIN trandauslot s1 ON s1.idtrandau = t.idtrandau AND s1.slot_so = 1
    LEFT JOIN trandauslot s2 ON s2.idtrandau = t.idtrandau AND s2.slot_so = 2
    WHERE t.idtrandau = NEW.idtrandau;

    IF v_doi1 IS NULL OR v_doi2 IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Không thể ghi kết quả khi trận chưa đủ 2 đội.';
    END IF;
    IF NEW.iddoithang IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kết quả phải xác định đội thắng.';
    END IF;
    IF NEW.iddoithang NOT IN (v_doi1, v_doi2) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Đội thắng phải là một trong hai đội của trận.';
    END IF;
    IF NEW.iddoithua IS NULL THEN
        IF NEW.iddoithang = v_doi1 THEN SET NEW.iddoithua = v_doi2; ELSE SET NEW.iddoithua = v_doi1; END IF;
    END IF;
    IF NEW.iddoithua NOT IN (v_doi1, v_doi2) OR NEW.iddoithua = NEW.iddoithang THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Đội thua phải là đội còn lại của trận.';
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
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_ketqua_ai
AFTER INSERT ON ketquatrandau
FOR EACH ROW
BEGIN
    UPDATE trandau
    SET trangthai = 'DA_KET_THUC', thoigianketthuc = COALESCE(thoigianketthuc, NEW.ngayghinhan), ngaycapnhat = CURRENT_TIMESTAMP
    WHERE idtrandau = NEW.idtrandau;
    CALL sp_cap_nhat_slot_tu_ketqua(NEW.idtrandau);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `khieunai`
--

DROP TABLE IF EXISTS `khieunai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `khieunai` (
  `idkhieunai` int(11) NOT NULL AUTO_INCREMENT,
  `idnguoigui` int(11) NOT NULL,
  `idgiaidau` int(11) NOT NULL,
  `idtrandau` int(11) DEFAULT NULL,
  `tieude` varchar(300) NOT NULL,
  `noidung` varchar(2000) NOT NULL,
  `minhchung` varchar(500) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_TIEP_NHAN',
  `ngaygui` datetime NOT NULL DEFAULT current_timestamp(),
  `ngayxuly` datetime DEFAULT NULL,
  `idnguoixuly` int(11) DEFAULT NULL,
  PRIMARY KEY (`idkhieunai`),
  KEY `fk_khieunai_nguoigui` (`idnguoigui`),
  KEY `fk_khieunai_giaidau` (`idgiaidau`),
  KEY `fk_khieunai_tran` (`idtrandau`),
  KEY `fk_khieunai_nguoixuly` (`idnguoixuly`),
  CONSTRAINT `fk_khieunai_giaidau` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON UPDATE CASCADE,
  CONSTRAINT `fk_khieunai_nguoigui` FOREIGN KEY (`idnguoigui`) REFERENCES `taikhoan` (`idtaikhoan`) ON UPDATE CASCADE,
  CONSTRAINT `fk_khieunai_nguoixuly` FOREIGN KEY (`idnguoixuly`) REFERENCES `taikhoan` (`idtaikhoan`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_khieunai_tran` FOREIGN KEY (`idtrandau`) REFERENCES `trandau` (`idtrandau`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_khieunai_trangthai` CHECK (`trangthai` in ('CHO_TIEP_NHAN','DANG_XU_LY','DA_XU_LY','TU_CHOI','KHONG_XU_LY')),
  CONSTRAINT `chk_khieunai_ngayxuly` CHECK (`ngayxuly` is null or `ngayxuly` >= `ngaygui`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `khieunai`
--

LOCK TABLES `khieunai` WRITE;
/*!40000 ALTER TABLE `khieunai` DISABLE KEYS */;
/*!40000 ALTER TABLE `khieunai` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `khuvuc`
--

DROP TABLE IF EXISTS `khuvuc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `khuvuc` (
  `idkhuvuc` int(11) NOT NULL AUTO_INCREMENT,
  `makhuvuc` varchar(100) NOT NULL,
  `tenkhuvuc` varchar(300) NOT NULL,
  `capkhuvuc` varchar(50) NOT NULL,
  `idkhuvuccha` int(11) DEFAULT NULL,
  `mota` varchar(1000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'HOAT_DONG',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL,
  PRIMARY KEY (`idkhuvuc`),
  UNIQUE KEY `makhuvuc` (`makhuvuc`),
  KEY `fk_khuvuc_cha` (`idkhuvuccha`),
  KEY `idx_khuvuc_cap_cha_trangthai` (`capkhuvuc`,`idkhuvuccha`,`trangthai`),
  CONSTRAINT `fk_khuvuc_capchinhquyen` FOREIGN KEY (`capkhuvuc`) REFERENCES `capchinhquyen` (`macap`) ON UPDATE CASCADE,
  CONSTRAINT `fk_khuvuc_cha` FOREIGN KEY (`idkhuvuccha`) REFERENCES `khuvuc` (`idkhuvuc`) ON UPDATE CASCADE,
  CONSTRAINT `chk_khuvuc_trangthai` CHECK (`trangthai` in ('HOAT_DONG','NGUNG_SU_DUNG'))
) ENGINE=InnoDB AUTO_INCREMENT=1040 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `khuvuc`
--

LOCK TABLES `khuvuc` WRITE;
/*!40000 ALTER TABLE `khuvuc` DISABLE KEYS */;
INSERT INTO `khuvuc` VALUES (1,'VN','Việt Nam','QUOC_GIA',NULL,'Khu vực cấp quốc gia.','HOAT_DONG','2026-05-22 10:33:15','2026-05-25 04:13:54'),(2,'TP_HCM','Hồ Chí Minh','TINH_THANH',1,'Khu vực cấp tỉnh/thành.','HOAT_DONG','2026-05-22 10:47:00','2026-05-25 04:13:54'),(3,'HA_NOI','Hà Nội','TINH_THANH',1,'Khu vực cấp tỉnh/thành.','HOAT_DONG','2026-05-22 10:47:00','2026-05-25 04:13:54'),(20,'PHUONG_SAI_GON','Phường Sài Gòn','XA_PHUONG',2,'Phường/xã thuộc Hồ Chí Minh.','HOAT_DONG','2026-05-22 10:47:00','2026-05-25 04:13:54'),(21,'PHUONG_BEN_THANH','Phường Bến Thành','XA_PHUONG',2,'Xã/phường cấp thấp nhất trong mô hình quản lý mới.','HOAT_DONG','2026-05-22 10:47:00',NULL),(30,'PHUONG_HOAN_KIEM','Phường Hoàn Kiếm','XA_PHUONG',3,'Xã/phường trực thuộc tỉnh/thành, không qua cấp quận.','HOAT_DONG','2026-05-22 10:47:00',NULL),(1034,'DA_NANG','Đà Nẵng','TINH_THANH',1,'Khu vực cấp tỉnh/thành.','HOAT_DONG','2026-05-25 04:13:54',NULL),(1037,'PHUONG_BINH_DUONG','Phường Bình Dương','XA_PHUONG',2,'Phường/xã thuộc Hồ Chí Minh.','HOAT_DONG','2026-05-25 04:13:54',NULL),(1038,'PHUONG_VUNG_TAU','Phường Vũng Tàu','XA_PHUONG',2,'Phường/xã thuộc Hồ Chí Minh.','HOAT_DONG','2026-05-25 04:13:54',NULL);
/*!40000 ALTER TABLE `khuvuc` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_khuvuc_bi
BEFORE INSERT ON khuvuc
FOR EACH ROW
BEGIN
    DECLARE v_capcha_yeucau VARCHAR(50);
    DECLARE v_capcha_thucte VARCHAR(50);
    DECLARE v_cap_trangthai VARCHAR(50);

    SET v_capcha_yeucau = NULL;
    SET v_capcha_thucte = NULL;
    SET v_cap_trangthai = NULL;

    SELECT macapcha, trangthai
      INTO v_capcha_yeucau, v_cap_trangthai
      FROM capchinhquyen
     WHERE macap = NEW.capkhuvuc;

    IF v_cap_trangthai <> 'HOAT_DONG' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cap khu vuc phai dang hoat dong.';
    END IF;

    IF v_capcha_yeucau IS NULL THEN
        IF NEW.idkhuvuccha IS NOT NULL THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Cap goc khong duoc co khu vuc cha.';
        END IF;
    ELSE
        IF NEW.idkhuvuccha IS NULL THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Khu vuc cap con phai co khu vuc cha.';
        END IF;

        SELECT capkhuvuc
          INTO v_capcha_thucte
          FROM khuvuc
         WHERE idkhuvuc = NEW.idkhuvuccha;

        IF v_capcha_thucte <> v_capcha_yeucau THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Cap khu vuc cha khong dung cap duoc khai bao.';
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
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_khuvuc_bu
BEFORE UPDATE ON khuvuc
FOR EACH ROW
BEGIN
    DECLARE v_capcha_yeucau VARCHAR(50);
    DECLARE v_capcha_thucte VARCHAR(50);
    DECLARE v_cap_trangthai VARCHAR(50);

    SET v_capcha_yeucau = NULL;
    SET v_capcha_thucte = NULL;
    SET v_cap_trangthai = NULL;

    SELECT macapcha, trangthai
      INTO v_capcha_yeucau, v_cap_trangthai
      FROM capchinhquyen
     WHERE macap = NEW.capkhuvuc;

    IF v_cap_trangthai <> 'HOAT_DONG' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cap khu vuc phai dang hoat dong.';
    END IF;

    IF v_capcha_yeucau IS NULL THEN
        IF NEW.idkhuvuccha IS NOT NULL THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Cap goc khong duoc co khu vuc cha.';
        END IF;
    ELSE
        IF NEW.idkhuvuccha IS NULL THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Khu vuc cap con phai co khu vuc cha.';
        END IF;

        SELECT capkhuvuc
          INTO v_capcha_thucte
          FROM khuvuc
         WHERE idkhuvuc = NEW.idkhuvuccha;

        IF v_capcha_thucte <> v_capcha_yeucau THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Cap khu vuc cha khong dung cap duoc khai bao.';
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `lichsudangnhap`
--

DROP TABLE IF EXISTS `lichsudangnhap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lichsudangnhap` (
  `idlichsu` int(11) NOT NULL AUTO_INCREMENT,
  `idtaikhoan` int(11) NOT NULL,
  `thoigian` datetime NOT NULL DEFAULT current_timestamp(),
  `ipaddress` varchar(100) DEFAULT NULL,
  `thietbi` varchar(300) DEFAULT NULL,
  `ketqua` varchar(50) NOT NULL,
  `ghichu` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`idlichsu`),
  KEY `fk_lsdn_taikhoan` (`idtaikhoan`),
  CONSTRAINT `fk_lsdn_taikhoan` FOREIGN KEY (`idtaikhoan`) REFERENCES `taikhoan` (`idtaikhoan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_lsdn_ketqua` CHECK (`ketqua` in ('THANH_CONG','THAT_BAI'))
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lichsudangnhap`
--

LOCK TABLES `lichsudangnhap` WRITE;
/*!40000 ALTER TABLE `lichsudangnhap` DISABLE KEYS */;
INSERT INTO `lichsudangnhap` VALUES (1,67,'2026-05-25 15:54:43','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','THANH_CONG','Dang nhap thanh cong'),(2,5,'2026-05-25 16:00:09','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','THANH_CONG','Dang nhap thanh cong'),(3,15,'2026-05-25 16:17:13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','THANH_CONG','Dang nhap thanh cong'),(4,17,'2026-05-25 16:17:28','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','THANH_CONG','Dang nhap thanh cong'),(5,17,'2026-05-25 16:20:12','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','THANH_CONG','Dang nhap thanh cong'),(6,5,'2026-05-25 16:28:04','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','THANH_CONG','Dang nhap thanh cong'),(7,115,'2026-05-29 17:55:55','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','THAT_BAI','Sai mat khau'),(8,115,'2026-05-29 17:56:02','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','THANH_CONG','Dang nhap thanh cong'),(9,115,'2026-05-30 12:22:20','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','THANH_CONG','Dang nhap thanh cong'),(10,5,'2026-05-30 12:22:31','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','THANH_CONG','Dang nhap thanh cong'),(11,5,'2026-05-30 12:23:09','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','THAT_BAI','Sai mat khau'),(12,5,'2026-05-30 12:23:12','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','THANH_CONG','Dang nhap thanh cong'),(13,115,'2026-05-30 12:38:12','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','THANH_CONG','Dang nhap thanh cong'),(14,5,'2026-05-30 16:12:10','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','THANH_CONG','Dang nhap thanh cong');
/*!40000 ALTER TABLE `lichsudangnhap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lichsumatkhau`
--

DROP TABLE IF EXISTS `lichsumatkhau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lichsumatkhau` (
  `idlichsumatkhau` int(11) NOT NULL AUTO_INCREMENT,
  `idtaikhoan` int(11) NOT NULL,
  `passwordold` varchar(255) NOT NULL,
  `ngaythaydoi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idlichsumatkhau`),
  KEY `fk_lsmk_taikhoan` (`idtaikhoan`),
  CONSTRAINT `fk_lsmk_taikhoan` FOREIGN KEY (`idtaikhoan`) REFERENCES `taikhoan` (`idtaikhoan`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lichsumatkhau`
--

LOCK TABLES `lichsumatkhau` WRITE;
/*!40000 ALTER TABLE `lichsumatkhau` DISABLE KEYS */;
INSERT INTO `lichsumatkhau` VALUES (1,5,'$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','2026-05-30 12:23:00'),(2,5,'$2y$10$rkQ.uq56gGlzR8E9ph0.ZelM.Ak.anxgYG7qv1OQ.89tBi7ieIFsS','2026-05-30 12:23:27');
/*!40000 ALTER TABLE `lichsumatkhau` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lichsuthanhviendoibong`
--

DROP TABLE IF EXISTS `lichsuthanhviendoibong`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lichsuthanhviendoibong` (
  `idlichsu` int(11) NOT NULL AUTO_INCREMENT,
  `idthanhvien` int(11) NOT NULL,
  `hanhdong` varchar(100) NOT NULL,
  `ghichu` varchar(1000) DEFAULT NULL,
  `ngaythuchien` datetime NOT NULL DEFAULT current_timestamp(),
  `idnguoithuchien` int(11) DEFAULT NULL,
  PRIMARY KEY (`idlichsu`),
  KEY `fk_lstvdb_thanhvien` (`idthanhvien`),
  KEY `fk_lstvdb_taikhoan` (`idnguoithuchien`),
  CONSTRAINT `fk_lstvdb_taikhoan` FOREIGN KEY (`idnguoithuchien`) REFERENCES `taikhoan` (`idtaikhoan`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_lstvdb_thanhvien` FOREIGN KEY (`idthanhvien`) REFERENCES `thanhviendoibong` (`idthanhvien`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_lstvdb_hanhdong` CHECK (`hanhdong` in ('THEM_THANH_VIEN','XOA_THANH_VIEN','CHUYEN_DOI_THANH_VIEN','CAP_NHAT_VAI_TRO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lichsuthanhviendoibong`
--

LOCK TABLES `lichsuthanhviendoibong` WRITE;
/*!40000 ALTER TABLE `lichsuthanhviendoibong` DISABLE KEYS */;
/*!40000 ALTER TABLE `lichsuthanhviendoibong` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loaidonvi`
--

DROP TABLE IF EXISTS `loaidonvi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loaidonvi` (
  `idloaidonvi` int(11) NOT NULL AUTO_INCREMENT,
  `maloaidonvi` varchar(100) NOT NULL,
  `tenloaidonvi` varchar(300) NOT NULL,
  `macapapdung` varchar(50) NOT NULL,
  `duoc_to_chuc_giai` tinyint(1) NOT NULL DEFAULT 0,
  `mota` varchar(1000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'HOAT_DONG',
  PRIMARY KEY (`idloaidonvi`),
  UNIQUE KEY `uq_loaidonvi_ma` (`maloaidonvi`),
  KEY `idx_loaidonvi_cap` (`macapapdung`),
  CONSTRAINT `fk_loaidonvi_capchinhquyen` FOREIGN KEY (`macapapdung`) REFERENCES `capchinhquyen` (`macap`) ON UPDATE CASCADE,
  CONSTRAINT `chk_loaidonvi_tochuc` CHECK (`duoc_to_chuc_giai` in (0,1)),
  CONSTRAINT `chk_loaidonvi_trangthai` CHECK (`trangthai` in ('HOAT_DONG','NGUNG_SU_DUNG'))
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loaidonvi`
--

LOCK TABLES `loaidonvi` WRITE;
/*!40000 ALTER TABLE `loaidonvi` DISABLE KEYS */;
INSERT INTO `loaidonvi` VALUES (1,'LIEN_DOAN_BONG_CHUYEN_VN','Liên đoàn Bóng chuyền VN','QUOC_GIA',1,'Đơn vị cấp quốc gia có thẩm quyền tổ chức giải.','HOAT_DONG'),(2,'SO_VH_TT_TINH','Sở VH-TT các tỉnh','TINH_THANH',1,'Đơn vị cấp tỉnh/thành có thẩm quyền tổ chức giải.','HOAT_DONG'),(3,'TRUNG_TAM_HL_TDTT_TINH','Trung tâm huấn luyện và thi đấu TDTT tỉnh','TINH_THANH',0,'Đơn vị cấp tỉnh/thành có BTC đại diện đăng ký đội.','HOAT_DONG'),(4,'TRUNG_TAM_TDTT_XA_PHUONG','Trung tâm TDTT Phường/xã','XA_PHUONG',0,'Đơn vị cấp xã/phường có BTC đại diện đăng ký đội.','HOAT_DONG'),(5,'TRUNG_TAM_VH_TT_XA_PHUONG','Trung tâm VH-TT Phường/xã','XA_PHUONG',1,'Đơn vị cấp xã/phường có thẩm quyền tổ chức giải.','HOAT_DONG'),(6,'NHA_VAN_HOA_THIEU_NHI_XA_PHUONG','Nhà văn hóa thiếu nhi','XA_PHUONG',0,'Đơn vị cấp xã/phường có BTC đại diện đăng ký đội.','HOAT_DONG'),(7,'TU_NHAN','Đơn vị tư nhân','XA_PHUONG',0,'Đơn vị tư nhân, không có quyền tổ chức giải.','HOAT_DONG');
/*!40000 ALTER TABLE `loaidonvi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loimoidoibong`
--

DROP TABLE IF EXISTS `loimoidoibong`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loimoidoibong` (
  `idloimoi` int(11) NOT NULL AUTO_INCREMENT,
  `iddoibong` int(11) NOT NULL,
  `idvandongvien` int(11) NOT NULL,
  `idhuanluyenvien` int(11) NOT NULL,
  `noidung` varchar(1000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_PHAN_HOI',
  `ngaygui` datetime NOT NULL DEFAULT current_timestamp(),
  `ngayphanhoi` datetime DEFAULT NULL,
  `ngayhethan` datetime NOT NULL,
  PRIMARY KEY (`idloimoi`),
  KEY `fk_lmdb_doibong` (`iddoibong`),
  KEY `fk_lmdb_vdv` (`idvandongvien`),
  KEY `fk_lmdb_hlv` (`idhuanluyenvien`),
  CONSTRAINT `fk_lmdb_doibong` FOREIGN KEY (`iddoibong`) REFERENCES `doibong` (`iddoibong`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lmdb_hlv` FOREIGN KEY (`idhuanluyenvien`) REFERENCES `huanluyenvien` (`idhuanluyenvien`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lmdb_vdv` FOREIGN KEY (`idvandongvien`) REFERENCES `vandongvien` (`idvandongvien`) ON UPDATE CASCADE,
  CONSTRAINT `chk_lmdb_trangthai` CHECK (`trangthai` in ('CHO_PHAN_HOI','DONG_Y','TU_CHOI','HET_HAN')),
  CONSTRAINT `chk_lmdb_han` CHECK (`ngayhethan` >= `ngaygui`),
  CONSTRAINT `chk_lmdb_phanhoi` CHECK (`ngayphanhoi` is null or `ngayphanhoi` >= `ngaygui`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loimoidoibong`
--

LOCK TABLES `loimoidoibong` WRITE;
/*!40000 ALTER TABLE `loimoidoibong` DISABLE KEYS */;
/*!40000 ALTER TABLE `loimoidoibong` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `luatthidau`
--

DROP TABLE IF EXISTS `luatthidau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `luatthidau` (
  `idluat` int(11) NOT NULL AUTO_INCREMENT,
  `tenluat` varchar(300) NOT NULL,
  `phienban` varchar(100) DEFAULT NULL,
  `so_vdv_thi_dau` int(11) NOT NULL DEFAULT 6,
  `so_vdv_du_bi` int(11) NOT NULL DEFAULT 6,
  `tong_vdv_toi_da` int(11) NOT NULL DEFAULT 12,
  `kieu_tran` varchar(20) NOT NULL DEFAULT 'BO5',
  `so_set_thang_tran` int(11) NOT NULL DEFAULT 3,
  `diem_set_thuong` int(11) NOT NULL DEFAULT 25,
  `diem_set_quyet_dinh` int(11) NOT NULL DEFAULT 15,
  `cach_biet_toi_thieu` int(11) NOT NULL DEFAULT 2,
  `noidung_mota` varchar(3000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'HOAT_DONG',
  PRIMARY KEY (`idluat`),
  CONSTRAINT `chk_luat_kieu` CHECK (`kieu_tran` in ('BO3','BO5')),
  CONSTRAINT `chk_luat_soluong` CHECK (`so_vdv_thi_dau` > 0 and `so_vdv_du_bi` >= 0 and `tong_vdv_toi_da` >= `so_vdv_thi_dau`),
  CONSTRAINT `chk_luat_set` CHECK (`so_set_thang_tran` in (2,3) and `diem_set_thuong` > 0 and `diem_set_quyet_dinh` > 0 and `cach_biet_toi_thieu` > 0),
  CONSTRAINT `chk_luat_trangthai` CHECK (`trangthai` in ('HOAT_DONG','NGUNG_SU_DUNG'))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `luatthidau`
--

LOCK TABLES `luatthidau` WRITE;
/*!40000 ALTER TABLE `luatthidau` DISABLE KEYS */;
INSERT INTO `luatthidau` VALUES (1,'Luật bóng chuyền trong nhà 6 người - BO5','VTMS-2026',6,6,12,'BO5',3,25,15,2,'Mẫu luật mặc định cho giải chính thức','HOAT_DONG'),(2,'Luật bóng chuyền trong nhà 6 người - BO3','VTMS-2026',6,6,12,'BO3',2,25,15,2,'Mẫu luật rút gọn cho giải phong trào','HOAT_DONG');
/*!40000 ALTER TABLE `luatthidau` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nguoidung`
--

DROP TABLE IF EXISTS `nguoidung`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nguoidung` (
  `idnguoidung` int(11) NOT NULL AUTO_INCREMENT,
  `idtaikhoan` int(11) NOT NULL,
  `ten` varchar(100) NOT NULL,
  `hodem` varchar(200) NOT NULL,
  `gioitinh` varchar(20) NOT NULL,
  `ngaysinh` date DEFAULT NULL,
  `quequan` varchar(500) DEFAULT NULL,
  `diachi` varchar(500) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `cccd` varchar(20) DEFAULT NULL,
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL,
  PRIMARY KEY (`idnguoidung`),
  UNIQUE KEY `idtaikhoan` (`idtaikhoan`),
  UNIQUE KEY `cccd` (`cccd`),
  CONSTRAINT `fk_nguoidung_taikhoan` FOREIGN KEY (`idtaikhoan`) REFERENCES `taikhoan` (`idtaikhoan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_nguoidung_gioitinh` CHECK (`gioitinh` in ('NAM','NU','KHAC'))
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nguoidung`
--

LOCK TABLES `nguoidung` WRITE;
/*!40000 ALTER TABLE `nguoidung` DISABLE KEYS */;
INSERT INTO `nguoidung` VALUES (1,1,'Quốc Gia','BTC','NAM','1980-01-01','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:54',NULL),(2,2,'Hà Nội','BTC','NAM','1980-01-01','HA_NOI','Sở VH-TT Hà Nội',NULL,NULL,'2026-05-25 04:13:54',NULL),(3,3,'Đà Nẵng','BTC','NAM','1980-01-01','DA_NANG','Sở VH-TT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:54',NULL),(4,4,'Hồ Chí Minh','BTC','NAM','1980-01-01','TP_HCM','Sở VH-TT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:54',NULL),(5,5,'Bình Dương 01','BTC Phường','NAM','1980-01-01','PHUONG_BINH_DUONG','Trung tâm VH-TT Phường Bình Dương',NULL,NULL,'2026-05-25 04:13:54',NULL),(6,6,'Vũng Tàu 01','BTC Phường','NAM','1980-01-01','PHUONG_VUNG_TAU','Trung tâm VH-TT Phường Vũng Tàu',NULL,NULL,'2026-05-25 04:13:54',NULL),(7,7,'01','HLV Quốc Gia','NAM','1985-01-01','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:54',NULL),(8,8,'02','HLV Quốc Gia','NAM','1985-01-01','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:54',NULL),(9,9,'01','HLV Hà Nội','NAM','1985-01-01','HA_NOI','Trung tâm TDTT Hà Nội',NULL,NULL,'2026-05-25 04:13:54',NULL),(10,10,'02','HLV Hà Nội','NAM','1985-01-01','HA_NOI','Trung tâm TDTT Hà Nội',NULL,NULL,'2026-05-25 04:13:54',NULL),(11,11,'01','HLV Đà Nẵng','NAM','1985-01-01','DA_NANG','Trung tâm TDTT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:54',NULL),(12,12,'02','HLV Đà Nẵng','NAM','1985-01-01','DA_NANG','Trung tâm TDTT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:54',NULL),(13,13,'01','HLV Hồ Chí Minh','NAM','1985-01-01','TP_HCM','Trung tâm TDTT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:54',NULL),(14,14,'02','HLV Hồ Chí Minh','NAM','1985-01-01','TP_HCM','Trung tâm TDTT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:54',NULL),(15,15,'01','HLV Phường Bình Dương','NAM','1985-01-01','PHUONG_BINH_DUONG','Trung tâm TDTT Phường Bình Dương',NULL,NULL,'2026-05-25 04:13:54',NULL),(16,16,'01','HLV Phường Vũng Tàu','NAM','1985-01-01','PHUONG_VUNG_TAU','Trung tâm TDTT Phường Vũng Tàu',NULL,NULL,'2026-05-25 04:13:54',NULL),(17,17,'01','HLV Tư Nhân Bình Dương','NAM','1985-01-01','PHUONG_BINH_DUONG','Tư nhân Bình Dương 01',NULL,NULL,'2026-05-25 04:13:54',NULL),(18,18,'01','HLV Tư Nhân Vũng Tàu','NAM','1985-01-01','PHUONG_VUNG_TAU','Tư nhân Vũng Tàu 01',NULL,NULL,'2026-05-25 04:13:54',NULL),(19,19,'01','VDV Quốc Gia','NAM','2000-01-02','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:54',NULL),(20,20,'02','VDV Quốc Gia','NAM','2000-01-03','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:54',NULL),(21,21,'03','VDV Quốc Gia','NAM','2000-01-04','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:54',NULL),(22,22,'04','VDV Quốc Gia','NAM','2000-01-05','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:54',NULL),(23,23,'05','VDV Quốc Gia','NAM','2000-01-06','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:54',NULL),(24,24,'06','VDV Quốc Gia','NAM','2000-01-07','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:54',NULL),(25,25,'07','VDV Quốc Gia','NAM','2000-01-08','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:54',NULL),(26,26,'08','VDV Quốc Gia','NAM','2000-01-09','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:54',NULL),(27,27,'09','VDV Quốc Gia','NAM','2000-01-10','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:54',NULL),(28,28,'10','VDV Quốc Gia','NAM','2000-01-11','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:54',NULL),(29,29,'11','VDV Quốc Gia','NAM','2000-01-12','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:54',NULL),(30,30,'12','VDV Quốc Gia','NAM','2000-01-13','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:54',NULL),(31,31,'01','VDV Hà Nội','NAM','2000-01-02','HA_NOI','Trung tâm TDTT Hà Nội',NULL,NULL,'2026-05-25 04:13:55',NULL),(32,32,'02','VDV Hà Nội','NAM','2000-01-03','HA_NOI','Trung tâm TDTT Hà Nội',NULL,NULL,'2026-05-25 04:13:55',NULL),(33,33,'03','VDV Hà Nội','NAM','2000-01-04','HA_NOI','Trung tâm TDTT Hà Nội',NULL,NULL,'2026-05-25 04:13:55',NULL),(34,34,'04','VDV Hà Nội','NAM','2000-01-05','HA_NOI','Trung tâm TDTT Hà Nội',NULL,NULL,'2026-05-25 04:13:55',NULL),(35,35,'05','VDV Hà Nội','NAM','2000-01-06','HA_NOI','Trung tâm TDTT Hà Nội',NULL,NULL,'2026-05-25 04:13:55',NULL),(36,36,'06','VDV Hà Nội','NAM','2000-01-07','HA_NOI','Trung tâm TDTT Hà Nội',NULL,NULL,'2026-05-25 04:13:55',NULL),(37,37,'07','VDV Hà Nội','NAM','2000-01-08','HA_NOI','Trung tâm TDTT Hà Nội',NULL,NULL,'2026-05-25 04:13:55',NULL),(38,38,'08','VDV Hà Nội','NAM','2000-01-09','HA_NOI','Trung tâm TDTT Hà Nội',NULL,NULL,'2026-05-25 04:13:55',NULL),(39,39,'09','VDV Hà Nội','NAM','2000-01-10','HA_NOI','Trung tâm TDTT Hà Nội',NULL,NULL,'2026-05-25 04:13:55',NULL),(40,40,'10','VDV Hà Nội','NAM','2000-01-11','HA_NOI','Trung tâm TDTT Hà Nội',NULL,NULL,'2026-05-25 04:13:55',NULL),(41,41,'11','VDV Hà Nội','NAM','2000-01-12','HA_NOI','Trung tâm TDTT Hà Nội',NULL,NULL,'2026-05-25 04:13:55',NULL),(42,42,'12','VDV Hà Nội','NAM','2000-01-13','HA_NOI','Trung tâm TDTT Hà Nội',NULL,NULL,'2026-05-25 04:13:55',NULL),(43,43,'13','VDV Đà Nẵng','NAM','2000-01-14','DA_NANG','Trung tâm TDTT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:55',NULL),(44,44,'14','VDV Đà Nẵng','NAM','2000-01-15','DA_NANG','Trung tâm TDTT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:55',NULL),(45,45,'15','VDV Đà Nẵng','NAM','2000-01-16','DA_NANG','Trung tâm TDTT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:55',NULL),(46,46,'16','VDV Đà Nẵng','NAM','2000-01-17','DA_NANG','Trung tâm TDTT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:55',NULL),(47,47,'17','VDV Đà Nẵng','NAM','2000-01-18','DA_NANG','Trung tâm TDTT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:55',NULL),(48,48,'18','VDV Đà Nẵng','NAM','2000-01-19','DA_NANG','Trung tâm TDTT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:55',NULL),(49,49,'19','VDV Đà Nẵng','NAM','2000-01-20','DA_NANG','Trung tâm TDTT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:55',NULL),(50,50,'20','VDV Đà Nẵng','NAM','2000-01-21','DA_NANG','Trung tâm TDTT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:55',NULL),(51,51,'21','VDV Đà Nẵng','NAM','2000-01-22','DA_NANG','Trung tâm TDTT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:55',NULL),(52,52,'22','VDV Đà Nẵng','NAM','2000-01-23','DA_NANG','Trung tâm TDTT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:55',NULL),(53,53,'23','VDV Đà Nẵng','NAM','2000-01-24','DA_NANG','Trung tâm TDTT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:55',NULL),(54,54,'24','VDV Đà Nẵng','NAM','2000-01-25','DA_NANG','Trung tâm TDTT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:55',NULL),(55,55,'25','VDV Hồ Chí Minh','NAM','2000-01-26','TP_HCM','Trung tâm TDTT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:55',NULL),(56,56,'26','VDV Hồ Chí Minh','NAM','2000-01-27','TP_HCM','Trung tâm TDTT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:55',NULL),(57,57,'27','VDV Hồ Chí Minh','NAM','2000-01-28','TP_HCM','Trung tâm TDTT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:55',NULL),(58,58,'28','VDV Hồ Chí Minh','NAM','2000-01-29','TP_HCM','Trung tâm TDTT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:55',NULL),(59,59,'29','VDV Hồ Chí Minh','NAM','2000-01-30','TP_HCM','Trung tâm TDTT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:55',NULL),(60,60,'30','VDV Hồ Chí Minh','NAM','2000-01-31','TP_HCM','Trung tâm TDTT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:55',NULL),(61,61,'31','VDV Hồ Chí Minh','NAM','2000-02-01','TP_HCM','Trung tâm TDTT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:55',NULL),(62,62,'32','VDV Hồ Chí Minh','NAM','2000-02-02','TP_HCM','Trung tâm TDTT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:55',NULL),(63,63,'33','VDV Hồ Chí Minh','NAM','2000-02-03','TP_HCM','Trung tâm TDTT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:55',NULL),(64,64,'34','VDV Hồ Chí Minh','NAM','2000-02-04','TP_HCM','Trung tâm TDTT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:55',NULL),(65,65,'35','VDV Hồ Chí Minh','NAM','2000-02-05','TP_HCM','Trung tâm TDTT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:55',NULL),(66,66,'36','VDV Hồ Chí Minh','NAM','2000-02-06','TP_HCM','Trung tâm TDTT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:55',NULL),(67,67,'01','VDV Phường Bình Dương','NAM','2000-01-02','PHUONG_BINH_DUONG','Trung tâm TDTT Phường Bình Dương',NULL,NULL,'2026-05-25 04:13:55',NULL),(68,68,'02','VDV Phường Bình Dương','NAM','2000-01-03','PHUONG_BINH_DUONG','Trung tâm TDTT Phường Bình Dương',NULL,NULL,'2026-05-25 04:13:55',NULL),(69,69,'03','VDV Phường Bình Dương','NAM','2000-01-04','PHUONG_BINH_DUONG','Trung tâm TDTT Phường Bình Dương',NULL,NULL,'2026-05-25 04:13:55',NULL),(70,70,'04','VDV Phường Bình Dương','NAM','2000-01-05','PHUONG_BINH_DUONG','Trung tâm TDTT Phường Bình Dương',NULL,NULL,'2026-05-25 04:13:55',NULL),(71,71,'05','VDV Phường Bình Dương','NAM','2000-01-06','PHUONG_BINH_DUONG','Trung tâm TDTT Phường Bình Dương',NULL,NULL,'2026-05-25 04:13:55',NULL),(72,72,'06','VDV Phường Bình Dương','NAM','2000-01-07','PHUONG_BINH_DUONG','Trung tâm TDTT Phường Bình Dương',NULL,NULL,'2026-05-25 04:13:55',NULL),(73,73,'07','VDV Phường Vũng Tàu','NAM','2000-01-08','PHUONG_VUNG_TAU','Trung tâm TDTT Phường Vũng Tàu',NULL,NULL,'2026-05-25 04:13:55',NULL),(74,74,'08','VDV Phường Vũng Tàu','NAM','2000-01-09','PHUONG_VUNG_TAU','Trung tâm TDTT Phường Vũng Tàu',NULL,NULL,'2026-05-25 04:13:55',NULL),(75,75,'09','VDV Phường Vũng Tàu','NAM','2000-01-10','PHUONG_VUNG_TAU','Trung tâm TDTT Phường Vũng Tàu',NULL,NULL,'2026-05-25 04:13:55',NULL),(76,76,'10','VDV Phường Vũng Tàu','NAM','2000-01-11','PHUONG_VUNG_TAU','Trung tâm TDTT Phường Vũng Tàu',NULL,NULL,'2026-05-25 04:13:55',NULL),(77,77,'11','VDV Phường Vũng Tàu','NAM','2000-01-12','PHUONG_VUNG_TAU','Trung tâm TDTT Phường Vũng Tàu',NULL,NULL,'2026-05-25 04:13:55',NULL),(78,78,'12','VDV Phường Vũng Tàu','NAM','2000-01-13','PHUONG_VUNG_TAU','Trung tâm TDTT Phường Vũng Tàu',NULL,NULL,'2026-05-25 04:13:55',NULL),(79,79,'13','VDV Tư Nhân Bình Dương','NAM','2000-01-14','PHUONG_BINH_DUONG','Tư nhân Bình Dương 01',NULL,NULL,'2026-05-25 04:13:55',NULL),(80,80,'14','VDV Tư Nhân Bình Dương','NAM','2000-01-15','PHUONG_BINH_DUONG','Tư nhân Bình Dương 01',NULL,NULL,'2026-05-25 04:13:55',NULL),(81,81,'15','VDV Tư Nhân Bình Dương','NAM','2000-01-16','PHUONG_BINH_DUONG','Tư nhân Bình Dương 01',NULL,NULL,'2026-05-25 04:13:55',NULL),(82,82,'16','VDV Tư Nhân Bình Dương','NAM','2000-01-17','PHUONG_BINH_DUONG','Tư nhân Bình Dương 01',NULL,NULL,'2026-05-25 04:13:55',NULL),(83,83,'17','VDV Tư Nhân Bình Dương','NAM','2000-01-18','PHUONG_BINH_DUONG','Tư nhân Bình Dương 01',NULL,NULL,'2026-05-25 04:13:55',NULL),(84,84,'18','VDV Tư Nhân Bình Dương','NAM','2000-01-19','PHUONG_BINH_DUONG','Tư nhân Bình Dương 01',NULL,NULL,'2026-05-25 04:13:55',NULL),(85,85,'19','VDV Tư Nhân Vũng Tàu','NAM','2000-01-20','PHUONG_VUNG_TAU','Tư nhân Vũng Tàu 01',NULL,NULL,'2026-05-25 04:13:55',NULL),(86,86,'20','VDV Tư Nhân Vũng Tàu','NAM','2000-01-21','PHUONG_VUNG_TAU','Tư nhân Vũng Tàu 01',NULL,NULL,'2026-05-25 04:13:55',NULL),(87,87,'21','VDV Tư Nhân Vũng Tàu','NAM','2000-01-22','PHUONG_VUNG_TAU','Tư nhân Vũng Tàu 01',NULL,NULL,'2026-05-25 04:13:55',NULL),(88,88,'22','VDV Tư Nhân Vũng Tàu','NAM','2000-01-23','PHUONG_VUNG_TAU','Tư nhân Vũng Tàu 01',NULL,NULL,'2026-05-25 04:13:55',NULL),(89,89,'23','VDV Tư Nhân Vũng Tàu','NAM','2000-01-24','PHUONG_VUNG_TAU','Tư nhân Vũng Tàu 01',NULL,NULL,'2026-05-25 04:13:55',NULL),(90,90,'24','VDV Tư Nhân Vũng Tàu','NAM','2000-01-25','PHUONG_VUNG_TAU','Tư nhân Vũng Tàu 01',NULL,NULL,'2026-05-25 04:13:55',NULL),(91,91,'01','Trọng tài Quốc Gia','NAM','1988-01-02','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:55',NULL),(92,92,'02','Trọng tài Quốc Gia','NAM','1988-01-03','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:55',NULL),(93,93,'03','Trọng tài Quốc Gia','NAM','1988-01-04','VN','Liên đoàn Bóng chuyền Việt Nam',NULL,NULL,'2026-05-25 04:13:55',NULL),(94,94,'01','Trọng tài Hà Nội','NAM','1988-01-02','HA_NOI','Sở VH-TT Hà Nội',NULL,NULL,'2026-05-25 04:13:55',NULL),(95,95,'02','Trọng tài Hà Nội','NAM','1988-01-03','HA_NOI','Sở VH-TT Hà Nội',NULL,NULL,'2026-05-25 04:13:55',NULL),(96,96,'03','Trọng tài Hà Nội','NAM','1988-01-04','HA_NOI','Sở VH-TT Hà Nội',NULL,NULL,'2026-05-25 04:13:55',NULL),(97,97,'01','Trọng tài Đà Nẵng','NAM','1988-01-02','DA_NANG','Sở VH-TT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:55',NULL),(98,98,'02','Trọng tài Đà Nẵng','NAM','1988-01-03','DA_NANG','Sở VH-TT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:55',NULL),(99,99,'03','Trọng tài Đà Nẵng','NAM','1988-01-04','DA_NANG','Sở VH-TT Đà Nẵng',NULL,NULL,'2026-05-25 04:13:55',NULL),(100,100,'01','Trọng tài Hồ Chí Minh','NAM','1988-01-02','TP_HCM','Sở VH-TT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:55',NULL),(101,101,'02','Trọng tài Hồ Chí Minh','NAM','1988-01-03','TP_HCM','Sở VH-TT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:55',NULL),(102,102,'03','Trọng tài Hồ Chí Minh','NAM','1988-01-04','TP_HCM','Sở VH-TT Thành phố Hồ Chí Minh',NULL,NULL,'2026-05-25 04:13:55',NULL),(103,103,'01','Trọng tài Phường Bình Dương','NAM','1988-01-02','PHUONG_BINH_DUONG','Trung tâm VH-TT Phường Bình Dương',NULL,NULL,'2026-05-25 04:13:55',NULL),(104,104,'02','Trọng tài Phường Bình Dương','NAM','1988-01-03','PHUONG_BINH_DUONG','Trung tâm VH-TT Phường Bình Dương',NULL,NULL,'2026-05-25 04:13:55',NULL),(105,105,'03','Trọng tài Phường Bình Dương','NAM','1988-01-04','PHUONG_BINH_DUONG','Trung tâm VH-TT Phường Bình Dương',NULL,NULL,'2026-05-25 04:13:55',NULL),(106,106,'04','Trọng tài Phường Bình Dương','NAM','1988-01-05','PHUONG_BINH_DUONG','Trung tâm VH-TT Phường Bình Dương',NULL,NULL,'2026-05-25 04:13:55',NULL),(107,107,'05','Trọng tài Phường Bình Dương','NAM','1988-01-06','PHUONG_BINH_DUONG','Trung tâm VH-TT Phường Bình Dương',NULL,NULL,'2026-05-25 04:13:55',NULL),(108,108,'06','Trọng tài Phường Bình Dương','NAM','1988-01-07','PHUONG_BINH_DUONG','Trung tâm VH-TT Phường Bình Dương',NULL,NULL,'2026-05-25 04:13:55',NULL),(109,109,'01','Trọng tài Phường Vũng Tàu','NAM','1988-01-02','PHUONG_VUNG_TAU','Trung tâm VH-TT Phường Vũng Tàu',NULL,NULL,'2026-05-25 04:13:55',NULL),(110,110,'02','Trọng tài Phường Vũng Tàu','NAM','1988-01-03','PHUONG_VUNG_TAU','Trung tâm VH-TT Phường Vũng Tàu',NULL,NULL,'2026-05-25 04:13:55',NULL),(111,111,'03','Trọng tài Phường Vũng Tàu','NAM','1988-01-04','PHUONG_VUNG_TAU','Trung tâm VH-TT Phường Vũng Tàu',NULL,NULL,'2026-05-25 04:13:55',NULL),(112,112,'04','Trọng tài Phường Vũng Tàu','NAM','1988-01-05','PHUONG_VUNG_TAU','Trung tâm VH-TT Phường Vũng Tàu',NULL,NULL,'2026-05-25 04:13:55',NULL),(113,113,'05','Trọng tài Phường Vũng Tàu','NAM','1988-01-06','PHUONG_VUNG_TAU','Trung tâm VH-TT Phường Vũng Tàu',NULL,NULL,'2026-05-25 04:13:55',NULL),(114,114,'06','Trọng tài Phường Vũng Tàu','NAM','1988-01-07','PHUONG_VUNG_TAU','Trung tâm VH-TT Phường Vũng Tàu',NULL,NULL,'2026-05-25 04:13:55',NULL),(115,115,'Test','Quan tri vien','NAM','1980-01-01','Viet Nam','Tai khoan quan tri vien test',NULL,NULL,'2026-05-29 15:55:49','2026-05-29 15:55:49');
/*!40000 ALTER TABLE `nguoidung` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nhatkyhethong`
--

DROP TABLE IF EXISTS `nhatkyhethong`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nhatkyhethong` (
  `idnhatky` int(11) NOT NULL AUTO_INCREMENT,
  `idtaikhoan` int(11) DEFAULT NULL,
  `hanhdong` varchar(300) NOT NULL,
  `bangtacdong` varchar(100) NOT NULL,
  `iddoituong` int(11) DEFAULT NULL,
  `thoigian` datetime NOT NULL DEFAULT current_timestamp(),
  `ipaddress` varchar(100) DEFAULT NULL,
  `ghichu` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`idnhatky`),
  KEY `fk_nkht_taikhoan` (`idtaikhoan`),
  CONSTRAINT `fk_nkht_taikhoan` FOREIGN KEY (`idtaikhoan`) REFERENCES `taikhoan` (`idtaikhoan`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nhatkyhethong`
--

LOCK TABLES `nhatkyhethong` WRITE;
/*!40000 ALTER TABLE `nhatkyhethong` DISABLE KEYS */;
INSERT INTO `nhatkyhethong` VALUES (1,67,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-25 15:54:43','::1','Tai khoan #67 role VAN_DONG_VIEN xem trang chu.'),(2,67,'Xem danh sach doi hinh','Doihinh',NULL,'2026-05-25 15:54:44','::1','VDV #49 xem 1 doi hinh.'),(3,67,'Xem chi tiet doi hinh','Doihinh',9,'2026-05-25 15:54:45','::1','VDV #49 xem doi hinh #9.'),(4,67,'Xem danh sach yeu cau sua id ca nhan VDV','Yeucaucapnhathoso',NULL,'2026-05-25 15:54:48','::1','VDV #49 xem 0 yeu cau sua id ca nhan.'),(5,67,'Xem lich thi dau ca nhan','Trandau',NULL,'2026-05-25 15:54:48','::1','VDV #49 xem 0 tran dau trong lich ca nhan.'),(6,67,'Xem danh sach don nghi phep thi dau VDV','Donnghivandongvien',NULL,'2026-05-25 15:54:48','::1','VDV #49 xem 0 don nghi phep thi dau.'),(7,67,'Xem danh sach yeu cau sua id ca nhan VDV','Yeucaucapnhathoso',NULL,'2026-05-25 15:54:50','::1','VDV #49 xem 0 yeu cau sua id ca nhan.'),(8,67,'Xem lich thi dau ca nhan','Trandau',NULL,'2026-05-25 15:54:51','::1','VDV #49 xem 0 tran dau trong lich ca nhan.'),(9,67,'Xem danh sach doi hinh','Doihinh',NULL,'2026-05-25 15:54:52','::1','VDV #49 xem 1 doi hinh.'),(10,67,'Xem chi tiet doi hinh','Doihinh',9,'2026-05-25 15:54:52','::1','VDV #49 xem doi hinh #9.'),(11,67,'Xem danh sach doi bong cua VDV','Doibong',NULL,'2026-05-25 15:54:52','::1','VDV #49 xem 1 doi bong.'),(12,67,'Xem thong tin doi bong','Doibong',9,'2026-05-25 15:54:53','::1','VDV #49 xem doi bong #9.'),(13,67,'Xem danh sach loi moi doi bong','Loimoidoibong',NULL,'2026-05-25 15:54:54','::1','VDV #49 xem 0 loi moi doi bong.'),(14,67,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-25 15:54:55','::1','Tai khoan #67 role VAN_DONG_VIEN xem trang chu.'),(15,5,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-25 16:00:09','::1','Tai khoan #5 role BAN_TO_CHUC xem trang chu.'),(16,5,'Tao giai dau','Giaidau',1,'2026-05-25 16:17:02','::1','Ban to chuc #5 tao giai dau \"Giai bóng chuyền chính thức Phường Bình Dương 2026\" theo cap #3, khu vuc #1037.'),(17,5,'Cong bo giai dau','Giaidau',1,'2026-05-25 16:17:05','::1','Ban to chuc #5 cong bo giai dau \"Giai bóng chuyền chính thức Phường Bình Dương 2026\".'),(18,5,'Mo dang ky giai dau','Giaidau',1,'2026-05-25 16:17:05','::1','Ban to chuc #5 mo dang ky giai dau \"Giai bóng chuyền chính thức Phường Bình Dương 2026\". Trang thai: CHUA_MO -> DANG_MO.'),(19,15,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-25 16:17:13','::1','Tai khoan #15 role HUAN_LUYEN_VIEN xem trang chu.'),(20,15,'Dang ky giai dau','Dangkygiaidau',1,'2026-05-25 16:17:18','::1','HLV #9 dang ky doi #9 \"doi_phuong_binhduong_01\" tham gia giai dau #1 \"Giai bóng chuyền chính thức Phường Bình Dương 2026\".'),(21,15,'Gui yeu cau xac nhan dang ky giai dau','Yeucauxacnhan',1,'2026-05-25 16:17:18','::1','HLV #9 dang ky doi #9 \"doi_phuong_binhduong_01\" tham gia giai dau #1 \"Giai bóng chuyền chính thức Phường Bình Dương 2026\".'),(22,17,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-25 16:17:28','::1','Tai khoan #17 role HUAN_LUYEN_VIEN xem trang chu.'),(23,17,'Dang ky giai dau','Dangkygiaidau',2,'2026-05-25 16:17:34','::1','HLV #11 dang ky doi #11 \"doi_tunhan_binhduong_01\" tham gia giai dau #1 \"Giai bóng chuyền chính thức Phường Bình Dương 2026\".'),(24,17,'Gui yeu cau xac nhan dang ky giai dau','Yeucauxacnhan',2,'2026-05-25 16:17:34','::1','HLV #11 dang ky doi #11 \"doi_tunhan_binhduong_01\" tham gia giai dau #1 \"Giai bóng chuyền chính thức Phường Bình Dương 2026\".'),(25,5,'Duyet dang ky doi bong','Dangkygiaidau',2,'2026-05-25 16:17:41','::1','Ban to chuc #5 duyet dang ky cua doi \"doi_tunhan_binhduong_01\" vao giai dau \"Giai bóng chuyền chính thức Phường Bình Dương 2026\".'),(26,5,'Duyet dang ky doi bong','Dangkygiaidau',1,'2026-05-25 16:17:42','::1','Ban to chuc #5 duyet dang ky cua doi \"doi_phuong_binhduong_01\" vao giai dau \"Giai bóng chuyền chính thức Phường Bình Dương 2026\".'),(27,5,'Dong dang ky giai dau','Giaidau',1,'2026-05-25 16:17:43','::1','Ban to chuc #5 dong dang ky giai dau \"Giai bóng chuyền chính thức Phường Bình Dương 2026\". Trang thai: DANG_MO -> DA_DONG.'),(28,5,'Cap nhat giai dau','Giaidau',1,'2026-05-25 16:18:12','::1','Ban to chuc #5 cap nhat giai dau \"Giai bóng chuyền chính thức Phường Bình Dương 2026\". Truong thay doi: tengiaidau, mota, idcapgiaidau, idkhuvucphamvi, idluat, thoigianbatdau, thoigianketthuc, quymo, hinhanh, tinhchat, gioitinh, ghichu_diadiem, dieule, thethuc, quytac, dieukien.'),(29,17,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-25 16:20:12','::1','Tai khoan #17 role HUAN_LUYEN_VIEN xem trang chu.'),(30,5,'Cap nhat giai dau','Giaidau',1,'2026-05-25 16:20:41','::1','Ban to chuc #5 cap nhat giai dau \"Giai bóng chuyền chính thức Phường Bình Dương 2026\". Truong thay doi: tengiaidau, mota, idcapgiaidau, idkhuvucphamvi, idluat, thoigianbatdau, thoigianketthuc, quymo, hinhanh, tinhchat, gioitinh, ghichu_diadiem, dieule, thethuc, quytac, dieukien.'),(31,5,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-25 16:28:04','::1','Tai khoan #5 role BAN_TO_CHUC xem trang chu.'),(32,5,'Cap nhat giai dau','Giaidau',1,'2026-05-25 16:31:41','::1','Ban to chuc #5 cap nhat giai dau \"Giai bóng chuyền chính thức Phường Bình Dương 2026\". Truong thay doi: tengiaidau, mota, idcapgiaidau, idkhuvucphamvi, idluat, thoigianbatdau, thoigianketthuc, quymo, hinhanh, tinhchat, gioitinh, ghichu_diadiem, dieule, thethuc, quytac, dieukien.'),(33,5,'Cap nhat giai dau','Giaidau',1,'2026-05-25 16:32:15','::1','Ban to chuc #5 cap nhat giai dau \"Giai bóng chuyền chính thức Phường Bình Dương 2026\". Truong thay doi: tengiaidau, mota, idcapgiaidau, idkhuvucphamvi, idluat, thoigianbatdau, thoigianketthuc, quymo, hinhanh, tinhchat, gioitinh, ghichu_diadiem, dieule, thethuc, quytac, dieukien.'),(34,5,'Huy giai dau da cong bo','Giaidau',1,'2026-05-25 16:32:52','::1','Ban to chuc #5 huy giai dau da cong bo \"Giai bóng chuyền chính thức Phường Bình Dương 2026\".'),(35,115,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-29 17:56:02','::1','Tai khoan #115 role ADMIN xem trang chu.'),(36,115,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-30 12:22:20','::1','Tai khoan #115 role ADMIN xem trang chu.'),(37,5,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-30 12:22:31','::1','Tai khoan #5 role BAN_TO_CHUC xem trang chu.'),(38,5,'Đổi mật khẩu','Taikhoan',5,'2026-05-30 12:23:01','::1','Tài khoản btc_phuong_binhduong_01 đổi mật khẩu đăng nhập.'),(39,5,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-30 12:23:12','::1','Tai khoan #5 role BAN_TO_CHUC xem trang chu.'),(40,5,'Đổi mật khẩu','Taikhoan',5,'2026-05-30 12:23:27','::1','Tài khoản btc_phuong_binhduong_01 đổi mật khẩu đăng nhập.'),(41,115,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-30 12:38:12','::1','Tai khoan #115 role ADMIN xem trang chu.'),(42,115,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-30 12:38:13','::1','Tai khoan #115 role ADMIN xem trang chu.'),(43,5,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-30 16:12:10','::1','Tai khoan #5 role BAN_TO_CHUC xem trang chu.'),(44,115,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-30 17:05:31','::1','Tai khoan #115 role ADMIN xem trang chu.'),(45,115,'Xem trang','Nguoidung',NULL,'2026-05-30 17:27:11','::1','Tai khoan #115 ADMIN thuc hien GET /admin/nguoi-dung (route /admin/nguoi-dung), HTTP 200.'),(46,115,'Xem danh sách dữ liệu','Nguoidung',NULL,'2026-05-30 17:27:11','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/users (route /api/admin/users), HTTP 200.'),(47,115,'Xem trang','Nguoidung',NULL,'2026-05-30 17:27:12','::1','Tai khoan #115 ADMIN thuc hien GET /admin/nguoi-dung (route /admin/nguoi-dung), HTTP 200.'),(48,115,'Xem danh sách dữ liệu','Nguoidung',NULL,'2026-05-30 17:27:12','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/users (route /api/admin/users), HTTP 200.'),(49,115,'Xem trang','Route',NULL,'2026-05-30 17:27:13','::1','Tai khoan #115 ADMIN thuc hien GET /admin/xac-nhan-thong-tin-btc (route /admin/xac-nhan-thong-tin-btc), HTTP 200.'),(50,115,'Tìm kiếm / lọc dữ liệu','Route',NULL,'2026-05-30 17:27:13','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/organizer-change-requests (route /api/admin/organizer-change-requests), HTTP 200. Query: {\"per_page\":\"100\"}'),(51,115,'Xem trang','Nhatkyhethong',NULL,'2026-05-30 17:27:13','::1','Tai khoan #115 ADMIN thuc hien GET /admin/logs (route /admin/logs), HTTP 200.'),(52,115,'Xem danh sách dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:27:13','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs/options (route /api/admin/system-logs/options), HTTP 200.'),(53,115,'Tìm kiếm / lọc dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:27:13','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs (route /api/admin/system-logs), HTTP 200. Query: {\"page\":\"1\",\"per_page\":\"20\"}'),(54,115,'Xem trang','Nguoidung',NULL,'2026-05-30 17:27:14','::1','Tai khoan #115 ADMIN thuc hien GET /admin/users (route /admin/users), HTTP 200.'),(55,115,'Xem danh sách dữ liệu','Route',NULL,'2026-05-30 17:27:14','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/roles (route /api/admin/roles), HTTP 200.'),(56,115,'Xem danh sách dữ liệu','Taikhoan',NULL,'2026-05-30 17:27:14','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/accounts (route /api/admin/accounts), HTTP 200.'),(57,115,'Xem trang','Nguoidung',NULL,'2026-05-30 17:27:14','::1','Tai khoan #115 ADMIN thuc hien GET /admin/nguoi-dung (route /admin/nguoi-dung), HTTP 200.'),(58,115,'Xem danh sách dữ liệu','Nguoidung',NULL,'2026-05-30 17:27:14','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/users (route /api/admin/users), HTTP 200.'),(59,115,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-30 17:27:15','::1','Tai khoan #115 role ADMIN xem trang chu.'),(60,115,'Xem trang','Dashboard',NULL,'2026-05-30 17:27:15','::1','Tai khoan #115 ADMIN thuc hien GET /dashboard (route /dashboard), HTTP 200.'),(61,115,'Xem trang','Nguoidung',NULL,'2026-05-30 17:27:15','::1','Tai khoan #115 ADMIN thuc hien GET /admin/users (route /admin/users), HTTP 200.'),(62,115,'Xem danh sách dữ liệu','Route',NULL,'2026-05-30 17:27:15','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/roles (route /api/admin/roles), HTTP 200.'),(63,115,'Xem danh sách dữ liệu','Taikhoan',NULL,'2026-05-30 17:27:15','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/accounts (route /api/admin/accounts), HTTP 200.'),(64,115,'Xem trang','Nguoidung',NULL,'2026-05-30 17:27:15','::1','Tai khoan #115 ADMIN thuc hien GET /admin/nguoi-dung (route /admin/nguoi-dung), HTTP 200.'),(65,115,'Xem danh sách dữ liệu','Nguoidung',NULL,'2026-05-30 17:27:15','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/users (route /api/admin/users), HTTP 200.'),(66,115,'Xem trang','Nhatkyhethong',NULL,'2026-05-30 17:27:15','::1','Tai khoan #115 ADMIN thuc hien GET /admin/logs (route /admin/logs), HTTP 200.'),(67,115,'Xem danh sách dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:27:16','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs/options (route /api/admin/system-logs/options), HTTP 200.'),(68,115,'Tìm kiếm / lọc dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:27:16','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs (route /api/admin/system-logs), HTTP 200. Query: {\"page\":\"1\",\"per_page\":\"20\"}'),(69,115,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-30 17:27:29','::1','Tai khoan #115 role ADMIN xem trang chu.'),(70,115,'Xem trang','Dashboard',NULL,'2026-05-30 17:27:29','::1','Tai khoan #115 ADMIN thuc hien GET /dashboard (route /dashboard), HTTP 200.'),(71,115,'Xem trang','Nhatkyhethong',NULL,'2026-05-30 17:27:30','::1','Tai khoan #115 ADMIN thuc hien GET /admin/logs (route /admin/logs), HTTP 200.'),(72,115,'Xem danh sách dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:27:30','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs/options (route /api/admin/system-logs/options), HTTP 200.'),(73,115,'Tìm kiếm / lọc dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:27:30','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs (route /api/admin/system-logs), HTTP 200. Query: {\"page\":\"1\",\"per_page\":\"20\"}'),(74,115,'Xem trang','Nguoidung',NULL,'2026-05-30 17:27:33','::1','Tai khoan #115 ADMIN thuc hien GET /admin/nguoi-dung (route /admin/nguoi-dung), HTTP 200.'),(75,115,'Xem danh sách dữ liệu','Nguoidung',NULL,'2026-05-30 17:27:33','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/users (route /api/admin/users), HTTP 200.'),(76,115,'Xem trang','Nhatkyhethong',NULL,'2026-05-30 17:27:35','::1','Tai khoan #115 ADMIN thuc hien GET /admin/logs (route /admin/logs), HTTP 200.'),(77,115,'Xem danh sách dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:27:35','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs/options (route /api/admin/system-logs/options), HTTP 200.'),(78,115,'Tìm kiếm / lọc dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:27:35','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs (route /api/admin/system-logs), HTTP 200. Query: {\"page\":\"1\",\"per_page\":\"20\"}'),(79,115,'Xem trang','Route',NULL,'2026-05-30 17:28:17','::1','Tai khoan #115 ADMIN thuc hien GET /admin/xac-nhan-thong-tin-btc (route /admin/xac-nhan-thong-tin-btc), HTTP 200.'),(80,115,'Tìm kiếm / lọc dữ liệu','Route',NULL,'2026-05-30 17:28:17','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/organizer-change-requests (route /api/admin/organizer-change-requests), HTTP 200. Query: {\"per_page\":\"100\"}'),(81,115,'Xem trang','Nhatkyhethong',NULL,'2026-05-30 17:28:19','::1','Tai khoan #115 ADMIN thuc hien GET /admin/logs (route /admin/logs), HTTP 200.'),(82,115,'Xem danh sách dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:28:19','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs/options (route /api/admin/system-logs/options), HTTP 200.'),(83,115,'Tìm kiếm / lọc dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:28:19','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs (route /api/admin/system-logs), HTTP 200. Query: {\"page\":\"1\",\"per_page\":\"20\"}'),(84,115,'Xem trang','Route',NULL,'2026-05-30 17:28:20','::1','Tai khoan #115 ADMIN thuc hien GET /admin/xac-nhan-thong-tin-btc (route /admin/xac-nhan-thong-tin-btc), HTTP 200.'),(85,115,'Tìm kiếm / lọc dữ liệu','Route',NULL,'2026-05-30 17:28:20','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/organizer-change-requests (route /api/admin/organizer-change-requests), HTTP 200. Query: {\"per_page\":\"100\"}'),(86,115,'Xem trang','Nhatkyhethong',NULL,'2026-05-30 17:28:24','::1','Tai khoan #115 ADMIN thuc hien GET /admin/logs (route /admin/logs), HTTP 200.'),(87,115,'Xem danh sách dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:28:24','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs/options (route /api/admin/system-logs/options), HTTP 200.'),(88,115,'Tìm kiếm / lọc dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:28:24','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs (route /api/admin/system-logs), HTTP 200. Query: {\"page\":\"1\",\"per_page\":\"20\"}'),(89,115,'Xem trang','Nguoidung',NULL,'2026-05-30 17:35:30','::1','Tai khoan #115 ADMIN thuc hien GET /admin/nguoi-dung (route /admin/nguoi-dung), HTTP 200.'),(90,115,'Xem danh sách dữ liệu','Nguoidung',NULL,'2026-05-30 17:35:30','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/users (route /api/admin/users), HTTP 200.'),(91,115,'Tìm kiếm / lọc dữ liệu','Nguoidung',NULL,'2026-05-30 17:35:35','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/users (route /api/admin/users), HTTP 200. Query: {\"role\":\"BAN_TO_CHUC\",\"status\":\"HOAT_DONG\"}'),(92,115,'Xem trang','Nhatkyhethong',NULL,'2026-05-30 17:35:36','::1','Tai khoan #115 ADMIN thuc hien GET /admin/logs (route /admin/logs), HTTP 200.'),(93,115,'Xem danh sách dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:35:36','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs/options (route /api/admin/system-logs/options), HTTP 200.'),(94,115,'Tìm kiếm / lọc dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:35:36','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs (route /api/admin/system-logs), HTTP 200. Query: {\"page\":\"1\",\"per_page\":\"20\"}'),(95,115,'Xem trang','Nhatkyhethong',NULL,'2026-05-30 17:36:35','::1','Tai khoan #115 ADMIN thuc hien GET /admin/logs (route /admin/logs), HTTP 200.'),(96,115,'Xem danh sách dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:36:35','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs/options (route /api/admin/system-logs/options), HTTP 200.'),(97,115,'Tìm kiếm / lọc dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:36:35','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs (route /api/admin/system-logs), HTTP 200. Query: {\"page\":\"1\",\"per_page\":\"20\"}'),(98,115,'Xem trang','Nhatkyhethong',NULL,'2026-05-30 17:36:36','::1','Tai khoan #115 ADMIN thuc hien GET /admin/logs (route /admin/logs), HTTP 200.'),(99,115,'Xem danh sách dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:36:36','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs/options (route /api/admin/system-logs/options), HTTP 200.'),(100,115,'Tìm kiếm / lọc dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:36:36','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs (route /api/admin/system-logs), HTTP 200. Query: {\"page\":\"1\",\"per_page\":\"20\"}'),(101,115,'Xem trang','Nhatkyhethong',NULL,'2026-05-30 17:36:37','::1','Tai khoan #115 ADMIN thuc hien GET /admin/logs (route /admin/logs), HTTP 200.'),(102,115,'Xem danh sách dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:36:37','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs/options (route /api/admin/system-logs/options), HTTP 200.'),(103,115,'Tìm kiếm / lọc dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:36:37','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs (route /api/admin/system-logs), HTTP 200. Query: {\"page\":\"1\",\"per_page\":\"20\"}'),(104,115,'Xem trang','Nhatkyhethong',NULL,'2026-05-30 17:36:38','::1','Tai khoan #115 ADMIN thuc hien GET /admin/logs (route /admin/logs), HTTP 200.'),(105,115,'Xem danh sách dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:36:38','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs/options (route /api/admin/system-logs/options), HTTP 200.'),(106,115,'Tìm kiếm / lọc dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:36:38','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs (route /api/admin/system-logs), HTTP 200. Query: {\"page\":\"1\",\"per_page\":\"20\"}'),(107,115,'Xem trang','Nguoidung',NULL,'2026-05-30 17:36:40','::1','Tai khoan #115 ADMIN thuc hien GET /admin/users (route /admin/users), HTTP 200.'),(108,115,'Xem danh sách dữ liệu','Route',NULL,'2026-05-30 17:36:40','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/roles (route /api/admin/roles), HTTP 200.'),(109,115,'Xem danh sách dữ liệu','Taikhoan',NULL,'2026-05-30 17:36:40','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/accounts (route /api/admin/accounts), HTTP 200.'),(110,115,'Xem trang','Nguoidung',NULL,'2026-05-30 17:36:40','::1','Tai khoan #115 ADMIN thuc hien GET /admin/nguoi-dung (route /admin/nguoi-dung), HTTP 200.'),(111,115,'Xem danh sách dữ liệu','Nguoidung',NULL,'2026-05-30 17:36:40','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/users (route /api/admin/users), HTTP 200.'),(112,115,'Xem trang','Nhatkyhethong',NULL,'2026-05-30 17:36:41','::1','Tai khoan #115 ADMIN thuc hien GET /admin/logs (route /admin/logs), HTTP 200.'),(113,115,'Xem danh sách dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:36:41','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs/options (route /api/admin/system-logs/options), HTTP 200.'),(114,115,'Tìm kiếm / lọc dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:36:41','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs (route /api/admin/system-logs), HTTP 200. Query: {\"page\":\"1\",\"per_page\":\"20\"}'),(115,115,'Xem trang','Route',NULL,'2026-05-30 17:36:59','::1','Tai khoan #115 ADMIN thuc hien GET /admin/xac-nhan-thong-tin-btc (route /admin/xac-nhan-thong-tin-btc), HTTP 200.'),(116,115,'Tìm kiếm / lọc dữ liệu','Route',NULL,'2026-05-30 17:36:59','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/organizer-change-requests (route /api/admin/organizer-change-requests), HTTP 200. Query: {\"per_page\":\"100\"}'),(117,115,'Xem trang','Nhatkyhethong',NULL,'2026-05-30 17:37:01','::1','Tai khoan #115 ADMIN thuc hien GET /admin/logs (route /admin/logs), HTTP 200.'),(118,115,'Xem danh sách dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:37:01','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs/options (route /api/admin/system-logs/options), HTTP 200.'),(119,115,'Tìm kiếm / lọc dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:37:01','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs (route /api/admin/system-logs), HTTP 200. Query: {\"page\":\"1\",\"per_page\":\"20\"}'),(120,115,'Xem trang','Nguoidung',NULL,'2026-05-30 17:37:01','::1','Tai khoan #115 ADMIN thuc hien GET /admin/nguoi-dung (route /admin/nguoi-dung), HTTP 200.'),(121,115,'Xem danh sách dữ liệu','Nguoidung',NULL,'2026-05-30 17:37:01','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/users (route /api/admin/users), HTTP 200.'),(122,115,'Xem chi tiết dữ liệu','Nguoidung',115,'2026-05-30 17:37:05','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/users/115 (route /api/admin/users/{id}), HTTP 200. Params: {\"id\":\"115\"}'),(123,115,'Xem chi tiết dữ liệu','Nguoidung',115,'2026-05-30 17:37:09','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/users/115 (route /api/admin/users/{id}), HTTP 200. Params: {\"id\":\"115\"}'),(124,115,'Xem trang','Nguoidung',NULL,'2026-05-30 17:37:13','::1','Tai khoan #115 ADMIN thuc hien GET /admin/users (route /admin/users), HTTP 200.'),(125,115,'Xem danh sách dữ liệu','Route',NULL,'2026-05-30 17:37:13','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/roles (route /api/admin/roles), HTTP 200.'),(126,115,'Xem danh sách dữ liệu','Taikhoan',NULL,'2026-05-30 17:37:13','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/accounts (route /api/admin/accounts), HTTP 200.'),(127,115,'Xem trang','Nguoidung',NULL,'2026-05-30 17:37:21','::1','Tai khoan #115 ADMIN thuc hien GET /admin/nguoi-dung (route /admin/nguoi-dung), HTTP 200.'),(128,115,'Xem danh sách dữ liệu','Nguoidung',NULL,'2026-05-30 17:37:21','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/users (route /api/admin/users), HTTP 200.'),(129,115,'Xem chi tiết dữ liệu','Nguoidung',40,'2026-05-30 17:39:43','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/users/40 (route /api/admin/users/{id}), HTTP 200. Params: {\"id\":\"40\"}'),(130,115,'Xem trang chu dashboard','Dashboard',NULL,'2026-05-30 17:39:48','::1','Tai khoan #115 role ADMIN xem trang chu.'),(131,115,'Xem trang','Dashboard',NULL,'2026-05-30 17:39:48','::1','Tai khoan #115 ADMIN thuc hien GET /dashboard (route /dashboard), HTTP 200.'),(132,115,'Xem trang','Nhatkyhethong',NULL,'2026-05-30 17:41:50','::1','Tai khoan #115 ADMIN thuc hien GET /admin/logs (route /admin/logs), HTTP 200.'),(133,115,'Xem danh sách dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:41:50','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs/options (route /api/admin/system-logs/options), HTTP 200.'),(134,115,'Tìm kiếm / lọc dữ liệu','Nhatkyhethong',NULL,'2026-05-30 17:41:50','::1','Tai khoan #115 ADMIN thuc hien GET /api/admin/system-logs (route /api/admin/system-logs), HTTP 200. Query: {\"page\":\"1\",\"per_page\":\"20\"}');
/*!40000 ALTER TABLE `nhatkyhethong` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nhatkytrangthai`
--

DROP TABLE IF EXISTS `nhatkytrangthai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nhatkytrangthai` (
  `idnhatkytrangthai` int(11) NOT NULL AUTO_INCREMENT,
  `loaidoituong` varchar(100) NOT NULL,
  `iddoituong` int(11) NOT NULL,
  `trangthaicu` varchar(100) DEFAULT NULL,
  `trangthaimoi` varchar(100) NOT NULL,
  `lydo` varchar(1000) DEFAULT NULL,
  `idnguoithuchien` int(11) DEFAULT NULL,
  `thoigian` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idnhatkytrangthai`),
  KEY `fk_nktt_taikhoan` (`idnguoithuchien`),
  CONSTRAINT `fk_nktt_taikhoan` FOREIGN KEY (`idnguoithuchien`) REFERENCES `taikhoan` (`idtaikhoan`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_nktt_loaidoituong` CHECK (`loaidoituong` in ('TAI_KHOAN','GIAI_DAU','DOI_BONG','SAN_DAU','TRAN_DAU','DANG_KY_GIAI','KHIEU_NAI','YEU_CAU_XAC_NHAN','VONG_DAU'))
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nhatkytrangthai`
--

LOCK TABLES `nhatkytrangthai` WRITE;
/*!40000 ALTER TABLE `nhatkytrangthai` DISABLE KEYS */;
INSERT INTO `nhatkytrangthai` VALUES (1,'GIAI_DAU',1,NULL,'NHAP','Tao giai dau o trang thai nhap',5,'2026-05-25 16:17:02'),(2,'GIAI_DAU',1,NULL,'DA_CONG_BO','Cong bo giai dau',5,'2026-05-25 16:17:05'),(3,'GIAI_DAU',1,'CHUA_MO','DANG_MO','Mo dang ky giai dau',5,'2026-05-25 16:17:05'),(4,'DANG_KY_GIAI',1,NULL,'CHO_DUYET','HLV dang ky giai dau',15,'2026-05-25 16:17:18'),(5,'YEU_CAU_XAC_NHAN',1,NULL,'CHO_DUYET','Gui yeu cau xac nhan dang ky giai dau',15,'2026-05-25 16:17:18'),(6,'DANG_KY_GIAI',2,NULL,'CHO_DUYET','HLV dang ky giai dau',17,'2026-05-25 16:17:34'),(7,'YEU_CAU_XAC_NHAN',2,NULL,'CHO_DUYET','Gui yeu cau xac nhan dang ky giai dau',17,'2026-05-25 16:17:34'),(8,'DANG_KY_GIAI',2,'CHO_DUYET','DA_DUYET','Duyet dang ky doi bong',5,'2026-05-25 16:17:41'),(9,'YEU_CAU_XAC_NHAN',2,'CHO_DUYET','DA_DUYET','Duyet dang ky doi bong',5,'2026-05-25 16:17:41'),(10,'DANG_KY_GIAI',1,'CHO_DUYET','DA_DUYET','Duyet dang ky doi bong',5,'2026-05-25 16:17:42'),(11,'YEU_CAU_XAC_NHAN',1,'CHO_DUYET','DA_DUYET','Duyet dang ky doi bong',5,'2026-05-25 16:17:42'),(12,'GIAI_DAU',1,'DANG_MO','DA_DONG','Dong dang ky giai dau',5,'2026-05-25 16:17:43'),(13,'GIAI_DAU',1,'DA_CONG_BO','DA_HUY','BTC hủy giải đấu đã công bố',5,'2026-05-25 16:32:52');
/*!40000 ALTER TABLE `nhatkytrangthai` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phancongtrongtai`
--

DROP TABLE IF EXISTS `phancongtrongtai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `phancongtrongtai` (
  `idphancong` int(11) NOT NULL AUTO_INCREMENT,
  `idtrandau` int(11) NOT NULL,
  `idtrongtai` int(11) NOT NULL,
  `vaitro` varchar(100) NOT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_XAC_NHAN',
  `ngayphancong` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idphancong`),
  UNIQUE KEY `uq_pctt` (`idtrandau`,`idtrongtai`),
  KEY `fk_pctt_trongtai` (`idtrongtai`),
  CONSTRAINT `fk_pctt_tran` FOREIGN KEY (`idtrandau`) REFERENCES `trandau` (`idtrandau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pctt_trongtai` FOREIGN KEY (`idtrongtai`) REFERENCES `trongtai` (`idtrongtai`) ON UPDATE CASCADE,
  CONSTRAINT `chk_pctt_vaitro` CHECK (`vaitro` in ('TRONG_TAI_CHINH','TRONG_TAI_PHU','GIAM_SAT')),
  CONSTRAINT `chk_pctt_trangthai` CHECK (`trangthai` in ('CHO_XAC_NHAN','DA_XAC_NHAN','TU_CHOI','DA_HUY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phancongtrongtai`
--

LOCK TABLES `phancongtrongtai` WRITE;
/*!40000 ALTER TABLE `phancongtrongtai` DISABLE KEYS */;
/*!40000 ALTER TABLE `phancongtrongtai` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_phancong_bi
BEFORE INSERT ON phancongtrongtai
FOR EACH ROW
BEGIN
    DECLARE v_ngay DATE;
    DECLARE v_count INT;
    SELECT DATE(thoigianbatdau) INTO v_ngay FROM trandau WHERE idtrandau = NEW.idtrandau;
    IF v_ngay IS NOT NULL THEN
        SELECT COUNT(*) INTO v_count
        FROM donnghitrongtai
        WHERE idtrongtai = NEW.idtrongtai
          AND trangthai = 'DA_DUYET'
          AND v_ngay BETWEEN tungay AND denngay;
        IF v_count > 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Trọng tài đang nghỉ phép trong ngày thi đấu.';
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `phiendangnhap`
--

DROP TABLE IF EXISTS `phiendangnhap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `phiendangnhap` (
  `idphien` int(11) NOT NULL AUTO_INCREMENT,
  `idtaikhoan` int(11) NOT NULL,
  `token` varchar(500) NOT NULL,
  `thoigiandangnhap` datetime NOT NULL DEFAULT current_timestamp(),
  `thoigiandangxuat` datetime DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'DANG_HOAT_DONG',
  PRIMARY KEY (`idphien`),
  UNIQUE KEY `token` (`token`),
  KEY `fk_phien_taikhoan` (`idtaikhoan`),
  CONSTRAINT `fk_phien_taikhoan` FOREIGN KEY (`idtaikhoan`) REFERENCES `taikhoan` (`idtaikhoan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_phien_trangthai` CHECK (`trangthai` in ('DANG_HOAT_DONG','DA_DANG_XUAT','HET_HAN')),
  CONSTRAINT `chk_phien_thoigian` CHECK (`thoigiandangxuat` is null or `thoigiandangxuat` >= `thoigiandangnhap`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phiendangnhap`
--

LOCK TABLES `phiendangnhap` WRITE;
/*!40000 ALTER TABLE `phiendangnhap` DISABLE KEYS */;
INSERT INTO `phiendangnhap` VALUES (1,67,'ea727878974d5885383a9ca0f36dbe3139d2a707f7f425f0cc23395875b4c7f1','2026-05-25 15:54:43','2026-05-25 15:54:56','DA_DANG_XUAT'),(2,5,'e3dc721f97c3153b085043ebdd2d0250fc2daf4881e7288d2507b8c51bc2188d','2026-05-25 16:00:09','2026-05-25 16:27:43','DA_DANG_XUAT'),(3,15,'7145572b86f119a2cb28f3710b56220b1d0fe4410268d4ae82577011eacf2cdc','2026-05-25 16:17:13','2026-05-25 16:17:20','DA_DANG_XUAT'),(4,17,'7adf921f15ac76c271cc6ec78d7fc635f2c2c7c4465f5944f9ccb65421bc3b2b','2026-05-25 16:17:28','2026-05-25 16:20:03','DA_DANG_XUAT'),(5,17,'82480605eddc5ee96e44bd899e4cdc8cdd26d68c76fa900a88e5734f98e03afd','2026-05-25 16:20:12',NULL,'DANG_HOAT_DONG'),(6,5,'e096a380aeea8d3f3c6e8dc17ec5aaebc92103c42c582069ef55579a5288b230','2026-05-25 16:28:04',NULL,'DANG_HOAT_DONG'),(7,115,'efd78299090416980c7348d61bf256227c02de2aa10f1c6470ed0b4cb0a55cf9','2026-05-29 17:56:02',NULL,'DANG_HOAT_DONG'),(8,115,'6655aa540814a1d2778dcff44628b0a82c58ca518b507e611a6919bfee5f58a9','2026-05-30 12:22:20','2026-05-30 12:22:28','DA_DANG_XUAT'),(9,5,'0163f94fabe5980f6a7bf2348f8ba8948da9a8bae166f0d4ba013946f2091cb6','2026-05-30 12:22:31','2026-05-30 12:23:03','DA_DANG_XUAT'),(10,5,'d7aa874363c82f47c28819c5c18f5498b24ccee3eeb0e8e7a3b561aca28ca053','2026-05-30 12:23:12','2026-05-30 12:23:30','DA_DANG_XUAT'),(11,115,'a955bdd4103b5198e4d60f5b9da54e475a4d7c2db5835887971b3ab6a6beecb2','2026-05-30 12:38:12',NULL,'DANG_HOAT_DONG'),(12,5,'f3e22c677d9636aae5a8bff7d3bc8de03d36517e34cbca3205aa5526a79d94fe','2026-05-30 16:12:10',NULL,'DANG_HOAT_DONG');
/*!40000 ALTER TABLE `phiendangnhap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phiensinhtran`
--

DROP TABLE IF EXISTS `phiensinhtran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `phiensinhtran` (
  `idphien` int(11) NOT NULL AUTO_INCREMENT,
  `idgiaidau` int(11) NOT NULL,
  `idvongdau` int(11) NOT NULL,
  `idbangdau` int(11) DEFAULT NULL,
  `kieu_sinh` varchar(50) NOT NULL,
  `pham_vi_sinh` varchar(50) NOT NULL DEFAULT 'VONG_DAU',
  `cach_xep_cap_dau` varchar(50) NOT NULL,
  `tong_tran_du_kien` int(11) DEFAULT NULL,
  `tong_tran_tao` int(11) NOT NULL DEFAULT 0,
  `preview_json` longtext DEFAULT NULL,
  `loi_sinh` varchar(1000) DEFAULT NULL,
  `checksum_cau_hinh` varchar(128) DEFAULT NULL,
  `ghichu` varchar(1000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'BAN_NHAP',
  `idnguoitao` int(11) DEFAULT NULL,
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngayxacnhan` datetime DEFAULT NULL,
  PRIMARY KEY (`idphien`),
  KEY `fk_pst_giaidau` (`idgiaidau`),
  KEY `fk_pst_vong` (`idvongdau`),
  KEY `fk_pst_taikhoan` (`idnguoitao`),
  KEY `idx_phiensinhtran_bang` (`idbangdau`),
  CONSTRAINT `fk_phiensinhtran_bangdau` FOREIGN KEY (`idbangdau`) REFERENCES `bangdau` (`idbangdau`) ON DELETE SET NULL,
  CONSTRAINT `fk_pst_giaidau` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pst_taikhoan` FOREIGN KEY (`idnguoitao`) REFERENCES `taikhoan` (`idtaikhoan`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pst_vong` FOREIGN KEY (`idvongdau`) REFERENCES `vongdau` (`idvongdau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_pst_kieu` CHECK (`kieu_sinh` in ('VONG_DIEM','VONG_LOAI','CHUNG_KET','TRANH_HANG_BA')),
  CONSTRAINT `chk_pst_cach` CHECK (`cach_xep_cap_dau` in ('RANDOM','SEEDED','POT_DRAW','MANUAL','HYBRID','KHONG_AP_DUNG')),
  CONSTRAINT `chk_phiensinhtran_trangthai` CHECK (`trangthai` in ('BAN_NHAP','NHAP','CHO_XAC_NHAN','DANG_SINH','DA_XAC_NHAN','DA_TAO','THAT_BAI','DA_HUY')),
  CONSTRAINT `chk_phiensinhtran_phamvi` CHECK (`pham_vi_sinh` in ('GIAI_DAU','VONG_DAU','BANG_DAU'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phiensinhtran`
--

LOCK TABLES `phiensinhtran` WRITE;
/*!40000 ALTER TABLE `phiensinhtran` DISABLE KEYS */;
/*!40000 ALTER TABLE `phiensinhtran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quantrivien`
--

DROP TABLE IF EXISTS `quantrivien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quantrivien` (
  `idquantrivien` int(11) NOT NULL AUTO_INCREMENT,
  `idnguoidung` int(11) NOT NULL,
  `machucvu` varchar(100) DEFAULT NULL,
  `ghichu` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`idquantrivien`),
  UNIQUE KEY `idnguoidung` (`idnguoidung`),
  CONSTRAINT `fk_qtv_nguoidung` FOREIGN KEY (`idnguoidung`) REFERENCES `nguoidung` (`idnguoidung`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quantrivien`
--

LOCK TABLES `quantrivien` WRITE;
/*!40000 ALTER TABLE `quantrivien` DISABLE KEYS */;
INSERT INTO `quantrivien` VALUES (1,115,'ADMIN_TEST','Tai khoan quan tri vien test');
/*!40000 ALTER TABLE `quantrivien` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quyencapbtc_capgiaidau`
--

DROP TABLE IF EXISTS `quyencapbtc_capgiaidau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quyencapbtc_capgiaidau` (
  `idquyen` int(11) NOT NULL AUTO_INCREMENT,
  `idcapbantochuc` int(11) NOT NULL,
  `idcapgiaidau` int(11) NOT NULL,
  `duoc_tao_giai` tinyint(1) NOT NULL DEFAULT 1,
  `duoc_quan_ly` tinyint(1) NOT NULL DEFAULT 1,
  `ghichu` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`idquyen`),
  UNIQUE KEY `uq_quyencapbtc_capgiaidau` (`idcapbantochuc`,`idcapgiaidau`),
  KEY `fk_qcapbtc_capgd` (`idcapgiaidau`),
  CONSTRAINT `fk_qcapbtc_capbtc` FOREIGN KEY (`idcapbantochuc`) REFERENCES `capbantochuc` (`idcapbantochuc`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_qcapbtc_capgd` FOREIGN KEY (`idcapgiaidau`) REFERENCES `capgiaidau` (`idcapgiaidau`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quyencapbtc_capgiaidau`
--

LOCK TABLES `quyencapbtc_capgiaidau` WRITE;
/*!40000 ALTER TABLE `quyencapbtc_capgiaidau` DISABLE KEYS */;
INSERT INTO `quyencapbtc_capgiaidau` VALUES (1,1,1,1,1,'BTC quốc gia quản lý giải quốc gia.'),(2,2,2,1,1,'BTC tỉnh/thành quản lý giải tỉnh/thành.'),(3,3,3,1,1,'BTC xã/phường quản lý giải xã/phường.');
/*!40000 ALTER TABLE `quyencapbtc_capgiaidau` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quytacchondoi`
--

DROP TABLE IF EXISTS `quytacchondoi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quytacchondoi` (
  `idquytac` int(11) NOT NULL AUTO_INCREMENT,
  `idgiaidau` int(11) NOT NULL,
  `chedochondoi` varchar(50) NOT NULL DEFAULT 'DANG_KY_THU_CONG',
  `capdoituongthamgia` varchar(50) NOT NULL,
  `yeu_cau_thanh_tich` varchar(50) NOT NULL DEFAULT 'KHONG_YEU_CAU',
  `idcapgiaidau_thanh_tich_nguon` int(11) DEFAULT NULL,
  `hang_toi_thieu_duoc_phep` int(11) DEFAULT NULL,
  `so_mua_giai_gan_nhat_duoc_tinh` int(11) DEFAULT NULL,
  `cho_phep_btc_duyet_ngoai_le` tinyint(1) NOT NULL DEFAULT 1,
  `soluongdoitoida` int(11) DEFAULT NULL,
  `mota` varchar(1000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'HOAT_DONG',
  PRIMARY KEY (`idquytac`),
  KEY `fk_qtcd_giaidau` (`idgiaidau`),
  KEY `idx_qtcd_capttnguon` (`idcapgiaidau_thanh_tich_nguon`),
  KEY `idx_qtcd_capdoi` (`capdoituongthamgia`),
  CONSTRAINT `fk_qtcd_capdoi_capchinhquyen` FOREIGN KEY (`capdoituongthamgia`) REFERENCES `capchinhquyen` (`macap`) ON UPDATE CASCADE,
  CONSTRAINT `fk_qtcd_capgiaidau_thanh_tich_nguon_v2` FOREIGN KEY (`idcapgiaidau_thanh_tich_nguon`) REFERENCES `capgiaidau` (`idcapgiaidau`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_qtcd_giaidau` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_qtcd_chedo` CHECK (`chedochondoi` in ('DANG_KY_THU_CONG','HE_THONG_GOI_Y','BTC_CHON_THU_CONG','KET_HOP')),
  CONSTRAINT `chk_qtcd_trangthai` CHECK (`trangthai` in ('HOAT_DONG','NGUNG_SU_DUNG'))
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quytacchondoi`
--

LOCK TABLES `quytacchondoi` WRITE;
/*!40000 ALTER TABLE `quytacchondoi` DISABLE KEYS */;
INSERT INTO `quytacchondoi` VALUES (1,1,'DANG_KY_THU_CONG','XA_PHUONG','KHONG_YEU_CAU',NULL,NULL,NULL,0,10,NULL,'NGUNG_SU_DUNG'),(2,1,'DANG_KY_THU_CONG','XA_PHUONG','KHONG_YEU_CAU',NULL,NULL,NULL,0,10,NULL,'NGUNG_SU_DUNG'),(3,1,'DANG_KY_THU_CONG','XA_PHUONG','KHONG_YEU_CAU',NULL,NULL,NULL,0,10,NULL,'NGUNG_SU_DUNG'),(4,1,'DANG_KY_THU_CONG','XA_PHUONG','KHONG_YEU_CAU',NULL,NULL,NULL,0,10,NULL,'NGUNG_SU_DUNG'),(5,1,'DANG_KY_THU_CONG','XA_PHUONG','KHONG_YEU_CAU',NULL,NULL,NULL,0,10,NULL,'HOAT_DONG');
/*!40000 ALTER TABLE `quytacchondoi` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_qtcd_tucach_bi_v2
BEFORE INSERT ON quytacchondoi
FOR EACH ROW
BEGIN
    DECLARE v_capdoi VARCHAR(50);
    DECLARE v_capnguon_ma VARCHAR(50);

    SELECT cg.capdoituongthamgia INTO v_capdoi
    FROM giaidau gd
    JOIN capgiaidau cg ON cg.idcapgiaidau = gd.idcapgiaidau
    WHERE gd.idgiaidau = NEW.idgiaidau;

    IF NEW.capdoituongthamgia <> v_capdoi THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Quy tac chon doi phai khop cap doi tuong tham gia cua cap giai.';
    END IF;

    IF NEW.yeu_cau_thanh_tich NOT IN ('KHONG_YEU_CAU','VO_DICH','A_QUAN','HANG_BA','TOP_N','THEO_XEP_HANG','BTC_CHON','DAC_CACH') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'yeu_cau_thanh_tich khong nam trong bo ma chuan.';
    END IF;

    IF NEW.yeu_cau_thanh_tich IN ('VO_DICH','A_QUAN','HANG_BA','TOP_N','THEO_XEP_HANG') THEN
        IF NEW.idcapgiaidau_thanh_tich_nguon IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Yeu cau thanh tich bat buoc co cap giai thanh tich nguon.';
        END IF;
        SELECT macapgiaidau INTO v_capnguon_ma FROM capgiaidau WHERE idcapgiaidau = NEW.idcapgiaidau_thanh_tich_nguon;
        IF v_capnguon_ma <> v_capdoi THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cap giai thanh tich nguon phai khop cap doi tuong tham gia.';
        END IF;
    ELSEIF NEW.idcapgiaidau_thanh_tich_nguon IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Khong yeu cau thanh tich thi khong duoc gan cap giai thanh tich nguon.';
    END IF;

    IF NEW.yeu_cau_thanh_tich = 'TOP_N' AND (NEW.hang_toi_thieu_duoc_phep IS NULL OR NEW.hang_toi_thieu_duoc_phep < 1) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'TOP_N bat buoc co hang_toi_thieu_duoc_phep >= 1.';
    END IF;

    IF NEW.hang_toi_thieu_duoc_phep IS NOT NULL AND NEW.hang_toi_thieu_duoc_phep < 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'hang_toi_thieu_duoc_phep phai >= 1.';
    END IF;

    IF NEW.so_mua_giai_gan_nhat_duoc_tinh IS NOT NULL AND NEW.so_mua_giai_gan_nhat_duoc_tinh < 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'so_mua_giai_gan_nhat_duoc_tinh phai >= 1.';
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
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_qtcd_tucach_bu_v2
BEFORE UPDATE ON quytacchondoi
FOR EACH ROW
BEGIN
    DECLARE v_capdoi VARCHAR(50);
    DECLARE v_capnguon_ma VARCHAR(50);

    SELECT cg.capdoituongthamgia INTO v_capdoi
    FROM giaidau gd
    JOIN capgiaidau cg ON cg.idcapgiaidau = gd.idcapgiaidau
    WHERE gd.idgiaidau = NEW.idgiaidau;

    IF NEW.capdoituongthamgia <> v_capdoi THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Quy tac chon doi phai khop cap doi tuong tham gia cua cap giai.';
    END IF;

    IF NEW.yeu_cau_thanh_tich NOT IN ('KHONG_YEU_CAU','VO_DICH','A_QUAN','HANG_BA','TOP_N','THEO_XEP_HANG','BTC_CHON','DAC_CACH') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'yeu_cau_thanh_tich khong nam trong bo ma chuan.';
    END IF;

    IF NEW.yeu_cau_thanh_tich IN ('VO_DICH','A_QUAN','HANG_BA','TOP_N','THEO_XEP_HANG') THEN
        IF NEW.idcapgiaidau_thanh_tich_nguon IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Yeu cau thanh tich bat buoc co cap giai thanh tich nguon.';
        END IF;
        SELECT macapgiaidau INTO v_capnguon_ma FROM capgiaidau WHERE idcapgiaidau = NEW.idcapgiaidau_thanh_tich_nguon;
        IF v_capnguon_ma <> v_capdoi THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cap giai thanh tich nguon phai khop cap doi tuong tham gia.';
        END IF;
    ELSEIF NEW.idcapgiaidau_thanh_tich_nguon IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Khong yeu cau thanh tich thi khong duoc gan cap giai thanh tich nguon.';
    END IF;

    IF NEW.yeu_cau_thanh_tich = 'TOP_N' AND (NEW.hang_toi_thieu_duoc_phep IS NULL OR NEW.hang_toi_thieu_duoc_phep < 1) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'TOP_N bat buoc co hang_toi_thieu_duoc_phep >= 1.';
    END IF;

    IF NEW.hang_toi_thieu_duoc_phep IS NOT NULL AND NEW.hang_toi_thieu_duoc_phep < 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'hang_toi_thieu_duoc_phep phai >= 1.';
    END IF;

    IF NEW.so_mua_giai_gan_nhat_duoc_tinh IS NOT NULL AND NEW.so_mua_giai_gan_nhat_duoc_tinh < 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'so_mua_giai_gan_nhat_duoc_tinh phai >= 1.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role` (
  `idrole` int(11) NOT NULL AUTO_INCREMENT,
  `namerole` varchar(100) NOT NULL,
  `mota` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`idrole`),
  UNIQUE KEY `namerole` (`namerole`),
  CONSTRAINT `chk_role_namerole` CHECK (`namerole` in ('ADMIN','BAN_TO_CHUC','TRONG_TAI','HUAN_LUYEN_VIEN','VAN_DONG_VIEN','BIEN_TAP'))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role`
--

LOCK TABLES `role` WRITE;
/*!40000 ALTER TABLE `role` DISABLE KEYS */;
INSERT INTO `role` VALUES (1,'ADMIN','Quản trị viên hệ thống'),(2,'BAN_TO_CHUC','Ban tổ chức giải đấu'),(3,'TRONG_TAI','Trọng tài'),(4,'HUAN_LUYEN_VIEN','Huấn luyện viên'),(5,'VAN_DONG_VIEN','Vận động viên'),(6,'BIEN_TAP','Biên tập viên nội dung');
/*!40000 ALTER TABLE `role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sandau`
--

DROP TABLE IF EXISTS `sandau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sandau` (
  `idsandau` int(11) NOT NULL AUTO_INCREMENT,
  `idvitrithidau` int(11) NOT NULL,
  `tensandau` varchar(300) NOT NULL,
  `loaisan` varchar(50) NOT NULL DEFAULT 'SAN_BONG_CHUYEN',
  `mat_san` varchar(100) DEFAULT NULL,
  `kichthuoc` varchar(100) DEFAULT NULL,
  `succhua` int(11) NOT NULL DEFAULT 0,
  `mota` varchar(1000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'HOAT_DONG',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL,
  PRIMARY KEY (`idsandau`),
  UNIQUE KEY `uq_sandau_vitri_ten` (`idvitrithidau`,`tensandau`),
  KEY `idx_sandau_vitri_trangthai` (`idvitrithidau`,`trangthai`),
  KEY `idx_sandau_loaisan_trangthai` (`loaisan`,`trangthai`),
  CONSTRAINT `fk_sandau_vitri` FOREIGN KEY (`idvitrithidau`) REFERENCES `vitrithidau` (`idvitrithidau`) ON UPDATE CASCADE,
  CONSTRAINT `chk_sandau_succhua` CHECK (`succhua` >= 0),
  CONSTRAINT `chk_sandau_trangthai` CHECK (`trangthai` in ('HOAT_DONG','DANG_BAO_TRI','NGUNG_SU_DUNG'))
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sandau`
--

LOCK TABLES `sandau` WRITE;
/*!40000 ALTER TABLE `sandau` DISABLE KEYS */;
INSERT INTO `sandau` VALUES (12,6,'Sân 1 - Cụm sân Trung tâm TDTT Phường Sài Gòn','SAN_CHINH','Sàn thể thao đa năng','18m x 9m',300,'Sân chính dữ liệu mẫu của Cụm sân Trung tâm TDTT Phường Sài Gòn.','HOAT_DONG','2026-05-22 17:07:47',NULL),(13,6,'Sân 2 - Cụm sân Trung tâm TDTT Phường Sài Gòn','SAN_PHU','Sàn thể thao đa năng','18m x 9m',300,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm TDTT Phường Sài Gòn.','HOAT_DONG','2026-05-22 17:07:47',NULL),(14,6,'Sân 3 - Cụm sân Trung tâm TDTT Phường Sài Gòn','SAN_PHU','Sàn thể thao đa năng','18m x 9m',300,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm TDTT Phường Sài Gòn.','HOAT_DONG','2026-05-22 17:07:47',NULL),(15,6,'Sân 4 - Cụm sân Trung tâm TDTT Phường Sài Gòn','SAN_PHU','Sàn thể thao đa năng','18m x 9m',300,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm TDTT Phường Sài Gòn.','HOAT_DONG','2026-05-22 17:07:47',NULL),(16,7,'Sân 1 - Cụm sân Trung tâm TDTT Phường Bến Thành','SAN_CHINH','Sàn thể thao đa năng','18m x 9m',300,'Sân chính dữ liệu mẫu của Cụm sân Trung tâm TDTT Phường Bến Thành.','HOAT_DONG','2026-05-22 17:07:47',NULL),(17,7,'Sân 2 - Cụm sân Trung tâm TDTT Phường Bến Thành','SAN_PHU','Sàn thể thao đa năng','18m x 9m',300,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm TDTT Phường Bến Thành.','HOAT_DONG','2026-05-22 17:07:47',NULL),(18,7,'Sân 3 - Cụm sân Trung tâm TDTT Phường Bến Thành','SAN_PHU','Sàn thể thao đa năng','18m x 9m',300,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm TDTT Phường Bến Thành.','HOAT_DONG','2026-05-22 17:07:47',NULL),(19,7,'Sân 4 - Cụm sân Trung tâm TDTT Phường Bến Thành','SAN_PHU','Sàn thể thao đa năng','18m x 9m',300,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm TDTT Phường Bến Thành.','HOAT_DONG','2026-05-22 17:07:47',NULL),(20,8,'Sân 1 - Cụm sân Trung tâm TDTT Phường Hoàn Kiếm','SAN_CHINH','Sàn thể thao đa năng','18m x 9m',300,'Sân chính dữ liệu mẫu của Cụm sân Trung tâm TDTT Phường Hoàn Kiếm.','HOAT_DONG','2026-05-22 17:07:47',NULL),(21,8,'Sân 2 - Cụm sân Trung tâm TDTT Phường Hoàn Kiếm','SAN_PHU','Sàn thể thao đa năng','18m x 9m',300,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm TDTT Phường Hoàn Kiếm.','HOAT_DONG','2026-05-22 17:07:47',NULL),(22,8,'Sân 3 - Cụm sân Trung tâm TDTT Phường Hoàn Kiếm','SAN_PHU','Sàn thể thao đa năng','18m x 9m',300,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm TDTT Phường Hoàn Kiếm.','HOAT_DONG','2026-05-22 17:07:47',NULL),(23,8,'Sân 4 - Cụm sân Trung tâm TDTT Phường Hoàn Kiếm','SAN_PHU','Sàn thể thao đa năng','18m x 9m',300,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm TDTT Phường Hoàn Kiếm.','HOAT_DONG','2026-05-22 17:07:47',NULL),(24,9,'Sân 1 - Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hồ Chí Minh','SAN_CHINH','Sàn thể thao đa năng','18m x 9m',1200,'Sân chính dữ liệu mẫu của Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hồ Chí Minh.','HOAT_DONG','2026-05-22 17:07:47',NULL),(25,9,'Sân 2 - Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hồ Chí Minh','SAN_PHU','Sàn thể thao đa năng','18m x 9m',1200,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hồ Chí Minh.','HOAT_DONG','2026-05-22 17:07:47',NULL),(26,9,'Sân 3 - Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hồ Chí Minh','SAN_PHU','Sàn thể thao đa năng','18m x 9m',1200,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hồ Chí Minh.','HOAT_DONG','2026-05-22 17:07:47',NULL),(27,9,'Sân 4 - Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hồ Chí Minh','SAN_PHU','Sàn thể thao đa năng','18m x 9m',1200,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hồ Chí Minh.','HOAT_DONG','2026-05-22 17:07:47',NULL),(28,9,'Sân 5 - Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hồ Chí Minh','SAN_PHU','Sàn thể thao đa năng','18m x 9m',1200,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hồ Chí Minh.','HOAT_DONG','2026-05-22 17:07:47',NULL),(29,9,'Sân 6 - Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hồ Chí Minh','SAN_PHU','Sàn thể thao đa năng','18m x 9m',1200,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hồ Chí Minh.','HOAT_DONG','2026-05-22 17:07:47',NULL),(30,10,'Sân 1 - Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hà Nội','SAN_CHINH','Sàn thể thao đa năng','18m x 9m',1200,'Sân chính dữ liệu mẫu của Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hà Nội.','HOAT_DONG','2026-05-22 17:07:47',NULL),(31,10,'Sân 2 - Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hà Nội','SAN_PHU','Sàn thể thao đa năng','18m x 9m',1200,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hà Nội.','HOAT_DONG','2026-05-22 17:07:47',NULL),(32,10,'Sân 3 - Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hà Nội','SAN_PHU','Sàn thể thao đa năng','18m x 9m',1200,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hà Nội.','HOAT_DONG','2026-05-22 17:07:47',NULL),(33,10,'Sân 4 - Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hà Nội','SAN_PHU','Sàn thể thao đa năng','18m x 9m',1200,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hà Nội.','HOAT_DONG','2026-05-22 17:07:47',NULL),(34,10,'Sân 5 - Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hà Nội','SAN_PHU','Sàn thể thao đa năng','18m x 9m',1200,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hà Nội.','HOAT_DONG','2026-05-22 17:07:47',NULL),(35,10,'Sân 6 - Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hà Nội','SAN_PHU','Sàn thể thao đa năng','18m x 9m',1200,'Sân phụ dữ liệu mẫu của Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hà Nội.','HOAT_DONG','2026-05-22 17:07:47',NULL),(36,11,'Sân số 1 Liên đoàn Bóng chuyền Việt Nam','SAN_BONG_CHUYEN','PVC','18m x 9m',5000,'Dữ liệu sân đấu test.','HOAT_DONG','2026-05-25 04:13:55',NULL),(37,11,'Sân số 2 Liên đoàn Bóng chuyền Việt Nam','SAN_BONG_CHUYEN','PVC','18m x 9m',5000,'Dữ liệu sân đấu test.','HOAT_DONG','2026-05-25 04:13:55',NULL),(38,12,'Sân số 1 Trung tâm TDTT Hà Nội','SAN_BONG_CHUYEN','PVC','18m x 9m',3000,'Dữ liệu sân đấu test.','HOAT_DONG','2026-05-25 04:13:55',NULL),(39,12,'Sân số 2 Trung tâm TDTT Hà Nội','SAN_BONG_CHUYEN','PVC','18m x 9m',3000,'Dữ liệu sân đấu test.','HOAT_DONG','2026-05-25 04:13:55',NULL),(40,13,'Sân số 1 Trung tâm TDTT Đà Nẵng','SAN_BONG_CHUYEN','PVC','18m x 9m',3000,'Dữ liệu sân đấu test.','HOAT_DONG','2026-05-25 04:13:55',NULL),(41,13,'Sân số 2 Trung tâm TDTT Đà Nẵng','SAN_BONG_CHUYEN','PVC','18m x 9m',3000,'Dữ liệu sân đấu test.','HOAT_DONG','2026-05-25 04:13:55',NULL),(42,14,'Sân số 1 Trung tâm TDTT Hồ Chí Minh','SAN_BONG_CHUYEN','PVC','18m x 9m',3000,'Dữ liệu sân đấu test.','HOAT_DONG','2026-05-25 04:13:55',NULL),(43,14,'Sân số 2 Trung tâm TDTT Hồ Chí Minh','SAN_BONG_CHUYEN','PVC','18m x 9m',3000,'Dữ liệu sân đấu test.','HOAT_DONG','2026-05-25 04:13:55',NULL),(44,6,'Sân số 1 Trung tâm TDTT Phường Sài Gòn','SAN_BONG_CHUYEN','PVC','18m x 9m',1000,'Dữ liệu sân đấu test.','HOAT_DONG','2026-05-25 04:13:55',NULL),(45,6,'Sân số 2 Trung tâm TDTT Phường Sài Gòn','SAN_BONG_CHUYEN','PVC','18m x 9m',1000,'Dữ liệu sân đấu test.','HOAT_DONG','2026-05-25 04:13:55',NULL),(46,15,'Sân số 1 Trung tâm TDTT Phường Bình Dương','SAN_BONG_CHUYEN','PVC','18m x 9m',1000,'Dữ liệu sân đấu test.','HOAT_DONG','2026-05-25 04:13:55',NULL),(47,15,'Sân số 2 Trung tâm TDTT Phường Bình Dương','SAN_BONG_CHUYEN','PVC','18m x 9m',1000,'Dữ liệu sân đấu test.','HOAT_DONG','2026-05-25 04:13:55',NULL),(48,16,'Sân số 1 Trung tâm TDTT Phường Vũng Tàu','SAN_BONG_CHUYEN','PVC','18m x 9m',1000,'Dữ liệu sân đấu test.','HOAT_DONG','2026-05-25 04:13:55',NULL),(49,16,'Sân số 2 Trung tâm TDTT Phường Vũng Tàu','SAN_BONG_CHUYEN','PVC','18m x 9m',1000,'Dữ liệu sân đấu test.','HOAT_DONG','2026-05-25 04:13:55',NULL);
/*!40000 ALTER TABLE `sandau` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_sandau_bi_strict
BEFORE INSERT ON sandau
FOR EACH ROW
BEGIN
    DECLARE v_trangthai_vitri VARCHAR(50);

    IF NEW.loaisan NOT IN ('SAN_BONG_CHUYEN','SAN_CHINH','SAN_PHU','SAN_KHOI_DONG','SAN_TAP_LUYEN','KHAC') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Loại sân không hợp lệ.';
    END IF;

    IF NEW.succhua < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sức chứa sân đấu không được âm.';
    END IF;

    SELECT trangthai INTO v_trangthai_vitri
    FROM vitrithidau
    WHERE idvitrithidau = NEW.idvitrithidau;

    IF NEW.trangthai = 'HOAT_DONG' AND v_trangthai_vitri <> 'HOAT_DONG' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Không thể kích hoạt sân thuộc vị trí thi đấu không hoạt động.';
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
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_sandau_bu_strict
BEFORE UPDATE ON sandau
FOR EACH ROW
BEGIN
    DECLARE v_trangthai_vitri VARCHAR(50);

    IF NEW.loaisan NOT IN ('SAN_BONG_CHUYEN','SAN_CHINH','SAN_PHU','SAN_KHOI_DONG','SAN_TAP_LUYEN','KHAC') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Loại sân không hợp lệ.';
    END IF;

    IF NEW.succhua < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sức chứa sân đấu không được âm.';
    END IF;

    SELECT trangthai INTO v_trangthai_vitri
    FROM vitrithidau
    WHERE idvitrithidau = NEW.idvitrithidau;

    IF NEW.trangthai = 'HOAT_DONG' AND v_trangthai_vitri <> 'HOAT_DONG' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Không thể kích hoạt sân thuộc vị trí thi đấu không hoạt động.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `suatthamdu`
--

DROP TABLE IF EXISTS `suatthamdu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suatthamdu` (
  `idsuat` int(11) NOT NULL AUTO_INCREMENT,
  `idgiaidau_nguon` int(11) DEFAULT NULL,
  `idgiaidau_dich` int(11) NOT NULL,
  `idcapgiaidau_nguon` int(11) DEFAULT NULL,
  `idcapgiaidau_dich` int(11) NOT NULL,
  `idkhuvucphamvi` int(11) DEFAULT NULL,
  `loaisuat` varchar(50) NOT NULL,
  `soluongsuat` int(11) NOT NULL DEFAULT 1,
  `hang_toi_thieu` int(11) DEFAULT NULL,
  `tieuchi_mota` varchar(1000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'MO',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL,
  PRIMARY KEY (`idsuat`),
  KEY `idx_suat_giai_nguon` (`idgiaidau_nguon`),
  KEY `idx_suat_giai_dich` (`idgiaidau_dich`),
  KEY `idx_suat_cap` (`idcapgiaidau_nguon`,`idcapgiaidau_dich`),
  KEY `idx_suat_khuvuc` (`idkhuvucphamvi`),
  KEY `fk_suat_cap_dich_v2` (`idcapgiaidau_dich`),
  CONSTRAINT `fk_suat_cap_dich_v2` FOREIGN KEY (`idcapgiaidau_dich`) REFERENCES `capgiaidau` (`idcapgiaidau`) ON UPDATE CASCADE,
  CONSTRAINT `fk_suat_cap_nguon_v2` FOREIGN KEY (`idcapgiaidau_nguon`) REFERENCES `capgiaidau` (`idcapgiaidau`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_suat_giai_dich_v2` FOREIGN KEY (`idgiaidau_dich`) REFERENCES `giaidau` (`idgiaidau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_suat_giai_nguon_v2` FOREIGN KEY (`idgiaidau_nguon`) REFERENCES `giaidau` (`idgiaidau`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_suat_khuvuc_v2` FOREIGN KEY (`idkhuvucphamvi`) REFERENCES `khuvuc` (`idkhuvuc`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_suat_loai_v2` CHECK (`loaisuat` in ('VO_DICH_CAP_DUOI','A_QUAN_CAP_DUOI','HANG_BA_CAP_DUOI','TOP_N_CAP_DUOI','XEP_HANG','BTC_CHON','DAC_CACH')),
  CONSTRAINT `chk_suat_soluong_v2` CHECK (`soluongsuat` >= 1),
  CONSTRAINT `chk_suat_hang_v2` CHECK (`hang_toi_thieu` is null or `hang_toi_thieu` >= 1),
  CONSTRAINT `chk_suat_trangthai_v2` CHECK (`trangthai` in ('MO','DA_SU_DUNG','HET_HAN','HUY')),
  CONSTRAINT `chk_suat_topn_v2` CHECK (`loaisuat` <> 'TOP_N_CAP_DUOI' or `hang_toi_thieu` is not null and `hang_toi_thieu` >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suatthamdu`
--

LOCK TABLES `suatthamdu` WRITE;
/*!40000 ALTER TABLE `suatthamdu` DISABLE KEYS */;
/*!40000 ALTER TABLE `suatthamdu` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_suatthamdu_bi_v2
BEFORE INSERT ON suatthamdu
FOR EACH ROW
BEGIN
    DECLARE v_cap_nguon INT;
    DECLARE v_cap_dich INT;

    IF NEW.idgiaidau_nguon IS NOT NULL THEN
        SELECT idcapgiaidau INTO v_cap_nguon FROM giaidau WHERE idgiaidau = NEW.idgiaidau_nguon;
        IF NEW.idcapgiaidau_nguon IS NOT NULL AND NEW.idcapgiaidau_nguon <> v_cap_nguon THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cap giai nguon cua suat tham du khong khop giai nguon.';
        END IF;
    END IF;

    SELECT idcapgiaidau INTO v_cap_dich FROM giaidau WHERE idgiaidau = NEW.idgiaidau_dich;
    IF NEW.idcapgiaidau_dich <> v_cap_dich THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cap giai dich cua suat tham du khong khop giai dich.';
    END IF;

    IF NEW.loaisuat IN ('VO_DICH_CAP_DUOI','A_QUAN_CAP_DUOI','HANG_BA_CAP_DUOI','TOP_N_CAP_DUOI') AND NEW.idcapgiaidau_nguon IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Suat thanh tich cap duoi phai co idcapgiaidau_nguon.';
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
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_suatthamdu_bu_v2
BEFORE UPDATE ON suatthamdu
FOR EACH ROW
BEGIN
    DECLARE v_cap_nguon INT;
    DECLARE v_cap_dich INT;

    IF NEW.idgiaidau_nguon IS NOT NULL THEN
        SELECT idcapgiaidau INTO v_cap_nguon FROM giaidau WHERE idgiaidau = NEW.idgiaidau_nguon;
        IF NEW.idcapgiaidau_nguon IS NOT NULL AND NEW.idcapgiaidau_nguon <> v_cap_nguon THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cap giai nguon cua suat tham du khong khop giai nguon.';
        END IF;
    END IF;

    SELECT idcapgiaidau INTO v_cap_dich FROM giaidau WHERE idgiaidau = NEW.idgiaidau_dich;
    IF NEW.idcapgiaidau_dich <> v_cap_dich THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cap giai dich cua suat tham du khong khop giai dich.';
    END IF;

    IF NEW.loaisuat IN ('VO_DICH_CAP_DUOI','A_QUAN_CAP_DUOI','HANG_BA_CAP_DUOI','TOP_N_CAP_DUOI') AND NEW.idcapgiaidau_nguon IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Suat thanh tich cap duoi phai co idcapgiaidau_nguon.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `sukientrandau`
--

DROP TABLE IF EXISTS `sukientrandau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sukientrandau` (
  `idsukien` int(11) NOT NULL AUTO_INCREMENT,
  `idtrandau` int(11) NOT NULL,
  `loaisukien` varchar(100) NOT NULL,
  `thoigian` datetime NOT NULL DEFAULT current_timestamp(),
  `noidung` varchar(1000) NOT NULL,
  `idnguoitao` int(11) DEFAULT NULL,
  PRIMARY KEY (`idsukien`),
  KEY `fk_sktd_tran` (`idtrandau`),
  KEY `fk_sktd_taikhoan` (`idnguoitao`),
  CONSTRAINT `fk_sktd_taikhoan` FOREIGN KEY (`idnguoitao`) REFERENCES `taikhoan` (`idtaikhoan`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sktd_tran` FOREIGN KEY (`idtrandau`) REFERENCES `trandau` (`idtrandau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_sktd_loai` CHECK (`loaisukien` in ('BAT_DAU','TAM_DUNG','TIEP_TUC','KET_THUC','SU_CO','GHI_NHAN_DIEM'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sukientrandau`
--

LOCK TABLES `sukientrandau` WRITE;
/*!40000 ALTER TABLE `sukientrandau` DISABLE KEYS */;
/*!40000 ALTER TABLE `sukientrandau` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `taikhoan`
--

DROP TABLE IF EXISTS `taikhoan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `taikhoan` (
  `idtaikhoan` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `sodienthoai` varchar(20) DEFAULT NULL,
  `idrole` int(11) NOT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHUA_KICH_HOAT',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL,
  PRIMARY KEY (`idtaikhoan`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `sodienthoai` (`sodienthoai`),
  KEY `fk_taikhoan_role` (`idrole`),
  CONSTRAINT `fk_taikhoan_role` FOREIGN KEY (`idrole`) REFERENCES `role` (`idrole`) ON UPDATE CASCADE,
  CONSTRAINT `chk_taikhoan_trangthai` CHECK (`trangthai` in ('HOAT_DONG','CHUA_KICH_HOAT','TAM_KHOA','DA_HUY','CHO_DUYET')),
  CONSTRAINT `chk_taikhoan_email` CHECK (`email` like '%@%')
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taikhoan`
--

LOCK TABLES `taikhoan` WRITE;
/*!40000 ALTER TABLE `taikhoan` DISABLE KEYS */;
INSERT INTO `taikhoan` VALUES (1,'btc_quocgia','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','btc_quocgia@vtms.test',NULL,2,'HOAT_DONG','2026-05-25 04:13:54',NULL),(2,'btc_hn','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','btc_hn@vtms.test',NULL,2,'HOAT_DONG','2026-05-25 04:13:54',NULL),(3,'btc_dn','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','btc_dn@vtms.test',NULL,2,'HOAT_DONG','2026-05-25 04:13:54',NULL),(4,'btc_hcm','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','btc_hcm@vtms.test',NULL,2,'HOAT_DONG','2026-05-25 04:13:54',NULL),(5,'btc_phuong_binhduong_01','$2y$10$B5XFF73eutetBVOX.ZSEyuJiTc6ZbGKx7c2hVcMgLZaQ1ocplpsHC','btc_phuong_binhduong_01@vtms.test',NULL,2,'HOAT_DONG','2026-05-25 04:13:54','2026-05-30 12:23:27'),(6,'btc_phuong_vungtau_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','btc_phuong_vungtau_01@vtms.test',NULL,2,'HOAT_DONG','2026-05-25 04:13:54',NULL),(7,'hlv_quocgia_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','hlv_quocgia_01@vtms.test',NULL,4,'HOAT_DONG','2026-05-25 04:13:54',NULL),(8,'hlv_quocgia_02','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','hlv_quocgia_02@vtms.test',NULL,4,'HOAT_DONG','2026-05-25 04:13:54',NULL),(9,'hlv_hn_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','hlv_hn_01@vtms.test',NULL,4,'HOAT_DONG','2026-05-25 04:13:54',NULL),(10,'hlv_hn_02','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','hlv_hn_02@vtms.test',NULL,4,'HOAT_DONG','2026-05-25 04:13:54',NULL),(11,'hlv_dn_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','hlv_dn_01@vtms.test',NULL,4,'HOAT_DONG','2026-05-25 04:13:54',NULL),(12,'hlv_dn_02','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','hlv_dn_02@vtms.test',NULL,4,'HOAT_DONG','2026-05-25 04:13:54',NULL),(13,'hlv_hcm_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','hlv_hcm_01@vtms.test',NULL,4,'HOAT_DONG','2026-05-25 04:13:54',NULL),(14,'hlv_hcm_02','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','hlv_hcm_02@vtms.test',NULL,4,'HOAT_DONG','2026-05-25 04:13:54',NULL),(15,'hlv_phuong_binhduong_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','hlv_phuong_binhduong_01@vtms.test',NULL,4,'HOAT_DONG','2026-05-25 04:13:54',NULL),(16,'hlv_phuong_vungtau_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','hlv_phuong_vungtau_01@vtms.test',NULL,4,'HOAT_DONG','2026-05-25 04:13:54',NULL),(17,'hlv_tunhan_binhduong_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','hlv_tunhan_binhduong_01@vtms.test',NULL,4,'HOAT_DONG','2026-05-25 04:13:54',NULL),(18,'hlv_tunhan_vungtau_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','hlv_tunhan_vungtau_01@vtms.test',NULL,4,'HOAT_DONG','2026-05-25 04:13:54',NULL),(19,'vdv_quocgia_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_quocgia_01@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:54',NULL),(20,'vdv_quocgia_02','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_quocgia_02@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:54',NULL),(21,'vdv_quocgia_03','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_quocgia_03@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:54',NULL),(22,'vdv_quocgia_04','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_quocgia_04@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:54',NULL),(23,'vdv_quocgia_05','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_quocgia_05@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:54',NULL),(24,'vdv_quocgia_06','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_quocgia_06@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:54',NULL),(25,'vdv_quocgia_07','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_quocgia_07@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:54',NULL),(26,'vdv_quocgia_08','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_quocgia_08@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:54',NULL),(27,'vdv_quocgia_09','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_quocgia_09@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:54',NULL),(28,'vdv_quocgia_10','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_quocgia_10@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:54',NULL),(29,'vdv_quocgia_11','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_quocgia_11@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:54',NULL),(30,'vdv_quocgia_12','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_quocgia_12@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:54',NULL),(31,'vdv_tinh_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_01@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(32,'vdv_tinh_02','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_02@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(33,'vdv_tinh_03','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_03@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(34,'vdv_tinh_04','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_04@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(35,'vdv_tinh_05','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_05@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(36,'vdv_tinh_06','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_06@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(37,'vdv_tinh_07','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_07@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(38,'vdv_tinh_08','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_08@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(39,'vdv_tinh_09','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_09@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(40,'vdv_tinh_10','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_10@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(41,'vdv_tinh_11','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_11@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(42,'vdv_tinh_12','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_12@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(43,'vdv_tinh_13','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_13@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(44,'vdv_tinh_14','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_14@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(45,'vdv_tinh_15','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_15@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(46,'vdv_tinh_16','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_16@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(47,'vdv_tinh_17','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_17@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(48,'vdv_tinh_18','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_18@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(49,'vdv_tinh_19','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_19@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(50,'vdv_tinh_20','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_20@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(51,'vdv_tinh_21','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_21@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(52,'vdv_tinh_22','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_22@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(53,'vdv_tinh_23','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_23@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(54,'vdv_tinh_24','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_24@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(55,'vdv_tinh_25','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_25@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(56,'vdv_tinh_26','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_26@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(57,'vdv_tinh_27','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_27@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(58,'vdv_tinh_28','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_28@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(59,'vdv_tinh_29','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_29@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(60,'vdv_tinh_30','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_30@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(61,'vdv_tinh_31','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_31@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(62,'vdv_tinh_32','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_32@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(63,'vdv_tinh_33','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_33@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(64,'vdv_tinh_34','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_34@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(65,'vdv_tinh_35','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_35@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(66,'vdv_tinh_36','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_tinh_36@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(67,'vdv_phuong_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_01@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(68,'vdv_phuong_02','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_02@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(69,'vdv_phuong_03','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_03@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(70,'vdv_phuong_04','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_04@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(71,'vdv_phuong_05','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_05@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(72,'vdv_phuong_06','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_06@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(73,'vdv_phuong_07','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_07@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(74,'vdv_phuong_08','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_08@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(75,'vdv_phuong_09','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_09@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(76,'vdv_phuong_10','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_10@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(77,'vdv_phuong_11','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_11@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(78,'vdv_phuong_12','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_12@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(79,'vdv_phuong_13','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_13@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(80,'vdv_phuong_14','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_14@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(81,'vdv_phuong_15','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_15@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(82,'vdv_phuong_16','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_16@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(83,'vdv_phuong_17','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_17@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(84,'vdv_phuong_18','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_18@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(85,'vdv_phuong_19','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_19@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(86,'vdv_phuong_20','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_20@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(87,'vdv_phuong_21','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_21@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(88,'vdv_phuong_22','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_22@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(89,'vdv_phuong_23','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_23@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(90,'vdv_phuong_24','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','vdv_phuong_24@vtms.test',NULL,5,'HOAT_DONG','2026-05-25 04:13:55',NULL),(91,'tt_quocgia_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_quocgia_01@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(92,'tt_quocgia_02','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_quocgia_02@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(93,'tt_quocgia_03','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_quocgia_03@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(94,'tt_hn_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_hn_01@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(95,'tt_hn_02','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_hn_02@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(96,'tt_hn_03','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_hn_03@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(97,'tt_dn_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_dn_01@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(98,'tt_dn_02','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_dn_02@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(99,'tt_dn_03','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_dn_03@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(100,'tt_hcm_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_hcm_01@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(101,'tt_hcm_02','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_hcm_02@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(102,'tt_hcm_03','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_hcm_03@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(103,'tt_phuong_binhduong_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_phuong_binhduong_01@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(104,'tt_phuong_binhduong_02','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_phuong_binhduong_02@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(105,'tt_phuong_binhduong_03','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_phuong_binhduong_03@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(106,'tt_phuong_binhduong_04','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_phuong_binhduong_04@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(107,'tt_phuong_binhduong_05','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_phuong_binhduong_05@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(108,'tt_phuong_binhduong_06','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_phuong_binhduong_06@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(109,'tt_phuong_vungtau_01','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_phuong_vungtau_01@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(110,'tt_phuong_vungtau_02','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_phuong_vungtau_02@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(111,'tt_phuong_vungtau_03','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_phuong_vungtau_03@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(112,'tt_phuong_vungtau_04','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_phuong_vungtau_04@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(113,'tt_phuong_vungtau_05','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_phuong_vungtau_05@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(114,'tt_phuong_vungtau_06','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','tt_phuong_vungtau_06@vtms.test',NULL,3,'HOAT_DONG','2026-05-25 04:13:55',NULL),(115,'admin_test','$2y$10$mbvDIA1sSHrb2fkk/35XcO4Vs9JAUinrmrDVTVZv/LwSOXRsZgu4S','admin_test@vtms.test',NULL,1,'HOAT_DONG','2026-05-29 15:55:49','2026-05-29 15:55:49');
/*!40000 ALTER TABLE `taikhoan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `thanhtichdoibong`
--

DROP TABLE IF EXISTS `thanhtichdoibong`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `thanhtichdoibong` (
  `idthanhtich` int(11) NOT NULL AUTO_INCREMENT,
  `iddoibong` int(11) NOT NULL,
  `idgiaidau` int(11) NOT NULL,
  `idvongdau` int(11) DEFAULT NULL,
  `idbangxephang` int(11) DEFAULT NULL,
  `idchitietbxh` int(11) DEFAULT NULL,
  `idcapgiaidau` int(11) NOT NULL,
  `idkhuvuc` int(11) NOT NULL,
  `mua_giai` int(11) NOT NULL,
  `hang_dat_duoc` int(11) NOT NULL,
  `danhhieu` varchar(50) NOT NULL,
  `ngay_cong_nhan` date NOT NULL,
  `nguon_ghi_nhan` varchar(50) NOT NULL DEFAULT 'BANG_XEP_HANG',
  `ghi_chu` varchar(1000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'HOP_LE',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL,
  PRIMARY KEY (`idthanhtich`),
  UNIQUE KEY `uq_tt_doi_giai_danhhieu` (`iddoibong`,`idgiaidau`,`danhhieu`),
  KEY `idx_tt_doi` (`iddoibong`),
  KEY `idx_tt_giai` (`idgiaidau`),
  KEY `idx_tt_cap_hang` (`idcapgiaidau`,`hang_dat_duoc`),
  KEY `idx_tt_mua` (`mua_giai`),
  KEY `idx_tt_khuvuc` (`idkhuvuc`),
  KEY `idx_tt_ctbxh` (`idchitietbxh`),
  KEY `fk_tt_vong_v2` (`idvongdau`),
  KEY `fk_tt_bxh_v2` (`idbangxephang`),
  CONSTRAINT `fk_tt_bxh_v2` FOREIGN KEY (`idbangxephang`) REFERENCES `bangxephang` (`idbangxephang`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tt_cap_v2` FOREIGN KEY (`idcapgiaidau`) REFERENCES `capgiaidau` (`idcapgiaidau`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tt_ctbxh_v2` FOREIGN KEY (`idchitietbxh`) REFERENCES `chitietbangxephang` (`idchitietbxh`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tt_doi_v2` FOREIGN KEY (`iddoibong`) REFERENCES `doibong` (`iddoibong`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tt_giai_v2` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tt_khuvuc_v2` FOREIGN KEY (`idkhuvuc`) REFERENCES `khuvuc` (`idkhuvuc`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tt_vong_v2` FOREIGN KEY (`idvongdau`) REFERENCES `vongdau` (`idvongdau`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_tt_mua_v2` CHECK (`mua_giai` between 2000 and 2100),
  CONSTRAINT `chk_tt_hang_v2` CHECK (`hang_dat_duoc` >= 1),
  CONSTRAINT `chk_tt_danhhieu_v2` CHECK (`danhhieu` in ('VO_DICH','A_QUAN','HANG_BA','TOP_4','TOP_8','THAM_DU','KHAC')),
  CONSTRAINT `chk_tt_nguon_v2` CHECK (`nguon_ghi_nhan` in ('BANG_XEP_HANG','BTC_NHAP_TAY','HE_THONG_TONG_HOP')),
  CONSTRAINT `chk_tt_trangthai_v2` CHECK (`trangthai` in ('HOP_LE','BI_HUY','TAM_TREO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thanhtichdoibong`
--

LOCK TABLES `thanhtichdoibong` WRITE;
/*!40000 ALTER TABLE `thanhtichdoibong` DISABLE KEYS */;
/*!40000 ALTER TABLE `thanhtichdoibong` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_thanhtichdoibong_bi_v2
BEFORE INSERT ON thanhtichdoibong
FOR EACH ROW
BEGIN
    DECLARE v_cap_giai INT;
    DECLARE v_khuvuc_giai INT;
    DECLARE v_vong_giai INT;
    DECLARE v_bxh_giai INT;
    DECLARE v_ct_doi INT;
    DECLARE v_ct_hang INT;
    DECLARE v_ct_giai INT;

    SELECT idcapgiaidau, idkhuvucphamvi INTO v_cap_giai, v_khuvuc_giai
    FROM giaidau WHERE idgiaidau = NEW.idgiaidau;

    IF NEW.idcapgiaidau <> v_cap_giai THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Thanh tich phai khop cap giai dau nguon.';
    END IF;
    IF NEW.idkhuvuc <> v_khuvuc_giai THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Thanh tich phai khop khu vuc/pham vi cua giai dau nguon.';
    END IF;

    IF NEW.idvongdau IS NOT NULL THEN
        SELECT idgiaidau INTO v_vong_giai FROM vongdau WHERE idvongdau = NEW.idvongdau;
        IF v_vong_giai <> NEW.idgiaidau THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Vong dau cua thanh tich khong thuoc giai dau nguon.';
        END IF;
    END IF;

    IF NEW.idbangxephang IS NOT NULL THEN
        SELECT idgiaidau INTO v_bxh_giai FROM bangxephang WHERE idbangxephang = NEW.idbangxephang;
        IF v_bxh_giai <> NEW.idgiaidau THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bang xep hang cua thanh tich khong thuoc giai dau nguon.';
        END IF;
    END IF;

    IF NEW.idchitietbxh IS NOT NULL THEN
        SELECT ct.iddoibong, ct.hang, bx.idgiaidau INTO v_ct_doi, v_ct_hang, v_ct_giai
        FROM chitietbangxephang ct
        JOIN bangxephang bx ON bx.idbangxephang = ct.idbangxephang
        WHERE ct.idchitietbxh = NEW.idchitietbxh;

        IF v_ct_doi <> NEW.iddoibong OR v_ct_giai <> NEW.idgiaidau OR v_ct_hang <> NEW.hang_dat_duoc THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chi tiet BXH khong khop doi/giai/hang cua thanh tich.';
        END IF;
    END IF;

    IF (NEW.hang_dat_duoc = 1 AND NEW.danhhieu <> 'VO_DICH')
       OR (NEW.hang_dat_duoc = 2 AND NEW.danhhieu <> 'A_QUAN')
       OR (NEW.hang_dat_duoc = 3 AND NEW.danhhieu <> 'HANG_BA')
       OR (NEW.danhhieu = 'TOP_4' AND NEW.hang_dat_duoc > 4)
       OR (NEW.danhhieu = 'TOP_8' AND NEW.hang_dat_duoc > 8) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Danh hieu khong khop voi thu hang dat duoc.';
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
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_thanhtichdoibong_bu_v2
BEFORE UPDATE ON thanhtichdoibong
FOR EACH ROW
BEGIN
    DECLARE v_cap_giai INT;
    DECLARE v_khuvuc_giai INT;
    DECLARE v_vong_giai INT;
    DECLARE v_bxh_giai INT;
    DECLARE v_ct_doi INT;
    DECLARE v_ct_hang INT;
    DECLARE v_ct_giai INT;

    SELECT idcapgiaidau, idkhuvucphamvi INTO v_cap_giai, v_khuvuc_giai
    FROM giaidau WHERE idgiaidau = NEW.idgiaidau;

    IF NEW.idcapgiaidau <> v_cap_giai THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Thanh tich phai khop cap giai dau nguon.';
    END IF;
    IF NEW.idkhuvuc <> v_khuvuc_giai THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Thanh tich phai khop khu vuc/pham vi cua giai dau nguon.';
    END IF;

    IF NEW.idvongdau IS NOT NULL THEN
        SELECT idgiaidau INTO v_vong_giai FROM vongdau WHERE idvongdau = NEW.idvongdau;
        IF v_vong_giai <> NEW.idgiaidau THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Vong dau cua thanh tich khong thuoc giai dau nguon.';
        END IF;
    END IF;

    IF NEW.idbangxephang IS NOT NULL THEN
        SELECT idgiaidau INTO v_bxh_giai FROM bangxephang WHERE idbangxephang = NEW.idbangxephang;
        IF v_bxh_giai <> NEW.idgiaidau THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bang xep hang cua thanh tich khong thuoc giai dau nguon.';
        END IF;
    END IF;

    IF NEW.idchitietbxh IS NOT NULL THEN
        SELECT ct.iddoibong, ct.hang, bx.idgiaidau INTO v_ct_doi, v_ct_hang, v_ct_giai
        FROM chitietbangxephang ct
        JOIN bangxephang bx ON bx.idbangxephang = ct.idbangxephang
        WHERE ct.idchitietbxh = NEW.idchitietbxh;

        IF v_ct_doi <> NEW.iddoibong OR v_ct_giai <> NEW.idgiaidau OR v_ct_hang <> NEW.hang_dat_duoc THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chi tiet BXH khong khop doi/giai/hang cua thanh tich.';
        END IF;
    END IF;

    IF (NEW.hang_dat_duoc = 1 AND NEW.danhhieu <> 'VO_DICH')
       OR (NEW.hang_dat_duoc = 2 AND NEW.danhhieu <> 'A_QUAN')
       OR (NEW.hang_dat_duoc = 3 AND NEW.danhhieu <> 'HANG_BA')
       OR (NEW.danhhieu = 'TOP_4' AND NEW.hang_dat_duoc > 4)
       OR (NEW.danhhieu = 'TOP_8' AND NEW.hang_dat_duoc > 8) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Danh hieu khong khop voi thu hang dat duoc.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `thanhviendoibong`
--

DROP TABLE IF EXISTS `thanhviendoibong`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `thanhviendoibong` (
  `idthanhvien` int(11) NOT NULL AUTO_INCREMENT,
  `iddoibong` int(11) NOT NULL,
  `idvandongvien` int(11) NOT NULL,
  `vaitro` varchar(100) NOT NULL DEFAULT 'THANH_VIEN',
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_XAC_NHAN',
  `ngaythamgia` date NOT NULL,
  `ngayroi` date DEFAULT NULL,
  PRIMARY KEY (`idthanhvien`),
  UNIQUE KEY `uq_tvdb_doi_vdv` (`iddoibong`,`idvandongvien`),
  KEY `fk_tvdb_vdv` (`idvandongvien`),
  CONSTRAINT `fk_tvdb_doibong` FOREIGN KEY (`iddoibong`) REFERENCES `doibong` (`iddoibong`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tvdb_vdv` FOREIGN KEY (`idvandongvien`) REFERENCES `vandongvien` (`idvandongvien`) ON UPDATE CASCADE,
  CONSTRAINT `chk_tvdb_vaitro` CHECK (`vaitro` in ('DOI_TRUONG','THANH_VIEN','DU_BI')),
  CONSTRAINT `chk_tvdb_trangthai` CHECK (`trangthai` in ('CHO_XAC_NHAN','DANG_THAM_GIA','DA_ROI_DOI','BI_LOAI')),
  CONSTRAINT `chk_tvdb_ngayroi` CHECK (`ngayroi` is null or `ngayroi` >= `ngaythamgia`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thanhviendoibong`
--

LOCK TABLES `thanhviendoibong` WRITE;
/*!40000 ALTER TABLE `thanhviendoibong` DISABLE KEYS */;
INSERT INTO `thanhviendoibong` VALUES (1,1,1,'DOI_TRUONG','DANG_THAM_GIA','2026-05-25',NULL),(2,1,2,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(3,1,3,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(4,1,4,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(5,1,5,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(6,1,6,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(7,2,7,'DOI_TRUONG','DANG_THAM_GIA','2026-05-25',NULL),(8,2,8,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(9,2,9,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(10,2,10,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(11,2,11,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(12,2,12,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(13,3,13,'DOI_TRUONG','DANG_THAM_GIA','2026-05-25',NULL),(14,3,14,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(15,3,15,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(16,3,16,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(17,3,17,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(18,3,18,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(19,4,19,'DOI_TRUONG','DANG_THAM_GIA','2026-05-25',NULL),(20,4,20,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(21,4,21,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(22,4,22,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(23,4,23,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(24,4,24,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(25,5,25,'DOI_TRUONG','DANG_THAM_GIA','2026-05-25',NULL),(26,5,26,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(27,5,27,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(28,5,28,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(29,5,29,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(30,5,30,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(31,6,31,'DOI_TRUONG','DANG_THAM_GIA','2026-05-25',NULL),(32,6,32,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(33,6,33,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(34,6,34,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(35,6,35,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(36,6,36,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(37,7,37,'DOI_TRUONG','DANG_THAM_GIA','2026-05-25',NULL),(38,7,38,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(39,7,39,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(40,7,40,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(41,7,41,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(42,7,42,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(43,8,43,'DOI_TRUONG','DANG_THAM_GIA','2026-05-25',NULL),(44,8,44,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(45,8,45,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(46,8,46,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(47,8,47,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(48,8,48,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(49,9,49,'DOI_TRUONG','DANG_THAM_GIA','2026-05-25',NULL),(50,9,50,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(51,9,51,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(52,9,52,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(53,9,53,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(54,9,54,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(55,10,55,'DOI_TRUONG','DANG_THAM_GIA','2026-05-25',NULL),(56,10,56,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(57,10,57,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(58,10,58,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(59,10,59,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(60,10,60,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(61,11,61,'DOI_TRUONG','DANG_THAM_GIA','2026-05-25',NULL),(62,11,62,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(63,11,63,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(64,11,64,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(65,11,65,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(66,11,66,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(67,12,67,'DOI_TRUONG','DANG_THAM_GIA','2026-05-25',NULL),(68,12,68,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(69,12,69,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(70,12,70,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(71,12,71,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL),(72,12,72,'THANH_VIEN','DANG_THAM_GIA','2026-05-25',NULL);
/*!40000 ALTER TABLE `thanhviendoibong` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `thethucgiaidau`
--

DROP TABLE IF EXISTS `thethucgiaidau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `thethucgiaidau` (
  `idthethuc` int(11) NOT NULL AUTO_INCREMENT,
  `idgiaidau` int(11) NOT NULL,
  `tenthethuc` varchar(300) NOT NULL,
  `tong_so_vong` int(11) NOT NULL DEFAULT 1,
  `co_vong_diem` tinyint(1) NOT NULL DEFAULT 0,
  `co_vong_loai` tinyint(1) NOT NULL DEFAULT 0,
  `co_tranh_hang_ba` tinyint(1) NOT NULL DEFAULT 0,
  `cach_xep_mac_dinh` varchar(50) NOT NULL DEFAULT 'HYBRID',
  `seed_source_mac_dinh` varchar(100) NOT NULL DEFAULT 'BTC_NHAP_TAY',
  `mota` varchar(2000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'DANG_THIET_LAP',
  PRIMARY KEY (`idthethuc`),
  UNIQUE KEY `idgiaidau` (`idgiaidau`),
  CONSTRAINT `fk_thethuc_giaidau` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_thethuc_vong` CHECK (`tong_so_vong` > 0),
  CONSTRAINT `chk_thethuc_cachxep` CHECK (`cach_xep_mac_dinh` in ('RANDOM','SEEDED','POT_DRAW','MANUAL','HYBRID')),
  CONSTRAINT `chk_thethuc_seed` CHECK (`seed_source_mac_dinh` in ('BANG_XEP_HANG_TRUOC','THU_HANG_VONG_TRUOC','DIEM_TICH_LUY','BTC_NHAP_TAY','KHONG_AP_DUNG')),
  CONSTRAINT `chk_thethuc_trangthai` CHECK (`trangthai` in ('DANG_THIET_LAP','DA_XAC_NHAN','DA_HUY'))
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thethucgiaidau`
--

LOCK TABLES `thethucgiaidau` WRITE;
/*!40000 ALTER TABLE `thethucgiaidau` DISABLE KEYS */;
INSERT INTO `thethucgiaidau` VALUES (5,1,'Vòng loại trực tiếp',1,0,1,1,'MANUAL','BTC_NHAP_TAY',NULL,'DANG_THIET_LAP');
/*!40000 ALTER TABLE `thethucgiaidau` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `thongbao`
--

DROP TABLE IF EXISTS `thongbao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `thongbao` (
  `idthongbao` int(11) NOT NULL AUTO_INCREMENT,
  `idnguoinhan` int(11) NOT NULL,
  `tieude` varchar(300) NOT NULL,
  `noidung` varchar(1000) NOT NULL,
  `loai` varchar(100) NOT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHUA_DOC',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaydoc` datetime DEFAULT NULL,
  PRIMARY KEY (`idthongbao`),
  KEY `fk_thongbao_taikhoan` (`idnguoinhan`),
  CONSTRAINT `fk_thongbao_taikhoan` FOREIGN KEY (`idnguoinhan`) REFERENCES `taikhoan` (`idtaikhoan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_thongbao_loai` CHECK (`loai` in ('HE_THONG','XAC_NHAN','LICH_THI_DAU','KET_QUA','LOI_MOI_DOI_BONG','KHIEU_NAI')),
  CONSTRAINT `chk_thongbao_trangthai` CHECK (`trangthai` in ('CHUA_DOC','DA_DOC','DA_XOA')),
  CONSTRAINT `chk_thongbao_ngaydoc` CHECK (`ngaydoc` is null or `ngaydoc` >= `ngaytao`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thongbao`
--

LOCK TABLES `thongbao` WRITE;
/*!40000 ALTER TABLE `thongbao` DISABLE KEYS */;
INSERT INTO `thongbao` VALUES (1,7,'Giải đấu mới: Giai bóng chuyền chính thức Phường Bình Dương 2026','Giải đấu Giai bóng chuyền chính thức Phường Bình Dương 2026 đã được công bố và mở đăng ký. Huấn luyện viên có đội đủ điều kiện có thể gửi hồ sơ tham gia.','HE_THONG','CHUA_DOC','2026-05-25 16:17:05',NULL),(2,8,'Giải đấu mới: Giai bóng chuyền chính thức Phường Bình Dương 2026','Giải đấu Giai bóng chuyền chính thức Phường Bình Dương 2026 đã được công bố và mở đăng ký. Huấn luyện viên có đội đủ điều kiện có thể gửi hồ sơ tham gia.','HE_THONG','CHUA_DOC','2026-05-25 16:17:05',NULL),(3,9,'Giải đấu mới: Giai bóng chuyền chính thức Phường Bình Dương 2026','Giải đấu Giai bóng chuyền chính thức Phường Bình Dương 2026 đã được công bố và mở đăng ký. Huấn luyện viên có đội đủ điều kiện có thể gửi hồ sơ tham gia.','HE_THONG','CHUA_DOC','2026-05-25 16:17:05',NULL),(4,10,'Giải đấu mới: Giai bóng chuyền chính thức Phường Bình Dương 2026','Giải đấu Giai bóng chuyền chính thức Phường Bình Dương 2026 đã được công bố và mở đăng ký. Huấn luyện viên có đội đủ điều kiện có thể gửi hồ sơ tham gia.','HE_THONG','CHUA_DOC','2026-05-25 16:17:05',NULL),(5,11,'Giải đấu mới: Giai bóng chuyền chính thức Phường Bình Dương 2026','Giải đấu Giai bóng chuyền chính thức Phường Bình Dương 2026 đã được công bố và mở đăng ký. Huấn luyện viên có đội đủ điều kiện có thể gửi hồ sơ tham gia.','HE_THONG','CHUA_DOC','2026-05-25 16:17:05',NULL),(6,12,'Giải đấu mới: Giai bóng chuyền chính thức Phường Bình Dương 2026','Giải đấu Giai bóng chuyền chính thức Phường Bình Dương 2026 đã được công bố và mở đăng ký. Huấn luyện viên có đội đủ điều kiện có thể gửi hồ sơ tham gia.','HE_THONG','CHUA_DOC','2026-05-25 16:17:05',NULL),(7,13,'Giải đấu mới: Giai bóng chuyền chính thức Phường Bình Dương 2026','Giải đấu Giai bóng chuyền chính thức Phường Bình Dương 2026 đã được công bố và mở đăng ký. Huấn luyện viên có đội đủ điều kiện có thể gửi hồ sơ tham gia.','HE_THONG','CHUA_DOC','2026-05-25 16:17:05',NULL),(8,14,'Giải đấu mới: Giai bóng chuyền chính thức Phường Bình Dương 2026','Giải đấu Giai bóng chuyền chính thức Phường Bình Dương 2026 đã được công bố và mở đăng ký. Huấn luyện viên có đội đủ điều kiện có thể gửi hồ sơ tham gia.','HE_THONG','CHUA_DOC','2026-05-25 16:17:05',NULL),(9,15,'Giải đấu mới: Giai bóng chuyền chính thức Phường Bình Dương 2026','Giải đấu Giai bóng chuyền chính thức Phường Bình Dương 2026 đã được công bố và mở đăng ký. Huấn luyện viên có đội đủ điều kiện có thể gửi hồ sơ tham gia.','HE_THONG','CHUA_DOC','2026-05-25 16:17:05',NULL),(10,16,'Giải đấu mới: Giai bóng chuyền chính thức Phường Bình Dương 2026','Giải đấu Giai bóng chuyền chính thức Phường Bình Dương 2026 đã được công bố và mở đăng ký. Huấn luyện viên có đội đủ điều kiện có thể gửi hồ sơ tham gia.','HE_THONG','CHUA_DOC','2026-05-25 16:17:05',NULL),(11,17,'Giải đấu mới: Giai bóng chuyền chính thức Phường Bình Dương 2026','Giải đấu Giai bóng chuyền chính thức Phường Bình Dương 2026 đã được công bố và mở đăng ký. Huấn luyện viên có đội đủ điều kiện có thể gửi hồ sơ tham gia.','HE_THONG','CHUA_DOC','2026-05-25 16:17:05',NULL),(12,18,'Giải đấu mới: Giai bóng chuyền chính thức Phường Bình Dương 2026','Giải đấu Giai bóng chuyền chính thức Phường Bình Dương 2026 đã được công bố và mở đăng ký. Huấn luyện viên có đội đủ điều kiện có thể gửi hồ sơ tham gia.','HE_THONG','CHUA_DOC','2026-05-25 16:17:05',NULL);
/*!40000 ALTER TABLE `thongbao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `thongkecanhan`
--

DROP TABLE IF EXISTS `thongkecanhan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `thongkecanhan` (
  `idthongkecanhan` int(11) NOT NULL AUTO_INCREMENT,
  `idvandongvien` int(11) NOT NULL,
  `idgiaidau` int(11) NOT NULL,
  `idtrandau` int(11) NOT NULL,
  `sodiem` int(11) NOT NULL DEFAULT 0,
  `solanphatbong` int(11) NOT NULL DEFAULT 0,
  `solanchanbong` int(11) NOT NULL DEFAULT 0,
  `solanghidiem` int(11) NOT NULL DEFAULT 0,
  `ghichu` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`idthongkecanhan`),
  UNIQUE KEY `uq_tkcn` (`idvandongvien`,`idgiaidau`,`idtrandau`),
  KEY `fk_tkcn_giaidau` (`idgiaidau`),
  KEY `fk_tkcn_tran` (`idtrandau`),
  CONSTRAINT `fk_tkcn_giaidau` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tkcn_tran` FOREIGN KEY (`idtrandau`) REFERENCES `trandau` (`idtrandau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tkcn_vdv` FOREIGN KEY (`idvandongvien`) REFERENCES `vandongvien` (`idvandongvien`) ON UPDATE CASCADE,
  CONSTRAINT `chk_tkcn_nonnegative` CHECK (`sodiem` >= 0 and `solanphatbong` >= 0 and `solanchanbong` >= 0 and `solanghidiem` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thongkecanhan`
--

LOCK TABLES `thongkecanhan` WRITE;
/*!40000 ALTER TABLE `thongkecanhan` DISABLE KEYS */;
/*!40000 ALTER TABLE `thongkecanhan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `thongkedoi`
--

DROP TABLE IF EXISTS `thongkedoi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `thongkedoi` (
  `idthongkedoi` int(11) NOT NULL AUTO_INCREMENT,
  `idgiaidau` int(11) NOT NULL,
  `idvongdau` int(11) DEFAULT NULL,
  `idbangdau` int(11) DEFAULT NULL,
  `iddoibong` int(11) NOT NULL,
  `sotran` int(11) NOT NULL DEFAULT 0,
  `sotranthang` int(11) NOT NULL DEFAULT 0,
  `sotranthua` int(11) NOT NULL DEFAULT 0,
  `sosetthang` int(11) NOT NULL DEFAULT 0,
  `sosetthua` int(11) NOT NULL DEFAULT 0,
  `diem` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`idthongkedoi`),
  UNIQUE KEY `uq_tkd_scope` (`idgiaidau`,`idvongdau`,`idbangdau`,`iddoibong`),
  KEY `fk_tkd_vong` (`idvongdau`),
  KEY `fk_tkd_bang` (`idbangdau`),
  KEY `fk_tkd_doi` (`iddoibong`),
  CONSTRAINT `fk_tkd_bang` FOREIGN KEY (`idbangdau`) REFERENCES `bangdau` (`idbangdau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tkd_doi` FOREIGN KEY (`iddoibong`) REFERENCES `doibong` (`iddoibong`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tkd_giaidau` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tkd_vong` FOREIGN KEY (`idvongdau`) REFERENCES `vongdau` (`idvongdau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_tkd_nonnegative` CHECK (`sotran` >= 0 and `sotranthang` >= 0 and `sotranthua` >= 0 and `sosetthang` >= 0 and `sosetthua` >= 0 and `diem` >= 0),
  CONSTRAINT `chk_tkd_tongtran` CHECK (`sotran` >= `sotranthang` + `sotranthua`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thongkedoi`
--

LOCK TABLES `thongkedoi` WRITE;
/*!40000 ALTER TABLE `thongkedoi` DISABLE KEYS */;
/*!40000 ALTER TABLE `thongkedoi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trandau`
--

DROP TABLE IF EXISTS `trandau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trandau` (
  `idtrandau` int(11) NOT NULL AUTO_INCREMENT,
  `idgiaidau` int(11) NOT NULL,
  `idvongdau` int(11) NOT NULL,
  `idbangdau` int(11) DEFAULT NULL,
  `idphien` int(11) DEFAULT NULL,
  `ma_tran` varchar(100) NOT NULL,
  `ten_tran` varchar(300) DEFAULT NULL,
  `loaitrandau` varchar(50) NOT NULL DEFAULT 'VONG_DIEM',
  `iddoibong1` int(11) DEFAULT NULL,
  `iddoibong2` int(11) DEFAULT NULL,
  `idvitrithidau` int(11) DEFAULT NULL,
  `idsandau` int(11) DEFAULT NULL,
  `thoigianbatdau` datetime DEFAULT NULL,
  `thoigianketthuc` datetime DEFAULT NULL,
  `thutu_tran` int(11) NOT NULL,
  `vong_so` int(11) DEFAULT NULL,
  `luot_dau` int(11) NOT NULL DEFAULT 1,
  `idtrandau_thang_tiep` int(11) DEFAULT NULL,
  `slot_thang_tiep` int(11) DEFAULT NULL,
  `idtrandau_thua_tiep` int(11) DEFAULT NULL,
  `slot_thua_tiep` int(11) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_DOI_DOI',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL,
  PRIMARY KEY (`idtrandau`),
  UNIQUE KEY `uq_trandau_ma` (`idgiaidau`,`ma_tran`),
  KEY `idx_trandau_vong` (`idvongdau`),
  KEY `idx_trandau_doi1` (`iddoibong1`),
  KEY `idx_trandau_doi2` (`iddoibong2`),
  KEY `fk_trandau_san` (`idsandau`),
  KEY `idx_trandau_phien` (`idphien`),
  KEY `idx_trandau_vitri` (`idvitrithidau`),
  KEY `idx_trandau_bang_trangthai` (`idbangdau`,`trangthai`,`thoigianbatdau`),
  KEY `idx_trandau_vong_loai` (`idvongdau`,`loaitrandau`,`vong_so`,`thutu_tran`),
  KEY `idx_trandau_tiep_thang` (`idtrandau_thang_tiep`),
  KEY `idx_trandau_tiep_thua` (`idtrandau_thua_tiep`),
  CONSTRAINT `fk_trandau_bang` FOREIGN KEY (`idbangdau`) REFERENCES `bangdau` (`idbangdau`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trandau_doi1` FOREIGN KEY (`iddoibong1`) REFERENCES `doibong` (`iddoibong`) ON UPDATE CASCADE,
  CONSTRAINT `fk_trandau_doi2` FOREIGN KEY (`iddoibong2`) REFERENCES `doibong` (`iddoibong`) ON UPDATE CASCADE,
  CONSTRAINT `fk_trandau_giaidau` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON UPDATE CASCADE,
  CONSTRAINT `fk_trandau_phiensinhtran` FOREIGN KEY (`idphien`) REFERENCES `phiensinhtran` (`idphien`) ON DELETE SET NULL,
  CONSTRAINT `fk_trandau_san` FOREIGN KEY (`idsandau`) REFERENCES `sandau` (`idsandau`) ON UPDATE CASCADE,
  CONSTRAINT `fk_trandau_thang_tiep` FOREIGN KEY (`idtrandau_thang_tiep`) REFERENCES `trandau` (`idtrandau`) ON DELETE SET NULL,
  CONSTRAINT `fk_trandau_thua_tiep` FOREIGN KEY (`idtrandau_thua_tiep`) REFERENCES `trandau` (`idtrandau`) ON DELETE SET NULL,
  CONSTRAINT `fk_trandau_vitrithidau` FOREIGN KEY (`idvitrithidau`) REFERENCES `vitrithidau` (`idvitrithidau`) ON DELETE SET NULL,
  CONSTRAINT `fk_trandau_vong` FOREIGN KEY (`idvongdau`) REFERENCES `vongdau` (`idvongdau`) ON UPDATE CASCADE,
  CONSTRAINT `chk_trandau_2doi` CHECK (`iddoibong1` is null or `iddoibong2` is null or `iddoibong1` <> `iddoibong2`),
  CONSTRAINT `chk_trandau_thoigian` CHECK (`thoigianketthuc` is null or `thoigianbatdau` is null or `thoigianketthuc` > `thoigianbatdau`),
  CONSTRAINT `chk_trandau_thutu` CHECK (`thutu_tran` > 0),
  CONSTRAINT `chk_trandau_loaitrandau` CHECK (`loaitrandau` in ('VONG_DIEM','LOAI_TRUC_TIEP','GIAO_HUU','TRANH_HANG_BA','CHUNG_KET')),
  CONSTRAINT `chk_trandau_luot_vong` CHECK (`luot_dau` >= 1 and (`vong_so` is null or `vong_so` >= 1)),
  CONSTRAINT `chk_trandau_slot_tiep` CHECK ((`slot_thang_tiep` is null or `slot_thang_tiep` in (1,2)) and (`slot_thua_tiep` is null or `slot_thua_tiep` in (1,2))),
  CONSTRAINT `chk_trandau_trangthai` CHECK (`trangthai` in ('CHUA_XAC_DINH_DOI','CHO_DOI_DOI','CHO_XEP_LICH','DA_SAN_SANG','DA_XEP_LICH','SAP_DIEN_RA','TRONG_TAI_TRE_GIAM_SAT','DANG_DIEN_RA','TAM_DUNG','DA_KET_THUC','DA_HUY','DA_HUY_KHONG_CO_GIAM_SAT'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trandau`
--

LOCK TABLES `trandau` WRITE;
/*!40000 ALTER TABLE `trandau` DISABLE KEYS */;
/*!40000 ALTER TABLE `trandau` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_trandau_bi
BEFORE INSERT ON trandau
FOR EACH ROW
BEGIN
    DECLARE v_giaidau INT;
    DECLARE v_cobang TINYINT DEFAULT 0;
    DECLARE v_bang_vong INT;
    DECLARE v_count INT DEFAULT 0;

    SELECT idgiaidau, COALESCE(co_bangdau, 0)
      INTO v_giaidau, v_cobang
      FROM vongdau
     WHERE idvongdau = NEW.idvongdau;

    IF v_giaidau <> NEW.idgiaidau THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tr?n ??u ph?i thu?c ??ng gi?i c?a v?ng ??u.';
    END IF;

    IF v_cobang = 0 AND NEW.idbangdau IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'V?ng kh?ng c? b?ng ??u th? tr?n ??u kh?ng ???c g?n b?ng ??u.';
    END IF;

    IF NEW.idbangdau IS NOT NULL THEN
        SELECT idvongdau
          INTO v_bang_vong
          FROM bangdau
         WHERE idbangdau = NEW.idbangdau;

        IF v_bang_vong <> NEW.idvongdau THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'B?ng ??u c?a tr?n ph?i thu?c ??ng v?ng ??u.';
        END IF;
    END IF;

    IF NEW.iddoibong1 IS NOT NULL THEN
        SELECT COUNT(*)
          INTO v_count
          FROM doitrongvongdau
         WHERE idvongdau = NEW.idvongdau
           AND iddoibong = NEW.iddoibong1;

        IF v_count = 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '??i 1 kh?ng thu?c v?ng ??u.';
        END IF;
    END IF;

    IF NEW.iddoibong2 IS NOT NULL THEN
        SELECT COUNT(*)
          INTO v_count
          FROM doitrongvongdau
         WHERE idvongdau = NEW.idvongdau
           AND iddoibong = NEW.iddoibong2;

        IF v_count = 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '??i 2 kh?ng thu?c v?ng ??u.';
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `trandauslot`
--

DROP TABLE IF EXISTS `trandauslot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trandauslot` (
  `idslot` int(11) NOT NULL AUTO_INCREMENT,
  `idtrandau` int(11) NOT NULL,
  `slot_so` int(11) NOT NULL,
  `slot_label` varchar(100) DEFAULT NULL,
  `source_type` varchar(50) NOT NULL DEFAULT 'TEAM',
  `iddoibong` int(11) DEFAULT NULL,
  `source_match_id` int(11) DEFAULT NULL,
  `source_result` varchar(20) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `ngaycapnhat` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `source_seed_no` int(11) DEFAULT NULL,
  `ghichu` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`idslot`),
  UNIQUE KEY `uq_slot_tran` (`idtrandau`,`slot_so`),
  KEY `fk_slot_doi` (`iddoibong`),
  KEY `fk_slot_source_match` (`source_match_id`),
  KEY `idx_trandauslot_source` (`source_type`,`source_match_id`,`source_result`),
  CONSTRAINT `fk_slot_doi` FOREIGN KEY (`iddoibong`) REFERENCES `doibong` (`iddoibong`) ON UPDATE CASCADE,
  CONSTRAINT `fk_slot_source_match` FOREIGN KEY (`source_match_id`) REFERENCES `trandau` (`idtrandau`) ON UPDATE CASCADE,
  CONSTRAINT `fk_slot_tran` FOREIGN KEY (`idtrandau`) REFERENCES `trandau` (`idtrandau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_slot_so` CHECK (`slot_so` in (1,2)),
  CONSTRAINT `chk_slot_source_type` CHECK (`source_type` in ('TEAM','WINNER','LOSER','SEED','BYE')),
  CONSTRAINT `chk_slot_source_result` CHECK (`source_result` is null or `source_result` in ('WINNER','LOSER')),
  CONSTRAINT `chk_slot_seed` CHECK (`source_seed_no` is null or `source_seed_no` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trandauslot`
--

LOCK TABLES `trandauslot` WRITE;
/*!40000 ALTER TABLE `trandauslot` DISABLE KEYS */;
/*!40000 ALTER TABLE `trandauslot` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_trandauslot_bi
BEFORE INSERT ON trandauslot
FOR EACH ROW
BEGIN
    DECLARE v_giaidau INT;
    DECLARE v_source_giaidau INT;
    DECLARE v_count INT;
    IF NEW.source_type = 'TEAM' AND NEW.iddoibong IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Slot TEAM phải có đội cụ thể.';
    END IF;
    IF NEW.source_type IN ('WINNER','LOSER') THEN
        IF NEW.source_match_id IS NULL OR NEW.source_result IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Slot WINNER/LOSER phải có source_match_id và source_result.';
        END IF;
        SELECT idgiaidau INTO v_giaidau FROM trandau WHERE idtrandau = NEW.idtrandau;
        SELECT idgiaidau INTO v_source_giaidau FROM trandau WHERE idtrandau = NEW.source_match_id;
        IF v_giaidau <> v_source_giaidau THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Trận nguồn của slot phải cùng giải đấu.';
        END IF;
    END IF;
    IF NEW.source_type = 'SEED' AND NEW.source_seed_no IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Slot SEED phải có source_seed_no.';
    END IF;
    IF NEW.iddoibong IS NOT NULL THEN
        SELECT COUNT(*) INTO v_count
        FROM trandau t JOIN doitrongvongdau dv ON dv.idvongdau = t.idvongdau AND dv.iddoibong = NEW.iddoibong
        WHERE t.idtrandau = NEW.idtrandau;
        IF v_count = 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Đội trong slot phải thuộc vòng đấu của trận.';
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `trongtai`
--

DROP TABLE IF EXISTS `trongtai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trongtai` (
  `idtrongtai` int(11) NOT NULL AUTO_INCREMENT,
  `idnguoidung` int(11) NOT NULL,
  `capbac` varchar(100) DEFAULT NULL,
  `kinhnghiem` int(11) NOT NULL DEFAULT 0,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_DUYET',
  PRIMARY KEY (`idtrongtai`),
  UNIQUE KEY `idnguoidung` (`idnguoidung`),
  CONSTRAINT `fk_trongtai_nguoidung` FOREIGN KEY (`idnguoidung`) REFERENCES `nguoidung` (`idnguoidung`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_trongtai_kinhnghiem` CHECK (`kinhnghiem` >= 0),
  CONSTRAINT `chk_trongtai_trangthai` CHECK (`trangthai` in ('HOAT_DONG','CHO_DUYET','DANG_NGHI','NGUNG_HOAT_DONG'))
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trongtai`
--

LOCK TABLES `trongtai` WRITE;
/*!40000 ALTER TABLE `trongtai` DISABLE KEYS */;
INSERT INTO `trongtai` VALUES (1,91,'QUOC_GIA',10,'HOAT_DONG'),(2,92,'QUOC_GIA',10,'HOAT_DONG'),(3,93,'QUOC_GIA',10,'HOAT_DONG'),(4,94,'TINH_THANH',7,'HOAT_DONG'),(5,95,'TINH_THANH',7,'HOAT_DONG'),(6,96,'TINH_THANH',7,'HOAT_DONG'),(7,97,'TINH_THANH',7,'HOAT_DONG'),(8,98,'TINH_THANH',7,'HOAT_DONG'),(9,99,'TINH_THANH',7,'HOAT_DONG'),(10,100,'TINH_THANH',7,'HOAT_DONG'),(11,101,'TINH_THANH',7,'HOAT_DONG'),(12,102,'TINH_THANH',7,'HOAT_DONG'),(13,103,'XA_PHUONG',3,'HOAT_DONG'),(14,104,'XA_PHUONG',3,'HOAT_DONG'),(15,105,'XA_PHUONG',3,'HOAT_DONG'),(16,106,'XA_PHUONG',3,'HOAT_DONG'),(17,107,'XA_PHUONG',3,'HOAT_DONG'),(18,108,'XA_PHUONG',3,'HOAT_DONG'),(19,109,'XA_PHUONG',3,'HOAT_DONG'),(20,110,'XA_PHUONG',3,'HOAT_DONG'),(21,111,'XA_PHUONG',3,'HOAT_DONG'),(22,112,'XA_PHUONG',3,'HOAT_DONG'),(23,113,'XA_PHUONG',3,'HOAT_DONG'),(24,114,'XA_PHUONG',3,'HOAT_DONG');
/*!40000 ALTER TABLE `trongtai` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trongtaitrandau`
--

DROP TABLE IF EXISTS `trongtaitrandau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trongtaitrandau` (
  `idtrongtaitrandau` int(11) NOT NULL AUTO_INCREMENT,
  `idtrandau` int(11) NOT NULL,
  `idtrongtai` int(11) NOT NULL,
  `vaitro` varchar(100) NOT NULL,
  `xacnhanthamgia` tinyint(1) NOT NULL DEFAULT 0,
  `thoigianxacnhan` datetime DEFAULT NULL,
  PRIMARY KEY (`idtrongtaitrandau`),
  UNIQUE KEY `uq_tttd` (`idtrandau`,`idtrongtai`),
  KEY `fk_tttd_trongtai` (`idtrongtai`),
  CONSTRAINT `fk_tttd_tran` FOREIGN KEY (`idtrandau`) REFERENCES `trandau` (`idtrandau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tttd_trongtai` FOREIGN KEY (`idtrongtai`) REFERENCES `trongtai` (`idtrongtai`) ON UPDATE CASCADE,
  CONSTRAINT `chk_tttd_vaitro` CHECK (`vaitro` in ('TRONG_TAI_CHINH','TRONG_TAI_PHU','GIAM_SAT')),
  CONSTRAINT `chk_tttd_xacnhan` CHECK (`xacnhanthamgia` = 0 and `thoigianxacnhan` is null or `xacnhanthamgia` = 1 and `thoigianxacnhan` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trongtaitrandau`
--

LOCK TABLES `trongtaitrandau` WRITE;
/*!40000 ALTER TABLE `trongtaitrandau` DISABLE KEYS */;
/*!40000 ALTER TABLE `trongtaitrandau` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `v_dieukien_giai_thanhtich`
--

DROP TABLE IF EXISTS `v_dieukien_giai_thanhtich`;
/*!50001 DROP VIEW IF EXISTS `v_dieukien_giai_thanhtich`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_dieukien_giai_thanhtich` AS SELECT 
 1 AS `iddieukienthamgia`,
 1 AS `idgiaidau`,
 1 AS `thanh_tich_duoc_phep`,
 1 AS `hang_tot_nhat`,
 1 AS `hang_toi_da_duoc_phep`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `vandongvien`
--

DROP TABLE IF EXISTS `vandongvien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vandongvien` (
  `idvandongvien` int(11) NOT NULL AUTO_INCREMENT,
  `idnguoidung` int(11) NOT NULL,
  `mavandongvien` varchar(100) NOT NULL,
  `chieucao` decimal(5,2) DEFAULT NULL,
  `cannang` decimal(5,2) DEFAULT NULL,
  `vitri` varchar(100) NOT NULL,
  `trangthaidaugiai` varchar(50) NOT NULL DEFAULT 'CHO_XAC_NHAN',
  PRIMARY KEY (`idvandongvien`),
  UNIQUE KEY `idnguoidung` (`idnguoidung`),
  UNIQUE KEY `mavandongvien` (`mavandongvien`),
  CONSTRAINT `fk_vdv_nguoidung` FOREIGN KEY (`idnguoidung`) REFERENCES `nguoidung` (`idnguoidung`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_vdv_chieucao` CHECK (`chieucao` is null or `chieucao` > 0),
  CONSTRAINT `chk_vdv_cannang` CHECK (`cannang` is null or `cannang` > 0),
  CONSTRAINT `chk_vdv_vitri` CHECK (`vitri` in ('CHU_CONG','PHU_CONG','CHUYEN_HAI','DOI_CHUYEN','LIBERO','DOI_TRU')),
  CONSTRAINT `chk_vdv_trangthai` CHECK (`trangthaidaugiai` in ('DU_DIEU_KIEN','CHO_XAC_NHAN','BI_HUY_TU_CACH','DANG_NGHI_PHEP'))
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vandongvien`
--

LOCK TABLES `vandongvien` WRITE;
/*!40000 ALTER TABLE `vandongvien` DISABLE KEYS */;
INSERT INTO `vandongvien` VALUES (1,19,'VDV_QUOCGIA_01',180.00,72.00,'CHU_CONG','DU_DIEU_KIEN'),(2,20,'VDV_QUOCGIA_02',181.00,73.00,'PHU_CONG','DU_DIEU_KIEN'),(3,21,'VDV_QUOCGIA_03',182.00,74.00,'CHUYEN_HAI','DU_DIEU_KIEN'),(4,22,'VDV_QUOCGIA_04',183.00,75.00,'DOI_CHUYEN','DU_DIEU_KIEN'),(5,23,'VDV_QUOCGIA_05',184.00,76.00,'LIBERO','DU_DIEU_KIEN'),(6,24,'VDV_QUOCGIA_06',185.00,77.00,'DOI_TRU','DU_DIEU_KIEN'),(7,25,'VDV_QUOCGIA_07',180.00,72.00,'CHU_CONG','DU_DIEU_KIEN'),(8,26,'VDV_QUOCGIA_08',181.00,73.00,'PHU_CONG','DU_DIEU_KIEN'),(9,27,'VDV_QUOCGIA_09',182.00,74.00,'CHUYEN_HAI','DU_DIEU_KIEN'),(10,28,'VDV_QUOCGIA_10',183.00,75.00,'DOI_CHUYEN','DU_DIEU_KIEN'),(11,29,'VDV_QUOCGIA_11',184.00,76.00,'LIBERO','DU_DIEU_KIEN'),(12,30,'VDV_QUOCGIA_12',185.00,77.00,'DOI_TRU','DU_DIEU_KIEN'),(13,31,'VDV_TINH_01',180.00,72.00,'CHU_CONG','DU_DIEU_KIEN'),(14,32,'VDV_TINH_02',181.00,73.00,'PHU_CONG','DU_DIEU_KIEN'),(15,33,'VDV_TINH_03',182.00,74.00,'CHUYEN_HAI','DU_DIEU_KIEN'),(16,34,'VDV_TINH_04',183.00,75.00,'DOI_CHUYEN','DU_DIEU_KIEN'),(17,35,'VDV_TINH_05',184.00,76.00,'LIBERO','DU_DIEU_KIEN'),(18,36,'VDV_TINH_06',185.00,77.00,'DOI_TRU','DU_DIEU_KIEN'),(19,37,'VDV_TINH_07',180.00,72.00,'CHU_CONG','DU_DIEU_KIEN'),(20,38,'VDV_TINH_08',181.00,73.00,'PHU_CONG','DU_DIEU_KIEN'),(21,39,'VDV_TINH_09',182.00,74.00,'CHUYEN_HAI','DU_DIEU_KIEN'),(22,40,'VDV_TINH_10',183.00,75.00,'DOI_CHUYEN','DU_DIEU_KIEN'),(23,41,'VDV_TINH_11',184.00,76.00,'LIBERO','DU_DIEU_KIEN'),(24,42,'VDV_TINH_12',185.00,77.00,'DOI_TRU','DU_DIEU_KIEN'),(25,43,'VDV_TINH_13',180.00,72.00,'CHU_CONG','DU_DIEU_KIEN'),(26,44,'VDV_TINH_14',181.00,73.00,'PHU_CONG','DU_DIEU_KIEN'),(27,45,'VDV_TINH_15',182.00,74.00,'CHUYEN_HAI','DU_DIEU_KIEN'),(28,46,'VDV_TINH_16',183.00,75.00,'DOI_CHUYEN','DU_DIEU_KIEN'),(29,47,'VDV_TINH_17',184.00,76.00,'LIBERO','DU_DIEU_KIEN'),(30,48,'VDV_TINH_18',185.00,77.00,'DOI_TRU','DU_DIEU_KIEN'),(31,49,'VDV_TINH_19',180.00,72.00,'CHU_CONG','DU_DIEU_KIEN'),(32,50,'VDV_TINH_20',181.00,73.00,'PHU_CONG','DU_DIEU_KIEN'),(33,51,'VDV_TINH_21',182.00,74.00,'CHUYEN_HAI','DU_DIEU_KIEN'),(34,52,'VDV_TINH_22',183.00,75.00,'DOI_CHUYEN','DU_DIEU_KIEN'),(35,53,'VDV_TINH_23',184.00,76.00,'LIBERO','DU_DIEU_KIEN'),(36,54,'VDV_TINH_24',185.00,77.00,'DOI_TRU','DU_DIEU_KIEN'),(37,55,'VDV_TINH_25',180.00,72.00,'CHU_CONG','DU_DIEU_KIEN'),(38,56,'VDV_TINH_26',181.00,73.00,'PHU_CONG','DU_DIEU_KIEN'),(39,57,'VDV_TINH_27',182.00,74.00,'CHUYEN_HAI','DU_DIEU_KIEN'),(40,58,'VDV_TINH_28',183.00,75.00,'DOI_CHUYEN','DU_DIEU_KIEN'),(41,59,'VDV_TINH_29',184.00,76.00,'LIBERO','DU_DIEU_KIEN'),(42,60,'VDV_TINH_30',185.00,77.00,'DOI_TRU','DU_DIEU_KIEN'),(43,61,'VDV_TINH_31',180.00,72.00,'CHU_CONG','DU_DIEU_KIEN'),(44,62,'VDV_TINH_32',181.00,73.00,'PHU_CONG','DU_DIEU_KIEN'),(45,63,'VDV_TINH_33',182.00,74.00,'CHUYEN_HAI','DU_DIEU_KIEN'),(46,64,'VDV_TINH_34',183.00,75.00,'DOI_CHUYEN','DU_DIEU_KIEN'),(47,65,'VDV_TINH_35',184.00,76.00,'LIBERO','DU_DIEU_KIEN'),(48,66,'VDV_TINH_36',185.00,77.00,'DOI_TRU','DU_DIEU_KIEN'),(49,67,'VDV_PHUONG_01',180.00,72.00,'CHU_CONG','DU_DIEU_KIEN'),(50,68,'VDV_PHUONG_02',181.00,73.00,'PHU_CONG','DU_DIEU_KIEN'),(51,69,'VDV_PHUONG_03',182.00,74.00,'CHUYEN_HAI','DU_DIEU_KIEN'),(52,70,'VDV_PHUONG_04',183.00,75.00,'DOI_CHUYEN','DU_DIEU_KIEN'),(53,71,'VDV_PHUONG_05',184.00,76.00,'LIBERO','DU_DIEU_KIEN'),(54,72,'VDV_PHUONG_06',185.00,77.00,'DOI_TRU','DU_DIEU_KIEN'),(55,73,'VDV_PHUONG_07',180.00,72.00,'CHU_CONG','DU_DIEU_KIEN'),(56,74,'VDV_PHUONG_08',181.00,73.00,'PHU_CONG','DU_DIEU_KIEN'),(57,75,'VDV_PHUONG_09',182.00,74.00,'CHUYEN_HAI','DU_DIEU_KIEN'),(58,76,'VDV_PHUONG_10',183.00,75.00,'DOI_CHUYEN','DU_DIEU_KIEN'),(59,77,'VDV_PHUONG_11',184.00,76.00,'LIBERO','DU_DIEU_KIEN'),(60,78,'VDV_PHUONG_12',185.00,77.00,'DOI_TRU','DU_DIEU_KIEN'),(61,79,'VDV_PHUONG_13',180.00,72.00,'CHU_CONG','DU_DIEU_KIEN'),(62,80,'VDV_PHUONG_14',181.00,73.00,'PHU_CONG','DU_DIEU_KIEN'),(63,81,'VDV_PHUONG_15',182.00,74.00,'CHUYEN_HAI','DU_DIEU_KIEN'),(64,82,'VDV_PHUONG_16',183.00,75.00,'DOI_CHUYEN','DU_DIEU_KIEN'),(65,83,'VDV_PHUONG_17',184.00,76.00,'LIBERO','DU_DIEU_KIEN'),(66,84,'VDV_PHUONG_18',185.00,77.00,'DOI_TRU','DU_DIEU_KIEN'),(67,85,'VDV_PHUONG_19',180.00,72.00,'CHU_CONG','DU_DIEU_KIEN'),(68,86,'VDV_PHUONG_20',181.00,73.00,'PHU_CONG','DU_DIEU_KIEN'),(69,87,'VDV_PHUONG_21',182.00,74.00,'CHUYEN_HAI','DU_DIEU_KIEN'),(70,88,'VDV_PHUONG_22',183.00,75.00,'DOI_CHUYEN','DU_DIEU_KIEN'),(71,89,'VDV_PHUONG_23',184.00,76.00,'LIBERO','DU_DIEU_KIEN'),(72,90,'VDV_PHUONG_24',185.00,77.00,'DOI_TRU','DU_DIEU_KIEN');
/*!40000 ALTER TABLE `vandongvien` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vitrithidau`
--

DROP TABLE IF EXISTS `vitrithidau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vitrithidau` (
  `idvitrithidau` int(11) NOT NULL AUTO_INCREMENT,
  `tenvitrithidau` varchar(300) NOT NULL,
  `loaihinh` varchar(50) NOT NULL DEFAULT 'NHA_THI_DAU',
  `idkhuvuc` int(11) NOT NULL,
  `diachi` varchar(500) NOT NULL,
  `diachi_chitiet` varchar(1000) DEFAULT NULL,
  `succhua` int(11) NOT NULL DEFAULT 0,
  `kinhdo` decimal(10,7) DEFAULT NULL,
  `vido` decimal(10,7) DEFAULT NULL,
  `sdt_lienhe` varchar(30) DEFAULT NULL,
  `nguoi_lienhe` varchar(200) DEFAULT NULL,
  `email_lienhe` varchar(200) DEFAULT NULL,
  `mota` varchar(1000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'HOAT_DONG',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`idvitrithidau`),
  UNIQUE KEY `uq_vitri_ten_diachi` (`tenvitrithidau`,`diachi`) USING HASH,
  KEY `idx_vitri_khuvuc_trangthai` (`idkhuvuc`,`trangthai`),
  KEY `idx_vitri_loaihinh_trangthai` (`loaihinh`,`trangthai`),
  CONSTRAINT `fk_vitri_khuvuc` FOREIGN KEY (`idkhuvuc`) REFERENCES `khuvuc` (`idkhuvuc`) ON UPDATE CASCADE,
  CONSTRAINT `chk_vitri_trangthai` CHECK (`trangthai` in ('HOAT_DONG','DANG_BAO_TRI','NGUNG_SU_DUNG'))
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vitrithidau`
--

LOCK TABLES `vitrithidau` WRITE;
/*!40000 ALTER TABLE `vitrithidau` DISABLE KEYS */;
INSERT INTO `vitrithidau` VALUES (6,'Cụm sân Trung tâm TDTT Phường Sài Gòn','NHA_THI_DAU',20,'Phường Sài Gòn, Hồ Chí Minh, Việt Nam','Phường Sài Gòn, Hồ Chí Minh, Việt Nam',1000,NULL,NULL,NULL,NULL,NULL,'Dữ liệu sân đấu test theo khu vực.','HOAT_DONG','2026-05-22 17:07:47','2026-05-25 04:13:55'),(7,'Cụm sân Trung tâm TDTT Phường Bến Thành','TRUNG_TAM_THE_THAO',21,'Trung tâm TDTT Phường Bến Thành, Phường Bến Thành, Thành phố Hồ Chí Minh','Trung tâm TDTT Phường Bến Thành, Phường Bến Thành, Thành phố Hồ Chí Minh, Việt Nam',1200,NULL,NULL,NULL,NULL,NULL,'Cụm sân bóng chuyền dữ liệu mẫu của Trung tâm TDTT Phường Bến Thành.','HOAT_DONG','2026-05-22 17:07:47',NULL),(8,'Cụm sân Trung tâm TDTT Phường Hoàn Kiếm','TRUNG_TAM_THE_THAO',30,'Trung tâm TDTT Phường Hoàn Kiếm, Phường Hoàn Kiếm, Thành phố Hà Nội','Trung tâm TDTT Phường Hoàn Kiếm, Phường Hoàn Kiếm, Thành phố Hà Nội, Việt Nam',1200,NULL,NULL,NULL,NULL,NULL,'Cụm sân bóng chuyền dữ liệu mẫu của Trung tâm TDTT Phường Hoàn Kiếm.','HOAT_DONG','2026-05-22 17:07:47',NULL),(9,'Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hồ Chí Minh','TRUNG_TAM_THE_THAO',2,'Trung tâm huấn luyện và thi đấu TDTT Thành phố Hồ Chí Minh','Trung tâm huấn luyện và thi đấu TDTT Thành phố Hồ Chí Minh, Việt Nam',7200,NULL,NULL,NULL,NULL,NULL,'Cụm sân bóng chuyền dữ liệu mẫu của Trung tâm huấn luyện và thi đấu TDTT Thành phố Hồ Chí Minh.','HOAT_DONG','2026-05-22 17:07:47',NULL),(10,'Cụm sân Trung tâm HL và thi đấu TDTT Thành phố Hà Nội','TRUNG_TAM_THE_THAO',3,'Trung tâm huấn luyện và thi đấu TDTT Thành phố Hà Nội','Trung tâm huấn luyện và thi đấu TDTT Thành phố Hà Nội, Việt Nam',7200,NULL,NULL,NULL,NULL,NULL,'Cụm sân bóng chuyền dữ liệu mẫu của Trung tâm huấn luyện và thi đấu TDTT Thành phố Hà Nội.','HOAT_DONG','2026-05-22 17:07:47',NULL),(11,'Cụm sân Liên đoàn Bóng chuyền Việt Nam','NHA_THI_DAU',1,'Mỹ Đình, Hà Nội, Việt Nam','Mỹ Đình, Hà Nội, Việt Nam',5000,NULL,NULL,NULL,NULL,NULL,'Dữ liệu sân đấu test theo khu vực.','HOAT_DONG','2026-05-25 04:13:55','2026-05-25 04:13:55'),(12,'Cụm sân Trung tâm TDTT Hà Nội','NHA_THI_DAU',3,'Đống Đa, Hà Nội, Việt Nam','Đống Đa, Hà Nội, Việt Nam',3000,NULL,NULL,NULL,NULL,NULL,'Dữ liệu sân đấu test theo khu vực.','HOAT_DONG','2026-05-25 04:13:55','2026-05-25 04:13:55'),(13,'Cụm sân Trung tâm TDTT Đà Nẵng','NHA_THI_DAU',1034,'KV1, Đà Nẵng, Việt Nam','KV1, Đà Nẵng, Việt Nam',3000,NULL,NULL,NULL,NULL,NULL,'Dữ liệu sân đấu test theo khu vực.','HOAT_DONG','2026-05-25 04:13:55','2026-05-25 04:13:55'),(14,'Cụm sân Trung tâm TDTT Hồ Chí Minh','NHA_THI_DAU',2,'Phường Sài Gòn, Hồ Chí Minh, Việt Nam','Phường Sài Gòn, Hồ Chí Minh, Việt Nam',3000,NULL,NULL,NULL,NULL,NULL,'Dữ liệu sân đấu test theo khu vực.','HOAT_DONG','2026-05-25 04:13:55','2026-05-25 04:13:55'),(15,'Cụm sân Trung tâm TDTT Phường Bình Dương','NHA_THI_DAU',1037,'Phường Bình Dương, Hồ Chí Minh, Việt Nam','Phường Bình Dương, Hồ Chí Minh, Việt Nam',1000,NULL,NULL,NULL,NULL,NULL,'Dữ liệu sân đấu test theo khu vực.','HOAT_DONG','2026-05-25 04:13:55','2026-05-25 04:13:55'),(16,'Cụm sân Trung tâm TDTT Phường Vũng Tàu','NHA_THI_DAU',1038,'Phường Vũng Tàu, Hồ Chí Minh, Việt Nam','Phường Vũng Tàu, Hồ Chí Minh, Việt Nam',1000,NULL,NULL,NULL,NULL,NULL,'Dữ liệu sân đấu test theo khu vực.','HOAT_DONG','2026-05-25 04:13:55','2026-05-25 04:13:55');
/*!40000 ALTER TABLE `vitrithidau` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_vitrithidau_bi_strict
BEFORE INSERT ON vitrithidau
FOR EACH ROW
BEGIN
    DECLARE v_trangthai_kv VARCHAR(50);

    IF NEW.loaihinh NOT IN ('NHA_THI_DAU','SAN_VAN_DONG','TRUNG_TAM_THE_THAO','TRUONG_HOC','CONG_TY','CLB','KHAC') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Loại hình vị trí thi đấu không hợp lệ.';
    END IF;

    IF NEW.succhua < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sức chứa vị trí thi đấu không được âm.';
    END IF;

    IF NEW.vido IS NOT NULL AND (NEW.vido < -90 OR NEW.vido > 90) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Vĩ độ không hợp lệ.';
    END IF;

    IF NEW.kinhdo IS NOT NULL AND (NEW.kinhdo < -180 OR NEW.kinhdo > 180) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kinh độ không hợp lệ.';
    END IF;

    IF NEW.email_lienhe IS NOT NULL AND NEW.email_lienhe NOT LIKE '%_@_%._%' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Email liên hệ không hợp lệ.';
    END IF;

    SELECT trangthai
      INTO v_trangthai_kv
      FROM khuvuc
     WHERE idkhuvuc = NEW.idkhuvuc;

    IF v_trangthai_kv <> 'HOAT_DONG' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Không thể tạo vị trí thi đấu trong khu vực ngừng sử dụng.';
    END IF;

    IF NEW.diachi_chitiet IS NULL THEN
        SET NEW.diachi_chitiet = NEW.diachi;
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
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_vitrithidau_bu_strict
BEFORE UPDATE ON vitrithidau
FOR EACH ROW
BEGIN
    DECLARE v_trangthai_kv VARCHAR(50);

    IF NEW.loaihinh NOT IN ('NHA_THI_DAU','SAN_VAN_DONG','TRUNG_TAM_THE_THAO','TRUONG_HOC','CONG_TY','CLB','KHAC') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Loại hình vị trí thi đấu không hợp lệ.';
    END IF;

    IF NEW.succhua < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sức chứa vị trí thi đấu không được âm.';
    END IF;

    IF NEW.vido IS NOT NULL AND (NEW.vido < -90 OR NEW.vido > 90) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Vĩ độ không hợp lệ.';
    END IF;

    IF NEW.kinhdo IS NOT NULL AND (NEW.kinhdo < -180 OR NEW.kinhdo > 180) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kinh độ không hợp lệ.';
    END IF;

    IF NEW.email_lienhe IS NOT NULL AND NEW.email_lienhe NOT LIKE '%_@_%._%' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Email liên hệ không hợp lệ.';
    END IF;

    SELECT trangthai
      INTO v_trangthai_kv
      FROM khuvuc
     WHERE idkhuvuc = NEW.idkhuvuc;

    IF v_trangthai_kv <> 'HOAT_DONG' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Không thể gắn vị trí thi đấu với khu vực ngừng sử dụng.';
    END IF;

    IF NEW.diachi_chitiet IS NULL THEN
        SET NEW.diachi_chitiet = NEW.diachi;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `vongdau`
--

DROP TABLE IF EXISTS `vongdau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vongdau` (
  `idvongdau` int(11) NOT NULL AUTO_INCREMENT,
  `idgiaidau` int(11) NOT NULL,
  `tenvongdau` varchar(300) NOT NULL,
  `loaivongdau` varchar(50) NOT NULL,
  `thutu` int(11) NOT NULL,
  `thoigianbatdau` date DEFAULT NULL,
  `thoigianketthuc` date DEFAULT NULL,
  `so_doi_tham_gia` int(11) NOT NULL,
  `co_bangdau` tinyint(1) NOT NULL DEFAULT 0,
  `so_bang_dau` int(11) DEFAULT NULL,
  `so_doi_moi_bang_du_kien` int(11) DEFAULT NULL,
  `so_luot_dau` int(11) NOT NULL DEFAULT 1,
  `so_doi_vao_vong_sau` int(11) DEFAULT NULL,
  `so_doi_vao_moi_bang` int(11) DEFAULT NULL,
  `cach_chon_doi_di_tiep` varchar(100) NOT NULL DEFAULT 'KHONG_AP_DUNG',
  `cach_xep_cap_dau` varchar(50) NOT NULL DEFAULT 'KHONG_AP_DUNG',
  `cach_phan_bo_bang` varchar(50) NOT NULL DEFAULT 'MANUAL',
  `cho_phep_bang_le` tinyint(1) NOT NULL DEFAULT 0,
  `chenh_lech_toi_da` int(11) NOT NULL DEFAULT 1,
  `tieu_chi_so_sanh_bang_le` varchar(50) NOT NULL DEFAULT 'DIEM_TRUNG_BINH',
  `seed_source` varchar(100) NOT NULL DEFAULT 'KHONG_AP_DUNG',
  `co_tranh_hang_ba` tinyint(1) NOT NULL DEFAULT 0,
  `trangthai` varchar(50) NOT NULL DEFAULT 'NHAP',
  `ngaytao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`idvongdau`),
  UNIQUE KEY `uq_vongdau_thutu` (`idgiaidau`,`thutu`),
  KEY `idx_vongdau_giai_thutu_trangthai` (`idgiaidau`,`thutu`,`trangthai`),
  CONSTRAINT `fk_vongdau_giaidau` FOREIGN KEY (`idgiaidau`) REFERENCES `giaidau` (`idgiaidau`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_vongdau_loai` CHECK (`loaivongdau` in ('VONG_DIEM','VONG_LOAI','CHUNG_KET','TRANH_HANG_BA')),
  CONSTRAINT `chk_vongdau_thutu` CHECK (`thutu` > 0),
  CONSTRAINT `chk_vongdau_sodoi` CHECK (`so_doi_tham_gia` >= 2),
  CONSTRAINT `chk_vongdau_bang` CHECK (`co_bangdau` = 0 and (`so_bang_dau` is null or `so_bang_dau` = 0) or `co_bangdau` = 1 and `so_bang_dau` is not null and `so_bang_dau` > 0),
  CONSTRAINT `chk_vongdau_luot` CHECK (`so_luot_dau` in (1,2)),
  CONSTRAINT `chk_vongdau_chondoi` CHECK (`cach_chon_doi_di_tiep` in ('TOP_N','TOP_N_MOI_BANG','THANG_DI_TIEP','BTC_CHON','KHONG_AP_DUNG')),
  CONSTRAINT `chk_vongdau_cachxep` CHECK (`cach_xep_cap_dau` in ('RANDOM','SEEDED','POT_DRAW','MANUAL','HYBRID','KHONG_AP_DUNG')),
  CONSTRAINT `chk_vongdau_seed` CHECK (`seed_source` in ('BANG_XEP_HANG_TRUOC','THU_HANG_VONG_TRUOC','DIEM_TICH_LUY','BTC_NHAP_TAY','KHONG_AP_DUNG')),
  CONSTRAINT `chk_vongdau_trangthai` CHECK (`trangthai` in ('NHAP','DA_TAO_DOI','CHO_PHAN_CONG_BANG','DA_TAO_BANG','DA_TAO_TRAN','DA_CONG_BO_LICH','DANG_DIEN_RA','DA_HOAN_THANH','DA_KET_THUC','DA_HUY')),
  CONSTRAINT `chk_vongdau_ngay` CHECK (`thoigianbatdau` is null or `thoigianketthuc` is null or `thoigianketthuc` >= `thoigianbatdau`),
  CONSTRAINT `chk_vongdau_bang_le` CHECK (`chenh_lech_toi_da` >= 0 and `tieu_chi_so_sanh_bang_le` in ('TONG_DIEM','DIEM_TRUNG_BINH','TY_LE_SET','TY_LE_DIEM')),
  CONSTRAINT `chk_vongdau_phan_bo_bang` CHECK (`cach_phan_bo_bang` in ('RANDOM','SEEDED','POT_DRAW','MANUAL','HYBRID'))
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vongdau`
--

LOCK TABLES `vongdau` WRITE;
/*!40000 ALTER TABLE `vongdau` DISABLE KEYS */;
INSERT INTO `vongdau` VALUES (5,1,'Vòng loại trực tiếp','VONG_LOAI',1,NULL,NULL,8,0,0,NULL,1,NULL,NULL,'THANG_DI_TIEP','MANUAL','MANUAL',0,1,'DIEM_TRUNG_BINH','BTC_NHAP_TAY',1,'NHAP','2026-05-25 16:32:15',NULL);
/*!40000 ALTER TABLE `vongdau` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_vongdau_bi
BEFORE INSERT ON vongdau
FOR EACH ROW
BEGIN
    DECLARE v_cap VARCHAR(50);
    SELECT cg.macapgiaidau INTO v_cap
    FROM giaidau gd JOIN capgiaidau cg ON cg.idcapgiaidau = gd.idcapgiaidau
    WHERE gd.idgiaidau = NEW.idgiaidau;
    IF v_cap = 'QUOC_GIA' AND NEW.co_bangdau = 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Giải cấp quốc gia trong VTMS không áp dụng bảng đấu mặc định.';
    END IF;
    IF NEW.loaivongdau = 'VONG_DIEM' AND NEW.cach_chon_doi_di_tiep = 'THANG_DI_TIEP' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Vòng điểm không dùng quy tắc thắng đi tiếp từng trận.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Temporary view structure for view `vw_cautruc_giaidau`
--

DROP TABLE IF EXISTS `vw_cautruc_giaidau`;
/*!50001 DROP VIEW IF EXISTS `vw_cautruc_giaidau`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_cautruc_giaidau` AS SELECT 
 1 AS `idgiaidau`,
 1 AS `tengiaidau`,
 1 AS `macapgiaidau`,
 1 AS `khuvucphamvi`,
 1 AS `idvongdau`,
 1 AS `tenvongdau`,
 1 AS `loaivongdau`,
 1 AS `thutu`,
 1 AS `co_bangdau`,
 1 AS `so_bang_dau`,
 1 AS `so_doi_tham_gia`,
 1 AS `so_doi_vao_vong_sau`,
 1 AS `trangthai_vong`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_dieu_kien_tham_gia_giai`
--

DROP TABLE IF EXISTS `vw_dieu_kien_tham_gia_giai`;
/*!50001 DROP VIEW IF EXISTS `vw_dieu_kien_tham_gia_giai`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_dieu_kien_tham_gia_giai` AS SELECT 
 1 AS `iddieukienthamgia`,
 1 AS `idgiaidau`,
 1 AS `tengiaidau`,
 1 AS `ten_dieukien`,
 1 AS `capdoituongthamgia`,
 1 AS `yeu_cau_thanh_tich`,
 1 AS `capgiaidau_thanh_tich_nguon`,
 1 AS `hang_toi_thieu_duoc_phep`,
 1 AS `so_mua_giai_gan_nhat_duoc_tinh`,
 1 AS `cho_phep_btc_duyet_ngoai_le`,
 1 AS `trangthai`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_doi_du_dieu_kien_tham_gia`
--

DROP TABLE IF EXISTS `vw_doi_du_dieu_kien_tham_gia`;
/*!50001 DROP VIEW IF EXISTS `vw_doi_du_dieu_kien_tham_gia`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_doi_du_dieu_kien_tham_gia` AS SELECT 
 1 AS `iddieukien`,
 1 AS `idgiaidau`,
 1 AS `tengiaidau`,
 1 AS `iddoibong`,
 1 AS `tendoibong`,
 1 AS `khuvuc_daidien`,
 1 AS `cap_daidien`,
 1 AS `nguon_dieukien`,
 1 AS `lydo_dieukien`,
 1 AS `trangthai`,
 1 AS `idthanhtich`,
 1 AS `hang_dat_duoc`,
 1 AS `danhhieu`,
 1 AS `mua_giai`,
 1 AS `idsuat`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_goi_y_doi_du_dieu_kien_theo_thanh_tich`
--

DROP TABLE IF EXISTS `vw_goi_y_doi_du_dieu_kien_theo_thanh_tich`;
/*!50001 DROP VIEW IF EXISTS `vw_goi_y_doi_du_dieu_kien_theo_thanh_tich`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_goi_y_doi_du_dieu_kien_theo_thanh_tich` AS SELECT 
 1 AS `iddieukienthamgia`,
 1 AS `idgiaidau_dich`,
 1 AS `tengiaidau_dich`,
 1 AS `iddoibong`,
 1 AS `tendoibong`,
 1 AS `idthanhtich`,
 1 AS `giai_dat_thanh_tich`,
 1 AS `hang_dat_duoc`,
 1 AS `danhhieu`,
 1 AS `mua_giai`,
 1 AS `yeu_cau_thanh_tich`,
 1 AS `hang_toi_thieu_duoc_phep`,
 1 AS `khuvuc_daidien`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_public_bangxephang`
--

DROP TABLE IF EXISTS `vw_public_bangxephang`;
/*!50001 DROP VIEW IF EXISTS `vw_public_bangxephang`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_public_bangxephang` AS SELECT 
 1 AS `idbangxephang`,
 1 AS `tengiaidau`,
 1 AS `tenbangxephang`,
 1 AS `phamvi`,
 1 AS `hang`,
 1 AS `tendoibong`,
 1 AS `sotran`,
 1 AS `thang`,
 1 AS `thua`,
 1 AS `sosetthang`,
 1 AS `sosetthua`,
 1 AS `diem`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_public_ketqua`
--

DROP TABLE IF EXISTS `vw_public_ketqua`;
/*!50001 DROP VIEW IF EXISTS `vw_public_ketqua`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_public_ketqua` AS SELECT 
 1 AS `idketqua`,
 1 AS `tengiaidau`,
 1 AS `ma_tran`,
 1 AS `ten_tran`,
 1 AS `doi1`,
 1 AS `doi2`,
 1 AS `doithang`,
 1 AS `sosetdoi1`,
 1 AS `sosetdoi2`,
 1 AS `diemdoi1`,
 1 AS `diemdoi2`,
 1 AS `trangthai`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_public_lichthidau`
--

DROP TABLE IF EXISTS `vw_public_lichthidau`;
/*!50001 DROP VIEW IF EXISTS `vw_public_lichthidau`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_public_lichthidau` AS SELECT 
 1 AS `idtrandau`,
 1 AS `tengiaidau`,
 1 AS `tenvongdau`,
 1 AS `tenbang`,
 1 AS `ma_tran`,
 1 AS `ten_tran`,
 1 AS `doi1`,
 1 AS `doi2`,
 1 AS `tenvitrithidau`,
 1 AS `tensandau`,
 1 AS `thoigianbatdau`,
 1 AS `trangthai`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_sandau_daydu_khuvuc`
--

DROP TABLE IF EXISTS `vw_sandau_daydu_khuvuc`;
/*!50001 DROP VIEW IF EXISTS `vw_sandau_daydu_khuvuc`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_sandau_daydu_khuvuc` AS SELECT 
 1 AS `idsandau`,
 1 AS `tensandau`,
 1 AS `loaisan`,
 1 AS `mat_san`,
 1 AS `kichthuoc`,
 1 AS `succhua_san`,
 1 AS `mota_san`,
 1 AS `trangthai_san`,
 1 AS `ngaytao_san`,
 1 AS `ngaycapnhat_san`,
 1 AS `idvitrithidau`,
 1 AS `tenvitrithidau`,
 1 AS `loaihinh_vitrithidau`,
 1 AS `idkhuvuc_gan_truc_tiep`,
 1 AS `makhuvuc_gan_truc_tiep`,
 1 AS `tenkhuvuc_gan_truc_tiep`,
 1 AS `capkhuvuc_gan_truc_tiep`,
 1 AS `id_quocgia`,
 1 AS `ten_quocgia`,
 1 AS `id_tinhthanh`,
 1 AS `ten_tinhthanh`,
 1 AS `id_quanhuyen`,
 1 AS `ten_quanhuyen`,
 1 AS `id_xaphuong`,
 1 AS `ten_xaphuong`,
 1 AS `id_donvi`,
 1 AS `ten_donvi`,
 1 AS `duong_dan_khuvuc`,
 1 AS `diachi`,
 1 AS `diachi_chitiet`,
 1 AS `succhua_vitrithidau`,
 1 AS `kinhdo`,
 1 AS `vido`,
 1 AS `trangthai_vitrithidau`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_thanhtich_doibong`
--

DROP TABLE IF EXISTS `vw_thanhtich_doibong`;
/*!50001 DROP VIEW IF EXISTS `vw_thanhtich_doibong`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_thanhtich_doibong` AS SELECT 
 1 AS `idthanhtich`,
 1 AS `iddoibong`,
 1 AS `tendoibong`,
 1 AS `idgiaidau`,
 1 AS `tengiaidau`,
 1 AS `macapgiaidau`,
 1 AS `tencapgiaidau`,
 1 AS `tenkhuvuc`,
 1 AS `mua_giai`,
 1 AS `hang_dat_duoc`,
 1 AS `danhhieu`,
 1 AS `nguon_ghi_nhan`,
 1 AS `ngay_cong_nhan`,
 1 AS `trangthai`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_vitrithidau_daydu_khuvuc`
--

DROP TABLE IF EXISTS `vw_vitrithidau_daydu_khuvuc`;
/*!50001 DROP VIEW IF EXISTS `vw_vitrithidau_daydu_khuvuc`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_vitrithidau_daydu_khuvuc` AS SELECT 
 1 AS `idvitrithidau`,
 1 AS `tenvitrithidau`,
 1 AS `loaihinh`,
 1 AS `idkhuvuc_gan_truc_tiep`,
 1 AS `makhuvuc_gan_truc_tiep`,
 1 AS `tenkhuvuc_gan_truc_tiep`,
 1 AS `capkhuvuc_gan_truc_tiep`,
 1 AS `id_quocgia`,
 1 AS `ten_quocgia`,
 1 AS `id_tinhthanh`,
 1 AS `ten_tinhthanh`,
 1 AS `id_quanhuyen`,
 1 AS `ten_quanhuyen`,
 1 AS `id_xaphuong`,
 1 AS `ten_xaphuong`,
 1 AS `id_donvi`,
 1 AS `ten_donvi`,
 1 AS `duong_dan_khuvuc`,
 1 AS `diachi`,
 1 AS `diachi_chitiet`,
 1 AS `succhua`,
 1 AS `kinhdo`,
 1 AS `vido`,
 1 AS `sdt_lienhe`,
 1 AS `nguoi_lienhe`,
 1 AS `email_lienhe`,
 1 AS `mota`,
 1 AS `trangthai`,
 1 AS `ngaytao`,
 1 AS `ngaycapnhat`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `yeucaucapnhathoso`
--

DROP TABLE IF EXISTS `yeucaucapnhathoso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `yeucaucapnhathoso` (
  `idyeucaucapnhat` int(11) NOT NULL AUTO_INCREMENT,
  `idnguoidung` int(11) NOT NULL,
  `banglienquan` varchar(100) NOT NULL,
  `truongcapnhat` varchar(100) NOT NULL,
  `giatricu` varchar(1000) DEFAULT NULL,
  `giatrimoi` varchar(1000) NOT NULL,
  `lydo` varchar(1000) DEFAULT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_DUYET',
  `ngaygui` datetime NOT NULL DEFAULT current_timestamp(),
  `ngayxuly` datetime DEFAULT NULL,
  PRIMARY KEY (`idyeucaucapnhat`),
  KEY `fk_yccnhs_nguoidung` (`idnguoidung`),
  CONSTRAINT `fk_yccnhs_nguoidung` FOREIGN KEY (`idnguoidung`) REFERENCES `nguoidung` (`idnguoidung`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_yccnhs_trangthai` CHECK (`trangthai` in ('CHO_DUYET','DA_DUYET','TU_CHOI')),
  CONSTRAINT `chk_yccnhs_ngayxuly` CHECK (`ngayxuly` is null or `ngayxuly` >= `ngaygui`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yeucaucapnhathoso`
--

LOCK TABLES `yeucaucapnhathoso` WRITE;
/*!40000 ALTER TABLE `yeucaucapnhathoso` DISABLE KEYS */;
/*!40000 ALTER TABLE `yeucaucapnhathoso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yeucauxacnhan`
--

DROP TABLE IF EXISTS `yeucauxacnhan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `yeucauxacnhan` (
  `idyeucau` int(11) NOT NULL AUTO_INCREMENT,
  `loainguoigui` varchar(100) NOT NULL,
  `idnguoigui` int(11) NOT NULL,
  `loainguoinhan` varchar(100) NOT NULL,
  `idnguoinhan` int(11) NOT NULL,
  `loaixacnhan` varchar(100) NOT NULL,
  `noidung` varchar(1000) NOT NULL,
  `trangthai` varchar(50) NOT NULL DEFAULT 'CHO_DUYET',
  `ngaygui` datetime NOT NULL DEFAULT current_timestamp(),
  `ngayxuly` datetime DEFAULT NULL,
  `ghichu` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`idyeucau`),
  CONSTRAINT `chk_ycxn_loaixacnhan` CHECK (`loaixacnhan` in ('XAC_NHAN_HLV','XAC_NHAN_VDV','XAC_NHAN_THAY_DOI_HO_SO','XAC_NHAN_NGHI_PHEP','XAC_NHAN_TAI_KHOAN_TRONG_TAI','XAC_NHAN_DANG_KY_GIAI')),
  CONSTRAINT `chk_ycxn_trangthai` CHECK (`trangthai` in ('CHO_DUYET','DA_DUYET','TU_CHOI','DA_HUY')),
  CONSTRAINT `chk_ycxn_ngayxuly` CHECK (`ngayxuly` is null or `ngayxuly` >= `ngaygui`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yeucauxacnhan`
--

LOCK TABLES `yeucauxacnhan` WRITE;
/*!40000 ALTER TABLE `yeucauxacnhan` DISABLE KEYS */;
INSERT INTO `yeucauxacnhan` VALUES (1,'HUAN_LUYEN_VIEN',9,'BAN_TO_CHUC',5,'XAC_NHAN_DANG_KY_GIAI','Dang ky giai dau #1, doi #9. Yeu cau xac nhan doi doi_phuong_binhduong_01 tham gia giai dau Giai bóng chuyền chính thức Phường Bình Dương 2026','DA_DUYET','2026-05-25 16:17:18','2026-05-25 16:17:42','Duyet dang ky doi bong'),(2,'HUAN_LUYEN_VIEN',11,'BAN_TO_CHUC',5,'XAC_NHAN_DANG_KY_GIAI','Dang ky giai dau #1, doi #11. Yeu cau xac nhan doi doi_tunhan_binhduong_01 tham gia giai dau Giai bóng chuyền chính thức Phường Bình Dương 2026','DA_DUYET','2026-05-25 16:17:34','2026-05-25 16:17:41','Duyet dang ky doi bong');
/*!40000 ALTER TABLE `yeucauxacnhan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'vtms'
--

--
-- Dumping routines for database 'vtms'
--
/*!50003 DROP FUNCTION IF EXISTS `fn_khuvuc_la_con` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_khuvuc_la_con`(p_child INT, p_parent INT) RETURNS tinyint(4)
    READS SQL DATA
BEGIN
    DECLARE v_current INT;
    DECLARE v_parent INT;
    DECLARE v_counter INT DEFAULT 0;
    DECLARE v_not_found TINYINT DEFAULT 0;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_not_found = 1;

    IF p_child IS NULL OR p_parent IS NULL THEN
        RETURN 0;
    END IF;
    IF p_child = p_parent THEN
        RETURN 1;
    END IF;

    SET v_current = p_child;
    WHILE v_current IS NOT NULL AND v_counter < 50 DO
        SET v_not_found = 0;
        SELECT idkhuvuccha INTO v_parent FROM khuvuc WHERE idkhuvuc = v_current;
        IF v_not_found = 1 THEN
            RETURN 0;
        END IF;
        IF v_parent = p_parent THEN
            RETURN 1;
        END IF;
        SET v_current = v_parent;
        SET v_counter = v_counter + 1;
    END WHILE;
    RETURN 0;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_cap_nhat_slot_tu_ketqua` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cap_nhat_slot_tu_ketqua`(IN p_idtrandau INT)
BEGIN
    DECLARE v_win INT;
    DECLARE v_lose INT;

    SELECT iddoithang, iddoithua INTO v_win, v_lose
    FROM ketquatrandau
    WHERE idtrandau = p_idtrandau
    ORDER BY idketqua DESC
    LIMIT 1;

    UPDATE trandauslot
    SET iddoibong = CASE
        WHEN source_result = 'WINNER' THEN v_win
        WHEN source_result = 'LOSER' THEN v_lose
        ELSE iddoibong
    END
    WHERE source_match_id = p_idtrandau
      AND source_type IN ('WINNER','LOSER');

    UPDATE trandau t
    LEFT JOIN trandauslot s1 ON s1.idtrandau = t.idtrandau AND s1.slot_so = 1
    LEFT JOIN trandauslot s2 ON s2.idtrandau = t.idtrandau AND s2.slot_so = 2
    SET t.iddoibong1 = s1.iddoibong,
        t.iddoibong2 = s2.iddoibong,
        t.trangthai = CASE
            WHEN s1.iddoibong IS NOT NULL AND s2.iddoibong IS NOT NULL AND t.trangthai = 'CHO_DOI_DOI'
            THEN 'CHO_XEP_LICH'
            ELSE t.trangthai
        END,
        t.ngaycapnhat = CURRENT_TIMESTAMP
    WHERE t.idtrandau IN (
        SELECT DISTINCT idtrandau
        FROM trandauslot
        WHERE source_match_id = p_idtrandau
    );
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_tao_doi_du_dieu_kien_tu_thanhtich` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_tao_doi_du_dieu_kien_tu_thanhtich`(IN p_idgiaidau INT)
BEGIN
    CALL sp_tao_doi_du_dieu_kien_tu_thanhtich_v2(p_idgiaidau);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_tao_doi_du_dieu_kien_tu_thanhtich_v2` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_tao_doi_du_dieu_kien_tu_thanhtich_v2`(IN p_idgiaidau INT)
BEGIN
    INSERT INTO doidudieukienthamgia (
        idgiaidau,
        iddoibong,
        iddieukienthamgia,
        idthanhtich,
        nguon_dieukien,
        lydo_dieukien,
        diem_xet_duyet,
        trangthai,
        ngay_xac_nhan
    )
    SELECT
        dk.idgiaidau,
        tt.iddoibong,
        dk.iddieukienthamgia,
        tt.idthanhtich,
        'THANH_TICH',
        CONCAT('Dat hang ', tt.hang_dat_duoc, ' tai ', gnguon.tengiaidau, ' mua ', tt.mua_giai),
        db.diem_xep_hang,
        'DU_DIEU_KIEN',
        NOW()
    FROM dieukienthamgiagiai dk
    JOIN giaidau gdich ON gdich.idgiaidau = dk.idgiaidau
    JOIN thanhtichdoibong tt ON tt.trangthai = 'HOP_LE'
    JOIN giaidau gnguon ON gnguon.idgiaidau = tt.idgiaidau
    JOIN doibong db ON db.iddoibong = tt.iddoibong AND db.trangthai = 'HOAT_DONG'
    JOIN khuvuc kvdoi ON kvdoi.idkhuvuc = db.idkhuvucdaidien
    WHERE dk.idgiaidau = p_idgiaidau
      AND dk.trangthai = 'HOAT_DONG'
      AND dk.yeu_cau_thanh_tich IN ('VO_DICH','A_QUAN','HANG_BA','TOP_N','THEO_XEP_HANG')
      AND kvdoi.capkhuvuc = dk.capdoituongthamgia
      AND (db.idkhuvucdaidien = gdich.idkhuvucphamvi OR fn_khuvuc_la_con(db.idkhuvucdaidien, gdich.idkhuvucphamvi) = 1)
      AND tt.idcapgiaidau = dk.idcapgiaidau_thanh_tich_nguon
      AND (
          (dk.yeu_cau_thanh_tich = 'VO_DICH' AND tt.hang_dat_duoc = 1)
          OR (dk.yeu_cau_thanh_tich = 'A_QUAN' AND tt.hang_dat_duoc = 2)
          OR (dk.yeu_cau_thanh_tich = 'HANG_BA' AND tt.hang_dat_duoc = 3)
          OR (dk.yeu_cau_thanh_tich IN ('TOP_N','THEO_XEP_HANG') AND tt.hang_dat_duoc <= dk.hang_toi_thieu_duoc_phep)
      )
    ON DUPLICATE KEY UPDATE
        iddieukienthamgia = VALUES(iddieukienthamgia),
        idthanhtich = VALUES(idthanhtich),
        nguon_dieukien = VALUES(nguon_dieukien),
        lydo_dieukien = VALUES(lydo_dieukien),
        diem_xet_duyet = VALUES(diem_xet_duyet),
        trangthai = IF(doidudieukienthamgia.trangthai IN ('TU_CHOI','HUY_TU_CACH'), doidudieukienthamgia.trangthai, VALUES(trangthai)),
        ngay_xac_nhan = VALUES(ngay_xac_nhan);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_vtms5_add_column_if_not_exists` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_vtms5_add_column_if_not_exists`(
    IN p_table_name VARCHAR(128),
    IN p_column_name VARCHAR(128),
    IN p_column_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table_name
          AND COLUMN_NAME = p_column_name
    ) THEN
        SET @vtms5_sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD COLUMN ', p_column_definition);
        PREPARE vtms5_stmt FROM @vtms5_sql;
        EXECUTE vtms5_stmt;
        DEALLOCATE PREPARE vtms5_stmt;
    END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_vtms5_add_index_if_not_exists` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_vtms5_add_index_if_not_exists`(
    IN p_table_name VARCHAR(128),
    IN p_index_name VARCHAR(128),
    IN p_index_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table_name
          AND INDEX_NAME = p_index_name
    ) THEN
        SET @vtms5_sql = p_index_definition;
        PREPARE vtms5_stmt FROM @vtms5_sql;
        EXECUTE vtms5_stmt;
        DEALLOCATE PREPARE vtms5_stmt;
    END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `v_dieukien_giai_thanhtich`
--

/*!50001 DROP VIEW IF EXISTS `v_dieukien_giai_thanhtich`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_dieukien_giai_thanhtich` AS select `d`.`iddieukienthamgia` AS `iddieukienthamgia`,`d`.`idgiaidau` AS `idgiaidau`,group_concat(`t`.`ma_thanhtich` order by `t`.`hang_tuong_ung` ASC,`t`.`ma_thanhtich` ASC separator ',') AS `thanh_tich_duoc_phep`,min(`t`.`hang_tuong_ung`) AS `hang_tot_nhat`,max(`t`.`hang_tuong_ung`) AS `hang_toi_da_duoc_phep` from (`dieukienthamgiagiai` `d` left join `dieukienthamgiagiai_thanhtich` `t` on(`t`.`iddieukienthamgia` = `d`.`iddieukienthamgia` and `t`.`trangthai` = 'HOAT_DONG')) group by `d`.`iddieukienthamgia`,`d`.`idgiaidau` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_cautruc_giaidau`
--

/*!50001 DROP VIEW IF EXISTS `vw_cautruc_giaidau`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_cautruc_giaidau` AS select `gd`.`idgiaidau` AS `idgiaidau`,`gd`.`tengiaidau` AS `tengiaidau`,`cg`.`macapgiaidau` AS `macapgiaidau`,`kv`.`tenkhuvuc` AS `khuvucphamvi`,`vd`.`idvongdau` AS `idvongdau`,`vd`.`tenvongdau` AS `tenvongdau`,`vd`.`loaivongdau` AS `loaivongdau`,`vd`.`thutu` AS `thutu`,`vd`.`co_bangdau` AS `co_bangdau`,`vd`.`so_bang_dau` AS `so_bang_dau`,`vd`.`so_doi_tham_gia` AS `so_doi_tham_gia`,`vd`.`so_doi_vao_vong_sau` AS `so_doi_vao_vong_sau`,`vd`.`trangthai` AS `trangthai_vong` from (((`giaidau` `gd` join `capgiaidau` `cg` on(`cg`.`idcapgiaidau` = `gd`.`idcapgiaidau`)) join `khuvuc` `kv` on(`kv`.`idkhuvuc` = `gd`.`idkhuvucphamvi`)) left join `vongdau` `vd` on(`vd`.`idgiaidau` = `gd`.`idgiaidau`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_dieu_kien_tham_gia_giai`
--

/*!50001 DROP VIEW IF EXISTS `vw_dieu_kien_tham_gia_giai`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_dieu_kien_tham_gia_giai` AS select `dk`.`iddieukienthamgia` AS `iddieukienthamgia`,`dk`.`idgiaidau` AS `idgiaidau`,`gd`.`tengiaidau` AS `tengiaidau`,`dk`.`ten_dieukien` AS `ten_dieukien`,`dk`.`capdoituongthamgia` AS `capdoituongthamgia`,`dk`.`yeu_cau_thanh_tich` AS `yeu_cau_thanh_tich`,`cgn`.`macapgiaidau` AS `capgiaidau_thanh_tich_nguon`,`dk`.`hang_toi_thieu_duoc_phep` AS `hang_toi_thieu_duoc_phep`,`dk`.`so_mua_giai_gan_nhat_duoc_tinh` AS `so_mua_giai_gan_nhat_duoc_tinh`,`dk`.`cho_phep_btc_duyet_ngoai_le` AS `cho_phep_btc_duyet_ngoai_le`,`dk`.`trangthai` AS `trangthai` from ((`dieukienthamgiagiai` `dk` join `giaidau` `gd` on(`gd`.`idgiaidau` = `dk`.`idgiaidau`)) left join `capgiaidau` `cgn` on(`cgn`.`idcapgiaidau` = `dk`.`idcapgiaidau_thanh_tich_nguon`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_doi_du_dieu_kien_tham_gia`
--

/*!50001 DROP VIEW IF EXISTS `vw_doi_du_dieu_kien_tham_gia`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_doi_du_dieu_kien_tham_gia` AS select `ddk`.`iddieukien` AS `iddieukien`,`ddk`.`idgiaidau` AS `idgiaidau`,`gd`.`tengiaidau` AS `tengiaidau`,`ddk`.`iddoibong` AS `iddoibong`,`db`.`tendoibong` AS `tendoibong`,`kv`.`tenkhuvuc` AS `khuvuc_daidien`,`kv`.`capkhuvuc` AS `cap_daidien`,`ddk`.`nguon_dieukien` AS `nguon_dieukien`,`ddk`.`lydo_dieukien` AS `lydo_dieukien`,`ddk`.`trangthai` AS `trangthai`,`ddk`.`idthanhtich` AS `idthanhtich`,`tt`.`hang_dat_duoc` AS `hang_dat_duoc`,`tt`.`danhhieu` AS `danhhieu`,`tt`.`mua_giai` AS `mua_giai`,`ddk`.`idsuat` AS `idsuat` from ((((`doidudieukienthamgia` `ddk` join `giaidau` `gd` on(`gd`.`idgiaidau` = `ddk`.`idgiaidau`)) join `doibong` `db` on(`db`.`iddoibong` = `ddk`.`iddoibong`)) join `khuvuc` `kv` on(`kv`.`idkhuvuc` = `db`.`idkhuvucdaidien`)) left join `thanhtichdoibong` `tt` on(`tt`.`idthanhtich` = `ddk`.`idthanhtich`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_goi_y_doi_du_dieu_kien_theo_thanh_tich`
--

/*!50001 DROP VIEW IF EXISTS `vw_goi_y_doi_du_dieu_kien_theo_thanh_tich`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_goi_y_doi_du_dieu_kien_theo_thanh_tich` AS select `dk`.`iddieukienthamgia` AS `iddieukienthamgia`,`dk`.`idgiaidau` AS `idgiaidau_dich`,`gdich`.`tengiaidau` AS `tengiaidau_dich`,`tt`.`iddoibong` AS `iddoibong`,`db`.`tendoibong` AS `tendoibong`,`tt`.`idthanhtich` AS `idthanhtich`,`gnguon`.`tengiaidau` AS `giai_dat_thanh_tich`,`tt`.`hang_dat_duoc` AS `hang_dat_duoc`,`tt`.`danhhieu` AS `danhhieu`,`tt`.`mua_giai` AS `mua_giai`,`dk`.`yeu_cau_thanh_tich` AS `yeu_cau_thanh_tich`,`dk`.`hang_toi_thieu_duoc_phep` AS `hang_toi_thieu_duoc_phep`,`kvdoi`.`tenkhuvuc` AS `khuvuc_daidien` from (((((`dieukienthamgiagiai` `dk` join `giaidau` `gdich` on(`gdich`.`idgiaidau` = `dk`.`idgiaidau`)) join `thanhtichdoibong` `tt` on(`tt`.`trangthai` = 'HOP_LE')) join `giaidau` `gnguon` on(`gnguon`.`idgiaidau` = `tt`.`idgiaidau`)) join `doibong` `db` on(`db`.`iddoibong` = `tt`.`iddoibong` and `db`.`trangthai` = 'HOAT_DONG')) join `khuvuc` `kvdoi` on(`kvdoi`.`idkhuvuc` = `db`.`idkhuvucdaidien`)) where `dk`.`trangthai` = 'HOAT_DONG' and `dk`.`yeu_cau_thanh_tich` in ('VO_DICH','A_QUAN','HANG_BA','TOP_N','THEO_XEP_HANG') and `kvdoi`.`capkhuvuc` = `dk`.`capdoituongthamgia` and `tt`.`idcapgiaidau` = `dk`.`idcapgiaidau_thanh_tich_nguon` and (`db`.`idkhuvucdaidien` = `gdich`.`idkhuvucphamvi` or `fn_khuvuc_la_con`(`db`.`idkhuvucdaidien`,`gdich`.`idkhuvucphamvi`) = 1) and (`dk`.`yeu_cau_thanh_tich` = 'VO_DICH' and `tt`.`hang_dat_duoc` = 1 or `dk`.`yeu_cau_thanh_tich` = 'A_QUAN' and `tt`.`hang_dat_duoc` = 2 or `dk`.`yeu_cau_thanh_tich` = 'HANG_BA' and `tt`.`hang_dat_duoc` = 3 or `dk`.`yeu_cau_thanh_tich` in ('TOP_N','THEO_XEP_HANG') and `tt`.`hang_dat_duoc` <= `dk`.`hang_toi_thieu_duoc_phep`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_public_bangxephang`
--

/*!50001 DROP VIEW IF EXISTS `vw_public_bangxephang`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_public_bangxephang` AS select `bxh`.`idbangxephang` AS `idbangxephang`,`gd`.`tengiaidau` AS `tengiaidau`,`bxh`.`tenbangxephang` AS `tenbangxephang`,`bxh`.`phamvi` AS `phamvi`,`ct`.`hang` AS `hang`,`d`.`tendoibong` AS `tendoibong`,`ct`.`sotran` AS `sotran`,`ct`.`thang` AS `thang`,`ct`.`thua` AS `thua`,`ct`.`sosetthang` AS `sosetthang`,`ct`.`sosetthua` AS `sosetthua`,`ct`.`diem` AS `diem` from (((`bangxephang` `bxh` join `giaidau` `gd` on(`gd`.`idgiaidau` = `bxh`.`idgiaidau`)) join `chitietbangxephang` `ct` on(`ct`.`idbangxephang` = `bxh`.`idbangxephang`)) join `doibong` `d` on(`d`.`iddoibong` = `ct`.`iddoibong`)) where `bxh`.`trangthai` in ('DA_CONG_BO','DA_CAP_NHAT') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_public_ketqua`
--

/*!50001 DROP VIEW IF EXISTS `vw_public_ketqua`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_public_ketqua` AS select `kq`.`idketqua` AS `idketqua`,`gd`.`tengiaidau` AS `tengiaidau`,`t`.`ma_tran` AS `ma_tran`,`t`.`ten_tran` AS `ten_tran`,`d1`.`tendoibong` AS `doi1`,`d2`.`tendoibong` AS `doi2`,`dt`.`tendoibong` AS `doithang`,`kq`.`sosetdoi1` AS `sosetdoi1`,`kq`.`sosetdoi2` AS `sosetdoi2`,`kq`.`diemdoi1` AS `diemdoi1`,`kq`.`diemdoi2` AS `diemdoi2`,`kq`.`trangthai` AS `trangthai` from (((((`ketquatrandau` `kq` join `trandau` `t` on(`t`.`idtrandau` = `kq`.`idtrandau`)) join `giaidau` `gd` on(`gd`.`idgiaidau` = `t`.`idgiaidau`)) left join `doibong` `d1` on(`d1`.`iddoibong` = `t`.`iddoibong1`)) left join `doibong` `d2` on(`d2`.`iddoibong` = `t`.`iddoibong2`)) left join `doibong` `dt` on(`dt`.`iddoibong` = `kq`.`iddoithang`)) where `kq`.`trangthai` in ('DA_CONG_BO','DA_DIEU_CHINH') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_public_lichthidau`
--

/*!50001 DROP VIEW IF EXISTS `vw_public_lichthidau`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_public_lichthidau` AS select `t`.`idtrandau` AS `idtrandau`,`gd`.`tengiaidau` AS `tengiaidau`,`vd`.`tenvongdau` AS `tenvongdau`,`bd`.`tenbang` AS `tenbang`,`t`.`ma_tran` AS `ma_tran`,`t`.`ten_tran` AS `ten_tran`,`d1`.`tendoibong` AS `doi1`,`d2`.`tendoibong` AS `doi2`,`vt`.`tenvitrithidau` AS `tenvitrithidau`,`sd`.`tensandau` AS `tensandau`,`t`.`thoigianbatdau` AS `thoigianbatdau`,`t`.`trangthai` AS `trangthai` from (((((((`trandau` `t` join `giaidau` `gd` on(`gd`.`idgiaidau` = `t`.`idgiaidau`)) join `vongdau` `vd` on(`vd`.`idvongdau` = `t`.`idvongdau`)) left join `bangdau` `bd` on(`bd`.`idbangdau` = `t`.`idbangdau`)) left join `doibong` `d1` on(`d1`.`iddoibong` = `t`.`iddoibong1`)) left join `doibong` `d2` on(`d2`.`iddoibong` = `t`.`iddoibong2`)) left join `sandau` `sd` on(`sd`.`idsandau` = `t`.`idsandau`)) left join `vitrithidau` `vt` on(`vt`.`idvitrithidau` = `sd`.`idvitrithidau`)) where `gd`.`trangthai` in ('DA_CONG_BO','DANG_DIEN_RA','DA_KET_THUC') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_sandau_daydu_khuvuc`
--

/*!50001 DROP VIEW IF EXISTS `vw_sandau_daydu_khuvuc`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_sandau_daydu_khuvuc` AS select `sd`.`idsandau` AS `idsandau`,`sd`.`tensandau` AS `tensandau`,`sd`.`loaisan` AS `loaisan`,`sd`.`mat_san` AS `mat_san`,`sd`.`kichthuoc` AS `kichthuoc`,`sd`.`succhua` AS `succhua_san`,`sd`.`mota` AS `mota_san`,`sd`.`trangthai` AS `trangthai_san`,`sd`.`ngaytao` AS `ngaytao_san`,`sd`.`ngaycapnhat` AS `ngaycapnhat_san`,`vt`.`idvitrithidau` AS `idvitrithidau`,`vt`.`tenvitrithidau` AS `tenvitrithidau`,`vt`.`loaihinh` AS `loaihinh_vitrithidau`,`vt`.`idkhuvuc_gan_truc_tiep` AS `idkhuvuc_gan_truc_tiep`,`vt`.`makhuvuc_gan_truc_tiep` AS `makhuvuc_gan_truc_tiep`,`vt`.`tenkhuvuc_gan_truc_tiep` AS `tenkhuvuc_gan_truc_tiep`,`vt`.`capkhuvuc_gan_truc_tiep` AS `capkhuvuc_gan_truc_tiep`,`vt`.`id_quocgia` AS `id_quocgia`,`vt`.`ten_quocgia` AS `ten_quocgia`,`vt`.`id_tinhthanh` AS `id_tinhthanh`,`vt`.`ten_tinhthanh` AS `ten_tinhthanh`,`vt`.`id_quanhuyen` AS `id_quanhuyen`,`vt`.`ten_quanhuyen` AS `ten_quanhuyen`,`vt`.`id_xaphuong` AS `id_xaphuong`,`vt`.`ten_xaphuong` AS `ten_xaphuong`,`vt`.`id_donvi` AS `id_donvi`,`vt`.`ten_donvi` AS `ten_donvi`,`vt`.`duong_dan_khuvuc` AS `duong_dan_khuvuc`,`vt`.`diachi` AS `diachi`,`vt`.`diachi_chitiet` AS `diachi_chitiet`,`vt`.`succhua` AS `succhua_vitrithidau`,`vt`.`kinhdo` AS `kinhdo`,`vt`.`vido` AS `vido`,`vt`.`trangthai` AS `trangthai_vitrithidau` from (`sandau` `sd` join `vw_vitrithidau_daydu_khuvuc` `vt` on(`vt`.`idvitrithidau` = `sd`.`idvitrithidau`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_thanhtich_doibong`
--

/*!50001 DROP VIEW IF EXISTS `vw_thanhtich_doibong`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_thanhtich_doibong` AS select `tt`.`idthanhtich` AS `idthanhtich`,`tt`.`iddoibong` AS `iddoibong`,`db`.`tendoibong` AS `tendoibong`,`tt`.`idgiaidau` AS `idgiaidau`,`gd`.`tengiaidau` AS `tengiaidau`,`cg`.`macapgiaidau` AS `macapgiaidau`,`cg`.`tencapgiaidau` AS `tencapgiaidau`,`kv`.`tenkhuvuc` AS `tenkhuvuc`,`tt`.`mua_giai` AS `mua_giai`,`tt`.`hang_dat_duoc` AS `hang_dat_duoc`,`tt`.`danhhieu` AS `danhhieu`,`tt`.`nguon_ghi_nhan` AS `nguon_ghi_nhan`,`tt`.`ngay_cong_nhan` AS `ngay_cong_nhan`,`tt`.`trangthai` AS `trangthai` from ((((`thanhtichdoibong` `tt` join `doibong` `db` on(`db`.`iddoibong` = `tt`.`iddoibong`)) join `giaidau` `gd` on(`gd`.`idgiaidau` = `tt`.`idgiaidau`)) join `capgiaidau` `cg` on(`cg`.`idcapgiaidau` = `tt`.`idcapgiaidau`)) join `khuvuc` `kv` on(`kv`.`idkhuvuc` = `tt`.`idkhuvuc`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_vitrithidau_daydu_khuvuc`
--

/*!50001 DROP VIEW IF EXISTS `vw_vitrithidau_daydu_khuvuc`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_vitrithidau_daydu_khuvuc` AS select `x`.`idvitrithidau` AS `idvitrithidau`,`x`.`tenvitrithidau` AS `tenvitrithidau`,`x`.`loaihinh` AS `loaihinh`,`x`.`idkhuvuc` AS `idkhuvuc_gan_truc_tiep`,`x`.`makhuvuc` AS `makhuvuc_gan_truc_tiep`,`x`.`tenkhuvuc` AS `tenkhuvuc_gan_truc_tiep`,`x`.`capkhuvuc` AS `capkhuvuc_gan_truc_tiep`,`x`.`id_quocgia` AS `id_quocgia`,`x`.`ten_quocgia` AS `ten_quocgia`,`x`.`id_tinhthanh` AS `id_tinhthanh`,`x`.`ten_tinhthanh` AS `ten_tinhthanh`,`x`.`id_quanhuyen` AS `id_quanhuyen`,`x`.`ten_quanhuyen` AS `ten_quanhuyen`,`x`.`id_xaphuong` AS `id_xaphuong`,`x`.`ten_xaphuong` AS `ten_xaphuong`,`x`.`id_donvi` AS `id_donvi`,`x`.`ten_donvi` AS `ten_donvi`,concat_ws(' > ',`x`.`ten_quocgia`,`x`.`ten_tinhthanh`,`x`.`ten_quanhuyen`,`x`.`ten_xaphuong`,`x`.`ten_donvi`) AS `duong_dan_khuvuc`,`x`.`diachi` AS `diachi`,`x`.`diachi_chitiet` AS `diachi_chitiet`,`x`.`succhua` AS `succhua`,`x`.`kinhdo` AS `kinhdo`,`x`.`vido` AS `vido`,`x`.`sdt_lienhe` AS `sdt_lienhe`,`x`.`nguoi_lienhe` AS `nguoi_lienhe`,`x`.`email_lienhe` AS `email_lienhe`,`x`.`mota` AS `mota`,`x`.`trangthai` AS `trangthai`,`x`.`ngaytao` AS `ngaytao`,`x`.`ngaycapnhat` AS `ngaycapnhat` from (select `vt`.`idvitrithidau` AS `idvitrithidau`,`vt`.`tenvitrithidau` AS `tenvitrithidau`,`vt`.`loaihinh` AS `loaihinh`,`vt`.`idkhuvuc` AS `idkhuvuc`,`vt`.`diachi` AS `diachi`,`vt`.`diachi_chitiet` AS `diachi_chitiet`,`vt`.`succhua` AS `succhua`,`vt`.`kinhdo` AS `kinhdo`,`vt`.`vido` AS `vido`,`vt`.`sdt_lienhe` AS `sdt_lienhe`,`vt`.`nguoi_lienhe` AS `nguoi_lienhe`,`vt`.`email_lienhe` AS `email_lienhe`,`vt`.`mota` AS `mota`,`vt`.`trangthai` AS `trangthai`,`vt`.`ngaytao` AS `ngaytao`,`vt`.`ngaycapnhat` AS `ngaycapnhat`,`kv0`.`makhuvuc` AS `makhuvuc`,`kv0`.`tenkhuvuc` AS `tenkhuvuc`,`kv0`.`capkhuvuc` AS `capkhuvuc`,coalesce(case when `kv0`.`capkhuvuc` = 'QUOC_GIA' then `kv0`.`idkhuvuc` end,case when `kv1`.`capkhuvuc` = 'QUOC_GIA' then `kv1`.`idkhuvuc` end,case when `kv2`.`capkhuvuc` = 'QUOC_GIA' then `kv2`.`idkhuvuc` end,case when `kv3`.`capkhuvuc` = 'QUOC_GIA' then `kv3`.`idkhuvuc` end,case when `kv4`.`capkhuvuc` = 'QUOC_GIA' then `kv4`.`idkhuvuc` end) AS `id_quocgia`,coalesce(case when `kv0`.`capkhuvuc` = 'QUOC_GIA' then `kv0`.`tenkhuvuc` end,case when `kv1`.`capkhuvuc` = 'QUOC_GIA' then `kv1`.`tenkhuvuc` end,case when `kv2`.`capkhuvuc` = 'QUOC_GIA' then `kv2`.`tenkhuvuc` end,case when `kv3`.`capkhuvuc` = 'QUOC_GIA' then `kv3`.`tenkhuvuc` end,case when `kv4`.`capkhuvuc` = 'QUOC_GIA' then `kv4`.`tenkhuvuc` end) AS `ten_quocgia`,coalesce(case when `kv0`.`capkhuvuc` = 'TINH_THANH' then `kv0`.`idkhuvuc` end,case when `kv1`.`capkhuvuc` = 'TINH_THANH' then `kv1`.`idkhuvuc` end,case when `kv2`.`capkhuvuc` = 'TINH_THANH' then `kv2`.`idkhuvuc` end,case when `kv3`.`capkhuvuc` = 'TINH_THANH' then `kv3`.`idkhuvuc` end,case when `kv4`.`capkhuvuc` = 'TINH_THANH' then `kv4`.`idkhuvuc` end) AS `id_tinhthanh`,coalesce(case when `kv0`.`capkhuvuc` = 'TINH_THANH' then `kv0`.`tenkhuvuc` end,case when `kv1`.`capkhuvuc` = 'TINH_THANH' then `kv1`.`tenkhuvuc` end,case when `kv2`.`capkhuvuc` = 'TINH_THANH' then `kv2`.`tenkhuvuc` end,case when `kv3`.`capkhuvuc` = 'TINH_THANH' then `kv3`.`tenkhuvuc` end,case when `kv4`.`capkhuvuc` = 'TINH_THANH' then `kv4`.`tenkhuvuc` end) AS `ten_tinhthanh`,coalesce(case when `kv0`.`capkhuvuc` = 'QUAN_HUYEN' then `kv0`.`idkhuvuc` end,case when `kv1`.`capkhuvuc` = 'QUAN_HUYEN' then `kv1`.`idkhuvuc` end,case when `kv2`.`capkhuvuc` = 'QUAN_HUYEN' then `kv2`.`idkhuvuc` end,case when `kv3`.`capkhuvuc` = 'QUAN_HUYEN' then `kv3`.`idkhuvuc` end,case when `kv4`.`capkhuvuc` = 'QUAN_HUYEN' then `kv4`.`idkhuvuc` end) AS `id_quanhuyen`,coalesce(case when `kv0`.`capkhuvuc` = 'QUAN_HUYEN' then `kv0`.`tenkhuvuc` end,case when `kv1`.`capkhuvuc` = 'QUAN_HUYEN' then `kv1`.`tenkhuvuc` end,case when `kv2`.`capkhuvuc` = 'QUAN_HUYEN' then `kv2`.`tenkhuvuc` end,case when `kv3`.`capkhuvuc` = 'QUAN_HUYEN' then `kv3`.`tenkhuvuc` end,case when `kv4`.`capkhuvuc` = 'QUAN_HUYEN' then `kv4`.`tenkhuvuc` end) AS `ten_quanhuyen`,coalesce(case when `kv0`.`capkhuvuc` = 'XA_PHUONG' then `kv0`.`idkhuvuc` end,case when `kv1`.`capkhuvuc` = 'XA_PHUONG' then `kv1`.`idkhuvuc` end,case when `kv2`.`capkhuvuc` = 'XA_PHUONG' then `kv2`.`idkhuvuc` end,case when `kv3`.`capkhuvuc` = 'XA_PHUONG' then `kv3`.`idkhuvuc` end,case when `kv4`.`capkhuvuc` = 'XA_PHUONG' then `kv4`.`idkhuvuc` end) AS `id_xaphuong`,coalesce(case when `kv0`.`capkhuvuc` = 'XA_PHUONG' then `kv0`.`tenkhuvuc` end,case when `kv1`.`capkhuvuc` = 'XA_PHUONG' then `kv1`.`tenkhuvuc` end,case when `kv2`.`capkhuvuc` = 'XA_PHUONG' then `kv2`.`tenkhuvuc` end,case when `kv3`.`capkhuvuc` = 'XA_PHUONG' then `kv3`.`tenkhuvuc` end,case when `kv4`.`capkhuvuc` = 'XA_PHUONG' then `kv4`.`tenkhuvuc` end) AS `ten_xaphuong`,coalesce(case when `kv0`.`capkhuvuc` = 'DON_VI' then `kv0`.`idkhuvuc` end,case when `kv1`.`capkhuvuc` = 'DON_VI' then `kv1`.`idkhuvuc` end,case when `kv2`.`capkhuvuc` = 'DON_VI' then `kv2`.`idkhuvuc` end,case when `kv3`.`capkhuvuc` = 'DON_VI' then `kv3`.`idkhuvuc` end,case when `kv4`.`capkhuvuc` = 'DON_VI' then `kv4`.`idkhuvuc` end) AS `id_donvi`,coalesce(case when `kv0`.`capkhuvuc` = 'DON_VI' then `kv0`.`tenkhuvuc` end,case when `kv1`.`capkhuvuc` = 'DON_VI' then `kv1`.`tenkhuvuc` end,case when `kv2`.`capkhuvuc` = 'DON_VI' then `kv2`.`tenkhuvuc` end,case when `kv3`.`capkhuvuc` = 'DON_VI' then `kv3`.`tenkhuvuc` end,case when `kv4`.`capkhuvuc` = 'DON_VI' then `kv4`.`tenkhuvuc` end) AS `ten_donvi` from (((((`vitrithidau` `vt` join `khuvuc` `kv0` on(`kv0`.`idkhuvuc` = `vt`.`idkhuvuc`)) left join `khuvuc` `kv1` on(`kv1`.`idkhuvuc` = `kv0`.`idkhuvuccha`)) left join `khuvuc` `kv2` on(`kv2`.`idkhuvuc` = `kv1`.`idkhuvuccha`)) left join `khuvuc` `kv3` on(`kv3`.`idkhuvuc` = `kv2`.`idkhuvuccha`)) left join `khuvuc` `kv4` on(`kv4`.`idkhuvuc` = `kv3`.`idkhuvuccha`))) `x` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-30 17:43:57
