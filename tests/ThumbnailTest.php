<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Thumbnail;
use Orkan\Utils;

/**
 * Test Thumbnail.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class ThumbnailTest extends \PHPUnit\Framework\TestCase
{
	protected static $dir = [];
	protected static $cfg = [];

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	public static function setUpBeforeClass(): void
	{
		/* @formatter:off */
		self::$dir = [
			'fixtures' => __DIR__ . '/_fixtures',
			'sandbox'  => __DIR__ . '/_sandbox',
		];
		self::$cfg = [
			'dir_upload'  => self::$dir['sandbox'] . '/uploads',
			'dir_assets'  => self::$dir['sandbox'] . '/assets',
		];
		/* @formatter:on */
	}

	protected function setUp(): void
	{
		Utils::dirClear( self::$dir['sandbox'] );
	}

	protected static function mkDir( string $dir ): bool
	{
		return is_dir( $dir ) ?: mkdir( $dir, 0777, true );
	}

	protected static function mkDirType( string $type ): bool
	{
		return self::mkDir( self::$cfg['dir_upload'] . '/' . $type );
	}

	protected static function newThumbnail( ?string $idx = '', ?string $type = '', array $cfg = [] ): Thumbnail
	{
		$cfg = array_merge( self::$cfg, $cfg );

		// Type subdirs required by Thumbnail class
		self::mkDirType( Thumbnail::TYPE_ORIG );
		self::mkDirType( Thumbnail::TYPE_FULL );
		self::mkDirType( $type );

		return new Thumbnail( $idx, $type, $cfg );
	}

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Compute image crop size and position (single).
	 */
	public function testCanComputeCropBoxSingle()
	{
		$data = array_combine( [ 'width', 'height', 'crop', 'ratio', 'box' ], $this->provideCrop( '642x321 50% 16:9' ) );

		$actual = Thumbnail::imageCropBox( $data['width'], $data['height'], $data['crop'], $data['ratio'] );
		$this->assertSame( $data['box'], $actual );
	}

	/**
	 * @see Thumbnail::imageCropBox( int $width, int $height, int $crop, string $ratio )
	 * @link https://calculateaspectratio.com
	 */
	public function provideCrop( string $key = '' )
	{
		/* @formatter:off */
		$arr = [
			// no crop, no position
			'400x400 0% 1:1' => [ 400, 400, 0, '1:1', [ 'x' => 0, 'y' => 0, 'w' => 400, 'h' => 400 ] ],
			'800x400 0% 2:1' => [ 800, 400, 0, '2:1', [ 'x' => 0, 'y' => 0, 'w' => 800, 'h' => 400 ] ],
			'600x400 0% 3:2' => [ 600, 400, 0, '3:2', [ 'x' => 0, 'y' => 0, 'w' => 600, 'h' => 400 ] ],
			// no crop, position no effect since no resize I:[w,h] == O:[w,h]
			'400x400 22% 1:1' => [ 400, 400, 22, '1:1', [ 'x' => 0, 'y' => 0, 'w' => 400, 'h' => 400 ] ],
			'800x400 33% 2:1' => [ 800, 400, 33, '2:1', [ 'x' => 0, 'y' => 0, 'w' => 800, 'h' => 400 ] ],
			'600x400 88% 3:2' => [ 600, 400, 88, '3:2', [ 'x' => 0, 'y' => 0, 'w' => 600, 'h' => 400 ] ],
			// crop to ratio
			'400x400 0% 2:1'    => [  400,  400, 0,  '2:1', [ 'x' => 0, 'y' => 0, 'w' =>  400, 'h' => 200 ] ],
			'400x400 0% 3:2'    => [  400,  400, 0,  '3:2', [ 'x' => 0, 'y' => 0, 'w' =>  400, 'h' => 267 ] ],
			'1080x1000 0% 16:9' => [ 1080, 1000, 0, '16:9', [ 'x' => 0, 'y' => 0, 'w' => 1080, 'h' => 608 ] ],
			'200x400 0% 4:3'    => [  200,  400, 0,  '4:3', [ 'x' => 0, 'y' => 0, 'w' =>  200, 'h' => 150 ] ],
			// crop to ratio @ position (landscape: use Y)
			'400x400 50% 2:1'    => [  400,  400, 50,  '2:1', [ 'x' => 0, 'y' => 100, 'w' =>  400, 'h' => 200 ] ],
			'400x400 33% 3:2'    => [  400,  400, 33,  '3:2', [ 'x' => 0, 'y' =>  44, 'w' =>  400, 'h' => 267 ] ],
			'1080x1000 50% 16:9' => [ 1080, 1000, 50, '16:9', [ 'x' => 0, 'y' => 196, 'w' => 1080, 'h' => 608 ] ],
			'200x400 50% 4:3'    => [  200,  400, 50,  '4:3', [ 'x' => 0, 'y' => 125, 'w' =>  200, 'h' => 150 ] ],
			// crop to ratio @ position (use X or Y in case of enlarge - no black bars)
			'400x400 50% 1:2'    => [  400,  400, 50,  '1:2', [ 'x' => 100, 'y' =>  0, 'w' => 200, 'h' =>  400 ] ],
			'400x400 33% 2:3'    => [  400,  400, 33,  '2:3', [ 'x' =>  44, 'y' =>  0, 'w' => 267, 'h' =>  400 ] ],
			'1080x1000 50% 9:16' => [ 1080, 1000, 50, '9:16', [ 'x' => 259, 'y' =>  0, 'w' => 563, 'h' => 1000 ] ],
			'200x400 50% 3:4'    => [  200,  400, 50,  '3:4', [ 'x' =>   0, 'y' => 67, 'w' => 200, 'h' =>  267 ] ],
			'642x321 50% 16:9'   => [  642,  321, 50, '16:9', [ 'x' =>  36, 'y' =>  0, 'w' => 571, 'h' =>  321 ] ],
		];
		/* @formatter:on */

		return $arr[$key] ?? $arr;
	}

	/**
	 * Compute image crop size and position.
	 *
	 * @dataProvider provideCrop
	 */
	public function testCanComputeCropBox( int $width, int $height, int $crop, string $ratio, array $expect )
	{
		$actual = Thumbnail::imageCropBox( $width, $height, $crop, $ratio );
		$this->assertSame( $expect, $actual );
	}

	/**
	 * @see Thumbnail::imageResizeBox( int $width, int $height, int $type )
	 */
	public function provideResize()
	{
		/* @formatter:off */
		return [
			// width == type
			'landscape: width == type' => [ 600, 200, 600, [ 'w' => 600, 'h' => 200 ] ],
			'portrait:  width == type' => [ 300, 800, 300, [ 'w' => 300, 'h' => 800 ] ],
			// enlarge
			'landscape: 356x82  > [400]' => [ 356,  82, 400, [ 'w' => 400, 'h' =>   92 ] ],
			'portrait:  82x356  > [400]' => [  82, 356, 400, [ 'w' => 400, 'h' => 1737 ] ],
			// shrink
			'landscape: 567x234 > [200]' => [ 567, 234, 200, [ 'w' => 200, 'h' =>  83 ] ],
			'portrait:  234x567 > [200]' => [ 234, 567, 200, [ 'w' => 200, 'h' => 485 ] ],
		];
		/* @formatter:on */
	}

	/**
	 * Compute resized image dimensions of given 'type'.
	 *
	 * @dataProvider provideResize
	 */
	public function testCanComputeResizeBox( int $width, int $height, int $type, array $expect )
	{
		$actual = Thumbnail::imageResizeBox( $width, $height, $type );
		$this->assertSame( $expect, $actual );
	}

	/**
	 * Thumbnail::type() get/set
	 */
	public function testCanGetType()
	{
		$idx = time() . __FUNCTION__;
		$type = '444';
		$cfg = [ 'th_default' => $type ];

		$Thumbnail = self::newThumbnail( $idx, $type, $cfg );

		$this->assertSame( $type, $Thumbnail->type() ); // getter
		$this->assertSame( Thumbnail::TYPE_ORIG, $Thumbnail->type( Thumbnail::TYPE_ORIG ) ); // setter + getter
		$this->assertSame( Thumbnail::TYPE_FULL, $Thumbnail->type( Thumbnail::TYPE_FULL ) ); // setter + getter
		$this->assertSame( Thumbnail::TYPE_FULL, $Thumbnail->type() ); // getter
	}

	/**
	 * Thumbnail::type() get/set
	 */
	public function testCanGetAlias()
	{
		$alias = 'sexy';
		$type = '666';
		$cfg = [ 'th_alias' => [ $alias => $type ] ];

		self::mkDirType( $type );
		$Thumbnail = new Thumbnail( '', '', array_merge( self::$cfg, $cfg ) );

		$this->assertSame( $type, $Thumbnail->type( $alias ) );
	}

	public function providePath()
	{
		/* @formatter:off */
		return [
			// type |      idx |  url
			[  '400',    '1234', '400/1000/1234'    ],
			[ '9999',     '45q', '9999/0/45q'       ],
			[  '200',  '78900a', '200/78000/78900a' ],
		];
		/* @formatter:on */
	}

	/**
	 * Generate thumb path.
	 *
	 * @dataProvider providePath
	 */
	public function testCanGeneratePath( $type, $idx, $path )
	{
		$expect = self::$cfg['dir_upload'] . '/' . $path;

		$Thumbnail = self::newThumbnail( $idx, $type );
		$actual = $Thumbnail->path( $type );

		$this->assertSame( $expect, $actual );
	}

	public function provideUrl()
	{
		/* @formatter:off */
		return [
			//    idx |  type |  url
			[   '1234',  '400', 'photo_1234_400.jpg'   ],
			[    '45q', '9999', 'photo_45q_9999.jpg'   ],
			[ '78900a',  '200', 'photo_78900a_200.jpg' ],
		];
		/* @formatter:on */
	}

	/**
	 * Generate thumb url.
	 *
	 * @dataProvider provideUrl
	 */
	public function testCanGenerateUrl( $idx, $type, $url )
	{
		$Thumbnail = self::newThumbnail( $idx, $type );

		// get url, not alias!
		$this->assertSame( $url, $Thumbnail->url( 'type', false ) );
	}

	public function provideAlias()
	{
		/* @formatter:off */
		return [
			//    idx |  type |      alias |  url
			[   '1234',  '400',    'middle', 'photo_1234_middle.jpg'      ],
			[    '45q', '9999',     'large', 'photo_45q_large.jpg'        ],
			[ '78900a',  '200', 'thumbnail', 'photo_78900a_thumbnail.jpg' ],
		];
		/* @formatter:on */
	}

	/**
	 * Generate thumb alias.
	 *
	 * @dataProvider provideAlias
	 */
	public function testCanGenerateAlias( $idx, $type, $alias, $url )
	{
		$Thumbnail = self::newThumbnail( $idx, $type, [ 'th_alias' => [ $alias => $type ] ] );

		// Set type now, since constructor will fallback to default type if type subdir doesn't exists.
		$actual1 = $Thumbnail->setType( $type )->url( 'type', true ); // get alias, not url!
		$actual2 = $Thumbnail->setType( $type )->url(); // get alias by default

		$this->assertSame( $url, $actual1 );
		$this->assertSame( $url, $actual2 );
	}

	public function provideImage()
	{
		/* @formatter:off */
		return [
			//    idx |  type | file
			[   '1234',  '400', 'images/a01.jpg' ], // cant use self here!
			[    '45q', '9999', 'images/a02.jpg' ],
			[ '78900a',  '200', 'images/a03.jpg' ],
		];
		/* @formatter:on */
	}

	/**
	 * Create thumbnails.
	 *
	 * @dataProvider provideImage
	 */
	public function testCanSaveThumbnail( $idx, $type, $file )
	{
		$image = self::$dir['fixtures'] . '/' . $file;

		// Save orig image
		$Thumbnail = self::newThumbnail( $idx, Thumbnail::TYPE_ORIG );
		$Thumbnail->save( $image );
		$this->assertFileEquals( $image, $Thumbnail->path() );

		// A type subdir must exists!
		self::mkDir( self::$cfg['dir_upload'] . '/' . $type );

		// Generate thumbnail of current type only
		$Thumbnail->rebuildThumbs( [ $type ] );
		$this->assertFileExists( $path = $Thumbnail->setType( $type )->path() );
		$this->assertStringContainsString( $idx, $path );
		$this->assertStringContainsString( $type, $path );
	}

	/**
	 * Get Thumbnail object reference.
	 *
	 * CAUTION:
	 * Thumbnail::getInstance() will preserve last static instance object (with same IDX)
	 * so make the IDX unique between tests!
	 */
	public function testCanGetInstance()
	{
		$idx = time() . __FUNCTION__;
		self::mkDirType( Thumbnail::TYPE_FULL );

		$Instance = Thumbnail::getInstance( $idx, Thumbnail::TYPE_FULL, self::$cfg );
		$this->assertSame( $Instance, Thumbnail::getInstance( $idx ) );
	}

	/**
	 * Cache the ORIG image data within global (static) Instance.
	 */
	public function testCanReuseOrigImageStream()
	{
		$idx = time() . __FUNCTION__;
		self::mkDirType( Thumbnail::TYPE_ORIG );

		$file = self::$dir['fixtures'] . '/images/a01.jpg';
		$exif = [ 'TestExifFieldA' => 'A', 'TestExifFieldB' => 'B' ];
		$Mock = $this->getMockBuilder( \stdClass::class )->addMethods( [ 'getExif' ] )->getMock();

		/* @formatter:off */
		$Thumbnail = Thumbnail::getInstance( $idx, Thumbnail::TYPE_ORIG, array_merge( self::$cfg, [
			'filter_orig_exif' => [ $Mock, 'getExif' ],
			'orig_keep'        => true, // Cache orig image data between methods
		]));
		/* @formatter:on */

		$Thumbnail->save( $file ); // required by Thumbnail::orgigGet()

		/*
		 * The trick is to count the number of calls to getExif() callback,
		 * since it's invoked only on NEW image data stream requests!
		 */
		$Mock->expects( $this->exactly( 1 ) )->method( 'getExif' )->with( $this->isType( 'resource' ) )->willReturn( $exif );

		// Call 1: Get EXIF from Mock->getExif() callback
		$this->assertSame( $exif, $Thumbnail->imageExif() );

		// Call 2: Get EXIF from instance cache. Do not call to Mock->getExif() again! see Mock->expects(1)
		$this->assertSame( $exif, $Thumbnail->imageExif() );

		// Check static instance getter
		$Instance = Thumbnail::getInstance( $idx );
		$this->assertSame( $Thumbnail, $Instance );
		$this->assertSame( $exif, $Instance->imageExif() );
	}

	/**
	 * Handle cloud storage commands: put, get, url, del.
	 */
	public function testCanUseCloudStorage()
	{
		$idx = time() . __FUNCTION__;
		$image = self::$dir['fixtures'] . '/images/a01.jpg';

		/* @formatter:off */
		$Storage = $this->getMockBuilder( \stdClass::class )->addMethods([
			'putObject',
			'getObject',
			'delObject',
			'getUrl',
			'getInfo',
		])->getMock();

		$Thumbnail = self::newThumbnail( $idx, Thumbnail::TYPE_ORIG, [
			'filter_orig_get'  => [ $Storage, 'getObject' ],
			'filter_orig_put'  => [ $Storage, 'putObject' ],
			'filter_orig_del'  => [ $Storage, 'delObject' ],
			'filter_orig_url'  => [ $Storage, 'getUrl'    ],
			'filter_orig_info' => [ $Storage, 'getInfo'   ],
			'orig_keep' => true, // Cache orig image between methods
		]);
		/* @formatter:on */

		$Thumbnail->save( $image ); // copy test image to ORIG subdir

		/*
		 * -------------------------------------------------------------------------------------------------------------
		 * Get local ORIG file info
		 */
		/* @formatter:off */
		$expectInfo = [
			'url'  => 'whatever',
			'size' => filesize( $image ),
			'mime' => 'whatever',
			'date' => new \DateTimeImmutable( '@' . filemtime( $image ) ),
			'meta' => 'whatever',
		];
		/* @formatter:on */

		$actualInfo = $Thumbnail->origInfo();
		$this->assertSame( array_keys( $expectInfo ), array_keys( $actualInfo ) );

		/*
		 * -------------------------------------------------------------------------------------------------------------
		 * Move ORIG to Cloud
		 */
		/* @formatter:off */
		$Storage->expects( $this->once() )->method( 'putObject' )
			->with(
				$idx,
				$Thumbnail->path(), // ORIG file path
				$this->isType( 'array' ), // meta
				$this->isType( 'array' ), // tags
			)
			->willReturn( 'OK' );
		/* @formatter:on */

		$this->assertSame( 'OK', $Thumbnail->origPut() );
		$this->assertFileDoesNotExist( $pathOrig = $Thumbnail->path( Thumbnail::TYPE_ORIG ) ); // moved!

		/*
		 * -------------------------------------------------------------------------------------------------------------
		 * Get remote ORIG file info
		 */
		/* @formatter:off */
		$Storage->expects( $this->once() )->method( 'getInfo' )
			->with( $idx )
			->willReturn( $expectInfo );
		/* @formatter:on */

		$this->assertSame( $expectInfo, $Thumbnail->origInfo() );

		/*
		 * -------------------------------------------------------------------------------------------------------------
		 * Check image exists in cloud
		 */
		/* @formatter:off */
		$Storage->expects( $this->once() )->method( 'getUrl' )
			->with( $idx )
			->willReturn( 'url/to/object' );
		/* @formatter:on */

		$this->assertTrue( $Thumbnail->isExists( Thumbnail::TYPE_ORIG, true ) );

		/**
		 * -------------------------------------------------------------------------------------------------------------
		 * Get ORIG from Cloud
		 *
		 * Load file into memory as stream resource. Same as AWS StreamInterface does!
		 * @link https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_streams.html
		 * @link https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Psr.Http.Message.StreamInterface.html
		 */
		$fpFile = fopen( $image, 'r+' );
		$Stream = fopen( 'php://memory', 'r+' );
		$bytes = stream_copy_to_stream( $fpFile, $Stream );
		$this->assertNotEmpty( $bytes, 'Error in stream_copy_to_stream( image, memory )' );

		// Create Img now since Thumbnail::origGet() consumes Stream resource!
		rewind( $Stream );
		$Img = imageCreateFromString( stream_get_contents( $Stream ) );

		/* @formatter:off */
		$Storage->expects( $this->exactly( 1 ) )->method( 'getObject' )
			->with( $idx, [ 'stream' => true ] )
			->willReturn( [ 'stream' => $Stream ] );
		/* @formatter:on */

		// Check 1
		$box = $Thumbnail->imageBox(); // <-- load orig image from Storage::getObject()
		$this->assertSame( ImageSX( $Img ), $box['w'] );

		// Check 2
		$this->assertFileDoesNotExist( $path9999 = $Thumbnail->path( Thumbnail::TYPE_FULL ) );
		$Thumbnail->rebuildThumbs(); // <-- load orig image from Thumbnail::$cache - see cfg[keep_orig]
		$this->assertFileExists( $path9999 );

		fclose( $fpFile );

		/*
		 * -------------------------------------------------------------------------------------------------------------
		 * Remove ORIG from Cloud
		 */
		/* @formatter:off */
		$Storage->expects( $this->once() )->method( 'delObject' )
			->with( $idx )
			->willReturn( $url = 'a/b/c' );
		/* @formatter:on */

		$expect = [ $path9999, $url ];
		$actual = $Thumbnail->removeAll();
		$this->assertSame( $expect, $actual );

		$this->assertFileDoesNotExist( $path9999 );
		$this->assertFileDoesNotExist( $pathOrig );
	}

	// ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Error: Error: Error: Error: Error: Error: Error: Error: Error: Error: Error: Error: Error: Error: Error: Error:
	// ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * In constructor: For missing type dir fallback to default type and just log the error.
	 * In setter: throw Exception.
	 */
	public function testExceptionThrownOnMissingTypeSubdir()
	{
		$fake1 = 'type_fake1';
		$fake2 = 'type_fake2';
		$cfg = [ 'th_default' => 'type_fallback' ];

		/*
		 * Exception disabled in constructor!
		 $this->expectExceptionMessage( $fake1 );
		 */
		$Thumbnail = new Thumbnail( 1, $fake1, $cfg );
		$this->assertSame( $cfg['th_default'], $Thumbnail->get( 'th_default' ) ); // config fallback set?
		$this->assertSame( $cfg['th_default'], $Thumbnail->type() ); // type set to fallback?

		/*
		 * Exception in setter!
		 */
		$this->expectExceptionMessage( $fake2 );
		$Thumbnail->type( $fake2 ); // Exception pls!
	}
}
