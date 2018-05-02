<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Installer extends AutoUpdater_Installer
{
	protected $uninstalled = false;
    protected $old_slug = 'perfect';
    protected $old_file = '';
    protected $new_file = '';

	public function __construct()
	{
        $this->old_slug .= 'dash';
		jimport('joomla.installer.installer');

        AutoUpdater_Api::getInstance()->initCms();

        $this->old_slug .= 'board';
        $this->old_file = JPATH_PLUGINS . '/system/' . $this->old_slug . '/' . $this->old_slug . '.php';
        $this->new_file = JPATH_PLUGINS . '/system/autoupdater/autoupdater.php';
	}

	/**
	 * @return bool
	 */
	public function install()
	{
        // TODO 1.16.1 if exists the old and the new plugin then remove the old one during installation and update

		$result = parent::install();

		$result = $this->enableExtension() && $result;

		return $result;
	}

	/**
	 * @return bool
	 */
	public function update()
	{
		$result = parent::update();

        $result = $this->enableExtension() && $result;

		if (defined('AUTOUPDATER_J_PLUGIN_HELPER_PATH'))
		{
			include_once AUTOUPDATER_J_PLUGIN_HELPER_PATH . 'Joomla.php';
			if (class_exists('AutoUpdater_Cms_Joomla_Helper_Whitelabeller'))
			{
				$labeller = new AutoUpdater_Cms_Joomla_Helper_Whitelabeller();
				$labeller->handle();
			}
		}

		return $result;
	}

	/**
     * @param bool $self
     *
	 * @return bool
	 */
	public function uninstall($self = false)
	{
        $this->disableExtensionProtection();

        // Do not run uninstaller if Auto-Updater is installed and this is another plugin
        if (file_exists($this->old_file) && file_exists($this->new_file))
        {
            return true;
        }

		// Make sure that it would not run it twice with Joomla Extensions Manager
		if ($this->uninstalled)
		{
			return true;
		}
		$this->uninstalled = true;

		$result = parent::uninstall($self);

		// Do not run the uninstall script again if it was trigger by Joomla back-end
		if (defined('AUTOUPDATER_J_PLUGIN_INSTALLER') || $self === false)
		{
			return $result;
		}

		try
		{
			/** @var \Joomla\CMS\Installer\Installer|JInstaller $installer */
			$installer = JInstaller::getInstance();
			$result = $installer->uninstall('plugin', $this->getExtensionId()) && $result;
		}
		catch (Exception $e)
		{
            AutoUpdater_Log::error('Failed to uninstall Child extension: ' . $e->getMessage());
			$result = false;
		}

		return $result;
	}

	/**
	 *
	 * @return int
	 */
	protected function getExtensionId()
	{
		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where($this->getExtensionWhereCondition());

		try
		{
			return (int) $db->setQuery($query)->loadResult();
		}
		catch (Exception $e)
		{
            AutoUpdater_Log::error('Failed to get Child extension ID: ' . $e->getMessage());
		}

		return 0;
	}

	/**
	 * @return bool
	 */
	protected function disableExtensionProtection()
	{
        AutoUpdater_Log::debug('Disable Child extension protection');

		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('protected') . ' = ' . $db->q(0, false))
			->where($this->getExtensionWhereCondition());

		try
		{
			return $db->setQuery($query)->execute();
		}
		catch (Exception $e)
		{
            AutoUpdater_Log::error('Failed to disable Child extension protection: ' . $e->getMessage());
		}

		return false;
	}

	/**
	 * @return bool
	 */
	protected function enableExtension()
	{
        AutoUpdater_Log::debug('Enable Child extension');

		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('enabled') . ' = ' . $db->q(1, false))
			->where($this->getExtensionWhereCondition());

		try
		{
			return $db->setQuery($query)->execute();
		}
		catch (Exception $e)
		{
            AutoUpdater_Log::error('Failed to enable Child extension: ' . $e->getMessage());
		}

		return false;
	}

	/**
	 * @return array
	 */
	protected function getExtensionWhereCondition()
	{
		return AutoUpdater_Cms_Joomla_Helper_Joomla::getChildWhereCondition($this->getOption('slug', AUTOUPDATER_J_PLUGIN_SLUG));
	}

	/**
     * TODO remove after migration of all clients
	 * @return bool
	 */
	protected function migrateVersion1_13()
	{
	    $old_slug = 'perfect';
	    if ($this->getOption('slug', AUTOUPDATER_J_PLUGIN_SLUG) !== 'autoupdater')
        {
            // Disable the response encryption when updating to a new plugin
            AutoUpdater_Config::set('encryption', 0);
            return parent::migrateVersion1_13(); // create the AES key
        }

		// Load the old plugin
		/** @var \Joomla\CMS\Table\Extension|JTableExtension $table */
        $old_slug .= 'dashboard';
		$table = JTable::getInstance('extension');
		$table->load(array(
			'type'    => 'plugin',
			'folder'  => 'system',
			'element' => $old_slug
		));

		// There is no old plugin
		if (!$table->get('extension_id'))
		{
            return parent::migrateVersion1_13(); // create the AES key
        }

        // Migrate the old plugin configuration
        $params = json_decode($table->get('params', '[]'), true);
        if (!empty($params['read_token']))
        {
            AutoUpdater_Config::set('read_token', $params['read_token']);
        }
        if (!empty($params['write_token']))
        {
            AutoUpdater_Config::set('write_token', $params['write_token']);
        }
        if (!empty($params['token_expires_at']))
        {
            AutoUpdater_Config::set('token_expires_at', $params['token_expires_at']);
        }
        if (!empty($params['aes_key']))
        {
            AutoUpdater_Config::set('aes_key', $params['aes_key']);
        }
        AutoUpdater_Config::set('backuptool_dir', isset($params['backuptool_dir']) ? $params['backuptool_dir'] : '');
        AutoUpdater_Config::set('whitelabel_name', isset($params['whitelabel_name']) ? $params['whitelabel_name'] : '');
        AutoUpdater_Config::set('whitelabel_author', isset($params['whitelabel_author']) ? $params['whitelabel_author'] : '');
        AutoUpdater_Config::set('whitelabel_child_page', isset($params['whitelabel_child_page']) ? $params['whitelabel_child_page'] : '');
        AutoUpdater_Config::set('whitelabel_login_page', isset($params['whitelabel_login_page']) ? $params['whitelabel_login_page'] : '');
        AutoUpdater_Config::set('hide_child', isset($params['hide_child']) ? (int) $params['hide_child'] : 0);
        AutoUpdater_Config::set('protect_child', isset($params['protect_child']) ? (int) $params['protect_child'] : 0);
        AutoUpdater_Config::set('ssl_verify', isset($params['ssl_verify']) ? (int) $params['ssl_verify'] : 0);
        AutoUpdater_Config::set('encryption', 1);

        $result = parent::migrateVersion1_13(); // create the AES key

        // Remove the old plugin
        /** @var \Joomla\CMS\Installer\Installer|JInstaller $installer */
        $installer = JInstaller::getInstance();
        $installer->uninstall('plugin', (int)$table->get('extension_id'));

        // Restore the slug after removing the old plugin
        $this->setOption('slug', 'autoupdater');

		return $result;
	}
}