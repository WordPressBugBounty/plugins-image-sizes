<?php
namespace Codexpert\ThumbPress\Helpers\Field;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Abstracts\Field;

/**
 * Time Field Class
 */
class Time extends Text {

	public function __construct( $config = array() ) {
		parent::__construct( $config );
		$this->set_type( 'time' );
	}
}
