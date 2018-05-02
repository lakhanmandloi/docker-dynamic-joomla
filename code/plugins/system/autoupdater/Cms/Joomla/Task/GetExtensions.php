<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_GetExtensions extends AutoUpdater_Task_GetExtensions
{
	public $language;

	/**
	 * @return array
	 */
	public function doTask()
	{
		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select(array(
				$db->qn('name'),
				$db->qn('type'),
				$db->qn('element'),
				$db->qn('folder'),
				$db->qn('client_id'),
				$db->qn('extension_id'),
				$db->qn('manifest_cache'),
				$db->qn('enabled'),
			))
			->from($db->qn('#__extensions'));

		try
		{
			$exts = $db->setQuery($query)->loadObjectList();
		}
		catch (Exception $e)
		{
			return array(
				'success'    => false,
				'extensions' => array(),
				'message'    => 'Failed to load extensions',
				'error'      => array(
					'code'    => $e->getCode(),
					'message' => $e->getMessage(),
				),
			);
		}

		$extensions1 = array();
		$extensions2 = array();

		if (!empty($exts))
		{
			// Get info about update servers.
			$update_servers = $this->getUpdateServers();
			// Get info about installed packages.
			$packages = $this->getPackages();

			foreach ($exts as $item)
			{

				$item->element = strtolower(trim($item->element));
				$item->type    = strtolower(trim($item->type));
				$item->folder  = strtolower(trim($item->folder));

				$this->loadLanguage($item->element, $item->type, $item->folder, $item->client_id);

				$extension                 = new stdClass();
				$extension->title          = $this->filterHTML(JText::_($item->name));
				$extension->name           = $this->filterHTML($item->name);
				$extension->slug           = $item->element;
				$extension->type           = $item->type;
				$extension->enabled        = $item->enabled;
				$extension->package        = '';
				$extension->update_servers = ((!empty($update_servers[$item->extension_id])) ? $update_servers[$item->extension_id] : null);

				if ($item->type == 'plugin')
				{
					$extension->slug = $item->folder . '/' . $item->element;
				}
				if ($item->client_id && in_array($item->type, array('module', 'language', 'template')))
				{
					$extension->slug = 'admin/' . $item->element;
				}

				$pkg_key = md5($extension->type . $extension->slug);
				if (isset($packages[$pkg_key]))
				{
					$extension->package = $packages[$pkg_key];
				}

				// check manifest
				if (!$manifest = $this->parseJSON($item->manifest_cache))
				{
					$manifest = $this->checkManifest($item);
				}

				$manifest = AutoUpdater_Cms_Joomla_Helper_Joomla::getRegistry($manifest);

				$extension->author     = $this->filterHTML($manifest->get('author', ''));
				$extension->author_url = $this->filterHTML($manifest->get('authorUrl', ''));

				if ($extension->type == 'file' && $extension->slug == 'joomla')
				{
					$extension->type    = 'cms';
					$extension->version = JVERSION;

					array_unshift($extensions1, $extension);
				}
				else
				{
					$extension->version = $manifest->get('version');

					if ($extension->package)
					{
						// Package child items put at the end
						array_push($extensions2, $extension);
					}
					else
					{
						array_push($extensions1, $extension);
					}
				}
			}
		}

		return array(
			'success'    => true,
			'extensions' => array_merge($extensions1, $extensions2),
		);
	}

	/**
	 * @return array|bool
	 */
	private function getUpdateServers()
	{
		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select(array(
				$db->qn('ue.extension_id'),
				$db->qn('us.type'),
				$db->qn('us.location'),
				$db->qn('us.enabled'),
				version_compare(JVERSION, '3.2.2', '>=')
					? $db->qn('us.extra_query')
					: 'NULL AS ' . $db->qn('extra_query')
			))
			->from($db->qn('#__update_sites', 'us'))
			->leftJoin($db->qn('#__update_sites_extensions', 'ue')
				. ' ON ' . $db->qn('us.update_site_id') . ' = ' . $db->qn('ue.update_site_id'))
			->order($db->qn('us.enabled') . ' DESC, ' . $db->qn('us.update_site_id') . ' DESC');

		try
		{
			$items = $db->setQuery($query)->loadObjectList();
		}
		catch (Exception $e)
		{
			return false;
		}

		if (version_compare(JVERSION, '3.0.0', '<'))
		{
			$dispatcher = JDispatcher::getInstance();
		}
		else
		{
			// From J! v4.0.0 it will be replaced with sth like $n_dispatcher = new \Joomla\Event\Dispatcher;
			// But new $n_dispatcher has completely different methods like triggerEvent($event) with only one argument.
			$dispatcher = JEventDispatcher::getInstance();
		}

		$update_sites = array();
		foreach ($items as $item)
		{
			if (filter_var($item->location, FILTER_VALIDATE_URL) === false)
			{
				continue;
			}

			if (empty($update_sites[$item->extension_id]))
			{
				$update_sites[$item->extension_id] = array();
			}

			$headers = array();
			$url     = $item->location;
			$dispatcher->trigger('onInstallerBeforePackageDownload', array(&$url, &$headers));

			$update_site = array(
				'url'     => $item->location,
				'type'    => $item->type,
				'enabled' => $item->enabled
			);

			if (!empty($item->extra_query))
			{
				$update_site['dl_query'] = $item->extra_query;
			}
			if ($url != $item->location)
			{
				$update_site['dl_url'] = $url;
			}
			if (!empty($headers))
			{
				$update_site['dl_headers'] = $headers;
			}

			$update_sites[$item->extension_id][] = $update_site;
		}

		return $update_sites;
	}

	/**
	 * @return array
	 */
	private function getPackages()
	{
		jimport('joomla.filesystem.folder');
		$manifests_files = JFolder::files(JPATH_MANIFESTS . '/packages/', '\.xml$');

		$packages = array();
		foreach ($manifests_files as $manifests_file)
		{
			$manifest = simplexml_load_file(JPATH_MANIFESTS . '/packages/' . $manifests_file);
			$pkg_name = 'pkg_' . strtolower(trim($manifest->packagename));
			if (isset($manifest->files->file))
			{
				foreach ($manifest->files->file as $file)
				{

					if (isset($file['id']) && $file['id'])
					{
						$slug = strtolower(trim($file['id']));
					}
					else
					{
						continue;
					}

					$type = null;
					if (isset($file['type']) && $file['type'])
					{
						$type = strtolower(trim($file['type']));
						if ($file['type'] == 'plugin' && isset($file['group']) && $file['group'])
						{
							$slug = strtolower(trim($file['group'])) . '/' . $slug;
						}
						elseif (isset($file['client']) && ($file['client'] == 1 || strtolower($file['client']) == 'administrator')
							&& in_array($file['type'], array('module', 'language', 'template')))
						{
							$slug = 'admin/' . $slug;
						}
					}

					$packages[md5($type . $slug)] = $pkg_name;
				}
			}
		}

		return $packages;
	}

	/**
	 * @param string $element
	 * @param string $type
	 * @param string $folder
	 * @param int    $client_id
	 */
	private function loadLanguage($element, $type, $folder, $client_id)
	{
		$paths = array(JPATH_ADMINISTRATOR, JPATH_ROOT);
		if ($type == 'component')
		{
			$key     = $element;
			$paths[] = JPATH_ADMINISTRATOR . '/components/' . $element;
			$paths[] = JPATH_ROOT . '/components/' . $element;
		}
		elseif ($type == 'module')
		{
			$key     = $element;
			$paths[] = ($client_id ? JPATH_ADMINISTRATOR : JPATH_ROOT) . '/modules/' . $element;
		}
		elseif ($type == 'plugin')
		{
			$key     = 'plg_' . $folder . '_' . $element;
			$paths[] = JPATH_PLUGINS . '/' . $folder . '/' . $element;
		}
		elseif ($type == 'template')
		{
			$key     = 'tpl_' . $element;
			$paths[] = ($client_id ? JPATH_ADMINISTRATOR : JPATH_ROOT) . '/templates/' . $element;
		}
		elseif ($type == 'library')
		{
			$key = 'lib_' . $element;
		}
		elseif ($type == 'file')
		{
			$key = 'file_' . $element;
		}
		else
		{
			$key = $element;
		}

		/** @var \Joomla\CMS\Language\Language|JLanguage $lang */
		$lang = JFactory::getLanguage();
		foreach ($paths as $path)
		{
			if ($lang->load($key . '.sys', $path, 'en-GB'))
			{
				break;
			}
		}
	}

	/**
	 * @param string $json_string
	 *
	 * @return bool|array
	 */
	private function parseJSON($json_string)
	{
		$json = json_decode($json_string, true);

		// If we are using PHP 5.3.0 or bigger or
		// if this is php 5.2 and json string don't contains `NULL` and function returns null
		if ((function_exists('json_last_error') && json_last_error() !== JSON_ERROR_NONE) || (is_null($json) && strtolower($json_string) !== 'null'))
		{
			return false;
		}

		return $json;
	}

	/**
	 * @param object $extension
	 *
	 * @return array|bool
	 */
	private function checkManifest($extension)
	{
		$filemanager = AutoUpdater_Filemanager::getInstance();

		switch ($extension->type)
		{
			case 'component':
				$xml_path = JPATH_ADMINISTRATOR . '/components/' . $extension->element
					. '/' . str_replace('com_', '', $extension->element) . '.xml';
				if (!$filemanager->is_file($xml_path))
				{
					$xml_path = JPATH_ROOT . '/components/' . $extension->element
						. '/' . str_replace('com_', '', $extension->element) . '.xml';
				}
				break;

			case 'file':
				$xml_path = JPATH_MANIFESTS . '/files/' . $extension->element . '.xml';
				break;

			case 'library':
				$xml_path = JPATH_MANIFESTS . '/libraries/' . $extension->element . '.xml';
				break;

			case 'module':
				/** @see \Joomla\CMS\Application\ApplicationHelper::getClientInfo */
				$client   = JApplicationHelper::getClientInfo($extension->client_id);
				$xml_path = $client->path . '/modules/' . $extension->element . '/' . $extension->element . '.xml';
				break;

			case 'package':
				$xml_path = JPATH_MANIFESTS . '/packages/' . $extension->element . '.xml';
				break;

			case 'plugin':
				/** @see \Joomla\CMS\Application\ApplicationHelper::getClientInfo */
				$client   = JApplicationHelper::getClientInfo($extension->client_id);
				$basePath = $client->path . '/plugins/' . $extension->folder;

				if ($filemanager->is_dir($basePath . '/' . $extension->element))
				{
					$xml_path = $basePath . '/' . $extension->element . '/' . $extension->element . '.xml';
				}
				else
				{
					// @deprecated 4.0 - This path supports Joomla! 1.5 plugin folder layouts
					$xml_path = $basePath . '/' . $extension->element . '.xml';
				}
				break;

			case 'template':
				/** @see \Joomla\CMS\Application\ApplicationHelper::getClientInfo */
				$client   = JApplicationHelper::getClientInfo($extension->client_id);
				$xml_path = $client->path . '/templates/' . $extension->element . '/templateDetails.xml';
				break;

			// TODO Find language XML file
			case 'language':
			default:
				$xml_path = null;
				break;
		}

		/** @see \Joomla\CMS\Installer\Installer::parseXMLInstallFile */
		if (!$xml_path || !$filemanager->is_file($xml_path) || !($data = JInstaller::parseXMLInstallFile($xml_path)))
		{
			$data = $this->parseManifestString($extension->manifest_cache);
		}

		return $data;
	}

	/**
	 * @param string $manifest_cache
	 *
	 * @return bool|array
	 */
	private function parseManifestString($manifest_cache)
	{
		// first problem i might know is a to long string ended with not a json tail :(
		if (preg_match('/(.*description\"\:\")(.*)/', $manifest_cache, $match) && isset($match[1]))
		{
			return $this->parseJSON($match[1]);
		}

		return false;
	}
}
