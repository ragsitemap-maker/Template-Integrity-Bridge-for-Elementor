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

assert.match( plugin, /Version:\s*1\.4\.4/ );
assert.match( plugin, /const VERSION\s*=\s*'1\.4\.4';/ );
assert.match( wordpressReadme, /Stable tag:\s*1\.4\.4/ );
assert.match( wordpressReadme, /= 1\.4\.4 =/ );
assert.match( publicReadme, /Version `1\.4\.4`/ );
assert.match( publicReadmeZhTw, /`1\.4\.4`/ );
assert.match(
	plugin,
	/const OPTION_ARCHIVE_ACF_TERM_CORRECTION\s*=\s*'peab_enable_archive_acf_term_correction';/
);
assert.match(
	plugin,
	/const SETTINGS_SECTION_ARCHIVE_ACF\s*=\s*'peab_archive_acf_term_correction';/
);
assert.match(
	plugin,
	/if \( self::is_archive_acf_term_correction_enabled\(\) \) \{[\s\S]*?'elementor\/frontend\/widget\/before_render'[\s\S]*?'elementor\/frontend\/widget\/after_render'[\s\S]*?'acf\/pre_load_post_id'[\s\S]*?\}/
);
assert.match(
	plugin,
	/OPTION_ARCHIVE_ACF_TERM_CORRECTION[\s\S]{0,500}?'default'\s*=>\s*1/
);
assert.match( plugin, /add_option\( self::OPTION_ARCHIVE_ACF_TERM_CORRECTION, 1, '', true \)/ );
assert.match( plugin, /class_exists\( '\\Elementor\\Plugin', false \)/ );
assert.match( plugin, /catch \( \\Throwable \$throwable \)/ );
assert.match( plugin, /! is_string\( \$document_type \)/ );
assert.doesNotMatch( plugin, /ELEMENTOR_PRO_VERSION|version_compare\s*\(/ );
assert.doesNotMatch(
	plugin,
	/update_option\(\s*self::OPTION_ARCHIVE_ACF_TERM_CORRECTION/
);
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
assert.match( wordpressReadme, /Later runtime evidence showed this path was still incomplete\./ );
assert.match( wordpressReadme, /runtime evidence disproved that preview-query hook/ );
assert.match(
	plugin,
	/const OPTION_NESTED_LOOP_PROTECTION\s*=\s*'peab_protect_nested_loop_conditions';/
);
assert.match(
	plugin,
	/get_option\(\s*self::OPTION_NESTED_LOOP_PROTECTION,\s*0\s*\)/
);
assert.match(
	plugin,
	/'elementor\/editor\/after_enqueue_scripts'/
);
assert.match(
	plugin,
	/array\(\s*'jquery',\s*'elementor-pro'\s*\)/
);
assert.match(
	plugin,
	/plugins_url\(\s*'assets\/js\/nested-loop-conditions-save-protection\.js',\s*__FILE__\s*\)/
);
assert.match(
	plugin,
	/'default'\s*=>\s*0/
);

process.stdout.write( 'All plugin contract checks passed.\n' );
