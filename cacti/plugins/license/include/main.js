// $Id$
/* Generic Cookie Functions */
function rtm_createCookie(name, value, days) {
	if (days) {
		var date    = new Date();
		date.setTime(date.getTime() + (days*24*60*60*1000));
		var expires = "; expires=" + date.toGMTString();
	} else {
		var expires = "";
	}

	document.cookie  = name + "=" + value + expires + "; path=/";
}

function rtm_readCookie(name) {
	var nameEQ = name + "=";

	var ca     = document.cookie.split(';');

	for (var i=0; i < ca.length; i++) {
		var c = ca[i];

		while (c.charAt(0)==' ') {
			c = c.substring(1, c.length);
		}

		if (c.indexOf(nameEQ) == 0) {
			return c.substring(nameEQ.length, c.length);
		}
	}

	return null;
}

function rtm_eraseCookie(name) {
	createCookie(name, "", -1);
}

function makeMultiSelect(objForm) {
	selects = document.getElementsByTagName("select");

	for (var i=0; i < selects.length; i++) {
		mySelect = selects[i];
		if (mySelect.type == "select-one") {
			if (mySelect.name != "refresh" && mySelect.name != "rows_selector") {
				mySelect.multiple = true;
				mySelect.size = 4;
			}
		}
	}
}

