define(function(require) {
    'use strict';

    var BaseChartComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var chartTemplate = require('text!orochart/js/templates/base-chart-template.html');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @class orochart.app.components.BaseChartComponent
     * @extends oroui.app.components.base.Component
     * @exports orochart/app/components/base-chart-component
     */
    BaseChartComponent = BaseComponent.extend({
        template: _.template(chartTemplate),

        aspectRatio: 0.4,
        /**
         *
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            var updateHandler;
            this.data = options.data;
            this.options = options.options;
            this.config = options.config;

            this.$el = $(options._sourceElement);
            this.$chart = null;

            this.renderBaseLayout();

            updateHandler = _.bind(this.update, this);

            this.$chart.bind('update.' + this.cid, updateHandler);
            // updates the chart on resize once per frame (1000/25)
            $(window).bind('resize.' + this.cid, _.throttle(updateHandler, 40, {leading: false}));
            $(window).bind('responsive-reflow.' + this.cid, updateHandler);

            _.defer(updateHandler);
        },

        /**
         * Dispose all event handlers
         *
         * @overrides
         */
        dispose: function() {
            this.$chart.unbind('.' + this.cid);
            $(window).unbind('.' + this.cid);
            delete this.$el;
            delete this.$chart;
            delete this.$legend;
            delete this.$container;
            BaseChartComponent.__super__.dispose.call(this);
        },

        renderBaseLayout: function() {
            this.$el.html(this.template());
            this.$chart = this.$el.find('.chart-content');
            this.$legend = this.$el.find('.chart-legend');
            this.$container = this.$el.find('.chart-container');
        },

        /**
         * Update chart size and redraw
         */
        update: function() {
            var isChanged = this.setChartSize();

            if (isChanged) {
                this.draw();
                this.fixSize();
            }
        },

        /**
         * Set size of chart
         *
         * @returns {boolean}
         */
        setChartSize: function() {
            var $chart = this.$chart;
            var $widgetContent = $chart.parents('.chart-container').parent();
            var chartWidth = Math.round($widgetContent.width() * 0.9);

            if (chartWidth > 0 && chartWidth !== $chart.width()) {
                $chart.width(chartWidth);
                $chart.height(Math.min(Math.round(chartWidth * this.aspectRatio), 350));
                return true;
            }
            return false;
        },

        /**
         * Set size of chart container
         */
        setChartContainerSize: function() {
            this.$chart.closest('.clearfix').width(this.$chart.width());
        },

        /**
         * Fix chart size after drawing to solve problems with too long labels
         */
        fixSize: function() {
            var $chart = this.$chart;
            var $labels = $chart.find('.flotr-grid-label-x');
            var labelMaxHeight = $labels.height();
            var labelMinHeight = $labels.height();

            $labels.each(function(index, element) {
                var height = $(element).height();
                if (height > labelMaxHeight) {
                    labelMaxHeight = height;
                } else if (height < labelMinHeight) {
                    labelMinHeight = height;
                }
            });

            $chart.height($chart.height() + (labelMaxHeight - labelMinHeight));

            this.setChartContainerSize();
        },

        /**
         * Draw comonent
         */
        draw: function() {
            this.$el.html('copmonent');
        }
    });

    return BaseChartComponent;
});
