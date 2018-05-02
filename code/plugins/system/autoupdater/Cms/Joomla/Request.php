<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Request extends AutoUpdater_Request
{
	/**
	 * @return JHttp|\Joomla\CMS\Http\Http
	 */
	protected function getHttp()
	{
		$ssl_verify = (bool) AutoUpdater_Config::get('ssl_verify', false);

		$options = AutoUpdater_Cms_Joomla_Helper_Joomla::getRegistry(array(
			'verify_peer' => $ssl_verify
		));

		if (defined('CURLOPT_SSL_VERIFYPEER'))
		{
			$options->set('transport.curl', array(CURLOPT_SSL_VERIFYPEER => $ssl_verify));
		}

		/** @see \Joomla\CMS\Http\HttpFactory::getHttp */
		return JHttpFactory::getHttp($options);
	}

	protected function makeGetRequest($url, $data = null, $headers = null, $timeout = null)
	{
		if (is_array($data))
		{
			$query = array();
			foreach ($data as $key => $value)
			{
				$query[] = $key . '=' . urlencode($value);
			}

			if (!empty($query))
			{
				$url .= (strpos($url, '?') === false ? '?' : '&') . implode('&', $query);
			}
		}

		try
		{
            AutoUpdater_Log::debug("GET $url");
			$result = $this->getHttp()->get($url, $headers, $timeout ? $timeout : static::$timeout);
		}
		catch (Exception $e)
		{
			$result = $e;
		}

		return AutoUpdater_Response::getInstance()
			->bind($result);
	}

	protected function makePostRequest($url, $data = null, $headers = null, $timeout = null)
	{
		if (!empty($data))
		{
			if (isset($headers['Content-Type']) &&
				strpos($headers['Content-Type'], 'application/json') !== false &&
				!is_scalar($data))
			{
				$data = json_encode($data);
			}
		}

		try
		{
            AutoUpdater_Log::debug("POST $url\nData " . (is_scalar($data) ? $data : print_r($data, true)));
			$result = $this->getHttp()->post($url, $data, $headers, $timeout ? $timeout : static::$timeout);
		}
		catch (Exception $e)
		{
			$result = $e;
		}

		return AutoUpdater_Response::getInstance()
			->bind($result);
	}
}