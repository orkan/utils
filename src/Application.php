<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
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
	const APP_VERSION = '7.0.0';
	const APP_DATE = 'Fri, 05 Apr 2024 17:47:14 +02:00';

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
	 * short | long      | meaning
	 * -----------------------------------------------------------------------------------------------------------------
	 * c     | config    | Parameter does not accept any value
	 * c:    | config:   | Parameter requires value. Use: -c value|-cvalue|-c=value
	 * c::   | config::  | Optional value. Can't use space in between, use either: -cvalue|-c=value
	 */
	const ARGUMENTS = [
		'setup'   => [ 'short' => 's', 'long' => 'setup'  , 'desc' => 'Display App config'                ],
		'version' => [ 'short' => 'V', 'long' => 'version', 'desc' => 'Display App version'               ],
		'help'    => [ 'short' => 'h', 'long' => 'help'   , 'desc' => 'Display App help'                  ],
		'dry-run' => [ 'short' => 'd', 'long' => 'dry-run', 'desc' => 'Do not make changes in filesystem' ],
		'quiet'   => [ 'short' => 'q', 'long' => 'quiet'  , 'desc' => 'Do not output any message'         ],
		'verbose' => [ 'short' => [ 'v', 'vv', 'vvv' ],
		               'long'  => 'verbose::',
		               'desc'  => 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug' ],
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
		self::VERBOSITY_QUIET        => 'ERROR',  // 400 -q
		self::VERBOSITY_NORMAL       => 'NOTICE', // 250
		self::VERBOSITY_VERBOSE      => 'INFO',   // 200 -v
		self::VERBOSITY_VERY_VERBOSE => 'DEBUG',  // 100 -vv
		self::VERBOSITY_DEBUG        => 'DEBUG',  // 100 -vvv
	];

	/* @formatter:on */

	/*
	 * Services:
	 */
	protected $Factory;
	protected $Utils;
	protected $Logger;

	/**
	 * Create Factory App.
	 */
	public function __construct( Factory $Factory )
	{
		!defined( 'DEBUG' ) && define( 'DEBUG', (bool) getenv( 'APP_DEBUG' ) );

		/**
		 * Don't initialize Services here, since config is NOT fully loaded yet!
		 * @see Application::run()
		 */
		$this->Factory = $Factory->merge( self::defaults() );
		$this->Utils = $Factory->Utils();

		// PHP setup
		date_default_timezone_set( $this->Factory->get( 'app_timezone' ) );
		foreach ( $this->Factory->get( 'php', [] ) as $k => $v ) {
			isset( $v ) && ini_set( $k, $v );
		}

		// App setup (no services)
		$this->checkExtensions();
		$this->setVerbosity();
	}

	/**
	 * Get defaults.
	 */
	private function defaults()
	{
		$packageDir = dirname( ( new \ReflectionClass( static::class ) )->getFileName(), 2 ); // vendor/orkan/[project]

		/**
		 * [cmd_title]
		 * CMD window title
		 * @see Application::cmdTitle()
		 *
		 * [app_opts]
		 * Defined command line argument (extendable by Factory::cfg())
		 * @see Application::ARGUMENTS
		 *
		 * [err_handle]
		 * Handle errors?
		 *
		 * [exc_handle]
		 * Handle exceptions?
		 *
		 * [extensions]
		 * Required PHP extensions: Array ( [extension_name] => (bool) verify, ... )
		 *
		 * [app_gc]
		 * Garbage collect: Free up memory once is no longer used
		 * @see Application::gc()
		 *
		 * -----------------------------------------------------------------------------------------------------------------
		 * PHP INI: Prepare for CLI
		 * @link https://www.php.net/manual/en/errorfunc.configuration.php
		 *
		 * Tip:
		 * Use NULL to skip set_ini()
		 *
		 * [max_execution_time]
		 * Maximum script execution time. Default: 30 -OR- 0 in CLI mode!
		 *
		 * [error_reporting]
		 * Show errors, warnings and notices. Default: E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED
		 * Suggested on PROD: E_ALL & ~E_DEPRECATED & ~E_STRICT
		 *
		 * [log_errors]
		 * Log PHP errors?
		 *
		 * [log_errors_max_len]
		 * Max length of php error messages. Default: 1024, 0: disable.
		 *
		 * [ignore_repeated_errors]
		 * Repeated errors must occur in same file on same line unless ignore_repeated_source is set true.
		 *
		 * [ignore_repeated_source]
		 * Do not log errors with repeated messages from different source lines.
		 *
		 * [html_errors]
		 * Format the error message as HTML?
		 *
		 * [error_log]
		 * Path to php_error.log
		 * @see Logger::__construct()
		 * -----------------------------------------------------------------------------------------------------------------
		 *
		 * @formatter:off */
		return [
			'cmd_title'    => static::APP_NAME,
			'app_opts'     => static::ARGUMENTS,
			'app_usage'    => 'app.php [options]',
			'app_timezone' => getenv( 'APP_TIMEZONE' ) ?: date_default_timezone_get(),
			'app_gc'       => getenv( 'APP_GC' ) ?: false,
			'err_handle'   => true,
			'exc_handle'   => true,
			'date_short'   => 'Y-m-d',
			'date_long'    => 'l, Y-m-d H:i',
			'log_level'    => DEBUG ? 'DEBUG' : 'NOTICE',
			'log_debug'    => DEBUG,
			'log_history'  => 'WARNING',
			'dir_package'  => $packageDir,
			'extensions'   => [],
			'php' => [
				'max_execution_time'     => null,
				'error_reporting'        => E_ALL,
				'log_errors'             => '1',
				'log_errors_max_len'     => '0',
				'ignore_repeated_errors' => '1',
				'ignore_repeated_source' => '0',
				'html_errors'            => '0',
				'error_log'              => null,
			],
		];
		/* @formatter:on */
	}

	/**
	 * Load User config provided as CMD line argument.
	 *
	 * CAUTION:
	 * An unknown switch leads PHP::getopt() to stop parsing following arguments!
	 *
	 * @param string $arg Argument key. See cfg[app_opts][{key}]
	 * @return string|null Config file location or null if argument wasn't present
	 */
	public function loadUserConfig( string $arg = '' ): ?string
	{
		if ( $arg ) {
			$file = $this->getArg( $arg );
		}
		else {
			$file = $this->Utils->cmdLastArg();
		}

		if ( $file ) {
			$this->Factory->merge( require $file, true );
			$this->Factory->cfg( 'cfg_user', realpath( $file ) );
		}

		return $file;
	}

	/**
	 * Set verbosity level from cmd line switches.
	 */
	public function setVerbosity( array $map = [] )
	{
		if ( defined( 'TESTING' ) ) {
			return;
		}

		$map = $map ?: static::VERBOSITY;

		$level = (int) $this->getArg( 'verbose' );
		$level = min( max( static::VERBOSITY_NORMAL, $level ), static::VERBOSITY_DEBUG );
		$level = null !== $this->getArg( 'quiet' ) ? static::VERBOSITY_QUIET : $level;

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
			throw new \InvalidArgumentException( 'Invalid EXTENSIONS definition! See Application::defaults() for more info.' );
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
	 * Free up variable.
	 */
	protected function gc( &$item ): void
	{
		if ( $this->Factory->get( 'app_gc' ) ) {
			$this->Factory->debug( $this->Utils->phpMemoryMax(), 1 );
			$item = null;
			$this->Factory->debug( $this->Utils->phpMemoryMax(), 1 );
		}
	}

	/**
	 * Get defined cmd line argument.
	 *
	 * @param string $name Defined app_opts[name] or empty string to get all parsed options
	 * @return string|null CMD line arg or NULL if not present
	 */
	public function getArg( string $name = '' )
	{
		if ( !$appOpts = $this->Factory->get( 'app_opts' ) ) {
			return null;
		}

		if ( !$cmdOpts = $this->Factory->get( 'cmd_opts' ) ) {
			$optS = array_column( $appOpts, 'short' );
			$optS = $this->Utils->arrayFlat( $optS );
			$optS = implode( '', $optS );
			$optL = array_column( $appOpts, 'long' );
			$cmdOpts = getopt( $optS, $optL );
			$this->Factory->cfg( 'cmd_opts', $cmdOpts );
		}

		if ( !$name ) {
			return $cmdOpts;
		}

		/*
		 * Grouped short args are parsed as array.
		 *
		 * Example: -a -cc
		 * Result: [cmd_opts] => Array (
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
		if ( is_array( $arg = $appOpts[$name]['short'] ?? false) ) {
			$opt = $arg[0][0];

			if ( isset( $cmdOpts[$opt] ) ) {
				// cast to array in case single -c was used. See notes.
				return count( (array) $cmdOpts[$opt] );
			}

			$appOpts[$name]['short'] = $opt;
		}

		// Extract defined arg switches for current option and remove 'require' signatures if any
		$nameS = rtrim( $appOpts[$name]['short'] ?? '', ':' );
		$nameL = rtrim( $appOpts[$name]['long'] ?? '', ':' );

		$value = $cmdOpts[$nameS] ?? $cmdOpts[$nameL] ?? $appOpts[$name]['default'] ?? null;

		/*
		 * CAUTION:
		 * Option switches (without value) have [false] assigned by PHP.
		 * Lets convert it to [true] so we can use it in IF clause as:
		 * if( Application->getArg('switch') )
		 */
		$value = is_bool( $value ) ? true : $value;

		return $value;
	}

	/**
	 * Get CLI args definition.
	 */
	public function getArgHelp(): array
	{
		$req = [ '', ' <value>', '=[value]' ];
		$max = 0;

		$out = [];
		foreach ( $this->Factory->get( 'app_opts' ) as $name => $arg ) {
			$valS = $valL = '';

			// Array args, like: -v|vv|vvv do not accept values!
			if ( is_array( $arg['short'] ?? false) ) {
				$arg['short'] = implode( ' | ', $arg['short'] );
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
	 * Get: name, version.
	 */
	public static function getVersion(): string
	{
		return sprintf( '%s v%s', static::APP_NAME, static::APP_VERSION );
	}

	/**
	 * Get: name, version, date.
	 */
	public static function getVersionLong(): string
	{
		return sprintf( '%s v%s (%s)', static::APP_NAME, static::APP_VERSION, static::APP_DATE );
	}

	/**
	 * Print exception message and write to php error log.
	 */
	public function exceptionHandler( \Throwable $E ): void
	{
		$this->Logger && $this->Utils && $this->Utils->writeln( implode( "\n", $this->Logger->getHistoryLogs() ), 2 );
		$this->Logger && $this->Logger->error( $E->getMessage() );
		$this->Utils && $this->Utils->exceptionPrint( $E );

		ini_get( 'log_errors' ) && error_log( $E );
		exit( $E->getCode() ?: 1 );
	}

	/**
	 * Update CMD window title.
	 *
	 * @param string|array $tokens Array( ['{token1}'] => text1, ['{token2}'] => text2, ... )
	 * @param string       $format Eg. '{token1} - {token2} - {title}'
	 */
	protected function cmdTitle( string $format = '', array $tokens = [] ): void
	{
		$format = $format ? "$format - {cmd_title}" : '{cmd_title}';
		$tokens['{cmd_title}'] = $this->Factory->get( 'cmd_title' );

		cli_set_process_title( strtr( $format, $tokens ) );
	}

	/**
	 * Initialize.
	 *
	 * Also initialize services now. For reason why so late:
	 * @see Application::__construct()
	 */
	public function run()
	{
		/* @formatter:off */
		$this->Utils->setup([
			'timeZone'   => $this->Factory->get( 'app_timezone' ),
			'dateFormat' => $this->Factory->get( 'date_long' ),
		]);
		/* @formatter:on */

		/**
		 * -------------------------------------------------------------------------------------------------------------
		 * Errors & Exceptions
		 * @see Utils::errorHandler()
		 * @see Application::exceptionHandler()
		 */
		if ( $this->Factory->get( 'err_handle' ) ) {
			set_error_handler( [ $this->Utils, 'errorHandler' ] );
		}
		if ( $this->Factory->get( 'exc_handle' ) ) {
			set_exception_handler( [ $this, 'exceptionHandler' ] );
		}

		$this->cmdTitle();
		$this->Logger = $this->Factory->Logger();

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

		if ( null !== $this->getArg( 'setup' ) ) {
			$cfg = $this->Factory->cfg();
			ksort( $cfg );
			print_r( $cfg );
			exit();
		}

		/*
		 * -------------------------------------------------------------------------------------------------------------
		 * LOG
		 *
		 * NOTE:
		 * Keep this after "Help" section to prevent extra output if App is going to exit anyway
		 */
		if ( DEBUG ) {
			$this->Logger->info( 'DEBUG is ON' );
			$this->Logger->debug( 'CMD: ' . implode( ' ', $GLOBALS['argv'] ) );
			$this->Logger->debug( 'ARGS: ' . $this->Utils->print_r( $this->getArg() ) );
			$this->Logger->debug( $this->Utils->print_r( $this->Factory->cfg() ) );
		}
	}
}
