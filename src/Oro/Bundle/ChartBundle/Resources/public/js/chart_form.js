/*global define*/
define(['underscore', 'jquery'],
    function (_, $) {
        'use strict';

        /**
         * @export orochart/js/chart_form
         * @class  orochart.ChartForm
         */
        return {

            /**
             * @property {Object}
             */
            options: {
                blocks: ['settings', 'data_schema'],
                parent: null,
                selectors: {
                    name: null
                },
                templates: {
                    name: '<%= parent %>_name',
                    parent: '<%= parent %>_<%= block %> > div',
                    target: '<%= parent %>_<%= block %>_<%= chart %>'
                }
            },

            /**
             * @param {String} selector
             * @param {Array} options
             */
            initialize: function (selector, options) {
                var self = this;
                self.options = _.extend({}, self.options, {parent: selector}, options);

                self.options.selectors.name = _.template(self.options.templates.name)(
                    {parent: self.options.parent});

                self.updateChartFormVisibility();
                self.addHandler();
            },

            /**
             * @returns void
             */
            addHandler: function () {
                var self = this;

                $(self.options.selectors.name).on('change', _.bind(function () {
                    self.updateChartFormVisibility();
                }, self));
            },

            /**
             * @returns void
             */
            updateChartFormVisibility: function () {
                var self = this;

                var name = $(self.options.selectors.name).val();

                _.each(self.options.blocks, function (block) {
                    var parentSelector = _.template(self.options.templates.parent)(
                        {parent: self.options.parent, block: block});

                    $(parentSelector).hide();

                    var targetSelector = _.template(self.options.templates.target)(
                        {parent: self.options.parent, block: block, chart: name});

                    $(targetSelector).show();
                });
            }
        };
    });
