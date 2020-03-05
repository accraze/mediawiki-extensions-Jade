/**
 * Render Jade Entity page content.
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */
$( function () {
	'use strict';
	var DiffWidget = require( 'jade.widgets' ).DiffWidget;
	var FacetListWidget = require( 'jade.widgets' ).FacetListWidget;

	this.diff = new DiffWidget();

	this.facetsList = new FacetListWidget( {
		entityData: mw.config.get( 'entityData' ) || {}
	} );

	this.stack = new OO.ui.StackLayout( {
		items: [
			new OO.ui.PanelLayout( {
				classes: [ 'jade-entity-diff-panel' ],
				$content: this.diff.$element,
				padded: true,
				scrollable: true,
				expanded: true
			} )
		],
		continuous: true,
		classes: [ 'jade-entity-view-stack' ]
	} );

	var hr = '<hr class="jade-entity-view-split" />';
	// eslint-disable-next-line no-jquery/no-global-selector
	$( '#mw-content-text' ).append(
		this.stack.$element,
		$( hr ),
		this.facetsList.$element
	);

} );
