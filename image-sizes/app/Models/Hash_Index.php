<?php
/**
 * Indexes the file hash/size meta so the derived counts are index reads.
 */

namespace Codexpert\ThumbPress\Models;

defined( 'ABSPATH' ) || exit;

class Hash_Index {

	/**
	 * Bump to make every site repopulate the index on its next scan.
	 */
	const VERSION = 1;

	/**
	 * Option holding the index version a site has fully populated.
	 */
	const VERSION_OPTION = 'thumbpress_hash_index_version';

	/**
	 * One row per indexed image.
	 */
	const TABLE_HASHES = 'hashes';

	/**
	 * One row per distinct hash, carrying how many images share it.
	 */
	const TABLE_GROUPS = 'hash_groups';

	/**
	 * Create both tables; dbDelta makes a repeat call a no-op.
	 */
	public static function install() {
		$hashes = new Database( self::TABLE_HASHES, 'attachment_id' );
		$hashes->create_table(
			array(
				'attachment_id' => 'BIGINT(20) UNSIGNED NOT NULL',
				'hash'          => 'CHAR(32) NOT NULL DEFAULT \'\'',
				'size_kb'       => 'INT(10) UNSIGNED NOT NULL DEFAULT 0',
				'thumbs'        => 'INT(10) UNSIGNED NOT NULL DEFAULT 0',
			),
			array(
				'primary_key' => 'attachment_id',
				'indexes'     => array(
					'hash'    => 'hash',
					'size_kb' => 'size_kb',
				),
			)
		);

		$groups = new Database( self::TABLE_GROUPS, 'hash' );
		$groups->create_table(
			array(
				'hash' => 'CHAR(32) NOT NULL',
				'cnt'  => 'INT(10) UNSIGNED NOT NULL DEFAULT 0',
			),
			array(
				'primary_key' => 'hash',
				'indexes'     => array(
					'cnt' => 'cnt',
				),
			)
		);
	}

	/**
	 * Fully qualified table name.
	 *
	 * @param string $name One of the TABLE_* constants.
	 * @return string
	 */
	public static function table( $name ) {
		global $wpdb;

		return $wpdb->prefix . 'thumbpress_' . $name;
	}

	/**
	 * Whether the index is populated for the current version and may be read from.
	 *
	 * @return bool
	 */
	public static function is_ready() {
		return (int) get_option( self::VERSION_OPTION ) >= self::VERSION;
	}

	/**
	 * Mark the index as fully populated for this version.
	 */
	public static function mark_ready() {
		update_option( self::VERSION_OPTION, self::VERSION );
	}

	/**
	 * Mark the index as needing a scan again.
	 */
	public static function mark_stale() {
		delete_option( self::VERSION_OPTION );
	}

	/**
	 * Record an image's hash and size, keeping the group counts correct.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $hash          md5 of the original file.
	 * @param int    $size_kb       Original file size in KB.
	 */
	public static function put( $attachment_id, $hash, $size_kb ) {
		global $wpdb;

		$attachment_id = (int) $attachment_id;
		$hash          = (string) $hash;

		if ( $attachment_id <= 0 || '' === $hash ) {
			return;
		}

		$hashes = self::table( self::TABLE_HASHES );

		$previous = $wpdb->get_var( $wpdb->prepare( "SELECT hash FROM {$hashes} WHERE attachment_id = %d", $attachment_id ) ); // phpcs:ignore WordPress.DB

		$wpdb->query( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"INSERT INTO {$hashes} ( attachment_id, hash, size_kb ) VALUES ( %d, %s, %d )
				 ON DUPLICATE KEY UPDATE hash = VALUES( hash ), size_kb = VALUES( size_kb )",
				$attachment_id,
				$hash,
				(int) $size_kb
			)
		);

		if ( $previous === $hash ) {
			return;
		}

		if ( null !== $previous ) {
			self::decrement( $previous );
		}

		self::increment( $hash );
	}

	/**
	 * Record how many generated sizes an image has.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @param int $thumbs        Generated sizes, including a -scaled original.
	 */
	public static function put_thumbs( $attachment_id, $thumbs ) {
		global $wpdb;

		$attachment_id = (int) $attachment_id;

		if ( $attachment_id <= 0 ) {
			return;
		}

		$hashes = self::table( self::TABLE_HASHES );

		$wpdb->query( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"INSERT INTO {$hashes} ( attachment_id, thumbs ) VALUES ( %d, %d )
				 ON DUPLICATE KEY UPDATE thumbs = VALUES( thumbs )",
				$attachment_id,
				max( 0, (int) $thumbs )
			)
		);
	}

	/**
	 * Write a whole batch in one statement; group counts come from rebuild_groups().
	 *
	 * @param array $rows Each row: [ attachment_id, hash, size_kb, thumbs ].
	 */
	public static function bulk_put( array $rows ) {
		global $wpdb;

		if ( empty( $rows ) ) {
			return;
		}

		$hashes = self::table( self::TABLE_HASHES );
		$values = array();

		foreach ( $rows as $row ) {
			$values[] = $wpdb->prepare( '( %d, %s, %d, %d )', (int) $row[0], (string) $row[1], (int) $row[2], (int) $row[3] );
		}

		$wpdb->query( // phpcs:ignore WordPress.DB
			"INSERT INTO {$hashes} ( attachment_id, hash, size_kb, thumbs ) VALUES " . implode( ',', $values ) .
			' ON DUPLICATE KEY UPDATE hash = VALUES( hash ), size_kb = VALUES( size_kb ), thumbs = VALUES( thumbs )'
		);
	}

	/**
	 * Derive every group count in one pass, run when a scan finishes.
	 */
	public static function rebuild_groups() {
		global $wpdb;

		$hashes = self::table( self::TABLE_HASHES );
		$groups = self::table( self::TABLE_GROUPS );

		// DELETE, not TRUNCATE: TRUNCATE is DDL and commits the caller's transaction.
		$wpdb->query( "DELETE FROM {$groups}" ); // phpcs:ignore WordPress.DB
		$wpdb->query( // phpcs:ignore WordPress.DB
			"INSERT INTO {$groups} ( hash, cnt )
			 SELECT hash, COUNT(*) FROM {$hashes} WHERE hash <> '' GROUP BY hash"
		);
	}

	/**
	 * Every generated size across the library.
	 *
	 * @return int
	 */
	public static function thumbnail_count() {
		global $wpdb;

		$hashes = self::table( self::TABLE_HASHES );

		return (int) $wpdb->get_var( "SELECT COALESCE( SUM( thumbs ), 0 ) FROM {$hashes}" ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Generated sizes an attachment's metadata describes, counting a -scaled original.
	 *
	 * @param array $metadata Attachment metadata.
	 * @return int
	 */
	public static function count_sizes( $metadata ) {
		if ( ! is_array( $metadata ) ) {
			return 0;
		}

		$count = empty( $metadata['sizes'] ) ? 0 : count( $metadata['sizes'] );

		if ( ! empty( $metadata['original_image'] ) ) {
			++$count;
		}

		return $count;
	}

	/**
	 * Drop an image from the index.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public static function forget( $attachment_id ) {
		global $wpdb;

		$attachment_id = (int) $attachment_id;
		$hashes        = self::table( self::TABLE_HASHES );

		$hash = $wpdb->get_var( $wpdb->prepare( "SELECT hash FROM {$hashes} WHERE attachment_id = %d", $attachment_id ) ); // phpcs:ignore WordPress.DB

		if ( null === $hash ) {
			return;
		}

		$wpdb->delete( $hashes, array( 'attachment_id' => $attachment_id ), array( '%d' ) );

		self::decrement( $hash );
	}

	/**
	 * Images that share their hash with at least one other image.
	 *
	 * @return int
	 */
	public static function duplicate_count() {
		global $wpdb;

		$groups = self::table( self::TABLE_GROUPS );

		return (int) $wpdb->get_var( "SELECT COALESCE( SUM( cnt ), 0 ) FROM {$groups} WHERE cnt > 1" ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Images whose original is larger than the given size.
	 *
	 * @param int $kb Threshold in KB.
	 * @return int
	 */
	public static function large_count( $kb ) {
		global $wpdb;

		$hashes = self::table( self::TABLE_HASHES );

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$hashes} WHERE size_kb > %d", (int) $kb ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * How many images the index currently holds.
	 *
	 * @return int
	 */
	public static function indexed_count() {
		global $wpdb;

		$hashes = self::table( self::TABLE_HASHES );

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$hashes}" ); // phpcs:ignore WordPress.DB
	}

	/**
	 * @param string $hash Hash whose group grew by one.
	 */
	private static function increment( $hash ) {
		global $wpdb;

		$groups = self::table( self::TABLE_GROUPS );

		$wpdb->query( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"INSERT INTO {$groups} ( hash, cnt ) VALUES ( %s, 1 )
				 ON DUPLICATE KEY UPDATE cnt = cnt + 1",
				$hash
			)
		);
	}

	/**
	 * @param string $hash Hash whose group shrank by one.
	 */
	private static function decrement( $hash ) {
		global $wpdb;

		$groups = self::table( self::TABLE_GROUPS );

		$wpdb->query( $wpdb->prepare( "UPDATE {$groups} SET cnt = cnt - 1 WHERE hash = %s AND cnt > 0", $hash ) ); // phpcs:ignore WordPress.DB
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$groups} WHERE hash = %s AND cnt = 0", $hash ) ); // phpcs:ignore WordPress.DB
	}
}
