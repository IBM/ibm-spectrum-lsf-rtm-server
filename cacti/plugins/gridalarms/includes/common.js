// $Id$
function changealarmType() {
	if ($('#row_alarm_type').val() != null) {
		type = $('#alarm_type').val();
		switch(type) {
		case '0':
			alarm_toggle_hilow('');
			alarm_toggle_time('none');
			break;
		case '1':
			alarm_toggle_hilow('none');
			alarm_toggle_time('');
			break;
		}
	}
}

function changealarmFrequency() {
	frequency = $('#frequency').val();
	failtrig  = $('#alarm_fail_trigger').val();
	realartcy = $('#repeat_alert').val();

	$('#alarm_fail_trigger').empty();
	$('#repeat_alert').empty();

	output_repeatalert = '';
	output_alarm_fail_trigger = '';

	for (key in repeatalert) {
		if (typeof(repeatalert[key] == 'string')) {
			if (key == 0 || parseInt(key) >= parseInt(frequency) && parseInt(key) % parseInt(frequency) == 0) {
				$('#repeat_alert').append('<option value="' + key + '">' + repeatalert[key] + '</option>');
				if (key == realartcy) {
					output_repeatalert += '<option value="' + key + '" selected>' + repeatalert[key] + '</option>';
				} else {
					output_repeatalert += '<option value="' + key + '">' + repeatalert[key] + '</option>';
				}
			}
		}
	}

	for (key in breachduration) {
		if (typeof(breachduration[key] == 'string')) {
			if (parseInt(key) >= parseInt(frequency) && parseInt(key) % parseInt(frequency) == 0) {
				$('#alarm_fail_trigger').append('<option value="' + key + '">' + breachduration[key] + '</option>');
				if (key == failtrig) {
					output_alarm_fail_trigger += '<option value="' + key + '" selected>' + breachduration[key] + '</option>';
				} else {
					output_alarm_fail_trigger += '<option value="' + key + '">' + breachduration[key] + '</option>';
				}
			}
		}
	}

	//keep the option of filter 'breach duration' and 'repeat alert cycle',
	//if the setting has gone, assign the first available option.
	$('#alarm_fail_trigger').val(failtrig);
	if ( $('#alarm_fail_trigger').val() == null) {
		$('#alarm_fail_trigger').val( $("#alarm_fail_trigger option:first").val() );
	}

	$('#repeat_alert').val(realartcy);
	if ( $('#repeat_alert').val() == null) {
		$('#repeat_alert').val( $("#repeat_alert option:first").val() );
	}

	$('#alarm_fail_trigger').html(output_alarm_fail_trigger);
	$( "#alarm_fail_trigger" ).selectmenu( "refresh" );
	$('#repeat_alert').html(output_repeatalert);
	$( "#repeat_alert" ).selectmenu( "refresh" );
}

function alarm_toggle_hilow(status) {
	if (status == '') {
		$('#row_alarm_header').show();
		$('#row_alarm_hi').show();
		$('#row_alarm_low').show();
		$('#row_alarm_fail_trigger').show();
	} else {
		$('#row_alarm_header').hide();
		$('#row_alarm_hi').hide();
		$('#row_alarm_low').hide();
		$('#row_alarm_fail_trigger').hide();
	}
}

function alarm_toggle_time(status) {
	if (status == '') {
		$('#row_time_header').show();
		$('#row_time_hi').show();
		$('#row_time_low').show();
		$('#row_time_fail_trigger').show();
		$('#row_time_fail_length').show();
	} else {
		$('#row_time_header').hide();
		$('#row_time_hi').hide();
		$('#row_time_low').hide();
		$('#row_time_fail_trigger').hide();
		$('#row_time_fail_length').hide();
	}
}

function appendSelected(arg) {
	var i = 0;
	if (arg == 1) {	// append
		var source = document.getElementById('not_selected_users');
		var destination = document.getElementById('selected_users');
		var counter = destination.options.length;
	} else {	// remove
		var source = document.getElementById('selected_users');
		var destination = document.getElementById('not_selected_users');
		var counter = destination.options.length;
	}

	for (i=0;i<source.options.length;i++) {
		if (source.options[i].selected) {
			destination[counter] = new Option(source.options[i].text, source.options[i].value, false, false);
			destination.options[counter] = destination[counter];
			counter ++;
		}
	}

	remove_selection(source);
	add_hidden();
}

function remove_selection(source) {
	var i = 0;
	for(i=(source.options.length-1);i>=0;i--) {
		if (source.options[i].selected) {
			source.remove(i);
		}
	}
}

function add_hidden() {
	var hidden_notify_accounts = document.getElementById('notify_accounts');
	var add_user = document.getElementById('selected_users');
	var user = '';
	var i = 0;
	for (i=0;i<add_user.options.length;i++) {
		user += add_user.options[i].value + ' ';
	}
	var len = user.length;
	user = user.slice(0, len-1);
	hidden_notify_accounts.value = user;
}

function changecluster() {
	if ($('#row_notify_cluster_admin').val() != null) {
		if ($('#clusterid').val() != null) {
			clusterid = $('#clusterid').val();

			switch (clusterid) {
				case '0':
					$('#row_notify_cluster_admin').hide();
					break;
				default:
					$('#row_notify_cluster_admin').show();
					break;
			}
		}
	}
}

function enableDisableTemplate() {
	if ($('#template_enabled').is(':checked') || ($('#tab').val() == 'actions' && $('#template_enabled').val() == 'on')) {
		$('input').prop('disabled', true);
		$('select').prop('disabled', true);
		$('textarea').prop('disabled', true);
		$('#template_enabled').prop('disabled', false);

		if ($('#tab').val() == 'general') {
			$('input[type="submit"]').prop('disabled', false);
			$('#id').prop('disabled', false);
			$('#save_component_alarm').prop('disabled', false);
			$('#tab').prop('disabled', false);
			$('input[name="action"]').prop('disabled', false);
			//$('#clusterid').prop('disabled', false);
			$('#template_id').prop('disabled', false);
		} else {
			$('#notify_cluster_admin').prop('disabled', false);
			$('#notify_users').prop('disabled', false);
			$('#notify_alert').prop('disabled', false);
			$('#not_selected_users').prop('disabled', false);
			$('#Append').prop('disabled', false);
			$('#Remove').prop('disabled', false);
			$('#selected_users').prop('disabled', false);
			$('input[type="submit"]').prop('disabled', false);
			$('#save_component_alarm').prop('disabled', false);
			$('input[name="action"]').prop('disabled', false);
			$('#template_id').prop('disabled', false);
		}
	} else {
		$('input').prop('disabled', false);
		$('select').prop('disabled', false);
		$('textarea').prop('disabled', false);
	}
}

function setDsType(field) {
	var type = $('#'+field).val();

	if (type == 0) {
		$('#row_type_display').show();
		$('#row_db_table').show();
		$('#row_sql_query').hide();
		$('#row_script_thold').hide();
		$('#row_script_data').hide();
		$('#row_script_data_type').hide();
	}else if (type == 1) {
		$('#row_sql_query').show();
		$('#row_type_display').hide();
		$('#row_db_table').hide();
		$('#row_script_thold').hide();
		$('#row_script_data').hide();
		$('#row_script_data_type').hide();
	} else {
		$('#row_type_display').hide();
		$('#row_script_thold').show();
		$('#row_script_data').show();
		$('#row_script_data_type').show();
		$('#row_db_table').hide();
		$('#row_sql_query').hide();
	}
}

