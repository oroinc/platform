define(function(require) {
    'use strict';

    const Flotr = require('flotr2');
    const numberFormatter = require('orolocale/js/formatter/number');
    const BaseChartComponent = require('orochart/js/app/components/base-chart-component');

    /**
     * @class orochart.app.components.BarChartComponent
     * @extends orochart.app.components.BaseChartComponent
     * @exports orochart/app/components/horizontal_bar-char-component
     */
    const BarChartComponent = BaseChartComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function BarChartComponent(options) {
            BarChartComponent.__super__.constructor.call(this, options);
        },

        /**
         * Draw chart
         *
         * @overrides
         */
        draw: function() {
            let intValue;
            let maxValue = 0;
            const $chart = this.$chart;
            const data = this.data;
            const options = this.options;
            const settings = this.options.settings;
            const formatter = options.data_schema.value.formatter;

            let yNumber = 0;
            const chartData = [];
            const yLabels = [];

            for (const i in data.reverse()) {
                if (!data.hasOwnProperty(i)) {
                    continue;
                }
                intValue = parseInt(data[i].value);
                maxValue = Math.max(intValue, maxValue);
                chartData.push([intValue, yNumber++]);
                yLabels.push(data[i].label);
            }

            const chartOptions = {
                data: chartData,
                color: settings.chartColors[0],
                markers: {
                    show: true,
                    position: 'mr',
                    labelFormatter: function(data) {
                        if (formatter) {
                            return numberFormatter[formatter](data.x);
                        } else {
                            return data.x;
                        }
                    }
                }
            };

            Flotr.draw($chart.get(0),
                [chartOptions],
                {
                    colors: settings.chartColors,
                    fontColor: settings.chartFontColor,
                    fontSize: settings.chartFontSize,
                    bars: {
                        show: true,
                        horizontal: true,
                        shadowSize: 0,
                        barWidth: 0.5
                    },
                    mouse: {
                        track: true,
                        relative: true,
                        position: 'se',
                        trackFormatter: function(data) {
                            let xValue;
                            if (formatter) {
                                xValue = numberFormatter[formatter](data.x);
                                return numberFormatter[formatter](data.x);
                            } else {
                                xValue = data.x;
                            }

                            return yLabels[parseInt(data.y)] + ': ' + xValue;
                        }
                    },
                    xaxis: {
                        min: 0,
                        max: maxValue * 1.2, // to make visible label above the highest bar
                        tickFormatter: function(y) {
                            if (formatter) {
                                return numberFormatter.formatCurrency(y);
                            } else {
                                return y;
                            }
                        }
                    },
                    yaxis: {
                        ticks: function() {
                            const ticks = [];
                            for (const i in yLabels) {
                                if (yLabels[i]) {
                                    ticks.push([i, yLabels[i]]);
                                }
                            }
                            return ticks;
                        },
                        tickFormatter: function(x) {
                            return yLabels[parseInt(x)];
                        }
                    },
                    grid: {
                        horizontalLines: false
                    }
                }
            );
        }
    });

    return BarChartComponent;
});
