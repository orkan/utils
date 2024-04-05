<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * User prompt helpers.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Prompt
{
	/* @formatter:off */


	/**
	 * Config value prefixes.
	 *
	 * Use as cfg[key]: "{prefix}value"
	 * Examples:
	 * "*"      -> returns empty value, no user prompt
	 * "?"      -> always prompt user
	 * "?123"   -> always prompt user, return 123 if no value provided
	 * "*123"   -> does nothing for numeric values, same as "123"
	 * "?a/b/c" -> returns "a/b/c" only if path exists, otherwise propmt user again
	 * "*a/b/c" -> returns "a/b/c" even if path does not exist
	 */
	const MODES = [
		'*', // value is not required, can be empty or invalid path
		'?', // always ask to confirm/change value
	];

	/* @formatter:on */

	/*
	 * Services:
	 */
	protected $Factory;
	protected $Utils;

	/**
	 * Setup.
	 */
	public function __construct( Factory $Factory )
	{
		$this->Factory = $Factory->merge( self::defaults() );
		$this->Utils = $Factory->Utils();
	}

	/**
	 * Default config.
	 */
	private function defaults(): array
	{
		/**
		 * [user_quit]
		 * User input quit sequence
		 *
		 * [user_quit_str]
		 * User "quit" text appended to prompt message
		 *
		 * @formatter:off */
		return [
			'user_quit'     => 'Q',
			'user_quit_str' => '(use Q to quit)',
		];
		/* @formatter:on */
	}

	/**
	 * Import bytes as int.
	 *
	 * @param string $key  Config[key] holding bytes to update
	 * @param string $msg  Prompt message
	 * @return int Bytes number
	 */
	public function importBytes( $key, string $msg = 'Enter text' ): int
	{
		$bytes = $this->Factory->get( $key );

		if ( null !== $out = $this->Utils->byteNumber( $bytes ) ) {
			$this->Factory->cfg( $key, $out );
			return $out;
		}

		$mode = $bytes[0] ?? '';
		$bytes = ltrim( $bytes, implode( '', self::MODES ) );

		$mode = in_array( $mode, self::MODES ) ? $mode : '';
		$ask = '?' === $mode; // always ask
		$ask |= '' === $mode; // ask if empty

		if ( $ask ) {

			if ( defined( 'TESTING' ) ) {
				throw new \InvalidArgumentException( "Cannot prompt user input in tests! Use cfg[$key]" );
			}

			$quit = $this->Factory->get( 'user_quit_str' );
			$msg .= $quit ? ' ' . $quit : '';
			$msg .= $bytes ? ': ' . $bytes : ':';

			do {
				$out = $this->Utils->prompt( $msg . "\n", $this->Factory->get( 'user_quit' ) );
				$out = '' === $out ? $bytes : $out;

				if ( preg_match( '/^([\d.]+)(\s)?([BKMGTPE]?)(B)?$/i', $out ) ) {
					break;
				}

				printf( '- invalid entry "%s". Use integer or size string: 100M, 3.4G, etc...' . "\n", $out );
			}
			while ( true );

			$bytes = $out;
		}

		$bytes = $this->Utils->byteNumber( $bytes ) ?? 0;
		$this->Factory->cfg( $key, $bytes );

		return $bytes;
	}

	/**
	 * Import path string.
	 *
	 * cfg[key]:
	 *      "": ask for path
	 *  "PATH": ask if missing path
	 * "-PATH": allow empty / missing path
	 * "+PATH": always ask for path
	 *
	 * @see Utils::prompt()
	 *
	 * @param string $key    cfg[key] holding initial path
	 * @param string $msg    Prompt message
	 * @param bool   $create Auto-create user path if not exist?
	 * @return int Fixed path or empty if not exist or not required (*)
	 */
	public function importPath( string $key, string $msg = 'Enter text', bool $create = false ): string
	{
		$dir = $this->Factory->get( $key );
		$dir = $this->Utils->pathFix( $dir );

		$mode = $dir[0] ?? '';
		$dir = ltrim( $dir, implode( '', self::MODES ) );

		$mode = in_array( $mode, self::MODES ) ? $mode : '';
		$ask = '?' === $mode; // always ask
		$ask |= '' === $mode && !is_dir( $dir ); // ask if not found

		if ( $ask ) {

			if ( defined( 'TESTING' ) ) {
				throw new \InvalidArgumentException( "Cannot prompt user input in tests! Use cfg[$key]" );
			}

			$quit = $this->Factory->get( 'user_quit_str' );
			$msg .= $quit ? ' ' . $quit : '';
			$msg .= $dir ? ': ' . $dir : ':';

			do {
				$out = $this->Utils->prompt( $msg . "\n", $this->Factory->get( 'user_quit' ) );
				$out = trim( $out ?: $dir );

				if ( is_dir( $out ) ) {
					break;
				}

				if ( !$out || !$create ) {
					printf( '- dir not found "%s"' . "\n", $out );
				}
				elseif ( $this->Utils->dirClear( $out ) ) {
					break;
				}
				else {
					printf( '- error creating path "%s"' . "\n", $out );
				}
			}
			while ( true );

			$dir = $out;
		}

		$dir = is_dir( $dir ) ? realpath( $this->Utils->pathFix( $dir ) ) : '';
		$this->Factory->cfg( $key, $dir );

		return $dir;
	}
}
