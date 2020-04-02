'use strict';

/**
 * Basic client for calling Jade api modules.
 *
 * @class
 * @classdesc Basic client for calling Jade api modules.
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 * @author Kevin Bazira < kbazira@wikimedia.org >
 */

var BaseClient = function BaseClient() {

	var moduleName = this.moduleName;

	/**
	 * Reload the page if no error found in data, otherwise return data.
	 *
	 * @callback requestCallback
	 * @function requestCallback
	 * @description Reload page or return error data.
	 * @param {Object} data - The data returned from api response.
	 * @param {Object} err
	 */
	this.requestCallback = function ( data, err ) {
		if ( !data.error ) {
			sessionStorage.loadBubbleNotificationAfterPageLoad = true;
			sessionStorage.bubbleNotificationMessage = moduleName.replace( 'jade', 'jade-' );
			location.reload();
		} else {
			return data;
		}
	};

	/**
	 * Execute call to MW api.
	 *
	 * @function execute
	 * @description Execute call to MW api.
	 * @param {Object} params - The form data to be sent to api module.
	 * @returns {Promise} Promise object represents the api response.
	 */
	this.execute = function ( params ) {
		var cleanedParams = this.buildParams( moduleName, params );
		var api = new mw.Api();
		var res = api.postWithEditToken( cleanedParams ).then( this.requestCallback )
			.catch( function ( err ) { return JSON.stringify( err ); } );
		return res;
	};

};

BaseClient.prototype.moduleName = '';

/**
 * Create an object of cleaned params that are expected by api module.
 *
 * @function buildParams
 * @description Create an object of cleaned params that are expected by api module.
 * @param {string} actionName - The name of the Action Api module to be executed.
 * @param {Object} data - The form data to be sent to api module.
 * @returns {Object} Cleaned params that are expected by api module.
 */
BaseClient.prototype.buildParams = function ( actionName, data ) {
	return {
		action: actionName
	};
};

module.exports = BaseClient;
