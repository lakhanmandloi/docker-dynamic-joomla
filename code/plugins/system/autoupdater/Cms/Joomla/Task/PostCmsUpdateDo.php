<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_PostCmsUpdateDo extends AutoUpdater_Task_PostCmsUpdateDo
{
	protected $errors = array();

	/**
	 * @return array
	 */
	public function doTask()
	{
		jimport('joomla.installer.installer');

		$this->setInput('type', 'cms');
		$this->setInput('slug', 'joomla');

		// Catch all installer errors
		/** @see \Joomla\CMS\Log\Log::addLogger */
		JLog::addLogger(array(
			'logger'   => 'callback',
			'callback' => array($this, 'logJoomlaInstallerError')
		), JLog::ALL, array('jerror'));

		$filemanager    = AutoUpdater_Filemanager::getInstance();
		$option         = 'com_joomlaupdate';
		$component_path = JPATH_ADMINISTRATOR . '/components/' . $option;

		// Define component path.
		if (!defined('JPATH_COMPONENT'))
		{
			define('JPATH_COMPONENT', JPATH_BASE . '/components/' . $option);
		}

		if (!defined('JPATH_COMPONENT_SITE'))
		{
			define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/' . $option);
		}

		if (!defined('JPATH_COMPONENT_ADMINISTRATOR'))
		{
			define('JPATH_COMPONENT_ADMINISTRATOR', $component_path);
		}

		/** @var JoomlaupdateModelDefault|\Joomla\CMS\MVC\Model\BaseDatabaseModel $model */
		JModelLegacy::addIncludePath($component_path . '/models', 'JoomlaupdateModel');
		$model = JModelLegacy::getInstance('default', 'JoomlaupdateModel');

		// Update Joomla
		if (!$model->finaliseUpgrade())
		{
			// Get the same installer instance as in above finaliseUpgrade method
			/** @var \Joomla\CMS\Installer\Installer|JInstaller $installer */
			if (version_compare(JVERSION, '3.8', '>='))
			{
				$installer = JInstaller::getInstance(JPATH_LIBRARIES . '/src/Installer');
			}
			elseif (version_compare(JVERSION, '3.4', '>='))
			{
				$installer = JInstaller::getInstance(JPATH_LIBRARIES . '/cms/installer');
			}
			else
			{
				$installer = JInstaller::getInstance();
			}

			if ($installer->message)
			{
				$this->errors[] = $installer->message;
			}
			if ($installer->get('extension_message'))
			{
				$this->errors[] = $installer->get('extension_message');
			}

			array_unshift($this->errors, 'CMS finalise upgrade error');

			return array(
				'success' => false,
				'message' => implode(".\n", $this->errors),
			);
		}

		$model->cleanUp();

		$filemanager->clearPhpCache();

		return array(
			'success' => true,
		);

	}

	/**
	 * @param JLogEntry|\Joomla\CMS\Log\LogEntry $entry
	 */
	public function logJoomlaInstallerError($entry)
	{
		$this->errors[] = preg_replace('/<[^>]+>/', ' ', $entry->message);

	}
}