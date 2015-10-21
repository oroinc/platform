define(function(require) {
    'use strict';

    var ActionComponentDropdownLauncher;
    var _ = require('underscore');
    var ActionLauncher = require('orodatagrid/js/datagrid/action-launcher');

    ActionComponentDropdownLauncher = ActionLauncher.extend({
        template: require('tpl!orodatagrid/templates/datagrid/action-component-dropdown-launcher.html'),

        /**
         * @type {Object}
         */
        componentOptions: null,

        /**
         * @type {BaseComponent}
         */
        component: null,

        /**
         * @type {Constructor.<BaseComponent>}
         */
        componentConstructor: null,

        /** @property {String} */
        wrapperClassName: undefined,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.componentOptions = _.omit(options, ['action', 'componentConstructor']);
            this.componentConstructor = options.componentConstructor;
            this.componentOptions.grid = options.action.datagrid;
            if (options.wrapperClassName) {
                this.wrapperClassName = options.wrapperClassName;
            }
            ActionComponentDropdownLauncher.__super__.initialize.call(this, options);
            this.events = {
                'click .dropdown-menu': 'onDropdownClick',
            };
        },

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            var data = ActionComponentDropdownLauncher.__super__.getTemplateData.call(this);
            data.wrapperClassName = this.wrapperClassName;
            return data;
        },

        /**
         * @inheritDoc
         */
        render: function() {
            ActionComponentDropdownLauncher.__super__.render.call(this);
            this.componentOptions._sourceElement = this.$('.dropdown-menu');
            var Component = this.componentConstructor;
            this.component = new Component(this.componentOptions);
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
            ActionComponentDropdownLauncher.__super__.dispose.call(this);
        },

        /**
         * Prevents dropdown from closing on click
         *
         * @param {jQuery.Event} e
         */
        onDropdownClick: function(e) {
            if (!this.$(e.target).is('.close')) {
                e.stopPropagation();
            }
        },

        /**
         * @inheritDoc
         */
        disable: function() {
            this.$('.dropdown-toggle').addClass('disabled');
            return ActionComponentDropdownLauncher.__super__.disable.call(this);
        },

        /**
         * @inheritDoc
         */
        enable: function() {
            this.$('.dropdown-toggle').removeClass('disabled');
            return ActionComponentDropdownLauncher.__super__.enable.call(this);
        }
    });

    return ActionComponentDropdownLauncher;
});
