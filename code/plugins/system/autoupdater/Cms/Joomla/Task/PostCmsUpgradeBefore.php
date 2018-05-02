<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_PostCmsUpgradeBefore extends AutoUpdater_Task_PostCmsUpgradeBefore
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		return AutoUpdater_Task::getInstance('PostCmsUpdateBefore', $this->payload)
			->doTask();
	}
}