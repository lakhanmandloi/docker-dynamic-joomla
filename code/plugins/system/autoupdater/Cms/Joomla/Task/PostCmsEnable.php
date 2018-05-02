<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_PostCmsEnable extends AutoUpdater_Task_PostCmsEnable
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		if (version_compare(JVERSION, '3.2') != -1)
		{
			@include_once JPATH_ROOT . '/components/com_config/model/cms.php';
			@include_once JPATH_ROOT . '/components/com_config/model/form.php';

			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_config/model/');
		}
		else
		{
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_config/models/');
		}

		/** @var ConfigModelApplication $config_model */
		$config_model = JModelLegacy::getInstance('Application', 'ConfigModel');

		$data            = $config_model->getData();
		$data['offline'] = 0;

		// Check if all necessary data is set.
		$check_data         = AutoUpdater_Cms_Joomla_Helper_Joomla::getRegistry($data);
		$data['ftp_enable'] = $check_data->get('ftp_enable', '0');
		$data['ftp_host']   = $check_data->get('ftp_host', '');
		$data['ftp_port']   = $check_data->get('ftp_port', '');
		$data['ftp_user']   = $check_data->get('ftp_user', '');
		$data['ftp_pass']   = $check_data->get('ftp_pass', '');
		$data['ftp_root']   = $check_data->get('ftp_root', '');

		$result = $config_model->save($data);

		// Clear "page" cache, so offline site won't display.
		$this->setInput('groups', array('page'));
        AutoUpdater_Task::getInstance('PostCmsCachePurge', $this->payload)
			->doTask();

		$file_manager = AutoUpdater_Filemanager::getInstance();
		$file_manager->clearPhpCache();

		if ($this->input('sh404sef', false))
		{
			/** @see \Joomla\CMS\Component\ComponentHelper::getParams */
			$sh404sef = JComponentHelper::getParams('com_sh404sef');
			if ($sh404sef->get('Enabled', false) !== false)
			{
				$sh404sef->set('Enabled', 1);
				/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true)
					->update($db->qn('#__extensions'))
					->set($db->qn('params') . ' = ' . $db->q($sh404sef->toString()))
					->where(array(
						$db->qn('element') . ' = ' . $db->q('com_sh404sef', false),
						$db->qn('type') . ' = ' . $db->q('component', false),
					));
				$db->setQuery($query)
					->execute();
			}
		}

		return array(
			'success'    => ($result === true),
			'is_offline' => !$result,
		);
	}
}