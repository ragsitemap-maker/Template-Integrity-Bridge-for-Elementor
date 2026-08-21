<?php
/**
 * Plugin Name: Polylang Elementor Archive Bridge
 * Description: Lets one Elementor Pro archive template condition match every Polylang translation of the selected taxonomy term.
 * Version: 1.4.4
 * Author: ragsitemap-maker
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Text Domain: polylang-elementor-archive-bridge
 */

namespace Polylang_Elementor_Archive_Bridge;

defined( 'ABSPATH' ) || exit;

/**
 * Resolve an Elementor taxonomy condition against the current Polylang term.
 *
 * Elementor stores a Theme Builder condition as a path such as:
 * include/archive/category/123
 *
 * Immediately before checking the last segment, Elementor exposes the
 * `elementor/theme/get_location_templates/condition_sub_id` filter. Returning
 * the matching current term, parent, or ancestor ID makes Elementor perform
 * its normal condition check without changing the saved template or metadata.
 */
final class Plugin {

	const VERSION           = '1.4.4';
	const MODE_EXACT        = 'exact';
	const MODE_DIRECT_CHILD = 'direct_child';
	const MODE_ANY_CHILD    = 'any_child';

	const OPTION_CACHE_PROTECTION             = 'peab_protect_conditions_cache';
	const OPTION_NESTED_LOOP_PROTECTION       = 'peab_protect_nested_loop_conditions';
	const OPTION_ARCHIVE_ACF_TERM_CORRECTION  = 'peab_enable_archive_acf_term_correction';
	const SETTINGS_GROUP                      = 'peab_settings';
	const SETTINGS_PAGE                       = 'polylang-elementor-archive-bridge';
	const SETTINGS_SECTION                    = 'peab_conditions_cache';
	const SETTINGS_SECTION_NESTED_LOOP        = 'peab_nested_loop_conditions';
	const SETTINGS_SECTION_ARCHIVE_ACF        = 'peab_archive_acf_term_correction';
	const NESTED_LOOP_SCRIPT_HANDLE     = 'peab-nested-loop-conditions-save-protection';

	/**
	 * Candidate term sets cached for the duration of the request.
	 *
	 * @var array<string, int[]>
	 */
	private static $candidate_sets = array();

	/**
	 * Candidate translation groups cached for the duration of the request.
	 *
	 * @var array<string, int[]>
	 */
	private static $translation_groups = array();

	/**
	 * Archive term contexts owned by currently rendering Template widgets.
	 *
	 * @var array<int, array{widget_hash: string, template_id: int, term_id: int, taxonomy: string}>
	 */
	private static $template_archive_contexts = array();

	/**
	 * Register runtime mapping, optional cache protection, and admin settings.
	 *
	 * Priority 20 allows another integration to normalize the ID first while
	 * this plugin still verifies membership in the queried term's group.
	 *
	 * @return void
	 */
	public static function boot() {
		if ( self::is_archive_acf_term_correction_enabled() ) {
			add_action(
				'elementor/frontend/widget/before_render',
				array( __CLASS__, 'enter_template_widget_context' ),
				20,
				1
			);

			add_action(
				'elementor/frontend/widget/after_render',
				array( __CLASS__, 'leave_template_widget_context' ),
				100,
				1
			);

			add_filter(
				'acf/pre_load_post_id',
				array( __CLASS__, 'normalize_archive_term_post_id' ),
				20,
				2
			);
		}

		add_filter(
			'elementor/theme/get_location_templates/condition_sub_id',
			array( __CLASS__, 'map_condition_term_id' ),
			20,
			2
		);

		if ( self::is_conditions_cache_protection_enabled() ) {
			add_filter(
				'elementor/theme/conditions/cache/regenerate/query_args',
				array( __CLASS__, 'include_all_languages_in_conditions_cache' )
			);
		}

		if ( self::is_nested_loop_conditions_protection_enabled() ) {
			add_action(
				'elementor/editor/after_enqueue_scripts',
				array( __CLASS__, 'enqueue_nested_loop_conditions_protection_script' ),
				100
			);
		}

		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
			add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
			add_filter(
				'plugin_action_links_' . plugin_basename( __FILE__ ),
				array( __CLASS__, 'add_settings_action_link' )
			);
		}
	}

	/**
	 * Capture a taxonomy Archive context for an Elementor Template widget.
	 *
	 * Priority 20 runs after Connect Polylang Elementor translates template_id.
	 * Only a real taxonomy Archive term creates a context frame.
	 *
	 * @param mixed $widget Elementor widget instance.
	 * @return void
	 */
	public static function enter_template_widget_context( $widget ) {
		if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) ) {
			return;
		}

		try {
			if ( 'template' !== $widget->get_name() ) {
				return;
			}
		} catch ( \Throwable $throwable ) {
			return;
		}

		if (
			! method_exists( $widget, 'get_settings' )
			|| ( ! is_category() && ! is_tag() && ! is_tax() )
		) {
			return;
		}

		$queried_term = get_queried_object();

		try {
			$template_id = $widget->get_settings( 'template_id' );
		} catch ( \Throwable $throwable ) {
			return;
		}

		if (
			! $queried_term instanceof \WP_Term
			|| 0 >= (int) $queried_term->term_id
			|| '' === (string) $queried_term->taxonomy
			|| ! self::is_bare_positive_integer( $template_id )
		) {
			return;
		}

		$template_id = (int) $template_id;

		self::$template_archive_contexts[] = array(
			'widget_hash' => spl_object_hash( $widget ),
			'template_id' => $template_id,
			'term_id'     => (int) $queried_term->term_id,
			'taxonomy'    => (string) $queried_term->taxonomy,
		);
	}

	/**
	 * End the Archive context owned by an Elementor Template widget.
	 *
	 * @param mixed $widget Elementor widget instance.
	 * @return void
	 */
	public static function leave_template_widget_context( $widget ) {
		if ( empty( self::$template_archive_contexts ) ) {
			return;
		}

		if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) ) {
			self::$template_archive_contexts = array();
			return;
		}

		try {
			if ( 'template' !== $widget->get_name() ) {
				return;
			}
		} catch ( \Throwable $throwable ) {
			self::$template_archive_contexts = array();
			return;
		}

		$context = array_pop( self::$template_archive_contexts );

		if ( $context['widget_hash'] !== spl_object_hash( $widget ) ) {
			self::$template_archive_contexts = array();
		}
	}

	/**
	 * Correct ACF identity for a Loop Item embedded as a Template widget.
	 *
	 * Runtime evidence showed Elementor's ACF provider treats any current
	 * loop-item document as an active Post Loop and passes get_the_ID(). When a
	 * Template widget directly embeds that same Loop Item document outside a
	 * real iteration, use the captured Archive term. Real nested Loops have a
	 * different current document ID and pass through unchanged.
	 *
	 * The original strict WP_Term-to-bare-ID preview correction is retained as
	 * a separate exact branch.
	 *
	 * @param mixed $preload A value returned by an earlier pre-load filter.
	 * @param mixed $post_id The original object ID passed to ACF.
	 * @return mixed
	 */
	public static function normalize_archive_term_post_id( $preload, $post_id ) {
		$has_template_context = ! empty( self::$template_archive_contexts );
		$is_embedded_candidate = $has_template_context
			&& null === $preload
			&& self::is_bare_positive_integer( $post_id );
		$is_preview_candidate = $post_id instanceof \WP_Term
			&& self::is_bare_positive_integer( $preload );

		if ( ! $is_embedded_candidate && ! $is_preview_candidate ) {
			return $preload;
		}

		if ( ! is_category() && ! is_tag() && ! is_tax() ) {
			return $preload;
		}

		$queried_term = get_queried_object();

		if ( ! $queried_term instanceof \WP_Term ) {
			return $preload;
		}

		if ( $is_embedded_candidate ) {
			$current_post_id = get_the_ID();

			if (
				self::is_bare_positive_integer( $current_post_id )
				&& (int) $post_id === (int) $current_post_id
			) {
				$context  = end( self::$template_archive_contexts );
				$document = self::get_current_elementor_document_identity();

				if (
					(int) $queried_term->term_id === $context['term_id']
					&& (string) $queried_term->taxonomy === $context['taxonomy']
					&& 'loop-item' === $document['type']
					&& $context['template_id'] === $document['id']
				) {
					return 'term_' . $context['term_id'];
				}
			}
		}

		if (
			! $is_preview_candidate
			|| (int) $queried_term->term_id !== (int) $post_id->term_id
			|| $queried_term->taxonomy !== $post_id->taxonomy
			|| (int) $preload !== (int) $post_id->term_id
		) {
			return $preload;
		}

		return 'term_' . (int) $post_id->term_id;
	}

	/**
	 * Read the current Elementor document type and main ID without mutating it.
	 *
	 * @return array{type: string, id: int}
	 */
	private static function get_current_elementor_document_identity() {
		$identity = array( 'type' => '', 'id' => 0 );

		if ( ! class_exists( '\Elementor\Plugin', false ) ) {
			return $identity;
		}

		try {
			$elementor = \Elementor\Plugin::instance();

			if ( ! is_object( $elementor ) || ! isset( $elementor->documents ) ) {
				return $identity;
			}

			$documents = $elementor->documents;

			if ( ! is_object( $documents ) || ! method_exists( $documents, 'get_current' ) ) {
				return $identity;
			}

			$document = $documents->get_current();

			if (
				! is_object( $document )
				|| ! method_exists( $document, 'get_type' )
				|| ! method_exists( $document, 'get_main_id' )
			) {
				return $identity;
			}

			$document_type = $document::get_type();

			if ( ! is_string( $document_type ) ) {
				return $identity;
			}

			$document_id = $document->get_main_id();

			if ( ! self::is_bare_positive_integer( $document_id ) ) {
				return $identity;
			}

			$identity['type'] = $document_type;
			$identity['id']   = (int) $document_id;
		} catch ( \Throwable $throwable ) {
			return array( 'type' => '', 'id' => 0 );
		}

		return $identity;
	}

	/**
	 * Check an exact bare positive integer shape.
	 *
	 * @param mixed $value Candidate object ID.
	 * @return bool
	 */
	private static function is_bare_positive_integer( $value ) {
		if ( is_int( $value ) ) {
			return 0 < $value;
		}

		return is_string( $value )
			&& '' !== $value
			&& 1 === preg_match( '/^[0-9]+$/D', $value )
			&& 0 < (int) $value;
	}

	/**
	 * Determine whether the optional conditions cache protection is enabled.
	 *
	 * Missing, malformed, or unchecked options are always treated as disabled.
	 *
	 * @return bool
	 */
	public static function is_conditions_cache_protection_enabled() {
		$value = get_option( self::OPTION_CACHE_PROTECTION, 0 );

		return 1 === $value || '1' === $value;
	}

	/**
	 * Determine whether nested Loop conditions-save protection is enabled.
	 *
	 * Missing, malformed, or unchecked options are always treated as disabled.
	 *
	 * @return bool
	 */
	public static function is_nested_loop_conditions_protection_enabled() {
		$value = get_option( self::OPTION_NESTED_LOOP_PROTECTION, 0 );

		return 1 === $value || '1' === $value;
	}

	/**
	 * Determine whether Archive ACF term correction is enabled.
	 *
	 * A unique sentinel distinguishes a missing option from malformed stored
	 * data. Provision the default once as an autoloaded option so an upgraded
	 * site does not query a permanently missing option on every request.
	 *
	 * @return bool
	 */
	public static function is_archive_acf_term_correction_enabled() {
		$missing = new \stdClass();
		$value   = get_option( self::OPTION_ARCHIVE_ACF_TERM_CORRECTION, $missing );

		if ( $missing === $value ) {
			add_option( self::OPTION_ARCHIVE_ACF_TERM_CORRECTION, 1, '', true );
			return true;
		}

		return 1 === $value || '1' === $value;
	}

	/**
	 * Load the guard only inside the Elementor editor.
	 *
	 * The Elementor Pro dependency ensures its AJAX facade exists before the
	 * guard attempts to wrap addRequest().
	 *
	 * @return void
	 */
	public static function enqueue_nested_loop_conditions_protection_script() {
		wp_enqueue_script(
			self::NESTED_LOOP_SCRIPT_HANDLE,
			plugins_url( 'assets/js/nested-loop-conditions-save-protection.js', __FILE__ ),
			array( 'jquery', 'elementor-pro' ),
			self::VERSION,
			true
		);
	}

	/**
	 * Prevent Polylang from limiting Elementor's cache rebuild to one language.
	 *
	 * This callback runs only on Elementor's dedicated conditions cache query
	 * arguments filter and only when the feature was enabled during bootstrap.
	 *
	 * @param mixed $query_args Elementor conditions cache query arguments.
	 * @return mixed
	 */
	public static function include_all_languages_in_conditions_cache( $query_args ) {
		/*
		 * This feature needs Polylang's language layer, not its term mapping
		 * service. Use the public general API only as a capability marker;
		 * do not call it because the language list itself is unnecessary.
		 */
		if (
			! is_array( $query_args )
			|| ! function_exists( 'pll_languages_list' )
		) {
			return $query_args;
		}

		$query_args['lang'] = '';

		return $query_args;
	}

	/**
	 * Normalize the checkbox value to a boolean-like integer.
	 *
	 * @param mixed $value Submitted option value.
	 * @return int
	 */
	public static function sanitize_cache_protection_option( $value ) {
		return in_array( $value, array( 1, '1', true ), true ) ? 1 : 0;
	}

	/**
	 * Normalize the nested Loop protection checkbox value.
	 *
	 * @param mixed $value Submitted option value.
	 * @return int
	 */
	public static function sanitize_nested_loop_protection_option( $value ) {
		return in_array( $value, array( 1, '1', true ), true ) ? 1 : 0;
	}

	/**
	 * Normalize the Archive ACF correction checkbox value.
	 *
	 * @param mixed $value Submitted option value.
	 * @return int
	 */
	public static function sanitize_archive_acf_term_correction_option( $value ) {
		return in_array( $value, array( 1, '1', true ), true ) ? 1 : 0;
	}

	/**
	 * Register the optional feature setting.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_CACHE_PROTECTION,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( __CLASS__, 'sanitize_cache_protection_option' ),
				'default'           => 0,
				'show_in_rest'      => false,
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_NESTED_LOOP_PROTECTION,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( __CLASS__, 'sanitize_nested_loop_protection_option' ),
				'default'           => 0,
				'show_in_rest'      => false,
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_ARCHIVE_ACF_TERM_CORRECTION,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( __CLASS__, 'sanitize_archive_acf_term_correction_option' ),
				'default'           => 1,
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			self::SETTINGS_SECTION,
			esc_html__( 'Conditions Cache Protection', 'polylang-elementor-archive-bridge' ),
			array( __CLASS__, 'render_cache_protection_section' ),
			self::SETTINGS_PAGE
		);

		add_settings_field(
			self::OPTION_CACHE_PROTECTION,
			esc_html__( 'Cache protection', 'polylang-elementor-archive-bridge' ),
			array( __CLASS__, 'render_cache_protection_field' ),
			self::SETTINGS_PAGE,
			self::SETTINGS_SECTION
		);

		add_settings_section(
			self::SETTINGS_SECTION_NESTED_LOOP,
			esc_html__( 'Nested Loop Conditions Save Protection', 'polylang-elementor-archive-bridge' ),
			array( __CLASS__, 'render_nested_loop_protection_section' ),
			self::SETTINGS_PAGE
		);

		add_settings_field(
			self::OPTION_NESTED_LOOP_PROTECTION,
			esc_html__( 'Nested Loop save protection', 'polylang-elementor-archive-bridge' ),
			array( __CLASS__, 'render_nested_loop_protection_field' ),
			self::SETTINGS_PAGE,
			self::SETTINGS_SECTION_NESTED_LOOP
		);

		add_settings_section(
			self::SETTINGS_SECTION_ARCHIVE_ACF,
			esc_html__( 'Archive Template ACF Term Correction', 'polylang-elementor-archive-bridge' ),
			array( __CLASS__, 'render_archive_acf_term_correction_section' ),
			self::SETTINGS_PAGE
		);

		add_settings_field(
			self::OPTION_ARCHIVE_ACF_TERM_CORRECTION,
			esc_html__( 'Archive ACF term correction', 'polylang-elementor-archive-bridge' ),
			array( __CLASS__, 'render_archive_acf_term_correction_field' ),
			self::SETTINGS_PAGE,
			self::SETTINGS_SECTION_ARCHIVE_ACF
		);
	}

	/**
	 * Add the plugin page under WordPress Settings.
	 *
	 * @return void
	 */
	public static function register_settings_page() {
		add_options_page(
			esc_html__( 'Polylang Elementor Archive Bridge', 'polylang-elementor-archive-bridge' ),
			esc_html__( 'Archive Bridge', 'polylang-elementor-archive-bridge' ),
			'manage_options',
			self::SETTINGS_PAGE,
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::SETTINGS_PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Explain the optional module's scope.
	 *
	 * @return void
	 */
	public static function render_cache_protection_section() {
		echo '<p>' . esc_html__(
			'Use this for Polylang language-filtered cache rebuilds: saving one Theme Builder Template makes Templates from other admin languages disappear from Elementor\'s condition results. If the problem happens specifically after Edit Loop Template and Save & Back, use Nested Loop Conditions Save Protection below.',
			'polylang-elementor-archive-bridge'
		) . '</p>';
	}

	/**
	 * Render the disabled-by-default checkbox.
	 *
	 * @return void
	 */
	public static function render_cache_protection_field() {
		$enabled = self::is_conditions_cache_protection_enabled();
		?>
		<input
			type="hidden"
			name="<?php echo esc_attr( self::OPTION_CACHE_PROTECTION ); ?>"
			value="0"
		/>
		<label for="<?php echo esc_attr( self::OPTION_CACHE_PROTECTION ); ?>">
			<input
				type="checkbox"
				id="<?php echo esc_attr( self::OPTION_CACHE_PROTECTION ); ?>"
				name="<?php echo esc_attr( self::OPTION_CACHE_PROTECTION ); ?>"
				value="1"
				<?php checked( $enabled ); ?>
			/>
			<?php
			echo esc_html__(
				'Protect Theme Builder Display Conditions during cache rebuilds.',
				'polylang-elementor-archive-bridge'
			);
			?>
		</label>
		<p class="description">
			<?php
			echo esc_html__(
				'When enabled, Elementor includes templates from every language instead of only the current admin language. After enabling it, re-save any Theme Builder Display Conditions once to rebuild the cache. Leave disabled if you have never seen this issue.',
				'polylang-elementor-archive-bridge'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Explain the nested Loop editor race and the feature boundary.
	 *
	 * @return void
	 */
	public static function render_nested_loop_protection_section() {
		echo '<p>' . esc_html__(
			'Enable this if editing a Loop Item through a Loop Grid inside another Elementor Template causes the outer Template\'s Display Conditions to disappear after Save & Back. This protection does not block Loop content saves.',
			'polylang-elementor-archive-bridge'
		) . '</p>';
	}

	/**
	 * Render the disabled-by-default nested Loop protection checkbox.
	 *
	 * @return void
	 */
	public static function render_nested_loop_protection_field() {
		$enabled = self::is_nested_loop_conditions_protection_enabled();
		?>
		<input
			type="hidden"
			name="<?php echo esc_attr( self::OPTION_NESTED_LOOP_PROTECTION ); ?>"
			value="0"
		/>
		<label for="<?php echo esc_attr( self::OPTION_NESTED_LOOP_PROTECTION ); ?>">
			<input
				type="checkbox"
				id="<?php echo esc_attr( self::OPTION_NESTED_LOOP_PROTECTION ); ?>"
				name="<?php echo esc_attr( self::OPTION_NESTED_LOOP_PROTECTION ); ?>"
				value="1"
				<?php checked( $enabled ); ?>
			/>
			<?php
			echo esc_html__(
				'Prevent Loop Item saves from queueing an empty Theme Builder Conditions request.',
				'polylang-elementor-archive-bridge'
			);
			?>
		</label>
		<p class="description">
			<?php
			echo esc_html__(
				'Disabled by default. The guard runs only in the Elementor editor and only while the current document is a Loop Item. Conditions already deleted before enabling this setting must be recreated once.',
				'polylang-elementor-archive-bridge'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Explain the directly embedded Loop Item ACF correction.
	 *
	 * @return void
	 */
	public static function render_archive_acf_term_correction_section() {
		echo '<p>' . esc_html__(
			'Use this when a Template widget directly embeds a Loop Item Template on a taxonomy Archive and ACF otherwise reads the current post instead of the queried term.',
			'polylang-elementor-archive-bridge'
		) . '</p>';
	}

	/**
	 * Render the enabled-by-default Archive ACF correction checkbox.
	 *
	 * @return void
	 */
	public static function render_archive_acf_term_correction_field() {
		$enabled = self::is_archive_acf_term_correction_enabled();
		?>
		<input
			type="hidden"
			name="<?php echo esc_attr( self::OPTION_ARCHIVE_ACF_TERM_CORRECTION ); ?>"
			value="0"
		/>
		<label for="<?php echo esc_attr( self::OPTION_ARCHIVE_ACF_TERM_CORRECTION ); ?>">
			<input
				type="checkbox"
				id="<?php echo esc_attr( self::OPTION_ARCHIVE_ACF_TERM_CORRECTION ); ?>"
				name="<?php echo esc_attr( self::OPTION_ARCHIVE_ACF_TERM_CORRECTION ); ?>"
				value="1"
				<?php checked( $enabled ); ?>
			/>
			<?php
			echo esc_html__(
				'Use the queried taxonomy term for ACF fields in directly embedded Loop Item Templates.',
				'polylang-elementor-archive-bridge'
			);
			?>
		</label>
		<p class="description">
			<?php
			echo esc_html__(
				'Enabled by default. Disable it if this site does not use this Archive structure or if you need to stop the compatibility correction. If Elementor Pro already supplies the correct value, the correction remains checked but silently does nothing.',
				'polylang-elementor-archive-bridge'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Add a direct link from the Plugins screen to this plugin's settings.
	 *
	 * @param string[] $links Existing plugin action links.
	 * @return string[]
	 */
	public static function add_settings_action_link( $links ) {
		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'options-general.php?page=' . self::SETTINGS_PAGE ) ),
			esc_html__( 'Settings', 'polylang-elementor-archive-bridge' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Map the saved condition term to the current translated archive term.
	 *
	 * @param mixed $sub_id           Saved Elementor condition object ID.
	 * @param mixed $parsed_condition Parsed Elementor condition components.
	 * @return mixed
	 */
	public static function map_condition_term_id( $sub_id, $parsed_condition ) {
		$source_term_id = absint( $sub_id );

		if (
			0 === $source_term_id
			|| ! is_array( $parsed_condition )
			|| ! function_exists( 'pll_get_term_translations' )
		) {
			return $sub_id;
		}

		$condition_name = isset( $parsed_condition['sub_name'] )
			? sanitize_key( $parsed_condition['sub_name'] )
			: '';
		$condition      = self::resolve_taxonomy_condition( $condition_name );

		if ( null === $condition ) {
			return $sub_id;
		}

		$taxonomy = $condition['taxonomy'];
		$mode     = $condition['mode'];

		if (
			function_exists( 'pll_is_translated_taxonomy' )
			&& ! pll_is_translated_taxonomy( $taxonomy )
		) {
			return $sub_id;
		}

		$queried_term = get_queried_object();

		if (
			! $queried_term instanceof \WP_Term
			|| $taxonomy !== $queried_term->taxonomy
		) {
			return $sub_id;
		}

		$candidate_ids = self::get_candidate_term_ids( $queried_term, $taxonomy, $mode );

		foreach ( $candidate_ids as $candidate_id ) {
			if ( in_array( $source_term_id, self::get_translation_group( $candidate_id, $taxonomy ), true ) ) {
				return $candidate_id;
			}
		}

		return $sub_id;
	}

	/**
	 * Resolve an Elementor condition name to a taxonomy and comparison mode.
	 *
	 * @param string $condition_name Elementor parsed sub-condition name.
	 * @return array{taxonomy: string, mode: string}|null
	 */
	private static function resolve_taxonomy_condition( $condition_name ) {
		if ( '' === $condition_name ) {
			return null;
		}

		if ( taxonomy_exists( $condition_name ) ) {
			return array(
				'taxonomy' => $condition_name,
				'mode'     => self::MODE_EXACT,
			);
		}

		$prefixes = array(
			'any_child_of_' => self::MODE_ANY_CHILD,
			'child_of_'     => self::MODE_DIRECT_CHILD,
		);

		foreach ( $prefixes as $prefix => $mode ) {
			if ( 0 !== strpos( $condition_name, $prefix ) ) {
				continue;
			}

			$taxonomy = substr( $condition_name, strlen( $prefix ) );

			if ( '' !== $taxonomy && taxonomy_exists( $taxonomy ) ) {
				return array(
					'taxonomy' => $taxonomy,
					'mode'     => $mode,
				);
			}
		}

		return null;
	}

	/**
	 * Build the term IDs Elementor can legitimately compare for this request.
	 *
	 * @param \WP_Term $queried_term Current queried term.
	 * @param string   $taxonomy     Resolved taxonomy.
	 * @param string   $mode         Comparison mode.
	 * @return int[]
	 */
	private static function get_candidate_term_ids( $queried_term, $taxonomy, $mode ) {
		$current_term_id = absint( $queried_term->term_id );
		$cache_key       = $taxonomy . ':' . $current_term_id . ':' . $mode;

		if ( isset( self::$candidate_sets[ $cache_key ] ) ) {
			return self::$candidate_sets[ $cache_key ];
		}

		if ( self::MODE_EXACT === $mode ) {
			self::$candidate_sets[ $cache_key ] = array( $current_term_id );

			return self::$candidate_sets[ $cache_key ];
		}

		$parent_id = absint( $queried_term->parent );

		if ( 0 === $parent_id ) {
			self::$candidate_sets[ $cache_key ] = array();

			return self::$candidate_sets[ $cache_key ];
		}

		if ( self::MODE_DIRECT_CHILD === $mode ) {
			$parent = get_term( $parent_id, $taxonomy );

			self::$candidate_sets[ $cache_key ] = $parent instanceof \WP_Term
				? array( $parent_id )
				: array();

			return self::$candidate_sets[ $cache_key ];
		}

		$candidate_ids = array();
		$visited_ids   = array();

		while ( 0 < $parent_id && ! isset( $visited_ids[ $parent_id ] ) ) {
			$visited_ids[ $parent_id ] = true;
			$parent                    = get_term( $parent_id, $taxonomy );

			if ( ! $parent instanceof \WP_Term ) {
				break;
			}

			$candidate_ids[] = $parent_id;
			$parent_id       = absint( $parent->parent );
		}

		self::$candidate_sets[ $cache_key ] = $candidate_ids;

		return self::$candidate_sets[ $cache_key ];
	}

	/**
	 * Get a Polylang translation group once per candidate term per request.
	 *
	 * @param int    $term_id  Candidate term ID.
	 * @param string $taxonomy Candidate taxonomy.
	 * @return int[]
	 */
	private static function get_translation_group( $term_id, $taxonomy ) {
		$cache_key = $taxonomy . ':' . $term_id;

		if ( ! isset( self::$translation_groups[ $cache_key ] ) ) {
			$translations = pll_get_term_translations( $term_id );

			self::$translation_groups[ $cache_key ] = is_array( $translations )
				? array_map( 'absint', array_values( $translations ) )
				: array();
		}

		return self::$translation_groups[ $cache_key ];
	}
}

Plugin::boot();
