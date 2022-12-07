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
class Application
{
	const APP_NAME = 'CLI App';
	const APP_VERSION = 'v2.0.0';
	const APP_DATE = 'Wed, 07 Dec 2022 02:01:29 +01:00';

	/**
	 * @link https://patorjk.com/software/taag/#p=display&v=0&f=Ivrit&t=CLI%20App
	 * @link Utils\usr\php\logo\logo.php
	 */
	const LOGO = '   ____ _     ___      _
  / ___| |   |_ _|    / \\   _ __  _ __
 | |   | |    | |    / _ \\ | \'_ \\| \'_ \\
 | |___| |___ | |   / ___ \\| |_) | |_) |
  \\____|_____|___| /_/   \\_\\ .__/| .__/
                           |_|   |_|';

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

	/**
	 * Required PHP extensions.
	 */
	const EXTENSIONS = [
		//'name' => (bool) verify?
	];

	/* @formatter:on */

	/*
	 * -----------------------------------------------------------------------------------------------------------------
	 * SERVICES
	 *
	 * NOTE:
	 * Re-declare overwriten parent services for IntelliSense features!
	 */

	/**
	 * @var Factory
	 */
	protected $Factory;

	/**
	 * @var Logger
	 */
	protected $Logger;

	/**
	 * @var Utils
	 */
	protected $Utils;

	/**
	 * Create Factory App.
	 *
	 * WARNING:
	 * Don't initialize Services here, sice the config isn't fully loaded yet!
	 */
	public function __construct( FactoryInterface $Factory )
	{
		!defined( 'DEBUG' ) && define( 'DEBUG', getenv( 'APP_DEBUG' ) );
		$this->Factory = $Factory->merge( $this->defaults() );
	}

	/**
	 * Get default config.
	 */
	protected function defaults()
	{
		$baseName = basename( static::class );
		$packageDir = dirname( ( new \ReflectionClass( static::class ) )->getFileName(), 3 ); // vendor/orkan/[project]

		/* @formatter:off */
		return [
			'cli_title'    => static::APP_NAME,
			'app_title'    => static::getVersion(),
			'app_args'     => static::ARGUMENTS,
			'app_usage'    => 'app.php [options]',
			'app_timezone' => date_default_timezone_get(),
			'date_short'   => 'Y-m-d',
			'date_long'    => 'l, Y-m-d H:i',
			'log_file'     => "$packageDir/$baseName.log", // vendor/orkan/[project]/Application.php
			'log_level'    => DEBUG ? Logger::DEBUG : Logger::INFO,
			'log_channel'  => DEBUG ? "$baseName/DEBUG" : $baseName,
			'log_format'   => "[%datetime%] [%channel%] %level_name%: %message%\n",
			'dir_package'  => $packageDir,
			'extensions'   => self::EXTENSIONS,
		];
		/* @formatter:on */
	}

	/**
	 * Check required PHP extensions.
	 *
	 * @throws \RuntimeException On missing required PHP extension
	 */
	protected function checkExtensions()
	{
		if ( !is_array( $extensions = $this->Factory->get( 'extensions' ) ) ) {
			throw new \InvalidArgumentException( 'Invalid EXTENSIONS definition! Check Application::EXTENSIONS for more info.' );
		}

		$missing = [];

		foreach ( $extensions as $name => $check ) {
			if ( $check && !extension_loaded( $name ) ) {
				$missing[] = $name;
			}
		}

		if ( $missing ) {
			throw new \RuntimeException( 'Missing PHP extensions: ' . implode( ', ', $missing ) );
		}
	}

	/**
	 * Get cmd line arg or NULL if not present.
	 * Note: the found options with no value have boolean false assigned by PHP :(
	 *
	 * @param  string $name Option name (short|long) or empty to get all parsed options
	 * @return array|NULL|mixed
	 */
	public function getArg( string $name = '' )
	{
		if ( !$arguments = $this->Factory->get( 'app_args' ) ) {
			return null;
		}

		if ( null === $cmdArgs = $this->Factory->cfg( 'cmd_args' ) ) {
			$optS = array_column( $arguments, 'short' );
			$optS = implode( '', $optS );
			$optL = array_column( $arguments, 'long' );
			$cmdArgs = getopt( $optS, $optL );
			$this->Factory->cfg( 'cmd_args', $cmdArgs );
		}

		if ( !$name ) {
			return $cmdArgs;
		}

		// Extract defined arg switches for current option and remove 'require' signatures if any
		$nameS = rtrim( $arguments[$name]['short'] ?? '', ':' );
		$nameL = rtrim( $arguments[$name]['long'] ?? '', ':' );

		return $cmdArgs[$nameS] ?? $cmdArgs[$nameL] ?? null;
	}

	/**
	 * Get CLI args definition.
	 */
	public function getArgHelp(): array
	{
		$req = [ '', ' <value>', ' [value]' ];
		$max = 0;

		$out = [];
		foreach ( $this->Factory->get( 'app_args' ) as $name => $arg ) {
			$opt = $arg['short'] ?? $arg['long'] ?? '';
			$lvl = count( explode( ':', $opt ) ) - 1;
			$val = $req[$lvl];

			$argS = isset( $arg['short'] ) ? '-' . rtrim( $arg['short'], ':' ) . $val : '';
			$argL = isset( $arg['long'] ) ? '--' . rtrim( $arg['long'], ':' ) . $val : '';
			$args = implode( '|', array_filter( [ $argS, $argL ] ) );
			$max = max( $max, strlen( $args ) );

			$out[$name] = [ 'args' => $args, 'desc' => $arg['desc'] ?? 'No description'];
		}

		$result = [];
		foreach ( $out as $name => $arg ) {
			$sep = $max - strlen( $arg['args'] );
			$sep = floor( $sep / 8 );
			$sep = str_repeat( "\t", $sep + 1 );
			$result[$name] = $arg['args'] . $sep . $arg['desc'];
		}

		return $result;
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
			/*1*/ static::LOGO,
			/*2*/ static::getVersionLong(),
			/*3*/ $this->Factory->get( 'app_usage' ),
			/*4*/ "\t" . implode( "\n\t", $this->getArgHelp() ) . "\n",
		);
		/* @formatter:on */
	}

	/**
	 * Get name & version string.
	 */
	public static function getVersion(): string
	{
		return sprintf( '%s v%s', static::APP_NAME, static::APP_VERSION );
	}

	/**
	 * Get name & version string.
	 */
	public static function getVersionLong(): string
	{
		return sprintf( '%s %s (%s)', static::APP_NAME, static::APP_VERSION, static::APP_DATE );
	}

	/**
	 * Get extra saved logs >= ['log_history'] level.
	 */
	public function getHistoryLogs(): array
	{
		$out = [];

		foreach ( $this->Logger->getHistory() ?: [] as $log ) {
			$out[] = sprintf( '%s: %s', $this->Logger->getLevelName( $log['level'] ), $log['message'] );
		}

		return $out;
	}

	/**
	 * Set cli window title.
	 *
	 * @param string $title String to append
	 */
	public function setCliTitle( string $title = '' ): void
	{
		$text = $title ? " $title" : '';
		cli_set_process_title( sprintf( '[%s] %s', $this->Factory->get( 'cli_title' ), $text ) );
	}

	/**
	 * Initialize.
	 *
	 * Also initialize services now. For reason why so late:
	 * @see Application::__construct()
	 */
	public function run()
	{
		$this->Utils = $this->Factory->Utils();

		/* @formatter:off */
		$this->Utils->setup([
			'timeZone'   => $this->Factory->get( 'app_timezone' ),
			'dateFormat' => $this->Factory->get( 'date_long' ),
		]);
		/* @formatter:on */

		/**
		 * -------------------------------------------------------------------------------------------------------------
		 * Exceptions
		 * @see Utils::errorHandler()
		 */
		set_error_handler( [ get_class( $this->Utils ), 'errorHandler' ] );

		set_exception_handler( function ( \Throwable $E ) {

			// Print all saved history logs
			$this->Utils->writeln( implode( "\n", $this->getHistoryLogs() ), 2 );

			/**
			 * 1. Turn OFF printing log messages since we're going to print Exception anyway!
			 * 2. Log full error + stack trace.
			 *
			 * @link https://symfony.com/doc/current/console/verbosity.html
			 */
			$this->Factory->cfg( 'log_verbose', 0 );
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

		$this->Logger = $this->Factory->Logger();
		$this->setCliTitle();
		$this->checkExtensions();

		/*
		 * -------------------------------------------------------------------------------------------------------------
		 * Help
		 */
		if ( null !== $this->getArg( 'version' ) ) {
			echo static::APP_VERSION;
			exit();
		}

		if ( null !== $this->getArg( 'help' ) ) {
			echo $this->getHelp();
			exit();
		}

		if ( null !== $this->getArg( 'config' ) ) {
			print_r( $this->Factory->cfg() );
			exit();
		}

		/*
		 * -------------------------------------------------------------------------------------------------------------
		 * LOG
		 *
		 * NOTE:
		 * Keep this after "Help" section to prevent extra output if App is going to exit anyway
		 */
		DEBUG && $this->Logger->info( 'DEBUG is ON' );

		/* @formatter:off */
		$this->Logger->is( $this->Logger::DEBUG ) &&
		$this->Logger->debug( $this->Utils->print_r( $this->Factory->cfg() ) );
		/* @formatter:on */
	}
}
