<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

use Monolog\Logger as Monolog;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;

/**
 * Factory Logger.
 *
 * Why not extend Monolog\Logger?
 * - keep Orkan\Utils dependency clean
 * - extended methods must follow parent signature (can extend but not change)
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Logger
{
	/* @formatter:off */

	/**
	 * Monolog levels.
	 */
	const DEBUG     = 100;
	const INFO      = 200;
	const NOTICE    = 250;
	const WARNING   = 300;
	const ERROR     = 400;
	const CRITICAL  = 500;
	const ALERT     = 550;
	const EMERGENCY = 600;

	/* @formatter:on */

	/**
	 * Cached log records from cfg[log_history].
	 *
	 * @var array Log messages Array (
	 *   [level]   => (int)    Logger::LEVEL
	 * 	 [message] => (string) Record
	 * 	), (...)
	 */
	private $history = [];

	/**
	 * Is Monolog installed?
	 */
	private static $isMonolog = true;

	/**
	 * Services:
	 */
	private $Factory;
	private $Logger;

	/**
	 * Build Factory Logger.
	 */
	public function __construct( Factory $Factory )
	{
		$this->Factory = $Factory->merge( self::defaults() );

		// Provide at least verbose output if no logging to file available
		if ( !class_exists( '\\Monolog\\Logger' ) ) {
			$this->Logger = new Noop();
			self::$isMonolog = false;
			return;
		}

		$this->Logger = new Monolog( $Factory->get( 'log_channel' ) );
		$this->Logger->setTimezone( new \DateTimeZone( $Factory->get( 'log_timezone' ) ) );

		// NOTE: Default log level for RotatingFileHandler is Logger::DEBUG (log everything)
		if ( $this->Factory->get( 'log_file' ) ) {
			/* @formatter:off */
			$Handler = new RotatingFileHandler(
				$this->Factory->get( 'log_file' ),
				$this->Factory->get( 'log_keep' ),
				$this->Factory->get( 'log_level' ),
			);
			/* @formatter:on */
			$Format = new LineFormatter( $Factory->get( 'log_format' ), $Factory->get( 'log_datetime' ) );
			$Handler->setFormatter( $Format );
			$this->Logger->pushHandler( $Handler );

			// Redirect PHP logs to Logger file.
			ini_set( 'error_log', $Handler->getUrl() );
		}

		// Mask sensitive data in log
		if ( $mask = $this->Factory->get( 'log_mask' ) ) {
			$this->Logger->pushProcessor( function ( $entry ) use ($mask ) {
				$entry['message'] = str_replace( $mask['search'], $mask['replace'], $entry['message'] );
				return $entry;
			} );
		}

		// Reset log file
		if ( $this->Factory->get( 'log_reset' ) ) {
			@unlink( $this->getFilename() );
		}
	}

	/**
	 * Get defaults.
	 */
	private function defaults()
	{
		/**
		 * [log_file]
		 * Full path to log file or or leave empty to skip creating log file
		 *
		 * [log_keep]
		 * @see \Monolog\Handler\RotatingFileHandler::__construct( $maxFiles )
		 *
		 * [log_datetime]
		 * Default: Y-m-d\TH:i:s.uP
		 *
		 * [log_format]
		 * Default: [%datetime%] %channel%.%level_name%: %message% %context% %extra%\n
		 *
		 * [log_mask]
		 * Replace sensitive info in logs messages
		 * Array( [search] => string|array, [replace] => string|array )
		 * @see str_replace( [search], [replace] )
		 *
		 * [log_reset]
		 * Empty previous log file
		 *
		 * [log_history]
		 * Min. log level to save in history. 0 == OFF
		 * @see Logger::getHistory()
		 *
		 * [log_verbose]
		 * Min. log level to echo. 0 == OFF
		 * @see Logger::addRecord()
		 *
		 * [log_debug]
		 * Print extra info in log file (backtrace, etc...)
		 *
		 * @formatter:off */
		return [
			'log_file'      => '',
			'log_keep'      => 5,
			'log_channel'   => __CLASS__,
			'log_timezone'  => getenv( 'LOG_TIMEZONE' ) ?: date_default_timezone_get(),
			'log_datetime'  => 'Y-m-d H:i:s',
			'log_format'    => "[%datetime%] %level_name%: %context% %message%\n",
			'log_mask'      => '',
			'log_reset'     => getenv( 'LOG_RESET'   ) ?: false,
			'log_level'     => getenv( 'LOG_LEVEL'   ) ?: self::INFO,
			'log_history'   => getenv( 'LOG_HISTORY' ) ?: 0,
			'log_verbose'   => getenv( 'LOG_VERBOSE' ) ?: 0,
			'log_debug'     => getenv( 'LOG_DEBUG'   ) ?: false,
		];
		/* @formatter:on */
	}

	/**
	 * Expose Monolog instance.
	 * Returns: \Monolog\Monolog | \Orkan\Noop !
	 *
	 * @return Monolog
	 */
	public function Monolog()
	{
		return $this->Logger;
	}

	/**
	 * Is Monolog available?
	 */
	public static function isMonolog(): bool
	{
		return self::$isMonolog;
	}

	/**
	 * Check if given level is currently handled.
	 *
	 * @param int|string $level Level name ie. 'debug', 'DEBUG', Logger::DEBUG
	 */
	public function is( $level ): bool
	{
		return self::isHandling( $this->Factory->get( 'log_level' ), $level );
	}

	/**
	 * Check if given level is currently handled by extra cfg level.
	 *
	 * @param int|string $cfgLevel Extra feature level, like: log_verbose, log_history, etc...
	 * @param int|string $nowLevel Current level to compare to
	 */
	public static function isHandling( $cfgLevel, $nowLevel ): bool
	{
		$cfgLevel = self::toLevel( $cfgLevel );
		$nowLevel = self::toLevel( $nowLevel );

		return $cfgLevel && $nowLevel >= $cfgLevel;
	}

	/**
	 * Convert string levels to Monolog ones.
	 * @see \Monolog\Logger::toMonologLevel()
	 *
	 * @param int|string $level Level name or empty ie. 'debug', 'DEBUG', Logger::DEBUG, '', 0, null
	 * @return int Monolog level or 0 if not defined
	 */
	public static function toLevel( $level ): int
	{
		static $cache = [];

		if ( isset( $cache[$level] ) ) {
			return $cache[$level];
		}

		$result = $level;

		if ( is_string( $level ) ) {
			if ( is_numeric( $level ) ) {
				$result = intval( $level );
			}
			else {
				$upper = __CLASS__ . '::' . strtr( $level, 'abcdefgilmnortuwy', 'ABCDEFGILMNORTUWY' );
				$result = defined( $upper ) ? constant( $upper ) : 0;
			}
		}

		return $cache[$level] = $cache[$result] = $result;
	}

	/**
	 * Get filename of current RotatingFileHandler
	 */
	public function getFilename(): string
	{
		$Handlers = $this->Logger->getHandlers() ?? [];

		foreach ( $Handlers as $Handler ) {
			if ( $Handler instanceof RotatingFileHandler ) {
				return $Handler->getUrl();
			}
		}

		return '';
	}

	/**
	 * Get the name of last calling function
	 *
	 * @param  int    $backtrace Add extra backtrace history levels
	 * @return string            In format [Namespace\Class->method()] $message
	 */
	private function backtrace( int $backtrace = 0 ): string
	{
		/*
		 * backtrace history (before this class):
		 * 3 ==> Logger->debug()->addRecord()->backtrace()
		 */
		$back = 3 + $backtrace;

		// https://www.php.net/manual/en/function.debug-backtrace.php
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, $back + 1 );
		$class = $trace[$back]['class'] ?? '';
		$type = $trace[$back]['type'] ?? '';
		$function = $trace[$back]['function'] ?? '';

		return isset( $trace[$back] ) ? $class . $type . $function . '()' : '{main}';
	}

	/**
	 * Get saved records >= [log_history].
	 *
	 * @return Array (
	 *   Array ( [level] => self::DEBUG, [message] => 'message 1' ] ),
	 *   Array ( [level] => self::ERROR, [message] => 'message 2' ] ),
	 * )
	 */
	public function getHistory(): array
	{
		return $this->history;
	}

	/**
	 * Get formated records >= [log_history].
	 *
	 * @return string[] (
	 *   WARNING: Message 1,
	 *   ERROR: Message 2,
	 * )
	 */
	public function getHistoryLogs(): array
	{
		$out = [];

		foreach ( $this->history as $rec ) {
			$out[] = sprintf( '%s: %s', self::getLevelName( $rec['level'] ), $rec['message'] );
		}

		return $out;
	}

	/**
	 * Get the name of the logging level.
	 */
	public static function getLevelName( int $level ): string
	{
		return self::isMonolog() ? Monolog::getLevelName( $level ) : "LEVEL_{$level}";
	}

	/**
	 * Add a log record.
	 *
	 * @param int $backtrace Add backtrace info in DEBUG mode. -1: OFF
	 */
	public function addRecord( int $level, string $message, int $backtrace = 0, array $context = [] ): ?bool
	{
		// Save history?
		if ( self::isHandling( $this->Factory->get( 'log_history' ), $level ) ) {
			$this->history[] = [ 'level' => $level, 'message' => $message ];
		}

		// Echo?
		if ( self::isHandling( $this->Factory->get( 'log_verbose' ), $level ) ) {
			if ( defined( 'TESTING' ) ) {
				throw new \LogicException( $message );
			}
			echo $message . "\n";
		}

		// Add to log file?
		if ( !self::isHandling( $this->Factory->get( 'log_level' ), $level ) ) {
			return false;
		}

		// Add method name?
		if ( $this->Factory->get( 'log_debug' ) && 0 <= $backtrace ) {
			$context[] = $this->backtrace( $backtrace );
		}

		return $this->Logger->addRecord( $level, $message, $context );
	}

	/**
	 * Add record: [DEBUG]
	 *
	 * @param int $backtrace Incrase backtrace level.
	 */
	public function debug( string $message, int $backtrace = 0 ): ?bool
	{
		return $this->addRecord( self::DEBUG, $message, $backtrace );
	}

	/**
	 * Add record: [INFO]
	 */
	public function info( string $message, int $backtrace = 0 ): ?bool
	{
		return $this->addRecord( self::INFO, $message, $backtrace );
	}

	/**
	 * Add record: [NOTICE]
	 */
	public function notice( string $message, int $backtrace = 0 ): ?bool
	{
		return $this->addRecord( self::NOTICE, $message, $backtrace );
	}

	/**
	 * Add record: [WARNING]
	 *
	 * @param int $backtrace Incrase backtrace level.
	 */
	public function warning( string $message, int $backtrace = 0 ): ?bool
	{
		return $this->addRecord( self::WARNING, $message, $backtrace );
	}

	/**
	 * Add record: [ERROR]
	 *
	 * @param int $backtrace Incrase backtrace level.
	 */
	public function error( string $message, int $backtrace = 0 ): ?bool
	{
		return $this->addRecord( self::ERROR, $message, $backtrace );
	}
}
