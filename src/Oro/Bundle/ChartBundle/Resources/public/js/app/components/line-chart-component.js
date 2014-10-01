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
            var $chart = this.$chart;
            var options = this.options;
            var xFormat = options.data_schema.label.type;
            var yFormat = options.data_schema.value.type;
            if (!$chart.get(0).clientWidth) {
                return;
            }

            var rawData = this.data;

            if (dataFormatter.isValueNumerical(xFormat)) {
                rawData.sort(function(first, second){
                    if(first.label == null){
                        return -1;
                    }
                    if(second.label == null){
                        return 1;
                    }
                    var firstLabel = dataFormatter.parseValue(first.label, xFormat);
                    var secondLabel = dataFormatter.parseValue(second.label, xFormat);
                    return firstLabel - secondLabel;
                });
            }

            var connectDots = options.settings.connect_dots_with_line;
            var colors = this.config.default_settings.chartColors;
            var chartData = [];
            var yMax = null;
            var yMin = null;
            var xMax = null;
            var xMin = null;
            var getXLabel = function(data) {
                var label = dataFormatter.formatValue(data, xFormat);
                if (label === null) {
                    var number = parseInt(data);
                    if (rawData.length > number) {
                        label = rawData[number]['label'] === null ? 'N/A' : rawData[number]['label'];
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
                        label = rawData[data]['value'] === null ? 'N/A' : rawData[data]['value'];
                    } else {
                        label = '';
                    }
                }
                return label;
            };
            for (var i in rawData) {
                var yValue = dataFormatter.parseValue(rawData[i]['value'], yFormat);
                yValue = yValue === null ? parseInt(i) : yValue;
                var xValue = dataFormatter.parseValue(rawData[i]['label'], xFormat);
                xValue = xValue === null ? parseInt(i) : xValue;
                if (xMax === null) {
                    xMax = xValue;
                    yMax = yValue;
                    yMin = yValue;
                    xMin = xValue;
                }
                xMax = xMax < xValue ? xValue : xMax;
                xMin = xMin > xValue ? xValue : xMin;
                yMax = yMax < yValue ? yValue : yMax;
                yMin = yMin > yValue ? yValue : yMin;

                var item = [xValue, yValue];
                chartData.push(item);
            }
            var deltaX = xMax - xMin;
            var deltaY = yMax - yMin;
            var xStep = (deltaX > 0 ? deltaX / rawData.length : 1);
            var yStep = (deltaY > 0 ? deltaY / rawData.length : 1);
            xMax += xStep;
            yMax += yStep;
            xMin -= xStep;
            yMin -= yStep;

            var chart = {
                data: chartData,
                color: colors[0],
                markers: {
                    show: true,
                    position: 'ct',
                    labelFormatter: function (pointData) {
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
                        lines : {
                            show : connectDots
                        },
                        mouse : {
                            track : true,
                            relative : true,
                            trackFormatter: function (pointData) {
                                return   getXLabel(pointData.x) + ': ' + getYLabel(pointData.y);
                            }
                        },
                        yaxis: {
                            max: yMax,
                            min: yMin,
                            tickFormatter: function (y) {
                                return getYLabel(y);
                            },
                            title: options.data_schema.value.label
                        },
                        xaxis: {
                            max: xMax,
                            min: xMin,
                            tickFormatter: function (x) {
                                return getXLabel(x);
                            },
                            title: options.data_schema.label.label
                        },
                        HtmlText : false,
                        grid: {
                            verticalLines : false
                        }
                    }
            );
        }
    });

    return BarChartComponent;
});
