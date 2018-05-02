<?php
/**
* @version   $Id: styledeclaration.php 15522 2013-11-13 21:47:07Z kevin $
 * @author		RocketTheme http://www.rockettheme.com
 * @copyright 	Copyright (C) 2007 - 2014 RocketTheme, LLC
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 *
 * Gantry uses the Joomla Framework (http://www.joomla.org), a GNU/GPLv2 content management system
 *
 */
defined('JPATH_BASE') or die();

gantry_import('core.gantryfeature');

class GantryFeatureStyleDeclaration extends GantryFeature {
	var $_feature_name = 'styledeclaration';

	function isEnabled() {
		global $gantry;
		$menu_enabled = $this->get('enabled');

		if (1 == (int)$menu_enabled) return true;
		return false;
	}

	function init() {
		global $gantry;
		$browser = $gantry->browser;

        // Colors
	$lessVariables = array(
		'body-linkcolor'     => $gantry->get('body-linkcolor',     '#F16256'),
		'body-background'  => $gantry->get('body-background',     '#f7f7f7'),
		'body-text'  => $gantry->get('body-text',     '#8d8d8d'),
		'body-heading'  => $gantry->get('body-heading',     '#303030'),
		'body-button-bgcolor'  => $gantry->get('body-button-bgcolor',     '#F36354'),
		'body-button-textcolor'  => $gantry->get('body-button-textcolor',     '#ffffff'),
		'body-button-bordercolor'  => $gantry->get('body-button-bordercolor',     '#F36354'),
		'header-style'     => $gantry->get('header-style',     'dark'),
		'header-gradientfrom'     => $gantry->get('header-gradientfrom',     '#e4b26e'),
		'header-gradientto'     => $gantry->get('header-gradientto',     '#c33345'),
		'menu-border-width'     => $gantry->get('menu-border-width',     '3px'),
		'menu-top-border-color'     => $gantry->get('menu-top-border-color',     '#ea3342'),
		'menu-bottom-border-color'     => $gantry->get('menu-bottom-border-color',     '#C75145'),
		'menu-fontcolor'     => $gantry->get('menu-fontcolor',     '#6e6e6e'),
		'menu-active-hover-fontcolor'     => $gantry->get('menu-active-hover-fontcolor',     '#ffffff'),
		'menu-active-hover-bgcolor'     => $gantry->get('menu-active-hover-bgcolor',     '#F76B52'),
		'menudropdown-bgcolor'     => $gantry->get('menudropdown-bgcolor',     '#c34b3d'),
		'menudropdown-subtext-color'     => $gantry->get('menudropdown-subtext-color',     '#ffffff'),
		'menudropdown-fontcolor'     => $gantry->get('menudropdown-fontcolor',     '#ffffff'),
		'menudropdown-active-hover-fontcolor'     => $gantry->get('menudropdown-active-hover-fontcolor',     '#ffffff'),
		'menudropdown-active-hover-bgcolor'     => $gantry->get('menudropdown-active-hover-bgcolor',     '#f76b52'),
		'showcase-linkcolor'     => $gantry->get('showcase-linkcolor',     '#fab561'),
		'showcase-text'  => $gantry->get('showcase-text',     '#ffffff'),
		'showcase-heading'     => $gantry->get('showcase-heading',     '#ffffff'),
		'showcase-gradientfrom'     => $gantry->get('showcase-gradientfrom',     '#f35944'),
		'showcase-gradientto'     => $gantry->get('showcase-gradientto',     '#ec3949'),
		'feature-linkcolor'     => $gantry->get('feature-linkcolor',     '#F16256'),
		'feature-text'  => $gantry->get('feature-text',     '#7f7f7f'),
		'feature-heading'     => $gantry->get('feature-heading',     '#424242'),
		'feature-bgcolor'     => $gantry->get('feature-bgcolor',     '#ffffff'),
		'maintop-linkcolor'     => $gantry->get('maintop-linkcolor',     '#F16256'),
		'maintop-text'  => $gantry->get('maintop-text',     '#7f7f7f'),
		'maintop-heading'     => $gantry->get('maintop-heading',     '#424242'),
		'bottom-linkcolor'     => $gantry->get('bottom-linkcolor',     '#fab561'),
		'bottom-text'  => $gantry->get('bottom-text',     '#ffffff'),
		'bottom-heading'     => $gantry->get('bottom-heading',     '#fab561'),
		'bottom-gradientfrom'     => $gantry->get('bottom-gradientfrom',     '#f35944'),
		'bottom-gradientto'     => $gantry->get('bottom-gradientto',     '#ec3949'),
		'footer-linkcolor'     => $gantry->get('footer-linkcolor',     '#F16256'),
		'footer-text'  => $gantry->get('footer-text',     '#a1a1a1'),
		'footer-heading'     => $gantry->get('footer-heading',     '#a1a1a1'),
		'footer-bgcolor'     => $gantry->get('footer-bgcolor',     '#252525'),
		'copyright-linkcolor'     => $gantry->get('copyright-linkcolor',     '#F16256'),
		'copyright-text'  => $gantry->get('copyright-text',     '#a1a1a1'),
		'copyright-heading'     => $gantry->get('copyright-heading',     '#a1a1a1'),
		'copyright-bgcolor'     => $gantry->get('copyright-bgcolor',     '#252525'),
        'socialicons-color'     => $gantry->get('socialicons-color',     '#ababab'),
        'socialicons-border'     => $gantry->get('socialicons-border',     '1px'),
        'socialicons-border-color'     => $gantry->get('socialicons-border-color',     '#d2d2d2'),
        'scrolltotop-bgcolor'     => $gantry->get('scrolltotop-bgcolor',     '#ffffff'),
		'scrolltotop-border-color'     => $gantry->get('scrolltotop-border-color',     '#dbdbdb'),
		'box3-linkcolor'     => $gantry->get('box3-linkcolor',     '#b81c0f'),
		'box3-bgcolor'  => $gantry->get('box3-bgcolor',     '#F16256'),
		'box3-text'  => $gantry->get('box3-text',     '#ffffff'),
		'box3-heading'  => $gantry->get('box3-heading',     '#ffffff'),
		'box3-button-bgcolor'  => $gantry->get('box3-button-bgcolor',     '#b81c0f'),
		'box3-button-textcolor'  => $gantry->get('box3-button-textcolor',     '#ffffff'),
		'box3-button-bordercolor'  => $gantry->get('box3-button-bordercolor',     '#b81c0f'),
		'title3-bgcolor'  => $gantry->get('title3-bgcolor',     '#F16256'),
		'title3-text'  => $gantry->get('title3-text',     '#ffffff'),
		'title4-bgcolor'  => $gantry->get('title4-bgcolor',     '#E7BB72'),
		'title4-text'  => $gantry->get('title4-text',     '#ffffff')

	);

	$gantry->addLess('global.less', 'master.css', 8, $lessVariables);

        // Logo
	$css = $this->buildLogo();

	$this->_disableRokBoxForiPhone();

	$gantry->addInlineStyle($css);
	if ($gantry->get('layout-mode')=="responsive") $gantry->addLess('mediaqueries.less');
	if ($gantry->get('layout-mode')=="960fixed") $gantry->addLess('960fixed.less');
	if ($gantry->get('layout-mode')=="1200fixed") $gantry->addLess('1200fixed.less');
	}

	function buildLogo(){
		global $gantry;

		if ($gantry->get('logo-type')!="custom") return "";

		$source = $width = $height = "";

		$logo = str_replace("&quot;", '"', str_replace("'", '"', $gantry->get('logo-custom-image')));
		$data = json_decode($logo);

		if (!$data){
			if (strlen($logo)) $source = $logo;
			else return "";
		} else {
			$source = $data->path;
		}

		if (substr($gantry->baseUrl, 0, strlen($gantry->baseUrl)) == substr($source, 0, strlen($gantry->baseUrl))){
			$file = JPATH_ROOT . '/' . substr($source, strlen($gantry->baseUrl));
		} else {
			$file = JPATH_ROOT . '/' . $source;
		}

		if (isset($data->width) && isset($data->height)){
			$width = $data->width;
			$height = $data->height;
		} else {
			$size = @getimagesize($file);
			$width = $size[0];
			$height = $size[1];
		}

		if (!preg_match('/^\//', $source))
		{
			$source = JURI::root(true).'/'.$source;
		}

        $source = str_replace(' ', '%20', $source);

		$output = "";
		$output .= "#rt-logo {background: url(".$source.") 50% 0 no-repeat !important;}"."\n";
		$output .= "#rt-logo {width: ".$width."px;height: ".$height."px;}"."\n";

		$file = preg_replace('/\//i', DIRECTORY_SEPARATOR, $file);

		return (file_exists($file)) ?$output : '';
	}

	function _createGradient($direction, $from, $fromOpacity, $fromPercent, $to, $toOpacity, $toPercent){
		global $gantry;
		$browser = $gantry->browser;

		$fromColor = $this->_RGBA($from, $fromOpacity);
		$toColor = $this->_RGBA($to, $toOpacity);
		$gradient = $default_gradient = '';

		$default_gradient = 'background: linear-gradient('.$direction.', '.$fromColor.' '.$fromPercent.', '.$toColor.' '.$toPercent.');';

		switch ($browser->engine) {
			case 'gecko':
			$gradient = ' background: -moz-linear-gradient('.$direction.', '.$fromColor.' '.$fromPercent.', '.$toColor.' '.$toPercent.');';
			break;

			case 'webkit':
			if ($browser->shortversion < '5.1'){

				switch ($direction){
					case 'top':
					$from_dir = 'left top'; $to_dir = 'left bottom'; break;
					case 'bottom':
					$from_dir = 'left bottom'; $to_dir = 'left top'; break;
					case 'left':
					$from_dir = 'left top'; $to_dir = 'right top'; break;
					case 'right':
					$from_dir = 'right top'; $to_dir = 'left top'; break;
				}
				$gradient = ' background: -webkit-gradient(linear, '.$from_dir.', '.$to_dir.', color-stop('.$fromPercent.','.$fromColor.'), color-stop('.$toPercent.','.$toColor.'));';
			} else {
				$gradient = ' background: -webkit-linear-gradient('.$direction.', '.$fromColor.' '.$fromPercent.', '.$toColor.' '.$toPercent.');';
			}
			break;

			case 'presto':
			$gradient = ' background: -o-linear-gradient('.$direction.', '.$fromColor.' '.$fromPercent.', '.$toColor.' '.$toPercent.');';
			break;

			case 'trident':
			if ($browser->shortversion >= '10'){
				$gradient = ' background: -ms-linear-gradient('.$direction.', '.$fromColor.' '.$fromPercent.', '.$toColor.' '.$toPercent.');';
			} else if ($browser->shortversion <= '6'){
				$gradient = $from;
				$default_gradient = '';
			} else {

				$gradient_type = ($direction == 'left' || $direction == 'right') ? 1 : 0;
				$from_nohash = str_replace('#', '', $from);
				$to_nohash = str_replace('#', '', $to);

				if (strlen($from_nohash) == 3) $from_nohash = str_repeat(substr($from_nohash, 0, 1), 6);
				if (strlen($to_nohash) == 3) $to_nohash = str_repeat(substr($to_nohash, 0, 1), 6);

				if ($fromOpacity == 0 || $fromOpacity == '0' || $fromOpacity == '0%') $from_nohash = '00' . $from_nohash;
				if ($toOpacity == 0 || $toOpacity == '0' || $toOpacity == '0%') $to_nohash = '00' . $to_nohash;

				$gradient = " filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#".$to_nohash."', endColorstr='#".$from_nohash."',GradientType=".$gradient_type." );";

				$default_gradient = '';

			}
			break;

			default:
			$gradient = $from;
			$default_gradient = '';
			break;
		}

		return  $default_gradient . $gradient;
	}

	function _HEX2RGB($hexStr, $returnAsString = false, $seperator = ','){
		$hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr);
		$rgbArray = array();

		if (strlen($hexStr) == 6){
			$colorVal = hexdec($hexStr);
			$rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
			$rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
			$rgbArray['blue'] = 0xFF & $colorVal;
		} elseif (strlen($hexStr) == 3){
			$rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
			$rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
			$rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
		} else {
			return false;
		}

		return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray;
	}

	function _RGBA($hex, $opacity){
		return 'rgba(' . $this->_HEX2RGB($hex, true) . ','.$opacity.')';
	}

	function _disableRokBoxForiPhone() {
		global $gantry;

		if ($gantry->browser->platform == 'iphone' || $gantry->browser->platform == 'android') {
			$gantry->addInlineScript("window.addEvent('domready', function() {\$\$('a[rel^=rokbox]').removeEvents('click');});");
		}
	}
}
