<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | (C) Copyright International Business Machines Corp, 2006-2022.          |
 | Portions Copyright (C) 2004-2022 The Cacti Group                        |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU Lesser General Public              |
 | License as published by the Free Software Foundation; either            |
 | version 2.1 of the License, or (at your option) any later version.      |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU Lesser General Public License for more details.                     |
 |                                                                         |
 | You should have received a copy of the GNU Lesser General Public        |
 | License along with this library; if not, write to the Free Software     |
 | Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA           |
 | 02110-1301, USA                                                         |
 +-------------------------------------------------------------------------+
 | - IBM Corporation - http://www.ibm.com/                                 |
 | - Cacti - http://www.cacti.net/                                         |
 +-------------------------------------------------------------------------+
*/

chdir('../../');
include_once('./include/auth.php');
include_once($config['base_path'] . '/plugins/gridgmgmt/functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

$actions = array(
	'1' => __('Delete', 'gridgmgmt')
);

set_default_action();

if (isset_request_var('drp_action')) {
	do_items();
} else {
	top_header();
	list_objects('slas');
	bottom_footer();
}

