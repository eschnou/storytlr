<?php
require_once( 'shared.php' );
require_once('Zend/Config/Ini.php');

// Parse the config file
if (file_exists($root . '/protected/config/config.ini')) {
	$config_path = $root . '/protected/config/config.ini';
} else if (file_exists('/etc/storytlr/config.ini')) {
	$config_path = '/etc/storytlr/config.ini';
}

$config = new Zend_Config_Ini($config_path,'general');

// Check the version number
if (!file_exists($root . '/protected/database/version')) {
	$current_version = 0;
} else {
	$current_version = (int) @file_get_contents($root . '/protected/database/version');
}

try {
	// Connect to the database
	$res = Database::Connect($config->db->host,$config->db->dbname,$config->db->username,$config->db->password);
	if( true !== $res )
	throw new Exception( 'Error connecting to the database:<br/><div class="nested-error">' . $res . '</div>' );
	
	// Run the update scripts
	while($current_version < DATABASE_VERSION) {
		$next_version = $current_version + 1;
		$folder = sprintf($root . '/protected/install/database/update/%03d/', $next_version);
		$res = Database::RunFolder($folder);
		if( true !== $res )
		throw new Exception( 'Error running database upgrade script:<br/><div class="nested-error">' . $res . '</div>' );
		
		// Save the new version and move to the next one
		@file_put_contents( $root . '/protected/install/database/version', $next_version);	
		Check::good("Applied upgrade script to version $next_version.");
	
		$current_version++;
	}
	
	// Done !
	Check::good("Successfully upgraded the database !");
}
catch ( Exception $e ) {
	Check::bad( $e->getMessage() );
}

return "Database upgrade";