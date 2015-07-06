define(['underscore', 'jquery'],
    function(_, $) {
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
                    name: _.template('[data-ftid=\'<%= baseName %>_name\']'),
                    parent: _.template('[data-ftid=\'<%= baseName %>_<%= block %>\'] > div'),
                    target: _.template('[data-ftid=\'<%= baseName %>_<%= block %>_<%= chart %>\']')
                }
            },

            /**
             * @param {String} selector
             * @param {Array} options
             */
            initialize: function(selector, options) {
                var $container = $(selector);
                options = $.extend(true, {}, this.options, options, {
                    baseName: $container.data('ftid'),
                    $container: $container
                });

                this.updateChartFormVisibility(options);
                this.getNameChoiceElement(options).on('change', _.bind(this.updateChartFormVisibility, this, options));
            },

            getNameChoiceElement: function(options) {
                var selector = options.templates.name({
                    baseName: options.baseName
                });
                return options.$container.find(selector);
            },

            getParentElement: function(options, block) {
                var selector = options.templates.parent({
                    baseName: options.baseName,
                    block: block
                });
                return options.$container.find(selector);
            },

            getTargetElement: function(options, block, chart) {
                var selector = options.templates.target({
                    baseName: options.baseName,
                    block: block,
                    chart: chart
                });
                return options.$container.find(selector);
            },

            updateChartFormVisibility: function(options) {
                var name = this.getNameChoiceElement(options).val();
                _.each(options.blocks, function(block) {
                    this.getParentElement(options, block).hide();
                    this.getTargetElement(options, block, name).show();
                }, this);
            }
        };
    });
