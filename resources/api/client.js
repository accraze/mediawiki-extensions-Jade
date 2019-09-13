'use strict';

/**
 * Basic api client.
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

var BaseClient = function BaseClient() {

	this.callback = function ( data, err ) {
		if ( !data.error ) {
			location.reload();
		} else {
			return data;
		}
	};

	this.execute = function ( params ) {
		var cleanedParams = this.buildParams( this.moduleName, params );
		var api = new mw.Api();
		var res = api.postWithEditToken( cleanedParams ).then( this.callback )
			.catch( function ( err ) { return JSON.stringify( err ); } );
		return res;
	};

};

BaseClient.prototype.moduleName = '';

BaseClient.prototype.buildParams = function ( actionName, data ) {
	return {
		action: actionName
	};
};

module.exports = BaseClient;
