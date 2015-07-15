define(function(require) {
    'use strict';

    var FlowchartEditorStepView;
    var FlowchartViewerStepView = require('../viewer/step-view');

    FlowchartEditorStepView = FlowchartViewerStepView.extend({
        template: require('tpl!oroworkflow/templates/flowchart/editor/step.html'),

        className: function() {
            var classNames = [FlowchartEditorStepView.__super__.className.call(this)];
            if (!this.model.get('_is_start')) {
                classNames.push('dropdown');
            }
            return classNames.join(' ');
        },

        events: {
            'dblclick': 'triggerEditStep',
            'click .jsplumb-source': 'triggerAddStep',
            'click .workflow-step-edit': 'triggerEditStep',
            'click .workflow-step-clone': 'triggerCloneStep',
            'click .workflow-step-delete': 'triggerRemoveStep'
        },

        triggerEditStep: function(e) {
            e.preventDefault();
            this.areaView.model.trigger('requestEditStep', this.model);
        },

        triggerRemoveStep: function(e) {
            e.preventDefault();
            this.areaView.model.trigger('requestRemoveStep', this.model);
        },

        triggerCloneStep: function(e) {
            e.preventDefault();
            this.areaView.model.trigger('requestCloneStep', this.model);
        },

        triggerAddStep: function() {
            this.areaView.model.trigger('requestAddTransition', this.model);
        }
    });

    return FlowchartEditorStepView;
});
