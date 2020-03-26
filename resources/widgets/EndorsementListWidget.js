'use strict';

/**
 * Widget for displaying a list of proposal endorsements.
 *
 * @extends OO.ui.SelectWidget
 *
 * @constructor
 * @param {Object} [config]
 * @cfg {jQuery} $element
 *
 * @classdesc Widget for displaying a list of proposal endorsements.
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var EndorsementListWidget = function EndorsementListWidget( config ) {
	config = config || {};

	// Call parent constructor
	EndorsementListWidget.parent.call( this, config );

	this.aggregate( {
		delete: 'itemDelete'
	} );

	this.connect( this, {
		itemDelete: 'onItemDelete'
	} );

	this.$element
		.addClass( 'jade-endorsementListWidget' );

};

OO.inheritClass( EndorsementListWidget, OO.ui.SelectWidget );

EndorsementListWidget.prototype.onItemDelete = function ( endorsementWidget ) {
	this.removeItems( [ endorsementWidget ] );
};

module.exports = EndorsementListWidget;
