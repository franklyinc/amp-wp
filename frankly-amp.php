<?php
/**
 * SUPER HACK!! Include all the stubs/copies of internal WordPress functions since we"re running outside the WordPress environment
 */
require_once 'includes/utils/frankly-amp-utils.php';

/**
 * Call the basic initialization done by default in the original module
 * This registers all the sanitizers, etc.
 * Requires the frankly-amp-utils above for access to WordPress functions
 */
require_once 'amp.php';

/**
 * Register custom classes
 */
AMP_Autoloader::register_autoload_class( 'Frankly_AMP_Slideshow_Embed_Handler', 'includes/embeds/frankly-amp-embeds/class-frankly-amp-slideshow-embed' );
AMP_Autoloader::register_autoload_class( 'Frankly_AMP_Facebook_Embed_Handler', 'includes/embeds/frankly-amp-embeds/class-frankly-amp-facebook-embed' );
AMP_Autoloader::register_autoload_class( 'Frankly_AMP_Instagram_Embed_Handler', 'includes/embeds/frankly-amp-embeds/class-frankly-amp-instagram-embed' );

$html = file_get_contents( 'php://input' );

/**
 * Adding custom handlers
 */
$embed_handlers = amp_get_content_embed_handlers( $html );
$embed_handlers['Frankly_AMP_Slideshow_Embed_Handler'] = array();
$embed_handlers['Frankly_AMP_Facebook_Embed_Handler']  = array();
$embed_handlers['Frankly_AMP_Instagram_Embed_Handler'] = array();

$amp_content = new AMP_Content(
	$html,
	$embed_handlers,
	amp_get_content_sanitizers( $html ),
	array(
		'content_max_width' => 400,
	)
);

echo $amp_content->get_amp_content();
