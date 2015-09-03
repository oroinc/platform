define(function(require) {
    'use strict';

    var ColumnManagerLauncher;
    var _ = require('underscore');
    var ActionLauncher = require('orodatagrid/js/datagrid/action-launcher');
    var ColumnManagerComponent = require('orodatagrid/js/app/components/column-manager-component');

    ColumnManagerLauncher = ActionLauncher.extend({
        template: require('tpl!orodatagrid/templates/datagrid/column-manager-launcher.html'),
        /**
         * @type {Object}
         */
        componentOptions: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.componentOptions = _.omit(options, ['action']);
            this.componentOptions.grid = options.action.datagrid;
            ColumnManagerLauncher.__super__.initialize.call(this, options);
            this.events = {
                'click .dropdown-menu': 'onDropdownClick'
            };
        },

        /**
         * @inheritDoc
         */
        render: function() {
            ColumnManagerLauncher.__super__.render.call(this);
            this.componentOptions._sourceElement = this.$('.dropdown-menu');
            this.component = new ColumnManagerComponent(this.componentOptions);
            return this;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.component.dispose();
            delete this.component;
            delete this.componentOptions;
            ColumnManagerLauncher.__super__.dispose.call(this);
        },

        /**
         * Prevents dropdown from closing on click
         *
         * @param {jQuery.Event} e
         */
        onDropdownClick: function(e) {
            e.stopPropagation();
        }
    });

    return ColumnManagerLauncher;
});
