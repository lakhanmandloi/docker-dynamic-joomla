<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_PostFileDownload extends AutoUpdater_Task_PostFileDownload
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		$url  = $this->input('file_url');
		$slug = $this->input('slug');
		$type = $this->input('type');

		if (!empty($url) && !empty($slug) && !empty($type))
		{
			$download_prepared = $this->input('download_prepared');
			if (empty($download_prepared))
			{
				// Add an extra query to the download URL if it was not prepared already by Auto-Updater API
				$extension = AutoUpdater_Cms_Joomla_Helper_Joomla::getExtension($type, $slug);
				$this->addExtraQuery($url, $extension->type, $extension->element, $extension->folder, $extension->client_id);
			}
			$this->setInput('file_url', $url);
		}

		return parent::doTask();
	}

	/**
	 * @param string      $url
	 * @param string      $type
	 * @param string      $element
	 * @param string|null $folder
	 * @param int|null    $client_id
	 *
	 * @return string
	 */
	private function addExtraQuery(&$url, $type, $element, $folder = null, $client_id = null)
	{
		if (!($element == 'joomla' && $type == 'file') && version_compare(JVERSION, '3.2.2', '>='))
		{
			//Check for extra query
			try
			{
				/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true)
					->select($db->qn('us.extra_query'))
					->from($db->qn('#__update_sites', 'us'))
					->innerJoin($db->qn('#__update_sites_extensions', 'us1')
						. ' ON ' . $db->qn('us1.update_site_id') . ' = ' . $db->qn('us.update_site_id'))
					->innerJoin($db->qn('#__extensions', 'ex')
						. ' ON ' . $db->qn('us1.extension_id') . ' = ' . $db->qn('ex.extension_id'))
					->where(array(
						$db->qn('ex.type') . ' = ' . $db->q($type),
						$db->qn('ex.element') . ' = ' . $db->q($element)
					));
				if ($folder !== null)
				{
					$query->where($db->qn('ex.folder') . ' = ' . $db->q($folder));
				}
				if ($client_id !== null)
				{
					$query->where($db->qn('ex.client_id') . ' = ' . $db->q((int) $client_id, false));
				}
				$extra_query = $db->setQuery($query)->loadResult();

				$url .= !empty($extra_query) ? '&' . $extra_query : ''; //All download links come with ?perfect=dashboard
			}
			catch (Exception $e)
			{
				//It looks like the upgrade to 3.2.2 was broken.
				//Ignoring exceptions is not a good idea, but what can you do.
			}
		}
	}
}