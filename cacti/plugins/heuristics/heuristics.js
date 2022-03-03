// $Id$
// Borrowing some functions from Cacti

function resizeMenu() {
	if ($('#menu').length) {
		menuHeight = 0;
        $('#menu').children().each(function() {
            menuHeight += $(this).height();
        });
        screenHeight = $(window).height()-110;
        myHeight = (screenHeight > menuHeight ? screenHeight:menuHeight);
        $('#menu').css("height", myHeight +  'px');
    }

    if ($('#domRoot').length) {
        menuHeight = 0;
        $('#domRoot').children().each(function() {
            menuHeight += $(this).height();
        });
        screenHeight = $(window).height()-110;
        myHeight = (screenHeight > menuHeight ? screenHeight:menuHeight);
        $('#domRoot').css("height", myHeight + 'px');
    }
}

function loadRTMPage(href) {
	$('#spinner').html("<img src='../grid/images/wait-loader.gif'>");

	$.ajaxQ.abortAll();
	$.get(href+(href.indexOf('?') == -1 ? '?':'&'), function(html) {
		var htmlObject  = $(html);

		var matches = html.match(/<title>(.*?)<\/title>/);
		if (matches) {
			var htmlTitle = matches[1];
			$('title').text(htmlTitle);
		}

		if (htmlObject.length) {
			var breadCrumbs = htmlObject.find('.breadcrumbs').html();
			if (breadCrumbs) {
				$('.breadcrumbs').html(breadCrumbs);
			}

			var content = htmlObject.find('#main').html();
			if (content) {
				$('#main').html(content);
			}else{
				$('#main').html(html);
			}
		}

		$('#spinner').html('');

		applySkin();

		if (typeof window.history.pushState !== 'undefined') {
			window.history.pushState({page:href}, htmlTitle, href);
		}

		window.scrollTo(0, 0);

		Pace.stop();

		return false;
	});

	return false;
}

function sortMe(sort_column, sort_direction, panel) {
	$('#spinner').html("<img src='../grid/images/wait-loader.gif'>");

	strURL  = '&sort_direction='+sort_direction;
	strURL += '&sort_column='+sort_column;

	initializePanel(panel, strURL);
}

