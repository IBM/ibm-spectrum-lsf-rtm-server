<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2022                                          |
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
include_once('./plugins/grid/lib/grid_functions.php');

$title = __('IBM Spectrum LSF RTM - Grid Heuristics Detail View', 'heuristics');

grid_view_heuristics();

function grid_view_get_heuristics_records(&$sql_where, $apply_limits = true, $row_limit, &$sql_params) {
	global $settings;

	$sort_order = '';
	$sql_where  = '';

	$custom = read_config_option('heuristics_custom_column');
	$lowest = read_config_option('heuristics_low_level_agg');
	$level  = get_request_var('rollup');
	if ($custom != 'none') {
		$display = $settings['rtmpi']['heuristics_custom_column']['array'][$custom];
	}

	$qcustom  = false;
	$qproject = false;
	$qresreq  = false;

	$sql_group_by = '';
	switch($lowest) {
		case 'custom':
			if ($custom != 'none') {
				if ($level == -1) {
					$sql_where .= "WHERE custom = '-' AND projectName = '-' AND resReq = '-' ";
				} else {
					$sql_where .= "WHERE custom != '-' AND projectName = '-' AND resReq = '-' ";
					$qcustom  = true;
				}
			} else {
				$sql_where .= "WHERE custom = '-' AND projectName = '-' AND resReq = '-' ";
			}

			break;
		case 'project':
			if ($custom != 'none') {
				if ($level == -1) {
					$sql_where .= "WHERE custom = '-' AND projectName = '-' AND resReq = '-' ";
				} elseif ($level == 1) {
					$sql_where .= "WHERE custom != '-' AND projectName = '-' AND resReq = '-' ";
					$qcustom  = true;
				} else {
					$sql_where .= "WHERE custom != '-' AND projectName != '-' AND resReq = '-' ";
					$qcustom  = true;
					$qproject = true;
				}
			} else {
				if ($level == -1) {
					$sql_where .= "WHERE custom = '-' AND projectName = '-' AND resReq = '-' ";
				} else {
					$sql_where .= "WHERE custom = '-' AND projectName != '-' AND resReq = '-' ";
					$qproject = true;
				}
			}
			break;
		case 'resreq':
			if ($custom != 'none') {
				if ($level == -1) {
					$sql_where .= "WHERE custom = '-' AND projectName = '-' AND resReq = '-' ";
				} elseif ($level == 1) {
					$sql_where .= "WHERE custom != '-' AND projectName = '-' AND resReq = '-' ";
					$qcustom  = true;
				} elseif ($level == 2) {
					//$sql_where .= "WHERE custom != '-' AND projectName != '-' AND resReq = '-' ";
					//We don't aggregate on Project level when aggregate on resreq level, so need use group by on resreq level data to get Project level data.
					$sql_group_by = "GROUP BY clusterid,queue,custom,projectName";
					$sql_where .= "WHERE custom != '-' AND projectName != '-' ";
					$qcustom  = true;
					$qproject = true;
				} elseif ($level == 3) {
					$sql_where .= "WHERE custom != '-' AND projectName != '-' AND resReq != '-' ";
					$qcustom  = true;
					$qproject = true;
					$qresreq  = true;
				} else {
					$qcustom  = true;
					$qproject = true;
					$qresreq  = true;
				}
			} else {
				if ($level == -1) {
					$sql_where .= "WHERE custom = '-' AND projectName = '-' AND resReq = '-' ";
				} elseif ($level == 1) {
					$sql_where .= "WHERE custom = '-' AND projectName != '-' AND resReq = '-' ";
					$qproject = true;
				} else {
					$sql_where .= "WHERE custom = '-' AND projectName != '-' AND resReq != '-'";
					$qproject = true;
					$qresreq  = true;
				}
			}
			break;
	}

	if (get_request_var('clusterid') != '0') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' gh.clusterid=?';
		$sql_params[] = get_filter_request_var('clusterid');
	}

	if (get_request_var('queue') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' queue=?';
		$sql_params[] = get_request_var('queue');
	}

	if ($qcustom && get_request_var('custom') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' custom=?';
		$sql_params[] = get_request_var('custom');
	}

	if ($qproject) {
		$agg      = read_config_option('grid_project_group_aggregation') == 'on' ? true:false;
		$delim    = read_config_option('grid_job_stats_project_delimiter');
		$count    = read_config_option('grid_job_stats_project_level_number');
		$project  = get_request_var('project');
		$pproject = get_request_var('pproject');

		if ($agg) {
			$level   = get_request_var('level');
			$link    = get_nfilter_request_var('link');
			$parts   = explode($delim, $project);
			$pcount  = cacti_sizeof($parts);
			$pstart  = $parts[0];

			if ($project != '' && $pcount == $level && $link == 'switch') {
				$link = 'direct';
			}

			switch($link) {
				case 'switch': // Level switch
					if ($level == -1) {
						if ($project != '') {
							$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' projectName LIKE ?';
							$sql_params[] = $project . '%';
						}
					} elseif ($level == 1) {
						if ($project != '') {
							$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' projectName = ?' . db_qstr($pstart);
							$sql_params[] = $pstart;
							set_request_var('project', $pstart);
						} else {
							$sql_where .= ($sql_where != '' ? ' AND':'WHERE') .
								' LENGTH(projectName) - LENGTH(REPLACE(projectName, ?, "")) = 0';
							$sql_params[] = $delim;
						}
					} else {
						if ($project != '') {
							if ($pcount + 1 < $level) {
								$sql_where .= ($sql_where != '' ? ' AND':'WHERE') .
									' projectName LIKE ?' .
									' AND  LENGTH(projectName) - LENGTH(REPLACE(projectName, ?, "")) = ?';
								$sql_params[] = $project . '%';
								$sql_params[] = $delim;
								$sql_params[] = $level - 1;
							} else {
								$sql_where .= ($sql_where != '' ? ' AND':'WHERE') .
									' SUBSTRING_INDEX(projectName, ?, ?) = ?' .
									' AND  LENGTH(projectName) - LENGTH(REPLACE(projectName, ?, "")) = ?';
								$sql_params[] = $delim;
								$sql_params[] = $level - 1;
								$sql_params[] = $project;
								$sql_params[] = $delim;
								$sql_params[] = $level - 1;
							}
						} else {
							$sql_where .= ($sql_where != '' ? ' AND':'WHERE') .
								' LENGTH(projectName) - LENGTH(REPLACE(projectName, ?, "")) = ?';
							$sql_params[] = $delim;
							$sql_params[] = $level - 1;
						}
					}
					break;
				case 'direct': // Give me this project
					if ($project != '') {
						$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' projectName = ?';
						$sql_params[] = $project;
						set_request_var('level', $pcount);
					}

					break;
				case 'left': // Give me this project one to the left
					if ($pproject != '') {
						$project = $pproject;
					}

					$parts   = explode($delim, $project);
					$pcount  = cacti_sizeof($parts);
					$pstart  = $parts[0];

					if ($pcount <= 1) {
						set_request_var('level', '-1');
						set_request_var('project', '');
					} else {
						$level = $pcount -1;
						set_request_var('level', $level);

						if ($level == 1) {
							$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' projectName = ?';
							$sql_params[] = $pstart;
							set_request_var('project', $pstart);
						} else {
							$project = implode($delim, array_slice($parts, 0, $level));
							set_request_var('project', $project);

							$sql_where .= ($sql_where != '' ? ' AND':'WHERE') .
								' SUBSTRING_INDEX(projectName, ?, ?) = ?' .
								' AND  LENGTH(projectName) - LENGTH(REPLACE(projectName, ?, "")) = ?';
							$sql_params[] = $delim;
							$sql_params[] = $level;
							$sql_params[] = $project;
							$sql_params[] = $delim;
							$sql_params[] = $level - 1;
						}

					}

					break;
				case 'right': // Give me this project one to the right
					if ($pproject != '') {
						$project = $pproject;
					}

					$parts   = explode($delim, $project);
					$pcount  = cacti_sizeof($parts);
					$pstart  = $parts[0];

					if ($parts == $count) {
						set_request_var('level', $count);
						$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' projectName LIKE ?';
						$sql_params[] = $project . '%';
					} else {
						set_request_var('level', $pcount + 1);
						set_request_var('project', $project);

						$sql_where .= ($sql_where != '' ? ' AND':'WHERE') .
							' SUBSTRING_INDEX(projectName, ?, ?) = ?' .
							' AND  LENGTH(projectName) - LENGTH(REPLACE(projectName, ?, "")) = ?';
						$sql_params[] = $delim;
						$sql_params[] = $pcount;
						$sql_params[] = $project;
						$sql_params[] = $delim;
						$sql_params[] = $pcount;
					}

					break;
			}
		} elseif ($agg) {
			set_request_var('level', '-1');
		} elseif ($project != '') {
			$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' projectName = ?';
			$sql_params[] = $project;
		}
	}

	if (get_request_var('reqcpus') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' reqCpus=?';
		$sql_params[] = get_request_var('reqcpus');
	}

	if ($qresreq && get_request_var('resreq') != '') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' resReq LIKE ?';
		$sql_params[] = '%' . get_request_var('resreq') . '%';
	}

	$sort_order = get_order_string();

	$sql_query = "SELECT gh.*, gc.clustername
		FROM grid_heuristics AS gh
		INNER JOIN grid_clusters AS gc
		ON gh.clusterid = gc.clusterid
		$sql_where
		$sql_group_by
		$sort_order";

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($row_limit*(get_request_var('page')-1)) . ',' . $row_limit;
	}

	//cacti_log(str_replace("\t", '', str_replace("\n", ' ', $sql_query)));

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function build_heuristics_display_array() {
	global $settings;

	if (get_request_var('metric') == 'runtime') {
		$display_text = array(
			'clusterid' => array(
				'display' => __('Cluster', 'heuristics'),
				'tip'     => __('The LSF Cluster that jobs to the right were run on during the Heuristics period.', 'heuristics'),
				'sort'    => 'ASC'
			),
			'queue' => array(
				'display' => __('Queue', 'heuristics'),
				'tip'     => __('The LSF Job Queue that jobs to the right requested during the Heuristics period.', 'heuristics'),
				'sort'    => 'ASC'
			),
			'reqCpus' => array(
				'display' => __('CPUs', 'heuristics'),
				'tip'     => __('The number of cores, threads or job slots that jobs to the right requested during the Heuristics period.', 'heuristics'),
				'align'   => 'center',
				'sort'    => 'DESC'
			),
		);

		$custom = read_config_option('heuristics_custom_column');

		if ($custom != 'none') {
			$display = $settings['rtmpi']['heuristics_custom_column']['array'][$custom];

			$display_text += array(
				'custom' => array(
					'display' => __($display, 'heuristics'),
					'sort'    => 'ASC'
				)
			);

			switch($custom) {
			case 'app':
				$display_text['custom']['tip'] =  __('The LSF Application Profile assigned to jobs during the requested during the Heuristics period.', 'heuristics');
				break;
			case 'sla':
				$display_text['custom']['tip'] =  __('The LSF Service Level Agreement assigned to the jobs during the Heuristics period.', 'heuristics');
				break;
			case 'chargedSAAP':
				$display_text['custom']['tip'] =  __('The LSF Charged SAAP assigned to the jobs during the Heuristics period.', 'heuristics');
				break;
			}
		} else {
		/*	$display_text += array(
				'none' => array(
					'display' => __('Should never be seen', 'heuristics'),
					'sort'    => 'ASC'
				)
			); */
		}

		$display_text += array(
			'projectName' => array(
				'display' => __('Project', 'heuristics'),
				'tip'     => __('The LSF Job Project that jobs to the right requested during the Heuristics period.', 'heuristics'),
				'sort'    => 'ASC'
			),
			'resReq' => array(
				'display' => __('Effective ResReq', 'heuristics'),
				'tip'     => __('The Effective Resource Requirements that jobs to the right requested during the Heuristics period.', 'heuristics'),
				'sort'    => 'ASC'
			),
			'jobs' => array(
				'display' => __('Jobs', 'heuristics'),
				'tip'     => __('The total number of jobs that ran matching the criteria to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'cores' => array(
				'display' => __('Cores', 'heuristics'),
				'tip'     => __('The number of cores, threads or job slots that jobs requested/used during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'run_min' => array(
				'display' => __('Minimum', 'heuristics'),
				'tip'     => __('The Minimum Run Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'run_25thp' => array(
				'display' => __('25th', 'heuristics'),
				'tip'     => __('The 25th percentile of Run Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'run_median' => array(
				'display' => __('Median', 'heuristics'),
				'tip'     => __('The Median (50th percentile) of Run Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'run_avg' => array(
				'display' => __('Average', 'heuristics'),
				'tip'     => __('The Average Run Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'run_75thp' => array(
				'display' => __('75th', 'heuristics'),
				'tip'     => __('The 75th percentile of Run Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'run_90thp' => array(
				'display' => __('90th', 'heuristics'),
				'tip'     => __('The 90th percentile of Run Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'run_max' => array(
				'display' => __('Maximum', 'heuristics'),
				'tip'     => __('The Maximum Run Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'run_stddev' => array(
				'display' => __('Stddev', 'heuristics'),
				'tip'     => __('The Standard Deviation of Run Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'jph_avg' => array(
				'display' => __('Jobs/Hr', 'heuristics'),
				'tip'     => __('The Hourly Average Job Throughput of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'jph_3std' => array(
				'display' => __('Jobs/Hr (3SD)', 'heuristics'),
				'tip'     => __('3 Standard deviations of Hourly Throughput of jobs matching fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			)
		);
	} elseif (get_request_var('metric') == 'memory') {
		$display_text = array(
			'clusterid' => array(
				'display' => __('Cluster', 'heuristics'),
				'tip'     => __('The LSF Cluster that jobs to the right were run on during the Heuristics period.', 'heuristics'),
				'sort'    => 'ASC'
			),
			'queue' => array(
				'display' => __('Queue', 'heuristics'),
				'tip'     => __('The LSF Job Queue that jobs to the right requested during the Heuristics period.', 'heuristics'),
				'sort'    => 'ASC'
			),
			'reqCpus' => array(
				'display' => __('CPUs', 'heuristics'),
				'tip'     => __('The number of cores, threads or job slots that jobs to the right requested during the Heuristics period.', 'heuristics'),
				'align'   => 'center',
				'sort'    => 'DESC'
			),
		);

		$custom = read_config_option('heuristics_custom_column');

		if ($custom != 'none') {
			$display = $settings['rtmpi']['heuristics_custom_column']['array'][$custom];

			$display_text += array(
				'custom' => array(
					'display' => __($display, 'heuristics'),
					'sort'    => 'ASC'
				)
			);

			switch($custom) {
			case 'app':
				$display_text['custom']['tip'] =  __('The LSF Application Profile assigned to jobs during the requested during the Heuristics period.', 'heuristics');
				break;
			case 'sla':
				$display_text['custom']['tip'] =  __('The LSF Service Level Agreement assigned to the jobs during the Heuristics period.', 'heuristics');
				break;
			case 'chargedSAAP':
				$display_text['custom']['tip'] =  __('The LSF Charged SAAP assigned to the jobs during the Heuristics period.', 'heuristics');
				break;
			}
		} else {
		/*	$display_text += array(
				'none' => array(
					'display' => __('Should never be seen', 'heuristics'),
					'sort'    => 'ASC'
				)
			); */
		}

		$display_text += array(
			'projectName' => array(
				'display' => __('Project', 'heuristics'),
				'tip'     => __('The LSF Job Project that jobs to the right requested during the Heuristics period.', 'heuristics'),
				'sort'    => 'ASC'
			),
			'resReq' => array(
				'display' => __('Effective ResReq', 'heuristics'),
				'tip'     => __('The Effective Resource Requirements that jobs to the right requested during the Heuristics period.', 'heuristics'),
				'sort'    => 'ASC'
			),
			'jobs' => array(
				'display' => __('Jobs', 'heuristics'),
				'tip'     => __('The total number of jobs that ran matching the criteria to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'cores' => array(
				'display' => __('Cores', 'heuristics'),
				'tip'     => __('The number of cores, threads or job slots that jobs requested/used during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'mem_min' => array(
				'display' => __('Minimum', 'heuristics'),
				'tip'     => __('The Minimum Max Memory of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'mem_25thp' => array(
				'display' => __('25th', 'heuristics'),
				'tip'     => __('The 25th percentile of Max Memory of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'mem_median' => array(
				'display' => __('Median', 'heuristics'),
				'tip'     => __('The Median (50th percentile) of Max Memory of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'mem_avg' => array(
				'display' => __('Average', 'heuristics'),
				'tip'     => __('The Average Max Memory of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'mem_75thp' => array(
				'display' => __('75th', 'heuristics'),
				'tip'     => __('The 75th percentile of Max Memory of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'mem_90thp' => array(
				'display' => __('90th', 'heuristics'),
				'tip'     => __('The 90th percentile of Max Memory of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'mem_max' => array(
				'display' => __('Maximum', 'heuristics'),
				'tip'     => __('The Maximum Run Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'mem_stddev' => array(
				'display' => __('Stddev', 'heuristics'),
				'tip'     => __('The Standard Deviation of Max Memory of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'jph_avg' => array(
				'display' => __('Jobs/Hr', 'heuristics'),
				'tip'     => __('The Hourly Average Job Throughput of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'jph_3std' => array(
				'display' => __('Jobs/Hr (3SD)', 'heuristics'),
				'tip'     => __('3 Standard deviations of Hourly Throughput of jobs matching fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			)
		);
	} else {
		$display_text = array(
			'clusterid' => array(
				'display' => __('Cluster', 'heuristics'),
				'tip'     => __('The LSF Cluster that jobs to the right were run on during the Heuristics period.', 'heuristics'),
				'sort'    => 'ASC'
			),
			'queue' => array(
				'display' => __('Queue', 'heuristics'),
				'tip'     => __('The LSF Job Queue that jobs to the right requested during the Heuristics period.', 'heuristics'),
				'sort'    => 'ASC'
			),
			'reqCpus' => array(
				'display' => __('CPUs', 'heuristics'),
				'tip'     => __('The number of cores, threads or job slots that jobs to the right requested during the Heuristics period.', 'heuristics'),
				'align'   => 'center',
				'sort'    => 'DESC'
			),
		);

		$custom = read_config_option('heuristics_custom_column');

		if ($custom != 'none') {
			$display = $settings['rtmpi']['heuristics_custom_column']['array'][$custom];

			$display_text += array(
				'custom' => array(
					'display' => __($display, 'heuristics'),
					'sort'    => 'ASC'
				)
			);

			switch($custom) {
			case 'app':
				$display_text['custom']['tip'] =  __('The LSF Application Profile assigned to jobs during the requested during the Heuristics period.', 'heuristics');
				break;
			case 'sla':
				$display_text['custom']['tip'] =  __('The LSF Service Level Agreement assigned to the jobs during the Heuristics period.', 'heuristics');
				break;
			case 'chargedSAAP':
				$display_text['custom']['tip'] =  __('The LSF Charged SAAP assigned to the jobs during the Heuristics period.', 'heuristics');
				break;
			}
		} else {
		/*	$display_text += array(
				'none' => array(
					'display' => __('Should never be seen', 'heuristics'),
					'sort'    => 'ASC'
				)
			); */
		}

		$display_text += array(
			'projectName' => array(
				'display' => __('Project', 'heuristics'),
				'tip'     => __('The LSF Job Project that jobs to the right requested during the Heuristics period.', 'heuristics'),
				'sort'    => 'ASC'
			),
			'resReq' => array(
				'display' => __('Effective ResReq', 'heuristics'),
				'tip'     => __('The Effective Resource Requirements that jobs to the right requested during the Heuristics period.', 'heuristics'),
				'sort'    => 'ASC'
			),
			'jobs' => array(
				'display' => __('Jobs', 'heuristics'),
				'tip'     => __('The total number of jobs that ran matching the criteria to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'cores' => array(
				'display' => __('Cores', 'heuristics'),
				'tip'     => __('The number of cores, threads or job slots that jobs requested/used during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'pend_min' => array(
				'display' => __('Minimum', 'heuristics'),
				'tip'     => __('The Minimum Pend Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'pend_25thp' => array(
				'display' => __('25th', 'heuristics'),
				'tip'     => __('The 25th percentile of Pend Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'pend_median' => array(
				'display' => __('Median', 'heuristics'),
				'tip'     => __('The Median (50th percentile) of Pend Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'pend_avg' => array(
				'display' => __('Average', 'heuristics'),
				'tip'     => __('The Average Pend Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'pend_75thp' => array(
				'display' => __('75th', 'heuristics'),
				'tip'     => __('The 75th percentile of Pend Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'pend_90thp' => array(
				'display' => __('90th', 'heuristics'),
				'tip'     => __('The 90th percentile of Pend Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'pend_max' => array(
				'display' => __('Maximum', 'heuristics'),
				'tip'     => __('The Maximum Pend Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'pend_stddev' => array(
				'display' => __('Stddev', 'heuristics'),
				'tip'     => __('The Standard Deviation of Pend Time of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'jph_avg' => array(
				'display' => __('Jobs/Hr', 'heuristics'),
				'tip'     => __('The Hourly Average Job Throughput of jobs matching the fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'jph_3std' => array(
				'display' => __('Jobs/Hr (3SD)', 'heuristics'),
				'tip'     => __('3 Standard deviations of Hourly Throughput of jobs matching fields to the left during the Heuristics period.', 'heuristics'),
				'align'   => 'right',
				'sort'    => 'DESC'
			)
		);
	}

	return $display_text;
}

function heuristicsFilter() {
	global $config, $settings, $grid_rows_selector;

	// Custom Column Detail
	$custom  = read_config_option('heuristics_custom_column');
	$display = $settings['rtmpi']['heuristics_custom_column']['array'][$custom];

	?><tr class='odd'>
		<td>
			<form id='form_grid' action='grid_heuristics.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'heuristics');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All', 'heuristics');?></option>
							<?php
							$clusters = grid_get_clusterlist(true);

							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Queue', 'heuristics');?>
					</td>
					<td>
						<select id='queue'>
							<option value='-1'<?php if (get_request_var('queue') == '-1') {?> selected<?php }?>><?php print __('All', 'heuristics');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$queues = db_fetch_assoc('SELECT DISTINCT queuename AS queue
									FROM grid_queues
									ORDER BY queuename');
							} else {
								$queues = db_fetch_assoc_prepared('SELECT DISTINCT queuename AS queue
									FROM grid_queues
									WHERE clusterid = ?
									ORDER BY queuename',
									array(get_filter_request_var('clusterid')));
							}

							if (cacti_sizeof($queues)) {
								foreach ($queues as $queue) {
									print '<option value="' . $queue['queue'] .'"'; if (get_request_var('queue') == $queue['queue']) { print ' selected'; } print '>' . html_escape($queue['queue']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Aggregation', 'heuristics');?>
					</td>
					<td>
						<select id='rollup'>
							<?php
							$lowest   = read_config_option('heuristics_low_level_agg');
							$custom   = read_config_option('heuristics_custom_column');
							$sproject = false;
							$show_custom = false;
							$show_resReq = false;
							if ($custom != 'none') {
								$display = $settings['rtmpi']['heuristics_custom_column']['array'][$custom];
							}

							switch($lowest) {
								case 'custom':
									if ($custom != 'none') {
										$levels = array(
											'-1' => __('Cluster, Queue, CPU\'s', 'heuristics'),
											'1'  => __('Cluster, Queue, CPU\'s, %s', $display, 'heuristics'),
										);
										if (get_request_var('rollup') >= 1) {
											$show_custom = true;
										}
									} else {
										$levels = array(
											'-1' => __('Cluster, Queue, CPU\'s', 'heuristics'),
										);
									}
									break;
								case 'project':
									if ($custom != 'none') {
										$levels = array(
											'-1' => __('Cluster, Queue, CPU\'s', 'heuristics'),
											'1'  => __('Cluster, Queue, CPU\'s, %s', $display, 'heuristics'),
											'2'  => __('Cluster, Queue, CPU\'s, %s, Project', $display, 'heuristics')
										);

										if (get_request_var('rollup') >= 1) {
											$show_custom = true;
										}
										if (get_request_var('rollup') == 2) {
											$sproject = true;
										}
									} else {
										$levels = array(
											'-1' => __('Cluster, Queue, CPU\'s', 'heuristics'),
											'1'  => __('Cluster, Queue, CPU\'s, Project', 'heuristics')
										);

										if (get_request_var('rollup') == 1) {
											$sproject = true;
										}
									}
									break;
								case 'resreq':
									if ($custom != 'none') {
										$levels = array(
											'-1' => __('Cluster, Queue, CPU\'s', 'heuristics'),
											'1'  => __('Cluster, Queue, CPU\'s, %s', $display, 'heuristics'),
											'2'  => __('Cluster, Queue, CPU\'s, %s, Project', $display, 'heuristics'),
											'3'  => __('Cluster, Queue, CPU\'s, %s, Project, ResReq', $display, 'heuristics')
										);

										if (get_request_var('rollup') >= 1) {
											$show_custom = true;
										}
										if (get_request_var('rollup') >= 2) {
											$sproject = true;
										}
										if (get_request_var('rollup') == 3) {
											$show_resReq = true;
										}
									} else {
										$levels = array(
											'-1' => __('Cluster, Queue, CPU\'s', 'heuristics'),
											'1'  => __('Cluster, Queue, CPU\'s, Project', 'heuristics'),
											'2'  => __('Cluster, Queue, CPU\'s, Project, ResReq', 'heuristics')
										);

										if (get_request_var('rollup') >= 1) {
											$sproject = true;
										}
										if (get_request_var('rollup') == 2) {
											$show_resReq = true;
										}
									}
									break;
							}

							foreach($levels as $key => $value) {
								print "<option value='$key'" . (get_request_var('rollup') == $key ? ' selected':'') . '>' . html_escape($value) . '</option>';
							}

							?>
						</select>
					</td> <?php if ($custom != 'none' && $show_custom) { ?>
					<td>
						<?php print $display;?>
					</td>
					<td>
						<select id='custom'>
							<option value='-1'<?php if (get_request_var('custom') == '-1') {?> selected<?php }?>><?php print __('All', 'heuristics');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$rows = db_fetch_assoc('SELECT DISTINCT custom
									FROM grid_heuristics
									WHERE custom <> "-"
									ORDER BY custom');
							} else {
								$rows = db_fetch_assoc_prepared('SELECT DISTINCT custom
									FROM grid_heuristics
									WHERE clusterid = ?
									AND custom <> "-"
									ORDER BY custom',
									array(get_filter_request_var('clusterid')));
							}

							if (cacti_sizeof($rows)) {
								foreach ($rows as $row) {
									print '<option value="' . $row['custom'] .'"'; if (get_request_var('custom') == $row['custom']) { print ' selected'; } print '>' . html_escape((empty($row['custom'])?'None':$row['custom'])) . '</option>';
								}
							}
							?>
						</select>
					</td><?php } else {?>
					<td>
						<input type='hidden' id='custom' value='-1'>
					</td><?php }?>
					<td>
						<?php print __('Metric', 'heuristics');?>
					</td>
					<td>
						<select id='metric'>
							<option value='runtime'<?php if (get_request_var('metric') == 'runtime') {?> selected<?php }?>><?php print __('Runtime', 'heuristics');?></option>
							<option value='memory'<?php if (get_request_var('metric') == 'memory') {?> selected<?php }?>><?php print __('Memory', 'heuristics');?></option>
							<option value='pendtime'<?php if (get_request_var('metric') == 'pendtime') {?> selected<?php }?>><?php print __('Pendtime', 'heuristics');?></option>
						</select>
					</td>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __('Go', 'heuristics');?>' title='<?php print __('Search', 'heuristics');?>'>
							<input type='button' id='clear' value='<?php print __('Clear', 'heuristics');?>' title='<?php print __('Clear Filters', 'heuristics');?>'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('CPUs', 'heuristics');?>
					</td>
					<td>
						<select id='reqcpus'>
							<?php
							print '<option value="-1"' . (get_request_var('reqcpus') == '-1' ? ' selected':'') . '>' . __('All', 'heuristics') . '</option>';

							if (get_request_var('clusterid') == 0) {
								$cpus = db_fetch_assoc('SELECT DISTINCT reqCpus
									FROM grid_heuristics
									ORDER BY reqCpus');
							} else {
								$cpus = db_fetch_assoc_prepared('SELECT DISTINCT reqCpus
									FROM grid_heuristics
									WHERE clusterid = ?
									ORDER BY reqCpus',
									array(get_filter_request_var('clusterid')));
							}

							if (cacti_sizeof($cpus)) {
								foreach ($cpus as $cpu) {
									print '<option value="' . $cpu['reqCpus'] .'"' . (get_request_var('reqcpus') == $cpu['reqCpus'] ? ' selected':'') . '>' . html_escape($cpu['reqCpus']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php
					$agg     = read_config_option('grid_project_group_aggregation') == 'on' ? true:false;
					$delim   = read_config_option('grid_job_stats_project_delimiter');
					$count   = read_config_option('grid_job_stats_project_level_number');
					if ($agg && $sproject) {
						print '<td>' .  __('Level', 'heuristics') . '</td>';
						print '<td><select id="level">';
						print '<option value="-1"' . (get_request_var('level') == -1 ? ' selected':'') . '>' . __esc('All Levels', 'heuristics') . '</option>';

						$level = 1;
						while ($level <= $count) {
							print '<option value="' . $level . '"' . (get_request_var('level') == $level ? ' selected':'') . '>' . __esc('Level %d', $level, 'heuristics') . '</option>';
							$level++;
						}

						print '</select></td>';
					} ?>
					<?php if ($sproject) {?>
					<td>
						<?php print __('Project', 'heuristics');?>
					</td>
					<td>
						<input type='text' id='project' size='25' value='<?php print html_escape_request_var('project');?>'>
					</td>
					<?php } ?>
					<?php if ($show_resReq) {?>
					<td>
						<?php print __('ResReq', 'heuristics');?>
					</td>
					<td>
						<input type='text' id='resreq' size='40' value='<?php print html_escape_request_var('resreq');?>'>
					</td>
					<?php } ?>
					</td>
					<td>
						<?php print __('Records', 'heuristics');?>
					</td>
					<td>
						<select id='rows'>
						<?php
							if (cacti_sizeof($grid_rows_selector)) {
								foreach ($grid_rows_selector as $key => $value) {
									print '<option value="' . $key . '"' . (get_request_var('rows') == $key ? ' selected':'') . '>' . $value . '</option>';
								}
							}
						?>
						</select>
					</td>
					<td>
						<?php if (read_config_option('heuristics_low_level_agg') != 'resreq') {?>
						<input type='hidden' id='resreq' value=''>
						<?php } ?>
						<?php if (read_config_option('heuristics_low_level_agg') == 'other') {?>
						<input type='hidden' id='project' value=''>
						<?php } ?>
						<input type='hidden' id='link' value='switch'>
						<input type='hidden' id='pproject' value=''>
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr><?php
}

function grid_view_heuristics() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $config, $settings;
	$sql_params = array();

	// Workaround Cacti bug
	set_request_var('project', '"' . get_nfilter_request_var('project') . '"');

	/* ================= input validation and session storage ================= */
	input_validate_input_regex_xss_attack(get_request_var('resreq'));
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'level' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1',
			'pageset' => true
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'jobs',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'rollup' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'metric' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'runtime',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'resreq' => array(
			'filter' => FILTER_DEFAULT,
			'default' => '',
			'pageset' => true
			),
		'queue' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'custom' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'project' => array(
			'filter' => FILTER_DEFAULT,
			'default' => '',
			'pageset' => true
			),
		'pproject' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'link' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'switch',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'reqcpus' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
	);

	validate_store_request_vars($filters, 'sess_grid_view_heuristics');

	$filters = array(
	    'clusterid' => array(
	        'filter' => FILTER_VALIDATE_INT,
	        'default' => read_grid_config_option('default_grid')
	        )
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ==================================================== */

	// Work around Cacti bug
	set_request_var('project', trim(get_request_var('project'), ' "'));

	$display_text = build_heuristics_display_array();

	general_header();

	?>
	<script type='text/javascript'>

	var startLevel = <?php print get_request_var('level');?>;

	function applyFilter() {
		strURL  = '?header=false';
		strURL += '&rollup='    + $('#rollup').val();
		strURL += '&metric='    + $('#metric').val();
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&queue='     + $('#queue').val();
		strURL += '&custom='    + $('#custom').val();
		strURL += '&rows='      + $('#rows').val();
		if(typeof $('#project').val() == 'undefined') {
			strURL += '&project=';
		} else {
			strURL += '&project='   + encodeURIComponent($('#project').val());
		}
		strURL += '&pproject='  + $('#pproject').val();
		strURL += '&level='     + $('#level').val();
		strURL += '&link='      + $('#link').val();
		strURL += '&reqcpus='   + $('#reqcpus').val();
		if(typeof $('#resreq').val() == 'undefined') {
			strURL += '&resreq=';
		} else {
			strURL += '&resreq='    + encodeURIComponent($('#resreq').val());
		}
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'grid_heuristics.php?header=false&reset=true&link=switch';
		loadPageNoHeader(strURL);
	}

	function filterProject(project, action) {
		if (action != 'direct') {
			$('#pproject').val(project);
		} else {
			$('#project').val(project);
		}

		if (project == '') {
			$('#pproject').val('');
			$('#project').val('');
			$('#level').val('-1');
		}

		$('#link').val(action);

		applyFilter();
	}

	function filterCluster(clusterid) {
		$('#clusterid').val(clusterid);
		applyFilter();
	}

	function filterCustom(custom) {
		$('#custom').val(custom);
		applyFilter();
	}

	function filterQueue(queue) {
		$('#queue').val(queue);
		applyFilter();
	}

	function filterCpu(cpus) {
		$('#reqcpus').val(cpus);
		applyFilter();
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#clusterid, #rollup, #metric, #queue, #reqcpus, #custom, #rows').change(function() {
			applyFilter();
		});

		$('#level').change(function() {
			$('#link').val('switch');

			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});

	});

	</script>
	<?php

	$hdays = array(
		'2'  => __('%d Days', 2, 'heuristics'),
		'3'  => __('%d Days', 3, 'heuristics'),
		'4'  => __('%d Days', 4, 'heuristics'),
		'5'  => __('%d Days', 5, 'heuristics'),
		'6'  => __('%d Days', 6, 'heuristics'),
		'7'  => __('%d Week', 1, 'heuristics'),
		'14' => __('%d Weeks', 2, 'heuristics'),
		'30' => __('%d Month', 1, 'heuristics')
	);

	$history = read_config_option('heuristics_days');

	if ($history > 0) {
		$history_text = $hdays[$history];
	} else {
		$history_text = __('Set Your History Duration in Console > Configuration > Settings > RTM Plugins', 'heuristics');
	}

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$row_limit = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$row_limit = 99999999;
	} else {
		$row_limit = get_request_var('rows');
	}

	$grid_heuristics_results = grid_view_get_heuristics_records($sql_where, true, $row_limit, $sql_params);

	$rows_query_string = "SELECT COUNT(*)
		FROM grid_heuristics AS gh
		INNER JOIN grid_clusters AS gc
		ON gh.clusterid = gc.clusterid
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	if (get_request_var('metric') == 'runtime') {
		html_start_box(__('Job Heuristics [ Runtime Statistics ] [ History: %s ]', $history_text, 'heuristics'), '100%', '', '3', 'center', '');
	} elseif (get_request_var('metric') == 'memory') {
		html_start_box(__('Job Heuristics [ Memory Statistics ] [ History: %s ]', $history_text, 'heuristics'), '100%', '', '3', 'center', '');
	} else {
		html_start_box(__('Job Heuristics [ Pendtime Statistics ] [ History: %s ]', $history_text, 'heuristics'), '100%', '', '3', 'center', '');
	}

	heuristicsFilter();
	html_end_box();

	/* generate page list */
	$nav = html_nav_bar('grid_heuristics.php?rollup=' . get_request_var('rollup'), MAX_DISPLAY_PAGES, get_request_var('page'), $row_limit, $total_rows, '', __('Records', 'heuristics'), 'page', 'main' );

	print $nav;

	$custom = read_config_option('heuristics_custom_column');
	$lowest = read_config_option('heuristics_low_level_agg');
	$level  = get_request_var('rollup');
	if ($custom != 'none') {
		$display = $settings['rtmpi']['heuristics_custom_column']['array'][$custom];
	}

	$dcustom  = false;
	$dproject = false;
	$dresreq  = false;

	switch($lowest) {
		case 'custom':
			if ($custom != 'none') {
				if ($level == -1) {
					unset($display_text['resReq']);
					unset($display_text['projectName']);
					unset($display_text['custom']);
				} else {
					unset($display_text['resReq']);
					unset($display_text['projectName']);
					$dcustom  = true;
				}
			} else {
				unset($display_text['resReq']);
				unset($display_text['projectName']);
				unset($display_text['custom']);
			}

			break;
		case 'project':
			if ($custom != 'none') {
				if ($level == -1) {
					unset($display_text['resReq']);
					unset($display_text['projectName']);
					unset($display_text['custom']);
				} elseif ($level == 1) {
					unset($display_text['resReq']);
					unset($display_text['projectName']);
					$dcustom  = true;
				} else {
					unset($display_text['resReq']);
					$dcustom  = true;
					$dproject = true;
				}
			} else {
				if ($level == -1) {
					unset($display_text['resReq']);
					unset($display_text['projectName']);
					unset($display_text['custom']);
				} else {
					unset($display_text['resReq']);
					unset($display_text['custom']);
					$dproject = true;
				}
			}
			break;
		case 'resreq':
			if ($custom != 'none') {
				if ($level == -1) {
					unset($display_text['resReq']);
					unset($display_text['projectName']);
					unset($display_text['custom']);
				} elseif ($level == 1) {
					unset($display_text['resReq']);
					unset($display_text['projectName']);
					$dcustom  = true;
				} elseif ($level == 2) {
					unset($display_text['resReq']);
					$dcustom  = true;
					$dproject = true;
				} else {
					// Show everything
					$dcustom  = true;
					$dproject = true;
					$dresreq  = true;
				}
			} else {
				if ($level == -1) {
					unset($display_text['resReq']);
					unset($display_text['projectName']);
					unset($display_text['custom']);
				} elseif ($level == 1) {
					unset($display_text['resReq']);
					unset($display_text['custom']);
					$dproject = true;
				} else {
					unset($display_text['custom']);
					$dproject = true;
					$dresreq  = true;
				}
			}
			break;
	}

	html_start_box('', '100%', '', '3', 'center', '');
	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;

	if (cacti_sizeof($grid_heuristics_results)) {
		foreach ($grid_heuristics_results as $gh) {
			form_alternate_row('line' . $i, true, true);

			form_selectable_cell($gh['clustername'] == '' ? '-' : makeClusterFilter($gh['clustername']), $i);
			form_selectable_cell($gh['queue'] == '' ? '-' : makeQueueFilter($gh['queue']), $i);

			form_selectable_cell(makeCpuFilter(number_format_i18n($gh['reqCpus'])), $i, '', 'center');

			if ($dcustom) {
				form_selectable_cell(makeCustomFilter($gh['custom']), $i);
			}

			if ($dproject) {
				form_selectable_cell(makeProjectFilter($gh['projectName']), $i);
			}

			if ($dresreq) {
				form_selectable_cell(prepResreq($gh['resReq']), $i);
			}

			form_selectable_cell(number_format($gh['jobs']), $i, '', 'right');
			form_selectable_cell(number_format($gh['cores']), $i, '', 'right');

			if (get_request_var('metric') == 'runtime') {
				form_selectable_cell($gh['run_min']    == '' ? __('N/A', 'heuristics') : display_job_time($gh['run_min'],2), $i, '', 'right');
				form_selectable_cell($gh['run_25thp']  == '' ? __('N/A', 'heuristics') : display_job_time($gh['run_25thp'],2), $i, '', 'right');
				form_selectable_cell($gh['run_median'] == '' ? __('N/A', 'heuristics') : display_job_time($gh['run_median'],2), $i, '', 'right');
				form_selectable_cell($gh['run_avg']    == '' ? __('N/A', 'heuristics') : display_job_time($gh['run_avg'],2), $i, '', 'right');
				form_selectable_cell($gh['run_75thp']  == '' ? __('N/A', 'heuristics') : display_job_time($gh['run_75thp'],2), $i, '', 'right');
				form_selectable_cell($gh['run_90thp']  == '' ? __('N/A', 'heuristics') : display_job_time($gh['run_90thp'],2), $i, '', 'right');
				form_selectable_cell($gh['run_max']    == '' ? __('N/A', 'heuristics') : display_job_time($gh['run_max'],2), $i, '', 'right');
				form_selectable_cell($gh['run_stddev'] == '' ? __('N/A', 'heuristics') : display_job_time($gh['run_stddev'],2), $i, '', 'right');
			} elseif (get_request_var('metric') == 'memory') {
				form_selectable_cell($gh['mem_min']    == '' ? __('N/A', 'heuristics') : display_job_memory($gh['mem_min'],2), $i, '', 'right');
				form_selectable_cell($gh['mem_25thp']  == '' ? __('N/A', 'heuristics') : display_job_memory($gh['mem_25thp'],2), $i, '', 'right');
				form_selectable_cell($gh['mem_median'] == '' ? __('N/A', 'heuristics') : display_job_memory($gh['mem_median'],2), $i, '', 'right');
				form_selectable_cell($gh['mem_avg']    == '' ? __('N/A', 'heuristics') : display_job_memory($gh['mem_avg'],2), $i, '', 'right');
				form_selectable_cell($gh['mem_75thp']  == '' ? __('N/A', 'heuristics') : display_job_memory($gh['mem_75thp'],2), $i, '', 'right');
				form_selectable_cell($gh['mem_90thp']  == '' ? __('N/A', 'heuristics') : display_job_memory($gh['mem_90thp'],2), $i, '', 'right');
				form_selectable_cell($gh['mem_max']    == '' ? __('N/A', 'heuristics') : display_job_memory($gh['mem_max'],2), $i, '', 'right');
				form_selectable_cell($gh['mem_stddev'] == '' ? __('N/A', 'heuristics') : display_job_memory($gh['mem_stddev'],2), $i, '', 'right');
			} else {
				form_selectable_cell($gh['pend_min']    == '' ? __('N/A', 'heuristics') : display_job_time($gh['pend_min'],2), $i, '', 'right');
				form_selectable_cell($gh['pend_25thp']  == '' ? __('N/A', 'heuristics') : display_job_time($gh['pend_25thp'],2), $i, '', 'right');
				form_selectable_cell($gh['pend_median'] == '' ? __('N/A', 'heuristics') : display_job_time($gh['pend_median'],2), $i, '', 'right');
				form_selectable_cell($gh['pend_avg']    == '' ? __('N/A', 'heuristics') : display_job_time($gh['pend_avg'],2), $i, '', 'right');
				form_selectable_cell($gh['pend_75thp']  == '' ? __('N/A', 'heuristics') : display_job_time($gh['pend_75thp'],2), $i, '', 'right');
				form_selectable_cell($gh['pend_90thp']  == '' ? __('N/A', 'heuristics') : display_job_time($gh['pend_90thp'],2), $i, '', 'right');
				form_selectable_cell($gh['pend_max']    == '' ? __('N/A', 'heuristics') : display_job_time($gh['pend_max'],2), $i, '', 'right');
				form_selectable_cell($gh['pend_stddev'] == '' ? __('N/A', 'heuristics') : display_job_time($gh['pend_stddev'],2), $i, '', 'right');
			}

			form_selectable_cell($gh['jph_avg']  == '' ? __('N/A', 'heuristics') : __('%s jph', number_format($gh['jph_avg'], 1), 'heuristics'), $i, '', 'right');
			form_selectable_cell($gh['jph_3std'] == '' ? __('N/A', 'heuristics') : __('%s jph', number_format($gh['jph_3std'], 1), 'heuristics'), $i, '', 'right');

			form_end_row();
		}

	} else {
		print '<tr><td colspan="'. (cacti_sizeof($display_text)) . '"><em>' . __('No Grid Heuristics Records Found', 'heuristics') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($grid_heuristics_results)) {
		print $nav;
	}

	bottom_footer();
}

function makeClusterFilter($cluster) {
	static $clusters = array();

	if (!isset($clusters[$cluster])) {
		$clusterid = db_fetch_cell_prepared('SELECT clusterid
			FROM grid_clusters
			WHERE clustername = ?',
			array($cluster));

		$clusters[$cluster] = $clusterid;
	} else {
		$clusterid = $clusters[$cluster];
	}

	return "<a href='#' onClick='filterCluster(\"$clusterid\")'>" . html_escape($cluster) . '</a>' .
		(get_request_var('clusterid') != '0' ? "&nbsp;&nbsp<a title='" . __esc('Clear Cluster Filter', 'heuristics') . "' href='#' class='far fa-trash-alt' onClick='filterCluster(\"0\")'></a>":'');
}

function makeCustomFilter($custom) {
	return "<a href='#' onClick='filterCustom(\"$custom\")'>" . html_escape($custom) . '</a>' .
		(get_request_var('custom') != '-1' ? "&nbsp;&nbsp<a title='" . __esc('Clear Custom Filter', 'heuristics') . "' href='#' class='far fa-trash-alt' onClick='filterCustom(\"-1\")'></a>":'');
}

function makeQueueFilter($queue) {
	return "<a href='#' onClick='filterQueue(\"$queue\")'>" . html_escape($queue) . '</a>' .
		(get_request_var('queue') != '-1' ? "&nbsp;&nbsp<a title='" . __esc('Clear Queue Filter', 'heuristics') . "' href='#' class='far fa-trash-alt' onClick='filterQueue(\"-1\")'></a>":'');
}

function makeProjectFilter($project) {
	$agg     = read_config_option('grid_project_group_aggregation') == 'on' ? true:false;
	$delim   = read_config_option('grid_job_stats_project_delimiter');
	$count   = read_config_option('grid_job_stats_project_level_number');

	if ($project != '') {
		if ($agg && $project != 'default') {
			$parts = explode($delim, $project);
			$num   = cacti_sizeof($parts);

			$href  = "<a title='" . __esc('Filter Exact Project', 'heuristics') . "' href='#' onClick='filterProject(\"$project\", \"direct\")'>" . html_escape($project) . '</a>';

			if ($num > 1) {
				$href .= "&nbsp;&nbsp<a title='" . __esc('Reduce Project Aggregation Level', 'heuristics') . "' href='#' class='fas fa-caret-left' onClick='filterProject(\"" . $project . "\", \"left\")'></a>";

				if ($num < $count) {
					$href .= "&nbsp;&nbsp<a title='" . __esc('Increase Project Aggregation Level', 'heuristics') . "' href='#' class='fas fa-caret-right' onClick='filterProject(\"" . $project . "\", \"right\")'></a>";
				}
			} else {
				$href  .= "&nbsp;&nbsp<a title='" . __esc('Increase Project Aggregation Level', 'heuristics') . "' href='#' class='fas fa-caret-right' onClick='filterProject(\"" . $project . "\", \"right\")'></a>";
			}

			$href .= (get_request_var('project') != '' ? "&nbsp;&nbsp<a title='" . __esc('Clear Project Filter', 'heuristics') . "' href='#' class='far fa-trash-alt' onClick='filterProject(\"\", \"switch\")'></a>":'');

			return $href;
		} else {
			return "<a title='" . __esc('Filter Exact Project', 'heuristics') . "' href='#' onClick='filterProject(\"$project\", \"direct\")'>" . html_escape($project) . '</a>' .
				(get_request_var('project') != '' ? "&nbsp;&nbsp<a title='" . __esc('Clear Project Filter', 'heuristics') . "' href='#' class='far fa-trash-alt' onClick='filterProject(\"\", \"switch\")'></a>":'');
		}
	} else {
		return '-';
	}
}

function makeCpuFilter($cpu) {
	return "<a href='#' onClick='filterCpu(\"$cpu\")'>" . html_escape($cpu) . '</a>' .
		(get_request_var('reqcpus') != '-1' ? "&nbsp;&nbsp<a title='" . __esc('Clear Requested CPU Filter', 'heuristics') . "' href='#' class='far fa-trash-alt' onClick='filterCpu(\"-1\")'></a>":'');
}

function prepResreq($resreq) {
	$resreq = str_ireplace('order[', '<br>order[', $resreq);
	$resreq = str_ireplace('span[', '<br>span[', $resreq);
	$resreq = str_ireplace('same[', '<br>same[', $resreq);
	$resreq = str_ireplace('affinity[', '<br>affinity[', $resreq);
	$resreq = str_ireplace('cu[', '<br>cu[', $resreq);
	$resreq = str_ireplace('rusage[', '<br>rusage[', $resreq);
	return $resreq;
}

