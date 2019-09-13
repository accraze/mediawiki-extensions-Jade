'use strict';

/**
 * Api client for updating a proposal's notes / jadeupdateproposal.
 *
 * @extends jade.api.BaseClient
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var BaseClient = require( './client.js' ),
	UpdateProposalClient = function UpdateProposalClient() {
		UpdateProposalClient.super.apply( this, arguments );
	};

OO.inheritClass( UpdateProposalClient, BaseClient );

UpdateProposalClient.prototype.moduleName = 'jadeupdateproposal';

UpdateProposalClient.prototype.buildParams = function ( actionName, data ) {
	return {
		action: actionName,
		title: data.title,
		entitydata: data.entitydata,
		facet: data.facet,
		labeldata: data.labeldata,
		notes: data.notes,
		comment: data.comment
	};
};

module.exports = UpdateProposalClient;
