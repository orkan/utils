<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * Helper functions.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Utils
{
	/**
	 * Time constants.
	 */
	const MINUTE = 60;
	const HOUR = 3600;
	const DAY = 86400;
	const WEEK = 604800;
	const MONTH = 2678400;
	const YEAR = 31536000;

	/**
	 * Default global properties.
	 * @see Utils::setup()
	 */
	protected static $execTime = 0;
	protected static $timeZone = 'UTC';
	protected static $dateFormat = 'Y-m-d H:i:s';

	/**
	 * ErrorException code.
	 * @see Utils::errorHandler()
	 */
	protected static $errorExceptionCode = 0;

	public function __construct()
	{
		static::$execTime = static::exectime();
	}

	/**
	 * Configure static properties.
	 */
	public static function setup( array $cfg = [] )
	{
		foreach ( $cfg as $property => $value ) {
			if ( isset( static::$$property ) ) {
				static::$$property = $value;
			}
		}
	}

	/*
	 * =================================================================================================================
	 */

	/**
	 * Flatten multi-dimensional array.
	 */
	public static function arrayFlat( array $a ): array
	{
		return iterator_to_array( new \RecursiveIteratorIterator( new \RecursiveArrayIterator( $a ) ), false );
	}

	/**
	 * Fancy implode.
	 * Example: 'aaa', 'bbb', 'ccc'
	 */
	public static function arrayImplode( array $arr, string $sep = ', ', string $start = "'", string $end = "'" ): string
	{
		return $start . implode( $end . $sep . $start, $arr ) . $end;
	}

	/**
	 * Increment array element.
	 */
	public static function arrayInc( &$arr, $key )
	{
		$arr[$key] = isset( $arr[$key] ) ? $arr[$key] + 1 : 1;
	}

	/**
	 * Get array last key.
	 */
	public static function arrayLastKey( array &$arr )
	{
		return key( array_slice( $arr, -1, null, true ) ); // 4th param - preserve numeric keys!
	}

	/**
	 * Merge exploded values.
	 *
	 * This is mainly used for HTTP headers created in format: 'key: value'.
	 * If two or more headers starts with 'key: ...' the resulting header is: 'key: val1, val2, ...'
	 * This method preserves only the last value, eg. 'key: val2'
	 *
	 * Example: arrayMergeValues(': ', Array( 'a: aaa', 'b: bbb' ), Array( 'a: xxx' ) )
	 * Results: Array( 'a:xxx', 'b:bbb' )
	 */
	public static function arrayMergeValues( array $a1, array $a2, string $delimiter = ': ' ): array
	{
		if ( !$a1 ) {
			return $a2;
		}

		if ( !$a2 ) {
			return $a1;
		}

		$tmp = $out = [];

		foreach ( array_merge( $a1, $a2 ) as $value ) {
			$a = explode( $delimiter, $value );
			$tmp[$a[0]] = $a[1] ?? '';
		}

		foreach ( $tmp as $k => $v ) {
			$out[] = $k . $delimiter . $v;
		}

		return $out;
	}

	/**
	 * Randomize array (preserve keys).
	 *
	 * Algorithm: Fisher-Yates (aka Knuth) Shuffle
	 * Visualization: http://bost.ocks.org/mike/shuffle/
	 * @link https://stackoverflow.com/questions/2450954/how-to-randomize-shuffle-a-javascript-array
	 */
	public static function arrayShuffle( array &$arr ): bool
	{
		$out = [];
		foreach ( $arr as $k => $v ) {
			$out[] = [ $k, $v ];
		}

		// Swap: out[i] <==> out[j]
		for ( $i = count( $out ) - 1; $i > 0; $i-- ) {
			$j = rand( 0, $i );
			if ( $j !== $i ) {
				$tmp = $out[$j];
				$out[$j] = $out[$i];
				$out[$i] = $tmp;
			}
		}

		// Restore keys
		$arr = [];
		foreach ( $out as $v ) {
			$arr[$v[0]] = $v[1];
		}

		return true;
	}

	/**
	 * Sort array with locale.
	 *
	 * @param string $locale Eg. 'us_US', 'pl_PL'
	 */
	public static function arraySort( array &$arr, string $locale ): void
	{
		$Collator = collator_create( $locale );
		collator_sort( $Collator, $arr );
	}

	/**
	 * Sort array keys with locale.
	 *
	 * @param array $arr
	 * @param string $locale
	 */
	public static function arraySortKey( array &$arr, string $locale ): void
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
	 * Sort multi-dimensional array by sub-array key (preserve keys).
	 *
	 * @param  string $sort Field name to sort by (orig|path|name)
	 * @param  bool   $asc  Ascending direction
	 */
	public static function arraySortMulti( array &$arr, string $sort = 'name', bool $asc = true ): bool
	{
		if ( empty( $arr ) || !isset( current( $arr )[$sort] ) ) {
			return false;
		}

		return uasort( $arr, function ( $a, $b ) use ($sort, $asc ) {

			if ( is_int( $a[$sort] ) || is_bool( $a[$sort] ) ) {
				$cmp = $a[$sort] - $b[$sort];
			}
			else {
				$cmp = strcasecmp( $a[$sort], $b[$sort] );
			}

			// Keep ASC sorting for unknown [dir]
			return $asc ? $cmp : -$cmp;
		} );
	}

	/**
	 * Convert size string to bytes.
	 * Examples: 123 -> 123, 1kB -> 1024, 1M -> 1048576
	 *
	 * @link https://stackoverflow.com/questions/11807115/php-convert-kb-mb-gb-tb-etc-to-bytes
	 *
	 * @param  string $str Byte size string
	 * @return int Size in bytes
	 */
	public static function byteNumber( string $str ): int
	{
		$m = [];
		if ( !preg_match( '/^([\d.]+)(\s)?([BKMGTPE]?)(B)?$/i', trim( $str ), $m ) ) return 0;
		return (int) floor( $m[1] * ( $m[3] ? ( 1024 ** strpos( 'BKMGTPE', strtoupper( $m[3] ) ) ) : 1 ) );
	}

	/**
	 * Format byte size string.
	 * Examples: 361 bytes | 1016.1 kB | 14.62 Mb | 2.81 GB
	 *
	 * @param  int $bytes Size in bytes
	 * @return string Byte string formated
	 */
	public static function byteString( int $bytes = 0 ): string
	{
		$sizes = array( 'bytes', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );
		return $bytes ? ( round( $bytes / pow( 1024, ( $i = floor( log( $bytes, 1024 ) ) ) ), $i > 1 ? 2 : 1 ) . ' ' . $sizes[$i] ) : '0 ' . $sizes[0];
	}

	/**
	 * Return an associative array of defined constants in format: [int] => 'CONSTANT_NAME'
	 *
	 * @see get_defined_constants()
	 *
	 * @param string $prefix   Regex pattern to match constant names
	 * @param string $category Lookup in this group only
	 */
	public static function constants( string $prefix, string $category = '' ): array
	{
		$constants = $category ? get_defined_constants( true )[$category] ?? []: get_defined_constants();

		$keys = preg_grep( "/^{$prefix}/", array_keys( $constants ) );
		$constants = array_intersect_key( $constants, array_flip( $keys ) );
		$constants = array_flip( $constants );

		return $constants;
	}

	/**
	 * Format diff timestamps with timezone.
	 *
	 * @link https://www.php.net/manual/en/timezones.php
	 * @link https://www.php.net/manual/en/datetime.format.php
	 * @link https://www.php.net/manual/en/dateinterval.format.php
	 *
	 * @param int    $begin  Timestamp A
	 * @param int    $final  Timestamp B
	 * @param string $tzone  Timezone
	 * @param array  $format Array ( [begin] => 'l, d.m.Y H:i' , [final] => 'l, d.m.Y H:i', [diff] => '%a' )
	 * @return array         Formated dates: Array ( [begin] => date , [final] => date, [diff] => days )
	 */
	public static function dateStringDiff( int $begin, int $final = 0, string $tzone = '', array $format = [] ): array
	{
		$out = [];

		$final = $final ?: time();
		$TZone = new \DateTimeZone( $tzone ?: self::$timeZone );

		$Begin = ( new \DateTime() )->setTimestamp( $begin )->setTimezone( $TZone );
		$Final = ( new \DateTime() )->setTimestamp( $final )->setTimezone( $TZone );

		$out['begin'] = $Begin->format( $format[0] ?? 'l, d.m.Y H:i');
		$out['final'] = $Final->format( $format[1] ?? 'l, d.m.Y H:i');

		if ( !isset( $format[2] ) || '%a' === $format[2] ) {
			$Begin->setTime( 0, 0 ); // Count full days!
			$Final->setTime( 0, 0 );
		}

		$out['diff'] = $Final->diff( $Begin )->format( $format[2] ?? '%a');

		return $out;
	}

	/**
	 * Format date.
	 */
	public static function dateString( float $timestamp = 0, string $format = '', string $zone = '' ): string
	{
		$timestamp = $timestamp ?: time();

		// Remove fractions from timestamp eg. 1588365133[974]
		if ( strlen( $timestamp ) > $len = strlen( time() ) ) {
			$timestamp = substr( $timestamp, 0, $len );
		}

		/* @formatter:off */
		$date = ( new \DateTime() )
			->setTimestamp( $timestamp )
			->setTimezone( new \DateTimeZone( $zone ?: self::$timeZone ) )
			->format( $format ?: self::$dateFormat );
		/* @formatter:on */

		return $date;
	}

	/**
	 * Dir clear.
	 */
	public static function dirClear( string $dir ): bool
	{
		if ( self::dirRemove( $dir ) ) {
			return mkdir( $dir, 0777, true );
		}

		return false;
	}

	/**
	 * Dir copy (recursively).
	 */
	public static function dirCopy( string $src, string $dst ): ?string
	{
		$cmd = defined( 'PHP_WINDOWS_VERSION_BUILD' ) ? 'xcopy /E  "%s" "%s"' : 'cp -R "%s" "%s"';
		$cmd = sprintf( $cmd, realpath( $src ), realpath( $dst ) );

		return shell_exec( $cmd );
	}

	/**
	 * Dir remove.
	 */
	public static function dirRemove( string $dir ): bool
	{
		if ( $abs = realpath( $dir ) ) {
			$cmd = defined( 'PHP_WINDOWS_VERSION_BUILD' ) ? 'rmdir /S /Q "%s"' : 'rm -rf "%s"';
			$cmd = sprintf( $cmd, $abs );
			shell_exec( $cmd );
			clearstatcache( true );
		}

		return is_dir( $dir ) ? false : true;
	}

	/**
	 * Check PHP function error result.
	 *
	 * Most PHP functions returns false on errors. In that case get last PHP error and throw Exception.
	 *
	 * @param mixed  $result PHP function results to check
	 * @param string $prefix Prefix Exception message
	 * @param int    $code   Use Exception code
	 * @throws \Exception
	 */
	public static function errorCheck( $result, string $prefix, int $code = 1 ): void
	{
		if ( false === $result ) {
			$e = error_get_last();
			$m = $prefix;
			$m .= sprintf( "\nPHP error (#%d): %s", $e['type'], $e['message'] );
			$m .= defined( 'DEBUG' ) ? sprintf( ' in %s:%d', $e['file'], $e['line'] ) : '';
			throw new \Exception( $m, $code );
		}
	}

	/**
	 * Turn PHP error message into ErrorException.
	 *
	 * NOTE:
	 * In case of error it will self-restore previous error handler!
	 *
	 * Use case:
	 * set_error_handler( [ Utils::class, 'errorHandler' ] );
	 * ... risky code ...
	 * restore_error_handler(); // no errors, so restore handler manually or keep?!?
	 *
	 * @link https://www.php.net/manual/en/class.errorexception.php
	 * @link https://www.php.net/manual/en/language.exceptions.php
	 * @link https://stackoverflow.com/questions/1241728/can-i-try-catch-a-warning
	 */
	public static function errorHandler( $severity, $message, $filename, $lineno ): void
	{
		/**
		 * This error code is not included in error_reporting
		 * or error was suppressed with the @-operator
		 */
		if ( !( error_reporting() & $severity ) ) {
			return;
		}

		restore_error_handler();
		throw new \ErrorException( $message, static::$errorExceptionCode, $severity, $filename, $lineno );
	}

	/**
	 * Get last JSON error.
	 */
	public static function errorJson( ?int $error = null ): string
	{
		$error = null === $error ? json_last_error() : $error;

		if ( JSON_ERROR_NONE === $error ) {
			return '';
		}

		/* @formatter:off */
		static $errors = [
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

		$key = $errors[$error][0] ?? '???';
		$msg = $errors[$error][1] ?? 'Definition missing for error #' . $error;

		return "$key $msg";
	}

	/**
	 * Get ZIP error.
	 */
	public static function errorZip( int $error )
	{
		/* @formatter:off */
		static $zipErrors = [
			\ZipArchive::ER_MULTIDISK			=> ['ZipArchive::ER_MULTIDISK',	 			'Multi-disk zip archives not supported'],
			\ZipArchive::ER_RENAME				=> ['ZipArchive::ER_RENAME'	,	 			'Renaming temporary file failed'],
			\ZipArchive::ER_CLOSE				=> ['ZipArchive::ER_CLOSE',		 			'Multi-disk zip archives not supported'],
			\ZipArchive::ER_SEEK				=> ['ZipArchive::ER_SEEK',		 			'Seek error'],
			\ZipArchive::ER_READ				=> ['ZipArchive::ER_READ',		 			'Read error'],
			\ZipArchive::ER_WRITE				=> ['ZipArchive::ER_WRITE',		 			'Write error'],
			\ZipArchive::ER_CRC					=> ['ZipArchive::ER_CRC',		 			'CRC error'],
			\ZipArchive::ER_ZIPCLOSED			=> ['ZipArchive::ER_ZIPCLOSED',	 			'Containing zip archive was close'],
			\ZipArchive::ER_NOENT				=> ['ZipArchive::ER_NOENT',		 			'No such file'],
			\ZipArchive::ER_EXISTS				=> ['ZipArchive::ER_EXISTS',	 			'File already exists'],
			\ZipArchive::ER_OPEN				=> ['ZipArchive::ER_OPEN',		 			'Can\'t open file'],
			\ZipArchive::ER_TMPOPEN				=> ['ZipArchive::ER_TMPOPEN',	 			'Failure to create temporary file'],
			\ZipArchive::ER_ZLIB				=> ['ZipArchive::ER_ZLIB',		 			'Zlib error'],
			\ZipArchive::ER_MEMORY				=> ['ZipArchive::ER_MEMORY',	 			'Memory allocation failure'],
			\ZipArchive::ER_CHANGED				=> ['ZipArchive::ER_CHANGED',	 			'Entry has been changed '],
			\ZipArchive::ER_COMPNOTSUPP			=> ['ZipArchive::ER_COMPNOTSUPP',			'Compression method not supported'],
			\ZipArchive::ER_EOF					=> ['ZipArchive::ER_EOF',		 			'Premature EOF'],
			\ZipArchive::ER_INVAL				=> ['ZipArchive::ER_INVAL',		 			'Invalid argument'],
			\ZipArchive::ER_NOZIP				=> ['ZipArchive::ER_NOZIP',		 			'Not a zip archive'],
			\ZipArchive::ER_INTERNAL			=> ['ZipArchive::ER_INTERNAL',	 			'Internal error '],
			\ZipArchive::ER_INCONS				=> ['ZipArchive::ER_INCONS',	 			'Zip archive inconsistent'],
			\ZipArchive::ER_REMOVE				=> ['ZipArchive::ER_REMOVE',	 			'Can\'t remove file' ],
			\ZipArchive::ER_DELETED				=> ['ZipArchive::ER_DELETED',	 			'Entry has been deleted'],
			/*
			 * version_compare( PHP_VERSION, '17.4.3' ) >= 0
			 \ZipArchive::ER_ENCRNOTSUPP 		=> ['ER_ENCRNOTSUPP',			'Encryption method not supported. Available as of PHP 7.4.3 and PECL zip 1.16.1, respectively'],
			 \ZipArchive::ER_RDONLY				=> ['ER_RDONLY',				'Read-only archive. Available as of PHP 7.4.3 and PECL zip 1.16.1, respectively'],
			 \ZipArchive::ER_NOPASSWD			=> ['ER_NOPASSWD',				'No password provided. Available as of PHP 7.4.3 and PECL zip 1.16.1, respectively'],
			 \ZipArchive::ER_WRONGPASSWD			=> ['ER_WRONGPASSWD',			'Wrong password provided. Available as of PHP 7.4.3 and PECL zip 1.16.1, respectively'],
			 \ZipArchive::ZIP_ER_OPNOTSUPP		=> ['ZIP_ER_OPNOTSUPP',			'Operation not supported. Available as of PHP 7.4.3 and PECL zip 1.16.1, respectively, if built against libzip ≥ 1.0.0'],
			 \ZipArchive::ZIP_ER_INUSE			=> ['ZIP_ER_INUSE',				'Resource still in use. Available as of PHP 7.4.3 and PECL zip 1.16.1, respectively, if built against libzip ≥ 1.0.0'],
			 \ZipArchive::ZIP_ER_TELL			=> ['ZIP_ER_TELL',				'Tell error. Available as of PHP 7.4.3 and PECL zip 1.16.1, respectively, if built against libzip ≥ 1.0.0'],
			 \ZipArchive::ZIP_ER_COMPRESSED_DATA	=> ['ZIP_ER_COMPRESSED_DATA',	'Compressed data invalid. Available as of PHP 7.4.3 and PECL zip 1.16.1, respectively, if built against libzip ≥ 1.6.0'],
			 \ZipArchive::ER_CANCELLED			=> ['ER_CANCELLED',				'Operation cancelled. Available as of PHP 7.4.3 and PECL zip 1.16.1, respectively, if built against libzip ≥ 1.6.0'],
			 */
		];
		/* @formatter:on */

		$key = $zipErrors[$error][0] ?? 'ZipArchive::???';
		$msg = $zipErrors[$error][1] ?? 'Definition missing for error #' . $error;

		return "$key $msg";
	}

	/**
	 * Handle Exceptions.
	 *
	 * @param bool $log   Write to PHP error log?
	 * @param int  $dirUp How many sub-dirs to show in path?
	 */
	public static function exceptionHandler( \Throwable $E, bool $log = true, int $dirUp = 4 ): void
	{
		static::exceptionPrint( $E, $log, $dirUp );
		exit( $E->getCode() ?: 1 );
	}

	/**
	 * Print formated Exception message.
	 *
	 * @param bool $log   Write to PHP error log?
	 * @param int  $dirUp How many sub-dirs to show in path?
	 */
	public static function exceptionPrint( \Throwable $E, bool $log = true, int $dirUp = 4 ): void
	{
		echo "\n----------\n";

		if ( defined( 'DEBUG' ) && DEBUG ) {
			echo $E;
		}
		else {
			$projectDir = dirname( __DIR__, $dirUp );
			$srcFile = substr( $E->getFile(), strlen( $projectDir ) );

			/* @formatter:off */
			printf( "\nIn ...%s:%d\n\n  [%s]\n  %s\n\n",
				$srcFile,
				$E->getLine(),
				get_class( $E ),
				trim( $E->getMessage() ), // prefixed with new line!
			);
			/* @formatter:on */
		}

		$log && error_log( $E );
	}

	/**
	 * Compute execution time.
	 *
	 * @param  float|null $start Previous time in nanoseconds, 0: get current time, null: diff to instance creation time
	 * @return float             Time diff in frac seconds
	 */
	public static function exectime( ?float $start = 0 ): float
	{
		$time = hrtime( true );

		if ( 0.0 === $start ) {
			return $time;
		}

		if ( null === $start ) {
			$start = static::$execTime; // get instance creation time
		}

		$end = $time - $start;
		$end = $end / 1e+9; // nanoseconds to seconds 0.123456789

		return $end;
	}

	/**
	 * Convert EXIF gps data to decimal location.
	 *
	 * This method expects flatten EXIF results (no sections)
	 * @see exif_read_data()
	 *
	 * @return Array ( [lat] => 12.567, [lon] => -34.567 )
	 */
	public static function exifGpsToLoc( array $exif ): array
	{
		/* @formatter:off */
		$gps = [
			// Latitude: N, S
			'lat'   => strtoupper( trim( $exif['GPSLatitudeRef'] ?? null ) ),
			'lat_d' => explode( '/', $exif['GPSLatitude'][0] ?? null ),
			'lat_m' => explode( '/', $exif['GPSLatitude'][1] ?? null ),
			'lat_s' => explode( '/', $exif['GPSLatitude'][2] ?? null ),
			// Longitude: W, E
			'lon'   => strtoupper( trim( $exif['GPSLongitudeRef'] ?? null ) ),
			'lon_d' => explode( '/', $exif['GPSLongitude'][0] ?? null ),
			'lon_m' => explode( '/', $exif['GPSLongitude'][1] ?? null ),
			'lon_s' => explode( '/', $exif['GPSLongitude'][2] ?? null ),
		];
		/* @formatter:on */

		foreach ( $gps as $k => $v ) {
			if ( is_string( $v ) && in_array( $v, [ 'N', 'E' ] ) ) {
				$gps[$k] = 1;
			}
			elseif ( is_string( $v ) && in_array( $v, [ 'S', 'W' ] ) ) {
				$gps[$k] = -1;
			}
			elseif ( is_array( $v ) && 2 === count( $v ) ) {
				$gps[$k] = $v[0] / $v[1];
			}
			else {
				return []; // invalid entry!
			}
		}

		/**
		 * NOTE:
		 * 4 decimal places is accurate to 11.1  meters (+/- 5.55 m) at the equator.
		 * 5 decimal places is accurate to  1.11 meters at the equator.
		 * 6 decimal places is accurate to 0.111 meters at the equator.
		 * @link http://wiki.gis.com/wiki/index.php/Decimal_degrees
		 *
		 */
		/* @formatter:off */
		return [
			'lat' => round( $gps['lat'] * ( $gps['lat_d'] + $gps['lat_m'] / 60 + $gps['lat_s'] / 3600 ), 6 ),
			'lon' => round( $gps['lon'] * ( $gps['lon_d'] + $gps['lon_m'] / 60 + $gps['lon_s'] / 3600 ), 6 ),
		];
		/* @formatter:on */
	}

	/**
	 * Get file extension.
	 *
	 * @param  string $file Name / path
	 * @return string       Extension
	 */
	public static function fileExt( string $file ): string
	{
		return strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
	}

	/**
	 * Get file path without extension.
	 *
	 * @param  string $file Name / path
	 * @return string       Base path with no extension
	 */
	public static function fileNoExt( string $file ): string
	{
		return preg_replace( '~(\.[^\.]+)$~', '', $file );
	}

	/**
	 * Remove file with wilcards.
	 *
	 * @todo Cannot suppress Windows message: Could Not Find ... on missing files
	 */
	public static function fileRemove( string $file ): ?string
	{
		if ( $abs = realpath( $file ) ) {
			$cmd = defined( 'PHP_WINDOWS_VERSION_BUILD' ) ? 'del /Q "%s"' : 'rm -f "%s"';
			$cmd = sprintf( $cmd, $abs );
			return shell_exec( $cmd );
		}

		return null;
	}

	/**
	 * Read JSON array from file.
	 */
	public static function jsonLoad( string $file )
	{
		return is_file( $file ) ? json_decode( file_get_contents( $file ), true ) : [];
	}

	/**
	 * Save JSON array to file.
	 */
	public static function jsonSave( string $file, array $data = [], bool $sort = false )
	{
		$sort && ksort( $data );
		return file_put_contents( $file, json_encode( $data, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Send array as JSON.
	 */
	public static function jsonSend( array $data, string $error = '', bool $exit = true ): void
	{
		header( 'Content-Type: application/json; charset=UTF-8' );
		echo json_encode( [ 'error' => $error, 'data' => $data ] );
		$exit && exit();
	}

	/**
	 * Finally the formatNumber() unified method.
	 */
	public static function numberString( float $number = 0, int $decimals = 0, string $point = '.', string $sep = ' ' ): string
	{
		return number_format( $number, $decimals, $point, $sep );
	}

	/**
	 * Generate path from BASE + array elements.
	 *
	 * @param  string $base  Home dir
	 * @param  array  $parts Path elements
	 * @return string        Complete path
	 */
	public static function pathBuild( string $base, array $parts ): string
	{
		return $base . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $parts );
	}

	/**
	 * Convert dir separators in $path to current OS.
	 *
	 * @param bool $trim Remove trailing /
	 */
	public static function pathFix( string $path, bool $trim = true ): string
	{
		$find = '/' === DIRECTORY_SEPARATOR ? '\\' : '/';
		$repl = '/' === $find ? '\\' : '/';

		$path = str_replace( $find, $repl, $path );
		$trim && $path = rtrim( $path, $repl );

		return $path;
	}

	/**
	 * Compute absolute path from relative at base.
	 *
	 * @param  string      $path Relative path
	 * @param  string      $base Base dir for $path
	 * @return string|bool       Absolute or false if not found
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
	 * Convert absolute path to relative from root.
	 *
	 * @param  string $path Absolute path
	 * @param  string $root Root path to remove
	 * @return string       Relative path
	 */
	public static function pathToRel( string $path, string $root ): string
	{
		$path = str_replace( '\\', '/', $path );
		$root = str_replace( '\\', '/', $root );

		$rel = $path;
		if ( 0 === strpos( $rel, $root ) ) {
			$rel = substr( $rel, strlen( $root ) );
		}

		return $rel;
	}

	/**
	 * Generate BIND array for PDO::execute()
	 *
	 * @param  array $data Array ( [name] => value, ... )
	 * @return array       Array ( [:name] => value, ... )
	 */
	public static function pdoExecuteParams( array $data ): array
	{
		$bind = [];

		foreach ( $data as $k => $v ) {
			$bind[":$k"] = $v;
		}

		return $bind;
	}

	/**
	 * Fancy expression print.
	 *
	 * @param mixed $exp    Expression to print: array|object|string|float|etc...
	 * @param bool  $simple Remove objects?
	 * @param array $keys   Keys replacements
	 */
	public static function print_r( $exp, bool $simple = true, array $keys = [] ): string
	{
		if ( is_array( $exp ) || is_object( $exp ) ) {

			$exp = (array) $exp;

			foreach ( $exp as $k => $v ) {
				// Replace bolean values
				if ( is_bool( $v ) ) {
					$exp[$k] = $v ? 'true' : 'false';
				}
				// Replace each Object in array with class name string
				else if ( $simple && is_object( $v ) ) {
					$exp[$k] = '(Object) ' . get_class( $v );
				}
			}

			// Replace keys
			if ( $keys ) {
				$new = [];
				foreach ( $exp as $k => $v ) {
					$key = $keys[$k] ?? $k; // Missing replacement - use old key!
					$new[$key] = $v;
				}
				$exp = $new;
			}
		}

		$str = print_r( $exp, true );
		$str = str_replace( [ "\r", "\n" ], ' ', $str ); // remove line breaks
		$str = preg_replace( '/[ ]{2,}/', ' ', $str ); // remove double spacess

		return $str;
	}

	/**
	 * Render Progress bar.
	 *
	 * @param number $current  Current item
	 * @param number $total    Total items
	 * @param number $size     Bar length
	 * @param string $cCurrent Proggres char
	 * @param string $cFill    Fill char
	 * @return Array (
	 *  [bar]  => |||-------
	 *  [cent] => 36
	 * )
	 */
	public static function progressBar( int $current, int $total, int $size = 20, string $cCurrent = '|', string $cFill = '-' ): array
	{
		$current = min( $current, $total );
		$progres = round( ( 100 / $total ) * $current );

		$_total = $size;
		$_current = round( ( $size * $current ) / $total );

		$bar = str_repeat( $cCurrent, $_current );
		$bar .= str_repeat( $cFill, $_total - $_current );

		return [ 'bar' => $bar, 'cent' => $progres ];
	}

	/**
	 * Get user input or die.
	 *
	 * @throws \BadMethodCallException In TESTING mode throws some exotic Exception instead of exit()
	 *
	 * @param  string $msg    Prompt message to show, ie. "Hit [Enter] to continue..."
	 * @param  bool   $quit   Enable user exit?
	 * @param  string $_input TESTING: Overwrite user input (for testing purposes)
	 * @return string User input or [$_input] arg if set
	 */
	public static function prompt( string $msg = '', bool $quit = true, string $_input = '' ): string
	{
		if ( defined( 'TESTING' ) ) {
			if ( $quit ) {
				throw new \BadMethodCallException( $msg );
			}
			else {
				return $_input;
			}
		}

		printf( "%s\n%s", $msg, $quit ? "Use [Q] to quit.\n" : '' );
		$input = $_input ?: self::stdin();

		if ( $quit && 'Q' === strtoupper( $input ) ) {
			exit( "User exit. Bye!\n" );
		}

		return $input;
	}

	/**
	 * String slugiffy.
	 * Based on Symfony Jobeet tutorial.
	 *
	 * @link https://stackoverflow.com/questions/2955251/php-function-to-make-slug-url-string
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
	 * Print message to STDERR.
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
	 * Get user input.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param int $length Chars limit
	 */
	public static function stdin( int $length = 4096 ): string
	{
		if ( function_exists( 'sapi_windows_cp_set' ) ) {
			$codepage = sapi_windows_cp_get();
			sapi_windows_cp_set( $cp = sapi_windows_cp_get( 'oem' ) );
		}

		$line = fgets( STDIN, $length );

		if ( function_exists( 'sapi_windows_cp_set' ) ) {
			sapi_windows_cp_set( $codepage );
			$line = iconv( "cp{$cp}", 'UTF-8', $line );
		}

		// Remove [Enter] key!
		$line = rtrim( $line, "\r\n" );

		return $line;
	}

	/**
	 * Format time.
	 *
	 * @param float   $seconds   Time in fractional seconds
	 * @param int     $precision How many fractional digits? 0 == no fractions part
	 * @return string            Time in format 18394d 16g 11m 41.589s
	 */
	public static function timeString( float $seconds, ?int $precision = null ): string
	{
		$precision = null === $precision ? 3 : $precision;

		$sign = $seconds < 0 ? '-' : '';
		$seconds = abs( $seconds );

		$d = $h = $m = 0;
		$s = (int) $seconds; // get natural part
		$u = $seconds - $s; // get fraction part

		if ( $s >= self::DAY ) {
			$d = floor( $s / self::DAY );
			$s = floor( $s % self::DAY );
		}
		if ( $s >= self::HOUR ) {
			$h = floor( $s / self::HOUR );
			$s = floor( $s % self::HOUR );
		}
		if ( $s >= self::MINUTE ) {
			$m = floor( $s / self::MINUTE );
			$s = floor( $s % self::MINUTE );
		}

		$s = sprintf( "%.{$precision}f", $s + $u );

		/* @formatter:off */
		$masks = [
			'd' => '%1$sd',
			'h' => '%2$sh',
			'm' => '%3$sm',
			's' => '%4$ss',
		];
		/* @formatter:on */

		// Remove '0' elements
		$mask = [];
		foreach ( $masks as $k => $v ) {
			if ( intval( $$k ) ) {
				$mask[$k] = $v;
			}
		}

		// Keep at least seconds!
		if ( !$mask ) {
			$mask['s'] = $masks['s'];
		}

		return $sign . trim( sprintf( implode( ' ', $mask ), $d, $h, $m, $s ) );
	}

	/**
	 * Print string with new lines.
	 */
	public static function writeln( string $text, int $addLines = 1, bool $echo = true ): string
	{
		if ( $text ) {
			$text .= str_repeat( "\n", $addLines );
		}

		$echo && print ( $text ) ;

		return $text;
	}
}
