define(function(require) {
    'use strict';

    var FlowChartComponent,
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        Flotr = require('flotr2'),
        numberFormatter = require('orolocale/js/formatter/number'),
        BaseChartComponent = require('orochart/js/app/components/base-chart-component');
    require('orochart/js/flotr2/funnel');

    /**
     * @class orochart.app.components.FlowChartComponent
     * @extends orochart.app.components.BaseChartComponent
     * @exports orochart/app/components/flow-chart-component
     */
    FlowChartComponent = BaseChartComponent.extend({
        /**
         *
         * @overrides
         * @param {Object} options
         */
        initialize: function(options) {
            FlowChartComponent.__super__.initialize.call(this, options);

            this.date = options.date;
            this._prepareData();

            this.update();
        },

        /**
         * Prepares proper data structure
         *
         * @protected
         */
        _prepareData: function () {
            var date = this.date;
            _.each(this.data, function(item) {
                var params, format;
                params = {
                    label: item.label,
                    date: date,
                    value: numberFormatter.formatCurrency(item.value)
                };
                format = 'oro.chart.flow_chart.label_fromatter.' + (item.isNozzle ? 'nozzle' : 'tick');
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
        draw: function () {
            var $chart = this.$chart;
            var options = this.options;

            if (!$chart.get(0).clientWidth) {
                return;
            }

            Flotr.draw(
                $chart.get(0),
                this.data,
                {
                    funnel: {
                        show: true,
                        showLabels: true,
                        formatter: numberFormatter.formatCurrency,
                        colors: options.settings.chartColors
                    },
                    mouse: {
                        track: true,
                        relative: true
                    },
                    grid: {
                        outlineWidth: 0
                    },
                    legend : {
                        show: false
                    }
                }
            );
        }
    });

    return FlowChartComponent;
});
