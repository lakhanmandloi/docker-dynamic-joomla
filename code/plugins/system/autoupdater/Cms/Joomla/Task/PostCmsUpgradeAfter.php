<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_PostCmsUpgradeAfter extends AutoUpdater_Task_PostCmsUpgradeAfter
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		return AutoUpdater_Task::getInstance('PostCmsUpdateAfter', $this->payload)
			->doTask();
	}
}