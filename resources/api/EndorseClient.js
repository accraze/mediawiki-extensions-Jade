'use strict';

/**
 * Api client for endorsing a proposal / jadeendorse.
 *
 * @extends jade.api.BaseClient
 *
 * @class
 * @classdesc Api client for endorsing a proposal.
 * @requires jade.api.BaseClient

 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var BaseClient = require( './client.js' ),
	EndorseClient = function EndorseClient() {
		EndorseClient.super.apply( this, arguments );
	};

OO.inheritClass( EndorseClient, BaseClient );

EndorseClient.prototype.moduleName = 'jadeendorse';

/**
 * Create an object of cleaned params that are expected by api module.
 *
 * @function buildParams
 * @description Create an object of cleaned params that are expected by api module.
 * @param {string} actionName - The name of the Action Api module to be executed.
 * @param {Object} data - The form data to be sent to api module.
 * @returns {Object} Cleaned params that are expected by api module.
 */
EndorseClient.prototype.buildParams = function ( actionName, data ) {
	return {
		action: actionName,
		title: data.title,
		entitydata: data.entitydata,
		facet: data.facet,
		labeldata: data.labeldata,
		nomove: true,
		endorsementorigin: data.endorsementorigin || 'mw-api',
		endorsementcomment: data.endorsementcomment
	};
};

module.exports = EndorseClient;
