<?php
namespace Codexpert\ThumbPress\Helpers\Field;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Abstracts\Field;

/**
 * Date Field Class
 */
class Date extends Text {

	public function __construct( $config = array() ) {
		parent::__construct( $config );
		$this->set_type( 'date' );
	}
}
