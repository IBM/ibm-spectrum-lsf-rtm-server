<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2026                                          |
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

/**
 * Sanitize SVG file content to prevent XSS and other security issues
 *
 * @param string $svg_content The SVG file content to sanitize
 * @param bool $strip_remote_references Whether to strip remote references (default: true)
 * @return string|false Sanitized SVG content or false on failure
 */
function sanitize_svg($svg_content, $strip_remote_references = true) {
	if (empty($svg_content)) {
		return false;
	}

	// Load the SVG content into DOMDocument
	$dom = new DOMDocument();
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput = false;
	
	// Suppress warnings for malformed XML
	libxml_use_internal_errors(true);
	
	// Try to load the SVG content
	if (!$dom->loadXML($svg_content, LIBXML_NONET | LIBXML_NOENT)) {
		libxml_clear_errors();
		return false;
	}
	
	libxml_clear_errors();

	// Dangerous tags that should be removed
	$dangerous_tags = array(
		'script',
		'iframe',
		'object',
		'embed',
		'link',
		'style',
		'foreignObject',
		'use'
	);

	// Remove dangerous tags
	foreach ($dangerous_tags as $tag) {
		$elements = $dom->getElementsByTagName($tag);
		$remove = array();
		
		foreach ($elements as $element) {
			$remove[] = $element;
		}
		
		foreach ($remove as $element) {
			if ($element->parentNode) {
				$element->parentNode->removeChild($element);
			}
		}
	}

	// Dangerous attributes that should be removed
	$dangerous_attrs = array(
		'onload',
		'onerror',
		'onmouseover',
		'onmouseout',
		'onclick',
		'ondblclick',
		'onmousedown',
		'onmouseup',
		'onmousemove',
		'onkeydown',
		'onkeyup',
		'onkeypress',
		'onfocus',
		'onblur',
		'onchange',
		'onsubmit',
		'onreset',
		'onselect',
		'onabort',
		'onunload',
		'onbeforeunload',
		'oncontextmenu',
		'ondrag',
		'ondragend',
		'ondragenter',
		'ondragleave',
		'ondragover',
		'ondragstart',
		'ondrop',
		'onscroll',
		'onwheel',
		'oncopy',
		'oncut',
		'onpaste',
		'onanimationstart',
		'onanimationend',
		'onanimationiteration',
		'ontransitionend'
	);

	// Get all elements and remove dangerous attributes
	$xpath = new DOMXPath($dom);
	$all_elements = $xpath->query('//*');
	
	foreach ($all_elements as $element) {
		// Remove dangerous event handler attributes
		foreach ($dangerous_attrs as $attr) {
			if ($element->hasAttribute($attr)) {
				$element->removeAttribute($attr);
			}
		}
		
		// Check and sanitize href and xlink:href attributes
		if ($element->hasAttribute('href')) {
			$href = $element->getAttribute('href');
			if (preg_match('/^(javascript|data|vbscript):/i', $href)) {
				$element->removeAttribute('href');
			} elseif ($strip_remote_references && preg_match('/^https?:\/\//i', $href)) {
				$element->removeAttribute('href');
			}
		}
		
		if ($element->hasAttribute('xlink:href')) {
			$href = $element->getAttribute('xlink:href');
			if (preg_match('/^(javascript|data|vbscript):/i', $href)) {
				$element->removeAttribute('xlink:href');
			} elseif ($strip_remote_references && preg_match('/^https?:\/\//i', $href)) {
				$element->removeAttribute('xlink:href');
			}
		}
		
		// Remove any attribute containing javascript:
		$attributes = $element->attributes;
		$attrs_to_remove = array();
		
		foreach ($attributes as $attr) {
			if (preg_match('/javascript:/i', $attr->value)) {
				$attrs_to_remove[] = $attr->name;
			}
		}
		
		foreach ($attrs_to_remove as $attr_name) {
			$element->removeAttribute($attr_name);
		}
	}

	// Return sanitized SVG
	$sanitized = $dom->saveXML();
	
	// Additional cleanup: remove XML declaration if present
	$sanitized = preg_replace('/<\?xml[^?]*\?>\s*/i', '', $sanitized);
	
	return $sanitized;
}

function rtm_div_version_info(){
	global $config;
	include_once(dirname(__FILE__) . '/../include/rtm_constants.php');

	print "<div class='versionInfo'>";
	$logo_svg = sanitize_svg(file_get_contents(dirname(__FILE__) . '/../images/ibm_logo.svg'));
	if ($logo_svg) {
		print $logo_svg;
	}
    print '<p class="legal-text">' . RTM_COPYRIGHT . '</p>';
    //print '<img class="cacti-logo" src="' . $config['url_path'] . '/images/cacti_logo.svg"/>';
	print "</div>";
	print "<div class='cactiPageBottom' style='display:none;' ></div>";
}

function rtm_div_version_adjust(){
	return "$('div.versionInfo').css('background-color', $('div.cactiPageBottom').css('background-color'));\n";
}

function rtm_div_legend_adjust(){
	$out_js = "if($('div.loginArea, div.logoutArea').width() == $('div.cactiLoginLogo, div.cactiLogoutLogo').width()){
            $('div.cactiLoginLogo, div.cactiLogoutLogo').css('float', 'none');
            $('div.loginArea, div.logoutArea').find('legend').css('font-size', '21px');
        } else {
            $('div.loginArea, div.logoutArea').find('legend').css('font-size', Math.round(($('div.loginArea, div.logoutArea').width() - $('div.cactiLoginLogo, div.cactiLogoutLogo').width())*0.066)+'px');
        }\n";
	if(get_selected_theme() == 'spectrum'){
		$out_js .= "$('div.loginArea, div.logoutArea').find('input[type=\"submit\"]').attr('class', '');";
	}
	return $out_js;
}
