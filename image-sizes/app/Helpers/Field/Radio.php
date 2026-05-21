<?php
namespace Codexpert\ThumbPress\Helpers\Field;

use Codexpert\ThumbPress\Abstracts\Field;

defined( 'ABSPATH' ) || exit;

/**
 * Radio Field Class
 */
class Radio extends Multicheck {
	protected $option_type = 'radio';
}
