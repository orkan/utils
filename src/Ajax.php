<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2026 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * Simple Ajax endpoint solution.
 *
 * Using API key:
 * - save it in cfg[api_key]
 * - send it as JS header named cfg[api_key_name]
 *
 * USAGE:
 * <pre>
 * // ------------------------------------------------------------------------------------------------------------------
 * Ajax.init([
 *   'api_key' => '...',
 *   'php_ini' => [
 *     'error_log' => 'err.log',
 *   ],
 * ]);
 *
 * // ------------------------------------------------------------------------------------------------------------------
 * // Read request sent by: XMLHttpRequest Object
 * // Content-Type: "application/x-www-form-urlencoded; charset=UTF-8"
 * $request = $_GET || $_POST;
 *
 * // ------------------------------------------------------------------------------------------------------------------
 * // Read request sent by: Fetch API
 * // Content-Type: "multipart/form-data"
 * $request = file_get_contents( 'php://input' ); // PHP 7
 * [$_POST, $_FILES] = request_parse_body(); // PHP 8
 *
 * // ------------------------------------------------------------------------------------------------------------------
 * // Send response:
 * // Content-Type: "application/json; charset=utf-8"
 * Ajax::send( mixed $data );
 * </pre>
 *
 * @author Orkan <orkans@gmail.com>
 */
class Ajax
{
	protected static $cfg;

	/**
	 * Prepare PHP environment for Ajax request.
	 */
	public static function setup( array $cfg = [] ): void
	{
		self::setExceptionHandler();

		if ( !self::$cfg ) {
			// Merge with defaults
			self::$cfg = array_replace_recursive( self::defaults(), $cfg );

			// PHP setup
			date_default_timezone_set( self::$cfg['time_zone'] );

			foreach ( self::$cfg['php_ini'] as $k => $v ) {
				isset( $v ) && ini_set( $k, $v );
			}
		}

		// Remove exposed PHP header.
		//header_remove( 'X-Powered-By' );

		// Verify API key, negotiate pre-CORS, etc...
		self::auth();
	}

	/**
	 * Get defaults.
	 */
	protected static function defaults(): array
	{
		/**
		 * [api_key]
		 * Authenticate ajax requests by compare with requested header api key
		 *
		 * [api_key_name]
		 * Api key header name
		 *
		 * -------------------------------------------------------------------------------------------------------------
		 * PHP INI: Prepare for CLI
		 * @link https://www.php.net/manual/en/errorfunc.configuration.php
		 *
		 * Tip:
		 * Use NULL to skip set_ini()
		 *
		 * [allow_url_fopen]
		 * Allow file_get_contents('php://input') to read JS::fetch() requests.
		 * @link https://stackoverflow.com/questions/19146984/file-get-contentsphp-input-always-returns-an-empty-string
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
		 * see Logger::__construct()
		 *
		 * @formatter:off */
		return [
			'api_key'       => null,
			'api_key_name'  => 'X-Api-Key',
			'time_zone'     => getenv( 'APP_TIMEZONE' ) ?: date_default_timezone_get(),
			'php_ini'       => [
				'allow_url_fopen'        => '1',
				'max_execution_time'     => null,
				'error_reporting'        => E_ALL,
				'log_errors'             => '1',
				'log_errors_max_len'     => '0',
				'ignore_repeated_errors' => '1',
				'ignore_repeated_source' => '0',
				'html_errors'            => '0',
				'error_log'              => getenv( 'LOG_FILE' ) ?: ini_get( 'error_log' ),
			],
		];
		/* @formatter:on */
	}

	/**
	 * see \Orkan\WP\AirDB\AirDB::ajaxSetExceptionHandler()
	 */
	public static function setExceptionHandler(): void
	{
		// Turn Errors into Exceptions
		set_error_handler( [ Utils::class, 'errorHandler' ] );

		// Log & Json error
		set_exception_handler( function ( \Throwable $E ) {
			self::logException( $E ); // because we like it ;)
			self::send( get_class( $E ) . ': ' . $E->getMessage(), $E->getCode() ?: 255 ); // +die!
		} );
	}

	/**
	 * Log exception to PHP error log.
	 * @see \Orkan\Thumbnail::logException()
	 */
	protected static function logException( \Throwable $E ): void
	{
		$out = [];

		/* @formatter:off */
		foreach ([
			'Message' => $E->getMessage(),
			'REFERER' => $_SERVER['HTTP_REFERER'] ?? '',
			'REQUEST' => $_SERVER['REQUEST_URI'] ?? '',
			'Caught'  => $E,
		] as $k => $v ) {
			$out[] = "$k: $v";
		}
		/* @formatter:on */

		error_log( implode( "\n", $out ) );
	}

	/**
	 * Authenticate AJAX request.
	 *
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CORS#preflighted_requests
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Methods/OPTIONS#preflighted_requests_in_cors
	 */
	protected static function auth(): void
	{
		// Allow all CORS requests.
		header( 'Access-Control-Allow-Origin: *' );

		// Allow preflighted (pre-CORS) request.
		if ( 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
			header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS' );
			header( 'Access-Control-Allow-Headers: Content-Type, ' . self::$cfg['api_key_name'] );
			header( 'Access-Control-Max-Age: 86400' ); // cache this response for 1 day
			header( 'HTTP/1.1 204 No Content' ); // or 'HTTP/1.1 200 OK'
			exit();
		}

		// Verify API key.
		$headers = getallheaders(); // or $_SERVER[HTTP_???]
		$api = self::$cfg['api_key'];
		$key = $headers[self::$cfg['api_key_name']] ?? null;
		if ( $api && $api !== $key ) {
			self::send( "Wrong Api Key: '$key'", 403 );
		}
	}

	/**
	 * Send JSON response and exit.
	 */
	public static function send( $value, int $code = 0 ): void
	{
		$data = $error = null;

		if ( $code ) {
			$error = $value;
			/**
			 * Send HTTP Status code along with message.
			 * @link https://www.php.net/http_response_code
			 *
			 * Note that you can NOT set arbitrary response codes:
			 * http_response_code( $code );
			 *
			 * To send HTTP Status code with custom message use header():
			 */
			header( "HTTP/1.1 500 API error #$code" );
		}
		else {
			$data = $value;
		}

		$body = [ 'data' => $data, 'error' => $error ];
		$json = json_encode( $body );

		header( 'Content-Type: application/json; charset=utf-8' );
		die( $json );
	}
}
