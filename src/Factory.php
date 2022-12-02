<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2022 Orkan <orkans+utilssrc@gmail.com>
 */
namespace Orkan;

/**
 * App Factory.
 *
 * @author Orkan <orkans+utilssrc@gmail.com>
 */
class Factory
{
	/**
	 * Global config.
	 *
	 * @var array
	 */
	protected $cfg;

	/**
	 * Command line arguments.
	 */
	protected $args;

	/*
	 * Services.
	 */
	protected $Utils;
	protected $Logger;

	/**
	 * Configure new Factory.
	 */
	public function __construct( array $cfg = [] )
	{
		$this->cfg = $cfg;
		$this->merge( $this->defaults() );
	}

	/**
	 * Get default config.
	 */
	protected function defaults()
	{
		/* @formatter:off */
		return [
			'app_args'     => [],
			'app_timezone' => 'UTC',
			'date_long'    => 'Y-m-d H:i:s',
		];
		/* @formatter:on */
	}

	/**
	 * Get cmd line arg or NULL if not present.
	 * Note: the found options with no value have boolean false assigned by PHP :(
	 *
	 * @param  string           $name Option name (short|long) or empty to get all parsed options
	 * @return array|NULL|mixed
	 */
	public function argGet( string $name = '' )
	{
		if ( !$arguments = $this->get( 'app_args' ) ) {
			return null;
		}

		if ( !isset( $this->args ) ) {
			$optS = array_column( $arguments, 'short' );
			$optS = implode( '', $optS );
			$optL = array_column( $arguments, 'long' );
			$this->args = getopt( $optS, $optL );
		}

		if ( !$name ) {
			return $this->args;
		}

		// Extract defined arg switches for current option and remove 'require' signatures if any
		$nameS = rtrim( $arguments[$name]['short'] ?? '', ':' );
		$nameL = rtrim( $arguments[$name]['long'] ?? '', ':' );

		return $this->args[$nameS] ?? $this->args[$nameL] ?? null;
	}

	/**
	 * Get CLI args definition.
	 */
	public function argHelp(): array
	{
		$mask = '[-%s, --%s] %s.';

		$out = [];
		foreach ( $this->get( 'app_args' ) as $name => $opt ) {
			$out[$name] = sprintf( $mask, $opt['short'], $opt['long'], $opt['desc'] );
		}

		return $out;
	}

	/**
	 * Set/Get config value.
	 */
	public function cfg( string $key = '', $val = null )
	{
		$last = $this->cfg[$key] ?? null;

		if ( isset( $val ) ) {
			$this->cfg[$key] = $val;
		}

		if ( '' === $key ) {
			return $this->cfg;
		}

		return $last;
	}

	/**
	 * Get config value or return default.
	 */
	public function get( string $key = '', $default = '' )
	{
		return $this->cfg( $key ) ?? $default;
	}

	/**
	 * Merge config array.
	 *
	 * @param array   $options Low priority config - will NOT replace self::cfg
	 * @param boolean $force   Hight priority config - will replace self::cfg
	 * @return self
	 */
	public function merge( array $options, bool $force = false ): self
	{
		$this->cfg = $force ? array_merge( $this->cfg, $options ) : array_merge( $options, $this->cfg );
		return $this;
	}

	/**
	 * Merge multi dimensional config array.
	 *
	 * Example:
	 * $this->cfg = Array ( 'moduleA' => [ 'opt1' => 1, 'opt2' => 2 ], 'moduleB' => [ 'opt1' => 1 ] )
	 * $options   = Array ( 'moduleA' => [ 'opt3' => 3 ] )
	 * $result    = Array ( 'moduleA' => [ 'opt1' => 1, 'opt2' => 2, 'opt3' => 3 ], 'moduleB' => [ 'opt1' => 1 ] )
	 *
	 * Note:
	 * To clear an array of options use [''] instead of [] !
	 *
	 * @param array   $options Low priority config - will NOT replace self::cfg
	 * @param boolean $force   Hight priority config - will replace self::cfg
	 * @return self
	 */
	public function mergeOptions( array $options, bool $force = false ): self
	{
		// Just a shorthand for standard PHP function...
		$this->cfg = $force ? array_replace_recursive( $this->cfg, $options ) : array_replace_recursive( $options, $this->cfg );
		return $this;
	}

	/**
	 * Get extra saved logs >= ['log_history'] level
	 */
	public function getHistoryLogs(): array
	{
		$out = [];
		foreach ( $this->Logger()->getHistory() as $log ) {
			$out[] = sprintf( '%s: %s', Logger::getLevelName( $log['level'] ), $log['message'] );
		}

		return $out;
	}

	/*
	 * -----------------------------------------------------------------------------------------------------------------
	 * SERVICES
	 * -----------------------------------------------------------------------------------------------------------------
	 */

	/**
	 * @return Utils
	 */
	public function Utils()
	{
		if ( !isset( $this->Utils ) ) {
			$this->Utils = new Utils();

			/* @formatter:off */
			$this->Utils->setup([
				'timeZone'   => $this->get( 'app_timezone' ),
				'dateFormat' => $this->cfg( 'date_long' ),
			]);
			/* @formatter:on */
		}

		return $this->Utils;
	}

	/**
	 * @return Logger
	 */
	public function Logger()
	{
		if ( !isset( $this->Logger ) ) {
			$Logger = $this->get( 'log_file' ) ? Logger::class : LoggerNoop::class;
			$this->Logger = new $Logger( $this );
		}

		return $this->Logger;
	}
}

/**
 * Noop Logger class if logging is disabled.
 */
class LoggerNoop
{

	public function __call( $name, $arguments )
	{
		// Do nothing...
	}
}
