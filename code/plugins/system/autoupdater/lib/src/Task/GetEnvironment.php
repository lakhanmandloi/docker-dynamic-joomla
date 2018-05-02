<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Task_GetEnvironment extends AutoUpdater_Task_Base
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		$data = array(
			'success'          => true,
			'cms_type'         => AUTOUPDATER_CMS,
			'cms_version'      => null,
			'php_version'      => PHP_VERSION,
			'os'               => php_uname('s'),
			'server'           => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '',
			'database_name'    => null,
			'database_version' => null,
		);

		return $data;
	}
}