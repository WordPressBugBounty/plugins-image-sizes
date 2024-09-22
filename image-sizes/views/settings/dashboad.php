<div class="tp-all-in-one-section">
    <h2><?php esc_html_e( 'ThumbPress - All in One WordPress Image & Thumbnail Management Solution', 'image-sizes' ); ?></h2>
    <div class="tp-all-in-one-section-wrapper">
        <div class="tp-all-features-wrap">
            <?php
                $modules = [
                    [
                        'icon'  => 'disable-thumbnails.png',
                        'title' => __( 'Disable Thumbnails', 'image-sizes' ),
                        'desc'  => __( 'Delete and disable unnecessary thumbnails for all uploaded images', 'image-sizes' ),
                        'url'   => esc_url( 'https://thumbpress.co/modules/disable-thumbnails/' )
                    ],
                    [
                        'icon'  => 'regenerate-thumbnails.png',
                        'title' => __( 'Regenerate Thumbnails', 'image-sizes' ),
                        'desc'  => __( 'Regenerate previously deleted thumbnails anytime, no matter the size', 'image-sizes' ),
                        'url'   => esc_url( 'https://thumbpress.co/modules/regenerate-thumbnails/' )
                    ],
                    [
                        'icon'  => 'detect-unused-images.png',
                        'title' => __( 'Detect Unused Images', 'image-sizes' ),
                        'desc'  => __( 'Find unused images & delete them from your website anytime with just one click.', 'image-sizes' ),
                        'url'   => esc_url( 'https://thumbpress.co/modules/detect-unused-images/' ),
                        'pro'   => true
                    ],
                    [
                        'icon'  => 'image-upload-limit.png',
                        'title' => __( 'Image Upload Limit', 'image-sizes' ),
                        'desc'  => __( 'Set a limit for maximum image upload size and resolution to speed up', 'image-sizes' ),
                        'url'   => esc_url( 'https://thumbpress.co/modules/image-upload-limit/' )
                    ],
                    [
                        'icon'  => 'detect-large-images.png',
                        'title' => __( 'Detect Large Images', 'image-sizes' ),
                        'desc'  => __( 'Identify and compress or delete large images to free up your website', 'image-sizes' ),
                        'url'   => esc_url( 'https://thumbpress.co/modules/detect-large-images/' ),
                        'pro'   => true
                    ],
                    [
                        'icon'  => 'compress-images.png',
                        'title' => __( 'Compress Images', 'image-sizes' ),
                        'desc'  => __( 'Compress your images to reduce image size, save server space, and boost site speed.', 'image-sizes' ),
                        'url'   => esc_url( 'https://thumbpress.co/modules/compress-images/' ),
                        'pro'   => true
                    ],
                    [
                        'icon'  => 'disable-right-click-on-image.png',
                        'title' => __( 'Disable Right Click on Image', 'image-sizes' ),
                        'desc'  => __( 'Prevent visitors from downloading your images by turning off the right-click', 'image-sizes' ),
                        'url'   => esc_url( 'https://thumbpress.co/modules/disable-right-click/' )
                    ],
                    [
                        'icon'  => 'swap-image-with-new-version.png',
                        'title' => __( 'Replace Image With New Version', 'image-sizes' ),
                        'desc'  => __( 'Upload new versions of images and replace the old ones without any issues.', 'image-sizes' ),
                        'url'   => esc_url( 'https://thumbpress.co/modules/replace-image-with-new-version/' ),
                        'pro'   => true
                    ],
                    [
                        'icon'  => 'image-editor.png',
                        'title' => __( 'Image Editor', 'image-sizes' ),
                        'desc'  => __( 'Enhance images with filters and adjustments to showcase their best', 'image-sizes' ),
                        'url'   => esc_url( 'https://thumbpress.co/modules/image-editor/' ),
                        'pro'   => true
                    ],
                    [
                        'icon'  => 'social-media-thumbnails.png',
                        'title' => __( 'Social Media Thumbnails', 'image-sizes' ),
                        'desc'  => __( 'Enjoy the freedom of setting separate thumbnails for different social media', 'image-sizes' ),
                        'url'   => esc_url( 'https://thumbpress.co/modules/set-social-media-thumbnails/' )
                    ],
                    [
                        'icon'  => 'convert-images-into-webp.png',
                        'title' => __( 'Convert Images into WebP', 'image-sizes' ),
                        'desc'  => __( 'Convert images to WebP format to retain image quality without needing', 'image-sizes' ),
                        'url'   => esc_url( 'https://thumbpress.co/modules/convert-images-to-webp/' )
                    ],
                ];
            ?>

            <?php foreach ( $modules as $module ) : ?>
                <div class="tp-single-features-wrap">
                    <div class="tp-single-fea-heading">
                        <img src="<?php echo esc_url( THUMBPRESS_ASSET . '/img/settings/dashboard/' . $module['icon'] ); ?>">
                    </div>

                    <div class="tp-single-fea-content">
                        <h3><?php echo esc_html( $module['title'] ); ?></h3>
                        <p><?php echo esc_html( $module['desc'] ); ?></p>
                    </div>

                    <div class="tp-button-wrap">
                        <a class="tp-button" href="<?php echo esc_url( $module['url'] ); ?>" target="_blank">
                            <?php esc_html_e( 'View Details', 'image-sizes' ); ?>
                        </a>
                    </div>
                    <?php if ( ! empty( $module['pro'] ) ) : ?>
                        <div class="tp-pro">
                            <?php esc_html_e( 'Pro', 'image-sizes' ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="tp-single-features-wrap" style="background: unset">
            </div>
        </div>
    </div>
</div>

<div class="tp-get-best-section">
    <h2><?php esc_html_e( 'Get Best Out of ThumbPress', 'image-sizes' ); ?></h2>
    <div class="tp-best-out-of-thumbpress-wrap">
    <?php
        $options = [
            [
                'color'     => 'tp-pink',
                'icon'      => 'how-your-love.png',
                'title'     => __( 'Show Your Love', 'image-sizes' ),
                'desc'      => __( 'Take 2 minutes to review ThumbPress and help us keep motivated.', 'image-sizes' ),
                'link_text' => __( 'Leave a review', 'image-sizes' ),
                'link_url'  => 'https://wordpress.org/support/plugin/image-sizes/reviews/?filter=5#new-post'
            ],
            [
                'color'     => 'tp-green',
                'icon'      => 'share-your-feedback.png',
                'title'     => __( 'Share Your Feedback', 'image-sizes' ),
                'desc'      => __( 'Share your ideas or suggestions to help make ThumbPress better. We would love to hear about them!', 'image-sizes' ),
                'link_text' => __( 'Share Feedback', 'image-sizes' ),
                'link_url'  => 'https://thumbpress.co/feedback/'
            ],
            [
                'color'     => 'tp-purple',
                'icon'      => 'read-our-blogs.png',
                'title'     => __( 'Read Our Blogs', 'image-sizes' ),
                'desc'      => __( 'Explore our blogs for fresh insights on WordPress images and Thumbnails. Join the conversation today!', 'image-sizes' ),
                'link_text' => __( 'Read Blogs', 'image-sizes' ),
                'link_url'  => 'https://thumbpress.co/blog/'
            ],
        ];
    ?>

    <?php foreach ( $options as $option ) : ?>
        <div class="tp-best-out-single-wrap <?php echo esc_attr( $option['color'] ); ?>">
            <div class="tp-best-out-header">
                <img src="<?php echo esc_url( THUMBPRESS_ASSET . '/img/settings/dashboard/' . $option['icon'] ); ?>">
            </div>
            <div class="tp-best-out-container">
                <h3><?php echo esc_html( $option['title'] ); ?></h3>
                <p><?php echo esc_html( $option['desc'] ); ?></p>
                <a href="<?php echo esc_url( $option['link_url'] ); ?>"><?php echo esc_html( $option['link_text'] ); ?></a>
            </div>
            <div class="tp-circle"></div>
        </div>
    <?php endforeach; ?>

    <!-- live support starts -->
    <div class="tp-best-out-single-wrap tp-orange">
        <div class="tp-best-out-header">
            <img src="<?php echo esc_url( THUMBPRESS_ASSET . '/img/settings/dashboard/get-support.png' ); ?>">
        </div>
        <div class="tp-best-out-container">
            <h3><?php esc_html_e( 'Get Support', 'image-sizes' ); ?></h3>
            <p><?php esc_html_e( 'Stuck with something? Our support team is always ready to help you out.', 'image-sizes' ); ?></p>

            <?php

            if( isset( $_GET['live-chat'] ) && $_GET['live-chat'] == 'enable' ) {
                update_option( 'thumbpress_live_chat_enabled', 1 );
            }

            $enabled = get_option( 'thumbpress_live_chat_enabled' ) == 1;
            printf(
                '<a href="%2$s" class="%3$s">%1$s</a>',
                $enabled ? esc_html__( 'Start Live Chat', 'image-sizes' ) : esc_html__( 'Enable Live Chat', 'image-sizes' ),
                $enabled ? '#' : esc_url( add_query_arg( 'live-chat', 'enable', admin_url( 'admin.php?page=thumbpress' ) ) ),
                $enabled ? 'tp-live-chat' : ''
            );
            ?>

        </div>
        <div class="tp-circle"></div>
    </div>
    <!-- live support ends -->

    </div>
    <?php
        global $thumbpress_pro;
        $activated = isset( $thumbpress_pro['license'] ) && $thumbpress_pro['license']->_is_activated();

        if ( ! $activated ) {
           ?>
            <div class="tp-see-more-btn">
                <a href="https://thumbpress.co/"><?php esc_html_e( 'Get ThumbPress Pro', 'image-sizes' ); ?></a>
            </div>
           <?php
        }
    ?>
</div>