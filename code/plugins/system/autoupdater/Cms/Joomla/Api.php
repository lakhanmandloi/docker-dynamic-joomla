<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Api extends AutoUpdater_Api
{
	protected $cms_initialized = false;

	protected function init()
	{
		parent::init();

		if ($this->initialized)
		{
			$this->initCms();
		}
	}

	public function initCms()
	{
		if ($this->cms_initialized)
		{
			return;
		}

		require_once AUTOUPDATER_J_PLUGIN_HELPER_PATH . 'Joomla.php';

		$this->cms_initialized = true;

		if ($this->isInitialized() || defined('AUTOUPDATER_J_PLUGIN_INSTALLER'))
		{
			// There is a lot of warnings during the request to J!3.0
			if (version_compare(JVERSION, '3.1.0', '<') &&
				version_compare(JVERSION, '3.0.0', '>='))
			{
				call_user_func('error' . '_report' . 'ing', 0);
			}

			// Set default language.
			/** @var \Joomla\CMS\Language\Language|JLanguage $lang */
			$lang = JFactory::getLanguage();
			$lang->setDefault('en-GB');
			$lang->setLanguage('en-GB');
			$lang->load();

            AutoUpdater_Db::getInstance()->setDefaultDbo();

			$this->overrideJoomlaLibrary();
		}
	}

	protected function overrideJoomlaLibrary()
	{
		if (defined('AUTOUPDATER_JLIB_PATH'))
		{
			return;
		}

		if (version_compare(JVERSION, '3.8.0', '>='))
		{
			//TODO there are totally new libraries
			return;
		}

		// override Joomla Libraries
		define('AUTOUPDATER_JLIB_PATH', AUTOUPDATER_J_PLUGIN_PATH . 'Cms/Joomla/Library/');

		JLoader::import('joomla.http.transport', AUTOUPDATER_JLIB_PATH);
		JLoader::register('JHttpTransport', AUTOUPDATER_JLIB_PATH . 'joomla/http/transport.php', true);

		JLoader::import('joomla.http.transport.curl', AUTOUPDATER_JLIB_PATH);
		JLoader::register('JHttpTransportCurl', AUTOUPDATER_JLIB_PATH . 'joomla/http/transport/curl.php', true);

		JLoader::import('joomla.http.transport.socket', AUTOUPDATER_JLIB_PATH);
		JLoader::register('JHttpTransportSocket', AUTOUPDATER_JLIB_PATH . 'joomla/http/transport/socket.php', true);

		JLoader::import('joomla.http.transport.stream', AUTOUPDATER_JLIB_PATH);
		JLoader::register('JHttpTransportStream', AUTOUPDATER_JLIB_PATH . 'joomla/http/transport/stream.php', true);

		if (version_compare(JVERSION, '2.5.15', '<'))
		{
			JLoader::import('joomla.http.factory', AUTOUPDATER_JLIB_PATH);
			JLoader::register('JHttpFactory', AUTOUPDATER_JLIB_PATH . 'joomla/http/factory.php', false);
		}

		if (version_compare(JVERSION, '3.0', '<'))
		{
			JLoader::import('joomla.log.logger.callback', AUTOUPDATER_JLIB_PATH);
			JLoader::register('JLogLoggerCallback', AUTOUPDATER_JLIB_PATH . 'joomla/log/logger/callback.php', true);
		}
	}
}