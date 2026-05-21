<?php
namespace Codexpert\ThumbPress\Helpers\Field;

use Codexpert\ThumbPress\Abstracts\Field;

defined( 'ABSPATH' ) || exit;

/**
 * Time Field Class
 */
class Time extends Text {

	public function __construct( $config = array() ) {
		parent::__construct( $config );
		$this->set_type( 'time' );
	}
}
