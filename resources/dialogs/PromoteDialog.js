'use strict';

/**
 * Dialog box for promoting a label.
 *
 * @extends OO.ui.ProcessDialog
 *
 * @constructor
 * @param {Object} [config]
 * @cfg {jQuery} $element
 * @cfg {Object} [proposal] Object containing proposal data.
 * in this facet.
 */
var PromoteClient = require( 'jade.api' ).PromoteClient;

var PromoteDialog = function PromoteDialog( config ) {
	config = config || {};
	PromoteDialog.super.call( this, config );
	this.proposal = config.proposal;
	var ProposalWidget = require( 'jade.widgets' ).ProposalWidget;
	this.proposalWidget = new ProposalWidget( {
		classes: [ 'jade-promoteDialog-proposalWidget' ],
		proposal: this.proposal
	} );
	this.proposalWidget.setDisplayMode();
	this.commentFormSubmit = new OO.ui.ButtonInputWidget( {
		classes: [ 'jade-promoteDialog-submitBtn' ],
		type: 'submit',
		name: 'publishAndEndorse',
		label: mw.message( 'jade-ui-promote-submit-btn' ).text(),
		flags: [
			'primary',
			'progressive'
		],
		align: 'right'
	} );
	this.commentFormCancel = new OO.ui.ButtonWidget( {
		classes: [ 'jade-promoteDialog-cancelBtn' ],
		framed: false,
		label: mw.message( 'jade-ui-cancel-btn' ).text()
	} );
	this.commentBox = new OO.ui.TextInputWidget( {
		classes: [ 'jade-promoteDialog-commentBox' ],
		placeholder: mw.message( 'jade-ui-promote-comment-placeholder' ).text()
	}
	);
	this.message = new OO.ui.MessageWidget( {
		type: 'error',
		classes: [ 'jade-promoteDialog-errorMessage' ]
	} );
	this.message.toggle();

	this.commentForm = new OO.ui.FieldsetLayout( {
		classes: [ 'jade-promoteDialog-commentForm' ],
		label: null,
		items: [
			new OO.ui.FieldLayout( this.commentBox, {
				align: 'top',
				label: mw.message( 'jade-ui-comment-label' ).text()
			} ),
			new OO.ui.FieldLayout( new OO.ui.Widget( {
				classes: [ 'jade-promoteDialog-commentForm-buttons' ],
				content: [
					new OO.ui.HorizontalLayout( {
						items: [
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
	this.errorBox = new OO.ui.MessageWidget( {
		type: 'error'
	} );
	this.errorBox.toggle();
	this.commentFormSubmit.connect( this, {
		click: 'onSubmitButtonClick'
	} );

	this.commentFormCancel.connect( this, {
		click: 'onCancelButtonClick'
	} );

};

OO.inheritClass( PromoteDialog, OO.ui.ProcessDialog );

PromoteDialog.prototype.onSubmitButtonClick = async function () {
	this.commentFormSubmit.setDisabled( true );
	var comment = this.commentBox.value;
	var params = {
		title: mw.config.get( 'entityTitle' ).prefixedText,
		facet: 'editquality',
		labeldata: JSON.stringify( this.proposal.labeldata ),
		comment: comment
	};
	var client = new PromoteClient();
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

PromoteDialog.prototype.onCancelButtonClick = function () {
	this.close();
};

// Specify a name for .addWindows()
PromoteDialog.static.name = 'promoteDialog';
// Specify a title and an action set that uses modes ('edit' and 'help' mode, in this example).
PromoteDialog.static.title = mw.message( 'jade-ui-promote-title' ).text();
PromoteDialog.static.actions = [
	{
		modes: 'edit',
		label: mw.message( 'jade-ui-cancel-btn' ).text(),
		flags: [ 'safe', 'close' ]
	}
];

// Customize the initialize() method to add content and set up event handlers.
// This example uses a stack layout with two panels: one displayed for
// edit mode and one for help mode.
PromoteDialog.prototype.initialize = function () {
	PromoteDialog.super.prototype.initialize.apply( this, arguments );
	this.panel1 = new OO.ui.PanelLayout( { padded: true, expanded: false } );
	this.panel1.$element.append( '<p>' + mw.message( 'jade-ui-promote-text' ).text() + '</p>' );
	this.panel1.$element.append( this.proposalWidget.$element );
	this.panel1.$element.append( this.commentForm.$element );
	this.panel1.$element.append( this.errorBox.$element );
	this.stackLayout = new OO.ui.StackLayout( {
		items: [ this.panel1 ]
	} );
	this.$body.append( this.stackLayout.$element );
};

// Set up the initial mode of the window ('edit', in this example.)
PromoteDialog.prototype.getSetupProcess = function ( data ) {
	return PromoteDialog.super.prototype.getSetupProcess.call( this, data )
	.next( function () {
		this.actions.setMode( 'edit' );
	}, this );
};

// Use the getActionProcess() method to set the modes and displayed item.
PromoteDialog.prototype.getActionProcess = function ( action ) {

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
	return PromoteDialog.super.prototype.getActionProcess.call( this, action );
};

// Get dialog height.
PromoteDialog.prototype.getBodyHeight = function () {
	return this.panel1.$element.outerHeight( true );
};

module.exports = PromoteDialog;
