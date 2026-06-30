<?php
namespace Codexpert\ThumbPress\Helpers\Field;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Abstracts\Field;

/**
 * Radio Field Class
 */
class Radio extends Multicheck {
	protected $option_type = 'radio';
}
