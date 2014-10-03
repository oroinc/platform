define(function(require) {

    var _ = require("underscore");
    var $ = require("jquery");
    var chartTemplate = require("text!orochart/js/templates/base-chart-template.html");
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseChartComponent;


    /**
     *
     * @class orochart.app.components.BaseCharComponent
     * @exports orochart/app/components/BaseCharComponent
     */
    BaseChartComponent = BaseComponent.extend({
        template: _.template(chartTemplate),

        /**
         *
         * @constructor
         * @param {object} params
         */
        initialize: function(params) {
            this.data = params.data;
            this.options = params.options;
            this.config = params.config;

            this.$el = $(params._sourceElement);
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
            var $labels = $chart.find(".flotr-grid-label-x");
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
            this.$el.html("copmonent");
        }
    });

    return BaseChartComponent;
});
