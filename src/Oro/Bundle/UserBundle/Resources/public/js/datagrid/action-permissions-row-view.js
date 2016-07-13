define(function(require) {
    'use strict';

    var ActionPermissionsRowView;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var PermissionCollectionView = require('orouser/js/datagrid/permission/permission-collection-view');
    var RolePermissionsActionView = require('orouser/js/datagrid/role-permissions-action-view');
    var FieldView = require('orouser/js/datagrid/action-permissions-field-view');
    var BaseView = require('oroui/js/app/views/base/view');

    ActionPermissionsRowView = BaseView.extend({
        tagName: 'tr',
        autoRender: false,
        animationDuration: 0,
        template: require('tpl!orouser/templates/datagrid/action-permissions-row-view.html'),
        events: {
            'click .collapse-action': 'onFieldsSectionToggle'
        },
        initialize: function(options) {
            ActionPermissionsRowView.__super__.initialize.call(this, options);
            var fields = this.model.get('fields');
            if (typeof fields === 'string') {
                fields = JSON.parse(fields);
            }
            if (!_.isArray(fields)) {
                fields = _.values(fields);
            }
            this.model.set('fields', fields, {silent: true});
            this.listenTo(this.model.get('permissions'), 'change', this.onAccessLevelChange);
        },

        render: function() {
            ActionPermissionsRowView.__super__.render.call(this);
            var fields = this.model.get('fields');
            var rolePermissionsActionView = new RolePermissionsActionView({
                el: this.$('[data-name=row-action]'),
                accessLevels: this.model.get('permissions').accessLevels
            });
            this.subview('permissions-items', new PermissionCollectionView({
                el: this.$('[data-name=action-permissions-items]'),
                collection: this.model.get('permissions')
            }));
            this.subview('row-action', rolePermissionsActionView);
            rolePermissionsActionView.on('row-access-level-change', _.bind(function(data) {
                this.model.get('permissions').each(function(model) {
                    model.set(data);
                });
            }, this));
            if (fields.length > 0) {
                this.subview('fields-list', new BaseCollectionView({
                    el: this.$('[data-name=fields-list]'),
                    collection: new BaseCollection(fields),
                    animationDuration: 0,
                    itemView: FieldView
                }));
            }
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
        },

        onFieldsSectionToggle: function() {
            this.$el.toggleClass('collapsed');
            this.$('[data-name=fields-list]').slideToggle(!this.$el.hasClass('collapsed'));
        }
    });

    return ActionPermissionsRowView;
});
