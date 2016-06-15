define(function(require) {
    'use strict';

    var RolePermissionsAction;
    var _ = require('underscore');
    var BaseClass = require('oroui/js/base-class');
    var RolePermissionsActionLauncher = require('orouser/js/datagrid/action/role-permissions-action-launcher');

    RolePermissionsAction = BaseClass.extend({
        /**
         * @type {Collection}
         */
        permissions: null,

        initialize: function(options) {
            this.permissions = options.model.get('permissions');
            RolePermissionsAction.__super__.initialize.call(this, options);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.permissions;
            RolePermissionsAction.__super__.dispose.call(this);
        },

        createLauncher: function(options) {
            _.extend(options, {
                accessLevels: this.permissions.accessLevels,
                action: this
            });
            return new RolePermissionsActionLauncher(options);
        },

        run: function(options) {
            options = _.defaults(options, {
                doExecute: true
            });
            this.trigger('preExecute', this, options);
            if (options.doExecute) {
                this.execute(options);
                this.trigger('postExecute', this, options);
            }
        },

        execute: function(options) {
            this.permissions.each(function(model) {
                model.set(options.modelPatch);
            });
        }
    });

    return RolePermissionsAction;
});
