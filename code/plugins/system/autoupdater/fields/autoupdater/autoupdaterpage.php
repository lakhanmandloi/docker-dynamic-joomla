<?php
/**
 * @package     autoupdater
 * @version     1.16
 *
 * @license     GNU General Public Licence http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

class JFormFieldAutoupdaterpage extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.6
	 */
	protected $type = 'Autoupdaterpage';

	/**
	 * Do not show label
	 *
	 * @var    boolean
	 * @since  11.1
	 */
	protected $hidden = false;

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 * @since    1.6
	 */
	protected function getInput()
	{
		if (!class_exists('AutoUpdater_Config'))
		{
			JFactory::getApplication()
				->enqueueMessage(JText::_('PLG_AUTOUPDATER_ENABLE_PLUGIN_ERROR'), 'error');
			return '';
		}

		if ($whitelabel_child_page = AutoUpdater_Config::get('whitelabel_child_page'))
		{
			return $whitelabel_child_page;
		}

		return '';
	}
}