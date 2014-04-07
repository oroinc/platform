/*global define*/
define(['underscore', 'backbone', 'oroui/js/mediator', 'oro/block-widget'
    ], function (_, Backbone, mediator, BlockWidget) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  orodashboard/js/widget/dashboard-item
     * @class   orodashboard.DashboardItemWidget
     * @extends oro.BlockWidget
     */
    return BlockWidget.extend({
        expanded: true,

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
                        '<a class="move-action" href="#"><i class="icon-move hide-text"></i></a>' +
                        '<a class="remove-action" href="#"><i class="icon-trash hide-text"></i></a>' +
                    '</div>' +
                    '<div class="pull-right widget-actions-container"></div>' +
                '</div>' +
                '<div class="row-fluid <%= contentClasses.join(\' \') %>"></div>' +
            '</div>')
        }),

        initialize: function() {
            BlockWidget.prototype.initialize.apply(this, arguments);
            this.once('widgetRender', this._initWidgetCollapseState);
        },

        _initWidgetCollapseState: function() {
            if (this.isCollapsed()) {
                this._collapse();
            } else {
                this._expand();
            }
        },

        collapse: function() {
            this._collapse();
            this.trigger('collapse', this.$el, this);
            mediator.trigger('widget:dashboard:collapse:' + this.getWid(), this.$el, this);
        },

        _collapse: function() {
            this.widget.attr('collapsed', 1);
            this.widgetContentContainer.hide();
            $('.collapse-expand-action-container .collapse-action').hide();
            $('.collapse-expand-action-container .expand-action').show();
        },

        expand: function() {
            this._expand();
            this.trigger('expand', this.$el, this);
            mediator.trigger('widget:dashboard:expand:' + this.getWid(), this.$el, this);
        },

        _expand: function() {
            this.widget.removeAttr('collapsed');
            this.widgetContentContainer.show();
            $('.collapse-expand-action-container .collapse-action').show();
            $('.collapse-expand-action-container .expand-action').hide();
        },

        isCollapsed: function() {
            return this.widget.attr('collapsed');
        },

        onMove: function() {
            this.trigger('move', this.$el, this);
            mediator.trigger('widget:dashboard:move:' + this.getWid(), this.$el, this);
        },

        onRemoveFromDashboard: function() {
            this.trigger('removeFromDashboard', this.$el, this);
            mediator.trigger('widget:dashboard:removeFromDashboard:' + this.getWid(), this.$el, this);
        }
    });
});
