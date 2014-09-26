define(function(require) {
    var Flotr = require('flotr2');
    var dataFormatter = require('orochart/js/data_formatter');
    var BaseChartComponent = require('orochart/js/app/components/base-chart-component');
    var BarChartComponent;


    /**
     *
     * @class orochart.app.components.BarCharComponent
     * @extends orochart.app.components.BaseCharComponent
     * @exports orochart/app/components/BarCharComponent
     */
    BarChartComponent = BaseChartComponent.extend({
        /**
         *
         * @overrides
         * @param {object} options
         */
        initialize: function(options) {
            BaseChartComponent.prototype.initialize.call(this, options);

            this.update();
        },

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
            if (!$chart.get(0).clientWidth) {
                return;
            }

            var rawData = this.data;

            if (dataFormatter.isValueNumerical(xFormat)) {
                var sort = function (rawData) {
                    rawData.sort(function (first, second) {
                        if (first.label == null) {
                            return -1;
                        }
                        if (second.label == null) {
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
                        label = rawData[number]['label'] === null
                            ? 'N/A'
                            : rawData[number]['label'];
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
                        label = rawData[data]['value'] === null
                            ? 'N/A'
                            : rawData[data]['value'];
                    } else {
                        label = '';
                    }
                }
                return label;
            };

            var makeChart = function (rawData, count, key) {
                var chartData = [];

                for (var i in rawData) {
                    var yValue = dataFormatter.parseValue(rawData[i]['value'], yFormat);
                    yValue = yValue === null ? parseInt(i) : yValue;
                    var xValue = dataFormatter.parseValue(rawData[i]['label'], xFormat);
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

            _.each(rawData, function (rawData, key) {
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
                    fontSize: options.settings.chartFontSize,
                    lines : {
                        show : connectDots
                    },
                    mouse : {
                        track : true,
                        relative : true,
                        trackFormatter: function (pointData) {
                            return pointData.series.label
                                + ', ' + getXLabel(pointData.x)
                                + ': ' + getYLabel(pointData.y);
                        }
                    },
                    yaxis: {
                        autoscale: true,
                        autoscaleMargin: 1,
                        tickFormatter: function (y) {
                            return getYLabel(y);
                        },
                        title: options.data_schema.value.label
                    },
                    xaxis: {
                        autoscale: true,
                        autoscaleMargin: 0,
                        tickFormatter: function (x) {
                            return getXLabel(x);
                        },
                        title: options.data_schema.label.label
                    },
                    HtmlText : false,
                    grid: {
                        verticalLines : false
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

    return BarChartComponent;
});
