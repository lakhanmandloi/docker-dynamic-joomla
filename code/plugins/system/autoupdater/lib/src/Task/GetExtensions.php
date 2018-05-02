<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Task_GetExtensions extends AutoUpdater_Task_Base
{
	/**
	 * @return array
	 */
	public function doTask()
	{
		return array(
			'success'    => true,
			'extensions' => array(),
		);
	}

	/**
	 * @param $string
	 *
	 * @return string
	 */
	protected function filterHTML($string)
	{
		return utf8_encode(trim(strip_tags(html_entity_decode($string))));
	}
}