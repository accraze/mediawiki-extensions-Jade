'use strict';

/**
 * Api client for updating an endorsement comment/ jadeupdateendorsement.
 *
 * @extends jade.api.BaseClient
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var BaseClient = require( './client.js' ),
	UpdateEndorsementClient = function UpdateEndorsementClient() {
		UpdateEndorsementClient.super.apply( this, arguments );
	};

OO.inheritClass( UpdateEndorsementClient, BaseClient );

UpdateEndorsementClient.prototype.moduleName = 'jadeupdateendorsement';

UpdateEndorsementClient.prototype.buildParams = function ( actionName, data ) {
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

module.exports = UpdateEndorsementClient;
