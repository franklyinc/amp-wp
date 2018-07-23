<?php

//
// SUPER HACK!! Include all the stubs/copies of internal wordpress functions since we're running outside the wordpress environment
// We need a better way to do this in prod usage.  (One partial idea I thought of as I went was adding an include directory for the original WP fiels instead of just copying the code here... )
// Probably if we want to run this for real performance we want to track down only what's needed here and stub the rest to prevent extra logic executing.

//
// Stubs or copies of internal wordpress functions needed for standalone converter outside wordpress
//

// needed to prevent: A non-numeric value encountered in includes/utils/class-amp-image-dimension-extractor.php on line 85
define("DAY_IN_SECONDS", 86400);

// STUBS
function get_bloginfo( $show = '', $filter = 'raw' ) {
  return "bloginfo";
}

function get_user_locale( $user_id = 0 ) {
  return 'en_US';
}

function is_admin() {
 return false;
}

function get_locale() {
  return 'en_US';
}

function get_option( $option, $default = false ) {
 return false;
}

function current_theme_supports( $feature ) {
  return true;
}

function _deprecated_argument( $function, $version, $message = null ) {
  return true;
}

function is_ssl() {
 return false;
}

function register_activation_hook($file, $function) {
 return true;
}

function register_deactivation_hook($file, $function) {
 return true;
}

function wp_embed_register_handler( $id, $regex, $callback, $priority = 10 ) {
 return true;
}
function wp_embed_unregister_handler( $id, $priority = 10 ) {
 return true;
}
function wp_guess_url() {
 return "https://www.google.com";
}


// REAL functions / classes

function absint( $maybeint ) {
    return abs( intval( $maybeint ) );
}

function wp_json_encode( $data, $options = 0, $depth = 512 ) {
    /*
     * json_encode() has had extra params added over the years.
     * $options was added in 5.3, and $depth in 5.5.
     * We need to make sure we call it with the correct arguments.
     */
    if ( version_compare( PHP_VERSION, '5.5', '>=' ) ) {
        $args = array( $data, $options, $depth );
    } elseif ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
        $args = array( $data, $options );
    } else {
        $args = array( $data );
    }

    // Prepare the data for JSON serialization.
    $args[0] = _wp_json_prepare_data( $data );

    $json = @call_user_func_array( 'json_encode', $args );

    // If json_encode() was successful, no need to do more sanity checking.
    // ... unless we're in an old version of PHP, and json_encode() returned
    // a string containing 'null'. Then we need to do more sanity checking.
    if ( false !== $json && ( version_compare( PHP_VERSION, '5.5', '>=' ) || false === strpos( $json, 'null' ) ) )  {
        return $json;
    }

    try {
        $args[0] = _wp_json_sanity_check( $data, $depth );
    } catch ( Exception $e ) {
        return false;
    }

    return call_user_func_array( 'json_encode', $args );
}

function _wp_json_prepare_data( $data ) {
	if ( ! defined( 'WP_JSON_SERIALIZE_COMPATIBLE' ) || WP_JSON_SERIALIZE_COMPATIBLE === false ) {
		return $data;
	}

	switch ( gettype( $data ) ) {
		case 'boolean':
		case 'integer':
		case 'double':
		case 'string':
		case 'NULL':
			// These values can be passed through.
			return $data;

		case 'array':
			// Arrays must be mapped in case they also return objects.
			return array_map( '_wp_json_prepare_data', $data );

		case 'object':
			// If this is an incomplete object (__PHP_Incomplete_Class), bail.
			if ( ! is_object( $data ) ) {
				return null;
			}

			if ( $data instanceof JsonSerializable ) {
				$data = $data->jsonSerialize();
			} else {
				$data = get_object_vars( $data );
			}

			// Now, pass the array (or whatever was returned from jsonSerialize through).
			return _wp_json_prepare_data( $data );

		default:
			return null;
	}
}

function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
    return add_filter($tag, $function_to_add, $priority, $accepted_args);
}

function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
    global $wp_filter;
    if ( ! isset( $wp_filter[ $tag ] ) ) {
        $wp_filter[ $tag ] = new WP_Hook();
    }
    $wp_filter[ $tag ]->add_filter( $tag, $function_to_add, $priority, $accepted_args );
    return true;
}

function apply_filters( $tag, $value ) {
    global $wp_filter, $wp_current_filter;

    $args = array();

    // Do 'all' actions first.
    if ( isset($wp_filter['all']) ) {
        $wp_current_filter[] = $tag;
        $args = func_get_args();
        _wp_call_all_hook($args);
    }

    if ( !isset($wp_filter[$tag]) ) {
        if ( isset($wp_filter['all']) )
            array_pop($wp_current_filter);
        return $value;
    }

    if ( !isset($wp_filter['all']) )
        $wp_current_filter[] = $tag;

    if ( empty($args) )
        $args = func_get_args();

    // don't pass the tag name to WP_Hook
    array_shift( $args );

    $filtered = $wp_filter[ $tag ]->apply_filters( $value, $args );

    array_pop( $wp_current_filter );

    return $filtered;
}

function wp_rand( $min = 0, $max = 0 ) {
  return abs( intval( random_int( $_min, $_max ) ));
}

function _doing_it_wrong( $function, $message, $version ) {
  return true;
}

function esc_html__( $text, $domain = 'default' ) {
    return esc_html( translate( $text, $domain ) );
}

function esc_html( $text ) {
    $safe_text = wp_check_invalid_utf8( $text );
    $safe_text = _wp_specialchars( $safe_text, ENT_QUOTES );
    /**
     * Filters a string cleaned and escaped for output in HTML.
     *
     * Text passed to esc_html() is stripped of invalid or special characters
     * before output.
     *
     * @since 2.8.0
     *
     * @param string $safe_text The text after it has been escaped.
     * @param string $text      The text prior to being escaped.
     */
    return apply_filters( 'esc_html', $safe_text, $text );
}

function wp_check_invalid_utf8( $string, $strip = false ) {
    $string = (string) $string;

    if ( 0 === strlen( $string ) ) {
        return '';
    }

    // Store the site charset as a static to avoid multiple calls to get_option()
    static $is_utf8 = null;
    if ( ! isset( $is_utf8 ) ) {
        $is_utf8 = in_array( get_option( 'blog_charset' ), array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' ) );
    }
    if ( ! $is_utf8 ) {
        return $string;
    }

    // Check for support for utf8 in the installed PCRE library once and store the result in a static
    static $utf8_pcre = null;
    if ( ! isset( $utf8_pcre ) ) {
        $utf8_pcre = @preg_match( '/^./u', 'a' );
    }
    // We can't demand utf8 in the PCRE installation, so just return the string in those cases
    if ( !$utf8_pcre ) {
        return $string;
    }

    // preg_match fails when it encounters invalid UTF8 in $string
    if ( 1 === @preg_match( '/^./us', $string ) ) {
        return $string;
    }

    // Attempt to strip the bad chars if requested (not recommended)
    if ( $strip && function_exists( 'iconv' ) ) {
        return iconv( 'utf-8', 'utf-8', $string );
    }

    return '';
}

function _wp_specialchars( $string, $quote_style = ENT_NOQUOTES, $charset = false, $double_encode = false ) {
    $string = (string) $string;

    if ( 0 === strlen( $string ) )
        return '';

    // Don't bother if there are no specialchars - saves some processing
    if ( ! preg_match( '/[&<>"\']/', $string ) )
        return $string;

    // Account for the previous behaviour of the function when the $quote_style is not an accepted value
    if ( empty( $quote_style ) )
        $quote_style = ENT_NOQUOTES;
    elseif ( ! in_array( $quote_style, array( 0, 2, 3, 'single', 'double' ), true ) )
        $quote_style = ENT_QUOTES;

    // Store the site charset as a static to avoid multiple calls to wp_load_alloptions()
    if ( ! $charset ) {
        static $_charset = null;
        if ( ! isset( $_charset ) ) {
            $alloptions = wp_load_alloptions();
            $_charset = isset( $alloptions['blog_charset'] ) ? $alloptions['blog_charset'] : '';
        }
        $charset = $_charset;
    }

    if ( in_array( $charset, array( 'utf8', 'utf-8', 'UTF8' ) ) )
        $charset = 'UTF-8';

    $_quote_style = $quote_style;

    if ( $quote_style === 'double' ) {
        $quote_style = ENT_COMPAT;
        $_quote_style = ENT_COMPAT;
    } elseif ( $quote_style === 'single' ) {
        $quote_style = ENT_NOQUOTES;
    }

    if ( ! $double_encode ) {
        // Guarantee every &entity; is valid, convert &garbage; into &amp;garbage;
        // This is required for PHP < 5.4.0 because ENT_HTML401 flag is unavailable.
        $string = wp_kses_normalize_entities( $string );
    }

    $string = @htmlspecialchars( $string, $quote_style, $charset, $double_encode );

    // Back-compat.
    if ( 'single' === $_quote_style )
        $string = str_replace( "'", '&#039;', $string );

    return $string;
}

function translate( $text, $domain = 'default' ) {
    $translations = get_translations_for_domain( $domain );
    $translation  = $translations->translate( $text );

    /**
     * Filters text with its translation.
     *
     * @since 2.0.11
     *
     * @param string $translation  Translated text.
     * @param string $text         Text to translate.
     * @param string $domain       Text domain. Unique identifier for retrieving translated strings.
     */
    return apply_filters( 'gettext', $translation, $text, $domain );
}

function get_translations_for_domain( $domain ) {
    global $l10n;
    if ( isset( $l10n[ $domain ] ) || ( _load_textdomain_just_in_time( $domain ) && isset( $l10n[ $domain ] ) ) ) {
        return $l10n[ $domain ];
    }

    static $noop_translations = null;
    if ( null === $noop_translations ) {
        $noop_translations = new NOOP_Translations;
    }

    return $noop_translations;
}

function _load_textdomain_just_in_time( $domain ) {
    global $l10n_unloaded;

    $l10n_unloaded = (array) $l10n_unloaded;

    // Short-circuit if domain is 'default' which is reserved for core.
    if ( 'default' === $domain || isset( $l10n_unloaded[ $domain ] ) ) {
        return false;
    }

    $translation_path = _get_path_to_translation( $domain );
    if ( false === $translation_path ) {
        return false;
    }

    return load_textdomain( $domain, $translation_path );
}

function load_textdomain( $domain, $mofile ) {
    global $l10n, $l10n_unloaded;

    $l10n_unloaded = (array) $l10n_unloaded;

    /**
     * Filters whether to override the .mo file loading.
     *
     * @since 2.9.0
     *
     * @param bool   $override Whether to override the .mo file loading. Default false.
     * @param string $domain   Text domain. Unique identifier for retrieving translated strings.
     * @param string $mofile   Path to the MO file.
     */
    $plugin_override = apply_filters( 'override_load_textdomain', false, $domain, $mofile );

    if ( true == $plugin_override ) {
        unset( $l10n_unloaded[ $domain ] );

        return true;
    }

    /**
     * Fires before the MO translation file is loaded.
     *
     * @since 2.9.0
     *
     * @param string $domain Text domain. Unique identifier for retrieving translated strings.
     * @param string $mofile Path to the .mo file.
     */
    do_action( 'load_textdomain', $domain, $mofile );

    /**
     * Filters MO file path for loading translations for a specific text domain.
     *
     * @since 2.9.0
     *
     * @param string $mofile Path to the MO file.
     * @param string $domain Text domain. Unique identifier for retrieving translated strings.
     */
    $mofile = apply_filters( 'load_textdomain_mofile', $mofile, $domain );

    if ( !is_readable( $mofile ) ) return false;

    $mo = new MO();
    if ( !$mo->import_from_file( $mofile ) ) return false;

    if ( isset( $l10n[$domain] ) )
        $mo->merge_with( $l10n[$domain] );

    unset( $l10n_unloaded[ $domain ] );

    $l10n[$domain] = &$mo;

    return true;
}

function _wp_call_all_hook($args) {
    global $wp_filter;

    $wp_filter['all']->do_all_hook( $args );
}

function _get_path_to_translation( $domain, $reset = false ) {
    static $available_translations = array();

    if ( true === $reset ) {
        $available_translations = array();
    }

    if ( ! isset( $available_translations[ $domain ] ) ) {
        $available_translations[ $domain ] = _get_path_to_translation_from_lang_dir( $domain );
    }

    return $available_translations[ $domain ];
}

function _get_path_to_translation_from_lang_dir( $domain ) {
    static $cached_mofiles = null;

    if ( null === $cached_mofiles ) {
        $cached_mofiles = array();

        $locations = array(
            WP_LANG_DIR . '/plugins',
            WP_LANG_DIR . '/themes',
        );

        foreach ( $locations as $location ) {
            $mofiles = glob( $location . '/*.mo' );
            if ( $mofiles ) {
                $cached_mofiles = array_merge( $cached_mofiles, $mofiles );
            }
        }
    }

    $locale = is_admin() ? get_user_locale() : get_locale();
    $mofile = "{$domain}-{$locale}.mo";

    $path = WP_LANG_DIR . '/plugins/' . $mofile;
    if ( in_array( $path, $cached_mofiles ) ) {
        return $path;
    }

    $path = WP_LANG_DIR . '/themes/' . $mofile;
    if ( in_array( $path, $cached_mofiles ) ) {
        return $path;
    }

    return false;
}

class NOOP_Translations {
    var $entries = array();
    var $headers = array();

    function add_entry($entry) {
        return true;
    }

    /**
     *
     * @param string $header
     * @param string $value
     */
    function set_header($header, $value) {
    }

    /**
     *
     * @param array $headers
     */
    function set_headers($headers) {
    }

    /**
     * @param string $header
     * @return false
     */
    function get_header($header) {
        return false;
    }

    /**
     * @param Translation_Entry $entry
     * @return false
     */
    function translate_entry(&$entry) {
        return false;
    }

    /**
     * @param string $singular
     * @param string $context
     */
    function translate($singular, $context=null) {
        return $singular;
    }

    /**
     *
     * @param int $count
     * @return bool
     */
    function select_plural_form($count) {
        return 1 == $count? 0 : 1;
    }

    /**
     * @return int
     */
    function get_plural_forms_count() {
        return 2;
    }

    /**
     * @param string $singular
     * @param string $plural
     * @param int    $count
     * @param string $context
     */
    function translate_plural($singular, $plural, $count, $context = null) {
            return 1 == $count? $singular : $plural;
    }

    /**
     * @param object $other
     */
    function merge_with(&$other) {
    }
}

function wp_parse_args( $args, $defaults = '' ) {
    if ( is_object( $args ) )
        $r = get_object_vars( $args );
    elseif ( is_array( $args ) )
        $r =& $args;
    else
        wp_parse_str( $args, $r );

    if ( is_array( $defaults ) )
        return array_merge( $defaults, $r );
    return $r;
}

function wp_parse_str( $string, &$array ) {
    parse_str( $string, $array );
    if ( get_magic_quotes_gpc() )
        $array = stripslashes_deep( $array );
    /**
     * Filters the array of variables derived from a parsed string.
     *
     * @since 2.3.0
     *
     * @param array $array The array populated with variables.
     */
    $array = apply_filters( 'wp_parse_str', $array );
}

function stripslashes_deep( $value ) {
    return map_deep( $value, 'stripslashes_from_strings_only' );
}

function map_deep( $value, $callback ) {
    if ( is_array( $value ) ) {
        foreach ( $value as $index => $item ) {
            $value[ $index ] = map_deep( $item, $callback );
        }
    } elseif ( is_object( $value ) ) {
        $object_vars = get_object_vars( $value );
        foreach ( $object_vars as $property_name => $property_value ) {
            $value->$property_name = map_deep( $property_value, $callback );
        }
    } else {
        $value = call_user_func( $callback, $value );
    }

    return $value;
}


function add_shortcode( $tag, $callback ) {
    global $shortcode_tags;

    if ( '' == trim( $tag ) ) {
        $message = __( 'Invalid shortcode name: Empty name given.' );
        _doing_it_wrong( __FUNCTION__, $message, '4.4.0' );
        return;
    }

    if ( 0 !== preg_match( '@[<>&/\[\]\x00-\x20=]@', $tag ) ) {
        /* translators: 1: shortcode name, 2: space separated list of reserved characters */
        $message = sprintf( __( 'Invalid shortcode name: %1$s. Do not use spaces or reserved characters: %2$s' ), $tag, '& / < > [ ] =' );
        _doing_it_wrong( __FUNCTION__, $message, '4.4.0' );
        return;
    }

    $shortcode_tags[ $tag ] = $callback;
}


function shortcode_exists( $tag ) {
    global $shortcode_tags;
    return array_key_exists( $tag, $shortcode_tags );
}

function remove_action( $tag, $function_to_remove, $priority = 10 ) {
    return remove_filter( $tag, $function_to_remove, $priority );
}

function remove_filter( $tag, $function_to_remove, $priority = 10 ) {
    global $wp_filter;

    $r = false;
    if ( isset( $wp_filter[ $tag ] ) ) {
        $r = $wp_filter[ $tag ]->remove_filter( $tag, $function_to_remove, $priority );
        if ( ! $wp_filter[ $tag ]->callbacks ) {
            unset( $wp_filter[ $tag ] );
        }
    }

    return $r;
}

function remove_shortcode($tag) {
    global $shortcode_tags;

    unset($shortcode_tags[$tag]);
}

function get_template() {
    /**
     * Filters the name of the current theme.
     *
     * @since 1.5.0
     *
     * @param string $template Current theme's directory name.
     */
    return apply_filters( 'template', get_option( 'template' ) );
}
function get_stylesheet() {
    /**
     * Filters the name of current stylesheet.
     *
     * @since 1.5.0
     *
     * @param string $stylesheet Name of the current stylesheet.
     */
    return apply_filters( 'stylesheet', get_option( 'stylesheet' ) );
}

function site_url( $path = '', $scheme = null ) {
    return get_site_url( null, $path, $scheme );
}
function get_site_url( $blog_id = null, $path = '', $scheme = null ) {
    if ( empty( $blog_id ) || !is_multisite() ) {
        $url = get_option( 'siteurl' );
    } else {
        switch_to_blog( $blog_id );
        $url = get_option( 'siteurl' );
        restore_current_blog();
    }

    $url = set_url_scheme( $url, $scheme );

    if ( $path && is_string( $path ) )
        $url .= '/' . ltrim( $path, '/' );

    /**
     * Filters the site URL.
     *
     * @since 2.7.0
     *
     * @param string      $url     The complete site URL including scheme and path.
     * @param string      $path    Path relative to the site URL. Blank string if no path is specified.
     * @param string|null $scheme  Scheme to give the site URL context. Accepts 'http', 'https', 'login',
     *                             'login_post', 'admin', 'relative' or null.
     * @param int|null    $blog_id Site ID, or null for the current site.
     */
    return apply_filters( 'site_url', $url, $path, $scheme, $blog_id );
}

function set_url_scheme( $url, $scheme = null ) {
    $orig_scheme = $scheme;

    if ( ! $scheme ) {
        $scheme = is_ssl() ? 'https' : 'http';
    } elseif ( $scheme === 'admin' || $scheme === 'login' || $scheme === 'login_post' || $scheme === 'rpc' ) {
        $scheme = is_ssl() || force_ssl_admin() ? 'https' : 'http';
    } elseif ( $scheme !== 'http' && $scheme !== 'https' && $scheme !== 'relative' ) {
        $scheme = is_ssl() ? 'https' : 'http';
    }

    $url = trim( $url );
    if ( substr( $url, 0, 2 ) === '//' )
        $url = 'http:' . $url;

    if ( 'relative' == $scheme ) {
        $url = ltrim( preg_replace( '#^\w+://[^/]*#', '', $url ) );
        if ( $url !== '' && $url[0] === '/' )
            $url = '/' . ltrim($url , "/ \t\n\r\0\x0B" );
    } else {
        $url = preg_replace( '#^\w+://#', $scheme . '://', $url );
    }

    /**
     * Filters the resulting URL after setting the scheme.
     *
     * @since 3.4.0
     *
     * @param string      $url         The complete URL including scheme and path.
     * @param string      $scheme      Scheme applied to the URL. One of 'http', 'https', or 'relative'.
     * @param string|null $orig_scheme Scheme requested for the URL. One of 'http', 'https', 'login',
     *                                 'login_post', 'admin', 'relative', 'rest', 'rpc', or null.
     */
    return apply_filters( 'set_url_scheme', $url, $scheme, $orig_scheme );
}



function wp_array_slice_assoc( $array, $keys ) {
    $slice = array();
    foreach ( $keys as $key )
        if ( isset( $array[ $key ] ) )
            $slice[ $key ] = $array[ $key ];

    return $slice;
}

function do_action($tag, $arg = '') {
    global $wp_filter, $wp_actions, $wp_current_filter;

    if ( ! isset($wp_actions[$tag]) )
        $wp_actions[$tag] = 1;
    else
        ++$wp_actions[$tag];

    // Do 'all' actions first
    if ( isset($wp_filter['all']) ) {
        $wp_current_filter[] = $tag;
        $all_args = func_get_args();
        _wp_call_all_hook($all_args);
    }

    if ( !isset($wp_filter[$tag]) ) {
        if ( isset($wp_filter['all']) )
            array_pop($wp_current_filter);
        return;
    }

    if ( !isset($wp_filter['all']) )
        $wp_current_filter[] = $tag;

    $args = array();
    if ( is_array($arg) && 1 == count($arg) && isset($arg[0]) && is_object($arg[0]) ) // array(&$this)
        $args[] =& $arg[0];
    else
        $args[] = $arg;
    for ( $a = 2, $num = func_num_args(); $a < $num; $a++ )
        $args[] = func_get_arg($a);

    $wp_filter[ $tag ]->do_action( $args );

    array_pop($wp_current_filter);
}


function wp_parse_url( $url, $component = -1 ) {
    $to_unset = array();
    $url = strval( $url );

    if ( '//' === substr( $url, 0, 2 ) ) {
        $to_unset[] = 'scheme';
        $url = 'placeholder:' . $url;
    } elseif ( '/' === substr( $url, 0, 1 ) ) {
        $to_unset[] = 'scheme';
        $to_unset[] = 'host';
        $url = 'placeholder://placeholder' . $url;
    }

    $parts = @parse_url( $url );

    if ( false === $parts ) {
        // Parsing failure.
        return $parts;
    }

    // Remove the placeholder values.
    foreach ( $to_unset as $key ) {
        unset( $parts[ $key ] );
    }

    return _get_component_from_parsed_url_array( $parts, $component );
}

function _get_component_from_parsed_url_array( $url_parts, $component = -1 ) {
    if ( -1 === $component ) {
        return $url_parts;
    }

    $key = _wp_translate_php_url_constant_to_key( $component );
    if ( false !== $key && is_array( $url_parts ) && isset( $url_parts[ $key ] ) ) {
        return $url_parts[ $key ];
    } else {
        return null;
    }
}

function _wp_translate_php_url_constant_to_key( $constant ) {
    $translation = array(
        PHP_URL_SCHEME   => 'scheme',
        PHP_URL_HOST     => 'host',
        PHP_URL_PORT     => 'port',
        PHP_URL_USER     => 'user',
        PHP_URL_PASS     => 'pass',
        PHP_URL_PATH     => 'path',
        PHP_URL_QUERY    => 'query',
        PHP_URL_FRAGMENT => 'fragment',
    );

    if ( isset( $translation[ $constant ] ) ) {
        return $translation[ $constant ];
    } else {
        return false;
    }
}

function is_wp_error( $thing ) {
    return ( $thing instanceof WP_Error );
}

function _wp_filter_build_unique_id($tag, $function, $priority) {
    global $wp_filter;
    static $filter_id_count = 0;

    if ( is_string($function) )
        return $function;

    if ( is_object($function) ) {
        // Closures are currently implemented as objects
        $function = array( $function, '' );
    } else {
        $function = (array) $function;
    }

    if (is_object($function[0]) ) {
        // Object Class Calling
        if ( function_exists('spl_object_hash') ) {
            return spl_object_hash($function[0]) . $function[1];
        } else {
            $obj_idx = get_class($function[0]).$function[1];
            if ( !isset($function[0]->wp_filter_id) ) {
                if ( false === $priority )
                    return false;
                $obj_idx .= isset($wp_filter[$tag][$priority]) ? count((array)$wp_filter[$tag][$priority]) : $filter_id_count;
                $function[0]->wp_filter_id = $filter_id_count;
                ++$filter_id_count;
            } else {
                $obj_idx .= $function[0]->wp_filter_id;
            }

            return $obj_idx;
        }
    } elseif ( is_string( $function[0] ) ) {
        // Static Calling
        return $function[0] . '::' . $function[1];
    }
}

function get_transient( $transient ) {

    /**
     * Filters the value of an existing transient.
     *
     * The dynamic portion of the hook name, `$transient`, refers to the transient name.
     *
     * Passing a truthy value to the filter will effectively short-circuit retrieval
     * of the transient, returning the passed value instead.
     *
     * @since 2.8.0
     * @since 4.4.0 The `$transient` parameter was added
     *
     * @param mixed  $pre_transient The default value to return if the transient does not exist.
     *                              Any value other than false will short-circuit the retrieval
     *                              of the transient, and return the returned value.
     * @param string $transient     Transient name.
     */
    $pre = apply_filters( "pre_transient_{$transient}", false, $transient );
    if ( false !== $pre )
        return $pre;

    if ( wp_using_ext_object_cache() ) {
        $value = wp_cache_get( $transient, 'transient' );
    } else {
        $transient_option = '_transient_' . $transient;
        if ( ! wp_installing() ) {
            // If option is not in alloptions, it is not autoloaded and thus has a timeout
            $alloptions = wp_load_alloptions();
            if ( !isset( $alloptions[$transient_option] ) ) {
                $transient_timeout = '_transient_timeout_' . $transient;
                $timeout = get_option( $transient_timeout );
                if ( false !== $timeout && $timeout < time() ) {
                    delete_option( $transient_option  );
                    delete_option( $transient_timeout );
                    $value = false;
                }
            }
        }

        if ( ! isset( $value ) )
            $value = get_option( $transient_option );
    }

    /**
     * Filters an existing transient's value.
     *
     * The dynamic portion of the hook name, `$transient`, refers to the transient name.
     *
     * @since 2.8.0
     * @since 4.4.0 The `$transient` parameter was added
     *
     * @param mixed  $value     Value of transient.
     * @param string $transient Transient name.
     */
    return apply_filters( "transient_{$transient}", $value, $transient );
}

function wp_using_ext_object_cache( $using = null ) {
    global $_wp_using_ext_object_cache;
    $current_using = $_wp_using_ext_object_cache;
    if ( null !== $using )
        $_wp_using_ext_object_cache = $using;
    return $current_using;
}

function wp_installing( $is_installing = null ) {
    static $installing = null;

    // Support for the `WP_INSTALLING` constant, defined before WP is loaded.
    if ( is_null( $installing ) ) {
        $installing = defined( 'WP_INSTALLING' ) && WP_INSTALLING;
    }

    if ( ! is_null( $is_installing ) ) {
        $old_installing = $installing;
        $installing = $is_installing;
        return (bool) $old_installing;
    }

    return (bool) $installing;
}

function wp_load_alloptions() {
    global $wpdb;

    if ( ! wp_installing() || ! is_multisite() ) {
        $alloptions = wp_cache_get( 'alloptions', 'options' );
    } else {
        $alloptions = false;
    }

    if ( ! $alloptions ) {
        $suppress = $wpdb->suppress_errors();
        if ( ! $alloptions_db = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE autoload = 'yes'" ) ) {
            $alloptions_db = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options" );
        }
        $wpdb->suppress_errors( $suppress );

        $alloptions = array();
        foreach ( (array) $alloptions_db as $o ) {
            $alloptions[$o->option_name] = $o->option_value;
        }

        if ( ! wp_installing() || ! is_multisite() ) {
            /**
             * Filters all options before caching them.
             *
             * @since 4.9.0
             *
             * @param array $alloptions Array with all options.
             */
            $alloptions = apply_filters( 'pre_cache_alloptions', $alloptions );
            wp_cache_add( 'alloptions', $alloptions, 'options' );
        }
    }

    /**
     * Filters all options after retrieving them.
     *
     * @since 4.9.0
     *
     * @param array $alloptions Array with all options.
     */
    return apply_filters( 'alloptions', $alloptions );
}

$wp_object_cache = NEW WP_Object_Cache();

function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
    global $wp_object_cache;

    return $wp_object_cache->get( $key, $group, $force, $found );
}

function is_multisite() {
    if ( defined( 'MULTISITE' ) )
        return MULTISITE;

    if ( defined( 'SUBDOMAIN_INSTALL' ) || defined( 'VHOST' ) || defined( 'SUNRISE' ) )
        return true;

    return false;
}

function wp_load_translations_early() {
    global $wp_locale;

    static $loaded = false;
    if ( $loaded )
        return;
    $loaded = true;

    if ( function_exists( 'did_action' ) && did_action( 'init' ) )
        return;

    // We need $wp_local_package
    require ABSPATH . WPINC . '/version.php';

    // Translation and localization
    require_once ABSPATH . WPINC . '/pomo/mo.php';
    require_once ABSPATH . WPINC . '/l10n.php';
    require_once ABSPATH . WPINC . '/class-wp-locale.php';
    require_once ABSPATH . WPINC . '/class-wp-locale-switcher.php';

    // General libraries
    require_once ABSPATH . WPINC . '/plugin.php';

    $locales = $locations = array();

    while ( true ) {
        if ( defined( 'WPLANG' ) ) {
            if ( '' == WPLANG )
                break;
            $locales[] = WPLANG;
        }

        if ( isset( $wp_local_package ) )
            $locales[] = $wp_local_package;

        if ( ! $locales )
            break;

        if ( defined( 'WP_LANG_DIR' ) && @is_dir( WP_LANG_DIR ) )
            $locations[] = WP_LANG_DIR;

        if ( defined( 'WP_CONTENT_DIR' ) && @is_dir( WP_CONTENT_DIR . '/languages' ) )
            $locations[] = WP_CONTENT_DIR . '/languages';

        if ( @is_dir( ABSPATH . 'wp-content/languages' ) )
            $locations[] = ABSPATH . 'wp-content/languages';

        if ( @is_dir( ABSPATH . WPINC . '/languages' ) )
            $locations[] = ABSPATH . WPINC . '/languages';

        if ( ! $locations )
            break;

        $locations = array_unique( $locations );

        foreach ( $locales as $locale ) {
            foreach ( $locations as $location ) {
                if ( file_exists( $location . '/' . $locale . '.mo' ) ) {
                    load_textdomain( 'default', $location . '/' . $locale . '.mo' );
                    if ( defined( 'WP_SETUP_CONFIG' ) && file_exists( $location . '/admin-' . $locale . '.mo' ) )
                        load_textdomain( 'default', $location . '/admin-' . $locale . '.mo' );
                    break 2;
                }
            }
        }

        break;
    }

    $wp_locale = new WP_Locale();
}

function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
    global $wp_object_cache;

    return $wp_object_cache->add( $key, $data, $group, (int) $expire );
}

function wp_suspend_cache_addition( $suspend = null ) {
    static $_suspend = false;

    if ( is_bool( $suspend ) )
        $_suspend = $suspend;

    return $_suspend;
}

function set_transient( $transient, $value, $expiration = 0 ) {

    $expiration = (int) $expiration;

    /**
     * Filters a specific transient before its value is set.
     *
     * The dynamic portion of the hook name, `$transient`, refers to the transient name.
     *
     * @since 3.0.0
     * @since 4.2.0 The `$expiration` parameter was added.
     * @since 4.4.0 The `$transient` parameter was added.
     *
     * @param mixed  $value      New value of transient.
     * @param int    $expiration Time until expiration in seconds.
     * @param string $transient  Transient name.
     */
    $value = apply_filters( "pre_set_transient_{$transient}", $value, $expiration, $transient );

    /**
     * Filters the expiration for a transient before its value is set.
     *
     * The dynamic portion of the hook name, `$transient`, refers to the transient name.
     *
     * @since 4.4.0
     *
     * @param int    $expiration Time until expiration in seconds. Use 0 for no expiration.
     * @param mixed  $value      New value of transient.
     * @param string $transient  Transient name.
     */
    $expiration = apply_filters( "expiration_of_transient_{$transient}", $expiration, $value, $transient );

    if ( wp_using_ext_object_cache() ) {
        $result = wp_cache_set( $transient, $value, 'transient', $expiration );
    } else {
        $transient_timeout = '_transient_timeout_' . $transient;
        $transient_option = '_transient_' . $transient;
        if ( false === get_option( $transient_option ) ) {
            $autoload = 'yes';
            if ( $expiration ) {
                $autoload = 'no';
                add_option( $transient_timeout, time() + $expiration, '', 'no' );
            }
            $result = add_option( $transient_option, $value, '', $autoload );
        } else {
            // If expiration is requested, but the transient has no timeout option,
            // delete, then re-create transient rather than update.
            $update = true;
            if ( $expiration ) {
                if ( false === get_option( $transient_timeout ) ) {
                    delete_option( $transient_option );
                    add_option( $transient_timeout, time() + $expiration, '', 'no' );
                    $result = add_option( $transient_option, $value, '', 'no' );
                    $update = false;
                } else {
                    update_option( $transient_timeout, time() + $expiration );
                }
            }
            if ( $update ) {
                $result = update_option( $transient_option, $value );
            }
        }
    }

    if ( $result ) {

        /**
         * Fires after the value for a specific transient has been set.
         *
         * The dynamic portion of the hook name, `$transient`, refers to the transient name.
         *
         * @since 3.0.0
         * @since 3.6.0 The `$value` and `$expiration` parameters were added.
         * @since 4.4.0 The `$transient` parameter was added.
         *
         * @param mixed  $value      Transient value.
         * @param int    $expiration Time until expiration in seconds.
         * @param string $transient  The name of the transient.
         */
        do_action( "set_transient_{$transient}", $value, $expiration, $transient );

        /**
         * Fires after the value for a transient has been set.
         *
         * @since 3.0.0
         * @since 3.6.0 The `$value` and `$expiration` parameters were added.
         *
         * @param string $transient  The name of the transient.
         * @param mixed  $value      Transient value.
         * @param int    $expiration Time until expiration in seconds.
         */
        do_action( 'setted_transient', $transient, $value, $expiration );
    }
    return $result;
}

function add_option( $option, $value = '', $deprecated = '', $autoload = 'yes' ) {
    global $wpdb;

    if ( !empty( $deprecated ) )
        _deprecated_argument( __FUNCTION__, '2.3.0' );

    $option = trim($option);
    if ( empty($option) )
        return false;

    wp_protect_special_option( $option );

    if ( is_object($value) )
        $value = clone $value;

    $value = sanitize_option( $option, $value );

    // Make sure the option doesn't already exist. We can check the 'notoptions' cache before we ask for a db query
    $notoptions = wp_cache_get( 'notoptions', 'options' );
    if ( !is_array( $notoptions ) || !isset( $notoptions[$option] ) )
        /** This filter is documented in wp-includes/option.php */
        if ( apply_filters( "default_option_{$option}", false, $option, false ) !== get_option( $option ) )
            return false;

    $serialized_value = maybe_serialize( $value );
    $autoload = ( 'no' === $autoload || false === $autoload ) ? 'no' : 'yes';

    /**
     * Fires before an option is added.
     *
     * @since 2.9.0
     *
     * @param string $option Name of the option to add.
     * @param mixed  $value  Value of the option.
     */
    do_action( 'add_option', $option, $value );

    $result = $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $serialized_value, $autoload ) );
    if ( ! $result )
        return false;

    if ( ! wp_installing() ) {
        if ( 'yes' == $autoload ) {
            $alloptions = wp_load_alloptions();
            $alloptions[ $option ] = $serialized_value;
            wp_cache_set( 'alloptions', $alloptions, 'options' );
        } else {
            wp_cache_set( $option, $serialized_value, 'options' );
        }
    }

    // This option exists now
    $notoptions = wp_cache_get( 'notoptions', 'options' ); // yes, again... we need it to be fresh
    if ( is_array( $notoptions ) && isset( $notoptions[$option] ) ) {
        unset( $notoptions[$option] );
        wp_cache_set( 'notoptions', $notoptions, 'options' );
    }

    /**
     * Fires after a specific option has been added.
     *
     * The dynamic portion of the hook name, `$option`, refers to the option name.
     *
     * @since 2.5.0 As "add_option_{$name}"
     * @since 3.0.0
     *
     * @param string $option Name of the option to add.
     * @param mixed  $value  Value of the option.
     */
    do_action( "add_option_{$option}", $option, $value );

    /**
     * Fires after an option has been added.
     *
     * @since 2.9.0
     *
     * @param string $option Name of the added option.
     * @param mixed  $value  Value of the option.
     */
    do_action( 'added_option', $option, $value );
    return true;
}

function wp_protect_special_option( $option ) {
    if ( 'alloptions' === $option || 'notoptions' === $option )
        wp_die( sprintf( __( '%s is a protected WP option and may not be modified' ), esc_html( $option ) ) );
}

function wp_die( $message = '', $title = '', $args = array() ) {

    if ( is_int( $args ) ) {
        $args = array( 'response' => $args );
    } elseif ( is_int( $title ) ) {
        $args  = array( 'response' => $title );
        $title = '';
    }

    if ( wp_doing_ajax() ) {
        /**
         * Filters the callback for killing WordPress execution for Ajax requests.
         *
         * @since 3.4.0
         *
         * @param callable $function Callback function name.
         */
        $function = apply_filters( 'wp_die_ajax_handler', '_ajax_wp_die_handler' );
    } elseif ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
        /**
         * Filters the callback for killing WordPress execution for XML-RPC requests.
         *
         * @since 3.4.0
         *
         * @param callable $function Callback function name.
         */
        $function = apply_filters( 'wp_die_xmlrpc_handler', '_xmlrpc_wp_die_handler' );
    } else {
        /**
         * Filters the callback for killing WordPress execution for all non-Ajax, non-XML-RPC requests.
         *
         * @since 3.0.0
         *
         * @param callable $function Callback function name.
         */
        $function = apply_filters( 'wp_die_handler', '_default_wp_die_handler' );
    }

    call_user_func( $function, $message, $title, $args );
}

function sanitize_option( $option, $value ) {
    global $wpdb;

    $original_value = $value;
    $error = '';

    switch ( $option ) {
        case 'admin_email' :
        case 'new_admin_email' :
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                $value = sanitize_email( $value );
                if ( ! is_email( $value ) ) {
                    $error = __( 'The email address entered did not appear to be a valid email address. Please enter a valid email address.' );
                }
            }
            break;

        case 'thumbnail_size_w':
        case 'thumbnail_size_h':
        case 'medium_size_w':
        case 'medium_size_h':
        case 'medium_large_size_w':
        case 'medium_large_size_h':
        case 'large_size_w':
        case 'large_size_h':
        case 'mailserver_port':
        case 'comment_max_links':
        case 'page_on_front':
        case 'page_for_posts':
        case 'rss_excerpt_length':
        case 'default_category':
        case 'default_email_category':
        case 'default_link_category':
        case 'close_comments_days_old':
        case 'comments_per_page':
        case 'thread_comments_depth':
        case 'users_can_register':
        case 'start_of_week':
        case 'site_icon':
            $value = absint( $value );
            break;

        case 'posts_per_page':
        case 'posts_per_rss':
            $value = (int) $value;
            if ( empty($value) )
                $value = 1;
            if ( $value < -1 )
                $value = abs($value);
            break;

        case 'default_ping_status':
        case 'default_comment_status':
            // Options that if not there have 0 value but need to be something like "closed"
            if ( $value == '0' || $value == '')
                $value = 'closed';
            break;

        case 'blogdescription':
        case 'blogname':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( $value !== $original_value ) {
                $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', wp_encode_emoji( $original_value ) );
            }

            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                $value = esc_html( $value );
            }
            break;

        case 'blog_charset':
            $value = preg_replace('/[^a-zA-Z0-9_-]/', '', $value); // strips slashes
            break;

        case 'blog_public':
            // This is the value if the settings checkbox is not checked on POST. Don't rely on this.
            if ( null === $value )
                $value = 1;
            else
                $value = intval( $value );
            break;

        case 'date_format':
        case 'time_format':
        case 'mailserver_url':
        case 'mailserver_login':
        case 'mailserver_pass':
        case 'upload_path':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                $value = strip_tags( $value );
                $value = wp_kses_data( $value );
            }
            break;

        case 'ping_sites':
            $value = explode( "\n", $value );
            $value = array_filter( array_map( 'trim', $value ) );
            $value = array_filter( array_map( 'esc_url_raw', $value ) );
            $value = implode( "\n", $value );
            break;

        case 'gmt_offset':
            $value = preg_replace('/[^0-9:.-]/', '', $value); // strips slashes
            break;

        case 'siteurl':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                if ( preg_match( '#http(s?)://(.+)#i', $value ) ) {
                    $value = esc_url_raw( $value );
                } else {
                    $error = __( 'The WordPress address you entered did not appear to be a valid URL. Please enter a valid URL.' );
                }
            }
            break;

        case 'home':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                if ( preg_match( '#http(s?)://(.+)#i', $value ) ) {
                    $value = esc_url_raw( $value );
                } else {
                    $error = __( 'The Site address you entered did not appear to be a valid URL. Please enter a valid URL.' );
                }
            }
            break;

        case 'WPLANG':
            $allowed = get_available_languages();
            if ( ! is_multisite() && defined( 'WPLANG' ) && '' !== WPLANG && 'en_US' !== WPLANG ) {
                $allowed[] = WPLANG;
            }
            if ( ! in_array( $value, $allowed ) && ! empty( $value ) ) {
                $value = get_option( $option );
            }
            break;

        case 'illegal_names':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                if ( ! is_array( $value ) )
                    $value = explode( ' ', $value );

                $value = array_values( array_filter( array_map( 'trim', $value ) ) );

                if ( ! $value )
                    $value = '';
            }
            break;

        case 'limited_email_domains':
        case 'banned_email_domains':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                if ( ! is_array( $value ) )
                    $value = explode( "\n", $value );

                $domains = array_values( array_filter( array_map( 'trim', $value ) ) );
                $value = array();

                foreach ( $domains as $domain ) {
                    if ( ! preg_match( '/(--|\.\.)/', $domain ) && preg_match( '|^([a-zA-Z0-9-\.])+$|', $domain ) ) {
                        $value[] = $domain;
                    }
                }
                if ( ! $value )
                    $value = '';
            }
            break;

        case 'timezone_string':
            $allowed_zones = timezone_identifiers_list();
            if ( ! in_array( $value, $allowed_zones ) && ! empty( $value ) ) {
                $error = __( 'The timezone you have entered is not valid. Please select a valid timezone.' );
            }
            break;

        case 'permalink_structure':
        case 'category_base':
        case 'tag_base':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                $value = esc_url_raw( $value );
                $value = str_replace( 'http://', '', $value );
            }

            if ( 'permalink_structure' === $option && '' !== $value && ! preg_match( '/%[^\/%]+%/', $value ) ) {
                $error = sprintf(
                    /* translators: %s: Codex URL */
                    __( 'A structure tag is required when using custom permalinks. <a href="%s">Learn more</a>' ),
                    __( 'https://codex.wordpress.org/Using_Permalinks#Choosing_your_permalink_structure' )
                );
            }
            break;

        case 'default_role' :
            if ( ! get_role( $value ) && get_role( 'subscriber' ) )
                $value = 'subscriber';
            break;

        case 'moderation_keys':
        case 'blacklist_keys':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                $value = explode( "\n", $value );
                $value = array_filter( array_map( 'trim', $value ) );
                $value = array_unique( $value );
                $value = implode( "\n", $value );
            }
            break;
    }

    if ( ! empty( $error ) ) {
        $value = get_option( $option );
        if ( function_exists( 'add_settings_error' ) ) {
            add_settings_error( $option, "invalid_{$option}", $error );
        }
    }

    /**
     * Filters an option value following sanitization.
     *
     * @since 2.3.0
     * @since 4.3.0 Added the `$original_value` parameter.
     *
     * @param string $value          The sanitized option value.
     * @param string $option         The option name.
     * @param string $original_value The original value passed to the function.
     */
    return apply_filters( "sanitize_option_{$option}", $value, $option, $original_value );
}

function maybe_serialize( $data ) {
    if ( is_array( $data ) || is_object( $data ) )
        return serialize( $data );

    // Double serialization is required for backward compatibility.
    // See https://core.trac.wordpress.org/ticket/12930
    // Also the world will end. See WP 3.6.1.
    if ( is_serialized( $data, false ) )
        return serialize( $data );

    return $data;
}

function is_serialized( $data, $strict = true ) {
    // if it isn't a string, it isn't serialized.
    if ( ! is_string( $data ) ) {
        return false;
    }
    $data = trim( $data );
    if ( 'N;' == $data ) {
        return true;
    }
    if ( strlen( $data ) < 4 ) {
        return false;
    }
    if ( ':' !== $data[1] ) {
        return false;
    }
    if ( $strict ) {
        $lastc = substr( $data, -1 );
        if ( ';' !== $lastc && '}' !== $lastc ) {
            return false;
        }
    } else {
        $semicolon = strpos( $data, ';' );
        $brace     = strpos( $data, '}' );
        // Either ; or } must exist.
        if ( false === $semicolon && false === $brace )
            return false;
        // But neither must be in the first X characters.
        if ( false !== $semicolon && $semicolon < 3 )
            return false;
        if ( false !== $brace && $brace < 4 )
            return false;
    }
    $token = $data[0];
    switch ( $token ) {
        case 's' :
            if ( $strict ) {
                if ( '"' !== substr( $data, -2, 1 ) ) {
                    return false;
                }
            } elseif ( false === strpos( $data, '"' ) ) {
                return false;
            }
            // or else fall through
        case 'a' :
        case 'O' :
            return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
        case 'b' :
        case 'i' :
        case 'd' :
            $end = $strict ? '$' : '';
            return (bool) preg_match( "/^{$token}:[0-9.E-]+;$end/", $data );
    }
    return false;
}

function has_filter($tag, $function_to_check = false) {
    global $wp_filter;

    if ( ! isset( $wp_filter[ $tag ] ) ) {
        return false;
    }

    return $wp_filter[ $tag ]->has_filter( $tag, $function_to_check );
}

function delete_transient( $transient ) {

    /**
     * Fires immediately before a specific transient is deleted.
     *
     * The dynamic portion of the hook name, `$transient`, refers to the transient name.
     *
     * @since 3.0.0
     *
     * @param string $transient Transient name.
     */
    do_action( "delete_transient_{$transient}", $transient );

    if ( wp_using_ext_object_cache() ) {
        $result = wp_cache_delete( $transient, 'transient' );
    } else {
        $option_timeout = '_transient_timeout_' . $transient;
        $option = '_transient_' . $transient;
        $result = delete_option( $option );
        if ( $result )
            delete_option( $option_timeout );
    }

    if ( $result ) {

        /**
         * Fires after a transient is deleted.
         *
         * @since 3.0.0
         *
         * @param string $transient Deleted transient name.
         */
        do_action( 'deleted_transient', $transient );
    }

    return $result;
}

function delete_option( $option ) {
    global $wpdb;

    $option = trim( $option );
    if ( empty( $option ) )
        return false;

    wp_protect_special_option( $option );

    // Get the ID, if no ID then return
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s", $option ) );
    if ( is_null( $row ) )
        return false;

    /**
     * Fires immediately before an option is deleted.
     *
     * @since 2.9.0
     *
     * @param string $option Name of the option to delete.
     */
    do_action( 'delete_option', $option );

    $result = $wpdb->delete( $wpdb->options, array( 'option_name' => $option ) );
    if ( ! wp_installing() ) {
        if ( 'yes' == $row->autoload ) {
            $alloptions = wp_load_alloptions();
            if ( is_array( $alloptions ) && isset( $alloptions[$option] ) ) {
                unset( $alloptions[$option] );
                wp_cache_set( 'alloptions', $alloptions, 'options' );
            }
        } else {
            wp_cache_delete( $option, 'options' );
        }
    }
    if ( $result ) {

        /**
         * Fires after a specific option has been deleted.
         *
         * The dynamic portion of the hook name, `$option`, refers to the option name.
         *
         * @since 3.0.0
         *
         * @param string $option Name of the deleted option.
         */
        do_action( "delete_option_{$option}", $option );

        /**
         * Fires after an option has been deleted.
         *
         * @since 2.9.0
         *
         * @param string $option Name of the deleted option.
         */
        do_action( 'deleted_option', $option );
        return true;
    }
    return false;
}

$wpdb = NEW wpdb("","","","");

class wpdb {

    /**
     * Whether to show SQL/DB errors.
     *
     * Default behavior is to show errors if both WP_DEBUG and WP_DEBUG_DISPLAY
     * evaluated to true.
     *
     * @since 0.71
     * @var bool
     */
    var $show_errors = false;

    /**
     * Whether to suppress errors during the DB bootstrapping.
     *
     * @since 2.5.0
     * @var bool
     */
    var $suppress_errors = false;

    /**
     * The last error during query.
     *
     * @since 2.5.0
     * @var string
     */
    public $last_error = '';

    /**
     * Amount of queries made
     *
     * @since 1.2.0
     * @var int
     */
    public $num_queries = 0;

    /**
     * Count of rows returned by previous query
     *
     * @since 0.71
     * @var int
     */
    public $num_rows = 0;

    /**
     * Count of affected rows by previous query
     *
     * @since 0.71
     * @var int
     */
    var $rows_affected = 0;

    /**
     * The ID generated for an AUTO_INCREMENT column by the previous query (usually INSERT).
     *
     * @since 0.71
     * @var int
     */
    public $insert_id = 0;

    /**
     * Last query made
     *
     * @since 0.71
     * @var array
     */
    var $last_query;

    /**
     * Results of the last query made
     *
     * @since 0.71
     * @var array|null
     */
    var $last_result;

    /**
     * MySQL result, which is either a resource or boolean.
     *
     * @since 0.71
     * @var mixed
     */
    protected $result;

    /**
     * Cached column info, for sanity checking data before inserting
     *
     * @since 4.2.0
     * @var array
     */
    protected $col_meta = array();

    /**
     * Calculated character sets on tables
     *
     * @since 4.2.0
     * @var array
     */
    protected $table_charset = array();

    /**
     * Whether text fields in the current query need to be sanity checked.
     *
     * @since 4.2.0
     * @var bool
     */
    protected $check_current_query = true;

    /**
     * Flag to ensure we don't run into recursion problems when checking the collation.
     *
     * @since 4.2.0
     * @see wpdb::check_safe_collation()
     * @var bool
     */
    private $checking_collation = false;

    /**
     * Saved info on the table column
     *
     * @since 0.71
     * @var array
     */
    protected $col_info;

    /**
     * Saved queries that were executed
     *
     * @since 1.5.0
     * @var array
     */
    var $queries;

    /**
     * The number of times to retry reconnecting before dying.
     *
     * @since 3.9.0
     * @see wpdb::check_connection()
     * @var int
     */
    protected $reconnect_retries = 5;

    /**
     * WordPress table prefix
     *
     * You can set this to have multiple WordPress installations
     * in a single database. The second reason is for possible
     * security precautions.
     *
     * @since 2.5.0
     * @var string
     */
    public $prefix = '';

    /**
     * WordPress base table prefix.
     *
     * @since 3.0.0
     * @var string
     */
     public $base_prefix;

    /**
     * Whether the database queries are ready to start executing.
     *
     * @since 2.3.2
     * @var bool
     */
    var $ready = false;

    /**
     * Blog ID.
     *
     * @since 3.0.0
     * @var int
     */
    public $blogid = 0;

    /**
     * Site ID.
     *
     * @since 3.0.0
     * @var int
     */
    public $siteid = 0;

    /**
     * List of WordPress per-blog tables
     *
     * @since 2.5.0
     * @see wpdb::tables()
     * @var array
     */
    var $tables = array( 'posts', 'comments', 'links', 'options', 'postmeta',
        'terms', 'term_taxonomy', 'term_relationships', 'termmeta', 'commentmeta' );

    /**
     * List of deprecated WordPress tables
     *
     * categories, post2cat, and link2cat were deprecated in 2.3.0, db version 5539
     *
     * @since 2.9.0
     * @see wpdb::tables()
     * @var array
     */
    var $old_tables = array( 'categories', 'post2cat', 'link2cat' );

    /**
     * List of WordPress global tables
     *
     * @since 3.0.0
     * @see wpdb::tables()
     * @var array
     */
    var $global_tables = array( 'users', 'usermeta' );

    /**
     * List of Multisite global tables
     *
     * @since 3.0.0
     * @see wpdb::tables()
     * @var array
     */
    var $ms_global_tables = array( 'blogs', 'signups', 'site', 'sitemeta',
        'sitecategories', 'registration_log', 'blog_versions' );

    /**
     * WordPress Comments table
     *
     * @since 1.5.0
     * @var string
     */
    public $comments;

    /**
     * WordPress Comment Metadata table
     *
     * @since 2.9.0
     * @var string
     */
    public $commentmeta;

    /**
     * WordPress Links table
     *
     * @since 1.5.0
     * @var string
     */
    public $links;

    /**
     * WordPress Options table
     *
     * @since 1.5.0
     * @var string
     */
    public $options;

    /**
     * WordPress Post Metadata table
     *
     * @since 1.5.0
     * @var string
     */
    public $postmeta;

    /**
     * WordPress Posts table
     *
     * @since 1.5.0
     * @var string
     */
    public $posts;

    /**
     * WordPress Terms table
     *
     * @since 2.3.0
     * @var string
     */
    public $terms;

    /**
     * WordPress Term Relationships table
     *
     * @since 2.3.0
     * @var string
     */
    public $term_relationships;

    /**
     * WordPress Term Taxonomy table
     *
     * @since 2.3.0
     * @var string
     */
    public $term_taxonomy;

    /**
     * WordPress Term Meta table.
     *
     * @since 4.4.0
     * @var string
     */
    public $termmeta;

    //
    // Global and Multisite tables
    //

    /**
     * WordPress User Metadata table
     *
     * @since 2.3.0
     * @var string
     */
    public $usermeta;

    /**
     * WordPress Users table
     *
     * @since 1.5.0
     * @var string
     */
    public $users;

    /**
     * Multisite Blogs table
     *
     * @since 3.0.0
     * @var string
     */
    public $blogs;

    /**
     * Multisite Blog Versions table
     *
     * @since 3.0.0
     * @var string
     */
    public $blog_versions;

    /**
     * Multisite Registration Log table
     *
     * @since 3.0.0
     * @var string
     */
    public $registration_log;

    /**
     * Multisite Signups table
     *
     * @since 3.0.0
     * @var string
     */
    public $signups;

    /**
     * Multisite Sites table
     *
     * @since 3.0.0
     * @var string
     */
    public $site;

    /**
     * Multisite Sitewide Terms table
     *
     * @since 3.0.0
     * @var string
     */
    public $sitecategories;

    /**
     * Multisite Site Metadata table
     *
     * @since 3.0.0
     * @var string
     */
    public $sitemeta;

    /**
     * Format specifiers for DB columns. Columns not listed here default to %s. Initialized during WP load.
     *
     * Keys are column names, values are format types: 'ID' => '%d'
     *
     * @since 2.8.0
     * @see wpdb::prepare()
     * @see wpdb::insert()
     * @see wpdb::update()
     * @see wpdb::delete()
     * @see wp_set_wpdb_vars()
     * @var array
     */
    public $field_types = array();

    /**
     * Database table columns charset
     *
     * @since 2.2.0
     * @var string
     */
    public $charset;

    /**
     * Database table columns collate
     *
     * @since 2.2.0
     * @var string
     */
    public $collate;

    /**
     * Database Username
     *
     * @since 2.9.0
     * @var string
     */
    protected $dbuser;

    /**
     * Database Password
     *
     * @since 3.1.0
     * @var string
     */
    protected $dbpassword;

    /**
     * Database Name
     *
     * @since 3.1.0
     * @var string
     */
    protected $dbname;

    /**
     * Database Host
     *
     * @since 3.1.0
     * @var string
     */
    protected $dbhost;

    /**
     * Database Handle
     *
     * @since 0.71
     * @var string
     */
    protected $dbh;

    /**
     * A textual description of the last query/get_row/get_var call
     *
     * @since 3.0.0
     * @var string
     */
    public $func_call;

    /**
     * Whether MySQL is used as the database engine.
     *
     * Set in WPDB::db_connect() to true, by default. This is used when checking
     * against the required MySQL version for WordPress. Normally, a replacement
     * database drop-in (db.php) will skip these checks, but setting this to true
     * will force the checks to occur.
     *
     * @since 3.3.0
     * @var bool
     */
    public $is_mysql = null;

    /**
     * A list of incompatible SQL modes.
     *
     * @since 3.9.0
     * @var array
     */
    protected $incompatible_modes = array( 'NO_ZERO_DATE', 'ONLY_FULL_GROUP_BY',
        'STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES', 'TRADITIONAL' );

    /**
     * Whether to use mysqli over mysql.
     *
     * @since 3.9.0
     * @var bool
     */
    private $use_mysqli = false;

    /**
     * Whether we've managed to successfully connect at some point
     *
     * @since 3.9.0
     * @var bool
     */
    private $has_connected = false;

    /**
     * Connects to the database server and selects a database
     *
     * PHP5 style constructor for compatibility with PHP5. Does
     * the actual setting up of the class properties and connection
     * to the database.
     *
     * @link https://core.trac.wordpress.org/ticket/3354
     * @since 2.0.8
     *
     * @global string $wp_version
     *
     * @param string $dbuser     MySQL database user
     * @param string $dbpassword MySQL database password
     * @param string $dbname     MySQL database name
     * @param string $dbhost     MySQL database host
     */
    public function __construct( $dbuser, $dbpassword, $dbname, $dbhost ) {
        return;
        register_shutdown_function( array( $this, '__destruct' ) );

        if ( WP_DEBUG && WP_DEBUG_DISPLAY )
            $this->show_errors();

        /* Use ext/mysqli if it exists and:
         *  - WP_USE_EXT_MYSQL is defined as false, or
         *  - We are a development version of WordPress, or
         *  - We are running PHP 5.5 or greater, or
         *  - ext/mysql is not loaded.
         */
        if ( function_exists( 'mysqli_connect' ) ) {
            if ( defined( 'WP_USE_EXT_MYSQL' ) ) {
                $this->use_mysqli = ! WP_USE_EXT_MYSQL;
            } elseif ( version_compare( phpversion(), '5.5', '>=' ) || ! function_exists( 'mysql_connect' ) ) {
                $this->use_mysqli = true;
            } elseif ( false !== strpos( $GLOBALS['wp_version'], '-' ) ) {
                $this->use_mysqli = true;
            }
        }

        $this->dbuser = $dbuser;
        $this->dbpassword = $dbpassword;
        $this->dbname = $dbname;
        $this->dbhost = $dbhost;

        // wp-config.php creation will manually connect when ready.
        if ( defined( 'WP_SETUP_CONFIG' ) ) {
            return;
        }

        $this->db_connect();
    }

    /**
     * PHP5 style destructor and will run when database object is destroyed.
     *
     * @see wpdb::__construct()
     * @since 2.0.8
     * @return true
     */
    public function __destruct() {
        return true;
    }

    /**
     * Makes private properties readable for backward compatibility.
     *
     * @since 3.5.0
     *
     * @param string $name The private member to get, and optionally process
     * @return mixed The private member
     */
    public function __get( $name ) {
        if ( 'col_info' === $name )
            $this->load_col_info();

        return $this->$name;
    }

    /**
     * Makes private properties settable for backward compatibility.
     *
     * @since 3.5.0
     *
     * @param string $name  The private member to set
     * @param mixed  $value The value to set
     */
    public function __set( $name, $value ) {
        $protected_members = array(
            'col_meta',
            'table_charset',
            'check_current_query',
        );
        if (  in_array( $name, $protected_members, true ) ) {
            return;
        }
        $this->$name = $value;
    }

    /**
     * Makes private properties check-able for backward compatibility.
     *
     * @since 3.5.0
     *
     * @param string $name  The private member to check
     *
     * @return bool If the member is set or not
     */
    public function __isset( $name ) {
        return isset( $this->$name );
    }

    /**
     * Makes private properties un-settable for backward compatibility.
     *
     * @since 3.5.0
     *
     * @param string $name  The private member to unset
     */
    public function __unset( $name ) {
        unset( $this->$name );
    }

    /**
     * Set $this->charset and $this->collate
     *
     * @since 3.1.0
     */
    public function init_charset() {
        $charset = '';
        $collate = '';

        if ( function_exists('is_multisite') && is_multisite() ) {
            $charset = 'utf8';
            if ( defined( 'DB_COLLATE' ) && DB_COLLATE ) {
                $collate = DB_COLLATE;
            } else {
                $collate = 'utf8_general_ci';
            }
        } elseif ( defined( 'DB_COLLATE' ) ) {
            $collate = DB_COLLATE;
        }

        if ( defined( 'DB_CHARSET' ) ) {
            $charset = DB_CHARSET;
        }

        $charset_collate = $this->determine_charset( $charset, $collate );

        $this->charset = $charset_collate['charset'];
        $this->collate = $charset_collate['collate'];
    }

    /**
     * Determines the best charset and collation to use given a charset and collation.
     *
     * For example, when able, utf8mb4 should be used instead of utf8.
     *
     * @since 4.6.0
     *
     * @param string $charset The character set to check.
     * @param string $collate The collation to check.
     * @return array The most appropriate character set and collation to use.
     */
    public function determine_charset( $charset, $collate ) {
        if ( ( $this->use_mysqli && ! ( $this->dbh instanceof mysqli ) ) || empty( $this->dbh ) ) {
            return compact( 'charset', 'collate' );
        }

        if ( 'utf8' === $charset && $this->has_cap( 'utf8mb4' ) ) {
            $charset = 'utf8mb4';
        }

        if ( 'utf8mb4' === $charset && ! $this->has_cap( 'utf8mb4' ) ) {
            $charset = 'utf8';
            $collate = str_replace( 'utf8mb4_', 'utf8_', $collate );
        }

        if ( 'utf8mb4' === $charset ) {
            // _general_ is outdated, so we can upgrade it to _unicode_, instead.
            if ( ! $collate || 'utf8_general_ci' === $collate ) {
                $collate = 'utf8mb4_unicode_ci';
            } else {
                $collate = str_replace( 'utf8_', 'utf8mb4_', $collate );
            }
        }

        // _unicode_520_ is a better collation, we should use that when it's available.
        if ( $this->has_cap( 'utf8mb4_520' ) && 'utf8mb4_unicode_ci' === $collate ) {
            $collate = 'utf8mb4_unicode_520_ci';
        }

        return compact( 'charset', 'collate' );
    }

    /**
     * Sets the connection's character set.
     *
     * @since 3.1.0
     *
     * @param resource $dbh     The resource given by mysql_connect
     * @param string   $charset Optional. The character set. Default null.
     * @param string   $collate Optional. The collation. Default null.
     */
    public function set_charset( $dbh, $charset = null, $collate = null ) {
        if ( ! isset( $charset ) )
            $charset = $this->charset;
        if ( ! isset( $collate ) )
            $collate = $this->collate;
        if ( $this->has_cap( 'collation' ) && ! empty( $charset ) ) {
            $set_charset_succeeded = true;

            if ( $this->use_mysqli ) {
                if ( function_exists( 'mysqli_set_charset' ) && $this->has_cap( 'set_charset' ) ) {
                    $set_charset_succeeded = mysqli_set_charset( $dbh, $charset );
                }

                if ( $set_charset_succeeded ) {
                    $query = $this->prepare( 'SET NAMES %s', $charset );
                    if ( ! empty( $collate ) )
                        $query .= $this->prepare( ' COLLATE %s', $collate );
                    mysqli_query( $dbh, $query );
                }
            } else {
                if ( function_exists( 'mysql_set_charset' ) && $this->has_cap( 'set_charset' ) ) {
                    $set_charset_succeeded = mysql_set_charset( $charset, $dbh );
                }
                if ( $set_charset_succeeded ) {
                    $query = $this->prepare( 'SET NAMES %s', $charset );
                    if ( ! empty( $collate ) )
                        $query .= $this->prepare( ' COLLATE %s', $collate );
                    mysql_query( $query, $dbh );
                }
            }
        }
    }

    /**
     * Change the current SQL mode, and ensure its WordPress compatibility.
     *
     * If no modes are passed, it will ensure the current MySQL server
     * modes are compatible.
     *
     * @since 3.9.0
     *
     * @param array $modes Optional. A list of SQL modes to set.
     */
    public function set_sql_mode( $modes = array() ) {
        if ( empty( $modes ) ) {
            if ( $this->use_mysqli ) {
                $res = mysqli_query( $this->dbh, 'SELECT @@SESSION.sql_mode' );
            } else {
                $res = mysql_query( 'SELECT @@SESSION.sql_mode', $this->dbh );
            }

            if ( empty( $res ) ) {
                return;
            }

            if ( $this->use_mysqli ) {
                $modes_array = mysqli_fetch_array( $res );
                if ( empty( $modes_array[0] ) ) {
                    return;
                }
                $modes_str = $modes_array[0];
            } else {
                $modes_str = mysql_result( $res, 0 );
            }

            if ( empty( $modes_str ) ) {
                return;
            }

            $modes = explode( ',', $modes_str );
        }

        $modes = array_change_key_case( $modes, CASE_UPPER );

        /**
         * Filters the list of incompatible SQL modes to exclude.
         *
         * @since 3.9.0
         *
         * @param array $incompatible_modes An array of incompatible modes.
         */
        $incompatible_modes = (array) apply_filters( 'incompatible_sql_modes', $this->incompatible_modes );

        foreach ( $modes as $i => $mode ) {
            if ( in_array( $mode, $incompatible_modes ) ) {
                unset( $modes[ $i ] );
            }
        }

        $modes_str = implode( ',', $modes );

        if ( $this->use_mysqli ) {
            mysqli_query( $this->dbh, "SET SESSION sql_mode='$modes_str'" );
        } else {
            mysql_query( "SET SESSION sql_mode='$modes_str'", $this->dbh );
        }
    }

    /**
     * Sets the table prefix for the WordPress tables.
     *
     * @since 2.5.0
     *
     * @param string $prefix          Alphanumeric name for the new prefix.
     * @param bool   $set_table_names Optional. Whether the table names, e.g. wpdb::$posts, should be updated or not.
     * @return string|WP_Error Old prefix or WP_Error on error
     */
    public function set_prefix( $prefix, $set_table_names = true ) {

        if ( preg_match( '|[^a-z0-9_]|i', $prefix ) )
            return new WP_Error('invalid_db_prefix', 'Invalid database prefix' );

        $old_prefix = is_multisite() ? '' : $prefix;

        if ( isset( $this->base_prefix ) )
            $old_prefix = $this->base_prefix;

        $this->base_prefix = $prefix;

        if ( $set_table_names ) {
            foreach ( $this->tables( 'global' ) as $table => $prefixed_table )
                $this->$table = $prefixed_table;

            if ( is_multisite() && empty( $this->blogid ) )
                return $old_prefix;

            $this->prefix = $this->get_blog_prefix();

            foreach ( $this->tables( 'blog' ) as $table => $prefixed_table )
                $this->$table = $prefixed_table;

            foreach ( $this->tables( 'old' ) as $table => $prefixed_table )
                $this->$table = $prefixed_table;
        }
        return $old_prefix;
    }

    /**
     * Sets blog id.
     *
     * @since 3.0.0
     *
     * @param int $blog_id
     * @param int $network_id Optional.
     * @return int previous blog id
     */
    public function set_blog_id( $blog_id, $network_id = 0 ) {
        if ( ! empty( $network_id ) ) {
            $this->siteid = $network_id;
        }

        $old_blog_id  = $this->blogid;
        $this->blogid = $blog_id;

        $this->prefix = $this->get_blog_prefix();

        foreach ( $this->tables( 'blog' ) as $table => $prefixed_table )
            $this->$table = $prefixed_table;

        foreach ( $this->tables( 'old' ) as $table => $prefixed_table )
            $this->$table = $prefixed_table;

        return $old_blog_id;
    }

    /**
     * Gets blog prefix.
     *
     * @since 3.0.0
     * @param int $blog_id Optional.
     * @return string Blog prefix.
     */
    public function get_blog_prefix( $blog_id = null ) {
        if ( is_multisite() ) {
            if ( null === $blog_id )
                $blog_id = $this->blogid;
            $blog_id = (int) $blog_id;
            if ( defined( 'MULTISITE' ) && ( 0 == $blog_id || 1 == $blog_id ) )
                return $this->base_prefix;
            else
                return $this->base_prefix . $blog_id . '_';
        } else {
            return $this->base_prefix;
        }
    }

    /**
     * Returns an array of WordPress tables.
     *
     * Also allows for the CUSTOM_USER_TABLE and CUSTOM_USER_META_TABLE to
     * override the WordPress users and usermeta tables that would otherwise
     * be determined by the prefix.
     *
     * The scope argument can take one of the following:
     *
     * 'all' - returns 'all' and 'global' tables. No old tables are returned.
     * 'blog' - returns the blog-level tables for the queried blog.
     * 'global' - returns the global tables for the installation, returning multisite tables only if running multisite.
     * 'ms_global' - returns the multisite global tables, regardless if current installation is multisite.
     * 'old' - returns tables which are deprecated.
     *
     * @since 3.0.0
     * @uses wpdb::$tables
     * @uses wpdb::$old_tables
     * @uses wpdb::$global_tables
     * @uses wpdb::$ms_global_tables
     *
     * @param string $scope   Optional. Can be all, global, ms_global, blog, or old tables. Defaults to all.
     * @param bool   $prefix  Optional. Whether to include table prefixes. Default true. If blog
     *                        prefix is requested, then the custom users and usermeta tables will be mapped.
     * @param int    $blog_id Optional. The blog_id to prefix. Defaults to wpdb::$blogid. Used only when prefix is requested.
     * @return array Table names. When a prefix is requested, the key is the unprefixed table name.
     */
    public function tables( $scope = 'all', $prefix = true, $blog_id = 0 ) {
        switch ( $scope ) {
            case 'all' :
                $tables = array_merge( $this->global_tables, $this->tables );
                if ( is_multisite() )
                    $tables = array_merge( $tables, $this->ms_global_tables );
                break;
            case 'blog' :
                $tables = $this->tables;
                break;
            case 'global' :
                $tables = $this->global_tables;
                if ( is_multisite() )
                    $tables = array_merge( $tables, $this->ms_global_tables );
                break;
            case 'ms_global' :
                $tables = $this->ms_global_tables;
                break;
            case 'old' :
                $tables = $this->old_tables;
                break;
            default :
                return array();
        }

        if ( $prefix ) {
            if ( ! $blog_id )
                $blog_id = $this->blogid;
            $blog_prefix = $this->get_blog_prefix( $blog_id );
            $base_prefix = $this->base_prefix;
            $global_tables = array_merge( $this->global_tables, $this->ms_global_tables );
            foreach ( $tables as $k => $table ) {
                if ( in_array( $table, $global_tables ) )
                    $tables[ $table ] = $base_prefix . $table;
                else
                    $tables[ $table ] = $blog_prefix . $table;
                unset( $tables[ $k ] );
            }

            if ( isset( $tables['users'] ) && defined( 'CUSTOM_USER_TABLE' ) )
                $tables['users'] = CUSTOM_USER_TABLE;

            if ( isset( $tables['usermeta'] ) && defined( 'CUSTOM_USER_META_TABLE' ) )
                $tables['usermeta'] = CUSTOM_USER_META_TABLE;
        }

        return $tables;
    }

    /**
     * Selects a database using the current database connection.
     *
     * The database name will be changed based on the current database
     * connection. On failure, the execution will bail and display an DB error.
     *
     * @since 0.71
     *
     * @param string        $db  MySQL database name
     * @param resource|null $dbh Optional link identifier.
     */
    public function select( $db, $dbh = null ) {
        if ( is_null($dbh) )
            $dbh = $this->dbh;

        if ( $this->use_mysqli ) {
            $success = mysqli_select_db( $dbh, $db );
        } else {
            $success = mysql_select_db( $db, $dbh );
        }
        if ( ! $success ) {
            $this->ready = false;
            if ( ! did_action( 'template_redirect' ) ) {
                wp_load_translations_early();

                $message = '<h1>' . __( 'Can&#8217;t select database' ) . "</h1>\n";

                $message .= '<p>' . sprintf(
                    /* translators: %s: database name */
                    __( 'We were able to connect to the database server (which means your username and password is okay) but not able to select the %s database.' ),
                    '<code>' . htmlspecialchars( $db, ENT_QUOTES ) . '</code>'
                ) . "</p>\n";

                $message .= "<ul>\n";
                $message .= '<li>' . __( 'Are you sure it exists?' ) . "</li>\n";

                $message .= '<li>' . sprintf(
                    /* translators: 1: database user, 2: database name */
                    __( 'Does the user %1$s have permission to use the %2$s database?' ),
                    '<code>' . htmlspecialchars( $this->dbuser, ENT_QUOTES )  . '</code>',
                    '<code>' . htmlspecialchars( $db, ENT_QUOTES ) . '</code>'
                ) . "</li>\n";

                $message .= '<li>' . sprintf(
                    /* translators: %s: database name */
                    __( 'On some systems the name of your database is prefixed with your username, so it would be like <code>username_%1$s</code>. Could that be the problem?' ),
                    htmlspecialchars( $db, ENT_QUOTES )
                ). "</li>\n";

                $message .= "</ul>\n";

                $message .= '<p>' . sprintf(
                    /* translators: %s: support forums URL */
                    __( 'If you don&#8217;t know how to set up a database you should <strong>contact your host</strong>. If all else fails you may find help at the <a href="%s">WordPress Support Forums</a>.' ),
                    __( 'https://wordpress.org/support/' )
                ) . "</p>\n";

                $this->bail( $message, 'db_select_fail' );
            }
        }
    }

    /**
     * Do not use, deprecated.
     *
     * Use esc_sql() or wpdb::prepare() instead.
     *
     * @since 2.8.0
     * @deprecated 3.6.0 Use wpdb::prepare()
     * @see wpdb::prepare
     * @see esc_sql()
     *
     * @param string $string
     * @return string
     */
    function _weak_escape( $string ) {
        if ( func_num_args() === 1 && function_exists( '_deprecated_function' ) )
            _deprecated_function( __METHOD__, '3.6.0', 'wpdb::prepare() or esc_sql()' );
        return addslashes( $string );
    }

    /**
     * Real escape, using mysqli_real_escape_string() or mysql_real_escape_string()
     *
     * @see mysqli_real_escape_string()
     * @see mysql_real_escape_string()
     * @since 2.8.0
     *
     * @param  string $string to escape
     * @return string escaped
     */
    function _real_escape( $string ) {
        if ( $this->dbh ) {
            if ( $this->use_mysqli ) {
                $escaped = mysqli_real_escape_string( $this->dbh, $string );
            } else {
                $escaped = mysql_real_escape_string( $string, $this->dbh );
            }
        } else {
            $class = get_class( $this );
            if ( function_exists( '__' ) ) {
                /* translators: %s: database access abstraction class, usually wpdb or a class extending wpdb */
                _doing_it_wrong( $class, sprintf( __( '%s must set a database connection for use with escaping.' ), $class ), '3.6.0' );
            } else {
                _doing_it_wrong( $class, sprintf( '%s must set a database connection for use with escaping.', $class ), '3.6.0' );
            }
            $escaped = addslashes( $string );
        }

        return $this->add_placeholder_escape( $escaped );
    }

    /**
     * Escape data. Works on arrays.
     *
     * @uses wpdb::_real_escape()
     * @since  2.8.0
     *
     * @param  string|array $data
     * @return string|array escaped
     */
    public function _escape( $data ) {
        if ( is_array( $data ) ) {
            foreach ( $data as $k => $v ) {
                if ( is_array( $v ) ) {
                    $data[$k] = $this->_escape( $v );
                } else {
                    $data[$k] = $this->_real_escape( $v );
                }
            }
        } else {
            $data = $this->_real_escape( $data );
        }

        return $data;
    }

    /**
     * Do not use, deprecated.
     *
     * Use esc_sql() or wpdb::prepare() instead.
     *
     * @since 0.71
     * @deprecated 3.6.0 Use wpdb::prepare()
     * @see wpdb::prepare()
     * @see esc_sql()
     *
     * @param mixed $data
     * @return mixed
     */
    public function escape( $data ) {
        if ( func_num_args() === 1 && function_exists( '_deprecated_function' ) )
            _deprecated_function( __METHOD__, '3.6.0', 'wpdb::prepare() or esc_sql()' );
        if ( is_array( $data ) ) {
            foreach ( $data as $k => $v ) {
                if ( is_array( $v ) )
                    $data[$k] = $this->escape( $v, 'recursive' );
                else
                    $data[$k] = $this->_weak_escape( $v, 'internal' );
            }
        } else {
            $data = $this->_weak_escape( $data, 'internal' );
        }

        return $data;
    }

    /**
     * Escapes content by reference for insertion into the database, for security
     *
     * @uses wpdb::_real_escape()
     *
     * @since 2.3.0
     *
     * @param string $string to escape
     */
    public function escape_by_ref( &$string ) {
        if ( ! is_float( $string ) )
            $string = $this->_real_escape( $string );
    }

    /**
     * Prepares a SQL query for safe execution. Uses sprintf()-like syntax.
     *
     * The following placeholders can be used in the query string:
     *   %d (integer)
     *   %f (float)
     *   %s (string)
     *
     * All placeholders MUST be left unquoted in the query string. A corresponding argument MUST be passed for each placeholder.
     *
     * For compatibility with old behavior, numbered or formatted string placeholders (eg, %1$s, %5s) will not have quotes
     * added by this function, so should be passed with appropriate quotes around them for your usage.
     *
     * Literal percentage signs (%) in the query string must be written as %%. Percentage wildcards (for example,
     * to use in LIKE syntax) must be passed via a substitution argument containing the complete LIKE string, these
     * cannot be inserted directly in the query string. Also see {@see esc_like()}.
     *
     * Arguments may be passed as individual arguments to the method, or as a single array containing all arguments. A combination
     * of the two is not supported.
     *
     * Examples:
     *     $wpdb->prepare( "SELECT * FROM `table` WHERE `column` = %s AND `field` = %d OR `other_field` LIKE %s", array( 'foo', 1337, '%bar' ) );
     *     $wpdb->prepare( "SELECT DATE_FORMAT(`field`, '%%c') FROM `table` WHERE `column` = %s", 'foo' );
     *
     * @link https://secure.php.net/sprintf Description of syntax.
     * @since 2.3.0
     *
     * @param string      $query    Query statement with sprintf()-like placeholders
     * @param array|mixed $args     The array of variables to substitute into the query's placeholders if being called with an array of arguments,
     *                              or the first variable to substitute into the query's placeholders if being called with individual arguments.
     * @param mixed       $args,... further variables to substitute into the query's placeholders if being called wih individual arguments.
     * @return string|void Sanitized query string, if there is a query to prepare.
     */
    public function prepare( $query, $args ) {
        if ( is_null( $query ) ) {
            return;
        }

        // This is not meant to be foolproof -- but it will catch obviously incorrect usage.
        if ( strpos( $query, '%' ) === false ) {
            wp_load_translations_early();
            _doing_it_wrong( 'wpdb::prepare', sprintf( __( 'The query argument of %s must have a placeholder.' ), 'wpdb::prepare()' ), '3.9.0' );
        }

        $args = func_get_args();
        array_shift( $args );

        // If args were passed as an array (as in vsprintf), move them up.
        $passed_as_array = false;
        if ( is_array( $args[0] ) && count( $args ) == 1 ) {
            $passed_as_array = true;
            $args = $args[0];
        }

        foreach ( $args as $arg ) {
            if ( ! is_scalar( $arg ) && ! is_null( $arg ) ) {
                wp_load_translations_early();
                _doing_it_wrong( 'wpdb::prepare', sprintf( __( 'Unsupported value type (%s).' ), gettype( $arg ) ), '4.8.2' );
            }
        }

        /*
         * Specify the formatting allowed in a placeholder. The following are allowed:
         *
         * - Sign specifier. eg, $+d
         * - Numbered placeholders. eg, %1$s
         * - Padding specifier, including custom padding characters. eg, %05s, %'#5s
         * - Alignment specifier. eg, %05-s
         * - Precision specifier. eg, %.2f
         */
        $allowed_format = '(?:[1-9][0-9]*[$])?[-+0-9]*(?: |0|\'.)?[-+0-9]*(?:\.[0-9]+)?';

        /*
         * If a %s placeholder already has quotes around it, removing the existing quotes and re-inserting them
         * ensures the quotes are consistent.
         *
         * For backwards compatibility, this is only applied to %s, and not to placeholders like %1$s, which are frequently
         * used in the middle of longer strings, or as table name placeholders.
         */
        $query = str_replace( "'%s'", '%s', $query ); // Strip any existing single quotes.
        $query = str_replace( '"%s"', '%s', $query ); // Strip any existing double quotes.
        $query = preg_replace( '/(?<!%)%s/', "'%s'", $query ); // Quote the strings, avoiding escaped strings like %%s.

        $query = preg_replace( "/(?<!%)(%($allowed_format)?f)/" , '%\\2F', $query ); // Force floats to be locale unaware.

        $query = preg_replace( "/%(?:%|$|(?!($allowed_format)?[sdF]))/", '%%\\1', $query ); // Escape any unescaped percents.

        // Count the number of valid placeholders in the query.
        $placeholders = preg_match_all( "/(^|[^%]|(%%)+)%($allowed_format)?[sdF]/", $query, $matches );

        if ( count( $args ) !== $placeholders ) {
            if ( 1 === $placeholders && $passed_as_array ) {
                // If the passed query only expected one argument, but the wrong number of arguments were sent as an array, bail.
                wp_load_translations_early();
                _doing_it_wrong( 'wpdb::prepare', __( 'The query only expected one placeholder, but an array of multiple placeholders was sent.' ), '4.9.0' );

                return;
            } else {
                /*
                 * If we don't have the right number of placeholders, but they were passed as individual arguments,
                 * or we were expecting multiple arguments in an array, throw a warning.
                 */
                wp_load_translations_early();
                _doing_it_wrong( 'wpdb::prepare',
                    /* translators: 1: number of placeholders, 2: number of arguments passed */
                    sprintf( __( 'The query does not contain the correct number of placeholders (%1$d) for the number of arguments passed (%2$d).' ),
                        $placeholders,
                        count( $args ) ),
                    '4.8.3'
                );
            }
        }

        array_walk( $args, array( $this, 'escape_by_ref' ) );
        $query = @vsprintf( $query, $args );

        return $this->add_placeholder_escape( $query );
    }

    /**
     * First half of escaping for LIKE special characters % and _ before preparing for MySQL.
     *
     * Use this only before wpdb::prepare() or esc_sql().  Reversing the order is very bad for security.
     *
     * Example Prepared Statement:
     *
     *     $wild = '%';
     *     $find = 'only 43% of planets';
     *     $like = $wild . $wpdb->esc_like( $find ) . $wild;
     *     $sql  = $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_content LIKE %s", $like );
     *
     * Example Escape Chain:
     *
     *     $sql  = esc_sql( $wpdb->esc_like( $input ) );
     *
     * @since 4.0.0
     *
     * @param string $text The raw text to be escaped. The input typed by the user should have no
     *                     extra or deleted slashes.
     * @return string Text in the form of a LIKE phrase. The output is not SQL safe. Call $wpdb::prepare()
     *                or real_escape next.
     */
    public function esc_like( $text ) {
        return addcslashes( $text, '_%\\' );
    }

    /**
     * Print SQL/DB error.
     *
     * @since 0.71
     * @global array $EZSQL_ERROR Stores error information of query and error string
     *
     * @param string $str The error to display
     * @return false|void False if the showing of errors is disabled.
     */
    public function print_error( $str = '' ) {
        global $EZSQL_ERROR;

        if ( !$str ) {
            if ( $this->use_mysqli ) {
                $str = mysqli_error( $this->dbh );
            } else {
                $str = mysql_error( $this->dbh );
            }
        }
        $EZSQL_ERROR[] = array( 'query' => $this->last_query, 'error_str' => $str );

        if ( $this->suppress_errors )
            return false;

        wp_load_translations_early();

        if ( $caller = $this->get_caller() ) {
            /* translators: 1: Database error message, 2: SQL query, 3: Name of the calling function */
            $error_str = sprintf( __( 'WordPress database error %1$s for query %2$s made by %3$s' ), $str, $this->last_query, $caller );
        } else {
            /* translators: 1: Database error message, 2: SQL query */
            $error_str = sprintf( __( 'WordPress database error %1$s for query %2$s' ), $str, $this->last_query );
        }

        error_log( $error_str );

        // Are we showing errors?
        if ( ! $this->show_errors )
            return false;

        // If there is an error then take note of it
        if ( is_multisite() ) {
            $msg = sprintf(
                "%s [%s]\n%s\n",
                __( 'WordPress database error:' ),
                $str,
                $this->last_query
            );

            if ( defined( 'ERRORLOGFILE' ) ) {
                error_log( $msg, 3, ERRORLOGFILE );
            }
            if ( defined( 'DIEONDBERROR' ) ) {
                wp_die( $msg );
            }
        } else {
            $str   = htmlspecialchars( $str, ENT_QUOTES );
            $query = htmlspecialchars( $this->last_query, ENT_QUOTES );

            printf(
                '<div id="error"><p class="wpdberror"><strong>%s</strong> [%s]<br /><code>%s</code></p></div>',
                __( 'WordPress database error:' ),
                $str,
                $query
            );
        }
    }

    /**
     * Enables showing of database errors.
     *
     * This function should be used only to enable showing of errors.
     * wpdb::hide_errors() should be used instead for hiding of errors. However,
     * this function can be used to enable and disable showing of database
     * errors.
     *
     * @since 0.71
     * @see wpdb::hide_errors()
     *
     * @param bool $show Whether to show or hide errors
     * @return bool Old value for showing errors.
     */
    public function show_errors( $show = true ) {
        $errors = $this->show_errors;
        $this->show_errors = $show;
        return $errors;
    }

    /**
     * Disables showing of database errors.
     *
     * By default database errors are not shown.
     *
     * @since 0.71
     * @see wpdb::show_errors()
     *
     * @return bool Whether showing of errors was active
     */
    public function hide_errors() {
        $show = $this->show_errors;
        $this->show_errors = false;
        return $show;
    }

    /**
     * Whether to suppress database errors.
     *
     * By default database errors are suppressed, with a simple
     * call to this function they can be enabled.
     *
     * @since 2.5.0
     * @see wpdb::hide_errors()
     * @param bool $suppress Optional. New value. Defaults to true.
     * @return bool Old value
     */
    public function suppress_errors( $suppress = true ) {
        $errors = $this->suppress_errors;
        $this->suppress_errors = (bool) $suppress;
        return $errors;
    }

    /**
     * Kill cached query results.
     *
     * @since 0.71
     */
    public function flush() {
        $this->last_result = array();
        $this->col_info    = null;
        $this->last_query  = null;
        $this->rows_affected = $this->num_rows = 0;
        $this->last_error  = '';

        if ( $this->use_mysqli && $this->result instanceof mysqli_result ) {
            mysqli_free_result( $this->result );
            $this->result = null;

            // Sanity check before using the handle
            if ( empty( $this->dbh ) || !( $this->dbh instanceof mysqli ) ) {
                return;
            }

            // Clear out any results from a multi-query
            while ( mysqli_more_results( $this->dbh ) ) {
                mysqli_next_result( $this->dbh );
            }
        } elseif ( is_resource( $this->result ) ) {
            mysql_free_result( $this->result );
        }
    }

    /**
     * Connect to and select database.
     *
     * If $allow_bail is false, the lack of database connection will need
     * to be handled manually.
     *
     * @since 3.0.0
     * @since 3.9.0 $allow_bail parameter added.
     *
     * @param bool $allow_bail Optional. Allows the function to bail. Default true.
     * @return bool True with a successful connection, false on failure.
     */
    public function db_connect( $allow_bail = true ) {
        $this->is_mysql = true;

        /*
         * Deprecated in 3.9+ when using MySQLi. No equivalent
         * $new_link parameter exists for mysqli_* functions.
         */
        $new_link = defined( 'MYSQL_NEW_LINK' ) ? MYSQL_NEW_LINK : true;
        $client_flags = defined( 'MYSQL_CLIENT_FLAGS' ) ? MYSQL_CLIENT_FLAGS : 0;

        if ( $this->use_mysqli ) {
            $this->dbh = mysqli_init();

            $host    = $this->dbhost;
            $port    = null;
            $socket  = null;
            $is_ipv6 = false;

            if ( $host_data = $this->parse_db_host( $this->dbhost ) ) {
                list( $host, $port, $socket, $is_ipv6 ) = $host_data;
            }

            /*
             * If using the `mysqlnd` library, the IPv6 address needs to be
             * enclosed in square brackets, whereas it doesn't while using the
             * `libmysqlclient` library.
             * @see https://bugs.php.net/bug.php?id=67563
             */
            if ( $is_ipv6 && extension_loaded( 'mysqlnd' ) ) {
                $host = "[$host]";
            }

            if ( WP_DEBUG ) {
                mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, null, $port, $socket, $client_flags );
            } else {
                @mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, null, $port, $socket, $client_flags );
            }

            if ( $this->dbh->connect_errno ) {
                $this->dbh = null;

                /*
                 * It's possible ext/mysqli is misconfigured. Fall back to ext/mysql if:
                 *  - We haven't previously connected, and
                 *  - WP_USE_EXT_MYSQL isn't set to false, and
                 *  - ext/mysql is loaded.
                 */
                $attempt_fallback = true;

                if ( $this->has_connected ) {
                    $attempt_fallback = false;
                } elseif ( defined( 'WP_USE_EXT_MYSQL' ) && ! WP_USE_EXT_MYSQL ) {
                    $attempt_fallback = false;
                } elseif ( ! function_exists( 'mysql_connect' ) ) {
                    $attempt_fallback = false;
                }

                if ( $attempt_fallback ) {
                    $this->use_mysqli = false;
                    return $this->db_connect( $allow_bail );
                }
            }
        } else {
            if ( WP_DEBUG ) {
                $this->dbh = mysql_connect( $this->dbhost, $this->dbuser, $this->dbpassword, $new_link, $client_flags );
            } else {
                $this->dbh = @mysql_connect( $this->dbhost, $this->dbuser, $this->dbpassword, $new_link, $client_flags );
            }
        }

        if ( ! $this->dbh && $allow_bail ) {
            wp_load_translations_early();

            // Load custom DB error template, if present.
            if ( file_exists( WP_CONTENT_DIR . '/db-error.php' ) ) {
                require_once( WP_CONTENT_DIR . '/db-error.php' );
                die();
            }

            $message = '<h1>' . __( 'Error establishing a database connection' ) . "</h1>\n";

            $message .= '<p>' . sprintf(
                /* translators: 1: wp-config.php. 2: database host */
                __( 'This either means that the username and password information in your %1$s file is incorrect or we can&#8217;t contact the database server at %2$s. This could mean your host&#8217;s database server is down.' ),
                '<code>wp-config.php</code>',
                '<code>' . htmlspecialchars( $this->dbhost, ENT_QUOTES ) . '</code>'
            ) . "</p>\n";

            $message .= "<ul>\n";
            $message .= '<li>' . __( 'Are you sure you have the correct username and password?' ) . "</li>\n";
            $message .= '<li>' . __( 'Are you sure that you have typed the correct hostname?' ) . "</li>\n";
            $message .= '<li>' . __( 'Are you sure that the database server is running?' ) . "</li>\n";
            $message .= "</ul>\n";

            $message .= '<p>' . sprintf(
                /* translators: %s: support forums URL */
                __( 'If you&#8217;re unsure what these terms mean you should probably contact your host. If you still need help you can always visit the <a href="%s">WordPress Support Forums</a>.' ),
                __( 'https://wordpress.org/support/' )
            ) . "</p>\n";

            $this->bail( $message, 'db_connect_fail' );

            return false;
        } elseif ( $this->dbh ) {
            if ( ! $this->has_connected ) {
                $this->init_charset();
            }

            $this->has_connected = true;

            $this->set_charset( $this->dbh );

            $this->ready = true;
            $this->set_sql_mode();
            $this->select( $this->dbname, $this->dbh );

            return true;
        }

        return false;
    }

    /**
     * Parse the DB_HOST setting to interpret it for mysqli_real_connect.
     *
     * mysqli_real_connect doesn't support the host param including a port or
     * socket like mysql_connect does. This duplicates how mysql_connect detects
     * a port and/or socket file.
     *
     * @since 4.9.0
     *
     * @param string $host The DB_HOST setting to parse.
     * @return array|bool Array containing the host, the port, the socket and whether
     *                    it is an IPv6 address, in that order. If $host couldn't be parsed,
     *                    returns false.
     */
    public function parse_db_host( $host ) {
        $port    = null;
        $socket  = null;
        $is_ipv6 = false;

        // We need to check for an IPv6 address first.
        // An IPv6 address will always contain at least two colons.
        if ( substr_count( $host, ':' ) > 1 ) {
            $pattern = '#^(?:\[)?(?<host>[0-9a-fA-F:]+)(?:\]:(?<port>[\d]+))?(?:/(?<socket>.+))?#';
            $is_ipv6 = true;
        } else {
            // We seem to be dealing with an IPv4 address.
            $pattern = '#^(?<host>[^:/]*)(?::(?<port>[\d]+))?(?::(?<socket>.+))?#';
        }

        $matches = array();
        $result = preg_match( $pattern, $host, $matches );

        if ( 1 !== $result ) {
            // Couldn't parse the address, bail.
            return false;
        }

        $host = '';
        foreach ( array( 'host', 'port', 'socket' ) as $component ) {
            if ( ! empty( $matches[ $component ] ) ) {
                $$component = $matches[ $component ];
            }
        }

        return array( $host, $port, $socket, $is_ipv6 );
    }

    /**
     * Checks that the connection to the database is still up. If not, try to reconnect.
     *
     * If this function is unable to reconnect, it will forcibly die, or if after the
     * the {@see 'template_redirect'} hook has been fired, return false instead.
     *
     * If $allow_bail is false, the lack of database connection will need
     * to be handled manually.
     *
     * @since 3.9.0
     *
     * @param bool $allow_bail Optional. Allows the function to bail. Default true.
     * @return bool|void True if the connection is up.
     */
    public function check_connection( $allow_bail = true ) {
        if ( $this->use_mysqli ) {
            if ( ! empty( $this->dbh ) && mysqli_ping( $this->dbh ) ) {
                return true;
            }
        } else {
            if ( ! empty( $this->dbh ) && mysql_ping( $this->dbh ) ) {
                return true;
            }
        }

        $error_reporting = false;

        // Disable warnings, as we don't want to see a multitude of "unable to connect" messages
        if ( WP_DEBUG ) {
            $error_reporting = error_reporting();
            error_reporting( $error_reporting & ~E_WARNING );
        }

        for ( $tries = 1; $tries <= $this->reconnect_retries; $tries++ ) {
            // On the last try, re-enable warnings. We want to see a single instance of the
            // "unable to connect" message on the bail() screen, if it appears.
            if ( $this->reconnect_retries === $tries && WP_DEBUG ) {
                error_reporting( $error_reporting );
            }

            if ( $this->db_connect( false ) ) {
                if ( $error_reporting ) {
                    error_reporting( $error_reporting );
                }

                return true;
            }

            sleep( 1 );
        }

        // If template_redirect has already happened, it's too late for wp_die()/dead_db().
        // Let's just return and hope for the best.
        if ( did_action( 'template_redirect' ) ) {
            return false;
        }

        if ( ! $allow_bail ) {
            return false;
        }

        wp_load_translations_early();

        $message = '<h1>' . __( 'Error reconnecting to the database' ) . "</h1>\n";

        $message .= '<p>' . sprintf(
            /* translators: %s: database host */
            __( 'This means that we lost contact with the database server at %s. This could mean your host&#8217;s database server is down.' ),
            '<code>' . htmlspecialchars( $this->dbhost, ENT_QUOTES ) . '</code>'
        ) . "</p>\n";

        $message .= "<ul>\n";
        $message .= '<li>' . __( 'Are you sure that the database server is running?' ) . "</li>\n";
        $message .= '<li>' . __( 'Are you sure that the database server is not under particularly heavy load?' ) . "</li>\n";
        $message .= "</ul>\n";

        $message .= '<p>' . sprintf(
            /* translators: %s: support forums URL */
            __( 'If you&#8217;re unsure what these terms mean you should probably contact your host. If you still need help you can always visit the <a href="%s">WordPress Support Forums</a>.' ),
            __( 'https://wordpress.org/support/' )
        ) . "</p>\n";

        // We weren't able to reconnect, so we better bail.
        $this->bail( $message, 'db_connect_fail' );

        // Call dead_db() if bail didn't die, because this database is no more. It has ceased to be (at least temporarily).
        dead_db();
    }

    /**
     * Perform a MySQL database query, using current database connection.
     *
     * More information can be found on the codex page.
     *
     * @since 0.71
     *
     * @param string $query Database query
     * @return int|false Number of rows affected/selected or false on error
     */
    public function query( $query ) {
        if ( ! $this->ready ) {
            $this->check_current_query = true;
            return false;
        }

        /**
         * Filters the database query.
         *
         * Some queries are made before the plugins have been loaded,
         * and thus cannot be filtered with this method.
         *
         * @since 2.1.0
         *
         * @param string $query Database query.
         */
        $query = apply_filters( 'query', $query );

        $this->flush();

        // Log how the function was called
        $this->func_call = "\$db->query(\"$query\")";

        // If we're writing to the database, make sure the query will write safely.
        if ( $this->check_current_query && ! $this->check_ascii( $query ) ) {
            $stripped_query = $this->strip_invalid_text_from_query( $query );
            // strip_invalid_text_from_query() can perform queries, so we need
            // to flush again, just to make sure everything is clear.
            $this->flush();
            if ( $stripped_query !== $query ) {
                $this->insert_id = 0;
                return false;
            }
        }

        $this->check_current_query = true;

        // Keep track of the last query for debug.
        $this->last_query = $query;

        $this->_do_query( $query );

        // MySQL server has gone away, try to reconnect.
        $mysql_errno = 0;
        if ( ! empty( $this->dbh ) ) {
            if ( $this->use_mysqli ) {
                if ( $this->dbh instanceof mysqli ) {
                    $mysql_errno = mysqli_errno( $this->dbh );
                } else {
                    // $dbh is defined, but isn't a real connection.
                    // Something has gone horribly wrong, let's try a reconnect.
                    $mysql_errno = 2006;
                }
            } else {
                if ( is_resource( $this->dbh ) ) {
                    $mysql_errno = mysql_errno( $this->dbh );
                } else {
                    $mysql_errno = 2006;
                }
            }
        }

        if ( empty( $this->dbh ) || 2006 == $mysql_errno ) {
            if ( $this->check_connection() ) {
                $this->_do_query( $query );
            } else {
                $this->insert_id = 0;
                return false;
            }
        }

        // If there is an error then take note of it.
        if ( $this->use_mysqli ) {
            if ( $this->dbh instanceof mysqli ) {
                $this->last_error = mysqli_error( $this->dbh );
            } else {
                $this->last_error = __( 'Unable to retrieve the error message from MySQL' );
            }
        } else {
            if ( is_resource( $this->dbh ) ) {
                $this->last_error = mysql_error( $this->dbh );
            } else {
                $this->last_error = __( 'Unable to retrieve the error message from MySQL' );
            }
        }

        if ( $this->last_error ) {
            // Clear insert_id on a subsequent failed insert.
            if ( $this->insert_id && preg_match( '/^\s*(insert|replace)\s/i', $query ) )
                $this->insert_id = 0;

            $this->print_error();
            return false;
        }

        if ( preg_match( '/^\s*(create|alter|truncate|drop)\s/i', $query ) ) {
            $return_val = $this->result;
        } elseif ( preg_match( '/^\s*(insert|delete|update|replace)\s/i', $query ) ) {
            if ( $this->use_mysqli ) {
                $this->rows_affected = mysqli_affected_rows( $this->dbh );
            } else {
                $this->rows_affected = mysql_affected_rows( $this->dbh );
            }
            // Take note of the insert_id
            if ( preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
                if ( $this->use_mysqli ) {
                    $this->insert_id = mysqli_insert_id( $this->dbh );
                } else {
                    $this->insert_id = mysql_insert_id( $this->dbh );
                }
            }
            // Return number of rows affected
            $return_val = $this->rows_affected;
        } else {
            $num_rows = 0;
            if ( $this->use_mysqli && $this->result instanceof mysqli_result ) {
                while ( $row = mysqli_fetch_object( $this->result ) ) {
                    $this->last_result[$num_rows] = $row;
                    $num_rows++;
                }
            } elseif ( is_resource( $this->result ) ) {
                while ( $row = mysql_fetch_object( $this->result ) ) {
                    $this->last_result[$num_rows] = $row;
                    $num_rows++;
                }
            }

            // Log number of rows the query returned
            // and return number of rows selected
            $this->num_rows = $num_rows;
            $return_val     = $num_rows;
        }

        return $return_val;
    }

    /**
     * Internal function to perform the mysql_query() call.
     *
     * @since 3.9.0
     *
     * @see wpdb::query()
     *
     * @param string $query The query to run.
     */
    private function _do_query( $query ) {
        if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
            $this->timer_start();
        }

        if ( ! empty( $this->dbh ) && $this->use_mysqli ) {
            $this->result = mysqli_query( $this->dbh, $query );
        } elseif ( ! empty( $this->dbh ) ) {
            $this->result = mysql_query( $query, $this->dbh );
        }
        $this->num_queries++;

        if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
            $this->queries[] = array( $query, $this->timer_stop(), $this->get_caller() );
        }
    }

    /**
     * Generates and returns a placeholder escape string for use in queries returned by ::prepare().
     *
     * @since 4.8.3
     *
     * @return string String to escape placeholders.
     */
    public function placeholder_escape() {
        static $placeholder;

        if ( ! $placeholder ) {
            // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
            $algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
            // Old WP installs may not have AUTH_SALT defined.
            $salt = defined( 'AUTH_SALT' ) && AUTH_SALT ? AUTH_SALT : (string) rand();

            $placeholder = '{' . hash_hmac( $algo, uniqid( $salt, true ), $salt ) . '}';
        }

        /*
         * Add the filter to remove the placeholder escaper. Uses priority 0, so that anything
         * else attached to this filter will recieve the query with the placeholder string removed.
         */
        if ( ! has_filter( 'query', array( $this, 'remove_placeholder_escape' ) ) ) {
            add_filter( 'query', array( $this, 'remove_placeholder_escape' ), 0 );
        }

        return $placeholder;
    }

    /**
     * Adds a placeholder escape string, to escape anything that resembles a printf() placeholder.
     *
     * @since 4.8.3
     *
     * @param string $query The query to escape.
     * @return string The query with the placeholder escape string inserted where necessary.
     */
    public function add_placeholder_escape( $query ) {
        /*
         * To prevent returning anything that even vaguely resembles a placeholder,
         * we clobber every % we can find.
         */
        return str_replace( '%', $this->placeholder_escape(), $query );
    }

    /**
     * Removes the placeholder escape strings from a query.
     *
     * @since 4.8.3
     *
     * @param string $query The query from which the placeholder will be removed.
     * @return string The query with the placeholder removed.
     */
    public function remove_placeholder_escape( $query ) {
        return str_replace( $this->placeholder_escape(), '%', $query );
    }

    /**
     * Insert a row into a table.
     *
     *     wpdb::insert( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
     *     wpdb::insert( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
     *
     * @since 2.5.0
     * @see wpdb::prepare()
     * @see wpdb::$field_types
     * @see wp_set_wpdb_vars()
     *
     * @param string       $table  Table name
     * @param array        $data   Data to insert (in column => value pairs).
     *                             Both $data columns and $data values should be "raw" (neither should be SQL escaped).
     *                             Sending a null value will cause the column to be set to NULL - the corresponding format is ignored in this case.
     * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data.
     *                             If string, that format will be used for all of the values in $data.
     *                             A format is one of '%d', '%f', '%s' (integer, float, string).
     *                             If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
     * @return int|false The number of rows inserted, or false on error.
     */
    public function insert( $table, $data, $format = null ) {
        return $this->_insert_replace_helper( $table, $data, $format, 'INSERT' );
    }

    /**
     * Replace a row into a table.
     *
     *     wpdb::replace( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
     *     wpdb::replace( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
     *
     * @since 3.0.0
     * @see wpdb::prepare()
     * @see wpdb::$field_types
     * @see wp_set_wpdb_vars()
     *
     * @param string       $table  Table name
     * @param array        $data   Data to insert (in column => value pairs).
     *                             Both $data columns and $data values should be "raw" (neither should be SQL escaped).
     *                             Sending a null value will cause the column to be set to NULL - the corresponding format is ignored in this case.
     * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data.
     *                             If string, that format will be used for all of the values in $data.
     *                             A format is one of '%d', '%f', '%s' (integer, float, string).
     *                             If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
     * @return int|false The number of rows affected, or false on error.
     */
    public function replace( $table, $data, $format = null ) {
        return $this->_insert_replace_helper( $table, $data, $format, 'REPLACE' );
    }

    /**
     * Helper function for insert and replace.
     *
     * Runs an insert or replace query based on $type argument.
     *
     * @since 3.0.0
     * @see wpdb::prepare()
     * @see wpdb::$field_types
     * @see wp_set_wpdb_vars()
     *
     * @param string       $table  Table name
     * @param array        $data   Data to insert (in column => value pairs).
     *                             Both $data columns and $data values should be "raw" (neither should be SQL escaped).
     *                             Sending a null value will cause the column to be set to NULL - the corresponding format is ignored in this case.
     * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data.
     *                             If string, that format will be used for all of the values in $data.
     *                             A format is one of '%d', '%f', '%s' (integer, float, string).
     *                             If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
     * @param string $type         Optional. What type of operation is this? INSERT or REPLACE. Defaults to INSERT.
     * @return int|false The number of rows affected, or false on error.
     */
    function _insert_replace_helper( $table, $data, $format = null, $type = 'INSERT' ) {
        $this->insert_id = 0;

        if ( ! in_array( strtoupper( $type ), array( 'REPLACE', 'INSERT' ) ) ) {
            return false;
        }

        $data = $this->process_fields( $table, $data, $format );
        if ( false === $data ) {
            return false;
        }

        $formats = $values = array();
        foreach ( $data as $value ) {
            if ( is_null( $value['value'] ) ) {
                $formats[] = 'NULL';
                continue;
            }

            $formats[] = $value['format'];
            $values[]  = $value['value'];
        }

        $fields  = '`' . implode( '`, `', array_keys( $data ) ) . '`';
        $formats = implode( ', ', $formats );

        $sql = "$type INTO `$table` ($fields) VALUES ($formats)";

        $this->check_current_query = false;
        return $this->query( $this->prepare( $sql, $values ) );
    }

    /**
     * Update a row in the table
     *
     *     wpdb::update( 'table', array( 'column' => 'foo', 'field' => 'bar' ), array( 'ID' => 1 ) )
     *     wpdb::update( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( 'ID' => 1 ), array( '%s', '%d' ), array( '%d' ) )
     *
     * @since 2.5.0
     * @see wpdb::prepare()
     * @see wpdb::$field_types
     * @see wp_set_wpdb_vars()
     *
     * @param string       $table        Table name
     * @param array        $data         Data to update (in column => value pairs).
     *                                   Both $data columns and $data values should be "raw" (neither should be SQL escaped).
     *                                   Sending a null value will cause the column to be set to NULL - the corresponding
     *                                   format is ignored in this case.
     * @param array        $where        A named array of WHERE clauses (in column => value pairs).
     *                                   Multiple clauses will be joined with ANDs.
     *                                   Both $where columns and $where values should be "raw".
     *                                   Sending a null value will create an IS NULL comparison - the corresponding format will be ignored in this case.
     * @param array|string $format       Optional. An array of formats to be mapped to each of the values in $data.
     *                                   If string, that format will be used for all of the values in $data.
     *                                   A format is one of '%d', '%f', '%s' (integer, float, string).
     *                                   If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
     * @param array|string $where_format Optional. An array of formats to be mapped to each of the values in $where.
     *                                   If string, that format will be used for all of the items in $where.
     *                                   A format is one of '%d', '%f', '%s' (integer, float, string).
     *                                   If omitted, all values in $where will be treated as strings.
     * @return int|false The number of rows updated, or false on error.
     */
    public function update( $table, $data, $where, $format = null, $where_format = null ) {
        if ( ! is_array( $data ) || ! is_array( $where ) ) {
            return false;
        }

        $data = $this->process_fields( $table, $data, $format );
        if ( false === $data ) {
            return false;
        }
        $where = $this->process_fields( $table, $where, $where_format );
        if ( false === $where ) {
            return false;
        }

        $fields = $conditions = $values = array();
        foreach ( $data as $field => $value ) {
            if ( is_null( $value['value'] ) ) {
                $fields[] = "`$field` = NULL";
                continue;
            }

            $fields[] = "`$field` = " . $value['format'];
            $values[] = $value['value'];
        }
        foreach ( $where as $field => $value ) {
            if ( is_null( $value['value'] ) ) {
                $conditions[] = "`$field` IS NULL";
                continue;
            }

            $conditions[] = "`$field` = " . $value['format'];
            $values[] = $value['value'];
        }

        $fields = implode( ', ', $fields );
        $conditions = implode( ' AND ', $conditions );

        $sql = "UPDATE `$table` SET $fields WHERE $conditions";

        $this->check_current_query = false;
        return $this->query( $this->prepare( $sql, $values ) );
    }

    /**
     * Delete a row in the table
     *
     *     wpdb::delete( 'table', array( 'ID' => 1 ) )
     *     wpdb::delete( 'table', array( 'ID' => 1 ), array( '%d' ) )
     *
     * @since 3.4.0
     * @see wpdb::prepare()
     * @see wpdb::$field_types
     * @see wp_set_wpdb_vars()
     *
     * @param string       $table        Table name
     * @param array        $where        A named array of WHERE clauses (in column => value pairs).
     *                                   Multiple clauses will be joined with ANDs.
     *                                   Both $where columns and $where values should be "raw".
     *                                   Sending a null value will create an IS NULL comparison - the corresponding format will be ignored in this case.
     * @param array|string $where_format Optional. An array of formats to be mapped to each of the values in $where.
     *                                   If string, that format will be used for all of the items in $where.
     *                                   A format is one of '%d', '%f', '%s' (integer, float, string).
     *                                   If omitted, all values in $where will be treated as strings unless otherwise specified in wpdb::$field_types.
     * @return int|false The number of rows updated, or false on error.
     */
    public function delete( $table, $where, $where_format = null ) {
        if ( ! is_array( $where ) ) {
            return false;
        }

        $where = $this->process_fields( $table, $where, $where_format );
        if ( false === $where ) {
            return false;
        }

        $conditions = $values = array();
        foreach ( $where as $field => $value ) {
            if ( is_null( $value['value'] ) ) {
                $conditions[] = "`$field` IS NULL";
                continue;
            }

            $conditions[] = "`$field` = " . $value['format'];
            $values[] = $value['value'];
        }

        $conditions = implode( ' AND ', $conditions );

        $sql = "DELETE FROM `$table` WHERE $conditions";

        $this->check_current_query = false;
        return $this->query( $this->prepare( $sql, $values ) );
    }

    /**
     * Processes arrays of field/value pairs and field formats.
     *
     * This is a helper method for wpdb's CRUD methods, which take field/value
     * pairs for inserts, updates, and where clauses. This method first pairs
     * each value with a format. Then it determines the charset of that field,
     * using that to determine if any invalid text would be stripped. If text is
     * stripped, then field processing is rejected and the query fails.
     *
     * @since 4.2.0
     *
     * @param string $table  Table name.
     * @param array  $data   Field/value pair.
     * @param mixed  $format Format for each field.
     * @return array|false Returns an array of fields that contain paired values
     *                    and formats. Returns false for invalid values.
     */
    protected function process_fields( $table, $data, $format ) {
        $data = $this->process_field_formats( $data, $format );
        if ( false === $data ) {
            return false;
        }

        $data = $this->process_field_charsets( $data, $table );
        if ( false === $data ) {
            return false;
        }

        $data = $this->process_field_lengths( $data, $table );
        if ( false === $data ) {
            return false;
        }

        $converted_data = $this->strip_invalid_text( $data );

        if ( $data !== $converted_data ) {
            return false;
        }

        return $data;
    }

    /**
     * Prepares arrays of value/format pairs as passed to wpdb CRUD methods.
     *
     * @since 4.2.0
     *
     * @param array $data   Array of fields to values.
     * @param mixed $format Formats to be mapped to the values in $data.
     * @return array Array, keyed by field names with values being an array
     *               of 'value' and 'format' keys.
     */
    protected function process_field_formats( $data, $format ) {
        $formats = $original_formats = (array) $format;

        foreach ( $data as $field => $value ) {
            $value = array(
                'value'  => $value,
                'format' => '%s',
            );

            if ( ! empty( $format ) ) {
                $value['format'] = array_shift( $formats );
                if ( ! $value['format'] ) {
                    $value['format'] = reset( $original_formats );
                }
            } elseif ( isset( $this->field_types[ $field ] ) ) {
                $value['format'] = $this->field_types[ $field ];
            }

            $data[ $field ] = $value;
        }

        return $data;
    }

    /**
     * Adds field charsets to field/value/format arrays generated by
     * the wpdb::process_field_formats() method.
     *
     * @since 4.2.0
     *
     * @param array  $data  As it comes from the wpdb::process_field_formats() method.
     * @param string $table Table name.
     * @return array|false The same array as $data with additional 'charset' keys.
     */
    protected function process_field_charsets( $data, $table ) {
        foreach ( $data as $field => $value ) {
            if ( '%d' === $value['format'] || '%f' === $value['format'] ) {
                /*
                 * We can skip this field if we know it isn't a string.
                 * This checks %d/%f versus ! %s because its sprintf() could take more.
                 */
                $value['charset'] = false;
            } else {
                $value['charset'] = $this->get_col_charset( $table, $field );
                if ( is_wp_error( $value['charset'] ) ) {
                    return false;
                }
            }

            $data[ $field ] = $value;
        }

        return $data;
    }

    /**
     * For string fields, record the maximum string length that field can safely save.
     *
     * @since 4.2.1
     *
     * @param array  $data  As it comes from the wpdb::process_field_charsets() method.
     * @param string $table Table name.
     * @return array|false The same array as $data with additional 'length' keys, or false if
     *                     any of the values were too long for their corresponding field.
     */
    protected function process_field_lengths( $data, $table ) {
        foreach ( $data as $field => $value ) {
            if ( '%d' === $value['format'] || '%f' === $value['format'] ) {
                /*
                 * We can skip this field if we know it isn't a string.
                 * This checks %d/%f versus ! %s because its sprintf() could take more.
                 */
                $value['length'] = false;
            } else {
                $value['length'] = $this->get_col_length( $table, $field );
                if ( is_wp_error( $value['length'] ) ) {
                    return false;
                }
            }

            $data[ $field ] = $value;
        }

        return $data;
    }

    /**
     * Retrieve one variable from the database.
     *
     * Executes a SQL query and returns the value from the SQL result.
     * If the SQL result contains more than one column and/or more than one row, this function returns the value in the column and row specified.
     * If $query is null, this function returns the value in the specified column and row from the previous SQL result.
     *
     * @since 0.71
     *
     * @param string|null $query Optional. SQL query. Defaults to null, use the result from the previous query.
     * @param int         $x     Optional. Column of value to return. Indexed from 0.
     * @param int         $y     Optional. Row of value to return. Indexed from 0.
     * @return string|null Database query result (as string), or null on failure
     */
    public function get_var( $query = null, $x = 0, $y = 0 ) {
        $this->func_call = "\$db->get_var(\"$query\", $x, $y)";

        if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
            $this->check_current_query = false;
        }

        if ( $query ) {
            $this->query( $query );
        }

        // Extract var out of cached results based x,y vals
        if ( !empty( $this->last_result[$y] ) ) {
            $values = array_values( get_object_vars( $this->last_result[$y] ) );
        }

        // If there is a value return it else return null
        return ( isset( $values[$x] ) && $values[$x] !== '' ) ? $values[$x] : null;
    }

    /**
     * Retrieve one row from the database.
     *
     * Executes a SQL query and returns the row from the SQL result.
     *
     * @since 0.71
     *
     * @param string|null $query  SQL query.
     * @param string      $output Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which correspond to
     *                            an stdClass object, an associative array, or a numeric array, respectively. Default OBJECT.
     * @param int         $y      Optional. Row to return. Indexed from 0.
     * @return array|object|null|void Database query result in format specified by $output or null on failure
     */
    public function get_row( $query = null, $output = OBJECT, $y = 0 ) {
        $this->func_call = "\$db->get_row(\"$query\",$output,$y)";

        if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
            $this->check_current_query = false;
        }

        if ( $query ) {
            $this->query( $query );
        } else {
            return null;
        }

        if ( !isset( $this->last_result[$y] ) )
            return null;

        if ( $output == OBJECT ) {
            return $this->last_result[$y] ? $this->last_result[$y] : null;
        } elseif ( $output == ARRAY_A ) {
            return $this->last_result[$y] ? get_object_vars( $this->last_result[$y] ) : null;
        } elseif ( $output == ARRAY_N ) {
            return $this->last_result[$y] ? array_values( get_object_vars( $this->last_result[$y] ) ) : null;
        } elseif ( strtoupper( $output ) === OBJECT ) {
            // Back compat for OBJECT being previously case insensitive.
            return $this->last_result[$y] ? $this->last_result[$y] : null;
        } else {
            $this->print_error( " \$db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N" );
        }
    }

    /**
     * Retrieve one column from the database.
     *
     * Executes a SQL query and returns the column from the SQL result.
     * If the SQL result contains more than one column, this function returns the column specified.
     * If $query is null, this function returns the specified column from the previous SQL result.
     *
     * @since 0.71
     *
     * @param string|null $query Optional. SQL query. Defaults to previous query.
     * @param int         $x     Optional. Column to return. Indexed from 0.
     * @return array Database query result. Array indexed from 0 by SQL result row number.
     */
    public function get_col( $query = null , $x = 0 ) {
        if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
            $this->check_current_query = false;
        }

        if ( $query ) {
            $this->query( $query );
        }

        $new_array = array();
        // Extract the column values
        for ( $i = 0, $j = count( $this->last_result ); $i < $j; $i++ ) {
            $new_array[$i] = $this->get_var( null, $x, $i );
        }
        return $new_array;
    }

    /**
     * Retrieve an entire SQL result set from the database (i.e., many rows)
     *
     * Executes a SQL query and returns the entire SQL result.
     *
     * @since 0.71
     *
     * @param string $query  SQL query.
     * @param string $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants.
     *                       With one of the first three, return an array of rows indexed from 0 by SQL result row number.
     *                       Each row is an associative array (column => value, ...), a numerically indexed array (0 => value, ...), or an object. ( ->column = value ), respectively.
     *                       With OBJECT_K, return an associative array of row objects keyed by the value of each row's first column's value.
     *                       Duplicate keys are discarded.
     * @return array|object|null Database query results
     */
    public function get_results( $query = null, $output = OBJECT ) {
        $this->func_call = "\$db->get_results(\"$query\", $output)";

        if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
            $this->check_current_query = false;
        }

        if ( $query ) {
            $this->query( $query );
        } else {
            return null;
        }

        $new_array = array();
        if ( $output == OBJECT ) {
            // Return an integer-keyed array of row objects
            return $this->last_result;
        } elseif ( $output == OBJECT_K ) {
            // Return an array of row objects with keys from column 1
            // (Duplicates are discarded)
            foreach ( $this->last_result as $row ) {
                $var_by_ref = get_object_vars( $row );
                $key = array_shift( $var_by_ref );
                if ( ! isset( $new_array[ $key ] ) )
                    $new_array[ $key ] = $row;
            }
            return $new_array;
        } elseif ( $output == ARRAY_A || $output == ARRAY_N ) {
            // Return an integer-keyed array of...
            if ( $this->last_result ) {
                foreach ( (array) $this->last_result as $row ) {
                    if ( $output == ARRAY_N ) {
                        // ...integer-keyed row arrays
                        $new_array[] = array_values( get_object_vars( $row ) );
                    } else {
                        // ...column name-keyed row arrays
                        $new_array[] = get_object_vars( $row );
                    }
                }
            }
            return $new_array;
        } elseif ( strtoupper( $output ) === OBJECT ) {
            // Back compat for OBJECT being previously case insensitive.
            return $this->last_result;
        }
        return null;
    }

    /**
     * Retrieves the character set for the given table.
     *
     * @since 4.2.0
     *
     * @param string $table Table name.
     * @return string|WP_Error Table character set, WP_Error object if it couldn't be found.
     */
    protected function get_table_charset( $table ) {
        $tablekey = strtolower( $table );

        /**
         * Filters the table charset value before the DB is checked.
         *
         * Passing a non-null value to the filter will effectively short-circuit
         * checking the DB for the charset, returning that value instead.
         *
         * @since 4.2.0
         *
         * @param string $charset The character set to use. Default null.
         * @param string $table   The name of the table being checked.
         */
        $charset = apply_filters( 'pre_get_table_charset', null, $table );
        if ( null !== $charset ) {
            return $charset;
        }

        if ( isset( $this->table_charset[ $tablekey ] ) ) {
            return $this->table_charset[ $tablekey ];
        }

        $charsets = $columns = array();

        $table_parts = explode( '.', $table );
        $table = '`' . implode( '`.`', $table_parts ) . '`';
        $results = $this->get_results( "SHOW FULL COLUMNS FROM $table" );
        if ( ! $results ) {
            return new WP_Error( 'wpdb_get_table_charset_failure' );
        }

        foreach ( $results as $column ) {
            $columns[ strtolower( $column->Field ) ] = $column;
        }

        $this->col_meta[ $tablekey ] = $columns;

        foreach ( $columns as $column ) {
            if ( ! empty( $column->Collation ) ) {
                list( $charset ) = explode( '_', $column->Collation );

                // If the current connection can't support utf8mb4 characters, let's only send 3-byte utf8 characters.
                if ( 'utf8mb4' === $charset && ! $this->has_cap( 'utf8mb4' ) ) {
                    $charset = 'utf8';
                }

                $charsets[ strtolower( $charset ) ] = true;
            }

            list( $type ) = explode( '(', $column->Type );

            // A binary/blob means the whole query gets treated like this.
            if ( in_array( strtoupper( $type ), array( 'BINARY', 'VARBINARY', 'TINYBLOB', 'MEDIUMBLOB', 'BLOB', 'LONGBLOB' ) ) ) {
                $this->table_charset[ $tablekey ] = 'binary';
                return 'binary';
            }
        }

        // utf8mb3 is an alias for utf8.
        if ( isset( $charsets['utf8mb3'] ) ) {
            $charsets['utf8'] = true;
            unset( $charsets['utf8mb3'] );
        }

        // Check if we have more than one charset in play.
        $count = count( $charsets );
        if ( 1 === $count ) {
            $charset = key( $charsets );
        } elseif ( 0 === $count ) {
            // No charsets, assume this table can store whatever.
            $charset = false;
        } else {
            // More than one charset. Remove latin1 if present and recalculate.
            unset( $charsets['latin1'] );
            $count = count( $charsets );
            if ( 1 === $count ) {
                // Only one charset (besides latin1).
                $charset = key( $charsets );
            } elseif ( 2 === $count && isset( $charsets['utf8'], $charsets['utf8mb4'] ) ) {
                // Two charsets, but they're utf8 and utf8mb4, use utf8.
                $charset = 'utf8';
            } else {
                // Two mixed character sets. ascii.
                $charset = 'ascii';
            }
        }

        $this->table_charset[ $tablekey ] = $charset;
        return $charset;
    }

    /**
     * Retrieves the character set for the given column.
     *
     * @since 4.2.0
     *
     * @param string $table  Table name.
     * @param string $column Column name.
     * @return string|false|WP_Error Column character set as a string. False if the column has no
     *                               character set. WP_Error object if there was an error.
     */
    public function get_col_charset( $table, $column ) {
        $tablekey = strtolower( $table );
        $columnkey = strtolower( $column );

        /**
         * Filters the column charset value before the DB is checked.
         *
         * Passing a non-null value to the filter will short-circuit
         * checking the DB for the charset, returning that value instead.
         *
         * @since 4.2.0
         *
         * @param string $charset The character set to use. Default null.
         * @param string $table   The name of the table being checked.
         * @param string $column  The name of the column being checked.
         */
        $charset = apply_filters( 'pre_get_col_charset', null, $table, $column );
        if ( null !== $charset ) {
            return $charset;
        }

        // Skip this entirely if this isn't a MySQL database.
        if ( empty( $this->is_mysql ) ) {
            return false;
        }

        if ( empty( $this->table_charset[ $tablekey ] ) ) {
            // This primes column information for us.
            $table_charset = $this->get_table_charset( $table );
            if ( is_wp_error( $table_charset ) ) {
                return $table_charset;
            }
        }

        // If still no column information, return the table charset.
        if ( empty( $this->col_meta[ $tablekey ] ) ) {
            return $this->table_charset[ $tablekey ];
        }

        // If this column doesn't exist, return the table charset.
        if ( empty( $this->col_meta[ $tablekey ][ $columnkey ] ) ) {
            return $this->table_charset[ $tablekey ];
        }

        // Return false when it's not a string column.
        if ( empty( $this->col_meta[ $tablekey ][ $columnkey ]->Collation ) ) {
            return false;
        }

        list( $charset ) = explode( '_', $this->col_meta[ $tablekey ][ $columnkey ]->Collation );
        return $charset;
    }

    /**
     * Retrieve the maximum string length allowed in a given column.
     * The length may either be specified as a byte length or a character length.
     *
     * @since 4.2.1
     *
     * @param string $table  Table name.
     * @param string $column Column name.
     * @return array|false|WP_Error array( 'length' => (int), 'type' => 'byte' | 'char' )
     *                              false if the column has no length (for example, numeric column)
     *                              WP_Error object if there was an error.
     */
    public function get_col_length( $table, $column ) {
        $tablekey = strtolower( $table );
        $columnkey = strtolower( $column );

        // Skip this entirely if this isn't a MySQL database.
        if ( empty( $this->is_mysql ) ) {
            return false;
        }

        if ( empty( $this->col_meta[ $tablekey ] ) ) {
            // This primes column information for us.
            $table_charset = $this->get_table_charset( $table );
            if ( is_wp_error( $table_charset ) ) {
                return $table_charset;
            }
        }

        if ( empty( $this->col_meta[ $tablekey ][ $columnkey ] ) ) {
            return false;
        }

        $typeinfo = explode( '(', $this->col_meta[ $tablekey ][ $columnkey ]->Type );

        $type = strtolower( $typeinfo[0] );
        if ( ! empty( $typeinfo[1] ) ) {
            $length = trim( $typeinfo[1], ')' );
        } else {
            $length = false;
        }

        switch( $type ) {
            case 'char':
            case 'varchar':
                return array(
                    'type'   => 'char',
                    'length' => (int) $length,
                );

            case 'binary':
            case 'varbinary':
                return array(
                    'type'   => 'byte',
                    'length' => (int) $length,
                );

            case 'tinyblob':
            case 'tinytext':
                return array(
                    'type'   => 'byte',
                    'length' => 255,        // 2^8 - 1
                );

            case 'blob':
            case 'text':
                return array(
                    'type'   => 'byte',
                    'length' => 65535,      // 2^16 - 1
                );

            case 'mediumblob':
            case 'mediumtext':
                return array(
                    'type'   => 'byte',
                    'length' => 16777215,   // 2^24 - 1
                );

            case 'longblob':
            case 'longtext':
                return array(
                    'type'   => 'byte',
                    'length' => 4294967295, // 2^32 - 1
                );

            default:
                return false;
        }
    }

    /**
     * Check if a string is ASCII.
     *
     * The negative regex is faster for non-ASCII strings, as it allows
     * the search to finish as soon as it encounters a non-ASCII character.
     *
     * @since 4.2.0
     *
     * @param string $string String to check.
     * @return bool True if ASCII, false if not.
     */
    protected function check_ascii( $string ) {
        if ( function_exists( 'mb_check_encoding' ) ) {
            if ( mb_check_encoding( $string, 'ASCII' ) ) {
                return true;
            }
        } elseif ( ! preg_match( '/[^\x00-\x7F]/', $string ) ) {
            return true;
        }

        return false;
    }

    /**
     * Check if the query is accessing a collation considered safe on the current version of MySQL.
     *
     * @since 4.2.0
     *
     * @param string $query The query to check.
     * @return bool True if the collation is safe, false if it isn't.
     */
    protected function check_safe_collation( $query ) {
        if ( $this->checking_collation ) {
            return true;
        }

        // We don't need to check the collation for queries that don't read data.
        $query = ltrim( $query, "\r\n\t (" );
        if ( preg_match( '/^(?:SHOW|DESCRIBE|DESC|EXPLAIN|CREATE)\s/i', $query ) ) {
            return true;
        }

        // All-ASCII queries don't need extra checking.
        if ( $this->check_ascii( $query ) ) {
            return true;
        }

        $table = $this->get_table_from_query( $query );
        if ( ! $table ) {
            return false;
        }

        $this->checking_collation = true;
        $collation = $this->get_table_charset( $table );
        $this->checking_collation = false;

        // Tables with no collation, or latin1 only, don't need extra checking.
        if ( false === $collation || 'latin1' === $collation ) {
            return true;
        }

        $table = strtolower( $table );
        if ( empty( $this->col_meta[ $table ] ) ) {
            return false;
        }

        // If any of the columns don't have one of these collations, it needs more sanity checking.
        foreach ( $this->col_meta[ $table ] as $col ) {
            if ( empty( $col->Collation ) ) {
                continue;
            }

            if ( ! in_array( $col->Collation, array( 'utf8_general_ci', 'utf8_bin', 'utf8mb4_general_ci', 'utf8mb4_bin' ), true ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Strips any invalid characters based on value/charset pairs.
     *
     * @since 4.2.0
     *
     * @param array $data Array of value arrays. Each value array has the keys
     *                    'value' and 'charset'. An optional 'ascii' key can be
     *                    set to false to avoid redundant ASCII checks.
     * @return array|WP_Error The $data parameter, with invalid characters removed from
     *                        each value. This works as a passthrough: any additional keys
     *                        such as 'field' are retained in each value array. If we cannot
     *                        remove invalid characters, a WP_Error object is returned.
     */
    protected function strip_invalid_text( $data ) {
        $db_check_string = false;

        foreach ( $data as &$value ) {
            $charset = $value['charset'];

            if ( is_array( $value['length'] ) ) {
                $length = $value['length']['length'];
                $truncate_by_byte_length = 'byte' === $value['length']['type'];
            } else {
                $length = false;
                // Since we have no length, we'll never truncate.
                // Initialize the variable to false. true would take us
                // through an unnecessary (for this case) codepath below.
                $truncate_by_byte_length = false;
            }

            // There's no charset to work with.
            if ( false === $charset ) {
                continue;
            }

            // Column isn't a string.
            if ( ! is_string( $value['value'] ) ) {
                continue;
            }

            $needs_validation = true;
            if (
                // latin1 can store any byte sequence
                'latin1' === $charset
            ||
                // ASCII is always OK.
                ( ! isset( $value['ascii'] ) && $this->check_ascii( $value['value'] ) )
            ) {
                $truncate_by_byte_length = true;
                $needs_validation = false;
            }

            if ( $truncate_by_byte_length ) {
                mbstring_binary_safe_encoding();
                if ( false !== $length && strlen( $value['value'] ) > $length ) {
                    $value['value'] = substr( $value['value'], 0, $length );
                }
                reset_mbstring_encoding();

                if ( ! $needs_validation ) {
                    continue;
                }
            }

            // utf8 can be handled by regex, which is a bunch faster than a DB lookup.
            if ( ( 'utf8' === $charset || 'utf8mb3' === $charset || 'utf8mb4' === $charset ) && function_exists( 'mb_strlen' ) ) {
                $regex = '/
                    (
                        (?: [\x00-\x7F]                  # single-byte sequences   0xxxxxxx
                        |   [\xC2-\xDF][\x80-\xBF]       # double-byte sequences   110xxxxx 10xxxxxx
                        |   \xE0[\xA0-\xBF][\x80-\xBF]   # triple-byte sequences   1110xxxx 10xxxxxx * 2
                        |   [\xE1-\xEC][\x80-\xBF]{2}
                        |   \xED[\x80-\x9F][\x80-\xBF]
                        |   [\xEE-\xEF][\x80-\xBF]{2}';

                if ( 'utf8mb4' === $charset ) {
                    $regex .= '
                        |    \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
                        |    [\xF1-\xF3][\x80-\xBF]{3}
                        |    \xF4[\x80-\x8F][\x80-\xBF]{2}
                    ';
                }

                $regex .= '){1,40}                          # ...one or more times
                    )
                    | .                                  # anything else
                    /x';
                $value['value'] = preg_replace( $regex, '$1', $value['value'] );


                if ( false !== $length && mb_strlen( $value['value'], 'UTF-8' ) > $length ) {
                    $value['value'] = mb_substr( $value['value'], 0, $length, 'UTF-8' );
                }
                continue;
            }

            // We couldn't use any local conversions, send it to the DB.
            $value['db'] = $db_check_string = true;
        }
        unset( $value ); // Remove by reference.

        if ( $db_check_string ) {
            $queries = array();
            foreach ( $data as $col => $value ) {
                if ( ! empty( $value['db'] ) ) {
                    // We're going to need to truncate by characters or bytes, depending on the length value we have.
                    if ( 'byte' === $value['length']['type'] ) {
                        // Using binary causes LEFT() to truncate by bytes.
                        $charset = 'binary';
                    } else {
                        $charset = $value['charset'];
                    }

                    if ( $this->charset ) {
                        $connection_charset = $this->charset;
                    } else {
                        if ( $this->use_mysqli ) {
                            $connection_charset = mysqli_character_set_name( $this->dbh );
                        } else {
                            $connection_charset = mysql_client_encoding();
                        }
                    }

                    if ( is_array( $value['length'] ) ) {
                        $length = sprintf( '%.0f', $value['length']['length'] );
                        $queries[ $col ] = $this->prepare( "CONVERT( LEFT( CONVERT( %s USING $charset ), $length ) USING $connection_charset )", $value['value'] );
                    } else if ( 'binary' !== $charset ) {
                        // If we don't have a length, there's no need to convert binary - it will always return the same result.
                        $queries[ $col ] = $this->prepare( "CONVERT( CONVERT( %s USING $charset ) USING $connection_charset )", $value['value'] );
                    }

                    unset( $data[ $col ]['db'] );
                }
            }

            $sql = array();
            foreach ( $queries as $column => $query ) {
                if ( ! $query ) {
                    continue;
                }

                $sql[] = $query . " AS x_$column";
            }

            $this->check_current_query = false;
            $row = $this->get_row( "SELECT " . implode( ', ', $sql ), ARRAY_A );
            if ( ! $row ) {
                return new WP_Error( 'wpdb_strip_invalid_text_failure' );
            }

            foreach ( array_keys( $data ) as $column ) {
                if ( isset( $row["x_$column"] ) ) {
                    $data[ $column ]['value'] = $row["x_$column"];
                }
            }
        }

        return $data;
    }

    /**
     * Strips any invalid characters from the query.
     *
     * @since 4.2.0
     *
     * @param string $query Query to convert.
     * @return string|WP_Error The converted query, or a WP_Error object if the conversion fails.
     */
    protected function strip_invalid_text_from_query( $query ) {
        // We don't need to check the collation for queries that don't read data.
        $trimmed_query = ltrim( $query, "\r\n\t (" );
        if ( preg_match( '/^(?:SHOW|DESCRIBE|DESC|EXPLAIN|CREATE)\s/i', $trimmed_query ) ) {
            return $query;
        }

        $table = $this->get_table_from_query( $query );
        if ( $table ) {
            $charset = $this->get_table_charset( $table );
            if ( is_wp_error( $charset ) ) {
                return $charset;
            }

            // We can't reliably strip text from tables containing binary/blob columns
            if ( 'binary' === $charset ) {
                return $query;
            }
        } else {
            $charset = $this->charset;
        }

        $data = array(
            'value'   => $query,
            'charset' => $charset,
            'ascii'   => false,
            'length'  => false,
        );

        $data = $this->strip_invalid_text( array( $data ) );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        return $data[0]['value'];
    }

    /**
     * Strips any invalid characters from the string for a given table and column.
     *
     * @since 4.2.0
     *
     * @param string $table  Table name.
     * @param string $column Column name.
     * @param string $value  The text to check.
     * @return string|WP_Error The converted string, or a WP_Error object if the conversion fails.
     */
    public function strip_invalid_text_for_column( $table, $column, $value ) {
        if ( ! is_string( $value ) ) {
            return $value;
        }

        $charset = $this->get_col_charset( $table, $column );
        if ( ! $charset ) {
            // Not a string column.
            return $value;
        } elseif ( is_wp_error( $charset ) ) {
            // Bail on real errors.
            return $charset;
        }

        $data = array(
            $column => array(
                'value'   => $value,
                'charset' => $charset,
                'length'  => $this->get_col_length( $table, $column ),
            )
        );

        $data = $this->strip_invalid_text( $data );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        return $data[ $column ]['value'];
    }

    /**
     * Find the first table name referenced in a query.
     *
     * @since 4.2.0
     *
     * @param string $query The query to search.
     * @return string|false $table The table name found, or false if a table couldn't be found.
     */
    protected function get_table_from_query( $query ) {
        // Remove characters that can legally trail the table name.
        $query = rtrim( $query, ';/-#' );

        // Allow (select...) union [...] style queries. Use the first query's table name.
        $query = ltrim( $query, "\r\n\t (" );

        // Strip everything between parentheses except nested selects.
        $query = preg_replace( '/\((?!\s*select)[^(]*?\)/is', '()', $query );

        // Quickly match most common queries.
        if ( preg_match( '/^\s*(?:'
                . 'SELECT.*?\s+FROM'
                . '|INSERT(?:\s+LOW_PRIORITY|\s+DELAYED|\s+HIGH_PRIORITY)?(?:\s+IGNORE)?(?:\s+INTO)?'
                . '|REPLACE(?:\s+LOW_PRIORITY|\s+DELAYED)?(?:\s+INTO)?'
                . '|UPDATE(?:\s+LOW_PRIORITY)?(?:\s+IGNORE)?'
                . '|DELETE(?:\s+LOW_PRIORITY|\s+QUICK|\s+IGNORE)*(?:.+?FROM)?'
                . ')\s+((?:[0-9a-zA-Z$_.`-]|[\xC2-\xDF][\x80-\xBF])+)/is', $query, $maybe ) ) {
            return str_replace( '`', '', $maybe[1] );
        }

        // SHOW TABLE STATUS and SHOW TABLES WHERE Name = 'wp_posts'
        if ( preg_match( '/^\s*SHOW\s+(?:TABLE\s+STATUS|(?:FULL\s+)?TABLES).+WHERE\s+Name\s*=\s*("|\')((?:[0-9a-zA-Z$_.-]|[\xC2-\xDF][\x80-\xBF])+)\\1/is', $query, $maybe ) ) {
            return $maybe[2];
        }

        // SHOW TABLE STATUS LIKE and SHOW TABLES LIKE 'wp\_123\_%'
        // This quoted LIKE operand seldom holds a full table name.
        // It is usually a pattern for matching a prefix so we just
        // strip the trailing % and unescape the _ to get 'wp_123_'
        // which drop-ins can use for routing these SQL statements.
        if ( preg_match( '/^\s*SHOW\s+(?:TABLE\s+STATUS|(?:FULL\s+)?TABLES)\s+(?:WHERE\s+Name\s+)?LIKE\s*("|\')((?:[\\\\0-9a-zA-Z$_.-]|[\xC2-\xDF][\x80-\xBF])+)%?\\1/is', $query, $maybe ) ) {
            return str_replace( '\\_', '_', $maybe[2] );
        }

        // Big pattern for the rest of the table-related queries.
        if ( preg_match( '/^\s*(?:'
                . '(?:EXPLAIN\s+(?:EXTENDED\s+)?)?SELECT.*?\s+FROM'
                . '|DESCRIBE|DESC|EXPLAIN|HANDLER'
                . '|(?:LOCK|UNLOCK)\s+TABLE(?:S)?'
                . '|(?:RENAME|OPTIMIZE|BACKUP|RESTORE|CHECK|CHECKSUM|ANALYZE|REPAIR).*\s+TABLE'
                . '|TRUNCATE(?:\s+TABLE)?'
                . '|CREATE(?:\s+TEMPORARY)?\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?'
                . '|ALTER(?:\s+IGNORE)?\s+TABLE'
                . '|DROP\s+TABLE(?:\s+IF\s+EXISTS)?'
                . '|CREATE(?:\s+\w+)?\s+INDEX.*\s+ON'
                . '|DROP\s+INDEX.*\s+ON'
                . '|LOAD\s+DATA.*INFILE.*INTO\s+TABLE'
                . '|(?:GRANT|REVOKE).*ON\s+TABLE'
                . '|SHOW\s+(?:.*FROM|.*TABLE)'
                . ')\s+\(*\s*((?:[0-9a-zA-Z$_.`-]|[\xC2-\xDF][\x80-\xBF])+)\s*\)*/is', $query, $maybe ) ) {
            return str_replace( '`', '', $maybe[1] );
        }

        return false;
    }

    /**
     * Load the column metadata from the last query.
     *
     * @since 3.5.0
     *
     */
    protected function load_col_info() {
        if ( $this->col_info )
            return;

        if ( $this->use_mysqli ) {
            $num_fields = mysqli_num_fields( $this->result );
            for ( $i = 0; $i < $num_fields; $i++ ) {
                $this->col_info[ $i ] = mysqli_fetch_field( $this->result );
            }
        } else {
            $num_fields = mysql_num_fields( $this->result );
            for ( $i = 0; $i < $num_fields; $i++ ) {
                $this->col_info[ $i ] = mysql_fetch_field( $this->result, $i );
            }
        }
    }

    /**
     * Retrieve column metadata from the last query.
     *
     * @since 0.71
     *
     * @param string $info_type  Optional. Type one of name, table, def, max_length, not_null, primary_key, multiple_key, unique_key, numeric, blob, type, unsigned, zerofill
     * @param int    $col_offset Optional. 0: col name. 1: which table the col's in. 2: col's max length. 3: if the col is numeric. 4: col's type
     * @return mixed Column Results
     */
    public function get_col_info( $info_type = 'name', $col_offset = -1 ) {
        $this->load_col_info();

        if ( $this->col_info ) {
            if ( $col_offset == -1 ) {
                $i = 0;
                $new_array = array();
                foreach ( (array) $this->col_info as $col ) {
                    $new_array[$i] = $col->{$info_type};
                    $i++;
                }
                return $new_array;
            } else {
                return $this->col_info[$col_offset]->{$info_type};
            }
        }
    }

    /**
     * Starts the timer, for debugging purposes.
     *
     * @since 1.5.0
     *
     * @return true
     */
    public function timer_start() {
        $this->time_start = microtime( true );
        return true;
    }

    /**
     * Stops the debugging timer.
     *
     * @since 1.5.0
     *
     * @return float Total time spent on the query, in seconds
     */
    public function timer_stop() {
        return ( microtime( true ) - $this->time_start );
    }

    /**
     * Wraps errors in a nice header and footer and dies.
     *
     * Will not die if wpdb::$show_errors is false.
     *
     * @since 1.5.0
     *
     * @param string $message    The Error message
     * @param string $error_code Optional. A Computer readable string to identify the error.
     * @return false|void
     */
    public function bail( $message, $error_code = '500' ) {
        if ( !$this->show_errors ) {
            if ( class_exists( 'WP_Error', false ) ) {
                $this->error = new WP_Error($error_code, $message);
            } else {
                $this->error = $message;
            }
            return false;
        }
        wp_die($message);
    }


    /**
     * Closes the current database connection.
     *
     * @since 4.5.0
     *
     * @return bool True if the connection was successfully closed, false if it wasn't,
     *              or the connection doesn't exist.
     */
    public function close() {
        if ( ! $this->dbh ) {
            return false;
        }

        if ( $this->use_mysqli ) {
            $closed = mysqli_close( $this->dbh );
        } else {
            $closed = mysql_close( $this->dbh );
        }

        if ( $closed ) {
            $this->dbh = null;
            $this->ready = false;
            $this->has_connected = false;
        }

        return $closed;
    }

    /**
     * Whether MySQL database is at least the required minimum version.
     *
     * @since 2.5.0
     *
     * @global string $wp_version
     * @global string $required_mysql_version
     *
     * @return WP_Error|void
     */
    public function check_database_version() {
        global $wp_version, $required_mysql_version;
        // Make sure the server has the required MySQL version
        if ( version_compare($this->db_version(), $required_mysql_version, '<') ) {
            /* translators: 1: WordPress version number, 2: Minimum required MySQL version number */
            return new WP_Error('database_version', sprintf( __( '<strong>ERROR</strong>: WordPress %1$s requires MySQL %2$s or higher' ), $wp_version, $required_mysql_version ));
        }
    }

    /**
     * Whether the database supports collation.
     *
     * Called when WordPress is generating the table scheme.
     *
     * Use `wpdb::has_cap( 'collation' )`.
     *
     * @since 2.5.0
     * @deprecated 3.5.0 Use wpdb::has_cap()
     *
     * @return bool True if collation is supported, false if version does not
     */
    public function supports_collation() {
        _deprecated_function( __FUNCTION__, '3.5.0', 'wpdb::has_cap( \'collation\' )' );
        return $this->has_cap( 'collation' );
    }

    /**
     * The database character collate.
     *
     * @since 3.5.0
     *
     * @return string The database character collate.
     */
    public function get_charset_collate() {
        $charset_collate = '';

        if ( ! empty( $this->charset ) )
            $charset_collate = "DEFAULT CHARACTER SET $this->charset";
        if ( ! empty( $this->collate ) )
            $charset_collate .= " COLLATE $this->collate";

        return $charset_collate;
    }

    /**
     * Determine if a database supports a particular feature.
     *
     * @since 2.7.0
     * @since 4.1.0 Added support for the 'utf8mb4' feature.
     * @since 4.6.0 Added support for the 'utf8mb4_520' feature.
     *
     * @see wpdb::db_version()
     *
     * @param string $db_cap The feature to check for. Accepts 'collation',
     *                       'group_concat', 'subqueries', 'set_charset',
     *                       'utf8mb4', or 'utf8mb4_520'.
     * @return int|false Whether the database feature is supported, false otherwise.
     */
    public function has_cap( $db_cap ) {
        $version = $this->db_version();

        switch ( strtolower( $db_cap ) ) {
            case 'collation' :    // @since 2.5.0
            case 'group_concat' : // @since 2.7.0
            case 'subqueries' :   // @since 2.7.0
                return version_compare( $version, '4.1', '>=' );
            case 'set_charset' :
                return version_compare( $version, '5.0.7', '>=' );
            case 'utf8mb4' :      // @since 4.1.0
                if ( version_compare( $version, '5.5.3', '<' ) ) {
                    return false;
                }
                if ( $this->use_mysqli ) {
                    $client_version = mysqli_get_client_info();
                } else {
                    $client_version = mysql_get_client_info();
                }

                /*
                 * libmysql has supported utf8mb4 since 5.5.3, same as the MySQL server.
                 * mysqlnd has supported utf8mb4 since 5.0.9.
                 */
                if ( false !== strpos( $client_version, 'mysqlnd' ) ) {
                    $client_version = preg_replace( '/^\D+([\d.]+).*/', '$1', $client_version );
                    return version_compare( $client_version, '5.0.9', '>=' );
                } else {
                    return version_compare( $client_version, '5.5.3', '>=' );
                }
            case 'utf8mb4_520' : // @since 4.6.0
                return version_compare( $version, '5.6', '>=' );
        }

        return false;
    }

    /**
     * Retrieve the name of the function that called wpdb.
     *
     * Searches up the list of functions until it reaches
     * the one that would most logically had called this method.
     *
     * @since 2.5.0
     *
     * @return string|array The name of the calling function
     */
    public function get_caller() {
        return wp_debug_backtrace_summary( __CLASS__ );
    }

    /**
     * Retrieves the MySQL server version.
     *
     * @since 2.7.0
     *
     * @return null|string Null on failure, version number on success.
     */
    public function db_version() {
        if ( $this->use_mysqli ) {
            $server_info = mysqli_get_server_info( $this->dbh );
        } else {
            $server_info = mysql_get_server_info( $this->dbh );
        }
        return preg_replace( '/[^0-9.].*/', '', $server_info );
    }
}


class WP_Object_Cache {

    /**
     * Holds the cached objects.
     *
     * @since 2.0.0
     * @var array
     */
    private $cache = array();

    /**
     * The amount of times the cache data was already stored in the cache.
     *
     * @since 2.5.0
     * @var int
     */
    public $cache_hits = 0;

    /**
     * Amount of times the cache did not have the request in cache.
     *
     * @since 2.0.0
     * @var int
     */
    public $cache_misses = 0;

    /**
     * List of global cache groups.
     *
     * @since 3.0.0
     * @var array
     */
    protected $global_groups = array();

    /**
     * The blog prefix to prepend to keys in non-global groups.
     *
     * @since 3.5.0
     * @var int
     */
    private $blog_prefix;

    /**
     * Holds the value of is_multisite().
     *
     * @since 3.5.0
     * @var bool
     */
    private $multisite;

    /**
     * Makes private properties readable for backward compatibility.
     *
     * @since 4.0.0
     *
     * @param string $name Property to get.
     * @return mixed Property.
     */
    public function __get( $name ) {
        return $this->$name;
    }

    /**
     * Makes private properties settable for backward compatibility.
     *
     * @since 4.0.0
     *
     * @param string $name  Property to set.
     * @param mixed  $value Property value.
     * @return mixed Newly-set property.
     */
    public function __set( $name, $value ) {
        return $this->$name = $value;
    }

    /**
     * Makes private properties checkable for backward compatibility.
     *
     * @since 4.0.0
     *
     * @param string $name Property to check if set.
     * @return bool Whether the property is set.
     */
    public function __isset( $name ) {
        return isset( $this->$name );
    }

    /**
     * Makes private properties un-settable for backward compatibility.
     *
     * @since 4.0.0
     *
     * @param string $name Property to unset.
     */
    public function __unset( $name ) {
        unset( $this->$name );
    }

    /**
     * Adds data to the cache if it doesn't already exist.
     *
     * @since 2.0.0
     *
     * @uses WP_Object_Cache::_exists() Checks to see if the cache already has data.
     * @uses WP_Object_Cache::set()     Sets the data after the checking the cache
     *                                  contents existence.
     *
     * @param int|string $key    What to call the contents in the cache.
     * @param mixed      $data   The contents to store in the cache.
     * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
     * @param int        $expire Optional. When to expire the cache contents. Default 0 (no expiration).
     * @return bool False if cache key and group already exist, true on success
     */
    public function add( $key, $data, $group = 'default', $expire = 0 ) {
        if ( wp_suspend_cache_addition() )
            return false;

        if ( empty( $group ) )
            $group = 'default';

        $id = $key;
        if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) )
            $id = $this->blog_prefix . $key;

        if ( $this->_exists( $id, $group ) )
            return false;

        return $this->set( $key, $data, $group, (int) $expire );
    }

    /**
     * Sets the list of global cache groups.
     *
     * @since 3.0.0
     *
     * @param array $groups List of groups that are global.
     */
    public function add_global_groups( $groups ) {
        $groups = (array) $groups;

        $groups = array_fill_keys( $groups, true );
        $this->global_groups = array_merge( $this->global_groups, $groups );
    }

    /**
     * Decrements numeric cache item's value.
     *
     * @since 3.3.0
     *
     * @param int|string $key    The cache key to decrement.
     * @param int        $offset Optional. The amount by which to decrement the item's value. Default 1.
     * @param string     $group  Optional. The group the key is in. Default 'default'.
     * @return false|int False on failure, the item's new value on success.
     */
    public function decr( $key, $offset = 1, $group = 'default' ) {
        if ( empty( $group ) )
            $group = 'default';

        if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) )
            $key = $this->blog_prefix . $key;

        if ( ! $this->_exists( $key, $group ) )
            return false;

        if ( ! is_numeric( $this->cache[ $group ][ $key ] ) )
            $this->cache[ $group ][ $key ] = 0;

        $offset = (int) $offset;

        $this->cache[ $group ][ $key ] -= $offset;

        if ( $this->cache[ $group ][ $key ] < 0 )
            $this->cache[ $group ][ $key ] = 0;

        return $this->cache[ $group ][ $key ];
    }

    /**
     * Removes the contents of the cache key in the group.
     *
     * If the cache key does not exist in the group, then nothing will happen.
     *
     * @since 2.0.0
     *
     * @param int|string $key        What the contents in the cache are called.
     * @param string     $group      Optional. Where the cache contents are grouped. Default 'default'.
     * @param bool       $deprecated Optional. Unused. Default false.
     * @return bool False if the contents weren't deleted and true on success.
     */
    public function delete( $key, $group = 'default', $deprecated = false ) {
        if ( empty( $group ) )
            $group = 'default';

        if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) )
            $key = $this->blog_prefix . $key;

        if ( ! $this->_exists( $key, $group ) )
            return false;

        unset( $this->cache[$group][$key] );
        return true;
    }

    /**
     * Clears the object cache of all data.
     *
     * @since 2.0.0
     *
     * @return true Always returns true.
     */
    public function flush() {
        $this->cache = array();

        return true;
    }

    /**
     * Retrieves the cache contents, if it exists.
     *
     * The contents will be first attempted to be retrieved by searching by the
     * key in the cache group. If the cache is hit (success) then the contents
     * are returned.
     *
     * On failure, the number of cache misses will be incremented.
     *
     * @since 2.0.0
     *
     * @param int|string $key    What the contents in the cache are called.
     * @param string     $group  Optional. Where the cache contents are grouped. Default 'default'.
     * @param string     $force  Optional. Unused. Whether to force a refetch rather than relying on the local
     *                           cache. Default false.
     * @param bool        $found  Optional. Whether the key was found in the cache (passed by reference).
     *                            Disambiguates a return of false, a storable value. Default null.
     * @return false|mixed False on failure to retrieve contents or the cache contents on success.
     */
    public function get( $key, $group = 'default', $force = false, &$found = null ) {
        if ( empty( $group ) )
            $group = 'default';

        if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) )
            $key = $this->blog_prefix . $key;

        if ( $this->_exists( $key, $group ) ) {
            $found = true;
            $this->cache_hits += 1;
            if ( is_object($this->cache[$group][$key]) )
                return clone $this->cache[$group][$key];
            else
                return $this->cache[$group][$key];
        }

        $found = false;
        $this->cache_misses += 1;
        return false;
    }

    /**
     * Increments numeric cache item's value.
     *
     * @since 3.3.0
     *
     * @param int|string $key    The cache key to increment
     * @param int        $offset Optional. The amount by which to increment the item's value. Default 1.
     * @param string     $group  Optional. The group the key is in. Default 'default'.
     * @return false|int False on failure, the item's new value on success.
     */
    public function incr( $key, $offset = 1, $group = 'default' ) {
        if ( empty( $group ) )
            $group = 'default';

        if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) )
            $key = $this->blog_prefix . $key;

        if ( ! $this->_exists( $key, $group ) )
            return false;

        if ( ! is_numeric( $this->cache[ $group ][ $key ] ) )
            $this->cache[ $group ][ $key ] = 0;

        $offset = (int) $offset;

        $this->cache[ $group ][ $key ] += $offset;

        if ( $this->cache[ $group ][ $key ] < 0 )
            $this->cache[ $group ][ $key ] = 0;

        return $this->cache[ $group ][ $key ];
    }

    /**
     * Replaces the contents in the cache, if contents already exist.
     *
     * @since 2.0.0
     *
     * @see WP_Object_Cache::set()
     *
     * @param int|string $key    What to call the contents in the cache.
     * @param mixed      $data   The contents to store in the cache.
     * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
     * @param int        $expire Optional. When to expire the cache contents. Default 0 (no expiration).
     * @return bool False if not exists, true if contents were replaced.
     */
    public function replace( $key, $data, $group = 'default', $expire = 0 ) {
        if ( empty( $group ) )
            $group = 'default';

        $id = $key;
        if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) )
            $id = $this->blog_prefix . $key;

        if ( ! $this->_exists( $id, $group ) )
            return false;

        return $this->set( $key, $data, $group, (int) $expire );
    }

    /**
     * Resets cache keys.
     *
     * @since 3.0.0
     *
     * @deprecated 3.5.0 Use switch_to_blog()
     * @see switch_to_blog()
     */
    public function reset() {
        _deprecated_function( __FUNCTION__, '3.5.0', 'switch_to_blog()' );

        // Clear out non-global caches since the blog ID has changed.
        foreach ( array_keys( $this->cache ) as $group ) {
            if ( ! isset( $this->global_groups[ $group ] ) )
                unset( $this->cache[ $group ] );
        }
    }

    /**
     * Sets the data contents into the cache.
     *
     * The cache contents is grouped by the $group parameter followed by the
     * $key. This allows for duplicate ids in unique groups. Therefore, naming of
     * the group should be used with care and should follow normal function
     * naming guidelines outside of core WordPress usage.
     *
     * The $expire parameter is not used, because the cache will automatically
     * expire for each time a page is accessed and PHP finishes. The method is
     * more for cache plugins which use files.
     *
     * @since 2.0.0
     *
     * @param int|string $key    What to call the contents in the cache.
     * @param mixed      $data   The contents to store in the cache.
     * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
     * @param int        $expire Not Used.
     * @return true Always returns true.
     */
    public function set( $key, $data, $group = 'default', $expire = 0 ) {
        if ( empty( $group ) )
            $group = 'default';

        if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) )
            $key = $this->blog_prefix . $key;

        if ( is_object( $data ) )
            $data = clone $data;

        $this->cache[$group][$key] = $data;
        return true;
    }

    /**
     * Echoes the stats of the caching.
     *
     * Gives the cache hits, and cache misses. Also prints every cached group,
     * key and the data.
     *
     * @since 2.0.0
     */
    public function stats() {
        echo "<p>";
        echo "<strong>Cache Hits:</strong> {$this->cache_hits}<br />";
        echo "<strong>Cache Misses:</strong> {$this->cache_misses}<br />";
        echo "</p>";
        echo '<ul>';
        foreach ($this->cache as $group => $cache) {
            echo "<li><strong>Group:</strong> $group - ( " . number_format( strlen( serialize( $cache ) ) / KB_IN_BYTES, 2 ) . 'k )</li>';
        }
        echo '</ul>';
    }

    /**
     * Switches the internal blog ID.
     *
     * This changes the blog ID used to create keys in blog specific groups.
     *
     * @since 3.5.0
     *
     * @param int $blog_id Blog ID.
     */
    public function switch_to_blog( $blog_id ) {
        $blog_id = (int) $blog_id;
        $this->blog_prefix = $this->multisite ? $blog_id . ':' : '';
    }

    /**
     * Serves as a utility function to determine whether a key exists in the cache.
     *
     * @since 3.4.0
     *
     * @param int|string $key   Cache key to check for existence.
     * @param string     $group Cache group for the key existence check.
     * @return bool Whether the key exists in the cache for the given group.
     */
    protected function _exists( $key, $group ) {
        return isset( $this->cache[ $group ] ) && ( isset( $this->cache[ $group ][ $key ] ) || array_key_exists( $key, $this->cache[ $group ] ) );
    }

    /**
     * Sets up object properties; PHP 5 style constructor.
     *
     * @since 2.0.8
     */
    public function __construct() {
        $this->multisite = is_multisite();
        $this->blog_prefix =  $this->multisite ? get_current_blog_id() . ':' : '';


        /**
         * @todo This should be moved to the PHP4 style constructor, PHP5
         * already calls __destruct()
         */
        register_shutdown_function( array( $this, '__destruct' ) );
    }

    /**
     * Saves the object cache before object is completely destroyed.
     *
     * Called upon object destruction, which should be when PHP ends.
     *
     * @since 2.0.8
     *
     * @return true Always returns true.
     */
    public function __destruct() {
        return true;
    }
}

final class WP_Hook implements Iterator, ArrayAccess {

    /**
     * Hook callbacks.
     *
     * @since 4.7.0
     * @var array
     */
    public $callbacks = array();

    /**
     * The priority keys of actively running iterations of a hook.
     *
     * @since 4.7.0
     * @var array
     */
    private $iterations = array();

    /**
     * The current priority of actively running iterations of a hook.
     *
     * @since 4.7.0
     * @var array
     */
    private $current_priority = array();

    /**
     * Number of levels this hook can be recursively called.
     *
     * @since 4.7.0
     * @var int
     */
    private $nesting_level = 0;

    /**
     * Flag for if we're current doing an action, rather than a filter.
     *
     * @since 4.7.0
     * @var bool
     */
    private $doing_action = false;

    /**
     * Hooks a function or method to a specific filter action.
     *
     * @since 4.7.0
     *
     * @param string   $tag             The name of the filter to hook the $function_to_add callback to.
     * @param callable $function_to_add The callback to be run when the filter is applied.
     * @param int      $priority        The order in which the functions associated with a
     *                                  particular action are executed. Lower numbers correspond with
     *                                  earlier execution, and functions with the same priority are executed
     *                                  in the order in which they were added to the action.
     * @param int      $accepted_args   The number of arguments the function accepts.
     */
    public function add_filter( $tag, $function_to_add, $priority, $accepted_args ) {
        $idx = _wp_filter_build_unique_id( $tag, $function_to_add, $priority );
        $priority_existed = isset( $this->callbacks[ $priority ] );

        $this->callbacks[ $priority ][ $idx ] = array(
            'function' => $function_to_add,
            'accepted_args' => $accepted_args
        );

        // if we're adding a new priority to the list, put them back in sorted order
        if ( ! $priority_existed && count( $this->callbacks ) > 1 ) {
            ksort( $this->callbacks, SORT_NUMERIC );
        }

        if ( $this->nesting_level > 0 ) {
            $this->resort_active_iterations( $priority, $priority_existed );
        }
    }

    /**
     * Handles reseting callback priority keys mid-iteration.
     *
     * @since 4.7.0
     *
     * @param bool|int $new_priority     Optional. The priority of the new filter being added. Default false,
     *                                   for no priority being added.
     * @param bool     $priority_existed Optional. Flag for whether the priority already existed before the new
     *                                   filter was added. Default false.
     */
    private function resort_active_iterations( $new_priority = false, $priority_existed = false ) {
        $new_priorities = array_keys( $this->callbacks );

        // If there are no remaining hooks, clear out all running iterations.
        if ( ! $new_priorities ) {
            foreach ( $this->iterations as $index => $iteration ) {
                $this->iterations[ $index ] = $new_priorities;
            }
            return;
        }

        $min = min( $new_priorities );
        foreach ( $this->iterations as $index => &$iteration ) {
            $current = current( $iteration );
            // If we're already at the end of this iteration, just leave the array pointer where it is.
            if ( false === $current ) {
                continue;
            }

            $iteration = $new_priorities;

            if ( $current < $min ) {
                array_unshift( $iteration, $current );
                continue;
            }

            while ( current( $iteration ) < $current ) {
                if ( false === next( $iteration ) ) {
                    break;
                }
            }

            // If we have a new priority that didn't exist, but ::apply_filters() or ::do_action() thinks it's the current priority...
            if ( $new_priority === $this->current_priority[ $index ] && ! $priority_existed ) {
                /*
                 * ... and the new priority is the same as what $this->iterations thinks is the previous
                 * priority, we need to move back to it.
                 */

                if ( false === current( $iteration ) ) {
                    // If we've already moved off the end of the array, go back to the last element.
                    $prev = end( $iteration );
                } else {
                    // Otherwise, just go back to the previous element.
                    $prev = prev( $iteration );
                }
                if ( false === $prev ) {
                    // Start of the array. Reset, and go about our day.
                    reset( $iteration );
                } elseif ( $new_priority !== $prev ) {
                    // Previous wasn't the same. Move forward again.
                    next( $iteration );
                }
            }
        }
        unset( $iteration );
    }

    /**
     * Unhooks a function or method from a specific filter action.
     *
     * @since 4.7.0
     *
     * @param string   $tag                The filter hook to which the function to be removed is hooked. Used
     *                                     for building the callback ID when SPL is not available.
     * @param callable $function_to_remove The callback to be removed from running when the filter is applied.
     * @param int      $priority           The exact priority used when adding the original filter callback.
     * @return bool Whether the callback existed before it was removed.
     */
    public function remove_filter( $tag, $function_to_remove, $priority ) {
        $function_key = _wp_filter_build_unique_id( $tag, $function_to_remove, $priority );

        $exists = isset( $this->callbacks[ $priority ][ $function_key ] );
        if ( $exists ) {
            unset( $this->callbacks[ $priority ][ $function_key ] );
            if ( ! $this->callbacks[ $priority ] ) {
                unset( $this->callbacks[ $priority ] );
                if ( $this->nesting_level > 0 ) {
                    $this->resort_active_iterations();
                }
            }
        }
        return $exists;
    }

    /**
     * Checks if a specific action has been registered for this hook.
     *
     * @since 4.7.0
     *
     * @param string        $tag               Optional. The name of the filter hook. Used for building
     *                                         the callback ID when SPL is not available. Default empty.
     * @param callable|bool $function_to_check Optional. The callback to check for. Default false.
     * @return bool|int The priority of that hook is returned, or false if the function is not attached.
     */
    public function has_filter( $tag = '', $function_to_check = false ) {
        if ( false === $function_to_check ) {
            return $this->has_filters();
        }

        $function_key = _wp_filter_build_unique_id( $tag, $function_to_check, false );
        if ( ! $function_key ) {
            return false;
        }

        foreach ( $this->callbacks as $priority => $callbacks ) {
            if ( isset( $callbacks[ $function_key ] ) ) {
                return $priority;
            }
        }

        return false;
    }

    /**
     * Checks if any callbacks have been registered for this hook.
     *
     * @since 4.7.0
     *
     * @return bool True if callbacks have been registered for the current hook, otherwise false.
     */
    public function has_filters() {
        foreach ( $this->callbacks as $callbacks ) {
            if ( $callbacks ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Removes all callbacks from the current filter.
     *
     * @since 4.7.0
     *
     * @param int|bool $priority Optional. The priority number to remove. Default false.
     */
    public function remove_all_filters( $priority = false ) {
        if ( ! $this->callbacks ) {
            return;
        }

        if ( false === $priority ) {
            $this->callbacks = array();
        } else if ( isset( $this->callbacks[ $priority ] ) ) {
            unset( $this->callbacks[ $priority ] );
        }

        if ( $this->nesting_level > 0 ) {
            $this->resort_active_iterations();
        }
    }

    /**
     * Calls the callback functions added to a filter hook.
     *
     * @since 4.7.0
     *
     * @param mixed $value The value to filter.
     * @param array $args  Arguments to pass to callbacks.
     * @return mixed The filtered value after all hooked functions are applied to it.
     */
    public function apply_filters( $value, $args ) {
        if ( ! $this->callbacks ) {
            return $value;
        }

        $nesting_level = $this->nesting_level++;

        $this->iterations[ $nesting_level ] = array_keys( $this->callbacks );
        $num_args = count( $args );

        do {
            $this->current_priority[ $nesting_level ] = $priority = current( $this->iterations[ $nesting_level ] );

            foreach ( $this->callbacks[ $priority ] as $the_ ) {
                if( ! $this->doing_action ) {
                    $args[ 0 ] = $value;
                }

                // Avoid the array_slice if possible.
                if ( $the_['accepted_args'] == 0 ) {
                    $value = call_user_func_array( $the_['function'], array() );
                } elseif ( $the_['accepted_args'] >= $num_args ) {
                    $value = call_user_func_array( $the_['function'], $args );
                } else {
                    $value = call_user_func_array( $the_['function'], array_slice( $args, 0, (int)$the_['accepted_args'] ) );
                }
            }
        } while ( false !== next( $this->iterations[ $nesting_level ] ) );

        unset( $this->iterations[ $nesting_level ] );
        unset( $this->current_priority[ $nesting_level ] );

        $this->nesting_level--;

        return $value;
    }

    /**
     * Executes the callback functions hooked on a specific action hook.
     *
     * @since 4.7.0
     *
     * @param mixed $args Arguments to pass to the hook callbacks.
     */
    public function do_action( $args ) {
        $this->doing_action = true;
        $this->apply_filters( '', $args );

        // If there are recursive calls to the current action, we haven't finished it until we get to the last one.
        if ( ! $this->nesting_level ) {
            $this->doing_action = false;
        }
    }

    /**
     * Processes the functions hooked into the 'all' hook.
     *
     * @since 4.7.0
     *
     * @param array $args Arguments to pass to the hook callbacks. Passed by reference.
     */
    public function do_all_hook( &$args ) {
        $nesting_level = $this->nesting_level++;
        $this->iterations[ $nesting_level ] = array_keys( $this->callbacks );

        do {
            $priority = current( $this->iterations[ $nesting_level ] );
            foreach ( $this->callbacks[ $priority ] as $the_ ) {
                call_user_func_array( $the_['function'], $args );
            }
        } while ( false !== next( $this->iterations[ $nesting_level ] ) );

        unset( $this->iterations[ $nesting_level ] );
        $this->nesting_level--;
    }

    /**
     * Return the current priority level of the currently running iteration of the hook.
     *
     * @since 4.7.0
     *
     * @return int|false If the hook is running, return the current priority level. If it isn't running, return false.
     */
    public function current_priority() {
        if ( false === current( $this->iterations ) ) {
            return false;
        }

        return current( current( $this->iterations ) );
    }

    /**
     * Normalizes filters set up before WordPress has initialized to WP_Hook objects.
     *
     * @since 4.7.0
     * @static
     *
     * @param array $filters Filters to normalize.
     * @return WP_Hook[] Array of normalized filters.
     */
    public static function build_preinitialized_hooks( $filters ) {
        /** @var WP_Hook[] $normalized */
        $normalized = array();

        foreach ( $filters as $tag => $callback_groups ) {
            if ( is_object( $callback_groups ) && $callback_groups instanceof WP_Hook ) {
                $normalized[ $tag ] = $callback_groups;
                continue;
            }
            $hook = new WP_Hook();

            // Loop through callback groups.
            foreach ( $callback_groups as $priority => $callbacks ) {

                // Loop through callbacks.
                foreach ( $callbacks as $cb ) {
                    $hook->add_filter( $tag, $cb['function'], $priority, $cb['accepted_args'] );
                }
            }
            $normalized[ $tag ] = $hook;
        }
        return $normalized;
    }

    /**
     * Determines whether an offset value exists.
     *
     * @since 4.7.0
     *
     * @link https://secure.php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset An offset to check for.
     * @return bool True if the offset exists, false otherwise.
     */
    public function offsetExists( $offset ) {
        return isset( $this->callbacks[ $offset ] );
    }

    /**
     * Retrieves a value at a specified offset.
     *
     * @since 4.7.0
     *
     * @link https://secure.php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset The offset to retrieve.
     * @return mixed If set, the value at the specified offset, null otherwise.
     */
    public function offsetGet( $offset ) {
        return isset( $this->callbacks[ $offset ] ) ? $this->callbacks[ $offset ] : null;
    }

    /**
     * Sets a value at a specified offset.
     *
     * @since 4.7.0
     *
     * @link https://secure.php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     */
    public function offsetSet( $offset, $value ) {
        if ( is_null( $offset ) ) {
            $this->callbacks[] = $value;
        } else {
            $this->callbacks[ $offset ] = $value;
        }
    }

    /**
     * Unsets a specified offset.
     *
     * @since 4.7.0
     *
     * @link https://secure.php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset The offset to unset.
     */
    public function offsetUnset( $offset ) {
        unset( $this->callbacks[ $offset ] );
    }

    /**
     * Returns the current element.
     *
     * @since 4.7.0
     *
     * @link https://secure.php.net/manual/en/iterator.current.php
     *
     * @return array Of callbacks at current priority.
     */
    public function current() {
        return current( $this->callbacks );
    }

    /**
     * Moves forward to the next element.
     *
     * @since 4.7.0
     *
     * @link https://secure.php.net/manual/en/iterator.next.php
     *
     * @return array Of callbacks at next priority.
     */
    public function next() {
        return next( $this->callbacks );
    }

    /**
     * Returns the key of the current element.
     *
     * @since 4.7.0
     *
     * @link https://secure.php.net/manual/en/iterator.key.php
     *
     * @return mixed Returns current priority on success, or NULL on failure
     */
    public function key() {
        return key( $this->callbacks );
    }

    /**
     * Checks if current position is valid.
     *
     * @since 4.7.0
     *
     * @link https://secure.php.net/manual/en/iterator.valid.php
     *
     * @return boolean
     */
    public function valid() {
        return key( $this->callbacks ) !== null;
    }

    /**
     * Rewinds the Iterator to the first element.
     *
     * @since 4.7.0
     *
     * @link https://secure.php.net/manual/en/iterator.rewind.php
     */
    public function rewind() {
        reset( $this->callbacks );
    }

}
?>
