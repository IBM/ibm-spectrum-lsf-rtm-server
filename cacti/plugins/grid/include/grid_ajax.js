// $Id$
var gl_form_id = "";

/*
func_change :	the JS fuction name for filter change in web page
filter_obj: 	The Ajax filter object for displaying label;
hidden_obj:  	The real object for storing id value;
form_obj:		The filter form name in web page
ajax_type:  	The type of Ajax filter;
dis_all_val:	The value defined for displaying all items  for examples, cluster: 0, for others -1
extra_para:		Some filter need to transfer a extra parameter for limiting SQL query.
newpath:		The path of grid_ajax.php
*/

function ajax_filter_creation(func_change, filter_obj, hidden_obj, form_obj, ajax_type, dis_all_val, extra_para, newpath){
	var grid_ajax_path = "../grid/include/grid_ajax.php";
	if (newpath > "") 	grid_ajax_path = newpath;

	$(function() {
		$('#'+filter_obj).autocomplete({
			source: function( request, response ) {
				document.getElementById(hidden_obj).value= document.getElementById(filter_obj).value;
				//Assign host_id filter value to -9999 if its input is non-numeric.
				if (hidden_obj == 'host_id') {
					if (!$.isNumeric( document.getElementById(filter_obj).value )){
						document.getElementById(hidden_obj).value= -9999;
					}
				}
				$.ajax({
					url: grid_ajax_path + "?extra_para=" + extra_para,
					type: "POST",
					dataType: "json",
					data: {
						ajaxtype: ajax_type,
						term: request.term
					},
					success: function( data ) {
						response( $.map( data, function( item ) {
							return {
								label: item.label,
								value: item.label,
								id: item.id
							}
						}));
					}
				});
			},
			minLength: 3,
			delay:800,
			noCache: true,
			select: function(event, ui) {
				if (ui.item) {
					$(this).parent().find('#' + hidden_obj).val(ui.item.id);
				}else{
					$(this).parent().find('#' + hidden_obj).val(dis_all_val);
				}
				$(this).parent().find('#' + filter_obj).val(ui.item.label);
				window[func_change](document.getElementById(form_obj));
			},
			change:function( event, ui ) { //if the input is not from the suggestion list
				document.getElementById(hidden_obj).value= document.getElementById(filter_obj).value;
				//Assign host_id filter value to -9999 if its input is non-numeric.
				if (hidden_obj == 'host_id') {
					if (!$.isNumeric( document.getElementById(filter_obj).value )){
						document.getElementById(hidden_obj).value= -9999;
					}
				}
			}
		});

	});
}

function forceReturn(evt) {
	var evt  = (evt) ? evt : ((event) ? event : null);
	var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);

	if ((evt.keyCode == 13) && (node.type=="text")) {
		document.getElementById(gl_form_id).submit();
		return false;
	}
}

//extend jquery autocomplete resize function to match autocomplete dropdown menu width to its parent element width
jQuery.ui.autocomplete.prototype._resizeMenu = function () {
	var ul = this.menu.element;
	ul.outerWidth(this.element.outerWidth());
}

$(function() {
	spage = window.location.pathname.substring(window.location.pathname.lastIndexOf('/') + 1);
	switch (spage) {
	case "grid_bhosts.php":			//Create Ajax filters in various PHP files
		if (document.getElementById("form_grid_view_bhosts")){
			//hgroup
			ajax_filter_creation("applyLoadFilterChange","ajax_hgroup_query","hgroup","form_grid_view_bhosts","ajax_hgroup",-1,document.form_grid_view_bhosts.hgroup_clusterid.value,"");
			//user
			ajax_filter_creation("applyLoadFilterChange","ajax_user_query","user","form_grid_view_bhosts","ajax_user",-1,document.form_grid_view_bhosts.user_clusterid.value,"");
			//For IE:force to submit form when return key is pressed
			gl_form_id = "form_grid_view_bhosts";
			document.onkeypress = forceReturn;
		}
		break;
	case "grid_summary.php":
		if (document.getElementById("exfilter")){  //in host tag
			//hgroup
			ajax_filter_creation("summaryFilterChange","ajax_hostgroup_query","hostgroup","form_grid","ajax_hgroup",-1,document.form_grid.hostgroup_clusterid.value,"");
		}
		if (document.getElementById("grid_summary_alarm")){ //in alarm tag
			//hgroup
			ajax_filter_creation("summaryAlertFilterChange","ajax_hostgroup_query","hostgroup","form_grid","ajax_hgroup",-1,document.form_grid.hostgroup_clusterid.value,"");
		}
		break;
	case "grid_bqueues.php":
		if (document.getElementById("form_grid_view_queues")){
			//user
			ajax_filter_creation("applyQueuesFilterChange","ajax_user_query","user","form_grid_view_queues","ajax_user_bqueues",-1,document.form_grid_view_queues.user_clusterid.value,"");
			//For IE:force to submit form when return key is pressed
			gl_form_id = "form_grid_view_queues";
			document.onkeypress = forceReturn;
		}
		break;
	case "grid_barrays.php":
		if (document.getElementById("form_grid_view_arrays")){
			//user group
			ajax_filter_creation("applyArraysFilterChange","ajax_usergroup_query","usergroup","form_grid_view_arrays","ajax_usergroup_barrays",-1,document.form_grid_view_arrays.usergroup_clusterid.value,"");
			//user
			ajax_filter_creation("applyArraysFilterChange","ajax_user_query","user","form_grid_view_arrays","ajax_user_barrays",-1,document.form_grid_view_arrays.user_clusterid.value,"");
			//For IE:force to submit form when return key is pressed
			gl_form_id = "form_grid_view_arrays";
			document.onkeypress = forceReturn;
		}
		break;
	case "grid_bjobs.php":
		if (document.getElementById("form_grid_view_jobs")){
			//user
			ajax_filter_creation("applyJobsFilterChange","ajax_user_query","user","form_grid_view_jobs","ajax_user_bjobs",-1,document.form_grid_view_jobs.user_clusterid.value,"");
			//user group
			ajax_filter_creation("applyJobsFilterChange","ajax_usergroup_query","usergroup","form_grid_view_jobs","ajax_usergroup_bjobs",-1,document.form_grid_view_jobs.usergroup_clusterid.value,"");
			//exec_host
			ajax_filter_creation("applyJobsFilterChange","ajax_host_query","exec_host","form_grid_view_jobs","ajax_host_bjobs",-1,document.form_grid_view_jobs.host_clusterid.value,"");
			//hgroup
			ajax_filter_creation("applyJobsFilterChange","ajax_hgroup_query","hgroup","form_grid_view_jobs","ajax_hgroup",-1,document.form_grid_view_jobs.hgroup_clusterid.value,"");
		}
		break;
	case "grid_busers.php":
		if (document.getElementById("form_grid_view_users")){
			//user group
			ajax_filter_creation("applyUsersFilterChange","ajax_group_query","group","form_grid_view_users","ajax_usergroup_barrays",-1,document.form_grid_view_users.group_clusterid.value,"");
			//For IE:force to submit form when return key is pressed
			gl_form_id = "form_grid_view_users";
			document.onkeypress = forceReturn;
		}
		break;
	case "grid_lsload.php":
		if (document.getElementById("form_grid_view_load")){
			//hgroup
			ajax_filter_creation("applyLoadFilterChange","ajax_hgroup_query","hgroup","form_grid_view_load","ajax_hgroup",-1,document.form_grid_view_load.hgroup_clusterid.value,"");
			//For IE:force to submit form when return key is pressed
			gl_form_id = "form_grid_view_load";
			document.onkeypress = forceReturn;
		}
		break;
	case "grid_lsgload.php":
		if (document.getElementById("form_grid_view_load")){
			//hgroup
			ajax_filter_creation("applyGLoadFilterChange","ajax_hgroup_query","hgroup","form_grid_view_load","ajax_hgroup",-1,document.form_grid_view_load.hgroup_clusterid.value,"");
			//For IE:force to submit form when return key is pressed
			gl_form_id = "form_grid_view_load";
			document.onkeypress = forceReturn;
		}
		break;
	case "grid_bhosts_closed.php":
		if (document.getElementById("form_grid_view_bhosts")){
			//hgroup
			ajax_filter_creation("applyLoadFilterChange","ajax_hgroup_query","hgroup","form_grid_view_bhosts","ajax_hgroup",-1,document.form_grid_view_bhosts.hgroup_clusterid.value,"");
			//For IE:force to submit form when return key is pressed
			gl_form_id = "form_grid_view_bhosts";
			document.onkeypress = forceReturn;
		}
		break;
	case "grid_bmgroup.php":
		if (document.getElementById("form_grid_view_hgroups")){
			//hgroup
			ajax_filter_creation("applyHGroupFilterChange","ajax_hgroup_query","group","form_grid_view_hgroups","ajax_hgroup",-1,document.form_grid_view_hgroups.hgroup_clusterid.value,"");
			//For IE:force to submit form when return key is pressed
			gl_form_id = "form_grid_view_hgroups";
			document.onkeypress = forceReturn;
		}
		break;
	case "grid_dailystats.php":
		if (document.getElementById("view_dlstat")){
			//user
			ajax_filter_creation("applyFilterChange","ajax_user_query","user","view_dlstat","ajax_user_dstat",-1,document.view_dlstat.user_clusterid.value,"");
			//exec_host
			ajax_filter_creation("applyFilterChange","ajax_host_query","exec_host","view_dlstat","ajax_host_dstat",-1,document.view_dlstat.host_clusterid.value,"");
			//For IE:force to submit form when return key is pressed
			gl_form_id = "view_dlstat";
			document.onkeypress = forceReturn;
		}
		break;
	case "graph_view.php":
		if (document.getElementById("form_graph_view")){  //for graph view mode
			ajax_filter_creation("applyGraphPreviewFilterChange","ajax_host_query","host_id","form_graph_view","ajax_host_graphview",0,"","plugins/grid/include/grid_ajax.php");
		}
		if (document.getElementById("form_graph_list")){  //for graph list mode
			ajax_filter_creation("applyGraphListFilterChange","ajax_host_query","host_id","form_graph_list","ajax_host_graphview",0,"","plugins/grid/include/grid_ajax.php");
		}
		break;
	case "graphs.php":
		if (document.getElementById("form_graph_id")){
			ajax_filter_creation("applyGraphsFilterChange","ajax_host_query","host_id","form_graph_id","ajax_host_graphs",-1,"","plugins/grid/include/grid_ajax.php");
		}
		break;
	case "data_sources.php":
		if (document.getElementById("form_data_sources")){
			ajax_filter_creation("applyDSFilterChange","ajax_host_query","host_id","form_data_sources","ajax_host_datasources",-1,"","plugins/grid/include/grid_ajax.php");
		}
		break;
	default:
		break;
	}


});
