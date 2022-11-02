define(function(require, exports, module) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const Backbone = require('backbone');
    let config = require('module-config').default(module.id);

    config = _.extend({
        launcherMode: 'icon-only'
    }, config);

    /**
     * Action launcher implemented as simple link or a set of links. Click on a link triggers action run
     *
     * Events:
     * click: Fired when launcher was clicked
     *
     * @export  orodatagrid/js/datagrid/action-launcher
     * @class   orodatagrid.datagrid.ActionLauncher
     * @extends Backbone.View
     */
    const SelectChoiceLauncher = Backbone.View.extend({
        /** @property */
        enabled: true,

        /** @property {String} */
        tagName: 'a',

        /** @property {Boolean} */
        onClickReturnValue: true,

        /** @property {oro.datagrid.action.AbstractAction} */
        action: undefined,

        /** @property {String} */
        label: undefined,

        /** @property {String} */
        ariaLabel: undefined,

        /**
         * Allow to use / set default aria-label attribute if it not defined
         *
         * @property {boolean}
         */
        allowDefaultAriaLabel: false,

        /** @property {String}: 'icon-text' | 'icon-only' | 'text-only' */
        launcherMode: '',

        /** @property {String} */
        className: undefined,

        /** @property {String} */
        link: '#',

        /** @property {Array} */
        links: undefined,

        /** @property {String} */
        runAction: true,

        /** @property {function(Object, ?Object=): String} */
        template: require('tpl-loader!orodatagrid/templates/datagrid/action-launcher.html'),

        /**
         * Defines map of events => handlers
         * @return {Object}
         */
        events: function() {
            const events = {};
            events['shown.bs.dropdown'] = 'onDropdownShown';
            events['click .dropdown-menu a'] = 'onClick';
            return events;
        },

        /**
         * @inheritdoc
         */
        constructor: function SelectChoiceLauncher(options) {
            SelectChoiceLauncher.__super__.constructor.call(this, options);
        },

        /**
         * Initialize
         *
         * @param {Object} options
         * @param {oro.datagrid.action.AbstractAction} options.action
         * @param {function(Object, ?Object=): string} [options.template]
         * @param {String} [options.label]
         * @param {Boolean} [options.launcherMode]
         * @param {String} [options.link]
         * @param {Boolean} [options.runAction]
         * @param {Boolean} [options.onClickReturnValue]
         * @param {Array} [options.links]
         * @throws {TypeError} If mandatory option is undefined
         */
        initialize: function(options) {
            if (!options.action) {
                throw new TypeError('"action" is required');
            }

            this.setOptions(options);
            this.selectedItem = options.selectedItem || options.items[0];

            SelectChoiceLauncher.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.action;
            delete this.runAction;

            SelectChoiceLauncher.__super__.dispose.call(this);
        },

        /**
         * @param {Object} options
         * @param {oro.datagrid.action.AbstractAction} options.action
         * @param {function(Object, ?Object=): string} [options.template]
         * @param {String} [options.label]
         * @param {Boolean} [options.launcherMode]
         * @param {String} [options.link]
         * @param {Boolean} [options.runAction]
         * @param {Boolean} [options.onClickReturnValue]
         * @param {Array} [options.links]
         */
        setOptions: function(options) {
            const truthy = _.pick(options, 'template', 'label', 'ariaLabel', 'allowDefaultAriaLabel',
                'link', 'launcherMode', 'className', 'attributes', 'runAction');

            _.extend(
                this,
                _.pick(options, 'action', 'items'),
                _.pick(truthy, Boolean)
            );
        },

        getTemplateData: function() {
            const label = this.label || this.action.label;
            let ariaLabel = this.ariaLabel;

            if (!ariaLabel && this.action.ariaLabel) {
                ariaLabel = this.action.ariaLabel;
            }

            if (!ariaLabel && this.allowDefaultAriaLabel) {
                ariaLabel = this.getDefaultAriaLabel(label);
            }

            if (!this.launcherMode) {
                this.launcherMode = this.icon ? config.launcherMode : 'text-only';
            }

            return {
                label: label,
                icon: this.selectedItem.icon,
                title: this.selectedItem.title,
                ariaLabel: ariaLabel,
                className: this.className,
                iconClassName: this.selectedItem.iconClassName,
                launcherMode: this.launcherMode,
                link: this.link,
                links: this.items,
                action: this.action,
                attributes: this.attributes,
                enabled: this.enabled,
                tagName: this.tagName
            };
        },

        /**
         * @return {string}
         */
        getDefaultAriaLabel: function(label) {
            return `${label} ${__('oro.datagrid.action.default_postfix')}`;
        },

        /**
         * Render actions
         *
         * @return {*}
         */
        render: function() {
            this.$el.empty();
            const $el = $(this.template(this.getTemplateData()));
            $el.insertAfter(this.$el);
            this.$el.remove();
            this.setElement($el);
            return this;
        },

        /**
         * Handle launcher click
         *
         * @protected
         * @return {Boolean}
         */
        onClick: function(e) {
            const actionOptions = {};
            if (!this.enabled) {
                return this.onClickReturnValue;
            }
            this.trigger('click', this, e.currentTarget);
            const $link = $(e.currentTarget);
            actionOptions.key = $link.data('key');
            actionOptions.index = parseInt($link.data('index'));
            actionOptions.item = this.items[actionOptions.index];

            this.action.run(actionOptions);
            e.preventDefault();
        },

        onDropdownShown: function(e) {
            this.trigger('expand', this);
        },

        /**
         * Disable
         *
         * @return {*}
         */
        disable: function() {
            this.enabled = false;
            this.$el.addClass('disabled');
            this.$('[data-toggle="dropdown"]').addClass('disabled');
            return this;
        },

        /**
         * Enable
         *
         * @return {*}
         */
        enable: function() {
            this.enabled = true;
            this.$el.removeClass('disabled');
            this.$('[data-toggle="dropdown"]').removeClass('disabled');
            return this;
        }
    });

    return SelectChoiceLauncher;
});
