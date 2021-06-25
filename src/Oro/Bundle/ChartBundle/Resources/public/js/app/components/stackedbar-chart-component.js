define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Flotr = require('flotr2');
    const dataFormatter = require('orochart/js/data_formatter');
    const BaseChartComponent = require('orochart/js/app/components/base-chart-component');

    /**
     * @class orochart.app.components.StackedBarChartComponent
     * @extends orochart.app.components.BaseChartComponent
     * @exports orochart/app/components/stackedbar-char-component
     */
    const StackedBarChartComponent = BaseChartComponent.extend({
        aspectRatio: 0.6,

        initialDatasetSize: 30,

        /**
         * @inheritdoc
         */
        constructor: function StackedBarChartComponent(options) {
            StackedBarChartComponent.__super__.constructor.call(this, options);
        },

        /**
         * Draw chart
         *
         * @overrides
         */
        draw: function() {
            const container = this.$chart.get(0);
            const series = this.getSeries();

            // speculatively use first series to set xaxis size
            const sample = series[0];
            const xSize = {
                min: sample.data[sample.data.length - this.initialDatasetSize][0],
                max: sample.data[sample.data.length - 1][0]
            };

            const graph = this.drawGraph(container, series, xSize);
            this.setupPanning(graph, container, series);
        },

        setupPanning: function(initialGraph, container, series) {
            let graph = initialGraph;
            const drawGraph = this.drawGraph.bind(this);
            let start;

            Flotr.EventAdapter.observe(container, 'flotr:mousedown', function(e) {
                start = graph.getEventPosition(e);
                Flotr.EventAdapter.observe(container, 'flotr:mousemove', onMove);
                Flotr.EventAdapter.observe(container, 'flotr:mouseup', onStop);
            });

            function onStop() {
                Flotr.EventAdapter.stopObserving(container, 'flotr:mousemove', onMove);
            }

            function onMove(e, o) {
                const xaxis = graph.axes.x;
                const offset = start.x - o.x;

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
            const options = this.options;
            const settings = this.options.settings;
            const chartOptions = {
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
                    centered: true
                },
                mouse: {
                    track: true,
                    relative: false,
                    position: 'ne',
                    trackFormatter: this.trackFormatter.bind(this)
                },
                yaxis: {
                    autoscale: true,
                    autoscaleMargin: 0.3,
                    noTicks: 2,
                    tickFormatter: this.YTickFormatter.bind(this),
                    title: options.data_schema.value.label
                },
                xaxis: {
                    min: xSize.min,
                    max: xSize.max,
                    tickFormatter: this.XTickFormatter.bind(this),
                    title: options.data_schema.label.label
                },
                grid: {
                    verticalLines: false
                },
                legend: {
                    show: true,
                    noColumns: 1,
                    position: 'nw'
                }
            };

            return Flotr.draw(container, series, chartOptions);
        },

        YTickFormatter: function(value) {
            const yFormat = this.options.data_schema.value.type;

            return dataFormatter.formatValue(value, yFormat);
        },

        XTickFormatter: function(value) {
            const xFormat = this.options.data_schema.label.type;

            return dataFormatter.formatValue(value, xFormat);
        },

        trackFormatter: function(pointData) {
            return pointData.series.label +
                ', ' + this.XTickFormatter(pointData.x) +
                ': ' + this.YTickFormatter(pointData.y);
        },

        getSeries: function() {
            const yFormat = this.options.data_schema.value.type;
            const xFormat = this.options.data_schema.label.type;
            const seriesData = [];

            _.each(this.data, function(categoryDataSet, category) {
                const serieData = [];
                _.each(categoryDataSet, function(categoryData, i) {
                    let yValue = dataFormatter.parseValue(categoryData.value, yFormat);
                    yValue = yValue === null ? parseInt(i) : yValue;
                    let xValue = dataFormatter.parseValue(categoryData.label, xFormat);
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
