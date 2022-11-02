define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const DropdownMenuCollectionView = require('oroui/js/app/views/dropdown-menu-collection-view');

    const PermissionView = BaseView.extend({
        tagName: 'li',

        className: 'action-permissions__item dropdown',

        template: require('tpl-loader!orouser/templates/datagrid/permission/permission-view.html'),

        events: {
            'shown.bs.dropdown': 'onDropdownOpen',
            'hide.bs.dropdown': 'onDropdownClose'
        },

        listen: {
            'change:access_level model': 'render'
        },

        /**
         * @inheritdoc
         */
        constructor: function PermissionView(options) {
            PermissionView.__super__.constructor.call(this, options);
        },

        id: function() {
            return 'ActionPermissionsCell-' + this.cid;
        },

        getTemplateData: function() {
            const data = PermissionView.__super__.getTemplateData.call(this);
            data.dropdownTarget = '#' + _.result(this, 'id');
            data.isAccessLevelChanged = this.model.isAccessLevelChanged();
            return data;
        },

        render: function() {
            const dropdown = this.subview('dropdown');
            this.$el.trigger('tohide.bs.dropdown');
            if (dropdown) {
                this.$('[data-toggle="dropdown"]').dropdown('dispose');
                dropdown.$el.detach();
            }
            PermissionView.__super__.render.call(this);
            if (dropdown) {
                this.$('[data-role="dropdown-menu-content"]').replaceWith(dropdown.$el);
            }
        },

        onDropdownOpen: function(e) {
            let dropdown = this.subview('dropdown');
            const accessLevels = this.model.accessLevels;
            if (!dropdown) {
                dropdown = new DropdownMenuCollectionView({
                    el: this.$('[data-role="dropdown-menu-content"]'),
                    collection: accessLevels,
                    keysMap: {
                        id: 'access_level',
                        text: 'access_level_label'
                    }
                });
                this.listenTo(dropdown, 'selected', this.onAccessLevelSelect);
                this.listenTo(this.model.accessLevels, 'sync', function() {
                    this.$('[data-toggle="dropdown"]').dropdown('update');
                });
                this.subview('dropdown', dropdown);
            }
            if (!accessLevels.length) {
                accessLevels.fetch({
                    success: function(collection) {
                        _.each(collection.models, function(model) {
                            if (isNaN(model.get('access_level'))) {
                                collection.remove(model);
                            }
                        });
                    }
                });
            }
            this.$('.action-permissions__item-wrapper').addClass('active');
        },

        onDropdownClose: function(e) {
            this.$('.action-permissions__item-wrapper').removeClass('active');
        },

        onAccessLevelSelect: function(patch) {
            this.model.set(patch);
        }
    });

    return PermissionView;
});
