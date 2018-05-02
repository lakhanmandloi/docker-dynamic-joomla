<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Api
{
	protected static $instance = null;

	protected $initialized = false;

	/**
	 * @return static
	 */
	public static function getInstance()
	{
		if (!is_null(static::$instance))
		{
			return static::$instance;
		}

		$class_name = AutoUpdater_Loader::loadClass('Api');

		static::$instance = new $class_name();

		return static::$instance;
	}

	public function __construct()
	{
		// Initialize Auto-Updater API if met the minimal requirements
		$method = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : null;
		if (isset($_REQUEST['autoupdater']) && $_REQUEST['autoupdater'] == 'api' &&
			isset($_REQUEST['pd_endpoint']) &&
			isset($_REQUEST['pd_signature']) &&
			in_array($method, array('get', 'post'))
		)
		{
			$this->init();
		}
	}

	/**
	 * @return bool
	 */
	public function isInitialized()
	{
		return $this->initialized;
	}

	protected function init()
	{
		$method = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : null;

		// Get all data from the request
		$payload = array();
		foreach ($_REQUEST as $key => $value)
		{
			if (substr($key, 0, 3) == 'pd_' && $key != 'pd_signature')
			{
				$payload[$key] = urldecode($value);
			}
		}
		// Sort the request data by keys, to generate a correct signature from the payload
		ksort($payload);

		if ($method == 'post')
		{
			// Get the request JSON body
			if ($body = file_get_contents('php://input'))
			{
				// Do not decode JSON before validation
				$payload['json'] = $body;
			}
		}

		// Validate the request payload
		$auth = AutoUpdater_Authentication::getInstance();
		if (!$auth->validate($payload))
		{
            AutoUpdater_Response::getInstance()
				->setCode(403)
                ->setAutoupdaterHeader()
				->send();
		}

		// Decode JSON
		if (isset($payload['json']))
		{
			try
			{
				$json = json_decode($payload['json'], true);
				unset($payload['json']);
				$payload = array_merge($payload, $json);
			}
			catch (Exception $e)
			{
                AutoUpdater_Response::getInstance()
					->setCode(400)
                    ->setAutoupdaterHeader()
					->setData(array(
						'success' => false,
						'message' => 'Failed to decode JSON',
						'error'   => array(
							'code'    => $e->getCode(),
							'message' => $e->getMessage(),
						),
					))
					->sendJSON();
			}
		}

		// Parse the endpoint and create the task name
		$endpoint = strtolower($payload['pd_endpoint']);
		$task     = str_replace(' ', '', ucwords($method . ' ' . str_replace('/', ' ', $endpoint)));

		register_shutdown_function(array($this, 'catchError'));

		// Get the task
		try
		{
			$this->task = AutoUpdater_Task::getInstance($task, $payload);
		}
		catch (Exception $e)
		{
            AutoUpdater_Response::getInstance()
				->setCode(400)
                ->setAutoupdaterHeader()
				->setData(array(
					'success' => false,
					'message' => 'Failed to initialize task ' . $task,
					'error'   => array(
						'code'    => $e->getCode(),
						'message' => $e->getMessage(),
					),
				))
				->sendJSON();
		}

		$this->initialized = true;
	}

	public function handle()
	{
		if (!$this->initialized)
		{
			return;
		}

        AutoUpdater_Config::set('ping', time());

		$response = AutoUpdater_Response::getInstance();
        AutoUpdater_Log::debug('Doing task ' . $this->task->getName());
		try
		{
			$data = $this->task->doTask();
			$response->setData($data);

            AutoUpdater_Log::debug('Task ' . $this->task->getName()
				. ' result ' . print_r($response->data, true)
			);
		}
		catch (AutoUpdater_Exception_Response $e)
		{
			$data = array(
				'success' => false,
				'message' => $e->getMessage(),
			);

			if ($e->getErrorCode())
			{
				$data['error'] = array(
					'code'    => $e->getErrorCode(),
					'message' => $e->getErrorMessage(),
				);
			}

			$response->setData($data)
				->setCode($e->getCode());

            AutoUpdater_Log::error('Task ' . $this->task->getName()
				. ' result ' . print_r($response->data, true)
			);
		}
		catch (Exception $e)
		{
			$data = array(
				'success' => false,
				'message' => $e->getCode() . ' ' . $e->getMessage(),
			);

			$response->setData($data);

            AutoUpdater_Log::error('Task ' . $this->task->getName()
				. ' result ' . print_r($response->data, true) . "\n"
				. 'Exception trace: ' . $e->getTraceAsString()
			);
		}

		$response->setAutoupdaterHeader()
            ->setEncryption($this->task->isEncryptionRequired())
            ->sendJSON();
	}

	public function catchError()
	{
		$error = error_get_last();
		// fatal error, E_ERROR === 1
		if ($error['type'] === E_ERROR)
		{
			$filemanager = AutoUpdater_Filemanager::getInstance();
			$message     = sprintf('%s on line %d in file %s',
				$error['message'],
				$error['line'],
				$filemanager->trimPath($error['file'])
			);

            AutoUpdater_Log::error($message);

            AutoUpdater_Response::getInstance()
				->setCode(500)
				->setData(array(
					'success' => false,
					'message' => 'PHP fatal error',
					'error'   => array(
						'code'    => 0,
						'message' => $message,
					),
				))
				->sendJSON();
		}
	}
}