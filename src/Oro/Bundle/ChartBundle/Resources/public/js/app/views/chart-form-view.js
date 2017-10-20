define(function(request) {
    'use strict';

    var ChartFormView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = request('jquery');
    var _ = request('underscore');

    /**
     * @export orochart/js/chart_form
     * @class  orochart.ChartForm
     */
    ChartFormView = BaseView.extend({
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
        initialize: function(options) {
            options = $.extend(true, {}, this.options, options, {
                baseName: this.$el.data('ftid'),
                $container: this.$el
            });

            this.updateChartFormVisibility(options);
            this.getNameChoiceElement(options).on('change', _.bind(this.updateChartFormVisibility, this, options));
        },

        getNameChoiceElement: function(options) {
            var selector = options.templates.name({
                baseName: options.baseName
            });

            return this.$el.find(selector);
        },

        getParentElement: function(options, block) {
            var selector = options.templates.parent({
                baseName: options.baseName,
                block: block
            });

            return this.$el.find(selector);
        },

        getTargetElement: function(options, block, chart) {
            var selector = options.templates.target({
                baseName: options.baseName,
                block: block,
                chart: chart
            });

            return this.$el.find(selector);
        },

        updateChartFormVisibility: function(options) {
            var name = this.getNameChoiceElement(options).val();

            _.each(options.blocks, function(block) {
                this.getParentElement(options, block).hide();
                this.getTargetElement(options, block, name).show();
            }, this);
        }
    });

    return ChartFormView;
});
