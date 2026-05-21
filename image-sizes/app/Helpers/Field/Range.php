<?php
namespace Codexpert\ThumbPress\Helpers\Field;

use Codexpert\ThumbPress\Abstracts\Field;

defined( 'ABSPATH' ) || exit;

/**
 * Range Field Class
 */
class Range extends Text {

	public function __construct( $config = array() ) {
		parent::__construct( $config );
		$this->set_type( 'range' );
	}
}
