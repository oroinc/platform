define(function (require) {
    'use strict';

    var FlowchartEditorWorkflowView,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        mediator = require('oroui/js/mediator'),
        FlowchartViewerWorkflowView = require('../viewer/workflow-view'),
        FlowChartEditorTransitionOverlayView = require('./transition-overlay-view'),
        FlowchartEditorStepView = require('./step-view');

    FlowchartEditorWorkflowView = FlowchartViewerWorkflowView.extend({

        isConnected: false,

        transitionOverlayView: FlowChartEditorTransitionOverlayView,
        stepView: FlowchartEditorStepView,
        className: 'workflow-flowchart-editor',

        /**
         * @type {function(): Object|Object}
         */
        defaultConnectionOptions: function () {
            return {
                detachable: true
            };
        },

        connect: function () {
            FlowchartEditorWorkflowView.__super__.connect.apply(this, arguments);
            this.jsPlumbInstance.bind('connectionDrag', _.bind(this.onConnectionDragStart, this));
            this.jsPlumbInstance.bind('connectionDragStop', _.bind(this.onConnectionDragStop, this));
            this.jsPlumbInstance.bind('beforeDrop', _.bind(this.onBeforeConnectionDrop, this));
        },

        onConnectionDragStart: function (connection) {
            $('#' + connection.sourceId).addClass('connection-source');
            this.$el.addClass('workflow-drag-connection');
        },

        onConnectionDragStop: function (connection) {
            $('#' + connection.sourceId).removeClass('connection-source');
            this.$el.removeClass('workflow-drag-connection');
        },

        onBeforeConnectionDrop: function (data) {
            var transitionModel, startingSteps, suspendedStep,
                stepFrom = this.findStepModelByElement(data.connection.source),
                stepTo = this.findStepModelByElement(data.connection.target);
            if (data.connection.suspendedElement && !stepTo.get('_is_start')) {
                transitionModel = data.connection.overlayView.model;
                startingSteps = transitionModel.getStartingSteps();
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
            } else if (!stepTo.get('_is_start')) {
                this.model.trigger('requestAddTransition', stepFrom, stepTo);
            } else {
                mediator.execute(
                    'showFlashMessage',
                    'error',
                    __(
                        'Cannot set end step to <i>(Start)</i> step. Please select another one'
                    )
                );
            }
            // never allow jsplumb just draw new connections, create connection model instead
            return false;
        }
    });

    return FlowchartEditorWorkflowView;
});
