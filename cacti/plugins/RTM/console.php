<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2021                                          |
 |                                                                         |
 | Licensed under the Apache License, Version 2.0 (the "License");         |
 | you may not use this file except in compliance with the License.        |
 | You may obtain a copy of the License at                                 |
 |                                                                         |
 | http://www.apache.org/licenses/LICENSE-2.0                              |
 |                                                                         |
 | Unless required by applicable law or agreed to in writing, software     |
 | distributed under the License is distributed on an "AS IS" BASIS,       |
 | WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.|
 | See the License for the specific language governing permissions and     |
 | limitations under the License.                                          |
 +-------------------------------------------------------------------------+
*/

function rtm_console() {
global $config;
render_external_links('FRONTTOP');
?>
<table class='cactiTable'>
	<?php
	$rtm_plugins_rows = db_fetch_assoc("SELECT directory
		FROM plugin_config
		WHERE directory in ('grid', 'license') AND status=1");

	if (cacti_sizeof($rtm_plugins_rows)) {
	?>
	<tr class='rtmTitle tableRow'>
		<td style='padding-left: 10px;' class='textAreaNotes top left'>
			<?php print __('IBM Spectrum LSF RTM Version %s', read_config_option('grid_version'), 'RTM');?><br>
			<?php print __('Cacti', 'RTM') . ' ' . get_cacti_version_text(); ?>
		</td>
	</tr>
	<tr class='rtmSubTitle tableRow'>
		<td style='padding-left: 10px;'>
			<?php
			function getMonitorClusterSize() {
				if (!isset($_SESSION['sess_acct_cluster_size']) || time() - 300 > $_SESSION['sess_last_acct_update']) {
					$_SESSION['sess_acct_cluster_size'] = db_fetch_row("SELECT
						COUNT(DISTINCT gc.clusterid) AS numCluster,
						COUNT(gh.host) AS numHost
						FROM grid_hosts gh
						JOIN grid_clusters gc
						ON gc.clusterid=gh.clusterid
						WHERE gc.disabled=''");

					$_SESSION['sess_last_acct_update'] = time();
				}

				return $_SESSION['sess_acct_cluster_size'];
			}

			$rtm_plugins = array();
			foreach($rtm_plugins_rows as $rtm_plugin) {
				$rtm_plugins[] = $rtm_plugin['directory'];
			}

			if (in_array('grid', $rtm_plugins)) {
				$hostacct = getMonitorClusterSize();
				if (cacti_sizeof($hostacct)) {
					$numcluster = $hostacct['numCluster'];
					$numhost    = $hostacct['numHost'];
				} else {
					$numcluster = 0;
					$numhost    = 0;
				}

				if ($numhost == 0 || $numcluster == 0) {
					print __('New Install Waiting for Configuration', 'RTM');
				} elseif ($numhost > 1) {
					if ($numcluster > 1) {
						print __('Monitoring %s Hosts on %d Clusters', $numhost, $numcluster, 'RTM');
					} else {
						print __('Monitoring %s Hosts on %d Cluster', $numhost, $numcluster, 'RTM');
					}
				} else {
					if ($numcluster > 1) {
						print __('Monitoring %s Host on %d Clusters', $numhost, $numcluster, 'RTM');
					} else {
						print __('Monitoring %s Host on %d Cluster', $numhost, $numcluster, 'RTM');
					}
				}
			}
		?>
		</td>
	</tr>
	<?php
	}
?>
</table>
<table class='cactiTable'>
<tr>
	<td>
		<ul style='list-style:none;display:flex;flex-direction:row;flex-wrap:wrap;margin:auto;padding:5px;align-content:flex-start'>
<?php
api_plugin_hook('rtm_landing_page');
?>
		</ul>
	</td>
</tr>
</table>
<script type='text/javascript'>
$(function() {
	resizeWindow();
	$(window).resize(function() {
		resizeWindow();
	});
	$('body').tooltip();
});
function resizeWindow() {
	height = parseInt($('#navigation_right').height());
	width  = $('#main').width();
	$('.content').css({'height':height+'px', 'width':width, 'margin-top':'-5px'});
}
</script>
<?php
render_external_links('FRONT');

bottom_footer();
exit;

}

