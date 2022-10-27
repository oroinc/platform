define(function(require) {
    'use strict';

    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const FlowchartViewerWorkflowView = require('../viewer/workflow-view');
    const FlowChartEditorTransitionOverlayView = require('./transition-overlay-view');
    const FlowchartEditorStepView = require('./step-view');

    const FlowchartEditorWorkflowView = FlowchartViewerWorkflowView.extend({
        isConnected: false,

        transitionOverlayView: FlowChartEditorTransitionOverlayView,

        stepView: FlowchartEditorStepView,

        className: 'workflow-flowchart-editor',

        /**
         * @inheritdoc
         */
        constructor: function FlowchartEditorWorkflowView(options) {
            FlowchartEditorWorkflowView.__super__.constructor.call(this, options);
        },

        /**
         * @type {function(): Object|Object}
         */
        defaultConnectionOptions: function() {
            return {
                detachable: true
            };
        },

        connect: function() {
            FlowchartEditorWorkflowView.__super__.connect.call(this);
            this.jsPlumbInstance.bind('connectionDrag', this.onConnectionDragStart.bind(this));
            this.jsPlumbInstance.bind('connectionDragStop', this.onConnectionDragStop.bind(this));
            this.jsPlumbInstance.bind('beforeDrop', this.onBeforeConnectionDrop.bind(this));
            this.jsPlumbInstance.bind('beforeDetach', this.onBeforeConnectionDetach.bind(this));
        },

        onConnectionDragStart: function(connection) {
            $('#' + connection.sourceId).addClass('connection-source');
            this.$el.addClass('workflow-drag-connection');
        },

        onConnectionDragStop: function(connection) {
            $('#' + connection.sourceId).removeClass('connection-source');
            this.$el.removeClass('workflow-drag-connection');
        },

        onBeforeConnectionDrop: function(data) {
            let transitionModel;
            let transitionName;
            let startingSteps;
            let suspendedStep;
            const stepFrom = this.findStepModelByElement(data.connection.source);
            const stepTo = this.findStepModelByElement(data.connection.target);
            if (data.connection.suspendedElement && !stepTo.get('_is_start')) {
                transitionModel = data.connection.overlayView.model;
                transitionName = transitionModel.get('name');
                startingSteps = this.model.get('steps').filter(function(item) {
                    return item.get('allowed_transitions').indexOf(transitionName) !== -1;
                });
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
                    stepFrom.trigger('change', stepFrom, {});
                    suspendedStep.trigger('change', suspendedStep, {});
                }
            } else if (!stepTo.get('_is_start')) {
                this.model.trigger('requestAddTransition', stepFrom, stepTo);
            } else {
                mediator.execute(
                    'showFlashMessage',
                    'error',
                    __('oro.workflow.error.cannot.set.step')
                );
            }
            // never allow jsplumb just draw new connections, create connection model instead
            return false;
        },

        isConnectionInDrag: function(jsPlumbConnection) {
            return !jsPlumbConnection.endpoints[0].connections.length ||
                !jsPlumbConnection.endpoints[1].connections.length;
        },

        willConnectionChange: function(jsPlumbConnection) {
            return jsPlumbConnection[jsPlumbConnection.suspendedElementType] !== jsPlumbConnection.suspendedElement;
        },

        onBeforeConnectionDetach: function(jsPlumbConnection) {
            if (!this.isConnectionInDrag(jsPlumbConnection)) {
                return true;
            }
            if (this.willConnectionChange(jsPlumbConnection)) {
                return true;
            }
            return false;
        }
    });

    return FlowchartEditorWorkflowView;
});
