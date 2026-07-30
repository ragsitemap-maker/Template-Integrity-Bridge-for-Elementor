'use strict';

const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const vm = require( 'node:vm' );

const guardSource = fs.readFileSync(
	'polylang-elementor-archive-bridge/assets/js/nested-loop-conditions-save-protection.js',
	'utf8'
);

function createDeferred() {
	return {
		resolved: false,
		resolve() {
			this.resolved = true;
			return this;
		},
	};
}

function createContext( documentType = 'archive', withElementorPro = true ) {
	const state = {
		documentType,
		requests: [],
		handlers: {},
	};

	function jquery( target ) {
		return {
			on( eventName, callback ) {
				state.handlers[ eventName ] = { target, callback };
			},
		};
	}

	jquery.Deferred = createDeferred;

	const window = {
		elementor: {
			documents: {
				getCurrent() {
					return {
						config: {
							type: state.documentType,
						},
					};
				},
			},
		},
	};

	if ( withElementorPro ) {
		window.elementorPro = {
			ajax: {
				addRequest( action, options, immediately ) {
					state.requests.push( { action, options, immediately } );
					return createDeferred();
				},
			},
		};
	}

	const context = {
		jQuery: jquery,
		window,
	};

	vm.createContext( context );
	vm.runInContext( guardSource, context );

	return { context, state, window };
}

{
	const { state, window } = createContext( 'loop-item' );
	const result = window.elementorPro.ajax.addRequest(
		'theme_builder_save_conditions',
		{ data: { conditions: [] } }
	);

	assert.equal( result.resolved, true );
	assert.equal( state.requests.length, 0 );
}

{
	const { state, window } = createContext( 'loop-item' );
	window.elementorPro.ajax.addRequest(
		'query_control_value_titles',
		{ data: { ids: [ 1 ] } }
	);

	assert.equal( state.requests.length, 1 );
	assert.equal( state.requests[ 0 ].action, 'query_control_value_titles' );
}

{
	const { state, window } = createContext( 'archive' );
	window.elementorPro.ajax.addRequest(
		'theme_builder_save_conditions',
		{ data: { conditions: [] } }
	);

	assert.equal( state.requests.length, 1 );
	assert.equal( state.requests[ 0 ].action, 'theme_builder_save_conditions' );
}

{
	const { state, window } = createContext( 'loop-item' );
	window.elementor.documents.getCurrent = function() {
		throw new Error( 'Future Elementor API mismatch.' );
	};

	window.elementorPro.ajax.addRequest(
		'theme_builder_save_conditions',
		{ data: { conditions: [] } }
	);

	assert.equal( state.requests.length, 1 );
}

{
	const { context, window } = createContext( 'archive' );
	const wrappedAddRequest = window.elementorPro.ajax.addRequest;

	vm.runInContext( guardSource, context );

	assert.equal( window.elementorPro.ajax.addRequest, wrappedAddRequest );
}

{
	const { context, state, window } = createContext( 'loop-item', false );

	assert.ok( state.handlers['elementor:init.peabNestedLoopConditions'] );

	window.elementorPro = {
		ajax: {
			addRequest( action, options ) {
				state.requests.push( { action, options } );
				return createDeferred();
			},
		},
	};

	state.handlers['elementor:init.peabNestedLoopConditions'].callback();
	const result = window.elementorPro.ajax.addRequest(
		'theme_builder_save_conditions',
		{ data: { conditions: [] } }
	);

	assert.equal( result.resolved, true );
	assert.equal( state.requests.length, 0 );
	assert.ok( context.window.__peabNestedLoopConditionsGuardBooted );
}

process.stdout.write( 'All nested Loop conditions-save protection checks passed.\n' );
