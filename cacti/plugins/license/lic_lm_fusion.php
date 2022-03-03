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

$guest_account = true;

chdir('../../');

include('./include/auth.php');
include_once('./plugins/RTM/include/fusioncharts/fusioncharts.php');
include_once('./plugins/license/include/lic_functions.php');
include_once($config['library_path'] . '/rtm_functions.php');

include_once('./include/global_arrays.php');

/* allow more memory */

lic_lm_fusion_charts();

function lic_lm_fusion_charts(){
	global $title, $report, $minimum_user_refresh_intervals, $config;
	global $lic_refresh_interval, $lic_rows_selector;

	$filters = array(
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'rows_selector' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'service_id',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'poller_type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '0'
			),
		'service_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'timestamp' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'graphs' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'show' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'top_bottom' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'text' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'users' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'addslashes')
			),
		'feature' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'feature_use' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'type' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => 'utilization',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	if (isset_request_var('timestamp')){
		if (get_request_var('timestamp') == -1&&get_request_var('graphs')==-5){
			kill_session_var('sess_lvlf_graphs');
			unset_request_var('graphs');
		}
	}

	if (!isset_request_var('reset')) {
		$level_one_changed = 0;
		$level_one_changed = check_changed('poller_type', 'sess_lvlf_poller_type');
		if ($level_one_changed && get_request_var('poller_type')!='0') {
			kill_session_var('sess_lvlf_service_id');
			kill_session_var('sess_lvlf_current_page');
			kill_session_var('sess_lvlf_feature');
			kill_session_var('sess_lvlf_feature_use');
			kill_session_var('sess_lvlf_users');

			set_request_var('page',1);
			unset_request_var('service_id');
			unset_request_var('feature');
			unset_request_var('feature_use');
			unset_request_var('users');
		}else{
			$service_changed = check_changed('service_id', 'sess_lvlf_service_id');
			$service_changed += check_changed('poller_type', 'sess_lvlf_poller_type');

			if ($service_changed) {
				set_request_var('feature',-1);
				set_request_var('users',-1);
			}
		}
	}

	//load_current_session_value('direction', 'sess_lvlf_direction', 'close');

	/* used for distribution calculation */
	$feature_changed = check_changed('feature', 'sess_lvlf_feature');
	if ($feature_changed) {
		set_request_var('feature_use',get_request_var('feature'));
	}

	//sess_lvlf = sess_lic_view_lm_fusion
	validate_store_request_vars($filters, 'sess_lvlf');

	$title = 'IBM Spectrum LSF RTM - LM Usage Charts';

	general_header();

	html_start_box(__('License Usage Chart Filters'), '100%', '', '3', 'center', '');

	/* Filter table */
	lic_lm_fusion_filter_table();

	html_end_box(true);

	/* Normal Chart */
	$Column2D_flag=0;
	$height=0;
	$strXML='';
	if (get_request_var('show') >= -2) {
		if(get_request_var('graphs') != -5){
			$strXML=lic_lm_build_fusion($height);
		}else if (get_request_var('graphs') == -5 && get_request_var('timestamp') != -1){
			$strXML=lic_lm_build_usage(get_request_var('timestamp'), get_request_var('feature_use'));
			$Column2D_flag=1;
		}
	}

	/* Raw statistical value */
	if (get_request_var('show') <= -2) {
		lic_lm_build_statistic();
	}

	?>
	<script type='text/javascript'>
	var chartobj;

	function get_size() {
		if (navigator.userAgent.match(/msie/i)) {
			var D = (document.body.clientWidth)? document.body: document.documentElement;
			fw  = D.clientWidth - 350;
		}else{
			fw=window.innerWidth - 350;
		}
		if(fw<0){
			fw=1;
		}
		fh = fw*250/800;
	}

	function drawResize() {
		get_size();
		<?php if($Column2D_flag==0 && $height!=0){
			print "fh = fw* $height /800;\n";
		}
		?>
		if(!chartobj && typeof chartobj != 'undefined')
			chartobj.resizeTo(fw,fh);
	}

	function drawChart() {
		get_size();
		if (FusionCharts('fusion_graphId')) FusionCharts('fusion_graphId').dispose();
		<?php if($Column2D_flag==1){
			print "chartobj = new FusionCharts('Column2D', 'fusion_graphId', fw, fh);\n";
		}else{
			if($height!=0){
				print "fh = fw* $height /800;\n";
			}
			print "chartobj = new FusionCharts('Bar2D', 'fusion_graphId', fw, fh);\n";
		}
		?>
		if($('div #fusion_graph').length){
			chartobj.setXMLData("<?php print $strXML;?>");
			chartobj.render('fusion_graph');
		}
	}

	function applyFilter() {
		strURL = '?header=false&service_id=' + $('#service_id').val();
		strURL = strURL + '&poller_type=' + $('#poller_type').val();
		strURL = strURL + '&users=' + decodeURIComponent($('#users').val());
		strURL = strURL + '&graphs=' + $('#graphs').val();
		strURL = strURL + '&show=' + $('#show').val();
		<?php if (get_request_var('graphs') == -1) {?>strURL = strURL + '&text=' + $('#text').val();<?php }?>
		strURL = strURL + '&timestamp=' + $('#timestamp').val();
		strURL = strURL + '&rows_selector=' + $('#rows_selector').val();
		strURL = strURL + '&top_bottom=' + $('#top_bottom').val();

		if($('#graphs').find('option:selected').text() =='Distribution'
			&& $('#feature').find('option:selected').text() == 'All'){
				$("#feature option[value='-1']").remove();
		}
		strURL = strURL + '&feature=' + $('#feature').val();
		loadPageNoHeader(strURL);
	}

    function clearFilter() {
        strURL  = 'lic_lm_fusion.php?header=false&clear=true';
        loadPageNoHeader(strURL);
    }

    $(function() {
        $('#form_lic_view').submit(function(event) {
            event.preventDefault();
            applyFilter();
        });

        $().ready(function() {
            drawChart();
<?php
    $lic_db_upgrade= read_config_option('lic_db_upgrade', TRUE);
    if (isset($lic_db_upgrade) && $lic_db_upgrade == '1') {?>
            $("select[id='timestamp']").attr('disabled',true);
<?php } ?>
        });

        $(window).resize(function() {
            drawResize();
        });
    });


	</script>
	<?php

	bottom_footer();
}

function lic_lm_build_fusion(&$height){
	global $title, $config;

	$strXML = '';

	if (isset_request_var('type')){
		$type = get_request_var('type');
	}else{
		set_request_var('type','duration');
		$type = get_request_var('type');
	}

	$base = 300;
	switch(get_request_var('top_bottom')) {
	case '-1':
	case '1':
	case '4':
		$height = $base;
		break;
	case '2':
	case '5':
		$height = $base * 2;
		break;
	case '3':
	case '6':
		$height = $base * 3;
		break;
	default:
		$height = $base;
		break;
	}

	switch(get_request_var('type')){
	case 'duration': $caption2 = 'Avg Duration';
		$title_suffix = 'Time';
		$units_suffix = '(In Minutes)';

		break;
	case 'utilization': $caption2 = 'Average Utilization';
		$title_suffix = 'Percentage';
		$units_suffix = '(%)';

		break;
	case 'transaction': $caption2 = 'Transaction';
		$title_suffix = 'Count';
		$units_suffix = '';

		break;
	case 'checkout': $caption2 = 'Checkout Transaction';
		$title_suffix = 'Count';
		$units_suffix = '';

		break;
	case 'queued_denied': $caption2 = 'Queued/Denied Transaction';
		$title_suffix = 'Count';
		$units_suffix = '';

		break;
	}

	switch(get_request_var('graphs')){
	case '-1':
		$graphtype = 'feature';
		$caption   = 'Feature Stats';
		$header    = 'Feature Stats';
		$type_name = 'Feature Name';
		$label     = 'feature';
		$showValue=1;

		break;
	case '-2':
		$graphtype = 'user';
		$caption   = 'User Stats';
		$header    = 'User Stats';
		$type_name = 'User Name';
		$label     = 'user';
		$showValue=0;

		break;
	case '-3':
		$graphtype = 'host';
		$caption   = 'Host';
		$header    = 'Host Stats';
		$type_name = 'Host Name';
		$label     = 'host';
		$showValue=0;

		break;
	case '-4':
		$graphtype = 'vendor';
		$caption   = 'Vendor';
		$header    = 'Vendor Stats';
		$type_name = 'Vendor Name';
		$label     = 'vendor';
		$showValue=0;

		break;
	}

	$sql_query = lic_lm_get_fusion_sql($type, $label);

	switch (get_request_var('timestamp')){
		case '-1':
			$subcaption = 'Daily';

			break;
		case '-2':
			$subcaption = 'Last 3 Days';

			break;
		case '-3':
			$subcaption = 'Last 5 Days';

			break;
		case '-4':
			$subcaption = 'Last Week';

			break;
		case '-5':
			$subcaption = 'Last 2 Weeks';

			break;
		case '-6':
			$subcaption = 'Last 3 Weeks';

			break;
		case '-7':
			$subcaption = 'Last 4 Weeks';

			break;
		case '-8':
			$subcaption = 'Last 5 Weeks';

			break;
		case '-9':
			$subcaption = 'Last 10 Weeks';

			break;
		case '-10':
			$subcaption = 'Last 6 Months';

			break;
		case '-11':
			$subcaption = 'Last Year';

			break;
	}

	$fusion_theme = "theme='"  . get_selected_theme() . "'";

	html_start_box($header, '100%', '', '3', 'center', '');
	$strXML = "<chart useRoundEdges='1' labelDisplay='ROTATE' showValues='$showValue' lantLabels='1' chartRightMargin='40' caption='$caption - $caption2' subcaption='$subcaption' yAxisName='$title_suffix $units_suffix' xAxisName='" . ucfirst($type_name) . "' formatNumberScale='0' numberSuffix ='%' showPercentInToolTip='1' $fusion_theme exportEnabled='1'>";

	//echo $sql_query."<br>";
	$values = db_fetch_assoc($sql_query);

	$height = (cacti_sizeof($values) * 25) + 120;

	if (cacti_sizeof($values)){
		switch($type){
		case 'duration':
			foreach($values as $value){
				$duration = number_format($value['duration']/($value['count']*60), 3); // convert to minute
				$strXML  .= "<set label='".$value[$label]."' value='" . $duration . "' />";
			}

			break;
		case 'utilization':
			foreach($values as $value){
				$utilization = number_format(($value['utilization']*100), 3); // convert to percentage
				$utilization = $utilization > 100?100:$utilization;
				if(get_request_var('graphs')==-1){
					if(get_request_var('text')==-1){
						$text=lic_format_seconds($value['checkout_time']);
					}else{
						$peakut = round($value['peak_ut']*100,2);
						$text='Peak UT '.$peakut.'%';
						$utilization = $utilization > $peakut?$peakut:$utilization;
					}
					$strXML     .= "<set label='".$value[$label]."' value='" . $utilization . "' displayValue='" .$text."'/>";
				}else{
					$strXML     .= "<set label='".$value[$label]."' value='" . $utilization . "'/>";
				}
			}

			break;
		case 'transaction':
			foreach ($values as $value){
				$strXML .= "<set label='".$value[$label].'-'.$value['user']."' value='" . $value['transaction'] . "'/>";
			}

			break;
		case 'checkout':
			foreach ($values as $value){
				$strXML .= "<set label='".$value[$label].'-'.$value['user']."' value='" . $value['transaction'] . "'/>";
			}

			break;
		case 'queued_denied':
			foreach ($values as $value){
				$strXML .= "<set label='".$value[$label].'-'.$value['user']."' value='" . $value['transaction'] . "'/>";
			}

			break;
		}

		$strXML .= '</chart>';
		print '<tr>';
		print "<td align='center'><div id='fusion_graph'></div>";
		print '</td>';
		print '</tr>';
	}else{
		print '<tr>';
		print '<td>';
		print 'No records found!! Unable to display Chart!!';
		print '</td>';
		print '</tr>';

	}
	html_end_box();
	return $strXML;
}

function lic_lm_get_fusion_sql($type, $label) {
	global $config, $title;

	$sql_where = '';
	$group_by  = '';

	if (get_request_var('service_id') != -1){
		$sql_where = ' AND service_id='.get_request_var('service_id');
	}else{
		if(isempty_request_var('poller_type')){
			$results = db_fetch_assoc('SELECT service_id FROM lic_services');
		}else{
			$results = db_fetch_assoc_prepared('SELECT service_id FROM lic_services INNER JOIN lic_pollers ON lic_pollers.id=lic_services.poller_id WHERE poller_type=?', array(get_request_var('poller_type')));
		}
		if (cacti_sizeof($results)){
			$portatserver = '(';
			foreach($results as $result){
				$portatserver .= $result['service_id'].',';
			}
			$portatserver  = substr_replace($portatserver,'',-1);
			$portatserver .= ')';
			$sql_where     = ' AND service_id IN '.$portatserver;
		}else{//LUM or other uncertain case
			$sql_where = ' AND 1=0 ';
		}
	}

	if (get_request_var('feature') != -1){
		$sql_where .= " AND feature='".get_request_var('feature')."'";
	}

	if (get_request_var('users') != -1){
		$sql_where .= " AND user='".get_request_var('users')."' AND type='0'";
		$group_by  .= ', user';
	}elseif (get_request_var('graphs') == -2) { // User Graphs
	}elseif (get_request_var('graphs') == -3) { // Host Graphs
	}else{
		$sql_where .= ' AND type="8" ';
	}

	switch(get_request_var('graphs')){
	case '-1';
		$group_by   = ' GROUP BY feature';

		break;
	case '-2';
		$sql_where .= '  AND type="0"';
		$group_by   = ' GROUP BY user';

		break;
	case '-3';
		$sql_where .= ' AND type="0"';
		$group_by   = ' GROUP BY host';

		break;
	case '-4';
		$group_by   = ' GROUP BY vendor';

		break;
	case '-5';
		break;
	}

	lic_set_time_span_values($current, $earlier);

	switch(get_request_var('top_bottom')){
	case '-1':
		$ranking = true;
		$limit   = 10;
		$order   = 'desc';

		break;
	case '1':
		$ranking = true;
		$limit   = 10;
		$order   = 'desc';

		break;
	case '2':
		$ranking = true;
		$limit   = 20;
		$order   = 'desc';

		break;
	case '3':
		$ranking = true;
		$limit   = 30;
		$order   = 'desc';

		break;
	case '4':
		$ranking = true;
		$limit   = 10;
		$order   = 'asc';

		break;
	case '5':
		$ranking = true;
		$limit   = 20;
		$order   = 'asc';

		break;
	case '6':
		$ranking = true;
		$limit   = 30;
		$order   = 'asc';

		break;
	}

	$sql_where .= " AND date_recorded BETWEEN '" . $earlier . "' AND '" . $current . "'";
	switch ($type){

	/* not used */
	/*
	case 'duration':
		$sql_query = "SELECT service_id, $label, SUM(duration) AS duration, SUM(count) AS count
			FROM lic_daily_stats
			WHERE duration != 0 AND action='IN'
			$sql_where
			$group_by";

		if ($ranking) {
			$sql_query .= " ORDER BY duration $order LIMIT $limit";
		}

		break;
	*/
	case 'utilization':

		if (read_config_option('grid_partitioning_enable') == '') {
			$table_query = 'lic_daily_stats';
		}
		else {
			$table_query = make_partition_query('lic_daily_stats', $earlier, $current, "WHERE utilization!=0 $sql_where");
		}

		$used_days = cacti_sizeof(db_fetch_assoc("SELECT DISTINCT interval_end FROM $table_query WHERE utilization!=0 $sql_where"));
		$total_days = db_fetch_cell("SELECT CEILING((UNIX_TIMESTAMP('$current') - UNIX_TIMESTAMP(MIN(interval_end))) / 86400) FROM $table_query WHERE utilization!=0 $sql_where");

		if(!isset($total_days) || empty($total_days) || $total_days=='') {

			$days_ratio = '0';
		}
		else {
			$days_ratio = "$used_days/$total_days";
		}

		$utilization = "(SUM(utilization*total_license_count)*($days_ratio)) / SUM(total_license_count)";

		if(get_request_var('graphs')==-1){
			$poller_interval=read_config_option('poller_interval');

			if (empty($poller_interval)){
				$poller_interval = 300;
			}

			$sql_query = "SELECT service_id, $label, $utilization AS utilization,
			 	MAX(peak_ut) as peak_ut, SUM(count)*$poller_interval as checkout_time
				FROM $table_query
				WHERE utilization !=0 AND action='INUSE'
				$sql_where
				$group_by";
		}else{
			$sql_query = "SELECT service_id, $label, $utilization AS utilization
				FROM $table_query
				WHERE utilization !=0 AND action='INUSE'
				$sql_where
				$group_by";
		}

		if ($ranking){
			$sql_query .= " ORDER BY utilization $order LIMIT $limit";
		}

		break;
	}
	//echo $sql_query."<br>";
	return $sql_query;
}

function lic_lm_build_usage($timespan='-2', $feature_name=''){
	global $config;
	$strXML = '';

	$utilization = array();

	switch($timespan){
		case '-2': // last 3 days
			$limit = 3;

			break;
		case '-3': // last 5 days
			$limit = 5;

			break;
		case '-4': // last week
			$limit = 7;

			break;
		case '-5': // last 2 weeks
			$limit = 14;

			break;
		case '-6':// last 3 weeks
			$limit = 21;

			break;
		case '-7': // last 4 weeks
			$limit = 28;

			break;
		case '-8': //last 5 weeks
			$limit = 35;

			break;
		case '-9': //last 10 weeks
			$limit = 70;

			break;
		case '-10': // last 6 months
			$limit = 183;

			break;
		case '-11':// last year
			$limit = 365;

			break;
	}


	html_start_box('Usage Distribution Chart', '100%', '', '3', 'center', '');

	$fusion_theme = "theme='"  . get_selected_theme() . "'";

	$strXML = "<chart labelDisplay='WRAP' useRoundEdges='1' slantLabels='1' showValues='0' caption='Usage Distribution Chart Over last ".$limit." Days' yAxisName='Number of Occurence' xAxisName='Feature - ".$feature_name."' formatNumberScale='0' $fusion_theme exportEnabled='1'>";

	if (get_request_var('users') != -1) {
		$sql_users = " AND user='" . get_request_var('users') . "' AND host!=''";
		$function  = 'sum';
		$group_by  = 'feature, user';
	}else{
		$sql_users = " AND user='' AND host=''";
		$function  = 'avg';
		$group_by  = 'feature';
	}

	if (get_request_var('service_id') != -1) {
		$sql_service_id = ' AND service_id=' . get_request_var('service_id');
	}else{
		$sql_service_id = '';
	}

	$min_time = strtotime(db_fetch_cell("SELECT min(date_recorded)
		FROM lic_daily_stats
		WHERE utilization!=0
		$sql_users
		$sql_service_id"));

	if (read_config_option('grid_partitioning_enable') == 'on') {
	 	$min_partition_time = strtotime(db_fetch_cell("SELECT min(min_time)
			FROM grid_table_partitions
			WHERE table_name='lic_daily_stats'"));

		 if(isset($min_partition_time) && !empty($min_partition_time) && $min_partition_time != '') {
			$min_time = $min_partition_time;
	 	}
	}

	$utilization['0-10']   = 0;
	$utilization['10-20']  = 0;
	$utilization['20-30']  = 0;
	$utilization['30-40']  = 0;
	$utilization['40-50']  = 0;
	$utilization['50-60']  = 0;
	$utilization['60-70']  = 0;
	$utilization['70-80']  = 0;
	$utilization['80-90']  = 0;
	$utilization['90-100'] = 0;
	$found = false;

	for ($i=0;$i<$limit;$i++){
		$timespan1 = mktime(date('H'), date('i'), date('s'), date('n'), date('j')-$i, date('Y'));
		$timespan2 = mktime(date('H'), date('i'), date('s'), date('n'), date('j')-($i+1), date('Y'));

		/* don't run the query unless we have data */
		if ($timespan1 < $min_time) {
			continue;
		}

		$timespan1 = strftime('%F %H:%M:%S', $timespan1);
		$timespan2 = strftime('%F %H:%M:%S', $timespan2);

		$utilization_function = 'SUM(utilization*total_license_count) / SUM(total_license_count)';

		if (read_config_option('grid_partitioning_enable') == '') {
			$partition_query = 'lic_daily_stats';
		}
		else {
			$partition_query = make_partition_query('lic_daily_stats', $timespan1, $timespan2, " WHERE date_recorded between '$timespan2' AND '$timespan1'
				AND utilization !=0 AND feature='$feature_name' $sql_users $sql_service_id");
		}

		$sql_query = "SELECT service_id, feature, $utilization_function as utilization, date_recorded
			FROM $partition_query
			WHERE date_recorded between '$timespan2' AND '$timespan1'
			AND utilization !=0 AND feature='$feature_name' $sql_users $sql_service_id
			GROUP BY $group_by";

		//echo $sql_query."<br>";

		$values = db_fetch_assoc($sql_query);

		if (cacti_sizeof($values)) {
			foreach ($values as $key => $value){
				$percent = number_format(($value['utilization']*100), 3);
				$found   = true;

				if ($percent <= 10) {
					$utilization['0-10']++;
				}elseif ($percent <= 20) {
					$utilization['10-20']++;
				}elseif ($percent <= 30) {
					$utilization['20-30']++;
				}elseif ($percent <= 40) {
					$utilization['30-40']++;
				}elseif ($percent <= 50) {
					$utilization['40-50']++;
				}elseif ($percent <= 60) {
					$utilization['50-60']++;
				}elseif ($percent <= 70) {
					$utilization['60-70']++;
				}elseif ($percent <= 80) {
					$utilization['70-80']++;
				}elseif ($percent <= 90) {
					$utilization['80-90']++;
				}else{
					$utilization['90-100']++;
				}
			}
		}
	}

	if ($found){
		foreach($utilization as $range => $count) {
			$strXML .= "<set label='" . $range . "%' value='" . $count . "' />";
		}

		$strXML .= '</chart>';
		print '<tr>';
		print "<td align='center'><div id='fusion_graph'></div>";
		print '</td>';
		print '</tr>';
	}else{
		print '<tr>';
		print '<td>';
		print 'No records found!! Unable to display Chart!!';
		print '</td>';
		print '</tr>';
	}

	html_end_box();

	return $strXML;
}

function lic_lm_build_statistic(){
	global $config;

	$sql_where = '';
	$total_rows = 0;

	if (get_request_var('rows_selector') == -1) {
		$row_limit = read_lic_config_option('grid_records');
	}elseif (get_request_var('rows_selector') == -2) {
		$row_limit = 99999999;
	}else{
		$row_limit = get_request_var('rows_selector');
	}

	$display_text = lic_build_display_array();
	$stats = lic_lm_get_license_records($sql_where, TRUE, $row_limit, $total_rows);

	$nav = html_nav_bar('lic_lm_fusion.php?action=view', MAX_DISPLAY_PAGES, get_request_var('page'), $row_limit, $total_rows, '', __('License Service Statistics'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($stats)){
		foreach ($stats as $stat){
			form_alternate_row();
			print '<td>'.$stat['feature'].'</td>';
			print '<td>'.$stat['service_id'].'</td>';
			($stat['user']? print '<td>'.$stat['user'].'</td>': print '<td>--</td>');
			($stat['host']? print '<td>'.$stat['host'].'</td>': print '<td>--</td>');
			($stat['vendor']? print '<td>'.$stat['vendor'].'</td>': print '<td>--</td>');
			print '<td style="text-align:right;white-space:nowrap;">'.lic_cal_utilization($stat['feature'], $stat['user'], $stat['host'], $stat['vendor'], $stat['service_id']).' %</td>';
			/*
			print '<td>'.lic_cal_duration($stat['feature'], $stat['user'], $stat['host'], $stat['vendor']).'</td>';
			*/
		}
		html_end_box(false);
		print $nav;
	}else{
		print "<tr><td colspan='7'><em>No License Service Statistics Found</em></td></tr>";
		html_end_box(false);
	}
}

function lic_lm_get_first_feature() {
	$sql_params = array();

	$current = $earlier = '';

	lic_set_time_span_values($current, $earlier);

	$sql_where = "WHERE last_updated>=?";
	$sql_params[] = $earlier;

	if (get_request_var('service_id') != -1){
		$sql_where .= ' AND ldst.service_id=?';
		$sql_params[] = get_request_var('service_id');
	}

	if (get_request_var('users') != -1){
		$sql_where .= " AND user=?";
		$sql_params[] = get_request_var('users');
	}

	$feature = db_fetch_cell_prepared("SELECT feature
		FROM lic_daily_stats_traffic AS ldst
		$sql_where
		ORDER BY feature
		LIMIT 1", $sql_params);

	return $feature;
}

function lic_lm_get_license_records(&$sql_where, $apply_limits=TRUE, $row_limit='30', &$total_rows){
	if (get_request_var('service_id') != -1){
		$sql_where = 'WHERE lic_daily_stats.service_id='.get_request_var('service_id');
	}

	if (get_request_var('feature') != -1){
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . " feature='".get_request_var('feature')."'";
	}

	if (get_request_var('users') != -1){
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . " user='".get_request_var('users')."'";
	}

	lic_set_time_span_values($current, $earlier);

	$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . " date_recorded BETWEEN '$earlier' AND '$current' AND user!='' AND host!='' AND user !='N/A' AND host !='N/A'";

	if (get_request_var('type') == 'utilization'){
		$group_by = 'GROUP BY lic_daily_stats.service_id, lic_daily_stats.feature, lic_daily_stats.user, lic_daily_stats.host, lic_daily_stats.vendor';
	}else{
		$group_by = '';
	}

	$sort_order = get_order_string();

	if (read_config_option('grid_partitioning_enable') == '') {
		$partition_query = 'lic_daily_stats';
	}
	else {
		$partition_query = make_partition_query('lic_daily_stats', $earlier, $current, $sql_where);
	}

	$sql_join_lp = "";
	if (get_request_var('poller_type') > 0) {
		$sql_where .= (strlen($sql_where)  ? ' AND ' : ' WHERE ') . '(poller_type=' . get_request_var('poller_type') . ')';

		$sql_join_lp = "INNER JOIN lic_pollers lp ON lp.id=lic_services.poller_id";
	}

	$sql_query = "SELECT lic_daily_stats.*
		FROM $partition_query
		INNER JOIN lic_services
		ON (lic_daily_stats.service_id=lic_services.service_id)
		$sql_join_lp
		$sql_where
		$group_by
		$sort_order";

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($row_limit*(get_request_var('page')-1)) . ',' . $row_limit;
	}

	$total_rows_query = "SELECT lic_daily_stats.*
		FROM $partition_query
		INNER JOIN lic_services
		ON (lic_daily_stats.service_id=lic_services.service_id)
		$sql_join_lp $sql_where $group_by";

	$total_rows = lic_count_query_size(db_fetch_assoc($total_rows_query));

	//echo $sql_query."<br>";

	return db_fetch_assoc($sql_query);
}

function lic_lm_fusion_filter_table(){
	global $config, $lic_refresh_interval, $lic_rows_selector;

	$current = $earlier = '';

	lic_set_time_span_values($current, $earlier, TRUE);

	?>
	<tr class='odd'>
		<td>
		<form id='form_lic_view' action='lic_lm_fusion.php'>
			<table cellpadding='1' cellspacing='0' class='filterTable'>
				<tr>
					<td width='60'>
						<?php print __('Manager', 'license');?>
					</td>
					<td>
						<select id='poller_type' onChange='applyFilter()'>
							<option value='0'<?php if (get_request_var('poller_type') == '0') {?> selected<?php }?>>All</option>
							<?php
							$managers = get_lic_managers();
							if (cacti_sizeof($managers)) {
								foreach ($managers as $manager) {
									print '<option value="' . $manager['id'] .'"'; if (get_request_var('poller_type') == $manager['id']) { print ' selected'; } print '>' . html_escape($manager['name']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Service', 'license');?>
					</td>
					<td>
						<select id='service_id' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('service_id') == '-1') {?> selected<?php }?>>All</option>
							<?php
							if (isempty_request_var('poller_type')) {
								$service_id = db_fetch_assoc_prepared("SELECT
									lic_services.service_id AS id, server_name AS name
									FROM (SELECT DISTINCT service_id
										FROM lic_daily_stats_traffic AS ldst
										WHERE last_updated>=?
									) AS short
									INNER JOIN lic_services
									ON lic_services.service_id=short.service_id
									INNER JOIN lic_pollers ON lic_pollers.id=lic_services.poller_id
									ORDER BY server_name", array($earlier));
							} else {
								$service_id = db_fetch_assoc_prepared("SELECT
									lic_services.service_id AS id, server_name AS name
									FROM (SELECT DISTINCT service_id
										FROM lic_daily_stats_traffic AS ldst
										WHERE last_updated>=?
									) AS short
									INNER JOIN lic_services
									ON lic_services.service_id=short.service_id
									INNER JOIN lic_pollers ON lic_pollers.id=lic_services.poller_id
									WHERE poller_type=?
									ORDER BY server_name", array($earlier, get_request_var('poller_type')));
							}

							if (cacti_sizeof($service_id) > 0) {
								foreach ($service_id as $server) {
									print '<option value="' . $server['id'] .'"'; if (get_request_var('service_id') == $server['id']) { print ' selected'; } print '>' . $server['name'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Feature', 'license');?>
					</td>
					<td>
						<select id='feature' onChange='applyFilter()'>
						<?php
						if(get_request_var('graphs')!=-5){ ?>
							<option value='-1'<?php if (get_request_var('feature') == '-1') {?> selected<?php }?>>All</option>
						<?php } ?>
						<?php
							$sql_params = array();
							$sql_where = "WHERE ldst.last_updated>=?";
							$sql_params[] = $earlier;
							if (get_request_var('users') != -1) {
								$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " ldst.user=?";
								$sql_params[] = get_request_var('users');
							}

							if (get_request_var('service_id') != -1) {
								$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " ldst.service_id=?";
								$sql_params[] = get_request_var('service_id');
							}

							if(!isempty_request_var('poller_type')){
								$sql_where .= (strlen($sql_where) ? ' AND':' WHERE') . " lic_pollers.poller_type=?";
								$sql_params[] = get_request_var('poller_type');
							}
							$features = db_fetch_assoc_prepared("SELECT DISTINCT ldst.feature value,
								IF(lafm.user_feature_name = '' OR lafm.user_feature_name IS NULL, ldst.feature, lafm.user_feature_name) AS feature_name
								FROM lic_daily_stats_traffic AS ldst
								LEFT JOIN lic_application_feature_map AS lafm ON ldst.feature=lafm.feature_name " .
								(isempty_request_var('poller_type') ? '' : ' JOIN lic_services ON lic_services.service_id=ldst.service_id INNER JOIN lic_pollers ON lic_pollers.id=lic_services.poller_id ') ."
								$sql_where
								ORDER BY value", $sql_params);

							if (cacti_sizeof($features)){
								foreach ($features as $feature){
									print '<option value="' . $feature['value'] .'"'; if (get_request_var('feature') == $feature['value']) { print ' selected'; } print '>' . $feature['feature_name'] . '</option>';
								}
							}
						?>
						</select>
					</td>
					<td>
						<?php print __('User', 'license');?>
					</td>
					<td>
						<select id='users' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('users') == '-1') {?> selected<?php }?>>All</option>
							<?php
							$sql_params = array();
							$sql_where = "WHERE last_updated>=? AND user!=''";
							$sql_params[] = $earlier;

							if (get_request_var('feature') != -1) {
								$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " ldst.feature=?";
								$sql_params[] = get_request_var('feature');
							}

							if (get_request_var('service_id') != -1) {
								$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " ldst.service_id=?";
								$sql_params[] = get_request_var('service_id');
							}

							if(!isempty_request_var('poller_type')){
								$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " lic_pollers.poller_type=?";
								$sql_params[] = get_request_var('poller_type');
							}
							$users = db_fetch_assoc_prepared('SELECT DISTINCT user
								FROM lic_daily_stats_traffic AS ldst '.
								(isempty_request_var('poller_type') ? '' : ' JOIN lic_services ON lic_services.service_id=ldst.service_id INNER JOIN lic_pollers ON lic_pollers.id=lic_services.poller_id ') ."
								$sql_where
								ORDER BY user", $sql_params);

							if (cacti_sizeof($users)) {
								foreach ($users as $user){
									print '<option value="' . urlencode($user['user']) .'"'; if (str_replace('\\\'', '\'', get_request_var('users')) == $user['user']) { print ' selected'; } print '>' . $user['user'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Top', 'license'); print '/'; print __('Bottom', 'license');?>
					</td>
					<td width='1'>
						<select id='top_bottom' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('top_bottom') == '-1') {?> selected<?php }?>>Default</option>
							<option value='1'<?php if (get_request_var('top_bottom') == '1') {?> selected<?php }?>>Top 10</option>
							<option value='2'<?php if (get_request_var('top_bottom') == '2') {?> selected<?php }?>>Top 20</option>
							<option value='3'<?php if (get_request_var('top_bottom') == '3') {?> selected<?php }?>>Top 30</option>
							<option value='4'<?php if (get_request_var('top_bottom') == '4') {?> selected<?php }?>>Bottom 10</option>
							<option value='5'<?php if (get_request_var('top_bottom') == '5') {?> selected<?php }?>>Bottom 20</option>
							<option value='6'<?php if (get_request_var('top_bottom') == '6') {?> selected<?php }?>>Bottom 30</option>
						</select>
					</td>
					<td>
						<input type='submit' id='go' value='Go' title='Go' onClick='applyFilter()'>
					</td>
					<td>
						<input type='button' id='clear' value='Clear' title='Clear Filters' onClick='clearFilter()'>
					</td>
				</tr>
			</table>
			<table cellpadding='1' cellspacing='0' class='filterTable'>
				<tr>
					<td width='60'>
						<?php print __('Show', 'license');?>
					</td>
					<td>
						<select id='show' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('show') == '-1') {?> selected<?php }?>>Chart</option>
							<option value='-2'<?php if (get_request_var('show') == '-2') {?> selected<?php }?>>Chart/Data</option>
							<option value='-3'<?php if (get_request_var('show') == '-3') {?> selected<?php }?>>Data</option>
						</select>
					</td>
					<td>
						<?php print __('Duration', 'license');?>
					</td>
					<td width='1'>
						<?php
						$min_date = db_fetch_cell('SELECT UNIX_TIMESTAMP(MIN(interval_end)) FROM lic_daily_stats');

						if (read_config_option('grid_partitioning_enable') == 'on') {
						 	$min_partition_date = db_fetch_cell("SELECT UNIX_TIMESTAMP(min(min_time))
								FROM grid_table_partitions
								WHERE table_name='lic_daily_stats'");

						 	if(isset($min_partition_date) && !empty($min_partition_date) && $min_partition_date != '') {
								$min_date = $min_partition_date;
						 	}
						}

						$current_time = time();

						if(!isset($min_date) || empty($min_date) || $min_date =='') {
							$min_date = $current_time;
						}

						$day_range = ceil(($current_time - $min_date)/86400);
						?>
						<select id='timestamp' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('timestamp') == '-1') {?> selected<?php }?>>Daily</option>
							<?php  if ($day_range >= 3) {?>
							<option value='-2'<?php if (get_request_var('timestamp') == '-2') {?> selected<?php }?>>Last 3 Days</option>
							<?php }?>
							<?php  if ($day_range >= 5) {?>
							<option value='-3'<?php if (get_request_var('timestamp') == '-3') {?> selected<?php }?>>Last 5 Days</option>
							<?php }?>
							<?php  if ($day_range >= 7) {?>
							<option value='-4'<?php if (get_request_var('timestamp') == '-4') {?> selected<?php }?>>Last Week</option>
							<?php }?>
							<?php  if ($day_range >= 14) {?>
							<option value='-5'<?php if (get_request_var('timestamp') == '-5') {?> selected<?php }?>>Last 2 Weeks</option>
							<?php }?>
							<?php  if ($day_range >= 21) {?>
							<option value='-6'<?php if (get_request_var('timestamp') == '-6') {?> selected<?php }?>>Last 3 Weeks</option>
							<?php }?>
							<?php  if ($day_range >= 28) {?>
							<option value='-7'<?php if (get_request_var('timestamp') == '-7') {?> selected<?php }?>>Last 4 Weeks</option>
							<?php }?>
							<?php  if ($day_range >= 35) {?>
							<option value='-8'<?php if (get_request_var('timestamp') == '-8') {?> selected<?php }?>>Last 5 Weeks</option>
							<?php }?>
							<?php  if ($day_range >= 70) {?>
							<option value='-9'<?php if (get_request_var('timestamp') == '-9') {?> selected<?php }?>>Last 10 Weeks</option>
							<?php }?>
							<?php  if ($day_range >= 183) {?>
							<option value='-10'<?php if (get_request_var('timestamp') == '-10') {?> selected<?php }?>>Last 6 Months</option>
							<?php }?>
							<?php  if ($day_range >= 365) {?>
							<option value='-11'<?php if (get_request_var('timestamp') == '-11') {?> selected<?php }?>>Last 1 Year</option>
							<?php }?>
						</select>
					</td>
					<td>
						<?php print __('Graph', 'license');?>
					</td>
					<td>
						<select id='graphs' id='graphs'  onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('graphs') == '-1') {?> selected<?php }?>>Feature</option>
							<option value='-2'<?php if (get_request_var('graphs') == '-2') {?> selected<?php }?>>User</option>
							<option value='-3'<?php if (get_request_var('graphs') == '-3') {?> selected<?php }?>>Host</option>
							<option value='-4'<?php if (get_request_var('graphs') == '-4') {?> selected<?php }?>>Vendor</option>
							<?php if (get_request_var('timestamp') != -1){ ?>
							<option value='-5'<?php if (get_request_var('graphs') == '-5') {?> selected<?php }?>>Distribution</option>
							<?php }?>
						</select>
					</td>
					<td>
						<?php print __('Records', 'license');?>
					</td>
					<td>
						<select id='rows_selector' onChange='applyFilter()'>
							<?php
							if (cacti_sizeof($lic_rows_selector) > 0) {
								foreach ($lic_rows_selector as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('rows_selector') == $key) { print ' selected'; } print '>' . $value . '</option>';
			 				       }
	       						}
							?>
						</select>
					</td>
					<?php if (get_request_var('graphs') == -1){ ?>
					<td>
						<?php print __('Text', 'license');?>
					</td>
					<td>
						<select id='text' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('text') == '-1') {?> selected<?php }?>>Token Time</option>
							<option value='-2'<?php if (get_request_var('text') == '-2') {?> selected<?php }?>>Peak UT</option>
						</select>
					</td>
					<?php }?>
				</tr>
			</table>
		</form>
		</td>
	</tr>
<?php
}

function lic_build_display_array() {
    $display_text = array();
    $display_text += array('feature'     => array('display' => __('Feature Name', 'license'), 'sort' => 'DESC'));
    $display_text += array('service_id'  => array('display' => __('Service ID', 'license'), 'sort' => 'ASC'));
    $display_text += array('user'        => array('display' => __('User Name', 'license'), 'sort' => 'DESC'));
    $display_text += array('host'        => array('display' => __('Host Name', 'license'), 'sort' => 'DESC'));
	$display_text += array('vendor'      => array('display' => __('Vendor Name', 'license'), 'sort' => 'DESC'));
    $display_text += array('utilization' => array('display' => __('Average Utilization', 'license'), 'align' => 'right'));
    return $display_text;
}

