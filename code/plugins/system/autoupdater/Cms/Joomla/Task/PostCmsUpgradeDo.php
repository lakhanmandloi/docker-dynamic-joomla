<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_PostCmsUpgradeDo extends AutoUpdater_Task_PostCmsUpgradeDo
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		$this->setInput('type', 'cms');
		$this->setInput('slug', 'joomla');

		return AutoUpdater_Task::getInstance('PostCmsUpdateDo', $this->payload)
			->doTask();
	}
}