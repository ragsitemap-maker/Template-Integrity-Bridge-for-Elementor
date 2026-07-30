( function( $, window ) {
	'use strict';

	var BOOT_MARKER = '__peabNestedLoopConditionsGuardBooted';
	var WRAP_MARKER = '__peabNestedLoopConditionsGuardInstalled';
	var CONDITIONS_ACTION = 'theme_builder_save_conditions';
	var LOOP_DOCUMENT_TYPE = 'loop-item';

	function getCurrentDocumentType() {
		try {
			if (
				! window.elementor ||
				! window.elementor.documents ||
				'function' !== typeof window.elementor.documents.getCurrent
			) {
				return '';
			}

			var currentDocument = window.elementor.documents.getCurrent();

			return currentDocument && currentDocument.config
				? currentDocument.config.type || ''
				: '';
		} catch ( error ) {
			return '';
		}
	}

	function installGuard() {
		if (
			! window.elementorPro ||
			! window.elementorPro.ajax ||
			'function' !== typeof window.elementorPro.ajax.addRequest
		) {
			return false;
		}

		var ajax = window.elementorPro.ajax;

		if ( ajax[ WRAP_MARKER ] ) {
			return true;
		}

		var originalAddRequest = ajax.addRequest;

		ajax.addRequest = function( action ) {
			if (
				CONDITIONS_ACTION === action &&
				LOOP_DOCUMENT_TYPE === getCurrentDocumentType()
			) {
				return $.Deferred().resolve();
			}

			return originalAddRequest.apply( this, arguments );
		};

		ajax[ WRAP_MARKER ] = true;

		return true;
	}

	if ( window[ BOOT_MARKER ] ) {
		return;
	}

	window[ BOOT_MARKER ] = true;

	if ( ! installGuard() ) {
		$( window ).on( 'elementor:init.peabNestedLoopConditions', installGuard );
	}
}( jQuery, window ) );
