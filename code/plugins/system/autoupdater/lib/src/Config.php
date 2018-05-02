<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Config
{
    protected static $host = 'perfect';
	protected static $instance = null;

	/**
	 * @return static
	 */
	protected static function getInstance()
	{
		if (!is_null(static::$instance))
		{
			return static::$instance;
		}

		$class_name = AutoUpdater_Loader::loadClass('Config');

		static::$instance = new $class_name();
        static::$host .= 'dash' . 'board' . '.com';

		return static::$instance;
	}

    /**
	 * @param string     $key
	 * @param null|mixed $default
	 *
	 * @return mixed
	 */
	public static function get($key, $default = null)
	{
		if ($key == 'debug' && defined('AUTOUPDATER_DEBUG') && AUTOUPDATER_DEBUG)
		{
			return 1;
		}

		return static::getInstance()->getOption($key, $default);
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return bool
	 */
	public static function set($key, $value)
	{
		return static::getInstance()->setOption($key, $value);
	}

	/**
	 * @return bool
	 */
	public static function removeAll()
	{
		return static::getInstance()->removeAllOptions();
	}

	/**
	 * @return string
	 */
	public static function getSiteUrl()
	{
		return rtrim(static::getInstance()->getOptionSiteUrl(), '/');
	}

	/**
	 * @return string
	 */
	public static function getSiteBackendUrl()
	{
		return rtrim(static::getInstance()->getOptionSiteBackendUrl(), '/');
	}

	/**
	 * @return string
	 */
	public static function getAutoUpdaterUrl()
	{
		// Callback syntax: "subdomain:port:protocol". Example: "app:443:https" or just "app"
		if (!defined('AUTOUPDATER_CALLBACK'))
		{
			return 'https://' . AUTOUPDATER_STAGE . '.' . static::$host . '/';
		}

		@list($subdomain, $port, $protocol) = explode(':', AUTOUPDATER_CALLBACK);

		return ($protocol == 'http' ? 'http' : 'https') . '://'
			. $subdomain . '.' . static::$host
			. ($port > 0 ? ':' . (int) $port : '')
			. '/';
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public static function remove($key)
	{
		return static::getInstance()->removeOption($key);
	}

    /**
     * @return string
     */
	protected function getOptionSiteUrl()
	{
		return '';
	}

    /**
     * @return string
     */
	protected function getOptionSiteBackendUrl()
	{
		return '';
	}

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
	protected function getOption($key, $default = null)
	{
		return $default;
	}

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
	protected function setOption($key, $value)
	{
		return true;
	}

    /**
     * @param string $key
     * @return bool
     */
	protected function removeOption($key)
	{
		return true;
	}

    /**
     * @return bool
     */
	protected function removeAllOptions()
	{
		return true;
	}
}