define(function(require) {
    'use strict';

    var StackedBarChartComponent;
    var _ = require('underscore');
    var Flotr = require('flotr2');
    var dataFormatter = require('orochart/js/data_formatter');
    var BaseChartComponent = require('orochart/js/app/components/base-chart-component');

    /**
     * @class orochart.app.components.StackedBarChartComponent
     * @extends orochart.app.components.BaseChartComponent
     * @exports orochart/app/components/stackedbar-char-component
     */
    StackedBarChartComponent = BaseChartComponent.extend({
        aspectRatio: 0.6,
        initialDatasetSize: 30,

        /**
         * Draw chart
         *
         * @overrides
         */
        draw: function() {
            var container = this.$chart.get(0);
            var series = this.getSeries();

            // speculatively use first series to set xaxis size
            var sample = series[0];
            var xSize = {
                min: sample.data[sample.data.length - this.initialDatasetSize][0],
                max: sample.data[sample.data.length - 1][0]
            };

            var graph = this.drawGraph(container, series, xSize);
            this.setupPanning(graph, container, series);
        },

        setupPanning: function(initialGraph, container, series) {
            var graph = initialGraph;
            var drawGraph = _.bind(this.drawGraph, this);
            var start;

            Flotr.EventAdapter.observe(container, 'flotr:mousedown',  function(e) {
                start = graph.getEventPosition(e);
                Flotr.EventAdapter.observe(container, 'flotr:mousemove', onMove);
                Flotr.EventAdapter.observe(container, 'flotr:mouseup', onStop);
            });

            function onStop () {
                Flotr.EventAdapter.stopObserving(container, 'flotr:mousemove', onMove);
            }

            function onMove(e, o) {
                var xaxis = graph.axes.x;
                var offset = start.x - o.x;

                // Redrawl the graph with new axis
                graph = drawGraph(
                    container,
                    series,
                    {
                        min: xaxis.min + offset,
                        max: xaxis.max + offset
                    }
                );
            }
        },

        drawGraph: function(container, series, xSize) {
            var options = this.options;
            var settings = this.options.settings;
            var chartOptions = {
                colors: settings.chartColors,
                fontColor: settings.chartFontColor,
                fontSize: settings.chartFontSize,
                HtmlText: false,
                bars: {
                    show: true,
                    stacked: true,
                    horizontal: false,
                    shadowSize: 0,
                    fillOpacity: 1,
                    lineWidth: 7.5,
                    centered: true,
                },
                mouse: {
                    track: true,
                    relative: false,
                    position: 'ne',
                    trackFormatter: _.bind(this.trackFormatter, this),
                },
                yaxis: {
                    autoscale: true,
                    autoscaleMargin: 0.3,
                    noTicks: 2,
                    tickFormatter: _.bind(this.YTickFormatter, this),
                    title: options.data_schema.value.label,
                },
                xaxis: {
                    min: xSize.min,
                    max: xSize.max,
                    tickFormatter: _.bind(this.XTickFormatter, this),
                    title: options.data_schema.label.label,
                },
                grid: {
                    verticalLines: false,
                },
                legend: {
                    show: true,
                    noColumns: 1,
                    position: 'nw',
                }
            };

            return Flotr.draw(container, series, chartOptions);
        },

        YTickFormatter: function(value) {
            var yFormat = this.options.data_schema.value.type;

            return dataFormatter.formatValue(value, yFormat);
        },

        XTickFormatter: function(value) {
            var xFormat = this.options.data_schema.label.type;

            return dataFormatter.formatValue(value, xFormat);
        },

        trackFormatter: function(pointData) {
            return pointData.series.label +
                ', ' + this.XTickFormatter(pointData.x) +
                ': ' + this.YTickFormatter(pointData.y);
        },

        getSeries: function() {
            var yFormat = this.options.data_schema.value.type;
            var xFormat = this.options.data_schema.label.type;
            var seriesData = [];

            _.each(this.data, function(categoryDataSet, category) {
                var serieData = [];
                _.each(categoryDataSet, function(categoryData, i) {
                    var yValue = dataFormatter.parseValue(categoryData.value, yFormat);
                    yValue = yValue === null ? parseInt(i) : yValue;
                    var xValue = dataFormatter.parseValue(categoryData.label, xFormat);
                    xValue = xValue === null ? parseInt(i) : xValue;

                    serieData.push([xValue, yValue]);
                });

                seriesData.push({data: serieData, label: category});
            });

            return seriesData;
        }
    });

    return StackedBarChartComponent;
});
