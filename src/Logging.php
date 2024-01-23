<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * Log spritf message shortcuts.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
trait Logging
{

	/**
	 * Log sprintf messsage.
	 *
	 * @param string $method  Logger method
	 * @param string $s       Sprintf formatted string or one char to make a ruler
	 * @param string ...$args Sprintf args
	 */
	public function log( string $method = 'info', string $s = '', ...$args )
	{
		$s = strlen( $s ) > 1 ? sprintf( $s, ...$args ) : str_repeat( $s ?: '-', 80 );
		$this->Factory->Logger()->$method( $s, 1 );
	}

	/**
	 * Log debug.
	 */
	public function debug( string $s = '', ...$args )
	{
		$this->log( 'debug', $s, ...$args );
	}

	/**
	 * Log info.
	 */
	public function info( string $s = '', ...$args )
	{
		$this->log( 'info', $s, ...$args );
	}

	/**
	 * Log notice.
	 */
	public function notice( string $s = '', ...$args )
	{
		$this->log( 'notice', $s, ...$args );
	}

	/**
	 * Log warning.
	 */
	public function warning( string $s = '', ...$args )
	{
		$this->log( 'warning', $s, ...$args );
	}

	/**
	 * Log error.
	 */
	public function error( string $s = '', ...$args )
	{
		$this->log( 'error', $s, ...$args );
	}
}
