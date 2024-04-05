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
	const APP_NAME = 'Copy files with: priority, randomizer, size limit';
	const APP_VERSION = '7.0.0';
	const APP_DATE = 'Fri, 05 Apr 2024 17:47:14 +02:00';

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

		if ( !count( $this->Factory->get( 'file_types', [] ) ) ) {
			throw new \InvalidArgumentException( 'No file types specified! Check cfg[file_types]' );
		}

		$this->pattern = implode( '|\.', $this->Factory->get( 'file_types' ) );
		$this->pattern = "~(\.{$this->pattern})$~i";
	}

	/**
	 * Get defaults.
	 */
	private function defaults(): array
	{
		/**
		 * [dir_src]
		 * Source files dir
		 *
		 * [dir_fav]
		 * Favorite source files dir. These have higher priority
		 *
		 * [dir_out]
		 * Copy files to...
		 *
		 * [max_bytes]
		 * Limit total size
		 *
		 * [user_quit]
		 * User input quit sequence
		 *
		 * @formatter:off */
		return [
			'cmd_title'   => 'Files Sync',
			'app_usage'   => 'vendor/bin/ork-files-sync [options]',
			'app_opts'    => [
				'config' => [ 'short' => 'c:', 'long' => 'config:', 'desc' => 'Configuration file' ],
			],
			'file_types'  => [ 'avi', 'flv' , 'gif', 'jpe?g', 'mp4', 'png' ],
			'total_bytes' => 0,
			'dir_src'     => '',
			'dir_fav'     => '',
			'dir_out'     => '',
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
		$this->cmdTitle( '[{cent_done}%] {time_left} left at {speed_bps}/s - "{dst}"', [
			'{cent_done}' => $tokens['progress']['cent_done'],
			'{time_left}' => $this->Utils->timeString( $tokens['progress']['time_left'], 0 ),
			'{speed_bps}' => $this->Utils->byteString( $tokens['progress']['speed_bps'] ),
			'{dst}'       => $tokens['{dst}'],
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
		$this->configure();
		$this->Files = $this->Factory->FilesSync();

		// Select files
		$this->Factory->info();
		$this->scan();
		$this->limit();
		$this->gc( $this->files ); // release memory!

		// Copy files
		$this->Files->run();

		// Finalize
		$this->cmdTitle();
		$this->Logger->notice( 'Done.' );
	}

	/**
	 * Limit files to cfg[total_bytes].
	 */
	protected function limit(): void
	{
		$total = $this->Factory->get( 'total_bytes' );
		$dirSrc = $this->Factory->get( 'dir_src' );
		$dirFav = $this->Factory->get( 'dir_fav' );

		/* @formatter:off */
		$tokens = [
			'{items}'  => 0,
			'{bytes}'  => $this->Utils->byteString( 0 ),
			'{avg}'    => $this->Utils->byteString( 0 ),
			'{total}'  => $this->Utils->byteString( $total ),
			'{left}'   => $this->Utils->byteString( $total ),
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

			// ---------------------------------------------------------------------------------------------------------
			// Check size:
			if ( $total && $bytesNow > $total ) {

				$this->Factory->debug(
					/**/ 'File too big! "{file}" (now:{bytes} + file:{size} = sum:{sumNow} > tot:{total})',
					/**/ $tokens );

				// Continue to find smaller file if there is still room for an average file size
				if ( $bytesAvg <= $total ) {
					$this->Factory->debug(
						/**/ 'Find smaller file... (now:{bytes} + avg:{avg} = sum:{sumAvg} <= tot:{total})',
						/**/ $tokens );
					continue;
				}

				// Skip adding files...
				break;
			}

			// ---------------------------------------------------------------------------------------------------------
			// Add:
			// Redirect all files from: [fav], [src] to [out] dir. Watch out for names conflict!
			$home = 0 === strpos( $file, $dirFav ) ? $dirFav : $dirSrc;
			$this->Files->add( $file, $home );

			/* @formatter:off */
			$tokens = array_merge( $tokens, [
				'{items}'  => $this->Files->stats( 'items' ),
				'{bytes}'  => $this->Utils->byteString( $this->Files->stats( 'bytes' ) ),
				'{sumAvg}' => $this->Utils->byteString( $this->Files->stats( 'bytes' ) + $this->Files->stats( 'avg' ) ),
				'{left}'   => $this->Utils->byteString( $total - $this->Files->stats( 'bytes' ) ),
				'{avg}'    => $this->Utils->byteString( $this->Files->stats( 'avg' ) ),
				'{min}'    => $this->Utils->byteString( $this->Files->stats( 'min' ) ),
				'{max}'    => $this->Utils->byteString( $this->Files->stats( 'max' ) ),
			]);
			/* @formatter:on */

			$this->Factory->debug( 'Add "{file}" [{size}]', $tokens );
		}

		// -------------------------------------------------------------------------------------------------------------
		// Summary:
		if ( count( $this->files ) > $this->Files->stats( 'items' ) ) {
			$this->Factory->info(
				/**/ '- reduced, space left: {left} (now:{bytes} + avg:{avg} = sum:{sumAvg} > tot:{total})',
				/**/ $tokens );
		}

		$this->Factory->info( 'Files total: {items} | {bytes} | ~{avg}', $tokens );
		$this->Factory->debug( 'min:{min} | max:{max} | avg:{avg}', $tokens );
	}

	/**
	 * Verify (prompt) user config.
	 */
	protected function configure(): void
	{
		$Prompt = $this->Factory->Prompt();

		$total = $Prompt->importBytes( 'total_bytes', 'Total size' );
		$this->Logger->info( 'Total size: ' . $this->Utils->byteString( $total ) );

		$dirFav = $Prompt->importPath( 'dir_fav', 'Favorites dir' );
		$dirSrc = $Prompt->importPath( 'dir_src', 'Source dir' );
		$dirOut = $Prompt->importPath( 'dir_out', 'Output dir' );
		$this->Logger->info( 'Fav dir: ' . $dirFav );
		$this->Logger->info( 'Src dir: ' . $dirSrc );
		$this->Logger->info( 'Out dir: ' . $dirOut );
	}

	/**
	 * Append more files.
	 */
	protected function addFiles( array $files, bool $shuffle = true ): void
	{
		$shuffle && $this->Utils->arrayShuffle( $files );
		$this->files = array_merge( $this->files, $files );
		$this->Logger->debug( $this->Utils->phpMemoryMax() );
	}

	/**
	 * Import files: FAV + SRC (separately randomized in each group).
	 */
	protected function scan( int $depth = 0 ): void
	{
		$this->addFiles( $this->Utils->dirScan( $this->Factory->get( 'dir_fav' ), $this->pattern, $depth ) );
		$this->addFiles( $this->Utils->dirScan( $this->Factory->get( 'dir_src' ), $this->pattern, $depth ) );

		/* @formatter:off */
		$this->Factory->info( 'Files found: {count} ({types})', [
			'{types}' => implode( '|', $this->Factory->get( 'file_types' ) ),
			'{count}' => count( $this->files ),
		]);
		/* @formatter:on */
	}
}
