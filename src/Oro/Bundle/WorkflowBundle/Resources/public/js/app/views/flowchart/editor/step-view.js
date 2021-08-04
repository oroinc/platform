define(function(require) {
    'use strict';

    const FlowchartViewerStepView = require('../viewer/step-view');

    const FlowchartEditorStepView = FlowchartViewerStepView.extend({
        template: require('tpl-loader!oroworkflow/templates/flowchart/editor/step.html'),

        className: function() {
            const classNames = [FlowchartEditorStepView.__super__.className.call(this)];
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

        /**
         * @inheritdoc
         */
        constructor: function FlowchartEditorStepView(options) {
            FlowchartEditorStepView.__super__.constructor.call(this, options);
        },

        connect: function() {
            const instance = this.areaView.jsPlumbInstance;
            // add element as source to jsPlumb
            if (this.model.get('draggable') !== false) {
                instance.draggable(this.$el, {
                    start: function(obj) {
                        instance.eventBus.trigger('step:drag-start', obj, this.model);
                    }.bind(this),
                    stop: function(obj) {
                        // update model position when dragging stops
                        this.model.set({position: obj.pos});
                        instance.eventBus.trigger('step:drag-stop', obj, this.model);
                    }.bind(this)
                });
            }
            FlowchartEditorStepView.__super__.connect.call(this);
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
