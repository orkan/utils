<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2025 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * Logger with sprintf support.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Loggex
{
	/*
	 * Services:
	 */
	protected $Factory;
	protected $Logger;

	/**
	 * Setup.
	 */
	public function __construct( Factory $Factory )
	{
		$this->Factory = $Factory->merge( self::defaults() );
		$this->Logger = $Factory->Logger();
	}

	/**
	 * Get defaults.
	 */
	protected function defaults(): array
	{
		/**
		 * [log_barlen]
		 * Default bar length. Use tokens[{bar}] to force custom length per log()
		 *
		 * @formatter:off */
		return [
			'log_barlen' => 100,
		];
		/* @formatter:on */
	}

	/**
	 * Log tokenized messsage or ruler.
	 *
	 * OPTIONS:
	 * [log]       string Logger method()
	 * [backtrace] int    Debug backtrace to use. Def. 2 --> 2:Cllee() > 1:Factory::info() > 0:Factory::log()
	 * [prefix]    string Prefix each line with string
	 * [newline]   int    Append new lines
	 *
	 * @param  string|array $format Tokenized string (or array of strings) or one char to make a ruler
	 * @param  array        $tokens Token replacements. Use [bar] to pass bar length
	 * @param  array        $opt Options
	 * @return string       Output string
	 */
	public function log( $format = null, $tokens = [], array $opt = [] ): string
	{
		/* @formatter:off */
		$opt = array_merge([
			'log'        => '',
			'backtrace'  => 2,
			'prefix'     => '',
			'newline'    => 0,
		], $opt);
		/* @formatter:on */

		$method = $opt['log'];
		$format = (array) ( $format ?? '');

		if ( !is_array( $tokens ) ) {
			$tokens = [ '%s' => (string) $tokens ];
		}

		$all = $out = [];
		$max = null;

		foreach ( $format as $line ) {
			if ( mb_strlen( $line ) > 1 ) {
				$line = strtr( $line, $tokens );
			}
			$max = max( $max, mb_strlen( $line ) );
			$all[] = $line ?? '';
		}

		$max = $max > 1 ? $max : $this->Factory->get( 'log_barlen' );
		$len = $tokens['{bar}'] ?? $max;

		foreach ( $all as $line ) {
			if ( mb_strlen( $line ) === 1 ) {
				$line = str_repeat( $line, $len );
			}
			elseif ( $opt['prefix'] ) {
				$line = $opt['prefix'] . $line;
			}

			$out[] = $line;

			if ( $method ) {
				$this->Logger->$method( $line, $opt['backtrace'] );
			}
		}

		if ( $out = implode( "\n", $out ) ) {
			$out .= str_repeat( "\n", $opt['newline'] );
		}

		return $out;
	}

	/**
	 * Log debug.
	 *
	 * @param string|array $format Tokenized string (or array of strings) or one char to make a ruler
	 * @param int|array    $tokens Token replacements or ruler length
	 * @return string      Output string
	 */
	public function debug( $format = '', $tokens = [] ): string
	{
		return $this->log( $format, $tokens, [ 'log' => 'debug' ] );
	}

	/**
	 * Log info.
	 *
	 * @param string|array $format Tokenized string (or array of strings) or one char to make a ruler
	 * @param int|array    $tokens Token replacements or ruler length
	 * @return string      Output string
	 */
	public function info( $format = '', $tokens = [] ): string
	{
		return $this->log( $format, $tokens, [ 'log' => 'info' ] );
	}

	/**
	 * Log notice.
	 *
	 * @param string|array $format Tokenized string (or array of strings) or one char to make a ruler
	 * @param int|array    $tokens Token replacements or ruler length
	 * @return string      Output string
	 */
	public function notice( $format = '', $tokens = [] ): string
	{
		return $this->log( $format, $tokens, [ 'log' => 'notice' ] );
	}

	/**
	 * Log warning.
	 *
	 * @param string|array $format Tokenized string (or array of strings) or one char to make a ruler
	 * @param int|array    $tokens Token replacements or ruler length
	 * @return string      Output string
	 */
	public function warning( $format = '', $tokens = [] ): string
	{
		return $this->log( $format, $tokens, [ 'log' => 'warning' ] );
	}

	/**
	 * Log error.
	 *
	 * @param string|array $format Tokenized string (or array of strings) or one char to make a ruler
	 * @param int|array    $tokens Token replacements or ruler length
	 * @return string      Output string
	 */
	public function error( $format = '', $tokens = [] ): string
	{
		return $this->log( $format, $tokens, [ 'log' => 'error' ] );
	}
}
