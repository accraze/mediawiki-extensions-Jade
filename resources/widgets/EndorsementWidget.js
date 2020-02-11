'use strict';

/**
 * Widget for a single proposal endorsement.
 *
 * @extends OO.ui.OptionWidget
 *
 * @constructor
 * @param {Object} [config]
 * @cfg {jQuery} $element
 * @cfg {Object} [endorsement] Object containing endorsement data.
 * @cfg {Object} [proposal] Object containing proposal data.
 * @cfg {Object} [numProposal] integer describing the total number of proposals
 * in this facet.
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var UpdateEndorsementClient = require( 'jade.api' ).UpdateEndorsementClient;

var MoveEndorsementDialog = require( 'jade.dialogs' ).MoveEndorsementDialog,
	DeleteEndorsementDialog = require( 'jade.dialogs' ).DeleteEndorsementDialog;

var EndorsementWidget = function ( config ) {
	config = config || {};
	EndorsementWidget.parent.call( this, config );

	this.endorsement = config.endorsement;
	this.proposal = config.proposal;
	this.proposals = config.proposals;
	this.numProposals = config.numProposal;
	this.menuEdit = mw.message( 'jade-ui-menu-edit' ).text();
	this.menuMove = mw.message( 'jade-ui-menu-move' ).text();
	this.menuDelete = mw.message( 'jade-ui-menu-delete' ).text();
	this.editButton = new OO.ui.OptionWidget( {
		classes: [ 'jade-endorsementWidget-endorsementMenu-edit' ],
		data: this,
		label: this.menuEdit
	} );
	this.moveButton = new OO.ui.OptionWidget( {
		classes: [ 'jade-endorsementWidget-endorsementMenu-move' ],
		data: this,
		label: this.menuMove,
		disabled: !( this.numProposals > 1 )
	} );
	this.deleteButton = new OO.ui.OptionWidget( {
		classes: [ 'jade-endorsementWidget-endorsementMenu-delete' ],
		data: this,
		label: this.menuDelete
	} );
	this.menuStack = new OO.ui.SelectWidget( {
		items: [ this.editButton, this.moveButton, this.deleteButton ],
		classes: [ 'jade-endorsementWidget-endorsementMenu' ]
	} );
	this.menuStack.on( 'choose', function ( cmd ) {
		var windowManager = new OO.ui.WindowManager();
		var processDialog;
		if ( cmd.label === cmd.data.menuDelete ) {
			$( document.body ).append( windowManager.$element );
			// Create a new dialog window.
			processDialog = new DeleteEndorsementDialog( {
				size: 'large',
				proposal: cmd.data.proposal,
				endorsement: cmd.data.endorsement
			} );

			// Add windows to window manager using the addWindows() method.
			windowManager.addWindows( [ processDialog ] );

			// Open the window.
			windowManager.openWindow( processDialog );
		} else if ( cmd.label === cmd.data.menuMove ) {
			$( document.body ).append( windowManager.$element );
			// Create a new dialog window.
			processDialog = new MoveEndorsementDialog( {
				size: 'large',
				proposal: cmd.data.proposal,
				endorsement: cmd.data.endorsement,
				proposals: cmd.data.proposals
			} );

			// Add windows to window manager using the addWindows() method.
			windowManager.addWindows( [ processDialog ] );

			// Open the window.
			windowManager.openWindow( processDialog );
		} else if ( cmd.label === cmd.data.menuEdit ) {
			cmd.data.menuButton.toggle();
			cmd.data.commentLabel.toggle();
			cmd.data.editForm.toggle();
			cmd.data.editBox.focus();
		}
	} );
	this.commentLabel = new OO.ui.LabelWidget( {
		classes: [ 'jade-endorsementWidget-comment' ],
		label: this.endorsement.comment
	} );
	this.getUserName = function () {
		var params = {
				action: 'query',
				list: 'users',
				ususerids: String( this.endorsement.author.id ),
				format: 'json'
			},
			api = new mw.Api();
		return api.get( params ).then( function ( data ) {
			var userName = data.query.users[ 0 ].name;
			var baseDiv = $( '<div>' );
			var userUrl = '/wiki/User:' + userName;
			var talkUrl = '/wiki/User_talk:' + userName;
			var contribUrl = '/wiki/Special:Contributions/' + userName;
			var user = $( '<a>' ).attr( 'href', userUrl ).text( userName );
			var talk = $( '<a>' ).attr( 'href', talkUrl ).text( 'talk' );
			var contrib = $( '<a>' ).attr( 'href', contribUrl ).text( 'contrib' );
			baseDiv.append( user ).append( ' (' ).append( talk ).append( '•' ).append( contrib ).append( ')' );
			return baseDiv;
		} );
	};
	this.authorLabel = new OO.ui.OptionWidget( {
		classes: [ 'jade-endorsementWidget-author' ]
	} );

	this.buildAuthor = async function () {
		var name;
		if ( this.endorsement.author.ip ) {
			name = this.endorsement.author.ip;
			this.authorLabel.setLabel( name );
		} else {
			var aname = await this.getUserName();
			this.authorLabel.setLabel( aname );
		}
	};
	this.buildAuthor();
	this.buildDate = function ( date ) {
		var local = new Date( date );
		return local.toUTCString();
	};
	this.createdLabel = new OO.ui.OptionWidget( {
		classes: [ 'jade-endorsementWidget-created' ],
		label: this.buildDate( this.endorsement.created )
	} );
	this.infoStack = new OO.ui.SelectWidget( {
		items: [ this.authorLabel, this.createdLabel ],
		classes: [ 'jade-endorsementWidget-infoStack' ]
	} );

	this.menuButton = new OO.ui.PopupButtonWidget( {
		classes: [ 'jade-endorsementWidget-menuBtn' ],
		framed: false,
		icon: 'ellipsis',
		popup: {
			$content: this.menuStack.$element,
			padded: true,
			anchor: false,
			align: 'forwards',
			width: '90px'
		}
	} );
	this.row = new OO.ui.HorizontalLayout( {
		items: [
			this.commentLabel,
			this.infoStack,
			this.menuButton
		]
	} );

	this.editFormSubmit = new OO.ui.ButtonInputWidget( {
		classes: [ 'jade-endorsementWidget-editForm-submitBtn' ],
		type: 'submit',
		name: 'publishAndEndorse',
		label: mw.message( 'jade-ui-edit-publish-btn' ).text(),
		flags: [
			'primary',
			'progressive'
		],
		align: 'right'
	} );
	this.editFormCancel = new OO.ui.ButtonWidget( {

		framed: false,
		label: mw.message( 'jade-ui-cancel-btn' ).text(),
		classes: [
			'jade-endorsementWidget-editForm-cancelBtn'
		]
	} );
	this.editBox = new OO.ui.TextInputWidget( {
		classes: [
			'jade-endorsementWidget-editForm-text'
		],
		placeholder: '(optional)',
		value: this.endorsement.comment
	} );

	this.editForm = new OO.ui.FieldsetLayout( {
		classes: [
			'jade-endorsementWidget-editForm'
		],
		label: null,
		items: [
			new OO.ui.FieldLayout( this.editBox, {
				align: 'top',
				label: null
			} ),
			new OO.ui.FieldLayout( new OO.ui.Widget( {
				content: [
					new OO.ui.HorizontalLayout( {
						items: [
							this.editFormSubmit,
							this.editFormCancel
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

	this.$element
		.addClass( 'jade-endorsementWidget' )
		.append( this.row.$element )
		.append( this.editForm.$element );

	this.editFormSubmit.connect( this, {
		click: 'onSubmitButtonClick'
	} );

	this.editFormCancel.connect( this, {
		click: 'onCancelButtonClick'
	} );

};

OO.inheritClass( EndorsementWidget, OO.ui.OptionWidget );
EndorsementWidget.prototype.onSubmitButtonClick = function () {
	// var comment = this.commentBox.value;
	var params = {
		title: mw.config.get( 'entityTitle' ).prefixedText,
		facet: 'editquality',
		labeldata: JSON.stringify( this.proposal.labeldata ),
		endorsementcomment: this.editBox.value
	};
	var client = new UpdateEndorsementClient( params );
	var res = client.execute( params );
	Promise.resolve( res );
};

EndorsementWidget.prototype.onCancelButtonClick = function () {
	this.editForm.toggle();
	this.commentLabel.toggle();
	this.menuButton.toggle();
};

EndorsementWidget.prototype.setDisplayMode = function () {
	this.menuButton.toggle();
};

module.exports = EndorsementWidget;
