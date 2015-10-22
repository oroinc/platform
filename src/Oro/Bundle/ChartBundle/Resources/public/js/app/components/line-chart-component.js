define(function(require) {
    'use strict';

    var LineChartComponent;
    var _ = require('underscore');
    var Flotr = require('flotr2');
    var dataFormatter = require('orochart/js/data_formatter');
    var BaseChartComponent = require('orochart/js/app/components/base-chart-component');

    /**
     * @class orochart.app.components.LineChartComponent
     * @extends orochart.app.components.BaseChartComponent
     * @exports orochart/app/components/line-chart-component
     */
    LineChartComponent = BaseChartComponent.extend({
        /**
         * Sorts chart data
         */
        sortData: function() {
            this.data.sort(_.bind(function(first, second) {
                if (first.label === null || first.label === undefined) {
                    return -1;
                }
                if (second.label === null || second.label === undefined) {
                    return 1;
                }
                var firstLabel = dataFormatter.parseValue(first.label, this.options.data_schema.label.type);
                var secondLabel = dataFormatter.parseValue(second.label, this.options.data_schema.label.type);
                return firstLabel - secondLabel;
            }, this));
        },

        /**
         * Process data for chart and collects limits
         *
         * @param {Array.<Object>} chartData
         * @param {Object} limits
         */
        processData: function(chartData, limits) {
            for (var i in this.data) {
                if (!this.data.hasOwnProperty(i)) {
                    continue;
                }
                var yValue = dataFormatter.parseValue(this.data[i].value, this.options.data_schema.value.type);
                yValue = yValue === null ? parseInt(i) : yValue;
                var xValue = dataFormatter.parseValue(this.data[i].label, this.options.data_schema.label.type);
                xValue = xValue === null ? parseInt(i) : xValue;
                if (limits.xMax === null) {
                    limits.xMax = xValue;
                    limits.yMax = yValue;
                    limits.yMin = yValue;
                    limits.xMin = xValue;
                }
                limits.xMax = limits.xMax < xValue ? xValue : limits.xMax;
                limits.xMin = limits.xMin > xValue ? xValue : limits.xMin;
                limits.yMax = limits.yMax < yValue ? yValue : limits.yMax;
                limits.yMin = limits.yMin > yValue ? yValue : limits.yMin;

                var item = [xValue, yValue];
                chartData.push(item);
            }
            var deltaX = limits.xMax - limits.xMin;
            var deltaY = limits.yMax - limits.yMin;
            var xStep = (deltaX > 0 ? deltaX / this.data.length : 1);
            var yStep = (deltaY > 0 ? deltaY / this.data.length : 1);
            limits.xMax += xStep;
            limits.yMax += yStep;
            limits.xMin -= xStep;
            limits.yMin -= yStep;
        },

        /**
         * Draw chart
         *
         * @overrides
         */
        draw: function() {
            var $chart = this.$chart;
            var options = this.options;
            var xFormat = options.data_schema.label.type;
            var yFormat = options.data_schema.value.type;
            if (!$chart.get(0).clientWidth) {
                return;
            }

            var rawData = this.data;

            if (dataFormatter.isValueNumerical(xFormat)) {
                this.sortData();
            }

            var connectDots = options.settings.connect_dots_with_line;
            var colors = this.config.default_settings.chartColors;
            var getXLabel = function(data) {
                var label = dataFormatter.formatValue(data, xFormat);
                if (label === null) {
                    var number = parseInt(data);
                    if (rawData.length > number) {
                        label = rawData[number].label === null ? 'N/A' : rawData[number].label;
                    } else {
                        label = '';
                    }
                }
                return label;
            };
            var getYLabel = function(data) {
                var label = dataFormatter.formatValue(data, yFormat);
                if (label === null) {
                    var number = parseInt(data);
                    if (rawData.length > number) {
                        label = rawData[data].value === null ? 'N/A' : rawData[data].value;
                    } else {
                        label = '';
                    }
                }
                return label;
            };

            var chartData = [];
            var limits = {
                yMax: null,
                yMin: null,
                xMax: null,
                xMin: null
            };
            this.processData(chartData, limits);

            var chart = {
                data: chartData,
                color: colors[0],
                markers: {
                    show: true,
                    position: 'ct',
                    labelFormatter: function(pointData) {
                        return getYLabel(pointData.y);
                    }
                },
                points: {
                    show: !connectDots
                }
            };
            Flotr.draw(
                $chart.get(0),
                [chart],
                {
                    colors: options.settings.chartColors,
                    fontColor: options.settings.chartFontColor,
                    fontSize: options.settings.chartFontSize,
                    lines: {
                        show: connectDots
                    },
                    mouse: {
                        track: true,
                        relative: true,
                        trackFormatter: function(pointData) {
                            return getXLabel(pointData.x) + ': ' + getYLabel(pointData.y);
                        }
                    },
                    yaxis: {
                        max: limits.yMax,
                        min: limits.yMin,
                        tickFormatter: function(y) {
                            return getYLabel(y);
                        },
                        title: options.data_schema.value.label
                    },
                    xaxis: {
                        max: limits.xMax,
                        min: limits.xMin,
                        tickFormatter: function(x) {
                            return getXLabel(x);
                        },
                        title: options.data_schema.label.label
                    },
                    HtmlText: false,
                    grid: {
                        verticalLines: false
                    }
                }
            );
        }
    });

    return LineChartComponent;
});
