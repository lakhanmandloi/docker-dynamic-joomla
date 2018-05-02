<?php
/**
 * @package     autoupdater
 * @version     1.16
 *
 * @license     GNU General Public Licence http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

if (!class_exists('plgSystemAutoupdater'))
{
    class PlgSystemAutoupdater extends JPlugin
    {
        /**
         * @var string
         */
        protected $slug = 'autoupdater';

        /**
         * @var Joomla\CMS\Table\Extension|JTableExtension
         */
        protected $extension;

        public function onAfterInitialise()
        {
            if (version_compare(PHP_VERSION, '5.3', '<'))
            {
                return;
            }

            /** @var \Joomla\CMS\Application\CMSApplication|JApplicationCms $app */
            $app = JFactory::getApplication();
            if (version_compare(JVERSION, '3.7', '>='))
            {
                $is_admin = $app->isClient('administrator');
            }
            else
            {
                $is_admin = $app->isAdmin();
            }

            // Do not load plugin if Joomla is installing, updating or removing some extension
            if ($is_admin && $app->input->get('option') == 'com_installer' &&
                in_array($app->input->get('task'), array('install.install', 'update.update', 'manage.remove'))
            )
            {
                return;
            }

            require_once dirname(__FILE__) . '/defines.php';
            require_once dirname(__FILE__) . '/lib/src/Init.php';

            $api = AutoUpdater_Api::getInstance();

            require_once AUTOUPDATER_LIB_PATH . 'Installer.php';
            AutoUpdater_Installer::getInstance()->selfUpdate();

            $api->handle();
        }

        protected function loadExtension()
        {
            if (!empty($this->extension))
            {
                return;
            }

            $this->extension = JTable::getInstance('extension');
            $this->extension->load(array(
                'element' => $this->slug,
                'type' => 'plugin',
                'folder' => 'system'
            ));
        }

        /**
         * @param string $context
         * @param array $pks
         * @param int $value
         *
         * @return bool
         */
        public function onContentChangeState($context, $pks, $value)
        {
            if ($context != 'com_plugins.plugin' || $value == 1 || !$this->params->get('protect_child'))
            {
                return true;
            }

            $this->loadExtension();
            $id = $this->extension->get('extension_id');

            if ($id && in_array($id, $pks))
            {
                $this->extension->publish(array($id), 1, JFactory::getUser()->get('id'));
            }

            return true;
        }

        /**
         * @param string $context
         * @param JTable|Joomla\CMS\Table\Table $table
         * @param bool $isNew
         * @param null|array $data Since J!3.7
         *
         * @return bool
         */
        public function onExtensionBeforeSave($context, $table, $isNew, $data = null)
        {
            if ($context != 'com_plugins.plugin' ||
                ($table->get('enabled') === null && $table->get('access') === null) ||
                ($table->get('enabled') == 1 && $table->get('access') == 1) ||
                !$this->params->get('protect_child'))
            {
                return true;
            }

            $this->loadExtension();
            $id = $this->extension->get('extension_id');

            if ($id && $table->get('extension_id') == $id)
            {
                $table->enabled = 1;
                $table->access = '1';
            }

            return true;
        }
    }
}