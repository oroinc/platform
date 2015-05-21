define(function (require) {
    'use strict';
    var _ = require('underscore'),
        WorkflowViewerFlowchartView = require('../viewer/workflow'),
        JsplubmTransitionOverlayView = require('./transition-overlay'),
        WorkflowFlowchartView;

    WorkflowFlowchartView = WorkflowViewerFlowchartView.extend({

        transitionOverlayView: JsplubmTransitionOverlayView,

        render: function () {
            WorkflowFlowchartView.__super__.render.apply(this, arguments);

            this.$el.addClass('workflow-flowchart-editor');

            this.initCollectionViews();

            this.jsPlumbInstance.bind('beforeDrop', _.bind(function (data) {
                var transitionModel, startingSteps, suspendedStep,
                    stepFrom = this.findStepModelByElement(data.connection.source),
                    stepTo = this.findStepModelByElement(data.connection.target);
                if (data.connection.suspendedElement) {
                    transitionModel = data.connection.overlayView.model;
                    startingSteps = transitionModel.getStartingSteps();
                    console.log('old', transitionModel);
                    if (stepTo.get('name') !== transitionModel.get('step_to')) {
                        // stepTo changed
                        transitionModel.set({
                            step_to: stepTo.get('name')
                        });
                    }
                    if (startingSteps.indexOf(stepFrom) === -1) {
                        suspendedStep = this.findStepModelByElement(data.connection.suspendedElement);
                        stepFrom.getAllowedTransitions().add(transitionModel);
                        suspendedStep.getAllowedTransitions().remove(transitionModel);
                        stepFrom.trigger('change');
                        suspendedStep.trigger('change');
                    }
                } else {
                    this.model.trigger('requestAddTransition', stepFrom, stepTo);
                }
                // never allow jsplumb just draw new connections, create connection model instead
                return false;
            }, this));
        }
    });

    return WorkflowFlowchartView;
});
