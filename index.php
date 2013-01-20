<?php
define("STORYTLR_VERSION","1.2.0");
define("DATABASE_VERSION", "2");
define("AUTO_INSTALL", true);
define("AUTO_UPGRADE", true);

// Update after deployment for location of non-public files
$root = dirname(__FILE__);

set_include_path(
      $root . '/protected/application' . PATH_SEPARATOR
    . $root . '/protected/application/admin/models' . PATH_SEPARATOR    
    . $root . '/protected/application/api/models' . PATH_SEPARATOR
    . $root . '/protected/application/public/models' . PATH_SEPARATOR
    . $root . '/protected/application/pages/models' . PATH_SEPARATOR
    . $root . '/protected/application/widgets/models' . PATH_SEPARATOR
    . $root . '/protected/library' . PATH_SEPARATOR
    . $root . '/protected/library/Feedcreator' . PATH_SEPARATOR
    . $root . '/protected/library/htmLawed' . PATH_SEPARATOR
    . get_include_path()
);

// Run the install stuff if configuration is missing
if( AUTO_INSTALL &&
	! file_exists( $root . '/protected/config/config.ini') &&
	! file_exists( '/etc/storytlr/storytlr.conf') &&
	! file_exists( '/etc/storytlr/config.ini')) {
	$template = array();
	ob_start();
	$template['title'] = require_once( $root . '/protected/install/install.php' );
	$template['content'] = ob_get_contents();
	ob_end_clean();
	require_once( $root . '/protected/install/template.phtml' );
	exit();
}

if( AUTO_UPGRADE &&
	! file_exists( $root . '/protected/install/database/version')
	|| trim(file_get_contents($root . '/protected/install/database/version')) != DATABASE_VERSION) {
	ob_start();
	$template['title'] = require_once( $root . '/protected/install/upgrade.php' );
	$template['content'] = ob_get_contents();
	ob_end_clean();
	require_once( $root . '/protected/install/template.phtml' );
	exit();
}

require_once 'Bootstrap.php';

Bootstrap::run();
