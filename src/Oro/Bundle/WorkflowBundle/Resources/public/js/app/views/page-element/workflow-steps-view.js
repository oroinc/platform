define(function(require) {
    'use strict';

    var WokflowStepsView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    WokflowStepsView = BaseView.extend({
        template: require('tpl!../../../../templates/workflow-steps-view.html'),
        listen: {
            'change model': 'render',
            'layout:reposition mediator': 'updateMaxWidth'
        },
        getTemplateData: function() {
            var data = WokflowStepsView.__super__.getTemplateData.call(this);

            if (!data.stepsData) {
                return data;
            }

            _.each(data.stepsData, function(stepData) {
                // calculated processed flag
                var processed = true;
                _.each(stepData.steps, function(step) {
                    if (step.name === stepData.currentStep.name) {
                        processed = false;
                    }
                    step.processed = processed;
                });
            });

            return data;
        },
        render: function() {
            WokflowStepsView.__super__.render.call(this);
            this.updateContainerWidth();
            this.updateMaxWidth();
        },
        updateContainerWidth: function() {
            var $container = this.$el;
            var $list = this.$('.workflow-step-container');
            $container.width(10000);
            $list.css({float: 'left'});
            $container.width($list.width() + 1/* floating pixel calculation compensation */);
            $list.css({float: 'none'});
        },
        updateMaxWidth: function() {
            this.$el.css({
                'max-width': this.$el.closest('.breadcrumb-pin').width()
            });
        }
    });

    return WokflowStepsView;
});
