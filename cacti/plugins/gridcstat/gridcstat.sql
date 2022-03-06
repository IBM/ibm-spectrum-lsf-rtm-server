--
-- $Id$
--
CREATE TABLE  `grid_job_daily_user_stats` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `user` varchar(45) NOT NULL default '',
  `wall_time` bigint(20) unsigned NOT NULL default '0',
  `total_wall_time` bigint(20) unsigned NOT NULL default '0',
  `cpu_time` bigint(20) unsigned NOT NULL default '0',
  `total_cpu_time` bigint(20) unsigned NOT NULL default '0',
  `slots_done` int(10) unsigned NOT NULL default '0',
  `slots_exited` int(10) unsigned NOT NULL default '0',
  `interval_start` timestamp NOT NULL default '0000-00-00 00:00:00',
  `interval_end` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_recorded` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  USING BTREE (`clusterid`,`user`,`interval_start`,`interval_end`),
  KEY `interval_start` (`interval_start`),
  KEY `interval_end` USING BTREE (`interval_end`),
  KEY `date_recorded` (`date_recorded`),
  KEY `user` (`user`)
) ENGINE=InnoDB;

CREATE TABLE  `grid_job_daily_usergroup_stats` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `usergroup` varchar(45) NOT NULL default '',
  `wall_time` bigint(20) unsigned NOT NULL default '0',
  `total_wall_time` bigint(20) unsigned NOT NULL default '0',
  `cpu_time` bigint(20) unsigned NOT NULL default '0',
  `total_cpu_time` bigint(20) unsigned NOT NULL default '0',
  `slots_done` int(10) unsigned NOT NULL default '0',
  `slots_exited` int(10) unsigned NOT NULL default '0',
  `interval_start` timestamp NOT NULL default '0000-00-00 00:00:00',
  `interval_end` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_recorded` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  USING BTREE (`clusterid`,`usergroup`,`interval_start`,`interval_end`),
  KEY `interval_start` (`interval_start`),
  KEY `interval_end` USING BTREE (`interval_end`),
  KEY `date_recorded` (`date_recorded`),
  KEY `usergroup` (`usergroup`)
) ENGINE=InnoDB;

REPLACE INTO user_auth_realm VALUES (1012, 1);
