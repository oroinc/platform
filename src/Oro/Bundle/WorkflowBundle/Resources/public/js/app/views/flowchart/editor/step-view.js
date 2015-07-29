define(function(require) {
    'use strict';

    var FlowchartEditorStepView;
    var _ = require('underscore');
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

        connect: function() {
            var instance = this.areaView.jsPlumbInstance;
            // add element as source to jsPlumb
            if (this.model.get('draggable') !== false) {
                instance.draggable(this.$el, {
                    containment: 'parent',
                    stop: _.bind(function(e) {
                        // update model position when dragging stops
                        this.model.set({position: e.pos});
                    }, this)
                });
            }
            FlowchartEditorStepView.__super__.connect.apply(this, arguments);
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
        },

        render: function() {
            FlowchartEditorStepView.__super__.render.call(this);
            this.$el.toggleClass('final-step', Boolean(this.model.get('is_final')));
        }
    });

    return FlowchartEditorStepView;
});
