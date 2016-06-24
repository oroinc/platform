define(function(require) {
    'use strict';

    var ActionLauncher;
    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
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
    ActionLauncher = Backbone.View.extend({
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
            var linkSelector = '';
            if (this.links) {
                events['shown.bs.dropdown'] = 'onDropdownShown';
                linkSelector = ' .dropdown-menu a';
            }
            events['click' + linkSelector] = 'onClick';
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

            if (opts.label) {
                this.label = opts.label;
            }

            if (opts.title) {
                this.title = opts.title;
            }

            if (opts.icon) {
                this.icon = opts.icon;
            }

            if (opts.iconHideText !== undefined) {
                this.iconHideText = opts.iconHideText;
            }

            if (opts.link) {
                this.link = opts.link;
            }

            if (opts.iconClassName) {
                this.iconClassName = opts.iconClassName;
            }

            if (opts.className) {
                this.className = opts.className;
            }

            if (_.has(opts, 'runAction')) {
                this.runAction = opts.runAction;
            }

            if (_.has(opts, 'onClickReturnValue')) {
                this.onClickReturnValue = opts.onClickReturnValue;
            }

            if (_.has(opts, 'links')) {
                this.links = options.links;
            }

            this.action = opts.action;
            ActionLauncher.__super__.initialize.apply(this, arguments);
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
            ActionLauncher.__super__.dispose.apply(this, arguments);
        },

        getTemplateData: function() {
            var label = this.label || this.action.label;

            return {
                label: label,
                icon: this.icon,
                iconHideText: this.iconHideText,
                title: this.title || label,
                className: this.className,
                iconClassName: this.iconClassName,
                link: this.link,
                links: this.links,
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
            var key;
            var actionOptions = {};
            if (!this.enabled) {
                return this.onClickReturnValue;
            }
            this.trigger('click', this, e.currentTarget);
            if (this.runAction) {
                if (this.links) {
                    $link = $(e.currentTarget);
                    key = $link.data('key');
                    if (!_.isUndefined(key)) {
                        this.action.actionKey = key;
                        $link.closest('.btn-group').toggleClass('open');
                    }
                }
                if (tools.isTargetBlankEvent(e)) {
                    actionOptions.target = '_blank';
                }
                this.action.run(actionOptions);

                //  skip launcher functionality, if action was executed
                e.preventDefault();
            }
            return this.onClickReturnValue;
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
            return this;
        }
    });

    return ActionLauncher;
});
