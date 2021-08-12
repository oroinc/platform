define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const Flotr = require('flotr2');
    const dataFormatter = require('orochart/js/data_formatter');
    const PieChartComponent = require('orochart/js/app/components/pie-chart-component');
    require('orochart/js/flotr2/funnel');

    let valueFormat = 'currency';

    /**
     * @class orochart.app.components.FlowChartComponent
     * @extends orochart.app.components.PieChartComponent
     * @exports orochart/app/components/flow-chart-component
     */
    const FlowChartComponent = PieChartComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function FlowChartComponent(options) {
            FlowChartComponent.__super__.constructor.call(this, options);
        },

        /**
         * @overrides
         * @param {Object} options
         */
        initialize: function(options) {
            FlowChartComponent.__super__.initialize.call(this, options);
            valueFormat = options.options.data_schema.value.type;

            this.date = options.date;
            this._prepareData();
        },

        /**
         * Prepares proper data structure
         *
         * @protected
         */
        _prepareData: function() {
            const date = this.date;
            _.each(this.data, function(item) {
                const params = {
                    label: item.label,
                    date: date,
                    value: dataFormatter.formatValue(item.value, valueFormat)
                };
                const format = 'oro.chart.flow_chart.label_fromatter.' + (item.isNozzle ? 'nozzle' : 'tick');
                item.originalLabel = item.label;
                item.label = __(format, params);
                item.data = [item.value];
            });
        },

        /**
         * Draw chart
         *
         * @overrides
         */
        draw: function() {
            let labelsWidth;
            const $chart = this.$chart;
            const $legend = this.$legend;
            const options = this.options;
            const hasPlaceForLabels = !this.$container.hasClass('wrapped-chart-legend');

            if (!$chart.get(0).clientWidth) {
                return;
            }

            labelsWidth = 0;
            $legend.html('');

            if (hasPlaceForLabels) {
                labelsWidth = 250;// width for embedded labels
                $chart.width($chart.width() + labelsWidth);
            }

            Flotr.draw(
                $chart.get(0),
                this.data,
                {
                    funnel: {
                        show: true,
                        showLabels: hasPlaceForLabels,
                        formatter: _.partial(dataFormatter.formatValue, _, valueFormat),
                        colors: options.settings.chartColors,
                        marginX: labelsWidth
                    },
                    mouse: {
                        track: true,
                        relative: true
                    },
                    grid: {
                        outlineWidth: 0
                    },
                    legend: {
                        show: !hasPlaceForLabels,
                        container: $legend.get(0),
                        labelBoxWidth: 20,
                        labelBoxHeight: 13,
                        labelBoxMargin: 0
                    }
                }
            );
        }
    });

    return FlowChartComponent;
});
