<?php
/*
 *  Copyright 2008-2009 Laurent Eschenauer and Alard Weisscher
 *  Copyright 2010 John Hobbs
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *  
 */

// Zend includes
require_once 'Zend/Loader.php';
require_once 'Zend/Registry.php';
require_once 'Zend/Config/Ini.php';
require_once 'Zend/Controller/Front.php';
require_once 'Zend/View.php';
require_once 'Zend/Controller/Action/Helper/ViewRenderer.php';
require_once 'Zend/Controller/Action/HelperBroker.php';
require_once 'Zend/Layout.php';
require_once 'Zend/Controller/Router/Route.php';
require_once 'Zend/Db/Table.php';
require_once 'Zend/Acl.php';
require_once 'Zend/Acl/Role.php';
require_once 'Zend/Acl/Resource.php';

// Stuffpress includes
require_once 'Stuffpress/Module/LayoutPlugin.php';
require_once 'Stuffpress/Controller/Plugin/Acl.php';
require_once 'Stuffpress/Application.php';
require_once 'Stuffpress/Cookie.php';
require_once 'Stuffpress/Db/Properties.php';
require_once 'Stuffpress/Controller/Action.php';

// Abstract includes
require_once('admin/controllers/BaseController.php');
require_once('public/controllers/BaseController.php');
require_once('pages/controllers/BaseController.php');

// Models frequently required
require_once('Comments.php');
require_once('Data.php');
require_once('Files.php');
require_once('ImageItem.php');
require_once('Properties.php');
require_once('SourceItem.php');
require_once('SourceModel.php');
require_once('Sources.php');
require_once('SourcesProperties.php');
require_once('Stories.php');
require_once('StoryItems.php');
require_once('Themes.php');
require_once('Users.php');
require_once('Widgets.php');
require_once('WidgetsProperties.php');

// Bootsrap class
class Bootstrap
{

	public static $frontController = null;

	public static $root = '';

	public static $registry = null;

	public static $start;

	public static function run()
	{
		self::$start = microtime();
		self::prepare();
		self::parseDomain();
		$response = self::$frontController->dispatch();
		self::sendResponse($response);
		self::logStats();
	}

	public static function prepare()
	{
		self::checkEnvironment();
		self::setupEnvironment();
		Zend_Loader::registerAutoload();
		self::setupRegistry();
		self::setupConfiguration();
		self::setupLogger();
		self::setupFrontController();
		self::setupView();
		self::setupLayout();
		self::setupDatabase();
		self::setupCache();
		self::setupRouter();
		self::setupPlugins();
		self::setupApplication();
		self::setupAcl();
	}

	/**
	 * Runs some basic checks to confirm that the environment contains everything
	 * needed to run Storytlr. This is to prevent the "white screen of death" ;-)
	 */
	public static function checkEnvironment() {
		if( ! function_exists( 'mcrypt_module_open' ) ) { die( 'Storytlr requires mcrypt, which can not be found.' ); }
		if( ! class_exists( 'PDO', false ) ) { die( 'Storytlr requires PDO, which can not be found.' ); }
		if( ! function_exists( 'curl_init' ) ) { die( 'Storytlr requires PHP Curl, which can not be found.' ); }
	}

	public static function setupEnvironment()
	{
		error_reporting(E_ALL|E_STRICT);
		ini_set('display_errors', false);
		ini_set('max_execution_time', 300);
		ini_set('upload_max_filesize', '15M');
		ini_set('user_agent', 'Storytlr/1.0');
		mb_internal_encoding("UTF-8");
		self::$root = dirname(dirname(__FILE__));
	}

	public static function setupRegistry()
	{
		self::$registry = new Zend_Registry(array(), ArrayObject::ARRAY_AS_PROPS);
		Zend_Registry::setInstance(self::$registry);
	}

	public static function setupConfiguration()
	{
		$config_path = self::$root . '/config/config.ini';
		if (!file_exists($config_path)) {
			die("Could not find the config.ini configuration file. Please verify your setup.");
		}

		$config = new Zend_Config_Ini(
		$config_path,
		'general'
		);
		self::$registry->configuration = $config;
		self::$registry->root = self::$root;
		date_default_timezone_set($config->web->timezone);
		if ($config->debug) {
			ini_set('display_errors', true);
			ini_set('log_errors', true);
			if (isset($config->path->logs)) {
				$log_root = $config->path->logs;
			} else {
				$log_root = self::$root .'/logs';
			}
			ini_set('error_log', $log_root .'/error.log');
		}
	}

	public static function setupFrontController()
	{
		self::$frontController = Zend_Controller_Front::getInstance();
		self::$frontController->throwExceptions(false);
		self::$frontController->returnResponse(true);
		self::$frontController->setControllerDirectory(array(
			"public" 	=> self::$root . '/application/public/controllers',
			"console" 	=> self::$root . '/application/console/controllers',
			"pages" 	=> self::$root . '/application/pages/controllers',		
			"widgets" 	=> self::$root . '/application/widgets/controllers',		
			"dialogs" 	=> self::$root . '/application/dialogs/controllers',
			"admin"	  	=> self::$root . '/application/admin/controllers'));		
		self::$frontController->setDefaultModule('public');
		self::$frontController->setParam('registry', self::$registry);
	}

	public static function setupView()
	{
		$view = new Zend_View;
		$view->setEncoding('UTF-8');
		$view->addHelperPath(self::$root . '/library/Stuffpress/View/Helper', 'Stuffpress_View_Helper');
		$viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer($view);
		Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
	}

	public static function setupLayout() {
		//Setup the layout
		Zend_Layout::startMvc(array(
			'layoutPath' =>	self::$root . '/application/admin/views/layouts',
			'layout' => 'default'
			));
			$layoutModulePlugin = new Stuffpress_Module_LayoutPlugin();
			$layoutModulePlugin->registerModuleLayout('admin', 	 self::$root . '/application/admin/views/layouts');
			$layoutModulePlugin->registerModuleLayout('public',  self::$root . '/application/public/views/layouts');
			$layoutModulePlugin->registerModuleLayout('dialogs', self::$root . '/application/public/views/layouts');
			$layoutModulePlugin->registerModuleLayout('pages',   self::$root . '/application/admin/views/layouts');
			self::$frontController->registerPlugin($layoutModulePlugin);
	}

	public static function sendResponse(Zend_Controller_Response_Http $response)
	{
		$response->setHeader('Content-Type', 'text/html; charset=UTF-8', true);
		$response->sendResponse();
	}

	public static function logStats() {
		$config = self::$registry->configuration;
		$uri = $_SERVER['REQUEST_URI'];
		$host = trim($_SERVER['SERVER_NAME'], "/");

		if ($config->profile) {
			$time = explode(' ', self::$start);
			$time = $time[1] + $time[0];
			$start = $time;

			$time = microtime();
			$time = explode(' ', $time);
			$time = $time[1] + $time[0];
			$finish = $time;
			$total_time = round(($finish - $start), 4);

			$peakUsage = round(memory_get_peak_usage(true)/1000000,3);
			//echo "<p>Page generated in $total_time seconds, memory peak of $peakUsage MB</p>\n";

			// Output time spent data
			if (isset($config->path->logs)) {
				$log_root = $config->path->logs;
			} else {
				$log_root = self::$root .'/logs';
			}
			$log = fopen($log_root .'/trace.log', "a");
			fwrite($log, "$host/$uri\r\n");
			fwrite($log, "$total_time seconds, memory peak of $peakUsage MB\n\r");

			// Get the data from the profiler
			$profiler = self::$registry->database->getProfiler();

			$totalTime    = $profiler->getTotalElapsedSecs();
			$queryCount   = $profiler->getTotalNumQueries();
			$longestTime  = 0;
			$longestQuery = null;

			foreach ($profiler->getQueryProfiles() as $query) {
				if ($query->getElapsedSecs() > $longestTime) {
					$longestTime  = $query->getElapsedSecs();
					$longestQuery = $query->getQuery();
				}
			}

			// Output the data
			fwrite($log,'Executed ' . $queryCount . ' queries in ' . $totalTime . ' seconds' . "\n");
			fwrite($log,'Average query length: ' . $totalTime / $queryCount . ' seconds' . "\n");
			fwrite($log, 'Queries per second: ' . $queryCount / $totalTime . "\n");
			fwrite($log, 'Longest query length: ' . $longestTime . "\n");
			fwrite($log, "Longest query: \n" . $longestQuery . "\n");
			fwrite($log, "----------------------------------\r\n\r\n");
			fclose($log);
		}
	}

	public static function setupRouter() {
		$router = self::$frontController->getRouter(); // returns a rewrite router by default

		$router->addRoute(
		'rss',
		new Zend_Controller_Router_Route('/rss/*', array('module' => 'public', 'controller' => 'timeline', 'action' => 'rss'))
		);

		$router->addRoute(
		'embed',
		new Zend_Controller_Router_Route_Regex(
								'embed/(.+)\.js', 
		array('module' => 'public', 'controller' => 'embed', 'action' => 'index'),
		array(1 => 'file'),
    							'embed/%s.js'));		

		$router->addRoute(
		'slug',
		new Zend_Controller_Router_Route_Regex(
								'entry/(.*?)(\d+)-(\d+)\.html', 
		array('module' => 'public', 'controller' => 'timeline', 'action' => 'view'),
		array(2 => 'source', 3 => 'item'),
    							'entry/%s%d-%d.html'));

		$router->addRoute(
		'page',
		new Zend_Controller_Router_Route('/page/:page', array('module' => 'admin', 'controller' => 'page', 'action' => 'view'))
		);

		$router->addRoute(
		'thumbnail',
		new Zend_Controller_Router_Route('/thumbnail/:key', array('module' => 'public', 'controller' => 'file', 'action' => 'view', 'size' => 'thumbnail', 'inline' => true))
		);

		$router->addRoute(
		'image',
		new Zend_Controller_Router_Route('/image/:size/:key/*', array('module' => 'public', 'controller' => 'file', 'action' => 'view', 'inline' => true))
		);

		$router->addRoute(
		'archives',
		new Zend_Controller_Router_Route('/archives/:year/:month', array('module' => 'public', 'controller' => 'timeline', 'action' => 'archive'))
		);

		$router->addRoute(
		'search',
		new Zend_Controller_Router_Route('/search', array('module' => 'public', 'controller' => 'timeline', 'action' => 'search'))
		);

		$router->addRoute(
		'slide',
		new Zend_Controller_Router_Route('/slide/:source/:item/index.html', array('module' => 'public', 'controller' => 'timeline', 'action' => 'slide'))
		);

		$router->addRoute(
		'tag',
		new Zend_Controller_Router_Route('/tag/:tag', array('module' => 'public', 'controller' => 'timeline', 'action' => 'tag'))
		);

		$router->addRoute(
		'pictures',
		new Zend_Controller_Router_Route('/pictures', array('module' => 'public', 'controller' => 'timeline', 'action' => 'type', 'type' => 'image'))
		);

		$router->addRoute(
		'videos',
		new Zend_Controller_Router_Route('/videos', array('module' => 'public', 'controller' => 'timeline', 'action' => 'type', 'type' => 'video'))
		);

		$router->addRoute(
		'stories',
		new Zend_Controller_Router_Route('/stories', array('module' => 'pages', 'controller' => 'stories', 'action' => 'index'))
		);

		$router->addRoute(
		'blog',
		new Zend_Controller_Router_Route('/blog', array('module' => 'public', 'controller' => 'timeline', 'action' => 'type', 'type' => 'blog'))
		);

		$router->addRoute(
		'status',
		new Zend_Controller_Router_Route('/status', array('module' => 'public', 'controller' => 'timeline', 'action' => 'type', 'type' => 'status'))
		);

		$router->addRoute(
		'links',
		new Zend_Controller_Router_Route('/links', array('module' => 'public', 'controller' => 'timeline', 'action' => 'type', 'type' => 'link'))
		);

		$router->addRoute(
		'type',
		new Zend_Controller_Router_Route('/type/:type', array('module' => 'public', 'controller' => 'timeline', 'action' => 'type'))
		);

		$router->addRoute(
		'surl',
		new Zend_Controller_Router_Route('/surl/:hash', array('module' => 'public', 'controller' => 'shorturl', 'action' => 'index'))
		);
			
		$router->addRoute(
		'story',
		new Zend_Controller_Router_Route_Regex(
								'story/(\d+)-(.+)\.html', 
		array('module' => 'public', 'controller' => 'story', 'action' => 'view'),
		array(1 => 'id', 2 => 'description'),
    							'story/%d-%s.html'));
			
		$router->addRoute(
		'oldrss',
		new Zend_Controller_Router_Route('/user/:user/rss', array('module' => 'public', 'controller' => 'timeline', 'action' => 'rss'))
		);

		$router->addRoute(
		'entry',
		new Zend_Controller_Router_Route('/entry/:source/:item/*', array('module' => 'public', 'controller' => 'timeline', 'action' => 'view'))
		);
	}

	public static function setupDatabase()
	{
		$config = self::$registry->configuration;

		$pdoParams = array(
		PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
		PDO::ATTR_PERSISTENT => true
		);

		$params = array(
		    'host'           => $config->db->host,
		    'username'       => $config->db->username,
		    'password'       => $config->db->password,
		    'dbname'         => $config->db->dbname,
		    'driver_options' => $pdoParams
		);

		$db = Zend_Db::factory('Pdo_Mysql', $params);

		try {
			$db->query("SET NAMES 'utf8'");
			$db->setFetchMode(Zend_Db::FETCH_ASSOC);
		}
		catch (Zend_Db_Adapter_Exception $e) {
			die("Failed to connect to the database with error " . $e->getMessage());
		}

		if ($config->profile) {
			$db->setProfiler(true);
		}

		self::$registry->database = $db;
		Zend_Db_Table::setDefaultAdapter($db);
	}

	public static function setupCache() {
		$config = self::$registry->configuration;

		// Setup the cache path
		if (isset($config->cache->path)) {
			$path = $config->cache->path;
		} 
		else if (isset($config->path->temp)) {
			$path = $config->path->temp;
		}
		else {
			$path = "/tmp";
		}

		// Test if the cache folder exists
		if (!file_exists($path)) {
			die("The specified cache directory ($path) does not exist.");
		}

		if (isset($config->cache) && $config->cache->content) {
			// Creates the subfolder if required
			$folder = $path . "/content/";
			if (!file_exists($folder)) {
				if (!mkdir($folder, 0777)) {
					die ("Could not create cache folder $folder");
				}
			}

			// Setting up a regular content cache
			$frontendOptions = array('automatic_serialization' => true,
				    				 'lifetime' => 3600);

			$backendOptions  = array('cache_dir' => $folder);

			$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);

			self::$registry->cache = $cache;
		}

		if (isset($config->cache) && $config->cache->metadata) {
			// Creates the subfolder if required
			$folder = $path . "/metadata/";
			if (!file_exists($folder)) {
				if (!mkdir($folder, 0777)) {
					die ("Could not create cache folder $folder");
				}
			}

			// Add a metadata-cahce
			$frontendOptions = array(
				    'automatic_serialization' => true,
				    'lifetime' => 3600
			);

			$backendOptions  = array(
				    'cache_dir'                => $folder
			);

			$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
			self::$registry->metadata_cache = $cache;
			Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
		}

		if (isset($config->cache) && $config->cache->sql) {
			// Creates the subfolder if required
			$folder = $path . "/sql/";
			if (!file_exists($folder)) {
				if (!mkdir($folder, 0777)) {
					die ("Could not create cache folder $folder");
				}
			}

			// Add a database cache
			$frontendOptions = array(
				    'automatic_serialization' => true,
				    'lifetime' => 3600
			);

			$backendOptions  = array(
				    'cache_dir'                => $folder
			);

			$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
			self::$registry->sql_cache = $cache;
		}
	}

	public static function setupPlugins() {
		$dir = self::$root . "/application/plugins/";
		self::$frontController->addModuleDirectory($dir);

		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if ($file != "." && $file != ".." && $file != "CVS" && $file != "SVN" && $file != ".svn") {
						set_include_path($dir . basename($file) . '/models/' . PATH_SEPARATOR . get_include_path());
					}
				}
				closedir($dh);
			}
		}
	}

	public static function setupApplication() {
		// Set the default values
		$application = Stuffpress_Application::getInstance();
		$application->user = null;
		$application->role = 'guest';

		// Try to authenticate based on cookies
		try {
			$cookie		= new Stuffpress_Cookie();
			$user_id	= $cookie->validate();
		}
		catch(Exception $e) {
			$user_id	= 0;
		}

		if ($user_id) {
			$users	= new Users();
			$user   = $users->getUser($user_id);

			if ($user) {
				$application->user = $user;
				$application->role = 'member';
			}
		}
	}

	public static function setupAcl() {
		$acl = new Zend_Acl();
		$application = Stuffpress_Application::getInstance();

		/* Creating roles */
		$acl->addRole(new Zend_Acl_Role('guest'))
		->addRole(new Zend_Acl_Role('member'), 'guest')
		->addRole(new Zend_Acl_Role('admin'), 'member');

		/* Add the root resource */
		$acl->add(new Zend_Acl_Resource('root'));
			
		/* Resources for public module */
		$acl->add(new Zend_Acl_Resource('public'), 			'root');
		$acl->add(new Zend_Acl_Resource('public:comments'), 'public');
		$acl->add(new Zend_Acl_Resource('public:embed'), 	'public');
		$acl->add(new Zend_Acl_Resource('public:error'), 	'public');
		$acl->add(new Zend_Acl_Resource('public:file'), 	'public');
		$acl->add(new Zend_Acl_Resource('public:index'), 	'public');
		$acl->add(new Zend_Acl_Resource('public:home'), 	'public');
		$acl->add(new Zend_Acl_Resource('public:shorturl'), 'public');
		$acl->add(new Zend_Acl_Resource('public:story'), 	'public');
		$acl->add(new Zend_Acl_Resource('public:storymap'), 'public');
		$acl->add(new Zend_Acl_Resource('public:mappage'),  'public');
		$acl->add(new Zend_Acl_Resource('public:timeline'), 'public');

		/* Resources for consolemodule */
		$acl->add(new Zend_Acl_Resource('console'), 		'root');
		$acl->add(new Zend_Acl_Resource('console:stats'), 	'console');

		/* Resources for admin module */
		$acl->add(new Zend_Acl_Resource('admin'), 			'root');
		$acl->add(new Zend_Acl_Resource('admin:advanced'), 	'admin');
		$acl->add(new Zend_Acl_Resource('admin:auth'), 		'admin');
		$acl->add(new Zend_Acl_Resource('admin:avatar'), 	'admin');
		$acl->add(new Zend_Acl_Resource('admin:backup'), 	'admin');
		$acl->add(new Zend_Acl_Resource('admin:bookmarklet'), 	'admin');
		$acl->add(new Zend_Acl_Resource('admin:design'), 	'admin');
		$acl->add(new Zend_Acl_Resource('admin:home'), 		'admin');
		$acl->add(new Zend_Acl_Resource('admin:index'), 	'admin');
		$acl->add(new Zend_Acl_Resource('admin:pages'), 	'admin');
		$acl->add(new Zend_Acl_Resource('admin:page'), 		'admin');
		$acl->add(new Zend_Acl_Resource('admin:password'), 	'admin');
		$acl->add(new Zend_Acl_Resource('admin:post'), 		'admin');
		$acl->add(new Zend_Acl_Resource('admin:postemail'),     'admin');
		$acl->add(new Zend_Acl_Resource('admin:preferences'), 	'admin');
		$acl->add(new Zend_Acl_Resource('admin:profile'), 	'admin');
		$acl->add(new Zend_Acl_Resource('admin:recover'), 	'admin');
		$acl->add(new Zend_Acl_Resource('admin:register'), 	'admin');
		$acl->add(new Zend_Acl_Resource('admin:services'), 	'admin');
		$acl->add(new Zend_Acl_Resource('admin:sns'), 		'admin');
		$acl->add(new Zend_Acl_Resource('admin:share'), 	'admin');
		$acl->add(new Zend_Acl_Resource('admin:story'), 	'admin');
		$acl->add(new Zend_Acl_Resource('admin:widgets'), 	'admin');

		/* Resources for widgets */
		$acl->add(new Zend_Acl_Resource('widgets'), 		 'root');
		$acl->add(new Zend_Acl_Resource('widgets:archives'), 'widgets');
		$acl->add(new Zend_Acl_Resource('widgets:bio'), 	 'widgets');
		$acl->add(new Zend_Acl_Resource('widgets:custom'), 	 'widgets');
		$acl->add(new Zend_Acl_Resource('widgets:lastcomments'), 'widgets');
		$acl->add(new Zend_Acl_Resource('widgets:links'), 	 'widgets');
		$acl->add(new Zend_Acl_Resource('widgets:logo'), 	 'widgets');
		$acl->add(new Zend_Acl_Resource('widgets:music'), 	 'widgets');
		$acl->add(new Zend_Acl_Resource('widgets:rsslink'),	 'widgets');
		$acl->add(new Zend_Acl_Resource('widgets:search'), 	 'widgets');
		$acl->add(new Zend_Acl_Resource('widgets:tags'), 	   'widgets');
		$acl->add(new Zend_Acl_Resource('widgets:membersgfc'), 'widgets');

		/* Resources for pages */
		$acl->add(new Zend_Acl_Resource('pages'), 			 'root');
		$acl->add(new Zend_Acl_Resource('pages:custom'),     'pages');
		$acl->add(new Zend_Acl_Resource('pages:dashboard'),  'pages');
		$acl->add(new Zend_Acl_Resource('pages:link'), 		 'pages');
		$acl->add(new Zend_Acl_Resource('pages:lifestream'), 'pages');
		$acl->add(new Zend_Acl_Resource('pages:nopage'),     'pages');
		$acl->add(new Zend_Acl_Resource('pages:pictures'),   'pages');
		$acl->add(new Zend_Acl_Resource('pages:stories'),    'pages');
		$acl->add(new Zend_Acl_Resource('pages:videos'),     'pages');
		$acl->add(new Zend_Acl_Resource('pages:map'),     	'pages');

		/* Resources for dialogs */
		$acl->add(new Zend_Acl_Resource('dialogs'), 		  'root');
		$acl->add(new Zend_Acl_Resource('dialogs:customrss'), 'dialogs');

		/* Deny everything to everyone*/
		$acl->deny(null);

		/* Permissions for admins */
		$acl->allow('admin', 'console');

		/* Permissions for members */
		$acl->allow('member', 'public');
		$acl->allow('member', 'admin');
		$acl->allow('member', 'widgets');
		$acl->allow('member', 'pages');

		/* Permissions for guests */
		$acl->allow('guest',  'public:comments', array('index', 'form', 'add'));
		$acl->allow('guest',  'public:embed');
		$acl->allow('guest',  'public:error');
		$acl->allow('guest',  'public:file');
		$acl->allow('guest',  'public:home');
		$acl->allow('guest',  'public:index');
		$acl->allow('guest',  'public:shorturl');
		$acl->allow('guest',  'public:story', 	 array('view', 'map'));
		$acl->allow('guest',  'public:storymap', array('view'));
		$acl->allow('guest',  'public:mappage');
		$acl->allow('guest',  'public:timeline', array('archive', 'search', 'rss', 'selection', 'view', 'tag', 'type', 'slide', 'image'));

		$acl->allow('guest',  'pages:custom',	 array('index'));
		$acl->allow('guest',  'pages:dashboard', array('index'));
		$acl->allow('guest',  'pages:lifestream',array('index'));
		$acl->allow('guest',  'pages:link',		 array('index'));
		$acl->allow('guest',  'pages:nopage',	 array('index'));
		$acl->allow('guest',  'pages:pictures',	 array('index'));
		$acl->allow('guest',  'pages:stories',	 array('index'));
		$acl->allow('guest',  'pages:videos',	 array('index'));
		$acl->allow('guest',  'pages:map',	 	 array('index'));


		$acl->allow('guest',  'widgets:archives',     array('index'));
		$acl->allow('guest',  'widgets:bio', 	      array('index'));
		$acl->allow('guest',  'widgets:custom',       array('index'));
		$acl->allow('guest',  'widgets:lastcomments', array('index'));
		$acl->allow('guest',  'widgets:links', 		  array('index'));
		$acl->allow('guest',  'widgets:logo', 		  array('index'));
		$acl->allow('guest',  'widgets:music', 		  array('index'));
		$acl->allow('guest',  'widgets:rsslink',      array('index'));
		$acl->allow('guest',  'widgets:search', 	  array('index'));
		$acl->allow('guest',  'widgets:tags',         array('index'));
		$acl->allow('guest',  'widgets:membersgfc',   array('index'));

		$acl->allow('guest',  'admin:index');
		$acl->allow('guest',  'admin:auth');
		$acl->allow('guest',  'admin:home');
		$acl->allow('guest',  'admin:page');
		$acl->allow('guest',  'admin:register');
		$acl->allow('guest',  'admin:recover');

		self::$frontController->registerPlugin(new Stuffpress_Controller_Plugin_Acl($acl, $application->role));
	}

	public static function setupLogger() {
		$config = self::$registry->configuration;
		$path = isset($config->path->logs) ? $config->path->logs : self::$root .'/logs/';
		
		$logger = new Zend_Log();
		$logger->addWriter(new Zend_Log_Writer_Stream($path . '/messages.log'));
		if ($config->debug) {
			$logger->addWriter(new Zend_Log_Writer_Firebug());
		}
		Zend_Registry::set('logger',$logger);
	}

	public static function parseDomain() {
		$config		= Zend_Registry::get('configuration');
		$our_host	= $config->web->host;
		$this_host	= @$_SERVER['HTTP_HOST'];
		$uri		= @$_SERVER['REQUEST_URI'];
		$application = Stuffpress_Application::getInstance();

		$users  = new Users();

		// Save the uri for debug purposes
		self::$registry->uri = $uri;

		// Do we hit our main domain ?
		if (($our_host == $this_host)) {
			self::$registry->host = $our_host;

			// Is a user specified in the config ?
			if (isset($config->app->user)) {
				if ($user = $users->getUserFromUsername($config->app->user)) {
					self::$registry->user = $user;
				}
			}

			return;
		}

		// A user storytlr page
		if (preg_match("/(?<user>[a-zA-Z0-9]+).{$our_host}$/", $this_host, $matches)) {
			$username = $matches['user'];
			if ($user = $users->getUserFromUsername($username)) {
				if ($config->web->redirect && strlen($user->domain) > 0) {
					if (!isset($application->user) || ($application->user->id != $user->id)) {
						$url = "http://{$user->domain}$uri";
						header("Location: $url");
						exit;
					}
				}
				self::$registry->host = $this_host;
				self::$registry->user = $user;
				return;
			}
			else {
				header("Location: http://$our_host");
				exit;
			}
		}

		// A or CNAME ?
		if ($user = $users->getUserFromDomain($this_host)) {
			self::$registry->host = $this_host;
			self::$registry->user = $user;
			return;
		}

		// Maybe we should strip the www in front ?
		$matches = array();
		if (preg_match("/www.(.*)/", $this_host, $matches)) {
			if ($user = $users->getUserFromDomain($matches[1])) {
				self::$registry->host = $this_host;
				self::$registry->user = $user;
				return;
			}
		}

		// Last chance
		header("Location: http://$our_host");
		exit;
	}
}
