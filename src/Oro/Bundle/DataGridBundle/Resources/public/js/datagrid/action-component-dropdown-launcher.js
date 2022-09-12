define(function(require) {
    'use strict';

    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const ActionLauncher = require('orodatagrid/js/datagrid/action-launcher');
    const DatagridSettingsDialogWidget = require('./datagrid-settings-dialog-widget');

    /**
     * @class ActionComponentDropdownLauncher
     * @extends ActionLauncher
     */
    const ActionComponentDropdownLauncher = ActionLauncher.extend({
        template: require('tpl-loader!orodatagrid/templates/datagrid/action-component-dropdown-launcher.html'),

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
            'show.bs.dropdown': 'onBeforeOpen',
            'shown.bs.dropdown': 'onOpen',
            'hide.bs.dropdown': 'onHide'
        },

        dialogWidget: null,

        allowDialog: false,

        /**
         * @inheritdoc
         */
        constructor: function ActionComponentDropdownLauncher(options) {
            ActionComponentDropdownLauncher.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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
         * @inheritdoc
         */
        getTemplateData: function() {
            const data = ActionComponentDropdownLauncher.__super__.getTemplateData.call(this);
            data.wrapperClassName = this.wrapperClassName;
            return data;
        },

        /**
         * @inheritdoc
         */
        render: function() {
            ActionComponentDropdownLauncher.__super__.render.call(this);
            if (this.allowDialog) {
                this.$('.dropdown-toggle').dropdown('dispose');
                this.$('.dropdown-toggle').on('click' + this.eventNamespace(), this.openDialogWidget.bind(this));
            } else {
                this.componentOptions._sourceElement = this.$('.dropdown-menu');
                const Component = this.componentConstructor;
                this.component = new Component(this.componentOptions);
            }
            return this;
        },

        /**
         * @inheritdoc
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
            const $dropdownMenu = this.$('>.dropdown-menu');
            if ($dropdownMenu.length) {
                const rect = $dropdownMenu[0].getBoundingClientRect();
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
        onHide: function(e) {
            if (e.clickEvent && !this.$(e.clickEvent.target).is('.close')) {
                const $clickTarget = this.$(e.clickEvent.target);
                if ($clickTarget.get(0) && !$clickTarget.is('.close')) {
                    // prevent closing dropdown on click within menu, except it's 'close' button
                    e.preventDefault();
                    return;
                }
            }

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
         * @inheritdoc
         */
        disable: function() {
            this.$('[data-toggle="dropdown"]').addClass('disabled');
            return ActionComponentDropdownLauncher.__super__.disable.call(this);
        },

        /**
         * @inheritdoc
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
