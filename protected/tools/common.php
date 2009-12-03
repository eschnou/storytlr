<?php
// We want to track how long the update takes
$start_time = time();

// Update after deployment for location of non-public files
$root = dirname(dirname(__FILE__));

// We're assuming the Zend Framework is already on the include_path
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

// Bootstrap
require_once 'Bootstrap.php';
Bootstrap::prepare();

// We don't want to limit this script in time 
// This overides bootstrap settings
ini_set('max_execution_time', 0);

