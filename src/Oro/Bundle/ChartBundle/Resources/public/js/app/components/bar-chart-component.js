define(function(require) {
    'use strict';

    var BarChartComponent;
    var Flotr = require('flotr2');
    var numberFormatter = require('orolocale/js/formatter/number');
    var BaseChartComponent = require('orochart/js/app/components/base-chart-component');

    /**
     * @class orochart.app.components.BarChartComponent
     * @extends orochart.app.components.BaseChartComponent
     * @exports orochart/app/components/bar-char-component
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

            var xNumber = 0;
            var chartData = [];
            var xLabels = [];
            var chartOptions;

            for (var i in data) {
                if (!data.hasOwnProperty(i)) {
                    continue;
                }
                intValue = parseInt(data[i].value);
                maxValue = Math.max(intValue, maxValue);
                chartData.push([xNumber++, intValue]);
                xLabels.push(data[i].label);
            }

            chartOptions = {
                data: chartData,
                color: settings.chartColors[0],
                markers: {
                    show: true,
                    position: 'ct',
                    labelFormatter: function(data) {
                        if (formatter) {
                            return numberFormatter[formatter](data.y);
                        } else {
                            return data.y;
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
                        horizontal: false,
                        shadowSize: 0,
                        barWidth: 0.5
                    },
                    mouse: {
                        track: true,
                        relative: true,
                        trackFormatter: function(data) {
                            var yValue;

                            if (formatter) {
                                yValue = numberFormatter[formatter](data.y);
                                return numberFormatter[formatter](data.y);
                            } else {
                                yValue = data.y;
                            }

                            return xLabels[parseInt(data.x)] + ': ' + yValue;
                        }
                    },
                    yaxis: {
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
                    xaxis: {
                        noTicks: settings.xNoTicks,
                        tickFormatter: function(x) {
                            return xLabels[parseInt(x)];
                        }
                    },
                    grid: {
                        verticalLines: false
                    }
                }
            );
        }
    });

    return BarChartComponent;
});
