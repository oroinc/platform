define([
    'jquery',
    './abstract_chart',
    'oroui/js/layout',
    'flotr2',
    'orolocale/js/formatter/number',
    'jquery-ui'
], function($, abstractChart, layout, Flotr, numberFormatter) {
    $.widget('orochart.barChart', $.orochart.abstractChart, {
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

            if (!$chart.get(0).clientWidth) {
                return;
            }
            var data = options.data;
            var colors = options.colors;
            var chartData = [];
            var xLabels = [];
            var xNumber = 0;
            for(var i in data){
                chartData.push([xNumber++, parseInt(data[i]['value'])]);
                xLabels.push(data[i]['label']);
            }
            var chart = {
                data: chartData,
                color: colors[0],
                markers: {
                    show: true,
                    position: 'ct',
                    labelFormatter: function (data) {
                        if(options.formatter) {
                            return numberFormatter[options.formatter](data.y);
                        } else {
                            return data.y;
                        }
                    }
                }
            };
            Flotr.draw($chart.get(0),
                [chart],
                {
                    colors: colors,
                    fontColor: options.fontColor,
                    fontSize: options.fontSize,
                    bars : {
                        show : true,
                        horizontal : false,
                        shadowSize : 0,
                        barWidth : 0.5
                    },
                    mouse : {
                        track : true,
                        relative : true,
                        trackFormatter: function (data) {
                            var yValue;

                            if(options.formatter) {
                                yValue = numberFormatter[options.formatter](data.y);
                                return numberFormatter[options.formatter](data.y);
                            } else {
                                yValue = data.y;
                            }

                            return xLabels[parseInt(data.x)] + ': ' + yValue;
                        }
                    },
                    yaxis: {
                        min: 0,
                        tickFormatter: function (y) {
                            if(options.formatter) {
                                return numberFormatter.formatCurrency(y);
                            } else {
                                return y;
                            }
                        }
                    },
                    xaxis: {
                        noTicks: options.noTicks,
                        tickFormatter: function (x) {
                            return xLabels[parseInt(x)];
                        }
                    },
                    grid: {
                        verticalLines : false
                    }
                }
            );
        }
    });
});