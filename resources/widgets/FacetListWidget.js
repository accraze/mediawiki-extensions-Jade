'use strict';

/**
 * Widget for displaying a list of facets within a Jade entity.
 *
 * @extends OO.ui.SelectWidget
 *
 * @constructor
 * @param {Object} [config]
 * @cfg {jQuery} $element
 * @cfg {Object} [entityData] Jade entity data sent from server as JSON
 *
 * @classdesc Widget for displaying a list of facets.
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var FacetWidget = require( './FacetWidget.js' );

var FacetListWidget = function FacetListWidget( config ) {
	config = config || {};

	this.entityData = config.entityData;

	// Call parent constructor
	FacetListWidget.parent.call( this, config );

	this.aggregate( {
		delete: 'facetDelete'
	} );

	this.connect( this, {
		itemDelete: 'onFacetDelete'
	} );

	this.$element
		.addClass( 'jade-facetListWidget' );

	// build the list of facets
	for ( var key in this.entityData.facets ) {
		var facet = this.entityData.facets[ key ];
		this.addItems( [
			new FacetWidget( {
				facet: facet,
				facetName: key
			} )
		] );
	}
};

OO.inheritClass( FacetListWidget, OO.ui.SelectWidget );

FacetListWidget.prototype.onFacetDelete = function ( facetWidget ) {
	this.removeItems( [ facetWidget ] );
};

module.exports = FacetListWidget;
