define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const DropdownMenuCollectionView = require('oroui/js/app/views/dropdown-menu-collection-view');

    const RolePermissionsActionView = BaseView.extend({
        className: 'dropleft',

        icon: '',

        autoRender: true,

        template: require('tpl-loader!orouser/templates/datagrid/role-permissions-action-view.html'),

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
            'mouseover .dropdown-toggle': '_showDropdown',
            'mouseleave': '_hideDropdown'
        },

        /**
         * @inheritdoc
         */
        constructor: function RolePermissionsActionView(options) {
            RolePermissionsActionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
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

        delegateListeners: function() {
            RolePermissionsActionView.__super__.delegateListeners.call(this);
            this.listenTo(this.accessLevels, 'sync', function() {
                this.$('[data-toggle="dropdown"]').dropdown('update');
            });
        },

        render: function() {
            const dropdown = this.subview('dropdown');
            if (dropdown) {
                this.$('[data-toggle="dropdown"]').dropdown('dispose');
                dropdown.$el.detach();
            }
            RolePermissionsActionView.__super__.render.call(this);
            if (dropdown) {
                this.$('[data-role="dropdown-menu-content"]').replaceWith(dropdown.$el);
            }
        },

        onDropdownOpen: function(e) {
            let dropdown = this.subview('dropdown');
            const accessLevels = this.accessLevels;
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
                this.subview('dropdown', dropdown);
            }
            if (!accessLevels.length) {
                accessLevels.fetch();
            }
        },

        onAccessLevelSelect: function(patch) {
            this.trigger('row-access-level-change', patch);
            if (this.$('[data-toggle="dropdown"]').parent().hasClass('show')) {
                this.$('[data-toggle="dropdown"]').dropdown('toggle');
            }
        },

        _showDropdown: function(e) {
            if (!this.$('[data-toggle="dropdown"]').parent().hasClass('show')) {
                this.$('[data-toggle="dropdown"]').dropdown('toggle');
            }
        },

        _hideDropdown: function(e) {
            if (this.$('[data-toggle="dropdown"]').parent().hasClass('show')) {
                this.$('[data-toggle="dropdown"]').dropdown('toggle');
            }
            e.stopPropagation();
        }
    });

    return RolePermissionsActionView;
});
