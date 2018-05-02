<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_PostCmsDisable extends AutoUpdater_Task_PostCmsDisable
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		$response = array();

		if (version_compare(JVERSION, '3.2', '>='))
		{
			@include_once JPATH_ROOT . '/components/com_config/model/cms.php';
			@include_once JPATH_ROOT . '/components/com_config/model/form.php';

			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_config/model/');
		}
		else
		{
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_config/models/');
		}

		// check sh404sef extension and check if is
		/** @see \Joomla\CMS\Component\ComponentHelper::getParams */
		$sh404sef = JComponentHelper::getParams('com_sh404sef');
		if ($sh404sef->get('Enabled', false))
		{
			$sh404sef->set('Enabled', 0);
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

			$response['sh404sef'] = true;
		}

		/** @var ConfigModelApplication $config_model */
		$config_model = JModelLegacy::getInstance('Application', 'ConfigModel');

		$data            = $config_model->getData();
		$data['offline'] = 1;

		$result = $config_model->save($data);

		// Clear "page" cache, so offline site will display.
		$this->setInput('groups', array('page'));
        AutoUpdater_Task::getInstance('PostCmsCachePurge', $this->payload)
			->doTask();

		$file_manager = AutoUpdater_Filemanager::getInstance();
		$file_manager->clearPhpCache();

		$response['success']    = ($result === true);
		$response['is_offline'] = $result;

		return $response;
	}
}