'use strict';

/**
 * Api client for promoting a label / SetPreference.
 *
 * @extends jade.api.BaseClient
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
