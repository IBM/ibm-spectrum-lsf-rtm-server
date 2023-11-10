// $Id$
// Host Autocomplete Magic
var pageName = basename($(location).attr('pathname'));
if (!!!ibmSVG) {
	var ibmSVG = '<svg class="ibm-logo" viewBox="0 0 57.4 20"> <g> <polygon points="0,0 9.8,0 9.8,1.3 0,1.3 0,0 "></polygon> <polygon points="0,2.7 9.8,2.7 9.8,4 0,4 0,2.7 "></polygon> <polygon points="2.8,5.4 7,5.4 7,6.6 2.8,6.6 2.8,5.4 "></polygon> <polygon points="2.8,8 7,8 7,9.3 2.8,9.3 2.8,8 "></polygon> <polygon points="2.8,10.7 7,10.7 7,12 2.8,12 2.8,10.7 "></polygon> <polygon points="2.8,13.4 7,13.4 7,14.7 2.8,14.7 2.8,13.4 "></polygon> <polygon points="0,16.1 9.8,16.1 9.8,17.3 0,17.3 0,16.1 "></polygon> <polygon points="0,18.7 9.8,18.7 9.8,20 0,20 0,18.7 "></polygon> <path d="M11.2,2.7h15.1l0,0c0.1,0.2,0.4,1,0.4,1.3l0,0H11.2V2.7L11.2,2.7L11.2,2.7z"></path> <polygon points="14,5.4 18.2,5.4 18.2,6.6 14,6.6 14,5.4 "></polygon> <path d="M22.4,5.4h4.5l0,0c0,0.3-0.1,1-0.2,1.3l0,0h-4.3L22.4,5.4L22.4,5.4L22.4,5.4z"></path> <path d="M14,10.7h10.8l0,0c0.3,0.2,0.9,0.9,1.2,1.3l0,0H14V10.7L14,10.7L14,10.7z"></path> <polygon points="14,13.4 18.2,13.4 18.2,14.7 14,14.7 14,13.4 "></polygon> <path d="M22.4,13.4h4.3l0,0c0.1,0.2,0.2,1,0.2,1.3l0,0h-4.5L22.4,13.4L22.4,13.4L22.4,13.4z"></path> <path d="M11.2,16.1h15.5l0,0c0,0.3-0.3,1-0.4,1.3l0,0H11.2V16.1L11.2,16.1L11.2,16.1z"></path> <path d="M11.2,18.7h14.1l0,0C24,19.8,22.9,20,21,20l0,0h-1.4h-8.4L11.2,18.7L11.2,18.7L11.2,18.7z"></path> <polygon points="28,0 36,0 36.5,1.3 28,1.3 28,0 "></polygon> <polygon points="28,2.7 36.9,2.7 37.3,4 28,4 28,2.7 "></polygon> <polygon points="30.8,5.4 37.8,5.4 38.2,6.6 30.8,6.6 30.8,5.4 "></polygon> <polygon points="39.8,8 47.6,8 47.6,9.3 43.4,9.3 43.4,8.2 43.1,9.3 39.4,9.3 39.8,8 "></polygon> <polygon points="39.1,9.3 35.4,9.3 35,8.2 35,9.3 30.8,9.3 30.8,8 38.7,8 39.1,9.3 "></polygon> <polygon points="30.8,10.7 35,10.7 35,12 30.8,12 30.8,10.7 "></polygon> <polygon points="30.8,13.4 35,13.4 35,14.7 30.8,14.7 30.8,13.4 "></polygon> <polygon points="28,16.1 35,16.1 35,17.3 28,17.3 28,16.1 "></polygon> <polygon points="28,18.7 35,18.7 35,20 28,20 28,18.7 "></polygon> <polygon points="42.4,0 50.4,0 50.4,1.3 42,1.3 42.4,0 "></polygon> <polygon points="41.6,2.7 50.4,2.7 50.4,4 41.1,4 41.6,2.7 "></polygon> <polygon points="40.7,5.4 47.6,5.4 47.6,6.6 40.2,6.6 40.7,5.4 "></polygon> <polygon points="43.4,10.7 47.6,10.7 47.6,12 43.4,12 43.4,10.7 "></polygon> <polygon points="43.4,13.4 47.6,13.4 47.6,14.7 43.4,14.7 43.4,13.4 "></polygon> <polygon points="43.4,16.1 50.4,16.1 50.4,17.3 43.4,17.3 43.4,16.1 "></polygon> <polygon points="43.4,18.7 50.4,18.7 50.4,20 43.4,20 43.4,18.7 "></polygon> <polygon points="35.9,10.7 42.6,10.7 42.2,12 36.3,12 35.9,10.7 "></polygon> <polygon points="36.8,13.4 41.7,13.4 41.2,14.7 37.2,14.7 36.8,13.4 "></polygon> <polygon points="37.7,16.1 40.8,16.1 40.3,17.3 38.1,17.3 37.7,16.1 "></polygon> <polygon points="38.6,18.7 39.9,18.7 39.4,20 39,20 38.6,18.7 "></polygon> <path d="M14,9.3h10.8l0,0c0.3-0.2,1-0.9,1.3-1.3l0,0H14V9.3L14,9.3L14,9.3z"></path> <path d="M11.2,1.3h14.1l0,0C24,0.2,22.9,0,21,0l0,0h-1.4h-8.4L11.2,1.3L11.2,1.3L11.2,1.3z"></path> <path d="M55.1,19.9c1.2,0,2.3-0.9,2.3-2.2c0-1.3-1-2.2-2.3-2.2c-1.2,0-2.3,0.9-2.3,2.2C52.9,19,53.9,19.9,55.1,19.9 L55.1,19.9z M53.3,17.7c0-1.1,0.8-1.9,1.8-1.9s1.8,0.8,1.8,1.9s-0.8,1.9-1.8,1.9S53.3,18.8,53.3,17.7L53.3,17.7z M54.7,17.9h0.5 l0.7,1.1h0.4l-0.7-1.1c0.4,0,0.7-0.2,0.7-0.7s-0.3-0.7-0.9-0.7h-1V19h0.4v-1.1H54.7z M54.7,17.5v-0.8h0.5c0.3,0,0.6,0.1,0.6,0.4 c0,0.4-0.3,0.4-0.6,0.4H54.7L54.7,17.5z"></path> </g> </svg>';
}

function themeReady() {
	var pageName = basename($(location).attr('pathname'));
	var hostTimer = false;
	var clickTimeout = false;
	var hostOpen = false;

	if ($('#cactiPageBottom').length == 0) {
		$('<div id="cactiPageBottom" class="cactiPageBottom"></a></div>').insertAfter('#cactiContent');
	}

	// Setup the navigation menu
	setMenuVisibility();

	// Add nice search filter to filters
	if ($('input[id="filter"]').length > 0 && $('input[id="filter"] + i[class="fa fa-search filter"]').length < 1) {
		$('input[id="filter"]').after("<i class='fa fa-search filter'/>").attr('autocomplete', 'off').attr('placeholder', searchFilter).parent('td').css('white-space', 'nowrap');
	}

	if ($('input[id="filterd"]').length > 0 && $('input[id="filterd"] + i[class="fa fa-search filter"]').length < 1) {
		$('input[id="filterd"]').after("<i class='fa fa-search filter'/>").attr('autocomplete', 'off').attr('placeholder', searchFilter).parent('td').css('white-space', 'nowrap');
	}

	if ($('input[id="rfilter"]').length > 0 && $('input[id="rfilter"] + i[class="fa fa-search filter"]').length < 1) {
		$('input[id="rfilter"]').after("<i class='fa fa-search filter'/>").attr('autocomplete', 'off').attr('placeholder', searchRFilter).parent('td').css('white-space', 'nowrap');
	}

	// Turn file buttons into jQueryUI buttons
	$('.import_label').button();
	$('.import_button').change(function() {
		text=this.value;
		setImportFile(text);
	});
	setImportFile(noFileSelected);

	function setImportFile(fileText) {
		$('.import_text').text(fileText);
	}

	/* Start clean up */
	if (!$('.loginBody').length) {
		$('input[type="text"]').addClass('ui-state-default ui-corner-all');
	}

	if (!!!brandName) {
		var brandName='IBM Spectrum';
		var brandNameBold='IBM <b>Spectrum</b>';
		var productName='LSF RTM 10.2.0.15';
		var copyRight='© Copyright International Business Machines Corp. 1992, 2023. US Government Users Restricted Rights - Use, duplication or disclosure restricted by GSA ADP Schedule Contract with IBM Corp. Portions Copyright © 2004, 2023 The Cacti Group, Inc.';
	}

	logoSVG = '<svg class="company-logo" style="fille: rgb(255,255,255);" aria-hidden="false"><use xlink:href="#LSF"><symbol viewBox="0 0 36 36" id="LSF" xmlns:xlink="http://www.w3.org/1999/xlink"> <polygon points="8.5,15 6.5,15 7.5,13 9.5,13 "></polygon> <polygon points="12.5,15 10.5,15 11.5,13 13.5,13 "></polygon> <polygon points="16.5,15 14.5,15 15.5,13 17.5,13 "></polygon> <polygon points="10,12 8,12 9,10 11,10 "></polygon> <polygon points="14,12 12,12 13,10 15,10 "></polygon> <polygon points="18,12 16,12 17,10 19,10 "></polygon> <polygon points="11.5,9 9.5,9 10.5,7 12.5,7 "></polygon> <polygon points="15.5,9 13.5,9 14.5,7 16.5,7 "></polygon> <polygon points="19.5,9 17.5,9 18.5,7 20.5,7 "></polygon> <polygon points="2,12 0,12 1,10 3,10 "></polygon> <polygon points="6,12 4,12 5,10 7,10 "></polygon> <polygon points="18.3,17 5.5,17 4.5,18 5.5,19 19.5,19 25.1,9.3 23.8,7.6 "></polygon> <g> <polygon points="30.7,27 35.8,18 27.9,4 25.3,4 31,14 25.3,24 18.4,24 20,27  "></polygon> <polygon points="14.3,22 6.3,22 12.1,32 27.9,32 29.3,29 18.6,29  "></polygon> </g> </symbol></use></svg>';
	logodark = '<svg class="company-logo" style="fille: rgb(255,255,255);" aria-hidden="false" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 32 32"><defs><linearGradient id="ae8e5876-815e-4dd7-9b8b-48628e568be2" x1="16.858" y1="3.531" x2="26.824" y2="20.792" gradientUnits="userSpaceOnUse"><stop offset="0.051" stop-color="#fff" stop-opacity="0"/><stop offset="0.399" stop-color="#fff"/></linearGradient><linearGradient id="bf25f80d-e501-4f40-a567-77eff671c830" x1="-738.094" y1="-589.368" x2="-746.906" y2="-604.632" gradientTransform="translate(748 620)" gradientUnits="userSpaceOnUse"><stop offset="0.139" stop-opacity="0"/><stop offset="0.569"/></linearGradient><linearGradient id="f7efb8e6-41c7-4924-99a8-f5ed8688d730" x1="16" y1="10" x2="25" y2="10" gradientUnits="userSpaceOnUse"><stop offset="0.39" stop-color="#fff"/><stop offset="0.949" stop-color="#fff" stop-opacity="0"/></linearGradient><linearGradient id="a224d0e0-1015-4234-8ffb-5616e014b46d" x1="10" y1="23" x2="10" y2="6" gradientUnits="userSpaceOnUse"><stop offset="0.58" stop-color="#fff"/><stop offset="0.9" stop-color="#fff" stop-opacity="0"/></linearGradient><mask id="ade8b32c-a3e7-4118-8be9-f9cc710af085" x="0" y="0" width="32" height="32" maskUnits="userSpaceOnUse"><path d="M23,23H21V17a2,2,0,0,1,2-2h5.259L20.386,1.5,22.114.5l8.75,15A1,1,0,0,1,30,17H23Z" fill="url(#ae8e5876-815e-4dd7-9b8b-48628e568be2)"/><path d="M8.136,28.5l-7-12a1,1,0,0,1,0-1.008l7-12A1,1,0,0,1,9,3h6a2,2,0,0,1,2,2V23H15V5H9.574L3.158,16,9.864,27.5Z" fill="#fff"/><rect y="16" width="11" height="14" transform="translate(11 46) rotate(180)" fill="url(#bf25f80d-e501-4f40-a567-77eff671c830)"/><rect x="16" y="9" width="9" height="2" fill="url(#f7efb8e6-41c7-4924-99a8-f5ed8688d730)"/><rect x="9" y="6" width="2" height="17" fill="url(#a224d0e0-1015-4234-8ffb-5616e014b46d)"/></mask><linearGradient id="bbc7ed7f-c57c-44e5-8fc3-c01e73cc0516" y1="32" x2="32" gradientUnits="userSpaceOnUse"><stop offset="0.1" stop-color="#3ddbd9"/><stop offset="0.9" stop-color="#24a148"/></linearGradient></defs><g id="acfb902f-694a-4283-a4d5-95ffda27d60d" data-name="Layer 2"><g id="a6726dba-b328-4c3c-a3b4-075db23147ff" data-name="Dark theme icons"><g><g mask="url(#ade8b32c-a3e7-4118-8be9-f9cc710af085)"><rect width="32" height="32" fill="url(#bbc7ed7f-c57c-44e5-8fc3-c01e73cc0516)"/></g><circle cx="10" cy="27" r="2" fill="#f4f4f4"/><circle cx="22" cy="27" r="2" fill="#f4f4f4"/><circle cx="16" cy="27" r="2" fill="#f4f4f4"/></g></g></g></svg>';
	logolight = '<svg class="company-logo" style="fille: rgb(255,255,255);" aria-hidden="false" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 32 32"><defs><linearGradient id="bf03047d-cf17-4bb5-9063-2e93c6fd8ea0" x1="16.858" y1="3.531" x2="26.824" y2="20.792" gradientUnits="userSpaceOnUse"><stop offset="0.051" stop-color="#fff" stop-opacity="0"/><stop offset="0.399" stop-color="#fff"/></linearGradient><linearGradient id="b1fc551f-c2ac-4dd9-aad1-e896896997d3" x1="-738.094" y1="-589.368" x2="-746.906" y2="-604.632" gradientTransform="translate(748 620)" gradientUnits="userSpaceOnUse"><stop offset="0.139" stop-opacity="0"/><stop offset="0.569"/></linearGradient><linearGradient id="a571ae98-aff5-47ac-b9c4-c2afd1cca519" x1="16" y1="10" x2="25" y2="10" gradientUnits="userSpaceOnUse"><stop offset="0.39" stop-color="#fff"/><stop offset="0.949" stop-color="#fff" stop-opacity="0"/></linearGradient><linearGradient id="e03918e9-e5da-44c4-b318-c55970117ed9" x1="10" y1="23" x2="10" y2="6" gradientUnits="userSpaceOnUse"><stop offset="0.58" stop-color="#fff"/><stop offset="0.9" stop-color="#fff" stop-opacity="0"/></linearGradient><mask id="ebb2d48a-879e-4700-afd3-8f10f13e8b6f" x="0" y="0" width="32" height="32" maskUnits="userSpaceOnUse"><path d="M23,23H21V17a2,2,0,0,1,2-2h5.259L20.386,1.5,22.114.5l8.75,15A1,1,0,0,1,30,17H23Z" fill="url(#bf03047d-cf17-4bb5-9063-2e93c6fd8ea0)"/><path d="M8.136,28.5l-7-12a1,1,0,0,1,0-1.008l7-12A1,1,0,0,1,9,3h6a2,2,0,0,1,2,2V23H15V5H9.574L3.158,16,9.864,27.5Z" fill="#fff"/><rect y="16" width="11" height="14" transform="translate(11 46) rotate(180)" fill="url(#b1fc551f-c2ac-4dd9-aad1-e896896997d3)"/><rect x="16" y="9" width="9" height="2" fill="url(#a571ae98-aff5-47ac-b9c4-c2afd1cca519)"/><rect x="9" y="6" width="2" height="17" fill="url(#e03918e9-e5da-44c4-b318-c55970117ed9)"/></mask><linearGradient id="f1348c0d-553f-4c9e-ba35-1434c43ae1ad" y1="32" x2="32" gradientUnits="userSpaceOnUse"><stop offset="0.1" stop-color="#08bdba"/><stop offset="0.9" stop-color="#198038"/></linearGradient></defs><g id="aed13bc3-a0cb-47b1-b69c-f47c24d55b9d" data-name="Layer 2"><g id="ad61ea00-7c7a-418b-9cd6-7550665568fb" data-name="Light theme icons"><g><g mask="url(#ebb2d48a-879e-4700-afd3-8f10f13e8b6f)"><rect width="32" height="32" fill="url(#f1348c0d-553f-4c9e-ba35-1434c43ae1ad)"/></g><circle cx="10" cy="27" r="2" fill="#001d6c"/><circle cx="22" cy="27" r="2" fill="#001d6c"/><circle cx="16" cy="27" r="2" fill="#001d6c"/></g></g></g></svg>';
	logomonodark = '<svg class="company-logo" style="fille: rgb(255,255,255);" aria-hidden="false" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 32 32"><defs><linearGradient id="f972d274-63a3-4a8a-9a06-3643ba35fba3" x1="16.858" y1="3.531" x2="26.824" y2="20.792" gradientUnits="userSpaceOnUse"><stop offset="0.051" stop-color="#fff" stop-opacity="0"/><stop offset="0.399" stop-color="#fff"/></linearGradient><linearGradient id="a27e9fd4-56cc-4340-9c64-c8e8700a8699" x1="-738.094" y1="-589.368" x2="-746.906" y2="-604.632" gradientTransform="translate(748 620)" gradientUnits="userSpaceOnUse"><stop offset="0.139" stop-opacity="0"/><stop offset="0.569"/></linearGradient><linearGradient id="a41899a5-8c2c-40e8-8ab2-e6baa43312a8" x1="16" y1="10" x2="25" y2="10" gradientUnits="userSpaceOnUse"><stop offset="0.39" stop-color="#fff"/><stop offset="0.949" stop-color="#fff" stop-opacity="0"/></linearGradient><linearGradient id="be33e8f7-5617-4336-837c-263202b94253" x1="10" y1="23" x2="10" y2="6" gradientUnits="userSpaceOnUse"><stop offset="0.58" stop-color="#fff"/><stop offset="0.9" stop-color="#fff" stop-opacity="0"/></linearGradient><mask id="ba5e2a56-c1b5-41c8-8584-c6ba12677c5e" x="0" y="0" width="32" height="32" maskUnits="userSpaceOnUse"><path d="M23,23H21V17a2,2,0,0,1,2-2h5.259L20.386,1.5,22.114.5l8.75,15A1,1,0,0,1,30,17H23Z" fill="url(#f972d274-63a3-4a8a-9a06-3643ba35fba3)"/><path d="M8.136,28.5l-7-12a1,1,0,0,1,0-1.008l7-12A1,1,0,0,1,9,3h6a2,2,0,0,1,2,2V23H15V5H9.574L3.158,16,9.864,27.5Z" fill="#fff"/><rect y="16" width="11" height="14" transform="translate(11 46) rotate(180)" fill="url(#a27e9fd4-56cc-4340-9c64-c8e8700a8699)"/><rect x="16" y="9" width="9" height="2" fill="url(#a41899a5-8c2c-40e8-8ab2-e6baa43312a8)"/><rect x="9" y="6" width="2" height="17" fill="url(#be33e8f7-5617-4336-837c-263202b94253)"/></mask></defs><g id="a8818e25-6bc9-44ca-8e63-bb004c78c88a" data-name="Layer 2"><g id="a2b0278d-8835-49a0-ac90-00387c4123f9" data-name="Layer 9"><g><g mask="url(#ba5e2a56-c1b5-41c8-8584-c6ba12677c5e)"><rect width="32" height="32" fill="#fff"/></g><circle cx="10" cy="27" r="2" fill="#fff"/><circle cx="22" cy="27" r="2" fill="#fff"/><circle cx="16" cy="27" r="2" fill="#fff"/></g></g></g></svg>';
	logomonolight = '<svg class="company-logo" style="fille: rgb(255,255,255);" aria-hidden="false" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 32 32"><defs><linearGradient id="e8b6fb7a-3a24-4a02-8584-87a7a8a7ae36" x1="16.858" y1="3.531" x2="26.824" y2="20.792" gradientUnits="userSpaceOnUse"><stop offset="0.051" stop-color="#fff" stop-opacity="0"/><stop offset="0.399" stop-color="#fff"/></linearGradient><linearGradient id="a41d0a9c-46ee-4a38-8ad2-ef96054f6b4b" x1="-738.094" y1="-589.368" x2="-746.906" y2="-604.632" gradientTransform="translate(748 620)" gradientUnits="userSpaceOnUse"><stop offset="0.139" stop-opacity="0"/><stop offset="0.569"/></linearGradient><linearGradient id="a3f2c23c-9eee-472a-8b63-206678b34acd" x1="16" y1="10" x2="25" y2="10" gradientUnits="userSpaceOnUse"><stop offset="0.39" stop-color="#fff"/><stop offset="0.949" stop-color="#fff" stop-opacity="0"/></linearGradient><linearGradient id="fd315509-fc0a-4e54-af81-1bc12563bcd8" x1="10" y1="23" x2="10" y2="6" gradientUnits="userSpaceOnUse"><stop offset="0.58" stop-color="#fff"/><stop offset="0.9" stop-color="#fff" stop-opacity="0"/></linearGradient><mask id="f7e20982-acf3-4bc8-82d3-c29174cc0dff" x="0" y="0" width="32" height="32" maskUnits="userSpaceOnUse"><path d="M23,23H21V17a2,2,0,0,1,2-2h5.259L20.386,1.5,22.114.5l8.75,15A1,1,0,0,1,30,17H23Z" fill="url(#e8b6fb7a-3a24-4a02-8584-87a7a8a7ae36)"/><path d="M8.136,28.5l-7-12a1,1,0,0,1,0-1.008l7-12A1,1,0,0,1,9,3h6a2,2,0,0,1,2,2V23H15V5H9.574L3.158,16,9.864,27.5Z" fill="#fff"/><rect y="16" width="11" height="14" transform="translate(11 46) rotate(180)" fill="url(#a41d0a9c-46ee-4a38-8ad2-ef96054f6b4b)"/><rect x="16" y="9" width="9" height="2" fill="url(#a3f2c23c-9eee-472a-8b63-206678b34acd)"/><rect x="9" y="6" width="2" height="17" fill="url(#fd315509-fc0a-4e54-af81-1bc12563bcd8)"/></mask></defs><g id="b8493614-2168-478b-b6e6-9574b8d148d2" data-name="Layer 2"><g id="e3c3bc39-5647-4e1a-ac63-d6af5ca1f3fd" data-name="Layer 8"><g><g mask="url(#f7e20982-acf3-4bc8-82d3-c29174cc0dff)"><rect width="32" height="32"/></g><circle cx="10" cy="27" r="2"/><circle cx="22" cy="27" r="2"/><circle cx="16" cy="27" r="2"/></g></g></g></svg>';

	if ($('.headerbar-logo').length == 0) {
		$('#tabs').prepend('<div class="headerbar-logo"></div><div class="headerbar-branding"><span class="brand-name">'+brandName+'</span></br>'+productName+'</div>');
		$('.headerbar-logo').html(logodark);
	}

	/* clean up the navigation menu */
	$('.cactiConsoleNavigationArea').find('#menu').appendTo($('.cactiConsoleNavigationArea').find('#navigation'));
	$('.cactiConsoleNavigationArea').find('#navigation > table').remove();

	$('.maintabs nav ul li a.lefttab').each(function() {
		id = $(this).attr('id');

		if (id == 'tab-graphs' && $(this).parent().hasClass('maintabs-has-submenu') == 0) {
			$(this).parent().addClass('maintabs-has-submenu');
			$('<div class="dropdownMenu">'
				+'<ul id="submenu-tab-graphs" class="submenuoptions" style="display:none;">'
					+'<li><a id="tab-graphs-tree-view" href="'+urlPath+'graph_view.php?action=tree">'+treeView+'</a></li>'
					+'<li><a id="tab-graphs-list-view" href="'+urlPath+'graph_view.php?action=list">'+listView+'</a></li>'
					+'<li><a id="tab-graphs-pre_view" href="'+urlPath+'graph_view.php?action=preview">'+previewView+'</a></li>'
				+'</ul>'
			+'</div>').appendTo('body');
		}
	});

	/* user menu on the right ... */
	if ($('.usertabs').length == 0) {
		$('.loggedInAs').show();
		$('#userDocumentation').remove();
		$('#userCommunity').remove();
		$('.menuHr').remove();
		$('<div class="maintabs usertabs">'
			+'<nav><ul>'
				+'<li><a id="menu-user-cloud" aria-label="LSF On IBM Cloud" title="LSF On IBM Cloud" href="https://ibm.biz/LSFonIBMCloud" target="_blank" rel="noopener noreferrer"><i class="fa fa-cloud"></i></a></li>'
				+'<li><a id="menu-user-help" class="usertabs-submenu" href="#"><i class="fa fa-question"></i></a></li>'
				+'<li class="action-icon-user"><a class="pic" href="#"><i class="fa fa-user"></i></a></li>'
			+'</ul></nav>'
		+'</div>').insertAfter('.maintabs');

		$('<div class="dropdownMenu">'
			+'<ul id="submenu-user-help" class="submenuoptions right" style="display:none;">'
				+'<li><a href="https://www.ibm.com/support/knowledgecenter/SSZT2D_10.2.0" target="_blank" rel="noopener noreferrer"><span>RTM Help</span></a></li>'
				+'<li><hr class="menu"></li>'
				+'<li><a href="'+urlPath+'about.php"><span>'+aboutCacti+'</span></a></li>'
			+'</ul>'
		+'</div>').appendTo('body');
	}

	ajaxAnchors();

	/* User Menu */
	$('.menuoptions').parent().appendTo('body');

	$(window).trigger('resize');

	$('.action-icon-user').unbind().click(function(event) {
		event.preventDefault();

		if ($('.menuoptions').is(':visible') === false) {
			$('.submenuoptions').slideUp(120);
			$('.menuoptions').slideDown(120);
		} else {
			$('.menuoptions').slideUp(120);
		}

		return false;
	});

	/* Highlight sortable table columns */
	$('.tableHeader th').has('i.fa-sort').removeClass('tableHeaderColumnHover tableHeaderColumnSelected');
	$('.tableHeader th').has('i.fa-sort-up').addClass('tableHeaderColumnSelected');
	$('.tableHeader th').has('i.fa-sort-down').addClass('tableHeaderColumnSelected');
	$('.tableHeader th').has('i.fa-sort').hover(
		function() {
			$(this).addClass('tableHeaderColumnHover');
		}, function() {
			$(this).removeClass('tableHeaderColumnHover');
		}
	);

	$('select.colordropdown').dropcolor();

	$('select').not('.colordropdown').each(function() {
		if ($(this).prop('multiple') != true) {
			$(this).each(function() {
				id = $(this).attr('id');

				$(this).selectmenu({
					open: function(event, ui) {
						var instance = $(this).selectmenu('instance');
						instance.menuInstance.focus(null, instance._getSelectedItem());
					},
					change: function(event, ui) {
						$(this).val(ui.item.value).change();
					},
					position: {
						my: "left top",
						at: "left bottom",
						collision: "flip"
					},
					width: 'auto'
				});

				$('#'+id+'-menu').css('max-height', '250px');
			});
		}
	});

	$('#host').unbind().autocomplete({
		source: pageName+'?action=ajax_hosts',
		autoFocus: true,
		minLength: 0,
		select: function(event,ui) {
			$('#host_id').val(ui.item.id);
			callBack = $('#call_back').val();
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
	}).addClass('ui-state-default ui-selectmenu-text').css('border', 'none').css('background-color', 'transparent');

	$('#host_click').css('z-index', '4');
	$('#host_wrapper').unbind().dblclick(function() {
		hostOpen = false;
		clearTimeout(hostTimer);
		clearTimeout(clickTimeout);
		$('#host').autocomplete('close').select();
	}).click(function() {
		if (hostOpen) {
			$('#host').autocomplete('close');
			clearTimeout(hostTimer);
			hostOpen = false;
		} else {
			clickTimeout = setTimeout(function() {
				$('#host').autocomplete('search', '');
				clearTimeout(hostTimer);
				hostOpen = true;
			}, 200);
		}
		$('#host').select();
	}).on('mouseenter', function() {
		$(this).addClass('ui-state-hover');
		$('input#host').addClass('ui-state-hover');
	}).on('mouseleave', function() {
		$(this).removeClass('ui-state-hover');
		$('#host').removeClass('ui-state-hover');
		hostTimer = setTimeout(function() { $('#host').autocomplete('close'); }, 800);
		hostOpen = false;
	});

	var hostPrefix = '';
	$('#host').autocomplete('widget').each(function() {
		hostPrefix=$(this).attr('id');

		if (hostPrefix != '') {
			$('ul[id="'+hostPrefix+'"]').on('mouseenter', function() {
				clearTimeout(hostTimer);
			}).on('mouseleave', function() {
				hostTimer = setTimeout(function() { $('#host').autocomplete('close'); }, 800);
				$(this).removeClass('ui-state-hover');
				$('input#host').removeClass('ui-state-hover');
			});
		}
	});

	/* End clean up */

	/* Notification Handler */
	if ($('#message').length) {
	//	alert($('#message_container').html());
	}

	/* Replace icons */
	$('.fa-arrow-down').addClass('fa-chevron-down').removeClass('fa-arrow-down');
	$('.fa-arrow-up').addClass('fa-chevron-up').removeClass('fa-arrow-up');
	$('.fa-remove').addClass('fa-trash-o').removeClass('fa-remove');

	if($('.versionInfo').parent().hasClass('loginCenter')){
		version_div_html = $('.versionInfo').html();
		$('.versionInfo').remove();
		$('.loginTitle').remove();
		$('label').not('[for^="remember_me"]').parent().css('display', 'none')
		$('label').not('[for^="remember_me"]').remove();
		$('input').not('[id="remember_me"]').attr('class', 'login-field');
		$('table.cactiLoginTable').addClass('login-table');
		$('.loginCenter').after('<div class="versionInfo">' + version_div_html + '</div>');
		$('.versionInfo').html(ibmSVG + '<p class="legal-text">' + copyRight + '</p>');
		$('.versionInfo').after("<div class='cactiPageBottom' style='display:none;' ></div>");
		$('.versionInfo').css('background-color', $('.cactiPageBottom').css('background-color'));
	}

	if($('legend').text() == 'User Login'){
		$('legend').html(brandNameBold + ' <span class="logo-area">' + productName + '</span>');
	}

	if($('.versionInfo').css('position') != 'absolute' && $("LINK[href*='RTM/include/main.css']")){
		$('head').append('<link rel="stylesheet" type="text/css" href="' + urlPath + 'plugins/RTM/include/main.css">');
	}
}

function setMenuVisibility() {
	storage=Storages.localStorage;
	// Initialize the navigation settings
	// This will setup the initial visibility of the menu
	$('li.menuitem').each(function() {
		var id = $(this).attr('id');
		if (storage.isSet(id)) {
			var active = storage.get(id);
		} else {
			var active = null;
		}
		if (active != null && active == 'active') {
			$(this).find('ul').attr('aria-hidden', 'false').attr('aria-expanded', 'true').show();
			$(this).children('a:first').addClass('active');
		} else {
			$(this).find('ul').attr('aria-hidden', 'true').attr('aria-expanded', 'false').hide();
			$(this).children('a:first').removeClass('active');
		}

		if ($(this).find('a.selected').length == 0) {
			$(this).find('ul').attr('aria-hidden', 'true').attr('aria-expanded', 'false').hide();
			$(this).children('a:first').removeClass('active');
			storage.set(id, 'collapsed');
		} else {
			$(this).find('ul').attr('aria-hidden', 'false').attr('aria-expanded', 'true').show();
			$(this).children('a:first').addClass('active');
			storage.set(id, 'active');
		}
	});

	// Function to give life to the Navigation pane
	$('#nav li:has(ul) a').unbind().click(function(event) {
		event.preventDefault();

		id = $(this).closest('.menuitem').attr('id');

		if ($(this).next().is(':visible')){
			$(this).next('ul').attr('aria-hidden', 'true').attr('aria-expanded', 'false');
			$(this).next().slideUp( { duration: 200, easing: 'swing' } );
			$(this).removeClass('active');
			storage.set(id, 'collapsed');
		} else {
			$(this).next('ul').attr('aria-hidden', 'false').attr('aria-expanded', 'true');
			$(this).next().slideToggle( { duration: 200, easing: 'swing' } );
			if ($(this).next().is(':visible')) {
				storage.set(id, 'active');
				$(this).addClass('active');
			} else {
				storage.set(id, 'collapsed');
				$(this).removeClass('active');
			}
		}

		$('li.menuitem').not('#'+id).each(function() {
			id = $(this).attr('id');

			$(this).find('ul').attr('aria-hidden', 'true').attr('aria-expanded', 'false');
			$(this).find('ul').slideUp( { duration: 200, easing: 'swing' } );
			$(this).children('a:first').removeClass('active');
			storage.set(id, 'collapsed');
		});
	});
}
