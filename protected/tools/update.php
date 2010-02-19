#!/usr/bin/php 
<?php
// Manage the command line
if ( $argc < 2 ) {
	die("Usage: {$argv[0]} user [source]\r\n");
} else if ( $argc == 3 ) {
	$cl_user = $argv[1];
	$cl_source = $argv[2];
} else if ( $argc < 3 ) {
	$cl_user = $argv[1];
	$cl_source = null;
} else {
	die("Usage: {$argv[0]} user [source]\r\n");
}

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

// We want to track how long the update takes
$start_time = time();

require_once 'Bootstrap.php';

Bootstrap::prepare();

// We don't want to limit this script in time
ini_set('max_execution_time', 0);

// Setup a logger
$logger = new Zend_Log();
$logger->addWriter(new Zend_Log_Writer_Stream($root .'/logs/updates.log'));
Zend_Registry::set('logger',$logger);

// Prepare models we need to access
echo "Memory usage on startup: " . memory_get_usage() . "\r\n";
$usersTable	  = new Users();

// Get the user
$user = $usersTable->getUserFromUsername($cl_user);
if (!$user || $user->is_suspended) {	
	echo "User {$user->username} is suspended.\r\n";
	$logger->log("User {$user->username} is suspended.\r\n", Zend_Log::INFO);		
	die();
}

Zend_Registry::set("shard", $user->id);

// Get the user sources
$sourcesTable = new Sources();
$sources 	  = $sourcesTable->getSources();
if (!$sources) {
	echo "No sources found to update.\r\n";
	$logger->log("No sources found to update.", Zend_Log::INFO);
	die();
}

// Log an entry
$logger->log("Updating {$user->username}", Zend_Log::INFO);	

shuffle($sources);

$success = 0;
$failure = 0;
$total   = count($sources);

foreach($sources as $source) {
	
	if ($source['service'] == 'stuffpress') {
		continue;
	}
	
	if (!$source['enabled']) {
		continue;
	}
	
	if( ! is_null( $cl_source ) && $source['service'] != $cl_source ) {
		continue;
	}
	
	echo "Memory: " . memory_get_usage() . "\r\n";
	
	$model 	 = SourceModel::newInstance($source['service'], $source);
	
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
		$logger->log("Could not update source {$source['id']}: ".$e->getMessage(), Zend_Log::ERR);
		echo $e->getTraceAsString();
		$failure++;
	}
}

// Wrap up
$end_time = time();

$total_time = $end_time - $start_time;
echo "Updated $success out of $total sources in $total_time seconds.\r\n";
