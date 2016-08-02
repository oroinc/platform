define(function(require) {
    'use strict';

    var RolePermissionsActionView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var DropdownMenuCollectionView = require('oroui/js/app/views/dropdown-menu-collection-view');

    RolePermissionsActionView = BaseView.extend({
        className: 'dropdown',
        icon: '',
        autoRender: true,
        template: function() {
            return '<a data-toggle="dropdown" ' +
                'class="dropdown-toggle role-permissions-action-launcher" href="javascript:void(0);">...</a>';
        },

        /**
         * @type {AccessLevelsCollection}
         */
        accessLevels: null,

        /**
         * @type {RolePermissionsAction}
         */
        action: null,

        events: {
            'shown.bs.dropdown': 'onDropdownOpen',
            'click': '_showDropdown',
            'mouseover .dropdown-toggle': '_showDropdown',
            'mouseleave .dropdown-menu, .dropdown-menu__placeholder': '_hideDropdown'
        },

        initialize: function(options) {
            _.extend(this, _.pick(options, ['accessLevels', 'action']));
            RolePermissionsActionView.__super__.initialize.call(this, options);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.accessLevels;
            delete this.action;
            RolePermissionsActionView.__super__.dispose.call(this);
        },

        render: function() {
            var dropdown = this.subview('dropdown');
            if (dropdown) {
                dropdown.$el.detach();
            }
            RolePermissionsActionView.__super__.render.call(this);
            if (dropdown) {
                this.$el.append(dropdown.$el);
            }
        },

        onDropdownOpen: function(e) {
            var dropdown = this.subview('dropdown');
            var accessLevels = this.accessLevels;
            if (!dropdown) {
                dropdown = new DropdownMenuCollectionView({
                    className: [
                        'dropdown-menu',
                        'dropdown-menu-collection',
                        'dropdown-menu__action-cell',
                        'dropdown-menu__role-permissions-action'
                    ].join(' '),
                    collection: accessLevels,
                    keysMap: {
                        id: 'access_level',
                        text: 'access_level_label'
                    }
                });
                this.listenTo(dropdown, 'selected', this.onAccessLevelSelect);
                this.subview('dropdown', dropdown);
                this.$el.append(dropdown.$el);
            }
            if (!accessLevels.length) {
                accessLevels.fetch();
            }
        },

        onAccessLevelSelect: function(patch) {
            this.trigger('row-access-level-change', patch);
            if (this.$('.dropdown-toggle').parent().hasClass('open')) {
                this.$('.dropdown-toggle').dropdown('toggle');
            }
        },

        _showDropdown: function(e) {
            if (!this.$('.dropdown-toggle').parent().hasClass('open')) {
                this.$('.dropdown-toggle').dropdown('toggle');
            }
            e.stopPropagation();
        },

        _hideDropdown: function(e) {
            if (this.$('.dropdown-toggle').parent().hasClass('open')) {
                this.$('.dropdown-toggle').dropdown('toggle');
            }
            e.stopPropagation();
        }
    });

    return RolePermissionsActionView;
});
