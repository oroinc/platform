define(function(require) {
    'use strict';

    var PieChartComponent,
        Flotr = require('flotr2'),
        BaseChartComponent = require('orochart/js/app/components/base-chart-component');

    /**
     * @class orochart.app.components.PieChartComponent
     * @extends orochart.app.components.BaseChartComponent
     * @exports orochart/app/components/pie-chart-component
     */
    PieChartComponent = BaseChartComponent.extend({
        /**
         *
         * @overrides
         * @param {Object} options
         */
        initialize: function(options) {
            PieChartComponent.__super__.initialize.call(this, options);

            this.options.settings.ratio = options.ratio;

            this.update();
        },

        setChartSize: function() {
            var $chart = this.$chart;
            var $widgetContent = $chart.parents('.chart-container').parent();
            var $chartLegend = this.$legend;
            var chartWidth = Math.min(Math.round($widgetContent.width() * Number(this.options.settings.ratio)), 350);

            if (chartWidth > 0 && chartWidth !== $chart.width()) {
                $chart.width(chartWidth);
                $chart.height(chartWidth);
                $chartLegend.height(chartWidth);
                return true;
            }
            return false;
        },

        setChartContainerSize: function() {
            var $chart = this.$chart;
            var $chartLegend = this.$legend;
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

        /**
         * Draw chart
         *
         * @overrides
         */
        draw: function() {
            var $chart = this.$chart;
            var $legend = this.$legend;
            var data = this.data;
            var settings = this.options.settings;
            var chartData = [];

            for(var i in data){
                chartData.push({data: [[0, data[i]['fraction']]], label: data[i]['label']});
            }

            Flotr.draw(
                $chart.get(0),
                chartData,
                {
                    colors: settings.chartColors,
                    fontColor: settings.chartFontColor,
                    fontSize: settings.chartFontSize,
                    shadowSize: 0,
                    HtmlText: true,
                    xaxis : {
                        showLabels : false
                    },
                    yaxis : {
                        showLabels : false
                    },
                    grid : {
                        color: settings.chartFontColor,
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
                        lineColor: settings.chartHighlightColor,
                        trackFormatter: function (obj) {
                            return obj.series.label +
                                '&nbsp;&nbsp;&nbsp;' + parseFloat(obj.fraction * 100).toFixed(2) + ' %';
                        }
                    },
                    legend : {
                        position : 'ne',
                        container: $legend.get(0),
                        labelBoxWidth: 20,
                        labelBoxHeight: 13,
                        labelBoxMargin: 0
                    }
                }
            );
        }
    });

    return PieChartComponent;
});
