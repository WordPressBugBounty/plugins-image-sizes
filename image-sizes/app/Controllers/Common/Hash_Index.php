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
	}

	/**
	 * Keep the generated-size count current.
	 *
	 * @param array $metadata      Attachment metadata being saved.
	 * @param int   $attachment_id Attachment ID.
	 * @return array
	 */
	public function index_thumbnails( $metadata, $attachment_id ) {
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

		Index::put( $attachment_id, $hash, (int) get_post_meta( $attachment_id, Utility::SIZE_META_KEY, true ) );
	}

	/**
	 * @param int $attachment_id Attachment being deleted.
	 */
	public function forget_attachment( $attachment_id ) {
		Index::forget( $attachment_id );
	}
}
