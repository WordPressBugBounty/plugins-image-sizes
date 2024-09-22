<?php
namespace Codexpert\ThumbPress\Modules;

use Codexpert\ThumbPress\Helper;
use Codexpert\Plugin\Base;

class Disable_Thumbnails extends Base {
    public $slug;
	public $version;
	public $id = 'prevent_image_sizes';

	/**
	 * Constructor
	 */
    public function __construct() {
        $this->plugin	= get_plugin_data( THUMBPRESS );
        $this->slug		= $this->plugin['TextDomain'];
        $this->version	= $this->plugin['Version'];

        $this->action( 'admin_enqueue_scripts', 'enqueue_scripts' );
		$this->action( 'admin_head', 'set_init_sizes' );
		// Stop regenerating thumbnails
		$this->filter( 'intermediate_image_sizes_advanced', 'image_sizes' );
		$this->filter( 'big_image_size_threshold', 'big_image_size', 10, 1 );
    }

	public function __settings ( $settings ) {
        $settings['sections'][ $this->id ] = [
            'id'        => $this->id,
            'label'     => __( 'Disable Thumbnails', 'image-sizes' ),
            'icon'      => 'dashicons-edit-large',
            'sticky'    => false,
            'content'	=> Helper::get_template( 'settings', 'modules/disable-thumbnails/views' ),
        ];

        return $settings;
    }

    public function enqueue_scripts() {
        wp_enqueue_script( "disable-thumbnails-js", plugins_url( 'js/admin.js', __FILE__ ), [ 'jquery' ], $this->version, true );
    }

	public function set_init_sizes() {
		update_option( '_image-sizes', Helper::default_image_sizes() );
	}

    public function image_sizes( $sizes ){
        $disables = Helper::get_option( 'prevent_image_sizes', 'disables', [] );
        
        if( count( $disables ) ) :
	        foreach( $disables as $disable ){
	            unset( $sizes[ $disable ] );
	        }
        endif;
        
        return $sizes;
    }

    public function big_image_size( $threshold ) {
    	$disables = Helper::get_option( 'prevent_image_sizes', 'disables', [] );

    	return in_array( 'scaled', $disables ) ? false : $threshold;
    }
}