#!/usr/bin/php 
<?php
// Manage the command line
if ($argc <3) {
	$argument = $argv[1];
} else {
	die("Usage: cache-flush.php [all]\r\n");
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

require_once 'Bootstrap.php';

Bootstrap::prepare();

/* ----- Code to be executed ----- */

if (isset($argument) && $argument = 'all') {
	$all = true;
} else {
	$all = false;
}

if (Zend_Registry::isRegistered("cache")) {
	$cache = Zend_Registry::get("cache");
	if ($all) {
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		echo "Flush old entries from content cache\r\n";
	} else {
		$cache->clean(Zend_Cache::CLEANING_MODE_OLD);
		echo "Flush all entries from content cache\r\n";		
	}
}

if (Zend_Registry::isRegistered("sql_cache")) {
	$cache = Zend_Registry::get("sql_cache");
	if ($all) {
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		echo "Flush old entries from sql cache\r\n";
	} else {
		$cache->clean(Zend_Cache::CLEANING_MODE_OLD);
		echo "Flush all entries from sql cache\r\n";		
	}
}

if (Zend_Registry::isRegistered("metadata_cache")) {
	$cache = Zend_Registry::get("metadata_cache");
	if ($all) {
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		echo "Flush old entries from metadata cache\r\n";
	} else {
		$cache->clean(Zend_Cache::CLEANING_MODE_OLD);
		echo "Flush all entries from metadata cache\r\n";		
	}
}