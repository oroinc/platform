define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const Flotr = require('flotr2');
    const dataFormatter = require('orochart/js/data_formatter');
    const BaseChartComponent = require('orochart/js/app/components/base-chart-component');

    /**
     * @class orochart.app.components.MultilineChartComponent
     * @extends orochart.app.components.BaseChartComponent
     * @exports orochart/app/components/multiline-chart-component
     */
    const MultilineChartComponent = BaseChartComponent.extend({

        narrowScreen: false,

        /**
         * @inheritdoc
         */
        constructor: function MultilineChartComponent(options) {
            MultilineChartComponent.__super__.constructor.call(this, options);
        },

        /**
         * Draw chart
         *
         * @overrides
         */
        draw: function() {
            const options = this.options;
            const $chart = this.$chart;
            const xFormat = options.data_schema.label.type;
            const yFormat = options.data_schema.value.type;
            const rawData = this.data;

            if (!$chart.get(0).clientWidth) {
                return;
            }

            if (dataFormatter.isValueNumerical(xFormat)) {
                const sort = function(rawData) {
                    rawData.sort(function(first, second) {
                        if (first.label === null || first.label === undefined) {
                            return -1;
                        }
                        if (second.label === null || second.label === undefined) {
                            return 1;
                        }
                        const firstLabel = dataFormatter.parseValue(first.label, xFormat);
                        const secondLabel = dataFormatter.parseValue(second.label, xFormat);
                        return firstLabel - secondLabel;
                    });
                };

                _.each(rawData, sort);
            }

            const connectDots = options.settings.connect_dots_with_line;
            const colors = this.config.default_settings.chartColors;

            let count = 0;
            const charts = [];

            const getXLabel = function(data) {
                let label = dataFormatter.formatValue(data, xFormat);
                if (label === null) {
                    const number = parseInt(data);
                    if (rawData.length > number) {
                        label = rawData[number].label === null
                            ? 'N/A'
                            : rawData[number].label;
                    } else {
                        label = '';
                    }
                }
                return label;
            };
            const getYLabel = function(data) {
                let label = dataFormatter.formatValue(data, yFormat);
                if (label === null) {
                    const number = parseInt(data);
                    if (rawData.length > number) {
                        label = rawData[data].value === null
                            ? 'N/A'
                            : rawData[data].value;
                    } else {
                        label = '';
                    }
                }
                return label;
            };

            const makeChart = function(rawData, count, key) {
                const chartData = [];

                for (const i in rawData) {
                    if (!rawData.hasOwnProperty(i)) {
                        continue;
                    }
                    let yValue = dataFormatter.parseValue(rawData[i].value, yFormat);
                    yValue = yValue === null ? parseInt(i) : yValue;
                    let xValue = dataFormatter.parseValue(rawData[i].label, xFormat);
                    xValue = xValue === null ? parseInt(i) : xValue;

                    const item = [xValue, yValue];
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

            _.each(rawData, function(rawData, key) {
                const result = makeChart(rawData, count, key);
                count++;

                charts.push(result);
            });

            Flotr.draw(
                $chart.get(0),
                charts,
                {
                    colors: colors,
                    title: ' ',
                    fontColor: options.settings.chartFontColor,
                    fontSize: options.settings.chartFontSize * (this.narrowScreen ? 0.8 : 1),
                    lines: {
                        show: connectDots
                    },
                    mouse: {
                        track: true,
                        relative: true,
                        trackFormatter: function(pointData) {
                            return pointData.series.label +
                                ', ' + getXLabel(pointData.x) +
                                ': ' + getYLabel(pointData.y);
                        }
                    },
                    yaxis: {
                        autoscale: true,
                        autoscaleMargin: 1,
                        tickFormatter: function(y) {
                            return getYLabel(y);
                        },
                        title: options.data_schema.value.label + '  '
                    },
                    xaxis: {
                        autoscale: true,
                        autoscaleMargin: 0,
                        tickFormatter: function(x) {
                            return getXLabel(x);
                        },
                        title: this.narrowScreen ? ' ' : options.data_schema.label.label,
                        mode: options.xaxis.mode,
                        noTicks: options.xaxis.noTicks,
                        labelsAngle: this.narrowScreen ? 90 : 0,
                        margin: true
                    },
                    HtmlText: false,
                    grid: {
                        verticalLines: false
                    },
                    legend: {
                        show: true,
                        noColumns: 1,
                        position: 'nw'
                    }
                }
            );
        },

        update: function() {
            this.narrowScreen = $('html').width() < 520;
            if (this.narrowScreen) {
                this.aspectRatio = 0.55;
            } else {
                this.aspectRatio = MultilineChartComponent.__super__.aspectRatio;
            }
            MultilineChartComponent.__super__.update.call(this);
        }
    });

    return MultilineChartComponent;
});
