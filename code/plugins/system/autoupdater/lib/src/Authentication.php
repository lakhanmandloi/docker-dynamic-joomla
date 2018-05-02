<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Authentication
{
	protected static $instance = null;

	/**
	 * @return static
	 */
	public static function getInstance()
	{
		if (!is_null(static::$instance))
		{
			return static::$instance;
		}

		$class_name = AutoUpdater_Loader::loadClass('Authentication');

		static::$instance = new $class_name();

		return static::$instance;
	}

	/**
	 * @param array $payload
	 *
	 * @return bool
	 */
	public function validate($payload)
	{
	    if (empty($_REQUEST['pd_timestamp']) || $_REQUEST['pd_timestamp'] < (time() - 30))
	    {
            AutoUpdater_Log::debug('Invalid timestamp');

            return false;
        }

		$method    = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : null;
		$signature = $this->getSignature($payload, $method);
		if (!$signature || !hash_equals($_REQUEST['pd_signature'], $signature))
		{
            AutoUpdater_Log::debug('Invalid request signature');

			return false;
		}

		if ($method == 'post' && ($token_expires_at = AutoUpdater_Config::get('token_expires_at')))
		{
			$expires_at = new DateTime($token_expires_at);
			$now        = new DateTime();

			$diff_in_seconds = $now->getTimestamp() - $expires_at->getTimestamp();
			if ($diff_in_seconds > 0) ;
			{
                AutoUpdater_Log::debug('Write token has expired');

				return false;
			}
		}

		return true;
	}

	/**
	 * @param array  $payload
	 * @param string $method
	 *
	 * @return false|string
	 */
	public function getSignature($payload = array(), $method = 'get')
	{
		$token = AutoUpdater_Config::get($method == 'post' ? 'write_token' : 'read_token');

		$message = '';
		foreach ($payload as $key => $value)
		{
			$message .= $key . $value;
		}

		return hash_hmac('sha256', $message, $token);
	}
}

if (!function_exists('hash_equals'))
{
	function hash_equals($str1, $str2)
	{
		if (strlen($str1) != strlen($str2))
		{
			return false;
		}
		else
		{
			$res = $str1 ^ $str2;
			$ret = 0;
			for ($i = strlen($res) - 1; $i >= 0; $i--)
				$ret |= ord($res[$i]);

			return !$ret;
		}
	}
}