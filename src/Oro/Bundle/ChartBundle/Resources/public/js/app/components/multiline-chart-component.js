define(function(require) {
    'use strict';

    var MultilineChartComponent;
    var _ = require('underscore');
    var Flotr = require('flotr2');
    var dataFormatter = require('orochart/js/data_formatter');
    var BaseChartComponent = require('orochart/js/app/components/base-chart-component');

    /**
     * @class orochart.app.components.MultilineChartComponent
     * @extends orochart.app.components.BaseChartComponent
     * @exports orochart/app/components/multiline-chart-component
     */
    MultilineChartComponent = BaseChartComponent.extend({
        /**
         * Draw chart
         *
         * @overrides
         */
        draw: function() {
            var options = this.options;
            var $chart = this.$chart;
            var xFormat = options.data_schema.label.type;
            var yFormat = options.data_schema.value.type;
            var narrowScreen = screen.width <= 480;
            if (!$chart.get(0).clientWidth) {
                return;
            }

            var rawData = this.data;

            if (dataFormatter.isValueNumerical(xFormat)) {
                var sort = function(rawData) {
                    rawData.sort(function(first, second) {
                        if (first.label === null || first.label === undefined) {
                            return -1;
                        }
                        if (second.label === null || second.label === undefined) {
                            return 1;
                        }
                        var firstLabel = dataFormatter.parseValue(first.label, xFormat);
                        var secondLabel = dataFormatter.parseValue(second.label, xFormat);
                        return firstLabel - secondLabel;
                    });
                };

                _.each(rawData, sort);
            }

            var connectDots = options.settings.connect_dots_with_line;
            var colors = this.config.default_settings.chartColors;

            var count = 0;
            var charts = [];

            var getXLabel = function(data) {
                var label = dataFormatter.formatValue(data, xFormat);
                if (label === null) {
                    var number = parseInt(data);
                    if (rawData.length > number) {
                        label = rawData[number].label === null ?
                            'N/A'
                            : rawData[number].label;
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
                        label = rawData[data].value === null ?
                            'N/A'
                            : rawData[data].value;
                    } else {
                        label = '';
                    }
                }
                return label;
            };

            var makeChart = function(rawData, count, key) {
                var chartData = [];

                for (var i in rawData) {
                    if (!rawData.hasOwnProperty(i)) {
                        continue;
                    }
                    var yValue = dataFormatter.parseValue(rawData[i].value, yFormat);
                    yValue = yValue === null ? parseInt(i) : yValue;
                    var xValue = dataFormatter.parseValue(rawData[i].label, xFormat);
                    xValue = xValue === null ? parseInt(i) : xValue;

                    var item = [xValue, yValue];
                    chartData.push(item);
                }

                return {
                    label: key,
                    data: chartData,
                    color: colors[count % colors.length],
                    markers: {
                        show: false
                    },
                    points: {
                        show: !connectDots
                    }
                };
            };

            _.each(rawData, function(rawData, key) {
                var result = makeChart(rawData, count, key);
                count++;

                charts.push(result);
            });

            Flotr.draw(
                $chart.get(0),
                charts,
                {
                    colors: colors,
                    fontColor: options.settings.chartFontColor,
                    fontSize: options.settings.chartFontSize * (narrowScreen ? 0.7 : 1),
                    lines: {
                        show: connectDots
                    },
                    mouse: {
                        track: true,
                        relative: true,
                        trackFormatter: function(pointData) {
                            return pointData.series.label +
                                ', ' + getXLabel(pointData.x) +
                                ': ' + getYLabel(pointData.y);
                        }
                    },
                    yaxis: {
                        autoscale: true,
                        autoscaleMargin: 1,
                        tickFormatter: function(y) {
                            return getYLabel(y);
                        },
                        title: options.data_schema.value.label + '  '
                    },
                    xaxis: {
                        autoscale: true,
                        autoscaleMargin: 0,
                        tickFormatter: function(x) {
                            return getXLabel(x);
                        },
                        title: narrowScreen ? void 0 : options.data_schema.label.label,
                        mode:    options.xaxis.mode,
                        noTicks: options.xaxis.noTicks,
                        labelsAngle: narrowScreen ? 45 : 0,
                        margin: true
                    },
                    HtmlText: false,
                    grid: {
                        verticalLines: false
                    },
                    legend: {
                        show: true,
                        noColumns: 1,
                        position: 'nw'
                    }
                }
            );
        }
    });

    return MultilineChartComponent;
});
