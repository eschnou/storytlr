<?php
	require_once( 'shared.php' );

	if( file_exists( $root . '/protected/config/config.ini' ) 
		|| file_exists( '/etc/storytr/storytlr.conf' ) 
		|| file_exists( '/etc/storytr/storytlr.ini' )) 
	{
		Check::bad( 'Storytlr appears to already be installed, config file already exists.' );
		return 'Installation';
	}

	// Preset some variables...
	$form_errors = array();
	$form_values = array(
		'mysql_host' => 'localhost',
		'mysql_database' => 'storytlr',
		'config_username' => 'admin'
	);

	if( isset( $_GET['config'] ) ) {
		header( 'Content-type: text/plain' );
		header( 'Content-Disposition: attachment; filename="config.ini"' );
		die( $_SESSION['config'] );
	}

	if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
		$form_values = $_POST;

		// Validation
		$required = array(
			'mysql_host',
			'mysql_database',
			'mysql_user',
			'mysql_password',
			'config_username',
			'config_password'
		);
		foreach( $required as $field ) {
			if( empty( $_POST[$field] ) )
				$form_errors[$field] = 'Field is required.';
		}

		// Installation
		try {
			if( 0 == count( $form_errors ) ) {
				$res = Database::Connect( $_POST['mysql_host'], $_POST['mysql_database'], $_POST['mysql_user'], $_POST['mysql_password'] );
				if( true !== $res ) {
					$form_errors['mysql_host'] = 'Please check this field.';
					$form_errors['mysql_database'] = 'Please check this field.';
					$form_errors['mysql_user'] = 'Please check this field.';
					$form_errors['mysql_password'] = 'Please check this field.';
					throw new Exception( $res );
				}
				Check::good( '[' . date( 'H:i:s' ) .'] Connected to database.' );

				// Run the initial schema
				$res = Database::RunFolder($root . '/protected/install/database/schema/' );
				if( true !== $res )
					throw new Exception( 'Error loading database schema:<br/><div class="nested-error">' . $res . '</div>' );

				// Process all updates
				$current_version = 0;
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
				
				// Save the new version
				@file_put_contents( $root . '/protected/install/database/version', DATABASE_VERSION);	
					
				Check::good( '[' . date( 'H:i:s' ) .'] Loaded database schema.' );

				$subs = array( 'username' => $_POST['config_username'], 'userpass' => md5( $_POST['config_password'] ) );
				$res = Database::RunFile( $root . '/protected/install/database/data/add_user.sql', $subs );

				if( true !== $res )
					throw new Exception( 'Error loading database data:<br/><div class="nested-error">' . $res . '</div>' );

				Check::good( '[' . date( 'H:i:s' ) .'] Loaded database data.' );

				$substitutions = array(
					'mysql_host'         => $_POST['mysql_host'],
					'mysql_database'     => $_POST['mysql_database'],
					'mysql_password'     => $_POST['mysql_password'],
					'mysql_username'     => $_POST['mysql_user'],
					'security_cookie'    => sha1( time() . $_SERVER['SERVER_NAME'] ),
					'host'               => $_SERVER['SERVER_NAME'],
					'path'               => $_SERVER['REQUEST_URI'], // TODO: This isn't very good...
					'user'               => $_POST['config_username'],
					'flickr_comment'     => ( empty( $_POST['config_flickr_api_key'] ) ) ? ';' : '',
					'flickr_key'         => $_POST['config_flickr_api_key'],
					'google_map_comment' => ( empty( $_POST['config_google_maps_api_key'] ) ) ? ';' : '',
					'google_map_key'     => $_POST['config_google_maps_api_key']
				);

				if( Config::SaveFile( $root . '/protected/install/config.ini.template', $root . '/protected/config/config.ini', $substitutions ) )
					Check::good( '[' . date( 'H:i:s' ) .'] Saved config file.' );
				else {
					$_SESSION['config'] = Config::RenderFile( $root . '/protected/install/config.ini.template', $substitutions );
					Check::warn( "Could not write config.ini.<br/>Please below to download your config file and place it in <tt>protected/config/</tt>.<br/><a href=\"?config\">Download config.ini</a>");
				}

				print "<p>Installation Complete!</p>";

				print "<p>Please remove or rename protected/install/install.php</p>";

				print '<p>Go To: <a href="http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']. '/admin">Administration</a> or <a href="http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']. '">Front Page</a></p>';

				return 'Installation';
			}
			else {
				throw new Exception( 'Your configuration has errors, please see below.' );
			}
		}
		catch ( Exception $e ) {
			Check::bad( $e->getMessage() );
			Check::restart();
		}

	}
?>
	<h2>Requirements Check</h2>
<?php
	Check::PHP( "5.0" );
	Check::SettingValue( "magic_quotes_gpc", false );
	Check::SettingValue( "short_open_tag", true );

	if( Check::CanInclude( 'Zend/Version.php', 'Zend Framework' ) )
		if( Zend_Version::compareVersion( '1.0.0' ) > 0 )
			warn( 'Zend Version 1.0.0 or newer is recommended' );

	Check::FunctionExists( 'mcrypt_module_open', 'mcrypt' );
	Check::FunctionExists( 'mb_internal_encoding', 'mbstring' );
	Check::FunctionExists( 'imagecreatefrompng', 'GD' );
	Check::FunctionExists( 'curl_init', 'cURL' );
	Check::FunctionExists( 'xmlrpc_encode_request', 'xmlrpc' );
	Check::ExtensionExists( 'PDO', 'PDO' );

	Check::PathWritable( 'protected/temp/' );
	Check::PathWritable( 'protected/upload/' );
	Check::PathWritable( 'protected/logs/' );
	Check::PathWritable( 'protected/install/database/' );
	Check::PathWritable( 'protected/config/', true );

	if( Check::no_errors() ):
		$form = new Form( $form_errors, $form_values );
?>
	<h2>Configuration</h2>
	<form action="" method="POST">
		<fieldset>
			<legend>MySQL</legend>
			<?php
				$form->text( 'mysql', 'host' );
				$form->text( 'mysql', 'database', 'Database Name' );
				$form->text( 'mysql', 'user' );
				$form->password( 'mysql', 'password' );
			?>
		</fieldset>
		<fieldset>
			<legend>User</legend>
			<?php
				$form->text( 'config', 'username', 'Username' );
				$form->password( 'config', 'password', 'Password' );
			?>
		</fieldset>
		<fieldset>
			<legend>API Keys (optional)</legend>
			<?php
				$form->text( 'config', 'google_maps_api_key' );
				$form->text( 'config', 'flickr_api_key' );
			?>
		</fieldset>
		<input type="submit" value="Install" />
	</form>
<?
	else:
?>
	<p>Please fix the above issues before continuing.</p>
	<!--  <form action=""><input type="submit" value="Check Again" onclick="this.disabled = true; this.value = 'Please Wait...';" /></form> -->
<?php
	endif;
	return 'Installation';
?>
