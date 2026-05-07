<?php
namespace Thumbpress\Helpers\Field;

use Thumbpress\Abstracts\Field;

defined( 'ABSPATH' ) || exit;

/**
 * Password Field Class
 */
class Password extends Text {

	public function __construct( $config = array() ) {
		parent::__construct( $config );
		$this->set_type( 'password' );
	}
}
