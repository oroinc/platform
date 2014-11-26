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
        },

        setChartSize: function () {
            var isChanged = false;
            var $container = this.$container;
            var isLegendWrapped = $container.hasClass('wrapped-chart-legend');
            var $chart = this.$chart;
            var $widgetContent = $container.parent();
            var $chartLegend = this.$legend;
            var chartWidth = Math.min(Math.round($widgetContent.width() * Number(this.options.settings.ratio)), 350);

            if (chartWidth > 0 && chartWidth !== $chart.width()) {
                $chart.width(chartWidth);
                $chart.height(chartWidth);
                $chartLegend.height(chartWidth);
                $chart.parent().width(chartWidth + $chartLegend.width());
                isChanged = true;
            }

            if ((isChanged || !isLegendWrapped) && this.$legend.position().top !== 0) {
                // container is not in wrapped mode yet but the legend already has dropped under chart
                $container.width(this.$chart.width());
                $container.addClass('wrapped-chart-legend');
                isChanged = true; // force changed ro redraw chart
            } else if ((isChanged || isLegendWrapped) && $container.outerWidth(true) / $container.width() > 1.7) {
                // container is in wrapped mode but there's already place for legend next to chart
                $container.removeClass('wrapped-chart-legend');
                $container.width('auto');
                isChanged = true; // force changed ro redraw chart
            }

            return isChanged;
        },

        setChartContainerSize: function () {
            // there's nothing to do with container
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
