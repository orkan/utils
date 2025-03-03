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
		return sprintf(
			<<<'EOT'
			%1$s
			%2$s - %3$s
			Loaded: %5$s [<a href="%6$s">refresh</a>]
			
			Usage:
				%4$s[?switch=1&switch=1&etc=...]
			Switches:
				Clear Apache error log   <a href="?clearlog_sapi=1">clearlog_sapi=1</a>
				Clear PHP CLI error log  <a href="?clearlog_cli=1">clearlog_cli=1</a>
				Print $_SERVER array     <a href="?server_info=1">server_info=1</a>
				Print phpinfo()          <a href="?php_info=1">php_info=1</a>
			EOT
			,
			/*1*/ self::$logo,
			/*2*/ self::APP_NAME,
			/*3*/ self::APP_DESC,
			/*4*/ self::getUrl(),
			/*5*/ Utils::dateString(),
			/*6*/ $_SERVER['PHP_SELF'] . ( isset( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ),
		);
		/* @formatter:on */
	}

	public static function getUrl()
	{
		/* @formatter:off */
		return sprintf( '%1$s://%2$s%3$s',
			/*1*/ $_SERVER['REQUEST_SCHEME'] ?? 'http',
			/*2*/ $_SERVER['HTTP_HOST'],
			/*3*/ $_SERVER['SCRIPT_NAME'],
		);
		/* @formatter:on */
	}

	public static function run( $timeZone = 'UTC', $dateFormat = DATE_RFC822 )
	{
		Utils::setup( [ 'timeZone' => $timeZone, 'dateFormat' => $dateFormat ] );

		$switches = [];
		parse_str( $_SERVER['QUERY_STRING'] ?? '', $switches );
		$logMsg = sprintf( "[%s] [%s] Log cleared by: %s\n", date( 'Y-m-d H:i:s' ), DebugStarter::APP_NAME, DebugStarter::getUrl() );

		echo '<pre>';
		echo DebugStarter::getHelp();
		echo "\n\n";

		if ( $switches['clearlog_sapi'] ?? false) {
			echo 'Clear Apache error log: ';
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
