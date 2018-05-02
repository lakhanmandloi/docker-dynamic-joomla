<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_PostSecurityaudit extends AutoUpdater_Task_PostSecurityaudit
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		/** @see \Joomla\CMS\Factory::getConfig */
		$config = JFactory::getConfig();

		$data = parent::doTask();

		$filemanager = AutoUpdater_Filemanager::getInstance();

		$data['additional_data']['database_prefix']            = (int) ($config->get('dbprefix') != 'jos_');
		$data['additional_data']['database_user']              = (int) ($config->get('user') != 'root');
		$data['additional_data']['debug_mode']                 = (int) (!$config->get('debug'));
		$data['additional_data']['template_positions_display'] = (int) $this->templateCanShowModulePositions();
		$data['additional_data']['installation_folder']        = (int) (!$filemanager->exists(JPATH_INSTALLATION));

		return $data;
	}

	/**
	 * @return int
	 */
	protected function isAdminUsernameNotUsed()
	{
		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->qn('#__users'))
			->where($db->qn('username') . '=' . $db->q('admin'));

		try
		{
			$admin_user = $db->setQuery($query)->loadResult();
		}
		catch (Exception $e)
		{
		}

		return (int) empty($admin_user);
	}

	/**
	 * @return int
	 */
	protected function isErrorReportingDisabled()
	{
		/** @see \Joomla\CMS\Factory::getConfig */
		if (JFactory::getConfig()->get('error_reporting') === 'none')
		{
			return 1;
		}

		return parent::isErrorReportingDisabled();
	}

	/**
	 * @return int
	 */
	protected function isPopularPasswordNotUsed()
	{
		// Get users only with core.login.admin access.
		// Get rules from root asset with information about connection between core.login.admin action and access level which can do this action.
		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('rules'))
			->from($db->qn('#__assets'))
			->where($db->qn('parent_id') . '=0');

		$groups_with_backend = array();
		try
		{
			$root_rules = json_decode($db->setQuery($query)->loadResult(), true);
			if (!empty($root_rules['core.login.admin']))
			{
				foreach ($root_rules['core.login.admin'] as $group_id => $rule)
				{
					if ($rule)
					{
						$groups_with_backend[] = $group_id;
					}
				}
			}
			if (!empty($root_rules['core.admin']))
			{
				foreach ($root_rules['core.admin'] as $group_id => $rule)
				{
					if ($rule)
					{
						$groups_with_backend[] = $group_id;
					}
				}
			}
		}
		catch (Exception $e)
		{
		}

		// Get users from groups.
		if (!empty($groups_with_backend) && empty($skip_passwords))
		{
			$groups_with_backend = array_merge($groups_with_backend, $this->getChildrenOfGroups($groups_with_backend));

			$query = $db->getQuery(true)
				->select($db->qn('u.password'))
				->from($db->qn('#__users', 'u'))
				->innerJoin($db->qn('#__user_usergroup_map', 'uum')
					. ' ON ' . $db->qn('uum.user_id') . ' = ' . $db->qn('u.id'))
				->where(array(
					$db->qn('u.block') . '=0',
					$db->qn('uum.group_id') . ' IN (' . implode(',', $groups_with_backend) . ')'
				));

			try
			{
				$users_passwords = $db->setQuery($query)->loadColumn();
			}
			catch (Exception $e)
			{
			}

			if (!empty($users_passwords))
			{
				foreach ($users_passwords as $user_password)
				{
					foreach ($this->popular_passwords as $popular_password)
					{
						if ($this->verifyPassword($popular_password, $user_password) === true)
						{
							return 0;
						}
					}
				}
			}
		}

		return 1;
	}

	/**
	 * @return array
	 */
	protected function getBackupFileUnprotectedDirectories()
	{
		$paths = parent::getBackupFileUnprotectedDirectories();

		$this->getBackupFileUnprotectedDirForAkeeba($paths);
		$this->getBackupFileUnprotectedDirForEasyJoomlaBackup($paths);
		$this->getBackupFileUnprotectedDirForXCloner($paths);

		return $paths;
	}

	/**
	 * @param array $paths
	 */
	protected function getBackupFileUnprotectedDirForAkeeba(&$paths)
	{
		if (version_compare(PHP_VERSION, '5.3', '<'))
		{
			return;
		}

		try
		{
			$akeeba_path = JPATH_ADMINISTRATOR . '/components/com_akeeba';
			$fof30_path  = JPATH_LIBRARIES . '/fof30';

			$file_manager = AutoUpdater_Filemanager::getInstance();

			if ($file_manager->is_dir(JPATH_ADMINISTRATOR . '/components/com_akeeba') && $file_manager->is_file($akeeba_path . '/BackupEngine/Factory.php') &&
				$file_manager->is_file($fof30_path . '/Autoloader/Autoloader.php') && $file_manager->is_file($fof30_path . '/Container/Container.php')
			)
			{
				if (!defined('AKEEBAENGINE'))
				{
					define('AKEEBAENGINE', 1);
					define('AKEEBAROOT', $akeeba_path . '/BackupEngine');
					define('ALICEROOT', $akeeba_path . '/AliceEngine');
				}

				require_once $akeeba_path . '/BackupEngine/Factory.php';

				// Load ALICE (Pro version only)
				if (JFile::exists($akeeba_path . '/AliceEngine/factory.php'))
				{
					require_once $akeeba_path . '/AliceEngine/factory.php';
				}

				require_once $fof30_path . '/Autoloader/Autoloader.php';
				require_once $fof30_path . '/Container/Container.php';

				// Get profile_id from last backup - that's how Akeeba is getting profile id.
				/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true)
					->select($db->qn('profile_id'))
					->from($db->qn('#__ak_stats'))
					->order($db->qn('id') . ' DESC');

				$db->setQuery($query, 0, 1);

				try
				{
					$platform_class = 'Akeeba\Engine\Platform';
					$factory_class  = 'Akeeba\Engine\Factory';
					$profile_id     = $db->loadResult();
					$profile_id     = $profile_id ?: 1;
					$platform_class::addPlatform('joomla3x', $akeeba_path . '/BackupPlatform/Joomla3x');
					$platform_class::getInstance()->load_configuration($profile_id);
					$akeeba_engine_config   = $factory_class::getConfiguration();
					$backups_directory_path = $akeeba_engine_config->get('akeeba.basic.output_directory');
				}
				catch (Exception $e)
				{
				}

				if (!empty($backups_directory_path) && $file_manager->is_dir($backups_directory_path))
				{
					if ($this->hasRemoteAccess($backups_directory_path))
					{
						$paths[] = $backups_directory_path;
					}
				}
			}
		}
		catch (Exception $e)
		{
		}
	}

	/**
	 * @param array $paths
	 */
	protected function getBackupFileUnprotectedDirForEasyJoomlaBackup(&$paths)
	{
		$file_manager = AutoUpdater_Filemanager::getInstance();

		// This backup folder can not be changed by config or user settings.
		$backups_directory_path = JPATH_ADMINISTRATOR . '/components/com_easyjoomlabackup/backups/';

		if (!empty($backups_directory_path) && $file_manager->is_dir($backups_directory_path))
		{
			if ($this->hasRemoteAccess($backups_directory_path))
			{
				$paths[] = $backups_directory_path;
			}
		}
	}

	/**
	 * @param array $paths
	 */
	protected function getBackupFileUnprotectedDirForXCloner(&$paths)
	{
		$file_manager = AutoUpdater_Filemanager::getInstance();

		$config_file = JPATH_ADMINISTRATOR . '/components/com_xcloner-backupandrestore\cloner.config.php';
		if (JFile::exists($config_file))
		{
			require_once $config_file;

			if (isset($_CONFIG['backup_path']))
			{
				// Taken from administrator/components/com_xcloner-backupandrestore/common.php
				$backups_dir = str_replace("//administrator", "/administrator",
					$_CONFIG['backup_path'] . "/administrator/backups");
				$backups_dir = str_replace("\\", "/", $backups_dir);

				$backups_directory_path = $backups_dir;

				if (!empty($backups_directory_path) && $file_manager->is_dir($backups_directory_path))
				{
					if ($this->hasRemoteAccess($backups_directory_path))
					{
						$paths[] = $backups_directory_path;
					}
				}
			}
		}
	}

	/**
	 * Verify password with backward compatibility
	 *
	 * @param   string $password The plaintext password to encrypt.
	 * @param   string $hash     The hash to verify against.
	 *
	 * @return  bool    True if the password and hash match, false otherwise
	 */
	private function verifyPassword($password, $hash)
	{
		if (version_compare(JVERSION, '3.2.1') == -1)
		{
			if (version_compare(JVERSION, '3.2.0') == -1)
			{
				// Taken from /plugins/authentication/joomla/joomla.php onUserAuthenticate
				$parts = explode(':', $hash);
				$crypt = $parts[0];
				$salt  = @$parts[1];
				/** @see \Joomla\CMS\User\UserHelper::getCryptedPassword */
				$testcrypt = JUserHelper::getCryptedPassword($password, $salt);

				if ($crypt == $testcrypt)
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				// Taken from /plugins/authentication/joomla/joomla.php onUserAuthenticate
				$match = false;
				if (substr($hash, 0, 4) == '$2y$')
				{
					// BCrypt passwords are always 60 characters, but it is possible that salt is appended although non standard.
					$password60 = substr($hash, 0, 60);

					/** @see \Joomla\CMS\Crypt\Crypt::hasStrongPasswordSupport */
					if (JCrypt::hasStrongPasswordSupport())
					{
						$match = password_verify($password, $password60);
					}
				}
				elseif (substr($hash, 0, 8) == '{SHA256}')
				{
					// Check the password
					$parts = explode(':', $hash);
					$crypt = $parts[0];
					$salt  = @$parts[1];
					/** @see \Joomla\CMS\User\UserHelper::getCryptedPassword */
					$testcrypt = JUserHelper::getCryptedPassword($password, $salt, 'sha256', false);

					if ($hash == $testcrypt)
					{
						$match = true;
					}
				}
				else
				{
					// Check the password
					$parts = explode(':', $hash);
					$crypt = $parts[0];
					$salt  = @$parts[1];
					/** @see \Joomla\CMS\User\UserHelper::getCryptedPassword */
					$testcrypt = JUserHelper::getCryptedPassword($password, $salt, 'md5-hex', false);

					if ($crypt == $testcrypt)
					{
						$match = true;
					}
				}

				return $match;
			}
		}
		else
		{
			/** @see \Joomla\CMS\User\UserHelper::verifyPassword */
			return JUserHelper::verifyPassword($password, $hash);
		}
	}

	/**
	 * Recursive function to get all children of user's group.
	 *
	 * @param array $groups
	 *
	 * @return array|mixed
	 */
	private function getChildrenOfGroups($groups)
	{
		static $counter = 10; // Max 10 levels of recursion for safety.

		$counter--;

		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select($db->qn('id'))
			->from($db->qn('#__usergroups'))
			->where($db->qn('parent_id') . ' IN (' . implode(',', $groups) . ')');

		try
		{
			$children = $db->setQuery($query)->loadColumn();
		}
		catch (Exception $e)
		{
		}

		if (empty($children) || empty($counter))
		{
			return array();
		}
		else
		{
			$children_of_children = $this->getChildrenOfGroups($children);

			$children = array_merge($children, $children_of_children);

			return $children;
		}
	}

	/**
	 * @return bool
	 */
	private function templateCanShowModulePositions()
	{
		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db = JFactory::getDbo();

		// Check if template can show module positions.
		$query = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__extensions'))
			->where(array(
				$db->quoteName('name') . '=' . $db->quote('com_templates'),
				$db->quoteName('type') . '=' . $db->quote('component')
			));

		$db->setQuery($query);

		try
		{
			$com_templates_params       = json_decode($db->loadResult());
			$template_positions_display = $com_templates_params->template_positions_display;
		}
		catch (Exception $e)
		{
		}

		return empty($template_positions_display);
	}
}