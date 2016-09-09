define(function(require) {
    'use strict';

    var ActionPermissionsReadonlyRowView;
    var _ = require('underscore');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var PermissionReadOnlyView = require('orouser/js/datagrid/permission/permission-readonly-view');
    var ReadonlyFieldView = require('orouser/js/datagrid/action-permissions-readonly-field-view');
    var BaseView = require('oroui/js/app/views/base/view');

    ActionPermissionsReadonlyRowView = BaseView.extend({
        tagName: 'tr',
        className: 'collapsed',
        autoRender: false,
        animationDuration: 0,
        template: require('tpl!orouser/templates/datagrid/action-permissions-row-view.html'),
        permissionItemView: PermissionReadOnlyView,
        fieldItemView: ReadonlyFieldView,
        events: {
            'click .collapse-action': 'onFieldsSectionToggle'
        },
        initialize: function(options) {
            ActionPermissionsReadonlyRowView.__super__.initialize.call(this, options);
            var fields = this.model.get('fields');
            if (typeof fields === 'string') {
                fields = JSON.parse(fields);
            }
            if (!_.isArray(fields)) {
                fields = _.values(fields);
            }
            this.model.set('fields', fields, {silent: true});
        },

        render: function() {
            ActionPermissionsReadonlyRowView.__super__.render.call(this);
            var fields = this.model.get('fields');
            this.subview('permissions-items', new BaseCollectionView({
                el: this.$('[data-name=action-permissions-items]'),
                tagName: 'ul',
                className: 'action-permissions',
                animationDuration: 0,
                collection: this.model.get('permissions'),
                itemView: this.permissionItemView
            }));
            if (fields.length > 0) {
                this.subview('fields-list', new BaseCollectionView({
                    el: this.$('[data-name=fields-list]'),
                    collection: new BaseCollection(fields),
                    animationDuration: 0,
                    itemView: this.fieldItemView
                }));
            }
            return this;
        },

        onFieldsSectionToggle: function(e) {
            e.preventDefault();
            this.$el.toggleClass('collapsed');
            this.$('[data-name=fields-list]').slideToggle(!this.$el.hasClass('collapsed'));
        }
    });

    return ActionPermissionsReadonlyRowView;
});
