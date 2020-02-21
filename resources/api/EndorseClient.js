'use strict';

/**
 * Api client for endorsing a proposal / jadeendorse.
 *
 * @extends jade.api.BaseClient
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var BaseClient = require( './client.js' ),
	EndorseClient = function EndorseClient() {
		EndorseClient.super.apply( this, arguments );
	};

OO.inheritClass( EndorseClient, BaseClient );

EndorseClient.prototype.moduleName = 'jadeendorse';

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
