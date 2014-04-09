/*global define*/
define(['underscore', 'backbone', 'oroui/js/mediator', 'oro/block-widget'
    ], function (_, Backbone, mediator, BlockWidget) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  orodashboard/js/dashboard
     * @class   orodashboard.Dashboard
     * @extends oro.BlockWidget
     */
    return BlockWidget.extend({
        /**
         * Widget events
         *
         * @property {Object}
         */
        widgetEvents: {
            'click .collapse-expand-action-container .collapse-action': function(event) {
                event.preventDefault();
                this.collapse();
            },
            'click .collapse-expand-action-container .expand-action': function(event) {
                event.preventDefault();
                this.expand();
            },
            'click .collapse-expand-action-container .move-action': function(event) {
                event.preventDefault();
                this.onMove();
            },
            'click .collapse-expand-action-container .remove-action': function(event) {
                event.preventDefault();
                this.onRemoveFromDashboard();
            }
        },

        /**
         * Widget options
         *
         * @property {Object}
         */
        options: _.extend({}, BlockWidget.prototype.options, {
            type: 'dashboard-item',
            actionsContainer: '.widget-actions-container',
            contentContainer: '.row-fluid',
            contentClasses: [],
            template: _.template('<div class="box-type1 dashboard-widget">' +
                '<div class="title">' +
                    '<div class="pull-left collapse-expand-action-container">' +
                        '<a class="collapse-action" href="#"><i class="icon-collapse hide-text"></i></a>' +
                        '<a class="expand-action" href="#"><i class="icon-collapse-top hide-text"></i></a>' +
                    '</div>' +
                    '<span class="widget-title"><%- title %></span>' +
                    '<div class="pull-right default-actions-container">' +
                        '<span class="action-wrapper">' +
                            '<a class="move-action" href="#"><i class="icon-move hide-text"></i></a>' +
                        '</span>' +
                        '<span class="action-wrapper">' +
                            '<a class="remove-action" href="#"><i class="icon-trash hide-text"></i></a>' +
                        '</span>' +
                    '</div>' +
                    '<div class="pull-right widget-actions-container"></div>' +
                '</div>' +
                '<div class="row-fluid <%= contentClasses.join(\' \') %>"></div>' +
            '</div>')
        }),

        /**
         * Initialize
         */
        initialize: function() {
            BlockWidget.prototype.initialize.apply(this, arguments);
            this.once('widgetRender', this._initWidgetCollapseState);
        },

        /**
         * Set initial widget collapse state
         */
        _initWidgetCollapseState: function() {
            if (this.isCollapsed()) {
                this._setCollapsed();
            } else {
                this._setExpanded();
            }
        },

        /**
         * Collapse widget
         */
        collapse: function() {
            this._setCollapsed();
            this.trigger('collapse', this.$el, this);
            mediator.trigger('widget:dashboard:collapse:' + this.getWid(), this.$el, this);
        },

        /**
         * Set collapsed state
         */
        _setCollapsed: function() {
            this.widget.addClass('collapsed');
            this.widgetContentContainer.hide();
            $('.collapse-expand-action-container .collapse-action').hide();
            $('.collapse-expand-action-container .expand-action').show();
        },

        /**
         * Expand widget
         */
        expand: function() {
            this._setExpanded();
            this.trigger('expand', this.$el, this);
            mediator.trigger('widget:dashboard:expand:' + this.getWid(), this.$el, this);
        },

        /**
         * Set expanded state
         */
        _setExpanded: function() {
            this.widget.removeClass('collapsed');
            this.widgetContentContainer.show();
            $('.collapse-expand-action-container .collapse-action').show();
            $('.collapse-expand-action-container .expand-action').hide();
        },

        /**
         * Is collapsed
         *
         * @returns {HTMLElement}
         */
        isCollapsed: function() {
            return this.widget.hasClass('collapsed');
        },

        /**
         * Triggering move action
         */
        onMove: function() {
            this.trigger('move', this.$el, this);
            mediator.trigger('widget:dashboard:move:' + this.getWid(), this.$el, this);
        },

        /**
         * Trigger remove action
         */
        onRemoveFromDashboard: function() {
            this.trigger('removeFromDashboard', this.$el, this);
            mediator.trigger('widget:dashboard:removeFromDashboard:' + this.getWid(), this.$el, this);
        }
    });
});
