'use strict';

/**
 * Widget for viewing a diff.
 *
 * @extends OO.ui.Element
 *
 * @constructor
 * @param {Object} [config]
 * @cfg {jQuery} $element
 * @cfg {Object} [entityData] Jade entity data sent from server as JSON
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var DiffWidget = function ( config ) {
	config = config || {};
	DiffWidget.super.call( this, config );

	this.btnGroup = new OO.ui.ButtonSelectWidget( {
		items: [
			new OO.ui.ButtonOptionWidget( {
				data: 'visual',
				icon: 'eye',
				label: 'Visual',
				disabled: true
			} ),
			new OO.ui.ButtonOptionWidget( {
				data: 'source',
				icon: 'wikiText',
				label: 'Wikitext'
			} )
		],
		classes: [ 'jade-ui-diffWidget-btn-group' ]
	} );

	this.onButtonSelect = function () {
		// console.log( 'selected' );
	};

	this.btnGroup.on( 'select', this.onButtonSelect );
	this.btnGroup.selectItemByData( 'source' );

	this.call = function () {
		var title = mw.config.get( 'entityTitle' ).mTextform;
		var api = new mw.Api();
		return api.get( {
			action: 'compare',
			fromrev: title.split( '/' )[ 1 ],
			torelative: 'prev',
			prop: 'diff|ids|title|user'
		} )
			.then( function ( data, err ) {
				// console.log( data );
				return data;
			} )
			.catch( function ( err ) { return JSON.stringify( err ); } );
	};

	this.getDiffData = async function () {
		this.data = await this.call();
		this.$element
		.addClass( 'jade-ui-diffWidget' );
		if ( typeof this.data === 'string' ) {
			this.message = new OO.ui.MessageWidget( {
				type: 'error',
				classes: [ 'jade-moveEndorsementDialog-errorMessage' ],
				label: 'No Diff Found'
			} );
			this.$element.append( this.message.$element );
		} else {
			var diffMarkup = '<tr class="diff-title" lang="en">' + mw.config.get( 'diffHeader' ) + '</tr>' + this.data.compare[ '*' ];
			this.$element.append( this.btnGroup.$element )
		// .append( $(mw.config.get('diffHeader')) )
		.append( '<table class="diff diff-contentalign-left">' + '<colgroup>' +
			'<col class="diff-marker">' + '<col class="diff-content">' + '<col class="diff-marker">'
			+ '<col class="diff-content">' + '</colgroup>' + '<tbody>'
			+ diffMarkup + '</tbody>' + '</table>' );
		}
	};

	this.getDiffData();

};

OO.inheritClass( DiffWidget, OO.ui.Element );

module.exports = DiffWidget;
