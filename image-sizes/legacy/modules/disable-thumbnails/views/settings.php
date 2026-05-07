<?php
namespace Codexpert\ThumbPress;
use Codexpert\ThumbPress\Helper;

// set flag
if( isset( $_GET['page'] ) && $_GET['page'] == 'image-sizes-modules' ) {
	update_option( 'image-sizes_setup_done', 1 );
}

$image_sizes 			= get_option( '_image-sizes', Helper::default_image_sizes() );
$image_sizes_disables 	= Helper::get_option( 'prevent_image_sizes', 'disables', [] );
$disables_count 		= count( $image_sizes_disables );
$enables_count 			= count( $image_sizes ) - $disables_count;
?>

<div class="image_sizes-thumbnails-panel">
	<div class="image_sizes-default-thumbnails-panel">
		<div class="image_sizes-default-thumbnails-panel-top">
			<div class="thumbpress-desc-panel">
				<?php
                // Translators: %1$d refers to the number of thumbnails currently registered.
                echo '<p class="thumbpress-desc">' . sprintf( __( 'You currently have <strong>%1$d thumbnails</strong> registered. It means, if you upload an image, it\'ll generate %1$d duplicates along with the original image.', 'image-sizes' ), count( $image_sizes ) ) . '</p>';
				echo '<p class="thumbpress-desc">' . __( 'Drag the image sizes you don\'t want to generate to the right side. The image sizes on the left will be generated.', 'image-sizes' ) . '</p>'; 
				?>
			</div>
			<div class="image_sizes-count">
				<h4>
                    <span class="disables-count">
                        <?php echo esc_html( $disables_count ); ?>
                    </span>
                    <?php esc_html_e( ' Thumbnails disabled', 'image-sizes' ); ?>
				</h4>
				<h4 class="tp-criomson">
                    <span class="enables-count">
                        <?php echo esc_html( $enables_count ); ?>
                    </span>
                    <?php esc_html_e( ' Thumbnails will be generated', 'image-sizes' ); ?>
				</h4>
			</div>
		</div>

		<div class="image_sizes-default-thumbnails">
			<div class="image_sizes-enable-thumbnails">
				<h4>
                    <?php echo esc_html__( 'Sizes Enabled ', 'image-sizes' ) . '<small>(' . esc_html__( 'will be generated', 'image-sizes' ) . ')</small>'; ?>
                </h4>
                    <div class="tp-enable-thumbnails-table-wrap">
                        <div class="image_sizes-table-heading">
                            <ul>
                                <li class="image_sizes-heading-name">
                                    <?php esc_html_e( 'Name', 'image-sizes' ); ?>
                                </li>
                                <li class="image_sizes-heading-size">
                                    <?php esc_html_e( 'Size', 'image-sizes' ); ?>
                                </li>
                                <li class="image_sizes-heading-type">
                                    <?php esc_html_e( 'Type', 'image-sizes' ); ?>
                                </li>
                                <li class="image_sizes-heading-cropped">
                                    <?php esc_html_e( 'Cropped?', 'image-sizes' ); ?>
                                </li>
                                <li></li>
                            </ul>
                        </div>
                        <ul id="sortable1" class="image_sizes-sortable enable">
                            <li class="image_sizes-original-size">
                                <span class="image_sizes-thumbnails-name">
                                    <img class="image_sizes-thumbnails-arrow-left" src="<?php echo esc_url( plugins_url( 'assets/img/arrow.png', THUMBPRESS ) ); ?>">
                                    <?php esc_html_e( 'Original Image', 'image-sizes' ); ?>
                                </span>
                                <span class="image_sizes-thumbnails-size">
                                    <?php esc_html_e( '100%', 'image-sizes' ); ?>
                                </span>
                                <span class="image_sizes-thumbnails-type">
                                    <?php esc_html_e( 'Original', 'image-sizes' ); ?>
                                </span>
                                <span class="image_sizes-thumbnails-cropped">
                                    <?php esc_html_e( 'No', 'image-sizes' ); ?>
                                </span>
                                <span>
                                    <img class="image_sizes-thumbnails-arrow-right" src="<?php echo esc_url( plugins_url( 'assets/img/arrow-black.png', THUMBPRESS ) ); ?>">
                                </span>
                            </li>
                            <?php foreach ( $image_sizes as $id => $size ):
                
                                if ( ! in_array( $id, $image_sizes_disables ) ) {
                                    $_cropped = $size['cropped'] ? __( 'Yes', 'image-sizes' ) : __( 'No', 'image-sizes' );
    
                                    echo '<li class="ui-state-default draggable-item">
                                        <span class="image_sizes-thumbnails-name"> <img class="image_sizes-thumbnails-arrow-left" src="' . esc_url( plugins_url( 'assets/img/arrow-green.png', THUMBPRESS ) ) . '">' . esc_html( $id ) . '</span> 
                                        <span class="image_sizes-thumbnails-size">' . esc_html( $size['width'] ) . 'x' . esc_html( $size['height'] ) . ' px</span> 
                                        <span class="image_sizes-thumbnails-type">' . esc_html( $size['type'] ) . '</span> 
                                        <span class="image_sizes-thumbnails-cropped">' . esc_html( $_cropped ) . '</span> 
                                        <span><img class="image_sizes-thumbnails-arrow-right" src="' . esc_url( plugins_url( 'assets/img/arrow.png', THUMBPRESS ) ) . '"></span>
                                        <input type="hidden" name="" value="' . esc_attr( $id ) . '">
                                    </li>';
                                }
                            endforeach; ?>
                        </ul>
                    </div>

                    <?php 
                    $thumbpress_modules = Helper::get_option( 'thumbpress_modules', 'regenerate-thumbnails' );

                    if ( $thumbpress_modules == 'on' ) {
                        ?>
                        <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'thumbpress-regenerate-thumbnails' ], admin_url( 'admin.php' ) ) ); ?>" class="imgs-thumb-size-back">
                            &#10550; <?php esc_html_e( 'Go to Regenerate Thumbnails Settings', 'image-sizes' ); ?>
                        </a>
                        <?php 
                    }
                    ?>                    
			</div>

			<div class="image_sizes-disable-thumbnails">
                <h4><?php esc_html_e( 'Sizes Disabled', 'image-sizes' ); ?> <small><?php esc_html_e( '(will not be generated)', 'image-sizes' ); ?></small></h4>
                <div class="tp-disable-thumbnails-table-wrap">
                    <div class="image_sizes-table-heading">
                        <ul>
                            <li class="image_sizes-heading-name">
                                <?php esc_html_e( 'Name', 'image-sizes' ); ?>
                            </li>
                            <li class="image_sizes-heading-size">
                                <?php esc_html_e( 'Size', 'image-sizes' ); ?>
                            </li>
                            <li class="image_sizes-heading-type">
                                <?php esc_html_e( 'Type', 'image-sizes' ); ?>
                            </li>
                            <li class="image_sizes-heading-cropped">
                                <?php esc_html_e( 'Cropped?', 'image-sizes' ); ?>
                            </li>
                            <li></li>
                        </ul>
                    </div>
                    <ul id="sortable2" class="image_sizes-sortable disable">
        
                        <?php foreach ( $image_sizes_disables as $id ):
    
                            if( ! isset( $image_sizes[ $id ] ) ) continue;
    
                            $size = $image_sizes[ $id ];
    
                            $_cropped = $size['cropped'] ? __( 'Yes', 'image-sizes' ) : __( 'No', 'image-sizes' );
    
                            echo '<li class="ui-state-highlight sortable-item">
                                    <span class="image_sizes-thumbnails-name"> <img class="image_sizes-thumbnails-arrow-left" src="' . esc_url( plugins_url( 'assets/img/arrow-green.png', THUMBPRESS ) ) . '">' . esc_html( $id ) . '</span> 
                                    <span class="image_sizes-thumbnails-size">' . esc_html( $size['width'] ) . 'x' . esc_html( $size['height'] ) . ' px</span> 
                                    <span class="image_sizes-thumbnails-type">' . esc_html( $size['type'] ) . '</span> 
                                    <span class="image_sizes-thumbnails-cropped">' . esc_html( $_cropped ) . '</span> 
                                    <span><img class="image_sizes-thumbnails-arrow-right" src="' . esc_url( plugins_url( 'assets/img/arrow.png', THUMBPRESS ) ) . '"></span>
                                <input type="hidden" name="" value="'. esc_attr( $id ) .'">
                            </li>';
                        endforeach; ?>
                    </ul>
                </div>
			</div>
		</div>
	</div>
</div>
