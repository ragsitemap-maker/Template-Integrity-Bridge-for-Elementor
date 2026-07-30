<?php
/**
 * Isolated smoke and behavior checks. Run with: php tests/run.php
 */

define( 'ABSPATH', __DIR__ );

$test_state = array(
	'queried_object'        => null,
	'translated_taxonomies' => array( 'category', 'product_cat' ),
	'term_translations'     => array(
		10 => array(
			'en' => 10,
			'zh' => 20,
			'de' => 30,
			'ja' => 40,
		),
		11 => array(
			'en' => 11,
			'zh' => 21,
		),
	),
	'terms'                 => array(),
	'translation_calls'     => array(),
	'languages_list_calls'  => 0,
	'get_term_calls'        => 0,
	'options'               => array(),
	'filters'               => array(),
	'actions'               => array(),
	'is_admin'              => false,
	'registered_settings'   => array(),
	'settings_sections'     => array(),
	'settings_fields'       => array(),
);

class WP_Term {
	public $term_id;
	public $taxonomy;
	public $parent;

	public function __construct( $term_id, $taxonomy, $parent = 0 ) {
		$this->term_id  = $term_id;
		$this->taxonomy = $taxonomy;
		$this->parent   = $parent;
	}
}

class WP_Error {}

function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
	global $test_state;

	$callback_key = is_array( $callback )
		? implode( '::', array_map( 'strval', $callback ) )
		: (string) $callback;

	$test_state['filters'][ $hook_name ][ $priority ][ $callback_key ] = array(
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

function get_option( $option_name, $default = false ) {
	global $test_state;

	return array_key_exists( $option_name, $test_state['options'] )
		? $test_state['options'][ $option_name ]
		: $default;
}

function is_admin() {
	global $test_state;

	return $test_state['is_admin'];
}

function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
	global $test_state;

	$callback_key = is_array( $callback )
		? implode( '::', array_map( 'strval', $callback ) )
		: (string) $callback;

	$test_state['actions'][ $hook_name ][ $priority ][ $callback_key ] = array(
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

function plugin_basename( $file ) {
	return basename( $file );
}

function esc_html__( $text, $domain ) {
	unset( $domain );

	return $text;
}

function register_setting( $group, $option_name, $args ) {
	global $test_state;

	$test_state['registered_settings'][ $option_name ] = array(
		'group' => $group,
		'args'  => $args,
	);
}

function add_settings_section( $section_id, $title, $callback, $page ) {
	global $test_state;

	$test_state['settings_sections'][ $section_id ] = compact( 'title', 'callback', 'page' );
}

function add_settings_field( $field_id, $title, $callback, $page, $section ) {
	global $test_state;

	$test_state['settings_fields'][ $field_id ] = compact( 'title', 'callback', 'page', 'section' );
}

function absint( $value ) {
	return abs( (int) $value );
}

function sanitize_key( $key ) {
	return strtolower( preg_replace( '/[^a-z0-9_\\-]/', '', (string) $key ) );
}

function taxonomy_exists( $taxonomy ) {
	return in_array( $taxonomy, array( 'category', 'post_tag', 'product_cat' ), true );
}

function pll_is_translated_taxonomy( $taxonomy ) {
	global $test_state;

	return in_array( $taxonomy, $test_state['translated_taxonomies'], true );
}

function pll_languages_list( $args = array() ) {
	global $test_state;

	unset( $args );
	++$test_state['languages_list_calls'];

	return array( 'en', 'zh', 'de', 'ja' );
}

function pll_get_term_translations( $term_id ) {
	global $test_state;

	if ( ! isset( $test_state['translation_calls'][ $term_id ] ) ) {
		$test_state['translation_calls'][ $term_id ] = 0;
	}

	++$test_state['translation_calls'][ $term_id ];

	foreach ( $test_state['term_translations'] as $translations ) {
		if ( in_array( (int) $term_id, $translations, true ) ) {
			return $translations;
		}
	}

	return array();
}

function get_term( $term_id, $taxonomy ) {
	global $test_state;

	++$test_state['get_term_calls'];
	$key = $taxonomy . ':' . (int) $term_id;

	return isset( $test_state['terms'][ $key ] )
		? $test_state['terms'][ $key ]
		: new WP_Error();
}

function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

function get_queried_object() {
	global $test_state;

	return $test_state['queried_object'];
}

require dirname( __DIR__ ) . '/polylang-elementor-archive-bridge/polylang-elementor-archive-bridge.php';

use Polylang_Elementor_Archive_Bridge\Plugin;

function assert_same( $expected, $actual, $label ) {
	if ( $expected !== $actual ) {
		fwrite(
			STDERR,
			sprintf(
				"FAIL: %s (expected %s, got %s)\n",
				$label,
				var_export( $expected, true ),
				var_export( $actual, true )
			)
		);
		exit( 1 );
	}

	echo "PASS: {$label}\n";
}

function is_filter_registered( $hook_name ) {
	global $test_state;

	return ! empty( $test_state['filters'][ $hook_name ] );
}

function is_action_registered( $hook_name ) {
	global $test_state;

	return ! empty( $test_state['actions'][ $hook_name ] );
}

function registered_callback_count( $registry, $hook_name ) {
	if ( empty( $registry[ $hook_name ] ) ) {
		return 0;
	}

	$count = 0;

	foreach ( $registry[ $hook_name ] as $callbacks ) {
		$count += count( $callbacks );
	}

	return $count;
}

assert_same(
	false,
	Plugin::is_conditions_cache_protection_enabled(),
	'missing cache-protection option defaults to disabled'
);
assert_same(
	false,
	is_filter_registered( 'elementor/theme/conditions/cache/regenerate/query_args' ),
	'cache-protection filter is not registered by default'
);

$test_state['options'][ Plugin::OPTION_CACHE_PROTECTION ] = 'invalid';
assert_same(
	false,
	Plugin::is_conditions_cache_protection_enabled(),
	'malformed cache-protection option remains disabled'
);

$test_state['options'][ Plugin::OPTION_CACHE_PROTECTION ] = '1';
assert_same(
	true,
	Plugin::is_conditions_cache_protection_enabled(),
	'checked cache-protection option is enabled'
);

Plugin::boot();
assert_same(
	true,
	is_filter_registered( 'elementor/theme/conditions/cache/regenerate/query_args' ),
	'enabled cache protection registers the dedicated Elementor filter'
);
Plugin::boot();
assert_same(
	1,
	registered_callback_count(
		$test_state['filters'],
		'elementor/theme/get_location_templates/condition_sub_id'
	),
	'repeated bootstrap does not duplicate the runtime filter'
);
assert_same(
	1,
	registered_callback_count(
		$test_state['filters'],
		'elementor/theme/conditions/cache/regenerate/query_args'
	),
	'repeated bootstrap does not duplicate the cache-protection filter'
);

$cache_query_args = array(
	'post_type' => array( 'elementor_library' ),
	'meta_key'  => '_elementor_conditions',
);
$protected_args   = $cache_query_args;
$protected_args['lang'] = '';
assert_same(
	$protected_args,
	Plugin::include_all_languages_in_conditions_cache( $cache_query_args ),
	'enabled callback adds an empty language argument'
);
assert_same(
	0,
	$test_state['languages_list_calls'],
	'cache protection checks general Polylang API availability without listing languages'
);
assert_same(
	'not-an-array',
	Plugin::include_all_languages_in_conditions_cache( 'not-an-array' ),
	'cache-protection callback safely preserves malformed input'
);
assert_same( 0, Plugin::sanitize_cache_protection_option( 0 ), 'unchecked setting sanitizes to zero' );
assert_same( 0, Plugin::sanitize_cache_protection_option( 'invalid' ), 'malformed setting sanitizes to zero' );
assert_same( 1, Plugin::sanitize_cache_protection_option( '1' ), 'checked setting sanitizes to one' );

$test_state['is_admin'] = true;
Plugin::boot();
assert_same( true, is_action_registered( 'admin_init' ), 'admin bootstrap registers settings initialization' );
assert_same( true, is_action_registered( 'admin_menu' ), 'admin bootstrap registers settings page' );
assert_same(
	1,
	registered_callback_count( $test_state['actions'], 'admin_init' ),
	'repeated bootstrap does not duplicate the settings hook'
);

Plugin::register_settings();
assert_same(
	Plugin::SETTINGS_GROUP,
	$test_state['registered_settings'][ Plugin::OPTION_CACHE_PROTECTION ]['group'],
	'cache-protection option uses the dedicated settings group'
);
assert_same(
	0,
	$test_state['registered_settings'][ Plugin::OPTION_CACHE_PROTECTION ]['args']['default'],
	'registered cache-protection setting defaults to zero'
);
assert_same(
	true,
	isset( $test_state['settings_sections'][ Plugin::SETTINGS_SECTION ] ),
	'cache-protection settings section is registered'
);
assert_same(
	true,
	isset( $test_state['settings_fields'][ Plugin::OPTION_CACHE_PROTECTION ] ),
	'cache-protection checkbox field is registered'
);

$category_condition = array(
	'type'     => 'include',
	'name'     => 'archive',
	'sub_name' => 'category',
	'sub_id'   => '10',
);

$test_state['queried_object'] = new WP_Term( 20, 'category' );
assert_same( 20, Plugin::map_condition_term_id( '10', $category_condition ), 'translated category maps to current ID' );

$exclude_category_condition         = $category_condition;
$exclude_category_condition['type'] = 'exclude';
assert_same( 20, Plugin::map_condition_term_id( '10', $exclude_category_condition ), 'exclude condition maps through the same native checker' );

$test_state['queried_object'] = new WP_Term( 10, 'category' );
assert_same( 10, Plugin::map_condition_term_id( '10', $category_condition ), 'source category remains matched' );

$test_state['queried_object'] = new WP_Term( 99, 'category' );
assert_same( '10', Plugin::map_condition_term_id( '10', $category_condition ), 'unrelated category does not match' );

$test_state['queried_object'] = new WP_Term( 20, 'product_cat' );
assert_same( '10', Plugin::map_condition_term_id( '10', $category_condition ), 'different taxonomy does not match' );

$test_state['queried_object'] = null;
assert_same( '10', Plugin::map_condition_term_id( '10', $category_condition ), 'non-term request is unchanged' );

$post_condition = array(
	'type'     => 'include',
	'name'     => 'singular',
	'sub_name' => 'post',
	'sub_id'   => '10',
);

$test_state['queried_object'] = new WP_Term( 20, 'category' );
assert_same( '10', Plugin::map_condition_term_id( '10', $post_condition ), 'non-taxonomy condition is unchanged' );

$tag_condition = array(
	'type'     => 'exclude',
	'name'     => 'archive',
	'sub_name' => 'post_tag',
	'sub_id'   => '10',
);

$test_state['queried_object'] = new WP_Term( 20, 'post_tag' );
assert_same( '10', Plugin::map_condition_term_id( '10', $tag_condition ), 'taxonomy excluded from Polylang is unchanged' );

$test_state['terms']['category:20'] = new WP_Term( 20, 'category', 0 );
$test_state['terms']['category:21'] = new WP_Term( 21, 'category', 20 );
$test_state['terms']['category:22'] = new WP_Term( 22, 'category', 21 );

$direct_child_condition = array(
	'type'     => 'include',
	'name'     => 'post_archive',
	'sub_name' => 'child_of_category',
	'sub_id'   => '10',
);

$test_state['queried_object'] = new WP_Term( 21, 'category', 20 );
assert_same( 20, Plugin::map_condition_term_id( '10', $direct_child_condition ), 'direct child maps translated parent ID' );

$test_state['queried_object'] = new WP_Term( 22, 'category', 21 );
assert_same( '10', Plugin::map_condition_term_id( '10', $direct_child_condition ), 'grandchild does not pass direct-child mapping' );

$any_child_condition = array(
	'type'     => 'include',
	'name'     => 'post_archive',
	'sub_name' => 'any_child_of_category',
	'sub_id'   => '10',
);

$test_state['queried_object'] = new WP_Term( 22, 'category', 21 );
assert_same( 20, Plugin::map_condition_term_id( '10', $any_child_condition ), 'any-child maps matching translated ancestor ID' );

$translation_calls_before = $test_state['translation_calls'];
$get_term_calls_before     = $test_state['get_term_calls'];
assert_same( 20, Plugin::map_condition_term_id( '10', $any_child_condition ), 'cached any-child mapping remains stable' );
assert_same( $translation_calls_before, $test_state['translation_calls'], 'translation groups are request-cached' );
assert_same( $get_term_calls_before, $test_state['get_term_calls'], 'ancestor candidate set is request-cached' );

$exclude_any_child_condition         = $any_child_condition;
$exclude_any_child_condition['type'] = 'exclude';
assert_same( 20, Plugin::map_condition_term_id( '10', $exclude_any_child_condition ), 'exclude any-child uses the same native operand mapping' );

$test_state['queried_object'] = new WP_Term( 20, 'category', 0 );
assert_same( '10', Plugin::map_condition_term_id( '10', $any_child_condition ), 'root term has no ancestors for any-child' );

$test_state['terms']['category:32'] = new WP_Term( 32, 'category', 33 );
$test_state['terms']['category:33'] = new WP_Term( 33, 'category', 32 );
$test_state['queried_object']       = new WP_Term( 31, 'category', 32 );
$cycle_calls_before                 = $test_state['get_term_calls'];
assert_same( '10', Plugin::map_condition_term_id( '10', $any_child_condition ), 'hierarchy cycle safely falls back' );
assert_same( 2, $test_state['get_term_calls'] - $cycle_calls_before, 'hierarchy cycle traversal terminates' );

echo "All behavior checks passed.\n";
