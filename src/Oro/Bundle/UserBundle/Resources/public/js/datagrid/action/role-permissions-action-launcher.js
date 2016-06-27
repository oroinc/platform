define(function(require) {
    'use strict';

    var RolePermissionsActionLauncher;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var DropdownMenuCollectionView = require('oroui/js/app/views/dropdown-menu-collection-view');

    RolePermissionsActionLauncher = BaseView.extend({
        className: 'dropdown',
        icon: '',
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
            'shown.bs.dropdown': 'onDropdownOpen'
        },

        initialize: function(options) {
            _.extend(this, _.pick(options, ['accessLevels', 'action']));
            RolePermissionsActionLauncher.__super__.initialize.call(this, options);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.accessLevels;
            delete this.action;
            RolePermissionsActionLauncher.__super__.dispose.call(this);
        },

        render: function() {
            var dropdown = this.subview('dropdown');
            if (dropdown) {
                dropdown.$el.detach();
            }
            RolePermissionsActionLauncher.__super__.render.call(this);
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
                    attributes: {
                        'data-options': '{"align": "right"}'
                    },
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
            var options = {modelPatch: patch};
            this.action.run(options);
        }
    });

    return RolePermissionsActionLauncher;
});
