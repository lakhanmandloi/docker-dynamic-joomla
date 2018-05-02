<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_PostCmsCachePurge extends AutoUpdater_Task_PostCmsCachePurge
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		// Groups of cache to clean.
		$groups = (array) $this->input('groups', array());

		/** @var \Joomla\CMS\Cache\Cache|JCache $cache */
		$cache = JFactory::getCache();

		if (!$groups)
		{
			$cache->clean(null, 'notgroup');
		}
		else
		{
			foreach ($groups as $group)
			{
				$cache->clean($group, 'group');
			}
		}

		// Additionaly clean page group to see changes on pages.
		$cache->clean('page', 'group');

		return array(
			'success' => true,
		);
	}
}