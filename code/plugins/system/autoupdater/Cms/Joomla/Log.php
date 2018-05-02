<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Log extends AutoUpdater_Log
{
	public function __construct()
	{
		/** @see \Joomla\CMS\Log\Log::addLogger */
		JLog::addLogger(array(
			'logger'    => 'formattedtext',
			'text_file' => 'autoupdater_' . date('Y-m-d') . '.logs.php',
		), AutoUpdater_Config::get('debug') ? JLog::ALL : JLog::ERROR,
			array('autoupdater')
		);
	}

	/**
	 * @return string
	 */
	public function getLogsPath()
	{
		/** @see \Joomla\CMS\Factory::getConfig */
		$path = rtrim(JFactory::getConfig()->get('log_path'), '/\\');
		if (!empty($path))
		{
			return $path . '/';
		}

		return parent::getLogsPath();
	}

	/**
	 * @param string $level
	 * @param string $message
	 */
	public function log($level = 'debug', $message)
	{
		switch ($level)
		{
			case 'info':
				$level = JLog::INFO;
				break;
			case 'error':
				$level = JLog::ERROR;
				break;
			default:
				$level = JLog::DEBUG;
		}
		/** @see \Joomla\CMS\Log\Log::add */
		JLog::add($message, $level, 'autoupdater');
	}
}