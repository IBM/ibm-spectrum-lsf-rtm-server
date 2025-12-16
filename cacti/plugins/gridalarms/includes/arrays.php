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

?>
<script type='text/javascript'>
	var pleaseWait = '<?php print __esc('Please wait ...', 'gridalarms');?>';
	var processing = '<?php print __esc('Processing ...', 'gridalarms');?>';

	var frequencies = {
		'1':'<?php print __('%d Minutes', 5, 'gridalarm');?>',
		'2':'<?php print __('%d Minutes', 10, 'gridalarm');?>',
		'3':'<?php print __('%d Minutes', 15, 'gridalarm');?>',
		'4':'<?php print __('%d Minutes', 20, 'gridalarm');?>',
		'6':'<?php print __('%d Minutes', 30, 'gridalarm');?>',
		'8':'<?php print __('%d Minutes', 45, 'gridalarm');?>',
		'12':'<?php print __('Hour', 'gridalarm');?>',
		'24':'<?php print __('%d Hours', 2, 'gridalarm');?>',
		'36':'<?php print __('%d Hours', 3, 'gridalarm');?>',
		'48':'<?php print __('%d Hours', 4, 'gridalarm');?>',
		'72':'<?php print __('%d Hours', 6, 'gridalarm');?>',
		'96':'<?php print __('%d Hours', 8, 'gridalarm');?>',
		'144':'<?php print __('%d Hours', 12, 'gridalarm');?>',
		'288':'<?php print __('%d Day', 1, 'gridalarms');?>'
	};

	var breachduration = {
        '1':'<?php print __('%d Minutes', 5, 'gridalarms');?>',
        '2':'<?php print __('%d Minutes', 10, 'gridalarms');?>',
        '3':'<?php print __('%d Minutes', 15, 'gridalarms');?>',
        '4':'<?php print __('%d Minutes', 20, 'gridalarms');?>',
        '6':'<?php print __('%d Minutes', 30, 'gridalarms');?>',
        '8':'<?php print __('%d Minutes', 45, 'gridalarms');?>',
        '12':'<?php print __('Hour', 'gridalarms');?>',
        '24':'<?php print __('%d Hours', 2, 'gridalarms');?>',
        '36':'<?php print __('%d Hours', 3, 'gridalarms');?>',
        '48':'<?php print __('%d Hours', 4, 'gridalarms');?>',
        '72':'<?php print __('%d Hours', 6, 'gridalarms');?>',
        '96':'<?php print __('%d Hours', 8, 'gridalarms');?>',
        '144':'<?php print __('%d Hours', 12, 'gridalarms');?>',
        '288':'<?php print __('%d Day', 1, 'gridalarms');?>',
        '576':'<?php print __('%d Days', 2, 'gridalarms');?>',
        '2016':'<?php print __('%d Week', 1, 'gridalarms');?>',
        '4032':'<?php print __('%d Weeks', 2, 'gridalarms');?>',
        '8640':'<?php print __('%d Month', 1, 'gridalarms');?>'
	};

	var repeatalert = {
		'0':'<?php print __('Never', 'gridalarms');?>',
        '1':'<?php print __('Every %d Minutes', 5, 'gridalarms');?>',
        '2':'<?php print __('Every %d Minutes', 10, 'gridalarms');?>',
        '3':'<?php print __('Every %d Minutes', 15, 'gridalarms');?>',
        '4':'<?php print __('Every %d Minutes', 20, 'gridalarms');?>',
        '6':'<?php print __('Every %d Minutes', 30, 'gridalarms');?>',
        '8':'<?php print __('Every %d Minutes', 45, 'gridalarms');?>',
        '12':'<?php print __('Every Hour', 'gridalarms');?>',
        '24':'<?php print __('Every %d Hours', 2, 'gridalarms');?>',
        '36':'<?php print __('Every %d Hours', 3, 'gridalarms');?>',
        '48':'<?php print __('Every %d Hours', 4, 'gridalarms');?>',
        '72':'<?php print __('Every %d Hours', 6, 'gridalarms');?>',
        '96':'<?php print __('Every %d Hours', 8, 'gridalarms');?>',
        '144':'<?php print __('Every %d Hours', 12, 'gridalarms');?>',
        '288':'<?php print __('Every Day', 'gridalarms');?>',
        '576':'<?php print __('Every %d Days', 2, 'gridalarms');?>',
        '2016':'<?php print __('Every Week', 'gridalarms');?>',
        '4032':'<?php print __('Every %d Weeks', 2, 'gridalarms');?>',
        '8640':'<?php print __('Every Month', 'gridalarms');?>'
	};
</script>
