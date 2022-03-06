--
-- $Id$
--

--
-- MySQL dump 10.14  Distrib 5.5.56-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: cacti
-- ------------------------------------------------------
-- Server version	5.5.56-MariaDB

/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `lic_application_accounting`
--

DROP TABLE IF EXISTS `lic_application_accounting`;
CREATE TABLE `lic_application_accounting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `application` varchar(40) DEFAULT '',
  `monthly_cost` double DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `application` (`application`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_application_feature_map`
--

DROP TABLE IF EXISTS `lic_application_feature_map`;
CREATE TABLE `lic_application_feature_map` (
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `feature_name` varchar(50) NOT NULL DEFAULT '',
  `user_feature_name` varchar(80) DEFAULT '',
  `application` varchar(40) DEFAULT '',
  `manager_hint` varchar(255) DEFAULT '',
  `critical` tinyint(3) unsigned DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`service_id`,`feature_name`),
  KEY `feature_name` (`feature_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_daily_stats`
--

DROP TABLE IF EXISTS `lic_daily_stats`;
CREATE TABLE `lic_daily_stats` (
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `feature` varchar(50) NOT NULL DEFAULT '',
  `user` varchar(40) NOT NULL DEFAULT '',
  `host` varchar(64) NOT NULL DEFAULT '',
  `action` varchar(20) NOT NULL DEFAULT '',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  `total_license_count` int(10) unsigned NOT NULL DEFAULT '0',
  `utilization` float NOT NULL DEFAULT '0',
  `peak_ut` float NOT NULL DEFAULT '0',
  `vendor` varchar(40) NOT NULL DEFAULT '0',
  `duration` int(10) unsigned NOT NULL DEFAULT '0',
  `transaction_count` int(10) unsigned NOT NULL DEFAULT '0',
  `type` enum('0','1','2','3','4','5','6','7','8') NOT NULL DEFAULT '0',
  `interval_end` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`service_id`,`feature`,`user`,`host`,`action`,`vendor`,`date_recorded`),
  KEY `feature` (`feature`),
  KEY `user` (`user`),
  KEY `host` (`host`),
  KEY `interval_end` (`interval_end`),
  KEY `date_recorded` (`date_recorded`),
  KEY `vendor` (`vendor`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `lic_daily_stats_today`;
CREATE TABLE `lic_daily_stats_today` (
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `feature` varchar(50) NOT NULL DEFAULT '',
  `user` varchar(40) NOT NULL DEFAULT '',
  `host` varchar(64) NOT NULL DEFAULT '',
  `action` varchar(20) NOT NULL DEFAULT '',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  `total_license_count` int(10) unsigned NOT NULL DEFAULT '0',
  `utilization` float NOT NULL DEFAULT '0',
  `peak_ut` float NOT NULL DEFAULT '0',
  `vendor` varchar(40) NOT NULL DEFAULT '0',
  `duration` int(10) unsigned NOT NULL DEFAULT '0',
  `transaction_count` int(10) unsigned NOT NULL DEFAULT '0',
  `type` enum('0','1','2','3','4','5','6','7','8') NOT NULL DEFAULT '0',
  `interval_end` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`service_id`,`feature`,`user`,`host`,`action`,`vendor`),
  KEY `feature` (`feature`),
  KEY `user` (`user`),
  KEY `host` (`host`),
  KEY `interval_end` (`interval_end`),
  KEY `date_recorded` (`date_recorded`),
  KEY `vendor` (`vendor`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Table structure for table `lic_daily_stats_traffic`
--

DROP TABLE IF EXISTS `lic_daily_stats_traffic`;
CREATE TABLE `lic_daily_stats_traffic` (
  `service_id` int(10) unsigned NOT NULL,
  `feature` varchar(50) NOT NULL,
  `user` varchar(40) NOT NULL,
  `host` varchar(64) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_id`,`feature`,`user`,`host`),
  KEY `feature` (`feature`),
  KEY `user` (`user`),
  KEY `host` (`host`),
  KEY `last_updated` (`last_updated`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_flexlm_log`
--

DROP TABLE IF EXISTS `lic_flexlm_log`;
CREATE TABLE `lic_flexlm_log` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `portatserver` int(12) NOT NULL,
  `vendor_daemon` varchar(100) NOT NULL,
  `feature` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `no_of_license_out_in` int(12) NOT NULL DEFAULT '1',
  `user` varchar(200) NOT NULL,
  `host` varchar(200) NOT NULL,
  `reasons` text NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_interval_stats`
--

DROP TABLE IF EXISTS `lic_interval_stats`;

CREATE TABLE `lic_interval_stats` (
  `seq` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `feature` varchar(50) NOT NULL DEFAULT '',
  `user` varchar(40) NOT NULL DEFAULT '',
  `host` varchar(64) NOT NULL DEFAULT '',
  `action` varchar(20) NOT NULL DEFAULT '',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  `total_license_count` int(10) unsigned NOT NULL DEFAULT '0',
  `utilization` float NOT NULL DEFAULT '0',
  `vendor` varchar(20) NOT NULL DEFAULT '0',
  `duration` int(10) unsigned NOT NULL DEFAULT '0',
  `interval_end` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `event_id` int(12) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`seq`),
  KEY `feature` (`feature`),
  KEY `interval_end` (`interval_end`),
  KEY `user` (`user`),
  KEY `host` (`host`),
  KEY `vendor` (`vendor`),
  KEY `event_id` (`event_id`),
  KEY `action_ut` (`action`,`utilization`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_managers`
--

DROP TABLE IF EXISTS `lic_managers`;
CREATE TABLE `lic_managers` (
  `id` smallint(10) unsigned NOT NULL AUTO_INCREMENT,
  `hash` char(32) NOT NULL,
  `name` varchar(20) NOT NULL,
  `description` varchar(127) NOT NULL,
  `type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '0: default, 1: json',
  `logparser_binary` varchar(127) NOT NULL,
  `collector_binary` varchar(127) NOT NULL,
  `lm_client` varchar(127) NOT NULL,
  `lm_client_arg1` varchar(127) NOT NULL,
  `failover_hosts` tinyint(3) unsigned DEFAULT '1',
  `disabled` tinyint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `lic_managers`
--

LOCK TABLES `lic_managers` WRITE;
/*!40000 ALTER TABLE `lic_managers` DISABLE KEYS */;
INSERT INTO `lic_managers` (`id`, `hash`, `name`, `description`, `type`, `logparser_binary`, `collector_binary`, `lm_client`, `lm_client_arg1`, `failover_hosts`, `disabled`) VALUES
(1,'9190583ab345af80da86efd5d683eb72','FLEXlm','Flexera License Manager',0,'','licflexpoller','lmstat','',3,0),
(2,'db3168fc7be049ea84efe3cebd189cf0','LUM','LUM License Manager',0,'','liclumpoller','i4blt','',3,1),
(3,'33fb6598074464bc113893507c623ee0','RLM','Reprise License Manager',0,'','liclmpoller','rlmstat','',2,0);
/*!40000 ALTER TABLE `lic_managers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_pollers`
--

DROP TABLE IF EXISTS `lic_pollers`;
CREATE TABLE `lic_pollers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `poller_path` varchar(100) NOT NULL,
  `client_path` varchar(100) NOT NULL,
  `poller_description` varchar(100) NOT NULL,
  `poller_hostname` varchar(64) NOT NULL,
  `poller_exechost` varchar(64) NOT NULL DEFAULT '',
  `poller_type` smallint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `lic_pollers`
--

LOCK TABLES `lic_pollers` WRITE;
/*!40000 ALTER TABLE `lic_pollers` DISABLE KEYS */;
INSERT INTO `lic_pollers` (`id`, `poller_path`, `client_path`, `poller_description`, `poller_hostname`, `poller_exechost`, `poller_type`) VALUES
(1,'/opt/IBM/rtm/lic/bin','/opt/IBM/flexlm/bin/','FLEXlm poller','local','',1),
(2,'/opt/IBM/rtm/lic/bin','/opt/IBM/rlm/bin/','RLM poller','local','',3);
/*!40000 ALTER TABLE `lic_pollers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_servers`
--

DROP TABLE IF EXISTS `lic_servers`;
CREATE TABLE `lic_servers` (
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(64) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT '',
  `type` varchar(50) NOT NULL DEFAULT '',
  `version` varchar(20) NOT NULL DEFAULT '',
  `errorno` int(10) NOT NULL DEFAULT '0',
  `present` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`service_id`,`name`),
  KEY `name` (`name`)
) ENGINE=MEMORY DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_services`
--

DROP TABLE IF EXISTS `lic_services`;
CREATE TABLE `lic_services` (
  `service_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `poller_interval` int(10) unsigned NOT NULL DEFAULT '300',
  `poller_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `poller_id` int(10) unsigned NOT NULL,
  `poller_trigger` int(11) NOT NULL DEFAULT '0',
  `server_portatserver` varchar(512) NOT NULL DEFAULT '',
  `server_timezone` varchar(64) NOT NULL,
  `server_name` varchar(256) NOT NULL DEFAULT '',
  `server_vendor` varchar(60) NOT NULL DEFAULT '',
  `server_licensetype` varchar(20) DEFAULT NULL,
  `server_licensefile` varchar(255) NOT NULL DEFAULT '',
  `server_department` varchar(45) DEFAULT NULL,
  `server_location` varchar(100) NOT NULL DEFAULT '',
  `server_region` varchar(100) NOT NULL DEFAULT '',
  `server_support_info` varchar(255) NOT NULL DEFAULT '',
  `enable_checkouts` varchar(20) NOT NULL DEFAULT '',
  `timeout` int(10) unsigned NOT NULL DEFAULT '1',
  `errorno` int(10) NOT NULL DEFAULT '0',
  `disabled` varchar(2) NOT NULL DEFAULT '',
  `options_path` varchar(2048) NOT NULL DEFAULT '',
  `retries` int(10) unsigned NOT NULL DEFAULT '3',
  `status` int(10) unsigned NOT NULL DEFAULT '0',
  `status_event_count` int(10) unsigned NOT NULL DEFAULT '0',
  `cur_time` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `min_time` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `max_time` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `avg_time` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `total_polls` int(10) unsigned NOT NULL DEFAULT '0',
  `failed_polls` int(10) unsigned NOT NULL DEFAULT '0',
  `status_fail_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `status_rec_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `availability` decimal(8,5) NOT NULL DEFAULT '0.00000',
  `server_subisv` varchar(40) NOT NULL DEFAULT '',
  `file_path` varchar(255) NOT NULL DEFAULT '',
  `prefix` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`service_id`),
  KEY `server_location` (`server_location`),
  KEY `server_region` (`server_region`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_services_feature`
--

DROP TABLE IF EXISTS `lic_services_feature`;
CREATE TABLE `lic_services_feature` (
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `feature_name` varchar(50) NOT NULL DEFAULT '',
  `feature_version` varchar(20) NOT NULL DEFAULT '',
  `feature_number_to_expire` int(10) unsigned NOT NULL DEFAULT '0',
  `total_reserved_token` int(10) unsigned NOT NULL DEFAULT '0',
  `feature_expiration_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `vendor_daemon` varchar(45) NOT NULL DEFAULT '',
  `present` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`service_id`,`feature_name`,`feature_version`,`vendor_daemon`,`feature_expiration_date`) USING HASH,
  KEY `feature_name` (`feature_name`),
  KEY `feature_version` (`feature_version`),
  KEY `vendor_daemon` (`vendor_daemon`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_services_feature_details`
--

DROP TABLE IF EXISTS `lic_services_feature_details`;
CREATE TABLE `lic_services_feature_details` (
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `vendor_daemon` varchar(40) NOT NULL DEFAULT '',
  `feature_name` varchar(50) NOT NULL DEFAULT '0',
  `subfeature` varchar(50) NOT NULL DEFAULT '',
  `feature_version` varchar(50) NOT NULL DEFAULT '',
  `username` varchar(40) NOT NULL DEFAULT '',
  `groupname` varchar(50) NOT NULL DEFAULT '',
  `hostname` varchar(64) NOT NULL DEFAULT '',
  `chkoutid` varchar(20) NOT NULL DEFAULT '',
  `restype` int(10) unsigned NOT NULL DEFAULT '0',
  `status` varchar(20) NOT NULL DEFAULT '',
  `tokens_acquired` int(10) unsigned NOT NULL DEFAULT '0',
  `tokens_acquired_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `present` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`service_id`,`vendor_daemon`,`feature_name`,`username`,`groupname`,`hostname`,`chkoutid`,`restype`,`status`,`tokens_acquired_date`),
  KEY `idx_vendor_daemon` (`vendor_daemon`),
  KEY `idx_feature_name` (`feature_name`),
  KEY `idx_username` (`username`),
  KEY `idx_hostname` (`hostname`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_services_feature_use`
--

DROP TABLE IF EXISTS `lic_services_feature_use`;
CREATE TABLE `lic_services_feature_use` (
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `feature_name` varchar(50) NOT NULL DEFAULT '',
  `feature_max_licenses` int(10) unsigned NOT NULL DEFAULT '0',
  `feature_inuse_licenses` int(10) unsigned NOT NULL DEFAULT '0',
  `feature_queued` int(10) unsigned NOT NULL DEFAULT '0',
  `feature_reserved` int(10) unsigned NOT NULL DEFAULT '0',
  `vendor_daemon` varchar(45) NOT NULL DEFAULT 'TBD',
  `present` tinyint(1) NOT NULL DEFAULT '1',
  `vendor_status` varchar(10) NOT NULL DEFAULT '',
  `vendor_version` varchar(30) NOT NULL DEFAULT '',
  `status` varchar(29) NOT NULL DEFAULT '',
  PRIMARY KEY (`feature_name`,`service_id`),
  KEY `service_id` (`service_id`),
  KEY `vendor_daemon` (`vendor_daemon`),
  KEY `feature_queued` (`feature_queued`),
  KEY `feature_reserved` (`feature_reserved`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_services_options_feature`
--

DROP TABLE IF EXISTS `lic_services_options_feature`;
CREATE TABLE `lic_services_options_feature` (
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `feature` varchar(50) NOT NULL DEFAULT '',
  `keyword` varchar(60) NOT NULL,
  `borrow_lowwater` int(10) unsigned DEFAULT NULL,
  `linger` int(10) unsigned DEFAULT NULL,
  `max_borrow_hours` int(10) unsigned DEFAULT NULL,
  `max_overdraft` int(10) unsigned DEFAULT NULL,
  `timeout` int(10) unsigned DEFAULT NULL,
  `present` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`service_id`,`feature`,`keyword`)
) ENGINE=MyISAM COMMENT='Includes Feature Options' DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_services_options_feature_type`
--

DROP TABLE IF EXISTS `lic_services_options_feature_type`;
CREATE TABLE `lic_services_options_feature_type` (
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `feature` varchar(50) NOT NULL DEFAULT '',
  `keyword` varchar(60) NOT NULL,
  `variable` varchar(20) NOT NULL DEFAULT '',
  `otype` varchar(20) NOT NULL DEFAULT '',
  `name` varchar(40) NOT NULL DEFAULT '',
  `notes` varchar(255) DEFAULT NULL,
  `present` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`service_id`,`feature`,`variable`,`otype`,`name`,`keyword`)
) ENGINE=MyISAM COMMENT='Per Feature/Type Options' DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_services_options_incexcl_all`
--

DROP TABLE IF EXISTS `lic_services_options_incexcl_all`;
CREATE TABLE `lic_services_options_incexcl_all` (
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `incexcl` varchar(12) NOT NULL DEFAULT '',
  `otype` varchar(20) NOT NULL DEFAULT '',
  `name` varchar(40) NOT NULL DEFAULT '',
  `notes` varchar(255) DEFAULT NULL,
  `present` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`service_id`,`incexcl`,`otype`,`name`),
  KEY `incexcl` (`incexcl`),
  KEY `otype` (`otype`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_services_options_global`
--

DROP TABLE IF EXISTS `lic_services_options_global`;
CREATE TABLE `lic_services_options_global` (
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `options_path` varchar(255) NOT NULL DEFAULT '',
  `debug_path` varchar(255) DEFAULT NULL,
  `report_path` varchar(255) DEFAULT NULL,
  `nolog_in` int(10) unsigned DEFAULT NULL,
  `nolog_out` int(10) unsigned DEFAULT NULL,
  `nolog_denied` int(10) unsigned DEFAULT NULL,
  `nolog_queued` int(10) unsigned DEFAULT NULL,
  `timeoutall` int(10) unsigned DEFAULT NULL,
  `groupcaseinsens` int(10) unsigned DEFAULT NULL,
  `present` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`service_id`,`options_path`(191))
) ENGINE=MyISAM COMMENT='Contains Per Options File Global Settings' DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_services_options_host_groups`
--

DROP TABLE IF EXISTS `lic_services_options_host_groups`;
CREATE TABLE `lic_services_options_host_groups` (
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `group` varchar(64) NOT NULL DEFAULT '',
  `host` varchar(64) NOT NULL DEFAULT '',
  `present` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`service_id`,`group`,`host`),
  KEY `group` (`group`),
  KEY `host` (`host`)
) ENGINE=MyISAM COMMENT='Shows Host Group in FLEXlm' DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_services_options_max`
--

DROP TABLE IF EXISTS `lic_services_options_max`;
CREATE TABLE `lic_services_options_max` (
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `num_lic` int(10) unsigned NOT NULL DEFAULT '0',
  `feature` varchar(50) NOT NULL DEFAULT '',
  `keyword` varchar(60) NOT NULL,
  `otype` varchar(10) NOT NULL DEFAULT '',
  `name` varchar(40) NOT NULL DEFAULT '',
  `notes` varchar(255) DEFAULT NULL,
  `present` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`service_id`,`feature`,`otype`,`name`,`keyword`),
  KEY `feature` (`feature`),
  KEY `name` (`name`),
  KEY `otype_name` (`otype`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_services_options_reserve`
--

DROP TABLE IF EXISTS `lic_services_options_reserve`;
CREATE TABLE `lic_services_options_reserve` (
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `num_lic` int(10) unsigned NOT NULL DEFAULT '0',
  `feature` varchar(50) NOT NULL DEFAULT '',
  `keyword` varchar(60) NOT NULL,
  `otype` varchar(10) NOT NULL DEFAULT '',
  `name` varchar(40) NOT NULL DEFAULT '',
  `notes` varchar(255) DEFAULT NULL,
  `present` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`service_id`,`feature`,`otype`,`name`,`keyword`),
  KEY `feature` (`feature`),
  KEY `name` (`name`),
  KEY `otype` (`otype`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_services_options_user_groups`
--

DROP TABLE IF EXISTS `lic_services_options_user_groups`;
CREATE TABLE `lic_services_options_user_groups` (
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `group` varchar(64) NOT NULL DEFAULT '',
  `user` varchar(40) NOT NULL DEFAULT '',
  `present` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`service_id`,`group`,`user`),
  KEY `group` (`group`),
  KEY `user` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS lic_settings LIKE grid_settings;

--
-- Table structure for table `lic_ip_ranges`
--

DROP TABLE IF EXISTS `lic_ip_ranges`;
CREATE TABLE `lic_ip_ranges` (
  `ip_range` varchar(16) NOT NULL,
  `hostname` varchar(64) NOT NULL,
  `ip_address` varchar(16) NOT NULL,
  `present` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`ip_range`,`hostname`)
) ENGINE=MyISAM COMMENT='Stores IP range and host membership from DNS' DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_ldap_to_flex_groups`
--

DROP TABLE IF EXISTS `lic_ldap_to_flex_groups`;
CREATE TABLE `lic_ldap_to_flex_groups` (
  `ldap_group` varchar(40) NOT NULL,
  `flex_group` varchar(40) NOT NULL,
  `present` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`ldap_group`,`flex_group`)
) ENGINE=MyISAM COMMENT='A Mapping Table of LDAP Groups to FLEXlm Group' DEFAULT CHARSET=latin1;

--
-- Table structure for table `lic_ldap_groups`
--

DROP TABLE IF EXISTS `lic_ldap_groups`;
CREATE TABLE `lic_ldap_groups` (
  `group` varchar(40) NOT NULL,
  `user` varchar(20) NOT NULL,
  `present` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`group`,`user`)
) ENGINE=MyISAM COMMENT='Stores LDAP Group Information' DEFAULT CHARSET=latin1;


--
-- Table structure for table `lic_users_winsp`
--

DROP TABLE IF EXISTS `lic_users_winsp`;
CREATE TABLE `lic_users_winsp` (
  `user` varchar(40) NOT NULL DEFAULT '',
  PRIMARY KEY (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Final view structure for view `lic_flexlm_servers_feature_details`
--

DROP TABLE IF EXISTS `lic_flexlm_servers_feature_details`;
DROP VIEW IF EXISTS `lic_flexlm_servers_feature_details`;
CREATE VIEW `lic_flexlm_servers_feature_details` AS select `lic_services`.`poller_id` AS `poller_id`,`lic_services_feature_details`.`service_id` AS `portatserver_id`,`lic_services_feature_details`.`vendor_daemon` AS `vendor_daemon`,`lic_services_feature_details`.`feature_name` AS `feature_name`,`lic_services_feature_details`.`subfeature` AS `subfeature`,`lic_services_feature_details`.`feature_version` AS `feature_version`,`lic_services_feature_details`.`username` AS `username`,`lic_services_feature_details`.`groupname` AS `groupname`,`lic_services_feature_details`.`hostname` AS `hostname`,`lic_services_feature_details`.`chkoutid` AS `chkoutid`,`lic_services_feature_details`.`restype` AS `restype`,`lic_services_feature_details`.`status` AS `status`,`lic_services_feature_details`.`tokens_acquired` AS `tokens_acquired`,`lic_services_feature_details`.`tokens_acquired_date` AS `tokens_acquired_date`,`lic_services_feature_details`.`last_updated` AS `last_updated`,`lic_services_feature_details`.`present` AS `present` from (`lic_services_feature_details` join `lic_services` on((`lic_services_feature_details`.`service_id` = `lic_services`.`service_id`)));

--
-- Final view structure for view `lic_flexlm_servers_feature_use`
--

DROP TABLE IF EXISTS `lic_flexlm_servers_feature_use`;
DROP VIEW IF EXISTS `lic_flexlm_servers_feature_use`;
CREATE VIEW `lic_flexlm_servers_feature_use` AS select `lic_services`.`poller_id` AS `poller_id`,`lic_services_feature_use`.`service_id` AS `portatserver_id`,`lic_services_feature_use`.`feature_name` AS `feature_name`,`lic_services_feature_use`.`feature_max_licenses` AS `feature_max_licenses`,`lic_services_feature_use`.`feature_inuse_licenses` AS `feature_inuse_licenses`,`lic_services_feature_use`.`feature_queued` AS `feature_queued`,`lic_services_feature_use`.`feature_reserved` AS `feature_reserved`,`lic_services_feature_use`.`vendor_daemon` AS `vendor_daemon`,`lic_services_feature_use`.`present` AS `present`,`lic_services_feature_use`.`vendor_status` AS `vendor_status`,`lic_services_feature_use`.`vendor_version` AS `vendor_version`,`lic_services_feature_use`.`status` AS `status` from (`lic_services_feature_use` join `lic_services` on((`lic_services_feature_use`.`service_id` = `lic_services`.`service_id`)));

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-12-18 19:08:30
