<?php
defined('_JEXEC') or die;

class AutoUpdater_Task_Cms_Joomla_PostFilesList extends AutoUpdater_Task_PostFilesList
{
	/**
	 * @return array
	 */
	protected function getDefaultExclusions()
	{
		$filemanager = AutoUpdater_Filemanager::getInstance();
		$logger      = AutoUpdater_Log::getInstance();
		$admin_path  = basename(JPATH_ADMINISTRATOR) . '/';

		$exclusions = array_merge(parent::getDefaultExclusions(), array(
			$admin_path . 'cache',
			$admin_path . 'logs', // default logs path J! >= 3.6
			'media/template', // Yoo Theme cache
			str_replace(AUTOUPDATER_SITE_PATH, '',
				$filemanager->untrailingslashit($logger->getLogsPath())
			), // config logs path
			str_replace(AUTOUPDATER_SITE_PATH, '',
				$filemanager->untrailingslashit($filemanager->getTempPath())
			), // config temp path
		));

		return array_unique($exclusions);
	}
}