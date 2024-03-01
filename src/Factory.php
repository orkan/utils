<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * Factory: Orkan.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Factory
{
	use Config;

	/*
	 * Services:
	 */
	protected $Utils;
	protected $Logger;
	protected $databases;

	/**
	 * Configure Factory.
	 */
	public function __construct( array $cfg = [] )
	{
		!defined( 'DEBUG' ) && define( 'DEBUG', (bool) getenv( 'APP_DEBUG' ) );
		$this->cfg = $cfg;
	}

	// =================================================================================================================
	// SERVICES
	// =================================================================================================================

	/**
	 * @return Utils
	 */
	public function Utils()
	{
		return $this->Utils ?? $this->Utils = new Utils();
	}

	/**
	 * @return Logger
	 */
	public function Logger()
	{
		return $this->Logger ?? $this->Logger = new Logger( $this );
	}

	/**
	 * Create / load / close database pointed by $dsn.
	 *
	 * Note:
	 * Do NOT save DB reference outside of this class to allow proper PDO object destruction (with $close param).
	 * Any 'living' reference to object created in PHP will block __destruct() call, which in this case
	 * wont's release db file and will prevent deleting it between test cases!
	 *
	 * @param bool $close Close DB and free up associated db file.
	 * @return Database|null
	 */
	public function Database( string $dsn = '', bool $close = false )
	{
		if ( !$dsn ) {
			return null;
		}

		if ( $close ) {
			unset( $this->databases[$dsn] );
			return null;
		}

		if ( !isset( $this->databases[$dsn] ) ) {
			$this->databases[$dsn] = new Database( $dsn );
		}

		return $this->databases[$dsn];
	}

	// =================================================================================================================
	// HELPERS
	// =================================================================================================================

	/**
	 * Update CMD window title.
	 *
	 * Put here since Application::setCliTitle() might be not available from within modules...
	 *
	 * @param string|array $tokens Array( [%token1%] => text1, [%token2%] => text2, ... )
	 * @param string       $format Eg. '%token1% - %token2% - %title%'
	 */
	public function cmdTitle( string $format = '%cmd_title%', array $tokens = [] ): void
	{
		$tokens['%cmd_title%'] = $this->get( 'cmd_title' );
		cli_set_process_title( strtr( $format, $tokens ) );
	}

	/**
	 * Sleep cfg[key_usec].
	 */
	public function sleep( string $key ): void
	{
		$ms = (int) $this->get( $key );
		$ms && $this->Logger()->debug( sprintf( 'Sleep cfg[%s]: %s usec... ', $key, $ms ) );
		$ms && usleep( $ms );
	}

	// =================================================================================================================
	// LOGGING
	// Add Logger record with sprintf support
	// =================================================================================================================

	/**
	 * Log tokenized messsage or ruler.
	 *
	 * @param string       $method Logger method
	 * @param string|array $format Tokenized string (or array of strings) or one char to make a ruler
	 * @param int|array    $tokens Token replacements or ruler length
	 */
	public function log( string $method, $format = '', $tokens = [] )
	{
		$len = 80;
		$format = (array) $format;

		if ( is_string( $tokens ) ) {
			$tokens = [ '%s' => $tokens ];
		}
		elseif ( is_int( $tokens ) ) {
			$len = $tokens;
		}

		foreach ( $format as $line ) {
			if ( strlen( $line ) > 1 ) {
				$line = strtr( $line, $tokens );
			}
			else {
				$chr = $line ?: '-';
				$line = str_repeat( $chr, $len );
			}
			$this->Logger()->$method( $line, 2 );
		}
	}

	/**
	 * Log debug.
	 *
	 * @param string|array $format Tokenized string (or array of strings) or one char to make a ruler
	 * @param int|array    $tokens Token replacements or ruler length
	 */
	public function debug( $format = '', $tokens = [] )
	{
		$this->log( 'debug', $format, $tokens );
	}

	/**
	 * Log info.
	 *
	 * @param string|array $format Tokenized string (or array of strings) or one char to make a ruler
	 * @param int|array    $tokens Token replacements or ruler length
	 */
	public function info( $format = '', $tokens = [] )
	{
		$this->log( 'info', $format, $tokens );
	}

	/**
	 * Log notice.
	 *
	 * @param string|array $format Tokenized string (or array of strings) or one char to make a ruler
	 * @param int|array    $tokens Token replacements or ruler length
	 */
	public function notice( $format = '', $tokens = [] )
	{
		$this->log( 'notice', $format, $tokens );
	}

	/**
	 * Log warning.
	 *
	 * @param string       $method Logger method
	 * @param string|array $format Tokenized string (or array of strings) or one char to make a ruler
	 * @param int|array    $tokens Token replacements or ruler length
	 */
	public function warning( $format = '', $tokens = [] )
	{
		$this->log( 'warning', $format, $tokens );
	}

	/**
	 * Log error.
	 *
	 * @param string|array $format Tokenized string (or array of strings) or one char to make a ruler
	 * @param int|array    $tokens Token replacements or ruler length
	 */
	public function error( $format = '', $tokens = [] )
	{
		$this->log( 'error', $format, $tokens );
	}
}
