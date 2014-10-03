define(function(require) {
    var Flotr = require('flotr2');
    var numberFormatter = require('orolocale/js/formatter/number');
    var BaseChartComponent = require('orochart/js/app/components/base-chart-component');
    var BarChartComponent;

    require('orochart/js/flotr2/funnel');

    /**
     *
     * @class orochart.app.components.BarCharComponent
     * @extends orochart.app.components.BaseCharComponent
     * @exports orochart/app/components/BarCharComponent
     */
    BarChartComponent = BaseChartComponent.extend({
        /**
         *
         * @overrides
         * @param {object} options
         */
        initialize: function(options) {
            BaseChartComponent.prototype.initialize.call(this, options);

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
                            return label
                                    + ' (from ' + scope.date + '): '
                                    + numberFormatter.formatCurrency(value);
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

    return BarChartComponent;
});
