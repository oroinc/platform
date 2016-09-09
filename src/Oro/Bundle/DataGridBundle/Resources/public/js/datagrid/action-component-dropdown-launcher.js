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

        events: {
            'click .dropdown-menu': 'onDropdownMenuClick',
            'shown.bs.dropdown': 'onOpen'
        },

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
        initComponent: function() {
            this.componentOptions._sourceElement = this.$('.dropdown-menu');
            var Component = this.componentConstructor;
            this.component = new Component(this.componentOptions);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            if (this.component) {
                this.component.dispose();
            }
            delete this.component;
            delete this.componentOptions;
            ActionComponentDropdownLauncher.__super__.dispose.call(this);
        },

        /**
         * Prevents dropdown menu from closing on click
         *
         * @param {jQuery.Event} e
         */
        onDropdownMenuClick: function(e) {
            if (!this.$(e.target).is('.close')) {
                e.stopPropagation();
            }
        },

        /**
         * Handles dropdown menu open and sets max-width for the element
         */
        onOpen: function() {
            if (!this.component) {
                this.initComponent();
            }
            var $dropdownMenu = this.$('>.dropdown-menu');
            if ($dropdownMenu.length) {
                var rect = $dropdownMenu[0].getBoundingClientRect();
                $dropdownMenu.css({
                    maxWidth: rect.right + 'px'
                });
            }
            var $elem = this.$('.dropdown-menu');
            // focus input after Bootstrap opened dropdown menu
            $elem.focusFirstInput();
            if (_.isFunction(this.component.updateViews)) {
                this.component.updateViews();
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
