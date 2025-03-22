<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * Photo Thumbnail.
 *
 * Each thumbnail consists of the originally uploaded "orig" image and thumbnails of various sizes placed in
 * numerical subdirectories. These subdirs are called types.
 * Types will be auto-generated and cached on demand.
 * All types except TYPE_ORIG (dir: 'orig') and TYPE_FULL (dir: '9999') can be freely added or removed.
 *
 * DEVELOP:
 * Keep this class compact for performance reasons.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Thumbnail
{
	use Config;

	/*
	 * Special thumbnail sizes.
	 */
	const TYPE_ORIG = 'orig';
	const TYPE_FULL = '9999';

	/**
	 * Thumbnail id.
	 * A numeric key representing thumb file in cache dir.
	 * Must start with number to allow group subdirs in cache dir.
	 * Examples: '1234', '56q', '678xyz', etc...
	 *
	 * @var string
	 */
	protected $idx;

	/**
	 * Current thumbnail type.
	 * @see Thumbnail::setType()
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Rererence to last instance created.
	 * This static instance might be then used in Exceptions handler.
	 * Together with cfg[keep_orig] might limit remote requests for orig photo.
	 * @see Thumbnail::getInstance()
	 * @see Thumbnail::errorException()
	 *
	 * @var Thumbnail
	 */
	protected static $Instance;

	/* @formatter:off */

	/**
	 * Internal cache.
	 *
	 * @see Thumbnail::origGet()
	 * @see Thumbnail::imageBox()
	 * @see Thumbnail::imageSize()
	 * @see Thumbnail::imageExif()
	 */
	protected $cache = [
		'box'   => null, // array
		'size'  => null, // array
		'image' => null, // gd resource
		'exif'  => null, // array
	];

	/* @formatter:on */

	/**
	 * Create Thumbnail object.
	 *
	 * Suppress any Exceptions thrown in constructor since we need the object created!
	 *
	 * Available options and constants:
	 * @see Thumbnail::defaults()
	 *
	 * @param string $idx  Thumbnail id. Use NULL to auto import from GET.
	 * @param string $type Thumbnail sub-dir. Use NULL to auto import from GET.
	 */
	public function __construct( ?string $idx = '', ?string $type = '', array $cfg = [] )
	{
		try {
			$this->cfg = $cfg;
			$this->merge( self::config() )->merge( self::defaults() );

			$this->idx = null === $idx ? self::import( 'idx', 0 ) : $idx;
			$this->type( '' === $type ? $this->get( 'th_default' ) : $type );
		}
		catch ( \Throwable $E ) {
			self::logException( $E ); // No exit!
		}
	}

	/**
	 * Get default config.
	 *
	 * [max_age]
	 * Browsers cache expire (sec)
	 *
	 * [date_format]
	 * GMT date format used in http headers
	 * DATE_RFC7231 = "D, d M Y H:i:s \G\M\T"; // Mon, 30 Apr 2016 17:52:13 GMT
	 * @link https://www.geeksforgeeks.org/http-headers-date/
	 * @link https://www.php.net/manual/en/class.datetimeinterface.php
	 *
	 * [th_expire]
	 * When requested thumb is older than this date, it needs to be rebuild.
	 * Even if this is a config value, for performance reasons, this date is used to touch the [expire_file],
	 * and as that, later is compared with requested thumbnail date.
	 * @see Thumbnail::config()
	 * @see Thumbnail::isExpired()
	 *
	 * [is_extended]
	 * Whether to use WP functionality when required?
	 * @see Thumbnail::isExtended()
	 *
	 * [is_temporary]
	 * Skeep cache dir and force create new thumbnail
	 *
	 * [orig_keep]
	 * Save orig image resource for later use.
	 * This is useful when serval calls to getOrig() are performed from different methods or contexts during one request.
	 * Like generating thumbnail meta for different image sizes for the same IDX, etc...
	 * @see Thumbnail::origGet()
	 * @see Thumbnail::imageBox()
	 *
	 * [th_default]
	 * Fallback type if none provided
	 *
	 * [def_image]
	 * Filename of image displayed on failure
	 * @see Thumbnail::sendDefault()
	 *
	 * [filter_thumb_args]
	 * Callback to filter args before create new thumb: array function( $args, $this )
	 * @see Thumbnail::create()
	 *
	 * [filter_orig_put]
	 * Callback to put image object: resource function( $idx )
	 * @see Thumbnail::origPut()
	 *
	 * [filter_orig_get]
	 * Callback to get image object: resource function( $idx )
	 * @see Thumbnail::origGet()
	 *
	 * [filter_orig_del]
	 * Callback to remove image object: bool function( $idx )
	 * @see Thumbnail::origDel()
	 *
	 * [filter_orig_url]
	 * Callback to check if object exists: bool function( $idx )
	 * @see Thumbnail::isExists()
	 *
	 * [th_???]
	 * Thumbnail settings
	 * @see Thumbnail::create()
	 *
	 * [th_alias]
	 * Map 'alias' to 'type', eg. Array (
	 *   [thumbnail] => 200,
	 *   [medium]    => 400,
	 *   [large]     => 9999,
	 * )
	 *
	 * [th_quality]
	 * Default jpeg quality
	 * @see Thumbnail::getQuality()
	 *
	 * [do_???]
	 * Enable particular generator
	 * @see Thumbnail::create()
	 *
	 * DEVELOP:
	 * Do NOT use any internal methods here since they might be using this config values not yet specified!
	 */
	public static function defaults(): array
	{
		/* @formatter:off */
		return [
			// headers
			'max_age'      => 3600 * 24 * 30,
			'date_format'  => DATE_RFC7231,
			// filesystem
			'is_extended'  => false, // defined( 'WPINC' )
			'is_temporary' => false,
			'def_image'    => 'default.png',
			'dir_upload'   => '',
			'dir_assets'   => '',
			'orig_keep'    => false,
			// permalinks
			'link_type'    => 'photo_%1$s_%2$s.jpg',
			'link_default' => 'photo_%1$s.jpg',
			// callbacks
			'filter_orig_put'   => null,
			'filter_orig_get'   => null,
			'filter_orig_del'   => null,
			'filter_orig_url'   => null,
			'filter_orig_info'  => null,
			'filter_orig_exif'  => null,
			'filter_thumb_args' => null,
			// thumbnail
			'th_mime'           => 'image/jpeg',
			'th_default'        => self::TYPE_FULL,
			'th_alias'          => [ 'large' => self::TYPE_FULL ],
			'th_quality'        => 95,
			'th_expire'         => 0,
			'th_crop'           => 50,
			'th_ratio'          => '16:9',
			'th_wmark_position' => 50,
			'th_wmark_strength' => 20,
			'th_wmark_file'     => 'watermark.png',
			'th_copybar_file'   => 'copybar.png',
			'th_copybar_font'   => 'copybar.ttf',
			'th_copybar_author' => '',
			'th_copybar_year'   => date( 'Y' ),
			'th_copybar_text'   => 'Copyright &copy; %year% %author%',
			// generators
			'do_crop'    => true,
			'do_resize'  => true,
			'do_wmark'   => true,
			'do_copybar' => true,
		];
		/* @formatter:on */
	}

	/**
	 * Get last instance or create new.
	 */
	public static function getInstance( ?string $idx = '', ?string $type = '', array $cfg = [] ): self
	{
		if ( !isset( self::$Instance ) || self::$Instance->idx() !== $idx ) {
			self::$Instance = new static( $idx, $type, $cfg );
		}
		else {
			self::$Instance->merge( $cfg, true );
			self::$Instance->type( $type );
		}

		return self::$Instance;
	}

	/**
	 * Get path to external config file / expiration file.
	 *
	 * @see Thumbnail::config()
	 * @see Thumbnail::isExpired()
	 */
	protected static function configFile(): string
	{
		/*
		 * PHPUnit Exception if not defined!
		 * return @constant( 'ORKAN_THUMBNAIL_CFG' );
		 */
		return defined( 'ORKAN_THUMBNAIL_CFG' ) ? ORKAN_THUMBNAIL_CFG : '';
	}

	/**
	 * Read/Write config file.
	 *
	 * ROLE 1:
	 * The idea behind separate config file is to limit DB access while requesting already existent thumbnail.
	 * Settings like mapping permalink -> alias, upload dir location, etc... can be configured in DB and then
	 * dumped to this file for later use, instead of defining countless CONSTANTS in global namespace.
	 *
	 * ROLE 2:
	 * The modification time of this file is used to tell the generator if the thumbnail needs to be re-created.
	 * No thumbnail older then this file will be send to the browser. Good to rebuild cached images after some
	 * significatn changes - just set this file modification time accordingly.
	 * @see Thumbnail::isExpired()
	 *
	 * CAUTION:
	 * require() caches results for the whole server session. Solution: file_put_contents( json )
	 * @link https://stackoverflow.com/questions/48858210/does-phps-require-function-cache-its-results-in-php-5-6
	 *
	 * @param  array $cfg Config to write or leave empty to read from file
	 * @return array      Config from file
	 */
	public static function config( array $cfg = [] ): array
	{
		$file = self::configFile();

		if ( !$cfg ) {
			return is_file( $file ) ? json_decode( file_get_contents( $file ), true ) : [];
		}

		file_put_contents( $file, json_encode( $cfg, JSON_PRETTY_PRINT ) );

		// Set thumbnails expiration date
		touch( $file, $cfg['th_expire'] ?? 0);

		// CHMOD config file to Apache/PHP (www-data) process.
		chmod( $file, 0660 );

		return $cfg;
	}

	/**
	 * Get/Set thumb type.
	 *
	 * @param string|null $type Subdir name eg. 400|9999|orig or null to import type from GET
	 */
	public function type( ?string $type = '' ): string
	{
		if ( ( '' === $type || $this->type === $type ) && $this->type ) {
			return $this->type;
		}

		if ( null === $type ) {
			$type = self::import( 'type', $this->get( 'th_default' ) );
		}

		if ( self::TYPE_ORIG === $type ) {
			// string type
		}
		elseif ( is_numeric( $type ) ) {
			$type = (int) abs( $type );
		}
		else {
			$type = $this->get( 'th_alias' )[$type] ?? $type;
		}

		$this->type = (string) $type;

		// Fall back to defautl type on error
		if ( !is_dir( $dir = $this->pathType( $type ) ) ) {
			$this->type = $this->get( 'th_default' );
			throw new \InvalidArgumentException( sprintf( 'Missing type [%s] subdir "%s"', $type, $dir ) );
		}

		return $this->type;
	}

	/**
	 * Get modified Thumbnail (mutable)
	 */
	public function setType( ?string $type ): self
	{
		$this->type( $type );
		return $this;
	}

	/**
	 * Get thumb alias or fall back to type.
	 *
	 * @param  string $type Thumb type to search alias for
	 * @return string       Thumb alias if found or type otherwise
	 */
	public function alias( string $type = '' ): string
	{
		$type = $type ?: $this->type();
		$alias = array_search( $type, $this->get( 'th_alias' ) );

		return $alias ?: $type;
	}

	/**
	 * Get thumb ID.
	 */
	public function idx(): string
	{
		return $this->idx;
	}

	/**
	 * Get save quality for current type.
	 */
	public function getQuality(): int
	{
		return $this->get( 'th_quality_' . $this->type() ) ?: $this->get( 'th_quality' );
	}

	/**
	 * Check if requested thumbnail exists.
	 */
	public function isExists( string $type = '', bool $checkOnline = false ): bool
	{
		$type = $type ?: $this->type();
		$exists = is_file( $this->path( $type ) );

		if ( !$exists && $checkOnline && is_callable( $filter = $this->get( 'filter_orig_url' ) ) ) {
			$exists = (bool) $filter( $this->idx() );
		}

		return $exists;
	}

	/**
	 * Check if WP is required.
	 */
	public function isExtended(): bool
	{
		return $this->get( 'is_extended' ) || $this->isOrig() || $this->isExpired() || $this->isTemporary();
	}

	/**
	 * Check whether the thumbnail needs re-creating.
	 */
	public function isExpired(): bool
	{
		if ( $this->isOrig() ) {
			return false;
		}

		if ( !$this->isExists() ) {
			return true;
		}

		// Compare thumb mtime with config file mtime
		if ( !$file = self::configFile() ) {
			return false;
		}

		return filemtime( $file ) > filemtime( $this->path() );
	}

	/**
	 * Check whether the thumbnail needs to be re-created.
	 */
	public function isTemporary(): bool
	{
		return $this->get( 'is_temporary' );
	}

	/**
	 * Check whether the current type is orig.
	 */
	public function isOrig(): bool
	{
		return self::TYPE_ORIG === $this->type();
	}

	/**
	 * Import var from URL.
	 */
	public static function import( string $name, string $default = '' ): string
	{
		$m = [];

		switch ( $name )
		{
			// Post ID: 123 | 123q | 123a...
			case 'idx':
				preg_match( '~^\d+[a-z]?$~', $_GET[$name] ?? '', $m );
				return $m[0] ?? $default;

			// Thumb type: 200 | 9999 | orig | large...
			case 'type':
				preg_match( '~\d+|\w{2,43}~', $_GET[$name] ?? '', $m );
				return $m[0] ?? $default;
		}
	}

	/**
	 * Path to dir: {photos}/{type}
	 * Example: photos/orig, photos/9999
	 *
	 * @return string Path to type subdir or empty on error
	 */
	public function pathType( string $type = '' ): string
	{
		$type = $type ?: $this->type;
		$path = $this->get( 'dir_upload' ) ?: '';

		return $path && $type ? "$path/$type" : '';
	}

	/**
	 * Path to photo [idx rage] subdir under [type] dir: {photos}/{type}/{range}
	 * Example: photos/200/4000 - for idx range 4000-4999
	 *
	 * @return string Path to type group dir or empty on error
	 */
	public function pathGroup( string $type = '' ): string
	{
		$idx = (int) $this->idx(); // remove following letters if any
		$group = (int) floor( $idx / 1000 ) * 1000;
		$path = $this->pathType( $type );

		return $path ? "$path/$group" : '';
	}

	/**
	 * Full path to photo: {photos}/{type}/{range}/{idx}
	 * Example: photos/200/4000/4231
	 *
	 * @return string Path to thumb file or empty on error
	 */
	public function path( string $type = '' ): string
	{
		$idx = $this->idx();
		$path = $this->pathGroup( $type );

		return $path ? "$path/$idx" : '';
	}

	/**
	 * Get thumbnail url.
	 *
	 * @param string $kind Use [type]|default
	 */
	public function url( string $kind = 'type', bool $getAlias = true ): string
	{
		$mask = $this->get( 'link_' . $kind ) ?: $this->get( 'link_type' );
		$type = $getAlias ? $this->alias() : $this->type();

		return $mask ? sprintf( $mask, $this->idx(), $type ) : '';
	}

	/**
	 * Remove orig photo and all cached thumbnails.
	 *
	 * @return array Removed files
	 */
	public function removeAll(): array
	{
		$files = $this->removeThumbs();
		$files[] = $this->origDel();

		return $files;
	}

	/**
	 * Remove cached thumbnails.
	 *
	 * @return array Thumbs removed
	 */
	public function removeThumbs(): array
	{
		$files = [];
		$types = $this->types();

		foreach ( $types as $type ) {
			if ( is_file( $file = $this->path( $type ) ) ) {
				unlink( $file );
				$files[] = $file;
			}
		}

		return $files;
	}

	/**
	 * Refresh thumbnails in cache.
	 */
	public function rebuildThumbs( array $types = [] )
	{
		$last = $this->type();
		$types = $types ?: $this->types();

		foreach ( $types as $type ) {
			$Img = $this->setType( $type )->create();
			$this->save( $Img );
		}

		$this->setType( $last );
	}

	/**
	 * Get all available thumbnail widths.
	 */
	public function types( bool $sort = true ): array
	{
		$types = [];

		try {
			$Dir = new \DirectoryIterator( $this->get( 'dir_upload' ) );

			foreach ( $Dir as $Node ) {
				if ( $Node->isDir() && is_numeric( $type = $Node->getFilename() ) ) {
					$types[$type] = $type;
				}
			}

			$sort && asort( $types, SORT_NUMERIC );
		}
		catch ( \Throwable $E ) {
			self::exceptionHandler( $E );
		}

		return $types;
	}

	/**
	 * Decode watermark string.
	 */
	public static function wmarkDecode( string $wmark ): array
	{
		$a = explode( '-', $wmark );

		/* @formatter:off */
		return [
			'position' => intval( $a[0] ?? 0 ),
			'strength' => intval( $a[1] ?? 0 ),
		];
		/* @formatter:on */
	}

	/**
	 * Encode watermark as string.
	 */
	public static function wmarkEncode( int $position, int $strength ): string
	{
		return $position . '-' . $strength;
	}

	/**
	 * Get paths to default images, sorted by priority.
	 */
	public function getDefaultImages(): array
	{
		/* @formatter:off */
		$paths = [
			'type' => $this->pathType(),
			'main' => $this->get( 'dir_upload' ),
			'base' => $this->get( 'dir_assets' ),
		];
		/* @formatter:on */

		$images = [];
		foreach ( $paths as $type => $path ) {
			$images[$type] = $path . '/' . $this->get( 'def_image' );
		}

		return $images;
	}

	/**
	 * Send default image.
	 */
	public function sendDefault(): void
	{
		$images = $this->getDefaultImages();

		foreach ( $images as $image ) {
			if ( $Img = @imageCreateFromPng( $image ) ) {
				header( 'Content-type: image/png' );
				header( 'ETag: default-' . time() );
				imagePng( $Img );
				return;
			}
		}

		/**
		 * Avoid infinite loop!
		 * @see Thumbnail::exceptionHandler() -> sendDefault()
		 */
		self::$Instance = null;

		/* @formatter:off */
		throw new \RuntimeException( sprintf(
			"No default image found! Was trying:\n" .
			"%s\n" .
			"Check config: [def_image], [dir_upload], [dir_assets]",
			implode( "\n", $images ),
		));
		/* @formatter:on */
	}

	/**
	 * Echo thumb image with headers.
	 */
	protected function sendFile(): void
	{
		if ( !is_file( $file = $this->path() ) ) {
			throw new \RuntimeException( 'File not found: ' . $file );
		}

		/* @formatter:off */
		$this->sendHeaders( [
			'Last-Modified'  => date( $this->get('date_format'), filemtime( $file ) ),
			'Accept-Ranges'  => 'bytes',
			'Content-Length' => filesize( $file ),
		]);
		/* @formatter:on */

		readfile( $file );
	}

	/**
	 * Send image headers.
	 *
	 * [Pragma]
	 * Deprecated: This feature is no longer recommended
	 *
	 * [Cache-Control]
	 * Behavior is the same as Cache-Control: no-cache if the Cache-Control header field is omitted in a request
	 *
	 * [Etag]
	 * Entity tag response header is an identifier for a specific version of a resource.
	 *
	 * [Last-Modified]
	 * It is less accurate than an ETag for determining file contents,
	 * but can be used as a fallback mechanism if ETags are unavailable.
	 *
	 * [Expires]
	 * CAUTION: It keeps the photo in browsers cache even after it has been modified on the server!
	 */
	public function sendHeaders( array $headers = [] ): void
	{
		/* @formatter:off */
		$headers = array_merge( [
			'Content-type'   => 'image/jpeg',
			'ETag'           => sprintf( '"%s-%s"', $this->idx(), $this->type() ),
			'Last-Modified'  => date( DATE_RFC7231 ), // default. @see self:::send()
			//'Expires'      => date( $this->get('date_format'), time() + $this->get('max_age') ),
			'Cache-Control'  => 'max-age=' . $this->get('max_age'),
			'Connection'     => 'close',
		], $headers );
		/* @formatter:on */

		foreach ( $headers as $key => $val ) {
			header( "$key: $val" );
		}
	}

	/**
	 * Echo image.
	 *
	 * ORIG: Send local or check remote
	 * THUMB: Create new if not exist, expired or is temporary (no save!)
	 */
	public function send(): void
	{
		// Check type dir
		if ( !is_dir( $dir = $this->pathType() ) ) {
			throw new \InvalidArgumentException( 'Current Type dir not found: ' . $dir );
		}

		// -------------------------------------------------------------------------------------------------------------
		// Send orig?
		if ( $this->isOrig() ) {
			if ( $this->isExists() ) {
				$this->sendFile();
				return;
			}

			$Img = $this->origGet();
		}
		// -------------------------------------------------------------------------------------------------------------
		// Send thumb?
		else {
			if ( !$this->isExpired() && !$this->isTemporary() ) {
				$this->sendFile();
				return;
			}

			$Img = $this->thumb();
		}

		$this->sendHeaders();
		imageJpeg( $Img, null, $this->getQuality() );
	}

	/**
	 * Save image file/resource.
	 *
	 * Create group subdir.
	 * CHMOD dir/file to Apache/PHP (www-data) process.
	 *
	 * @param Resource|string $img Image resource or file to save
	 */
	public function save( $img )
	{
		$file = $this->path();

		if ( !is_dir( $dir = dirname( $file ) ) ) {
			mkdir( $dir, 0770, true );
		}

		if ( is_resource( $img ) ) {
			imageJpeg( $img, $file, $this->getQuality() );
		}
		elseif ( is_file( $img ) ) {
			copy( $img, $file );
		}

		chmod( $file, 0660 );
	}

	/**
	 * Put ORIG image to cloud storage, remove local file.
	 *
	 * NOTE:
	 * The resulting url is totaly separated from image url on the page, which is driven by htaccess file.
	 * It should be only used as a confirmation if the image was uploaded correctly or not.
	 *
	 * @throws \RuntimeException On missing orig image
	 * @return string Storage object url or empty string on upload error
	 */
	public function origPut( array $meta = [], array $tags = [] ): string
	{
		if ( !is_file( $file = $this->path( self::TYPE_ORIG ) ) ) {
			throw new \RuntimeException( sprintf( 'Missing orig image "%s"', $file ) );
		}

		$put = '';
		$meta = array_merge( [ 'url' => $this->url( 'default' ) ], $meta );

		if ( is_callable( $filter = $this->get( 'filter_orig_put' ) ) ) {
			$put = $filter( $this->idx(), $file, $meta, $tags );
		}

		if ( $put ) {
			unlink( $file );
		}

		return $put;
	}

	/**
	 * Get ORIG image.
	 *
	 * Use cache if enabled.
	 * @see Thumbnail::get(orig_keep)
	 *
	 * @throws \RuntimeException On missing orig file
	 * @return resource Image resource
	 */
	public function origGet()
	{
		if ( !isset( $this->cache['image'] ) ) {
			/*
			 * ---------------------------------------------------------------------------------------------------------
			 * Get stream
			 */
			if ( is_file( $file = $this->path( self::TYPE_ORIG ) ) ) {
				$Stream = fopen( $file, 'r+' ); // file://path
			}
			elseif ( is_callable( $filter = $this->get( 'filter_orig_get' ) ) ) {
				$Stream = $filter( $this->idx(), [ 'stream' => true ] )['stream']; // php://memory
			}
			else {
				throw new \RuntimeException( sprintf( 'Orig stream not found for IDX: %s', $this->idx() ) );
			}

			/*
			 * ---------------------------------------------------------------------------------------------------------
			 * Create image
			 */
			rewind( $Stream );
			$this->cache['image'] = imageCreateFromString( stream_get_contents( $Stream ) );

			if ( is_callable( $filter = $this->get( 'filter_orig_exif' ) ) ) {
				$this->cache['exif'] = $filter( $Stream );
			}
			else {
				$this->cache['exif'] = exif_read_data( $Stream );
			}

			/*
			 * ---------------------------------------------------------------------------------------------------------
			 * Clean...
			 */
			fclose( $Stream );
		}

		if ( !isset( $this->cache['image'] ) ) {
			throw new \RuntimeException( sprintf( 'Empty orig Image #%s', $this->idx() ) );
		}

		/*
		 * ---------------------------------------------------------------------------------------------------------
		 * Get Orig image from cache
		 */
		$Img = $this->cache['image'];

		/*
		 * CAUTION:
		 * Limit memory usage and remove stream resource from cache if not needed.
		 * This might have side effect however if the Orig image is going to be requested serval times.
		 * In that case use cfg[orig_keep] to save the resource in cache until object is destructed.
		 * This will limit remote request to Storage server for the same Object.
		 */
		if ( !$this->get( 'orig_keep' ) ) {
			unset( $this->cache['image'] );
		}

		return $Img;
	}

	/**
	 * Remove ORIG image.
	 *
	 * @return string Removed file
	 */
	public function origDel(): string
	{
		if ( is_file( $file = $this->path( self::TYPE_ORIG ) ) ) {
			unlink( $file );
		}
		elseif ( is_callable( $filter = $this->get( 'filter_orig_del' ) ) ) {
			$file = $filter( $this->idx() );
		}

		return $file;
	}

	/**
	 * Get ORIG info.
	 *
	 * @return array (
	 *   [url]  => <string>   External url
	 *   [size] => <int>      Content length
	 *   [mime] => <string>   Content type
	 *   [date] => <DateTime> Last modified
	 *   [meta] => <array>    Metadata headers
	 * )
	 */
	public function origInfo(): array
	{
		if ( is_file( $file = $this->path( self::TYPE_ORIG ) ) ) {
			/* @formatter:off */
			return [
				'url'  => '',
				'size' => filesize( $file ),
				'mime' => 'image/jpeg',
				'date' => new \DateTimeImmutable( '@' . filemtime( $file ) ),
				'meta' => [],
			];
			/* @formatter:on */
		}
		elseif ( is_callable( $filter = $this->get( 'filter_orig_info' ) ) ) {
			return $filter( $this->idx() );
		}

		return [];
	}

	/**
	 * Get thumb image resource.
	 */
	protected function thumb( bool $save = true )
	{
		$Img = $this->create();

		if ( !$save || $this->isTemporary() ) {
			return $Img;
		}

		$this->save( $Img );
		return $Img;
	}

	/**
	 * Create new thumb.
	 * @link https://www.php.net/manual/en/class.gdimage.php
	 *
	 * @throws \RuntimeException On invalid values returned from filters
	 * @return Resource GD image (\GdImage PHP v8)
	 */
	protected function create()
	{
		if ( $this->isOrig() ) {
			throw new \RuntimeException( sprintf( 'Can NOT create thumbnail of type: [%s]', $this->type() ) );
		}

		$args = $this->getThumbArgs();
		$args['idx'] = $this->idx();
		$args['img'] = $this->origGet();

		if ( is_callable( $filter = $args['filter_thumb_args'] ) ) {
			$args = $filter( $args, $this );
		}

		if ( !is_int( $args['width'] ) || 0 >= $args['width'] ) {
			throw new \RuntimeException( sprintf( 'Invalid thumbnail width: %spx for type: [%s]', $args['width'], $this->type() ) );
		}

		/* @formatter:off */
		$args['do_crop']    && self::imageCrop( $args );
		$args['do_resize']  && self::imageResize( $args );
		$args['do_wmark']   && self::imageWatermark( $args );
		$args['do_copybar'] && self::imageCopybar( $args );
		/* @formatter:on */

		return $args['img'];
	}

	/**
	 * Get args to create new thumb.
	 *
	 * @see Thumbnail::create()
	 * @see Thumbnail::imageBox()
	 */
	protected function getThumbArgs(): array
	{
		$cfg = $this->cfg();
		$type = $this->type();
		$dirType = $this->pathType( $type );

		// Modiffy some type specific settings or fall back to globals
		$cfg['do_crop'] = $cfg["do_crop_{$type}"] ?? $cfg['do_crop'];
		$cfg['do_resize'] = self::TYPE_FULL === $type ? false : $cfg['do_resize'];

		/* @formatter:off */
		$args = array_merge( $cfg, [
			'width'           => intval( $type ),
			'th_wmark_file'   => $dirType . '/' . $this->get( 'th_wmark_file' ),
			'th_copybar_file' => $dirType . '/' . $this->get( 'th_copybar_file' ),
			'th_copybar_font' => $this->get( 'dir_assets' ) . '/' . $this->get( 'th_copybar_font' ),
		]);
		/* @formatter:on */

		return $args;
	}

	/**
	 * Crop image.
	 *
	 * Crop vertically (landscape) or horizontaly (portraits) at given % position with current ratio.
	 * No enlarge, no black bars.
	 */
	public static function imageCrop( array &$args ): void
	{
		if ( self::TYPE_FULL == $args['width'] || !$args['th_ratio'] ) {
			return;
		}

		$src = $args['img'];
		$box = self::imageCropBox( ImageSX( $src ), ImageSY( $src ), $args['th_crop'], $args['th_ratio'] );

		$new = ImageCreateTrueColor( $box['w'], $box['h'] );
		ImageCopy( $new, $src, 0, 0, $box['x'], $box['y'], $box['w'], $box['h'] );

		$args['img'] = $new;
	}

	/**
	 * Compute image crop size and position.
	 *
	 * @throws \TypeError On invalid args
	 *
	 * @param float  $width  Source img width
	 * @param float  $height Source img height
	 * @param int    $crop   Crop position in %. For landscape: X axis, for portraits: Y axis.
	 * @param string $ratio  Ratio, i.e "3:2"
	 * @return array Array (
	 *   @type int [x] => Croping X pos
	 *   @type int [y] => Croping Y pos
	 *   @type int [w] => Croped img width
	 *   @type int [h] => Croped img height
	 * )
	 */
	public static function imageCropBox( float $width, float $height, int $crop = 50, string $ratio = '16:9' ): array
	{
		if ( !$ratio ) {
			return [ 'x' => 0, 'y' => 0, 'w' => $width, 'h' => $height ];
		}

		$_ratio = explode( ':', $ratio );

		if ( 2 != count( $_ratio ) ) {
			throw new \TypeError( sprintf( 'Invalid ratio format: "%s"', $ratio ) );
		}

		$ratio = $_ratio;

		if ( 0 > $crop || 100 < $crop ) {
			$crop = 50;
		}

		$iW = $width;
		$iH = $height;

		// Output size
		if ( $ratio[0] === $ratio[1] ) {
			$oW = $oH = min( $iW, $iH );
		}
		elseif ( $ratio[0] < $ratio[1] ) {
			$oH = $iH;
			$oW = ( $oH * $ratio[0] ) / $ratio[1];
		}
		elseif ( $ratio[0] > $ratio[1] ) {
			$oW = $iW;
			$oH = ( $oW * $ratio[1] ) / $ratio[0];
		}

		// Do not enlarge input image (no black bars)
		if ( $oH > $iH ) {
			$oW = $oW / ( $oH / $iH );
			$oH = $iH;
		}
		elseif ( $oW > $iW ) {
			$oH = $oH / ( $oW / $iW );
			$oW = $iW;
		}

		// Crop position
		$oX = $iW > $oW ? ( $iW - $oW ) / 100 * $crop : 0; // Landscape (X,0)
		$oY = $iH > $oH ? ( $iH - $oH ) / 100 * $crop : 0; // Portraits (0,Y)

		$box = [ 'x' => $oX, 'y' => $oY, 'w' => $oW, 'h' => $oH ];
		$box = self::filterBox( $box );

		return $box;
	}

	/**
	 * Resize image proportionally to given width.
	 */
	public static function imageResize( array &$args ): void
	{
		if ( self::TYPE_FULL == $args['width'] ) {
			return;
		}

		$src = $args['img'];
		$box = self::imageResizeBox( ImageSX( $src ), ImageSY( $src ), $args['width'] );

		$args['img'] = ImageScale( $args['img'], $box['w'], $box['h'] );
	}

	/**
	 * Compute resized image dimensions with preserved aspect ratio.
	 *
	 * @param float $width    Source width
	 * @param float $height   Source height
	 * @param float $outWidth Output width (@see 'type')
	 * @return array Array (
	 *   @type int [w] => Output width (always equals to $outWidth)
	 *   @type int [h] => Output height (with aspect ratio)
	 * )
	 */
	public static function imageResizeBox( float $width, float $height, float $outWidth ): array
	{
		if ( $width != $outWidth ) {
			$height = $height / ( $width / $outWidth ); // keep ratio!
			$width = $outWidth; // desired width
		}

		$box = [ 'w' => $width, 'h' => $height ];
		$box = self::filterBox( $box );

		return $box;
	}

	/**
	 * Round all dimension values to int.
	 *
	 * CAUTION:
	 * All image functions filter image dimensions to int, hence 112.8 will result as 112.
	 * This is why you should always round float dimensions before calling image functions.
	 */
	public static function filterBox( array $box ): array
	{
		foreach ( [ 'x', 'y', 'w', 'h' ] as $k ) {
			if ( isset( $box[$k] ) ) {
				$box[$k] = (int) round( $box[$k] );
			}
		}

		return $box;
	}

	/**
	 * Add watermark to image.
	 */
	public static function imageWatermark( array &$args ): void
	{
		if ( !is_file( $args['th_wmark_file'] ) || !$args['th_wmark_strength'] ) {
			return; // No watermark
		}

		$sX = ImageSX( $args['img'] );
		$sY = ImageSY( $args['img'] );

		$w = ImageCreateFromPng( $args['th_wmark_file'] );
		$wX = ImageSX( $w );

		if ( $wX != $sX ) {
			$w = ImageScale( $w, $sX );
			$wX = $sX;
		}

		// Position wmark. X:center, Y:
		$wY = ImageSY( $w );
		$dX = round( ( $sX - $wX ) / 2 );
		$dY = round( ( $sY - $wY ) / 100 * $args['th_wmark_position'] );

		self::imageCopyMergeAlpha( $args['img'], $w, $dX, $dY, 0, 0, $wX, $wY, $args['th_wmark_strength'] );
	}

	/**
	 * Copy and merge part of an image (with alpha!).
	 *
	 * Bugfix:
	 * @link https://www.php.net/manual/en/function.imagecopymerge.php#92787
	 * PNG ALPHA CHANNEL SUPPORT for imagecopymerge();
	 */
	public static function imageCopyMergeAlpha( $dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct )
	{
		// creating a cut resource
		$cut = ImageCreateTrueColor( $src_w, $src_h );

		// copying relevant section from background to the cut resource
		ImageCopy( $cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h );

		// copying relevant section from watermark to the cut resource
		ImageCopy( $cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h );

		// insert cut resource to destination image
		ImageCopyMerge( $dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct );
	}

	/**
	 * Add copyright bar to the image.
	 * The bar consist of custom text on the left side and the copybar.png sprite on the right.
	 * The first column of pixels in the spirite image is reserved for copybar settings, which are:
	 * [0] - background color
	 * [1] - font color at line 1
	 * [2] - font color at line 2
	 * ...
	 * [last] - text shadow color
	 */
	public static function imageCopybar( array &$args ): void
	{
		if ( !is_file( $args['th_copybar_file'] ) ) {
			return;
		}

		/* @formatter:off */
		$args = array_merge([
			'copybar_indent'   =>  4, // Text indent
			'copybar_height'   => 70, // Text height in %
			'copybar_baseline' => -1, // Text baseline fix
		], $args);
		/* @formatter:on */

		// Source image
		$src = $args['img'];
		$sX = ImageSX( $src );
		$sY = ImageSY( $src );

		// -------------------------------------------------------------------------------------------------------------
		// Copybar sprite
		$cbr = ImageCreateFromPng( $args['th_copybar_file'] );
		$cX = ImageSX( $cbr );
		$cY = ImageSY( $cbr );

		/*
		 * First row of Copybar image is reserved for colors definition
		 * @formatter:off */
		$color = [
			'background' => ImageColorAt( $cbr, 0, 0 ), // first pixel
			//'font'     => pixels between {Y:0} - {Y:last} will build font gradient effect
			'shadow'     => ImageColorAt( $cbr, 0, $cY - 1 ), // last pixel
		];
		/* @formatter:on */

		// Fill background
		$bar = ImageCreateTrueColor( $sX, $cY );
		ImageFill( $bar, 0, 0, $color['background'] );

		// Paste copybar to the right
		ImageCopy( $bar, $cbr, $sX - $cX, 0, 1, 0, $cX - 1, $cY );

		// -------------------------------------------------------------------------------------------------------------
		// Copybar text
		if ( is_file( $font = $args['th_copybar_font'] ) ) {
			/* @formatter:off */
			$text = strtr( $args['th_copybar_text'], [
				'%author%' => $args['th_copybar_author'],
				'%year%'   => $args['th_copybar_year'],
			]);
			/* @formatter:on */

			// Replace &copy; > ©
			$text = html_entity_decode( $text );

			// Text settings
			$ttf = self::ttfInfo( $cY / 100 * $args['copybar_height'], $font, $text );
			$ttf['indent'] = $args['copybar_indent'];
			$ttf['baseline'] = round( ( $cY / 2 + $ttf['height'] / 2 ) ) + $args['copybar_baseline'];

			// Drop shadow
			ImageFtText( $bar, $ttf['size'], 0, $ttf['indent'] + 1, $ttf['baseline'] + 1, $color['shadow'], $font, $text );

			/*
			 * Add gradient text
			 * 1. Generate separate strings for each color
			 * 2. Combine different color lines into one string
			 * @formatter:off */
			$box = [
				'x'      => 0,
				'y'      => 0,
				'width'  => round( ( $ttf['indent'] + $ttf['width'] ) * 1.05 ), // add 5% for font width deviations
				'height' => $cY,
			];
			/* @formatter:on */
			for ( $line = 1, $lines = $cY - 2; $line <= $lines; $line++ ) {
				$tmp = ImageCrop( $bar, $box );

				ImageFtText( $tmp, $ttf['size'], 0, $ttf['indent'], $ttf['baseline'], ImageColorAt( $cbr, 0, $line ), $font, $text );
				ImageCopy( $bar, $tmp, 0, $line, 0, $line, $box['width'], 1 ); // paste current line
			}
		}

		// Join all...
		$new = ImageCreateTrueColor( $sX, $sY + $cY );
		ImageCopy( $new, $src, 0, 0, 0, 0, $sX, $sY );
		ImageCopy( $new, $bar, 0, $sY, 0, 0, $sX, $cY );

		$args['img'] = $new;
	}

	/**
	 * Find the closest TTF font size for the given height in px.
	 *
	 * @param float $height Desired box height
	 * @param string $font  Font path
	 * @param string $text  String to use
	 * @param int $size     Initial font size
	 * @return array        TTF size and box dimensions
	 */
	public static function ttfInfo( float $height, string $font, string $text, int $size = 6 ): array
	{
		$text = $text ?: 'uninitialized';

		do {
			$b = imageTTFBBox( ++$size, 0, $font, $text );
			$w = abs( $b[4] - $b[0] );
			$h = abs( $b[1] - $b[5] );
		}
		while ( $h < $height );

		return [ 'size' => $size, 'width' => $w, 'height' => $h ];
	}

	/**
	 * Compute image dimensions.
	 *
	 * @return array Dimensions Array (
	 *   @type int [w] => thumb width
	 *   @type int [h] => thumb height
	 * )
	 */
	public function imageBox(): array
	{
		$type = $this->type();
		$this->cache['box'][$type] = $this->cache['box'][$type] ?? [];
		$box = &$this->cache['box'][$type];

		if ( $box ) {
			return $box;
		}

		$Img = $this->origGet();
		$box = [ 'w' => ImageSX( $Img ), 'h' => ImageSY( $Img ) ];

		if ( $this->isOrig() ) {
			return $box;
		}

		$args = $this->getThumbArgs();

		// Don't crop/resize full photos ;)
		if ( $args['do_crop'] ) {
			$box = self::imageCropBox( ImageSX( $Img ), ImageSY( $Img ), $this->get( 'th_crop' ), $this->get( 'th_ratio' ) );
		}

		if ( $args['do_resize'] ) {
			$box = self::imageResizeBox( $box['w'], $box['h'], $type );
		}

		if ( $args['do_copybar'] && is_file( $img = $args['th_copybar_file'] ) ) {
			$Img = ImageCreateFromPng( $img );
			$box['h'] += ImageSY( $Img );
		}

		return $box;
	}

	/**
	 * Get image size in bytes.
	 */
	public function imageSize(): int
	{
		$type = $this->type();
		$this->cache['size'][$type] = $this->cache['size'][$type] ?? 0;
		$size = &$this->cache['size'][$type];

		if ( $size ) {
			return $size;
		}

		$Img = $this->isOrig() ? $this->origGet() : $this->thumb( false );

		ob_start();
		imageJpeg( $Img, null, $this->getQuality() );
		$size = ob_get_length();
		ob_end_clean();

		return $size;
	}

	/**
	 * Read image EXIF.
	 */
	public function imageExif(): array
	{
		!$this->cache['exif'] && $this->origGet();
		return $this->cache['exif'];
	}

	/**
	 * Log exception to PHP error log.
	 * @todo Send admin email
	 */
	public static function logException( \Throwable $E ): void
	{
		if ( defined( 'TESTING' ) && TESTING ) {
			return;
		}

		$out = [];

		/* @formatter:off */
		foreach ([
			'Message' => $E->getMessage(),
			'REFERER' => $_SERVER['HTTP_REFERER'] ?? '',
			'REQUEST' => $_SERVER['REQUEST_URI'] ?? '',
			'User ID' => $GLOBALS['current_user']->ID ?? 0,
			'Caught'  => $E,
		] as $k => $v ) {
			$out[] = "$k: $v";
		}
		/* @formatter:on */

		error_log( implode( "\n", $out ) );
	}

	/**
	 * Send default image on Exceptions.
	 *
	 * Called if an exception is not caught within a try/catch block
	 * Execution will stop after the exception_handler is called.
	 * Hardcoded here to skip including external code.
	 * @see set_exception_handler()
	 */
	public static function exceptionHandler( \Throwable $E ): void
	{
		self::logException( $E );

		if ( isset( self::$Instance ) ) {
			self::$Instance->sendDefault();
			return;
		}

		throw $E;
	}

	/**
	 * Turn PHP errors into Exceptions.
	 *
	 * @see set_error_handler()
	 * Hardcoded here to skip including external code.
	 */
	public static function errorException( $severity, $message, $filename, $lineno ): void
	{
		// This error code is not included in error_reporting or error was suppressed with the @-operator
		if ( !( error_reporting() & $severity ) ) {
			return;
		}
		throw new \ErrorException( $message, 0, $severity, $filename, $lineno );
	}
}
