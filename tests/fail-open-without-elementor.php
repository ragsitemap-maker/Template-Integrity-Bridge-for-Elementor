<?php
/** Verify the ACF correction silently fails open when Elementor is absent. */

define( 'ABSPATH', __DIR__ );

class WP_Term {
	public $term_id;
	public $taxonomy;

	public function __construct( $term_id, $taxonomy ) {
		$this->term_id  = $term_id;
		$this->taxonomy = $taxonomy;
	}
}

class Test_Missing_Elementor_Widget {
	public function get_name() {
		return 'template';
	}

	public function get_settings( $key ) {
		return 'template_id' === $key ? 42 : null;
	}
}

function add_action() {}
function add_filter() {}
function plugin_basename( $file ) { return basename( $file ); }
function is_admin() { return false; }
function is_category() { return true; }
function is_tag() { return false; }
function is_tax() { return false; }
function get_the_ID() { return 99; }
function get_queried_object() { return new WP_Term( 7, 'category' ); }
function get_option( $name, $default = false ) {
	return 'peab_enable_archive_acf_term_correction' === $name ? 1 : $default;
}

set_error_handler(
	function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);

require dirname( __DIR__ ) . '/polylang-elementor-archive-bridge/polylang-elementor-archive-bridge.php';

use Polylang_Elementor_Archive_Bridge\Plugin;

if ( class_exists( '\\Elementor\\Plugin', false ) ) {
	fwrite( STDERR, "FAIL: Elementor class unexpectedly exists\n" );
	exit( 1 );
}

Plugin::enter_template_widget_context( new Test_Missing_Elementor_Widget() );
$actual = Plugin::normalize_archive_term_post_id( null, 99 );

if ( null !== $actual ) {
	fwrite( STDERR, "FAIL: missing Elementor class did not preserve preload\n" );
	exit( 1 );
}

restore_error_handler();
echo "PASS: missing Elementor class silently preserves preload\n";
