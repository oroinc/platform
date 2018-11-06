define(function(require) {
    'use strict';

    var ActionComponentDropdownLauncher;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var ActionLauncher = require('orodatagrid/js/datagrid/action-launcher');
    var DatagridSettingsDialogWidget = require('./datagrid-settings-dialog-widget');

    /**
     * @class ActionComponentDropdownLauncher
     * @extends ActionLauncher
     */
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
            'show.bs.dropdown': 'onBeforeOpen',
            'shown.bs.dropdown': 'onOpen',
            'hide.bs.dropdown': 'onHide'
        },

        dialogWidget: null,

        allowDialog: true,

        /**
         * @inheritDoc
         */
        constructor: function ActionComponentDropdownLauncher() {
            ActionComponentDropdownLauncher.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['allowDialog']));
            this.componentOptions = _.omit(options, ['action', 'componentConstructor']);
            this.componentConstructor = options.componentConstructor;
            this.componentOptions.grid = options.action.datagrid;
            if (options.wrapperClassName) {
                this.wrapperClassName = options.wrapperClassName;
            }
            mediator.on('layout:reposition', this._updateDropdown, this);

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
        render: function() {
            ActionComponentDropdownLauncher.__super__.render.call(this);
            if (_.isMobile() && this.allowDialog) {
                this.$('.dropdown-toggle').dropdown('dispose');
                this.$('.dropdown-toggle').on('click' + this.eventNamespace(), _.bind(this.openDialogWidget, this));
            }
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

            this.$('.dropdown-toggle').off(this.eventNamespace());

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
         * Handles bootstrap dropdown show event
         *
         * @param {jQuery.Event} showEvent
         */
        onBeforeOpen: function(showEvent) {
            if (_.isFunction(this.component.beforeOpen)) {
                this.component.beforeOpen(showEvent);
            }
        },

        /**
         * Handles dropdown menu open and sets max-width for the element
         */
        onOpen: function() {
            if (_.isFunction(this.component.updateViews)) {
                this.component.updateViews();
            }
            var $dropdownMenu = this.$('>.dropdown-menu');
            if ($dropdownMenu.length) {
                var rect = $dropdownMenu[0].getBoundingClientRect();
                $dropdownMenu.css({
                    maxWidth: rect.right + 'px'
                });

                // focus input after Bootstrap opened dropdown menu
                $dropdownMenu.focusFirstInput();
            }
            mediator.trigger('dropdown-launcher:show');
        },

        /**
         * Handles dropdown menu hide
         */
        onHide: function() {
            mediator.trigger('dropdown-launcher:hide');
        },

        /**
         * Create component view in scope of DialogWidget instance
         */
        openDialogWidget: function() {
            mediator.execute('showLoading');

            this.dialogWidget = new DatagridSettingsDialogWidget({
                title: _.__('oro.datagrid.settings.title'),
                View: this.componentConstructor,
                viewOptions: this.componentOptions,
                stateEnabled: false,
                incrementalPosition: true,
                resize: false
            });

            this.dialogWidget.render();
        },

        /**
         * @inheritDoc
         */
        disable: function() {
            this.$('[data-toggle="dropdown"]').addClass('disabled');
            return ActionComponentDropdownLauncher.__super__.disable.call(this);
        },

        /**
         * @inheritDoc
         */
        enable: function() {
            this.$('[data-toggle="dropdown"]').removeClass('disabled');
            return ActionComponentDropdownLauncher.__super__.enable.call(this);
        },

        /**
         * Triggering dropdown update
         * @private
         */
        _updateDropdown: function() {
            this.$('[data-toggle="dropdown"]').dropdown('update');
        }
    });

    return ActionComponentDropdownLauncher;
});
