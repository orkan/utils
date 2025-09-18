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
	const APP_VERSION = '13.0.0';
	const APP_DATE = 'Thu, 18 Sep 2025 15:34:15 +02:00';

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
	 * Files to copy.
	 */
	protected $files = [];

	/**
	 * Files count.
	 */
	protected $items = 0;

	/**
	 * Normalized dir paths.
	 * This is required to have all paths normalized before any string operations like strpos()
	 */
	protected $dirFav;
	protected $dirSrc;

	/**
	 * Total bytes to copy.
	 */
	protected $bytesMax;

	/**
	 * File types.
	 * @var array
	 */
	protected $types;

	/**
	 * File types regex.
	 * @var string
	 */
	protected $pattern;

	/*
	 * Services:
	 */
	protected $Loggex;
	protected $Files;

	/**
	 * Setup.
	 */
	public function __construct( Factory $Factory )
	{
		$this->Factory = $Factory->merge( self::defaults() );
		parent::__construct( $Factory );

		$this->cfgLoad( 'config' );
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
		 * @see \Orkan\Prompt::MODES
		 *
		 * [sync_dir_fav]
		 * Favorite source files dir. These have higher priority
		 * @see \Orkan\Prompt::MODES
		 *
		 * @formatter:off */
		return [
			// App
			'app_title'   => 'Files Sync',
			'app_usage'   => 'vendor/bin/ork-files-sync [OPTIONS]',
			'app_opts'    => [
				'config' => [ 'short' => 'c:', 'long' => 'config:', 'desc' => 'Configuration file' ],
			],
			// AppFilesSync
			'sync_bytes'     => 0,
			'sync_types'     => null,
			'sync_depth'     => 0,
			'sync_shuffle'   => true,
			'sync_dir_src'   => getenv( 'APP_SRCDIR' ) ?: '',
			'sync_dir_fav'   => getenv( 'APP_FAVDIR' ) ?: '',
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
	 * Verify user config with prompt.
	 *
	 * {@inheritDoc}
	 * @see \Orkan\Application::configure()
	 */
	protected function configure(): void
	{
		parent::configure();
		$this->Loggex = $this->Factory->Loggex();
	}

	/**
	 * Start export.
	 */
	public function run(): void
	{
		parent::run();

		// Select files
		$this->Loggex->info( '-' );
		$this->verify();
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
	 * Verify user config.
	 */
	protected function verify(): void
	{
		$Prompt = $this->Factory->Prompt();

		$this->dirFav = $Prompt->importPath( 'sync_dir_fav', [ 'msg' => 'Favorites dir', 'autodirs' => false ] );
		$this->dirSrc = $Prompt->importPath( 'sync_dir_src', [ 'msg' => 'Source dir', 'autodirs' => false ] );
		$this->dirOut = $Prompt->importPath( 'sync_dir_out', [ 'msg' => 'Output dir' ] );
		$this->Loggex->info( 'Fav dir: "%s"', $this->Utils->pathCut( $this->dirFav, 63 ) );
		$this->Loggex->info( 'Src dir: "%s"', $this->Utils->pathCut( $this->dirSrc, 63 ) );
		$this->Loggex->info( 'Out dir: "%s"', $this->Utils->pathCut( $this->dirOut, 63 ) );

		if ( !$this->dirFav && !$this->dirSrc ) {
			throw new \InvalidArgumentException(
				/**/ 'At least one source dir required. None specified! Check cfg[sync_dir_fav], cfg[sync_dir_src]' );
		}

		$this->bytesMax = $Prompt->importBytes( 'sync_bytes', 'Total size' );
		$this->Loggex->info( 'Total size: %s', $this->bytesMax ? $this->Utils->byteString( $this->bytesMax ) : 'no limit' );

		// Filter scaned files with regex?
		$this->types = (array) $this->Factory->get( 'sync_types' );
		$this->pattern = $this->types ? '~(\.' . implode( '|\.', $this->types ) . ')$~i' : '';
	}

	/**
	 * Import files: FAV + SRC (separately randomized in each group).
	 */
	protected function scan(): void
	{
		foreach ( [ $this->dirFav, $this->dirSrc ] as $dir ) {
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

		// Remove duplicates, eg. if {fav} is inside {src} dir
		$items = count( $this->files );
		$this->files = array_unique( $this->files );
		$this->items = count( $this->files );

		/* @formatter:off */
		$this->Loggex->info( 'Files found: {count} ({types})', [
			'{types}' => $this->types ? implode( '|', $this->types )  : '*',
			'{count}' => $this->items,
		]);
		/* @formatter:on */

		if ( $items > $this->items ) {
			$this->Loggex->debug( 'Duplicates removed: %s', $items - $this->items );
		}

		$this->Logger->debug( $this->Utils->phpMemoryMax() );
	}

	/**
	 * Limit files to cfg[sync_bytes].
	 */
	protected function limit(): void
	{

		// NOTE: FilesSync requires cfg[sync_dir_out] to be defined already
		$this->Files = $this->Factory->FilesSync();

		/* @formatter:off */
		$tokens = [
			'{items}'  => 0,
			'{bytes}'  => $this->Utils->byteString( 0 ),
			'{avg}'    => $this->Utils->byteString( 0 ),
			'{total}'  => $this->Utils->byteString( $this->bytesMax ),
			'{left}'   => $this->Utils->byteString( $this->bytesMax ),
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
			if ( $this->bytesMax && $bytesNow > $this->bytesMax ) {

				$this->Loggex->debug(
					/**/ 'File too big! "{file}" (now:{bytes} + file:{size} = sum:{sumNow} > tot:{total})',
					/**/ $tokens );

				// Continue to find smaller file if there is still room for an average file size
				if ( $bytesAvg <= $this->bytesMax ) {
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
			// Redirect all files from: {fav} and {src} to {out} dir. Watch out for names conflicts!
			$home = $this->dirFav && 0 === strpos( $file, $this->dirFav ) ? $this->dirFav : $this->dirSrc;
			$this->Files->add( $file, $home );

			/* @formatter:off */
			$tokens = array_merge( $tokens, [
				'{items}'  => $this->Files->stats( 'items' ),
				'{bytes}'  => $this->Utils->byteString( $this->Files->stats( 'bytes' ) ),
				'{sumAvg}' => $this->Utils->byteString( $this->Files->stats( 'bytes' ) + $this->Files->stats( 'avg' ) ),
				'{rem}'    => $this->Utils->byteString( $this->bytesMax - $this->Files->stats( 'bytes' ) ),
				'{avg}'    => $this->Utils->byteString( $this->Files->stats( 'avg' ) ),
				'{min}'    => $this->Utils->byteString( $this->Files->stats( 'min' ) ),
				'{max}'    => $this->Utils->byteString( $this->Files->stats( 'max' ) ),
			]);
			/* @formatter:on */

			$this->Loggex->debug( 'Add "{file}" [{size}]', $tokens );
		}

		// -------------------------------------------------------------------------------------------------------------
		// Summary:
		if ( $this->items > $this->Files->stats( 'items' ) ) {
			$this->Loggex->info(
				/**/ '- reduced, space remaining: {rem} (now:{bytes} + avg:{avg} = sum:{sumAvg} > rem:{rem})',
				/**/ $tokens );
		}

		$this->Loggex->info( 'Files total: {items} | {bytes} | ~{avg}', $tokens );
		$this->Loggex->debug( 'min:{min} | max:{max} | avg:{avg}', $tokens );
	}
}
