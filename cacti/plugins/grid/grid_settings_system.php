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

chdir('../../');
include('./include/auth.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['library_path'] . '/rtm_functions.php');

/* set default action */
set_default_action();

switch (get_request_var('action')) {
case 'save':
	input_validate_input_regex(get_nfilter_request_var('tab'), '^([a-zA-Z0-9_]+)$');
	if (get_nfilter_request_var('tab') == 'grid_runlimit') {
		$runtime_threshold = read_config_option('gridrunlimitvio_threshold');
	}

	foreach ($grid_settings_system[get_request_var('tab')] as $field_name => $field_array) {
		if (($field_array['method'] == 'header') || ($field_array['method'] == 'spacer' )) {
				/* do nothing */
		} elseif ($field_array['method'] == 'textbox_password') {
			if (get_nfilter_request_var($field_name) != get_nfilter_request_var($field_name.'_confirm')) {
				raise_message(4);
				break;
			} else {
				db_execute_prepared('REPLACE INTO settings (name,value) VALUES (?, ?)',
					array($field_name, (isset_request_var($field_name) ? get_nfilter_request_var($field_name) : '')));
			}
		} elseif ((isset($field_array['items'])) && (is_array($field_array['items']))) {
			foreach ($field_array['items'] as $sub_field_name => $sub_field_array) {
				db_execute_prepared('REPLACE INTO settings (name,value) VALUES (?, ?)',
					array($sub_field_name, (isset_request_var($sub_field_name) ? get_nfilter_request_var($sub_field_name) : '')));
			}
		} else {
			if ($field_name == 'grid_db_maint_time') {
				if (!strtotime(get_request_var($field_name))) {
					raise_message(147);
					$_SESSION['sess_error_fields'][$field_name] = $field_name;
					break;
				}
			}

			db_execute_prepared('REPLACE INTO settings (name,value) VALUES (?, ?)',
				array($field_name, (isset_request_var($field_name) ? get_nfilter_request_var($field_name) : '')));
		}
	}

	//do restore here.
    if (get_request_var('tab') === 'grid_maint' && isset($_FILES['tgz_file']['tmp_name']) && file_exists($_FILES['tgz_file']['tmp_name'])) {
        //grid_restore_cacti_db($_FILES['tgz_file']);
    } else {
       raise_message(1);
    }

	/* reset local settings cache so the user sees the new settings */
	kill_session_var('sess_config_array');
	if (get_request_var('tab') == 'grid_runlimit') {
		if ($runtime_threshold != read_config_option('gridrunlimitvio_threshold')) {
			db_execute('DELETE FROM grid_jobs_runtime WHERE type=1 OR type=2');
		}
	}

	header('Location: grid_settings_system.php?header=false&tab=' . get_request_var('tab'));
	break;
default:
	top_header();

	/* set the default settings category */
	if (!isset_request_var('tab')) {
		/* there is no selected tab; select the first one */
		$current_tab = array_keys($tabs_grid_system_settings);
		$current_tab = $current_tab[0];
	} else {
		$current_tab = get_request_var('tab');
	}

	form_start('grid_settings_system.php');

	/* draw the categories tabs on the top of the page */
	print "<table><tr><td style='padding-bottom:0px;'>\n";
	print "<div class='tabs' style='float:left;'><nav><ul role='tablist'>\n";

    if (cacti_sizeof($tabs_grid_system_settings)) {
        $i = 0;

        foreach (array_keys($tabs_grid_system_settings) as $tab_short_name) {
            print "<li role='tab' tabindex='$i' aria-controls='tabs-" . ($i+1) . "' class='subTab'><a role='presentation' tabindex='-1' " . (($tab_short_name == $current_tab) ? "class='selected'" : "class=''") . " href='" . html_escape($config['url_path'] . "plugins/grid/grid_settings_system.php?tab=$tab_short_name") . "'>" . $tabs_grid_system_settings[$tab_short_name] . "</a></li>\n";

            $i++;
        }
    }

    print "</ul></nav></div>\n";
    print "</tr></table><table style='width:100%;'><tr><td style='padding:0px;'>\n";

	if ($current_tab == 'grid_maint') {
		html_start_box(__('Grid Settings (%s)', $tabs_grid_system_settings[$current_tab]) . rtm_hover_help('grid_settings_maint.html', __esc('Learn More', 'grid')), '100%', '', '3', 'center', '');
	} else {
		html_start_box(__('Grid Settings (%s)', $tabs_grid_system_settings[$current_tab]), '100%', '', '3', 'center', '');
	}

	$form_array   = array();
	$config_array = array('method' => 'post', 'enctype' => 'multipart/form-data');

	foreach ($grid_settings_system[$current_tab] as $field_name => $field_array) {
		$form_array += array($field_name => $field_array);

		if ((isset($field_array['items'])) && (is_array($field_array['items']))) {
			foreach ($field_array['items'] as $sub_field_name => $sub_field_array) {
				if (config_value_exists($sub_field_name)) {
					$form_array[$field_name]['items'][$sub_field_name]['form_id'] = 1;
				}

				$form_array[$field_name]['items'][$sub_field_name]['value'] = db_fetch_cell_prepared('SELECT value
					FROM settings
					WHERE name = ?',
					array($sub_field_name));
			}
		} else {
			if (config_value_exists($field_name)) {
				$form_array[$field_name]['form_id'] = 1;
			}

            if (!isset($form_array[$field_name]['value'])) {
                $form_array[$field_name]['value'] = db_fetch_cell_prepared('SELECT value
					FROM settings
					WHERE name = ?',
					array($field_name));
            }
		}
	}

	draw_edit_form(
		array(
			'config' => $config_array,
			'fields' => $form_array
		)
	);

	html_end_box();

	form_hidden_box('tab', $current_tab, '');

	form_save_button('', 'save');

	form_end();

	print "</td></tr></table>\n";

    ?>
    <script type='text/javascript'>

	$('.subTab').find('a').click(function(event) {
		event.preventDefault();
		strURL = $(this).attr('href');
		strURL += (strURL.indexOf('?') > 0 ? '&':'?') + 'header=false';
		loadPageNoHeader(strURL);
	});

	$('input[type="submit"]').click(function(event) {
		event.preventDefault();

		$.post('grid_settings_system.php?tab='+$('#tab').val()+'&header=false', $('input, select, textarea').serialize()).done(function(data) {
			$('#main').hide().html(data);
			applySkin();
		});
	});

	</script>
	<?php

	bottom_footer();

	break;
}
