/*global define*/
define(['underscore', 'jquery'],
    function (_, $) {
        'use strict';

        /**
         * @export  orochart/js/chart_form
         * @class   orochart.ChartForm
         */
        return {

            /**
             * @property {Object}
             */
            options: {
                blocks: ['settings', 'data_schema'],
                parent: null,
                selectors: {
                    type: null
                },
                templates: {
                    type: '<%= parent %>_type',
                    parent: '<%= parent %>_<%= block %> > div',
                    target: '<%= parent %>_<%= block %>_<%= chart %>'
                }
            },

            /**
             * @param {Object} selector
             * @param {Object} options
             */
            initialize: function (selector, options) {
                var self = this;
                self.options = _.extend({}, self.options, {parent: selector}, options);

                self.options.selectors.type = _.template(
                    self.options.templates.type,
                    {parent: self.options.parent}
                );

                self.updateChartFormVisibility();
                self.addHandler();
            },

            addHandler: function () {
                var self = this;

                $(self.options.selectors.type).on('change', _.bind(function () {
                    self.updateChartFormVisibility();
                }, self));
            },

            updateChartFormVisibility: function () {
                var self = this;

                var name = $(self.options.selectors.type).val();

                _.each(self.options.blocks, function (block) {
                    var parentSelector = _.template(
                        self.options.templates.parent,
                        {parent: self.options.parent, block: block}
                    );

                    $(parentSelector).hide();

                    var targetSelector = _.template(
                        self.options.templates.target,
                        {parent: self.options.parent, block: block, chart: name}
                    );

                    $(targetSelector).show();
                });
            }
        };
    });
