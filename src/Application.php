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
	const APP_VERSION = 'v2.2.0';
	const APP_DATE = 'Thu, 08 Dec 2022 22:20:03 +01:00';

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
	 * c:      config:  => parameter requires value. Use: -c value|-cvalue|-c=value
	 * c::     config:: => optional value. Can't use space in between, use either: -cvalue|-c=value
	 */
	const ARGUMENTS = [
		'config'  => [ 'short' => 'c', 'long' => 'config' , 'desc' => 'Display App config'  ],
		'version' => [ 'short' => 'V', 'long' => 'version', 'desc' => 'Display App version' ],
		'help'    => [ 'short' => 'h', 'long' => 'help'   , 'desc' => 'Display App help'    ],
		'dry-run' => [ 'short' => 'd', 'long' => 'dry-run', 'desc' => 'Do not make changes in filesystem' ],
		'quiet'   => [ 'short' => 'q', 'long' => 'quiet'  , 'desc' => 'Do not output any message' ],
		'verbose' => [ 'short' => [ 'v', 'vv', 'vvv' ],
		               'long'  => 'verbose::',
		               'desc'  => 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug' ],
	];

	/**
	 * Required PHP extensions.
	 */
	const EXTENSIONS = [
		//'name' => (bool) verify?
	];

	/*
	 * Translate Verbosity levels from cmd line.
	 */
	const VERBOSITY_QUIET        = -1; // -q, --quite
	const VERBOSITY_NORMAL       =  0; // default
	const VERBOSITY_VERBOSE      =  1; // -v,   --verbose=1
	const VERBOSITY_VERY_VERBOSE =  2; // -vv,  --verbose=2
	const VERBOSITY_DEBUG        =  3; // -vvv, --verbose=3

	/**
	 * Map Verbosity levels to Logger levels.
	 *
	 * @see Application::setVerbosity()
	 */
	const VERBOSITY = [
		self::VERBOSITY_QUIET        => Logger::ERROR,  // 400
		self::VERBOSITY_NORMAL       => Logger::NOTICE,
		self::VERBOSITY_VERBOSE      => Logger::INFO,
		self::VERBOSITY_VERY_VERBOSE => Logger::DEBUG,
		self::VERBOSITY_DEBUG        => Logger::DEBUG,  // 100
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
	public function __construct( Factory $Factory )
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
	 * Set verbosity level from cmd line switches.
	 */
	public function setVerbosity( array $map = [] )
	{
		$map = $map ?: static::VERBOSITY;
		$level = null !== $this->getArg( 'quiet' ) ? static::VERBOSITY_QUIET : min( max( 0, $this->getArg( 'verbose' ) ), 3 );
		$this->Factory->cfg( 'log_verbose', $map[$level] );
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
		if ( !$appArgs = $this->Factory->get( 'app_args' ) ) {
			return null;
		}

		if ( null === $cmdArgs = $this->Factory->cfg( 'cmd_args' ) ) {
			$optS = array_column( $appArgs, 'short' );
			$optS = Utils::flattenArray( $optS );
			$optS = implode( '', $optS );
			$optL = array_column( $appArgs, 'long' );
			$cmdArgs = getopt( $optS, $optL );
			$this->Factory->cfg( 'cmd_args', $cmdArgs );
		}

		if ( !$name ) {
			return $cmdArgs;
		}

		/*
		 * Grouped short args are parsed as array.
		 *
		 * Example: -a -cc
		 * Result: [cmd_args] => Array (
		 * 	[a] => false,
		 * 	[c] => Array (
		 * 		[0] => false,
		 * 		[1] => false,
		 * 	)
		 * )
		 * So, for -cc we return number of chars used -> [2]
		 *
		 * NOTES:
		 * Even if -cc was used, PHP will parse it under single letter [c] key - not [cc]
		 * For single -c PHP will return false, not Array( [0] => false )
		 */
		if ( is_array( $arg = $appArgs[$name]['short'] ?? false) ) {
			$opt = $arg[0][0];

			if ( isset( $cmdArgs[$opt] ) ) {
				// cast to array in case single -c was used. See notes.
				return count( (array) $cmdArgs[$opt] );
			}

			$appArgs[$name]['short'] = $opt;
		}

		// Extract defined arg switches for current option and remove 'require' signatures if any
		$nameS = rtrim( $appArgs[$name]['short'] ?? '', ':' );
		$nameL = rtrim( $appArgs[$name]['long'] ?? '', ':' );

		return $cmdArgs[$nameS] ?? $cmdArgs[$nameL] ?? $appArgs[$name]['default'] ?? null;
	}

	/**
	 * Get CLI args definition.
	 */
	public function getArgHelp(): array
	{
		$req = [ '', ' <value>', '=[value]' ];
		$max = 0;

		$out = [];
		foreach ( $this->Factory->get( 'app_args' ) as $name => $arg ) {
			$valS = $valL = '';

			// Array args, like: -v|vv|vvv do not accept values!
			if ( is_array( $arg['short'] ?? false) ) {
				$arg['short'] = implode( '|', $arg['short'] );
			}

			if ( is_string( $arg['short'] ?? false) ) {
				$lvlS = count( explode( ':', $arg['short'] ) ) - 1;
				$valS = $req[$lvlS];
			}

			if ( is_string( $arg['long'] ?? false) ) {
				$lvlL = count( explode( ':', $arg['long'] ) ) - 1;
				$valL = $req[$lvlL];
			}

			$argS = isset( $arg['short'] ) ? '-' . rtrim( $arg['short'], ':' ) . $valS : '';
			$argL = isset( $arg['long'] ) ? '--' . rtrim( $arg['long'], ':' ) . $valL : '';
			$args = implode( ', ', array_filter( [ $argS, $argL ] ) );

			$desc = $arg['desc'] ?? '';
			$desc .= isset( $arg['default'] ) ? sprintf( ' (def: %s)', $arg['default'] ) : '';

			$out[$name] = [ 'args' => $args, 'desc' => $desc ];
			$max = max( $max, strlen( $args ) );
		}

		$result = [];
		foreach ( $out as $name => $arg ) {
			$sep = $max - strlen( $arg['args'] );
			$sep = str_repeat( ' ', $sep + 2 );
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
		return sprintf( '%s %s', static::APP_NAME, static::APP_VERSION );
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
			$this->Factory->cfg( 'log_verbose', $this->Logger::NONE );
			$this->Logger->error( $E );

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
		$this->setVerbosity();
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
