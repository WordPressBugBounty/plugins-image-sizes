<?php
namespace Thumbpress\Helpers\Field;

use Thumbpress\Abstracts\Field;

defined( 'ABSPATH' ) || exit;

/**
 * Radio Field Class
 */
class Radio extends Multicheck {
	protected $option_type = 'radio';
}
