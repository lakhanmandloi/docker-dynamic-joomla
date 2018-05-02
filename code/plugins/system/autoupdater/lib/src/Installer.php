<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Installer
{
	protected static $instance = null;
	protected $options = array();

	/**
	 * @return static
	 */
	public static function getInstance()
	{
		if (!is_null(static::$instance))
		{
			return static::$instance;
		}

		$class_name = AutoUpdater_Loader::loadClass('Installer');

		static::$instance = new $class_name();

		return static::$instance;
	}

	public function __construct()
	{

	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return $this
	 */
	public function setOption($key, $value)
	{
		$this->options[$key] = $value;

		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public function getOption($key, $default = null)
	{
		if (isset($this->options[$key]))
		{
			return $this->options[$key];
		}

		return $default;
	}

	/**
	 * @return bool
	 */
	public function install()
	{
        AutoUpdater_Log::debug(sprintf('Installing Child %s', AUTOUPDATER_VERSION));

		if (!AutoUpdater_Config::get('version'))
		{
            AutoUpdater_Config::set('version', AUTOUPDATER_VERSION);
		}

		$this->createTokens();

        AutoUpdater_Log::debug(sprintf('Child %s has been installed.', AUTOUPDATER_VERSION));

		return true;
	}

	public function selfUpdate()
	{
		if (isset($_REQUEST['pd_endpoint']) && in_array($_REQUEST['pd_endpoint'], array('child/update/after', 'child/verify')))
		{
			// Do not run self-update as we are running it through API
			return;
		}

		$version = AutoUpdater_Config::get('version', '1.0');
		if (version_compare($version, AUTOUPDATER_VERSION, '<'))
		{
            AutoUpdater_Log::debug("Self update from version $version to " . AUTOUPDATER_VERSION);
			$this->update();
		}
	}

	/**
	 * @return bool
	 */
	public function update()
	{
		$current_version = AutoUpdater_Config::get('version', '1.0');
		$new_version     = $this->getOption('version', AUTOUPDATER_VERSION);
        AutoUpdater_Log::debug(sprintf('Updating Child from version %s to %s', $current_version, $new_version));

        // TODO remove after migration of all clients
		if (version_compare($current_version, '1.16', '<'))
		{
			if (!$this->migrateVersion1_13())
			{
				return false;
			}
		}

		if (version_compare($current_version, $new_version, '<'))
		{
            AutoUpdater_Config::set('version', $new_version);
		}

        AutoUpdater_Log::debug(sprintf('Child has been updated from version %s to %s', $current_version, $new_version));

		return true;
	}

	/**
     * TODO remove after migration of all clients
	 * @return bool
	 */
	protected function migrateVersion1_12()
	{
		return true;
	}

    /**
     * TODO remove after migration of all clients
     * @return bool
     */
    protected function migrateVersion1_13()
    {
        return $this->recreateAesKey();
    }

	/**
	 * @return bool
	 */
	protected function createTokens()
	{
		if (!AutoUpdater_Config::get('read_token'))
		{
            AutoUpdater_Config::set('read_token', $this->generateToken());

			if (!AutoUpdater_Config::get('write_token'))
			{
                AutoUpdater_Config::set('write_token', $this->generateToken());
			}
		}
        if (!AutoUpdater_Config::get('aes_key'))
        {
            AutoUpdater_Config::set('aes_key', $this->generateToken());
        }

		return true;
	}

	/**
	 * @return bool
	 */
	protected function recreateAesKey()
	{
        AutoUpdater_Log::debug('Create the AES key and send it to Auto-Updater API');

		$aes_key = AutoUpdater_Config::get('aes_key');
		if ($aes_key)
        {
            AutoUpdater_Log::debug('The AES key has been already created');
            return true;
        }

        // Create the AES key
        $aes_key = $this->generateToken();
		// Save the AES key that another page load would not set a new value
        AutoUpdater_Config::set('aes_key', $aes_key);

		$json = array(
			'aes_key'     => $aes_key,
			'read_token'  => AutoUpdater_Config::get('read_token'),
			'write_token' => AutoUpdater_Config::get('write_token'),
		);
		if (!empty($this->options['site_id']))
		{
			$json['site_id'] = (int) $this->options['site_id'];
		}
		else
		{
			$json['site_url'] = AutoUpdater_Config::getSiteUrl();
		}
		$json = json_encode($json);

		$signature = AutoUpdater_Authentication::getInstance()
			->getSignature(array(
				'json' => $json,
			));

		$response = AutoUpdater_Request::post(
            AutoUpdater_Config::getAutoUpdaterUrl() . 'api/1.0/child/site/tokens?pd_signature=' . $signature,
			$json,
			array(
				'Content-Type' => 'application/json',
			)
		);

		if ($response->code == 200 && !empty($response->body->success))
		{
            AutoUpdater_Log::debug('The AES key has been successfully created');
			return true;
		}

		// Limit the number of attempts to create the AES key
		$aes_update_attempts = (int) AutoUpdater_Config::get('aes_update_attempts', 0);
        $aes_update_attempts++;

        AutoUpdater_Log::debug(sprintf('Failed to send the AES key for the %d time', $aes_update_attempts));

		if ($aes_update_attempts > 5)
		{
		    // Disable the encryption if it has failed 5 times to pass the key to Auto-Updater API
            AutoUpdater_Config::set('encryption', 0);
			return true;
		}

        AutoUpdater_Config::set('aes_update_attempts', $aes_update_attempts);
		if (php_sapi_name() !== 'cli')
        {
            // Remove the AES key as it has failed to send to Auto-Updater API. There will be a next attempt.
            AutoUpdater_Config::set('aes_key', '');
        }

		return false;
	}

	/**
	 * @return string
	 */
	protected function generateToken()
	{
		$key   = '';
		$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$max   = mb_strlen($chars, '8bit') - 1;
		for ($i = 0; $i < 32; ++$i)
		{
			$key .= $chars[random_int(0, $max)];
		}

		return $key;
	}

	/**
     * @param bool $self
     *
	 * @return bool
	 */
	public function uninstall($self = false)
	{
        AutoUpdater_Log::debug(sprintf('Uninstalling Child %s', AUTOUPDATER_VERSION));

        AutoUpdater_Backuptool::getInstance()
			->uninstall();

        AutoUpdater_Config::removeAll();

        AutoUpdater_Log::debug(sprintf('Child %s has been uninstalled.', AUTOUPDATER_VERSION));

		return true;
	}
}