'use strict';

/**
 * Widget for a list of proposals within a facet.
 *
 * @extends OO.ui.SelectWidget
 *
 * @constructor
 * @param {Object} [config]
 * @cfg {jQuery} $element
 * @cfg {Object} [proposals] A list of proposals in a facet.
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var ProposalWidget = require( './ProposalWidget.js' );
var ProposeNewLabelDialog = require( 'jade.dialogs' ).ProposeNewLabelDialog;

var ProposalListWidget = function ProposalListWidget( config ) {
	config = config || {};

	this.proposals = config.proposals;

	// Call parent constructor
	ProposalListWidget.parent.call( this, config );

	this.aggregate( {
		delete: 'itemDelete'
	} );

	this.connect( this, {
		itemDelete: 'onItemDelete'
	} );
	this.proposeNewLabelButton = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposalListWidget-proposeNewLabelBtn' ],
		align: 'bottom',
		icon: 'add',
		label: mw.message( 'jade-ui-propose-new-label-btn' ).text(),
		flags: 'progressive'
	} );
	var numProposal = this.proposals.length;
	if ( numProposal === 0 ) {
		this.proposeNewLabelButton.setFlags( 'primary' );
	}
	this.alternativesButton = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposalListWidget-alternativesBtn' ],
		framed: false,
		align: 'bottom',
		label: mw.message( 'jade-ui-proposal-alternatives' ).text() + ' (' + ( numProposal - 1 ) + ')'
	} );
	this.expandIcon = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposalListWidget-alternativesBtn-expandBtn' ],
		framed: false,
		icon: 'expand'
	} );
	this.collapseIcon = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposalListWidget-alternativesBtn-collapseBtn' ],
		framed: false,
		icon: 'collapse'
	} );
	this.collapseIcon.toggle();
	if ( numProposal === 1 || numProposal === 0 ) {
		this.alternativesButton.toggle();
		this.expandIcon.toggle();
	}
	this.proposals.sort( function ( a, b ) {
		return b.preferred - a.preferred;
	} );

	this.preferredProposal = [];
	this.nonPreferredProposals = [];
	for ( var proposalIdx in this.proposals ) {
		var proposal = this.proposals[ proposalIdx ];
		var item = new ProposalWidget( {
			proposal: proposal,
			proposals: this.proposals,
			numProposal: numProposal,
			visible: ( proposalIdx > 0 )
		} );
		if ( proposalIdx > 0 ) {
			item.toggle();
		}
		if ( item.visible ) {
			this.preferredProposal.push( item );
		} else {
			this.nonPreferredProposals.push( item );
		}
	}

	this.$element
		.addClass( 'jade-proposalListWidget' )
		.append( this.addItems( this.preferredProposal ).$element )
		.append( this.alternativesButton.$element )
		.append( this.expandIcon.$element )
		.append( this.collapseIcon.$element )
		.append( this.addItems( this.nonPreferredProposals ).$element )
		.append( $( '<p>' ) )
		.append( this.proposeNewLabelButton.$element );

	this.proposeNewLabelButton.connect( this, {
		click: 'onProposeNewLabelButtonClick'
	} );
	this.alternativesButton.connect( this, {
		click: 'onAlternativesButtonClick'
	} );
	this.expandIcon.connect( this, {
		click: 'onAlternativesButtonClick'
	} );
	this.collapseIcon.connect( this, {
		click: 'onAlternativesButtonClick'
	} );

};

OO.inheritClass( ProposalListWidget, OO.ui.SelectWidget );

ProposalListWidget.prototype.onItemDelete = function ( proposalWidget ) {
	this.removeItems( [ proposalWidget ] );
};

ProposalListWidget.prototype.onAlternativesButtonClick = function () {
	for ( var idx in this.items ) {
		if ( idx > 0 ) {
			var item = this.items[ idx ];
			item.toggle();
			this.expandIcon.toggle();
			this.collapseIcon.toggle();
		}
	}
};

ProposalListWidget.prototype.onProposeNewLabelButtonClick = function () {
	var windowManager = new OO.ui.WindowManager();
	$( document.body ).append( windowManager.$element );
	// Create a new dialog window.
	var processDialog = new ProposeNewLabelDialog( {
		size: 'large'
	} );

	// Add windows to window manager using the addWindows() method.
	windowManager.addWindows( [ processDialog ] );

	// Open the window.
	windowManager.openWindow( processDialog );
};

module.exports = ProposalListWidget;
