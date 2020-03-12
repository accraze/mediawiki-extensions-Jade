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

	var $hrElement = $( '<hr>' ).addClass( 'jade-entity-view-split' );
	// eslint-disable-next-line no-jquery/no-global-selector
	if ( $( '.noarticletext' )[ 0 ] ) {
	// eslint-disable-next-line no-jquery/no-global-selector
		$( '.noarticletext' ).hide();
		$hrElement.hide();
	}

	this.diff = new DiffWidget();
	this.loadEntityData = function () {
		var data = mw.config.get( 'entityData' );
		if ( Object.keys( data ).length === 0 || data === '{}' ) {
			data = { facets: { editquality: { proposals: [] } } };
		}
		return data;
	};

	this.facetsList = new FacetListWidget( {
		entityData: this.loadEntityData()
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

	// eslint-disable-next-line no-jquery/no-global-selector
	$( '#mw-content-text' ).append(
		this.stack.$element,
		$hrElement,
		this.facetsList.$element
	);

} );
