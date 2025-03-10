<?php

namespace Codexpert\ThumbPress;
use Codexpert\Plugin\Base;

if ( ! class_exists( 'Notice' ) ) {
    class Notice {

        private $intervals = [];

        private $expiry = MONTH_IN_SECONDS;
        
        private $message = '';
        
        private $id = '';

        private $install_time = '';

        private $current_time;

        private $screens = [];

        public function __construct( $id ) {
            $this->id = sanitize_key( $id );
            $this->install_time = $this->id . '_install_time';
            $this->current_time = date_i18n( 'U' );

            if ( ! get_option( $this->install_time ) ) {
                update_option( $this->install_time, $this->current_time );
            }

            add_action( 'wp_ajax_tp_hide_notice', [ $this, 'hide_notice' ] );
        }

        public function set_intervals( $intervals ) {
            if ( is_array( $intervals ) ) {
                $this->intervals = $intervals;
            }
        }

        public function set_expiry( $expiry = MONTH_IN_SECONDS ) {
            $this->expiry = $expiry;
        }

        public function set_message( $message ) {
            $this->message = $message;
        }

        /**
         * Set specific screens where the notice should appear.
         *
         * @param array $screens Array of screen IDs (e.g., 'dashboard', 'post', 'edit-post').
         */
        public function set_screens( $screens ) {
            if ( is_array( $screens ) ) {
                $this->screens = $screens;
            }
        }

        public function render() {
            $install_time   = get_option( $this->install_time, $this->current_time );
            $last_dismissed = get_option( $this->id . '_dismissed', 0 );

            foreach ( $this->intervals as $interval ) {
                $show_time = $install_time + $interval;

                if ( 
                    $this->current_time >= $show_time && 
                    $last_dismissed < $show_time && 
                    $this->current_time <= $install_time + $this->expiry 
                ) {
                    add_action( 'admin_notices', [ $this, 'output_notice' ] );
                    return;
                }
            }

        }

        public function output_notice() {
            $screen = get_current_screen();

            if ( ! empty( $this->screens ) && ! in_array( $screen->id, $this->screens, true ) ) {
                return;
            }

            ?>
            <div class="notice notice-info is-dismissible" data-notice-id="<?php echo esc_attr( $this->id ); ?>">
                <?php echo wp_kses_post( $this->message ); ?>
            </div>
            <script type="text/javascript">
                (function($) {
                    $('.notice[data-notice-id="<?php echo esc_js( $this->id ); ?>"]').on('click', '.notice-dismiss, .thumbpress-dismissible-notice-button', function() {
                        $.post(ajaxurl, {
                            action: 'tp_hide_notice',
                            notice_id: '<?php echo esc_js( $this->id ); ?>',
                        });
                    });
                    
                })(jQuery);
            </script>
            <?php
        }

        public function hide_notice() {
            if ( isset( $_POST['notice_id'] ) && $_POST['notice_id'] === $this->id ) {
                update_option( $this->id . '_dismissed', $this->current_time );
                wp_send_json_success();
            }

            wp_send_json_error();
        }
    }
}