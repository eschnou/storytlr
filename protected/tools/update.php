#!/usr/bin/php 
<?php
// Update after deployment for location of non-public files
$root = dirname(dirname(__FILE__));

// We're assuming the Zend Framework is already on the include_path
// TODO this should be moved to the boostrap file
set_include_path(
      $root . '/application' . PATH_SEPARATOR
    . $root . '/application/admin/models' . PATH_SEPARATOR    
    . $root . '/application/public/models' . PATH_SEPARATOR
    . $root . '/library' . PATH_SEPARATOR
    . $root . '/library/Feedcreator' . PATH_SEPARATOR
    . get_include_path()
);

// We don't want to limit this script in time
ini_set('max_execution_time', 0);

// Prepare the environment
require_once 'Bootstrap.php';
Bootstrap::prepare();

// Go !
$updater = new Updater($argc, $argv);
$updater->run();

class Updater
{
	private $cl_username;
	
	private $cl_source;
	
	private $config;
	
	private $logger;
	
	public function __construct($argc, $argv) {		
		// Get the config
		$this->config = Zend_Registry::get("configuration");
		
		// Setup a logger
		$this->logger = new Zend_Log();
		$log_root = isset($this->config->path->logs) ? $this->config->path->logs : $root .'/logs';
		$this->logger->addWriter(new Zend_Log_Writer_Stream($log_root . '/updates.log'));
		Zend_Registry::set('logger',$this->logger);
		
		// Parse the command line
		if ( $argc == 3 ) {
			$this->cl_username = $argv[1];
			$this->cl_source = $argv[2];
		} else if ( $argc == 2) {
			$this->cl_username = $argv[1];
			$this->cl_source = null;
		}
	}
	
	public function run() {
		// We want to track how long the update takes
		$start_time = time();
		
		// Prepare models we need to access
		$usersTable	  = new Users();
		
		// Accumulators
		$success = 0;
		$failure = 0;
		$total = 0;
		
		if ($this->cl_username) {
			// Get the user
			$user = $usersTable->getUserFromUsername($this->cl_username);
			if (!$user || $user->is_suspended) {
				echo "User {$user->username} is suspended.\r\n";
				$logger->log("User {$user->username} is suspended.\r\n", Zend_Log::INFO);
				die;
			}
				
			// Update this user content
			$this->updateUser($user, $success, $failure, $total);
		} else {
			$users = $usersTable->getAllUsers(true);
			foreach ($users as $user) {
				$this->updateUser($user, $success, $failure, $total);
			}
		}
		
		// Wrap up
		$end_time = time();
		
		// Output results
		$total_time = $end_time - $start_time;
		echo "Updated $success out of $total sources in $total_time seconds.\r\n";	
	}

	function updateUser($user, &$success, &$failure, &$total) {
		$this->logger->log("Updating data for " . $user->username, Zend_Log::INFO);
		
		// Assign the proper shard id
		Zend_Registry::set("shard", $user->id);
		
		// Get the user sources
		$sourcesTable = new Sources();
		$sources 	  = $sourcesTable->getSources();
		if (!$sources) {
			echo "No sources found to update.\r\n";
			$this->logger->log("No sources found to update.", Zend_Log::INFO);
			die();
		}
		
		// Log an entry
		$this->logger->log("Updating {$user->username}", Zend_Log::INFO);
		
		shuffle($sources);		
		$total = $total + count($sources);
		
		foreach($sources as $source) {
		
			if ($source['service'] == 'stuffpress') {
				continue;
			}
		
			if (!$source['enabled']) {
				continue;
			}
		
			if( ! is_null( $this->cl_source ) && $source['service'] != $this->cl_source ) {
				continue;
			}
				
			$model 	 = SourceModel::newInstance($source['service'], $source);
			if (!$model->isActive()) {
				echo "Skipping unactive source {$source['service']}.\r\n ";
				continue;
			}
		
			try {
				if ($source['imported']) {
					echo "Updating source {$source['service']} for user {$user->username} [" . ($success + $failure) . "/$total] ({$source['id']})....";
					$items = $model->updateData();
					$model->onNewItems($items);
					echo " found " . count($items) . " items\r\n";
				}
				else {
					echo "Importing source {$source['service']} ({$source['id']}).\r\n";
					$items = $model->importData();
				}
				$success++;
			} catch (Exception $e) {
				echo "Could not update source {$source['id']}: ".$e->getMessage();
				$this->logger->log("Could not update source {$source['id']}: ".$e->getMessage(), Zend_Log::ERR);
				echo $e->getTraceAsString();
				$failure++;
			}
		}
	}	
}