import Flotr from 'flotr2';
import dataFormatter from 'orochart/js/data_formatter';
import BarChartComponent from 'orochart/default/js/app/components/base-chart-component';
import hintTemplate from 'tpl-loader!orochart/default/templates/flotr-hint.html';
import logger from 'oroui/js/tools/logger';

const PurchaseVolumeChartComponent = BarChartComponent.extend({
    chartContainerClass: 'purchase-volume-chart',

    /**
     * @inheritdoc
     */
    constructor: function PurchaseVolumeChartComponent(options) {
        PurchaseVolumeChartComponent.__super__.constructor.call(this, options);
    },

    /**
     * Draw chart
     *
     * @overrides
     */
    draw() {
        let intValue;
        let maxValue = 0;
        const chart = this.$chart.get(0);
        const data = this.data;
        const options = this.options;
        const xFormat = options.data_schema.label.type;
        const yFormat = options.data_schema.value.type;

        let xNumber = 0;
        const chartData = [];
        const xLabels = [];
        const xTicks = [];

        for (const i in data) {
            if (!data.hasOwnProperty(i)) {
                continue;
            }
            intValue = parseInt(data[i].value);
            maxValue = Math.max(intValue, maxValue);
            chartData.push([xNumber++, intValue]);
            xLabels.push(data[i].label);

            xTicks.push([
                i,
                dataFormatter.formatValue(
                    dataFormatter.parseValue(data[i].label, xFormat),
                    xFormat
                )
            ]);
        }

        const chartOptions = {
            data: chartData
        };

        let colors = ['#0A7F8F', '#f7941d', '#6e98dc'];

        try {
            colors = this.getChartCSSValue('--chart-colors')
                .replace(/,/g, '').split(' ');
        } catch (e) {
            logger.warn('Invalid SCSS list is set for "--chart-colors" variable');
        }

        Flotr.draw(chart,
            [chartOptions],
            {
                color: this.getChartCSSValue('--chart-color') || '#0A7F8F',
                colors: colors,
                fontColor: this.getChartCSSValue('--chart-font-color') || '#002434',
                fontSize: parseInt(this.getChartCSSValue('--chart-font-size')) || 14,
                bars: {
                    show: true,
                    horizontal: false,
                    centered: true,
                    shadowSize: 0,
                    barWidth: parseFloat(this.getChartCSSValue('--chart-bar-width')) || 0.76,
                    fillOpacity: parseInt(this.getChartCSSValue('--chart-bar-opacity')) || 1,
                    topPadding: 0
                },
                mouse: {
                    track: true,
                    relative: true,
                    lineColor: this.getChartCSSValue('--chart-tooltip-line-color') || '#075963',
                    fillColor: this.getChartCSSValue('--chart-tooltip-fill-color') || '#075963',
                    fillOpacity: parseInt(this.getChartCSSValue('--chart-tooltip-opacity')) || 1,
                    position: 'cc',
                    margin: parseInt(this.getChartCSSValue('--chart-tooltip-margin')) || -84,
                    radius: parseInt(this.getChartCSSValue('--chart-tooltip-radius')) || 4,
                    trackFormatter(data) {
                        let yValue = data.y;

                        if (yFormat) {
                            yValue = dataFormatter.formatValue(data.y, yFormat);
                        }

                        return hintTemplate({
                            date: dataFormatter.formatValue(
                                dataFormatter.parseValue(xLabels[data.index], xFormat),
                                'month_long'
                            ),
                            volume: yValue
                        });
                    }
                },
                yaxis: {
                    color: this.getChartCSSValue('--chart-yaxis-color') || '#002434',
                    min: 0,
                    // makes visible label above the highest bar
                    max: maxValue * 1.1,
                    tickFormatter(y) {
                        if (parseInt(y) === 0) {
                            return dataFormatter.formatValue(y, 'integer');
                        } else if (yFormat) {
                            return dataFormatter.formatValue(y, yFormat);
                        }

                        return y;
                    }
                },
                xaxis: {
                    color: this.getChartCSSValue('--chart-xaxis-color') || '#002434',
                    noTicks: chartData.length,
                    ticks: xTicks
                },
                grid: {
                    outlineWidth: 1,
                    outline: 's',
                    verticalLines: false,
                    labelMargin: parseInt(this.getChartCSSValue('--chart-grid-label-margin')) || 10,
                    tickColor: this.getChartCSSValue('--chart-grid-tick-color') || '#F0F3F5',
                    color: this.getChartCSSValue('--chart-grid-text-color') || '#C3CFCF'
                }
            }
        );
    }
});


export default PurchaseVolumeChartComponent;
