/*global define*/
define(['underscore', 'jquery'],
    function (_, $) {
        'use strict';

        /**
         * @export  orodashboard/js/chart_form
         * @class   orodashboard.ChartForm
         */
        return {

            /**
             * @property {Object}
             */
            options: {
                typeSelector: '#oro_chart #oro_chart_type',
                parentSelector: '#oro_chart > .control-group',
                selectorPrefix: '#oro_chart_'
            },

            /**
             * @param {Object} selector
             * @param {Object} options
             */
            initialize: function (selector, options) {
                this.options = _.extend({}, this.options, options);

                this.updateChartFormVisibility();

                this.addHandler(selector)
            },

            /**
             * @param {Object} selector
             */
            addHandler: function (selector) {
                var self = this;
                $(selector).on('change', _.bind(function () {
                    self.updateChartFormVisibility();
                }, this));
            },

            updateChartFormVisibility: function () {
                $(this.options.parentSelector).not(':first').hide();

                var name = $(this.options.typeSelector).val();
                $(this.options.parentSelector)
                    .has(this.options.selectorPrefix + name)
                    .show();
            }
        };
    });
