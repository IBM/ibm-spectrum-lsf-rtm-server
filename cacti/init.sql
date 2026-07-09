--
-- $Id$
--

--
-- Initial Cacti data for fresh installation to cover Cacti Install Wizard result.
--


LOCK TABLES `data_template` WRITE;
/*!40000 ALTER TABLE `data_template` DISABLE KEYS */;
INSERT INTO `data_template` VALUES
(1,'9b8c92d3c32703900ff7dd653bfc9cd8','Unix - Processes'),
(2,'9e72511e127de200733eb502eb818e1d','Unix - Load Average'),
(3,'c221c2164c585b6da378013a7a6a2c13','Unix - Logged in Users'),
(4,'dc33aa9a8e71fb7c61ec0e7a6da074aa','Linux - Memory - Free'),
(5,'41f55087d067142d702dd3c73c98f020','Linux - Memory - Free Swap'),
(6,'e4ac6919d4f6f21ec5b281a1d6ac4d4e','Unix - Hard Drive Space');
/*!40000 ALTER TABLE `data_template` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `data_template_data` WRITE;
/*!40000 ALTER TABLE `data_template_data` DISABLE KEYS */;
INSERT INTO `data_template_data` VALUES
(1,0,0,1,7,'','|host_description| - Processes','',NULL,'','on','',300,'',1),
(2,0,0,2,4,'','|host_description| - Load Average','',NULL,'','on','',300,'',1),
(3,0,0,3,5,'','|host_description| - Logged in Users','',NULL,'','on','',300,'',1),
(4,0,0,4,6,'','|host_description| - Memory - Free','',NULL,'','on','',300,'',1),
(5,0,0,5,6,'','|host_description| - Memory - Free Swap','',NULL,'','on','',300,'',1),
(6,0,0,6,11,'on','|host_description| - Hard Drive Space','',NULL,'','on','',300,'',1);
/*!40000 ALTER TABLE `data_template_data` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `data_template_rrd` WRITE;
/*!40000 ALTER TABLE `data_template_rrd` DISABLE KEYS */;
INSERT INTO `data_template_rrd` VALUES
(1,'f1ba3a5b17b95825021241398bb0f277',0,0,1,'','1000','','0','',600,'',1,'','proc','',24),
(2,'8175ca431c8fe50efff5a1d3ae51b55d',0,0,2,'','500','','0','',600,'',1,'','load_1min','',17),
(3,'a2eeb8acd6ea01cd0e3ac852965c0eb6',0,0,2,'','500','','0','',600,'',1,'','load_5min','',18),
(4,'9f951b7fb3b19285a411aebb5254a831',0,0,2,'','500','','0','',600,'',1,'','load_15min','',19),
(5,'46a5afe8e6c0419172c76421dc9e304a',0,0,3,'','500','','0','',600,'',1,'','users','',21),
(6,'a4df3de5238d3beabee1a2fe140d3d80',0,0,4,'','0','','0','',600,'',1,'','mem_buffers','',23),
(7,'7fea6acc9b1a19484b4cb4cef2b6c5da',0,0,5,'','0','','0','',600,'',1,'','mem_swap','',23),
(8,'4c82df790325d789d304e6ee5cd4ab7d',0,0,6,'','0','','0','',600,'',1,'','hdd_free','',0),
(9,'07175541991def89bd02d28a215f6fcc',0,0,6,'','0','','0','',600,'',1,'','hdd_used','',0);
/*!40000 ALTER TABLE `data_template_rrd` ENABLE KEYS */;
UNLOCK TABLES;


LOCK TABLES `graph_template_input` WRITE;
/*!40000 ALTER TABLE `graph_template_input` DISABLE KEYS */;
INSERT INTO `graph_template_input` VALUES
(1,'592cedd465877bc61ab549df688b0b2a',1,'Processes Data Source','','task_item_id'),
(2,'1d51dbabb200fcea5c4b157129a75410',1,'Legend Color','','color_id'),
(3,'8cb8ed3378abec21a1819ea52dfee6a3',2,'1 Minute Data Source','','task_item_id'),
(4,'5dfcaf9fd771deb8c5430bce1562e371',2,'5 Minute Data Source','','task_item_id'),
(5,'6f3cc610315ee58bc8e0b1f272466324',2,'15 Minute Data Source','','task_item_id'),
(6,'b457a982bf46c6760e6ef5f5d06d41fb',3,'Logged in Users Data Source','','task_item_id'),
(7,'bd4a57adf93c884815b25a8036b67f98',3,'Legend Color','','color_id'),
(8,'6273c71cdb7ed4ac525cdbcf6180918c',4,'Free Data Source','','task_item_id'),
(9,'5e62dbea1db699f1bda04c5863e7864d',4,'Swap Data Source','','task_item_id'),
(10,'940beb0f0344e37f4c6cdfc17d2060bc',5,'Available Disk Space Data Source','','task_item_id'),
(11,'7b0674dd447a9badf0d11bec688028a8',5,'Used Disk Space Data Source','','task_item_id');
/*!40000 ALTER TABLE `graph_template_input` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `graph_template_input_defs` WRITE;
/*!40000 ALTER TABLE `graph_template_input_defs` DISABLE KEYS */;
INSERT INTO `graph_template_input_defs` VALUES
(1,1),
(1,2),
(1,3),
(1,4),
(2,1),
(3,5),
(3,6),
(4,7),
(4,8),
(5,9),
(5,10),
(6,12),
(6,13),
(6,14),
(6,15),
(7,12),
(8,16),
(8,17),
(8,18),
(8,19),
(9,20),
(9,21),
(9,22),
(9,23),
(10,28),
(10,29),
(10,30),
(10,31),
(11,24),
(11,25),
(11,26),
(11,27);
/*!40000 ALTER TABLE `graph_template_input_defs` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `graph_templates` WRITE;
/*!40000 ALTER TABLE `graph_templates` DISABLE KEYS */;
INSERT INTO `graph_templates` (`id`, `hash`, `name`, `multiple`) VALUES
(1,'9fe8b4da353689d376b99b2ea526cc6b','Unix - Processes',''),
(2,'fe5edd777a76d48fc48c11aded5211ef','Unix - Load Average',''),
(3,'63610139d44d52b195cc375636653ebd','Unix - Logged in Users',''),
(4,'6992ed4df4b44f3d5595386b8298f0ec','Linux - Memory Usage',''),
(5,'8e7c8a511652fe4a8e65c69f3d34779d','Unix - Available Disk Space','');
/*!40000 ALTER TABLE `graph_templates` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `graph_templates_gprint` WRITE;
/*!40000 ALTER TABLE `graph_templates_gprint` DISABLE KEYS */;
INSERT INTO `graph_templates_gprint` VALUES
(5,'4e72328b57f5f2bb08a83a477b6cd10c','Percent','%8.1lf %%');
/*!40000 ALTER TABLE `graph_templates_gprint` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `graph_templates_graph` WRITE;
/*!40000 ALTER TABLE `graph_templates_graph` DISABLE KEYS */;
INSERT INTO `graph_templates_graph` VALUES
(1,0,0,1,'',1,'','|host_description| - Processes','','',120,'',500,'','100','','0','','processes','','on','','on','',2,'','','','','','','','on','',1000,'','','','','','','',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'','30','',NULL,'',NULL,'',NULL,'',NULL),
(2,0,0,2,'',1,'','|host_description| - Load Average','','',120,'',500,'','100','','0','','processes in the run queue','','on','','on','',2,'','','','','','on','','on','',1000,'','','','','','','',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'','30','',NULL,'',NULL,'',NULL,'',NULL),
(3,0,0,3,'',1,'','|host_description| - Logged in Users','','',120,'',500,'','100','','0','','users','','on','','on','',2,'','','','','','on','','on','',1000,'','','','','','','',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'','30','',NULL,'',NULL,'',NULL,'',NULL),
(4,0,0,4,'',1,'','|host_description| - Memory Usage','','',120,'',500,'','100','','0','','bytes','','on','','on','',2,'','','','','','on','','on','',1000,'','','','','','','',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'','30','',NULL,'',NULL,'',NULL,'',NULL),
(5,0,0,5,'',1,'on','|host_description| - Available Disk Space','','',120,'',500,'','100','','0','','bytes','','on','','on','',2,'','','','','','on','','on','',1024,'','','','','','','',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'',NULL,'','30','',NULL,'',NULL,'',NULL,'',NULL);
/*!40000 ALTER TABLE `graph_templates_graph` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `graph_templates_item` WRITE;
/*!40000 ALTER TABLE `graph_templates_item` DISABLE KEYS */;
INSERT INTO `graph_templates_item` VALUES
(1,'ba00ecd28b9774348322ff70a96f2826',0,0,1,1,48,'FF',7,0.00,NULL,NULL,0,0,NULL,1,NULL,'Running Processes','','',2,1),
(2,'8d76de808efd73c51e9a9cbd70579512',0,0,1,1,0,'FF',9,0.00,NULL,NULL,0,0,NULL,4,NULL,'Current:','','',3,2),
(3,'304244ca63d5b09e62a94c8ec6fbda8d',0,0,1,1,0,'FF',9,0.00,NULL,NULL,0,0,NULL,1,NULL,'Average:','','',3,3),
(4,'da1ba71a93d2ed4a2a00d54592b14157',0,0,1,1,0,'FF',9,0.00,NULL,NULL,0,0,NULL,3,NULL,'Maximum:','','on',3,4),
(5,'93ad2f2803b5edace85d86896620b9da',0,0,2,2,15,'FF',7,0.00,NULL,NULL,0,0,NULL,1,NULL,'1 Minute Average','','',2,1),
(6,'e28736bf63d3a3bda03ea9f1e6ecb0f1',0,0,2,2,0,'FF',9,0.00,NULL,NULL,0,0,NULL,4,NULL,'Current:','','on',4,2),
(7,'bbdfa13adc00398eed132b1ccb4337d2',0,0,2,3,8,'FF',8,0.00,NULL,NULL,0,0,NULL,1,NULL,'5 Minute Average','','',2,3),
(8,'2c14062c7d67712f16adde06132675d6',0,0,2,3,0,'FF',9,0.00,NULL,NULL,0,0,NULL,4,NULL,'Current:','','on',4,4),
(9,'9cf6ed48a6a54b9644a1de8c9929bd4e',0,0,2,4,9,'FF',8,0.00,NULL,NULL,0,0,NULL,1,NULL,'15 Minute Average','','',2,5),
(10,'c9824064305b797f38feaeed2352e0e5',0,0,2,4,0,'FF',9,0.00,NULL,NULL,0,0,NULL,4,NULL,'Current:','','on',4,6),
(11,'fa1bc4eff128c4da70f5247d55b8a444',0,0,2,0,1,'FF',4,0.00,NULL,NULL,12,0,NULL,1,NULL,'','','',2,7),
(12,'5c94ac24bc0d6d2712cc028fa7d4c7d2',0,0,3,5,67,'FF',7,0.00,NULL,NULL,0,0,NULL,1,NULL,'Users','','',2,1),
(13,'8bc7f905526f62df7d5c2d8c27c143c1',0,0,3,5,0,'FF',9,0.00,NULL,NULL,0,0,NULL,4,NULL,'Current:','','',3,2),
(14,'cd074cd2b920aab70d480c020276d45b',0,0,3,5,0,'FF',9,0.00,NULL,NULL,0,0,NULL,1,NULL,'Average:','','',3,3),
(15,'415630f25f5384ba0c82adbdb05fe98b',0,0,3,5,0,'FF',9,0.00,NULL,NULL,0,0,NULL,3,NULL,'Maximum:','','on',3,4),
(16,'5fa7c2317f19440b757ab2ea1cae6abc',0,0,4,6,41,'FF',7,0.00,NULL,NULL,14,0,NULL,1,NULL,'Free','','',2,9),
(17,'b1d18060bfd3f68e812c508ff4ac94ed',0,0,4,6,0,'FF',9,0.00,NULL,NULL,14,0,NULL,4,NULL,'Current:','','',2,10),
(18,'780b6f0850aaf9431d1c246c55143061',0,0,4,6,0,'FF',9,0.00,NULL,NULL,14,0,NULL,1,NULL,'Average:','','',2,11),
(19,'2d54a7e7bb45e6c52d97a09e24b7fba7',0,0,4,6,0,'FF',9,0.00,NULL,NULL,14,0,NULL,3,NULL,'Maximum:','','on',2,12),
(20,'40206367a3c192b836539f49801a0b15',0,0,4,7,30,'FF',8,0.00,NULL,NULL,14,0,NULL,1,NULL,'Swap','','',2,13),
(21,'7ee72e2bb3722d4f8a7f9c564e0dd0d0',0,0,4,7,0,'FF',9,0.00,NULL,NULL,14,0,NULL,4,NULL,'Current:','','',2,14),
(22,'c8af33b949e8f47133ee25e63c91d4d0',0,0,4,7,0,'FF',9,0.00,NULL,NULL,14,0,NULL,1,NULL,'Average:','','',2,15),
(23,'568128a16723d1195ce6a234d353ce00',0,0,4,7,0,'FF',9,0.00,NULL,NULL,14,0,NULL,3,NULL,'Maximum:','','on',2,16),
(24,'6ca2161c37b0118786dbdb46ad767e5d',0,0,5,9,48,'FF',7,0.00,NULL,NULL,14,0,NULL,1,NULL,'Used','','',2,1),
(25,'8ef3e7fb7ce962183f489725939ea40f',0,0,5,9,0,'FF',9,0.00,NULL,NULL,14,0,NULL,4,NULL,'Current:','','',2,2),
(26,'3b13eb2e542fe006c9bf86947a6854fa',0,0,5,9,0,'FF',9,0.00,NULL,NULL,14,0,NULL,1,NULL,'Average:','','',2,3),
(27,'a751838f87068e073b95be9555c57bde',0,0,5,9,0,'FF',9,0.00,NULL,NULL,14,0,NULL,3,NULL,'Maximum:','','on',2,4),
(28,'5d6dff9c14c71dc1ebf83e87f1c25695',0,0,5,8,20,'FF',8,0.00,NULL,NULL,14,0,NULL,1,NULL,'Available','','',2,5),
(29,'b27cb9a158187d29d17abddc6fdf0f15',0,0,5,8,0,'FF',9,0.00,NULL,NULL,14,0,NULL,4,NULL,'Current:','','',2,6),
(30,'6c0555013bb9b964e51d22f108dae9b0',0,0,5,8,0,'FF',9,0.00,NULL,NULL,14,0,NULL,1,NULL,'Average:','','',2,7),
(31,'42ce58ec17ef5199145fbf9c6ee39869',0,0,5,8,0,'FF',9,0.00,NULL,NULL,14,0,NULL,3,NULL,'Maximum:','','on',2,8),
(32,'9bdff98f2394f666deea028cbca685f3',0,0,5,0,1,'FF',5,0.00,NULL,NULL,15,0,NULL,1,NULL,'Total','','',2,9),
(33,'fb831fefcf602bc31d9d24e8e456c2e6',0,0,5,0,0,'FF',9,0.00,NULL,NULL,15,0,NULL,4,NULL,'Current:','','',2,10),
(34,'5a958d56785a606c08200ef8dbf8deef',0,0,5,0,0,'FF',9,0.00,NULL,NULL,15,0,NULL,1,NULL,'Average:','','',2,11),
(35,'5ce67a658cec37f526dc84ac9e08d6e7',0,0,5,0,0,'FF',9,0.00,NULL,NULL,15,0,NULL,3,NULL,'Maximum:','','on',2,12);
/*!40000 ALTER TABLE `graph_templates_item` ENABLE KEYS */;
UNLOCK TABLES;


LOCK TABLES `host` WRITE;
/*!40000 ALTER TABLE `host` DISABLE KEYS */;
INSERT INTO `host` (`id`, `poller_id`, `site_id`, `host_template_id`, `description`, `hostname`, `location`, `notes`, `external_id`, `snmp_community`, `snmp_version`, `snmp_username`, `snmp_password`, `snmp_auth_protocol`, `snmp_priv_passphrase`, `snmp_priv_protocol`, `snmp_context`, `snmp_engine_id`, `snmp_port`, `snmp_timeout`, `snmp_sysDescr`, `snmp_sysObjectID`, `snmp_sysUpTimeInstance`, `snmp_sysContact`, `snmp_sysName`, `snmp_sysLocation`, `availability_method`, `ping_method`, `ping_port`, `ping_timeout`, `ping_retries`, `max_oids`, `device_threads`, `deleted`, `disabled`, `status`, `status_event_count`, `status_fail_date`, `status_rec_date`, `status_last_error`, `min_time`, `max_time`, `cur_time`, `avg_time`, `polling_time`, `total_polls`, `failed_polls`, `availability`, `last_updated`) VALUES
(1,1,0,1,'Local Linux Machine','localhost',NULL,'',NULL,'',0,'','','','','','','',161,500,'','',0,'','','',0,2,0,400,1,10,1,'','',3,0,'0000-00-00 00:00:00','0000-00-00 00:00:00','',0.00000,0.00000,0.00000,0.00000,0,588,0,100.00000,current_timestamp);
/*!40000 ALTER TABLE `host` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `host_graph` WRITE;
/*!40000 ALTER TABLE `host_graph` DISABLE KEYS */;
INSERT INTO `host_graph` VALUES
(1,1),
(1,2),
(1,3),
(1,4);
/*!40000 ALTER TABLE `host_graph` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `host_snmp_query` WRITE;
/*!40000 ALTER TABLE `host_snmp_query` DISABLE KEYS */;
INSERT INTO `host_snmp_query` VALUES
(1,1,'dskDevice','|query_dskDevice|',1);
/*!40000 ALTER TABLE `host_snmp_query` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `host_template` WRITE;
/*!40000 ALTER TABLE `host_template` DISABLE KEYS */;
INSERT INTO `host_template` (`id`, `hash`, `name`) VALUES
(1,'2d3e47f416738c2d22c87c40218cc55e','Local Linux Machine');
/*!40000 ALTER TABLE `host_template` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `host_template_graph` WRITE;
/*!40000 ALTER TABLE `host_template_graph` DISABLE KEYS */;
INSERT INTO `host_template_graph` VALUES
(1,1),
(1,2),
(1,3),
(1,4);
/*!40000 ALTER TABLE `host_template_graph` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `host_template_snmp_query` WRITE;
/*!40000 ALTER TABLE `host_template_snmp_query` DISABLE KEYS */;
INSERT INTO `host_template_snmp_query` VALUES
(1,1);
/*!40000 ALTER TABLE `host_template_snmp_query` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
REPLACE INTO `settings` VALUES 
('auth_cache_enabled','on'),
('autocomplete_enabled','1'),
('autocomplete_rows','30'),
('automation_email',''),
('automation_fromemail',''),
('automation_fromname',''),
('automation_graphs_enabled','on'),
('automation_tree_enabled','on'),
('availability_method','0'),
('axis_size','7'),
('base_url',CONCAT('http://', @@hostname, '/cacti/')),
('boost_parallel','1'),
('boost_png_cache_directory', '/opt/IBM/cacti/cache/boost/'),
('boost_png_cache_enable',''),
('boost_poller_mem_limit','2048'),
('boost_redirect',''),
('boost_rrd_update_enable',''),
('boost_rrd_update_interval','60'),
('boost_rrd_update_max_records','1000000'),
('boost_rrd_update_max_records_per_select','50000'),
('boost_rrd_update_max_runtime','1200'),
('boost_rrd_update_string_length','2000'),
('boost_rrd_update_system_enable',''),
('concurrent_processes','1'),
('cron_interval','300'),
('default_datechar','0'),
('default_date_format','4'),
('default_graphs_new_dropdown','-2'),
('default_graph_height','150'),
('default_graph_width','500'),
('default_has',''),
('default_image_format','1'),
('default_poller','1'),
('default_site','1'),
('default_template','1'),
('deletion_verification','on'),
('device_threads','1'),
('drag_and_drop','on'),
('dsstats_daily_interval','60'),
('dsstats_enable',''),
('dsstats_hourly_duration','60'),
('dsstats_major_update_time','12:00am'),
('dsstats_poller_mem_limit','2048'),
('enable_snmp_agent',''),
('font_method','1'),
('force_https',''),
('graph_auth_method','1'),
('graph_watermark','Generated by IBM® Spectrum LSF RTM'),
('grds_creation_method','0'),
('gridalarms_db_version', '10.2.0.16'),
('gridpend_db_version', '10.2.0.16'),
('grid_archive_rrd_files', 'off'),
('grid_archive_rrd_location', '/opt/IBM/cacti/rraarchive'),
('grid_backup_enable', 'on'),
('grid_backup_generations', '4'),
('grid_backup_path', '/opt/IBM/cacti/backup'),
('grid_backup_restore_host_file', ''),
('grid_backup_schedule', 'd'),
('grid_backup_weekday', '0'),
('grid_cache_dir', '/opt/IBM/cacti/gridcache'),
('grid_collection_enabled', 'on'),
('grid_db_version', '10.2.0.16'),
('grid_host_autopurge', '1'),
('grid_os', 'on'),
('grid_short_hostname', 'on'),
('grid_system_collection_enabled', 'on'),
('grid_thold_resdown_status', '1'),
('grid_version', '10.2.0.16'),
('grid_xport_rows', '1000'),
('guest_user','0'),
('help_loc_online_kc','https://www.ibm.com/support/knowledgecenter/SSZT2D_10.2.0'),
('hide_console',''),
('hide_form_description',''),
('i18n_auto_detection','1'),
('i18n_default_language','en-US'),
('i18n_language_support','1'),
('install_complete',UNIX_TIMESTAMP(NOW(6))),
('install_started',UNIX_TIMESTAMP(NOW(6))),
('install_theme','spectrum'),
('ldap_encryption','0'),
('ldap_group_member_type','1'),
('ldap_group_require',''),
('ldap_mode','0'),
('ldap_port','389'),
('ldap_port_ssl','636'),
('ldap_referrals','0'),
('ldap_version','3'),
('legend_size','8'),
('license_db_version', '10.2.0.16'),
('lic_add_device', 'on'),
('lic_data_retention', '2weeks'),
('lic_db_maint_time', '12:00am'),
('logrotate_enabled','on'),
('logrotate_frequency','1'),
('logrotate_retain','7'),
('log_destination','1'),
('log_perror','on'),
('log_pstats',''),
('log_pwarn',''),
('log_refresh_interval','60'),
('log_validation',''),
('log_verbosity','2'),
('max_data_query_field_length','40'),
('max_display_rows','1000'),
('max_get_size','10'),
('max_threads','1'),
('max_title_length','110'),
('notify_admin',''),
('ntp_server', 'pool.ntp.org'),
('num_rows_log','500'),
('num_rows_table','30'),
('oid_increasing_check_disable',''),
('path_cactilog','/opt/IBM/cacti/log/cacti.log'),
('path_php_binary','/usr/bin/php'),
('path_rrdtool','/usr/bin/rrdtool'),
('path_rrdtool_default_font', ''),
('path_snmpbulkwalk','/usr/bin/snmpbulkwalk'),
('path_snmpgetnext','/usr/bin/snmpgetnext'),
('path_snmpget','/usr/bin/snmpget'),
('path_snmptrap','/usr/bin/snmptrap'),
('path_snmpwalk','/usr/bin/snmpwalk'),
('path_stderrlog','/opt/IBM/cacti/log/cacti_stderr.log'),
('path_webroot','/opt/IBM/cacti'),
('php_servers','1'),
('ping_failure_count','2'),
('ping_method','2'),
('ping_port','23'),
('ping_recovery_count','3'),
('ping_retries','1'),
('ping_timeout','400'),
('poller_debug',''),
('poller_enabled','on'),
('poller_interval','300'),
('poller_sync_interval','7200'),
('poller_type','1'),
('process_leveling','on'),
('realtime_cache_path','/opt/IBM/cacti/cache/realtime/'),
('realtime_enabled','on'),
('realtime_gwindow','60'),
('realtime_interval','10'),
('reindex_method','0'),
('remote_agent_timeout','5'),
('reports_allow_ln',''),
('reports_default_image_format','1'),
('reports_log_verbosity','2'),
('reports_max_attach','10485760'),
('rrdp_fingerprint',''),
('rrdp_fingerprint_backup',''),
('rrdp_port','40301'),
('rrdp_port_backup','40301'),
('rrd_archive','/opt/IBM/cacti/rra/archive/'),
('rrd_autoclean',''),
('rrd_autoclean_method','1'),
('script_timeout','25'),
('secpass_expireaccount','0'),
('secpass_expirepass','0'),
('secpass_forceold',''),
('secpass_history','0'),
('secpass_lockfailed','5'),
('secpass_minlen','8'),
('secpass_reqmixcase','on'),
('secpass_reqnum','on'),
('secpass_reqspec','on'),
('secpass_unlocktime','1440'),
('selected_theme','spectrum'),
('selective_debug',''),
('selective_device_debug',''),
('selective_plugin_debug',''),
('settings_dns_primary',''),
('settings_dns_secondary',''),
('settings_dns_timeout','500'),
('settings_how','PHP Mail() ??'),
('settings_ping_mail','0'),
('settings_sendmail_path','/usr/sbin/sendmail'),
('settings_smtp_host','localhost'),
('settings_smtp_port','25'),
('settings_smtp_secure','none'),
('settings_smtp_timeout','10'),
('settings_wordwrap','120'),
('snmp_version','0'),
('spikekill_avgnan','last'),
('spikekill_backupdir','/opt/IBM/cacti/cache/spikekill/'),
('spikekill_basetime','12:00am'),
('spikekill_batch','0'),
('spikekill_deviations','10'),
('spikekill_method','2'),
('spikekill_number','5'),
('spikekill_outliers','5'),
('spikekill_percent','1000'),
('spine_log_level','0'),
('storage_location','0'),
('timezone', 'America/New_York'),
('title_size','10'),
('unit_size','7'),
('user_template','0');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `settings_user` WRITE;
/*!40000 ALTER TABLE `settings_user` DISABLE KEYS */;
INSERT INTO `settings_user` VALUES
(1,'selected_theme','spectrum'),
(1,'user_language','en-US');
/*!40000 ALTER TABLE `settings_user` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `version` WRITE;
/*!40000 ALTER TABLE `version` DISABLE KEYS */;
TRUNCATE TABLE version;
REPLACE INTO version VALUES ('1.2.30');
/*!40000 ALTER TABLE `version` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `automation_networks` WRITE;
/*!40000 ALTER TABLE `automation_networks` DISABLE KEYS */;
TRUNCATE TABLE automation_networks;
/*!40000 ALTER TABLE `automation_networks` ENABLE KEYS */;
UNLOCK TABLES;

-- password: 'admin' in md5, no change password
update user_auth set password='21232f297a57a5a743894a0e4a801fc3', must_change_password='on' where id=1;

-- Prevent snmp WARNING messages for localhost
UPDATE host SET availability_method=0, snmp_version=0,status=3 WHERE hostname='localhost';
