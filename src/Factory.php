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
	protected $Loggex;
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
	 * @return Loggex
	 */
	public function Loggex()
	{
		return $this->Loggex ?? $this->Loggex = new Loggex( $this );
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
	 * @return ProgressStats
	 */
	public function ProgressStats( int $steps )
	{
		return new ProgressStats( $this, $steps );
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
}
