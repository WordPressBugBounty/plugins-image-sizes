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
	const VERSION = 2;

	/**
	 * Option holding the index version a site has fully populated.
	 */
	const VERSION_OPTION = 'thumbpress_hash_index_version';

	/**
	 * Table layout version. Bump whenever a column is added, so an updated site upgrades its table
	 * before anything writes the new column: the installer only runs on activation, and a plugin
	 * update does not activate.
	 */
	const SCHEMA = 2;

	/**
	 * Option holding the layout version this site's tables are at.
	 */
	const SCHEMA_OPTION = 'thumbpress_hash_index_schema';

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
				'alt_missing'   => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT 0',
				'bad_name'      => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT 0',
				'base'          => 'VARCHAR(191) NOT NULL DEFAULT \'\'',
				'used'          => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT 0',
			),
			array(
				'primary_key' => 'attachment_id',
				'indexes'     => array(
					'hash'    => 'hash',
					'size_kb' => 'size_kb',
					'base'    => 'base',
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

		update_option( self::SCHEMA_OPTION, self::SCHEMA );
	}

	/**
	 * Bring the tables up to the current layout once, the first time anything is about to write to them.
	 */
	public static function ensure_schema() {
		if ( (int) get_option( self::SCHEMA_OPTION ) >= self::SCHEMA ) {
			return;
		}

		self::install();
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
	 * @param array $rows Each row: [ attachment_id, hash, size_kb, thumbs, alt_missing, bad_name, base ].
	 */
	public static function bulk_put( array $rows ) {
		global $wpdb;

		if ( empty( $rows ) ) {
			return;
		}

		$hashes = self::table( self::TABLE_HASHES );
		$values = array();

		foreach ( $rows as $row ) {
			$values[] = $wpdb->prepare( '( %d, %s, %d, %d, %d, %d, %s )', (int) $row[0], (string) $row[1], (int) $row[2], (int) $row[3], (int) $row[4], (int) $row[5], (string) $row[6] );
		}

		$wpdb->query( // phpcs:ignore WordPress.DB
			"INSERT INTO {$hashes} ( attachment_id, hash, size_kb, thumbs, alt_missing, bad_name, base ) VALUES " . implode( ',', $values ) .
			' ON DUPLICATE KEY UPDATE hash = VALUES( hash ), size_kb = VALUES( size_kb ), thumbs = VALUES( thumbs ), alt_missing = VALUES( alt_missing ), bad_name = VALUES( bad_name ), base = VALUES( base )'
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
	 * What the SEO and usage numbers need to know about one image, from meta already read.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array{0: int, 1: int, 2: string} alt_missing, bad_name, base.
	 */
	public static function flags( $attachment_id ) {
		$alt  = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
		$file = (string) get_post_meta( $attachment_id, '_wp_attached_file', true );

		return array(
			'' === $alt ? 1 : 0,
			self::is_bad_name( $file ) ? 1 : 0,
			self::base_of( $file ),
		);
	}

	/**
	 * Refresh only the flags of one image; the hash, size and thumbnails belong to other writers.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public static function put_flags( $attachment_id ) {
		global $wpdb;

		$attachment_id = (int) $attachment_id;

		if ( $attachment_id <= 0 ) {
			return;
		}

		list( $alt_missing, $bad_name, $base ) = self::flags( $attachment_id );

		$hashes = self::table( self::TABLE_HASHES );

		$wpdb->query( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"INSERT INTO {$hashes} ( attachment_id, alt_missing, bad_name, base ) VALUES ( %d, %d, %d, %s )
				 ON DUPLICATE KEY UPDATE alt_missing = VALUES( alt_missing ), bad_name = VALUES( bad_name ), base = VALUES( base )",
				$attachment_id,
				$alt_missing,
				$bad_name,
				$base
			)
		);
	}

	/**
	 * The name a file is looked up by: no folder, extension, -scaled or -WxH suffix, lower case.
	 *
	 * The original, its -scaled copy and every generated size share it, so one key matches whichever
	 * of them a post happens to embed.
	 *
	 * @param string $file Path or URL.
	 * @return string
	 */
	public static function base_of( $file ) {
		$name = strtolower( pathinfo( wp_basename( (string) $file ), PATHINFO_FILENAME ) );
		$name = preg_replace( '/-(?:scaled|\d+x\d+)$/', '', $name );

		return substr( (string) $name, 0, 191 );
	}

	/**
	 * Whether a filename says nothing about the picture: a camera or screenshot default, a counter or a hash.
	 *
	 * @param string $file Path or URL.
	 * @return bool
	 */
	public static function is_bad_name( $file ) {
		$name = self::base_of( $file );

		if ( '' === $name ) {
			return false;
		}

		// A screenshot is always vague; a camera prefix is vague on its own or followed by counters.
		return (bool) preg_match( '/^(?:screen[ _-]?shot.*|(?:img|dsc|dscn|dscf|pxl|pic|photo|image|scan|untitled|wp)(?:[ _-]?\d+(?:[ _-][a-z]{0,3}\d+)*)?|\d+|[a-f0-9]{16,})$/', $name );
	}

	/**
	 * Images without alt text.
	 *
	 * @return int
	 */
	public static function missing_alt_count() {
		return self::sum( 'alt_missing' );
	}

	/**
	 * Images whose filename says nothing.
	 *
	 * @return int
	 */
	public static function bad_name_count() {
		return self::sum( 'bad_name' );
	}

	/**
	 * Kilobytes held by every original.
	 *
	 * @return int
	 */
	public static function library_kb() {
		return self::sum( 'size_kb' );
	}

	/**
	 * Kilobytes taken by the copies: every image in a shared-hash group except one.
	 *
	 * @return int
	 */
	public static function duplicate_kb() {
		global $wpdb;

		$hashes = self::table( self::TABLE_HASHES );
		$groups = self::table( self::TABLE_GROUPS );

		// Each group's size less the one image that stays.
		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB
			"SELECT COALESCE( SUM( t.total - t.smallest ), 0 ) FROM (
				SELECT SUM( h.size_kb ) AS total, MIN( h.size_kb ) AS smallest
				FROM {$hashes} h INNER JOIN {$groups} g ON g.hash = h.hash AND g.cnt > 1
				GROUP BY h.hash
			) t"
		);
	}

	/**
	 * Forget which images were found in use, before a usage pass marks them again.
	 */
	public static function reset_usage() {
		global $wpdb;

		$hashes = self::table( self::TABLE_HASHES );

		$wpdb->query( "UPDATE {$hashes} SET used = 0 WHERE used <> 0" ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Mark images as in use by attachment ID.
	 *
	 * @param int[] $ids Attachment IDs.
	 */
	public static function mark_used_ids( array $ids ) {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );

		if ( empty( $ids ) ) {
			return;
		}

		$hashes = self::table( self::TABLE_HASHES );

		foreach ( array_chunk( $ids, 1000 ) as $chunk ) {
			$wpdb->query( "UPDATE {$hashes} SET used = 1 WHERE used = 0 AND attachment_id IN ( " . implode( ',', $chunk ) . ' )' ); // phpcs:ignore WordPress.DB
		}
	}

	/**
	 * Mark images as in use by the name a post embeds them under.
	 *
	 * @param string[] $bases Values of base_of().
	 */
	public static function mark_used_bases( array $bases ) {
		global $wpdb;

		$bases = array_values( array_unique( array_filter( array_map( 'strval', $bases ) ) ) );

		if ( empty( $bases ) ) {
			return;
		}

		$hashes = self::table( self::TABLE_HASHES );

		foreach ( array_chunk( $bases, 500 ) as $chunk ) {
			$marks = implode( ',', array_fill( 0, count( $chunk ), '%s' ) );

			$wpdb->query( $wpdb->prepare( "UPDATE {$hashes} SET used = 1 WHERE used = 0 AND base IN ( {$marks} )", $chunk ) ); // phpcs:ignore WordPress.DB
		}
	}

	/**
	 * Images no post, page, meta value or option was found to use, and what they weigh.
	 *
	 * @return array{count: int, kb: int}
	 */
	public static function unused_estimate() {
		global $wpdb;

		$hashes = self::table( self::TABLE_HASHES );

		$row = $wpdb->get_row( "SELECT COUNT(*) AS n, COALESCE( SUM( size_kb ), 0 ) AS kb FROM {$hashes} WHERE used = 0 AND hash <> ''", ARRAY_A ); // phpcs:ignore WordPress.DB

		return array(
			'count' => (int) ( $row['n'] ?? 0 ),
			'kb'    => (int) ( $row['kb'] ?? 0 ),
		);
	}

	/**
	 * @param string $column Column to add up.
	 * @return int
	 */
	private static function sum( $column ) {
		global $wpdb;

		$hashes = self::table( self::TABLE_HASHES );

		return (int) $wpdb->get_var( "SELECT COALESCE( SUM( {$column} ), 0 ) FROM {$hashes}" ); // phpcs:ignore WordPress.DB
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
