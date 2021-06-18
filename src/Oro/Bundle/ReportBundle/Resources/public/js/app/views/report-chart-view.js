define(function(request) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = request('jquery');
    const _ = request('underscore');

    const ReportChartView = BaseView.extend({
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
         * @inheritdoc
         */
        constructor: function ReportChartView(options) {
            const $el = $(options.el);

            this.$choiceElement = $el.find(this.defaults.templates.name({baseName: $el.data('ftid')}));
            ReportChartView.__super__.constructor.call(this, options);
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
            ReportChartView.__super__.undelegateEvents.call(this, events);
            this.$choiceElement.on('change' + this.eventNamespace(), this.updateChartFormVisibility.bind(this));
        },

        undelegateEvents: function() {
            if (this.$choiceElement) {
                this.$choiceElement.off(this.eventNamespace());
            }
            ReportChartView.__super__.undelegateEvents.call(this);
        },

        getNameChoiceElement: function() {
            const options = this.options;
            const selector = options.templates.name({
                baseName: options.baseName
            });

            return this.$el.find(selector);
        },

        getParentElement: function(block) {
            const options = this.options;
            const selector = options.templates.parent({
                baseName: options.baseName,
                block: block
            });

            return this.$el.find(selector);
        },

        getTargetElement: function(block, chart) {
            const options = this.options;
            const selector = options.templates.target({
                baseName: options.baseName,
                block: block,
                chart: chart
            });

            return this.$el.find(selector);
        },

        updateChartFormVisibility: function() {
            const options = this.options;
            const name = this.getNameChoiceElement().val();

            _.each(options.blocks, function(block) {
                this.getParentElement(block).hide();
                this.getTargetElement(block, name).show();
            }, this);
        },

        dispose: function() {
            delete this.options;
            delete this.$choiceElement;

            ReportChartView.__super__.dispose.call(this);
        }
    });

    return ReportChartView;
});
