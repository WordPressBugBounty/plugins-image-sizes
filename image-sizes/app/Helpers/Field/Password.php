<?php
namespace Codexpert\ThumbPress\Helpers\Field;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Abstracts\Field;

/**
 * Password Field Class
 */
class Password extends Text {

	public function __construct( $config = array() ) {
		parent::__construct( $config );
		$this->set_type( 'password' );
	}
}
