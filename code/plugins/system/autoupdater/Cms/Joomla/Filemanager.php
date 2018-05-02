<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Filemanager extends AutoUpdater_Filemanager
{
	public function __construct()
	{
		parent::__construct();

		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.path');
	}

	/**
	 * @param string $file
	 * @param string $contents
	 *
	 * @return bool
	 */
	public function put_contents($file, $contents)
	{
		return JFile::write($file, $contents);
	}

	/**
	 * TODO Consider this - using methods from J! we loose functionalities connected with few arguments.
	 *
	 * @param string $source
	 * @param string $destination
	 * @param bool   $overwrite
	 * @param bool   $mode
	 *
	 * @return bool
	 */
	public function copy($source, $destination, $overwrite = false, $mode = false)
	{
		if (substr($source, -1) == '*')
		{
			$result      = true;
			$source      = substr($source, 0, -1);
			$destination = $this->trailingslashit($destination);

			$files = JFolder::files($source, '.', false, false);
			foreach ($files as $file)
			{
				$result = $this->copy($source . $file, $destination . $file, $overwrite) && $result;
			}

			$folders = JFolder::folders($source, '.', false, false);
			foreach ($folders as $folder)
			{
				$result = $this->copy($source . $folder, $destination . $folder, $overwrite) && $result;
			}

			return $result;
		}

		if ($this->is_file($source))
		{
			if ($this->is_file($destination) && !$overwrite)
			{
				return false;
			}

			return JFile::copy($source, $destination);
		}
		else
		{
			if ($this->is_dir($destination) && !$overwrite)
			{
				return false;
			}

			try
			{
				return JFolder::copy($source, $destination, '', $overwrite);
			}
			catch (Exception $e)
			{
                AutoUpdater_Log::error('Failed to copy directory. ' . $e->getMessage() . ". Source: $source Destination: $destination");
			}

			return false;
		}
	}

	/**
	 * TODO Consider this - using methods from J! we loose functionalities connected with few arguments.
	 *
	 * @param string $source
	 * @param string $destination
	 * @param bool   $overwrite
	 *
	 * @return bool
	 */
	public function move($source, $destination, $overwrite = false)
	{
		if (substr($source, -1) == '*')
		{
			$result      = true;
			$source      = substr($source, 0, -1);
			$destination = $this->trailingslashit($destination);

			$files = JFolder::files($source, '.', false, false);
			foreach ($files as $file)
			{
				$result = $this->move($source . $file, $destination . $file, $overwrite) && $result;
			}

			$folders = JFolder::folders($source, '.', false, false);
			foreach ($folders as $folder)
			{
				$result = $this->move($source . $folder, $destination . $folder, $overwrite) && $result;
			}

			return $result;
		}

		if ($this->is_file($source))
		{
			if ($this->is_file($destination))
			{
				if (!$overwrite)
				{
					return false;
				}

				$result = JFile::copy($source, $destination);
				JFile::delete($source);

				return $result;
			}

			return JFile::move($source, $destination);
		}
		else
		{
			if ($this->is_dir($destination))
			{
				if (!$overwrite)
				{
					return false;
				}

				try
				{
					$result = JFolder::copy($source, $destination, '', $overwrite);
					JFolder::delete($source);
				}
				catch (Exception $e)
				{
                    AutoUpdater_Log::error('Failed to move directory. ' . $e->getMessage() . ". Source: $source Destination: $destination");
					$result = false;
				}

				return $result;
			}

			return JFolder::move($source, $destination);
		}
	}

	/**
	 * TODO Consider this - using methods from J! we loose functionalities connected with few arguments.
	 *
	 * @param string $file
	 * @param bool   $recursive
	 * @param bool   $type
	 *
	 * @return bool
	 */
	public function delete($file, $recursive = false, $type = false)
	{
		if ($this->is_file($file))
		{
			return JFile::delete($file);
		}
		else
		{
			return JFolder::delete($file);
		}
	}

	/**
	 * @param string $file
	 *
	 * @return bool
	 */
	public function is_file($file)
	{
		return JFile::exists($file);
	}

	/**
	 * @param string $path
	 *
	 * @return bool
	 */
	public function is_dir($path)
	{
		return JFolder::exists($path);
	}

	/**
	 * @param string $path
	 * @param bool   $chmod
	 * @param bool   $chown
	 * @param bool   $chgrp
	 *
	 * @return bool
	 */
	public function mkdir($path, $chmod = false, $chown = false, $chgrp = false)
	{
		if (!$chmod)
			$chmod = $this->FS_CHMOD_DIR;

		return JFolder::create($path, $chmod);
	}

	/**
	 * Download a file (based on /libraries/cms/installer/helper.php).
	 *
	 * @param string $url
	 * @param null   $destination
	 *
	 * @return bool|null|string
	 * @throws Exception
	 */
	public function download($url, $destination = null)
	{
		// Capture PHP errors
		$track_errors = ini_get('track_errors');
		ini_set('track_errors', true);
		// Set user agent
		/** @var \Joomla\CMS\Version|JVersion $version */
		$version = new JVersion;
		ini_set('user_agent', $version->getUserAgent('Installer'));

		// Load installer plugins, and allow url and headers modification
		$headers = array();

		jimport('joomla.plugin.helper');
		/** @see \Joomla\CMS\Plugin\PluginHelper::importPlugin */
		JPluginHelper::importPlugin('installer');

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

		$dispatcher->trigger('onInstallerBeforePackageDownload', array(&$url, &$headers));

		$response = AutoUpdater_Request::get($url, null, $headers, 600);

		if (in_array($response->code, array(301, 302, 303)) && !empty($response->headers['Location']))
		{
			return $this->download($response->headers['Location'], $destination);
		}
		elseif (empty($response->body) || $response->code !== 200)
		{
			throw new AutoUpdater_Exception_Response('Failed to download file from URL: ' . $url . ' with response code: ' . $response->code);
		}

		if (empty($destination))
		{
			// Parse the Content-Disposition header to get the file name
			if (!empty($response->headers['Content-Disposition'])
				&& preg_match("/\s*filename\s?=\s?(.*)/", $response->headers['Content-Disposition'], $parts))
			{
				$destination = trim(rtrim($parts[1], ";"), '"');
			}

			// Set the target path if not given
			/** @see \Joomla\CMS\Installer\InstallerHelper::getFilenameFromUrl */
			$destination = $this->getTempPath() . (empty($destination) ? JInstallerHelper::getFilenameFromUrl($url) : basename($destination));

			// Remove query from filename
			$parsed_url = parse_url($destination);
			if (!empty($parsed_url['path']))
			{
				$destination = $parsed_url['path'];
			}
		}

		$destination_dir = dirname($destination);

		// Check if destination directory exists
		if (!$this->is_dir($destination_dir))
		{
			$this->mkdir($destination_dir);
		}
		// Make the destination writable
		elseif (!$this->is_writable($destination_dir))
		{
			$this->chmod($destination_dir, 0755);
		}

		// Delete destination file if it exists
		if ($this->is_file($destination) && !$this->delete($destination))
		{
			if (!$this->is_writable($destination))
			{
				$this->chmod($destination, 0644);
			}
		}

		// Write buffer to file
		if ($this->put_contents($destination, $response->body) !== true)
		{
			throw new AutoUpdater_Exception_Response('Failed to save downloaded file to: ' . $destination);
		}

		// Restore error tracking to what it was before
		ini_set('track_errors', $track_errors);
		// Bump the max execution time because not using built in php zip libs are slow
		@set_time_limit(ini_get('max_execution_time'));

		return JPath::clean($destination);
	}

	/**
	 * Unpacks a file (based on /libraries/cms/installer/helper.php).
	 *
	 * @param string $file
	 * @param null   $destination
	 *
	 * @return string
     * @throws AutoUpdater_Exception_Response
	 */
	public function unpack($file, $destination = null)
	{
		if (version_compare(JVERSION, '3.0', '>='))
		{
			jimport('joomla.archive.archive');
		}
		else
		{
			jimport('joomla.filesystem.archive');
		}

		// Clean the paths to use for archive extraction
		$destination = JPath::clean(empty($destination)
			? dirname($file) . '/' . uniqid('install_') . '/'
			: $this->trailingslashit($destination)
		);
		$file        = JPath::clean($file);

		// Do the unpacking of the archive.
		if (JArchive::extract($file, $destination) !== true)
		{
			throw new AutoUpdater_Exception_Response('JArchive failed to extract file: ' . $file);
		}

		return $destination;
	}

	/**
	 * @return mixed
	 */
	public function getTempPath()
	{
		/** @see \Joomla\CMS\Factory::getConfig */
		return rtrim(JFactory::getConfig()->get('tmp_path'), '/\\') . '/';
	}
}