/** @lends AclUsersSearchApiAccessor */
define(function(require) {
    'use strict';

    /**
     * Provides access to acl_users search API.
     *
     * @class
     * @augments [SearchApiAccessor](../../../../UIBundle/Resources/doc/reference/client-side/search-api-accessor.md)
     *
     * @param options {Object}
     * @param options.permission_check_entity_name {string} - Entity name to check permissions on
     * @param options.permission {string} - Optional. Permission name to check. Default value is `'ASSIGN'`
     *
     * @exports AclUsersSearchApiAccessor
     */
    var AclUsersSearchApiAccessor;
    var SearchApiAccessor = require('oroui/js/tools/search-api-accessor');

    AclUsersSearchApiAccessor = SearchApiAccessor.extend(/** @exports AclUsersSearchApiAccessor.prototype */{
        /**
         * @constructor
         */
        initialize: function(options) {
            if (!options) {
                options = {};
            }
            options.search_handler_name = 'acl_users';
            if (!options.permission_check_entity_name) {
                throw new Error('`permission_check_entity_name` option is required');
            }
            this.permissionCheckEntityName = options.permission_check_entity_name;
            options.label_field_name = options.label_field_name || 'fullName';
            this.permission = options.permission || 'ASSIGN';
            AclUsersSearchApiAccessor.__super__.initialize.call(this, options);
        },

        prepareUrlParameters: function(urlParameters) {
            if (!urlParameters.id) {
                throw new Error('`id` url parameter is required');
            }
            urlParameters.query = [urlParameters.term, this.permissionCheckEntityName, this.permission,
                urlParameters.id, ''].join(';');
            urlParameters.name = this.searchHandlerName;
            return urlParameters;
        }
    });

    return AclUsersSearchApiAccessor;
});
