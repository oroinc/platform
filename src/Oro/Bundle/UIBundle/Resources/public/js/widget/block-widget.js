define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Backbone = require('backbone');
    const AbstractWidgetView = require('oroui/js/widget/abstract-widget');
    const $ = Backbone.$;

    /**
     * @export  oro/block-widget
     * @class   oro.BlockWidgetView
     * @extends oroui.widget.AbstractWidgetView
     */
    const BlockWidgetView = AbstractWidgetView.extend({
        options: _.extend({}, AbstractWidgetView.prototype.options, {
            type: 'block',
            cssClass: '',
            title: null,
            titleBlock: '.title',
            titleContainer: '.widget-title',
            actionsContainer: '.widget-actions-container',
            contentContainer: '.row-fluid',
            contentClasses: [],
            templateParams: {},
            template: _.template('<div class="box-type1" data-layout="separate">' +
                '<div class="title"<% if (_.isNull(title)) { %>style="display: none;"<% } %>>' +
                    '<div class="pull-right widget-actions-container"></div>' +
                    '<span class="widget-title"><%- title %></span>' +
                '</div>' +
                '<div class="row-fluid <%= contentClasses.join(\' \') %>"></div>' +
            '</div>')
        }),

        /**
         * @inheritdoc
         */
        constructor: function BlockWidgetView(options) {
            BlockWidgetView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            options = options || {};
            this.options = _.defaults(options, this.options);

            if (!_.isFunction(this.options.template)) {
                this.options.template = _.template(this.options.template);
            }
            const params = _.extend({
                title: this.options.title,
                contentClasses: this.options.contentClasses
            }, this.options.templateParams);
            this.widget = $(this.options.template(params));
            this.widget.addClass(this.options.cssClass);
            this.widgetContentContainer = this.widget.find(this.options.contentContainer);
            this.initializeWidget(options);
            this.delegateEvents();
        },

        setTitle: function(title) {
            if (_.isNull(this.options.title)) {
                this._getTitleContainer().closest(this.options.titleBlock).show();
            }
            this.options.title = title;
            this._getTitleContainer().text(this.options.title).attr('title', this.options.title);
        },

        getActionsElement: function() {
            if (this.actionsContainer === undefined) {
                this.actionsContainer = this.widget.find(this.options.actionsContainer);
            }
            return this.actionsContainer;
        },

        _getTitleContainer: function() {
            if (this.titleContainer === undefined) {
                this.titleContainer = this.widget.find(this.options.titleContainer);
            }
            return this.titleContainer;
        },

        /**
         * Remove widget
         */
        remove: function() {
            BlockWidgetView.__super__.remove.call(this);
            this.widget.remove();
        },

        show: function() {
            if (!this.$el.data('wid')) {
                if (this.$el.parent().length) {
                    this._showStatic();
                } else {
                    this._showRemote();
                }
            }
            this.loadingElement = this.widgetContentContainer.parent();
            BlockWidgetView.__super__.show.call(this);
        },

        _showStatic: function() {
            const anchorId = '_widget_anchor-' + this.getWid();
            const anchorDiv = $('<div id="' + anchorId + '"/>');
            anchorDiv.insertAfter(this.$el);
            this.widgetContentContainer.append(this.$el);
            $('#' + anchorId).replaceWith($(this.widget));
        },

        _showRemote: function() {
            this.widgetContentContainer.empty();
            this.widgetContentContainer.append(this.$el);
        },

        delegateEvents: function(events) {
            BlockWidgetView.__super__.delegateEvents.call(this, events);
            if (this.widget) {
                this._delegateWidgetEvents(events);
            }
        },

        _delegateWidgetEvents: function(events) {
            const delegateEventSplitter = /^(\S+)\s*(.*)$/;
            if (!(events || (events = _.result(this, 'widgetEvents')))) {
                return;
            }
            this._undelegateWidgetEvents();
            for (const key in events) {
                if (events.hasOwnProperty(key)) {
                    let method = events[key];
                    if (!_.isFunction(method)) {
                        method = this[events[key]];
                    }
                    if (!method) {
                        throw new Error('Method "' + events[key] + '" does not exist');
                    }
                    const match = key.match(delegateEventSplitter);
                    let eventName = match[1];
                    const selector = match[2];
                    method = method.bind(this);
                    eventName += '.delegateWidgetEvents' + this.cid;
                    if (selector === '') {
                        this.widget.on(eventName, method);
                    } else {
                        this.widget.on(eventName, selector, method);
                    }
                }
            }
        },

        _undelegateWidgetEvents: function() {
            this.widget.off('.delegateWidgetEvents' + this.cid);
        }
    });

    return BlockWidgetView;
});
