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
        defaults: {
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
         * @param {Array} options
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.defaults, options, {
                baseName: this.$el.data('ftid'),
                $container: this.$el
            });

            this.updateChartFormVisibility();
        },

        delegateEvents: function(events) {
            ChartFormView.__super__.undelegateEvents.call(this, events);
            this.$choiceElement.on('change' + this.eventNamespace(), this.updateChartFormVisibility.bind(this));
        },

        undelegateEvents: function() {
            this.$choiceElement.off(this.eventNamespace());
            ChartFormView.__super__.undelegateEvents.call(this);
        },

        getNameChoiceElement: function() {
            var options = this.options;
            var selector = options.templates.name({
                baseName: options.baseName
            });

            return this.$el.find(selector);
        },

        getParentElement: function(block) {
            var options = this.options;
            var selector = options.templates.parent({
                baseName: options.baseName,
                block: block
            });

            return this.$el.find(selector);
        },

        getTargetElement: function(block, chart) {
            var options = this.options;
            var selector = options.templates.target({
                baseName: options.baseName,
                block: block,
                chart: chart
            });

            return this.$el.find(selector);
        },

        updateChartFormVisibility: function() {
            var options = this.options;
            var name = this.getNameChoiceElement().val();

            _.each(options.blocks, function(block) {
                this.getParentElement(block).hide();
                this.getTargetElement(block, name).show();
            }, this);
        },

        constructor: function(options) {
            var $el = $(options.el);

            this.$choiceElement = $el.find(this.defaults.templates.name({baseName: $el.data('ftid')}));
            ChartFormView.__super__.constructor.apply(this, arguments);
        },

        dispose: function() {
            delete this.options;
            delete this.$choiceElement;

            ChartFormView.__super__.dispose.apply(this, arguments);
        }
    });

    return ChartFormView;
});
