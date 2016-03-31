define(function(require) {
    'use strict';

    var WokflowStepsView;
    var BaseView = require('oroui/js/app/views/base/view');

    WokflowStepsView = BaseView.extend({
        template: require('tpl!../../../../templates/workflow-steps-view.html'),
        listen: {
            'change model': 'render'
        },
        getTemplateData: function() {
            var data = WokflowStepsView.__super__.getTemplateData.call(this);
            if (!data.steps || !data.steps.length) {
                return data;
            }
            // calculated processed flag
            var processed = true;
            for (var i = 0; i < data.steps.length; i++) {
                var step = data.steps[i];
                if (step.name === data.currentStep.name) {
                    processed = false;
                }
                step.processed = processed;
            }
            return data;
        }
    });

    return WokflowStepsView;
});
