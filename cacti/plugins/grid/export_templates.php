#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2022 The Cacti Group                                 |
 | Copyright IBM Corp. 2006, 2022                                          |
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

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['library_path'] . '/export.php');
include_once($config['library_path'] . '/rtm_functions.php');
include_once(dirname(__FILE__)  . '/lib/grid_functions.php');
include_once(dirname(__FILE__) . '/setup.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug = false;
if (!cacti_sizeof($parms)) {
    display_help();
    exit;
}

$template_type = '';
$template_id = 0;
$dependence = false;
$listtemplate = false;
$output = '';

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

    switch ($arg) {
    case '-d':
        $debug = true;
        break;
    case '--type':
        switch(strtoupper($value)) {
            case 'H':
                $template_type = 'host_template';
                break;
            case 'DQ':
                $template_type = 'data_query';
                break;
            case 'DS':
                $template_type = 'data_template';
                break;
            case 'G':
                $template_type = 'graph_template';
                break;
            default:
                print "FATAL: Invalid template type.\n";
                display_help();
                exit;
        }
        break;
    case '--template':
        $template_id = $value;
        if ((int)$value <= 0) {
            print "FATAL: Invalid template id.\n";
            print "FATAL: Try --list-templates\n";
            display_help();
            exit;
        }
        break;
    case '--dependencies':
        $dependence = true;
        break;
    case '--list-templates':
        $listtemplate = true;
        break;
    case '--output':
        $output = $value;
        break;
    case '-v':
    case '-V':
    case '--version':
        display_version();
        exit;
    case '-h':
    case '--help':
        display_help();
        exit;
    default:
        print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
        display_help();
        exit;
    }
}

if ($listtemplate) {
    if (empty($template_type)) {
        print "FATAL: Invalid template type.\n";
        display_help();
        exit;
    }

    displayTemplates($template_type);

    exit(0);
}

if (empty($template_type) || $template_id == 0) {
    print "FATAL: Invalid template type or template id.\n";
    display_help();
    exit;
}

$default_filename = get_default_file_name($template_type, $template_id);

$filename = '';
if (empty($output)) {
    $filename = $default_filename;
} else if (is_dir($output)) {
    if (is_writable($output)) {
        $filename = $output . DELIM . $default_filename;
    } else {
        print "FATAL: Can not write template file into output directory.\n";
        exit(0);
    }
} else if (is_file($output)) {
    if (is_writable($output)) {
        $filename = $output;
    } else {
        print "FATAL: Can not output template into file.\n";
        exit(0);
    }
}

//$export_types
print "Export RTM templates..\n";

export_template($template_type, $template_id, $dependence, $filename);

print "Templates export as $filename.\n";
print "Templates export complete.\n";

function get_default_file_name($template_type, $template_id) {
    global $export_types;

    $default_filename  = 'cacti_';
    $default_filename .= $template_type . '_';
    $default_filename .= strtolower(clean_up_file_name(db_fetch_cell(str_replace('|id|', $template_id, $export_types[$template_type]['title_sql']))));
    $default_filename .= '.xml';

    return $default_filename;
}

function export_template($template_type, $template_id, $dependence, $filename) {
    $xml_data = get_item_xml($template_type, $template_id, $dependence);

    $tempfile = tempnam('/tmp', $filename);
    if ($tempfile) {
        file_put_contents($tempfile, $xml_data);

        if (file_exists($filename . '.old')) {
			unlink($filename . '.old');
        }

        if (file_exists($filename)) {
            rename($filename, $filename . '.old');
        }

        rename($tempfile, $filename);
        chmod($filename, 0644);
    }

    return true;
}

function displayTemplates($template_type) {
    global $export_types;

    $templates = db_fetch_assoc($export_types[$template_type]['dropdown_sql']);

    print "Known Templates:(templateId, templateName)\n";

    if (cacti_sizeof($templates)) {
        foreach($templates as $template) {
            print "\t" . $template["id"] . "\t" . $template['name'] . "\n";
        }
    }
}

// get_item_xml($type, $id, $follow_deps) {

function display_version() {
    print 'IBM Spectrum LSF RTM Template Exporter ' . get_grid_version() . "\n";
    print rtm_copyright();
}

function display_help () {
    print "database_upgrade_fp.php " . get_grid_version() . "\n\n";
    print "Export host/graph/datasource template, and dataquery\n";
    print "usage: \n";
    print "export_templates.php [-d] --type=[H|DQ|DS|G] --template=[Id] [--dependencies] --output=[directory|filename]\n";
    print "export_templates.php [-h|--help] [-V|--version] \n\n";
    print "--type           - Template type is one of H(host), DQ(DataQuery), DS(DataSource) and G(Graph)\n";
    print "--template       - Id of specified template \n";
    print "--dependencies   - Export template include dependencies\n";
    print "--output         - Output template as specified filename or into directory\n";
    print "List Options:    --type=[H|DQ|DS|G] --list-templates\n";
    print "-d               - Display verbose output during execution\n";
    print "-V --version     - Display this help message\n";
    print "-h --help        - display this help message\n";
}
