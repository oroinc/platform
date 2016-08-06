define(function(require) {
    'use strict';

    var SelectChoiceLauncher;
    var $ = require('jquery');
    var _ = require('underscore');
    var Backbone = require('backbone');
    var module = require('module');

    var config = module.config();
    config = _.extend({
        iconHideText: true
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
    SelectChoiceLauncher = Backbone.View.extend({
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
        title: undefined,

        /** @property {String} */
        icon: undefined,

        /** @property {Boolean} */
        iconHideText: config.iconHideText,

        /** @property {String} */
        iconClassName: undefined,

        /** @property {String} */
        className: undefined,

        /** @property {String} */
        link: '#',

        /** @property {Array} */
        links: undefined,

        /** @property {String} */
        runAction: true,

        /** @property {function(Object, ?Object=): String} */
        template: require('tpl!orodatagrid/templates/datagrid/action-launcher.html'),

        /**
         * Defines map of events => handlers
         * @return {Object}
         */
        events: function() {
            var events = {};
            events['shown.bs.dropdown'] = 'onDropdownShown';
            events['click .dropdown-menu a'] = 'onClick';
            return events;
        },

        /**
         * Initialize
         *
         * @param {Object} options
         * @param {oro.datagrid.action.AbstractAction} options.action
         * @param {function(Object, ?Object=): string} [options.template]
         * @param {String} [options.label]
         * @param {String} [options.icon]
         * @param {Boolean} [options.iconHideText]
         * @param {String} [options.link]
         * @param {Boolean} [options.runAction]
         * @param {Boolean} [options.onClickReturnValue]
         * @param {Array} [options.links]
         * @throws {TypeError} If mandatory option is undefined
         */
        initialize: function(options) {
            var opts = options || {};

            if (!opts.action) {
                throw new TypeError('"action" is required');
            }

            if (opts.template) {
                this.template = opts.template;
            }

            if (opts.attributes) {
                this.attributes = opts.attributes;
            }

            if (opts.iconHideText !== undefined) {
                this.iconHideText = opts.iconHideText;
            }

            if (opts.className) {
                this.className = opts.className;
            }

            if (_.has(opts, 'runAction')) {
                this.runAction = opts.runAction;
            }

            this.selectedItem = opts.selectedItem || opts.items[0];

            this.items = opts.items;

            this.action = opts.action;
            SelectChoiceLauncher.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.action;
            delete this.runAction;
            SelectChoiceLauncher.__super__.dispose.apply(this, arguments);
        },

        getTemplateData: function() {
            var label = this.label || this.action.label;

            return {
                label: label,
                icon: this.selectedItem.icon,
                iconHideText: this.iconHideText,
                title: this.selectedItem.title,
                className: this.selectedItem.className,
                iconClassName: this.selectedItem.iconClassName,
                link: this.link,
                links: this.items,
                action: this.action,
                attributes: this.attributes,
                enabled: this.enabled,
                tagName: this.tagName
            };
        },

        /**
         * Render actions
         *
         * @return {*}
         */
        render: function() {
            this.$el.empty();
            var $el = $(this.template(this.getTemplateData()));
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
            var $link;
            var actionOptions = {};
            if (!this.enabled) {
                return this.onClickReturnValue;
            }
            this.trigger('click', this, e.currentTarget);
            $link = $(e.currentTarget);
            actionOptions.key = $link.data('key');
            actionOptions.index = parseInt($link.data('index'));
            actionOptions.item = this.items[actionOptions.index];
            $link.closest('.btn-group').toggleClass('open');

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
            this.$('.dropdown-toggle').addClass('disabled');
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
            this.$('.dropdown-toggle').removeClass('disabled');
            return this;
        }
    });

    return SelectChoiceLauncher;
});
