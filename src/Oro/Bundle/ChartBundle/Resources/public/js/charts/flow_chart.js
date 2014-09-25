define([
    'jquery',
    './abstract_chart',
    'oroui/js/layout',
    'flotr2',
    'orolocale/js/formatter/number',
    'jquery-ui'
], function($, abstractChart, layout, Flotr, numberFormatter) {
    $.widget('orochart.flowChart', $.orochart.abstractChart, {
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
            var formatter = numberFormatter;

            if (!$chart.get(0).clientWidth) {
                return;
            }
            var data = options.data;
            var chartData = {};
            var nozzleSteps = [];
            _.each(data, function(value, key) {
                if (value.value <= 0) {
                    data[key].value = 0.0001;
                }
                chartData[value.label] = value.value;
                if (value.isNozzle) {
                    nozzleSteps.push(value.label);
                }
            });
            Flotr.draw(
                    $chart.get(0),
                    new Array(chartData),
                    {
                        funnel : {
                            show : true,
                            formatter: formatter.formatCurrency,
                            nozzleSteps: nozzleSteps,
                            colors: options.colors,
                            tickFormatter: function (label, value) {
                                return label + ': ' + formatter.formatCurrency(value);
                            },
                            nozzleFormatter: function (label, value) {
                                return label
                                        + options.label
                                        + formatter.formatCurrency(value);
                            }
                        },
                        mouse: {
                            track: true,
                            relative: true
                        },
                        grid: {
                            outlineWidth: 0
                        }
                    }
            );
        }
    });
});