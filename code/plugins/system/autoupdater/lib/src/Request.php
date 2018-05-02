<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Request
{
	protected static $instance = null;
	protected static $timeout = 5;

	/**
	 * @return static
	 */
	protected static function getInstance()
	{
		if (!is_null(static::$instance))
		{
			return static::$instance;
		}

		$class_name = AutoUpdater_Loader::loadClass('Request');

		static::$instance = new $class_name();

		return static::$instance;
	}

	/**
	 * @param string     $url
	 * @param null|array $data
	 * @param null|array $headers
	 * @param null|int   $timeout
	 *
	 * @return AutoUpdater_Response
	 */
	public static function get($url, $data = null, $headers = null, $timeout = null)
	{
		return static::getInstance()->makeGetRequest($url, $data, $headers, $timeout);
	}

	/**
	 * @param string            $url
	 * @param null|array|string $data
	 * @param null|array        $headers
	 * @param null|int          $timeout
	 *
	 * @return AutoUpdater_Response
	 */
	public static function post($url, $data = null, $headers = null, $timeout = null)
	{
		return static::getInstance()->makePostRequest($url, $data, $headers, $timeout);
	}

	/**
	 * @param string     $url
	 * @param null|array $data
	 * @param null|array $headers
	 * @param null|int   $timeout
	 *
	 * @return AutoUpdater_Response
	 */
	protected function makeGetRequest($url, $data = null, $headers = null, $timeout = null)
	{
		return AutoUpdater_Response::getInstance();
	}

	/**
	 * @param string            $url
	 * @param null|array|string $data
	 * @param null|array        $headers
	 * @param null|int          $timeout
	 *
	 * @return AutoUpdater_Response
	 */
	protected function makePostRequest($url, $data = null, $headers = null, $timeout = null)
	{
		return AutoUpdater_Response::getInstance();
	}
}