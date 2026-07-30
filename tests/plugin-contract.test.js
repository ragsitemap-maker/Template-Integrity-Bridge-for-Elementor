'use strict';

const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );

const plugin = fs.readFileSync(
	'polylang-elementor-archive-bridge/polylang-elementor-archive-bridge.php',
	'utf8'
);

assert.match( plugin, /Version:\s*1\.3\.0/ );
assert.match( plugin, /const VERSION\s*=\s*'1\.3\.0';/ );
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
