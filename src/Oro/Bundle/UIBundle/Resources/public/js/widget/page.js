/*global define*/
define(['underscore', 'backbone', 'oroui/js/mediator', 'oro/block-widget'
], function (_, Backbone, mediator, BlockWidget) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/page-widget
     * @class   oro.PageWidget
     * @extends oro.BlockWidget
     */
    return BlockWidget.extend({
        options: _.extend({}, BlockWidget.prototype.options, {
            type: 'page',
            contentContainer: '.layout-content',
            template: _.template('<div>' +
                '<div class="container-fluid page-title">' +
                    '<div class="navigation clearfix navbar-extra navbar-extra-right">' +
                        '<div class="row">' +
                            '<div class="span9">' +
                                '<div class="clearfix customer-info well-small customer-simple">' +
                                    '<div class="customer-content pull-left">' +
                                        '<div class="clearfix">' +
                                            '<div class="pull-left">' +
                                                '<h1 class="user-name widget-title"><%- title %></h1>' +
                                            '</div>' +
                                        '</div>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +

                            '<div class="pull-right title-buttons-container widget-actions-container"></div>' +
                        '</div>' +
                    '</div>' +
                    '</div>' +

                '<div class="layout-content scrollable-container <%= contentClasses.join(\' \') %>"></div>' +
            '</div>'),
            replacementEl: null
        }),

        initialize: function(options) {
            this.$replacementEl = $(this.options.replacementEl);
            this.options.container = this.$replacementEl.parent();
            this.on('adoptedFormResetClick', this.remove);

            BlockWidget.prototype.initialize.apply(this, options);
        },

        remove: function() {
            this.$replacementEl.show();

            BlockWidget.prototype.remove.apply(this);

            mediator.trigger('layout:adjustHeight');
        },

        show: function() {
            this.$replacementEl.hide();

            BlockWidget.prototype.show.apply(this);

            mediator.trigger('layout:adjustHeight');
        }
    });
});
