<?php
namespace Codexpert\ThumbPress;
use Codexpert\ThumbPress\Helper;

$modules        = thumbpress_modules();
$active_modules = get_option( 'thumbpress_modules', [] );

echo '
<div class="step-two">
<p class="cx-wizard-sub">
	 ' . __( 'Please enable/disable the features you want to use. You can always change your settings later based on your needs.', 'image-sizes' ) . '
</p>

<table class="form-table" id="image_sizes-form-table">
	<thead>
		<tr>
        <th>' . __( 'Name', 'image-sizes' ) . '</th>
        <th>' . __( 'Type', 'image-sizes' ) . '</th>
        <th>' . __( 'Enable/Disable', 'image-sizes' ) . '</th>
		</tr>
	</thead>
	<tbody>
';

foreach ( $modules as $module ) {
	global $thumbpress_pro;
    $type       	= $module['pro'] == 1 ? __( 'Pro', 'image-sizes' ) : __( 'Free', 'image-sizes' );
    $_checked   	= array_key_exists( $module['id'], $active_modules ) ? 'checked' : '';
	$activated 		= isset( $thumbpress_pro['license'] ) && $thumbpress_pro['license']->_is_activated();
 	$module_class 	= $module['pro'] ? 'pro' : 'free';
	$disabled 		= $module['pro'] && ! $activated ? 'disabled' : ''; 
	$module_class 	= $activated && $module['pro'] ? 'pro-active' : $module_class;
	echo "
		<tr id='row-". esc_attr( $module['id'] ) ." ' class='image-sizes-". esc_attr( $module_class ) ."'>
			<td>". esc_html( $module['title'] ) ."</td>
			
            <td>" . $type . "</td>
            <td class='image_sizes-switch-col'>
				<label class='image_sizes-switch'>
				  <input ". $disabled ." name='modules[" . $module['id'] . "]' value='on' type='checkbox' " . $_checked . " class='image_sizes-switch-checkbox'>
				  <span class='image_sizes-slider round'></span> 
				</label>
			</td>
		</td>";
}

echo '
	</tbody>
</table>
';
?>

<div id="thumb-pro-popup" style="display: none;">
	<button id="thumb-pro-popup-hide" type="button">&times;</button>
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
</div>

<script>	
	jQuery(function ($) {
		
		// modules pro popup show
        $(".cx-wizard-container .image-sizes-pro .image_sizes-switch").click(function (e) {
			console.log('work');
            $("#thumb-pro-popup").slideDown("fast");
        });
        
        // modules pro popup hide
        $("#thumb-pro-popup-hide").click(function (e) {
            $("#thumb-pro-popup").slideUp("fast");
        });
    });
</script>