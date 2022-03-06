<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2022 The Cacti Group                                 |
 | Copyright IBM Corp. 2017, 2022                                          |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 |  Cacti - http://www.cacti.net/                                          |
 +-------------------------------------------------------------------------+
 |  IBM Corporation - http://www.ibm.com/                                  |
 +-------------------------------------------------------------------------+
*/

function xml2array($data) {
	/* mvo voncken@mailandnews.com
	original ripped from  on the php-manual:gdemartini@bol.com.br
	to be used for data retrieval(result-structure is Data oriented) */
	$p = xml_parser_create();
	$vals = array();
	$index = array();
	xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, 1);
	xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
	xml_parse_into_struct($p, $data, $vals, $index);
	xml_parser_free($p);

	$tree = array();
	$i = 0;
	$tree = get_children($vals, $i);

	return $tree;
}

function get_children($vals, &$i) {
	$children = array();

	if (isset($vals[$i]['value'])) {
		if ($vals[$i]['value']) array_push($children, $vals[$i]['value']);
	}

	$prevtag = ''; $j = 0;

	while (++$i < cacti_count($vals)) {
		switch ($vals[$i]['type']) {
		case 'cdata':
			array_push($children, $vals[$i]['value']);
			break;
		case 'complete':
			/* if the value is an empty string, php doesn't include the 'value' key
			in its array, so we need to check for this first */
			if (isset($vals[$i]['value'])) {
				$children[$vals[$i]['tag']] = $vals[$i]['value'];
			} else {
				$children[$vals[$i]['tag']] = '';
			}

			break;
		case 'open':
			$j++;

			if ($prevtag <> $vals[$i]['tag']) {
				$j = 0;
				$prevtag = $vals[$i]['tag'];
			}

			$children[$vals[$i]['tag']] = get_children($vals,$i);
			break;
		case 'close':
			return $children;
		}
	}
}
