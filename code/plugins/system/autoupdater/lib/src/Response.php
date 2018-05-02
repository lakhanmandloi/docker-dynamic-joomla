<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Response
{
	public $code = 0;
	public $message = '';
	public $headers = array();
	public $body = null;
	public $data = null;
	protected $encrypt = false;

	protected static $http_codes = array(
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		208 => 'Already Reported',
		226 => 'IM Used',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Switch Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Payload Too Large',
		414 => 'URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		421 => 'Misdirected Request',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		451 => 'Unavailable For Legal Reasons',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		510 => 'Not Extended',
		511 => 'Network Authentication Required',
	);

	/**
	 * @return static
	 */
	public static function getInstance()
	{
		$class_name = AutoUpdater_Loader::loadClass('Response');

		return new $class_name();
	}

	/**
	 * @param mixed $data
	 *
	 * @return $this
	 */
	public function bind($data)
	{
		return $this;
	}

	/**
	 * @param int $code
	 *
	 * @return $this
	 */
	public function setCode($code)
	{
		$this->code = (int) $code;

		return $this;
	}

	/**
	 * @param string $message
	 *
	 * @return $this
	 */
	public function setMessage($message)
	{
		$this->message = (string) $message;

		return $this;
	}

	/**
	 * @param mixed $data
	 *
	 * @return $this
	 */
	public function setData($data)
	{
		$this->data = $data;

		return $this;
	}

	/**
	 * @param mixed $body
	 *
	 * @return $this
	 */
	public function setBody($body)
	{
		$this->body = $body;

		return $this;
	}

	/**
	 * @param string $name
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setHeader($name, $value)
	{
		$this->headers[$name] = $value;

		return $this;
	}

    /**
     * @return $this
     */
	public function setAutoupdaterHeader()
    {
        $this->setHeader('Autoupdater-API', '1.0');

        return $this;
    }

    /**
     * @param bool $encrypt
     *
     * @return $this
     */
    public function setEncryption($encrypt)
    {
        $this->encrypt = (bool) $encrypt;

        return $this;
    }

	public function send()
	{
		$this->sendHeaders();
		$this->sendBody();
		exit();
	}

	public function sendJSON()
	{
		$this->setHeader('Content-Type', 'application/json')
			->send();
	}

	protected function sendHeaders()
	{
		$protocol = 'HTTP/1.0';
		if (isset($_SERVER['SERVER_PROTOCOL']) && 'HTTP/1.1' == $_SERVER['SERVER_PROTOCOL'])
		{
			$protocol = 'HTTP/1.1';
		}

		if ($this->code <= 0)
		{
			$this->code = 200;
		}

		if (empty($this->message) && isset(static::$http_codes[$this->code]))
		{
			$this->message = static::$http_codes[$this->code];
		}

		header($protocol . ' ' . $this->code . ($this->message ? ' ' . $this->message : ''), true, $this->code);
		foreach ($this->headers as $name => $value)
		{
			header("$name: $value", true);
		}
	}

	protected function sendBody()
	{
		if (is_null($this->body) && !is_null($this->data))
		{
			$this->body = array(
				'data'     => $this->data,
				'metadata' => array(
					'version' => AUTOUPDATER_VERSION,
				)
			);
		}

		if (is_array($this->body) || (
				isset($this->headers['Content-Type']) &&
				strpos($this->headers['Content-Type'], 'application/json') !== false
			))
		{
		    $body = is_scalar($this->body) ? $this->body : json_encode($this->body);

		    echo $this->prepareBody($body);
		}
		else
		{
			echo $this->prepareBody($this->body, false);
		}
	}

	public function sendFile($filename)
	{
		$this->setHeader('Content-Description', 'File Transfer')
			->setHeader('Content-Type', 'application/octet-stream')
			->setHeader('Content-Disposition', 'attachment; filename="' . basename($filename) . '"')
			->setHeader('Expires', '0')
			->setHeader('Cache-Control', 'must-revalidate')
			->setHeader('Pragma', 'public')
			->setHeader('Content-Length', filesize($filename))
			->sendHeaders();

		readfile($filename);
		exit();
	}

    private function prepareBody($body, $wrap = true)
    {
        if (!$body)
        {
            return $body;
        }

        if ($this->encrypt &&
            (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') &&
            ($aes_key = AutoUpdater_Config::get('aes_key')) &&
            AutoUpdater_Config::get('encryption', 1))
        {
            try
            {
                $nonce = \ParagonIE_Sodium_Compat::randombytes_buf(24);
                $body = \ParagonIE_Sodium_Compat::crypto_aead_xchacha20poly1305_ietf_encrypt(
                    $body,
                    $nonce,
                    $nonce,
                    $aes_key
                );

                return '##!##' . base64_encode($nonce) . ':' . base64_encode($body) . '##!##';
            }
            catch (Exception $e)
            {
                AutoUpdater_Log::error('Encryption error. Exception message: ' . $e->getMessage());
            }
        }

        if ($wrap)
        {
            $body = '###' . $body . '###';
        }

        return $body;
    }
}