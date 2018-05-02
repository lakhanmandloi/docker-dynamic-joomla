<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_PostExtensionUpdate extends AutoUpdater_Task_PostExtensionUpdate
{
	/**
	 * @return array
	 *
	 * @throws AutoUpdater_Exception_Response
	 */
	public function doTask()
	{
		jimport('joomla.installer.installer');

		$type = $this->input('type');
		$slug = $this->input('slug');
		$path = $this->input('path');

		if (empty($type) || empty($slug) || empty($path))
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

		$extension = AutoUpdater_Cms_Joomla_Helper_Joomla::getExtension($type, $slug);

		/*
		 * Try to find the correct install directory.  In case the package is inside a
		 * subdirectory detect this and set the install directory to the correct path.
		 *
		 * List all the items in the installation directory.  If there is only one, and
		 * it is a folder, then we will set that folder to be the installation folder.
		 */
		$dirList = array_merge((array) JFolder::files($path, ''), (array) JFolder::folders($path, ''));

		if (count($dirList) === 1)
		{
			if (JFolder::exists($path . '/' . $dirList[0]))
			{
				$dir = JPath::clean($path . '/' . $dirList[0]);
			}
		}

		$installation_folder = JPath::clean(empty($dir) ? $path : $dir);
		$method              = AutoUpdater_Cms_Joomla_Helper_Joomla::isExtensionInstalled($extension) ?
			'update' : 'install';

		// Install the package
		/** @var \Joomla\CMS\Installer\Installer|JInstaller $installer */
		$installer = JInstaller::getInstance();
		if (!$installer->$method($installation_folder))
		{
			throw new AutoUpdater_Exception_Response('Install error: ' . $installer->message, 400);
		}

		/** @see \Joomla\CMS\Installer\InstallerHelper::cleanupInstall */
		JInstallerHelper::cleanupInstall(null, $path);

		// Remove update of this extension from updates db table
		$this->purgeUpdates($extension);

		$filemanager->clearPhpCache();

		return array(
			'success' => true,
		);
	}

	/**
	 * @param object $extension
	 *
	 * @return bool
	 */
	protected function purgeUpdates($extension)
	{
		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);

		$conditions = array(
			$db->qn('type') . ' = ' . $db->q($extension->type),
			$db->qn('element') . ' = ' . $db->q($extension->element),
		);

		if ($extension->folder)
		{
			$conditions[] = $db->qn('folder') . ' = ' . $db->q($extension->folder);
		}
		if ($extension->client_id)
		{
			$conditions[] = $db->qn('client_id') . ' = ' . $db->q($extension->client_id);
		}

		$query->delete($db->qn('#__updates'))->where($conditions);

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (Exception $e)
		{
			return false;
		}

		return true;
	}
}