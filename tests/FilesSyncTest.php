<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Utils;

/**
 * Test: Orkan\FilesSync.
 * @see \Orkan\FilesSync
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class FilesSyncTest extends \Orkan\Tests\TestCase
{
	const USE_FIXTURE = true;
	const USE_SANDBOX = true;
	private static $progress;

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void
	{
		self::$progress = [];
	}

	/**
	 * Callback: Add progress data.
	 * cfg[sync_callback] = cbProgress()
	 */
	public static function cbProgress( array $data ): void
	{
		self::$progress[] = $data;
	}

	/**
	 * Create Factory.
	 * @param bool $withSandbox Configure dir: {sandbox}/{test}/out
	 */
	private function getFactory( bool $withSandbox = true ): FactoryMock
	{
		$Factory = new FactoryMock( $this );

		if ( $withSandbox ) {
			$dir = self::sandboxPath( $this->getName() . '/out' );
			Utils::dirClear( $dir );
			$Factory->cfg( 'sync_dir_out', $dir );
		}
		return $Factory;
	}

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * run() - progress & stats info.
	 */
	public function testCanGetProgress()
	{
		/* @formatter:off */
		$files = [
			'file0 10b.txt',
			'file0 20b.mp4',
			'dir1/file1 10b.txt',
		];
		/* @formatter:on */

		$Factory = $this->getFactory();
		$Factory->cfg( 'sync_callback', [ self::class, 'cbProgress' ] );
		$Sync = $Factory->FilesSync();

		// Add files
		$home = self::fixturePath( 'sync' );
		$bytes = 0;
		foreach ( $files as $file ) {
			$file = self::fixturePath( "sync/$file" );
			$Sync->add( $file, $home );
			$bytes += filesize( $file );
		}

		$stats = $Sync->stats();
		$this->assertSame( $bytes, $stats['bytes'], 'stats(bytes)' );
		$this->assertSame( $items = count( $files ), $Sync->stats( 'items' ), 'stats(items)' );
		$this->assertSame( $avg = $bytes / $items, $Sync->stats( 'avg' ), 'stats(avg)' );
		$this->assertSame( 10, $Sync->stats( 'min' ), 'stats(min)' );
		$this->assertSame( 20, $Sync->stats( 'max' ), 'stats(max)' );

		// Copy files to output dir
		$Factory->ProgressBar()->expects( $this->exactly( $items ) )->method( 'inc' );
		$Sync->run();

		// Check progress data
		$this->assertCount( $items, self::$progress, 'Callback: progress' );
		$this->assertSame( $bytes, (int) self::$progress[2]['progress']['byte_done'], 'bytes done ' );
		$this->assertSame( $avg * 1, self::$progress[0]['progress']['byte_done'], 'byte_done 1/3' );
		$this->assertSame( $avg * 2, self::$progress[1]['progress']['byte_done'], 'byte_done 2/3' );
		$this->assertSame( $avg * 3, self::$progress[2]['progress']['byte_done'], 'byte_done 3/3' );
		// ceil() gives 1-100%, otherwise: 33%, 66%, 99%
		$this->assertSame( 34, self::$progress[0]['progress']['cent_done'], 'cent_done 1/3' );
		$this->assertSame( 67, self::$progress[1]['progress']['cent_done'], 'cent_done 2/3' );
		$this->assertSame( 100, self::$progress[2]['progress']['cent_done'], 'cent_done 3/3' );
	}

	/**
	 * run() - copy renamed, modified and new files.
	 */
	public function testCanUpdateFiles()
	{
		/* @formatter:off */
		$files = [
			'a' => 'file0 15b.avi',
			'b' => 'file0 20b.mp4',
			'c' => 'dir1/file1 10b.txt',
			'd' => 'dir2/file2 25b.txt',
			'g' => '.file0b', // zero bytes!
		];
		/* @formatter:on */

		$Factory = $this->getFactory();
		$out = $Factory->get( 'sync_dir_out' );

		// -------------------------------------------------------------------------------------------------------------
		// Run: 1/2 - copy
		$Sync = $Factory->FilesSync();

		// Add files
		$home = self::fixturePath( 'sync' );
		foreach ( $files as $k => $file ) {
			$files[$k] = [ $home . '/' . $file, $home, Utils::pathFix( $out . '/' . $file ) ];
			$Sync->add( $files[$k][0], $files[$k][1] );
		}

		// Copy files
		$Sync->run();

		foreach ( $files as $file ) {
			$this->assertFileExists( $file[2] );
		}

		// -------------------------------------------------------------------------------------------------------------
		// Update files
		/* @formatter:off */
		$files2 = array_merge( $files, [
			'e' => 'file0 25b.jpg',
			'f' => 'file0 10b.txt',
		]);
		/* @formatter:on */

		// deleted
		unset( $files2['a'] );

		// updated
		touch( $files2['b'][2], time() - 3600 );

		// renamed (move level up)
		$files2['c'][1] = $home . '/dir1';
		$files2['c'][2] = Utils::pathFix( $out . '/' . basename( $files['c'][0] ) );

		// new files
		$files2['e'] = [ $home . '/' . $files2['e'], $home, Utils::pathFix( $out . '/' . $files2['e'] ) ];
		$files2['f'] = [ $home . '/' . $files2['f'], $home, Utils::pathFix( $out . '/' . $files2['f'] ) ];

		// -------------------------------------------------------------------------------------------------------------
		// Run: 2/2 - update
		$Sync = $Factory->FilesSync();
		foreach ( $files2 as $file ) {
			$Sync->add( $file[0], $file[1] );
		}

		// Update files
		$Factory->cfg( 'sync_callback', [ self::class, 'cbProgress' ] );
		$Sync->run();

		foreach ( $files2 as $file ) {
			$this->assertFileExists( $file[2] );
		}
		$this->assertFileDoesNotExist( $files['a'][2] );

		// Modified: [b], Added: [e,f]
		$this->assertCount( 4, self::$progress, 'Callback: progress' );
		$this->assertSame( realpath( $files2['b'][2] ), self::$progress[0]['{dst}'], 'Copy: modified [b]' );
		$this->assertSame( realpath( $files2['c'][2] ), self::$progress[1]['{dst}'], 'Copy: renamed [c]' );
		$this->assertSame( realpath( $files2['e'][2] ), self::$progress[2]['{dst}'], 'Copy: added [e]' );
		$this->assertSame( realpath( $files2['f'][2] ), self::$progress[3]['{dst}'], 'Copy: added [f]' );

		// Extra stats
		$stats = $Sync->stats();
		$this->assertCount( 2, $stats['skipped'], 'Stats: skipped' );
		$this->assertSame( $files['d'][2], $stats['skipped'][0], 'Stats: skipped [d]' );
		$this->assertSame( $files['g'][2], $stats['skipped'][1], 'Stats: skipped [g]' );

		$this->assertCount( 1, $stats['deleted'], 'Stats: deleted' );
		$this->assertSame( $files['a'][2], $stats['deleted'][0], 'Stats: deleted [a]' );

		$this->assertCount( 1, $stats['updated'], 'Stats: updated' );
		$this->assertSame( $files2['b'][2], $stats['updated'][0], 'Stats: updated [b]' );

		$this->assertCount( 1, $stats['renamed'], 'Stats: renamed' );
		$this->assertSame( $files2['c'][2], $stats['renamed'][0], 'Stats: renamed [c]' );
	}

	/**
	 * manifestUnlink() - unlink and copy again on invalid manifest entry.
	 */
	public function testCanUnlinkFileOnInvalidManifestEntry()
	{
		$Factory = $this->getFactory();
		$Sync = $Factory->FilesSync();

		// Save valid manifest
		$home = self::fixturePath( 'sync' );
		$file = $home . '/file0 15b.avi';
		$Sync->add( $file, $home );
		$Sync->run();

		// Infect manifest file
		$manifest = $Factory->get( 'sync_dir_out' ) . '/' . $Factory->get( 'sync_manifest' );
		$json = json_decode( file_get_contents( $manifest ), true );
		$json = array_values( $json ); // remove keys
		file_put_contents( $manifest, json_encode( $json, JSON_PRETTY_PRINT ) );

		// Run again
		$Sync = $Factory->FilesSync();
		$Sync->add( $file, $home );
		$Sync->run();

		// Check if updated
		$stats = $Sync->stats();
		$this->assertCount( 1, $stats['invalid'], 'Stats: invalid' );
		$this->assertSame( Utils::pathFix( $file ), $stats['invalid'][0], 'Manifest: invalid' );
	}

	// ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Error: Error: Error: Error: Error: Error: Error: Error: Error: Error: Error: Error: Error: Error: Error: Error:
	// ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * __construct(): missing dir out.
	 */
	public function testExceptionThrownOnMissingDirOut()
	{
		$this->expectExceptionMessage( $dir = 'missing' );

		$Factory = $this->getFactory( false );
		$Factory->cfg( 'sync_dir_out', $dir );
		$Factory->FilesSync()->run();
	}

	/**
	 * run(): multiple run.
	 */
	public function testExceptionThrownOnMultipleRun()
	{
		$this->expectExceptionMessage( 'Already launched!' );

		$Sync = $this->getFactory()->FilesSync();
		$Sync->run();
		$Sync->run();
	}
}
