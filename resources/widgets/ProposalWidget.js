'use strict';

/**
 * Widget for a single proposal.
 *
 * @extends OO.ui.OptionWidget
 *
 * @constructor
 * @param {Object} [config]
 * @cfg {jQuery} $element
 * @cfg {Object} [creationTime] timestamp showing when proposal was made.
 * @cfg {Object} [proposal] Object containing proposal data.
 * @cfg {Object} [numProposal] integer describing the total number of proposals
 * in this facet.
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var UpdateProposalClient = require( 'jade.api' ).UpdateProposalClient;

var EndorsementListWidget = require( './EndorsementListWidget.js' );
var EndorsementWidget = require( './EndorsementWidget.js' );

var EndorseDialog = require( 'jade.dialogs' ).EndorseDialog;
var DeleteProposalDialog = require( 'jade.dialogs' ).DeleteProposalDialog;
var PromoteDialog = require( 'jade.dialogs' ).PromoteDialog;

var ProposalWidget = function ( config ) {
	config = config || {};
	ProposalWidget.parent.call( this, config );

	this.creationTime = config.creationTime;
	this.proposal = config.proposal;
	this.proposals = config.proposals;
	this.numProposal = config.numProposal;
	this.menuEdit = mw.message( 'jade-ui-menu-edit' ).text();
	this.menuPromote = mw.message( 'jade-ui-menu-promote' ).text();
	this.menuDelete = mw.message( 'jade-ui-menu-delete' ).text();
	this.editButton = new OO.ui.OptionWidget( {
		classes: [ 'jade-proposalWidget-menu-edit' ],
		data: this,
		label: this.menuEdit

	} );
	this.promoteButton = new OO.ui.OptionWidget( {
		classes: [ 'jade-proposalWidget-menu-promote' ],
		data: this,
		label: this.menuPromote,
		disabled: this.proposal.preferred
	} );
	this.deleteProposalButton = new OO.ui.OptionWidget( {
		classes: [ 'jade-proposalWidget-menu-delete' ],
		data: this,
		label: this.menuDelete
	} );
	this.menuStack = new OO.ui.SelectWidget( {
		items: [ this.editButton, this.promoteButton, this.deleteProposalButton ],
		classes: [ 'jade-proposalWidget-menu' ]
	} );
	this.menuStack.on( 'choose', function ( cmd ) {
		var windowManager = new OO.ui.WindowManager();
		var processDialog;
		if ( cmd.label === cmd.data.menuDelete ) {
			$( document.body ).append( windowManager.$element );
			// Create a new dialog window.
			processDialog = new DeleteProposalDialog( {
				size: 'large',
				proposal: cmd.data.proposal
			} );

			// Add windows to window manager using the addWindows() method.
			windowManager.addWindows( [ processDialog ] );

			// Open the window.
			windowManager.openWindow( processDialog );
		} else if ( cmd.label === cmd.data.menuPromote ) {
			$( document.body ).append( windowManager.$element );
			// Create a new dialog window.
			processDialog = new PromoteDialog( {
				size: 'large',
				proposal: cmd.data.proposal
			} );

			// Add windows to window manager using the addWindows() method.
			windowManager.addWindows( [ processDialog ] );

			// Open the window.
			windowManager.openWindow( processDialog );
		} else if ( cmd.label === cmd.data.menuEdit ) {
			cmd.data.menuButton.toggle();
			cmd.data.notesLabel.toggle();
			cmd.data.editForm.toggle();
			cmd.data.editBox.focus();
		}
	} );
	this.menuButton = new OO.ui.PopupButtonWidget( {
		classes: [ 'jade-proposalWidget-menuBtn' ],
		framed: false,
		icon: 'ellipsis',
		popup: {
			$content: this.menuStack.$element,
			padded: true,
			anchor: false,
			align: 'forward',
			width: '90px'
		}
	} );

	this.renderLabel = function () {
		var icon1 = 'articleCheck';
		var label1 = mw.message( 'jade-ui-productive-label' ).text();
		var flags1 = 'progressive';
		if ( this.proposal.labeldata.damaging ) {
			icon1 = 'error';
			label1 = mw.message( 'jade-ui-damaging-label' ).text();
			flags1 = [ 'destructive', 'error' ];
		}
		this.damagingButton = new OO.ui.ButtonWidget( {
			classes: [ 'jade-proposalWidget-label-damaging' ],
			framed: false,
			align: 'right',
			icon: icon1,
			label: label1,
			flags: flags1
		} );
		var icon2 = 'heart';
		var label2 = mw.message( 'jade-ui-goodfaith-label' ).text();
		var flags2 = 'progressive';
		if ( !this.proposal.labeldata.goodfaith ) {
			icon2 = 'userAnonymous';
			label2 = mw.message( 'jade-ui-badfaith-label' ).text();
			flags2 = '';
		}
		this.goodfaithButton = new OO.ui.ButtonWidget( {
			classes: [ 'jade-proposalWidget-label-goodfaith' ],
			framed: false,
			icon: icon2,
			label: label2,
			flags: flags2
		} );
		this.labelStr = new OO.ui.LabelWidget( { label: $( '<b>' ).text( 'Label:' ) } );
		this.labelHeader = new OO.ui.HorizontalLayout( {
			classes: [ 'jade-proposalWidget-endorsements-label' ]
		} );

		this.labelHeader.addItems( [
			this.labelStr,
			this.damagingButton,
			this.goodfaithButton,
			this.menuButton
		] );
	};
	this.renderLabel();
	this.editFormSubmit = new OO.ui.ButtonInputWidget( {
		classes: [ 'jade-proposalWidget-editForm-submitBtn' ],
		type: 'submit',
		name: 'publish',
		label: mw.message( 'jade-ui-edit-publish-btn' ).text(),
		flags: [
			'primary',
			'progressive'
		],
		align: 'right'
	} );
	this.editFormCancel = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposalWidget-editForm-cancelBtn' ],
		framed: false,
		label: mw.message( 'jade-ui-cancel-btn' ).text()
	} );
	this.editBox = new OO.ui.MultilineTextInputWidget( {
		classes: [ 'jade-proposalWidget-editForm-text' ],
		placeholder: mw.message( 'jade-ui-proposenewlabel-comment-placeholder' ).text(),
		value: this.proposal.notes
	} );

	this.editForm = new OO.ui.FieldsetLayout( {
		classes: [ 'jade-proposalWidget-editForm' ],
		label: null,
		items: [
			new OO.ui.FieldLayout( this.editBox, {
				align: 'top',
				label: null
			} ),
			new OO.ui.FieldLayout( new OO.ui.Widget( {
				classes: [ 'jade-proposalWidget-editForm-buttons' ],
				content: [
					new OO.ui.HorizontalLayout( {
						items: [
							this.editFormCancel,
							this.editFormSubmit
						]
					} )
				]
			} ), {
				align: 'top',
				label: null
			} )
		]
	} );
	this.editForm.toggle();
	this.notesLabel = new OO.ui.LabelWidget( {
		classes: [ 'jade-proposalWidget-notes' ],
		label: this.proposal.notes
	} );
	this.endorsementsButton = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposalWidget-endorsementsButton' ],
		framed: false,
		label: mw.message( 'jade-ui-proposal-endorsements' ).text() + ' (' + this.proposal.endorsements.length + ')'
	} );
	this.collapseIcon = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposalWidget-endorsements-collapseBtn' ],
		framed: false,
		icon: 'collapse',
		title: 'collapse',
		align: 'left'
	} );
	this.expandIcon = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposalWidget-endorsements-expandBtn' ],
		framed: false,
		icon: 'expand',
		title: 'expand',
		align: 'left'
	} );
	this.collapseIcon.toggle();
	// if ( this.numProposal == 1 ) {
	// this.expandIcon.toggle();
	// }
	this.endorseButton = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposalWidget-endorseBtn' ],
		icon: 'add',
		label: '1',
		flags: [
			'progressive'
		]
	} );
	this.header = new OO.ui.HorizontalLayout( {
		classes: [ 'jade-proposalWidget-endorsements-toggle' ]
	} );
	this.header.addItems( [
		this.endorsementsButton,
		new OO.ui.FieldLayout( this.collapseIcon ),
		new OO.ui.FieldLayout( this.expandIcon ),
		this.endorseButton
	] );

	this.endorsementList = new EndorsementListWidget( {
	} );
	this.endorsementList.toggle();
	for ( var eIdx in this.proposal.endorsements ) {
		var endorsement = this.proposal.endorsements[ eIdx ];
		this.endorsementList.addItems( [
			new EndorsementWidget( {
				endorsement: endorsement,
				proposal: this.proposal,
				proposals: this.proposals,
				numProposal: this.numProposal
			} )
		] );
	}

	this.$element
		.addClass( 'jade-proposalWidget' )
		.append( this.labelHeader.$element )
		.append( this.editForm.$element )
		.append( this.notesLabel.$element )
		.append( this.header.$element )
		.append( this.endorsementList.$element );

	this.endorseButton.connect( this, {
		click: 'onEndorseButtonClick'
	} );
	this.expandIcon.connect( this, {
		click: 'onToggleButtonClick'
	} );

	this.collapseIcon.connect( this, {
		click: 'onToggleButtonClick'
	} );
	this.editFormSubmit.connect( this, {
		click: 'onSubmitButtonClick'
	} );

	this.editFormCancel.connect( this, {
		click: 'onCancelButtonClick'
	} );

};

OO.inheritClass( ProposalWidget, OO.ui.OptionWidget );

ProposalWidget.prototype.onSubmitButtonClick = function () {
	var params = {
		title: mw.config.get( 'entityTitle' ).prefixedText,
		facet: 'editquality', // TODO - remove this hardcoding
		labeldata: JSON.stringify( this.proposal.labeldata ),
		notes: this.editBox.value
	};
	var client = new UpdateProposalClient( params );
	var res = client.execute( params );
	Promise.resolve( res );
};

ProposalWidget.prototype.onCancelButtonClick = function () {
	this.editForm.toggle();
	this.notesLabel.toggle();
	this.menuButton.toggle();
};

ProposalWidget.prototype.onToggleButtonClick = function () {
	this.endorsementList.toggle();
	this.expandIcon.toggle();
	this.collapseIcon.toggle();
};

ProposalWidget.prototype.onEndorseButtonClick = function () {
	var windowManager = new OO.ui.WindowManager();
	$( document.body ).append( windowManager.$element );
	// Create a new dialog window.
	var processDialog = new EndorseDialog( {
		size: 'large',
		proposal: this.proposal
	} );

	// Add windows to window manager using the addWindows() method.
	windowManager.addWindows( [ processDialog ] );

	// Open the window.
	windowManager.openWindow( processDialog );

};

ProposalWidget.prototype.setDisplayMode = function () {
	this.endorseButton.toggle();
	this.expandIcon.toggle();
	this.menuButton.toggle();
};

module.exports = ProposalWidget;
