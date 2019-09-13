'use strict';

/**
 * Dialog box for deleting an endorsement.
 *
 * @extends OO.ui.ProcessDialog
 *
 * @constructor
 * @param {Object} [config]
 * @cfg {jQuery} $element
 * @cfg {Object} [proposal] Object containing proposal data.
 * in this facet.
 */

var DeleteEndorsementClient = require( 'jade.api' ).DeleteEndorsementClient;

var DeleteEndorsementDialog = function DeleteEndorsementDialog( config ) {
	config = config || {};
	DeleteEndorsementDialog.super.call( this, config );
	this.proposal = config.proposal;
	var ProposalWidget = require( 'jade.widgets' ).ProposalWidget;
	this.proposalWidget = new ProposalWidget( {
		classes: [ 'jade-deleteEndorsementDialog-proposalWidget' ],
		proposal: this.proposal
	} );
	this.proposalWidget.setDisplayMode();
	this.endorsement = config.endorsement;
	var EndorsementWidget = require( 'jade.widgets' ).EndorsementWidget;
	this.endorsementWidget = new EndorsementWidget( {
		classes: [ 'jade-deleteEndorsementDialog-endorsementWidget' ],
		endorsement: this.endorsement
	} );
	this.endorsementWidget.setDisplayMode();
	this.commentFormSubmit = new OO.ui.ButtonInputWidget( {
		classes: [ 'jade-deleteEndorsementDialog-submitBtn' ],
		type: 'submit',
		name: 'deleteEndorsement',
		label: mw.message( 'jade-ui-deleteendorsement-submit-btn' ).text(),
		flags: [
			'primary',
			'destructive'
		],
		align: 'right'
	} );
	this.commentFormCancel = new OO.ui.ButtonWidget( {
		classes: [ 'jade-deleteEndorsementDialog-cancelBtn' ],
		framed: false,
		label: mw.message( 'jade-ui-cancel-btn' ).text()
	} );
	this.commentBox = new OO.ui.TextInputWidget( {
		classes: [ 'jade-deleteEndorsementDialog-commentBox' ],
		placeholder: mw.message( 'jade-ui-deleteendorsement-comment-placeholder' ).text()
	} );
	this.message = new OO.ui.MessageWidget( {
		type: 'error',
		classes: [ 'jade-deleteEndorsementDialog-errorMessage' ]
	} );
	this.message.toggle();

	this.commentForm = new OO.ui.FieldsetLayout( {
		classes: [ 'jade-deleteEndorsementDialog-commentForm' ],
		items: [
			new OO.ui.FieldLayout( this.commentBox, {
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
	this.commentFormSubmit.connect( this, {
		click: 'onSubmitButtonClick'
	} );

	this.commentFormCancel.connect( this, {
		click: 'onCancelButtonClick'
	} );

};
OO.inheritClass( DeleteEndorsementDialog, OO.ui.ProcessDialog );

DeleteEndorsementDialog.prototype.onSubmitButtonClick = async function () {
	var comment = this.commentBox.value;
	var params = {
		title: mw.config.get( 'entityTitle' ).prefixedText,
		facet: 'editquality',
		labeldata: JSON.stringify( this.proposal.labeldata ),
		comment: comment
	};
	var client = new DeleteEndorsementClient();
	var res = await client.execute( params );
	res = res.slice( 1, -1 );
	var err = mw.message( res ).text();
	if ( err ) {
		this.message.setLabel( err );
		this.message.toggle();
		this.updateSize();
	}
};

DeleteEndorsementDialog.prototype.onCancelButtonClick = function () {
	this.close();
};
// Specify a name for .addWindows()
DeleteEndorsementDialog.static.name = 'deleteEndorsementDialog';
// Specify a title and an action set that uses modes ('edit' and 'help' mode, in this example).
DeleteEndorsementDialog.static.title = mw.message( 'jade-ui-deleteendorsement-title' ).text();
DeleteEndorsementDialog.static.actions = [
	{
		modes: 'edit',
		label: mw.message( 'jade-ui-cancel-btn' ).text(),
		flags: [ 'safe', 'close' ]
	}
];

// Customize the initialize() method to add content and set up event handlers.
// This example uses a stack layout with two panels: one displayed for
// edit mode and one for help mode.
DeleteEndorsementDialog.prototype.initialize = function () {
	DeleteEndorsementDialog.super.prototype.initialize.apply( this, arguments );
	this.panel1 = new OO.ui.PanelLayout( { padded: true, expanded: false } );
	this.panel1.$element.append( '<p>' + mw.message( 'jade-ui-deleteendorsement-text-1' ).text() + '</p>' );
	this.panel1.$element.append( this.endorsementWidget.$element );
	this.panel1.$element.append( '<p>' + mw.message( 'jade-ui-deleteendorsement-text-2' ).text() + '</p>' );

	this.panel1.$element.append( this.proposalWidget.$element );
	this.panel1.$element.append( this.commentForm.$element );

	this.panel2 = new OO.ui.PanelLayout( { padded: true, expanded: false } );
	this.stackLayout = new OO.ui.StackLayout( {
		items: [ this.panel1, this.panel2 ]
	} );

	this.$body.append( this.stackLayout.$element );
};

// Set up the initial mode of the window ('edit', in this example.)
DeleteEndorsementDialog.prototype.getSetupProcess = function ( data ) {
	return DeleteEndorsementDialog.super.prototype.getSetupProcess.call( this, data )
	.next( function () {
		this.actions.setMode( 'edit' );
	}, this );
};

// Use the getActionProcess() method to set the modes and displayed item.
DeleteEndorsementDialog.prototype.getActionProcess = function ( action ) {

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
	return DeleteEndorsementDialog.super.prototype.getActionProcess.call( this, action );
};

module.exports = DeleteEndorsementDialog;
