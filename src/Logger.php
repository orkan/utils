<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2024 Orkan <orkans+utils@gmail.com>
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
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Logger
{
	/* @formatter:off */

	/**
	 * Special level to disable logging features.
	 */
	const NONE = 0;

	/**
	 * Map Monolog levels.
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
	 * Cache results of Monolog::isHandling($level).
	 */
	protected $handling = [];

	/**
	 * Cached log records from cfg[log_history].
	 */
	protected $history = [];

	/**
	 * Monolog instance.
	 *
	 * @var \Monolog\Logger
	 */
	protected $Logger;

	/**
	 * @var Factory
	 */
	protected $Factory;

	/**
	 * Build Factory Logger.
	 */
	public function __construct( Factory $Factory )
	{
		$this->Factory = $Factory->merge( $this->defaults() );

		// Provide at least verbose output if no logging to file available
		if ( !class_exists( '\\Monolog\\Logger' ) ) {
			$this->Logger = new Noop();
			return;
		}

		$this->Logger = new Monolog( $Factory->cfg( 'log_channel' ) );
		$this->Logger->setTimezone( new \DateTimeZone( $Factory->cfg( 'log_timezone' ) ) );

		// NOTE: Default log level for RotatingFileHandler is Logger::DEBUG (log everything)
		if ( $this->Factory->get( 'log_file' ) ) {
			/* @formatter:off */
			$Handler = new RotatingFileHandler(
				$this->Factory->cfg( 'log_file' ),
				$this->Factory->cfg( 'log_keep' ),
				$this->Factory->cfg( 'log_level' ),
			);
			/* @formatter:on */
			$Format = new LineFormatter( $Factory->cfg( 'log_format' ), $Factory->cfg( 'log_datetime' ) );
			$Handler->setFormatter( $Format );
			$this->Logger->pushHandler( $Handler );

			// Redirect all unhandled php errors to this log file.
			ini_set( 'error_log', $Handler->getUrl() );
		}

		// Mask sensitive data in log
		if ( $mask = $this->Factory->cfg( 'log_mask' ) ) {
			$this->Logger->pushProcessor( function ( $entry ) use ($mask ) {
				$entry['message'] = str_replace( $mask['search'], $mask['replace'], $entry['message'] );
				return $entry;
			} );
		}

		// Reset log file
		if ( $this->Factory->cfg( 'log_reset' ) ) {
			@unlink( $this->getFilename() );
		}
	}

	/**
	 * Get defaults.
	 */
	protected function defaults()
	{
		/**
		 * [log_format]
		 * Default: [%datetime%] %channel%.%level_name%: %message% %context% %extra%\n
		 *
		 * [log_datetime]
		 * Default: Y-m-d\TH:i:s.uP
		 *
		 * [log_keep]
		 * @see \Monolog\Handler\RotatingFileHandler::__construct( $maxFiles )
		 *
		 * [log_reset]
		 * Empty previous log file
		 *
		 * [log_history]
		 * Min. log level to save in history. 0 == OFF
		 * Array (
		 *   Array ( [level] => self::DEBUG, [message] => 'message 1' ] ),
		 *   Array ( [level] => self::ERROR, [message] => 'message 2' ] ),
		 * )
		 *
		 * [log_verbose]
		 * Min. log level to echo. 0 == OFF
		 *
		 * [log_debug]
		 * Print extra info in log file (backtrace, etc...)
		 *
		 * @formatter:off */
		return [
			'log_channel'   => getenv( 'LOG_CHANNEL'  ) ?: __CLASS__,
			'log_timezone'  => getenv( 'LOG_TIMEZONE' ) ?: date_default_timezone_get(),
			'log_format'    => getenv( 'LOG_FORMAT'   ) ?: "[%datetime%] %level_name%: %context% %message%\n",
			'log_datetime'  => getenv( 'LOG_DATETIME' ) ?: 'Y-m-d H:i:s',
			'log_keep'      => getenv( 'LOG_KEEP'     ) ?: 5,
			'log_reset'     => getenv( 'LOG_RESET'    ) ?: false,
			'log_level'     => getenv( 'LOG_LEVEL'    ) ?: self::INFO,
			'log_history'   => getenv( 'LOG_HISTORY'  ) ?: self::NONE,
			'log_verbose'   => getenv( 'LOG_VERBOSE'  ) ?: self::NONE,
			'log_debug'     => getenv( 'LOG_DEBUG'    ) ?: false,
		];
		/* @formatter:on */
	}

	/**
	 * Expose original Monolog.
	 */
	public function Monolog(): Monolog
	{
		return $this->Logger;
	}

	/**
	 * Check if given level is currently handled, aka: $level >= cfg[log_level].
	 *
	 * @param mixed $level Level name ie. 'debug' or Level constant ie. Logger::DEBUG
	 */
	public function is( $level ): ?bool
	{
		$level = $this->Logger->toMonologLevel( $level );
		$this->handling[$level] = $this->handling[$level] ?? $this->Logger->isHandling( $level );

		return $this->handling[$level];
	}

	/**
	 * Check if given level is currently handled by extra cfg level.
	 *
	 * @param int $cfgLevel Extra feature level, like: log_verbose, log_history, etc...
	 * @param int $level    Current level
	 */
	protected static function isHandling( int $cfgLevel, int $level ): bool
	{
		return self::NONE !== $cfgLevel && $cfgLevel <= $level;
	}

	/**
	 * Get filename of current RotatingFileHandler
	 */
	public function getFilename(): string
	{
		foreach ( $this->Monolog()->getHandlers() as $Handler ) {
			if ( $Handler instanceof RotatingFileHandler ) {
				return realpath( $Handler->getUrl() );
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
	 * Get saved records
	 */
	public function getHistory(): array
	{
		return $this->history;
	}

	/**
	 * Get the name of the logging level
	 */
	public static function getLevelName( int $level ): string
	{
		return class_exists( '\\Monolog\\Logger' ) ? Monolog::getLevelName( $level ) : "LEVEL_{$level}";
	}

	/**
	 * Add a log record.
	 *
	 * @param int $backtrace Add backtrace info in DEBUG mode. -1: OFF
	 */
	public function addRecord( int $level, string $message, int $backtrace = 0, array $context = [] ): bool
	{
		// Save history?
		if ( self::isHandling( $this->Factory->cfg( 'log_history' ), $level ) ) {
			$this->history[] = [ 'level' => $level, 'message' => $message ];
		}

		// Echo?
		if ( self::isHandling( $this->Factory->cfg( 'log_verbose' ), $level ) ) {
			if ( defined( 'TESTING' ) ) {
				throw new \LogicException( $message );
			}

			printf( "%s\n", $message );
		}

		// Add to log file?
		if ( !$this->is( $level ) ) {
			return false;
		}

		// Add method name?
		if ( $this->Factory->get( 'log_debug' ) && $this->is( self::DEBUG ) && 0 <= $backtrace ) {
			$context[] = $this->backtrace( $backtrace );
		}

		return $this->Logger->addRecord( $level, $message, $context );
	}

	/**
	 * Add record: [DEBUG]
	 *
	 * @param int $backtrace Incrase backtrace level.
	 */
	public function debug( string $message, int $backtrace = 0 ): bool
	{
		return $this->addRecord( self::DEBUG, $message, $backtrace );
	}

	/**
	 * Add record: [INFO]
	 */
	public function info( string $message, int $backtrace = 0 ): bool
	{
		return $this->addRecord( self::INFO, $message, $backtrace );
	}

	/**
	 * Add record: [NOTICE]
	 */
	public function notice( string $message, int $backtrace = 0 ): bool
	{
		return $this->addRecord( self::NOTICE, $message, $backtrace );
	}

	/**
	 * Add record: [WARNING]
	 *
	 * @param int $backtrace Incrase backtrace level.
	 */
	public function warning( string $message, int $backtrace = 0 ): bool
	{
		return $this->addRecord( self::WARNING, $message, $backtrace );
	}

	/**
	 * Add record: [ERROR]
	 *
	 * @param int $backtrace Incrase backtrace level.
	 */
	public function error( string $message, int $backtrace = 0 ): bool
	{
		return $this->addRecord( self::ERROR, $message, $backtrace );
	}
}
