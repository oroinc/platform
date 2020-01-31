define(function(require, exports, module) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const tools = require('oroui/js/tools');
    const Backbone = require('backbone');
    let config = require('module-config').default(module.id);

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
    const ActionLauncher = Backbone.View.extend({
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
        ariaLabel: undefined,

        /** @property {String} */
        icon: undefined,

        /** @property {String} */
        iconClassName: undefined,

        /** @property {Boolean} */
        /** @deprecated use launcherMode */
        iconHideText: config.iconHideText,

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
         * @property {Object}
         */
        attributes: null,

        /**
         * Defines map of events => handlers
         * @return {Object}
         */
        events: function() {
            const events = {};
            let linkSelector = '';
            if (this.links) {
                events['shown.bs.dropdown'] = 'onDropdownShown';
                linkSelector = ' .dropdown-menu a';
            }
            events['click' + linkSelector] = 'onClick';
            return events;
        },

        /**
         * @inheritDoc
         */
        constructor: function ActionLauncher(options) {
            ActionLauncher.__super__.constructor.call(this, options);
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
         * @param {String} [options.launcherMode]
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

            const truthy = _.pick(options, 'template', 'label', 'title', 'ariaLabel', 'icon', 'link',
                'launcherMode', 'iconClassName', 'className', 'action', 'attributes');

            _.extend(
                this,
                _.pick(options, 'iconHideText', 'runAction', 'onClickReturnValue', 'links'),
                _.pick(truthy, Boolean)
            );

            if (!this.launcherMode) {
                this.launcherMode = this._convertToLauncherMode();
            }

            ActionLauncher.__super__.initialize.call(this, options);
        },

        /**
         * @return {String}
         */
        _convertToLauncherMode: function() {
            if (this.icon) {
                return this.iconHideText ? 'icon-only' : 'icon-text';
            } else {
                return 'text-only';
            }
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
            delete this.attributes;

            ActionLauncher.__super__.dispose.call(this);
        },

        getTemplateData: function() {
            const data = _.pick(this, 'icon', 'title', 'label', 'ariaLabel', 'className', 'iconClassName',
                'launcherMode', 'link', 'links', 'action', 'attributes', 'enabled', 'tagName');

            if (!data.label) {
                data.label = this.action.label;
            }

            if (!data.ariaLabel) {
                data.ariaLabel = this.action.ariaLabel
                    ? this.action.ariaLabel : `${data.label} ${__('oro.datagrid.action.default_postfix')}`;
            }

            if (!data.title) {
                data.title = data.label;
            }

            if (!data.launcherMode) {
                data.launcherMode = this._convertToLauncherMode();
            }

            return data;
        },

        /**
         * Render actions
         *
         * @return {*}
         */
        render: function() {
            this.$el.empty();
            const $el = $(this.template(this.getTemplateData()));
            this.setElement($el);

            this.trigger('render');

            return this;
        },

        /**
         * Handle launcher click
         *
         * @protected
         * @return {Boolean}
         */
        onClick: function(e) {
            let $link;
            let key;
            const actionOptions = {};
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
