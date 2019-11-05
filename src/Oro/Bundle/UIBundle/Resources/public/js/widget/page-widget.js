define(['underscore', 'backbone', 'oroui/js/mediator', 'oro/block-widget'
], function(_, Backbone, mediator, BlockWidget) {
    'use strict';

    const $ = Backbone.$;

    /**
     * @export  oro/page-widget
     * @class   oro.PageWidget
     * @extends oro.BlockWidget
     */
    const PageWidget = BlockWidget.extend({
        options: _.extend({}, BlockWidget.prototype.options, {
            type: 'page',
            contentContainer: '.layout-content',
            template: _.template('<div>' +
                '<div class="container-fluid page-title">' +
                    '<div class="navigation navbar-extra navbar-extra-right">' +
                        '<div class="row">' +
                            '<div class="pull-left">' +
                                '<div class="clearfix">' +
                                    '<div class="page-title__path pull-left">' +
                                        '<div class="clearfix">' +
                                            '<div class="pull-left">' +
                                                '<h1 class="page-title__entity-title widget-title"><%- title %></h1>' +
                                            '</div>' +
                                        '</div>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +

                            '<div class="pull-right title-buttons-container widget-actions-container"></div>' +
                        '</div>' +
                    '</div>' +
                    '</div>' +
                '<div class="scrollable-container layout-content <%= contentClasses.join(\' \') %>"></div>' +
            '</div>'),
            replacementEl: null
        }),

        constructor: function PageWidget(options) {
            PageWidget.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$replacementEl = $(this.options.replacementEl);
            this.options.container = this.$replacementEl.parent();
            this.on('adoptedFormResetClick', this.remove);

            PageWidget.__super__.initialize.call(this, options);
        },

        remove: function() {
            PageWidget.__super__.remove.call(this);

            const latestShownPageWidget = $('.page-widget').last();
            latestShownPageWidget.show();
            if (!latestShownPageWidget.length) {
                this.$replacementEl.show();
            }
            mediator.trigger('layout:adjustHeight');
        },

        _show: function() {
            const latestShownPageWidget = $('.page-widget').last();
            latestShownPageWidget.hide();
            if (!latestShownPageWidget.length) {
                this.$replacementEl.hide();
            }
            this.widget.addClass('page-widget');

            PageWidget.__super__._show.call(this);
            this.getActionsElement().find('button').wrap('<div class="btn-group"/>');

            mediator.trigger('layout:adjustHeight');
        }
    });

    return PageWidget;
});
