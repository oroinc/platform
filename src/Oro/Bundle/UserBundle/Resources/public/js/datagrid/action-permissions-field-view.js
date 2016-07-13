define(function(require) {
    'use strict';

    var ActionPermissionsFieldView;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var PermissionModel = require('orouser/js/models/role/permission-model');
    var AccessLevelsCollection = require('orouser/js/models/role/access-levels-collection');
    var PermissionCollectionView = require('orouser/js/datagrid/permission/permission-collection-view');
    var BaseView = require('oroui/js/app/views/base/view');

    ActionPermissionsFieldView = BaseView.extend({
        autoRender: false,
        animationDuration: 0,
        className: 'field-permission-container clearfix',
        template: require('tpl!orouser/templates/datagrid/action-permissions-field-view.html'),

        render: function() {
            ActionPermissionsFieldView.__super__.render.call(this);
            var collection = new BaseCollection(_.values(this.model.get('permissions')), {
                model: PermissionModel
            });
            collection.accessLevels = new AccessLevelsCollection([], {
                routeParameters: {
                    oid: this.model.get('identity').replace(/\\/g, '_'),
                    permission: this.model.get('name')
                }
            });
            this.listenTo(collection, 'change', this.onAccessLevelChange);
            this.subview('permissions-items', new PermissionCollectionView({
                el: this.$('[data-name=field-permissions-items]'),
                collection: collection
            }));
            return this;
        },

        onAccessLevelChange: function(model) {
            mediator.trigger('securityAccessLevelsComponent:link:click', {
                accessLevel: model.get('access_level'),
                identityId: model.get('identity'),
                permissionName: model.get('name'),
                group: this.model.get('group'),
                category: this.model.get('group'),
                isInitialValue: !model.isAccessLevelChanged()
            });
        }
    });

    return ActionPermissionsFieldView;
});
