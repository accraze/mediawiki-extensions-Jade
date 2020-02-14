'use strict';

/**
 * Api client for using the ProposeOrEndorse module / jadeproposeorendorse.
 *
 * @extends jade.api.BaseClient
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var BaseClient = require( './client.js' ),
	ProposeClient = function ProposeClient() {
		ProposeClient.super.apply( this, arguments );
	};

OO.inheritClass( ProposeClient, BaseClient );

ProposeClient.prototype.moduleName = 'jadecreateandendorse';

ProposeClient.prototype.buildParams = function ( actionName, data ) {
	return {
		action: actionName,
		title: data.title,
		entitydata: data.entitydata,
		facet: data.facet,
		labeldata: data.labeldata,
		notes: data.notes,
		endorsementorigin: data.endorsementorigin || 'mw-api',
		comment: data.comment
	};
};

module.exports = ProposeClient;
