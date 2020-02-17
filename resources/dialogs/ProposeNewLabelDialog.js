'use strict';

/**
 * Dialog box for proposing a new label.
 *
 * @extends OO.ui.ProcessDialog
 *
 * @constructor
 * @param {Object} [config]
 * @cfg {jQuery} $element
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var ProposeClient = require( 'jade.api' ).ProposeClient;

var ProposeNewLabelDialog = function ProposeNewLabelDialog( config ) {
	config = config || {};
	ProposeNewLabelDialog.super.call( this, config );
	this.damagingButton = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposeNewLabelDialog-selectOption-1-damagingIcon' ],
		framed: false,
		type: 'success',
		align: 'right',
		icon: 'articleCheck',
		label: mw.message( 'jade-ui-productive-label' ).text(),
		flags: [ 'progressive', 'primary' ]
	} );
	this.goodfaithButton = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposeNewLabelDialog-selectOption-1-goodfaithIcon' ],
		framed: false,
		icon: 'heart',
		flags: [ 'progressive', 'primary' ],
		label: mw.message( 'jade-ui-goodfaith-label' ).text()
	} );

	this.selectOneLabel = new OO.ui.LabelWidget( {
		classes: [ 'jade-proposeNewLabelDialog-selectOption-1-msg' ],
		label: mw.message( 'jade-ui-proposenewlabel-select-1-label' ).text()
	} );

	this.selectOne = new OO.ui.HorizontalLayout( {
		classes: [ 'jade-proposeNewLabelDialog-selectOption-1' ],
		items: [
			this.damagingButton,
			this.goodfaithButton,
			this.selectOneLabel
		]
	} );
	this.damagingButton2 = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposeNewLabelDialog-selectOption-2-damagingIcon' ],
		framed: false,
		align: 'right',
		icon: 'notice',
		label: mw.message( 'jade-ui-damaging-label' ).text(),
		flags: [ 'destructive', 'primary', 'error' ]
	} );
	this.goodfaithButton2 = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposeNewLabelDialog-selectOption-2-goodfaithIcon' ],
		framed: false,
		icon: 'heart',
		flags: [ 'progressive', 'primary' ],
		label: mw.message( 'jade-ui-goodfaith-label' ).text()
	} );

	this.selectTwoLabel = new OO.ui.LabelWidget( {
		classes: [ 'jade-proposeNewLabelDialog-selectOption-2-msg' ],
		label: mw.message( 'jade-ui-proposenewlabel-select-2-label' ).text()
	} );

	this.selectTwo = new OO.ui.HorizontalLayout( {
		classes: [ 'jade-proposeNewLabelDialog-selectOption-2' ],
		items: [
			this.damagingButton2,
			this.goodfaithButton2,
			this.selectTwoLabel
		]
	} );
	this.damagingButton3 = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposeNewLabelDialog-selectOption-3-damagingIcon' ],
		framed: false,
		align: 'right',
		icon: 'notice',
		label: 'damaging',
		flags: [ 'destructive', 'primary', 'error' ]
	} );
	this.goodfaithButton3 = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposeNewLabelDialog-selectOption-3-goodfaithIcon' ],
		framed: false,
		icon: 'userAnonymous',
		label: mw.message( 'jade-ui-badfaith-label' ).text()
	} );

	this.selectThreeLabel = new OO.ui.LabelWidget( {
		classes: [ 'jade-proposeNewLabelDialog-selectOption-3-msg' ],
		label: mw.message( 'jade-ui-proposenewlabel-select-3-label' ).text()
	} );

	this.selectThree = new OO.ui.HorizontalLayout( {
		classes: [ 'jade-proposeNewLabelDialog-selectOption-3' ],
		items: [
			this.damagingButton3,
			this.goodfaithButton3,
			this.selectThreeLabel
		]
	} );

	this.labelForm = new OO.ui.RadioSelectWidget( {
		classes: [ 'jade-proposeNewLabelDialog-labelForm' ],
		items: [
			new OO.ui.RadioOptionWidget( {
				classes: [ 'jade-proposeNewLabelDialog-labelForm-radioOption' ],
				data: { damaging: false, goodfaith: true },
				label: this.selectOne.$element
			} ),
			new OO.ui.RadioOptionWidget( {
				classes: [ 'jade-proposeNewLabelDialog-labelForm-radioOption' ],
				data: { damaging: true, goodfaith: true },
				label: this.selectTwo.$element
			} ),
			new OO.ui.RadioOptionWidget( {
				classes: [ 'jade-proposeNewLabelDialog-labelForm-radioOption' ],
				data: { damaging: true, goodfaith: false },
				label: this.selectThree.$element
			} )
		]
	} );

	this.notesFormSubmit = new OO.ui.ButtonInputWidget( {
		classes: [ 'jade-proposeNewLabelDialog-submitBtn' ],
		type: 'submit',
		name: 'publishAndEndorse',
		label: mw.message( 'jade-ui-proposenewlabel-submit-btn' ).text(),
		flags: [
			'primary',
			'progressive'
		],
		align: 'right'
	} );
	this.notesFormCancel = new OO.ui.ButtonWidget( {
		classes: [ 'jade-proposeNewLabelDialog-cancelBtn' ],
		framed: false,
		label: mw.message( 'jade-ui-cancel-btn' ).text(),
		flags: 'destructive'
	} );
	this.notesBox = new OO.ui.MultilineTextInputWidget( {
		classes: [ 'jade-proposeNewLabelDialog-notesBox' ],
		placeholder: mw.message( 'jade-ui-proposenewlabel-comment-placeholder' ).text()
	} );
	this.message = new OO.ui.MessageWidget( {
		type: 'error',
		classes: [ 'jade-proposeNewLabelDialog-errorMessage' ]
	} );
	this.message.toggle();
	this.notesForm = new OO.ui.FieldsetLayout( {
		classes: [ 'jade-proposeNewLabelDialog-notesForm' ],
		items: [
			new OO.ui.FieldLayout( this.notesBox, {
				align: 'top',
				label: mw.message( 'jade-ui-proposenewlabel-notes-label' ).text()
			} ),
			new OO.ui.FieldLayout( new OO.ui.Widget( {
				content: [
					new OO.ui.HorizontalLayout( {
						items: [
							this.notesFormCancel,
							this.notesFormSubmit,
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

	this.notesFormSubmit.connect( this, {
		click: 'onSubmitButtonClick'
	} );

	this.notesFormCancel.connect( this, {
		click: 'onCancelButtonClick'
	} );

};
OO.inheritClass( ProposeNewLabelDialog, OO.ui.ProcessDialog );

ProposeNewLabelDialog.prototype.onSubmitButtonClick = async function () {
	var comment = this.notesBox.value;
	var newLabel = this.labelForm.findSelectedItem().data;
	var params = {
		title: mw.config.get( 'entityTitle' ).prefixedText,
		facet: 'editquality',
		labeldata: JSON.stringify( newLabel ),
		notes: comment,
		endorsementorigin: 'mw-api'
	};
	var client = new ProposeClient();
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

ProposeNewLabelDialog.prototype.onCancelButtonClick = function () {
	this.close();
};

// Specify a name for .addWindows()
ProposeNewLabelDialog.static.name = 'proposeNewLabelDialog';
// Specify a title and an action set that uses modes ('edit' and 'help' mode, in this example).
ProposeNewLabelDialog.static.title = mw.message( 'jade-ui-proposenewlabel-title' ).text();
ProposeNewLabelDialog.static.actions = [
	{
		modes: 'edit',
		label: mw.message( 'jade-ui-cancel-btn' ).text(),
		flags: [ 'safe', 'close' ]
	}
];

// Customize the initialize() method to add content and set up event handlers.
// This example uses a stack layout with two panels: one displayed for
// edit mode and one for help mode.
ProposeNewLabelDialog.prototype.initialize = function () {
	ProposeNewLabelDialog.super.prototype.initialize.apply( this, arguments );
	this.panel1 = new OO.ui.PanelLayout( { padded: true, expanded: false } );
	this.panel1.$element.append( '<p>' + mw.message( 'jade-ui-proposenewlabel-text' ).text() + '</p>' );
	this.panel1.$element.append( this.labelForm.$element );
	this.panel1.$element.append( this.notesForm.$element );
	this.panel2 = new OO.ui.PanelLayout( { padded: true, expanded: false } );
	this.stackLayout = new OO.ui.StackLayout( {
		items: [ this.panel1, this.panel2 ]
	} );
	this.$body.append( this.stackLayout.$element );
};

// Set up the initial mode of the window ('edit', in this example.)
ProposeNewLabelDialog.prototype.getSetupProcess = function ( data ) {
	return ProposeNewLabelDialog.super.prototype.getSetupProcess.call( this, data )
	.next( function () {
		this.actions.setMode( 'edit' );
	}, this );
};

// Use the getActionProcess() method to set the modes and displayed item.
ProposeNewLabelDialog.prototype.getActionProcess = function ( action ) {

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
	return ProposeNewLabelDialog.super.prototype.getActionProcess.call( this, action );
};

module.exports = ProposeNewLabelDialog;
