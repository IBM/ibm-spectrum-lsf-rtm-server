// $Id$
function applySkinRTM() {
	var job_user_open = false;
	var userTimer;
	$('#rtm_job_user').unbind().autocomplete({
		source: pageName+'?action=ajax_rtm_users',
		autoFocus: true,
		minLength: 0,
		select: function(event,ui) {
			$('#job_user').val(ui.item.id);
			callBack = $('#call_back_job_user').val();
			if (callBack != 'undefined') {
				if (callBack.indexOf('applyFilter') >= 0) {
					applyFilter();
				} else if (callBack.indexOf('applyGraphFilter') >= 0) {
					applyGraphFilter();
				}
			} else if (typeof applyGraphFilter === 'function') {
				applyGraphFilter();
			} else {
				applyFilter();
			}
		}
	}).css('border', 'none').css('background-color', 'transparent');

	$('#rtm_job_user_click').css('z-index', '4');
	$('#job_user_wrapper').unbind().dblclick(function() {
		job_user_open = false;
		clearTimeout(userTimer);
		clearTimeout(user_clickTimeout);
		$('#rtm_job_user').autocomplete('close');
	}).click(function() {
		if (job_user_open) {
			$('#rtm_job_user').autocomplete('close');
			clearTimeout(userTimer);
			job_user_open = false;
		} else {
			user_clickTimeout = setTimeout(function() {
				$('#rtm_job_user').autocomplete('search', '');
				clearTimeout(userTimer);
				job_user_open = true;
			}, 200);
		}
	}).on('mouseenter', function() {
		$(this).addClass('ui-state-hover');
		$('input#rtm_job_user').addClass('ui-state-hover');
	}).on('mouseleave', function() {
		$(this).removeClass('ui-state-hover');
		$('#rtm_job_user').removeClass('ui-state-hover');
		userTimer = setTimeout(function() { $('#rtm_job_user').autocomplete('close'); }, 800);
		job_user_open = false;
	});

	var userPrefix = '';
	$('#rtm_job_user').autocomplete('widget').each(function() {
		userPrefix=$(this).attr('id');

		if (userPrefix != '') {
			$('ul[id="'+userPrefix+'"]').on('mouseenter', function() {
				clearTimeout(userTimer);
			}).on('mouseleave', function() {
				userTimer = setTimeout(function() { $('#rtm_job_user').autocomplete('close'); }, 800);
				$(this).removeClass('ui-state-hover');
				$('input#rtm_job_user').removeClass('ui-state-hover');
			});
		}
	});

	var usergroup_open = false;
	var usergroupTimer;
	$('#rtm_usergroup').unbind().autocomplete({
		source: pageName+'?action=ajax_rtm_usergroups',
		autoFocus: true,
		minLength: 0,
		select: function(event,ui) {
			$('#usergroup').val(ui.item.id);
			callBack = $('#call_back_usergroup').val();
			if (callBack != 'undefined') {
				if (callBack.indexOf('applyFilter') >= 0) {
					applyFilter();
				} else if (callBack.indexOf('applyGraphFilter') >= 0) {
					applyGraphFilter();
				}
			} else if (typeof applyGraphFilter === 'function') {
				applyGraphFilter();
			} else {
				applyFilter();
			}
		}
	}).css('border', 'none').css('background-color', 'transparent');

	$('#rtm_usergroup_click').css('z-index', '4');
	$('#usergroup_wrapper').unbind().dblclick(function() {
		usergroup_open = false;
		clearTimeout(usergroupTimer);
		clearTimeout(usergroup_clickTimeout);
		$('#rtm_usergroup').autocomplete('close');
	}).click(function() {
		if (usergroup_open) {
			$('#rtm_usergroup').autocomplete('close');
			clearTimeout(usergroupTimer);
			usergroup_open = false;
		} else {
			usergroup_clickTimeout = setTimeout(function() {
				$('#rtm_usergroup').autocomplete('search', '');
				clearTimeout(usergroupTimer);
				usergroup_open = true;
			}, 200);
		}
	}).on('mouseenter', function() {
		$(this).addClass('ui-state-hover');
		$('input#rtm_usergroup').addClass('ui-state-hover');
	}).on('mouseleave', function() {
		$(this).removeClass('ui-state-hover');
		$('#rtm_usergroup').removeClass('ui-state-hover');
		usergroupTimer = setTimeout(function() { $('#rtm_usergroup').autocomplete('close'); }, 800);
		usergroup_open = false;
	});

	var usergroupPrefix = '';
	$('#rtm_usergroup').autocomplete('widget').each(function() {
		usergroupPrefix=$(this).attr('id');

		if (usergroupPrefix != '') {
			$('ul[id="'+usergroupPrefix+'"]').on('mouseenter', function() {
				clearTimeout(usergroupTimer);
			}).on('mouseleave', function() {
				usergroupTimer = setTimeout(function() { $('#rtm_usergroup').autocomplete('close'); }, 800);
				$(this).removeClass('ui-state-hover');
				$('input#rtm_usergroup').removeClass('ui-state-hover');
			});
		}
	});

	var hgroup_open = false;
	var hgroupTimer;
	$('#rtm_hgroup').unbind().autocomplete({
		source: pageName+'?action=ajax_rtm_hgroups',
		autoFocus: true,
		minLength: 0,
		select: function(event,ui) {
			$('#hgroup').val(ui.item.id);
			callBack = $('#call_back_hgroup').val();
			if (callBack != 'undefined') {
				if (callBack.indexOf('applyFilter') >= 0) {
					applyFilter();
				} else if (callBack.indexOf('applyGraphFilter') >= 0) {
					applyGraphFilter();
				}
			} else if (typeof applyGraphFilter === 'function') {
				applyGraphFilter();
			} else {
				applyFilter();
			}
		}
	}).css('border', 'none').css('background-color', 'transparent');

	$('#rtm_hgroup_click').css('z-index', '4');
	$('#hgroup_wrapper').unbind().dblclick(function() {
		hgroup_open = false;
		clearTimeout(hgroupTimer);
		clearTimeout(hgroup_clickTimeout);
		$('#rtm_hgroup').autocomplete('close');
	}).click(function() {
		if (hgroup_open) {
			$('#rtm_hgroup').autocomplete('close');
			clearTimeout(hgroupTimer);
			hgroup_open = false;
		} else {
			hgroup_clickTimeout = setTimeout(function() {
				$('#rtm_hgroup').autocomplete('search', '');
				clearTimeout(hgroupTimer);
				hgroup_open = true;
			}, 200);
		}
	}).on('mouseenter', function() {
		$(this).addClass('ui-state-hover');
		$('input#rtm_hgroup').addClass('ui-state-hover');
	}).on('mouseleave', function() {
		$(this).removeClass('ui-state-hover');
		$('#rtm_hgroup').removeClass('ui-state-hover');
		hgroupTimer = setTimeout(function() { $('#rtm_hgroup').autocomplete('close'); }, 800);
		hgroup_open = false;
	});

	var hgroupPrefix = '';
	$('#rtm_hgroup').autocomplete('widget').each(function() {
		hgroupPrefix=$(this).attr('id');

		if (hgroupPrefix != '') {
			$('ul[id="'+hgroupPrefix+'"]').on('mouseenter', function() {
				clearTimeout(hgroupTimer);
			}).on('mouseleave', function() {
				hgroupTimer = setTimeout(function() { $('#rtm_hgroup').autocomplete('close'); }, 800);
				$(this).removeClass('ui-state-hover');
				$('input#rtm_hgroup').removeClass('ui-state-hover');
			});
		}
	});

	var exec_host_open = false;
	var exechostTimer;
	$('#rtm_exec_host').unbind().autocomplete({
		source: pageName+'?action=ajax_rtm_exec_hosts',
		autoFocus: true,
		minLength: 0,
		select: function(event,ui) {
			$('#exec_host').val(ui.item.id);
			callBack = $('#call_back_exec_host').val();
			if (callBack != 'undefined') {
				if (callBack.indexOf('applyFilter') >= 0) {
					applyFilter();
				} else if (callBack.indexOf('applyGraphFilter') >= 0) {
					applyGraphFilter();
				}
			} else if (typeof applyGraphFilter === 'function') {
				applyGraphFilter();
			} else {
				applyFilter();
			}
		}
	}).css('border', 'none').css('background-color', 'transparent');

	$('#rtm_exec_host_click').css('z-index', '4');
	$('#exec_host_wrapper').unbind().dblclick(function() {
		exec_host_open = false;
		clearTimeout(exechostTimer);
		clearTimeout(exec_host_clickTimeout);
		$('#rtm_exec_host').autocomplete('close');
	}).click(function() {
		if (exec_host_open) {
			$('#rtm_exec_host').autocomplete('close');
			clearTimeout(exechostTimer);
			exec_host_open = false;
		} else {
			exec_host_clickTimeout = setTimeout(function() {
				$('#rtm_exec_host').autocomplete('search', '');
				clearTimeout(exechostTimer);
				exec_host_open = true;
			}, 200);
		}
	}).on('mouseenter', function() {
		$(this).addClass('ui-state-hover');
		$('input#rtm_exec_host').addClass('ui-state-hover');
	}).on('mouseleave', function() {
		$(this).removeClass('ui-state-hover');
		$('#rtm_exec_host').removeClass('ui-state-hover');
		exechostTimer = setTimeout(function() { $('#rtm_exec_host').autocomplete('close'); }, 800);
		exec_host_open = false;
	});

	var exechostPrefix = '';
	$('#rtm_exec_host').autocomplete('widget').each(function() {
		exechostPrefix=$(this).attr('id');

		if (exechostPrefix != '') {
			$('ul[id="'+exechostPrefix+'"]').on('mouseenter', function() {
				clearTimeout(exechostTimer);
			}).on('mouseleave', function() {
				exechostTimer = setTimeout(function() { $('#rtm_exec_host').autocomplete('close'); }, 800);
				$(this).removeClass('ui-state-hover');
				$('input#rtm_exec_host').removeClass('ui-state-hover');
			});
		}
	});

	var project_open = false;
	var projectTimer;
	$('#rtm_project').unbind().autocomplete({
	    source: pageName+'?action=ajax_rtm_projects',
	    autoFocus: true,
	    minLength: 0,
	    select: function(event,ui) {
	        $('#project').val(ui.item.id);
	        callBack = $('#call_back_project').val();
	        if (callBack != 'undefined') {
	            if (callBack.indexOf('applyFilter') >= 0) {
	                applyFilter();
	            } else if (callBack.indexOf('applyGraphFilter') >= 0) {
	                applyGraphFilter();
	            }
	        } else if (typeof applyGraphFilter === 'function') {
	            applyGraphFilter();
	        } else {
	            applyFilter();
	        }
	    }
	}).css('border', 'none').css('background-color', 'transparent');

	$('#rtm_project_click').css('z-index', '4');
	$('#project_wrapper').unbind().dblclick(function() {
	    project_open = false;
	    clearTimeout(projectTimer);
	    clearTimeout(project_clickTimeout);
	    $('#rtm_project').autocomplete('close');
	}).click(function() {
	    if (project_open) {
	        $('#rtm_project').autocomplete('close');
	        clearTimeout(projectTimer);
	        project_open = false;
	    } else {
	        project_clickTimeout = setTimeout(function() {
	            $('#rtm_project').autocomplete('search', '');
	            clearTimeout(projectTimer);
	            project_open = true;
	        }, 200);
	    }
	}).on('mouseenter', function() {
	    $(this).addClass('ui-state-hover');
	    $('input#rtm_project').addClass('ui-state-hover');
	}).on('mouseleave', function() {
	    $(this).removeClass('ui-state-hover');
	    $('#rtm_project').removeClass('ui-state-hover');
	    projectTimer = setTimeout(function() { $('#rtm_project').autocomplete('close'); }, 800);
	    project_open = false;
	});

	var projectPrefix = '';
	$('#rtm_project').autocomplete('widget').each(function() {
	    projectPrefix=$(this).attr('id');

	    if (projectPrefix != '') {
	        $('ul[id="'+projectPrefix+'"]').on('mouseenter', function() {
	            clearTimeout(projectTimer);
	        }).on('mouseleave', function() {
	            projectTimer = setTimeout(function() { $('#rtm_project').autocomplete('close'); }, 800);
	            $(this).removeClass('ui-state-hover');
	            $('input#rtm_project').removeClass('ui-state-hover');
	        });
	    }
	});

	var queue_open = false;
	var queueTimer;
	$('#rtm_queue').unbind().autocomplete({
	    source: pageName+'?action=ajax_rtm_queues',
	    autoFocus: true,
	    minLength: 0,
	    select: function(event,ui) {
	        $('#queue').val(ui.item.id);
	        callBack = $('#call_back_queue').val();
	        if (callBack != 'undefined') {
	            if (callBack.indexOf('applyFilter') >= 0) {
	                applyFilter();
	            } else if (callBack.indexOf('applyGraphFilter') >= 0) {
	                applyGraphFilter();
	            }
	        } else if (typeof applyGraphFilter === 'function') {
	            applyGraphFilter();
	        } else {
	            applyFilter();
	        }
	    }
	}).css('border', 'none').css('background-color', 'transparent');

	$('#rtm_queue_click').css('z-index', '4');
	$('#queue_wrapper').unbind().dblclick(function() {
	    queue_open = false;
	    clearTimeout(queueTimer);
	    clearTimeout(queue_clickTimeout);
	    $('#rtm_queue').autocomplete('close');
	}).click(function() {
	    if (queue_open) {
	        $('#rtm_queue').autocomplete('close');
	        clearTimeout(queueTimer);
	        queue_open = false;
	    } else {
	        queue_clickTimeout = setTimeout(function() {
	            $('#rtm_queue').autocomplete('search', '');
	            clearTimeout(queueTimer);
	            queue_open = true;
	        }, 200);
	    }
	}).on('mouseenter', function() {
	    $(this).addClass('ui-state-hover');
	    $('input#rtm_queue').addClass('ui-state-hover');
	}).on('mouseleave', function() {
	    $(this).removeClass('ui-state-hover');
	    $('#rtm_queue').removeClass('ui-state-hover');
	    queueTimer = setTimeout(function() { $('#rtm_queue').autocomplete('close'); }, 800);
	    queue_open = false;
	});

	var queuePrefix = '';
	$('#rtm_queue').autocomplete('widget').each(function() {
	    queuePrefix=$(this).attr('id');

	    if (queuePrefix != '') {
	        $('ul[id="'+queuePrefix+'"]').on('mouseenter', function() {
	            clearTimeout(queueTimer);
	        }).on('mouseleave', function() {
	            queueTimer = setTimeout(function() { $('#rtm_queue').autocomplete('close'); }, 800);
	            $(this).removeClass('ui-state-hover');
	            $('input#rtm_queue').removeClass('ui-state-hover');
	        });
	    }
	});

	var app_open = false;
	var appTimer;
	$('#rtm_app').unbind().autocomplete({
	    source: pageName+'?action=ajax_rtm_apps',
	    autoFocus: true,
	    minLength: 0,
	    select: function(event,ui) {
	        $('#app').val(ui.item.id);
	        callBack = $('#call_back_app').val();
	        if (callBack != 'undefined') {
	            if (callBack.indexOf('applyFilter') >= 0) {
	                applyFilter();
	            } else if (callBack.indexOf('applyGraphFilter') >= 0) {
	                applyGraphFilter();
	            }
	        } else if (typeof applyGraphFilter === 'function') {
	            applyGraphFilter();
	        } else {
	            applyFilter();
	        }
	    }
	}).css('border', 'none').css('background-color', 'transparent');

	$('#rtm_app_click').css('z-index', '4');
	$('#app_wrapper').unbind().dblclick(function() {
	    app_open = false;
	    clearTimeout(appTimer);
	    clearTimeout(app_clickTimeout);
	    $('#rtm_app').autocomplete('close');
	}).click(function() {
	    if (app_open) {
	        $('#rtm_app').autocomplete('close');
	        clearTimeout(appTimer);
	        app_open = false;
	    } else {
	        app_clickTimeout = setTimeout(function() {
	            $('#rtm_app').autocomplete('search', '');
	            clearTimeout(appTimer);
	            app_open = true;
	        }, 200);
	    }
	}).on('mouseenter', function() {
	    $(this).addClass('ui-state-hover');
	    $('input#rtm_app').addClass('ui-state-hover');
	}).on('mouseleave', function() {
	    $(this).removeClass('ui-state-hover');
	    $('#rtm_app').removeClass('ui-state-hover');
	    appTimer = setTimeout(function() { $('#rtm_app').autocomplete('close'); }, 800);
	    app_open = false;
	});

	var appPrefix = '';
	$('#rtm_app').autocomplete('widget').each(function() {
	    appPrefix=$(this).attr('id');

	    if (appPrefix != '') {
	        $('ul[id="'+appPrefix+'"]').on('mouseenter', function() {
	            clearTimeout(appTimer);
	        }).on('mouseleave', function() {
	            appTimer = setTimeout(function() { $('#rtm_app').autocomplete('close'); }, 800);
	            $(this).removeClass('ui-state-hover');
	            $('input#rtm_app').removeClass('ui-state-hover');
	        });
	    }
	});

	var lic_host_open = false;
	var lichostTimer;
	$('#rtm_lic_host').unbind().autocomplete({
		source: pageName+'?action=ajax_rtm_lic_hosts',
		autoFocus: true,
		minLength: 0,
		select: function(event,ui) {
			$('#lic_host').val(ui.item.id);
			callBack = $('#call_back_lic_host').val();
			if (callBack != 'undefined') {
				if (callBack.indexOf('applyFilter') >= 0) {
					applyFilter();
				} else if (callBack.indexOf('applyGraphFilter') >= 0) {
					applyGraphFilter();
				}
			} else if (typeof applyGraphFilter === 'function') {
				applyGraphFilter();
			} else {
				applyFilter();
			}
		}
	}).css('border', 'none').css('background-color', 'transparent');

	$('#rtm_lic_host_click').css('z-index', '4');
	$('#lic_host_wrapper').unbind().dblclick(function() {
		lic_host_open = false;
		clearTimeout(lichostTimer);
		clearTimeout(lic_host_clickTimeout);
		$('#rtm_lic_host').autocomplete('close');
	}).click(function() {
		if (lic_host_open) {
			$('#rtm_lic_host').autocomplete('close');
			clearTimeout(lichostTimer);
			lic_host_open = false;
		} else {
			lic_host_clickTimeout = setTimeout(function() {
				$('#rtm_lic_host').autocomplete('search', '');
				clearTimeout(lichostTimer);
				lic_host_open = true;
			}, 200);
		}
	}).on('mouseenter', function() {
		$(this).addClass('ui-state-hover');
		$('input#rtm_lic_host').addClass('ui-state-hover');
	}).on('mouseleave', function() {
		$(this).removeClass('ui-state-hover');
		$('#rtm_lic_host').removeClass('ui-state-hover');
		lichostTimer = setTimeout(function() { $('#rtm_lic_host').autocomplete('close'); }, 800);
		lic_host_open = false;
	});

	var lichostPrefix = '';
	$('#rtm_lic_host').autocomplete('widget').each(function() {
		lichostPrefix=$(this).attr('id');

		if (lichostPrefix != '') {
			$('ul[id="'+lichostPrefix+'"]').on('mouseenter', function() {
				clearTimeout(lichostTimer);
			}).on('mouseleave', function() {
				lichostTimer = setTimeout(function() { $('#rtm_lic_host').autocomplete('close'); }, 800);
				$(this).removeClass('ui-state-hover');
				$('input#rtm_lic_host').removeClass('ui-state-hover');
			});
		}
	});

	var lic_user_open = false;
	var licuserTimer;
	$('#rtm_lic_user').unbind().autocomplete({
	    source: pageName+'?action=ajax_rtm_lic_users',
	    autoFocus: true,
	    minLength: 0,
	    select: function(event,ui) {
	        $('#lic_user').val(ui.item.id);
	        callBack = $('#call_back_lic_user').val();
	        if (callBack != 'undefined') {
	            if (callBack.indexOf('applyFilter') >= 0) {
	                applyFilter();
	            } else if (callBack.indexOf('applyGraphFilter') >= 0) {
	                applyGraphFilter();
	            }
	        } else if (typeof applyGraphFilter === 'function') {
	            applyGraphFilter();
	        } else {
	            applyFilter();
	        }
	    }
	}).css('border', 'none').css('background-color', 'transparent');

	$('#rtm_lic_user_click').css('z-index', '4');
	$('#lic_user_wrapper').unbind().dblclick(function() {
	    lic_user_open = false;
	    clearTimeout(licuserTimer);
	    clearTimeout(lic_user_clickTimeout);
	    $('#rtm_lic_user').autocomplete('close');
	}).click(function() {
	    if (lic_user_open) {
	        $('#rtm_lic_user').autocomplete('close');
	        clearTimeout(licuserTimer);
	        lic_user_open = false;
	    } else {
	        lic_user_clickTimeout = setTimeout(function() {
	            $('#rtm_lic_user').autocomplete('search', '');
	            clearTimeout(licuserTimer);
	            lic_user_open = true;
	        }, 200);
	    }
	}).on('mouseenter', function() {
	    $(this).addClass('ui-state-hover');
	    $('input#rtm_lic_user').addClass('ui-state-hover');
	}).on('mouseleave', function() {
	    $(this).removeClass('ui-state-hover');
	    $('#rtm_lic_user').removeClass('ui-state-hover');
	    licuserTimer = setTimeout(function() { $('#rtm_lic_user').autocomplete('close'); }, 800);
	    lic_user_open = false;
	});

	var licuserPrefix = '';
	$('#rtm_lic_user').autocomplete('widget').each(function() {
	    licuserPrefix=$(this).attr('id');

	    if (licuserPrefix != '') {
	        $('ul[id="'+licuserPrefix+'"]').on('mouseenter', function() {
	            clearTimeout(licuserTimer);
	        }).on('mouseleave', function() {
	            licuserTimer = setTimeout(function() { $('#rtm_lic_user').autocomplete('close'); }, 800);
	            $(this).removeClass('ui-state-hover');
	            $('input#rtm_lic_user').removeClass('ui-state-hover');
	        });
	    }
	});

	var lic_feature_open = false;
	var licfeatureTimer;
	$('#rtm_lic_feature').unbind().autocomplete({
	    source: pageName+'?action=ajax_rtm_lic_features',
	    autoFocus: true,
	    minLength: 0,
	    select: function(event,ui) {
	        $('#lic_feature').val(ui.item.id);
	        callBack = $('#call_back_lic_feature').val();
	        if (callBack != 'undefined') {
	            if (callBack.indexOf('applyFilter') >= 0) {
	                applyFilter();
	            } else if (callBack.indexOf('applyGraphFilter') >= 0) {
	                applyGraphFilter();
	            }
	        } else if (typeof applyGraphFilter === 'function') {
	            applyGraphFilter();
	        } else {
	            applyFilter();
	        }
	    }
	}).css('border', 'none').css('background-color', 'transparent');

	$('#rtm_lic_feature_click').css('z-index', '4');
	$('#lic_feature_wrapper').unbind().dblclick(function() {
	    lic_feature_open = false;
	    clearTimeout(licfeatureTimer);
	    clearTimeout(lic_feature_clickTimeout);
	    $('#rtm_lic_feature').autocomplete('close');
	}).click(function() {
	    if (lic_feature_open) {
	        $('#rtm_lic_feature').autocomplete('close');
	        clearTimeout(licfeatureTimer);
	        lic_feature_open = false;
	    } else {
	        lic_feature_clickTimeout = setTimeout(function() {
	            $('#rtm_lic_feature').autocomplete('search', '');
	            clearTimeout(licfeatureTimer);
	            lic_feature_open = true;
	        }, 200);
	    }
	}).on('mouseenter', function() {
	    $(this).addClass('ui-state-hover');
	    $('input#rtm_lic_feature').addClass('ui-state-hover');
	}).on('mouseleave', function() {
	    $(this).removeClass('ui-state-hover');
	    $('#rtm_lic_feature').removeClass('ui-state-hover');
	    licfeatureTimer = setTimeout(function() { $('#rtm_lic_feature').autocomplete('close'); }, 800);
	    lic_feature_open = false;
	});

	var licfeaturePrefix = '';
	$('#rtm_lic_feature').autocomplete('widget').each(function() {
	    licfeaturePrefix=$(this).attr('id');

	    if (licfeaturePrefix != '') {
	        $('ul[id="'+licfeaturePrefix+'"]').on('mouseenter', function() {
	            clearTimeout(licfeatureTimer);
	        }).on('mouseleave', function() {
	            licfeatureTimer = setTimeout(function() { $('#rtm_lic_feature').autocomplete('close'); }, 800);
	            $(this).removeClass('ui-state-hover');
	            $('input#rtm_lic_feature').removeClass('ui-state-hover');
	        });
	    }
	});
}
