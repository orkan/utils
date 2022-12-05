<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2022 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * Console app.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class App
{
	const APP_NAME = 'CLI App Framework by Orkan';
	const APP_VERSION = 'v1.8.7';
	const APP_DATE = 'Mon, 05 Dec 2022 21:13:27 +01:00';

	/* @formatter:off */

	/**
	 * Options from the command line argument list.
	 * @link https://www.php.net/manual/en/function.getopt.php
	 *
	 * FORMAT:
	 * short | long     => meaning
	 * c       config   => parameter does not accept any value
	 * c:      config:  => parameter requires value
	 * c::     config:: => optional value
	 */
	const ARGUMENTS = [
		'config'  => [ 'short' => 'c', 'long' => 'config' , 'desc' => 'Display config'      ],
		'version' => [ 'short' => 'v', 'long' => 'version', 'desc' => 'Display app version' ],
		'help'    => [ 'short' => 'h', 'long' => 'help'   , 'desc' => 'Display app help'    ],
		'dry-run' => [ 'short' => 'd', 'long' => 'dry-run', 'desc' => 'Do not make changes in filesystem' ],
	];

	/* @formatter:on */

	/**
	 * @link https://patorjk.com/software/taag/#p=display&v=0&f=Ivrit&t=CLI%20App
	 * @link Utils\usr\php\logo.php
	 */
	private static $logo = '   ____ _     ___      _
  / ___| |   |_ _|    / \\   _ __  _ __
 | |   | |    | |    / _ \\ | \'_ \\| \'_ \\
 | |___| |___ | |   / ___ \\| |_) | |_) |
  \\____|_____|___| /_/   \\_\\ .__/| .__/
                           |_|   |_|';
	/**
	 * @var Factory
	 */
	protected $Factory;

	/**
	 * @var Utils
	 */
	protected $Utils;

	/**
	 * Create Factory App.
	 *
	 * WARNING:
	 * Don't initialize Logger here, sice the config isn't fully loaded yet!
	 */
	public function __construct( Factory $Factory )
	{
		!defined( 'DEBUG' ) && define( 'DEBUG', getenv( 'APP_DEBUG' ) );

		$this->Factory = $Factory->merge( $this->defaults() );
		$this->Utils = $this->Factory->Utils();
	}

	/**
	 * Get default config.
	 */
	protected function defaults()
	{
		/* @formatter:off */
		return [
			'app_args'  => static::ARGUMENTS,
			'app_usage' => 'app.php [options]',
			'cli_title' => static::APP_NAME,
		];
		/* @formatter:on */
	}

	/**
	 * Display help screen.
	 */
	public function getHelp(): string
	{
		/* @formatter:off */
		return sprintf(
			"\n%1\$s" .
			"\n%2\$s" .
			"\n\nUsage:\n" .
			"\t%3\$s\n" .
			"Options:\n" .
			"%4\$s",
			/*1*/ static::$logo,
			/*2*/ $this->getVersion(),
			/*3*/ $this->Factory->get( 'app_usage' ),
			/*4*/ "\t" . implode( "\n\t", $this->Factory->argHelp() ) . "\n",
		);
		/* @formatter:on */
	}

	/**
	 * Get name & version string.
	 */
	public function getVersion(): string
	{
		return sprintf( '%s %s (%s)', static::APP_NAME, static::APP_VERSION, static::APP_DATE );
	}


	/**
	 * Set cli window title.
	 *
	 * @param string $title String to append
	 */
	public function setCliTitle( string $title = '' ): void
	{
		$text = $title ? " $title" : '';
		cli_set_process_title( sprintf( '%s %s', $this->Factory->get( 'cli_title' ), $text ) );
	}

	/**
	 * Initialize app.
	 */
	public function run()
	{
		/**
		 * -------------------------------------------------------------------------------------------------------------
		 * Exceptions
		 * @see Utils::errorHandler()
		 */
		set_error_handler( [ get_class( $this->Utils ), 'errorHandler' ] );

		set_exception_handler( function ( \Throwable $E ) {

			// Print all saved history logs
			$this->Utils->writeln( implode( "\n", $this->Factory->getHistoryLogs() ), 2 );

			/**
			 * 1. Turn OFF printing log messages since we're going to print Exception anyway!
			 * 2. Log full error + stack trace.
			 *
			 * @link https://symfony.com/doc/current/console/verbosity.html
			 */
			$this->Factory->cfg( 'log_verbosity', 0 );
			$this->Factory->Logger()->error( $E );

			if ( DEBUG ) {
				print $E;
			}
			else {
				/* @formatter:off */
				printf( "%s\n\nIn %s line %d:\n\n  [%s]\n  %s\n\n",
					$E->getMessage(),
					basename( $E->getFile() ),
					$E->getLine(),
					get_class( $E ),
					$E->getMessage()
				);
				/* @formatter:on */
			}

			exit( $E->getCode() ?: 1 );
		} );

		// Set CLI window title
		$this->setCliTitle();

		/*
		 * -------------------------------------------------------------------------------------------------------------
		 * Help
		 */
		if ( null !== $this->Factory->argGet( 'version' ) ) {
			echo static::APP_VERSION;
			exit();
		}

		if ( null !== $this->Factory->argGet( 'help' ) ) {
			echo $this->getHelp();
			exit();
		}

		if ( null !== $this->Factory->argGet( 'config' ) ) {
			print_r( $this->Factory->cfg() );
			exit();
		}

		/*
		 * -------------------------------------------------------------------------------------------------------------
		 * Log
		 * Keep this after Help section to prevent extra output
		 */
		$Logger = $this->Factory->Logger();
		DEBUG && $Logger->info( 'DEBUG is ON' );

		/* @formatter:off */
		$Logger->is( $Logger::DEBUG ) &&
		$Logger->debug( $this->Utils->print_r( $this->Factory->cfg() ) );
		/* @formatter:on */
	}
}
