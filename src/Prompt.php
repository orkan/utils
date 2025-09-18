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
	 * <pre>
	 * "*"      -> returns empty value, no user prompt
	 * "?"      -> always prompt user
	 * "?123"   -> always prompt user, return 123 if no value provided
	 * "*123"   -> does nothing for numeric values, same as "123"
	 * "?a/b/c" -> returns "a/b/c" only if path exists, otherwise propmt user again
	 * "*a/b/c" -> returns "a/b/c" even if path does not exist
	 * </pre>
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
	protected $Logger;

	/**
	 * Setup.
	 */
	public function __construct( Factory $Factory )
	{
		$this->Factory = $Factory->merge( self::defaults() );
		$this->Utils = $Factory->Utils();
		$this->Logger = $this->Factory->Logger();
	}

	/**
	 * Default config.
	 */
	protected function defaults(): array
	{
		/**
		 * [prompt_quit_key]
		 * User input quit sequence
		 *
		 * [prompt_quit_str]
		 * User "quit" text appended to prompt message
		 *
		 * [prompt_autodirs]
		 * Create path if not exist
		 *
		 * @formatter:off */
		return [
			'prompt_quit_key' => 'Q',
			'prompt_quit_str' => '(use Q to quit)',
			'prompt_autodirs' => false,
		];
		/* @formatter:on */
	}

	/**
	 * Import bytes as int.
	 *
	 * @param string $key Config[key] holding bytes to update
	 * @param string $msg Prompt message
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
				throw new \RuntimeException( "Cannot prompt user input in tests! Use cfg[$key]" );
			}

			$quit = $this->Factory->get( 'prompt_quit_str' );
			$msg .= $quit ? ' ' . $quit : '';
			$msg .= $bytes ? ': ' . $bytes : ':';

			do {
				$out = $this->Utils->prompt( $msg . "\n", '0', $this->Factory->get( 'prompt_quit' ) );
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
	 * @see Prompt::MODES
	 *
	 * @param  string $key cfg[key] holding initial path
	 * @param  string $msg Prompt message
	 * @return int    Fixed path or empty if not exist or not required (*)
	 */
	public function importPath( string $key, array $opt = [] ): string
	{
		/* @formatter:off */
		$opt = array_merge([
			'quit_str' => $this->Factory->get( 'prompt_quit_str' ),
			'quit_key' => $this->Factory->get( 'prompt_quit_key' ),
			'autodirs' => $this->Factory->get( 'prompt_autodirs' ),
			'msg'      => 'Enter text',
		], $opt);
		/* @formatter:on */

		$dir = $this->Factory->get( $key );

		$mode = $dir[0] ?? '';
		$mode = in_array( $mode, self::MODES ) ? $mode : '';
		$mode && $dir = substr( $dir, 1 );

		if ( !is_dir( $dir ) && '*' !== $mode ) {

			$k2d = sprintf( 'cfg[%s]: "%s"', $key, $dir );
			$this->Logger->notice( "Dir not found $k2d" );

			// Auto-create?
			if ( $opt['autodirs'] ) {
				if ( $this->Utils->dirClear( $dir ) ) {
					$this->Logger->notice( "Creating path $k2d" );
				}
				else {
					throw new \RuntimeException( 'Error creating path!' );
				}
			}
			// Ask user...
			elseif ( '?' === $mode ) {
				$quit = $opt['quit_str'];
				$msg .= $quit ? " $quit" : '';
				$msg .= $dir ? ": $dir" : ':';

				do {
					$out = $this->Utils->prompt( "$msg\n", $dir, $opt['quit_key'] );
					$out = trim( $out ?: $dir );

					if ( !is_dir( $out ) ) {
						printf( 'Dir not found: "%s"%s', $out, "\n" );
						continue;
					}
				}
				while ( 1 );

				$dir = $out;
			}
		}

		$dir = is_dir( $dir ) ? realpath( $this->Utils->pathFix( $dir ) ) : '';
		$this->Factory->cfg( $key, $dir );

		return $dir;
	}
}
