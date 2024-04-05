<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * Progress Bar.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class ProgressBar
{
	protected $format;
	protected $step = 0;
	protected $steps;
	protected $last = 0;
	protected $verbose;

	/*
	 * Services:
	 */
	protected $Factory;
	protected $Utils;
	protected $Logger;

	/**
	 * Setup.
	 */
	public function __construct( Factory $Factory, int $steps = 10, string $format = '' )
	{
		$this->Factory = $Factory->merge( self::defaults() );
		$this->Utils = $Factory->Utils();
		$this->Logger = $Factory->Logger();

		$this->steps = $steps;
		$this->format( $format );

		/**
		 * Let Logger handle verbosity level as one of Logger->(info|notice|etc..) methods
		 * Logger verbosity is set by: Application::setVerbosity() | ENV[LOG_VERBOSE] | cfg[log_verbose]
		 * @see Application::setVerbosity()
		 */
		$this->verbose = $this->Logger->isHandling( $Factory->get( 'log_verbose' ), $Factory->get( 'bar_verbose' ) );
	}

	/**
	 * Clean.
	 */
	public function __destruct()
	{
		if ( !$this->verbose || defined( 'TESTING' ) ) {
			return;
		}

		echo "\n";
	}

	/**
	 * Get defaults.
	 */
	private function defaults(): array
	{
		/**
		 * [bar_format]
		 * Display format
		 *
		 * [bar_verbose]
		 * To display Bar set cfg[bar_verbose] >= cfg[log_verbose]
		 * @see ProgressBar::__construct()
		 *
		 * [bar_width]
		 * Total line width
		 *
		 * [bar_size]
		 * Progress bar size
		 *
		 * [bar_usleep]
		 * Progress bar slow down
		 *
		 * [bar_debug]
		 * Progress bar slow down with [Enter]
		 *
		 * @formatter:off */
		return [
			'bar_format'    => '[{bar}] {cent}% {text}',
			'bar_verbose'   => 'NOTICE',
			'bar_width'     => 80,
			'bar_size'      => 20,
			'bar_char_done' => '|',
			'bar_char_fill' => '.',
			'bar_usleep'    => getenv( 'BAR_USLEEP' ) ?: 0,
			'bar_debug'     => getenv( 'BAR_DEBUG' ) ?: false,
		];
		/* @formatter:on */
	}

	/**
	 * Echo Bar
	 *
	 * @param string $text Message passed to Bar::inc()
	 */
	protected function draw( string $text, array $tokens ): bool
	{
		// Don't display empty Bar or in less verbose modes
		if ( !$this->steps || !$this->verbose ) {
			return false;
		}

		/* @formatter:off */
		$bar = $this->Utils->progressBar(
			$this->step,
			$this->steps,
			$this->Factory->get( 'bar_size' ),
			$this->Factory->get( 'bar_char_done' ),
			$this->Factory->get( 'bar_char_fill' ),
		);
		/* @formatter:on */

		$max = $this->Factory->get( 'bar_width' );

		do {
			/* @formatter:off */
			$line = strtr( $this->format, array_merge([
				'{bar}'   => $bar['bar'],
				'{cent}'  => sprintf( '% 3d', $bar['cent'] ),
				'{step}'  => $this->step,
				'{steps}' => $this->steps,
				'{text}'  => $text,
			], $tokens ));
			/* @formatter:on */

			$len = mb_strlen( $line );
			$sub = $max - $len;

			if ( !$text || $sub >= 0 ) {
				break;
			}

			$text = $this->Utils->pathCut( $text, mb_strlen( $text ) + $sub );
		}
		while ( true );

		$fill = max( 0, $this->last - $len );
		$line .= str_repeat( ' ', $fill );
		$this->last = $len;

		if ( defined( 'TESTING' ) ) {
			throw new \LogicException( $line );
		}

		echo "$line\r";
		return true;
	}

	/**
	 * Advance Bar progress.
	 *
	 * @param string $text  Message to display under {text} token
	 * @param array $tokens More tokens to replace in cfg[bar_format]
	 */
	public function inc( string $text = '', int $advance = 1, array $tokens = [] ): bool
	{
		$this->step += $advance;
		$result = $this->draw( $text, $tokens );

		DEBUG && $result && $this->Factory->sleep( 'bar_usleep' );
		DEBUG && $result && $this->Factory->get( 'bar_debug' ) && $this->Utils->stdin(); // Hit [Enter] to continue...

		return $result;
	}

	/**
	 * Set Bar format.
	 *
	 * Tokens:
	 * {bar}   - (string) ||||.....
	 * {cent}  - (int) procentage progress
	 * {step}  - (int) step
	 * {steps} - (int) steps
	 * {text}  - (string) message passed to BAR::inc()
	 *
	 * @param string $format cfg[key] Holding Bar format
	 * @throws \InvalidArgumentException On missing format
	 */
	public function format( string $format = '' )
	{
		$format = $format ?: 'bar_format';
		$this->format = $this->Factory->get( $format );

		if ( !$this->format ) {
			throw new \InvalidArgumentException( sprintf( 'Bar format "%1$s" not found. Check cfg[%1$s]', $format ) );
		}
	}
}
