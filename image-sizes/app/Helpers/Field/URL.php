<?php
namespace Codexpert\ThumbPress\Helpers\Field;

use Codexpert\ThumbPress\Abstracts\Field;

defined( 'ABSPATH' ) || exit;

/**
 * URL Field Class
 */
class URL extends Text {

	public function __construct( $config = array() ) {
		parent::__construct( $config );
		$this->set_type( 'url' );
	}
}
