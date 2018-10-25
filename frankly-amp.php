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
 * Remove core classes
 */
AMP_Autoloader::remove_autoload_class("AMP_Tag_And_Attribute_Sanitizer");
AMP_Autoloader::remove_autoload_class("AMP_Iframe_Sanitizer");

/**
 * Register custom classes
 */
AMP_Autoloader::register_autoload_class( 'Frankly_AMP_Slideshow_Embed_Handler', 'includes/embeds/frankly-amp-embeds/class-frankly-amp-slideshow-embed' );
AMP_Autoloader::register_autoload_class( 'Frankly_AMP_Facebook_Embed_Handler', 'includes/embeds/frankly-amp-embeds/class-frankly-amp-facebook-embed' );
AMP_Autoloader::register_autoload_class( 'Frankly_AMP_Instagram_Embed_Handler', 'includes/embeds/frankly-amp-embeds/class-frankly-amp-instagram-embed' );
AMP_Autoloader::register_autoload_class( 'Frankly_AMP_Video_Embed_Handler', 'includes/embeds/frankly-amp-embeds/class-frankly-amp-video-embed' );
AMP_Autoloader::register_autoload_class( 'Frankly_AMP_UB_Embed_Handler', 'includes/embeds/frankly-amp-embeds/class-frankly-amp-ub-embed' );
AMP_Autoloader::register_autoload_class( 'Frankly_AMP_Iframe_Sanitizer', 'includes/sanitizers/frankly-amp-sanitizers/class-frankly-amp-iframe-sanitizer' );
AMP_Autoloader::register_autoload_class( 'Frankly_AMP_Tag_And_Attribute_Sanitizer', 'includes/sanitizers/frankly-amp-sanitizers/class-frankly-amp-tag-and-attribute-sanitizer' );
AMP_Autoloader::register_autoload_class( 'Frankly_AMP_P_Tag_Sanitizer', 'includes/sanitizers/frankly-amp-sanitizers/class-frankly-amp-p-tag-sanitizer' );

$html = file_get_contents( 'php://input' );

/**
 * Adding custom handlers
 */
$embed_handlers = amp_get_content_embed_handlers( $html );
$embed_handlers['Frankly_AMP_Slideshow_Embed_Handler'] = array();
$embed_handlers['Frankly_AMP_Facebook_Embed_Handler']  = array();
$embed_handlers['Frankly_AMP_Instagram_Embed_Handler'] = array();
$embed_handlers['Frankly_AMP_Video_Embed_Handler'] = array();
$embed_handlers['Frankly_AMP_UB_Embed_Handler'] = array();

$sanitizer_handlers = amp_get_content_sanitizers( $html );
$sanitizer_handlers = array('Frankly_AMP_Iframe_Sanitizer'=>array('add_placeholder' => true)) + $sanitizer_handlers;
$sanitizer_handlers['Frankly_AMP_Tag_And_Attribute_Sanitizer'] = array();
$sanitizer_handlers['Frankly_AMP_P_Tag_Sanitizer'] = array();

$amp_content = new AMP_Content(
	$html,
	$embed_handlers,
	$sanitizer_handlers,
	array(
		'content_max_width' => 400,
	)
);

echo $amp_content->get_amp_content();
