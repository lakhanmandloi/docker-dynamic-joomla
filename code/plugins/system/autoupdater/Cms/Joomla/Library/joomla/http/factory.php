<?php
/**
 * @package     Joomla.Platform
 * @subpackage  HTTP
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

if (!class_exists('JHttpFactory'))
{
	JLoader::import('joomla.http.factory', AUTOUPDATER_JLIB_PATH);
	JLoader::register('JHttpFactory', AUTOUPDATER_JLIB_PATH . 'joomla/http/factory.php', true);
	JLoader::import('joomla.http.transport.curl', AUTOUPDATER_JLIB_PATH);
	JLoader::register('JHttpTransportCurl', AUTOUPDATER_JLIB_PATH . 'joomla/http/transport/curl.php', true);
	JLoader::import('joomla.http.transport.socket', AUTOUPDATER_JLIB_PATH);
	JLoader::register('JHttpTransportSocket', AUTOUPDATER_JLIB_PATH . 'joomla/http/transport/socket.php', true);
	JLoader::import('joomla.http.transport.stream', AUTOUPDATER_JLIB_PATH);
	JLoader::register('JHttpTransportStream', AUTOUPDATER_JLIB_PATH . 'joomla/http/transport/stream.php', true);

	/**
	 * HTTP factory class.
	 *
	 * @since  12.1
	 */
	class JHttpFactory
	{
		/**
		 * method to receive Http instance.
		 *
		 * @param   JRegistry $options  Client options object.
		 * @param   mixed     $adapters Adapter (string) or queue of adapters (array) to use for communication.
		 *
		 * @return  JHttp      Joomla Http class
		 *
		 * @throws  RuntimeException
		 *
		 * @since   12.1
		 */
		public static function getHttp(JRegistry $options = null, $adapters = null)
		{
			if (empty($options))
			{
				$options = AutoUpdater_Cms_Joomla_Helper_Joomla::getRegistry();
			}

			if (empty($adapters))
			{
				$config = JFactory::getConfig();

				if ($config->get('proxy_enable'))
				{
					$adapters = 'curl';
				}
			}

			if (!$driver = self::getAvailableDriver($options, $adapters))
			{
				throw new RuntimeException('No transport driver available.');
			}

			return new JHttp($options, $driver);
		}

		/**
		 * Finds an available http transport object for communication
		 *
		 * @param   JRegistry $options Option for creating http transport object
		 * @param   mixed     $default Adapter (string) or queue of adapters (array) to use
		 *
		 * @return  JHttpTransport Interface sub-class
		 *
		 * @since   12.1
		 */
		public static function getAvailableDriver(JRegistry $options, $default = null)
		{
			if (is_null($default))
			{
				$availableAdapters = self::getHttpTransports();
			}
			else
			{
				settype($default, 'array');
				$availableAdapters = $default;
			}

			// Check if there is available http transport adapters
			if (!count($availableAdapters))
			{
				return false;
			}

			foreach ($availableAdapters as $adapter)
			{
				$class = 'JHttpTransport' . ucfirst($adapter);

				try
				{
					//PD only construct object and do not call method isSupported() which may not exist
					if (class_exists($class))
					{
						$transport = new $class($options);

						return $transport;
					}
				}
				catch (Exception $e)
				{

				}
			}

			return false;
		}

		/**
		 * Get the http transport handlers
		 *
		 * @return  array  An array of available transport handlers
		 *
		 * @since   12.1
		 */
		public static function getHttpTransports()
		{
			$names    = array();
			$iterator = new DirectoryIterator(JPATH_LIBRARIES . '/joomla/http/transport'); //PD changed location
			foreach ($iterator as $file)
			{
				$fileName = $file->getFilename();

				// Only load for php files.
				// Note: DirectoryIterator::getExtension only available PHP >= 5.3.6
				if ($file->isFile() && substr($fileName, strrpos($fileName, '.') + 1) == 'php')
				{
					$names[] = substr($fileName, 0, strrpos($fileName, '.'));
				}
			}

			// Keep alphabetical order across all environments
			sort($names);

			// If curl is available set it to the first position
			if ($key = array_search('curl', $names))
			{
				unset($names[$key]);
				array_unshift($names, 'curl');
			}

			return $names;
		}
	}

}
