'use strict';

/**
 * Api client for DeleteEndorsement.
 *
 * @extends jade.api.BaseClient
 *
 * @class
 * @classdesc Api client for DeleteEndorsement.
 * @requires jade.api.BaseClient
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var BaseClient = require( './client.js' ),
	DeleteEndorsementClient = function DeleteProposal() {
		DeleteEndorsementClient.super.apply( this, arguments );
	};

OO.inheritClass( DeleteEndorsementClient, BaseClient );

DeleteEndorsementClient.prototype.moduleName = 'jadedeleteendorsement';

/**
 * Create an object of cleaned params that are expected by api module.
 *
 * @function buildParams
 * @description Create an object of cleaned params that are expected by api module.
 * @param {string} actionName - The name of the Action Api module to be executed.
 * @param {Object} data - The form data to be sent to api module.
 * @returns {Object} Cleaned params that are expected by api module.
 */
/* eslint camelcase: ["error", {allow: ["user_id", "global_id"]}]*/
DeleteEndorsementClient.prototype.buildParams = function ( actionName, data ) {
	return {
		action: actionName,
		title: data.title,
		entitydata: data.entitydata,
		facet: data.facet,
		labeldata: data.labeldata,
		user_id: data.user_id,
		global_id: data.global_id,
		ip: data.ip,
		comment: data.comment
	};
};

module.exports = DeleteEndorsementClient;
