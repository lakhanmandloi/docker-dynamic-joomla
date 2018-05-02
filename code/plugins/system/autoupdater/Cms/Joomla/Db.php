<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Db extends AutoUpdater_Db
{
	/**
	 * @return array
	 */
	public function getConfig()
	{
		/** @see \Joomla\CMS\Factory::getConfig */
		$config = JFactory::getConfig();

		return array(
			'name'     => $config->get('db'),
			'user'     => $config->get('user'),
			'password' => $config->get('password'),
			'host'     => rtrim($config->get('host'), ':'),
			'prefix'   => $config->get('dbprefix'),
			'driver'   => $config->get('dbtype', class_exists('mysqli') ? 'mysqli' : 'mysql'),
		);
	}

	/**
	 * @return bool
	 */
	public function setDefaultDbo()
	{
		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db = JFactory::getDbo();

		if (version_compare(JVERSION, '3.0', '<'))
		{
			// For J2 class names examples: JDatabaseMySQL, JDatabaseMySQLi, JDatabaseSQLAzure, JDatabaseSQLSrv.
			$pattern = '/^JDatabase[A-Za-z]+$/';
		}
		else
		{
			// For J3 class names examples: JDatabaseDriverMysql, JDatabaseDriverMysqli, JDatabaseDriverSqlazure, JDatabaseDriverSqlsrv.
			$pattern = '/^JDatabaseDriver[A-Z]{1}[a-z]+$/';
		}

		// Only if db is not instance of default driver's class.
		if (!preg_match($pattern, get_class($db)))
		{
			/** @see \Joomla\CMS\Factory::getConfig */
			$config = JFactory::getConfig();

			$options = array(
				'driver'   => $config->get('dbtype'),
				'host'     => $config->get('host'),
				'user'     => $config->get('user'),
				'password' => $config->get('password'),
				'database' => $config->get('db'),
				'prefix'   => $config->get('dbprefix')
			);

			try
			{
				if (version_compare(JVERSION, '3.0', '<'))
				{
					$db = JDatabase::getInstance($options);
				}
				else
				{
					$db = JDatabaseDriver::getInstance($options);
				}
			}
			catch (Exception $e)
			{
                AutoUpdater_Log::error('Failed to load Joomla native database driver: ' . $e->getMessage());

				return false;
			}

			if (method_exists($db, 'setDebug'))
			{
				$db->setDebug($config->get('debug'));
			}

			JFactory::$database = $db;
		}

		return true;
	}

	/**
	 * @param string $sql
	 *
	 * @return bool
	 */
	public function doQuery($sql)
	{
		try
		{
			/** @see \Joomla\CMS\Factory::getDbo */
			return JFactory::getDbo()->setQuery($sql)->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * @param string $sql
	 *
	 * @return mixed
	 */
	public function doQueryWithResults($sql)
	{
		try
		{
			/** @see \Joomla\CMS\Factory::getDbo */
			return JFactory::getDbo()->setQuery($sql)->loadAssocList();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

}