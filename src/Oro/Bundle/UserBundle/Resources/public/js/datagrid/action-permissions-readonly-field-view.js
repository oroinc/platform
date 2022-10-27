define(function(require) {
    'use strict';

    const BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    const PermissionReadOnlyView = require('orouser/js/datagrid/permission/permission-readonly-view');
    const BaseView = require('oroui/js/app/views/base/view');

    const ActionPermissionsReadonlyFieldView = BaseView.extend({
        autoRender: false,

        animationDuration: 0,

        className: 'field-permission-container',

        template: require('tpl-loader!orouser/templates/datagrid/action-permissions-field-view.html'),

        permissionView: PermissionReadOnlyView,

        /**
         * @inheritdoc
         */
        constructor: function ActionPermissionsReadonlyFieldView(options) {
            ActionPermissionsReadonlyFieldView.__super__.constructor.call(this, options);
        },

        render: function() {
            ActionPermissionsReadonlyFieldView.__super__.render.call(this);
            this.subview('permissions-items', new BaseCollectionView({
                el: this.$('[data-name=field-permissions-items]'),
                tagName: 'ul',
                className: 'action-permissions',
                animationDuration: 0,
                collection: this.model.get('permissions'),
                itemView: this.permissionView
            }));
            return this;
        }
    });

    return ActionPermissionsReadonlyFieldView;
});
