<?php
/**
 * @package     autoupdater
 * @version     1.16
 *
 * @license     GNU General Public Licence http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die;

if (!class_exists('plgSystemAutoupdaterInstallerScript'))
{
    class plgSystemAutoupdaterInstallerScript
    {
        protected $version = '';
        protected $previous_version = '';
        protected $action = 'install';
        protected $slug = 'autoupdater';

        /**
         * @param JAdapterInstance|\Joomla\CMS\Installer\InstallerAdapter $adapter
         *
         * @return bool
         */
        protected function init($adapter)
        {
            if (!defined('AUTOUPDATER_J_PLUGIN_INSTALLER'))
            {
                define('AUTOUPDATER_J_PLUGIN_INSTALLER', true);
            }

            $parent = $adapter->getParent();
            $extension_root = rtrim($parent->getPath('extension_root'), '/\\') . '/';

            require_once $extension_root . 'defines.php';
            $this->loadLanguage();

            if (!defined('AUTOUPDATER_LIB'))
            {
                return false;
            }

            if (!class_exists('AutoUpdater_Installer'))
            {
                require_once $extension_root . 'lib/src/Init.php';
                require_once AUTOUPDATER_LIB_PATH . 'Installer.php';
            }

            return true;
        }

        protected function out($message, $type = 'message')
        {
            if (php_sapi_name() === 'cli')
            {
                fwrite(STDOUT, '[' . strtoupper($type) . '] ' . $message . PHP_EOL);
            }
            else
            {
                /** @see \Joomla\CMS\Application\CMSApplication::enqueueMessage */
                JFactory::getApplication()
                    ->enqueueMessage($message, $type);
            }
        }

        /**
         * Called before any type of action.
         *
         * @param string $action The type of change (install, uninstall, update or discover_install)
         * @param JAdapterInstance|\Joomla\CMS\Installer\InstallerAdapter $adapter The object responsible for running this script
         *
         * @return bool
         */
        public function preflight($action, $adapter)
        {
            // TODO remove after migration of all clients
            $old_slug = 'perfect';

            if (version_compare(PHP_VERSION, '5.3.0', '<'))
            {
                $this->out('Auto-Updater requires at least PHP 5.3 version', 'error');

                return false;
            }

            if (version_compare(JVERSION, '2.5.5', '<'))
            {
                $this->out('Auto-Updater requires at least Joomla 2.5.5 version', 'error');

                return false;
            }

            $parent = $adapter->getParent();
            $path_source = rtrim($parent->getPath('source'), '/\\') . '/';

            $manifest = @simplexml_load_file($path_source . '/' . $this->slug . '.xml');
            $this->version = (string)$manifest->version;
            unset($manifest);

            $path_plugin = JPATH_PLUGINS . '/system/' . $this->slug . '/' . $this->slug . '.xml';
            if (file_exists($path_plugin))
            {
                $this->action = 'update';
                $manifest = @simplexml_load_file($path_plugin);
                $this->previous_version = (string)$manifest->version;
                unset($manifest);
                return true;
            }

            // TODO remove after migration of all clients
            $old_slug .= 'dashboard';
            $path_plugin = JPATH_PLUGINS . '/system/' . $old_slug . '/' . $old_slug . '.xml';
            if ($this->slug === 'autoupdater' && file_exists($path_plugin))
            {
                $this->action = 'update';
                $manifest = @simplexml_load_file($path_plugin);
                $this->previous_version = (string)$manifest->version;
                if (version_compare($this->previous_version, '1.16.0', '='))
                {
                    $this->previous_version = '1.13.1';
                }
                unset($manifest);
                return true;
            }

            return true;
        }

        /**
         * Called on uninstallation
         *
         * @param   JAdapterInstance|\Joomla\CMS\Installer\InstallerAdapter $adapter The object responsible for running this script
         *
         * @return  boolean  True on success
         */
        public function uninstall($adapter)
        {
            $this->action = 'uninstall';
            $this->init($adapter);

            AutoUpdater_Installer::getInstance()
                ->setOption('slug', $this->slug)
                ->uninstall();

            $this->cacheClean();

            AutoUpdater_Log::debug('Auto-Updater has been uninstalled.');

            return true;
        }

        /**
         * Called after any type of action
         *
         * @param   string $action The type of change (install, uninstall, update or discover_install)
         * @param   JAdapterInstance|\Joomla\CMS\Installer\InstallerAdapter $adapter The object responsible for running this script
         *
         * @return  boolean  True on success
         */
        public function postflight($action, $adapter)
        {
            if ($action == 'uninstall')
            {
                return true;
            }

            // Do not run the installer if it has failed to initialize.
            // It will be updated by the task POST child/updater/after or the plugin self-update.
            if ($this->init($adapter))
            {
                $installer = AutoUpdater_Installer::getInstance()
                    ->setOption('slug', $this->slug);

                // Migrate the old plugin to the new one
                if ($this->previous_version && version_compare($this->previous_version, '1.16', '<'))
                {
                    AutoUpdater_Log::debug('Old plugin has been found. Switch from plugin installation to update.');

                    // Make the plugin to think that the older version is already installed
                    AutoUpdater_Config::set('version', $this->previous_version);

                    if (!empty($_REQUEST['site_id']))
                    {
                        $installer->setOption('site_id', (int)$_REQUEST['site_id']);
                    }

                    $installer
                        ->setOption('version', $this->version)
                        ->update();
                }
                elseif ($action == 'install' || $action == 'discover_install')
                {
                    $installer->install();
                }
            }

            $this->removeOldFiles();
            $this->cacheClean();

            // Do not display success message when running in CLI
            if (php_sapi_name() === 'cli')
            {
                return true;
            }

            // Installation in back-end
            $this->displayConnectMessage();

            return true;
        }

        protected function displayConnectMessage()
        {
            // nothing to be displayed in this variant of the plugin
        }

        protected function cacheClean()
        {
            /* @see \Joomla\CMS\Factory::getConfig */
            $config = JFactory::getConfig();

            // Check if cache is enabled.
            if ($config->get('caching'))
            {
                /** @var \Joomla\CMS\Cache\Cache|JCache $cache */
                $cache = JFactory::getCache();
                $cache->clean('_system', 'group');
            }
        }

        protected function loadLanguage()
        {
            /* @see \Joomla\CMS\Language\Language::load */
            return JFactory::getLanguage()
                ->load('plg_system_' . $this->slug, JPATH_ADMINISTRATOR, null, true, true);
        }

        protected function removeOldFiles()
        {
            // nothing to remove in this variant of the plugin
        }
    }
}