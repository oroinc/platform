define(function(require) {
    'use strict';

    var FlowChartComponent,
        _ = require('underscore'),
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

            this.update();
        },

        /**
         * Draw chart
         *
         * @overrides
         */
        draw: function () {
            var scope = this;
            var $chart = this.$chart;
            var options = this.options;

            if (!$chart.get(0).clientWidth) {
                return;
            }

            var data = this.data;
            var chartData = {};
            var nozzleSteps = [];

            _.each(data, function(value, key) {
                if (value.value <= 0) {
                    data[key].value = 0.0001;
                }
                chartData[value.label] = value.value;
                if (value.isNozzle) {
                    nozzleSteps.push(value.label);
                }
            });
            Flotr.draw(
                $chart.get(0),
                new Array(chartData),
                {
                    funnel : {
                        show : true,
                        formatter: numberFormatter.formatCurrency,
                        nozzleSteps: nozzleSteps,
                        colors: options.settings.chartColors,
                        tickFormatter: function (label, value) {
                            return label + ': ' + numberFormatter.formatCurrency(value);
                        },
                        nozzleFormatter: function (label, value) {
                            return label +
                                ' (from ' + scope.date + '): ' +
                                numberFormatter.formatCurrency(value);
                        }
                    },
                    mouse: {
                        track: true,
                        relative: true
                    },
                    grid: {
                        outlineWidth: 0
                    }
                }
            );
        }
    });

    return FlowChartComponent;
});
