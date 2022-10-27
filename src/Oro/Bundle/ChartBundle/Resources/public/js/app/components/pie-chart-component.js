define(function(require) {
    'use strict';

    const Flotr = require('flotr2');
    const BaseChartComponent = require('orochart/js/app/components/base-chart-component');

    /**
     * @class orochart.app.components.PieChartComponent
     * @extends orochart.app.components.BaseChartComponent
     * @exports orochart/app/components/pie-chart-component
     */
    const PieChartComponent = BaseChartComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function PieChartComponent(options) {
            PieChartComponent.__super__.constructor.call(this, options);
        },

        /**
         * @overrides
         * @param {Object} options
         */
        initialize: function(options) {
            PieChartComponent.__super__.initialize.call(this, options);

            this.options.settings.ratio = options.ratio;
        },

        setChartSize: function() {
            let isChanged = false;
            const $container = this.$container;
            const isLegendWrapped = $container.hasClass('wrapped-chart-legend');
            const $chart = this.$chart;
            const $widgetContent = $container.parent();
            const $chartLegend = this.$legend;
            const chartWidth = Math.min(Math.round($widgetContent.width() * Number(this.options.settings.ratio)), 350);

            if (chartWidth > 0 && chartWidth !== $chart.width()) {
                $chart.width(chartWidth);
                $chart.height(chartWidth);
                $chartLegend.height(chartWidth);
                $chart.parent().width(chartWidth + $chartLegend.width());
                isChanged = true;
            }

            if ((isChanged || !isLegendWrapped) && this.$legend.position().top !== 0) {
                // container is not in wrapped mode yet but the legend already has dropped under chart
                $container.width(this.$chart.width());
                $container.addClass('wrapped-chart-legend');
                isChanged = true; // force changed ro redraw chart
            } else if ((isChanged || isLegendWrapped) && $container.outerWidth(true) / $container.width() > 1.7) {
                // container is in wrapped mode but there's already place for legend next to chart
                $container.removeClass('wrapped-chart-legend');
                $container.width('auto');
                isChanged = true; // force changed ro redraw chart
            }

            return isChanged;
        },

        setChartContainerSize: function() {
            // there's nothing to do with container
        },

        /**
         * Draw chart
         *
         * @overrides
         */
        draw: function() {
            const $chart = this.$chart;
            const $legend = this.$legend;
            const data = this.data;
            const settings = this.options.settings;
            const chartData = [];
            let showPercentInTooltip = true;

            this.valueSuffix = settings.hasOwnProperty('valueSuffix') ? settings.valueSuffix : '';
            this.valuePrefix = settings.hasOwnProperty('valuePrefix') ? settings.valuePrefix : '';

            if (settings.hasOwnProperty('showPercentInTooltip')) {
                // handle boolean, int, string
                showPercentInTooltip = !!(parseInt(settings.showPercentInTooltip) || settings.showPercentInTooltip > 0);
            }

            const trackFormatter = (showPercentInTooltip ? this.percentFormatter : this.valueFormatter).bind(this);

            for (const i in data) {
                if (data.hasOwnProperty(i)) {
                    chartData.push({data: [[0, data[i].fraction]], label: data[i].label});
                }
            }

            Flotr.draw(
                $chart.get(0),
                chartData,
                {
                    colors: settings.chartColors,
                    fontColor: settings.chartFontColor,
                    fontSize: settings.chartFontSize,
                    shadowSize: 0,
                    HtmlText: true,
                    xaxis: {
                        showLabels: false
                    },
                    yaxis: {
                        showLabels: false
                    },
                    grid: {
                        color: settings.chartFontColor,
                        verticalLines: false,
                        horizontalLines: false,
                        outlineWidth: 0
                    },
                    pie: {
                        show: true,
                        explode: 0,
                        sizeRatio: 0.8,
                        startAngle: Math.PI / 3.5,
                        labelFormatter: function(total, value) {
                            return parseFloat(parseFloat(value * 100).toFixed(2)) + '%';
                        }
                    },
                    mouse: {
                        track: true,
                        relative: true,
                        lineColor: settings.chartHighlightColor,
                        trackFormatter: trackFormatter
                    },
                    legend: {
                        position: 'ne',
                        container: $legend.get(0),
                        labelBoxWidth: 20,
                        labelBoxHeight: 13,
                        labelBoxMargin: 0
                    }
                }
            );
        },

        percentFormatter: function(obj) {
            const value = parseFloat(parseFloat(obj[this.options.settings.fraction_input_data_field] * 100).toFixed(2));

            return this.getTooltipText(obj.series.label, value, '', '%');
        },

        valueFormatter: function(obj) {
            const rawValue = this.data[obj.nearest.seriesIndex][this.options.settings.fraction_input_data_field];
            const value = parseFloat(parseFloat(rawValue).toPrecision(2));

            return this.getTooltipText(obj.series.label, value, this.valuePrefix, this.valueSuffix);
        },

        getTooltipText: function(label, value, valuePrefix, valueSuffix) {
            return label + ':&nbsp;&nbsp;&nbsp;' + valuePrefix + value + valueSuffix;
        }
    });

    return PieChartComponent;
});
