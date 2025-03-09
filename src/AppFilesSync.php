<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * App: Files Sync.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class AppFilesSync extends Application
{
	const APP_NAME = 'Copy files with priority, shuffle and size limit';
	const APP_VERSION = '10.1.2';
	const APP_DATE = 'Sun, 09 Mar 2025 05:33:59 +01:00';

	/**
	 * @link https://patorjk.com/software/taag/#p=display&v=0&f=Speed&t=File-Sync
	 * @link usr/php/logo/logo.php
	 */
	const LOGO = '____________________           ________
___  ____/__(_)__  /____       __  ___/____  _______________
__  /_   __  /__  /_  _ \\___________ \\__  / / /_  __ \\  ___/
_  __/   _  / _  / /  __//_____/___/ /_  /_/ /_  / / / /__
/_/      /_/  /_/  \\___/       /____/ _\\__, / /_/ /_/\\___/
                                      /____/';

	/**
	 * Files match regex.
	 */
	protected $pattern;

	/**
	 * Files to copy.
	 */
	protected $files = [];

	/*
	 * Services:
	 */
	protected $Files;

	/**
	 * Setup.
	 */
	public function __construct( Factory $Factory )
	{
		$this->Factory = $Factory->merge( self::defaults() );
		parent::__construct( $Factory );

		$this->loadUserConfig( 'config' );

		// Filter scaned files with regex?
		if ( $this->pattern = $this->Factory->get( 'sync_types' ) ) {
			$this->pattern = implode( '|\.', $this->pattern );
			$this->pattern = "~(\.{$this->pattern})$~i";
		}
	}

	/**
	 * Get defaults.
	 */
	protected function defaults(): array
	{
		/**
		 * [sync_bytes]
		 * Limit total size. Use 0 for no limit
		 *
		 * [sync_types]
		 * List of file extensions to sync. Use NULL to disable filtering
		 *
		 * [sync_depth]
		 * Max scaned dirs depth. Use -1 for no limit
		 *
		 * [sync_shuffle]
		 * Shuffle found files before applying limits?
		 *
		 * [sync_dir_src]
		 * Source files dir
		 *
		 * [sync_dir_fav]
		 * Favorite source files dir. These have higher priority
		 *
		 * @formatter:off */
		return [
			// App
			'app_title'   => 'Files Sync',
			'app_usage'   => 'vendor/bin/ork-files-sync [options]',
			'app_opts'    => [
				'config' => [ 'short' => 'c:', 'long' => 'config:', 'desc' => 'Configuration file' ],
			],
			// AppFilesSync
			'sync_bytes'     => 0,
			'sync_types'     => null,
			'sync_depth'     => 0,
			'sync_shuffle'   => true,
			'sync_dir_src'   => '',
			'sync_dir_fav'   => '',
			// FileSync
			'sync_callback' => [ $this, 'cbFileSync' ],
		];
		/* @formatter:on */
	}

	/**
	 * Callback: FileSync.
	 * @param array $data Progress info
	 */
	public function cbFileSync( array $tokens ): void
	{
		/* @formatter:off */
		$this->cmdTitle( '[{cent_done}%] {time_left} left at {speed_bps}/s - {done} done - "{path}"', [
			'{cent_done}' => $tokens['progress']['cent_done'],
			'{done}'      => $tokens['{done}'],
			'{time_left}' => $this->Utils->timeString( $tokens['progress']['time_left'], 0 ),
			'{speed_bps}' => $this->Utils->byteString( $tokens['progress']['speed_bps'] ),
			'{path}'      => $this->Utils->pathCut( $tokens['{src}'], 120 ),
		]);
		/* @formatter:on */
	}

	/**
	 * Start export.
	 */
	public function run(): void
	{
		parent::run();

		// Verify config
		$this->Loggex->info( '-' );
		$this->configure();
		$this->Files = $this->Factory->FilesSync();

		// Select files
		$this->Loggex->info( '-' );
		$this->scan();
		$this->limit();
		$this->gc( $this->files ); // release memory!

		// Copy files
		$this->Loggex->info( '-' );
		$this->Files->run();

		// Finalize
		$this->Loggex->info( '-' );
		$this->cmdTitle();
		$this->Logger->notice( 'Done.' );
	}

	/**
	 * Limit files to cfg[sync_bytes].
	 */
	protected function limit(): void
	{
		$bytesMax = $this->Factory->get( 'sync_bytes' );
		$dirSrc = $this->Factory->get( 'sync_dir_src' );
		$dirFav = $this->Factory->get( 'sync_dir_fav' );

		/* @formatter:off */
		$tokens = [
			'{items}'  => 0,
			'{bytes}'  => $this->Utils->byteString( 0 ),
			'{avg}'    => $this->Utils->byteString( 0 ),
			'{total}'  => $this->Utils->byteString( $bytesMax ),
			'{left}'   => $this->Utils->byteString( $bytesMax ),
		];
		/* @formatter:on */

		// -------------------------------------------------------------------------------------------------------------
		// Add files:
		foreach ( $this->files as $file ) {
			$stat = stat( $file );
			$bytesNow = $this->Files->stats( 'bytes' ) + $stat['size'];
			$bytesAvg = $this->Files->stats( 'bytes' ) + $this->Files->stats( 'avg' );

			/* @formatter:off */
			$tokens = array_merge( $tokens, [
				'{file}'   => basename( $file ),
				'{size}'   => $this->Utils->byteString( $stat['size'] ),
				'{sumNow}' => $this->Utils->byteString( $bytesNow ),
				'{sumAvg}' => $this->Utils->byteString( $bytesAvg ),
			]);
			/* @formatter:on */

			// ----------------------------------------------------------------------------------------------------------
			// Check size:
			if ( $bytesMax && $bytesNow > $bytesMax ) {

				$this->Loggex->debug(
					/**/ 'File too big! "{file}" (now:{bytes} + file:{size} = sum:{sumNow} > tot:{total})',
					/**/ $tokens );

				// Continue to find smaller file if there is still room for an average file size
				if ( $bytesAvg <= $bytesMax ) {
					$this->Loggex->debug(
						/**/ 'Find smaller file... (now:{bytes} + avg:{avg} = sum:{sumAvg} <= tot:{total})',
						/**/ $tokens );
					continue;
				}

				// Skip adding files...
				break;
			}

			// ----------------------------------------------------------------------------------------------------------
			// Add:
			// Redirect all files from: [fav], [src] to [out] dir. Watch out for names conflict!
			$home = $dirFav && 0 === strpos( $file, $dirFav ) ? $dirFav : $dirSrc;
			$this->Files->add( $file, $home );

			/* @formatter:off */
			$tokens = array_merge( $tokens, [
				'{items}'  => $this->Files->stats( 'items' ),
				'{bytes}'  => $this->Utils->byteString( $this->Files->stats( 'bytes' ) ),
				'{sumAvg}' => $this->Utils->byteString( $this->Files->stats( 'bytes' ) + $this->Files->stats( 'avg' ) ),
				'{left}'   => $this->Utils->byteString( $bytesMax - $this->Files->stats( 'bytes' ) ),
				'{avg}'    => $this->Utils->byteString( $this->Files->stats( 'avg' ) ),
				'{min}'    => $this->Utils->byteString( $this->Files->stats( 'min' ) ),
				'{max}'    => $this->Utils->byteString( $this->Files->stats( 'max' ) ),
			]);
			/* @formatter:on */

			$this->Loggex->debug( 'Add "{file}" [{size}]', $tokens );
		}

		// -------------------------------------------------------------------------------------------------------------
		// Summary:
		if ( count( $this->files ) > $this->Files->stats( 'items' ) ) {
			$this->Loggex->info(
				/**/ '- reduced, space left: {left} (now:{bytes} + avg:{avg} = sum:{sumAvg} > tot:{total})',
				/**/ $tokens );
		}

		$this->Loggex->info( 'Files total: {items} | {bytes} | ~{avg}', $tokens );
		$this->Loggex->debug( 'min:{min} | max:{max} | avg:{avg}', $tokens );
	}

	/**
	 * Verify (prompt) user config.
	 */
	protected function configure(): void
	{
		$Prompt = $this->Factory->Prompt();

		$bytesMax = $Prompt->importBytes( 'sync_bytes', 'Total size' );
		$this->Logger->info( 'Total size: ' . ( $bytesMax ? $this->Utils->byteString( $bytesMax ) : 'no limit' ) );

		$dirFav = $Prompt->importPath( 'sync_dir_fav', 'Favorites dir' );
		$dirSrc = $Prompt->importPath( 'sync_dir_src', 'Source dir' );
		$dirOut = $Prompt->importPath( 'sync_dir_out', 'Output dir' );
		$this->Loggex->info( 'Fav dir: "%s"', $this->Utils->pathCut( $dirFav, 69 ) );
		$this->Loggex->info( 'Src dir: "%s"', $this->Utils->pathCut( $dirSrc, 69 ) );
		$this->Loggex->info( 'Out dir: "%s"', $this->Utils->pathCut( $dirOut, 69 ) );

		if ( !$dirFav && !$dirSrc ) {
			throw new \InvalidArgumentException(
				/**/ 'At least one source dir required. None specified! Check cfg[sync_dir_fav], cfg[sync_dir_src]' );
		}
	}

	/**
	 * Import files: FAV + SRC (separately randomized in each group).
	 */
	protected function scan(): void
	{
		/* @formatter:off */
		$dirs = [
			$this->Factory->get( 'sync_dir_fav' ),
			$this->Factory->get( 'sync_dir_src' ),
		];
		/* @formatter:on */

		foreach ( $dirs as $dir ) {
			if ( is_dir( $dir ) ) {
				$files = $this->Utils->dirScan( $dir, $this->pattern, $this->Factory->get( 'sync_depth' ) );
				if ( $this->Factory->get( 'sync_shuffle' ) ) {
					/* @formatter:off */
					$this->Loggex->info( '- shuffle {count} files in "{dir}"', [
						'{count}' => count( $files ),
						'{dir}'   => $dir
					]);
					/* @formatter:on */
					$this->Utils->arrayShuffle( $files );
				}
				$this->files = array_merge( $this->files, $files );
			}
		}

		/* @formatter:off */
		$this->Loggex->info( 'Files found: {count} ({types})', [
			'{types}' => $this->pattern ? implode( '|', $this->Factory->get( 'sync_types' ) )  : '*',
			'{count}' => count( $this->files ),
		]);
		/* @formatter:on */

		$this->Logger->debug( $this->Utils->phpMemoryMax() );
	}
}
