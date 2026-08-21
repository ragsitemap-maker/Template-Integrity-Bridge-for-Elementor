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

assert.match( plugin, /Version:\s*1\.4\.0/ );
assert.match( plugin, /const VERSION\s*=\s*'1\.4\.0';/ );
assert.match( wordpressReadme, /Stable tag:\s*1\.4\.0/ );
assert.match( wordpressReadme, /= 1\.4\.0 =/ );
assert.match( publicReadme, /Version `1\.4\.0`/ );
assert.match( publicReadmeZhTw, /`1\.4\.0`/ );
assert.match( plugin, /'acf\/pre_load_post_id'[\s\S]*?normalize_archive_term_post_id[\s\S]*?20,[\s\S]*?2/ );
assert.match( plugin, /public static function normalize_archive_term_post_id\( \$preload, \$post_id \)/ );
assert.match( plugin, /preg_match\( '\/\^\[0-9\]\+\$\/D', \$preload \)/ );
assert.doesNotMatch( plugin, /elementor\/frontend\/widget\/(?:before|after)_render/ );
assert.doesNotMatch( plugin, /peab_bridge_archive_acf_term_context/ );
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
