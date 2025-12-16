<?php
// $Id$
// die if they didn't pass an argument
if ($_SERVER["argc"] < 2) {
	echo "usage: php ".$_SERVER["argv"][0]." <hostName>\n";
} else {
	$host = $_SERVER["argv"][1];

	$memcache = new Memcache;
	$memcache->connect($host, 11211) or die ("Could not connect");

	//var_dump($memcache->getStats());
	$result = $memcache->getExtendedStats();

	foreach ($result as $serverStats) {
		foreach ($serverStats as $statKey => $statVal) {
			if ($statKey == "rusage_user" || $statKey == "rusage_system") {
				$statVal = round($statVal * 1000);
			}
			echo "$statKey:$statVal ";
		}
	}
	echo "\n";
}
?>
