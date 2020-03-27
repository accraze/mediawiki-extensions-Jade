'use strict';

/**
 * Api client for promoting a label / SetPreference.
 *
 * @extends jade.api.BaseClient
 *
 * @class
 * @classdesc Api client for promoting a label.
 * @requires jade.api.BaseClient
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var BaseClient = require( './client.js' ),
	PromoteClient = function PromoteClient() {
		PromoteClient.super.apply( this, arguments );
	};

OO.inheritClass( PromoteClient, BaseClient );

PromoteClient.prototype.moduleName = 'jadesetpreference';

/**
 * Create an object of cleaned params that are expected by api module.
 *
 * @function buildParams
 * @description Create an object of cleaned params that are expected by api module.
 * @param {string} actionName - The name of the Action Api module to be executed.
 * @param {Object} data - The form data to be sent to api module.
 * @returns {Object} Cleaned params that are expected by api module.
 */
PromoteClient.prototype.buildParams = function ( actionName, data ) {
	return {
		action: actionName,
		title: data.title,
		entitydata: data.entitydata,
		facet: data.facet,
		labeldata: data.labeldata,
		comment: data.comment
	};
};

module.exports = PromoteClient;
