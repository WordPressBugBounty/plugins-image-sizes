<?php
namespace Codexpert\ThumbPress\Modules;

use Codexpert\ThumbPress\Helper;
use Codexpert\Plugin\Base;

class Social_Share extends Base {
	public $plugin;
	public $slug;
	public $version;
	public $id = 'thumbpress-social-share';

	/**
	 * Constructor
	 */
	public function __construct() {

		// require_once( __DIR__ . '/inc/functions.php' );

		$this->plugin	= get_plugin_data( THUMBPRESS );
		$this->slug		= $this->plugin['TextDomain'];
		$this->version	= $this->plugin['Version'];

		$this->action( 'add_meta_boxes', 'social_share_images_metabox' );
		$this->action( 'save_post', 'save_social_share_images_meta' );
		$this->action( 'wp_head', 'show_social_share_images' );
	}

	public function __settings ( $settings ) {
		$settings['sections'][ $this->id ] = [
			'id'        => $this->id,
			'label'     => __( 'Social Share Image', 'image-sizes' ),
			'icon'      => 'dashicons-share',
			'sticky'    => false,
			'fields'    => [
				[
					'id'        => 'enable_fb_share_img',
					'label'     => __( 'Enable Facebook Share Image', 'image-sizes' ),
					'type'      => 'switch',
					'desc'      => __( 'By enabling this you\'ll be able to set image for Facebook share.', 'image-sizes' ),
					'disabled'  => false,
				],
				[
					'id'        => 'enable_ln_share_img',
					'label'     => __( 'Enable LinkedIn Share Image', 'image-sizes' ),
					'type'      => 'switch',
					'desc'      => __( 'By enabling this you\'ll be able to set image for LinkedIn share.', 'image-sizes' ),
					'disabled'  => false,
				],
				[
					'id'        => 'enable_tw_share_img',
					'label'     => __( 'Enable Twitter Share Image', 'image-sizes' ),
					'type'      => 'switch',
					'desc'      => __( 'By enabling this you\'ll be able to set image for Twitter share.', 'image-sizes' ),
					'disabled'  => false,
				],
				[
					'id'        => 'enable_pin_share_img',
					'label'     => __( 'Enable Pinterest Share Image', 'image-sizes' ),
					'type'      => 'switch',
					'desc'      => __( 'By enabling this you\'ll be able to set image for Pinterest share.', 'image-sizes' ),
					'disabled'  => false,
				],
			],
		];

		return $settings;
	}

	public function social_share_images_metabox() {
		$post_types = ['post', 'page', 'product'];

		add_meta_box(
			'social_share_images_metabox',
			__( 'Social Share Image', 'image-sizes' ),
			[ $this, 'render_social_share_images_metabox' ],
			$post_types,
			'normal',
			'default'
		);
	}

	public function render_social_share_images_metabox( $post, $args ) {
		$is_fb_share    = Helper::get_option( 'thumbpress-social-share', 'enable_fb_share_img', false );
		$is_ln_share    = Helper::get_option( 'thumbpress-social-share', 'enable_ln_share_img', false );
		$is_tw_share    = Helper::get_option( 'thumbpress-social-share', 'enable_tw_share_img', false );
		$is_pin_share   = Helper::get_option( 'thumbpress-social-share', 'enable_pin_share_img', false );

		// Add nonce for security
		wp_nonce_field( 'thumbpress_social_metabox_nonce', 'thumbpress_social_images_meta_box_nonce');

		// Get saved meta values
		$facebook_image     = get_post_meta( $post->ID, 'thumbpress_facebook_image', true );
		$linkedin_image     = get_post_meta( $post->ID, 'thumbpress_linkedin_image', true );
		$twitter_image      = get_post_meta( $post->ID, 'thumbpress_twitter_image', true );
		$pinterest_image    = get_post_meta( $post->ID, 'thumbpress_pinterest_image', true );

		// HTML for meta box content
		if ( $is_fb_share ) {
		?>
			<p>
				<label for="thumbpress_facebook_image"><?php echo esc_html__( 'Facebook Image:', 'image-sizes' ); ?></label><br>
				<input type="text" id="thumbpress_facebook_image" name="thumbpress_facebook_image" readonly value="<?php echo esc_attr( $facebook_image ); ?>" size="50">
				<button type="button" class="button thumbpress_upload_image_button"><?php echo esc_html__( 'Upload Image', 'image-sizes' ); ?></button>
			</p>
		<?php 
		}

		if ( $is_ln_share ) {
		?>
			<p>
				<label for="thumbpress_linkedin_image"><?php echo esc_html__( 'LinkedIn Image:', 'image-sizes' ); ?></label><br>
				<input type="text" id="thumbpress_linkedin_image" name="thumbpress_linkedin_image" readonly value="<?php echo esc_attr( $linkedin_image ); ?>" size="50">
				<button type="button" class="button thumbpress_upload_image_button"><?php echo esc_html__( 'Upload Image', 'image-sizes' ); ?></button>
			</p>
		<?php 
		}

		if ( $is_tw_share ) {
		?>
			<p>
				<label for="thumbpress_twitter_image"><?php echo esc_html__( 'Twitter Image:', 'image-sizes' ); ?></label><br>
				<input type="text" id="thumbpress_twitter_image" name="thumbpress_twitter_image" readonly value="<?php echo esc_attr( $twitter_image ); ?>" size="50">
				<button type="button" class="button thumbpress_upload_image_button"><?php echo esc_html__( 'Upload Image', 'image-sizes' ); ?></button>
			</p>
		<?php 
		}

		if ( $is_pin_share ) {
		?>
			<p>
				<label for="thumbpress_pinterest_image"><?php echo esc_html__( 'Pinterest Image:', 'image-sizes' ); ?></label><br>
				<input type="text" id="thumbpress_pinterest_image" name="thumbpress_pinterest_image" readonly value="<?php echo esc_attr( $pinterest_image ); ?>" size="50">
				<button type="button" class="button thumbpress_upload_image_button"><?php echo esc_html__( 'Upload Image', 'image-sizes' ); ?></button>
			</p>
		<?php 
		}
		?>
		<script>
			jQuery(document).ready(function($){
				// Media uploader
				var thumbpress_uploader;

				$(document).on( 'click', '.thumbpress_upload_image_button', function(e) {
					e.preventDefault();
					// 'this' refers to the button that was clicked
					var clickedButton = $(this);
					var associatedInput = clickedButton.prev('input');
					
					// console.log("before select ", associatedInput);

					// Extend the wp.media object
					thumbpress_uploader = wp.media({
						title: 'Choose Image',
						button: {
							text: 'Choose Image'
						},
						multiple: false
					});


					// When a file is selected, grab the URL and set it as the text field's value
					thumbpress_uploader.on('select', function() {
						var attachment = thumbpress_uploader.state().get('selection').first().toJSON();
						associatedInput.val(attachment.url);
						// console.log("after select ", associatedInput);
					});

					// Open the uploader dialog
					thumbpress_uploader.open();
				});
			});
		</script>
		<?php
	}

	public function save_social_share_images_meta( $post_id ) {
		// Check if nonce is set and verify nonce
		if ( ! isset( $_POST['thumbpress_social_images_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['thumbpress_social_images_meta_box_nonce'], 'thumbpress_social_metabox_nonce' ) ) return;

		// Check if this is an autosave or the user's permissions
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || ! current_user_can( 'edit_post', $post_id ) ) return;

		$meta_fields = ['thumbpress_facebook_image', 'thumbpress_linkedin_image', 'thumbpress_twitter_image', 'thumbpress_pinterest_image'];

		// Update or save meta values
		foreach ( $meta_fields as $field ) {
			if ( isset( $_POST[$field] ) && $_POST[$field] != '' ) {
				update_post_meta( $post_id, $field, sanitize_text_field( $_POST[$field] ) );
			} else {
				delete_post_meta( $post_id, $field );
			}
		}
	}

	public function show_social_share_images() {
		// Check if single post
		if ( ! is_singular( 'post' ) && ! is_singular( 'page' ) && ! is_singular( 'product' ) ) return;

		// Get post data
		$post_id        = get_the_ID();
		$post_url	    = get_permalink();
		$post_title     = get_the_title();
		$post_desc      = get_the_excerpt();
		$site_name      = get_bloginfo( 'name' );
		$fb_img         = get_post_meta( $post_id, 'thumbpress_facebook_image', true );
		$ln_img         = get_post_meta( $post_id, 'thumbpress_linkedin_image', true );
		$tw_img         = get_post_meta( $post_id, 'thumbpress_twitter_image', true );
		$pin_img        = get_post_meta( $post_id, 'thumbpress_pinterest_image', true );
		$is_fb_share    = Helper::get_option( 'thumbpress-social-share', 'enable_fb_share_img', false );
		$is_ln_share    = Helper::get_option( 'thumbpress-social-share', 'enable_ln_share_img', false );
		$is_tw_share    = Helper::get_option( 'thumbpress-social-share', 'enable_tw_share_img', false );
		$is_pin_share   = Helper::get_option( 'thumbpress-social-share', 'enable_pin_share_img', false );
		
		// Facebook share image
		if ( $is_fb_share && $fb_img ) {
			$fb_img_info    = thubmpress_get_image_info( $fb_img );

			if ( empty( $fb_img_info ) && is_array( $fb_img_info ) && in_array( ['width' , 'height', 'alt', 'type'], $fb_img_info ) ) {
				printf(
					'<meta property="og:site_name" content="%1s" />
					<meta property="og:title" content="%2s" />
					<meta property="og:description" content="%3s" />
					<meta property="og:url" content="%4s" />
					<meta property="og:image" content="%5s" />
					<meta property="og:image:width" content="%6s" />
					<meta property="og:image:height" content="%7s" />
					<meta property="og:image:alt" content="%8s" />
					<meta property="og:image:type" content="%9s" />',
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
		
		// Linkedin share image
		if ( $is_ln_share && $ln_img ) {
			$ln_img_info    = thubmpress_get_image_info( $ln_img );

			if ( empty( $ln_img_info ) && is_array( $ln_img_info ) && in_array( ['width' , 'height', 'alt', 'type'], $ln_img_info ) ) {
				printf( 
					'<meta property="og:site_name" content="%1s" />
					<meta property="og:title" content="%2s" />
					<meta property="og:description" content="%3s" />
					<meta property="og:url" content="%4s" />
					<meta property="og:image" content="%5s" />
					<meta property="og:image:width" content="%6s" />
					<meta property="og:image:height" content="%7s" />
					<meta property="og:image:alt" content="%8s" />
					<meta property="og:image:type" content="%9s" />',
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

		// Twitter share image
		if ( $is_tw_share && $tw_img ) {
			$post_author_id = get_post_field( 'post_author', $post_id );
			$post_author    = get_userdata( $post_author_id );

			printf(
				'<meta name="twitter:card" content="summary_large_image" />
				<meta name="twitter:title" content="%1s" />
				<meta name="twitter:description" content="%2s" />
				<meta name="twitter:image" content="%3s" />
				<meta name="og:image" content="%4s" />
				<meta name="twitter:label1" content="Written by" />
				<meta name="twitter:data1" content="%5s" />',
				esc_html( $post_title ),
				esc_html( $post_desc ),
				esc_url( $tw_img ),
				esc_url( $tw_img ),
				esc_html( $post_author->display_name )
			);
		}

		// Pinterest share image
		if ( $is_pin_share && $pin_img ) {
			$post_author_id = get_post_field( 'post_author', $post_id ) ;
			$post_author    = get_userdata( $post_author_id );
			$post_type 		= get_post_type();
			
			if ( in_array( $post_type, ['post', 'page'] ) ) : 

				printf( 
					'<meta property="og:type" content="article" />
					<meta property="og:title" content="%1s" />
					<meta property="og:description" content="%2s" />
					<meta property="og:image" content="%3s" />
					<meta property="og:url" content="%4s" />
					<meta property="og:site_name" content="%5s" />
					<meta property="article:published_time" content="%6s" />
					<meta property="article:author" content="%7s" />',
					esc_html( $post_title ),
					esc_html( $post_desc ),
					esc_url( $pin_img ),
					esc_url( $post_url ),
					esc_attr( $site_name ),
					esc_attr( get_post_field( 'post_date', $post_id ) ),
					esc_html( $post_author->display_name )
				);

			endif;

			if ( $post_type === 'product' ) : 
				$product 		= wc_get_product( $post_id );
				$price 			= $product->get_price();
				$currency 		= get_woocommerce_currency();
				$is_available 	= $product->is_in_stock();

				printf(
					'<meta property="og:type" content="product" />
					<meta property="og:title" content="%1s" />
					<meta property="og:description" content="%2s" />
					<meta property="og:image" content="%3s" />
					<meta property="og:url" content="%4s" />
					<meta property="og:site_name" content="%5s" />
					<meta property="product:price:amount" content="%6s" />
					<meta property="product:price:currency" content="%7s" />
					<meta property="og:availability" content="%8s" />',
					esc_html( $post_title ),
					esc_html( $post_desc ),
					esc_url( $pin_img ),
					esc_url( $post_url ),
					esc_attr( $site_name ),
					esc_attr( $price ),
					esc_attr( $currency ),
					$is_available ? esc_html( 'instock' ) : esc_html( 'outofstock' )
				);

			endif;
		}
	}
}