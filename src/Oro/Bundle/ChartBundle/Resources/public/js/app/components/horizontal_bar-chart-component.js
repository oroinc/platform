define(function(require) {
    'use strict';

    var BarChartComponent;
    var Flotr = require('flotr2');
    var numberFormatter = require('orolocale/js/formatter/number');
    var BaseChartComponent = require('orochart/js/app/components/base-chart-component');

    /**
     * @class orochart.app.components.BarChartComponent
     * @extends orochart.app.components.BaseChartComponent
     * @exports orochart/app/components/horizontal_bar-char-component
     */
    BarChartComponent = BaseChartComponent.extend({
        /**
         * Draw chart
         *
         * @overrides
         */
        draw: function() {
            var intValue;
            var maxValue = 0;
            var $chart = this.$chart;
            var data = this.data;
            var options = this.options;
            var settings = this.options.settings;
            var formatter = options.data_schema.value.formatter;

            var yNumber = 0;
            var chartData = [];
            var yLabels = [];
            var chartOptions;

            for (var i in data.reverse()) {
                if (!data.hasOwnProperty(i)) {
                    continue;
                }
                intValue = parseInt(data[i].value);
                maxValue = Math.max(intValue, maxValue);
                chartData.push([intValue, yNumber++]);
                yLabels.push(data[i].label);
            }

            chartOptions = {
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
                            var xValue;
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
