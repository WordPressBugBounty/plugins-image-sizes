<?php
namespace Thumbpress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Traits\Hook;
use Thumbpress\Traits\Asset;

class Social_Share {

	use Hook;
	use Asset;

	public function __construct() {
		$this->action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		$this->action( 'add_meta_boxes', array( $this, 'social_share_images_metabox' ) );
		$this->action( 'save_post', array( $this, 'save_social_share_images_meta' ) );
		$this->action( 'wp_head', array( $this, 'show_social_share_images' ) );
	}

	public function enqueue_scripts() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->base, array( 'post', 'post-new' ), true ) ) {
			return;
		}
		$this->enqueue_script(
			'thumbpress_social_share',
			THUMBPRESS_PLUGIN_URL . 'assets/admin/js/social-share.js',
			array( 'jquery' )
		);
	}

	/**
	 * Register social share images metabox on post/page/product.
	 */
	public function social_share_images_metabox() {
		$post_types = array( 'post', 'page', 'product' );

		add_meta_box(
			'social_share_images_metabox',
			__( 'Social Share Image', 'image-sizes' ),
			array( $this, 'render_social_share_images_metabox' ),
			$post_types,
			'normal',
			'default'
		);
	}

	/**
	 * Render the social share images metabox.
	 */
	public function render_social_share_images_metabox( $post ) {
		$social_share = get_option( 'thumbpress_social_share', array() );
		$is_fb_share  = isset( $social_share['enable_fb_share_img'] ) && $social_share['enable_fb_share_img'] === 'on';
		$is_ln_share  = isset( $social_share['enable_ln_share_img'] ) && $social_share['enable_ln_share_img'] === 'on';
		$is_tw_share  = isset( $social_share['enable_tw_share_img'] ) && $social_share['enable_tw_share_img'] === 'on';
		$is_pin_share = isset( $social_share['enable_pin_share_img'] ) && $social_share['enable_pin_share_img'] === 'on';

		wp_nonce_field( 'thumbpress_social_metabox_nonce', 'thumbpress_social_images_meta_box_nonce' );

		$facebook_image  = get_post_meta( $post->ID, 'thumbpress_facebook_image', true );
		$linkedin_image  = get_post_meta( $post->ID, 'thumbpress_linkedin_image', true );
		$twitter_image   = get_post_meta( $post->ID, 'thumbpress_twitter_image', true );
		$pinterest_image = get_post_meta( $post->ID, 'thumbpress_pinterest_image', true );

		if ( $is_fb_share ) {
			?>
			<p>
				<label for="thumbpress_facebook_image"><?php esc_html_e( 'Facebook Image:', 'image-sizes' ); ?></label><br>
				<input type="text" id="thumbpress_facebook_image" name="thumbpress_facebook_image" readonly value="<?php echo esc_attr( $facebook_image ); ?>" size="50">
				<button type="button" class="button thumbpress_upload_image_button"><?php esc_html_e( 'Upload Image', 'image-sizes' ); ?></button>
				<button type="button" class="button thumbpress_remove_image_button" style="<?php echo empty( $facebook_image ) ? 'display:none;' : ''; ?>"><?php esc_html_e( 'Remove Image', 'image-sizes' ); ?></button>
			</p>
			<?php
		}

		if ( $is_ln_share ) {
			?>
			<p>
				<label for="thumbpress_linkedin_image"><?php esc_html_e( 'LinkedIn Image:', 'image-sizes' ); ?></label><br>
				<input type="text" id="thumbpress_linkedin_image" name="thumbpress_linkedin_image" readonly value="<?php echo esc_attr( $linkedin_image ); ?>" size="50">
				<button type="button" class="button thumbpress_upload_image_button"><?php esc_html_e( 'Upload Image', 'image-sizes' ); ?></button>
				<button type="button" class="button thumbpress_remove_image_button" style="<?php echo empty( $linkedin_image ) ? 'display:none;' : ''; ?>"><?php esc_html_e( 'Remove Image', 'image-sizes' ); ?></button>
			</p>
			<?php
		}

		if ( $is_tw_share ) {
			?>
			<p>
				<label for="thumbpress_twitter_image"><?php esc_html_e( 'Twitter Image:', 'image-sizes' ); ?></label><br>
				<input type="text" id="thumbpress_twitter_image" name="thumbpress_twitter_image" readonly value="<?php echo esc_attr( $twitter_image ); ?>" size="50">
				<button type="button" class="button thumbpress_upload_image_button"><?php esc_html_e( 'Upload Image', 'image-sizes' ); ?></button>
				<button type="button" class="button thumbpress_remove_image_button" style="<?php echo empty( $twitter_image ) ? 'display:none;' : ''; ?>"><?php esc_html_e( 'Remove Image', 'image-sizes' ); ?></button>
			</p>
			<?php
		}

		if ( $is_pin_share ) {
			?>
			<p>
				<label for="thumbpress_pinterest_image"><?php esc_html_e( 'Pinterest Image:', 'image-sizes' ); ?></label><br>
				<input type="text" id="thumbpress_pinterest_image" name="thumbpress_pinterest_image" readonly value="<?php echo esc_attr( $pinterest_image ); ?>" size="50">
				<button type="button" class="button thumbpress_upload_image_button"><?php esc_html_e( 'Upload Image', 'image-sizes' ); ?></button>
				<button type="button" class="button thumbpress_remove_image_button" style="<?php echo empty( $pinterest_image ) ? 'display:none;' : ''; ?>"><?php esc_html_e( 'Remove Image', 'image-sizes' ); ?></button>
			</p>
			<?php
		}
	}

	/**
	 * Save social share images meta on post save.
	 */
	public function save_social_share_images_meta( $post_id ) {
		if ( ! isset( $_POST['thumbpress_social_images_meta_box_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['thumbpress_social_images_meta_box_nonce'] ) ), 'thumbpress_social_metabox_nonce' ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$meta_fields = array( 'thumbpress_facebook_image', 'thumbpress_linkedin_image', 'thumbpress_twitter_image', 'thumbpress_pinterest_image' );

		foreach ( $meta_fields as $field ) {
			if ( isset( $_POST[ $field ] ) && $_POST[ $field ] !== '' ) {
				update_post_meta( $post_id, $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
			} else {
				delete_post_meta( $post_id, $field );
			}
		}
	}

	/**
	 * Output OG/Twitter meta tags in wp_head for social share images.
	 */
	public function show_social_share_images() {
		if ( ! is_singular( 'post' ) && ! is_singular( 'page' ) && ! is_singular( 'product' ) ) {
			return;
		}

		$post_id      = get_the_ID();
		$post_url     = get_permalink();
		$post_title   = get_the_title();
		$post_desc    = get_the_excerpt();
		$site_name    = get_bloginfo( 'name' );
		$social_share = get_option( 'thumbpress_social_share', array() );
		$is_fb_share  = isset( $social_share['enable_fb_share_img'] ) && $social_share['enable_fb_share_img'] === 'on';
		$is_ln_share  = isset( $social_share['enable_ln_share_img'] ) && $social_share['enable_ln_share_img'] === 'on';
		$is_tw_share  = isset( $social_share['enable_tw_share_img'] ) && $social_share['enable_tw_share_img'] === 'on';
		$is_pin_share = isset( $social_share['enable_pin_share_img'] ) && $social_share['enable_pin_share_img'] === 'on';
		$fb_img       = get_post_meta( $post_id, 'thumbpress_facebook_image', true );
		$ln_img       = get_post_meta( $post_id, 'thumbpress_linkedin_image', true );
		$tw_img       = get_post_meta( $post_id, 'thumbpress_twitter_image', true );
		$pin_img      = get_post_meta( $post_id, 'thumbpress_pinterest_image', true );

		// Facebook share image.
		if ( $is_fb_share && $fb_img ) {
			$fb_img_info = $this->get_image_info( $fb_img );

			if ( $fb_img_info ) {
				printf(
					'<meta property="og:site_name" content="%1$s" />
					<meta property="og:title" content="%2$s" />
					<meta property="og:description" content="%3$s" />
					<meta property="og:url" content="%4$s" />
					<meta property="og:image" content="%5$s" />
					<meta property="og:image:width" content="%6$s" />
					<meta property="og:image:height" content="%7$s" />
					<meta property="og:image:alt" content="%8$s" />
					<meta property="og:image:type" content="%9$s" />',
					esc_attr( $site_name ),
					esc_html( $post_title ),
					esc_html( $post_desc ),
					esc_url( $post_url ),
					esc_url( $fb_img ),
					esc_attr( $fb_img_info['width'] ),
					esc_attr( $fb_img_info['height'] ),
					esc_attr( $fb_img_info['alt'] ),
					esc_attr( $fb_img_info['type'] )
				);
			}
		}

		// LinkedIn share image.
		if ( $is_ln_share && $ln_img ) {
			$ln_img_info = $this->get_image_info( $ln_img );

			if ( $ln_img_info ) {
				printf(
					'<meta property="og:site_name" content="%1$s" />
					<meta property="og:title" content="%2$s" />
					<meta property="og:description" content="%3$s" />
					<meta property="og:url" content="%4$s" />
					<meta property="og:image" content="%5$s" />
					<meta property="og:image:width" content="%6$s" />
					<meta property="og:image:height" content="%7$s" />
					<meta property="og:image:alt" content="%8$s" />
					<meta property="og:image:type" content="%9$s" />',
					esc_attr( $site_name ),
					esc_html( $post_title ),
					esc_html( $post_desc ),
					esc_url( $post_url ),
					esc_url( $ln_img ),
					esc_attr( $ln_img_info['width'] ),
					esc_attr( $ln_img_info['height'] ),
					esc_attr( $ln_img_info['alt'] ),
					esc_attr( $ln_img_info['type'] )
				);
			}
		}

		// Twitter share image.
		if ( $is_tw_share && $tw_img ) {
			$post_author_id = get_post_field( 'post_author', $post_id );
			$post_author    = get_userdata( $post_author_id );

			printf(
				'<meta name="twitter:card" content="summary_large_image" />
				<meta name="twitter:title" content="%1$s" />
				<meta name="twitter:description" content="%2$s" />
				<meta name="twitter:image" content="%3$s" />
				<meta name="og:image" content="%4$s" />
				<meta name="twitter:label1" content="Written by" />
				<meta name="twitter:data1" content="%5$s" />',
				esc_html( $post_title ),
				esc_html( $post_desc ),
				esc_url( $tw_img ),
				esc_url( $tw_img ),
				esc_html( $post_author->display_name )
			);
		}

		// Pinterest share image.
		if ( $is_pin_share && $pin_img ) {
			$post_author_id = get_post_field( 'post_author', $post_id );
			$post_author    = get_userdata( $post_author_id );
			$post_type      = get_post_type();

			if ( in_array( $post_type, array( 'post', 'page' ) ) ) {
				printf(
					'<meta property="og:type" content="article" />
					<meta property="og:title" content="%1$s" />
					<meta property="og:description" content="%2$s" />
					<meta property="og:image" content="%3$s" />
					<meta property="og:url" content="%4$s" />
					<meta property="og:site_name" content="%5$s" />
					<meta property="article:published_time" content="%6$s" />
					<meta property="article:author" content="%7$s" />',
					esc_html( $post_title ),
					esc_html( $post_desc ),
					esc_url( $pin_img ),
					esc_url( $post_url ),
					esc_attr( $site_name ),
					esc_attr( get_post_field( 'post_date', $post_id ) ),
					esc_html( $post_author->display_name )
				);
			}

			if ( $post_type === 'product' && function_exists( 'wc_get_product' ) ) {
				$product      = wc_get_product( $post_id );
				$price        = $product->get_price();
				$currency     = get_woocommerce_currency();
				$is_available = $product->is_in_stock();

				printf(
					'<meta property="og:type" content="product" />
					<meta property="og:title" content="%1$s" />
					<meta property="og:description" content="%2$s" />
					<meta property="og:image" content="%3$s" />
					<meta property="og:url" content="%4$s" />
					<meta property="og:site_name" content="%5$s" />
					<meta property="product:price:amount" content="%6$s" />
					<meta property="product:price:currency" content="%7$s" />
					<meta property="og:availability" content="%8$s" />',
					esc_html( $post_title ),
					esc_html( $post_desc ),
					esc_url( $pin_img ),
					esc_url( $post_url ),
					esc_attr( $site_name ),
					esc_attr( $price ),
					esc_attr( $currency ),
					$is_available ? 'instock' : 'outofstock'
				);
			}
		}
	}

	/**
	 * Get image info (width, height, alt, type) from URL.
	 */
	private function get_image_info( $image_url ) {
		$image_id = attachment_url_to_postid( $image_url );

		if ( $image_id ) {
			$image_meta = wp_get_attachment_metadata( $image_id );

			return array(
				'width'  => isset( $image_meta['width'] ) ? $image_meta['width'] : 0,
				'height' => isset( $image_meta['height'] ) ? $image_meta['height'] : 0,
				'alt'    => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
				'type'   => wp_check_filetype( $image_url )['type'],
			);
		}

		return false;
	}
}
