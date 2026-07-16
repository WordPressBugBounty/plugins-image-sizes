<?php
namespace Codexpert\ThumbPress\Traits;

defined( 'ABSPATH' ) || exit;

trait Cache {

	private $key_prefix = 'thumbpress_';

	private $cache_group = 'thumbpress';

	private static $use_object_cache = null;

	/**
	 * Checks if external object cache is in use and returns true or false.
	 *
	 * @return bool Returns true if external object cache is used, otherwise false.
	 */
	private function is_using_object_cache() {
		if ( null === self::$use_object_cache ) {
			self::$use_object_cache = wp_using_ext_object_cache();
		}
		return self::$use_object_cache;
	}

	/**
	 * Sets a cache value using either object cache or transients based on availability.
	 *
	 * @param string $key The cache key to store the value under.
	 * @param mixed  $value The value to store in the cache.
	 * @param int    $expiration Optional. The time until expiration, in seconds. Default 3600 (1 hour).
	 * @param bool   $allow_empty Should we allow caching empty values
	 */
	public function set_cache( $key, $value, $expiration = 3600, $allow_empty = false ) {

		$key = $this->key_prefix . $key;

		// if we don't want to cache empty values, abort.
		if ( true !== $allow_empty && empty( $value ) ) {
			return;
		}

		if ( $this->is_using_object_cache() ) {
			wp_cache_set( $key, $value, $this->cache_group, $expiration );
		} else {
			set_transient( $key, $value, $expiration );
		}
	}

	/**
	 * Retrieves a cache value using either object cache or transients based on availability.
	 *
	 * @param string $key The cache key to retrieve.
	 * @return mixed The cached value, or false if the value is not found or is expired.
	 */
	public function get_cache( $key ) {

		$key = $this->key_prefix . $key;

		if ( $this->is_using_object_cache() ) {
			return wp_cache_get( $key, $this->cache_group );
		}

		return get_transient( $key );
	}

	/**
	 * Deletes a cache value using either object cache or transients based on availability.
	 *
	 * @param string $key The cache key whose value should be deleted.
	 */
	public function delete_cache( $key ) {

		$key = $this->key_prefix . $key;

		if ( $this->is_using_object_cache() ) {
			wp_cache_delete( $key, $this->cache_group );
		} else {
			delete_transient( $key );
		}
	}

	/**
	 * Return a cached value, computing it via $callback on a cold cache with
	 * stampede protection. With a persistent object cache, only the request that
	 * wins the atomic wp_cache_add() lock runs $callback; concurrent callers return
	 * $default until it repopulates the cache. Without an external object cache a
	 * lock cannot span requests, so it just computes.
	 *
	 * @param string   $key
	 * @param int      $expiration
	 * @param callable $callback
	 * @param mixed    $default
	 * @return mixed
	 */
	public function remember( $key, $expiration, callable $callback, $default = 0 ) {
		$cached = $this->get_cache( $key );
		if ( false !== $cached ) {
			return $cached;
		}

		if ( $this->is_using_object_cache() ) {
			$lock = $this->key_prefix . $key . '_lock';
			if ( false === wp_cache_add( $lock, 1, $this->cache_group, 30 ) ) {
				return $default;
			}

			$value = call_user_func( $callback );
			$this->set_cache( $key, $value, $expiration, true );
			wp_cache_delete( $lock, $this->cache_group );
			return $value;
		}

		$value = call_user_func( $callback );
		$this->set_cache( $key, $value, $expiration, true );
		return $value;
	}
}
