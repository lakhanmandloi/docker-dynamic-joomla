<?php
/**
* @author    RocketTheme http://www.rockettheme.com
* @copyright Copyright (C) 2007 - 2014 RocketTheme, LLC
* @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*
* Gantry uses the Joomla Framework (http://www.joomla.org), a GNU/GPLv2 content management system
*
*/
defined('JPATH_BASE') or die();

gantry_import('core.gantryfeature');

class GantryFeatureSocial extends GantryFeature {
	var $_feature_name = 'social';

	function init(){
		global $gantry;
	}

	function render($position="") {
		ob_start();
		global $gantry;
		?>
		<div class="rt-block social-icons">
    		<ul>
    		<?php if ($gantry->get('social-facebook') != null) : ?>
    		<li>
			<a id="rt-facebook-btn" target="_blank" href="<?php echo $gantry->get('social-facebook'); ?>">
				<span class="fa fa-facebook"></span>
			</a>
    		</li>
			<?php endif; ?>
			<?php if ($gantry->get('social-twitter') != null) : ?>
			<li>
			<a id="rt-twitter-btn" target="_blank" href="<?php echo $gantry->get('social-twitter'); ?>">
				<span class="fa fa-twitter"></span>
			</a>
			</li>
			<?php endif; ?>
			<?php if ($gantry->get('social-youtube') != null) : ?>
			<li>
			<a id="rt-youtube-btn" target="_blank" href="<?php echo $gantry->get('social-youtube'); ?>">
				<span class="fa fa-youtube"></span>
			</a>
			</li>
			<?php endif; ?>
			<?php if ($gantry->get('social-google-plus') != null) : ?>
			<li>
			<a id="rt-google-plus-btn" target="_blank" href="<?php echo $gantry->get('social-google-plus'); ?>">
				<span class="fa fa-google-plus"></span>
			</a>
			</li>
			<?php endif; ?>
			<?php if ($gantry->get('social-linkedin') != null) : ?>
			<li>
			<a id="rt-linkedin-btn" target="_blank" href="<?php echo $gantry->get('social-linkedin'); ?>">
				<span class="fa fa-linkedin"></span>
			</a>
			</li>
			<?php endif; ?>
    		</ul>
		</div>
		<?php
		return ob_get_clean();
	}
}
