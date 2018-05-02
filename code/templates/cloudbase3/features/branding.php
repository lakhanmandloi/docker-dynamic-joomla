<?php
/**
* @version   $Id: branding.php 2355 2012-08-14 01:04:50Z btowles $
* @author    RocketTheme http://www.rockettheme.com
* @copyright Copyright (C) 2007 - 2014 RocketTheme, LLC
* @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*
* Gantry uses the Joomla Framework (http://www.joomla.org), a GNU/GPLv2 content management system
*
*/
defined('JPATH_BASE') or die();

gantry_import('core.gantryfeature');

class GantryFeatureBranding extends GantryFeature {
    var $_feature_name = 'branding';

	function render($position) {
	    ob_start();
	    ?>
	    <div class="rt-block branding">
    	    <span>Joomla! Hosting from</span>
			<a href="http://www.cloudaccess.net/products/joomla-hosting-support.html" title="ClodAccess.net Joomla Hosting" class="powered-by"></a>
		</div>
		<?php
	    return ob_get_clean();
	}
}