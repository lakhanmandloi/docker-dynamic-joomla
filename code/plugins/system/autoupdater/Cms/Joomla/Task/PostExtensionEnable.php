<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Task_PostExtensionEnable extends AutoUpdater_Task_PostExtensionEnable
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		$extensions = (array) $this->input('extensions', array());
		$slug       = (string) $this->input('slug');
		$type       = (string) $this->input('type');

		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);

		// Fields to update.
		$fields = array(
			$db->qn('enabled') . ' = 1',
		);

		$glue       = 'AND';
		$conditions = array();

		// Set conditions for which records should be updated.
		if (!empty($extensions))
		{
			// Enable extensions basing on argument.
			if (isset($extensions[0]) && $extensions[0] == 'all')
			{
				// Enable all extensions.
				$conditions = array('1');
			}
			else
			{
				// Enable only extensions from argument.
				foreach ($extensions as $ext)
				{
					$extension = AutoUpdater_Cms_Joomla_Helper_Joomla::getExtension($ext['type'],
						$ext['slug']);

					$where = array();
					if ($extension->folder !== null)
					{
						$where[] = $db->qn('folder') . ' = ' . $db->q($extension->folder);
					}
					if ($extension->client_id !== null)
					{
						$where[] = $db->qn('client_id') . ' = ' . $db->q($extension->client_id);
					}

					$where[] = $db->qn('type') . ' = ' . $db->q($extension->type);
					$where[] = $db->qn('element') . ' = ' . $db->q($extension->element);

					$conditions[] = '(' . implode(' AND ', $where) . ')';
				}
				$glue = 'OR';
			}
		}
		elseif (!empty($slug) && !empty($type))
		{
			$extension = AutoUpdater_Cms_Joomla_Helper_Joomla::getExtension($type, $slug);

			$conditions[] = $db->qn('element') . ' = ' . $db->q($extension->element);
			$conditions[] = $db->qn('type') . ' = ' . $db->q($extension->type);

			if ($extension->folder !== null)
			{
				$conditions[] = $db->qn('folder') . ' = ' . $db->q($extension->folder);
			}
			if ($extension->client_id !== null)
			{
				$conditions[] = $db->qn('client_id') . ' = ' . $db->q($extension->client_id);
			}
		}
		else
		{
			// Enable all non core extensions.
			foreach (AutoUpdater_Cms_Joomla_Helper_Joomla::getCoreExtensions() as $type => $slugs)
			{
				foreach ($slugs as $slug)
				{
					$extension = AutoUpdater_Cms_Joomla_Helper_Joomla::getExtension($type, $slug);

					$where = array();
					if ($extension->folder !== null)
					{
						$where[] = $db->qn('folder') . ' != ' . $db->q($extension->folder);
					}
					if ($extension->client_id !== null)
					{
						$where[] = $db->qn('client_id') . ' != ' . $db->q($extension->client_id);
					}

					$where[] = $db->qn('type') . ' != ' . $db->q($extension->type);
					$where[] = $db->qn('element') . ' != ' . $db->q($extension->element);

					$conditions[] = '(' . implode(' OR ', $where) . ')';
				}
			}

			// Also do not enable plugin autoupdater (it is already enabled)
			$conditions[] = '(' . $db->qn('element') . ' != ' . $db->q(AUTOUPDATER_J_PLUGIN_SLUG) .
				' OR ' . $db->qn('folder') . ' != ' . $db->q('system') .
				' OR ' . $db->qn('client_id') . ' != 0)';
		}

		$query->update($db->qn('#__extensions'))
			->set($fields)
			->where($conditions, $glue);

		return array(
			'success' => $db->setQuery($query)->execute(),
		);
	}
}