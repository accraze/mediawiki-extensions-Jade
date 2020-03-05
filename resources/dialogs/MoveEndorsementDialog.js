'use strict';

/**
 * Dialog box for moving an endorsement to another proposal.
 *
 * @extends OO.ui.ProcessDialog
 *
 * @constructor
 * @param {Object} [config]
 * @cfg {jQuery} $element
 * @cfg {Object} [proposal] Object containing proposal data.
 * in this facet.
 */

var MoveEndorsementClient = require( 'jade.api' ).MoveEndorsementClient;

var MoveEndorsementDialog = function MoveEndorsementDialog( config ) {
	config = config || {};
	MoveEndorsementDialog.super.call( this, config );
	this.proposal = config.proposal;
	this.proposals = config.proposals;
	this.commentFormSubmit = new OO.ui.ButtonInputWidget( {
		classes: [ 'jade-moveEndorsementDialog-submitBtn' ],
		type: 'submit',
		name: 'publishAndEndorse',
		label: mw.message( 'jade-ui-moveendorsement-submit-btn' ).text(),
		flags: [
			'primary',
			'progressive'
		],
		align: 'right'
	} );
	this.commentFormCancel = new OO.ui.ButtonWidget( {
		classes: [ 'jade-moveEndorsementDialog-cancelBtn' ],
		framed: false,
		label: mw.message( 'jade-ui-cancel-btn' ).text()
	} );
	this.message = new OO.ui.MessageWidget( {
		type: 'error',
		classes: [ 'jade-moveEndorsementDialog-errorMessage' ]
	} );
	this.message.toggle();
	this.commentBox = new OO.ui.TextInputWidget( {
		classes: [ 'jade-moveEndorsementDialog-commentBox' ],
		placeholder: mw.message( 'jade-ui-moveendorsement-comment-placeholder' ).text()
	} );
	this.commentForm = new OO.ui.FieldsetLayout( {
		classes: [ 'jade-moveEndorsementDialog-commentForm' ],
		items: [
			new OO.ui.FieldLayout(
				this.commentBox, {
					align: 'top',
					label: mw.message( 'jade-ui-comment-label' ).text()
				} ),
			new OO.ui.FieldLayout( new OO.ui.Widget( {
				classes: [ 'jade-moveEndorsementDialog-commentForm-buttons' ],
				content: [
					new OO.ui.HorizontalLayout( {
						items: [
							this.message,
							this.commentFormCancel,
							this.commentFormSubmit
						]
					} )
				]
			} ), {
				align: 'top',
				label: null
			} )
		]
	} );

	this.commentFormSubmit.connect( this, {
		click: 'onSubmitButtonClick'
	} );

	this.commentFormCancel.connect( this, {
		click: 'onCancelButtonClick'
	} );

};

OO.inheritClass( MoveEndorsementDialog, OO.ui.ProcessDialog );

MoveEndorsementDialog.prototype.onEndorseButtonClick = function () {
	//
	var ProposalWidget = require( 'jade.widgets' ).ProposalWidget;
	var proposalWidget = new ProposalWidget( {
		classes: [ 'proposal' ],
		proposal: this.data
	} );
	proposalWidget.setDisplayMode();
	this.obj.commentFormSubmit.setData( this.data );
	this.obj.panel2.$element.append( '<p>' + mw.message( 'jade-ui-moveendorsement-text-panel2' ).text() + '</p>' );
	this.obj.panel2.$element.append( proposalWidget.$element );
	this.obj.panel2.$element.append( this.obj.commentForm.$element );
	this.obj.stackLayout.setItem( this.obj.panel2 );
	this.obj.updateSize();

};

MoveEndorsementDialog.prototype.onSubmitButtonClick = async function () {
	this.commentFormSubmit.setDisabled( true );
	var comment = this.commentBox.value;
	var params = {
		title: mw.config.get( 'entityTitle' ).prefixedText,
		facet: 'editquality',
		labeldata: JSON.stringify( this.commentFormSubmit.data.labeldata ),
		comment: comment,
		endorsementorigin: 'jade-ui'
	};
	var client = new MoveEndorsementClient();
	var res = await client.execute( params );
	if ( res ) {
		res = res.slice( 1, -1 );
		var err = mw.message( res ).text();
		if ( err ) {
			this.message.setLabel( err );
			this.message.toggle();
			this.updateSize();
			this.commentFormSubmit.setDisabled( false );
		}
	}
};

MoveEndorsementDialog.prototype.onCancelButtonClick = function () {
	this.close();
};

// Specify a name for .addWindows()
MoveEndorsementDialog.static.name = 'moveEndorsementDialog';
// Specify a title and an action set that uses modes ('edit' and 'help' mode, in this example).
MoveEndorsementDialog.static.title = mw.message( 'jade-ui-moveendorsement-title' ).text();
MoveEndorsementDialog.static.actions = [
	{
		modes: 'edit',
		label: mw.message( 'jade-ui-cancel-btn' ).text(),
		flags: [ 'safe', 'close' ]
	}
];

// Customize the initialize() method to add content and set up event handlers.
// This example uses a stack layout with two panels: one displayed for
// edit mode and one for help mode.
MoveEndorsementDialog.prototype.initialize = function () {
	MoveEndorsementDialog.super.prototype.initialize.apply( this, arguments );
	this.panel1 = new OO.ui.PanelLayout( { padded: true, expanded: false } );
	this.panel1.$element.append( '<p>' + mw.message( 'jade-ui-moveendorsement-text' ).text() + '</p>' );
	var ProposalWidget = require( 'jade.widgets' ).ProposalWidget;
	for ( var idx in this.proposals ) {
		var data = this.proposals[ idx ];
		var proposal = new ProposalWidget( {
			classes: [ 'proposal' ],
			proposal: data
		} );
		proposal.setDisplayMode();
		var btn = new OO.ui.ButtonInputWidget( {
			type: 'submit',
			name: 'publishAndEndorse',
			label: mw.message( 'jade-ui-moveendorsement-submit-btn' ).text(),
			flags: [
				'primary',
				'progressive'
			],
			disabled: ( this.proposal.labeldata === data.labeldata ),
			data: data,
			align: 'right'
		} );
		btn.connect( { obj: this, data: btn.data }, {
			click: this.onEndorseButtonClick
		} );

		this.panel1.$element.append(
			proposal.$element
		);
		this.btnSpanLayout = new OO.ui.HorizontalLayout( {
			classes: [ 'jade-ui-moveendorsement-button' ],
			items: [ btn ]
		} );
		this.panel1.$element.append( this.btnSpanLayout.$element );
	}
	this.proposalWidget = new ProposalWidget( {
		classes: [ 'proposal' ],
		proposal: this.proposal
	} );
	this.proposalWidget.setDisplayMode();

	this.panel2 = new OO.ui.PanelLayout( { padded: true, expanded: false } );

	this.stackLayout = new OO.ui.StackLayout( {
		items: [ this.panel1, this.panel2 ]
	} );
	this.$body.append( this.stackLayout.$element );
};

// Set up the initial mode of the window ('edit', in this example.)
MoveEndorsementDialog.prototype.getSetupProcess = function ( data ) {
	return MoveEndorsementDialog.super.prototype.getSetupProcess.call( this, data )
	.next( function () {
		this.actions.setMode( 'edit' );
	}, this );
};

// Use the getActionProcess() method to set the modes and displayed item.
MoveEndorsementDialog.prototype.getActionProcess = function ( action ) {

	if ( action === 'help' ) {
		// Set the mode to help.
		this.actions.setMode( 'help' );
		// Show the help panel.
		this.stackLayout.setItem( this.panel2 );
	} else if ( action === 'back' ) {
		// Set the mode to edit.
		this.actions.setMode( 'edit' );
		// Show the edit panel.
		this.stackLayout.setItem( this.panel1 );
	} else if ( action === 'continue' ) {
		var dialog = this;
		return new OO.ui.Process( function () {
			// Do something about the edit.
			dialog.close();
		} );
	}
	return MoveEndorsementDialog.super.prototype.getActionProcess.call( this, action );
};

module.exports = MoveEndorsementDialog;
