define(function (require) {
    var JsplubmBoxView = require('./jsplumb/box'),
        JsplumbWorkflowStepView;

    JsplumbWorkflowStepView = JsplubmBoxView.extend({
        template: require('tpl!oroworkflow/templates/flowchart/step.html'),

        listen: {
            'change model': 'render'
        },

        events: {
            'click .workflow-step-edit': 'triggerEditStep',
            'click .workflow-step-clone': 'triggerCloneStep',
            'click .workflow-step-delete': 'triggerRemoveStep'
        },

        triggerEditStep: function (e) {
            e.preventDefault();
            this.areaView.model.trigger('requestEditStep', this.model);
        },

        triggerRemoveStep: function (e) {
            e.preventDefault();
            this.areaView.model.trigger('requestRemoveStep', this.model);
        },

        triggerCloneStep: function (e) {
            e.preventDefault();
            this.areaView.model.trigger('requestCloneStep', this.model);
        }
    });

    return JsplumbWorkflowStepView;
});
