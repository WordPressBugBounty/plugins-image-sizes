<?php
namespace Codexpert\ThumbPress\API;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Traits\Rest;
use Codexpert\ThumbPress\Traits\Cache;
use Codexpert\ThumbPress\Controllers\Common\Thumbnails;

class Regenerate {

	use Rest;
	use Cache;

	public function regen_now( $request ) {
		global $wpdb;

		$offset               = $request->get_param( 'offset' );
		$limit                = $request->get_param( 'limit' );
		$deleted              = $request->get_param( 'thumbs_deleteds' );
		$created              = $request->get_param( 'thumbs_createds' );
		$offset               = $offset ? absint( $offset ) : 0;
		$limit                = $limit ? absint( $limit ) : 40;
		$not_found_prev       = absint( $request->get_param( 'not_found' ) );
		$processed_count_prev = absint( $request->get_param( 'processed_count' ) );
		$total_images         = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->posts` WHERE `post_type` = 'attachment' AND `post_mime_type` LIKE 'image/%' AND `post_status` != 'trash'" );
		$images               = $wpdb->get_results( $wpdb->prepare( "SELECT `ID` FROM `$wpdb->posts` WHERE `post_type` = 'attachment' AND `post_mime_type` LIKE 'image/%' AND `post_status` != 'trash' LIMIT %d OFFSET %d", $limit, $offset ) );
		$next_offset          = $offset + count( $images );
		$thumbs_created       = $thumbs_deleted = 0;
		$space_saved          = absint( $request->get_param( 'space_saved' ) );

		if ( ! $images ) {
			return $this->response_error(
				__( 'No images found.', 'image-sizes' ),
				array(
					'status' => 2,
				)
			);
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$thumbnails      = new Thumbnails();
		$chunk_saved     = 0;
		$not_found       = 0;
		$processed_count = 0;

		foreach ( $images as $image ) {
			$file = get_attached_file( $image->ID );
			if ( ! $file || ! file_exists( $file ) ) {
				++$not_found;
				continue;
			}
			++$processed_count;
			$res = $thumbnails->regenerate_one( $image->ID );
			if ( $res['skipped'] ) {
				continue;
			}
			$thumbs_deleted += $res['thumbs_deleted'];
			$thumbs_created += $res['thumbs_created'];
			$space_saved    += $res['space_saved'];
			$chunk_saved    += $res['space_saved'];
		}

		thumbpress_add_space_saved( $chunk_saved );

		$total_deleted         = $deleted + $thumbs_deleted;
		$total_created         = $created + $thumbs_created;
		$total_not_found       = $not_found_prev + $not_found;
		$total_processed_count = $processed_count_prev + $processed_count;
		$total_images          = $total_images > 0 ? $total_images : 1;
		$progress              = floor( ( $next_offset / $total_images ) * 100 );
		$progress              = min( $progress, 100 );

		update_option( 'thumbpress_regenerate_space_saved', $space_saved );

		$message = __( 'Regenerating Thumbnails...', 'image-sizes' );
		if ( $progress == 100 ) {
			$message = __( 'Congratulations, Thumbnail Regeneration is Completed!', 'image-sizes' );
			$this->delete_cache( 'stat_total_thumbnails' );
		}

		$space_saved_label = size_format( $space_saved );

		return $this->response_success(
			array(
				'message'            => $message,
				'offset'             => $next_offset,
				'progress'           => $progress,
				'thumbs_deleted'     => $total_deleted,
				'thumbs_created'     => $total_created,
				'total_images_count' => $total_images,
				'space_saved'        => $space_saved,
				'space_saved_label'  => $space_saved_label,
				'not_found'          => $total_not_found,
				'processed_count'    => $total_processed_count,
			)
		);
	}

	public function regen_background( $request ) {
		$limit = $request->get_param( 'limit' );
		$limit = $limit ? absint( $limit ) : 500;

		global $wpdb;

		as_unschedule_all_actions( 'thumbpress_regenerate_all_image' );
		delete_option( 'thumbpress_regenerate_cancelled' );
		delete_option( 'thumbpress_regenerate_progress' );
		delete_option( 'thumbpress_regenerate_total_processed' );
		delete_option( 'thumbpress_regenerate_total_deleted' );
		delete_option( 'thumbpress_regenerate_total_created' );
		delete_option( 'thumbpress_regenerate_space_saved' );
		delete_option( 'thumbpress_regenerate_total_not_found' );

		update_option( 'thumbpress_regenerate_limit', $limit );

		$total_images = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' AND post_status != 'trash'" );

		if ( ! $total_images ) {
			return $this->response_error( __( 'No images found.', 'image-sizes' ) );
		}

		update_option( 'thumbpress_regenerate_total_image', $total_images );

		$offset    = 0;
		$action_id = as_schedule_single_action( wp_date( 'U' ) - 10, 'thumbpress_regenerate_all_image', array( 'offset' => $offset ) );

		if ( ! $action_id ) {
			return $this->response_error( __( 'Failed to schedule action.', 'image-sizes' ) );
		}

		return $this->response_success(
			array(
				'message'   => __( 'Your images are being regenerated in the background.', 'image-sizes' ),
				'action_id' => $action_id,
			)
		);
	}

	/**
	 * Regenerate thumbnails for a single image.
	 */
	public function regen_single( $request ) {
		$image_id = absint( $request->get_param( 'image_id' ) );

		if ( ! $image_id || get_post_type( $image_id ) !== 'attachment' ) {
			return $this->response_error( __( 'Invalid attachment.', 'image-sizes' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$thumbnails = new Thumbnails();
		$result     = $thumbnails->regenerate_one( $image_id );

		if ( $result['skipped'] ) {
			return $this->response_error( __( 'Image file not found.', 'image-sizes' ) );
		}

		thumbpress_add_space_saved( $result['space_saved'] );
		$this->delete_cache( 'stat_total_thumbnails' );

		return $this->response_success(
			array(
				'message'        => __( 'Thumbnails regenerated successfully.', 'image-sizes' ),
				'thumbs_deleted' => $result['thumbs_deleted'],
				'thumbs_created' => $result['thumbs_created'],
			)
		);
	}

	/**
	 * Get background regeneration progress from wp_options.
	 */
	public function get_progress() {
		$progress  = (float) get_option( 'thumbpress_regenerate_progress', 0 );
		$processed = (int) get_option( 'thumbpress_regenerate_total_processed', 0 );
		$deleted   = (int) get_option( 'thumbpress_regenerate_total_deleted', 0 );
		$created   = (int) get_option( 'thumbpress_regenerate_total_created', 0 );
		$total     = (int) get_option( 'thumbpress_regenerate_total_image', 0 );

		$space_saved       = (int) get_option( 'thumbpress_regenerate_space_saved', 0 );
		$space_saved_label = size_format( $space_saved );
		$not_found         = (int) get_option( 'thumbpress_regenerate_total_not_found', 0 );

		return $this->response_success(
			array(
				'progress'          => round( $progress ),
				'processed'         => $processed,
				'deleted'           => $deleted,
				'created'           => $created,
				'total'             => $total,
				'space_saved_label' => $space_saved_label,
				'not_found'         => $not_found,
				'is_complete'       => $progress >= 100,
			)
		);
	}
}
