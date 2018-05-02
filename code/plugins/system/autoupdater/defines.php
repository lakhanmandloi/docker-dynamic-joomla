<?php
defined('_JEXEC') or die;

if (!defined('AUTOUPDATER_LIB'))
{
	$manifest = simplexml_load_file(dirname(__FILE__) . '/' . basename(dirname(__FILE__)) . '.xml');

	define('AUTOUPDATER_STAGE', 'app');

	define('AUTOUPDATER_LIB', true);
	define('AUTOUPDATER_CMS', 'joomla');
	define('AUTOUPDATER_SITE_PATH', rtrim(JPATH_ROOT, '/\\') . '/');
	if (!defined('AUTOUPDATER_VERSION'))
	{
		define('AUTOUPDATER_VERSION', (string) $manifest->version);
	}

    define('AUTOUPDATER_J_PLUGIN_SLUG', (string) $manifest->files->filename['plugin']);
    define('AUTOUPDATER_J_PLUGIN_PATH', JPATH_PLUGINS . '/system/' . AUTOUPDATER_J_PLUGIN_SLUG . '/');
	define('AUTOUPDATER_J_PLUGIN_HELPER_PATH', AUTOUPDATER_J_PLUGIN_PATH . 'Cms/Joomla/Helper/');

    if (AUTOUPDATER_J_PLUGIN_SLUG !== 'autoupdater' && php_sapi_name() === 'cli' &&
        file_exists(AUTOUPDATER_J_PLUGIN_PATH . 'legacy.php'))
    {
        @include_once AUTOUPDATER_J_PLUGIN_PATH . 'legacy.php';
    }
}