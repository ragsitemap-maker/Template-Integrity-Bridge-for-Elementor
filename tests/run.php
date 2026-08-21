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
	'enqueued_scripts'      => array(),
	'archive_type'          => '',
	'current_post_id'        => 0,
	'add_option_calls'       => array(),
	'fail_add_option'        => false,
	'concurrent_option'      => null,
	'get_queried_calls'      => 0,
	'get_the_id_calls'       => 0,
	'get_current_calls'      => 0,
	'get_main_id_calls'      => 0,
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

class WP_Post {
	public $ID;
	public $post_type;

	public function __construct( $post_id, $post_type = 'post' ) {
		$this->ID        = $post_id;
		$this->post_type = $post_type;
	}
}

class Test_Widget {
	private $name;
	private $template_id;

	public function __construct( $name, $template_id = 0 ) {
		$this->name        = $name;
		$this->template_id = $template_id;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_settings( $key ) {
		return 'template_id' === $key ? $this->template_id : null;
	}
}

class Test_Throwing_Name_Widget {
	public function get_name() {
		throw new RuntimeException( 'Elementor widget name unavailable' );
	}
}

class Test_Throwing_Settings_Widget {
	public function get_name() {
		return 'template';
	}

	public function get_settings( $key ) {
		unset( $key );
		throw new RuntimeException( 'Elementor widget settings unavailable' );
	}
}

abstract class Test_Elementor_Document {
	private $main_id;

	public function __construct( $main_id ) {
		$this->main_id = $main_id;
	}

	public function get_main_id() {
		global $test_state;

		++$test_state['get_main_id_calls'];
		return $this->main_id;
	}
}

class Test_Loop_Document extends Test_Elementor_Document {
	public static function get_type() { return 'loop-item'; }
}

class Test_Archive_Document extends Test_Elementor_Document {
	public static function get_type() { return 'archive'; }
}

class Test_Malformed_Type_Document extends Test_Elementor_Document {
	public static function get_type() { return array( 'loop-item' ); }
}

class Test_Throwing_Type_Document extends Test_Elementor_Document {
	public static function get_type() {
		throw new RuntimeException( 'Elementor document type unavailable' );
	}
}

class Test_Throwing_Id_Document extends Test_Elementor_Document {
	public static function get_type() {
		return 'loop-item';
	}

	public function get_main_id() {
		throw new RuntimeException( 'Elementor document ID unavailable' );
	}
}

class Test_Documents_Manager {
	public $current;

	public function get_current() {
		global $test_state;

		++$test_state['get_current_calls'];
		return $this->current;
	}
}

class Test_Throwing_Documents_Manager {
	public function get_current() {
		throw new RuntimeException( 'Elementor documents manager unavailable' );
	}
}

class Test_Elementor_Plugin {
	public static $instance;
	public static $throw_on_instance = false;
	public $documents;

	public function __construct() { $this->documents = new Test_Documents_Manager(); }
	public static function instance() {
		if ( self::$throw_on_instance ) {
			throw new RuntimeException( 'Elementor plugin instance unavailable' );
		}
		return self::$instance;
	}
}

class_alias( 'Test_Elementor_Plugin', 'Elementor\Plugin' );
Test_Elementor_Plugin::$instance = new Test_Elementor_Plugin();

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

function add_option( $option_name, $value = '', $deprecated = '', $autoload = null ) {
	global $test_state;

	$test_state['add_option_calls'][] = compact( 'option_name', 'value', 'deprecated', 'autoload' );

	if ( $test_state['fail_add_option'] ) {
		if ( null !== $test_state['concurrent_option'] ) {
			$test_state['options'][ $option_name ] = $test_state['concurrent_option'];
		}
		return false;
	}

	if ( array_key_exists( $option_name, $test_state['options'] ) ) {
		return false;
	}

	$test_state['options'][ $option_name ] = $value;
	return true;
}

function is_admin() {
	global $test_state;

	return $test_state['is_admin'];
}

function is_category() {
	global $test_state;

	return 'category' === $test_state['archive_type'];
}

function is_tag() {
	global $test_state;

	return 'tag' === $test_state['archive_type'];
}

function is_tax() {
	global $test_state;

	return 'tax' === $test_state['archive_type'];
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

function esc_attr( $value ) {
	return (string) $value;
}

function checked( $checked ) {
	if ( $checked ) {
		echo 'checked="checked"';
	}
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

function plugins_url( $path, $file ) {
	unset( $file );

	return 'https://example.test/plugins/polylang-elementor-archive-bridge/' . ltrim( $path, '/' );
}

function wp_enqueue_script( $handle, $src, $dependencies, $version, $in_footer ) {
	global $test_state;

	$test_state['enqueued_scripts'][ $handle ] = compact(
		'src',
		'dependencies',
		'version',
		'in_footer'
	);
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

	++$test_state['get_queried_calls'];
	return $test_state['queried_object'];
}

function get_the_ID() {
	global $test_state;

	++$test_state['get_the_id_calls'];
	return $test_state['current_post_id'];
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
assert_same(
	false,
	Plugin::is_nested_loop_conditions_protection_enabled(),
	'missing nested-loop option defaults to disabled'
);
assert_same(
	false,
	is_action_registered( 'elementor/editor/after_enqueue_scripts' ),
	'nested-loop editor hook is not registered by default'
);
assert_same(
	true,
	Plugin::is_archive_acf_term_correction_enabled(),
	'missing Archive ACF option is provisioned as enabled'
);
assert_same(
	1,
	$test_state['options'][ Plugin::OPTION_ARCHIVE_ACF_TERM_CORRECTION ],
	'first bootstrap stores the Archive ACF enabled default'
);
assert_same( 1, count( $test_state['add_option_calls'] ), 'missing Archive ACF option is added only once' );
assert_same( true, $test_state['add_option_calls'][0]['autoload'], 'Archive ACF default is explicitly autoloaded' );
Plugin::is_archive_acf_term_correction_enabled();
assert_same( 1, count( $test_state['add_option_calls'] ), 'existing Archive ACF option does not call add_option again' );

$add_calls_before = count( $test_state['add_option_calls'] );
$test_state['options'][ Plugin::OPTION_ARCHIVE_ACF_TERM_CORRECTION ] = 'invalid';
assert_same( false, Plugin::is_archive_acf_term_correction_enabled(), 'malformed Archive ACF option remains disabled' );
assert_same( 'invalid', $test_state['options'][ Plugin::OPTION_ARCHIVE_ACF_TERM_CORRECTION ], 'malformed Archive ACF option is not overwritten' );
assert_same( $add_calls_before, count( $test_state['add_option_calls'] ), 'malformed Archive ACF option is not re-provisioned' );

unset(
	$test_state['actions']['elementor/frontend/widget/before_render'],
	$test_state['actions']['elementor/frontend/widget/after_render'],
	$test_state['filters']['acf/pre_load_post_id']
);
Plugin::boot();
assert_same( false, is_action_registered( 'elementor/frontend/widget/before_render' ), 'disabled Archive ACF feature does not register before_render' );
assert_same( false, is_action_registered( 'elementor/frontend/widget/after_render' ), 'disabled Archive ACF feature does not register after_render' );
assert_same( false, is_filter_registered( 'acf/pre_load_post_id' ), 'disabled Archive ACF feature does not register its ACF filter' );
assert_same( true, is_filter_registered( 'elementor/theme/get_location_templates/condition_sub_id' ), 'disabling Archive ACF leaves the legacy condition mapping active' );

$test_state['options'][ Plugin::OPTION_ARCHIVE_ACF_TERM_CORRECTION ] = 0;
assert_same( false, Plugin::is_archive_acf_term_correction_enabled(), 'unchecked Archive ACF option is disabled' );
Plugin::boot();
assert_same( false, is_action_registered( 'elementor/frontend/widget/before_render' ), 'unchecked Archive ACF option keeps before_render unregistered' );
assert_same( false, is_action_registered( 'elementor/frontend/widget/after_render' ), 'unchecked Archive ACF option keeps after_render unregistered' );
assert_same( false, is_filter_registered( 'acf/pre_load_post_id' ), 'unchecked Archive ACF option keeps the ACF filter unregistered' );

unset( $test_state['options'][ Plugin::OPTION_ARCHIVE_ACF_TERM_CORRECTION ] );
$test_state['fail_add_option']   = true;
$test_state['concurrent_option'] = 1;
$add_calls_before                = count( $test_state['add_option_calls'] );
assert_same( true, Plugin::is_archive_acf_term_correction_enabled(), 'add_option race remains enabled for the current request' );
assert_same( $add_calls_before + 1, count( $test_state['add_option_calls'] ), 'add_option race attempts one provision' );
Plugin::is_archive_acf_term_correction_enabled();
assert_same( $add_calls_before + 1, count( $test_state['add_option_calls'] ), 'concurrently stored option prevents another add attempt' );
$test_state['fail_add_option']   = false;
$test_state['concurrent_option'] = null;
Plugin::boot();
assert_same( true, is_filter_registered( 'acf/pre_load_post_id' ), 'enabled Archive ACF feature restores its runtime hooks' );

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

assert_same(
	1,
	registered_callback_count( $test_state['filters'], 'acf/pre_load_post_id' ),
	'ACF term identity filter is registered once'
);
$acf_filter_callbacks = array_values( $test_state['filters']['acf/pre_load_post_id'][20] );
assert_same(
	20,
	$acf_filter_callbacks[0]['priority'],
	'ACF term identity filter runs after Elementor Pro priority 10'
);
assert_same(
	2,
	$acf_filter_callbacks[0]['accepted_args'],
	'ACF term identity filter receives preload and original object ID'
);
assert_same( 1, registered_callback_count( $test_state['actions'], 'elementor/frontend/widget/before_render' ), 'Template widget context starts once' );
$template_before_callbacks = array_values( $test_state['actions']['elementor/frontend/widget/before_render'][20] );
assert_same( 20, $template_before_callbacks[0]['priority'], 'Template context starts after Template ID translation' );
assert_same( false, is_action_registered( 'elementor/template-library/before_get_source_data' ), 'disproved Template source hook is not registered' );
assert_same( 1, registered_callback_count( $test_state['actions'], 'elementor/frontend/widget/after_render' ), 'Template widget context ends once' );

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
assert_same( 0, Plugin::sanitize_nested_loop_protection_option( 0 ), 'unchecked nested-loop setting sanitizes to zero' );
assert_same( 0, Plugin::sanitize_nested_loop_protection_option( 'invalid' ), 'malformed nested-loop setting sanitizes to zero' );
assert_same( 1, Plugin::sanitize_nested_loop_protection_option( '1' ), 'checked nested-loop setting sanitizes to one' );
assert_same( 0, Plugin::sanitize_archive_acf_term_correction_option( 0 ), 'unchecked Archive ACF setting sanitizes to zero' );
assert_same( 0, Plugin::sanitize_archive_acf_term_correction_option( 'invalid' ), 'malformed Archive ACF setting sanitizes to zero' );
assert_same( 1, Plugin::sanitize_archive_acf_term_correction_option( '1' ), 'checked Archive ACF setting sanitizes to one' );

$test_state['options'][ Plugin::OPTION_NESTED_LOOP_PROTECTION ] = 'invalid';
assert_same(
	false,
	Plugin::is_nested_loop_conditions_protection_enabled(),
	'malformed nested-loop option remains disabled'
);

$test_state['options'][ Plugin::OPTION_NESTED_LOOP_PROTECTION ] = '1';
Plugin::boot();
assert_same(
	true,
	Plugin::is_nested_loop_conditions_protection_enabled(),
	'checked nested-loop option is enabled'
);
assert_same(
	true,
	is_action_registered( 'elementor/editor/after_enqueue_scripts' ),
	'enabled nested-loop protection registers the Elementor editor hook'
);

Plugin::enqueue_nested_loop_conditions_protection_script();
assert_same(
	array( 'jquery', 'elementor-pro' ),
	$test_state['enqueued_scripts'][ Plugin::NESTED_LOOP_SCRIPT_HANDLE ]['dependencies'],
	'nested-loop guard loads after jQuery and Elementor Pro'
);
assert_same(
	Plugin::VERSION,
	$test_state['enqueued_scripts'][ Plugin::NESTED_LOOP_SCRIPT_HANDLE ]['version'],
	'nested-loop guard uses the plugin version'
);
assert_same(
	true,
	$test_state['enqueued_scripts'][ Plugin::NESTED_LOOP_SCRIPT_HANDLE ]['in_footer'],
	'nested-loop guard loads in the editor footer'
);

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
	0,
	$test_state['registered_settings'][ Plugin::OPTION_NESTED_LOOP_PROTECTION ]['args']['default'],
	'registered nested-loop setting defaults to zero'
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
assert_same(
	true,
	isset( $test_state['settings_sections'][ Plugin::SETTINGS_SECTION_NESTED_LOOP ] ),
	'nested-loop settings section is registered'
);
assert_same(
	true,
	isset( $test_state['settings_fields'][ Plugin::OPTION_NESTED_LOOP_PROTECTION ] ),
	'nested-loop checkbox field is registered'
);
assert_same(
	1,
	$test_state['registered_settings'][ Plugin::OPTION_ARCHIVE_ACF_TERM_CORRECTION ]['args']['default'],
	'registered Archive ACF setting defaults to one'
);
assert_same(
	true,
	isset( $test_state['settings_sections'][ Plugin::SETTINGS_SECTION_ARCHIVE_ACF ] ),
	'Archive ACF settings section is registered independently'
);
assert_same(
	Plugin::SETTINGS_SECTION_ARCHIVE_ACF,
	$test_state['settings_fields'][ Plugin::OPTION_ARCHIVE_ACF_TERM_CORRECTION ]['section'],
	'Archive ACF checkbox belongs to its independent section'
);

$test_state['options'][ Plugin::OPTION_ARCHIVE_ACF_TERM_CORRECTION ] = 1;
ob_start();
Plugin::render_archive_acf_term_correction_field();
$archive_acf_field = ob_get_clean();
assert_same( true, false !== strpos( $archive_acf_field, 'type="hidden"' ), 'Archive ACF field submits zero when unchecked' );
assert_same( true, false !== strpos( $archive_acf_field, 'checked="checked"' ), 'Archive ACF field renders checked by default' );
$test_state['options'][ Plugin::OPTION_ARCHIVE_ACF_TERM_CORRECTION ] = 0;
ob_start();
Plugin::render_archive_acf_term_correction_field();
$archive_acf_field = ob_get_clean();
assert_same( false, false !== strpos( $archive_acf_field, 'checked="checked"' ), 'Archive ACF field renders unchecked when disabled' );
$test_state['options'][ Plugin::OPTION_ARCHIVE_ACF_TERM_CORRECTION ] = 1;

$test_state['get_queried_calls'] = 0;
$test_state['get_the_id_calls']  = 0;
$test_state['get_current_calls'] = 0;
assert_same( 'options', Plugin::normalize_archive_term_post_id( 'options', 'options' ), 'non-candidate ACF identity returns immediately' );
assert_same( 0, $test_state['get_queried_calls'], 'non-candidate path does not inspect the queried object' );
assert_same( 0, $test_state['get_the_id_calls'], 'non-candidate path does not read the current post' );
assert_same( 0, $test_state['get_current_calls'], 'non-candidate path does not inspect Elementor documents' );

$test_state['archive_type']   = 'category';
$test_state['queried_object'] = new WP_Term( 123, 'category' );
$archive_term                 = new WP_Term( 123, 'category' );
assert_same(
	'term_123',
	Plugin::normalize_archive_term_post_id( 123, $archive_term ),
	'integer archive term ID is normalized for ACF'
);
assert_same(
	'term_123',
	Plugin::normalize_archive_term_post_id( '123', $archive_term ),
	'decimal-string archive term ID is normalized for ACF'
);

$test_state['archive_type'] = 'tag';
assert_same(
	'term_123',
	Plugin::normalize_archive_term_post_id( 123, $archive_term ),
	'tag archive uses the same term identity correction'
);
$test_state['archive_type'] = 'tax';
assert_same(
	'term_123',
	Plugin::normalize_archive_term_post_id( 123, $archive_term ),
	'custom taxonomy archive uses the same term identity correction'
);

$test_state['archive_type'] = '';
assert_same(
	123,
	Plugin::normalize_archive_term_post_id( 123, $archive_term ),
	'non-taxonomy archive leaves the preload unchanged'
);

$test_state['archive_type'] = 'category';
assert_same(
	123,
	Plugin::normalize_archive_term_post_id( 123, new WP_Post( 123 ) ),
	'post object remains unchanged'
);
assert_same(
	'options',
	Plugin::normalize_archive_term_post_id( 'options', 'options' ),
	'options object ID remains unchanged'
);
assert_same(
	'user_7',
	Plugin::normalize_archive_term_post_id( 'user_7', 'user_7' ),
	'user object ID remains unchanged'
);
assert_same(
	'comment_9',
	Plugin::normalize_archive_term_post_id( 'comment_9', 'comment_9' ),
	'comment object ID remains unchanged'
);

$test_state['queried_object'] = new WP_Term( 123, 'category' );
assert_same(
	456,
	Plugin::normalize_archive_term_post_id( 456, new WP_Term( 456, 'category' ) ),
	'different taxonomy-loop term remains unchanged'
);
assert_same(
	123,
	Plugin::normalize_archive_term_post_id( 123, new WP_Term( 123, 'post_tag' ) ),
	'different taxonomy remains unchanged'
);
assert_same(
	456,
	Plugin::normalize_archive_term_post_id( 456, $archive_term ),
	'different preload ID remains unchanged'
);

$invalid_bare_ids = array( 123.0, '123.0', -123, '-123', '1.23e2', '', 0, '0', true, false, ' 123', '+123' );
foreach ( $invalid_bare_ids as $invalid_bare_id ) {
	assert_same(
		$invalid_bare_id,
		Plugin::normalize_archive_term_post_id( $invalid_bare_id, $archive_term ),
		'invalid bare term ID shape remains unchanged: ' . var_export( $invalid_bare_id, true )
	);
}

assert_same(
	null,
	Plugin::normalize_archive_term_post_id( null, $archive_term ),
	'null preload remains unchanged for an upstream fix'
);
assert_same(
	'term_123',
	Plugin::normalize_archive_term_post_id( 'term_123', $archive_term ),
	'already normalized term ID remains unchanged'
);
$upstream_term = new WP_Term( 123, 'category' );
assert_same(
	$upstream_term,
	Plugin::normalize_archive_term_post_id( $upstream_term, $archive_term ),
	'upstream WP_Term result remains the same object'
);
assert_same(
	'custom_object_123',
	Plugin::normalize_archive_term_post_id( 'custom_object_123', $archive_term ),
	'other valid object ID remains unchanged'
);

$test_state['queried_object'] = null;
assert_same(
	123,
	Plugin::normalize_archive_term_post_id( 123, $archive_term ),
	'missing queried object fails open'
);
$test_state['queried_object'] = new WP_Post( 123 );
assert_same(
	123,
	Plugin::normalize_archive_term_post_id( 123, $archive_term ),
	'non-term queried object fails open'
);

$test_state['archive_type']    = 'category';
$test_state['queried_object']  = new WP_Term( 1330, 'category' );
$test_state['current_post_id'] = 77370;
$template_widget               = new Test_Widget( 'template', 109458 );
Test_Elementor_Plugin::$instance->documents->current = new Test_Loop_Document( 109458 );
Plugin::enter_template_widget_context( $template_widget );
$test_state['get_queried_calls'] = 0;
$test_state['get_the_id_calls']  = 0;
$test_state['get_current_calls'] = 0;
$test_state['get_main_id_calls'] = 0;
assert_same(
	'term_1330',
	Plugin::normalize_archive_term_post_id( null, 77370 ),
	'directly embedded Loop Item Template uses the Archive term'
);
assert_same( 1, $test_state['get_queried_calls'], 'embedded candidate reads the queried term once' );
assert_same( 1, $test_state['get_the_id_calls'], 'embedded candidate reads the current post once' );
assert_same( 1, $test_state['get_current_calls'], 'embedded candidate reads the Elementor document once' );
assert_same( 1, $test_state['get_main_id_calls'], 'embedded candidate reads the document main ID once' );
$ordinary_nested_widget = new Test_Widget( 'heading', 0 );
Plugin::leave_template_widget_context( $ordinary_nested_widget );
assert_same(
	'term_1330',
	Plugin::normalize_archive_term_post_id( null, 77370 ),
	'an ordinary nested widget after_render does not pop the Template context'
);
assert_same(
	'term_1330',
	Plugin::normalize_archive_term_post_id( null, '77370' ),
	'decimal-string current post ID uses the Archive term in the same exact context'
);
assert_same(
	'term_upstream',
	Plugin::normalize_archive_term_post_id( 'term_upstream', 77370 ),
	'correct upstream output passes through inside embedded Loop Item Template'
);
assert_same(
	null,
	Plugin::normalize_archive_term_post_id( null, 77371 ),
	'post ID different from get_the_ID remains unchanged'
);

Test_Elementor_Plugin::$instance->documents->current = new Test_Loop_Document( 109945 );
assert_same(
	null,
	Plugin::normalize_archive_term_post_id( null, 77370 ),
	'real nested Post Loop document ID remains unchanged'
);
assert_same(
	null,
	Plugin::normalize_archive_term_post_id( null, new WP_Term( 2184, 'product_cat' ) ),
	'real nested Taxonomy Loop term remains unchanged'
);

Test_Elementor_Plugin::$instance->documents->current = new Test_Archive_Document( 109458 );
assert_same(
	null,
	Plugin::normalize_archive_term_post_id( null, 77370 ),
	'non-Loop Elementor document remains unchanged'
);

$stable_elementor = Test_Elementor_Plugin::$instance;
$stable_documents = $stable_elementor->documents;
set_error_handler(
	function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);
Test_Elementor_Plugin::$instance = null;
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'non-object Elementor instance fails open without warnings' );
Test_Elementor_Plugin::$instance = (object) array( 'documents' => array() );
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'malformed Elementor documents property fails open without warnings' );
Test_Elementor_Plugin::$instance = (object) array( 'documents' => new stdClass() );
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'documents manager without get_current fails open without warnings' );
Test_Elementor_Plugin::$instance = $stable_elementor;
$stable_elementor->documents->current = new stdClass();
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'document without identity methods fails open without warnings' );
$stable_elementor->documents->current = new Test_Malformed_Type_Document( 109458 );
$main_id_calls_before = $test_state['get_main_id_calls'];
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'malformed document type fails open without warnings' );
assert_same( $main_id_calls_before, $test_state['get_main_id_calls'], 'malformed document type returns before reading its main ID' );
$stable_elementor->documents->current = new Test_Loop_Document( array( 109458 ) );
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'malformed document ID fails open without warnings' );
$resource_document_id = fopen( 'php://memory', 'r' );
$stable_elementor->documents->current = new Test_Loop_Document( $resource_document_id );
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'resource document ID fails open without warnings' );
fclose( $resource_document_id );
$stable_elementor->documents->current = new Test_Throwing_Type_Document( 109458 );
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'throwing document type API fails open without warnings' );
$stable_elementor->documents->current = new Test_Throwing_Id_Document( 109458 );
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'throwing document ID API fails open without warnings' );
$stable_elementor->documents = new Test_Throwing_Documents_Manager();
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'throwing documents manager fails open without warnings' );
$stable_elementor->documents = $stable_documents;
Test_Elementor_Plugin::$throw_on_instance = true;
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'throwing Elementor instance API fails open without warnings' );
Test_Elementor_Plugin::$throw_on_instance = false;
restore_error_handler();

Test_Elementor_Plugin::$instance->documents->current = new Test_Loop_Document( 109458 );
$test_state['queried_object'] = new WP_Term( 1331, 'category' );
assert_same(
	null,
	Plugin::normalize_archive_term_post_id( null, 77370 ),
	'queried term different from captured Archive term remains unchanged'
);
$test_state['queried_object'] = new WP_Term( 1330, 'post_tag' );
assert_same(
	null,
	Plugin::normalize_archive_term_post_id( null, 77370 ),
	'queried taxonomy different from captured Archive taxonomy remains unchanged'
);
$test_state['queried_object'] = new WP_Term( 1330, 'category' );

$nested_template_widget = new Test_Widget( 'template', 110399 );
Plugin::enter_template_widget_context( $nested_template_widget );
Test_Elementor_Plugin::$instance->documents->current = new Test_Loop_Document( 110399 );
assert_same(
	'term_1330',
	Plugin::normalize_archive_term_post_id( null, 77370 ),
	'nested directly embedded Loop Item Template uses its own exact Template ID'
);
Plugin::leave_template_widget_context( $nested_template_widget );
Test_Elementor_Plugin::$instance->documents->current = new Test_Loop_Document( 109458 );
assert_same(
	'term_1330',
	Plugin::normalize_archive_term_post_id( null, 77370 ),
	'leaving nested Template restores outer exact Template context'
);
Plugin::leave_template_widget_context( $template_widget );
assert_same(
	null,
	Plugin::normalize_archive_term_post_id( null, 77370 ),
	'Archive term context does not leak after Template rendering'
);

Plugin::enter_template_widget_context( new Test_Widget( 'template', array( 109458 ) ) );
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'array Template ID cannot create a context' );
Plugin::enter_template_widget_context( new Test_Widget( 'template', new stdClass() ) );
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'object Template ID cannot create a context' );
$resource_template_id = fopen( 'php://memory', 'r' );
Plugin::enter_template_widget_context( new Test_Widget( 'template', $resource_template_id ) );
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'resource Template ID cannot create a context' );
fclose( $resource_template_id );
Plugin::enter_template_widget_context( new Test_Throwing_Name_Widget() );
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'throwing widget name API fails open' );
Plugin::enter_template_widget_context( new Test_Throwing_Settings_Widget() );
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'throwing widget settings API fails open' );

Plugin::enter_template_widget_context( $template_widget );
Plugin::leave_template_widget_context( new Test_Throwing_Name_Widget() );
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'throwing after_render widget clears captured context' );
Plugin::enter_template_widget_context( $template_widget );
Plugin::leave_template_widget_context( new Test_Widget( 'template', 109458 ) );
assert_same( null, Plugin::normalize_archive_term_post_id( null, 77370 ), 'mismatched Template widget identity clears captured context' );

$ordinary_widget = new Test_Widget( 'heading', 109458 );
Plugin::enter_template_widget_context( $ordinary_widget );
assert_same(
	null,
	Plugin::normalize_archive_term_post_id( null, 77370 ),
	'non-Template widget cannot create an Archive context'
);
Plugin::leave_template_widget_context( $ordinary_widget );

$test_state['archive_type']   = '';
$test_state['queried_object'] = null;

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
