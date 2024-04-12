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
	protected $Prompt;
	protected $databases;

	/**
	 * Configure Factory.
	 */
	public function __construct( array $cfg = [] )
	{
		!defined( 'DEBUG' ) && define( 'DEBUG', (bool) getenv( 'APP_DEBUG' ) );
		$this->cfg = $cfg;

		$this->Utils()->exceptionHandle();
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
	 * @return Prompt
	 */
	public function Prompt()
	{
		return $this->Prompt ?? $this->Prompt = new Prompt( $this );
	}

	// =================================================================================================================
	// INSTANCES
	// =================================================================================================================

	/**
	 * @return FilesSync
	 */
	public function FilesSync()
	{
		return new FilesSync( $this );
	}

	/**
	 * @return ProgressBar
	 */
	public function ProgressBar( int $steps = 10, string $format = '' )
	{
		return new ProgressBar( $this, $steps, $format );
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
	 * Sleep cfg[key_usec].
	 */
	public function sleep( string $key ): void
	{
		if ( defined( 'TESTING' ) ) {
			return;
		}

		$ms = (int) $this->get( $key );
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
	 * @param array        $tokens Token replacements. Use key {bar} to pass bar length
	 */
	public function log( string $method, $format = '', $tokens = [] )
	{
		$format = (array) $format;

		if ( !is_array( $tokens ) ) {
			$tokens = [ '%s' => (string) $tokens ];
		}

		$out = [];
		$max = null;

		foreach ( $format as $line ) {
			if ( strlen( $line ) > 1 ) {
				$line = strtr( $line, $tokens );
			}
			else {
				$line = $line ?: '-';
			}
			$max = max( $max, strlen( $line ) );
			$out[] = $line;
		}

		$max = $max > 1 ? $max : $this->get( 'log_barlen', 80 );
		$len = $tokens['{bar}'] ?? $max;

		foreach ( $out as $line ) {
			if ( strlen( $line ) === 1 ) {
				$line = str_repeat( $line, $len );
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
