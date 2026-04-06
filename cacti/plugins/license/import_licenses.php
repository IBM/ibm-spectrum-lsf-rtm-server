<?php

#-------------------------------------------------
# Template
# ansys_wan (clic04),Ansys,ansys,27005@azp-cad-clic04.eng.microchip.com,"Chandler, AZ",US/MST,Global WAN,WAN,60,60
#
# lic_add_service.php --name=[SERVICE_NAME] --portatserver=[NAME] --vendor=[NAME] --interval=[SECONDS]
#   --timeout=[SECONDS] --location=[NAME] --support=[NAME] [--region=[PATH]]
#   --poller=[NUMBER] --department=[NAME] [--type=[NAME]]
#
# name = 0
# vendor = 1
# portatserver = 3
# location = 4
# timezone = 5
# department = 6
# type = 7
# interval = 8
# timeout = 9

$licenses = '/pkg/rtm/install/licenses/LicenseSchedulerHosts.csv';

if (file_exists($licenses)) {
	$f = fopen($licenses, 'r');
	$i = 0;

	while (!feof($f)) {
		$license = fgetcsv($f);

		if ($i > 0) {
			shell_exec(sprintf('php -q lic_add_service.php --name="%s" --portatserver="%s" --vendor="%s" --interval=%s --timeout=%s --location="%s" --department="%s" --type="%s"',
	$license[0], $license[3], $license[1], $license[8], $license[9], $license[4], $license[6], $license[7]));
		}

		$i++;
	}

	fclose($f);
}


