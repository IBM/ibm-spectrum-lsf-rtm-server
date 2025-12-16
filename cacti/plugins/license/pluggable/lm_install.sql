--
-- $Id$
--
INSERT INTO `lic_managers` (`id`, `hash`, `name`, `description`, `type`, `logparser_binary`, `collector_binary`, `lm_client`, `lm_client_arg1`, `failover_hosts`, `disabled`) VALUES
(4,'8aa623b0d5d76750925632ba3f2977c1','LMX','LM-X License Manager',0,'','licjsonpoller','/opt/IBM/lmx/bin/lmxendutil','-C',1,0),
(5,'61f5397851f315c92f0a635ef5922a6e','DSLS','DSLS License Manager',0,'','licjsonpoller','/opt/IBM/dsls/bin/DSLicSrv','-C',3,0);

INSERT INTO `lic_pollers`
(`poller_path`, `client_path`, `poller_description`, `poller_hostname`, `poller_exechost`, `poller_type`) VALUES
('/opt/IBM/rtm/lic/bin','/opt/IBM/rtm/lic/etc/pluggable/lic_lmx_capture.php','LM-X Poller','local','',4),
('/opt/IBM/rtm/lic/bin','/opt/IBM/rtm/lic/etc/pluggable/lic_dsls_capture.php','DSLS Poller','local','',5);
