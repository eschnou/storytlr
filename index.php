<?php
define("STORYTLR_VERSION","0.9.3.dev");

// Update after deployment for location of non-public files
$root = dirname(__FILE__);

// We're assuming the Zend Framework is already on the include_path
// TODO this should be moved to the boostrap file
set_include_path(
      $root . '/protected/application' . PATH_SEPARATOR
    . $root . '/protected/application/admin/models' . PATH_SEPARATOR    
    . $root . '/protected/application/public/models' . PATH_SEPARATOR
    . $root . '/protected/application/pages/models' . PATH_SEPARATOR
    . $root . '/protected/application/widgets/models' . PATH_SEPARATOR
    . $root . '/protected/library' . PATH_SEPARATOR
    . $root . '/protected/library/Feedcreator' . PATH_SEPARATOR
    . get_include_path()
);

require_once 'Bootstrap.php';

Bootstrap::run();
