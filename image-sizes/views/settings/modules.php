<?php
use Codexpert\ThumbPress\Helper;

global $thumbpress_pro;
$modules        = thumbpress_modules();
$active_modules = get_option( 'thumbpress_modules', [] );
?>

<div class="thumb-content-area">
    <div class="thumb-header-content">
		<div class="thumb-header-filter">
			<div class="thumb-group-wrap">
				<div class="thumb-filter-group">
                    <button type="button" class="thumb-filter filter-all active tp-all" data-filter=".free, .pro"><?php _e( 'All', 'image-sizes' ); ?></button>
                    <button type="button" class="thumb-filter filter-free tp-free" data-filter=".free"><?php _e( 'Free', 'image-sizes' ); ?></button>
                    <button type="button" class="thumb-filter filter-pro tp-pro-m" data-filter=".pro"><?php _e( 'Pro', 'image-sizes' ); ?></button>
                </div>
			</div>
		</div>
		<div class="thumb-toggle-group">
			<h4 class="thumb-disable"><?php _e( 'Disable All', 'image-sizes' ); ?></h4>
			<label class="thumb-toggle-all-wrap">
			  	<input type="checkbox">
			  	<span class="thumb-toggle-all thumb-module-all-active"></span>
			</label>
			<h4 class="thumb-enable"><?php _e( 'Enable All', 'image-sizes' ); ?></h4>
		</div>
	</div>

    <div class="thumb-settings-modules-container">
        <div class="<?php echo count( $active_modules ) == 0 ? "thumb-settings-modules-list" : "thumb-settings-modules-list thumb-list-column-2"; ?>">

        <?php foreach( $modules as $key=>$module ) : ?>
            <?php $module_class = $module['pro'] ? 'pro' : 'free'; ?>
            <div class="<?php echo 'thumb-widget thumb-settings-module-block ' . $module_class; ?>">
                <div class="cx-label-wrap">
                    <div class="tp-setting-modules-heading">
                        <label for="<?php echo 'thumbpress_modules-' . $key; ?>">
                            <?php echo esc_html( $module['title'] ); ?>
                        </label>
                        <div class="cx-field-wrap">
                            <label class="cx-toggle">
                            <input 
                                    type="checkbox" name="<?php echo $key; ?>" 
                                    id="<?php echo 'thumbpress_modules-'. $module['id']; ?>" 
                                    class="cx-toggle-checkbox cx-field cx-field-switch" value="on" 

                                    <?php 
                                    $activated = isset( $thumbpress_pro['license'] ) && $thumbpress_pro['license']->_is_activated();
            
                                    if ( $activated && $module['pro'] ) {
                                        echo array_key_exists( $key, $active_modules ) ? 'checked' : '';
                                    } 
                                    elseif ( ! $activated && $module['pro'] ) {
                                        echo '';
                                    }
                                    elseif ( $activated || ! $activated && ! $module['pro'] ) {
                                        echo array_key_exists( $key, $active_modules ) ? 'checked' : '';
                                    }
                                    
                                    echo ! $activated && $module['pro'] ? 'disabled' : '';
                                    ?>
                                >
                                <div class="<?php echo ! $activated && $module['pro'] ? 'cx-toggle-switch pro' : 'cx-toggle-switch' ?>"></div>
                            </label>
                        </div>
                    </div>
                    <p class="thumb-module-desc"><?php echo $module['desc']; ?></p>
                    <div class="tp-button-details">
                        <a href="<?php echo esc_url( $module['url'] ); ?>" target="_blank">
                            <?php esc_html_e( 'View Details', 'image-sizes' ); ?>
                        </a>
                    </div>
                </div>
                
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="thumb-pro-popup" style="display: none;">
	<button id="thumb-pro-popup-hide" type="button">&times;</button>

    <?php if ( ! defined( 'THUMBPRESS_PRO' ) ) : ?>
        <h2 class="thumb-pro-popup-title"><?php _e( 'Get this Premium Feature', 'image-sizes' ); ?></h2>
        <img class="wl-pro-popup-img" src="<?php echo THUMBPRESS_ASSET . '/img/pro-rocket.png'; ?>">
        <p class="thumb-pro-popup-txt"><?php _e( 'This is a Premium Feature. This feature is only available in <strong>ThumbPress Pro</strong>!', 'image-sizes' ); ?></p>
        <p class="thumb-pro-popup-txt">
            <?php _e( 'Make a smart choice today; a <strong>small investment</strong> can lead to a <strong>big boost</strong> in your website performance. Unlock All Premium Features.', 'image-sizes' ); ?>
        </p>
        <p>
            <a id="thumb-pro-popup-btn" href="<?php echo esc_url( 'https://thumbpress.co/' ); ?>" target="_blank">
                <span class="dashicons dashicons-unlock"></span>
                <?php _e( 'Unlock Premium Features', 'image-sizes' ); ?>
            </a>
        </p>
    <?php endif; ?>

    <?php if ( defined( 'THUMBPRESS_PRO' ) && isset( $thumbpress_pro['license'] ) && ! $thumbpress_pro['license']->_is_activated() ) : ?>
        <p class="thumb-pro-popup-txt">
            <?php _e( 'To use this Premium Feature. Activate your license now!', 'image-sizes' ); ?>
        </p>
        <p>
            <a id="thumb-pro-popup-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=thumbpress-license' ) ); ?>">
                <span class="dashicons dashicons-unlock"></span>
                <?php _e( 'Activate License', 'image-sizes' ); ?>
            </a>
        </p>
    <?php endif; ?>
</div>

<script>
    jQuery(function ($) {
        // modules pro popup show
        $("#thumbpress_modules .cx-toggle-switch.pro").click(function (e) {
            $("#thumb-pro-popup").slideDown("fast");
        });
        
        // modules pro popup hide
        $("#thumb-pro-popup-hide").click(function (e) {
            $("#thumb-pro-popup").slideUp("fast");
        });
    });
</script>