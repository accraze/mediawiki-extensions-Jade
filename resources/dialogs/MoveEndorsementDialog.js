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
	var ProposalWidget = require( 'jade.widgets' ).ProposalWidget;
	this.proposalWidget = new ProposalWidget( {
		classes: [ 'jade-moveEndorsementDialog-proposalWidget' ],
		proposal: this.proposal
	} );
	this.proposalWidget.setDisplayMode();
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

	this.commentForm = new OO.ui.FieldsetLayout( {
		classes: [ 'jade-moveEndorsementDialog-commentForm' ],
		items: [
			new OO.ui.FieldLayout(
				new OO.ui.TextInputWidget( {
					classes: [ 'jade-moveEndorsementDialog-commentBox' ],
					placeholder: mw.message( 'jade-ui-moveendorsement-comment-placeholder' ).text()
				} ), {
					align: 'top',
					label: mw.message( 'jade-ui-comment-label' ).text()
				} ),
			new OO.ui.FieldLayout( new OO.ui.Widget( {
				content: [
					new OO.ui.HorizontalLayout( {
						items: [
							this.commentFormCancel,
							this.commentFormSubmit,
							this.message
						]
					} )
				]
			} ), {
				align: 'top',
				label: null
			} )
		]
	} );

};

OO.inheritClass( MoveEndorsementDialog, OO.ui.ProcessDialog );

MoveEndorsementDialog.prototype.onSubmitButtonClick = async function () {
	var params = {
		title: mw.config.get( 'entityTitle' ).prefixedText,
		facet: 'editquality',
		labeldata: JSON.stringify( this.data.labeldata ),
		// endorsementcomment: comment,
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
		btn.connect( btn, {
			click: this.onSubmitButtonClick
		} );

		this.panel1.$element.append(
			proposal.$element
		);
		this.panel1.$element.append( btn.$element );
	}
	this.stackLayout = new OO.ui.StackLayout( {
		items: [ this.panel1 ]
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
