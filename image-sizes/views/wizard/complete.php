<?php
use Codexpert\ThumbPress\Helper;

$plugins = [];
$all_plugins = get_plugins();

if ( array_key_exists( 'woocommerce/woocommerce.php', $all_plugins ) ) {
	if( ! array_key_exists( 'woolementor/woolementor.php', $all_plugins ) ){
		$plugins['woolementor']  = [
			'label'	=> __( 'CoDesigner (formerly Woolementor)', 'image-sizes' ),
			'desc'	=> sprintf( __( 'The Best Elementor Addon to Customize WooCommerce. Helps <a href="%s" target="_blank">boost your sales</a> significantly.', 'image-sizes' ), add_query_arg( [ 'utm_campaign' => 'image-sizes_wizard' ], 'https://codexpert.io/thumbpress/' ) ),
		];
	}
	if( ! array_key_exists( 'wc-affiliate/wc-affiliate.php', $all_plugins ) ){
		$plugins['wc-affiliate']  = [
			'label'	=> __( 'WC Affiliate', 'image-sizes' ),
			'desc'	=> sprintf( __( 'The most feature-rich yet affordable <a href="%s" target="_blank">WooCommerce Affiliate</a> Plugin.', 'image-sizes' ), add_query_arg( [ 'utm_campaign' => 'image-sizes_wizard' ], 'https://codexpert.io/wc-affiliate/' ) )
		];
	}
	
}

if ( array_key_exists( 'elementor/elementor.php', $all_plugins ) ) {
	if( ! array_key_exists( 'restrict-elementor-widgets/restrict-elementor-widgets.php', $all_plugins ) ){
		$plugins['restrict-elementor-widgets']  = [
			'label'	=> __( 'Restrict Elementor Widgets', 'image-sizes' ),
			'desc'	=> sprintf( __( 'Hide your Elementor widgets, columns or sections based on <a href="%s" target="_blank">different conditions</a>.', 'image-sizes' ), add_query_arg( [ 'utm_campaign' => 'image-sizes_wizard' ], 'https://codexpert.io/product/restrict-elementor-widgets/' ) )
		];
	}	
}

if( ! array_key_exists( 'coschool/coschool.php', $all_plugins ) ){
	$plugins['coschool']  = [
		'label'	=> __( 'CoSchool', 'image-sizes' ),
		'desc'	=> sprintf( __( 'A New & Different WordPress <a href="%s" target="_blank">LMS Plugin</a> to sell your courses online.', 'image-sizes' ), add_query_arg( [ 'utm_campaign' => 'image-sizes_wizard' ], 'https://codexpert.io/coschool/' ) )
	];
}

echo '
<div class="step-three">
	<h1 class="cx-almost">' . __( 'Congratulations!👏', 'image-sizes' ) . '</h1>
	<p class="cx-wizard-sub">' . __( 'You are all set to save more server space and get a blazing-fast website by smartly managing images and thumbnails! 😎', 'image-sizes' ) . '</p>'
    . 
    '<p class="cx-wizard-sub">' . __( "We have revamped the plugin with 11 new exciting features and launched a ThumbPress Pro. To celebrate the grand launch, we're offering up to 50% discount on the pro plans to celebrate the launch.", 'image-sizes' ) . '
	<a class ="cx-claim" target="_blank" href="https://thumbpress.co/">' . __( 'Get Pro Now', 'image-sizes' ) . '</a>
	</p>';
	
echo '
</div>

<input type="hidden" name="cx-complete" value="1" />

<div id="loader_div" class="loader_div"></div>';
?>

<script type="text/javascript">
jQuery(document).ready(function($) {
	$('#complete-btn').on('click', function(event) {        
		$(".loader_div").show();   
	});
});
</script>