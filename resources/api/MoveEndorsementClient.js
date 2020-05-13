'use strict';

/**
 * Api client for moving an endorsement to another proposal / jademoveendorsement.
 *
 * @extends jade.api.BaseClient
 *
 * @class
 * @classdesc Api client for moving an endorsement to another proposal.
 * @requires jade.api.BaseClient
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var BaseClient = require( './client.js' ),
	MoveEndorsementClient = function MoveEndorsementClient() {
		MoveEndorsementClient.super.apply( this, arguments );
	};

OO.inheritClass( MoveEndorsementClient, BaseClient );

MoveEndorsementClient.prototype.moduleName = 'jademoveendorsement';

/**
 * Create an object of cleaned params that are expected by api module.
 *
 * @function buildParams
 * @description Create an object of cleaned params that are expected by api module.
 * @param {string} actionName - The name of the Action Api module to be executed.
 * @param {Object} data - The form data to be sent to api module.
 * @returns {Object} Cleaned params that are expected by api module.
 */
MoveEndorsementClient.prototype.buildParams = function ( actionName, data ) {
	return {
		action: actionName,
		title: data.title,
		entitydata: data.entitydata,
		facet: data.facet,
		labeldata: data.labeldata,
		endorsementcomment: data.endorsementcomment,
		comment: data.comment
	};
};

module.exports = MoveEndorsementClient;
