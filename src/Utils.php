<?php
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
	 * Remove double spaces from PHP::print_r()
	 *
	 * @param array $array
	 * @param bool $simple Remove objects?
	 * @return string
	 */
	public static function print_r( array $array, bool $simple = true ): string
	{
		/*
		 * Replace each Object in array with class name string
		 *
		 * @formatter:off */
		if( $simple ) {
			array_walk_recursive( $array, function( &$val ) {
				is_object( $val ) && $val = '(Object) ' . get_class( $val );
			});
		}
		/* @formatter:on */

		$str = print_r( $array, true );
		return preg_replace( '/[ ]{2,}/', '', $str ); // remove double spacess
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
		if ( defined( 'TESTING' ) ) {
			$date = \DateTime::createFromFormat( 'U.u', microtime( true ) )->format( 'Y-m-d H:i:s.u' );
			$line = sprintf( '[%s] %s', $date, $message );
			file_put_contents( __DIR__ . '/../tests/_cache/TESTING-Orkan-Utils-print.log', $line, FILE_APPEND );
			return;
		}

		if ( 'cli' === php_sapi_name() ) {
			fwrite( $is_error ? STDERR : STDOUT, iconv( 'utf-8', $codepage, $message ) );
		}
		else {
			echo $message;
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
}
