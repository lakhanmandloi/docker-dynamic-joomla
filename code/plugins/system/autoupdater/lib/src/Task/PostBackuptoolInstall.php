<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Task_PostBackuptoolInstall extends AutoUpdater_Task_Base
{
	/**
	 * @throws AutoUpdater_Exception_Response
	 *
	 * @return array
	 */
	public function doTask()
	{
		if (AUTOUPDATER_STAGE != 'app')
		{
			// Get credentials from the request payload
			$dir      = $this->input('directory');
			$login    = $this->input('login');
			$password = $this->input('password');
			$secret   = $this->input('secret');
		}

		if (empty($dir) || empty($login) || empty($password) || empty($secret))
		{
			// Get credentials from Auto-Updater API
			$query = array(
				'pd_site_id' => (int) $this->input('site_id'),
			);

			$query['pd_signature'] = AutoUpdater_Authentication::getInstance()
				->getSignature($query);
			if (!$query['pd_site_id'] || !$query['pd_signature'])
			{
				throw new AutoUpdater_Exception_Response('Missing required parameters', 400);
			}

			$response = AutoUpdater_Request::get(
                AutoUpdater_Config::getAutoUpdaterUrl() . 'api/1.0/child/backuptool/credentials',
				$query,
				array(
					'Content-Type' => 'application/json',
				)
			);

			$dir      = !empty($response->body->directory) ? $response->body->directory : null;
			$login    = !empty($response->body->login) ? $response->body->login : null;
			$password = !empty($response->body->password) ? $response->body->password : null;
			$secret   = !empty($response->body->secret) ? $response->body->secret : null;
		}

		if (empty($dir) || empty($login) || empty($password) || empty($secret))
		{
			throw new AutoUpdater_Exception_Response('Failed to get backup tool credentials', 400);
		}

		$options = array(
			'htaccess_disable' => (bool) $this->input('htaccess_disable', false),
			'backup_part_size' => (int) $this->input('backup_part_size', 0),
		);

		return AutoUpdater_Backuptool::getInstance()
			->install($dir, $login, $password, $secret, $options);
	}
}