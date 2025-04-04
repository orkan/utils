<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * Files Sync.
 * Copy modified only files with progress bar.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class FilesSync
{
	/* @formatter:off */

	/**
	 * Files stats.
	 */
	protected $stats = [
		'item'  => 0, // current file no.
		'items' => 0, // total files
		'bytes' => 0, // total bytes
		'avg'   => 0, // average file size
		'max'   => 0, // max file size
		'min'   => 0, // min file size
	];

	/* @formatter:on */

	/**
	 * List of exported files.
	 *
	 * Will save this list in output dir to keep track of exported files.
	 * The [id] must always match in both manifests: [old] <-> [new] - to help identify the same entry!
	 *
	 * @see FilesSync::manifestUnlink()
	 *
	 * @var array Array(
	 * [id] => Array( [src] => source file, [dst] => export file ),
	 * [id] => Array( ... )
	 * )
	 */
	protected $manifest = [];

	/**
	 * Output dir.
	 */
	protected $dir;

	/**
	 * Progress start time.
	 * @var float
	 */
	protected $start;

	/**
	 * Write mode or dry-run?
	 */
	protected $isWrite;

	/*
	 * Services:
	 */
	protected $Factory;
	protected $Utils;
	protected $Logger;
	protected $Loggex;

	/**
	 * Setup.
	 */
	public function __construct( Factory $Factory )
	{
		$this->Factory = $Factory->merge( self::defaults() );
		$this->Utils = $Factory->Utils();
		$this->Logger = $Factory->Logger();
		$this->Loggex = $Factory->Loggex();

		if ( !$this->dir = realpath( $dir = $Factory->get( 'sync_dir_out' ) ) ) {
			throw new \RuntimeException( sprintf( 'Output dir not found: "%s". Check cfg[sync_dir_out]', $dir ) );
		}

		$this->isWrite = !$Factory->get( 'app_dryrun' );
	}

	/**
	 * Get defaults.
	 */
	protected function defaults(): array
	{
		/**
		 * [sync_dir_out]
		 * Copy files to...
		 *
		 * [sync_manifest]
		 * Filename holding list of all files created by previus export to help make a diff list
		 * Saved in output dir
		 *
		 * [sync_callback]
		 * Callback on copying each file
		 * func([tokent+progress])
		 * @see FilesSync::run()
		 *
		 * [bar_analyzing]
		 * [bar_copying]
		 * Format Progress bar
		 * @see ProgressBar::format()
		 *
		 * [bar_size]
		 * Progress bar indicator width reduced due to length of {text} paths
		 *
		 * @formatter:off */
		return [
			// FileSync
			'sync_dir_out'    => '',
			'sync_manifest'   => 'sync.json',
			'sync_callback'   => null,
			// ProgressBar
			'bar_analyzing'   => '- analyzing [{bar}] {step}/{steps}',
			'bar_copying'     => '- copying [{bar}] "{text}" [{size}]',
			'bar_size'        => 10,
			'bar_text_type'   => 'path',
		];
		/* @formatter:on */
	}

	/**
	 * Add file to export.
	 *
	 * @param string $src  Source file
	 * @param string $home Source file path part to replace with [out] path: [home]/[src] => [out]/[stc]
	 * @return bool True if added, false if already in queue
	 */
	public function add( string $src, string $home ): bool
	{
		// dst: {out}/{src - home}
		if ( $src === $dst = str_replace( $home, $this->dir, $src ) ) {
			throw new \RuntimeException( sprintf( 'Home dir "%s" not found in: "%s"', $home, $src ) );
		}

		if ( !$this->manifestInsert( $src, $dst ) ) {
			return false;
		}

		$stat = stat( $src );
		$this->stats['items']++;
		$this->stats['bytes'] += $stat['size'];
		$this->stats['min'] = min( $this->stats['min'] ?: $stat['size'], $stat['size'] );
		$this->stats['max'] = max( $this->stats['max'], $stat['size'] );
		$this->statsRebuild();

		return true;
	}

	/**
	 * Get stats about files added.
	 */
	public function stats( ?string $key = null )
	{
		return $key ? $this->stats[$key] : $this->stats;
	}

	/**
	 * Sync files.
	 */
	public function run()
	{
		if ( isset( $this->start ) ) {
			throw new \RuntimeException( 'Already launched!' );
		}

		$this->start = $this->Utils->exectime();

		// Unlink invalid files and write new manifest ASAP,
		// so we know what files to delete next time, even if export failed in half way.
		$this->manifestUnlink();
		$this->manifestWrite();

		/* @formatter:off */
		$this->Loggex->notice( 'Copy: {items} files | {bytes}', $tokens = [
			'{items}' => $this->stats['items'],
			'{bytes}' => $this->Utils->byteString( $this->stats['bytes'] ),
		]);
		/* @formatter:on */

		$Bar = $this->Factory->ProgressBar( $this->stats['items'], 'bar_copying' );
		$callback = $this->Factory->get( 'sync_callback' );

		foreach ( $this->manifest as $new ) {
			// Don't replace existing files matched by manifestUnlink()
			if ( is_file( $new['dst'] ) ) {
				continue;
			}

			$tokens['progress'] = $this->progress();
			$tokens['{done}'] = $this->Utils->byteString( $tokens['progress']['byte_done'] );
			$tokens['{size}'] = $this->Utils->byteString( filesize( $new['src'] ) );
			$tokens['{src}'] = $new['src'];
			$tokens['{dst}'] = $new['dst'];

			$callback && call_user_func( $callback, $tokens );
			$Bar->inc( $new['src'], 1, $tokens );

			// Copy to [dst], update [dst:mtime] to match [src:mtime]
			// Warning: The touch(mtime) might be 1s inaccurate on Windows! Bug or performance?
			if ( $this->isWrite ) {
				@mkdir( dirname( $new['dst'] ), 0777, true );
				copy( $new['src'], $new['dst'] );
				touch( $new['dst'], filemtime( $new['src'] ) );
			}
		}

		$Bar = null;
	}

	/**
	 * Remove manifest entries from filesystem.
	 *
	 * Do not delete if the same file is exported again and it's size and
	 * modification time is less than 10s diffrent: [src] <=10s=> [dst].
	 * In all other cases unlink old files and orphans that are not going to be exported again.
	 */
	protected function manifestUnlink(): bool
	{
		$this->Loggex->notice( 'Sync: "%s"', $this->dir );
		$file = $this->dir . '/' . $this->Factory->get( 'sync_manifest' );

		// No manifest found? Clear output dir to get rid of all untracked files
		if ( !is_file( $file ) ) {
			$get = $this->Utils->prompt( 'Manifest file not found! Clear output dir? [y/N/q]: ', 'N', 'Q' );
			if ( 'Y' === strtoupper( $get ) ) {
				$this->Loggex->notice( '- clearing dir: "%s"', $this->dir );
				$this->Utils->dirClear( $this->dir );
			}
			return false;
		}

		// Collect extra data
		if ( DEBUG ) {
			$this->stats['invalid'] = [];
			$this->stats['renamed'] = [];
			$this->stats['updated'] = [];
			$this->stats['deleted'] = [];
			$this->stats['skipped'] = [];
		}

		// -------------------------------------------------------------------------------------------------------------
		// Check old manifest:
		$manifest = json_decode( file_get_contents( $file ), true );

		$bytes = $invalid = $renamed = $updated = $deleted = $skipped = 0;
		$Bar = $this->Factory->ProgressBar( count( $manifest ), 'bar_analyzing' );

		foreach ( $manifest as $id => $old ) {
			$size = null;
			$newSrc = $this->manifest[$id]['src'] ?? null;
			$newDst = $this->manifest[$id]['dst'] ?? null;
			$oldSrc = $old['src'];
			$oldDst = $old['dst'];

			$Bar->inc( $oldDst );

			// Check manifest file integrity. Dont check [dst] since it might change
			if ( $id !== $this->manifestId( $oldSrc ) ) {
				$unlink = true;
				$invalid++;
				$this->Loggex->warning( 'Invalid [src:{src}, id:{id}]', [ '{id}' => $id, '{src}' => $oldSrc ] );
				DEBUG && $this->stats['invalid'][] = $oldSrc;
			}
			// Same [src] but [dst] location has been changed
			elseif ( $newDst && $newDst !== $oldDst ) {
				$unlink = true;
				$renamed++;
				/* @formatter:off */
				DEBUG && $this->Loggex->debug( 'Rename [src:{src}, oldDst:{old}, newDst:{new}, id:{id}]', [
					'{id}'  => $id,
					'{src}' => $oldSrc,
					'{dst}' => $oldDst,
					'{new}' => $newDst,
				]);
				/* @formatter:on */
				DEBUG && $this->stats['renamed'][] = $newDst;
			}
			// Check [dst] difference
			elseif ( $newDst && is_file( $newDst ) ) {
				$statSrc = stat( $newSrc );
				$statDst = stat( $newDst );
				$unlink = false;
				$unlink |= $statSrc['size'] !== $statDst['size'];
				$unlink |= abs( $statSrc['mtime'] - $statDst['mtime'] ) > 10; // allow 10s shift @see touch(mtime)
				$unlink && $updated++;
				$size = $statDst['size'];
				/* @formatter:off */
				DEBUG && $this->Loggex->debug(
					'{action} [dst:{dst}, size:{srcB}/{dstB}, mtime:{srcT}/{dstT}, id:{id}]', [
					'{id}'     => $id,
					'{action}' => $unlink ? 'Update' : 'Keep',
					'{dst}'    => $oldDst,
					'{srcB}'   => $statSrc['size'],
					'{dstB}'   => $statDst['size'],
					'{srcT}'   => $statSrc['mtime'],
					'{dstT}'   => $statDst['mtime'],
				]);
				/* @formatter:on */
				DEBUG && $unlink && $this->stats['updated'][] = $oldDst;
			}
			else {
				$unlink = true;
				DEBUG && $this->Loggex->debug( 'Delete [dst:{dst}, id:{id}]', [ '{id}' => $id, '{dst}' => $oldDst ] );
				DEBUG && $this->stats['deleted'][] = $oldDst;
			}

			// Delete to allow copy new file
			if ( $unlink && @unlink( $oldDst ) ) {
				$deleted++;
			}
			// File won't be copied over. Reduce totals!
			elseif ( null !== $size ) {
				$bytes += $size;
				$skipped++;
				DEBUG && $this->stats['skipped'][] = $oldDst;
			}
		}

		$Bar = null;

		// -------------------------------------------------------------------------------------------------------------
		// Summary:
		$skipped && $this->Logger->info( "- skipped {$skipped} files" );
		$invalid && $this->Logger->info( "- invalid {$invalid} files" );
		$renamed && $this->Logger->info( "- renamed {$renamed} files" );
		$updated && $this->Logger->info( "- updated {$updated} files" );
		$deleted && $this->Logger->info( "- deleted {$deleted} files" );

		if ( $skipped ) {
			$this->stats['items'] -= $skipped; // might reduce to 0
			$this->stats['bytes'] -= $bytes;
			$this->statsRebuild();

			/* @formatter:off */
			$this->Loggex->info( '- saved {bytes} by not exporting {items} matched files', [
				'{items}' => $skipped,
				'{bytes}' => $this->Utils->byteString( $bytes ),
			]);
			/* @formatter:on */
		}

		// -------------------------------------------------------------------------------------------------------------
		// Unlink old manifest
		if ( $this->isWrite ) {
			@unlink( $file );
		}

		return true;
	}

	/**
	 * Rebuild stats.
	 */
	protected function statsRebuild(): void
	{
		$this->stats['avg'] = $this->stats['items'] ? $this->stats['bytes'] / $this->stats['items'] : 0;
	}

	/**
	 * Add file to manifest.
	 *
	 * @param string $source Source file
	 * @param string $export Export file
	 * @return True if file was added
	 */
	protected function manifestInsert( string $source, string $export ): bool
	{
		if ( !$src = realpath( $source ) ) {
			throw new \RuntimeException( sprintf( 'Missing manifest [src] file: "%s"', $src ) );
		}

		if ( !$dst = $this->Utils->pathFix( $export ) ) {
			throw new \RuntimeException( sprintf( 'Missing manifest [dst] file: "%s"', $export ) );
		}

		$id = $this->manifestId( $src );

		if ( !isset( $this->manifest[$id] ) ) {
			$this->manifest[$id] = [ 'src' => $src, 'dst' => $dst ];
			return true;
		}

		return false;
	}

	/**
	 * Compute manifest item id.
	 * @param string $source Source file
	 */
	protected function manifestId( string $source ): int
	{
		return crc32( $source );
	}

	/**
	 * Save manifest to file.
	 */
	protected function manifestWrite(): void
	{
		$file = $this->dir . '/' . $this->Factory->get( 'sync_manifest' );

		if ( $this->isWrite ) {
			$flags = DEBUG ? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE : 0;
			file_put_contents( $file, json_encode( $this->manifest, $flags ) );
		}
	}

	/**
	 * Compute progress info.
	 *
	 * Info:
	 * [byte_done] => elapsed: bytes
	 * [cent_done] => progress:  %
	 * [cent_left] => remaining: %
	 * [time_exec] => elapsed: seconds
	 * [time_cent] => seconds per cent
	 * [time_left] => remaining: seconds
	 * [speed_bps] => average: bytes / sec
	 *
	 * @return array Progress info
	 */
	protected function progress(): array
	{
		/* @formatter:off */
		$out = [
			'byte_done' => 0,
			'cent_done' => 0,
			'cent_left' => 100,
			'time_exec' => 0,
			'time_cent' => 0,
			'time_left' => 0,
			'speed_bps' => 0,
		];
		/* @formatter:on */

		// Dont overflow!
		$this->stats['item'] = min( $this->stats['item'] + 1, $this->stats['items'] );

		// No items loaded yet or progress finished
		if ( !$this->stats['items'] || !$this->stats['bytes'] ) {
			return $out;
		}

		$out['byte_done'] = $this->stats['avg'] * $this->stats['item'];
		$out['cent_done'] = $this->Utils->matchCent( $out['byte_done'] , $this->stats['bytes'] );
		$out['cent_left'] = 100 - $out['cent_done'];

		$out['time_exec'] = $this->Utils->exectime( $this->start );
		$out['time_cent'] = $out['time_exec'] / $out['cent_done'];
		$out['time_left'] = $out['time_cent'] * $out['cent_left'];

		$out['speed_bps'] = $out['byte_done'] / $out['time_exec'];

		return $out;
	}
}
