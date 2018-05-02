<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Response extends AutoUpdater_Response
{
	/**
	 * @param JHttpResponse|Joomla\CMS\Http\Response|Exception $data
	 *
	 * @return $this
	 */
	public function bind($data)
	{
		if ($data instanceof Exception)
		{
			/** @var Exception $data */
			$this->code    = $data->getCode();
			$this->message = $data->getMessage();
		}
		else
		{
			/** @var $data JHttpResponse|Joomla\CMS\Http\Response */
			$this->code    = $data->code;
			$this->message = '';
			$this->headers = $data->headers;
			$this->body    = $data->body;

			if (isset($this->headers['Content-Type']) &&
				strpos($this->headers['Content-Type'], 'application/json') !== false &&
				is_scalar($this->body))
			{
				try
				{
					$this->body = json_decode($this->body);
				}
				catch (Exception $e)
				{

				}
			}
		}

		if (AutoUpdater_Config::get('debug'))
		{
			$response = get_object_vars($this);
			if (isset($this->headers['Content-Type']) &&
				strpos($this->headers['Content-Type'], 'application/json') !== 0 &&
				strpos($this->headers['Content-Type'], 'application/xml') !== 0 &&
				strpos($this->headers['Content-Type'], 'text/') !== 0
			)
			{
				// Do not log downloaded file content
				$response['body'] = 'Truncated...';
			}

            AutoUpdater_Log::debug('Response ' . print_r($response, true));
		}

		return $this;
	}
}