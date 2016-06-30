define(function(require) {
    'use strict';

    var PermissionReadOnlyView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    PermissionReadOnlyView = BaseView.extend({
        tagName: 'li',
        className: 'action-permissions__item dropdown',
        template: require('tpl!orouser/templates/datagrid/cell/permission/permission-readonly-view.html'),
        id: function() {
            return 'ActionPermissionsCell-' + this.cid;
        },

        /**
         * @type {AccessLevelsCollection}
         */
        accessLevels: null,

        initialize: function(options) {
            _.extend(this, _.pick(options, ['accessLevels']));
            PermissionReadOnlyView.__super__.initialize.call(this, options);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.accessLevels;
            PermissionReadOnlyView.__super__.dispose.call(this);
        }
    });

    return PermissionReadOnlyView;
});
