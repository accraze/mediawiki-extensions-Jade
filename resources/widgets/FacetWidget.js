'use strict';

/**
 * Widget for a single facet within a Jade entity.
 *
 * @extends OO.ui.OptionWidget
 *
 * @constructor
 * @param {Object} [config]
 * @cfg {jQuery} $element
 * @cfg {Object} [entityData] Jade entity data sent from server as JSON
 *
 * @classdesc Widget for a single facet within a Jade entity.
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var ProposalListWidget = require( './ProposalListWidget.js' );

var FacetWidget = function ( config ) {
	config = config || {};
	FacetWidget.parent.call( this, config );

	this.facet = config.facet;
	this.facetName = config.facetName;
	this.proposalList = new ProposalListWidget( {
		proposals: this.facet.proposals
	} );
	this.collapseIcon = new OO.ui.ButtonWidget( {
		classes: [ 'jade-facetWidget-header-collapseBtn' ],
		framed: false,
		icon: 'collapse',
		title: 'collapse',
		align: 'left'
	} );
	this.expandIcon = new OO.ui.ButtonWidget( {
		classes: [ 'jade-facetWidget-header-expandBtn' ],
		framed: false,
		icon: 'expand',
		title: 'expand',
		align: 'left'
	} );
	this.expandIcon.toggle();
	this.getFacetName = function ( facetName ) {
		if ( facetName === 'editquality' ) {
			return mw.message( 'jade-ui-editquality' ).text();
		}
	};
	this.label1 = new OO.ui.LabelWidget( {
		classes: [ 'jade-facetWidget-header-facetLabel' ],
		label: $( '<b>' ).text( this.getFacetName( this.facetName ) ),
		align: 'left'
	} );

	this.facetHeader = new OO.ui.HorizontalLayout( {
		classes: [ 'jade-facetWidget-header' ]
	} );
	this.facetHeader.addItems( [
		// TODO - change the help text once we target more than 1 facet.
		new OO.ui.FieldLayout( this.label1, {
			help: mw.message( 'jade-facet-editquality-desc' ).text(),
			classes: [ 'jade-facet-editquality-desc-popup' ]
		} ),
		new OO.ui.FieldLayout( this.collapseIcon ),
		new OO.ui.FieldLayout( this.expandIcon )
	] );

	this.$element
		.addClass( 'jade-facetWidget' )
		.append( this.facetHeader.$element )
		.append( $( '<hr>' ).addClass( 'jade-facetWidget-line' ) )
		.append( this.proposalList.$element );

	this.expandIcon.connect( this, {
		click: 'onToggleButtonClick'
	} );

	this.collapseIcon.connect( this, {
		click: 'onToggleButtonClick'
	} );
};

OO.inheritClass( FacetWidget, OO.ui.OptionWidget );

/**
 * @description Toggles visibility of proposal list.
 */
FacetWidget.prototype.onToggleButtonClick = function () {
	this.proposalList.toggle();
	this.expandIcon.toggle();
	this.collapseIcon.toggle();
};

FacetWidget.prototype.onEditButtonClick = function () {
	this.emit( 'edit' );
};
FacetWidget.prototype.onMoveButtonClick = function () {
	this.emit( 'move' );
};
FacetWidget.prototype.onDeleteButtonClick = function () {
	this.emit( 'delete' );
};

module.exports = FacetWidget;
