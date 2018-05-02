<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_PostCmsUpdateAfter extends AutoUpdater_Task_PostCmsUpdateAfter
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		$this->setInput('type', 'cms');
		$this->setInput('slug', 'joomla');

		// Restore SQL files after update has finished and run those queries
        AutoUpdater_Cms_Joomla_Helper_Joomla::renameDbConversionFiles('.sql.bak', '.sql');

		$this->fixDatabase();

		$this->purgeUpdates();
		$this->flushAssets();

		return array(
			'success' => true,
		);
	}

	protected function fixDatabase()
	{
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_installer/models', 'InstallerModel');
		/** @var InstallerModelDatabase $model */
		$model = JModelLegacy::getInstance('Database', 'InstallerModel');
		$model->fix();
	}

	protected function purgeUpdates()
	{
		if (version_compare(JVERSION, '3.0', '>='))
		{
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_joomlaupdate/models', 'JoomlaupdateModel');
			/** @var JoomlaupdateModelDefault $model */
			$model = JModelLegacy::getInstance('default', 'JoomlaupdateModel');
			$model->purge();
		}
	}

	protected function flushAssets()
	{
		if (version_compare(JVERSION, '3.2', '>='))
		{
			// Refresh versionable assets cache
			/** @see \Joomla\CMS\Application\WebApplication::flushAssets */
			JFactory::getApplication()->flushAssets();
		}
	}
}