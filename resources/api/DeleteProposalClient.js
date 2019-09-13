'use strict';

/**
 * Api client for DeleteProposal.
 *
 * @extends jade.api.BaseClient
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var BaseClient = require( './client.js' ),
	DeleteProposalClient = function DeleteProposal() {
		DeleteProposalClient.super.apply( this, arguments );
	};

OO.inheritClass( DeleteProposalClient, BaseClient );

DeleteProposalClient.prototype.moduleName = 'jadedeleteproposal';

DeleteProposalClient.prototype.buildParams = function ( actionName, data ) {
	return {
		action: actionName,
		title: data.title,
		entitydata: data.entitydata,
		facet: data.facet,
		labeldata: data.labeldata,
		endorsementorigin: data.endorsementorigin || 'mw-api',
		comment: data.comment
	};
};

module.exports = DeleteProposalClient;
