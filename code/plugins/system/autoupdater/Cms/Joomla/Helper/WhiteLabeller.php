<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Helper_Whitelabeller
{
	private $name = null;
	private $author = null;
	private $child_page = null;
	private $login_page = null;
	private $protect_child = null;
	private $hide_child = null;

	/**
	 * AutoUpdater_Cms_Joomla_Helper_Whitelabeller constructor.
	 *
	 * @param string|null $name
	 * @param string|null $child_page
	 * @param string|null $login_page
	 * @param bool|null   $protect_child
	 * @param bool|null   $hide_child
	 * @param string|null $author
	 */
	public function __construct($name = null, $child_page = null, $login_page = null, $protect_child = null, $hide_child = null, $author = null)
	{
		$this->name          = (string) !is_null($name) ? $name : AutoUpdater_Config::get('whitelabel_name');
		$this->author        = (string) !is_null($author) ? $author : AutoUpdater_Config::get('whitelabel_author');
		$this->child_page    = (string) !is_null($child_page) ? $child_page : AutoUpdater_Config::get('whitelabel_child_page');
		$this->login_page    = (string) !is_null($login_page) ? $login_page : AutoUpdater_Config::get('whitelabel_login_page');
		$this->protect_child = (bool) !is_null($protect_child) ? $protect_child : AutoUpdater_Config::get('protect_child');
		$this->hide_child    = (bool) !is_null($hide_child) ? $hide_child : AutoUpdater_Config::get('hide_child');
	}

	/**
	 * @throws AutoUpdater_Exception_Response
	 */
	public function handle()
	{
		$this->setName();
		$this->setChildPage();
		$this->setLoginPage();
		$this->protectChild();
	}

	/**
	 * Override name of Auto-Updater extension.
	 *
	 * @throws AutoUpdater_Exception_Response
	 *
	 * @return bool
	 */
	public function setName()
	{
        AutoUpdater_Config::set('whitelabel_name', $this->name);
        AutoUpdater_Config::set('whitelabel_author', $this->author);

		$this->setManifestCache();

		/** @see \Joomla\CMS\Language\Language::getKnownLanguages */
		// Get all available languages on site.
		foreach (JLanguage::getKnownLanguages() as $lang)
		{
			// Set language for right JText value.
			/** @var \Joomla\CMS\Language\Language|JLanguage $jlang */
			$jlang = JFactory::getLanguage();
			$jlang->setDefault($lang['tag']);
			$jlang->load();
			$jlang->load('plg_system_' . AUTOUPDATER_J_PLUGIN_SLUG);

			$override_filename = JPATH_ADMINISTRATOR . '/language/overrides/' . $lang['tag'] . '.override.ini';

			$override_name = array(
				'language' => $lang['name'] . ' [' . $lang['tag'] . ']',
				'client'   => 'Administrator',
				'file'     => $override_filename
			);

			$name_overrides = array(
				'AUTO-UPDATER'           => $this->name,
				'AUTOUPDATER'            => $this->name,
				'PLG_SYSTEM_AUTOUPDATER' => $this->name,
			);

			if (empty($this->name))
			{
				foreach ($name_overrides as $id => $item)
				{
					$override_name['id'] = $id;

					if (!$this->deleteOverride($override_name))
					{
						throw new AutoUpdater_Exception_Response('Could not delete overrides.');
					}
				}
			}
			else
			{
				foreach ($name_overrides as $id => $item)
				{
					$override_name['key']      = $id;
					$override_name['override'] = $item;
					$override_name['id']       = $id;

					if (!$this->saveOverride($override_name))
					{
						throw new AutoUpdater_Exception_Response('Could not save overrides.');
					}
				}
			}
		}

		return true;
	}

	/**
	 *
	 * @return bool
	 */
	public function setChildPage()
	{
		if (empty($this->child_page))
		{
			return AutoUpdater_Config::remove('whitelabel_child_page');
		}
		else
		{
			return AutoUpdater_Config::set('whitelabel_child_page', $this->child_page);
		}
	}

	/**
	 * Override content of site's login page.
	 *
	 * @return bool
	 */
	public function setLoginPage()
	{
		if (php_sapi_name() === 'cli')
		{
			return true;
		}

		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		// Check if custom module in position "login" exist - we store login_page there.
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select($db->qn('id'))
			->from($db->qn('#__modules'))
			->where(array(
				$db->qn('title') . '=' . $db->q('Admin login page'),
				$db->qn('position') . '=' . $db->q('login'),
				$db->qn('module') . '=' . $db->q('mod_custom'),
			));

		try
		{
			$module_id = $db->setQuery($query)
				->loadResult();
		}
		catch (Exception $e)
		{
		}

		try
		{
			if (empty($module_id))
			{
				// Module doesn't exist - add it.
				$module            = new stdClass();
				$module->title     = 'Admin login page';
				$module->content   = $this->login_page;
				$module->ordering  = 1;
				$module->position  = 'login';
				$module->published = 1;
				$module->module    = 'mod_custom';
				$module->access    = 1;
				$module->params    = '{"prepare_content":"1"}';
				$module->client_id = 1;
				$module->language  = '*';

				$db->insertObject('#__modules', $module, 'id');

				if (!empty($module->id))
				{
					$modules_menu           = new stdClass();
					$modules_menu->moduleid = $module->id;
					$modules_menu->menuid   = 0;

					$db->insertObject('#__modules_menu', $modules_menu);
				}
			}
			else
			{
				$query = $db->getQuery(true)
					->update($db->qn('#__modules'))
					->set($db->qn('content') . '=' . $db->q($this->login_page))
					->where($db->qn('id') . '=' . (int) $module_id);

				$db->setQuery($query)
					->execute();
			}
		}
		catch (Exception $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * Protect extension.
	 *
	 * @return bool
	 */
	public function protectChild()
	{
		$result = false;
        AutoUpdater_Config::set('protect_child', $this->protect_child);

		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('protected') . ' = ' . $db->q((int) $this->protect_child, false))
			->where(AutoUpdater_Cms_Joomla_Helper_Joomla::getChildWhereCondition());

		try
		{
			$result = $db->setQuery($query)->execute();
		}
		catch (Exception $e)
		{

		}

		return $result;
	}

	/**
	 * Override language constants values.
	 * It's part of administrator\components\com_languages\models\override.php save method, not used directly because off JPATH_COMPONENT usage there.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	protected function saveOverride($data)
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_languages/helpers/languages.php';

		// Parse the override.ini file in oder to get the keys and strings.
		$strings = LanguagesHelper::parseFile($data['file']);

		if (isset($strings[$data['id']]))
		{
			// If an existent string was edited check whether
			// the name of the constant is still the same.
			if ($data['key'] == $data['id'])
			{
				// If yes, simply override it.
				$strings[$data['key']] = $data['override'];
			}
			else
			{
				// If no, delete the old string and prepend the new one.
				unset($strings[$data['id']]);
				$strings = array($data['key'] => $data['override']) + $strings;
			}
		}
		else
		{
			// If it is a new override simply prepend it.
			$strings = array($data['key'] => $data['override']) + $strings;
		}

		return $this->writeOverride($strings, $data);
	}

	/**
	 * Delete override of language constants values.
	 * It's part of administrator\components\com_languages\models\overrides.php delete method, not used directly because of JPATH_COMPONENT usage there.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	protected function deleteOverride($data)
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_languages/helpers/languages.php';

		// Parse the override.ini file in oder to get the keys and strings.
		$strings = LanguagesHelper::parseFile($data['file']);

		// Unset strings that shall be deleted
		if (isset($strings[$data['id']]))
		{
			unset($strings[$data['id']]);
		}

		foreach ($strings as $key => $string)
		{
			$strings[$key] = str_replace('"', '"_QQ_"', $string);
		}

		return $this->writeOverride($strings, $data);
	}

	/**
	 * Helper method used in saveOverride and deleteOverride.
	 *
	 * @param array $strings
	 * @param array $data
	 *
	 * @return bool
	 */
	protected function writeOverride($strings, $data)
	{
		foreach ($strings as $key => $string)
		{
			$strings[$key] = str_replace('"', '"_QQ_"', $string);
		}

		// Write override.ini file with the strings.
		$registry = AutoUpdater_Cms_Joomla_Helper_Joomla::getRegistry();
		$registry->loadObject($strings);
		$reg = $registry->toString('INI');

		$file_manager = AutoUpdater_Filemanager::getInstance();

		if ($file_manager->put_contents($data['file'], $reg))
		{
			return true;
		}
	}

	protected function setManifestCache()
	{
		// Get the plugin manifest cache
		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('manifest_cache'))
			->from($db->qn('#__extensions'))
			->where(AutoUpdater_Cms_Joomla_Helper_Joomla::getChildWhereCondition());

		try
		{
			$manifest_cache = $db->setQuery($query)->loadResult();
		}
		catch (Exception $e)
		{

		}

		$manifest_cache = !empty($manifest_cache) ? json_decode($manifest_cache, true) : array();

		// Set a new manifest cache data
		$manifest_cache['name']        = $this->name ? $this->name : $manifest_cache['name'];
		$manifest_cache['author']      = $this->name ? $this->author : $manifest_cache['author'];
		$manifest_cache['authorEmail'] = $this->name ? '' : $manifest_cache['authorEmail'];
		$manifest_cache['authorUrl']   = $this->name ? '' : $manifest_cache['authorUrl'];
		$manifest_cache['copyright']   = $this->name ? 'Copyright (C) ' . date('Y') . ' ' . $this->author : $manifest_cache['copyright'];

		// Save the new manifest cache data
		$query = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set(array(
				$db->qn('name') . ' = ' . $db->q($manifest_cache['name']),
				$db->qn('manifest_cache') . ' = ' . $db->q(json_encode($manifest_cache))
			))
			->where(AutoUpdater_Cms_Joomla_Helper_Joomla::getChildWhereCondition());

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (Exception $e)
		{

		}
	}
}