<?php
namespace Codexpert\ThumbPress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Helpers\Utility;
use Codexpert\ThumbPress\Models\Hash_Index as Index;
use Codexpert\ThumbPress\Traits\Hook;

/**
 * Keeps the hash index in step with the meta it indexes.
 */
class Hash_Index {

	use Hook;

	public function __construct() {
		$this->action( 'thumbpress_file_meta_refreshed', array( $this, 'index_attachment' ) );
		$this->action( 'delete_attachment', array( $this, 'forget_attachment' ) );
		$this->filter( 'wp_update_attachment_metadata', array( $this, 'index_thumbnails' ), 10, 2 );
		$this->action( 'added_post_meta', array( $this, 'index_alt' ), 10, 3 );
		$this->action( 'updated_post_meta', array( $this, 'index_alt' ), 10, 3 );
		$this->action( 'deleted_post_meta', array( $this, 'index_alt' ), 10, 3 );
	}

	/**
	 * Keep the missing-alt flag current when alt text is added, edited or cleared.
	 *
	 * @param int|int[] $meta_id      Meta ID (unused).
	 * @param int       $attachment_id Post the meta belongs to.
	 * @param string    $meta_key     Meta key.
	 */
	public function index_alt( $meta_id, $attachment_id, $meta_key ) {
		if ( '_wp_attachment_image_alt' !== $meta_key || ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		Index::put_flags( $attachment_id );
	}

	/**
	 * Keep the generated-size count current.
	 *
	 * @param array $metadata      Attachment metadata being saved.
	 * @param int   $attachment_id Attachment ID.
	 * @return array
	 */
	public function index_thumbnails( $metadata, $attachment_id ) {
		Index::ensure_schema();
		Index::put_thumbs( $attachment_id, Index::count_sizes( $metadata ) );

		return $metadata;
	}

	/**
	 * Mirror an image's hash/size meta into the index.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function index_attachment( $attachment_id ) {
		$attachment_id = (int) $attachment_id;

		if ( $attachment_id <= 0 ) {
			return;
		}

		$hash = get_post_meta( $attachment_id, Utility::HASH_META_KEY, true );

		if ( empty( $hash ) ) {
			return;
		}

		Index::ensure_schema();
		Index::put( $attachment_id, $hash, (int) get_post_meta( $attachment_id, Utility::SIZE_META_KEY, true ) );
		Index::put_flags( $attachment_id );
	}

	/**
	 * @param int $attachment_id Attachment being deleted.
	 */
	public function forget_attachment( $attachment_id ) {
		Index::forget( $attachment_id );
	}
}
