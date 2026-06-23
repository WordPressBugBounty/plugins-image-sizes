<?php
namespace Codexpert\ThumbPress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Traits\Hook;
use Codexpert\ThumbPress\Traits\Asset;

/**
 * Shows upgrade-prompt buttons for premium features on the single
 * media/attachment edit screen when ThumbPress Pro is not active.
 */
class Media_Buttons {

	use Hook;
	use Asset;

	public function __construct() {

		$this->action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		if ( defined( 'THUMBPRESS_PRO_VERSION' ) ) {
			return;
		}

		$this->filter( 'attachment_fields_to_edit', array( $this, 'add_compress_field' ), 16, 2 );
		$this->filter( 'attachment_fields_to_edit', array( $this, 'add_replace_button' ), 17, 2 );
		$this->filter( 'attachment_fields_to_edit', array( $this, 'add_editor_button' ), 18, 2 );
		$this->action( 'admin_footer', array( $this, 'render_modal' ) );
	}

	/**
	 * Enqueue CSS on media pages. JS + localize only when Pro is not active.
	 */
	public function enqueue_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'upload.php' ), true ) ) {
			return;
		}

		$this->enqueue_style(
			'thumbpress-media-buttons',
			THUMBPRESS_PLUGIN_URL . 'assets/admin/css/media-buttons.css',
			array(),
			THUMBPRESS_VERSION
		);

		if ( defined( 'THUMBPRESS_PRO_VERSION' ) ) {
			return;
		}

		$this->enqueue_script(
			'thumbpress-media-buttons',
			THUMBPRESS_PLUGIN_URL . 'assets/admin/js/media-buttons.js',
			array( 'image-sizes_admin' ),
			THUMBPRESS_VERSION,
			true
		);

		$upgrade_url = admin_url( 'admin.php' ) . '?page=thumbpress#/pro';

		wp_localize_script(
			'thumbpress-media-buttons',
			'THUMBPRESS_MEDIA',
			array(
				'upgrade_url' => esc_url( $upgrade_url ),
				'features'    => array(
					'compress' => array(
						'title'       => __( 'Image Compression is a Pro feature', 'image-sizes' ),
						'description' => __( 'Reduce file sizes without losing visual quality. Compress images in bulk or one at a time to speed up your site.', 'image-sizes' ),
						'cta'         => __( 'Upgrade to compress images', 'image-sizes' ),
					),
					'replace'  => array(
						'title'       => __( 'Image Replacement is a Pro feature', 'image-sizes' ),
						'description' => __( 'Swap out images without losing their metadata or breaking existing links. Drop in a new file and ThumbPress handles the rest.', 'image-sizes' ),
						'cta'         => __( 'Upgrade to replace images', 'image-sizes' ),
					),
					'editor'   => array(
						'title'       => __( 'Image Editor is a Pro feature', 'image-sizes' ),
						'description' => __( 'Crop, rotate, flip and adjust images directly inside WordPress — no external tools needed.', 'image-sizes' ),
						'cta'         => __( 'Upgrade to edit images', 'image-sizes' ),
					),
				),
			)
		);
	}

	/**
	 * "Compress Image" row — dropdown + button, matching Pro's UI.
	 */
	public function add_compress_field( $form_fields, $post ) {
		if ( ! in_array( $post->post_mime_type, thumbpress_supported_image_mimes(), true ) ) {
			return $form_fields;
		}

		$select_options = array(
			'10'  => '10%',
			'20'  => '20%',
			'30'  => '30%',
			'40'  => '40%',
			'50'  => '50%',
			'60'  => '60%',
			'70'  => '70%',
			'80'  => '80%',
			'90'  => '90%',
			'100' => '100%',
		);

		$select = '<select class="thumbpress-media-select select-thumb thumbpress-media-btn" data-feature="compress">';
		$select .= '<option value="">' . esc_html__( 'Select Option', 'image-sizes' ) . '</option>';
		foreach ( $select_options as $value => $label ) {
			$select .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
		}
		$select .= '</select>';

		$gear_icon = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.6864 8.8409C11.6005 9.13891 11.4814 9.42631 11.3312 9.69768L11.6318 10.2877C11.7279 10.4763 11.6932 10.6958 11.5435 10.8455L10.8455 11.5435C10.6958 11.6931 10.4764 11.7279 10.2878 11.6318L9.69769 11.3311C9.42632 11.4813 9.13894 11.6005 8.84094 11.6864L8.63644 12.3158C8.57104 12.5171 8.39125 12.6477 8.17963 12.6477H7.19247C6.98082 12.6477 6.80103 12.5171 6.73563 12.3159L6.53113 11.6864C6.23312 11.6005 5.94572 11.4813 5.67435 11.3312L5.08428 11.6318C4.89569 11.7279 4.67622 11.6931 4.52657 11.5435L3.82853 10.8455C3.67888 10.6958 3.64413 10.4763 3.74019 10.2877L4.04088 9.69765C3.89072 9.42628 3.77155 9.13889 3.6856 8.8409L3.05619 8.6364C2.85491 8.57099 2.72428 8.39121 2.72428 8.17955V7.1924C2.72428 6.98077 2.85491 6.80096 3.05616 6.73555L3.6856 6.53105C3.77153 6.23305 3.8907 5.94565 4.04085 5.67427L3.74019 5.08421C3.6441 4.89561 3.67888 4.67615 3.82853 4.52649L4.52653 3.82846C4.67619 3.6788 4.89569 3.64405 5.08425 3.74011L5.67435 4.0408C5.94571 3.89064 6.2331 3.77147 6.5311 3.68552L6.7356 3.05611C6.801 2.85483 6.98078 2.72421 7.19241 2.72421H8.17957C8.39122 2.72421 8.571 2.8548 8.63638 3.05611L8.84091 3.68561C9.13888 3.77154 9.42625 3.89068 9.6976 4.0408L10.2877 3.74011C10.4763 3.64402 10.6958 3.6788 10.8454 3.82846L11.5435 4.52646C11.6931 4.67612 11.7279 4.89558 11.6318 5.08418L11.3312 5.67424C11.4813 5.94562 11.6005 6.23302 11.6864 6.53102L12.3158 6.73552C12.5171 6.80093 12.6478 6.98071 12.6478 7.19233V8.17949C12.6478 8.39115 12.5172 8.5709 12.3158 8.63633L11.6864 8.84083L11.6864 8.8409ZM2.25966 3.9179L2.88966 4.5479L3.52272 1.86455L0.83941 2.49761L1.4851 3.1433C-0.723778 6.15065 -0.468715 10.4012 2.251 13.121C4.69838 15.5683 8.38538 16.0199 11.2906 14.4764L10.4855 13.6713C8.03869 14.8155 5.03522 14.3776 3.01482 12.3572C0.718004 10.0604 0.466754 6.49343 2.25966 3.9179ZM11.8492 13.5074L14.5325 12.8744L13.8871 12.2289C16.096 9.22158 15.8407 4.9708 13.1209 2.25105C10.6736 -0.196354 6.98653 -0.647947 4.08128 0.895553L4.88644 1.70071C7.33322 0.55649 10.3367 0.994396 12.3571 3.0148C14.6539 5.31161 14.9054 8.87871 13.1124 11.4542L12.4822 10.8241L11.8492 13.5074L11.8492 13.5074ZM8.65072 6.7213C8.11794 6.18852 7.25413 6.18852 6.72135 6.7213C6.18857 7.25408 6.18857 8.1179 6.72135 8.65068C7.25413 9.18346 8.11794 9.18346 8.65072 8.65068C9.1835 8.1179 9.1835 7.25408 8.65072 6.7213ZM9.64788 5.72415C8.56438 4.64065 6.80769 4.64065 5.72419 5.72415C4.64069 6.80765 4.64069 8.56433 5.72419 9.64783C6.80769 10.7313 8.56438 10.7313 9.64788 9.64783C10.7314 8.56433 10.7314 6.80765 9.64788 5.72415Z" fill="currentColor"/></svg>';

		$button = sprintf(
			'<button type="button" class="button thumbpress_img_btn thumbpress-media-btn" data-feature="compress">%s%s</button>',
			$gear_icon,
			esc_html__( 'Compress', 'image-sizes' )
		);

		$form_fields['thumbpress_compress_field'] = array(
			'label' => __( 'Compress Image', 'image-sizes' ),
			'input' => 'html',
			'html'  => '<span class="thumbpress-media-compress-wrap">' . $select . $button . '</span>',
		);

		return $form_fields;
	}

	/**
	 * "Replace Image" row.
	 */
	public function add_replace_button( $form_fields, $post ) {
		if ( ! in_array( $post->post_mime_type, thumbpress_supported_image_mimes(), true ) ) {
			return $form_fields;
		}

		$icon = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.51"/></svg>';

		$form_fields['thumbpress_replace_field'] = array(
			'label' => __( 'Replace New File', 'image-sizes' ),
			'input' => 'html',
			'html'  => sprintf(
				'<button type="button" class="button thumbpress_img_btn thumbpress-media-btn" data-feature="replace">%s%s</button>',
				$icon,
				esc_html__( 'Replace New File', 'image-sizes' )
			),
		);

		return $form_fields;
	}

	/**
	 * "Image Editor" row.
	 */
	public function add_editor_button( $form_fields, $post ) {
		if ( ! in_array( $post->post_mime_type, thumbpress_supported_image_mimes(), true ) ) {
			return $form_fields;
		}

		$pencil_icon = '<svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M13.45 5.55C12.7 4.8 11.5 4.8 10.75 5.55L5.65 10.65C5.6 10.7 5.55 10.8 5.5 10.9L5 13.4C4.95 13.55 5 13.75 5.15 13.85C5.25 13.95 5.45 14 5.6 14L8.1 13.5C8.2 13.5 8.3 13.45 8.35 13.35L13.45 8.25C14.2 7.5 14.2 6.3 13.45 5.55ZM7.75 12.55L6.15 12.85L6.45 11.25L10.5 7.2L11.8 8.5L7.75 12.55ZM12.75 7.55L12.5 7.8L11.2 6.5L11.45 6.25C11.8 5.9 12.4 5.9 12.75 6.25C13.1 6.6 13.1 7.2 12.75 7.55ZM8.15 6.35C8.35 6.55 8.65 6.55 8.85 6.35C9.05 6.15 9.05 5.85 8.85 5.65L7.85 4.65C7.65 4.45 7.35 4.45 7.15 4.65L4.5 7.3L3.35 6.15C3.15 5.95 2.85 5.95 2.65 6.15L1 7.8V1.5C1 1.2 1.2 1 1.5 1H10.5C10.8 1 11 1.2 11 1.5V3.5C11 3.8 11.2 4 11.5 4C11.8 4 12 3.8 12 3.5V1.5C12 0.65 11.35 0 10.5 0H1.5C0.65 0 0 0.65 0 1.5V10.5C0 11.35 0.65 12 1.5 12H4C4.3 12 4.5 11.8 4.5 11.5C4.5 11.2 4.3 11 4 11H1.5C1.2 11 1 10.8 1 10.5V9.2L3 7.2L4.15 8.35C4.35 8.55 4.65 8.55 4.85 8.35L7.5 5.7L8.15 6.35Z" fill="currentColor"/>
			<path d="M3 3.5C3 4.35 3.65 5 4.5 5C5.35 5 6 4.35 6 3.5C6 2.65 5.35 2 4.5 2C3.65 2 3 2.65 3 3.5ZM5 3.5C5 3.8 4.8 4 4.5 4C4.2 4 4 3.8 4 3.5C4 3.2 4.2 3 4.5 3C4.8 3 5 3.2 5 3.5Z" fill="#40189D"/>
		</svg>';

		$form_fields['thumbpress_editor_field'] = array(
			'label' => __( 'Edit with ThumbPress', 'image-sizes' ),
			'input' => 'html',
			'html'  => sprintf(
				'<button id="thumbpress_edit_img" type="button" class="button thumbpress_img_btn thumbpress-media-btn" data-feature="editor">%s%s</button>',
				$pencil_icon,
				esc_html__( 'Edit Image', 'image-sizes' )
			),
		);

		return $form_fields;
	}

	/**
	 * Render the shared modal shell in admin footer.
	 */
	public function render_modal() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->base, array( 'post', 'upload' ), true ) ) {
			return;
		}
		?>
		<div id="thumbpress-media-modal" style="display:none" role="dialog" aria-modal="true">
			<div class="thumbpress-media-overlay"></div>
			<div class="thumbpress-media-box">
				<button type="button" class="thumbpress-media-close" aria-label="<?php esc_attr_e( 'Close', 'image-sizes' ); ?>">
					<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M0.260418 0.260418C0.607642 -0.086806 1.17015 -0.086806 1.51731 0.260418L5 3.7431L8.48269 0.260418C8.82991 -0.086806 9.39241 -0.086806 9.73958 0.260418C10.0868 0.607642 10.0868 1.17015 9.73958 1.51731L6.2569 5L9.73958 8.48269C10.0868 8.82991 10.0868 9.39241 9.73958 9.73958C9.39236 10.0868 8.82985 10.0868 8.48269 9.73958L5 6.2569L1.51731 9.73958C1.17009 10.0868 0.607587 10.0868 0.260418 9.73958C-0.0867505 9.39236 -0.086806 8.82985 0.260418 8.48269L3.7431 5L0.260418 1.51731C-0.086806 1.17009 -0.086806 0.607587 0.260418 0.260418Z" fill="currentColor"/>
					</svg>
				</button>

				<div class="thumbpress-media-icon">
					<svg width="18" height="17" viewBox="0 0 18 17" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M15.8307 14.8408H1.97914C1.43271 14.8408 0.989746 15.2838 0.989746 15.8302C0.989746 16.3766 1.43271 16.8196 1.97914 16.8196H15.8307C16.3771 16.8196 16.8201 16.3766 16.8201 15.8302C16.8201 15.2838 16.3771 14.8408 15.8307 14.8408Z" fill="#FFCF5C"/>
						<path d="M17.3104 2.10902C16.9678 1.91332 16.5417 1.93989 16.2261 2.17662L13.0357 4.56945L9.67714 0.371324C9.48938 0.136622 9.20512 0 8.90456 0C8.604 0 8.31973 0.136622 8.13197 0.371324L4.77346 4.56945L1.58305 2.17662C1.26741 1.93989 0.841284 1.91333 0.498687 2.10902C0.156084 2.30471 -0.0374975 2.68525 0.00607412 3.07739L0.995446 11.9819C1.05112 12.483 1.47465 12.862 1.97879 12.862H15.8303C16.3345 12.862 16.758 12.483 16.8137 11.9819L17.803 3.07739C17.8466 2.68525 17.653 2.30471 17.3104 2.10902Z" fill="#FFCF5C"/>
					</svg>
				</div>

				<h2 class="thumbpress-media-title" id="thumbpress-media-title"></h2>
				<p class="thumbpress-media-desc" id="thumbpress-media-desc"></p>

				<a class="thumbpress-media-cta" id="thumbpress-media-cta" href="#">
					<svg width="18" height="17" viewBox="0 0 18 17" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M15.8307 14.8408H1.97914C1.43271 14.8408 0.989746 15.2838 0.989746 15.8302C0.989746 16.3766 1.43271 16.8196 1.97914 16.8196H15.8307C16.3771 16.8196 16.8201 16.3766 16.8201 15.8302C16.8201 15.2838 16.3771 14.8408 15.8307 14.8408Z" fill="#1C1C1C"/>
						<path d="M17.3104 2.10902C16.9678 1.91332 16.5417 1.93989 16.2261 2.17662L13.0357 4.56945L9.67714 0.371324C9.48938 0.136622 9.20512 0 8.90456 0C8.604 0 8.31973 0.136622 8.13197 0.371324L4.77346 4.56945L1.58305 2.17662C1.26741 1.93989 0.841284 1.91333 0.498687 2.10902C0.156084 2.30471 -0.0374975 2.68525 0.00607412 3.07739L0.995446 11.9819C1.05112 12.483 1.47465 12.862 1.97879 12.862H15.8303C16.3345 12.862 16.758 12.483 16.8137 11.9819L17.803 3.07739C17.8466 2.68525 17.653 2.30471 17.3104 2.10902Z" fill="#1C1C1C"/>
					</svg>
					<span id="thumbpress-media-cta-text"></span>
				</a>
			</div>
		</div>
		<?php
	}
}
