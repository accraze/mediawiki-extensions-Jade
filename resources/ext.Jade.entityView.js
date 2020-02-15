/**
 * Render Jade Entity page content.
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */
$( function () {
	'use strict';
	var FacetListWidget = require( 'jade.widgets' ).FacetListWidget;

	this.facetsList = new FacetListWidget( {
		entityData: mw.config.get( 'entityData' ) || {}
	} );

	$( '#mw-content-text' ).append(
		this.facetsList.$element
	);

} );