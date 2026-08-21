'use strict';

const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );

const plugin = fs.readFileSync(
	'polylang-elementor-archive-bridge/polylang-elementor-archive-bridge.php',
	'utf8'
);
const wordpressReadme = fs.readFileSync( 'polylang-elementor-archive-bridge/readme.txt', 'utf8' );
const publicReadme = fs.readFileSync( 'README.md', 'utf8' );
const publicReadmeZhTw = fs.readFileSync( 'README.zh-TW.md', 'utf8' );
const nestedLoopScript = fs.readFileSync(
	'polylang-elementor-archive-bridge/assets/js/nested-loop-conditions-save-protection.js',
	'utf8'
);

const fullName = 'Template Integrity Bridge for Elementor';
const tagline = 'Protects Elementor template selection, render context, condition cache, and nested editor state, including Polylang-aware taxonomy matching.';
const currentWordpressReadme = wordpressReadme.split( '== Changelog ==' )[0];

assert.match( plugin, /^ \* Plugin Name: Template Integrity Bridge for Elementor\r?$/m );
assert.match( plugin, /^ \* Description: Protects Elementor template selection, render context, condition cache, and nested editor state, including Polylang-aware taxonomy matching\.\r?$/m );
assert.match( plugin, /^ \* Version: 1\.5\.0\r?$/m );
assert.match( plugin, /const VERSION\s*=\s*'1\.5\.0';/ );
assert.match(
	plugin,
	/add_options_page\([\s\S]*?esc_html__\( 'Template Integrity Bridge for Elementor',[\s\S]*?esc_html__\( 'Template Integrity',/
);
assert.match( plugin, /get_admin_page_title\(\)/ );
assert.match( plugin, /'plugin_action_links_' \. plugin_basename\( __FILE__ \)/ );

assert.equal( publicReadme.split( /\r?\n/ )[0], `# ${ fullName }` );
assert.equal( publicReadmeZhTw.split( /\r?\n/ )[0], `# ${ fullName }` );
assert.equal( wordpressReadme.split( /\r?\n/ )[0], `=== ${ fullName } ===` );
assert.match( publicReadme, new RegExp( tagline.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) ) );
assert.match( publicReadme, /Formerly Polylang Elementor Archive Bridge\./ );
assert.match( publicReadmeZhTw, /保護 Elementor Template 的選擇、渲染資料 context、Conditions cache 與巢狀編輯狀態，並支援 Polylang taxonomy matching。/ );
assert.match( publicReadmeZhTw, /原名 Polylang Elementor Archive Bridge。/ );
assert.match( publicReadme, /Settings > Template Integrity/ );
assert.doesNotMatch( publicReadme, /Settings > Archive Bridge/ );
assert.match( publicReadmeZhTw, /設定 > Template Integrity/ );
assert.doesNotMatch( publicReadmeZhTw, /設定 > Archive Bridge/ );
assert.match( publicReadme, /Version `1\.5\.0`/ );
assert.match( publicReadmeZhTw, /`1\.5\.0`/ );

assert.match( wordpressReadme, /^Stable tag: 1\.5\.0\r?$/m );
assert.match( wordpressReadme, /^Tags: elementor, templates, theme builder, polylang, acf\r?$/m );
assert.match( wordpressReadme, /= 1\.5\.0 =/ );
assert.match( currentWordpressReadme, /Settings > Template Integrity/ );
assert.doesNotMatch( currentWordpressReadme, /Settings > Archive Bridge/ );
assert.match( currentWordpressReadme, /Formerly Polylang Elementor Archive Bridge\./ );
assert.match( wordpressReadme, /Later runtime evidence showed this path was still incomplete\./ );
assert.match( wordpressReadme, /runtime evidence disproved that preview-query hook/ );

assert.match( plugin, /^ \* Text Domain: polylang-elementor-archive-bridge\r?$/m );
assert.match( plugin, /namespace Polylang_Elementor_Archive_Bridge;/ );
assert.match( plugin, /const SETTINGS_PAGE\s*=\s*'polylang-elementor-archive-bridge';/ );
assert.match( plugin, /const SETTINGS_GROUP\s*=\s*'peab_settings';/ );
assert.match( plugin, /const OPTION_CACHE_PROTECTION\s*=\s*'peab_protect_conditions_cache';/ );
assert.match( plugin, /const OPTION_NESTED_LOOP_PROTECTION\s*=\s*'peab_protect_nested_loop_conditions';/ );
assert.match( plugin, /const OPTION_ARCHIVE_ACF_TERM_CORRECTION\s*=\s*'peab_enable_archive_acf_term_correction';/ );
assert.match( plugin, /const SETTINGS_SECTION\s*=\s*'peab_conditions_cache';/ );
assert.match( plugin, /const SETTINGS_SECTION_NESTED_LOOP\s*=\s*'peab_nested_loop_conditions';/ );
assert.match( plugin, /const SETTINGS_SECTION_ARCHIVE_ACF\s*=\s*'peab_archive_acf_term_correction';/ );
assert.match( plugin, /const NESTED_LOOP_SCRIPT_HANDLE\s*=\s*'peab-nested-loop-conditions-save-protection';/ );
assert.match( nestedLoopScript, /__peabNestedLoopConditionsGuardBooted/ );
assert.match( nestedLoopScript, /__peabNestedLoopConditionsGuardInstalled/ );
assert.match( nestedLoopScript, /elementor:init\.peabNestedLoopConditions/ );

assert.match(
	plugin,
	/if \( self::is_archive_acf_term_correction_enabled\(\) \) \{[\s\S]*?'elementor\/frontend\/widget\/before_render'[\s\S]*?'elementor\/frontend\/widget\/after_render'[\s\S]*?'acf\/pre_load_post_id'[\s\S]*?\}/
);
assert.match( plugin, /OPTION_ARCHIVE_ACF_TERM_CORRECTION[\s\S]{0,500}?'default'\s*=>\s*1/ );
assert.match( plugin, /add_option\( self::OPTION_ARCHIVE_ACF_TERM_CORRECTION, 1, '', true \)/ );
assert.match( plugin, /class_exists\( '\\Elementor\\Plugin', false \)/ );
assert.match( plugin, /catch \( \\Throwable \$throwable \)/ );
assert.match( plugin, /! is_string\( \$document_type \)/ );
assert.doesNotMatch( plugin, /ELEMENTOR_PRO_VERSION|version_compare\s*\(/ );
assert.doesNotMatch( plugin, /update_option\(\s*self::OPTION_ARCHIVE_ACF_TERM_CORRECTION/ );
assert.match( plugin, /'acf\/pre_load_post_id'[\s\S]*?normalize_archive_term_post_id[\s\S]*?20,[\s\S]*?2/ );
assert.match( plugin, /public static function normalize_archive_term_post_id\( \$preload, \$post_id \)/ );
assert.match( plugin, /preg_match\( '\/\^\[0-9\]\+\$\/D', \$value \)/ );
assert.match( plugin, /'elementor\/frontend\/widget\/before_render'/ );
assert.doesNotMatch( plugin, /'elementor\/template-library\/before_get_source_data'/ );
assert.match( plugin, /'elementor\/frontend\/widget\/after_render'/ );
assert.match( plugin, /null === \$preload/ );
assert.match( plugin, /'loop-item' === \$document\['type'\]/ );
assert.match( plugin, /\$context\['template_id'\] === \$document\['id'\]/ );
assert.match( plugin, /private static \$template_archive_contexts = array\(\);/ );
assert.doesNotMatch( plugin, /'elementor_library' === \$post_id->post_type/ );
assert.doesNotMatch( plugin, /switch_to_query|restore_current_query/ );
assert.doesNotMatch( plugin, /peab_bridge_archive_acf_term_context/ );
assert.match( plugin, /get_option\(\s*self::OPTION_NESTED_LOOP_PROTECTION,\s*0\s*\)/ );
assert.match( plugin, /'elementor\/editor\/after_enqueue_scripts'/ );
assert.match( plugin, /array\(\s*'jquery',\s*'elementor-pro'\s*\)/ );
assert.match( plugin, /plugins_url\(\s*'assets\/js\/nested-loop-conditions-save-protection\.js',\s*__FILE__\s*\)/ );

process.stdout.write( 'All plugin contract checks passed.\n' );