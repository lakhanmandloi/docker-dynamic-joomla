<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_PostCmsUpdateBefore extends AutoUpdater_Task_PostCmsUpdateBefore
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		$this->setInput('type', 'cms');
		$this->setInput('slug', 'joomla');
		$path = $this->input('path');

		if ($this->input('fixdb'))
		{
			$this->fixDatabase();

			// Finish here, as database fix constructs old JoomlaInstallerScript
			return array(
				'success' => true,
				'return'  => array(
					'path' => $path,
				),
			);
		}

		if (empty($path))
		{
			throw new AutoUpdater_Exception_Response('Missing required parameters', 400);
		}

		$filemanager = AutoUpdater_Filemanager::getInstance();
		if (!$filemanager->is_dir($path))
		{
			$path = AUTOUPDATER_SITE_PATH . $path;
			if (!$filemanager->is_dir($path))
			{
				throw new AutoUpdater_Exception_Response('Missing installation folder', 400);
			}
		}

		// Prevent Joomla from converting database tables format
		// during update by renaming SQL files. It will be run later.
        AutoUpdater_Cms_Joomla_Helper_Joomla::renameDbConversionFiles('.sql', '.sql.bak', $path);

		// Delete old files
		JLoader::register('JoomlaInstallerScript', $path . 'administrator/components/com_admin/script.php');
		$manifestClass = new JoomlaInstallerScript;

		ob_start();
		$manifestClass->deleteUnexistingFiles();
		$result = ob_get_clean();

		if (!empty($result))
		{
			// Continue event deleting old files has failed. Next task would run it again.
            AutoUpdater_Log::error("Failed to delete unexisting files:\n"
				. preg_replace('/<br\s*\/?>/', "\n", $result));
		}

		// Move new files to website root path
		$success = $filemanager->move($path . '*', JPATH_ROOT . '/', true);
		$filemanager->delete($path);
		$filemanager->clearPhpCache();

		return array(
			'success' => $success,
		);
	}

	protected function fixDatabase()
	{
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_installer/models', 'InstallerModel');
		/** @var InstallerModelDatabase $model */
		$model = JModelLegacy::getInstance('Database', 'InstallerModel');
		$model->fix();
	}
}