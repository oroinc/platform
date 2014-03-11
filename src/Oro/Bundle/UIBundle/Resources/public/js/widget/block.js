/*global define*/
define(['underscore', 'backbone', 'oroui/js/widget/abstract'
    ], function (_, Backbone, AbstractWidget) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/block-widget
     * @class   oro.BlockWidget
     * @extends oro.AbstractWidget
     */
    return AbstractWidget.extend({
        options: _.extend({}, AbstractWidget.prototype.options, {
            type: 'block',
            title: null,
            titleBlock: '.title',
            titleContainer: '.widget-title',
            actionsContainer: '.widget-actions-container',
            contentContainer: '.row-fluid',
            contentClasses: [],
            template: _.template('<div class="box-type1">' +
                '<div class="title"<% if (_.isNull(title)) { %>style="display: none;"<% } %>>' +
                    '<div class="pull-right widget-actions-container"></div>' +
                    '<span class="widget-title"><%- title %></span>' +
                '</div>' +
                '<div class="row-fluid <%= contentClasses.join(\' \') %>"></div>' +
            '</div>')
        }),

        initialize: function(options) {
            options = options || {};

            if (!_.isFunction(this.options.template)) {
                this.options.template = _.template(this.options.template);
            }
            this.widget = $(this.options.template({
                'title': this.options.title,
                'contentClasses': this.options.contentClasses
            }));
            this.widgetContentContainer = this.widget.find(this.options.contentContainer);
            this.initializeWidget(options);
        },

        setTitle: function(title) {
            if (_.isNull(this.options.title)) {
                this._getTitleContainer().closest(this.options.titleBlock).show();
            }
            this.options.title = title;
            this._getTitleContainer().html(this.options.title);
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
            AbstractWidget.prototype.remove.call(this);
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
            AbstractWidget.prototype.show.apply(this);
        },

        _showStatic: function() {
            var anchorId = '_widget_anchor-' + this.getWid();
            var anchorDiv = $('<div id="' + anchorId + '"/>');
            anchorDiv.insertAfter(this.$el);
            this.widgetContentContainer.append(this.$el);
            $('#' + anchorId).replaceWith($(this.widget));
        },

        _showRemote: function() {
            this.widgetContentContainer.empty();
            this.widgetContentContainer.append(this.$el);
        }
    });
});
