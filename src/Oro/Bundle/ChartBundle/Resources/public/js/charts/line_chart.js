define([
    'jquery',
    './abstract_chart',
    'oroui/js/layout',
    'flotr2',
    'orolocale/js/formatter/number',
    'jquery-ui'
], function($, abstractChart, layout, Flotr, numberFormatter) {
    $.widget('orochart.lineChart', $.orochart.abstractChart, {
        options: {
            containerId: null,
            data: {},
            colors: [],
            fontColors: [],
            fontSize: 10,
            formatter: null,
            noTicks: null
        },

        _draw: function () {
            var $chart = this.element;
            var options = this.options;
            var xFormat = options.xFormat;
            var yFormat = options.yFormat;
            if (!$chart.get(0).clientWidth) {
                return;
            }

            var rawData = options.rawData;

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

            var connectDots = options.connectDots;
            var colors = options.colors;
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
                        label = rawData[number]['label'] === null ? options.label  : rawData[number]['label'];
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
                        label = rawData[data]['value'] === null ? options.label : rawData[data]['value'];
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
                        colors: colors,
                        fontColor: options.fontColor,
                        fontSize: options.fontSize,
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
                            title: options.title
                        },
                        xaxis: {
                            max: xMax,
                            min: xMin,
                            tickFormatter: function (x) {
                                return getXLabel(x);
                            },
                            title: options.title
                        },
                        HtmlText : false,
                        grid: {
                            verticalLines : false
                        }
                    }
            );
        }
    });
});