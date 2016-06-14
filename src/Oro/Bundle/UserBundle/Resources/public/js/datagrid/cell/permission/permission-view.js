define(function(require) {
    'use strict';

    var PermissionView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var DropdownMenuCollectionView = require('oroui/js/app/views/dropdown-menu-collection-view');

    PermissionView = BaseView.extend({
        tagName: 'li',
        className: 'action-permissions__item dropdown',
        template: require('tpl!orouser/templates/datagrid/cell/permission/permission-view.html'),
        events: {
            'shown.bs.dropdown': 'onDropdownOpen'
        },
        listen: {
            'change:value model': 'render'
        },
        id: function() {
            return 'ActionPermissionsCell-' + this.cid;
        },

        /**
         * @type {AccessLevelsCollection?}
         */
        accessLevels: null,

        initialize: function(options) {
            _.extend(this, _.pick(options, ['accessLevels']));
            PermissionView.__super__.initialize.call(this, options);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.accessLevels;
            PermissionView.__super__.dispose.call(this);
        },

        getTemplateData: function() {
            var data = PermissionView.__super__.getTemplateData.call(this);
            data.dropdownTarget = '#' + _.result(this, 'id');
            data.isValueChanged = this.model.isValueChanged();
            return data;
        },

        render: function() {
            var dropdown = this.subview('dropdown');
            if (dropdown) {
                dropdown.$el.detach();
            }
            PermissionView.__super__.render.call(this);
            if (dropdown) {
                this.$el.append(dropdown.$el);
            }
        },

        onDropdownOpen: function(e) {
            var dropdown = this.subview('dropdown');
            if (!dropdown) {
                dropdown = new DropdownMenuCollectionView({
                    collection: this.accessLevels,
                    keysMap: {
                        id: 'value',
                        text: 'value_text'
                    }
                });
                this.listenTo(dropdown, 'selected', this.onAccessLevelSelect);
                this.subview('dropdown', dropdown);
                this.$el.append(dropdown.$el);
            }
            if (!this.accessLevels.length) {
                this.accessLevels.fetch();
            }
        },

        onAccessLevelSelect: function(patch) {
            this.model.set(patch);
        }
    });

    return PermissionView;
});
