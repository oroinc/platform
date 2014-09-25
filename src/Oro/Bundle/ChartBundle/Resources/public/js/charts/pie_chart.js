define([
    'jquery',
    './abstract_chart',
    'oroui/js/layout',
    'flotr2',
    'orolocale/js/formatter/number',
    'jquery-ui'
], function($, abstractChart, layout, Flotr, numberFormatter) {
    $.widget('orochart.pieChart', $.orochart.abstractChart, {
        options: {
            containerId: null,
            rawData: {},
            colors: [],
            fontColors: [],
            lineColor: "#444",
            chartFontColor: '#000',
            fontSize: 10,
            formatter: null,
            noTicks: null
        },

        _setChartSize: function () {
            var $chart = this.element;
            var $widgetContent = $chart.parents('.chart-container').parent();
            var $chartLegend = $('#' + this.options.containerId + '-legend');
            var chartWidth = Math.min(Math.round($widgetContent.width() * this.options.ratio), 350);
            if (chartWidth != $chart.width()) {
                $chart.width(chartWidth);
                $chart.height(chartWidth);
                $chartLegend.height(chartWidth);
                return true;
            }
            return false;
        },

        _setChartContainerSize: function () {
            var $chart = this.element;
            var $chartLegend = $('#' + this.options.containerId + '-legend');
            var $chartLegendTable = $chartLegend.find('table');
            var $td = $chartLegendTable.find('td');
            var padding = parseInt($td.css('padding-bottom'));
            if (padding > 0 && ($chartLegendTable.height() + 20) > $chartLegend.height()) {
                while (($chartLegendTable.height() + 20) > $chartLegend.height()) {
                    padding = padding - 1;
                    $td.css('padding-bottom', padding + 'px');
                    if (padding <= 0) {
                        break;
                    }
                }
            } else if (padding < 7 && ($chartLegendTable.height() + 20) < $chartLegend.height()) {
                while (($chartLegendTable.height() + 20) < $chartLegend.height()) {
                    padding = padding + 1;
                    $td.css('padding-bottom', padding + 'px');
                    if (padding >= 7) {
                        break;
                    }
                }
            }
            $chart.closest('.clearfix').width(
                $chart.width() +
                    $chartLegendTable.outerWidth() +
                    parseInt($chartLegendTable.css('margin-left'))
            );
        },

        _draw: function () {
            var $chart = this.element;
            var options = this.options;

            if (!$chart.get(0).clientWidth) {
                return;
            }
            var $chartLegend = $('#' + this.options.containerId + '-legend');
            var data = [];
            var rawData = options.rawData;
            var xNumber = 0;
            for(var i in rawData){
                data.push({data: [[0, rawData[i]['fraction']]], label: rawData[i]['label']});
            }
            Flotr.draw(
                    $chart.get(0),
                    data,
                    {
                        colors: options.colors,
                        fontColor: options.fontColor,
                        fontSize: options.fontSize,
                        shadowSize: 0,
                        HtmlText: true,
                        xaxis : {
                            showLabels : false
                        },
                        yaxis : {
                            showLabels : false
                        },
                        grid : {
                            color: options.chartFontColor,
                            verticalLines : false,
                            horizontalLines : false,
                            outlineWidth: 0
                        },
                        pie : {
                            show : true,
                            explode : 0,
                            sizeRatio: 0.8,
                            startAngle: Math.PI/3.5
                        },
                        mouse : {
                            track : true,
                            relative: true,
                            lineColor: options.lineColor,
                            trackFormatter: function (obj) {
                                return obj.series.label +
                                    '&nbsp;&nbsp;&nbsp;' + parseFloat(obj.fraction * 100).toFixed(2) + ' %';
                            }
                        },
                        legend : {
                            position : 'ne',
                            container: $chartLegend.get(0),
                            labelBoxWidth: 20,
                            labelBoxHeight: 13,
                            labelBoxMargin: 0
                        }
                    }
            );
        }
    });
});