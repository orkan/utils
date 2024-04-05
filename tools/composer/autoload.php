<?php
/**
 * @link https://github.com/sebastianbergmann/phpunit/blob/main/phpunit
 */
// if ( !in_array( PHP_SAPI, [ 'cli', 'phpdbg', 'embed' ], true ) ) {
// 	fwrite( STDERR, sprintf( "Warning:\nThe console should be invoked via the CLI version of PHP, not the %s SAPI\n\n", PHP_SAPI ) );
// }

if ( isset( $GLOBALS['_composer_autoload_path'] ) ) {
	define( 'ORKAN_COMPOSER_INSTALL', $GLOBALS['_composer_autoload_path'] );
	unset( $GLOBALS['_composer_autoload_path'] );
}
else {
	foreach([
		__DIR__ . '/../../../../autoload.php',     // [project]/vendor/orkan/utils/tools/composer
		__DIR__ . '/../../../vendor/autoload.php', // [project]/rel/tools/composer
		] as $file ) {
		if ( file_exists( $file ) ) {
			define( 'ORKAN_COMPOSER_INSTALL', $file );
			break;
		}
	}
	unset( $file );
}

if ( !defined( 'ORKAN_COMPOSER_INSTALL' ) ) {
	fwrite( STDERR, "You need to set up the project dependencies using Composer:\n\n\tcomposer install orkan/utils\n\n" );
	die( 1 );
}

require ORKAN_COMPOSER_INSTALL;
