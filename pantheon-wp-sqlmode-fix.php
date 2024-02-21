<?php
/*
Plugin Name: Pantheon SQL Modes Fix
Description: Forcing to set additional sql_modes to be enforced in wp db overriding the default. This includes ANSI_QUOTES in attempt to improve db performance. 
Version: 0.1
Author: Pantheon Professional Services Application Performance Team
Author URI: https://pantheon.io
Related internal tickets:
https://getpantheon.atlassian.net/browse/PSAPP-857
https://getpantheon.atlassian.net/browse/BUGS-7443
*/

// Custom function to add additional incompatible modes
function custom_additional_modes( $incompatible_modes ) {
	// Add your additional incompatible modes here
	$additional_modes = array(
		'REAL_AS_FLOAT',
		'PIPES_AS_CONCAT',
		'ANSI_QUOTES',
		'IGNORE_SPACE',
		'ONLY_FULL_GROUP_BY',
		'STRICT_TRANS_TABLES',
		'STRICT_ALL_TABLES',
		'NO_ZERO_IN_DATE',
		'ERROR_FOR_DIVISION_BY_ZERO',
		'NO_ENGINE_SUBSTITUTION',
		'NO_AUTO_VALUE_ON_ZERO',
	);

	// Merge the additional modes with the existing ones
	$incompatible_modes = array_merge( $incompatible_modes, $additional_modes );

	return $incompatible_modes;
}

// Hook the custom function into the 'incompatible_sql_modes' filter
// This do not work as the existing hooks of wp applies only with the Mysql session and no global
// add_filter( 'incompatible_sql_modes', 'custom_additional_modes' );

/**
 * Get the current SQL modes from the database.
 *
 * @return array|false Array of current SQL modes or false on failure.
 */
function get_current_sql_modes() {
	global $wpdb;

	// Ensure the database class is available
	if ( ! class_exists( 'wpdb' ) ) {
		return false;
	}

	// Get the current SQL modes from the database
	$sql_modes = $wpdb->get_var( 'SELECT @@GLOBAL.sql_mode global, @@SESSION.sql_mode session;' );

	// Check for errors
	if ( $wpdb->last_error ) {
		return false;
	}

	// Convert the string of SQL modes into an array
	$current_modes = explode( ',', $sql_modes );

	return $current_modes;
}

/**
 * Undocumented function
 *
 * @return void
 */
function set_session_sql_modes() {
	$session_modes =
		'REAL_AS_FLOAT,
		PIPES_AS_CONCAT,
		ANSI_QUOTES,
		IGNORE_SPACE,
		ONLY_FULL_GROUP_BY,
		STRICT_TRANS_TABLES,
		STRICT_ALL_TABLES,
		NO_ZERO_IN_DATE,
		ERROR_FOR_DIVISION_BY_ZERO,
		NO_ENGINE_SUBSTITUTION,
		NO_AUTO_VALUE_ON_ZERO';

	if ( defined( 'PDO::MYSQL_ATTR_INIT_COMMAND' ) ) {
		$dsn     = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
		$options = array(
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET SESSION sql_mode="' . $session_modes . '"',
		);

		try {
			$pdo = new PDO( $dsn, DB_USER, DB_PASSWORD, $options );
		} catch ( PDOException $e ) {
			// Handle connection error
			return;
		}
	}
}

// Hook the function into the 'init' action to set modes on each page load
add_action( 'init', 'set_session_sql_modes' );

// Function to set SQL modes globally
function set_global_sql_modes() {
	$global_modes = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION,NO_AUTO_VALUE_ON_ZERO';

	$query = "SET GLOBAL sql_mode = '$global_modes'";

	global $wpdb;

	// Ensure the database class is available
	if ( ! class_exists( 'wpdb' ) ) {
		return;
	}

	// Set global modes using the $wpdb object
	$wpdb->query( $query );
}

// Hook the function into the 'init' action to set global modes on each page load
add_action( 'init', 'set_global_sql_modes' );
