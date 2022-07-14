<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-@Year@ Orkan <orkans@gmail.com>
 */
namespace Orkan;

/**
 * Helper functions
 *
 * @author Orkan
 */
class Utils
{

	/**
	 * Format byte size string
	 * Examples: 361 bytes | 1016.1 kB | 14.62 Mb | 2.81 GB
	 *
	 * @param int $bytes Size in bytes
	 * @return string Byte size string
	 */
	public static function formatBytes( int $bytes = 0 ): string
	{
		$sizes = array( 'bytes', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );
		return $bytes ? ( round( $bytes / pow( 1024, ( $i = floor( log( $bytes, 1024 ) ) ) ), $i > 1 ? 2 : 1 ) . ' ' . $sizes[$i] ) : '0 ' . $sizes[0];
	}

	/**
	 * Convert size string to bytes
	 * Examples: 123 -> 123, 1kB -> 1024, 1M -> 1048576
	 *
	 * @link https://stackoverflow.com/questions/11807115/php-convert-kb-mb-gb-tb-etc-to-bytes
	 *
	 * @param int $bytes Size in bytes
	 * @return string Byte size string
	 */
	public static function toBytes( string $str ): int
	{
		$m = [];
		if ( !preg_match( '/^([\d.]+)(\s)?([BKMGTPE]?)(B)?$/i', trim( $str ), $m ) ) return 0;
		return (int) floor( $m[1] * ( $m[3] ? ( 1024 ** strpos( 'BKMGTPE', strtoupper( $m[3] ) ) ) : 1 ) );
	}

	/**
	 * Finally the formatNumber() unified method
	 */
	public static function formatNumber( float $number = 0, int $decimals = 0, string $point = '.', string $sep = ' ' ): string
	{
		return number_format( $number, $decimals, $point, $sep );
	}

	/**
	 * Format time
	 *
	 * @param float $seconds Time in fractional seconds
	 * @param bool $fractions Add fractions part?
	 * @param int $precision How many fractional digits?
	 * @return string Time in format 18394d 16g 11m 41.589s
	 */
	public static function formatTime( float $seconds, bool $fractions = true, int $precision = 6 ): string
	{
		$d = $h = $m = 0;
		$s = (int) $seconds; // get int
		$u = $seconds - $s; // get fractions

		if ( $s >= 86400 ) {
			$d = floor( $s / 86400 );
			$s = floor( $s % 86400 );
		}
		if ( $s >= 3600 ) {
			$h = floor( $s / 3600 );
			$s = floor( $s % 3600 );
		}
		if ( $s >= 60 ) {
			$m = floor( $s / 60 );
			$s = floor( $s % 60 );
		}
		$s = $fractions ? sprintf( "%.{$precision}f", $s + $u ) : $s;
		return trim( ( $d ? "{$d}d " : '' ) . ( $h ? "{$h}h " : '' ) . ( $m ? "{$m}m " : '' ) . "{$s}s" );
	}

	/**
	 * Fancy array print
	 *
	 * @param bool  $simple Remove objects?
	 * @param array $keys   Keys replacements
	 */
	public static function print_r( array $array, bool $simple = true, array $keys = [] ): string
	{
		foreach ( $array as $k => $v ) {
			// Replace bolean values
			if ( is_bool( $v ) ) {
				$array[$k] = $v ? 'true' : 'false';
			}
			// Replace each Object in array with class name string
			else if ( $simple && is_object( $v ) ) {
				$array[$k] = '(Object) ' . get_class( $v );
			}
		}

		// Replace keys
		if ( $keys ) {
			$new = [];
			foreach ( $array as $k => $v ) {
				$key = $keys[$k] ?? $k; // Missing replacement - use old key!
				$new[$key] = $v;
			}
			$array = $new;
		}

		$str = print_r( $array, true );
		$str = preg_replace( '/[ ]{2,}/', '', $str ); // remove double spacess

		return $str;
	}

	/**
	 * Print message to standard output or STDERR if in CLI mode
	 * Notes:
	 * STDOUT and echo both seems to work in CLI
	 * STDERR is buffered and displays last
	 *
	 * @codeCoverageIgnore
	 *
	 * @param string $message
	 * @param bool $is_error Choose the right I/O stream for outputing errors
	 * @param string $codepage
	 */
	public static function print( string $message, bool $is_error = false, string $codepage = 'cp852' ): void
	{
		/*
		 * Deprecated due the fact that Utils now are tested from parents projects instead
		 * and we are unable to locate a writable dir
		 *
		 if ( defined( 'TESTING' ) ) {
		 $date = \DateTime::createFromFormat( 'U.u', microtime( true ) )->format( 'Y-m-d H:i:s.u' );
		 $line = sprintf( '[%s] %s', $date, $message );
		 file_put_contents( __DIR__ . '/../tests/_cache/TESTING-Orkan-Utils-print.log', $line, FILE_APPEND );
		 return;
		 }
		 */

		/**
		 * Note:
		 * In CLI the constants STDIN, STDOUT, STDERR are undefined. A workaround is to re-define them:
		 * @link https://stackoverflow.com/questions/17769041/notice-use-of-undefined-constant-stdout-assumed-stdout
		 * if(!defined('STDIN'))  define('STDIN',  fopen('php://stdin',  'rb'));
		 * if(!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'wb'));
		 * if(!defined('STDERR')) define('STDERR', fopen('php://stderr', 'wb'));
		 */
		if ( in_array( PHP_SAPI, [ 'cli', 'phpdbg', 'embed' ], true ) ) {
			fwrite( $is_error ? STDERR : STDOUT, iconv( 'utf-8', $codepage, $message ) );
		}
		else {
			echo nl2br( $message );
		}
	}

	/**
	 * Print message to STDERR
	 *
	 * @codeCoverageIgnore
	 *
	 * @param string $message
	 * @param string $codepage
	 */
	public static function stderr( string $message, string $codepage = 'cp852' ): void
	{
		self::print( $message, true, $codepage );
	}

	/**
	 * PHP function to make slug (URL string)
	 * This was based off the one in Symfony's Jobeet tutorial.
	 *
	 * @link https://stackoverflow.com/questions/2955251/php-function-to-make-slug-url-string
	 * @param string $text
	 * @return string
	 */
	public static function slugify( string $text ): string
	{
		// replace non letter or digits by -
		$text = preg_replace( '~[^.\pL\d]+~u', '-', $text );

		// transliterate
		$text = iconv( 'utf-8', 'us-ascii//TRANSLIT', $text );

		// remove unwanted characters
		$text = preg_replace( '~[^-.\w]+~', '', $text );

		// trim
		$text = trim( $text, '-' );

		// remove duplicate -
		$text = preg_replace( '~-+~', '-', $text );

		// lowercase
		$text = strtolower( $text );

		if ( empty( $text ) ) {
			return 'n-a';
		}

		return $text;
	}

	/**
	 * Some fancy implode
	 * Example: 'aaa', 'bbb', 'ccc'
	 *
	 * @param array $arr
	 * @param string $sep
	 * @param string $start
	 * @param string $end
	 * @return string
	 */
	public static function implode( array $arr, string $sep = ', ', string $start = "'", string $end = "'" ): string
	{
		return $start . implode( $end . $sep . $start, $arr ) . $end;
	}

	/**
	 * Compute absolute path from relative at base
	 *
	 * @param string $path Relative path
	 * @param string $base Base dir for $path
	 * @return string|bool Realpath or false if not found
	 */
	public static function pathToAbs( string $path, string $base )
	{
		$old = getcwd();
		chdir( $base );
		$result = realpath( $path );
		chdir( $old );
		return $result;
	}

	/**
	 * Get last key of given array
	 *
	 * @param array $arr
	 * @return mixed
	 */
	public static function lastKey( array &$arr )
	{
		return key( array_slice( $arr, -1, null, true ) ); // 4th param - preserve numeric keys!
	}

	/**
	 * Format two timestamps with timezone
	 * Calculate time diff
	 *
	 * @param int $time Timestamp A
	 * @param int $stop Timestamp B
	 * @param string $zone @link https://www.php.net/manual/en/timezones.php
	 * @param array $format['begin', 'final', 'diff']
	 * @link https://www.php.net/manual/en/datetime.format.php
	 * @link https://www.php.net/manual/en/dateinterval.format.php
	 * @return array Formated dates: Array ( 'begin' => ... , 'final' => ..., 'diff' => ... )
	 */
	public static function formatDateDiff( int $time, int $stop, string $zone, array $format = [] ): array
	{
		$out = [];

		$Tzone = new \DateTimeZone( $zone );
		$begin = ( new \DateTime() )->setTimestamp( $time )->setTimezone( $Tzone );
		$final = ( new \DateTime() )->setTimestamp( $stop )->setTimezone( $Tzone );

		$out['begin'] = $begin->format( $format[0] ?? 'l, d.m.Y H:i');
		$out['final'] = $final->format( $format[1] ?? 'l, d.m.Y H:i');

		if ( !isset( $format[2] ) || '%a' === $format[2] ) {
			$begin->setTime( 0, 0 ); // Count full days!
			$final->setTime( 0, 0 );
		}

		$out['diff'] = $final->diff( $begin )->format( $format[2] ?? '%a');

		return $out;
	}

	/**
	 * Increment array element
	 */
	public static function keyIncrement( &$arr, $key )
	{
		$arr[$key] = isset( $arr[$key] ) ? $arr[$key] + 1 : 1;
	}

	/**
	 * Collator::sort()
	 *
	 * @param array $arr
	 * @param string $locale
	 */
	public static function sort( array &$arr, string $locale ): void
	{
		$Collator = collator_create( $locale );
		collator_sort( $Collator, $arr );
	}

	/**
	 * Missing Collator::ksort()
	 *
	 * @param array $arr
	 * @param string $locale
	 */
	public static function ksort( array &$arr, string $locale ): void
	{
		$out = [];
		$keys = array_keys( $arr );
		$Collator = collator_create( $locale );
		collator_sort( $Collator, $keys );
		foreach ( $keys as $k ) {
			$out[$k] = $arr[$k];
		}
		$arr = $out;
	}

	/**
	 * Compute execution time
	 *
	 * @param int|float $start Previous time in nanoseconds or leave empty to get new one
	 * @return float Time diff in frac seconds
	 */
	public static function exectime( $start = 0 )
	{
		$time = hrtime( true );

		if ( 0 == $start ) {
			return $time;
		}

		$end = $time - $start;
		$end = $end / 1e+9; // nanoseconds to seconds 0.123456789
		return $end;
	}

	/**
	 * Throw Exception if false
	 *
	 * @param mixed $result PHP function results
	 * @param string $message
	 * @param int $code
	 * @throws \Exception
	 */
	public static function checkError( $result, string $message, int $code = 1 ): void
	{
		if ( false === $result ) {
			$e = error_get_last();
			$m = $message;
			$m .= sprintf( "\nPHP error (#%d): %s", $e['type'], $e['message'] );
			$m .= defined( 'DEBUG' ) ? sprintf( ' in %s:%d', $e['file'], $e['line'] ) : '';
			throw new \Exception( $m, $code );
		}
	}

	/**
	 * Get definition of last JSON error
	 */
	public static function getJsonLastError(): string
	{
		/* @formatter:off */
		static $jsonError = [
			JSON_ERROR_DEPTH				 => ['JSON_ERROR_DEPTH',				 'The maximum stack depth has been exceeded'],
			JSON_ERROR_STATE_MISMATCH		 => ['JSON_ERROR_STATE_MISMATCH',		 'Invalid or malformed JSON'],
			JSON_ERROR_CTRL_CHAR			 => ['JSON_ERROR_CTRL_CHAR',			 'Control character error, possibly incorrectly encoded'],
			JSON_ERROR_SYNTAX				 => ['JSON_ERROR_SYNTAX',				 'Syntax error'],
			JSON_ERROR_UTF8					 => ['JSON_ERROR_UTF8',					 'Malformed UTF-8 characters, possibly incorrectly encoded'],
			JSON_ERROR_RECURSION			 => ['JSON_ERROR_RECURSION',			 'One or more recursive references in the value to be encoded'],
			JSON_ERROR_INF_OR_NAN			 => ['JSON_ERROR_INF_OR_NAN',			 'One or more NAN or INF values in the value to be encoded'],
			JSON_ERROR_UNSUPPORTED_TYPE		 => ['JSON_ERROR_UNSUPPORTED_TYPE',		 'A value of a type that cannot be encoded was given'],
			JSON_ERROR_INVALID_PROPERTY_NAME => ['JSON_ERROR_INVALID_PROPERTY_NAME', 'A property name that cannot be encoded was given'],
			JSON_ERROR_UTF16				 => ['JSON_ERROR_UTF16',				 'Malformed UTF-16 characters, possibly incorrectly encoded'],
		];
		/* @formatter:on */

		$result = '';

		if ( JSON_ERROR_NONE != $lastError = json_last_error() ) {
			$key = $jsonError[$lastError][0] ?? '???';
			$msg = $jsonError[$lastError][1] ?? 'Definition missing for error #' . $lastError;
			$result = "$key: $msg";
		}

		return $result;
	}

	/**
	 * Get file extension
	 *
	 * @param string $file Name / path
	 * @return string Extension
	 */
	public static function fileExt( string $file ): string
	{
		return strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
	}

	/**
	 * Get file path without extension
	 *
	 * @param string $file Name / path
	 * @return string Base path with no extension
	 */
	public static function fileNoExt( string $file ): string
	{
		return preg_replace( '~(\.[^\.]+)$~', '', $file );
	}

	/**
	 * Generate path from BASE + array elements
	 *
	 * @param string $base Home dir
	 * @param array $elements Path elements
	 * @return string
	 */
	public static function buildPath( string $base, array $elements )
	{
		return $base . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $elements );
	}

	/**
	 * Remove file. Wilcards possible
	 *
	 * @param  string  $file
	 */
	// 	public static function removeFile( $file )
	// 	{
	// 		$file = str_replace( '\\', '/', $file );
	// 		$file = str_replace( '/', DIRECTORY_SEPARATOR, $file );

	// 		$cmd = defined( 'PHP_WINDOWS_VERSION_BUILD' ) ? 'del /Q %s' : 'rm -f %s';
	// 		$cmd = sprintf( $cmd, $file );
	// 		shell_exec( $cmd );

	// 		// Cannot suppress Windows message: Could Not Find ... on missing files
	// 	}

	/**
	 * Recursively remove a directory
	 */
	public static function removeDirectory( $directory )
	{
		$cmd = defined( 'PHP_WINDOWS_VERSION_BUILD' ) ? 'rd /S /Q "%s"' : 'rm -rf "%s"';
		$cmd = sprintf( $cmd, realpath( $directory ) );
		shell_exec( $cmd );

		return !is_dir( $directory );
	}

	/**
	 * Clear and re-create a directory
	 */
	public static function clearDirectory( $directory )
	{
		if ( is_dir( $directory ) ) {
			self::removeDirectory( $directory );
			clearstatcache( true );
		}

		return mkdir( $directory, 0777, true );
	}

	/**
	 * Recursively copy a directory
	 *
	 * @param  string  $source
	 * @param  string  $destination
	 * @return bool
	 */
	public static function copyDirectory( $source, $destination )
	{
		$cmd = defined( 'PHP_WINDOWS_VERSION_BUILD' ) ? 'xcopy /E  %s %s' : 'cp -R %s %s';
		$cmd = sprintf( $cmd, realpath( $source ), realpath( $destination ) );
		shell_exec( $cmd );

		return true;
	}

	/**
	 * Randomize array. Keep key assigments!
	 * @link https://www.php.net/manual/en/function.uniqid.php
	 */
	public static function shuffleArray( array &$arr ): bool
	{
		if ( empty( $arr ) ) {
			return false;
		}

		$out = [];

		// Generate qnique keys
		foreach ( $arr as $k => $v ) {
			$key = random_bytes( 8 );
			$key = bin2hex( $key );
			$out[$key] = [ $k, $v ];
		}

		// Sort == randomize ;)
		ksort( $out );

		// Recreate key assigments
		$arr = [];
		foreach ( $out as $v ) {
			$arr[$v[0]] = $v[1];
		}

		return true;
	}

	/**
	 * Sorts multi-dimensional array by sub-array key
	 * Maintains key assigments!
	 *
	 * @param string $sort Field name to sort by (orig|path|name)
	 * @param string $dir  Sort direction (asc|desc)
	 * @return bool
	 */
	public static function sortMultiArray( array &$arr, string $sort = 'name', string $dir = 'asc' ): bool
	{
		if ( empty( $arr ) || !isset( $arr[0][$sort] ) ) {
			return false;
		}

		uasort( $arr, function ( $a, $b ) use ($sort, $dir ) {

			if ( is_int( $a[$sort] ) ) {
				$cmp = $a[$sort] < $b[$sort] ? -1 : 1;
			}
			else {
				$cmp = strcasecmp( $a[$sort], $b[$sort] );
			}

			// Keep ASC sorting for unknown [dir]
			return 'desc' == $dir ? -$cmp : $cmp;
		} );

		return true;
	}

	/**
	 * Render Progress bar
	 *
	 * @param number $current
	 * @param number $total
	 * @param number $size
	 * @param string $cchar
	 * @param string $tchar
	 * @return array [ 'bar' => |||-------, 'cent' => 36 ]
	 */
	public static function progressBar( int $current, int $total, int $size = 20, string $cchar = '|', string $tchar = '-' ): array
	{
		$progres = round( ( 100 / $total ) * $current );

		$_total = $size;
		$_current = round( ( $size * $current ) / $total );

		$bar = str_repeat( $cchar, $_current );
		$bar .= str_repeat( $tchar, $_total - $_current );

		return [ 'bar' => $bar, 'cent' => $progres ];
	}

	/**
	 * Returns an associative array of defined constants in format: [int] => 'CONSTANT_NAME'
	 * @see get_defined_constants()
	 *
	 * @param string $prefix   Regex pattern to match constant names
	 * @param string $category Lookup in this group only
	 */
	public static function getDefinedConstants( string $prefix, string $category = '' ): array
	{
		$constants = $category ? get_defined_constants( true )[$category] ?? []: get_defined_constants();

		$keys = preg_grep( "/^{$prefix}/", array_keys( $constants ) );
		$constants = array_intersect_key( $constants, array_flip( $keys ) );
		$constants = array_flip( $constants );

		return $constants;
	}
}
