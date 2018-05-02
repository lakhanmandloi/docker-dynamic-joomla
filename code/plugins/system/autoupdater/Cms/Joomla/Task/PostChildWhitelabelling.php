<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_PostChildWhitelabelling extends AutoUpdater_Task_PostChildWhitelabelling
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		$name          = (string) $this->input('name', '');
		$author        = (string) $this->input('author', '');
		$child_page    = (string) $this->input('child_page', '');
		$login_page    = (string) $this->input('login_page', '');
		$protect_child = (int) $this->input('protect_child', 1);
		$hide_child    = (int) $this->input('hide_child', 0);

		require_once AUTOUPDATER_J_PLUGIN_HELPER_PATH . 'WhiteLabeller.php';

		$labeller = new AutoUpdater_Cms_Joomla_Helper_Whitelabeller(
			$name, $child_page, $login_page, $protect_child, $hide_child, $author);

		$labeller->handle();

		return array(
			'success' => true,
		);
	}
}