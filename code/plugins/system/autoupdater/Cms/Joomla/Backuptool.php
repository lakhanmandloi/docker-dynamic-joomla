<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Cms_Joomla_Backuptool extends AutoUpdater_Backuptool
{
	/**
	 * @param bool $htaccess_disable
	 */
	protected function setWAFExceptions($htaccess_disable = false)
	{
		$filemanager    = AutoUpdater_Filemanager::getInstance();
		$backuptool_dir = $this->getDir();

		// Add backup directory to admintools fullaccessdirs.
		// If admin tools are installed.
		if ($filemanager->is_dir(JPATH_ADMINISTRATOR . '/components/com_admintools'))
		{
			/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
			$db = JFactory::getDbo();

			$query = $db->getQuery(true)
				->select($db->qn('value'))
				->from($db->qn('#__admintools_storage'))
				->where($db->qn('key') . ' = ' . $db->q('cparams'));

			try
			{
				$res = $db->setQuery($query)->loadResult();
			}
			catch (Exception $e)
			{
				// Most probably table #__admintools_storage doesn't exist, so admintools are not installed now (but folder exist)
				$res = null;
			}

			$config   = AutoUpdater_Cms_Joomla_Helper_Joomla::getRegistry($res);
			$htconfig = $config->get('htconfig');

			if (!empty($htconfig))
			{
				$htconfig = json_decode(call_user_func('ba' . 'se' . '64' . '_decode', $htconfig), true);
				if (empty($htconfig['fullaccessdirs']))
				{
					$htconfig['fullaccessdirs'] = array();
				}

				$found = false;
				foreach ($htconfig['fullaccessdirs'] as $path)
				{
					if (strpos($path, $backuptool_dir) !== false)
					{
						$found = true;
						break;
					}
				}

				if (!$found)
				{
					$htconfig['fullaccessdirs'][] = $backuptool_dir;
				}

				$htconfig = call_user_func('ba' . 'se' . '64' . '_encode', json_encode($htconfig));
				$config->set('htconfig', $htconfig);

				$query = $db->getQuery(true)
					->update($db->qn('#__admintools_storage'))
					->set($db->qn('value') . ' = ' . $db->q($config->toString()))
					->where($db->qn('key') . ' = ' . $db->q('cparams'));

				try
				{
					$db->setQuery($query)->execute();
				}
				catch (Exception $e)
				{

				}
			}
		}

		// Check if backup folder has full access rule in htaccess - if not add it.
		$htaccess_file = JPATH_ROOT . '/.htaccess';
		if ($filemanager->is_file($htaccess_file))
		{
			$htaccess_content                   = $filemanager->get_contents($htaccess_file);
			$backuptool_accessdir_line_position = strpos($htaccess_content, $backuptool_dir);

			if ($backuptool_accessdir_line_position === false)
			{
				$backuptool_accessdir_line = 'RewriteRule ^' . $backuptool_dir . '/ - [L]';

				$fullaccessdirs_block_end = strpos($htaccess_content,
					'##### Advanced server protection rules exceptions -- END');

				if ($fullaccessdirs_block_end !== false)
				{
					$new_htaccess_content = substr_replace($htaccess_content, $backuptool_accessdir_line . PHP_EOL,
						$fullaccessdirs_block_end, 0);
					$filemanager->put_contents($htaccess_file, $new_htaccess_content);
				}
				elseif (preg_match('/RewriteEngine\s+On/i', $htaccess_content, $match))
				{
					$new_htaccess_content = str_replace($match[0], $match[0] . PHP_EOL . $backuptool_accessdir_line,
						$htaccess_content);
					$filemanager->put_contents($htaccess_file, $new_htaccess_content);
				}
			}
		}

		parent::setWAFExceptions($htaccess_disable);
	}

	/**
	 * @param null $id
	 * @param null $filename
	 *
	 * @return string
	 */
	protected function getBackupSql($id = null, $filename = null)
	{
		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$prefix = $this->getDbPrefix();
		$db     = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select(array(
				$db->qn('id'),
				$db->qn('archivename'),
				$db->qn('multipart'),
				$db->qn('total_size'),
			))
			->from($db->qn($prefix . 'ak_stats'))
			->order($db->qn('id') . ' DESC');

		if ($id)
		{
			$query->where($db->qn('id') . ' = ' . $db->q((int) $id, false));
		}
		elseif ($filename)
		{
			$query->where($db->qn('archivename') . ' = ' . $db->q($filename));
		}
		else
		{
			$query->where(array(
				$db->qn('archivename') . ' != ' . $db->q('', false),
				$db->qn('archivename') . ' IS NOT NULL',
			));
		}

		if (method_exists($query, 'setLimit'))
		{
			$query->setLimit(10);
		}

		return $query->__toString();
	}
}