define([
    'jquery',
    'underscore',
    'backbone'
], function($, _, Backbone) {
    'use strict';

    var ActionLauncher;

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

        /** @property {String} */
        iconClassName: undefined,

        /** @property {String} */
        className: undefined,

        /** @property {String} */
        link: 'javascript:void(0);',

        /** @property {Array} */
        links: undefined,

        /** @property {String} */
        runAction: true,

        /** @property {function(Object, ?Object=): String} */
        template: _.template(
            '<% if (links) { %><div class="btn-group"><% } %>' +
            '<<%= tagName %>' +
                '<% if (tagName == "a") { %> href="<%= link %>"<% } %>' +
                ' class="action' +
                    '<%= className ? " " + className : "" %>' +
                    '<%= !enabled ? " disabled" : "" %>' +
                    '<% if (links) { %> dropdown-toggle<% } %>' +
                '"' +
                ' <%= attributesTemplate({attributes: attributes}) %>' +
                ' title="<%= title %>"' +
                '<% if (links) { %> data-toggle="dropdown"<% } %>' +
            '>' +
                '<% if (icon) { %>' +
                    '<i class="icon-<%= icon %> hide-text"><%= label %></i>' +
                '<% } else { %>' +
                    '<% if (iconClassName) { %>' +
                        '<i class="<%= iconClassName %>"></i>' +
                    '<% } %>' +
                    ' <%= label %>' +
                '<% } %>' +
                '<% if (links) { %><i class="caret"></i><% } %>' +
            '</<%= tagName %>>' +
            '<% if (links) { %>' +
                '<ul class="dropdown-menu">' +
                '<% _.each(links, function(item) { %>' +
                    '<li><a href="<%= link %>"' +
                        ' title="<%= item.label %>"' +
                        '<% if (item.attributes) { %> <%= attributesTemplate(item) %><% } %>' +
                        ' data-key="<%= item.key %>"' +
                    '>' +
                    '<% if (item.icon) { %>' +
                        '<i class="icon-<%= item.icon %> hide-text"><%= item.label %></i>' +
                    '<% } else { %>' +
                        '<% if (item.iconClassName) { %>' +
                            '<i class="<%= item.iconClassName %>"></i>' +
                        '<% } %>' +
                        ' <%= item.label %>' +
                    '<% } %>' +
                    '</a></li>' +
                '<% }) %>' +
                '</ul>' +
            '</div>' +
            '<% } %>'
        ),

        attributesTemplate: _.template(
            '<% _.each(attributes, function(attribute, name) { %>' +
                '<%= name %><% if (!_.isNull(attribute)) { %>="<%= attribute %>"<% } %> ' +
            '<% }) %>'
        ),

        /**
         * Initialize
         *
         * @param {Object} options
         * @param {oro.datagrid.action.AbstractAction} options.action
         * @param {function(Object, ?Object=): string} [options.template]
         * @param {String} [options.label]
         * @param {String} [options.icon]
         * @param {String} [options.link]
         * @param {Boolean} [options.runAction]
         * @param {Boolean} [options.onClickReturnValue]
         * @param {Array} [options.links]
         * @throws {TypeError} If mandatory option is undefined
         */
        initialize: function(options) {
            var opts, linkSelector;
            opts = options || {};

            if (!opts.action) {
                throw new TypeError("'action' is required");
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

            this.events = {};
            linkSelector = '';
            if (_.has(opts, 'links')) {
                this.events['click .dropdown-toggle'] = 'onToggle';
                this.links = options.links;
                linkSelector = ' .dropdown-menu a';
            }
            this.events['click' + linkSelector] = 'onClick';

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

        /**
         * Render actions
         *
         * @return {*}
         */
        render: function() {
            var $el, label;
            this.$el.empty();

            label = this.label || this.action.label;
            $el = $(this.template({
                label: this.label || this.action.label,
                icon: this.icon,
                title: this.title || label,
                className: this.className,
                iconClassName: this.iconClassName,
                link: this.link,
                links: this.links,
                action: this.action,
                attributes: this.attributes,
                attributesTemplate: this.attributesTemplate,
                enabled: this.enabled,
                tagName: this.tagName
            }));

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
            var $link, key;
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
                this.action.run();

                //  skip launcher functionality, if action was executed
                return false;
            }
            return this.onClickReturnValue;
        },

        onToggle: function(e) {
            var $link = $(e.currentTarget);
            if (!$link.closest('.btn-group').hasClass('open')) {
                this.trigger('expand', this);
            }
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
