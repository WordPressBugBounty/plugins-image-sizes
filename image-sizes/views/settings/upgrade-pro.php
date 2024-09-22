<div class="tp-pro-page-wrapper">
    
    <!-- START TITLE AND COUNTUP SECTION  -->
    <!-- <div class="tp-count-up-section" style="background-image: url('<?php echo esc_url( THUMBPRESS_ASSET . '/img/upgrade-pro/update-header-bg.png' ); ?>')">

        <h2>
           <?php esc_html_e( 'Upgrade to Manage Your WordPress Images & Thumbnails Like a Pro', 'image-sizes' ); ?>
        </h2>
        <h5>
            <?php esc_html_e( 'Rich in Features, Light in Cost!', 'image-sizes' ); ?>
        </h5>
        <div class="tp-countup-wrapper">
            <div class="tp-countup-single tp-active-user">
                <h3>
                    <?php esc_html_e( '60,000+', 'image-sizes' ); ?>
                </h3>
                <p>
                    <?php esc_html_e( 'Active Users', 'image-sizes' ); ?>
                </p>
            </div>
            <div class="tp-countup-single tp-downloads">
                <h3>
                    <?php esc_html_e( '500,000+', 'image-sizes' ); ?>
                </h3>
                <p>
                    <?php esc_html_e( 'Downloads', 'image-sizes' ); ?>
                </p>
            </div>
            <div class="tp-countup-single tp-active-user">
                <h3>
                    <?php esc_html_e( '7+', 'image-sizes' ); ?>
                </h3>
                <p>
                    <?php esc_html_e( 'Active Years', 'image-sizes' ); ?>
                </p>
            </div>
            <div class="tp-countup-single tp-active-user">
                <h3>
                    <?php esc_html_e( '7+', 'image-sizes' ); ?>
                </h3>
                <p>
                    <?php esc_html_e( 'Languages', 'image-sizes' ); ?>
                </p>
            </div>
        </div>
        <div class="tp-thumbpress-pro">
            <a href="<?php echo esc_url( 'https://thumbpress.co/pricing' ); ?>">
                <?php esc_html_e( 'Get ThumbPress Pro', 'image-sizes' ); ?>
            </a>
        </div>
    </div> -->
    <!-- END TITLE AND COUNTUP SECTION  -->

    <!-- START PRO MOUDLES SECTION  -->
    <div class="tp-pro-modules-section">
        <h2>
            <?php _e( 'ThumbPress Pro Modules', 'image-sizes' ); ?>
        </h2>
        <p>
            <?php _e( 'Refine your WordPress images, save server space, and maximize your thumbnail potential with ThumbPress Pro.', 'image-sizes' ); ?>
        </p>

        <?php
        $modules = [
            [
                'icon'  => 'compress.png',
                'title' => __( 'Compress Images', 'image-sizes' ),
                'desc'  => __( 'Reduce image size and speed up your website with our built-in image compressor.', 'image-sizes' ),
                'url'   => esc_url( 'https://thumbpress.co/modules/compress-images/?utm_source=in-plugin&utm_medium=Advance+Features+Page+&utm_campaign=Compress+Images/' ),
            ],
            [
                'icon'  => 'edit-image.png',
                'title' => __( 'Edit Images', 'image-sizes' ),
                'desc'  => __( "Edit images directly within WordPress to fit your site's aesthetics perfectly.", 'image-sizes' ),
                'url'   => esc_url( 'https://thumbpress.co/modules/image-editor/?utm_source=in-plugin&utm_medium=Advance+Features+Page+&utm_campaign=Edit+Images/' ),
            ],
            [
                'icon'  => 'Unused.png',
                'title' => __( 'Detect Unused Images', 'image-sizes' ),
                'desc'  => __( 'Free up space by detecting and removing images not used anywhere on your website.', 'image-sizes' ),
                'url'   => esc_url( 'https://thumbpress.co/modules/detect-unused-images/?utm_source=in-plugin&utm_medium=Advance+Features+Page+&utm_campaign=Detect+Unused+Images/' ),
            ],
            [
                'icon'  => 'large.png',
                'title' => __( 'Detect Large Images', 'image-sizes' ),
                'desc'  => __( 'Detect and delete large images that slow down your server and improve loading times.', 'image-sizes' ),
                'url'   => esc_url( 'https://thumbpress.co/modules/detect-large-images/?utm_source=in-plugin&utm_medium=Advance+Features+Page+&utm_campaign=Detect+Large+Images/' ),
            ],
            [
                'icon'  => 'replace.png',
                'title' => __( 'Replace Images', 'image-sizes' ),
                'desc'  => __( 'Update images with newer versions without changing their links on your site.', 'image-sizes' ),
                'url'   => esc_url( 'https://thumbpress.co/modules/replace-image-with-new-version/?utm_source=in-plugin&utm_medium=Advance+Features+Page+&utm_campaign=Replace+Images/' ),
            ]
        ];
        ?>

        <div class="tp-all-pro-modules-wrap">
            <?php foreach ( $modules as $module ) : ?>
                <div class="tp-single-module-pro">
                    <div class="tp-single-pro-module-heading">
                        <img src="<?php echo esc_url( THUMBPRESS_ASSET . '/img/upgrade-pro/' . $module['icon'] ); ?>">
                    </div>
                    <div class="tp-single-pro-module-content">
                        <h3><?php echo esc_html( $module['title'] ); ?></h3>
                        <p><?php echo esc_html( $module['desc'] ); ?></p>
                    </div>

                    <div class="tp-button-wrap">
                        <a class="tp-button" href="<?php echo esc_url( $module['url'] ); ?>" target="_blank">
                            <?php esc_html_e( 'View Details', 'image-sizes' ); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="tp-thumbpress-pro tp-thumbpress-pro-margin-top">
            <a href="<?php echo esc_url( 'https://thumbpress.co/pricing/?utm_source=in-plugin&utm_medium=Advance+Features+Page+&utm_campaign=pricing+after+modules/' ); ?>">
                <?php _e( 'Get ThumbPress Pro', 'image-sizes' ); ?>
            </a>
        </div>
    </div>
    <!-- END PRO MOUDLES SECTION  -->

    <!-- START COMPARISON SECTION -->
    <div class="tp-comarison-section">
        <h2><?php _e( 'Compare Free Vs. Pro', 'image-sizes' ); ?></h2>
        <p>
            <?php _e( 'Compare ThumbPress Free and Pro features to see which extra features you\'ll enjoy', 'image-sizes' ); ?>
        </p>
        <div class="tp-comparison-wrapper">
            <div class="tp-comparison-table-row tp-comparison-table-header-row">
                <div class="tp-col-1"><?php _e( 'Feature', 'image-sizes' ); ?></div>
                <div class="tp-col-2"><?php _e( 'Free', 'image-sizes' ); ?></div>
                <div class="tp-col-3"><?php _e( 'Pro', 'image-sizes' ); ?></div>
            </div>

            <?php
            $features = [
                [
                    'name' => esc_html__( 'Disable Thumbnails', 'image-sizes' ),
                    'free' => '<i class="fa-solid fa-check tp-success"></i>',
                    'pro'  => '<i class="fa-solid fa-check tp-success"></i>',
                ],
                [
                    'name' => esc_html__( 'Regenerate Thumbnails', 'image-sizes' ),
                    'free' => '<i class="fa-solid fa-check tp-success"></i>',
                    'pro'  => '<i class="fa-solid fa-check tp-success"></i>',
                ],
                [
                    'name' => esc_html__( 'Detect Unused Images', 'image-sizes' ),
                    'free' => '<i class="fa-solid fa-xmark tp-cross"></i>',
                    'pro'  => '<i class="fa-solid fa-check tp-success"></i>',
                ],
                [
                    'name' => esc_html__( 'Image Upload Limit', 'image-sizes' ),
                    'free' => '<i class="fa-solid fa-check tp-success"></i>',
                    'pro'  => '<i class="fa-solid fa-check tp-success"></i>',
                ],
                [
                    'name' => esc_html__( 'Detect Large Images', 'image-sizes' ),
                    'free' => '<i class="fa-solid fa-xmark tp-cross"></i>',
                    'pro'  => '<i class="fa-solid fa-check tp-success"></i>',
                ],
                [
                    'name' => esc_html__( 'Compress Images', 'image-sizes' ),
                    'free' => '<i class="fa-solid fa-xmark tp-cross"></i>',
                    'pro'  => '<i class="fa-solid fa-check tp-success"></i>',
                ],
                [
                    'name' => esc_html__( 'Disable Right Click on Image', 'image-sizes' ),
                    'free' => '<i class="fa-solid fa-check tp-success"></i>',
                    'pro'  => '<i class="fa-solid fa-check tp-success"></i>',
                ],
                [
                    'name' => esc_html__( 'Replace Image with New Version', 'image-sizes' ),
                    'free' => '<i class="fa-solid fa-xmark tp-cross"></i>',
                    'pro'  => '<i class="fa-solid fa-check tp-success"></i>',
                ],
                [
                    'name' => esc_html__( 'Social Media Thumbnails', 'image-sizes' ),
                    'free' => '<i class="fa-solid fa-check tp-success"></i>',
                    'pro'  => '<i class="fa-solid fa-check tp-success"></i>',
                ],
                [
                    'name' => esc_html__( 'Image Editor', 'image-sizes' ),
                    'free' => '<i class="fa-solid fa-xmark tp-cross"></i>',
                    'pro'  => '<i class="fa-solid fa-check tp-success"></i>',
                ],
                [
                    'name' => esc_html__( 'Convert Images into WebP', 'image-sizes' ),
                    'free' => '<i class="fa-solid fa-check tp-success"></i>',
                    'pro'  => '<i class="fa-solid fa-check tp-success"></i>',
                ],
            ];

            foreach ( $features as $feature ) :
                ?>
                <div class="tp-comparison-table-row">
                    <div class="tp-col-1"><?php echo $feature[ 'name' ]; ?></div>
                    <div class="tp-col-2"><?php echo $feature[ 'free' ]; ?></div>
                    <div class="tp-col-3"><?php echo $feature[ 'pro' ]; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="tp-thumbpress-pro tp-thumbpress-pro-margin-top">
        <a href="<?php echo esc_url( 'https://thumbpress.co/pricing/?utm_source=in-plugin&utm_medium=Advance+Features+Page+&utm_campaign=pricing+after+comparison/' ); ?>">
            <?php _e( 'Get ThumbPress Pro', 'image-sizes' ); ?>
        </a>
    </div>
    <!-- END COMPARISON SECTION  -->

    <!-- START MONEY BACK SECTION  -->
    <div class="tp-money-back-section">
        <div class="tp-money-back-wrapper">
            <div class="tp-money-back-left">
                <img src="<?php echo esc_url( THUMBPRESS_ASSET . '/img/money-back.png' ); ?>">
            </div>
            <div class="tp-money-back-right">
                <h3><?php _e( '100% Hassle-Free Money Back Guarantee!', 'image-sizes' ); ?></h3>
                <p>
                    <?php 
                        _e( 'We\'re confident that you\'ll love using ThumbPress. However, if within the next 30 days, you feel 
                        that ThumbPress isn\'t the best fit for your needs, simply reach out and we\'ll refund your money in full, 
                        no questions asked!', 'image-sizes' ); 
                    ?>
                </p>
            </div>
        </div>
    </div>
    <!-- END MONEY BACK SECTION  -->

    <!-- START USER REVIEW SECTION  -->
    <div class="tp-user-review-section">
        <h2><?php _e( 'See Why 60,000+ Users Love ThumbPress', 'image-sizes' ); ?></h2>
        <!-- data-flickity='{ "wrapAround": true, "autoPlay": 2000, "pauseAutoPlayOnHover": false, "prevNextButtons": false, "pageDots": false }' -->
        <div class="tp-user-review-wrap"
        >
        <?php 
        $reviews = [
            [
                'url'       => 'https://wordpress.org/support/topic/great-combine-with-broken-link-checker/',
                'image'     => 'Tom-Hart.jpg',
                'alt'       => 'Tomhart',
                'author'    => 'Tomhart',
                'site'      => '@WordPress.org',
                'content'   => 'My host is a pain about website size disk usage and this quickly knocked a whole bunch of space off. Combine with insanity or similar to reduce the size of initial uploads and broken link checker in case you accidentally delete an image size you are using and very quickly you\'ve got a much cleaner website.',
            ],
            [
                'url'       => 'https://wordpress.org/support/topic/6000-unnecessary-thumbnails-gone-in-10-seconds/',
                'image'     => 'Math-vault.png',
                'alt'       => 'Math Vault',
                'author'    => 'Math Vault',
                'site'      => '@WordPress.org',
                'content'   => 'Over the years, WordPress and our themes/plugins has gathered 21 different types of thumbnails, so the size was increasing at an alarming speed. By disabling all thumbnails, all 6000 thumbnails are gone for good and after a bit of manual tweaking, all media are functioning as usual. Long live this plugin!',
            ],
            [
                'url'       => 'https://wordpress.org/support/topic/beautiful-and-useful-12/',
                'image'     => 'Ajans-Medialine-(aksoysanat).jpg',
                'alt'       => 'Aksoysanat',
                'author'    => 'Aksoysanat',
                'site'      => '@WordPress.org',
                'content'   => 'I am very happy with the plugin. I\'ve been using it for a long time. I would recommend it to everyone. Before I started using this plugin, I didn\'t know that themes generate so many unnecessary preview images. It is one of the indispensable plugin of all my sites at the moment. Thank you.',
            ],
            // [
            //     'url'       => 'https://wordpress.org/support/topic/works-like-a-magic-4/',
            //     'image'     => 'Pzakhire.jpg',
            //     'alt'       => 'Pzakhire',
            //     'author'    => 'Pzakhire',
            //     'site'      => '@WordPress.org',
            //     'content'   => 'Thank you so much. you saved me. my site was making 21thumbnails and slowing down my site speed. something that you didn\'t mention is after choosing setting you have to press regenerate to start deleting old thumbs and generate the ones needed or maybe I missed that part.',
            // ],
            // [
            //     'url'       => 'https://wordpress.org/support/topic/very-helpful-plugin-296/',
            //     'image'     => 'Trst.jpg',
            //     'alt'       => 'David Coleman',
            //     'author'    => 'David Coleman',
            //     'site'      => '@WordPress.org',
            //     'content'   => 'Great plugin to keep your website images under control! Also like the ability to crop individual image sizes, if needed. Thank you very much for this plugin!',
            // ],
            // [
            //     'url'       => 'https://wordpress.org/support/topic/thumbs-up-127/',
            //     'image'     => 'Aram-Adur-(Allah-Bir)-.jpg',
            //     'alt'       => 'Aramadur',
            //     'author'    => 'Aramadur',
            //     'site'      => '@WordPress.org',
            //     'content'   => 'Thank you for the great plugin.',
            // ],
        ];
        
        foreach ( $reviews as $review ) : 
        ?>
            <div class="tp-single-review-wrap">
                <div class="tp-single-review-header">
                    <a href="<?php echo esc_url( $review[ 'url' ] ); ?>">
                        <div class="tp-single-review-left">
                            <img src="<?php echo esc_url( THUMBPRESS_ASSET . '/img/user-review/' . $review[ 'image' ] ); ?>" alt="<?php echo esc_html( $review[ 'alt' ], 'image-sizes' ); ?>">
                            <div class="tp-review-author-text">
                                <h6><?php echo esc_html( $review[ 'author' ] ); ?></h6>
                                <span><?php echo esc_html( $review[ 'site' ] ); ?></span>
                            </div>
                        </div>
                        <img src="<?php echo esc_url( THUMBPRESS_ASSET . '/img/user-review/starts.svg' ); ?>" alt="<?php echo _e( 'icon', 'image-sizes' ); ?>">
                    </a>
                </div>
                <div class="tp-single-review-content">
                    <p><?php echo esc_html( $review['content'], 'image-sizes' ); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <div class="tp-review-slider-left-shadow"></div>
        <div class="tp-review-slider-right-shadow"></div>
    </div>
    <!-- END USER REVIEW SECTION  -->

    <!-- STRAT BEST THUMBPRESS SECTION  -->
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
                    'link_url'  => 'https://thumbpress.co/feedback/?utm_source=in-plugin&utm_medium=Advance+Features+Page+&utm_campaign=feedback/'
                ],
                [
                    'color'     => 'tp-purple',
                    'icon'      => 'read-our-blogs.png',
                    'title'     => __( 'Read Our Blogs', 'image-sizes' ),
                    'desc'      => __( 'Explore our blogs for fresh insights on WordPress images and Thumbnails. Join the conversation today!', 'image-sizes' ),
                    'link_text' => __( 'Read Blogs', 'image-sizes' ),
                    'link_url'  => 'https://thumbpress.co/blog/?utm_source=in-plugin&utm_medium=Advance+Features+Page+&utm_campaign=blog/'
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
                    $enabled ? __( 'Start Live Chat', 'image-sizes' ) : __( 'Enable Live Chat', 'image-sizes' ),
                    $enabled ? '#' : add_query_arg( 'live-chat', 'enable', admin_url( 'admin.php?page=thumbpress' ) ),
                    $enabled ? 'tp-live-chat' : ''
                );
                ?>

            </div>
            <div class="tp-circle"></div>
            </div>
        </div>
    </div>
    <!-- STRAT BEST THUMBPRESS SECTION  -->

    <!-- START BANNER SECTION  -->
    <section class="tp-banner-section">
        <div class="tp-banner-wrap">
            <div class="tp-banner-bg-effect"></div>
            <div class="tp-banner-fg-effect"></div>
            <h2><?php esc_html_e( 'Effortlessly Manage WordPress Images & Thumbnails', 'image-sizes' ); ?></h2>
            <p>
                <?php _e( 'Focus on crafting your blazing-fast WordPress site and leave the image & thumbnail management to ThumbPress!', 'image-sizes' ); ?>
            </p>
            <div class="tp-main-link">
                <a href="<?php echo esc_url( 'https://thumbpress.co/pricing/?utm_source=in-plugin&utm_medium=Advance+Features+Page+&utm_campaign=pricing+in+footer/' ); ?>" class="tp-btn">
                    <?php _e( 'Get ThumbPress Pro', 'image-sizes' ); ?>
                    <span>
                        <img width="16" src="<?php echo esc_url( THUMBPRESS_ASSET . '/img/blue-right-arrow.png' ); ?>" alt="<?php _e( 'icon', 'image-sizes' ); ?>">
                    </span>
                </a>
            </div>
        </div>
    </section>
    <!-- END BANNER SECTION  -->
</div>