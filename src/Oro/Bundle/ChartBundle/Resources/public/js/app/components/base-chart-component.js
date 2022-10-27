define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const chartTemplate = require('text-loader!orochart/js/templates/base-chart-template.html');
    const BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @class orochart.app.components.BaseChartComponent
     * @extends oroui.app.components.base.Component
     * @exports orochart/app/components/base-chart-component
     */
    const BaseChartComponent = BaseComponent.extend({
        template: _.template(chartTemplate),

        aspectRatio: 0.4,

        updateDelay: 40,

        listen: {
            'layout:reposition mediator': 'debouncedUpdate'
        },

        /**
         * @inheritdoc
         */
        constructor: function BaseChartComponent(options) {
            this.debouncedUpdate = _.debounce(this.update, this.updateDelay);

            BaseChartComponent.__super__.constructor.call(this, options);
        },

        /**
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

            const updateHandler = this.update.bind(this);

            this.$chart.bind('update.' + this.cid, updateHandler);

            _.defer(updateHandler);
        },

        /**
         * Dispose all event handlers
         *
         * @overrides
         */
        dispose: function() {
            this.$chart.unbind('.' + this.cid);
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
            const isChanged = this.setChartSize();

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
            const $chart = this.$chart;
            const $widgetContent = $chart.parents('.chart-container').parent();
            const chartWidth = Math.round($widgetContent.width() * 0.9);

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
            const $chart = this.$chart;
            const $labels = $chart.find('.flotr-grid-label-x');
            let labelMaxHeight = $labels.height();
            let labelMinHeight = $labels.height();

            $labels.each(function(index, element) {
                const height = $(element).height();
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
