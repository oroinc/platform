define(function(require) {
    'use strict';

    var BaseChartComponent,
        _ = require('underscore'),
        $ = require('jquery'),
        chartTemplate = require('text!orochart/js/templates/base-chart-template.html'),
        BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @class orochart.app.components.BaseChartComponent
     * @extends oroui.app.components.base.Component
     * @exports orochart/app/components/base-chart-component
     */
    BaseChartComponent = BaseComponent.extend({
        template: _.template(chartTemplate),

        /**
         *
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.data = options.data;
            this.options = options.options;
            this.config = options.config;

            this.$el = $(options._sourceElement);
            this.$chart = null;

            this.renderBaseLayout();

            this.$chart.bind('update.' + this.cid, $.proxy(this.update, this));
            $(window).bind('resize.' + this.cid, $.proxy(this.update, this));
        },

        /**
         * Dispose all event handlers
         *
         * @overrides
         */
        dispose: function() {
            this.$chart.unbind('.' + this.cid);
            $(window).unbind('.' + this.cid);
            BaseChartComponent.__super__.dispose.call(this);
        },

        renderBaseLayout: function() {
            this.$el.html(this.template());
            this.$chart = this.$el.find('.chart-content');
        },

        /**
         * Update chart size and redraw
         */
        update: function() {
            if(this.setChartSize()) {
                this.draw();
                this.fixSize();
            }
        },

        delayUpdate: function() {

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
                $chart.height(Math.min(Math.round(chartWidth * 0.4), 350));
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
                if(height > labelMaxHeight) {
                    labelMaxHeight = height;
                } else if(height < labelMinHeight) {
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
