'use strict';

/**
 * Widget for rendering a diff.
 *
 * @extends OO.ui.Element
 *
 * @constructor
 * @param {Object} [config]
 * @cfg {jQuery} $element
 * @cfg {Object} [entityData] Jade entity data sent from server as JSON
 *
 * @classdesc Widget for rendering a diff.
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

	/**
	 * Call compare api to retrieve diff html markup.
	 *
	 * @function callCompare
	 * @description Call compare api to retrieve diff html markup.
	 * @returns {Promise} Promise object represents diff html markup.
	 */
	this.callCompare = function () {
		var title = mw.config.get( 'entityTitle' ).mTextform;
		var api = new mw.Api();
		return api.get( {
			action: 'compare',
			fromrev: title.split( '/' )[ 1 ],
			torelative: 'prev',
			prop: 'diff|ids|title|user'
		} )
			.then( function ( data, err ) {
				return data;
			} )
			.catch( function ( err ) { return JSON.stringify( err ); } );
	};

	/**
	 * Retrieve diff data and populate widget html markup
	 *
	 * @async
	 * @function loadDiffData
	 * @description Retrieve diff data and populate widget html markup
	 */
	this.loadDiffData = async function () {
		this.data = await this.callCompare();
		this.$element
		.addClass( 'jade-ui-diffWidget' );
		if ( typeof this.data === 'string' ) {
			// display error
			this.message = new OO.ui.MessageWidget( {
				type: 'error',
				classes: [ 'jade-moveEndorsementDialog-errorMessage' ],
				label: 'No Diff Found'
			} );
			this.$element.append( this.message.$element );
		} else {
			// populate diff markup
			var header = mw.config.get( 'diffHeader', '' );
			var diffData = this.data.compare[ '*' ];
			if ( header.indexOf( 'diff-otitle' ) === -1 ) {
				var label = new OO.ui.LabelWidget( {
					classes: [ 'jade-ui-diffWidget-noDiff' ],
					label: '(No Difference)'
				} ).$element.html();
				diffData = '<tr><td class="diff-notice" colspan="2"><div class="mw-diff-empty">' + label + '</div></td></tr>';
			}
			var diffMarkup = '<tr class="diff-title" lang="en">' + header + '</tr>' + diffData;
			this.$element.append( this.btnGroup.$element )
			.append( '<table class="diff diff-contentalign-left">' + '<colgroup>' +
			'<col class="diff-marker">' + '<col class="diff-content">' + '<col class="diff-marker">'
			+ '<col class="diff-content">' + '</colgroup>' + '<tbody>'
			+ diffMarkup + '</tbody>' + '</table>' );
		}
	};

	this.loadDiffData();

};

OO.inheritClass( DiffWidget, OO.ui.Element );

module.exports = DiffWidget;
