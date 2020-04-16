<?php
/**
 * CodeHacker class file.
 *
 * @package WooCommerce/Testing
 */

//phpcs:disable Squiz.Commenting.FunctionComment.Missing, Squiz.Commenting.VariableComment.Missing
//phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors.Discouraged

namespace Automattic\WooCommerce\Testing\CodeHacking;

use \ReflectionObject;
use \ReflectionFunction;
use \ReflectionException;

/**
 * CodeHacker - allows to hack (alter on the fly) the content of PHP code files.
 *
 * Based on BypassFinals: https://github.com/dg/bypass-finals
 *
 * How to use:
 *
 * 1. Register hacks using CodeHacker::add_hack(hack). A hack is either:
 *    - A function with 'hack($code, $path)' signature, or
 *    - An object having a public 'hack($code, $path)' method.
 *
 *    Where $code is a string containing the code to hack, and $path is the full path of the file
 *    containing the code. The function/method must return a string with the code already hacked.
 *
 * 2. Run CodeHacker::enable()
 *
 * For using with PHPUnit, see CodeHackerTestHook.
 */
class CodeHacker {

	const PROTOCOL = 'file';

	public $context;

	private $handle;

	private static $path_white_list = array();

	private static $hacks = array();

	private static $enabled = false;

	/**
	 * Enable the code hacker.
	 */
	public static function enable() {
		if ( ! self::$enabled ) {
			stream_wrapper_unregister( self::PROTOCOL );
			stream_wrapper_register( self::PROTOCOL, __CLASS__ );
			self::$enabled = true;
		}
	}

	/**
	 * Disable the code hacker.
	 */
	public static function restore() {
		if ( self::$enabled ) {
			stream_wrapper_restore( self::PROTOCOL );
			self::$enabled = false;
		}
	}

	/**
	 * Unregister all the registered hacks.
	 */
	public static function clear_hacks() {
		self::$hacks = array();
	}

	/**
	 * Check if the code hacker is enabled.
	 *
	 * @return bool True if the code hacker is enabled.
	 */
	public static function is_enabled() {
		return self::$enabled;
	}

	/**
	 * Register a new hack.
	 *
	 * @param mixed $hack A function with signature "hack($code, $path)" or an object containing a method with that signature.
	 * @throws \Exception Invalid input.
	 */
	public static function add_hack( $hack ) {
		if ( ! is_callable( $hack ) && ! is_object( $hack ) ) {
			throw new \Exception( "Hacks must be either functions, or objects having a 'process(\$text, \$path)' method." );
		}

		if ( ! self::is_valid_hack_callback( $hack ) && ! self::is_valid_hack_object( $hack ) ) {
			throw new \Exception( "CodeHacker::addhack: hacks must be either a function with a 'hack(\$code,\$path)' signature, or an object containing a public method 'hack' with that signature. " );
		}

		self::$hacks[] = $hack;
	}

	private static function is_valid_hack_callback( $callback ) {
		return is_callable( $callback ) && 2 === ( new ReflectionFunction( $callback ) )->getNumberOfRequiredParameters();
	}

	private static function is_valid_hack_object( $callback ) {
		if ( ! is_object( $callback ) ) {
			return false;
		}

		$ro = new ReflectionObject( ( $callback ) );
		try {
			$rm = $ro->getMethod( 'hack' );
			return $rm->isPublic() && ! $rm->isStatic() && 2 === $rm->getNumberOfRequiredParameters();
		} catch ( ReflectionException $exception ) {
			return false;
		}
	}

	/**
	 * Set the white list of files to hack. If note set, all the PHP files will be hacked.
	 *
	 * @param array $path_white_list Paths of the files to hack, can be relative paths.
	 */
	public static function set_white_list( array $path_white_list ) {
		self::$path_white_list = $path_white_list;
	}


	public function dir_closedir() {
		closedir( $this->handle );
	}


	public function dir_opendir( $path, $options ) {
		$this->handle = $this->context
			? $this->native( 'opendir', $path, $this->context )
			: $this->native( 'opendir', $path );
		return (bool) $this->handle;
	}


	public function dir_readdir() {
		return readdir( $this->handle );
	}


	public function dir_rewinddir() {
		return rewinddir( $this->handle );
	}


	public function mkdir( $path, $mode, $options ) {
		$recursive = (bool) ( $options & STREAM_MKDIR_RECURSIVE );
		return $this->native( 'mkdir', $path, $mode, $recursive, $this->context );
	}


	public function rename( $path_from, $path_to ) {
		return $this->native( 'rename', $path_from, $path_to, $this->context );
	}


	public function rmdir( $path, $options ) {
		return $this->native( 'rmdir', $path, $this->context );
	}


	public function stream_cast( $cast_as ) {
		return $this->handle;
	}

	public function stream_close() {
		fclose( $this->handle );
	}


	public function stream_eof() {
		return feof( $this->handle );
	}


	public function stream_flush() {
		return fflush( $this->handle );
	}


	public function stream_lock( $operation ) {
		return $operation
			? flock( $this->handle, $operation )
			: true;
	}


	public function stream_metadata( $path, $option, $value ) {
		switch ( $option ) {
			case STREAM_META_TOUCH:
				$value += array( null, null );
				return $this->native( 'touch', $path, $value[0], $value[1] );
			case STREAM_META_OWNER_NAME:
			case STREAM_META_OWNER:
				return $this->native( 'chown', $path, $value );
			case STREAM_META_GROUP_NAME:
			case STREAM_META_GROUP:
				return $this->native( 'chgrp', $path, $value );
			case STREAM_META_ACCESS:
				return $this->native( 'chmod', $path, $value );
		}
	}


	public function stream_open( $path, $mode, $options, &$opened_path ) {
		$use_path = (bool) ( $options & STREAM_USE_PATH );
		if ( 'rb' === $mode && self::path_in_white_list( $path ) && 'php' === pathinfo( $path, PATHINFO_EXTENSION ) ) {
			$content = $this->native( 'file_get_contents', $path, $use_path, $this->context );
			if ( false === $content ) {
				return false;
			}
			$modified = self::hack( $content, $path );
			if ( $modified !== $content ) {
				$this->handle = tmpfile();
				$this->native( 'fwrite', $this->handle, $modified );
				$this->native( 'fseek', $this->handle, 0 );
				return true;
			}
		}
		$this->handle = $this->context
			? $this->native( 'fopen', $path, $mode, $use_path, $this->context )
			: $this->native( 'fopen', $path, $mode, $use_path );
		return (bool) $this->handle;
	}


	public function stream_read( $count ) {
		return fread( $this->handle, $count );
	}


	public function stream_seek( $offset, $whence = SEEK_SET ) {
		return fseek( $this->handle, $offset, $whence ) === 0;
	}


	public function stream_set_option( $option, $arg1, $arg2 ) {
	}


	public function stream_stat() {
		return fstat( $this->handle );
	}


	public function stream_tell() {
		return ftell( $this->handle );
	}


	public function stream_truncate( $new_size ) {
		return ftruncate( $this->handle, $new_size );
	}


	public function stream_write( $data ) {
		return fwrite( $this->handle, $data );
	}


	public function unlink( $path ) {
		return $this->native( 'unlink', $path );
	}


	public function url_stat( $path, $flags ) {
		$func = $flags & STREAM_URL_STAT_LINK ? 'lstat' : 'stat';
		return $flags & STREAM_URL_STAT_QUIET
			? @$this->native( $func, $path )
			: $this->native( $func, $path );
	}


	private function native( $func ) {
		stream_wrapper_restore( self::PROTOCOL );
		$res = call_user_func_array( $func, array_slice( func_get_args(), 1 ) );
		stream_wrapper_unregister( self::PROTOCOL );
		stream_wrapper_register( self::PROTOCOL, __CLASS__ );
		return $res;
	}


	private static function hack( $code, $path ) {
		foreach ( self::$hacks as $hack ) {
			if ( is_callable( $hack ) ) {
				$code = call_user_func( $hack, $code, $path );
			} else {
				$code = $hack->hack( $code, $path );
			}
		}

		return $code;
	}


	private static function path_in_white_list( $path ) {
		if ( empty( self::$path_white_list ) ) {
			return true;
		}
		foreach ( self::$path_white_list as $white_list_item ) {
			if ( substr( $path, -strlen( $white_list_item ) ) === $white_list_item ) {
				return true;
			}
		}
		return false;
	}
}

//phpcs:enable Squiz.Commenting.FunctionComment.Missing, Squiz.Commenting.VariableComment.Missing
//phpcs:enable WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors.Discouraged

