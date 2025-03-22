<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */

/**
 * Eclipse Debug Starter.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

class DebugStarter
{
	const APP_NAME = 'Eclipse DEBUG starter';
	const APP_DESC = 'Use <a href="' . __FILE__ . '">this</a> file to init debug in Eclipse.';

	/**
	 * @link https://patorjk.com/software/taag/#p=display&v=0&f=Slant&t=Eclipse-DEBUG
	 * @link Utils\usr\php\logo.php
	 */
	private static $logo = '    ______     ___                       ____  __________  __  ________
   / ____/____/ (_)___  ________        / __ \\/ ____/ __ )/ / / / ____/
  / __/ / ___/ / / __ \\/ ___/ _ \\______/ / / / __/ / __  / / / / / __
 / /___/ /__/ / / /_/ (__  )  __/_____/ /_/ / /___/ /_/ / /_/ / /_/ /
/_____/\\___/_/_/ .___/____/\\___/     /_____/_____/_____/\\____/\\____/
              /_/';

	public static function getHelp()
	{
		/* @formatter:off */
		return strtr(
			<<<'EOT'
			{logo}
			{name} - {desc}
			Loaded: {date} [<a href="{query}">refresh</a>]
			
			Usage:
				{url}[?switch=1&switch=1&etc=...]
			Switches:
				Clear Server error log   <a href="?clearlog_sapi=1">clearlog_sapi=1</a>
				Clear PHP CLI error log  <a href="?clearlog_cli=1">clearlog_cli=1</a>
				Print $_SERVER array     <a href="?server_info=1">server_info=1</a>
				Print phpinfo()          <a href="?php_info=1">php_info=1</a>
			EOT
			, [
			'{logo}'  => self::$logo,
			'{name}'  => self::APP_NAME,
			'{desc}'  => self::APP_DESC,
			'{url}'   => self::getUrl(),
			'{date}'  => Utils::dateString(),
			'{query}' => $_SERVER['PHP_SELF'] . ( isset( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ),
		]);
		/* @formatter:on */
	}

	public static function getUrl()
	{
		/* @formatter:off */
		return strtr( '{scheme}://{host}{file}', [
			'{scheme}' => static::getUrlScheme(),
			'{host}'   => $_SERVER['HTTP_HOST'],
			'{file}'   => $_SERVER['SCRIPT_NAME'],
		]);
		/* @formatter:on */
	}

	public static function getUrlScheme()
	{
		$isHttps = false;
		$isHttps |= isset( $_SERVER['REQUEST_SCHEME'] ) && $_SERVER['REQUEST_SCHEME'] === 'https';
		$isHttps |= isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on';
		$isHttps |= isset( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] === '443';
		return $isHttps ? 'https' : 'http';
	}

	public static function run( $timeZone = 'UTC', $dateFormat = DATE_RFC822 )
	{
		Utils::setup( [ 'timeZone' => $timeZone, 'dateFormat' => $dateFormat ] );

		$switches = [];
		parse_str( $_SERVER['QUERY_STRING'] ?? '', $switches );
		$logMsg = sprintf( "[%s] [%s] Log cleared by: %s\n", date( 'Y-m-d H:i:s' ), static::APP_NAME, static::getUrl() );

		echo '<pre>';
		echo static::getHelp();
		echo "\n\n";

		if ( $switches['clearlog_sapi'] ?? false) {
			echo 'Clear Server error log: ';
			if ( false !== file_put_contents( $log = ini_get( 'error_log' ), $logMsg ) ) {
				echo "$log - Done!\n";
			}
		}

		if ( $switches['clearlog_cli'] ?? false) {
			echo 'Clear CLI error log: ';
			$log = 'n/a';
			$lines = explode( "\n", shell_exec( 'php -i' ) );
			foreach ( $lines as $line ) {
				if ( 0 === strpos( $line, 'error_log' ) ) {
					$line = explode( ' => ', $line );
					$log = array_pop( $line );
					break;
				}
			}

			if ( false !== file_put_contents( $log, $logMsg ) ) {
				echo "$log - Done!\n";
			}
		}

		if ( $switches['server_info'] ?? false) {
			echo '$_SERVER: ' . print_r( $_SERVER, true );
		}

		if ( $switches['php_info'] ?? false) {
			echo phpinfo();
		}
	}
}
