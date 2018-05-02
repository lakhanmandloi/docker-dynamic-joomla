<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_GetEnvironment extends AutoUpdater_Task_GetEnvironment
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		$data = parent::doTask();

		$data['cms_version'] = JVERSION;

		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select('version()');

		try
		{
			$database_version_info = $db->setQuery($query)->loadResult();
		}
		catch (Exception $e)
		{
			$database_version_info = false;
		}

		if ($database_version_info)
		{
			$data['database_name'] = strpos(strtolower($database_version_info),
				'mariadb') !== false ? 'MariaDB' : 'MySQL';
		}
		else
		{
			$data['database_name'] = $db->name;
		}

		$data['database_version'] = $db->getVersion();

		if ($data['database_name'] === 'MariaDB' && $database_version_info)
		{
			$version = explode('-', $database_version_info);
			if (!empty($version[0]))
			{
				$data['database_version'] = $version[0];
			}
		}

		return $data;
	}
}