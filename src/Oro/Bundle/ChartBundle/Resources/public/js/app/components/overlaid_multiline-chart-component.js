define(function(require) {
    'use strict';

    const BaseChartComponent = require('orochart/js/app/components/multiline-chart-component');
    const dataFormatter = require('orochart/js/data_formatter');

    /**
     * @class orochart.app.components.OverlaidMultilineChartComponent
     * @extends orochart.app.components.MultilineChartComponent
     * @exports orochart/app/components/overlaid-multiline-chart-component
     */
    const OverlaidMultilineChartComponent = BaseChartComponent.extend({

        /**
         * @inheritdoc
         */
        constructor: function OverlaidMultilineChartComponent(options) {
            OverlaidMultilineChartComponent.__super__.constructor.call(this, options);
        },

        /**
         * Extends chart data with 'originalData' array that contains x-axis values
         * which should be used in point tooltip.
         */
        makeChart: function(rawData, count, key) {
            const colors = this.config.default_settings.chartColors;
            const connectDots = this.options.settings.connect_dots_with_line;

            return {
                label: key,
                data: this.getChartData(rawData),
                originalData: this.getOriginalChartData(rawData),
                color: colors[count % colors.length],
                markers: {
                    show: false
                },
                points: {
                    show: !connectDots
                }
            };
        },

        /**
         * Provides array that contains x-axis values which should be used in point tooltip.
         *
         * @param {Array} rawData
         * @returns {*[]}
         */
        getOriginalChartData: function(rawData) {
            const xFormat = this.options.data_schema.label.type;
            const chartData = [];

            for (const i in rawData) {
                if (!rawData.hasOwnProperty(i)) {
                    continue;
                }
                let xOriginalLabel = dataFormatter.parseValue(rawData[i].originalLabel, xFormat);
                xOriginalLabel = xOriginalLabel === null ? parseInt(i) : xOriginalLabel;

                const item = {value: xOriginalLabel};

                if (rawData[i].startLabel !== undefined) {
                    let xOriginalStartLabel = dataFormatter.parseValue(rawData[i].startLabel, xFormat);
                    xOriginalStartLabel = xOriginalStartLabel === null ? parseInt(i) : xOriginalStartLabel;

                    item.xOriginalStartLabel = xOriginalStartLabel;
                }
                if (rawData[i].endLabel !== undefined) {
                    let xOriginalEndLabel = dataFormatter.parseValue(rawData[i].endLabel, xFormat);
                    xOriginalEndLabel = xOriginalEndLabel === null ? parseInt(i) : xOriginalEndLabel;

                    item.xOriginalEndLabel = xOriginalEndLabel;
                }

                chartData.push(item);
            }

            return chartData;
        },

        /**
         * @overrides
         */
        trackFormatter: function(pointData) {
            if (pointData.series.originalData[pointData.index].xOriginalStartLabel !== undefined &&
                pointData.series.originalData[pointData.index].xOriginalEndLabel !== undefined
            ) {
                return this.XTickFormatter(pointData.series.originalData[pointData.index].xOriginalStartLabel) +
                    ' - ' + this.XTickFormatter(pointData.series.originalData[pointData.index].xOriginalEndLabel) +
                    ' - ' + this.YTickFormatter(pointData.y);
            }

            return this.XTickFormatter(pointData.series.originalData[pointData.index].value) +
               ' - ' + this.YTickFormatter(pointData.y);
        }
    });

    return OverlaidMultilineChartComponent;
});
