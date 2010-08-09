<?php

	class Check {

		protected static $errors = 0;
		protected static $include_found = true;

		public static function no_errors () {
			return ( 0 == self::$errors );
		}

		public static function restart () {
			self::$errors = 0;
		}

		public static function error_count () {
			return self::$errors;
		}

		protected static function show_check_div ( $string, $class ) {
			echo '<div class="' . $class . '">' . $string . '</div>';
		}

		public static function good ( $string ) { self::show_check_div( $string, 'good' ); }
		public static function bad ( $string ) { self::$errors++; self::show_check_div( $string, 'bad' ); }
		public static function warn ( $string ) { self::show_check_div( $string, 'warn' ); }

		// Check the level of php available
		public static function PHP ( $required ) {
			if( 1 == version_compare( $required, phpversion() ) )
				Check::bad( "Your PHP version is too low, the minimum required is $required." );
			else
				Check::good( "PHP Version " . phpversion() . " meets requirement." );
		}

		public static function SettingValue ( $setting, $expected ) {
			if( $expected != ini_get( $setting ) )
				Check::bad( "PHP Setting '$setting' should be '". var_export( $expected, true ) . "'." );
			else
				Check::good( "PHP Setting '$setting' is '" . var_export( $expected, true ) ."'." );
		}

		// Check if a class exists
		public static function ClassExists ( $class, $name, $warn_only=false ) {
			if( class_exists( $class, false ) )
				Check::good( "Found $name." );
			else if( $warn_only )
				Check::warn( "Can not find $name." );
			else
				Check::bad( "Can not find $name." );
		}

		// Check if a function exists.
		public static function FunctionExists ( $function, $name, $warn_only=false ) {
			if( function_exists( $function ) )
				Check::good( "Found $name." );
			else if( $warn_only )
				Check::warn( "Can not find $name." );
			else
				Check::bad( "Can not find $name." );
		}

		// Check if a file can be included, is on the path.
		public static function CanInclude ( $include, $name, $warn_only=false ) {
			self::$include_found = true;
			set_error_handler( 'Check::include_error_handler', E_WARNING );
			include_once( $include );
			restore_error_handler();
			if( self::$include_found )
				Check::good( "Found $name." );
			else if( $warn_only )
				Check::warn( "Can not find $name." );
			else
				Check::bad( "Can not find $name." );
			return self::$include_found;
		}

		protected static function include_error_handler ( $errno, $errstr ) {
			self::$include_found = false;
		}

		// Checks an extension existence by phpversion. Doesn't work for all extensions.
		public static function ExtensionExists ( $extension, $name, $warn_only=false ) {
			if( false !== phpversion( $extension ) )
				Check::good( "Found $name." );
			else if( $warn_only )
				Check::warn( "Can not find $name." );
			else
				Check::bad( "Can not find $name." );
		}

		public static function PathWritable ( $path, $warn_only=false ) {
			$root = dirname( __FILE__ ) . '/../../';
			if( is_writable( $root . $path ) )
				Check::good( "$path is writable." );
			else if( $warn_only )
				Check::warn( "$path is not writable." );
			else
				Check::bad( "$path is not writable." );
		}
	} // Class Check
	
	class Form {

		public function __construct ( $errors, $values ) {
			$this->errors = $errors;
			$this->values = $values;
		}
	
		public function input ( $type, $set, $name, $label = null ) {
			$label = ( is_null( $label ) ? ucwords( str_replace( '_', ' ', $name ) ) : $label );
			$iname =  $set . '_' . $name;
			$value = isset($this->values[$iname]) ? $this->values[$iname] : '';
			print '<label for="' . $iname . '">'. $label . ':</label> <input type="' . $type . '" id="'. $iname . '" name="' . $iname . '" value="' . ( ( 'password' == $type ) ? '' : $value ) . '"/>';
			if( isset( $this->errors[$iname] ) )
				print '<div class="install-error">' . $this->errors[$iname] . '</div>';
			print '<br/>';
		}
	
		public function text ( $set, $name, $label = null ) {
			$this->input( 'text', $set, $name, $label );
		}
		
		public function password ( $set, $name, $label = null ) {
			$this->input( 'password', $set, $name, $label );
		}
		
	} // Class Form
	
	class Database {
	
		protected static $link = null;
		
		public static function connect ( $host, $db, $user, $password ) {
			$link = @mysql_connect( $host, $user, $password );
			if( ! $link )
				return 'Could not connect to host: ' . mysql_error();

			if( ! @mysql_select_db( $db, $link ) )
				return 'Could not select database: ' . mysql_error();
			
			self::$link = $link;
			
			return true;
		}
		
		public static function RunFile ( $file, $substitutions = array() ) {
			
			if( null == self::$link )
				return 'Not connected to a database.';
			
			if( ! file_exists( $file ) )
				return 'File does not exist: ' . $file;
			
			$data = file_get_contents( $file );
			foreach( $substitutions as $key => $value )
				$data = str_replace( "[:$key]", mysql_real_escape_string( $value, self::$link ), $data );

			$queries = explode( ';', $data );
			
			foreach( $queries as $query ) {
				$query = trim( $query );
				if( empty( $query ) )
					continue;

				if( false === mysql_query( trim( $query ), self::$link ) )
					return mysql_error();
			}
			
			return true;
		}
		
		public static function RunFolder ($folder, $substitutions = array() ) {
			if (!is_dir($folder)) {
				return "Not a folder: $folder.";
			}
			
			if (!$handle = opendir($folder)) {
				return "Could not open folder $folder.";
			}
			
		    while (false !== ($file = readdir($handle))) {
		    	if ($file != "." && $file != "..") {
        			$res = Database::RunFile($folder . "/" . $file, $substitutions);
        			if (true !== $res) {
        				return $res;
        			}
		    	}
    		}

    		closedir($handle);
    		
    		return true;
		}
		
	} // Class Database
	
	class Config {
	
		public static function RenderFile ( $template_file, $substitutions ) {
		
			if( ! file_exists( $template_file ) )
				return false;

			$data = file_get_contents( $template_file );
			foreach( $substitutions as $key => $value )
				$data = str_replace( "[:$key]", $value, $data );

			return $data;
		}
		
		public static function SaveFile ( $template_file, $dest, $substitutions ) {
			$contents = Config::RenderFile( $template_file, $substitutions );

			if( false === $contents )
				return false;
			return ( false !== @file_put_contents( $dest, $contents ) );
		}
		
	}
