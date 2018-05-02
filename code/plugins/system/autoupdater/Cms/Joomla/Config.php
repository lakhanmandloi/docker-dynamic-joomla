<?php
defined('_JEXEC') or die;

require_once AUTOUPDATER_J_PLUGIN_HELPER_PATH . 'Joomla.php';

class AutoUpdater_Cms_Joomla_Config extends AutoUpdater_Config
{
	/**
	 * @var array
	 */
	private $params = null;

	/**
	 * @return string
	 */
	protected function getOptionSiteUrl()
	{
		/** @see \Joomla\CMS\Uri\Uri::root */
		return JUri::root(false);
	}

	/**
	 * @return string
	 */
	protected function getOptionSiteBackendUrl()
	{
		if (AutoUpdater_Cms_Joomla_Helper_Joomla::isAdmin())
		{
			/** @see \Joomla\CMS\Uri\Uri::base */
			return JUri::base(false);
		}

		/** @see \Joomla\CMS\Uri\Uri::root */
		return rtrim(JUri::root(false), '/') . '/' . basename(JPATH_ADMINISTRATOR);
	}


	/**
	 * @param string     $key
	 * @param null|mixed $default
	 *
	 * @return mixed
	 */
	protected function getOption($key, $default = null)
	{
		$this->loadOptions();

		return isset($this->params[$key]) ? $this->params[$key] : $default;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return bool
	 */
	protected function setOption($key, $value)
	{
		$this->loadOptions();

		$this->params[$key] = $value;

		return $this->save();
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	protected function removeOption($key)
	{
		$this->loadOptions();

		if (isset($this->params[$key]))
		{
			unset($this->params[$key]);
		}

		return $this->save();
	}

	/**
	 * @return bool
	 */
	protected function removeAllOptions()
	{
		$this->params = array();

		return $this->save();
	}

	private function loadOptions()
	{
		if (!empty($this->params))
		{
			return;
		}

		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('params'))
			->from($db->qn('#__extensions'))
			->where(AutoUpdater_Cms_Joomla_Helper_Joomla::getChildWhereCondition());

		try
		{
			$params = $db->setQuery($query)->loadResult();
			if (!empty($params))
			{
				$this->params = json_decode($params, true);
			}
		}
		catch (Exception $e)
		{

		}
	}

	/**
	 * @return bool
	 */
	private function save()
	{
		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('params') . ' = ' . $db->q(json_encode($this->params)))
			->where(AutoUpdater_Cms_Joomla_Helper_Joomla::getChildWhereCondition());

		try
		{
			return $db->setQuery($query)->execute();
		}
		catch (Exception $e)
		{

		}

		return false;
	}
}