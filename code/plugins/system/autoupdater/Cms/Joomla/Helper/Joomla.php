<?php
defined('_JEXEC') or die;

class AutoUpdater_Cms_Joomla_Helper_Joomla
{
	protected static $core_extensions = array(
		'component' => array(
			'com_admin',
			'com_ajax',
			'com_associations',
			'com_banners',
			'com_cache',
			'com_categories',
			'com_checkin',
			'com_config',
			'com_contact',
			'com_content',
			'com_contenthistory',
			'com_cpanel',
			'com_fields',
			'com_finder',
			'com_installer',
			'com_joomlaupdate',
			'com_languages',
			'com_login',
			'com_mailto',
			'com_media',
			'com_menus',
			'com_messages',
			'com_modules',
			'com_newsfeeds',
			'com_plugins',
			'com_postinstall',
			'com_redirect',
			'com_search',
			'com_tags',
			'com_templates',
			'com_users',
			'com_weblinks',
			'com_wrapper',
		),
		'module'    => array(
			'mod_articles_archive',
			'mod_articles_categories',
			'mod_articles_category',
			'mod_articles_latest',
			'mod_articles_news',
			'mod_articles_popular',
			'mod_banners',
			'mod_breadcrumbs',
			'mod_custom',
			'mod_feed',
			'mod_finder',
			'mod_footer',
			'mod_languages',
			'mod_login',
			'mod_menu',
			'mod_random_image',
			'mod_related_items',
			'mod_search',
			'mod_stats',
			'mod_syndicate',
			'mod_tags_popular',
			'mod_tags_similar',
			'mod_users_latest',
			'mod_weblinks',
			'mod_whosonline',
			'mod_wrapper',
			'admin/mod_custom',
			'admin/mod_feed',
			'admin/mod_latest',
			'admin/mod_logged',
			'admin/mod_login',
			'admin/mod_menu',
			'admin/mod_multilangstatus',
			'admin/mod_popular',
			'admin/mod_quickicon',
			'admin/mod_sampledata',
			'admin/mod_stats_admin',
			'admin/mod_status',
			'admin/mod_submenu',
			'admin/mod_title',
			'admin/mod_toolbar',
			'admin/mod_version',
		),
		'plugin'    => array(
			'authentication/cookie',
			'authentication/gmail',
			'authentication/joomla',
			'authentication/ldap',
			'captcha/recaptcha',
			'content/contact',
			'content/emailcloak',
			'content/fields',
			'content/finder',
			'content/geshi',
			'content/joomla',
			'content/loadmodule',
			'content/pagebreak',
			'content/pagenavigation',
			'content/vote',
			'editors/codemirror',
			'editors/none',
			'editors/tinymce',
			'editors-xtd/article',
			'editors-xtd/contact',
			'editors-xtd/fields',
			'editors-xtd/image',
			'editors-xtd/menu',
			'editors-xtd/module',
			'editors-xtd/pagebreak',
			'editors-xtd/readmore',
			'extension/joomla',
			'fields/calendar',
			'fields/checkboxes',
			'fields/color',
			'fields/editor',
			'fields/imagelist',
			'fields/integer',
			'fields/list',
			'fields/media',
			'fields/radio',
			'fields/sql',
			'fields/text',
			'fields/textare',
			'fields/url',
			'fields/user',
			'fields/usergrouplist',
			'finder/categories',
			'finder/contacts',
			'finder/content',
			'finder/newsfeeds',
			'finder/tags',
			'installer/folderinstaller',
			'installer/packageinstaller',
			'installer/urlinstaller',
			'quickicon/eosnotify',
			'quickicon/extensionupdate',
			'quickicon/joomlaupdate',
			'quickicon/phpversioncheck',
			'sampledata/blog',
			'search/categories',
			'search/contacts',
			'search/content',
			'search/newsfeeds',
			'search/tags',
			'system/cache',
			'system/debug',
			'system/fields',
			'system/highlight',
			'system/languagecode',
			'system/languagefilter',
			'system/log',
			'system/logout',
			'system/p3p',
			'system/redirect',
			'system/remember',
			'system/sef',
			'system/stats',
			'system/updatenotification',
			'twofactorauth/totp',
			'twofactorauth/yubikey',
			'user/contactcreator',
			'user/joomla',
			'user/profile',
		),
		'template'  => array(
			'atomic',
			'beez3',
			'beez5',
			'beez_20',
			'protostar',
			'system',
			'admin/bluestork',
			'admin/hathor',
			'admin/isis',
			'admin/system',
		),
		'library'   => array(
			'fof',
			'idna_convert',
			'joomla',
			'lib_fof30',
			'phpmailer',
			'phpass',
			'phputf8',
			'simplepie',
		),
		'language'  => array(
			'en-GB',
			'admin/en-GB',
		),
		'file'      => array(
			'joomla'
		)
	);

	/**
	 *
	 * @return array
	 */
	public static function getCoreExtensions()
	{
		return static::$core_extensions;
	}

	/**
	 * @param string $type
	 * @param string $slug
	 *
	 * @return object
	 */
	public static function getExtension($type, $slug)
	{
		$extension            = new stdClass();
		$extension->folder    = null;
		$extension->client_id = null;

		if ($slug == 'joomla' && $type == 'cms')
		{
			$type = 'file';
		}
		elseif ($type == 'plugin')
		{
			list($extension->folder, $slug) = explode('/', $slug, 2);
		}
		elseif (in_array($type, array('module', 'language', 'template')))
		{
			if (strpos($slug, 'admin/') === 0)
			{
				$extension->client_id = 1;
				list(, $slug) = explode('/', $slug, 2);
			}
			else
			{
				$extension->client_id = 0;
			}
		}
		$extension->type    = $type;
		$extension->element = $slug;

		return $extension;
	}

	/**
	 * @param object $extension
	 *
	 * @return bool
	 */
	public static function isExtensionInstalled($extension)
	{
		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);

		$conditions = array(
			$db->qn('type') . ' = ' . $db->q($extension->type),
			$db->qn('element') . ' = ' . $db->q($extension->element),
		);

		if ($extension->folder)
		{
			$conditions[] = $db->qn('folder') . ' = ' . $db->q($extension->folder);
		}
		if ($extension->client_id)
		{
			$conditions[] = $db->qn('client_id') . ' = ' . $db->q($extension->client_id);
		}

		$query->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where($conditions);

		try
		{
			$result = $db->setQuery($query)->loadResult() > 0;
		}
		catch (Exception $e)
		{
			return false;
		}

		return $result;
	}

	/**
	 *
     * @param string $slug
     *
	 * @return array
	 */
	public static function getChildWhereCondition($slug = '')
	{
		/** @var \Joomla\Database\DatabaseDriver|JDatabaseDriver $db */
		$db = JFactory::getDbo();

		return array(
			$db->qn('type') . ' = ' . $db->q('plugin', false),
			$db->qn('element') . ' = ' . $db->q($slug ? $slug : AUTOUPDATER_J_PLUGIN_SLUG),
			$db->qn('folder') . ' = ' . $db->q('system', false),
			$db->qn('client_id') . ' = ' . $db->q(0, false),
		);
	}

	/**
	 *
	 * @return bool
	 */
	public static function isAdmin()
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = JFactory::getApplication();
		if (version_compare(JVERSION, '3.7', '>='))
		{
			return $app->isClient('administrator');
		}

		return $app->isAdmin();
	}

	/**
	 * @param mixed $data
	 *
	 * @return \Joomla\Registry\Registry|JRegistry
	 */
	public static function getRegistry($data = null)
	{
		if (version_compare(JVERSION, '3.4', '>='))
		{
			$classname = 'Joomla\Registry\Registry';
		}
		else
		{
			$classname = 'JRegistry';
		}

		return new $classname($data);
	}

	/**
	 * @param string      $from
	 * @param string      $to
	 * @param string|null $path
	 */
	public static function renameDbConversionFiles($from, $to, $path = null)
	{
		$filemanager = AutoUpdater_Filemanager::getInstance();

		$path  = $filemanager->untrailingslashit($path ? $path : JPATH_ROOT);
		$files = JFolder::files($path . 'administrator/components/com_admin/sql/others/mysql',
			'.+' . str_replace('.', '\.', $from) . '$', false, true);
		if (!empty($files))
		{
			foreach ($files as $file)
			{
				$dest = substr($file, 0, 0 - strlen($from)) . $to;
				$filemanager->move($file, $dest, true);
			}
		}
	}
}