<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Exception_Response extends Exception
{
	/**
	 * @var mixed
	 */
	protected $error_code = 0;
	/**
	 * @var string
	 */
	protected $error_message = '';

	/**
	 * AutoUpdater_Exception_Response constructor.
	 *
	 * @param string         $message JSON message
	 * @param int            $code    HTTP code
	 * @param null|Exception $previous
	 */
	public function __construct($message = '', $code = 0, $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}

	/**
	 * @param mixed  $code
	 * @param string $message
	 */
	public function setError($code, $message)
	{
		$this->error_code    = $code;
		$this->error_message = $message;
	}

	/**
	 * @return mixed
	 */
	public function getErrorCode()
	{
		return $this->error_code;
	}

	/**
	 * @return string
	 */
	public function getErrorMessage()
	{
		return $this->error_message;
	}
}