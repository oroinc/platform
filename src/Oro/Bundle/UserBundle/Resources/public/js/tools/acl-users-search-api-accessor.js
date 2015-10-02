/** @lends AclUsersSearchApiAccessor */
define(function(require) {
    'use strict';

    /**
     * Provides access to acl_users search API.
     *
     * @class
     * @augment SearchApiAccessor
     * @exports AclUsersSearchApiAccessor
     */
    var AclUsersSearchApiAccessor;
    var SearchApiAccessor = require('oroui/js/tools/search-api-accessor');

    AclUsersSearchApiAccessor = SearchApiAccessor.extend(/** @exports AclUsersSearchApiAccessor.prototype */{
        /**
         * @constructor
         * @param options {object}
         * @param options.entity_name {string} entity name to check permissions on
         * @param options.permission {string} Optional. Permission name to check
         */
        initialize: function(options) {
            if (!options) {
                options = {};
            }
            options.search_handler_name = 'acl_users';
            if (!options.entity_name) {
                throw new Error('`entity_name` option is required');
            }
            this.entityName = options.entity_name;
            options.label_field_name = options.label_field_name || 'fullName';
            this.permission = options.permission || 'ASSIGN';
            AclUsersSearchApiAccessor.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        prepareUrlParameters: function(urlParameters) {
            if (!urlParameters.id) {
                throw new Error('`id` url parameter is required');
            }
            urlParameters.query = [urlParameters.term, this.entityName, this.permission,
                urlParameters.id, ''].join(';');
            return urlParameters;
        }
    });

    return AclUsersSearchApiAccessor;
});
