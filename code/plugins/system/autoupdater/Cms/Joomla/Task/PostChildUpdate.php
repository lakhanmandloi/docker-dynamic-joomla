<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_PostChildUpdate extends AutoUpdater_Task_PostChildUpdate
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		$this->setInput('type', 'plugin');
		$this->setInput('slug', AUTOUPDATER_J_PLUGIN_SLUG);

		// Joomla needs an URL
		$this->setInput('file_url', AutoUpdater_Config::getAutoUpdaterUrl()
			. 'download/child/' . AUTOUPDATER_CMS . '/' . AUTOUPDATER_J_PLUGIN_SLUG . '.zip');

		// Download package.
        AutoUpdater_Log::debug('Download package');
		$result = AutoUpdater_Task::getInstance('PostFileDownload', $this->payload)
			->doTask();

		if (empty($result['success']))
		{
			// Error - do not continue execution.
			return $result;
		}
		else
		{
			$this->setInput('file_path', $result['return']['file_path']);
		}

		// Unpack package.
        AutoUpdater_Log::debug('Unpack package');
		$result = AutoUpdater_Task::getInstance('PostFileUnpack', $this->payload)
			->doTask();

		if (empty($result['success']))
		{
			// Error - do not continue execution.
			return $result;
		}
		else
		{
			$this->setInput('path', $result['return']['path']);
		}

		// Install update.
        AutoUpdater_Log::debug('Install package');
		return AutoUpdater_Task::getInstance('PostExtensionUpdate', $this->payload)
			->doTask();
	}
}