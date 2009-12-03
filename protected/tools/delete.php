#!/usr/bin/php 
<?php
// Manage the command line
if ($argc <2) {
	die("Usage: delete.php user_id\r\n");
} else if ($argc <3) {
	$user_id = $argv[1];
} else {
	die("Usage: delete.php user_id\r\n");
}

// Update after deployment for location of non-public files
$root = dirname(dirname(__FILE__));

// We're assuming the Zend Framework is already on the include_path
// TODO this should be moved to the boostrap file
set_include_path(
      $root . '/application' . PATH_SEPARATOR
    . $root . '/application/admin/models' . PATH_SEPARATOR    
    . $root . '/application/public/models' . PATH_SEPARATOR
    . $root . '/application/pages/models' . PATH_SEPARATOR
    . $root . '/application/widgets/models' . PATH_SEPARATOR
    . $root . '/library' . PATH_SEPARATOR
    . $root . '/library/Feedcreator' . PATH_SEPARATOR
    . get_include_path()
);

require_once 'Bootstrap.php';

Bootstrap::prepare();

/* ----- Code to be executed ----- */

// Fetch the user
$udb = new Users();
if (!($user = $udb->getUser($user_id))) {
	die("No such user with id $user_id");
}

// Ask for confirmation
fwrite(STDOUT, "Are you sure to delete user {$user->username} ({$user->email}) ? Type 'yes': ");
$answer = trim(fgets(STDIN));
if ($answer != 'yes') {
	die("Operation aborted, nothing was deleted.\r\n");
}

// Assign the shard for the database
Zend_Registry::set("shard", $user->id);

// Fetch all sources and delete them
$sdb = new Sources();
$sources = $sdb->getSources();

// Delete all sources and associated items
if ($sources && count($sources) > 0) {
	foreach($sources as $source) {
		// Instantiate a model and remove all the data
		$model = SourceModel::newInstance($source['service']);
		$model->setSource($source);
		$model->deleteItems();
		
		// Delete the duplicated from the Data table
		$data = new Data();
		$data->deleteItems($source['id']);
		
		// Delete the source settings
		$properties = new SourcesProperties(array(Properties::KEY => $source['id']));
		$properties->deleteAllProperties();
		
		// Remove the source
		$sdb->deleteSource($source['id']);
		
		// We should also delete the associated comments
		$comments = new Comments();
		$comments->deleteComments($source['id']);
	}
}

// Delete all user files
$fdb 	= new Files();
$files 	= $fdb->getFiles();
if ($files && count($files) > 0) {
	foreach($files as $file) {
		$fdb->deleteFile($file->key);
	}
}

// Delete all stories
$stdb	 = new Stories();
$stories = $stdb->getStories();

if ($stories && count($stories) > 0) {
	foreach($stories as $story) {
		$stdb->deleteStory($story['id']);
	}
}

// Delete all widgets
$wdb 	= new Widgets();
$widgets = $wdb->getWidgets();
if ($widgets && count($widgets) > 0) {
	foreach($widgets as $widget) {
		$wdb->deleteWidget($widget['id']);
	}
}

// Delete all properties
$up = new Properties(array(Properties::KEY => $user->id));
$up->deleteAllProperties();

$wp = new WidgetsProperties(array(Properties::KEY => $user->id));
$wp->deleteAllProperties();

// Delete the user
$udb->deleteUser($user->id);

// Exit with proper message
die("User $user->username has been deleted.\r\n");
